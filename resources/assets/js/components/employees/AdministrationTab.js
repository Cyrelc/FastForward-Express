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

    return (
        <Card border='dark'>
            <Card.Header>
                <Row>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Employee Number</InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                name='employeeNumber'
                                onChange={props.handleChanges}
                                placeholder='Employee Number'
                                readOnly={props.readOnly}
                                value={props.employeeNumber}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <Form.Check
                            checked={props.enabled}
                            name='enabled'
                            value={props.enabled}
                            onChange={props.handleChanges}
                            label='Enabled'
                        />
                        <i className='fas fa-question' title={enabledTitle}></i>
                    </Col>
                    <Col md={3}>
                        <Form.Check
                            checked={props.driver}
                            name='driver'
                            value={props.driver}
                            onChange={props.handleChanges}
                            label='Driver'
                        />
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Additional Info</h4></Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>SIN</InputGroup.Text></InputGroup.Prepend>
                            <FormControl
                                name='SIN'
                                placeholder='Social Insurance Number'
                                value={props.SIN}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>Birth Date</InputGroup.Text></InputGroup.Prepend>
                            <DatePicker 
                                dateFormat='MMMM d, yyyy'
                                onChange={value => props.handleChanges({target: {name: 'birthDate', value: value}})}
                                showMonthDropdown
                                showYearDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={props.birthDate}
                                className='form-control'
                            />
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>Start Date</InputGroup.Text></InputGroup.Prepend>
                            <DatePicker 
                                dateFormat='MMMM d, yyyy'
                                onChange={value => props.handleChanges({target: {name: 'startDate', value: value}})}
                                showMonthDropdown
                                showYearDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={props.startDate}
                                className='form-control'
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
                                    <td><Form.Check checked={props.employeePermissions.createAccounts} name='createAccounts' onChange={props.handlePermissionChange} disabled={!props.enabled || props.readOnly}></Form.Check></td>
                                    <td>
                                        <Form.Check checked={props.employeePermissions.viewAccountsBasic} name='viewAccountsBasic' disabled={!props.enabled || props.readOnly} label='Basic' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.viewAccountsFull} name='viewAccountsFull' disabled={!props.enabled || props.readOnly} label='Full' onChange={props.handlePermissionChange}></Form.Check>
                                    </td>
                                    <td>
                                        <Form.Check checked={props.employeePermissions.editAccountsBasic} name='editAccountsBasic' disabled={!props.enabled || props.readOnly} label='Basic' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.editAccountsFull} name='editAccountsFull' disabled={!props.enabled || props.readOnly} label='Full' onChange={props.handlePermissionChange}></Form.Check>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Account Users</th>
                                    <td><Form.Check checked={props.employeePermissions.createAccountUsers} disabled={!props.enabled || props.readOnly} name='createAccountUsers' onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td></td>
                                    <td><Form.Check checked={props.employeePermissions.editAccountUsers} disabled={!props.enabled || props.readOnly} name='editAccountUsers' onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.deleteAccountUsers} disabled={!props.enabled || props.readOnly} name='deleteAccountUsers' onChange={props.handlePermissionChange}></Form.Check></td>
                                </tr>
                                <tr>
                                    <th>Administrator App Settings</th>
                                    <td></td>
                                    <td></td>
                                    <td><Form.Check checked={props.employeePermissions.editAppSettings} disabled={!props.enabled || props.readOnly} name='editAppSettings' onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Bills (All)</th>
                                    <td>
                                        <Form.Check checked={props.employeePermissions.createBillsBasic} name='createBillsBasic' label='Basic' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.createBillsFull} name='createBillsFull' label='Full' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check>
                                    </td>
                                    <td>
                                        <Form.Check checked={props.employeePermissions.viewBillsBasic} name='viewBillsBasic' disabled={!props.enabled || props.readOnly} label='Basic' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.viewBillsDispatch} name='viewBillsDispatch' disabled={!props.enabled || props.readOnly} label='Dispatch' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.viewBillsBilling} name='viewBillsBilling' disabled={!props.enabled || props.readOnly} label='Billing' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.viewBillsActivityLog} name='viewBillsActivityLog' disabled={!props.enabled || props.readOnly} label='ActivityLog' onChange={props.handlePermissionChange}></Form.Check>
                                    </td>
                                    <td>
                                        <Form.Check checked={props.employeePermissions.editBillsBasic} disabled={!props.enabled || props.readOnly} name='editBillsBasic' label='Basic' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.editBillsDispatch} disabled={!props.enabled || props.readOnly} name='editBillsDispatch' label='Dispatch' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.editBillsBilling} disabled={!props.enabled || props.readOnly} name='editBillsBilling' label='Billing' onChange={props.handlePermissionChange}></Form.Check>
                                    </td>
                                    <td><Form.Check checked={props.employeePermissions.deleteBills} disabled={!props.enabled || props.readOnly} name='deleteBills' onChange={props.handlePermissionChange}></Form.Check></td>
                                </tr>
                                <tr>
                                    <th>Chargebacks</th>
                                    <td></td>
                                    <td><Form.Check checked={props.employeePermissions.viewChargebacks} name='viewChargebacks' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.editChargebacks} name='editChargebacks' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Employees</th>
                                    <td><Form.Check checked={props.employeePermissions.createEmployees} name='createEmployees' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td>
                                        <Form.Check checked={props.employeePermissions.viewEmployeesBasic} name='viewEmployeesBasic' disabled={!props.enabled || props.readOnly} label='Basic' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.viewEmployeesAdvanced} name='viewEmployeesAdvanced' disabled={!props.enabled || props.readOnly} label='Full' onChange={props.handlePermissionChange}></Form.Check>
                                    </td>
                                    <td>
                                        <Form.Check checked={props.employeePermissions.editEmployeesBasic} name='editEmployeesBasic' disabled={!props.enabled || props.readOnly} label='Basic' onChange={props.handlePermissionChange}></Form.Check>
                                        <Form.Check checked={props.employeePermissions.editEmployeesAdvanced} name='editEmployeesAdvanced' disabled={!props.enabled || props.readOnly} label='Full' onChange={props.handlePermissionChange}></Form.Check>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Invoices</th>
                                    <td><Form.Check checked={props.employeePermissions.createInvoices} name='createInvoices' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.viewInvoices} name='viewInvoices' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.editInvoices} name='editInvoices' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.deleteInvoices} name='deleteInvoices' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                </tr>
                                <tr>
                                    <th>Manifests</th>
                                    <td><Form.Check checked={props.employeePermissions.createManifests} name='createManifests' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.viewManifests} name='viewManifests' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.editManifests} name='editManifests' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.deleteManifests} name='deleteManifests' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                </tr>
                                <tr>
                                    <th>Payments</th>
                                    <td><Form.Check checked={props.employeePermissions.createPayments} name='createPayments' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.viewPayments} name='viewPayments' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td><Form.Check checked={props.employeePermissions.editPayments}  name='editPayments' disabled={!props.enabled || props.readOnly} onChange={props.handlePermissionChange}></Form.Check></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </Table>
                    </Col>
                </Row>
            </Card.Header>
        </Card>
    )
}
