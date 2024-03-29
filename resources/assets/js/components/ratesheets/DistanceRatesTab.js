import React from 'react'
import {Card, Col, FormControl, InputGroup, Row, Table} from 'react-bootstrap'

import ZoneRate from './ZoneRate'

export default function DistanceRatesTab(props) {
    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={12}>
                        <h4 className='text-muted'>Distance Rates</h4>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                {props.useInternalZonesCalc &&
                    <Col md={12}>
                        <Row className='justify-content-md-center'>
                            <h5 className='text-muted'>Zone Distance Rates</h5>
                        </Row>
                        <Table size='sm'>
                            <thead>
                                <tr>
                                    <td></td>
                                    {props.deliveryTypes.map(type => 
                                        <td key={type.friendlyName + '.friendlyName'}>
                                            <h5 className='text-muted'>{type.friendlyName}</h5>
                                        </td>
                                    )}
                                </tr>
                            </thead>
                            <thead>
                                <tr>
                                    <td>
                                        <h5 className='text-muted'>Additional Time: </h5>
                                    </td>
                                    {props.deliveryTypes.map(type =>
                                        <td key={type.friendlyName + '.additionalTime'}>
                                            <InputGroup size='sm'>
                                                <InputGroup.Text>Time Est. </InputGroup.Text>
                                                <FormControl
                                                    type='number'
                                                    min='0.1'
                                                    step='0.1'
                                                    name='time'
                                                    value={type.time}
                                                    onChange={event => props.handleChange(event, 'deliveryTypes', type.id)}
                                                />
                                            </InputGroup>
                                        </td>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {props.zoneRates.map(rate => 
                                    <ZoneRate 
                                        key={rate.id}
                                        id={rate.id}
                                        zones={rate.zones}
                                        regularCost={rate.regular_cost}
                                        rushCost={rate.rush_cost}
                                        directCost={rate.direct_cost}
                                        directRushCost={rate.direct_rush_cost}
                                        handleZoneRateChange={props.handleZoneRateChange}/>
                                    )
                                }
                            </tbody>
                        </Table>
                    </Col>
                }
            </Card.Body>
        </Card>
    )
}

