import React from 'react'
import {Card, Col, Collapse, Dropdown, FormControl, InputGroup, Row} from 'react-bootstrap'
import CurrencyInput from 'react-currency-input-field'
import Select from 'react-select'

const zoneTypes = [
    {label: 'Internal', value: 'internal'},
    {label: 'Peripheral', value: 'peripheral'},
    {label: 'Outlying', value: 'outlying'}
]

export default function Zone(props) {
    return(
        <Card>
            <Card.Header style={{backgroundColor: props.colour}}>
                <Row>
                    <Col>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Name: </InputGroup.Text>
                            <FormControl type='text' name='name' value={props.zone.name} onChange={event => props.handleChange(event, 'mapZones', props.id)}/>
                        </InputGroup>
                    </Col>
                    <Col md='auto'>
                        <Dropdown>
                            <Dropdown.Toggle size='sm' variant='secondary' id='zone-options'>
                                <i className='fas fa-bars'></i>
                            </Dropdown.Toggle>
                            <Dropdown.Menu>
                                <Dropdown.Item onClick={() => props.deleteZone(props.id)}>
                                    <i className='fas fa-trash'></i> Delete
                                </Dropdown.Item>
                                <Dropdown.Item onClick={props.zone.snapToRoads}>
                                    <i className='fas fa-road' />Snap To Roads
                                </Dropdown.Item>
                                <Dropdown.Item onClick={props.zone.smooth}>
                                    <i className='fas fa-chart-line' />Smooth
                                </Dropdown.Item>
                                <Dropdown.Item onClick={() => props.zone.match(props.mapZones)}>
                                    <i className='fas fa-check' />Match
                                </Dropdown.Item>
                                <Dropdown.Item onClick={props.zone.removeDuplicates}>
                                    Dedup
                                </Dropdown.Item>
                            </Dropdown.Menu>
                        </Dropdown>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <InputGroup size='sm'>
                    <InputGroup.Text>Zone Type</InputGroup.Text>
                    <Select
                        options={zoneTypes}
                        onChange={zoneType => props.handleZoneTypeChange({target: {name: 'type', type: 'string', value: zoneType.value}}, props.id)}
                        value={zoneTypes.find(zoneType => zoneType.value === props.zone.type)}
                    />
                </InputGroup>
            </Card.Body>
            {(props.zone.type === 'peripheral' || props.zone.type === 'outlying') &&
            <Collapse in={props.zone.viewDetails}>
                <Card.Body>
                    <hr/>
                    <InputGroup size='sm'>
                        <InputGroup.Text>Additional Time: </InputGroup.Text>
                        <FormControl type='number' step={0.1} min={0.00} name='additionalTime' value={props.zone.additionalTime} onChange={event => props.handleChange(event, 'mapZones', props.id)} />
                        <InputGroup.Text> hours</InputGroup.Text>
                    </InputGroup>
                {props.zone.type !== 'peripheral' ? null :
                    <InputGroup size='sm'>
                        <InputGroup.Text>Additional Cost: </InputGroup.Text>
                        <CurrencyInput
                            decimalsLimit={2}
                            decimalScale={2}
                            min={0.01}
                            name='regularCost'
                            onValueChange={value => props.handleChange({target: {name: 'regularCost', type: 'currency', value: value}}, 'mapZones', props.id)}
                            prefix='$'
                            step={0.01}
                            value={props.zone.regularCost}
                        />
                    </InputGroup>
                }
                {props.zone.type !== 'outlying' ? null :
                    <div>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Regular Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='regular'
                                onValueChange={value => props.handleChange({target: {name: 'regular', type: 'currency', value: value}}, 'mapZones', props.id)}
                                prefix='$'
                                step={0.01}
                                value={props.zone.additionalCosts.regular}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Rush Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='rush'
                                onValueChange={value => props.handleChange({target: {name: 'rush', type: 'currency', value: value}}, 'mapZones', props.id)}
                                prefix='$'
                                step={0.01}
                                value={props.zone.additionalCosts.rush}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Direct Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='direct'
                                onValueChange={value => props.handleChange({target: {name: 'direct', type: 'currency', value: value}}, 'mapZones', props.id)}
                                prefix='$'
                                step={0.01}
                                value={props.zone.additionalCosts.direct}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Direct Rush Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='directRush'
                                onValueChange={value => props.handleChange({target: {name: 'directRush', type: 'currency', value: value}}, 'mapZones', props.id)}
                                prefix='$'
                                step={0.01}
                                value={props.zone.additionalCosts.direct_rush}
                            />
                        </InputGroup>
                    </div>
                }
                </Card.Body>
            </Collapse>
            }
        </Card>
    )
}
