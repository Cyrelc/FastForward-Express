import React, {Fragment, useCallback, useEffect, useState} from 'react'
import {Button, ButtonGroup, Col, ListGroup, Nav, Navbar, Tab, Tabs, Row} from 'react-bootstrap'
import {debounce} from 'lodash'
import {DateTime} from 'luxon'
import {toast} from 'react-toastify'
import {useHistory} from 'react-router-dom'

import ActivityLogTab from '../partials/ActivityLogTab'
import AdministrationTab from './AdministrationTab'
import BasicTab from './BasicTab'
import DriverTab from './DriverTab'
import LoadingSpinner from '../partials/LoadingSpinner'

import {useAPI} from '../../contexts/APIContext'
import useAddress from '../partials/Hooks/useAddress'
import useContact from '../partials/Hooks/useContact'

export default function Employee(props) {
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

    const address = useAddress()
    const contact = useContact()
    const api = useAPI()
    const history = useHistory()
    const now = DateTime.now().toJSDate()
    const threeMonthsFromNow = DateTime.now().plus({month: 3}).toJSDate()

    useEffect(() => {
        // toastr.clear()
        configureEmployee()
    }, [props.match.params.employeeId])

    const setTabKey = tabKey => {
        window.location.hash = tabKey
        setKey(tabKey)
    }

    // TODO - does not clear the form if directing from employee to create
    const configureEmployee = async () => {
        setIsLoading(true)
        const {match: {params}} = props
        const fetchUrl = params.employeeId ? `/employees/${params.employeeId}` : '/employees/create'
        document.title = params.employeeId ? `Edit Employee - ${params.employeeId}` : 'Create Employee'

        const response = await api.get(fetchUrl)

        setEmployeePermissions(response.employee_permissions)
        setPermissions(response.permissions)
        setVehicleTypes(response.vehicle_types)
        setKey(window.location.hash?.substr(1) || 'basic')

        if(params.employeeId) {
            let sortedEmployees = localStorage.getItem('employees.sortedList')
            sortedEmployees = sortedEmployees.split(',').map(index => parseInt(index))
            const thisEmployeeIndex = sortedEmployees.findIndex(employee_id => employee_id === response.employee_id)
            const prevEmployeeId = thisEmployeeIndex <= 0 ? null : sortedEmployees[thisEmployeeIndex - 1]
            const nextEmployeeId = (thisEmployeeIndex < 0 || thisEmployeeIndex === sortedEmployees.length - 1) ? null : sortedEmployees[thisEmployeeIndex + 1]

            address.setup(response.contact.address)

            setActivityLog(response.activity_log.map(log => {
                return {...log, properties: JSON.parse(log.properties)}
            }))
            setBirthDate(Date.parse(response.dob))
            setEmployeeId(response.employee_id)
            setEmployeeNumber(response.employee_number)
            setIsDriver(!!response.is_driver)
            setIsEnabled(!!response.is_enabled)
            setNextEmployeeId(nextEmployeeId)
            setPrevEmployeeId(prevEmployeeId)
            setSIN(response.sin)
            setStartDate(Date.parse(response.start_date))
            setUpdatedAt(response.updated_at)
            setCompanyName(response.company_name ?? '')
            setDeliveryCommission(response.delivery_commission)
            setDriversLicenseNumber(response.drivers_license_number)
            setDriversLicenseExpirationDate(Date.parse(response.drivers_license_expiration_date))
            setInsuranceNumber(response.insurance_number)
            setInsuranceExpirationDate(Date.parse(response.insurance_expiration_date))
            setLicensePlateNumber(response.license_plate_number)
            setLicensePlateExpirationDate(Date.parse(response.license_plate_expiration_date))
            setPickupCommission(response.pickup_commission)
            setVehicleType(response.vehicle_types.find(type => type.selection_id == response.vehicle_type))

            // toastr.clear()
        }

        contact.setup(response.contact)

        setIsLoading(false)
    }

    const debouncedWarnings = useCallback(
        debounce(() => {
            if(isEnabled && employeeId) {
                // toastr.clear()
                if(driversLicenseExpirationDate < now)
                    toast.error('Drivers License has passed expiration date', {toastId: `${employeeId}-dln-expiry`})
                else if(driversLicenseExpirationDate < threeMonthsFromNow)
                    toast.warn('Drivers License will expire soon', {toastId: `${employeeId}-dln-expiry`})

                if(licensePlateExpirationDate < now)
                    toast.error('License Plate has passed expiration date', {toastId: `${employeeId}-license-plate-expiry`})
                else if(licensePlateExpirationDate < threeMonthsFromNow)
                    toast.warn('License Plate will expire soon', {toastId: `${employeeId}-license-plate-expiry`})

                if(insuranceExpirationDate < now)
                    toast.error('Insurance has passed expiration date', {toastId: `${employeeId}-insurance-expiry`})
                else if(insuranceExpirationDate < threeMonthsFromNow)
                    toast.warn('Insurance will expire soon', {toastId: `${employeeId}-insurance-expiry`})
            }
        }, 1000), [driversLicenseExpirationDate, licensePlateExpirationDate, insuranceExpirationDate, isEnabled]
    )

    useEffect(() => {
        if(!isLoading)
            debouncedWarnings()
    }, [driversLicenseExpirationDate, licensePlateExpirationDate, insuranceExpirationDate, isEnabled, isLoading])

    const storeEmployee = async () => {
        if(employeeId ? !permissions.editBasic : !permissions.create) {
            toast.error(`Authenticated User does not have permission to ${employeeId ? 'update this Employee' : 'create Employee'}`, {autoClose: false});
            return;
        }

        setReadOnly(true)

        var data = {
            contact: {
                address: address.collect(),
                ...contact.collect(),
            },
            employee_id: employeeId,
        }

        if(permissions.editAdvanced)
            data = {
                ...data,
                birth_date: birthDate.toISOString(),
                employee_number: employeeNumber,
                is_driver: isDriver,
                is_enabled: isEnabled,
                permissions: employeePermissions,
                sin: SIN,
                start_date: startDate.toISOString()
            }

        if(permissions.editAdvanced && isDriver)
            data = {
                ...data,
                company_name: companyName,
                delivery_commission: deliveryCommission,
                drivers_license_expiration_date: driversLicenseExpirationDate.toISOString(),
                drivers_license_number: driversLicenseNumber,
                insurance_expiration_date: insuranceExpirationDate.toISOString(),
                insurance_number: insuranceNumber,
                license_plate_number: licensePlateNumber,
                license_plate_expiration_date: licensePlateExpirationDate.toISOString(),
                pickup_commission: pickupCommission,
                vehicle_type: vehicleType
            }

        try {
            const response = await (employeeId ? api.put(`/employees/${employeeId}`, data) : api.post('/employees', data))

            // toastr.clear()
            if(employeeId) {
                setUpdatedAt(response.updated_at)
                toast.success(`Employee ${employeeId} was successfully updated!`)
            } else {
                setReadOnly(true)
                toast.success(`Employee ${response.employee_id} was successfully created`, {
                    position: 'top-center',
                    onClose: () => configureEmployee(),
                })
            }
            setReadOnly(false)
        } catch (error) {
            console.log('Employee caught error', error)
            setReadOnly(false)
        }
    }

    if(isLoading)
        return <LoadingSpinner />

    return (
        <Fragment>
            <Navbar expand='md' variant='dark' bg='dark' className='justify-content-between'>
                <Navbar.Brand style={{paddingLeft: '15px'}}>{employeeId ? `Edit Employee ${employeeId}` : 'Create Employee'}</Navbar.Brand>
                {(employeeId && isDriver) &&
                    <Fragment>
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
                        <Nav>
                            <Nav.Link onClick={() => history.push(`/bills?filter[pickup_driver_id]=${employeeId}`)} variant='secondary' >Bills</Nav.Link>
                            <Nav.Link onClick={() => history.push(`/manifests?filter[driver_id]=${employeeId}`)} variant='secondary' >Manifests</Nav.Link>
                        </Nav>
                    </Fragment>
                }
            </Navbar>
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
                        <Button variant='info' disabled={!prevEmployeeId} onClick={() => {setIsLoading(true); history.push(`/employees/${prevEmployeeId}`)}}>
                            <i className='fas fa-arrow-circle-left'></i> Back - {prevEmployeeId}
                        </Button>
                        <Button variant='primary' onClick={storeEmployee} disabled={readOnly}>
                            <i className='fas fa-save'></i> Submit
                        </Button>
                        <Button variant='info' disabled={!nextEmployeeId} onClick={() => {setIsLoading(true); history.push(`/employees/${nextEmployeeId}`)}}>
                            Next - {nextEmployeeId} <i className='fas fa-arrow-circle-right'></i>
                        </Button>
                    </ButtonGroup>
                </Col>
            </Row>
        </Fragment>
    )
}
