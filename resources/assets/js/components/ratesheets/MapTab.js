import React from 'react'
import {Button, Card, Col, FormControl, InputGroup, Popover, Row, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'
import {polyColours} from './Classes/Zone'
import MapZone from './MapZone'
import Select from 'react-select'
import {GoogleMap, DrawingManager, LoadScript} from '@react-google-maps/api'

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
        setSnapPrecision,
        snapPrecision,
    } = props.mapState

    const editZone = mapZones.find(zone => zone.polygon.zIndex == editZoneZIndex)

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
            <Row>
                <Col md={3}>
                    <Select
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
                <Col md={3} className='justify-content-md-center' style={{display: 'flex'}}>
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
                <Col md={3}>
                    <Button onClick={() => {
                        setDrawingMap(0)
                        mapZones.forEach((mapZone, index) => {
                            setDrawingMap(index / mapZones.length / 3)
                            console.log('running match for zone', mapZone.polygon.zIndex)
                            mapZone.match(mapZones.filter(zone => zone.polygon.zIndex > mapZone.polygon.zIndex))
                        })
                        mapZones.forEach((mapZone, index)=> {
                            setDrawingMap(33 + index / mapZones.length / 3)
                            console.log('running smooth for zone', mapZone.polygon.zIndex)
                            mapZone.smooth()
                        })
                        mapZones.forEach((mapZone, index) => {
                            setDrawingMap(66 + index / mapZones.length / 3)
                            mapZone.removeDuplicates()
                        })
                        setDrawingMap(100)
                    }}>Clean Map (Recommended; experimental) {mapZones.reduce((acc, zone) => acc + zone.getCoordinateCount(), 0)}</Button>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Snap Accuracy</InputGroup.Text>
                        <FormControl
                            type='number'
                            name='snapPrecision'
                            value={snapPrecision}
                            onChange={event => setSnapPrecision(event.target.value)}
                        />
                    </InputGroup>
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
