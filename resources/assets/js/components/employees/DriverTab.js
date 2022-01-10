import React from 'react'
import {Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

export default function DriverTab(props) {
    return (
        <Card border='dark'>
            <Card.Header>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Company Info</h4></Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Company Name:</InputGroup.Text>
                            <FormControl
                                name='companyName'
                                placeholder='Company Name (optional)'
                                value={props.companyName}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Pickup Commission: </InputGroup.Text>
                            <FormControl
                                type='number'
                                min={0}
                                max={100}
                                name='pickupCommission'
                                value={props.pickupCommission}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Delivery Commission: </InputGroup.Text>
                            <FormControl
                                type='number'
                                min={0}
                                max={100}
                                name='deliveryCommission'
                                value={props.deliveryCommission}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Driver Details</h4></Col>
                    <Col md={3}>
                        <Card>
                            <Card.Header>
                                <h4 className='text-muted'><i className='fas fa-id-card'></i> Drivers License</h4>
                            </Card.Header>
                            <Card.Body>
                                <InputGroup>
                                    <InputGroup.Text>License Number:</InputGroup.Text>
                                    <FormControl
                                        name='driversLicenseNumber'
                                        placeholder='Drivers License Number'
                                        value={props.driversLicenseNumber}
                                        onChange={props.handleChanges}
                                        readOnly={props.readOnly}
                                    />
                                </InputGroup>
                                <InputGroup>
                                    <InputGroup.Text>Expiry Date</InputGroup.Text>
                                    <DatePicker
                                        dateFormat='MMMM d, yyyy'
                                        onChange={value => props.handleChanges({target: {name: 'driversLicenseExpirationDate', value: value}})}
                                        showMonthDropdown
                                        showYearDropdown
                                        monthDropdownItemNumber={15}
                                        scrollableMonthDropdown
                                        scrollableYearDropdown
                                        selected={props.driversLicenseExpirationDate}
                                        className='form-control'
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card>
                            <Card.Header>
                                <h4 className='text-muted'><i className='fas fa-car'></i> License Plate</h4>
                            </Card.Header>
                            <Card.Body>
                                <InputGroup>
                                    <InputGroup.Text>License Plate Number:</InputGroup.Text>
                                    <FormControl
                                        name='licensePlateNumber'
                                        placeholder='License Plate Number'
                                        value={props.licensePlateNumber}
                                        onChange={props.handleChanges}
                                        readOnly={props.readOnly}
                                    />
                                </InputGroup>
                                <InputGroup>
                                    <InputGroup.Text>Expiry Date</InputGroup.Text>
                                    <DatePicker
                                        dateFormat='MMMM d, yyyy'
                                        onChange={value => props.handleChanges({target: {name: 'licensePlateExpirationDate', value: value}})}
                                        showMonthDropdown
                                        showYearDropdown
                                        monthDropdownItemNumber={15}
                                        scrollableMonthDropdown
                                        scrollableYearDropdown
                                        selected={props.licensePlateExpirationDate}
                                        className='form-control'
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card>
                            <Card.Header>
                                <h4 className='text-muted'><i className='fas fa-car-crash'></i> Insurance Info</h4>
                            </Card.Header>
                            <Card.Body>
                                <InputGroup>
                                    <InputGroup.Text>Insurance Number:</InputGroup.Text>
                                    <FormControl
                                        name='insuranceNumber'
                                        placeholder='Insurance Number'
                                        value={props.insuranceNumber}
                                        onChange={props.handleChanges}
                                        readOnly={props.readOnly}
                                    />
                                </InputGroup>
                                <InputGroup>
                                    <InputGroup.Text>Expiry Date</InputGroup.Text>
                                    <DatePicker
                                        dateFormat='MMMM d, yyyy'
                                        onChange={value => props.handleChanges({target: {name: 'insuranceExpirationDate', value: value}})}
                                        showMonthDropdown
                                        showYearDropdown
                                        monthDropdownItemNumber={15}
                                        scrollableMonthDropdown
                                        scrollableYearDropdown
                                        selected={props.insuranceExpirationDate}
                                        className='form-control'
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}

