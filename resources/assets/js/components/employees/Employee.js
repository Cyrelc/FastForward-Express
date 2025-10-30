import React, {Fragment, useCallback, useEffect, useState} from 'react'
import {Button, ButtonGroup, Col, ListGroup, Nav, Navbar, Tab, Tabs, Row} from 'react-bootstrap'
import {debounce} from 'lodash'
import {DateTime} from 'luxon'
import {toast} from 'react-toastify'
import {useHistory, useLocation} from 'react-router-dom'

import ActivityLogTab from '../partials/ActivityLogTab'
import AdministrationTab from './AdministrationTab'
import BasicTab from './BasicTab'
import DriverTab from './DriverTab'
import LoadingSpinner from '../partials/LoadingSpinner'

import {useAPI} from '../../contexts/APIContext'
import useAddress from '../partials/Hooks/useAddress'
import useContact from '../partials/Hooks/useContact'
import useEmployee from './useEmployee'

export default function Employee(props) {
    const [isLoading, setIsLoading] = useState(true)
    const [key, setKey] = useState('basic')
    const [nextEmployeeId, setNextEmployeeId] = useState(null)
    const [permissions, setPermissions] = useState([])
    const [prevEmployeeId, setPrevEmployeeId] = useState('')
    const [readOnly, setReadOnly] = useState(false)
    const [updatedAt, setUpdatedAt] = useState('')

    const address = useAddress()
    const api = useAPI()
    const contact = useContact()
    const employee = useEmployee()
    const history = useHistory()
    const location = useLocation()
    const now = DateTime.now().toJSDate()
    const threeMonthsFromNow = DateTime.now().plus({month: 3}).toJSDate()
    const {
        driversLicenseExpirationDate,
        employeeId,
        insuranceExpirationDate,
        isDriver,
        isEnabled,
        licensePlateExpirationDate,
    } = employee

    useEffect(() => {
        setKey(location.hash.substr(1))
    }, [location.hash])

    useEffect(() => {
        setIsLoading(true)
        const {match: {params}} = props
        setKey(window.location.hash?.substr(1) || 'basic')

        const getCreate = async () => {
            await api.get('/employees/create').then(
                data => {
                    address.reset()
                    contact.reset()
                    employee.reset()
                    employee.setupCreate(data)
                    setPermissions(data.permissions)
                    setIsLoading(false)
                }
            )
        }

        const getEmployee = async (employeeId) => {
            await api.get(`/employees/${params.employeeId}`)
                .then(data => {
                    address.setup(data.contact.address)
                    contact.setup(data.contact)
                    employee.setup(data)
                    setPermissions(data.permissions)
                    setUpdatedAt(data.updated_at)
                    setIsLoading(false)
                })
        }

        if(params.employeeId) {
            document.title = `Edit Employee - ${params.employeeId}`
            let sortedEmployees = localStorage.getItem('employees.sortedList') ?? ''
            sortedEmployees = sortedEmployees.split(',').map(index => parseInt(index))

            const thisEmployeeIndex = sortedEmployees.findIndex(employee_id => employee_id === params.employeeId)
            const prevEmployeeId = thisEmployeeIndex <= 0 ? null : sortedEmployees[thisEmployeeIndex - 1]
            const nextEmployeeId = (thisEmployeeIndex < 0 || thisEmployeeIndex === sortedEmployees.length - 1) ? null : sortedEmployees[thisEmployeeIndex + 1]
            setNextEmployeeId(nextEmployeeId)
            setPrevEmployeeId(prevEmployeeId)
            getEmployee()
        } else {
            document.title = `Create Employee`
            getCreate()
        }
    }, [props.match.params.employeeId])

    const setTabKey = tabKey => {
        window.location.hash = tabKey
        setKey(tabKey)
    }

    const debouncedWarnings = useCallback(
        debounce(() => {
            if(isDriver && isEnabled && employeeId) {
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
            ...employeeId ? {employee_id: employeeId} : {},
            ... permissions.editAdvanced ? employee.collectAdvanced() : [],
            ...(permissions.editAdvanced && employee.isDriver) ? employee.collectDriver() : []
        }

        try {
            if(employeeId) {
                const response = await api.put(`/employees/${employeeId}`, data)
                setUpdatedAt(response.updated_at)
                toast.success(`Employee ${employeeId} was successfully updated!`)
            } else {
                setReadOnly(true)
                const response = await api.post('/employees', data)
                toast.success(`Employee successfully created`, {
                    position: 'top-center',
                    onClose: () => history.push(`/employees/${response.employee_id}`),
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
                            {employee.licensePlateExpirationDate < now &&
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
                                    employee={employee}
                                    readOnly={!permissions.editAdvanced}
                                />
                            </Tab>
                        }
                        {permissions.editAdvanced &&
                            <Tab eventKey='admin' title={<h4>Administration</h4>}>
                                <AdministrationTab
                                    employee={employee}
                                />
                            </Tab>
                        }
                        {(employee.activityLog && permissions.viewActivityLog) &&
                            <Tab eventKey='activity_log' title={<h4>Activity Log  <i className='fas fa-book-open'></i></h4>}>
                                <ActivityLogTab
                                    activityLog={employee.activityLog}
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
