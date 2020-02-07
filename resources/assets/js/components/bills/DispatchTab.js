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
                            options={props.drivers}
                            getOptionLabel={driver => driver.employee_number + ' - ' + driver.contact.first_name + ' ' + driver.contact.last_name}
                            isSearchable
                            value={props.pickupEmployee}
                            onChange={driver => props.handleChanges({target: {name: 'pickupEmployee', type: 'object', value: driver}})}
                            isDisabled={props.readOnly}
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
                            readOnly={props.readOnly}
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
                            readOnly={props.readOnly}
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
                            options={props.drivers}
                            getOptionLabel={driver => driver.employee_number + ' - ' + driver.contact.first_name + ' ' + driver.contact.last_name}
                            isSearchable
                            value={props.deliveryEmployee}
                            onChange={driver => props.handleChanges({target: {name: 'deliveryEmployee', type: 'object', value: driver}})}
                            isDisabled={props.readOnly}
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
                            readOnly={props.readOnly}
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
                            readOnly={props.readOnly}
                            className='form-control'
                        />
                    </InputGroup>
                </Col>
            </Row>
            <hr/>
            <Row> {/* Interliner */}
                <Col md={2}>
                    <h4 className='text-muted'>Interliner</h4>
                </Col>
                <Col md={9}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Interliner: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <Select 
                            options={props.interliners}
                            getOptionLabel={interliner => interliner.name}
                            isSearchable
                            value={props.interliner}
                            onChange={interliner => props.handleChanges({target: {name: 'interliner', type: 'object', value: interliner}})}
                            isDisabled={props.readOnly}
                        />
                        <InputGroup.Prepend>
                            <InputGroup.Text>Tracking #</InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='text'
                            placeholder='Tracking Number'
                            name='interlinerTrackingId'
                            value={props.interlinerTrackingId}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly}
                        />
                        <InputGroup.Prepend>
                            <InputGroup.Text>Cost To Customer: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            step={0.01}
                            min={0}
                            name='interlinerCostToCustomer'
                            value={props.interlinerCostToCustomer}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly}
                        />
                        <InputGroup.Prepend>
                            <InputGroup.Text>Actual Cost: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            step={0.01}
                            min={0}
                            name='interlinerActualCost'
                            value={props.interlinerActualCost}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly}
                        />
                    </InputGroup>
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
