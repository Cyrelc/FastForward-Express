import React from 'react'
import {Card, Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import Select from 'react-select'

export default function DriverTab(props) {
    const {
        companyName,
        deliveryCommission,
        driversLicenseExpirationDate,
        driversLicenseNumber,
        insuranceExpirationDate,
        insuranceNumber,
        licensePlateExpirationDate,
        licensePlateNumber,
        pickupCommission,
        vehicleType,
        vehicleTypes,
        setCompanyName,
        setDeliveryCommission,
        setDriversLicenseExpirationDate,
        setDriversLicenseNumber,
        setInsuranceExpirationDate,
        setInsuranceNumber,
        setLicensePlateExpirationDate,
        setLicensePlateNumber,
        setPickupCommission,
        setVehicleType,

        readOnly
    } = props

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
                                placeholder='Company Name (opt)'
                                value={companyName}
                                onChange={setCompanyName}
                                readOnly={readOnly}
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
                                value={pickupCommission}
                                onChange={setPickupCommission}
                                readOnly={readOnly}
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
                                value={deliveryCommission}
                                onChange={setDeliveryCommission}
                                readOnly={readOnly}
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Driver Details</h4></Col>
                    <Col md={10}>
                        <Row>
                            <Col md={4}>
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
                                                value={driversLicenseNumber}
                                                onChange={setDriversLicenseNumber}
                                                readOnly={readOnly}
                                            />
                                        </InputGroup>
                                        <InputGroup>
                                            <InputGroup.Text>Expiry Date</InputGroup.Text>
                                            <DatePicker
                                                className='form-control'
                                                dateFormat='MMMM d, yyyy'
                                                disabled={readOnly}
                                                monthDropdownItemNumber={15}
                                                onChange={setDriversLicenseExpirationDate}
                                                scrollableMonthDropdown
                                                scrollableYearDropdown
                                                selected={driversLicenseExpirationDate}
                                                showMonthDropdown
                                                showYearDropdown
                                                wrapperClassName='form-control'
                                            />
                                        </InputGroup>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={4}>
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
                                                value={licensePlateNumber}
                                                onChange={setLicensePlateNumber}
                                                readOnly={readOnly}
                                            />
                                        </InputGroup>
                                        <InputGroup>
                                            <InputGroup.Text>Expiry Date</InputGroup.Text>
                                            <DatePicker
                                                className='form-control'
                                                dateFormat='MMMM d, yyyy'
                                                disabled={readOnly}
                                                monthDropdownItemNumber={15}
                                                onChange={setLicensePlateExpirationDate}
                                                scrollableMonthDropdown
                                                scrollableYearDropdown
                                                selected={licensePlateExpirationDate}
                                                showMonthDropdown
                                                showYearDropdown
                                                wrapperClassName='form-control'
                                            />
                                        </InputGroup>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={4}>
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
                                                value={insuranceNumber}
                                                onChange={setInsuranceNumber}
                                                readOnly={readOnly}
                                            />
                                        </InputGroup>
                                        <InputGroup>
                                            <InputGroup.Text>Expiry Date</InputGroup.Text>
                                            <DatePicker
                                                className='form-control'
                                                dateFormat='MMMM d, yyyy'
                                                disabled={readOnly}
                                                monthDropdownItemNumber={15}
                                                onChange={setInsuranceExpirationDate}
                                                scrollableMonthDropdown
                                                scrollableYearDropdown
                                                selected={insuranceExpirationDate}
                                                showMonthDropdown
                                                showYearDropdown
                                                wrapperClassName='form-control'
                                            />
                                        </InputGroup>
                                    </Card.Body>
                                </Card>
                            </Col>
                            <Col md={4}>
                                <InputGroup>
                                    <InputGroup.Text>Vehicle Type:</InputGroup.Text>
                                    <Select
                                        options={vehicleTypes}
                                        getOptionLabel={option => option.name}
                                        value={vehicleType}
                                        onChange={setVehicleType}
                                        isDisabled={readOnly}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}

