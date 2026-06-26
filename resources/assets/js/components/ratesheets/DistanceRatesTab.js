import React from 'react'
import {Card, Col, Row, Table} from 'react-bootstrap'

import ZoneRate from './ZoneRate'

export default function DistanceRatesTab(props) {
    const {
        deliveryTypes,
        useInternalZonesCalc,
        setZoneRates,
        zoneRates,
    } = props.ratesheetState

    const ensureZoneRateShape = (zoneRate) => {
        const normalizedZoneRate = {...zoneRate}

        ;(deliveryTypes ?? []).forEach(type => {
            const key = type.friendlyName
            const existing = normalizedZoneRate?.[key]
            if(existing && typeof existing === 'object') {
                normalizedZoneRate[key] = {
                    cost: existing.cost ?? '',
                    time: existing.time ?? '',
                }
            } else {
                normalizedZoneRate[key] = {
                    cost: '',
                    time: '',
                }
            }
        })

        return normalizedZoneRate
    }

    const handleZoneRateChange = (index, typeName, field, value) => {
        const updated = zoneRates.map(zoneRate => {
            if(zoneRate.id == index) {
                const normalizedZoneRate = ensureZoneRateShape(zoneRate)
                return {
                    ...normalizedZoneRate,
                    [typeName]: {
                        ...(normalizedZoneRate[typeName] ?? {cost: '', time: ''}),
                        [field]: value ?? ''
                    }
                }
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
                            <tbody>
                                {zoneRates.map(
                                    rate =>
                                        <ZoneRate
                                            key={rate.id}
                                            deliveryTypes={deliveryTypes}
                                            id={rate.id}
                                            zoneRate={ensureZoneRateShape(rate)}
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

