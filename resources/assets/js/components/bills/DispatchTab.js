import React from 'react';
import {Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap';
import Select from 'react-select'
import DatePicker from 'react-datepicker'

export default function DispatchTab(props) {
    return (
        <Card border='dark'>
            <Row> {/* Pickup */}
                <Col md={2}><h4 className='text-muted'>Pickup</h4></Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Driver: </InputGroup.Text>
                        <Select
                            options={props.billId ? props.drivers : props.drivers.filter(driver => driver.active)}
                            isSearchable
                            value={props.pickupEmployee}
                            onChange={driver => props.handleChanges({target: {name: 'pickupEmployee', type: 'object', value: driver}})}
                            isDisabled={props.readOnly || props.isPickupManifested}
                        />
                    </InputGroup>
                </Col>
                <Col md={2}>
                    <InputGroup>
                        <InputGroup.Text>Commission: </InputGroup.Text>
                        <FormControl
                            type='number'
                            min={0}
                            max={100}
                            value={props.pickupEmployeeCommission}
                            name='pickupEmployeeCommission'
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.isPickupManifested}
                        />
                        <InputGroup.Text> %</InputGroup.Text>
                    </InputGroup>
                </Col>
                <Col md={2}>
                    <InputGroup>
                        <InputGroup.Text>Est. Income</InputGroup.Text>
                        <FormControl
                            value={
                                (props.pickupEmployeeCommission / 100 * props.charges.reduce((chargeTotal, charge) =>
                                    charge.chargeType.name === 'Employee' ? chargeTotal : charge.lineItems.reduce((lineItemTotal, lineItem) =>
                                        lineItemTotal + parseFloat(lineItem.driver_amount), chargeTotal), 0))
                                            .toLocaleString('en-US', {style: 'currency', currency: 'USD'})
                            }
                            disabled={true}
                        />
                    </InputGroup>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Actual Time: </InputGroup.Text>
                        <DatePicker
                            showTimeSelect
                            showMonthDropdown
                            monthDropdownItemNumber={15}
                            scrollableMonthDropdown
                            timeIntervals={15}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            selected={props.pickupTimeActual}
                            onChange={datetime => props.handleChanges({target: {name: 'pickupTimeActual', type:'date', value: datetime}})}
                            readOnly={props.readOnly || props.isPickupManifested}
                            className='form-control'
                            wrapperClassName='form-control'
                        />
                    </InputGroup>
                </Col>
            </Row>
            <hr/>
            <Row> {/* Delivery */}
                <Col md={2}><h4 className='text-muted'>Delivery</h4></Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Driver: </InputGroup.Text>
                        <Select 
                            options={props.billId ? props.drivers : props.drivers.filter(driver => driver.active)}
                            isSearchable
                            value={props.deliveryEmployee}
                            onChange={driver => props.handleChanges({target: {name: 'deliveryEmployee', type: 'object', value: driver}})}
                            isDisabled={props.readOnly || props.isDeliveryManifested}
                        />
                    </InputGroup>
                </Col>
                <Col md={2}>
                    <InputGroup>
                        <InputGroup.Text>Commission: </InputGroup.Text>
                        <FormControl
                            type='number'
                            min='0'
                            max='100'
                            name='deliveryEmployeeCommission'
                            value={props.deliveryEmployeeCommission}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.isDeliveryManifested}
                        />
                        <InputGroup.Text> %</InputGroup.Text>
                    </InputGroup>
                </Col>
                <Col md={2}>
                    <InputGroup>
                        <InputGroup.Text>Est. Income</InputGroup.Text>
                        <FormControl
                            value={
                                (props.deliveryEmployeeCommission / 100 * props.charges.reduce((chargeTotal, charge) =>
                                    charge.chargeType.name === 'Employee' ? chargeTotal : charge.lineItems.reduce((lineItemTotal, lineItem) =>
                                        lineItemTotal + parseFloat(lineItem.driver_amount), chargeTotal), 0))
                                            .toLocaleString('en-US', {style: 'currency', currency: 'USD'})
                            }
                            disabled={true}
                        />
                    </InputGroup>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Actual Time: </InputGroup.Text>
                        <DatePicker
                            showTimeSelect
                            showMonthDropdown
                            monthDropdownItemNumber={15}
                            scrollableMonthDropdown
                            timeIntervals={15}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            selected={props.deliveryTimeActual}
                            onChange={datetime => props.handleChanges({target: {name: 'deliveryTimeActual', type:'date', value: datetime}})}
                            readOnly={props.readOnly || props.isDeliveryManifested}
                            className='form-control'
                            wrapperClassName='form-control'
                        />
                    </InputGroup>
                </Col>
            </Row>
            <hr/>
            <Row className='pad-top'>
                <Col md={2}><h4 className='text-muted'>Internal Notes</h4></Col>
                <Col md={10}>
                    <FormControl
                        as='textarea'
                        placeholder='Internal notes are never meant to be seen by the client'
                        name='internalNotes'
                        value={props.internalNotes}
                        onChange={props.handleChanges}
                        readOnly={props.readOnly}
                    />
                </Col>
            </Row>
            <hr/>
            <Row>
                <Col md={2}>
                    <h4 className='text-muted'>Additional Timestamps</h4>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Call Received: </InputGroup.Text>
                        <DatePicker
                            showTimeSelect
                            timeIntervals={15}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            showYearDropdown
                            yearDropdownItemNumber={15}
                            scrollableYearDropdown
                            selected={props.timeCallReceived}
                            onChange={datetime => props.handleChanges({target: {name: 'timeCallReceived', type:'date', value: datetime}})}
                            readOnly={true}
                            className='form-control'
                            wrapperClassName='form-control'
                        />
                    </InputGroup>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Dispatched: </InputGroup.Text>
                        <DatePicker
                            showTimeSelect
                            timeIntervals={15}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            selected={props.timeDispatched}
                            onChange={datetime => props.handleChanges({target: {name: 'timeDispatched', type:'date', value: datetime}})}
                            onFocus={props.timeDispatched === '' ? props.handleChanges({target: {name: 'timeDispatched', type: 'date', value: new Date()}}) : null}
                            readOnly={true}
                            className='form-control'
                            wrapperClassName='form-control'
                        />
                    </InputGroup>
                </Col>
            </Row>
        </Card>
    )
}
