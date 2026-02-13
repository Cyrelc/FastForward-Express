import React, {useState, useEffect} from 'react'
import {Button, Card, Col, Collapse, FormControl, InputGroup, Popover, Row, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'
import {polyColours} from './Classes/Zone'
import MapZone from './MapZone'
import Select from 'react-select'
import {GoogleMap, DrawingManager, LoadScript} from '@react-google-maps/api'
import topoSimplify from './utils/topoSimplify.js'
import { toast } from 'react-toastify'

export default function MapTab(props) {
    const {
        createZone,
        defaultZoneType,
        deleteZone,
        editZoneZIndex,
        handleZoneEdit,
        mapCenter,
        mapZones,
        mapZoom,
        setDefaultZoneType,
        setDrawingManager,
        setDrawingMap,
        setEditZoneZIndex,
        setMap,
        setSnapTolerance,
        snapTolerance,
    } = props.mapState

    const [simplifyMode, setSimplifyMode] = useState('topo') // 'topo' or 'quick'
    const [tolerance, setTolerance] = useState(snapTolerance || 2) // meters - used for both snapping and simplification
    const [showSettings, setShowSettings] = useState(false)
    const [singleZoneOnly, setSingleZoneOnly] = useState(true)
    const [refreshKey, setRefreshKey] = useState(0)
    const [isProcessing, setIsProcessing] = useState(false)
    const [lastUndoSnapshot, setLastUndoSnapshot] = useState(null)
    const [lastUndoLabel, setLastUndoLabel] = useState('')
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

    const popover = (
        <Popover id='map-info-popover' title='How to Create a Great RateMap'>
            <strong>Map Zones</strong> come in two varieties:<br/>
            <strong>Internal</strong> and <strong>Peripheral</strong><br/><br/>
            <strong>Peripheral Zones</strong> are areas outside of your regular delivery service area. They have additional costs, and time requirements associated with them.<br/><br/>
            <strong>Internal Zones</strong> are areas within your regular delivery service area. You can either have <strong>one</strong> internal zone, in which case there will be no associated charge with crossing that zone, or you can have <strong>many</strong> internal zones. If you choose to have many, then define them on the map and the map will be able to automatically calculate how many zones were crossed for a delivery, and charge the correct rate.<br/><br/>
            <strong>Note: for internal zones to be considered adjacent to one another, they must share (snap to) a minimum of one point.</strong>
        </Popover>
    )

    return (
        <Card style={{padding: '0px'}}>
            <Row style={{alignItems: 'flex-start'}}>
                <Col md={3} className='justify-content-md-center' style={{display: 'flex'}}>
                <Row>
                    <Col md={12}>
                    <InputGroup>
                        <InputGroup.Text>New Zone Type: </InputGroup.Text>
                        <ToggleButtonGroup
                            type='radio'
                            name='defaultZoneType'
                            onChange={value => setDefaultZoneType(value)}
                            value={defaultZoneType}
                        >
                            <ToggleButton
                                id={'defaultZoneType.internal'}
                                variant='secondary'
                                value='internal'
                                key='internal'
                                style={{backgroundColor: polyColours.internalFill, color: defaultZoneType === 'internal' ? 'black' : 'white'}}
                            >Internal</ToggleButton>
                            <ToggleButton
                                variant='secondary'
                                id='defaultZoneType.peripheral'
                                value='peripheral'
                                key='peripheral'
                                style={{backgroundColor: polyColours.peripheralFill, color: defaultZoneType === 'peripheral' ? 'black' : 'white'}}
                            >Peripheral</ToggleButton>
                            <ToggleButton
                                variant='secondary'
                                id='defaultZoneType.outlying'
                                value='outlying'
                                key='outlying' 
                                style={{backgroundColor: polyColours.outlyingFill, color: defaultZoneType === 'outlying' ? 'black' : 'white'}}
                            >Outlying</ToggleButton>
                        </ToggleButtonGroup>
                    </InputGroup>
                    </Col>
                    <Col>
                        <Select
                            key={refreshKey}
                            options={mapZones}
                            getOptionLabel={zone => {
                                return `${zone.name} (${zone.type}) (${zone.getCoordinateCount()} points)`
                            }}
                            getOptionValue={zone => zone.polygon.zIndex}
                            value={mapZones.find(zone => zone.polygon.zIndex == editZoneZIndex)}
                            onChange={zone => setEditZoneZIndex(zone.polygon.zIndex)}
                            isSearchable
                        />
                    </Col>
                </Row>
                </Col>
                <Col md={9}>
                    <Button variant='secondary' onClick={() => setShowSettings(!showSettings)} aria-controls='map-settings' aria-expanded={showSettings}>
                        {showSettings ? 'Hide Settings' : 'Show Settings'}
                    </Button>
                    <Collapse in={showSettings}>
                        <div id='map-settings' style={{padding: '8px', marginTop: '8px'}}>
                            <Row>
                                <Col md={4}>
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
                                </Col>
                                {/* <Col md={8}>
                                    <InputGroup style={{display: 'none'}}>
                                        <InputGroup.Text>Simplify Mode</InputGroup.Text>
                                        <Select
                                            options={[{value:'topo', label:'Topology (preserve shared edges)'},{value:'quick', label:'Quick (per-zone simplify)'}]}
                                            onChange={opt => setSimplifyMode(opt.value)}
                                            value={{value: simplifyMode, label: simplifyMode === 'topo' ? 'Topology (preserve shared edges)' : 'Quick (per-zone simplify)'}}
                                        />
                                    </InputGroup>
                                </Col> */}
                            </Row>
                            <Row style={{marginTop: '8px'}}>
                                <Col md={12}>
                                    <InputGroup>
                                        <InputGroup.Checkbox 
                                            checked={singleZoneOnly}
                                            onChange={e => setSingleZoneOnly(e.target.checked)}
                                        />
                                        <InputGroup.Text>Apply only to selected zone ({editZone ? editZone.name : 'none selected'})</InputGroup.Text>
                                    </InputGroup>
                                </Col>
                            </Row>
                            <Row style={{marginTop: '8px'}}>
                                <Col md={2}>
                                    <Button onClick={runSimplify}>Simplify (run)</Button>
                                </Col>
                                <Col md={2}>
                                    <Button variant='outline-secondary' onClick={runUndo} disabled={!lastUndoSnapshot}>Undo last action</Button>
                                </Col>
                            </Row>
                        </div>
                    </Collapse>
                </Col>
            </Row>
            <Row>
                <Col md={3}>
                    {editZone &&
                        <MapZone
                            deleteZone={props.mapState.deleteZone}
                            // editZone={props.editZone}
                            handleZoneChange={props.mapState.handleZoneChange}
                            // handleZoneTypeChange={props.mapState.handleZoneTypeChange}
                            mapZones={mapZones}
                            zone={editZone}
                        />
                    }
                    Note: Due to technical constraints, snapping currently only occurs on zone edit, not on create. Recommendation is to create a simple polygon, and then edit it to fit your desired dimensions
                </Col>
                <Col md={9}>
                    <GoogleMap
                        center={mapCenter}
                        mapContainerStyle={{height: '85vh', width: '100%'}}
                        options={{disableDefaultUI: true}}
                        zoom={mapZoom}
                        onLoad={map => {
                            setMap(map)
                        }}
                        id='test_id'
                    >
                        <DrawingManager
                            onLoad={drawingManager => setDrawingManager(drawingManager)}
                            options={{
                                drawingControl: true,
                                drawingControlOptions: {
                                    position: window.google.maps.ControlPosition.TOP_CENTER,
                                    drawingModes: [
                                        window.google.maps.drawing.OverlayType.POLYGON
                                    ]
                                },
                                polygonOptions: {
                                    clickable: true,
                                    editable: true,
                                    fillColor: polyColours[`${defaultZoneType}Fill`],
                                    strokeColor: polyColours[`${defaultZoneType}Stroke`],
                                    zIndex: mapZones.length + 1
                                }
                            }}
                            onPolygonComplete={createZone}
                        />
                    </GoogleMap>
                </Col>
            </Row>
        </Card>
    )
}
