import React, {Component, Fragment} from 'react'
import {Button, ButtonGroup, Col, ListGroup, Tab, Tabs, Row} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'
import { connect } from 'react-redux'

import ActivityLogTab from '../partials/ActivityLogTab'
import AdministrationTab from './AdministrationTab'
import BasicTab from './BasicTab'
import DriverTab from './DriverTab'

const initialState = {
    activityLog: undefined,
    birthDate: new Date(),
    companyName: '',
    deliveryCommission: '',
    driver: false,
    driversLicenseExpirationDate: new Date(),
    driversLicenseNumber: '',
    emailAddresses: [{email: '', is_primary: true}],
    emailAddressesToDelete: [],
    emergencyContactModalShow: false,
    emergencyContacts: undefined,
    employeeAddressFormatted: '',
    employeeAddressLat: '',
    employeeAddressLng: '',
    employeeAddressName: '',
    employeeAddressPlaceId: '',
    employeeId: undefined,
    employeeNumber: '',
    employeePermissions: [],
    enabled: true,
    firstName: '',
    insuranceExpirationDate: new Date(),
    insuranceNumber: '',
    key: 'basic',
    lastName: '',
    licensePlateNumber: '',
    licensePlateExpirationDate: new Date(),
    permissions: [],
    phoneNumbers: [{phone: '', extension: '', is_primary: true}],
    phoneNumbersToDelete: [],
    pickupCommission: '',
    position: '',
    readOnly: false,
    SIN: '',
    startDate: new Date(),
    updatedAt: ''
}

class Employee extends Component {
    constructor() {
        super()
        this.state = {
            ...initialState
        }
        this.configureEmployee = this.configureEmployee.bind(this)
        this.handleChanges = this.handleChanges.bind(this)
        this.handlePermissionChange = this.handlePermissionChange.bind(this)
        this.storeEmployee = this.storeEmployee.bind(this)
    }

    componentDidMount() {
        this.configureEmployee()
    }

    componentDidUpdate(prevProps) {
        if(prevProps.location.pathname != this.props.location.pathname)
            this.configureEmployee()
    }

