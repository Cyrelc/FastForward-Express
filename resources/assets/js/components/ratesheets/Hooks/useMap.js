import React, {useEffect, useState, useRef} from 'react'
import PolySnapper from '../../../public/polysnapper-master/polysnapper.js'
import Zone, {DEFAULT_ZONE_COLOUR, darkenColour, resolveZoneColour} from '../Classes/Zone'
import spatialIndex from '../utils/spatialIndex.js'
import { TerraDraw, TerraDrawPolygonMode, TerraDrawSelectMode } from 'terra-draw'
import { TerraDrawGoogleMapsAdapter } from 'terra-draw-google-maps-adapter'

var nextPolygonIndex = 0;

// The snap tolerance is a distance on the ground, but it has to survive being looked at through a
// zoom level, and at the zoom the map opens at (11) a 2m radius is about 1/25th of a pixel - no
// cursor can ever land inside it. So the tolerance is converted to pixels at the current zoom and
// held inside this band: the floor keeps snapping reachable when zoomed out, the ceiling stops a
// deeply zoomed-in cursor from grabbing a vertex halfway across the screen.
const MIN_SNAP_RADIUS_PIXELS = 6
const MAX_SNAP_RADIUS_PIXELS = 20

export default function useMap() {
    // A newly drawn zone joins this group (if any) — the closest thing left to the old "default zone
    // type", now that type is gone and groups are what actually matter.
    const [defaultZoneGroupId, setDefaultZoneGroupId] = useState(null)
    const [zoneGroups, setZoneGroups] = useState([])
    // Zones and groups save individually, so the map tracks what is unsaved rather than blindly
    // pushing everything: which zones changed, which groups changed, and which saved zones the user
    // deleted (deletion is an explicit call now, not "absent from the payload").
    const [pendingZoneDeletions, setPendingZoneDeletions] = useState([])
    const [dirtyZoneGroupIds, setDirtyZoneGroupIds] = useState([])
    const [dirtyTick, setDirtyTick] = useState(0)
    const [drawingMap, setDrawingMap] = useState(0)
    const [editZoneZIndex, setEditZoneZIndex] = useState(null)
    const [latLngPrecision, setLatLngPrecision] = useState(5)
    const [map, setMap] = useState(undefined)
    const [mapCenter, setMapCenter] = useState(new google.maps.LatLng(53.544389, -113.4909266))
    const [isDrawingMode, setIsDrawingMode] = useState(false)
    const [mapZones, setMapZones] = useState([])
    const [mapZoom, setMapZoom] = useState(11)
    const [savingMap, setSavingMap] = useState(100)
    const [snapTolerance, setSnapTolerance] = useState(2) // meters
    const polySnapperRef = useRef(null)
    const terraDrawRef = useRef(null)
    const createZoneRef = useRef(null)
    const defaultZoneGroupIdRef = useRef(defaultZoneGroupId)
    const zoneGroupsRef = useRef(zoneGroups)
    const mapRef = useRef(null)
    const copyBordersTimeoutRef = useRef(null)
    const isCopyingBordersRef = useRef(false)
    const snapToleranceRef = useRef(snapTolerance)

    // Keep refs in sync so TerraDraw callbacks always have the latest values
    useEffect(() => { defaultZoneGroupIdRef.current = defaultZoneGroupId }, [defaultZoneGroupId])
    useEffect(() => { zoneGroupsRef.current = zoneGroups }, [zoneGroups])
    useEffect(() => { mapRef.current = map }, [map])
    // TerraDraw is built once, so the snap callback reads the tolerance from a ref rather than
    // closing over the value it happened to be constructed with.
    useEffect(() => { snapToleranceRef.current = snapTolerance }, [snapTolerance])

    /**
     * Snapping for a zone being drawn.
     *
     * PolySnapper cannot do this job. It snaps by intercepting set_at/insert_at on an existing
     * google.maps.Polygon path, so it only has something to listen to once a zone exists - which is
     * why snapping used to work while editing and not while drawing. TerraDraw owns the pointer
     * during a draw and asks this hook, on every hover and every click, whether the cursor should be
     * pulled onto an existing point. We answer from the same spatial index PolySnapper queries when
     * editing, so a drawn border lands on its neighbour's vertices and the two zones share them.
     *
     * Returns the snapped [lng, lat], or undefined to leave the cursor where it is.
     */
    const snapToNearbyVertex = (event, {project, unproject}) => {
        const cursor = new google.maps.LatLng(event.lat, event.lng)

        // What one pixel is worth on the ground here and now, which is what lets the tolerance be
        // expressed in metres and still be reachable at any zoom.
        const cursorPoint = project(event.lng, event.lat)
        const onePixelOver = unproject(cursorPoint.x + 1, cursorPoint.y)
        const metresPerPixel = google.maps.geometry.spherical.computeDistanceBetween(
            cursor,
            new google.maps.LatLng(onePixelOver.lat, onePixelOver.lng),
        )
        if(!metresPerPixel)
            return undefined

        const tolerance = snapToleranceRef.current || 2
        const radiusInPixels = Math.min(
            MAX_SNAP_RADIUS_PIXELS,
            Math.max(MIN_SNAP_RADIUS_PIXELS, tolerance / metresPerPixel),
        )
        const radius = radiusInPixels * metresPerPixel

        let closest = null
        let closestDistance = Infinity
        for(const candidate of spatialIndex.queryNearby(event.lat, event.lng, radius)) {
            const distance = google.maps.geometry.spherical.computeDistanceBetween(
                cursor,
                new google.maps.LatLng(candidate.lat, candidate.lng),
            )
            if(distance <= radius && distance < closestDistance) {
                closestDistance = distance
                closest = candidate
            }
        }

        return closest ? [closest.lng, closest.lat] : undefined
    }

    useEffect(() => {
        const activeZone = mapZones.find(zone => zone.polygon.zIndex == editZoneZIndex)
        if(!activeZone)
            return
        mapZones.forEach(zone => {
            if(zone.polygon.zIndex === editZoneZIndex)
                zone.edit()
            else {
                zone.neighbourLabel.setMap(activeZone.getCommonCoordinates(zone).length ? map : null)
                zone.polygon.setOptions({editable: false, snapable: true})
            }
        })
        // ensure persistent PolySnapper exists and is configured
        try{
            if(!polySnapperRef.current || polySnapperRef.current._map !== map) {
                polySnapperRef.current = new PolySnapper({ map: map, threshold: snapTolerance * 40, polygons: mapZones.map(mapZone => mapZone.polygon), hidePOI: true })
                polySnapperRef.current._map = map
            } else {
                // recreate to update polygons/threshold
                polySnapperRef.current = new PolySnapper({ map: map, threshold: snapTolerance * 40, polygons: mapZones.map(mapZone => mapZone.polygon), hidePOI: true })
                polySnapperRef.current._map = map
            }
            polySnapperRef.current.enable(activeZone.polygon.zIndex)
        } catch(e){ console.error('PolySnapper init error', e) }
    }, [editZoneZIndex])

    // expose spatialIndex to PolySnapper via global window for use in the public script
    useEffect(() => {
        try{ if(typeof window !== 'undefined') window.spatialIndex = spatialIndex } catch(e){}
    }, [spatialIndex])

    // Initialize TerraDraw once the map is ready.
    //
    // "Ready" means idle, not loaded. The Google Maps adapter binds its pointer listeners to
    // `map.getDiv().querySelector('.gm-style > div')`, and that element does not exist yet when
    // onLoad fires - starting there throws ("Cannot read properties of null"), which leaves
    // terraDrawRef null and silently kills the Draw Zone button.
    useEffect(() => {
        if(!map) return

        // Destroy any existing instance before recreating
        if(terraDrawRef.current) {
            try{ terraDrawRef.current.stop() } catch(e){}
            terraDrawRef.current = null
        }

        const initialise = () => {
            try{
                const draw = new TerraDraw({
                    adapter: new TerraDrawGoogleMapsAdapter({ map, lib: google.maps }),
                    modes: [
                        new TerraDrawPolygonMode({snapping: {toCustom: snapToNearbyVertex}}),
                        new TerraDrawSelectMode(),
                    ],
                })
                draw.start()

                draw.on('finish', (id, context) => {
                    if(context.action !== 'draw') return

                    const features = draw.getSnapshot()
                    const feature = features.find(f => f.id === id)
                    if(!feature) return

                    // Convert TerraDraw GeoJSON coordinates to google.maps.LatLng paths
                    const coords = feature.geometry.coordinates[0]
                    const paths = coords.slice(0, -1).map(([lng, lat]) => ({ lat, lng }))

                    // Remove TerraDraw's own rendering before we hand off to createZone
                    draw.removeFeatures([id])
                    draw.setMode('static')
                    setIsDrawingMode(false)

                    // A new zone has no colour of its own; it shows its default group's colour.
                    const defaultGroup = (zoneGroupsRef.current ?? [])
                        .find(zoneGroup => zoneGroup.zoneGroupId === defaultZoneGroupIdRef.current)
                    const fillColour = defaultGroup?.colour ?? DEFAULT_ZONE_COLOUR
                    const polygon = new google.maps.Polygon({
                        paths,
                        map: mapRef.current,
                        clickable: true,
                        editable: true,
                        fillColor: fillColour,
                        strokeColor: darkenColour(fillColour),
                    })

                    createZoneRef.current(polygon)
                })

                terraDrawRef.current = draw
            } catch(e){ console.error('TerraDraw init error', e) }
        }

        // The map may already be idle by the time this runs (e.g. a re-render), in which case 'idle'
        // would not fire again - so check for the adapter's element first and only wait if it is
        // genuinely not there yet.
        const isRendered = () => !!map.getDiv()?.querySelector('.gm-style > div')

        if(isRendered()) {
            initialise()
            return
        }

        const idleListener = google.maps.event.addListenerOnce(map, 'idle', initialise)

        return () => {
            google.maps.event.removeListener(idleListener)
        }
    }, [map])

    // Drawing no longer touches PolySnapper. enable() with no zIndex hit its "not currently supported"
    // branch, which put a phantom editable polygon on the map and hijacked the map div's mousemove
    // without ever snapping a drawn point; the matching disable() then called setMap(null) on whatever
    // polygon happened to be current - a real zone, if one had been edited first. Snapping while
    // drawing is TerraDraw's job now (see snapToNearbyVertex).
    const startDrawing = () => {
        if(!terraDrawRef.current) return
        try{
            terraDrawRef.current.setMode('polygon')
        } catch(e){ console.error('startDrawing error', e) }
        setIsDrawingMode(true)
    }

    const stopDrawing = () => {
        if(!terraDrawRef.current) return
        try{
            terraDrawRef.current.setMode('static')
        } catch(e){ console.error('stopDrawing error', e) }
        setIsDrawingMode(false)
    }

    // const handleZoneTypeChange = (event, zIndex) => {
    //     const {value} = event.target
    //     const strokeColour = polyColours[`${value}Stroke`]
    //     const fillColour = polyColours[`${value}Fill`]

    //     const updated = mapState.mapZones.map(mapZone => {
    //         if(mapZone.zIndex == zIndex) {
    //             mapZone.polygon.setOptions({strokeColor: strokeColour, fillColor: fillColour})
    //             return {...mapZone, type: value}
    //         }
    //         return mapZone
    //     })
    //     mapState.setMapZones(updated)
    // }

    // createPolygon(polygon, zone = null) {
    //     polygon.addListener('click', () => this.editZone(polygon.zIndex))
    //     google.maps.event.addListener(polygon, 'rightclick', (point) => this.deletePolyPoint(point, polygon.zIndex));
    //     const name = zone ? zone.name : this.state.defaultZoneType + '_zone_' + polygon.zIndex
    //     const position = polylabel([coordinates])
    //     var newZone = {
    //         id: polygon.zIndex,
    //         name : name,
    //         polygon: polygon,
    //         zoneId: zone ? zone.zone_id : null,
    //     }
    //     if(type === 'peripheral') {
    //         const cost = zone ? JSON.parse(zone.additional_costs) : null
    //         newZone.regularCost = cost ? cost.regular : ''
    //         newZone.additionalTime = zone ? zone.additional_time : ''
    //     } else if (type === 'outlying') {
    //         const costs = zone ? JSON.parse(zone.additional_costs) : null
    //         newZone.regularCost = costs ? costs.regular : ''
    //         newZone.rushCost = costs ? costs.rush : ''
    //         newZone.directCost = costs ? costs.direct : ''
    //         newZone.directRushCost = costs ? costs.direct_rush : ''
    //         newZone.additionalTime = zone ? zone.additional_time : ''
    //     }
    //     this.setState((prevState, props) => ({mapZones: prevState.mapZones.concat(newZone)}))
    // }
    const createZone = (polygon = null, mapZone = null) => {
        const zIndex = nextPolygonIndex++
        if(!mapZone) {
            mapZone = {
                colour: null,
                deliveryTypeModifiers: {},
                groupIds: defaultZoneGroupId ? [defaultZoneGroupId] : [],
                name: `zone_${zIndex}`,
                neighbours: [],
                tags: [],
                zoneId: null,
            }
        } else {
            if(mapZone.zoneId === undefined && mapZone.zone_id !== undefined)
                mapZone.zoneId = mapZone.zone_id
            if(typeof mapZone.coordinates === 'string')
                mapZone.coordinates = JSON.parse(mapZone.coordinates)

            if(mapZone.deliveryTypeModifiers === undefined) {
                mapZone.deliveryTypeModifiers = mapZone.delivery_type_modifiers
                    ? JSON.parse(mapZone.delivery_type_modifiers)
                    : {}
            }
            if(Array.isArray(mapZone.tags)) {
                mapZone.tags = mapZone.tags.map(tag => `${tag}`.trim()).filter(tag => !!tag)
            } else if(typeof mapZone.tags === 'string') {
                mapZone.tags = mapZone.tags.split('|').map(tag => tag.trim()).filter(tag => !!tag)
            } else {
                mapZone.tags = []
            }

            if(mapZone.groupIds === undefined)
                mapZone.groupIds = (mapZone.group_ids ?? []).map(groupId => parseInt(groupId, 10))
        }

        // Resolve the zone's fill from its own colour, else its first group's.
        mapZone.zoneGroups = zoneGroups

        mapZone.polygon = polygon ?? new google.maps.Polygon({
            paths: mapZone.coordinates.map(coord => {return {lat: parseFloat(coord.lat), lng: parseFloat(coord.lng)}}),
            map: map,
            zIndex: zIndex
        })
        if(polygon)
            mapZone.polygon.setOptions({zIndex: zIndex})
        // we add the edit listener here so we can access this setState function
        mapZone.polygon.addListener('click', () => setEditZoneZIndex(zIndex))
        const newZone = new Zone(mapZone)
        registerDirtyListener(newZone)
        setMapZones(prevMapZones => prevMapZones.concat(newZone))
        // add to spatial index for snapping queries
        try{ spatialIndex.addZone(newZone) } catch(e){ console.error('spatialIndex.addZone failed', e) }
        if(polygon)
            setEditZoneZIndex(zIndex)
    }
    createZoneRef.current = createZone

    const deleteZone = (zIndex) => {
        const deleteZone = mapZones.find(zone => zone.polygon.zIndex == zIndex)
        if(confirm(`Are you sure you wish to delete zone "${deleteZone.name}"?\n This action can not be undone`)) {
            // remove from spatial index then delete
            try{ spatialIndex.removeZone(deleteZone) } catch(e){ /* ignore */ }
            deleteZone.delete()

            // Deletion is explicit now: a zone that exists server-side is queued for a DELETE call on
            // save. Dropping it from the payload no longer removes it (that implicit behaviour is what
            // used to let a bad payload wipe a map), so it must be asked for by name.
            if(deleteZone.zoneId)
                setPendingZoneDeletions(pending => pending.concat(deleteZone.zoneId))

            setMapZones(mapZones.filter(zone => zone.polygon.zIndex != zIndex))
        }
    }

    const handleZoneChange = (event, zIndex) => {
        const {name, value} = event.target
        const updated = mapZones.map(zone => {
            if(zone.polygon.zIndex == zIndex) {
                if(name == 'deliveryTypeModifiers') {
                    zone.deliveryTypeModifiers = value ?? {}
                }
                else if(name == 'tags') {
                    zone.tags = Array.isArray(value) ? value : []
                }
                else if(name == 'colour') {
                    zone.colour = value || null
                    zone.applyColour(zoneGroups)
                }
                else if(name == 'groupIds') {
                    zone.groupIds = Array.isArray(value) ? value : []
                    // Membership can change which colour the zone inherits when it has none of its own.
                    zone.applyColour(zoneGroups)
                } else
                    zone[name] = value

                zone.markDirty()
                return zone
            }
            return zone
        })
        setMapZones(updated)
        setDirtyTick(tick => tick + 1)
    }

    const dirtyZones = mapZones.filter(zone => zone.isDirty)
    const hasUnsavedChanges = dirtyZones.length > 0
        || pendingZoneDeletions.length > 0
        || dirtyZoneGroupIds.length > 0

    /**
     * Zones mutate outside React (a dragged vertex writes straight to the Google Maps path, and
     * match() rewrites its neighbours' paths). Zone.onDirty pokes this counter so the UI can re-render
     * its unsaved markers when that happens.
     */
    const registerDirtyListener = (zone) => {
        zone.onDirty = () => setDirtyTick(tick => tick + 1)
    }

    const markZoneGroupDirty = (zoneGroupId) => {
        setDirtyZoneGroupIds(ids => ids.includes(zoneGroupId) ? ids : ids.concat(zoneGroupId))
    }

    /**
     * Saves one zone group. A group created in the browser carries a negative placeholder id until it
     * is saved; the real id from the server replaces it here, and every zone that referenced the
     * placeholder is repointed, so zones can be saved afterwards with an id the server recognises.
     */
    const saveZoneGroup = async (api, ratesheetId, zoneGroup) => {
        const isNew = zoneGroup.zoneGroupId < 0
        const endpoint = isNew
            ? `/ratesheets/${ratesheetId}/zone-group`
            : `/ratesheets/${ratesheetId}/zone-group/${zoneGroup.zoneGroupId}`

        const response = await api.post(endpoint, {zoneGroup})
        const savedId = response.zone_group_id

        if(isNew) {
            const placeholderId = zoneGroup.zoneGroupId
            setZoneGroups(groups => groups.map(group =>
                group.zoneGroupId === placeholderId ? {...group, zoneGroupId: savedId} : group
            ))
            mapZones.forEach(zone => {
                if((zone.groupIds ?? []).includes(placeholderId))
                    zone.groupIds = zone.groupIds.map(groupId => groupId === placeholderId ? savedId : groupId)
            })
            if(defaultZoneGroupId === placeholderId)
                setDefaultZoneGroupId(savedId)
        }

        setDirtyZoneGroupIds(ids => ids.filter(id => id !== zoneGroup.zoneGroupId))

        return savedId
    }

    /**
     * Saves one zone. The server's zone_id is written back so a newly drawn zone stops looking new -
     * without this, saving twice would insert it twice.
     */
    const saveZone = async (api, ratesheetId, zone) => {
        const endpoint = zone.zoneId
            ? `/ratesheets/${ratesheetId}/zone/${zone.zoneId}`
            : `/ratesheets/${ratesheetId}/zone`

        const response = await api.post(endpoint, {zone: zone.collect()})

        zone.zoneId = response.zone_id
        zone.markClean()
        setDirtyTick(tick => tick + 1)

        return response.zone_id
    }

    /**
     * Saves everything the map has outstanding, and only that: changed groups, then changed zones,
     * then deletions. Groups go first because a zone may reference a group that does not exist
     * server-side yet.
     *
     * Sequential rather than parallel: each zone save recomputes adjacency against the zones already
     * in the database, and firing them at once would race on each other's neighbour rows.
     */
    const saveMap = async (api, ratesheetId) => {
        for(const zoneGroup of zoneGroups.filter(group => dirtyZoneGroupIds.includes(group.zoneGroupId)))
            await saveZoneGroup(api, ratesheetId, zoneGroup)

        for(const zone of mapZones.filter(zone => zone.isDirty))
            await saveZone(api, ratesheetId, zone)

        for(const zoneId of pendingZoneDeletions)
            await api.delete(`/ratesheets/${ratesheetId}/zone/${zoneId}`)

        setPendingZoneDeletions([])
    }

    const deleteZoneGroup = async (api, ratesheetId, zoneGroupId) => {
        // A group that was never saved only exists in the browser, so there is nothing to delete.
        if(zoneGroupId > 0)
            await api.delete(`/ratesheets/${ratesheetId}/zone-group/${zoneGroupId}`)

        setZoneGroups(groups => groups.filter(group => group.zoneGroupId !== zoneGroupId))
        setDirtyZoneGroupIds(ids => ids.filter(id => id !== zoneGroupId))

        mapZones.forEach(zone => {
            if((zone.groupIds ?? []).includes(zoneGroupId)) {
                zone.groupIds = zone.groupIds.filter(groupId => groupId !== zoneGroupId)
                zone.applyColour(zoneGroups)
            }
        })

        if(defaultZoneGroupId === zoneGroupId)
            setDefaultZoneGroupId(null)
    }

    return {
        createZone,
        defaultZoneGroupId,
        setDefaultZoneGroupId,
        zoneGroups,
        setZoneGroups,
        deleteZone,
        deleteZoneGroup,
        dirtyZones,
        dirtyZoneGroupIds,
        hasUnsavedChanges,
        markZoneGroupDirty,
        pendingZoneDeletions,
        saveMap,
        saveZone,
        saveZoneGroup,
        drawingMap,
        editZoneZIndex,
        handleZoneChange,
        isDrawingMode,
        latLngPrecision,
        map,
        mapCenter,
        mapZones,
        mapZoom,
        savingMap,
        setDrawingMap,
        setEditZoneZIndex,
        setLatLngPrecision,
        setMap,
        setMapCenter,
        setMapZones,
        setMapZoom,
        setSavingMap,
        setSnapTolerance,
        snapTolerance,
        startDrawing,
        stopDrawing,
    }
}

