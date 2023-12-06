import React from 'react'
import {Card, Row, Col, InputGroup, FormControl, Form, Table} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

export default function AdministrationTab(props) {
    const enabledTitle = 'Enabled Employees can log in to the system and have the following basic permissions:\n\n' +
        ' - Edit their personal details (name, phone numbers, emails, etc.)\n' +
        ' - Edit, add, remove emergency contacts\n' +
        ' - Change their own password\n'

    const driverTitle = 'Employees marked as Driver will have the following basic permissions:\n\n' +
        ' - View / Print Manifests assigned to them\n' +
        ' - See basic pertinent information regarding bills assigned to them\n' +
        ' - View bills where they are assigned as either pickup or delivery driver'

    const {
        birthDate,
        employeeNumber,
        employeePermissions,
        isDriver,
        isEnabled,
        SIN,
        startDate,

        setBirthDate,
        setEmployeeNumber,
        setEmployeePermissions,
        setIsDriver,
        setIsEnabled,
        setSIN,
        setStartDate,

        readOnly
    } = props

    const handlePermissionChange = event => {
        const {name, value, checked} = event.target

        const newEmployeePermissions = {...employeePermissions, [name]: checked}
        setEmployeePermissions(newEmployeePermissions)
    }

    const PermissionCheckbox = (props) => {
        return <Form.Check
            checked={employeePermissions[props.name]}
            label={props.label ? props.label : null}
            name={props.name}
            onChange={handlePermissionChange}
            disabled={!isEnabled || readOnly}
        ></Form.Check>
    }

    return (
        <Card border='dark'>
            <Card.Header>
                <Row>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Employee Number</InputGroup.Text>
                            <FormControl
                                name='employeeNumber'
                                onChange={setEmployeeNumber}
                                placeholder='Employee Number'
                                readOnly={readOnly}
                                value={employeeNumber}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <Form.Check
                                checked={isEnabled}
                                name='enabled'
                                value={isEnabled}
                                onChange={() => setIsEnabled(!isEnabled)}
                                label='Enabled'
                            />
                            <InputGroup.Text><i className='fas fa-question-circle' title={enabledTitle}></i></InputGroup.Text>
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <Form.Check
                            checked={isDriver}
                            name='driver'
                            value={isDriver}
                            onChange={() => setIsDriver(!isDriver)}
                            label='Driver'
                        />
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Additional Info</h4></Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>SIN</InputGroup.Text>
                            <FormControl
                                name='SIN'
                                placeholder='Social Insurance Number'
                                value={SIN}
                                onChange={setSIN}
                                readOnly={readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Birth Date</InputGroup.Text>
                            <DatePicker 
                                className='form-control'
                                dateFormat='MMMM d, yyyy'
                                monthDropdownItemNumber={15}
                                onChange={setBirthDate}
                                scrollableMonthDropdown
                                selected={birthDate}
                                showMonthDropdown
                                showYearDropdown
                                wrapperClassName='form-control'
                            />
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Start Date</InputGroup.Text>
                            <DatePicker 
                                className='form-control'
                                dateFormat='MMMM d, yyyy'
                                monthDropdownItemNumber={15}
                                onChange={setStartDate}
                                scrollableMonthDropdown
                                selected={startDate}
                                showMonthDropdown
                                showYearDropdown
                                wrapperClassName='form-control'
                            />
                        </InputGroup>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Permissions</h4></Col>
                    <Col md={10}>
                        <Table striped bordered size='sm' variant='dark'>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Create</th>
                                    <th>View</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                {/* Accounts */}
                                <tr>
                                    <th>Accounts</th>
                                    <td><PermissionCheckbox name='createAccounts'></PermissionCheckbox></td>
                                    <td>
                                        <PermissionCheckbox name='viewAccountsBasic' label='Basic'></PermissionCheckbox>
                                        <PermissionCheckbox name='viewAccountsFull' label='Full'></PermissionCheckbox>
                                    </td>
                                    <td>
                                        <PermissionCheckbox name='editAccountsBasic' label='Basic'></PermissionCheckbox>
                                        <PermissionCheckbox name='editAccountsFull' label='Full'></PermissionCheckbox>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Account Users</th>
                                    <td><PermissionCheckbox name='createAccountUsers'></PermissionCheckbox></td>
                                    <td></td>
                                    <td>
                                        <PermissionCheckbox name='editAccountUsers' label='Edit'></PermissionCheckbox>
                                        <PermissionCheckbox name='impersonateAccountUsers' label='Impersonate'></PermissionCheckbox>
                                    </td>
                                    <td><PermissionCheckbox name='deleteAccountUsers'></PermissionCheckbox></td>
                                </tr>
                                <tr>
                                    <th>Administrator App Settings</th>
                                    <td></td>
                                    <td></td>
                                    <td><PermissionCheckbox name='editAppSettings'></PermissionCheckbox></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Bills (All)</th>
                                    <td>
                                        <PermissionCheckbox name='createBillsBasic' label='Basic'></PermissionCheckbox>
                                        <PermissionCheckbox name='createBillsFull' label='Full'></PermissionCheckbox>
                                    </td>
                                    <td>
                                        <PermissionCheckbox name='viewBillsBasic' label='Basic'></PermissionCheckbox>
                                        <PermissionCheckbox name='viewBillsDispatch' label='Dispatch'></PermissionCheckbox>
                                        <PermissionCheckbox name='viewBillsBilling' label='Billing'></PermissionCheckbox>
                                        <PermissionCheckbox name='viewBillsActivityLog' label='ActivityLog'></PermissionCheckbox>
                                    </td>
                                    <td>
                                        <PermissionCheckbox name='editBillsBasic' label='Basic'></PermissionCheckbox>
                                        <PermissionCheckbox name='editBillsDispatch' label='Dispatch'></PermissionCheckbox>
                                        <PermissionCheckbox name='editBillsBilling' label='Billing'></PermissionCheckbox>
                                    </td>
                                    <td><PermissionCheckbox name='deleteBills'></PermissionCheckbox></td>
                                </tr>
                                <tr>
                                    <th>Chargebacks</th>
                                    <td></td>
                                    <td><PermissionCheckbox name='viewChargebacks'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='editChargebacks'></PermissionCheckbox></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Employees</th>
                                    <td><PermissionCheckbox name='createEmployees'></PermissionCheckbox></td>
                                    <td>
                                        <PermissionCheckbox name='viewEmployeesBasic' label='Basic'></PermissionCheckbox>
                                        <PermissionCheckbox name='viewEmployeesAdvanced' label='Full'></PermissionCheckbox>
                                    </td>
                                    <td>
                                        <PermissionCheckbox name='editEmployeesBasic' label='Basic'></PermissionCheckbox>
                                        <PermissionCheckbox name='editEmployeesAdvanced' label='Full'></PermissionCheckbox>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Invoices</th>
                                    <td><PermissionCheckbox name='createInvoices'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='viewInvoices'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='editInvoices'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='deleteInvoices'></PermissionCheckbox></td>
                                </tr>
                                <tr>
                                    <th>Manifests</th>
                                    <td><PermissionCheckbox name='createManifests'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='viewManifests'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='editManifests'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='deleteManifests'></PermissionCheckbox></td>
                                </tr>
                                <tr>
                                    <th>Payments</th>
                                    <td><PermissionCheckbox name='createPayments'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='viewPayments'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='editPayments'></PermissionCheckbox></td>
                                    <td><PermissionCheckbox name='revertPayments'></PermissionCheckbox></td>
                                </tr>
                            </tbody>
                        </Table>
                    </Col>
                </Row>
            </Card.Header>
        </Card>
    )
}
