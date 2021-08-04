import React from 'react'
import {Card, InputGroup, FormControl, ButtonGroup, Button, Col, Row, Collapse} from 'react-bootstrap'

export default function Zone(props) {
    return(
        <Card>
            <Card.Header style={{backgroundColor: props.colour}}>
                <Row>
                    <Col>
                        <InputGroup size='sm'>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Name: </InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl type='text' name='name' value={props.zone.name} onChange={event => props.handleChange(event, 'mapZones', props.id)}/>
                        </InputGroup>
                    </Col>
                    <Col md='auto'>
                        <ButtonGroup>
                            <Button variant='danger' onClick={() => props.deleteZone(props.id)} size='sm'><i className='fas fa-trash'></i></Button>
                        </ButtonGroup>
                    </Col>
                </Row>
            </Card.Header>
            {(props.zone.type === 'peripheral' || props.zone.type === 'outlying') &&
            <Collapse in={props.zone.viewDetails}>
                <Card.Body>
                    <InputGroup size='sm'>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Additional Time: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl type='number' step={0.1} min={0.00} name='additionalTime' value={props.zone.additionalTime} onChange={event => props.handleChange(event, 'mapZones', props.id)} />
                        <InputGroup.Append>
                            <InputGroup.Text> hours</InputGroup.Text>
                        </InputGroup.Append>
                    </InputGroup>
                {props.zone.type !== 'peripheral' ? null : 
                    <InputGroup size='sm'>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Additional Cost: $</InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl type='number' step={0.01} min={0.00} name='regularCost' value={props.zone.regularCost} onChange={event => props.handleChange(event, 'mapZones', props.id)} />
                    </InputGroup>
                }
                {props.zone.type !== 'outlying' ? null :
                <div>
                    <InputGroup size='sm'>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Regular Cost: $</InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl type='number' step={0.01} min ={0.00} name='regularCost' value={props.zone.regularCost} onChange={event => props.handleChange(event, 'mapZones', props.id)} />
                    </InputGroup>
                    <InputGroup size='sm'>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Rush Cost: $</InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl type='number' step={0.01} min ={0.00} name='rushCost' value={props.zone.rushCost} onChange={event => props.handleChange(event, 'mapZones', props.id)} />
                    </InputGroup>
                    <InputGroup size='sm'>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Direct Cost: $</InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl type='number' step={0.01} min ={0.00} name='directCost' value={props.zone.directCost} onChange={event => props.handleChange(event, 'mapZones', props.id)} />
                    </InputGroup>
                    <InputGroup size='sm'>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Direct Rush Cost: $</InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl type='number' step={0.01} min ={0.00} name='directRushCost' value={props.zone.directRushCost} onChange={event => props.handleChange(event, 'mapZones', props.id)} />
                    </InputGroup>
                </div>
                }
                </Card.Body>
            </Collapse>
            }
        </Card>
    )
}