    configureEmployee() {
        const {match: {params}} = this.props
        const fetchUrl = params.employeeId ? '/employees/getModel/' + params.employeeId : '/employees/getModel'
        params.employeeId ? document.title = 'Edit Employee - ' + params.employeeId : 'Create Employee'

        makeAjaxRequest(fetchUrl, 'GET', null, response => {
            response = JSON.parse(response)
            var setup = {
                ...initialState,
                emailTypes: response.contact.email_types,
                employeePermissions: response.employee_permissions,
                permissions: response.permissions,
                phoneTypes: response.contact.phone_types,
            }
            this.setState(setup)
            if(params.employeeId) {
                const thisEmployeeIndex = this.props.sortedEmployees.findIndex(employee_id => employee_id === response.employee.employee_id)
                const prevEmployeeId = thisEmployeeIndex <= 0 ? null : this.props.sortedEmployees[thisEmployeeIndex - 1]
                const nextEmployeeId = (thisEmployeeIndex < 0 || thisEmployeeIndex === this.props.sortedEmployees.length - 1) ? null : this.props.sortedEmployees[thisEmployeeIndex + 1]
                setup = {...setup,
                    activityLog: response.activity_log,
                    birthDate: Date.parse(response.employee.dob),
                    driver: response.employee.is_driver,
                    emailAddresses: response.contact.emails,
                    emergencyContacts: response.emergency_contacts,
                    employeeAddressLat: response.contact.address.lat,
                    employeeAddressLng: response.contact.address.lng,
                    employeeAddressFormatted: response.contact.address.formatted,
                    employeeAddressName: response.contact.address.name,
                    employeeAddressPlaceId: response.contact.address.place_id,
                    employeeId: response.employee.employee_id,
                    employeeNumber: response.employee.employee_number,
                    enabled: response.employee.is_enabled,
                    firstName: response.contact.first_name,
                    key: this.state.key,
                    lastName: response.contact.last_name,
                    nextEmployeeId: nextEmployeeId,
                    phoneNumbers: response.contact.phone_numbers,
                    position: response.contact.position,
                    prevEmployeeId: prevEmployeeId,
                    SIN: response.employee.sin,
                    startDate: Date.parse(response.employee.start_date),
                    updatedAt: response.employee.updated_at,
                    //driverAttributes
                    companyName: response.employee.company_name === null ? undefined : response.employee.company_name,
                    deliveryCommission: response.employee.delivery_commission,
                    driversLicenseNumber: response.employee.drivers_license_number,
                    driversLicenseExpirationDate: Date.parse(response.employee.drivers_license_expiration_date),
                    insuranceNumber: response.employee.insurance_number,
                    insuranceExpirationDate: Date.parse(response.employee.insurance_expiration_date),
                    licensePlateNumber: response.employee.license_plate_number,
                    licensePlateExpirationDate: Date.parse(response.employee.license_plate_expiration_date),
                    pickupCommission: response.employee.pickup_commission
                }
                toastr.clear()
                if(response.employee.is_driver == 1 && response.employee.is_enabled == 1) {
                    if(setup.driversLicenseExpirationDate < new Date())
                        toastr.error('Drivers License has passed expiration date', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})
                    if(setup.licensePlateExpirationDate < new Date())
                        toastr.error('License Plate has passed expiration date', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})
                    if(setup.insuranceExpirationDate < new Date())
                        toastr.error('Insurance has passed expiration date', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})
                    if(setup.emergencyContacts.length < 2)
                        toastr.error('Please provide a minimum of 2 emergency contacts', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})
                }
            }
            this.setState(setup)
        })
    }

    handleChanges(events) {
        if(!Array.isArray(events))
            events = [events]
        var temp = {}
        events.forEach(event => {
            const {name, value, type, checked} = event.target
            temp[name] = type === 'checkbox' ? checked : value
        })
        this.setState(temp)
    }

    handlePermissionChange(event) {
        const {name, value, checked} = event.target

        const employeePermissions = {...this.state.employeePermissions, [name]: checked}

        this.setState({employeePermissions: employeePermissions})
    }

    render() {
        return (
            <Fragment>
                {(this.state.employeeId && this.state.driver == 1) &&
                    <Row className='justify-content-md-center'>
                        <Col md={6}>
                            <ListGroup className='list-group-horizontal' as='ul'>
                                {this.state.driversLicenseExpirationDate < new Date() &&
                                    <ListGroup.Item variant='danger'>Drivers License Expired</ListGroup.Item>
                                }
                                {this.state.licensePlateExpirationDate < new Date() &&
                                    <ListGroup.Item variant='danger'>License Plate Expired</ListGroup.Item>
                                }
                                {this.state.insuranceExpirationDate < new Date() &&
                                    <ListGroup.Item variant='danger'>Insurance Expired</ListGroup.Item>
                                }
                                {this.state.emergencyContacts.length < 2 &&
                                    <ListGroup.Item variant='danger'>Minimum 2 Emergency Contacts Required</ListGroup.Item>
                                }
                            </ListGroup>
                        </Col>
                        <Col md={6} style={{textAlign: 'right'}}>
                            <LinkContainer to={'/app/manifests?filter[driver_id]=' + this.state.employeeId}><Button variant='secondary'>Manifests</Button></LinkContainer>
                            <LinkContainer to={'/app/bills?filter[pickup_driver_id]=' + this.state.employeeId}><Button variant='secondary'>All Bills</Button></LinkContainer>
                        </Col>
                    </Row>
                }
                <Row className='justify-content-md-center'>
                    <Col md={12}>
                        <Tabs id='employee-tabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.handleChanges({target: {name: 'key', type: 'string', value: key}})}>
                            <Tab eventKey='basic' title={<h4>Basic</h4>}>
                                <BasicTab
                                    address={{
                                        type: 'Address',
                                        name: this.state.employeeAddressName,
                                        formatted: this.state.employeeAddressFormatted,
                                        lat: this.state.employeeAddressLat,
                                        lng: this.state.employeeAddressLng,
                                        placeId: this.state.employeeAddressPlaceId
                                    }}
                                    emailAddresses={this.state.emailAddresses}
                                    emergencyContacts={this.state.emergencyContacts}
                                    employeeId={this.state.employeeId}
                                    firstName={this.state.firstName}
                                    lastName={this.state.lastName}
                                    phoneNumbers={this.state.phoneNumbers}
                                    phoneTypes={this.state.phoneTypes}
                                    position={this.state.position}
                                    readOnly={this.state.readOnly}
                                    handleChanges={this.handleChanges}
                                />
                            </Tab>
                            {(this.state.permissions.viewAdvanced && this.state.driver == 1) &&
                                <Tab eventKey='driver' title={<h4>Driver</h4>}>
                                    <DriverTab
                                        companyName={this.state.companyName}
                                        pickupCommission={this.state.pickupCommission}
                                        deliveryCommission={this.state.deliveryCommission}
                                        driversLicenseNumber={this.state.driversLicenseNumber}
                                        driversLicenseExpirationDate={this.state.driversLicenseExpirationDate}
                                        insuranceNumber={this.state.insuranceNumber}
                                        insuranceExpirationDate={this.state.insuranceExpirationDate}
                                        licensePlateNumber={this.state.licensePlateNumber}
                                        licensePlateExpirationDate={this.state.licensePlateExpirationDate}

                                        handleChanges={this.handleChanges}
                                        readOnly={!this.state.permissions.editAdvanced}
                                    />
                                </Tab>
                            }
                            {this.state.permissions.editAdvanced &&
                                <Tab eventKey='admin' title={<h4>Administration</h4>}>
                                    <AdministrationTab
                                        birthDate={this.state.birthDate}
                                        driver={this.state.driver}
                                        employeeNumber={this.state.employeeNumber}
                                        employeePermissions={this.state.employeePermissions}
                                        enabled={this.state.enabled}
                                        SIN={this.state.SIN}
                                        startDate={this.state.startDate}

                                        handleChanges={this.handleChanges}
                                        handlePermissionChange={this.handlePermissionChange}
                                    />
                                </Tab>
                            }
                            {(this.state.activityLog && this.state.permissions.viewActivityLog) &&
                                <Tab eventKey='activity_log' title={<h4>Activity Log  <i className='fas fa-book-open'></i></h4>}>
                                    <ActivityLogTab
                                        activityLog={this.state.activityLog}
                                    />
                                </Tab>
                            }
                        </Tabs>
                    </Col>
                </Row>
                <Row className='justify-content-md-center'>
                    <Col align='center'>
                        <ButtonGroup>
                            <LinkContainer to={'/app/employees/edit/' + this.state.prevEmployeeId}><Button variant='info' disabled={!this.state.prevEmployeeId}><i className='fas fa-arrow-circle-left'></i> Back - {this.state.prevEmployeeId}</Button></LinkContainer>
                            <Button variant='primary' onClick={this.storeEmployee} disabled={this.state.readOnly}><i className='fas fa-save'></i> Submit</Button>
                            <LinkContainer to={'/app/employees/edit/' + this.state.nextEmployeeId}><Button variant='info' disabled={!this.state.nextEmployeeId}>Next - {this.state.nextEmployeeId} <i className='fas fa-arrow-circle-right'></i></Button></LinkContainer>
                        </ButtonGroup>
                    </Col>
                </Row>
            </Fragment>
        )
    }

    storeEmployee() {
        if(this.state.employeeId ? !this.state.permissions.editBasic : !this.state.permissions.create) {
            toastr.error('Authenticated User does not have permission to ' + this.state.employeeId ? 'update this Employee' : 'create Employee', 'Error');
            return;
        }

        var data = {
            address_formatted: this.state.employeeAddressFormatted,
            address_lat: this.state.employeeAddressLat,
            address_lng: this.state.employeeAddressLng,
            address_name: this.state.employeeAddressName,
            address_place_id: this.state.employeeAddressPlaceId,
            employee_id: this.state.employeeId,
            emails: this.state.emailAddresses,
            emergency_contacts: this.state.emergencyContacts,
            first_name: this.state.firstName,
            last_name: this.state.lastName,
            phone_numbers: this.state.phoneNumbers,
        }

        if(this.state.permissions.editAdvanced)
            data = {...data,
                birth_date: this.state.birthDate.toLocaleString('en-us'),
                employee_number: this.state.employeeNumber,
                is_driver: this.state.driver,
                is_enabled: this.state.enabled,
                permissions: this.state.employeePermissions,
                position: this.state.position,
                sin: this.state.SIN,
                start_date: this.state.startDate.toLocaleString('en-us')
            }

        if(this.state.permissions.editAdvanced && this.state.driver)
            data = {...data,
                company_name: this.state.companyName,
                delivery_commission: this.state.deliveryCommission,
                drivers_license_expiration_date: this.state.driversLicenseExpirationDate.toLocaleString('en-us'),
                drivers_license_number: this.state.driversLicenseNumber,
                insurance_expiration_date: this.state.insuranceExpirationDate.toLocaleString('en-us'),
                insurance_number: this.state.insuranceNumber,
                license_plate_number: this.state.licensePlateNumber,
                license_plate_expiration_date: this.state.licensePlateExpirationDate.toLocaleString('en-us'),
                pickup_commission: this.state.pickupCommission,
            }

        makeAjaxRequest('/employees/store', 'POST', data, response => {
            toastr.clear()
            if(this.state.employeeId) {
                this.setState({updatedAt: response.updated_at})
                toastr.success('Employee ' + this.state.employeeId + ' was successfully updated!', 'Success')
            }
            else {
                this.setState({readOnly: true})
                toastr.success('Employee ' + response.employee_id + ' was successfully created', 'Success', {
                    'progressBar': true,
                    'positionClass': 'toast-top-full-width',
                    'showDuration': 500,
                    'onHidden': function(){this.configureEmployee()}
                })
            }
        })
    }
}

const mapStateToProps = store => {
    return {
        sortedEmployees: store.employees.sortedList
    }
}

export default connect(mapStateToProps)(Employee)
