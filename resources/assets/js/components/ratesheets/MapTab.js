import React from 'react'
import {ButtonGroup, Card, Col, FormControl, InputGroup, Popover, Row, ToggleButton} from 'react-bootstrap'
import Zone from './Zone'
import Select from 'react-select'

export default function MapTab(props) {
    const popover = (
        <Popover id='map-info-popover' title='How to Create a Great RateMap'>
            <strong>Maps Zones</strong> come in two varieties:<br/>
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
                        options={props.mapZones}
                        getOptionLabel={zone => zone.name + '  (' + zone.type + ')' + '  (' + zone.coordinates.length + ' points)'}
                        getOptionValue={zone => zone.id}
                        value={props.mapZones.filter(zone => zone.viewDetails)[0]}
                        onChange={zone => props.editZone(zone.id)}
                        isSearchable
                    />
                </Col>
                <Col md={6} className='justify-content-md-center' style={{display: 'flex'}}>
                    <InputGroup>
                        <InputGroup.Text>New Zone Type: </InputGroup.Text>
                        <ButtonGroup>
                            <ToggleButton 
                                type='radio'
                                variant='secondary'
                                name='defaultZoneType'
                                value='internal'
                                active={props.defaultZoneType === 'internal'}
                                onChange={props.handleChange}
                                style={{backgroundColor: props.polyColours.internalFill, color:props.defaultZoneType === 'internal' ? 'black' : 'white'}}>Internal</ToggleButton>
                            <ToggleButton
                                type='radio'
                                variant='secondary'
                                name='defaultZoneType'
                                value='peripheral'
                                active={props.defaultZoneType === 'peripheral'}
                                onChange={props.handleChange}
                                style={{backgroundColor: props.polyColours.peripheralFill, color:props.defaultZoneType === 'peripheral' ? 'black' : 'white'}}>Peripheral</ToggleButton>
                            <ToggleButton 
                                type='radio'
                                variant='secondary' 
                                name='defaultZoneType' 
                                value='outlying' 
                                active={props.defaultZoneType === 'outlying'} 
                                onChange={props.handleChange}
                                style={{backgroundColor: props.polyColours.outlyingFill, color:props.defaultZoneType === 'outlying' ? 'black' : 'white'}}>Outlying</ToggleButton>
                        </ButtonGroup>
                    </InputGroup>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Snap Accuracy</InputGroup.Text>
                        <FormControl
                            type='number'
                            name='snapPrecision'
                            value={props.snapPrecision}
                            onChange={props.handleChange}
                        />
                    </InputGroup>
                </Col>
            </Row>
            <Row>
                <Col md={3}>
                    {props.mapZones && props.mapZones.length > 0 && props.mapZones.filter(zone => zone.viewDetails).map(zone =>
                        <Zone
                            key={zone.id}
                            id={zone.id}
                            zone={zone}
                            handleChange={props.handleChange}
                            handleZoneTypeChange={props.handleZoneTypeChange}
                            deleteZone={props.deleteZone}
                            editZone={props.editZone}
                            viewDetails={zone.viewDetails}
                            colour={zone.type === 'internal' ? props.polyColours.internalFill : zone.type === 'peripheral' ? props.polyColours.peripheralFill : props.polyColours.outlyingFill}
                            zoneRemoveDuplicates={props.zoneRemoveDuplicates}
                        />
                    )}
                    Note: Due to technical constraints, snapping currently only occurs on zone edit, not on create. Recommedation is to create a simple polygon, and then edit it to fit your desired dimensions
                </Col>
                <Col md={9}>
                    <div id='googleMap' style={{height:800}}></div>
                </Col>
            </Row>
        </Card>
    )
}
