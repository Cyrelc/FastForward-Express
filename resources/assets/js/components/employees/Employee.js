import React, {Component} from 'react'
import {Button, Col, Tab, Tabs, Row} from 'react-bootstrap'

import ActivityLogTab from '../partials/ActivityLogTab'
import AdministrationTab from './AdministrationTab'
import BasicTab from './BasicTab'
import DriverTab from './DriverTab'

const initialState = {
    activityLog: undefined,
    action: undefined,
    active: true,
    admin: true, // ????
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
    firstName: '',
    insuranceExpirationDate: new Date(),
    insuranceNumber: '',
    key: 'basic',
    lastName: '',
    licensePlateNumber: '',
    licensePlateExpirationDate: new Date(),
    phoneNumbers: [{phone: '', extension: '', is_primary: true}],
    phoneNumbersToDelete: [],
    pickupCommission: '',
    position: '',
    readOnly: false,
    SIN: '',
    startDate: new Date(),
}

export default class Employee extends Component {
    constructor() {
        super()
        this.state = {
            ...initialState
        }
        this.configureEmployee = this.configureEmployee.bind(this)
        this.handleChanges = this.handleChanges.bind(this)
        this.storeEmployee = this.storeEmployee.bind(this)
    }

    componentDidMount() {
        this.configureEmployee()
    }

    componentDidUpdate(prevProps) {
        const {match: {params}} = this.props
        if(params.action != this.state.action || (params.employeeId != this.state.employeeId && params.employeeId.toUpperCase() != 'N' + this.state.employeeNumber))
            this.configureEmployee()
    }

