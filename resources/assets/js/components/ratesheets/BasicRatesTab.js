import React from 'react'
import {Button, Card, Col, InputGroup, FormControl, Form, Row, Table} from 'react-bootstrap'

import RateOption from './RateOption'

export default function BasicRatesTab(props) {

    function addMiscRate() {
        props.handleChange({target: {
            name: 'miscRates',
            type: 'object',
            value: props.miscRates.concat([{name: '', price: ''}])
        }})
    }

    function deleteMiscRate(index) {
        props.handleChange({target: {
            name: 'miscRates',
            type: 'object',
            value: props.miscRates.filter((rate, i) => i != index)
        }})
    }

    function handleMiscRateChange(event) {
        const {name, value} = event.target
        console.log(name, value, event.target.dataset.miscrateindex)
        const miscRates = props.miscRates.map((rate, i) => {
            if(i == event.target.dataset.miscrateindex)
                return {...rate, [name]: value}
            return rate
        })
        props.handleChange({target: {name: 'miscRates', type: 'object', value: miscRates}})
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
                            <FormControl type='text' placeholder='Ratesheet Name' name='ratesheetName' value={props.ratesheetName} onChange={props.handleChange}/>
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <strong>
                            <Form.Check type='checkbox' name='useInternalZonesCalc' label='Use Internal Zones Crossed to Calculate Pricing' checked={props.useInternalZonesCalc} onChange={props.handleChange} />
                        </strong>
                    </Col>
                    <Col md={2}>
                        <Button
                            variant='secondary'
                            onClick={() => {props.handleChange({target: {name: 'showImportModal', type: 'boolean', value: true}}); props.handleChange({target: 'selectedImports', type: 'array', value: []})}}
                            style={{float: 'right'}}
                        ><i className='fas fa-copy'/> Import</Button>
                    </Col>
                </Row>
            </Card.Header>
            {!props.useInternalZonesCalc &&
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
                                    {props.deliveryTypes.map(type => 
                                        <RateOption 
                                            key={type.id}
                                            friendlyName={type.friendlyName}
                                            time={type.time}
                                            cost={type.cost}
                                            id={type.id}
                                            handleChange={props.handleChange}
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
                                    <th><Button variant='success' onClick={addMiscRate} size='sm'><i className='fas fa-plus'></i></Button></th>
                                    <th>Name</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                {props.miscRates && props.miscRates.map((rate, index) =>
                                    <tr key={'miscRates.' + index}>
                                        <td><Button variant='danger' onClick={() => deleteMiscRate(index)} size='sm'><i className='fas fa-trash'></i></Button></td>
                                        <td>
                                            <InputGroup size='sm'>
                                                <FormControl
                                                    name='name'
                                                    value={rate.name}
                                                    key={index}
                                                    data-miscrateindex={index}
                                                    onChange={handleMiscRateChange}
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
                                                    onChange={handleMiscRateChange}
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
