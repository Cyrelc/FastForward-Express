import React, {Fragment, useCallback, useEffect, useState} from 'react'
import {Button, ButtonGroup, Col, ListGroup, Tab, Tabs, Row} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'
import { connect } from 'react-redux'
import {debounce} from 'lodash'
import {DateTime} from 'luxon'

import ActivityLogTab from '../partials/ActivityLogTab'
import AdministrationTab from './AdministrationTab'
import BasicTab from './BasicTab'
import DriverTab from './DriverTab'
import LoadingSpinner from '../partials/LoadingSpinner'

import useAddress from '../partials/Hooks/useAddress'
import useContact from '../partials/Hooks/useContact'

const Employee = (props) => {
    const [activityLog, setActivityLog] = useState(undefined)
    const [birthDate, setBirthDate] = useState(new Date())
    const [companyName, setCompanyName] = useState('')
    const [deliveryCommission, setDeliveryCommission] = useState('')
    const [driversLicenseExpirationDate, setDriversLicenseExpirationDate] = useState(new Date())
    const [driversLicenseNumber, setDriversLicenseNumber] = useState('')
    const [employeeId, setEmployeeId] = useState(undefined)
    const [employeeNumber, setEmployeeNumber] = useState('')
    const [employeePermissions, setEmployeePermissions] = useState('')
    const [insuranceExpirationDate, setInsuranceExpirationDate] = useState(new Date())
    const [insuranceNumber, setInsuranceNumber] = useState('')
    const [isDriver, setIsDriver] = useState(false)
    const [isEnabled, setIsEnabled] = useState(true)
    const [isLoading, setIsLoading] = useState(true)
    const [key, setKey] = useState('basic')
    const [licensePlateExpirationDate, setLicensePlateExpirationDate] = useState(new Date())
    const [licensePlateNumber, setLicensePlateNumber] = useState('')
    const [nextEmployeeId, setNextEmployeeId] = useState(null)
    const [permissions, setPermissions] = useState([])
    const [pickupCommission, setPickupCommission] = useState('')
    const [prevEmployeeId, setPrevEmployeeId] = useState('')
    const [readOnly, setReadOnly] = useState(false)
    const [SIN, setSIN] = useState('')
    const [startDate, setStartDate] = useState(new Date())
    const [updatedAt, setUpdatedAt] = useState('')
    const [vehicleType, setVehicleType] = useState({})
    const [vehicleTypes, setVehicleTypes] = useState([])
    const [warnings, setWarnings] = useState([])

    const address = useAddress()
    const contact = useContact()
    const now = DateTime.now().toJSDate()
    const threeMonthsFromNow = DateTime.now().plus({month: 3}).toJSDate()

    useEffect(() => {
        toastr.clear()
        configureEmployee()
    }, [props.match.params.employeeId])

    const setTabKey = tabKey => {
        window.location.hash = tabKey
        setKey(tabKey)
    }

    const configureEmployee = () => {
        const {match: {params}} = props
        const fetchUrl = params.employeeId ? `/employees/${params.employeeId}` : '/employees/create'
        params.employeeId ? document.title = `Edit Employee - ${params.employeeId}` : 'Create Employee'

        makeAjaxRequest(fetchUrl, 'GET', null, response => {
            response = JSON.parse(response)
            setEmployeePermissions(response.employee_permissions)
            setPermissions(response.permissions)
            setVehicleTypes(response.vehicle_types)
            setKey(window.location.hash?.substr(1) || 'basic')

            if(params.employeeId) {
                const thisEmployeeIndex = props.sortedEmployees.findIndex(employee_id => employee_id === response.employee.employee_id)
                const prevEmployeeId = thisEmployeeIndex <= 0 ? null : props.sortedEmployees[thisEmployeeIndex - 1]
                const nextEmployeeId = (thisEmployeeIndex < 0 || thisEmployeeIndex === props.sortedEmployees.length - 1) ? null : props.sortedEmployees[thisEmployeeIndex + 1]

                address.setup(response.address)

                setActivityLog(response.activity_log)
                setBirthDate(Date.parse(response.employee.dob))
                setEmployeeId(response.employee.employee_id)
                setEmployeeNumber(response.employee.employee_number)
                setIsDriver(!!response.employee.is_driver)
                setIsEnabled(!!response.employee.is_enabled)
                setNextEmployeeId(nextEmployeeId)
                setPrevEmployeeId(prevEmployeeId)
                setSIN(response.employee.sin)
                setStartDate(Date.parse(response.employee.start_date))
                setUpdatedAt(response.employee.updated_at)
                setCompanyName(response.employee.company_name ?? undefined)
                setDeliveryCommission(response.employee.delivery_commission)
                setDriversLicenseNumber(response.employee.drivers_license_number)
                setDriversLicenseExpirationDate(Date.parse(response.employee.drivers_license_expiration_date))
                setInsuranceNumber(response.employee.insurance_number)
                setInsuranceExpirationDate(Date.parse(response.employee.insurance_expiration_date))
                setLicensePlateNumber(response.employee.license_plate_number)
                setLicensePlateExpirationDate(Date.parse(response.employee.license_plate_expiration_date))
                setPickupCommission(response.employee.pickup_commission)
                setVehicleType(response.vehicle_types.find(type => type.selection_id == response.employee.vehicle_type))

                toastr.clear()
            }

            contact.setup(response.contact)

            setIsLoading(false)
        }
    )}

    const debouncedWarnings = useCallback(
        debounce(() => {
            if(isEnabled && employeeId) {
                toastr.clear()
                if(driversLicenseExpirationDate < now)
                    toastr.error('Drivers License has passed expiration date', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})
                else if(driversLicenseExpirationDate < threeMonthsFromNow)
                    toastr.warn('Drivers License will soon expire', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})

                if(licensePlateExpirationDate < now)
                    toastr.error('License Plate has passed expiration date', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})
                else if(licensePlateExpirationDate < threeMonthsFromNow)
                    toastr.warn('License Plate will soon expire', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})

                if(insuranceExpirationDate < now)
                    toastr.error('Insurance has passed expiration date', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})
                else if(insuranceExpirationDate < threeMonthsFromNow)
                    toastr.warn('Insurance will soon expire', 'WARNING', {'timeOut': 0, 'extendedTImeout': 0})
            }
        }, 1000), [driversLicenseExpirationDate, licensePlateExpirationDate, insuranceExpirationDate, isEnabled]
    )

    useEffect(() => {
        if(!isLoading)
            debouncedWarnings()
    }, [driversLicenseExpirationDate, licensePlateExpirationDate, insuranceExpirationDate, isEnabled, isLoading])

    const storeEmployee = () => {
        if(employeeId ? !permissions.editBasic : !permissions.create) {
            toastr.error(`Authenticated User does not have permission to ${employeeId ? 'update this Employee' : 'create Employee'}`, 'Error');
            return;
        }

        var data = {
            ...contact.collect(),
            address_formatted: address.formatted,
            address_lat: address.lat,
            address_lng: address.lng,
            address_name: address.name,
            address_place_id: address.placeId,
            employee_id: employeeId,
        }

        if(permissions.editAdvanced)
            data = {
                ...data,
                birth_date: birthDate.toLocaleString('en-us'),
                employee_number: employeeNumber,
                is_driver: isDriver,
                is_enabled: isEnabled,
                permissions: employeePermissions,
                sin: SIN,
                start_date: startDate.toLocaleString('en-us')
            }

        if(permissions.editAdvanced && isDriver)
            data = {
                ...data,
                company_name: companyName,
                delivery_commission: deliveryCommission,
                drivers_license_expiration_date: driversLicenseExpirationDate.toLocaleString('en-us'),
                drivers_license_number: driversLicenseNumber,
                insurance_expiration_date: insuranceExpirationDate.toLocaleString('en-us'),
                insurance_number: insuranceNumber,
                license_plate_number: licensePlateNumber,
                license_plate_expiration_date: licensePlateExpirationDate.toLocaleString('en-us'),
                pickup_commission: pickupCommission,
            }

            makeAjaxRequest('/employees', 'POST', data, response => {
            toastr.clear()
            if(employeeId) {
                setUpdatedAt(response.updated_at)
                toastr.success(`Employee ${employeeId} was successfully updated!`, 'Success')
            }
            else {
                setReadOnly(true)
                toastr.success(`Employee ${response.employee_id} was successfully created`, 'Success', {
                    'progressBar': true,
                    'positionClass': 'toast-top-full-width',
                    'showDuration': 500,
                    'onHidden': function(){configureEmployee()}
                })
            }
        })
    }

    if(isLoading)
        return <LoadingSpinner />

    return (
        <Fragment>
            {(employeeId && isDriver) &&
                <Row className='justify-content-md-center'>
                    <Col>
                        <ListGroup className='list-group-horizontal' as='ul'>
                            {driversLicenseExpirationDate < now &&
                                <ListGroup.Item variant='danger'>Drivers License Expired</ListGroup.Item>
                            }
                            {(driversLicenseExpirationDate < threeMonthsFromNow && driversLicenseExpirationDate > now) &&
                                <ListGroup.Item variant='warning'>Drivers License Expires Soon</ListGroup.Item>
                            }
                            {licensePlateExpirationDate < now &&
                                <ListGroup.Item variant='danger'>License Plate Expired</ListGroup.Item>
                            }
                            {(licensePlateExpirationDate < threeMonthsFromNow && licensePlateExpirationDate > now) &&
                                <ListGroup.Item variant='warning'>License Plate Expires Soon</ListGroup.Item>
                            }
                            {insuranceExpirationDate < now &&
                                <ListGroup.Item variant='danger'>Insurance Expired</ListGroup.Item>
                            }
                            {(insuranceExpirationDate < threeMonthsFromNow && insuranceExpirationDate > now) &&
                                <ListGroup.Item variant='warning'>Insurance Expires Soon</ListGroup.Item>
                            }
                        </ListGroup>
                    </Col>
                    <Col style={{textAlign: 'right'}}>
                        <LinkContainer to={`/manifests?filter[driver_id]=${employeeId}`}><Button variant='secondary'>Manifests</Button></LinkContainer>
                        <LinkContainer to={`/bills?filter[pickup_driver_id]=${employeeId}`}><Button variant='secondary'>All Bills</Button></LinkContainer>
                    </Col>
                </Row>
            }
            <Row className='justify-content-md-center'>
                <Col md={12}>
                    <Tabs id='employee-tabs' className='nav-justified' activeKey={key} onSelect={setTabKey}>
                        <Tab eventKey='basic' title={<h4>Basic</h4>}>
                            <BasicTab
                                address={address}
                                contact={contact}
                                employeeId={employeeId}
                                readOnly={readOnly}
                            />
                        </Tab>
                        {(permissions.viewAdvanced && isDriver) &&
                            <Tab eventKey='driver' title={<h4>Driver</h4>}>
                                <DriverTab
                                    companyName={companyName}
                                    deliveryCommission={deliveryCommission}
                                    driversLicenseExpirationDate={driversLicenseExpirationDate}
                                    driversLicenseNumber={driversLicenseNumber}
                                    insuranceExpirationDate={insuranceExpirationDate}
                                    insuranceNumber={insuranceNumber}
                                    licensePlateExpirationDate={licensePlateExpirationDate}
                                    licensePlateNumber={licensePlateNumber}
                                    pickupCommission={pickupCommission}
                                    vehicleType={vehicleType}
                                    vehicleTypes={vehicleTypes}

                                    setCompanyName={setCompanyName}
                                    setDeliveryCommission={setDeliveryCommission}
                                    setDriversLicenseExpirationDate={setDriversLicenseExpirationDate}
                                    setDriversLicenseNumber={setDriversLicenseNumber}
                                    setInsuranceExpirationDate={setInsuranceExpirationDate}
                                    setInsuranceNumber={setInsuranceNumber}
                                    setLicensePlateExpirationDate={setLicensePlateExpirationDate}
                                    setLicensePlateNumber={setLicensePlateNumber}
                                    setPickupCommission={setPickupCommission}
                                    setVehicleType={setVehicleType}

                                    readOnly={!permissions.editAdvanced}
                                />
                            </Tab>
                        }
                        {permissions.editAdvanced &&
                            <Tab eventKey='admin' title={<h4>Administration</h4>}>
                                <AdministrationTab
                                    birthDate={birthDate}
                                    employeeNumber={employeeNumber}
                                    employeePermissions={employeePermissions}
                                    isDriver={isDriver}
                                    isEnabled={isEnabled}
                                    SIN={SIN}
                                    startDate={startDate}

                                    setBirthDate={setBirthDate}
                                    setEmployeeNumber={setEmployeeNumber}
                                    setEmployeePermissions={setEmployeePermissions}
                                    setIsDriver={setIsDriver}
                                    setIsEnabled={setIsEnabled}
                                    setSIN={setSIN}
                                    setStartDate={setStartDate}
                                />
                            </Tab>
                        }
                        {(activityLog && permissions.viewActivityLog) &&
                            <Tab eventKey='activity_log' title={<h4>Activity Log  <i className='fas fa-book-open'></i></h4>}>
                                <ActivityLogTab
                                    activityLog={activityLog}
                                />
                            </Tab>
                        }
                    </Tabs>
                </Col>
            </Row>
            <Row className='justify-content-md-center'>
                <Col align='center'>
                    <ButtonGroup>
                        <LinkContainer to={`/employees/${prevEmployeeId}`}>
                            <Button variant='info' disabled={!prevEmployeeId}>
                                <i className='fas fa-arrow-circle-left'></i> Back - {prevEmployeeId}
                            </Button>
                        </LinkContainer>
                        <Button variant='primary' onClick={storeEmployee} disabled={readOnly}>
                            <i className='fas fa-save'></i> Submit
                        </Button>
                        <LinkContainer to={`/employees/${nextEmployeeId}`}>
                            <Button variant='info' disabled={!nextEmployeeId}>
                                Next - {nextEmployeeId} <i className='fas fa-arrow-circle-right'></i>
                            </Button>
                        </LinkContainer>
                    </ButtonGroup>
                </Col>
            </Row>
        </Fragment>
    )
}

const mapStateToProps = store => {
    return {
        sortedEmployees: store.employees.sortedList
    }
}

export default connect(mapStateToProps)(Employee)