    configureEmployee() {
        const {match: {params}} = this.props
        console.log('Params.employeeId = ' + params.employeeId)
        var fetchUrl = '/employees/getModel'
        if(params.action ==='edit' || params.action === 'view') {
            document.title = params.action === 'edit' ? 'Edit Employee - ' + document.title : 'View Employee - ' + document.title
            fetchUrl += '/' + params.employeeId
        } else {
            document.title = 'Create Employee - ' + document.title
        }
        makeFetchRequest(fetchUrl, data => {
            var setup = {
                ...initialState,
                action: params.action,
                emailTypes: data.contact.email_types,
                phoneTypes: data.contact.phone_types,
            }
            if(params.action === 'edit' || params.action === 'view') {
                setup = {...setup,
                    activityLog: data.activity_log,
                    birthDate: Date.parse(data.employee.dob),
                    driver: data.employee.is_driver === 1,
                    emailAddresses: data.contact.emails,
                    emergencyContacts: data.emergency_contacts,
                    employeeAddressLat: data.contact.address.lat,
                    employeeAddressLng: data.contact.address.lng,
                    employeeAddressFormatted: data.contact.address.formatted,
                    employeeAddressName: data.contact.address.name,
                    employeeAddressPlaceId: data.contact.address.place_id,
                    employeeId: data.employee.employee_id,
                    employeeNumber: data.employee.employee_number,
                    firstName: data.contact.first_name,
                    lastName: data.contact.last_name,
                    phoneNumbers: data.contact.phone_numbers,
                    position: data.contact.position,
                    SIN: data.employee.sin,
                    startDate: Date.parse(data.employee.start_date),
                    //driverAttributes
                    companyName: data.employee.company_name === null ? undefined : data.employee.company_name,
                    pickupCommission: data.employee.pickup_commission,
                    deliveryCommission: data.employee.delivery_commission,
                    licensePlateNumber: data.employee.license_plate_number,
                    licensePlateExpirationDate: Date.parse(data.employee.license_plate_expiration_date),
                    driversLicenseNumber: data.employee.drivers_license_number,
                    driversLicenseExpirationDate: Date.parse(data.employee.drivers_license_expiration_date),
                    insuranceNumber: data.employee.insurance_number,
                    insuranceExpirationDate: Date.parse(data.employee.insurance_expiration_date)
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

    render() {
        return (
            <span>
                <Row md={11} className='justify-content-md-center'>
                    <Col md={11}>
                        <Tabs id='employee-tabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.setState({key})}>
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
                                    admin={this.state.admin}
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
                            { this.state.driver &&
                                <Tab eventKey='driver' title={<h4>Driver</h4>}>
                                    <DriverTab
                                        admin={this.state.admin}
                                        companyName={this.state.companyName}
                                        pickupCommission={this.state.pickupCommission}
                                        deliveryCommission={this.state.deliveryCommission}
                                        driversLicenseNumber={this.state.driversLicenseNumber}
                                        driversLicenseExpirationDate={this.state.driversLicenseExpirationDate}
                                        handleChanges={this.handleChanges}
                                        insuranceNumber={this.state.insuranceNumber}
                                        insuranceExpirationDate={this.state.insuranceExpirationDate}
                                        licensePlateNumber={this.state.licensePlateNumber}
                                        licensePlateExpirationDate={this.state.licensePlateExpirationDate}
                                        readOnly={this.state.readOnly}
                                    />
                                </Tab>
                            }
                            <Tab eventKey='admin' title={<h4>Administration</h4>}>
                                <AdministrationTab
                                    active={this.state.active}
                                    admin={this.state.admin}
                                    birthDate={this.state.birthDate}
                                    driver={this.state.driver}
                                    employeeNumber={this.state.employeeNumber}
                                    handleChanges={this.handleChanges}
                                    SIN={this.state.SIN}
                                    startDate={this.state.startDate}
                                />
                            </Tab>
                            {this.state.activityLog &&
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
                    <Button variant='primary' onClick={this.storeEmployee} disabled={this.state.readOnly}><i className='fas fa-save'></i> Submit</Button>
                </Row>
            </span>
        )
    }

    storeEmployee() {
        const data = {
            active: this.state.active,
            address_formatted: this.state.employeeAddressFormatted,
            address_lat: this.state.employeeAddressLat,
            address_lng: this.state.employeeAddressLng,
            address_name: this.state.employeeAddressName,
            address_place_id: this.state.employeeAddressPlaceId,
            birth_date: this.state.birthDate.toLocaleString('en-us'),
            company_name: this.state.companyName,
            delivery_commission: this.state.deliveryCommission,
            is_driver: this.state.driver,
            drivers_license_expiration_date: this.state.driversLicenseExpirationDate.toLocaleString('en-us'),
            drivers_license_number: this.state.driversLicenseNumber,
            emails: this.state.emailAddresses,
            emergency_contacts: this.state.emergencyContacts,
            employee_id: this.state.employeeId ? this.state.employeeId : null,
            employee_number: this.state.employeeNumber,
            first_name: this.state.firstName,
            insurance_expiration_date: this.state.insuranceExpirationDate.toLocaleString('en-us'),
            insurance_number: this.state.insuranceNumber,
            last_name: this.state.lastName,
            license_plate_number: this.state.licensePlateNumber,
            license_plate_expiration_date: this.state.licensePlateExpirationDate.toLocaleString('en-us'),
            phone_numbers: this.state.phoneNumbers,
            pickup_commission: this.state.pickupCommission,
            position: this.state.position,
            sin: this.state.SIN,
            start_date: this.state.startDate.toLocaleString('en-us'),
        }
        makeAjaxRequest('/employees/store', 'POST', data, response => {
            toastr.clear()
            if(this.state.employeeId)
                toastr.success('Employee ' + this.state.employeeId + ' was successfully updated!', 'Success')
            else {
                this.setState({readOnly:true})
                toastr.success('Employee ' + response.id + ' was successfully created', 'Success', {
                    'progressBar': true,
                    'positionClass': 'toast-top-full-width',
                    'showDuration': 500,
                    'onHidden': function(){location.reload()}
                })
            }
        })
    }
}
