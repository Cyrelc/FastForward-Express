import React from 'react'
import {Card, Col, FormControl, InputGroup, Row, Table} from 'react-bootstrap'

import ZoneRate from './ZoneRate'

export default function DistanceRatesTab(props) {
    const {
        deliveryTypes,
        setDeliveryTypes,
        useInternalZonesCalc,
        setZoneRates,
        zoneRates,
    } = props.ratesheetState

    const handleEstimatedTimeChange = (index, field, value) => {
        console.log(index, field, value)
        const updated = deliveryTypes.map(deliveryType => {
            if(deliveryType.id == index)
                return {...deliveryType, [field]: value}
            return deliveryType
        })
        setDeliveryTypes(updated)
    }

    const handleZoneRateChange = (index, name, value) => {
        const updated = zoneRates.map(zoneRate => {
            if(zoneRate.id == index) {
                return {...zoneRate, [name]: value}
            }
            return zoneRate
        })
        setZoneRates(updated)
    }

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
                {useInternalZonesCalc &&
                    <Col md={12}>
                        <Row className='justify-content-md-center'>
                            <h5 className='text-muted'>Zone Distance Rates</h5>
                        </Row>
                        <Table size='sm'>
                            <thead>
                                <tr>
                                    <td></td>
                                    {deliveryTypes.map(type => 
                                        <td key={`${type.friendlyName}.friendlyName`}>
                                            <h5 className='text-muted'>{type.friendlyName}</h5>
                                        </td>
                                    )}
                                </tr>
                            </thead>
                            <thead>
                                <tr style={{backgroundColor: 'black'}}>
                                    <td>
                                        <h5 className='text-muted'>Additional Time (In Hours): </h5>
                                    </td>
                                    {deliveryTypes.map(type =>
                                        <td key={`${type.friendlyName}.additionalTime`}>
                                            <InputGroup size='sm'>
                                                <InputGroup.Text>Time Est. </InputGroup.Text>
                                                <FormControl
                                                    type='number'
                                                    min='0.1'
                                                    step='0.1'
                                                    name='time'
                                                    value={type.time}
                                                    onChange={event => handleEstimatedTimeChange(type.id, 'time', event.target.value)}
                                                />
                                            </InputGroup>
                                        </td>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {zoneRates.map(
                                    rate =>
                                        <ZoneRate
                                            key={rate.id}
                                            id={rate.id}
                                            zones={rate.zones}
                                            regularCost={rate.regular_cost}
                                            rushCost={rate.rush_cost}
                                            directCost={rate.direct_cost}
                                            directRushCost={rate.direct_rush_cost}
                                            handleZoneRateChange={handleZoneRateChange}
                                        />
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

