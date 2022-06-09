import React from 'react'
import {Card, InputGroup, FormControl, ButtonGroup, Button, Col, Row, Collapse} from 'react-bootstrap'
import CurrencyInput from 'react-currency-input-field'

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
                        <ButtonGroup>
                            <Button variant='danger' onClick={() => props.deleteZone(props.id)} size='sm'><i className='fas fa-trash'></i></Button>
                            <Button variant='danger' onClick={() => props.zoneRemoveDuplicates(props.id)} size='sm'>Dedup</Button>
                        </ButtonGroup>
                    </Col>
                </Row>
            </Card.Header>
            {(props.zone.type === 'peripheral' || props.zone.type === 'outlying') &&
            <Collapse in={props.zone.viewDetails}>
                <Card.Body>
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
                                name='regularCost'
                                onValueChange={value => props.handleChange({target: {name: 'regularCost', type: 'currency', value: value}}, 'mapZones', props.id)}
                                prefix='$'
                                step={0.01}
                                value={props.zone.regularCost}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Rush Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='rushCost'
                                onValueChange={value => props.handleChange({target: {name: 'rushCost', type: 'currency', value: value}}, 'mapZones', props.id)}
                                prefix='$'
                                step={0.01}
                                value={props.zone.rushCost}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Direct Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='directCost'
                                onValueChange={value => props.handleChange({target: {name: 'directCost', type: 'currency', value: value}}, 'mapZones', props.id)}
                                prefix='$'
                                step={0.01}
                                value={props.zone.directCost}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Direct Rush Cost: </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='directRushCost'
                                onValueChange={value => props.handleChange({target: {name: 'directRushCost', type: 'currency', value: value}}, 'mapZones', props.id)}
                                prefix='$'
                                step={0.01}
                                value={props.zone.directRushCost}
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
