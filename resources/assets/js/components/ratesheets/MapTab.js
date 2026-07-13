import React, {useMemo, useState, useEffect} from 'react'
import {Button, Card, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'
import {randomZoneColour} from './Classes/Zone'
import MapZone from './MapZone'
import ZoneGroupModal from './ZoneGroupModal'
import ZoneGroupsPanel from './ZoneGroupsPanel'
import Select from 'react-select'
import {menuPortalProps} from './selectMenuPortal'
import {GoogleMap} from '@react-google-maps/api'
import topoSimplify from './utils/topoSimplify.js'
import { toast } from 'react-toastify'
import {useAPI} from '../../contexts/APIContext'

// The map and the control column are both pinned to this, so neither can push the other off screen.
const MAP_HEIGHT = '85vh'

export default function MapTab(props) {
    const api = useAPI()
    const {
        createZone,
        defaultZoneGroupId,
        setDefaultZoneGroupId,
        zoneGroups,
        setZoneGroups,
        deleteZone,
        deleteZoneGroup: deleteZoneGroupFromMap,
        hasUnsavedChanges,
        dirtyZones,
        dirtyZoneGroupIds,
        markZoneGroupDirty,
        pendingZoneDeletions,
        saveZone: saveZoneToServer,
        editZoneZIndex,
        handleZoneEdit,
        isDrawingMode,
        mapCenter,
        mapZones,
        mapZoom,
        setDrawingMap,
        setEditZoneZIndex,
        setMap,
        setSnapTolerance,
        snapTolerance,
        startDrawing,
        stopDrawing,
    } = props.mapState

    const {deliveryTypes, ratesheetId} = props

    const [simplifyMode, setSimplifyMode] = useState('topo') // 'topo' or 'quick'
    const [tolerance, setTolerance] = useState(snapTolerance || 2) // meters - used for both snapping and simplification
    const [showSettings, setShowSettings] = useState(false)
    const [singleZoneOnly, setSingleZoneOnly] = useState(true)
    const [refreshKey, setRefreshKey] = useState(0)
    const [isProcessing, setIsProcessing] = useState(false)
    const [lastUndoSnapshot, setLastUndoSnapshot] = useState(null)
    const [lastUndoLabel, setLastUndoLabel] = useState('')
    const [editZoneGroupId, setEditZoneGroupId] = useState(null)

    const zoneGroupOptions = useMemo(() => {
        return (zoneGroups ?? []).map(zoneGroup => ({value: zoneGroup.zoneGroupId, label: zoneGroup.name}))
    }, [zoneGroups])

    const editedZoneGroup = (zoneGroups ?? []).find(zoneGroup => zoneGroup.zoneGroupId === editZoneGroupId) ?? null

    // A group created here has no id until it is saved, so it gets a negative placeholder that the
    // zones reference; the server maps it to the real id on save.
    const createZoneGroup = (name) => {
        const nextId = Math.min(0, ...(zoneGroups ?? []).map(zoneGroup => zoneGroup.zoneGroupId)) - 1
        const zoneGroup = {
            zoneGroupId: nextId,
            name: name || `Group ${(zoneGroups ?? []).length + 1}`,
            colour: randomZoneColour(),
            zoneRates: [],
        }
        setZoneGroups([...(zoneGroups ?? []), zoneGroup])
        setDefaultZoneGroupId(nextId)
        markZoneGroupDirty(nextId)
        setEditZoneGroupId(nextId)
    }

    const updateZoneGroup = (updated) => {
        setZoneGroups((zoneGroups ?? []).map(zoneGroup =>
            zoneGroup.zoneGroupId === updated.zoneGroupId ? updated : zoneGroup
        ))
        markZoneGroupDirty(updated.zoneGroupId)
        // A group's colour is inherited by any of its zones that have none of their own.
        mapZones.forEach(zone => zone.applyColour(
            (zoneGroups ?? []).map(zoneGroup => zoneGroup.zoneGroupId === updated.zoneGroupId ? updated : zoneGroup)
        ))
    }

    const saveZone = async (zone) => {
        setIsProcessing(true)
        try {
            // A zone can reference a group that has never been saved, so its group must exist
            // server-side before the zone can point at it.
            for(const groupId of zone.groupIds ?? []) {
                if(groupId > 0)
                    continue
                const zoneGroup = (zoneGroups ?? []).find(group => group.zoneGroupId === groupId)
                if(zoneGroup)
                    await props.mapState.saveZoneGroup(api, ratesheetId, zoneGroup)
            }

            await saveZoneToServer(api, ratesheetId, zone)
            toast.success(`Zone "${zone.name}" saved`)
        } catch(error) {
            toast.error(`Could not save zone "${zone.name}"`)
        } finally {
            setIsProcessing(false)
        }
    }

    const saveZoneGroup = async (zoneGroup) => {
        setIsProcessing(true)
        try {
            await props.mapState.saveZoneGroup(api, ratesheetId, zoneGroup)
            toast.success(`Group "${zoneGroup.name}" saved`)
        } catch(error) {
            toast.error(`Could not save group "${zoneGroup.name}"`)
        } finally {
            setIsProcessing(false)
        }
    }

    const deleteZoneGroup = async (deleted) => {
        const memberCount = mapZones.filter(zone => (zone.groupIds ?? []).includes(deleted.zoneGroupId)).length
        const warning = memberCount
            ? `\n\n${memberCount} zone${memberCount === 1 ? '' : 's'} will leave this group, and any conditional that prices by crossing it will stop applying.`
            : ''

        if(!confirm(`Delete zone group "${deleted.name}"?${warning}`))
            return

        setIsProcessing(true)
        try {
            await deleteZoneGroupFromMap(api, ratesheetId, deleted.zoneGroupId)
            setEditZoneGroupId(null)
            toast.success(`Group "${deleted.name}" deleted`)
        } catch(error) {
            toast.error(`Could not delete group "${deleted.name}"`)
        } finally {
            setIsProcessing(false)
        }
    }

    const tagOptions = useMemo(() => {
        const tags = []
        ;(mapZones ?? []).forEach(zone => {
            ;(zone.tags ?? []).forEach(tag => {
                if(tag)
                    tags.push(tag)
            })
        })
        return Array.from(new Set(tags)).map(tag => ({value: tag, label: tag}))
    }, [mapZones])
    // Sync tolerance changes back to snapTolerance in parent state
    useEffect(() => {
        if(setSnapTolerance && tolerance !== snapTolerance) {
            setSnapTolerance(tolerance)
        }
    }, [tolerance, setSnapTolerance, snapTolerance])
    const editZone = mapZones.find(zone => zone.polygon.zIndex == editZoneZIndex)

    const buildNeighbourMap = (zones, allZones) => {
        const result = new Map()
        zones.forEach(zone => {
            const neighbours = new Set()
            allZones.forEach(other => {
                if(other.polygon.zIndex === zone.polygon.zIndex)
                    return
                if(zone.getCommonCoordinates(other).length)
                    neighbours.add(other.polygon.zIndex)
            })
            result.set(zone.polygon.zIndex, neighbours)
        })
        return result
    }

    const neighboursChanged = (beforeMap, afterMap) => {
        for(const [zoneId, beforeNeighbours] of beforeMap.entries()) {
            const afterNeighbours = afterMap.get(zoneId) ?? new Set()
            if(beforeNeighbours.size !== afterNeighbours.size)
                return true
            for(const neighbourId of beforeNeighbours)
                if(!afterNeighbours.has(neighbourId))
                    return true
        }
        return false
    }

    const runSimplify = () => {
        const zonesToProcess = singleZoneOnly && editZone ? [editZone] : mapZones
        const snapshotCoords = new Map(zonesToProcess.map(zone => [zone.polygon.zIndex, zone.getCoordinates(8)]))
        const beforeNeighbours = buildNeighbourMap(zonesToProcess, mapZones)

        // Log BEFORE state
        console.log('=== SIMPLIFY START ===')
        console.log('Zones to process:', zonesToProcess.map(z => `${z.name} (${z.getCoordinateCount()} vertices)`))
        const beforeCoords = {}
        zonesToProcess.forEach(zone => {
            beforeCoords[zone.name] = zone.getCoordinates()
            console.log(`${zone.name} BEFORE (${beforeCoords[zone.name].length} vertices):`, beforeCoords[zone.name])
        })

        // Simplify sequence: pre-smooth, streaming simplify, then topology/quick simplify, dedupe
        setDrawingMap(0)
        zonesToProcess.forEach((mapZone, index) => {
            setDrawingMap(index / zonesToProcess.length / 8)
            try{ mapZone.smooth(Math.max(1e-6, tolerance/10), mapZones) } catch(e){/*ignore*/}
        })
        zonesToProcess.forEach((mapZone, index) => {
            setDrawingMap(4 + index / zonesToProcess.length / 8)
            try{ mapZone.simplifyStreaming(parseFloat(tolerance) || 2, {preserveSharedVertices: true, otherZones: mapZones}) } catch(e){ console.error(e) }
        })
        if(simplifyMode === 'topo'){
            try{ topoSimplify.simplifyZones(zonesToProcess, tolerance) } catch(e){ console.error('topo simplify failed', e) }
        } else {
            zonesToProcess.forEach((mapZone, index)=> { try{ mapZone.smooth(tolerance, mapZones) } catch(e){} })
        }
        zonesToProcess.forEach((mapZone, index) => { try{ mapZone.removeDuplicates({preserveKeys: mapZone.getSharedVertexKeysExact(mapZones)}) } catch(e){} })
        setDrawingMap(100)

        const afterNeighbours = buildNeighbourMap(zonesToProcess, mapZones)
        if(neighboursChanged(beforeNeighbours, afterNeighbours)) {
            zonesToProcess.forEach(zone => {
                const coords = snapshotCoords.get(zone.polygon.zIndex)
                if(coords)
                    zone.polygon.setPath(coords)
            })
            setRefreshKey(prev => prev + 1)
            toast.warn('Simplify reverted because neighbour relationships changed.')
            return
        }

        setLastUndoSnapshot(snapshotCoords)
        setLastUndoLabel(singleZoneOnly && editZone ? `Simplify (${editZone.name})` : 'Simplify')

        // Log AFTER state
        console.log('=== SIMPLIFY COMPLETE ===')
        let totalRemoved = 0
        zonesToProcess.forEach(zone => {
            const afterCoords = zone.getCoordinates()
            const removed = beforeCoords[zone.name].length - afterCoords.length
            totalRemoved += removed
            console.log(`${zone.name} AFTER (${afterCoords.length} vertices):`, afterCoords)
        })
        // Check for shared vertices between processed zones and their neighbors
        if(zonesToProcess.length >= 2) {
            for(let i = 0; i < zonesToProcess.length - 1; i++) {
                for(let j = i + 1; j < zonesToProcess.length; j++) {
                    const shared = zonesToProcess[i].getCommonCoordinates(zonesToProcess[j])
                    console.log(`Shared vertices between ${zonesToProcess[i].name} and ${zonesToProcess[j].name}:`, shared.length)
                }
            }
        }
        // Show toast notification
        toast.success(`Removed ${totalRemoved} coordinate${totalRemoved !== 1 ? 's' : ''} successfully`)
        // Force dropdown refresh
        setRefreshKey(prev => prev + 1)
    }

    const runUndo = () => {
        if(!lastUndoSnapshot)
            return
        mapZones.forEach(zone => {
            const coords = lastUndoSnapshot.get(zone.polygon.zIndex)
            if(coords)
                zone.polygon.setPath(coords)
        })
        setRefreshKey(prev => prev + 1)
        toast.info(`${lastUndoLabel || 'Last action'} undone.`)
        setLastUndoSnapshot(null)
        setLastUndoLabel('')
    }

    return (
        <Card style={{padding: '0px'}}>
            {/*
              * One row, two columns. The control column scrolls inside its own height so a long zone
              * editor can never push the map down the page - the map keeps a fixed 85vh and stays
              * fully visible. (This used to be two stacked rows, which meant the tall control column
              * reserved matching dead height beside it and shoved the map below the fold.)
              */}
            <Row style={{alignItems: 'flex-start'}} className='g-2'>
                <Col md={3} style={{maxHeight: MAP_HEIGHT, overflowY: 'auto', overflowX: 'hidden', paddingRight: '12px'}}>
                    {isDrawingMode
                        ? <Button variant='danger' onClick={stopDrawing}>Cancel Drawing</Button>
                        : <Button variant='primary' onClick={startDrawing}>Draw Zone</Button>
                    }
                    {!isDrawingMode &&
                        <Button
                            variant='outline-secondary'
                            style={{marginLeft: '4px'}}
                            onClick={props.openImport}
                            title='Copy zones from another ratesheet'
                        >
                            <i className='fas fa-file-import' /> Import
                        </Button>
                    }
                    {isDrawingMode &&
                        <small className='text-muted' style={{display: 'block', marginTop: '4px'}}>
                            Click to add points &mdash; double-click to finish
                        </small>
                    }

                    <ZoneGroupsPanel
                        createZoneGroup={createZoneGroup}
                        defaultZoneGroupId={defaultZoneGroupId}
                        editZoneGroup={setEditZoneGroupId}
                        mapZones={mapZones}
                        setDefaultZoneGroupId={setDefaultZoneGroupId}
                        zoneGroups={zoneGroups}
                    />

                    {hasUnsavedChanges &&
                        <div className='text-warning' style={{marginTop: '8px', fontSize: '0.85em'}}>
                            <i className='fas fa-exclamation-triangle' />{' '}
                            Unsaved: {dirtyZones.length} zone{dirtyZones.length === 1 ? '' : 's'}
                            {dirtyZoneGroupIds.length > 0 && `, ${dirtyZoneGroupIds.length} group${dirtyZoneGroupIds.length === 1 ? '' : 's'}`}
                            {pendingZoneDeletions.length > 0 && `, ${pendingZoneDeletions.length} deletion${pendingZoneDeletions.length === 1 ? '' : 's'}`}
                        </div>
                    }

                    <div style={{marginTop: '8px'}}>
                        <Select
                            key={refreshKey}
                            options={mapZones}
                            getOptionLabel={zone => `${zone.name} (${zone.getCoordinateCount()} points)`}
                            getOptionValue={zone => zone.polygon.zIndex}
                            value={mapZones.find(zone => zone.polygon.zIndex == editZoneZIndex) ?? null}
                            onChange={zone => setEditZoneZIndex(zone.polygon.zIndex)}
                            placeholder='Select a zone to edit'
                            isSearchable
                            {...menuPortalProps}
                        />
                    </div>

                    {editZone &&
                        <div style={{marginTop: '8px'}}>
                            <MapZone
                                deleteZone={props.mapState.deleteZone}
                                saveZone={saveZone}
                                isSaving={isProcessing}
                                handleZoneChange={props.mapState.handleZoneChange}
                                mapZones={mapZones}
                                deliveryTypes={deliveryTypes}
                                tagOptions={tagOptions}
                                zoneGroups={zoneGroups}
                                zone={editZone}
                            />
                        </div>
                    }
                </Col>
                <Col md={9} style={{position: 'relative'}}>
                    {/* Overlaid on the map rather than stacked above it, so it costs no vertical space. */}
                    <Button
                        size='sm'
                        variant='light'
                        onClick={() => setShowSettings(true)}
                        style={{position: 'absolute', top: '8px', right: '20px', zIndex: 1, boxShadow: '0 1px 4px rgba(0,0,0,.3)'}}
                    >
                        <i className='fas fa-sliders-h' /> Map Tools
                    </Button>
                    <GoogleMap
                        center={mapCenter}
                        mapContainerStyle={{height: MAP_HEIGHT, width: '100%'}}
                        options={{disableDefaultUI: true}}
                        zoom={mapZoom}
                        onLoad={map => {
                            setMap(map)
                        }}
                        id='test_id'
                    >
                    </GoogleMap>
                </Col>
            </Row>

            <Modal show={showSettings} onHide={() => setShowSettings(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Map Tools</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <InputGroup>
                        <InputGroup.Text>Tolerance</InputGroup.Text>
                        <FormControl
                            type='number'
                            step='0.1'
                            name='tolerance'
                            value={tolerance}
                            onChange={event => setTolerance(parseFloat(event.target.value) || 0.1)}
                        />
                        <InputGroup.Text>(m)</InputGroup.Text>
                    </InputGroup>
                    <InputGroup style={{marginTop: '8px'}}>
                        <InputGroup.Checkbox
                            checked={singleZoneOnly}
                            onChange={e => setSingleZoneOnly(e.target.checked)}
                        />
                        <InputGroup.Text>
                            Apply only to selected zone ({editZone ? editZone.name : 'none selected'})
                        </InputGroup.Text>
                    </InputGroup>
                    <div style={{marginTop: '12px', display: 'flex', gap: '8px'}}>
                        <Button onClick={runSimplify}>Simplify (run)</Button>
                        <Button variant='outline-secondary' onClick={runUndo} disabled={!lastUndoSnapshot}>
                            Undo last action
                        </Button>
                    </div>
                    <hr/>
                    <small className='text-muted'>
                        Points snap to nearby zone vertices both while drawing and while editing, so a
                        new zone can be traced straight onto its neighbours' borders. Tolerance is the
                        snap distance on the ground; zoom in far enough for it to be more than a pixel
                        or two, or snapping falls back to whatever sits under the cursor.
                    </small>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant='secondary' onClick={() => setShowSettings(false)}>Close</Button>
                </Modal.Footer>
            </Modal>

            {editedZoneGroup &&
                <ZoneGroupModal
                    deliveryTypes={deliveryTypes}
                    zoneGroup={editedZoneGroup}
                    zoneCount={mapZones.filter(zone => (zone.groupIds ?? []).includes(editedZoneGroup.zoneGroupId)).length}
                    isDirty={dirtyZoneGroupIds.includes(editedZoneGroup.zoneGroupId)}
                    isSaving={isProcessing}
                    onChange={updateZoneGroup}
                    onSave={saveZoneGroup}
                    onDelete={deleteZoneGroup}
                    onClose={() => setEditZoneGroupId(null)}
                />
            }
        </Card>
    )
}
