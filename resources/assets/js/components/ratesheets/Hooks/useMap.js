import React, {useEffect, useState, useRef} from 'react'
import PolySnapper from '../../../public/polysnapper-master/polysnapper.js'
import Zone, {polyColours} from '../Classes/Zone'
import spatialIndex from '../utils/spatialIndex.js'

var nextPolygonIndex = 0;

export default function useMap() {
    const [defaultZoneType, setDefaultZoneType] = useState('internal')
    const [drawingMap, setDrawingMap] = useState(0)
    const [editZoneZIndex, setEditZoneZIndex] = useState(null)
    const [latLngPrecision, setLatLngPrecision] = useState(5)
    const [map, setMap] = useState(undefined)
    const [mapCenter, setMapCenter] = useState(new google.maps.LatLng(53.544389, -113.4909266))
    const [drawingManager, setDrawingManager] = useState(undefined)
    const [mapZones, setMapZones] = useState([])
    const [mapZoom, setMapZoom] = useState(11)
    const [savingMap, setSavingMap] = useState(100)
    const [snapTolerance, setSnapTolerance] = useState(2) // meters
    const polySnapperRef = useRef(null)
    const copyBordersTimeoutRef = useRef(null)
    const isCopyingBordersRef = useRef(false)

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

    // enable poly snapper when drawing mode changes on DrawingManager
    useEffect(() => {
        if(!drawingManager || !map) return
        try{
            // ensure PolySnapper instance exists
            polySnapperRef.current = new PolySnapper({ map: map, threshold: snapTolerance * 20, polygons: mapZones.map(mapZone => mapZone.polygon), hidePOI: true })
            polySnapperRef.current._map = map
            drawingManager.addListener('drawingmode_changed', () => {
                const mode = drawingManager.getDrawingMode()
                if(mode === window.google.maps.drawing.OverlayType.POLYGON){
                    try{ polySnapperRef.current.enable() } catch(e){}
                } else {
                    try{ polySnapperRef.current.disable() } catch(e){}
                }
            })
        } catch(e){ console.error('DrawingManager hook error', e) }
    }, [drawingManager, map, mapZones, snapTolerance])

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
                additionalCosts: {regular: '', rush: '', direct: '', direct_rush: ''},
                additionalTime: 0,
                name: `${defaultZoneType}_zone_${zIndex}`,
                neighbours: [],
                type: defaultZoneType,
                zoneId: null,
            }
        } else {
            if(mapZone.zoneId === undefined && mapZone.zone_id !== undefined)
                mapZone.zoneId = mapZone.zone_id
            if(typeof mapZone.coordinates === 'string')
                mapZone.coordinates = JSON.parse(mapZone.coordinates)

            if(mapZone.additionalCosts === undefined) {
                mapZone.additionalCosts = mapZone.additional_costs ? JSON.parse(mapZone.additional_costs) : {regular: '', rush: '', direct: '', direct_rush: ''}
            }
            mapZone.additionalTime = parseFloat(mapZone.additional_time ?? mapZone.additionalTime ?? 0)
        }

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
        setMapZones(prevMapZones => prevMapZones.concat(newZone))
        // add to spatial index for snapping queries
        try{ spatialIndex.addZone(newZone) } catch(e){ console.error('spatialIndex.addZone failed', e) }
        if(polygon)
            setEditZoneZIndex(zIndex)
    }

    const deleteZone = (zIndex) => {
        const deleteZone = mapZones.find(zone => zone.polygon.zIndex == zIndex)
        if(confirm(`Are you sure you wish to delete zone "${deleteZone.name}"?\n This action can not be undone`)) {
            // remove from spatial index then delete
            try{ spatialIndex.removeZone(deleteZone) } catch(e){ /* ignore */ }
            deleteZone.delete()
            setMapZones(mapZones.filter(zone => zone.polygon.zIndex != zIndex))
        }
    }

    const handleZoneChange = (event, zIndex) => {
        const {name, value} = event.target
        const updated = mapZones.map(zone => {
            if(zone.polygon.zIndex == zIndex) {
                if(name.includes('additionalCosts.')) {
                    zone.additionalCosts[name.split('.')[1]] = value
                }
                else if(name == 'type') {
                    const fillColour = polyColours[`${value}Fill`]
                    const strokeColour = polyColours[`${value}Stroke`]
                    zone.polygon.setOptions({strokeColor: strokeColour, fillColor: fillColour})
                    zone.type = value
                    zone.fillColour = fillColour
                    zone.strokeColour = strokeColour
                } else
                    zone[name] = value
                return zone
            }
            return zone
        })
        setMapZones(updated)
    }

    return {
        createZone,
        defaultZoneType,
        deleteZone,
        drawingManager,
        drawingMap,
        editZoneZIndex,
        handleZoneChange,
        latLngPrecision,
        map,
        mapCenter,
        mapZones,
        mapZoom,
        savingMap,
        setDefaultZoneType,
        setDrawingMap,
        setDrawingManager,
        setEditZoneZIndex,
        setLatLngPrecision,
        setMap,
        setMapCenter,
        setMapZones,
        setMapZoom,
        setSavingMap,
        setSnapTolerance,
        snapTolerance,
    }
}

