import React from 'react'
import {Card, Col, Form, Row, Table} from 'react-bootstrap'

import {useUser} from '../../../contexts/UserContext'

export default function UserPermissionTab(props) {
    const accountUserEnabledDescription = 'Enabled Users can log in to the system and have the following basic permissions:\n\n' +
        '\t- Edit their personal details (name, phone numbers, emails, etc.)\n' +
        '\t- Change their password\n' +
        '\t- View basic and invoicing information for the account\n' +
        '\t- View the list of account users, but not their details'

    const hasChildren = props.belongsTo.some(account => account.children.length > 0) || props.canBeParent

    const {contact} = useUser()

    return (
        <Row>
            <Col md={6}>
                <Table striped bordered size='sm' variant='dark'>
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Permission Name</th>
                            <th>My Accounts</th>
                            {hasChildren == true && <th>Children of<br/>My Accounts</th> }
                        </tr>
                    </thead>
                    <tbody>
                        <tr key='is_enabled'>
                            <th>Enabled <i className='fas fa-question-circle' title={accountUserEnabledDescription}></i></th>
                            <td></td>
                            <td>
                                <Form.Check
                                    checked={props.accountUserPermissions.is_enabled}
                                    name='is_enabled'
                                    onChange={props.handlePermissionChange}
                                    disabled={props.readOnly || contact?.contact_id == props.contactId}
                                ></Form.Check>
                            </td>
                            {hasChildren == true && <td></td>}
                        </tr>
                        {/* Accounts */}
                        <tr key='accounts.edit.basic.my'>
                            <th rowSpan={hasChildren ? 4 : 3}>Account</th>
                            <td>Edit Basic <i className='fas fa-question-circle' title='Edit any value on the "Basic" account tab'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.editAccountBasicMy} name='editAccountBasicMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.editAccountBasicChildren} name='editAccountBasicChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        {hasChildren == true &&
                            <tr key='accounts.edit.basic.children'>
                                <td>View <i className='fas fa-question-circle' title='Ability to view child accounts basic and invoicing tabs'></i></td>
                                <td></td>
                                {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.viewAccountsBasicChildren} name='viewAccountsBasicChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                            </tr>
                        }
                        <tr>
                            <td>Edit Invoice Settings <i className='fas fa-question-circle' title='Edit any value on the "Invoicing" tab'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.editAccountInvoiceSettingsMy} name='editAccountInvoiceSettingsMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.editAccountInvoiceSettingsChildren} name='editAccountInvoiceSettingsChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        <tr>
                            <td>View Activity Log <i className='fas fa-question-circle' title='View the activity log of the account'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.viewAccountActivityLogMy} name='viewAccountActivityLogMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            { hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.viewAccountActivityLogChildren} name='viewAccountActivityLogChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        {/* Bills */}
                        <tr>
                            <th rowSpan='2'>Bills</th>
                            <td>Create Bills <i className='fas fa-question-circle' title='Create bills assigned to this account'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.createBillsMy} name='createBillsMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.createBillsChildren} name='createBillsChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        <tr>
                            <td>View Bills <i className='fas fa-question-circle' title='View all bills assigned to this account'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.viewBillsMy} name='viewBillsMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.viewBillsChildren} name='viewBillsChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        {/* Invoices */}
                        <tr>
                            <th>Invoices</th>
                            <td>View / Print <i className='fas fa-question-circle' title='View invoices or print them'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.viewInvoicesMy} name='viewInvoicesMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.viewInvoicesChildren} name='viewInvoicesChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        {/* Payments */}
                        <tr>
                            <th rowSpan='2'>Payments</th>
                            <td>View Payments <i className='fas fa-question-circle' title='View and print payments'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.viewPaymentsMy} name='viewPaymentsMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.viewPaymentsChildren} name='viewPaymentsChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        <tr>
                            <td>Manage Payment Methods <i className='fas fa-question-circle' title='Add, edit, and delete credit card information'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.editPaymentsMy} name='editPaymentsMy' onChange={props.handlePermissionChange} disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.editPaymentsChildren} name='editPaymentsChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        {/* Users */}
                        <tr>
                            <th rowSpan='5'>Users</th>
                            <td>Edit Users <i className='fas fa-question-circle' title='Edit, disable, and update the password of all users assigned to the account'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.editAccountUsersMy} name='editAccountUsersMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.editAccountUsersChildren} name='editAccountUsersChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        <tr>
                            <td>View Permissions <i className='fas fa-question-circle' title='Ability to view the permissions of users on their account or child accounts (if applicable)'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.viewAccountUserPermissionsMy} name='viewAccountUserPermissionsMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.viewAccountUserPermissionsChildren} name='viewAccountUserPermissionsChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        <tr>
                            <td>Modify Permissions <i className='fas fa-question-circle' title='Ability to modify the permissions of users on their account or child accounts (if applicable)'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.editAccountUserPermissionsMy} name='editAccountUserPermissionsMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.editAccountUserPermissionsChildren} name='editAccountUserPermissionsChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>} 
                        </tr>
                        <tr>
                            <td>Create Users <i className='fas fa-question-circle' title='Create users assigned to this account'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.createAccountUsersMy} name='createAccountUsersMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.createAccountUsersChildren} name='createAccountUsersChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                        <tr>
                            <td>View Activity Log <i className='fas fa-question-circle' title='View Activity Log for Users belonging to this account'></i></td>
                            <td><Form.Check checked={props.accountUserPermissions.viewAccountUserActivityLogsMy} name='viewAccountUserActivityLogsMy' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>
                            {hasChildren == true && <td><Form.Check checked={props.accountUserPermissions.viewAccountUserActivityLogsChildren} name='viewAccountUserActivityLogsChildren' onChange={props.handlePermissionChange}  disabled={props.readOnly || !props.accountUserPermissions.is_enabled}></Form.Check></td>}
                        </tr>
                    </tbody>
                </Table>
            </Col>
            <Col md={6}>
                <Card>
                    <Card.Header>
                        <Card.Title>Accounts</Card.Title>
                        <ul>
                            <li key='myAccountExample'>My Account
                                <ul>
                                    <li key='childAccountExample'>Child Account (If present)</li>
                                </ul>
                            </li>
                        </ul>
                    </Card.Header>
                    <Card.Body>
                        <ul>
                            {props.belongsTo.map(({account, children}) =>
                                <li key={'parent_' + account.account_id} style={{color: account.active ? 'black' : 'red'}}>
                                    {`${account.account_number} - ${account.name}`}
                                    <ul key={account.account_id + '.children'}>
                                        {children.map(({ account: child }) =>
                                            <li key={'child_' + child.account_id} style={{color: child.active ? 'black' : 'red'}}>{`${child.account_number} - ${child.name}`}</li>
                                        )}
                                    </ul>
                                </li>
                            )}
                        </ul>
                    </Card.Body>
                </Card>
            </Col>
        </Row>
    )
}
