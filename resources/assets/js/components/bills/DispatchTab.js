import React from 'react';
import {Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap';
import Select from 'react-select'
import DatePicker from 'react-datepicker'

const getEmployeeEstimatedIncome = (charges, commission, employeeId) => {
    if(!charges || !commission || !employeeId)
        null

    const income = commission / 100 * charges.reduce((chargeTotal, charge) =>
        charge.charge_employee_id == employeeId ? chargeTotal :
            charge.lineItems.reduce((lineItemTotal, lineItem) =>
                lineItemTotal + (parseFloat(lineItem.driver_amount) == NaN ? 0 : parseFloat(lineItem.driver_amount)), chargeTotal), 0)

    const outgoing = charges.reduce((chargeTotal, charge) =>
        charge.charge_employee_id == employeeId ? chargeTotal + charge.price : chargeTotal
    , 0)

    return (income - outgoing).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})
}

export default function DispatchTab(props) {
    const {billId, delivery, drivers, internalComments, pickup, timeDispatched, timeCallReceived, timeTenFoured} = props.billState

    const {charges, isDeliveryManifested, isPickupManifested, readOnly} = props

    return (
        <Card border='dark'>
            <Row> {/* Pickup */}
                <Col md={2}><h4 className='text-muted'>Pickup</h4></Col>
                <Col md={10}>
                    <Row>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Driver: </InputGroup.Text>
                                <Select
                                    options={billId ? drivers : drivers.filter(driver => driver.is_enabled)}
                                    isClearable
                                    isSearchable
                                    onChange={driver => props.billDispatch({type: 'SET_PICKUP_DRIVER', payload: driver})}
                                    isDisabled={readOnly || isPickupManifested}
                                    value={pickup.driver}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Commission: </InputGroup.Text>
                                <FormControl
                                    type='number'
                                    min={0}
                                    max={100}
                                    value={pickup.driverCommission}
                                    onChange={event => props.billDispatch({type: 'SET_PICKUP_VALUE', payload: {name: 'driverCommission', value: event.target.value}})}
                                    readOnly={readOnly || isPickupManifested}
                                />
                                <InputGroup.Text> %</InputGroup.Text>
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Est. Income</InputGroup.Text>
                                <FormControl
                                    value={pickup.driver ? getEmployeeEstimatedIncome(charges, pickup.driverCommission, pickup.driver.employee_id) : ''}
                                    disabled={true}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Actual Time: </InputGroup.Text>
                                <DatePicker
                                    showTimeSelect
                                    showMonthDropdown
                                    monthDropdownItemNumber={15}
                                    scrollableMonthDropdown
                                    timeIntervals={15}
                                    dateFormat='MMMM d, yyyy h:mm aa'
                                    selected={pickup.timeActual}
                                    onChange={datetime => props.billDispatch({type: 'SET_PICKUP_VALUE', payload: {name: 'timeActual', type:'date', value: datetime}})}
                                    readOnly={readOnly || isPickupManifested}
                                    className='form-control'
                                    wrapperClassName='form-control'
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Picked Up From:</InputGroup.Text>
                                <FormControl
                                    value={pickup.personName}
                                    onChange={event => props.billDispatch({type: 'SET_PICKUP_VALUE', payload: {name: 'personName', type: 'string', value: event.target.value}})}
                                    placeholder='name'
                                    readOnly={readOnly}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                </Col>
            </Row>
            <hr/>
            <Row> {/* Delivery */}
                <Col md={2}><h4 className='text-muted'>Delivery</h4></Col>
                <Col md={10}>
                    <Row>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Driver: </InputGroup.Text>
                                <Select
                                    options={billId ? drivers : drivers.filter(driver => driver.is_enabled)}
                                    isClearable
                                    isSearchable
                                    value={delivery.driver}
                                    onChange={driver => props.billDispatch({type: 'SET_DELIVERY_DRIVER', payload: driver})}
                                    isDisabled={readOnly || isDeliveryManifested}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Commission: </InputGroup.Text>
                                <FormControl
                                    type='number'
                                    min='0'
                                    max='100'
                                    name='deliveryEmployeeCommission'
                                    value={delivery.driverCommission}
                                    onChange={event => props.billDispatch({type: 'SET_DELIVERY_VALUE', payload: {name: 'driverCommission', value: event.target.value}})}
                                    readOnly={readOnly || isDeliveryManifested}
                                />
                                <InputGroup.Text> %</InputGroup.Text>
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Est. Income</InputGroup.Text>
                                <FormControl
                                    value={delivery.driver ? getEmployeeEstimatedIncome(charges, delivery.driverCommission, delivery.driver.employee_id) : ''}
                                    disabled={true}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Actual Time: </InputGroup.Text>
                                <DatePicker
                                    showTimeSelect
                                    showMonthDropdown
                                    monthDropdownItemNumber={15}
                                    scrollableMonthDropdown
                                    timeIntervals={15}
                                    dateFormat='MMMM d, yyyy h:mm aa'
                                    selected={delivery.timeActual}
                                    onChange={datetime => props.billDispatch({type: 'SET_DELIVERY_VALUE', payload: {name: 'timeActual', type:'date', value: datetime}})}
                                    readOnly={readOnly || isDeliveryManifested}
                                    className='form-control'
                                    wrapperClassName='form-control'
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Received By:</InputGroup.Text>
                                <FormControl
                                    value={delivery.personName}
                                    onChange={event => props.billDispatch({type: 'SET_DELIVERY_VALUE', payload: {name: 'personName', type: 'string', value: event.target.value}})}
                                    placeholder='name'
                                    readOnly={readOnly}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                </Col>
            </Row>
            <hr/>
            <Row className='pad-top'>
                <Col md={2}><h4 className='text-muted'>Internal Comments</h4></Col>
                <Col md={10}>
                    <FormControl
                        as='textarea'
                        placeholder='Internal comments are never meant to be seen by the client'
                        value={internalComments}
                        onChange={event => props.billDispatch({type: 'SET_INTERNAL_COMMENTS', payload: event.target.value})}
                        readOnly={readOnly}
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
                            selected={timeCallReceived}
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
                            selected={timeDispatched}
                            readOnly={true}
                            className='form-control'
                            wrapperClassName='form-control'
                        />
                    </InputGroup>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Text>Time 10-4: </InputGroup.Text>
                        <DatePicker
                            showTimeSelect
                            timeIntervals={15}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            selected={timeTenFoured}
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
