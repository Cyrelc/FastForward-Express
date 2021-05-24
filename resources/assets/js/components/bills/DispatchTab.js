import React from 'react';
import {Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap';
import Select from 'react-select'
import DatePicker from 'react-datepicker'

export default function DispatchTab(props) {
    return (
        <Card border='dark'>
            <Row> {/* Pickup */}
                <Col md={2}><h4 className='text-muted'>Pickup</h4></Col>
                <Col md={4}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Driver: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <Select 
                            options={props.billId ? props.drivers : props.drivers.filter(driver => driver.active)}
                            isSearchable
                            value={props.pickupEmployee}
                            onChange={driver => props.handleChanges({target: {name: 'pickupEmployee', type: 'object', value: driver}})}
                            isDisabled={props.readOnly || props.pickupManifestId}
                        />
                    </InputGroup>
                </Col>
                <Col md={2}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Commission: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            min={0}
                            max={100}
                            value={props.pickupEmployeeCommission}
                            name='pickupEmployeeCommission'
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.pickupManifestId}
                        />
                        <InputGroup.Append>
                            <InputGroup.Text> %</InputGroup.Text>
                        </InputGroup.Append>
                    </InputGroup>
                </Col>
                <Col md={4}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Actual Time: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <DatePicker
                            showTimeSelect
                            showMonthDropdown
                            monthDropdownItemNumber={15}
                            scrollableMonthDropdown
                            timeIntervals={15}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            selected={props.pickupTimeActual}
                            onChange={datetime => props.handleChanges({target: {name: 'pickupTimeActual', type:'date', value: datetime}})}
                            readOnly={props.readOnly || props.pickupManifestId}
                            className='form-control'
                        />
                    </InputGroup>
                </Col>
            </Row>
            <hr/>
            <Row> {/* Delivery */}
                <Col md={2}><h4 className='text-muted'>Delivery</h4></Col>
                <Col md={4}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Driver: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <Select 
                            options={props.billId ? props.drivers : props.drivers.filter(driver => driver.active)}
                            isSearchable
                            value={props.deliveryEmployee}
                            onChange={driver => props.handleChanges({target: {name: 'deliveryEmployee', type: 'object', value: driver}})}
                            isDisabled={props.readOnly || props.deliveryManifestId}
                        />
                    </InputGroup>
                </Col>
                <Col md={2}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Commission: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            min='0'
                            max='100'
                            name='deliveryEmployeeCommission'
                            value={props.deliveryEmployeeCommission}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.deliveryManifestId}
                        />
                        <InputGroup.Append>
                            <InputGroup.Text> %</InputGroup.Text>
                        </InputGroup.Append>
                    </InputGroup>
                </Col>
                <Col md={4}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Actual Time: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <DatePicker
                            showTimeSelect
                            showMonthDropdown
                            monthDropdownItemNumber={15}
                            scrollableMonthDropdown
                            timeIntervals={15}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            selected={props.deliveryTimeActual}
                            onChange={datetime => props.handleChanges({target: {name: 'deliveryTimeActual', type:'date', value: datetime}})}
                            readOnly={props.readOnly || props.deliveryManifestId}
                            className='form-control'
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
                        <InputGroup.Prepend>
                            <InputGroup.Text>Call Received: </InputGroup.Text>
                        </InputGroup.Prepend>
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
                        />
                    </InputGroup>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Dispatched: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <DatePicker
                            showTimeSelect
                            timeIntervals={15}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            selected={props.timeDispatched}
                            onChange={datetime => props.handleChanges({target: {name: 'timeDispatched', type:'date', value: datetime}})}
                            onFocus={props.timeDispatched === '' ? props.handleChanges({target: {name: 'timeDispatched', type: 'date', value: new Date()}}) : null}
                            readOnly={true}
                            className='form-control'
                        />
                    </InputGroup>
                </Col>
            </Row>
        </Card>
    )
}
