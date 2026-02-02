import React from 'react'
import {Button, Card, Col, InputGroup, FormControl, Form, Row, Table} from 'react-bootstrap'

import RateOption from './RateOption'

export default function BasicRatesTab(props) {
    const {
        deliveryTypes,
        setDeliveryTypes,
        miscRates,
        name,
        setName,
        setMiscRates,
        useInternalZonesCalc,
        setUseInternalZonesCalc
    } = props.ratesheetState

    const addMiscRate = () => {
        setMiscRates(miscRates.concat({name: '', price: ''}))
    }

    const deleteMiscRate = deleteIndex => {
        const newMiscRates = miscRates.filter((miscRate, index) => index !== deleteIndex)
        setMiscRates(newMiscRates)
    }

    const handleDeliveryTypeChange = (event, section, id) => {
        const {name, value, type, checked} = event.target
        const updated = deliveryTypes.map(dt => {
            // id may be number or string depending on source; attempt loose equality
            if(dt.id == id)
                return type === 'checkbox' ? {...dt, [name]: checked} : {...dt, [name]: value}
            return dt
        })
        setDeliveryTypes(updated)
    }

    const handleMiscRateChange = (index, field, value) =>  {
        const updated = miscRates.map((miscRate, i) => {
            if(i == index)
                return {...miscRate, [field]: value}
            return miscRate
        })
        console.log(index, field, value, updated)
        setMiscRates(updated)
    }

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Basic Options</h4>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Ratesheet Name</InputGroup.Text>
                            <FormControl type='text' placeholder='Ratesheet Name' name='ratesheetName' value={name} onChange={event => setName(event.target.value)}/>
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <strong>
                            <Form.Check
                                type='checkbox'
                                name='useInternalZonesCalc'
                                label='Use Internal Zones Crossed to Calculate Pricing'
                                checked={useInternalZonesCalc}
                                onChange={event => setUseInternalZonesCalc(event.target.checked)}
                            />
                        </strong>
                    </Col>
                </Row>
            </Card.Header>
            {!useInternalZonesCalc &&
                <Card.Body>
                    <Row>
                        <Col md={2}>
                            <h4 className='text-muted'>Delivery Types</h4>
                        </Col>
                        <Col md={10}>
                            <Table>
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Additional Cost</th>
                                        <th>Additional Time (hours)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {deliveryTypes.map(type => 
                                        <RateOption
                                            key={type.id}
                                            friendlyName={type.friendlyName}
                                            time={type.time}
                                            cost={type.cost}
                                            id={type.id}
                                            handleChange={handleDeliveryTypeChange}
                                        />
                                    )}
                                </tbody>
                            </Table>
                        </Col>
                    </Row>
                </Card.Body>
            }
            <Card.Footer>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Miscellaneous Rates</h4>
                    </Col>
                    <Col md={10}>
                        <Table size='sm'>
                            <thead>
                                <tr>
                                    <th>
                                        <Button variant='success' onClick={addMiscRate} size='sm'>
                                            <i className='fas fa-plus'></i>
                                        </Button>
                                    </th>
                                    <th>Name</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                {miscRates && miscRates.map((rate, index) =>
                                    <tr key={'miscRates.' + index}>
                                        <td>
                                            <Button variant='danger' onClick={() => deleteMiscRate(index)} size='sm'>
                                                <i className='fas fa-trash'></i>
                                            </Button>
                                        </td>
                                        <td>
                                            <InputGroup size='sm'>
                                                <FormControl
                                                    name='name'
                                                    value={rate.name}
                                                    key={index}
                                                    data-miscrateindex={index}
                                                    onChange={event => handleMiscRateChange(index, 'name', event.target.value)}
                                                />
                                            </InputGroup>
                                        </td>
                                        <td>
                                            <InputGroup size='sm'>
                                                <FormControl
                                                    name='price'
                                                    type='number'
                                                    step='0.01'
                                                    min='0.01'
                                                    value={rate.price}
                                                    key={index}
                                                    data-miscrateindex={index}
                                                    onChange={event => handleMiscRateChange(index, 'price', event.target.value)}
                                                />
                                            </InputGroup>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </Table>
                    </Col>
                </Row>
            </Card.Footer>
        </Card>
    )
}
