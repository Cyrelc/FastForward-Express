import React from 'react'
import {Card, Col, Collapse, Dropdown, FormControl, InputGroup, Row} from 'react-bootstrap'
import CurrencyInput from 'react-currency-input-field'
import Select from 'react-select'

const zoneTypes = [
    {label: 'Internal', value: 'internal'},
    {label: 'Peripheral', value: 'peripheral'},
    {label: 'Outlying', value: 'outlying'}
]

export default function MapZone(props) {
    const {
        additionalCosts,
        additionalTime,
        fillColour,
        name,
        type,
    } = props.zone
console.log(type)
    const {zIndex} = props.zone.polygon

    return(
        <Card>
            <Card.Header style={{backgroundColor: fillColour}}>
                <Row>
                    <Col>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Name: </InputGroup.Text>
                            <FormControl
                                type='text'
                                name='name'
                                value={name}
                                onChange={event => props.handleZoneChange(event, zIndex)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md='auto'>
                        <Dropdown>
                            <Dropdown.Toggle size='sm' variant='secondary' id='zone-options'>
                                <i className='fas fa-bars'></i>
                            </Dropdown.Toggle>
                            <Dropdown.Menu>
                                <Dropdown.Item onClick={() => props.deleteZone(zIndex)}>
                                    <i className='fas fa-trash'></i> Delete
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
                        onChange={zoneType => props.handleZoneChange({target: {name: 'type', type: 'string', value: zoneType.value}}, zIndex)}
                        value={zoneTypes.find(zoneType => zoneType.value === props.zone.type)}
                    />
                </InputGroup>
            </Card.Body>
            {(type == 'peripheral' || type == 'outlying') &&
            <Collapse in={true}>
                <Card.Body>
                    <hr/>
                    <InputGroup size='sm'>
                        <InputGroup.Text>Additional Time: </InputGroup.Text>
                        <FormControl type='number' step={0.1} min={0.00} name='additionalTime' value={additionalTime} onChange={event => props.handleZoneChange(event, zIndex)} />
                        <InputGroup.Text> hours</InputGroup.Text>
                    </InputGroup>
                {type == 'peripheral' &&
                    <InputGroup size='sm'>
                        <InputGroup.Text>Additional Cost: </InputGroup.Text>
                        <CurrencyInput
                            decimalsLimit={2}
                            decimalScale={2}
                            min={0.01}
                            name='regularCost'
                            onValueChange={value => props.handleZoneChange({target: {name: 'additionalCosts.regular', type: 'currency', value: value}}, zIndex)}
                            prefix='$'
                            step={0.01}
                            value={additionalCosts.regular}
                        />
                    </InputGroup>
                }
                {type == 'outlying' &&
                    <div>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Regular Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='regular'
                                onValueChange={value => props.handleZoneChange({target: {name: 'additionalCosts.regular', type: 'currency', value: value}}, zIndex)}
                                prefix='$'
                                step={0.01}
                                value={additionalCosts.regular}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Rush Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='rush'
                                onValueChange={value => props.handleZoneChange({target: {name: 'additionalCosts.rush', type: 'currency', value: value}}, zIndex)}
                                prefix='$'
                                step={0.01}
                                value={additionalCosts.rush}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Direct Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='direct'
                                onValueChange={value => props.handleZoneChange({target: {name: 'additionalCosts.direct', type: 'currency', value: value}}, zIndex)}
                                prefix='$'
                                step={0.01}
                                value={additionalCosts.direct}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Direct Rush Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='directRush'
                                onValueChange={value => props.handleZoneChange({target: {name: 'additionalCosts.direct_rush', type: 'currency', value: value}}, zIndex)}
                                prefix='$'
                                step={0.01}
                                value={additionalCosts.direct_rush}
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
