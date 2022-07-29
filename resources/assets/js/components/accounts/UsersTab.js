import React, {Component} from 'react'
import {Badge, Button, ButtonGroup, Card, Col, Modal, Row, Tab, Table, Tabs, ToastHeader} from 'react-bootstrap'

import ChangePasswordModal from '../partials/ChangePasswordModal'

import ActivityLogTab from '../partials/ActivityLogTab'
import Contact from '../partials/Contact'
import UserPermissionTab from './UserPermissionTab'

const initialState = {
    accountUserAddressFormatted: '',
    accountUserAddressLat: '',
    accountUserAddressLng: '',
    accountUserAddressName: '',
    accountUserAddressPlaceId: '',
    activityLog: [],
    belongsTo: [],
    contactId: '',
    emailAddresses: [],
    emailTypes: [],
    firstName: '',
    key: 'contact',
    lastName: '',
    permissions: [],
    position: '',
    phoneNumbers: [],
    phoneTypes: [],
    showAccountUserModal: false,
    showChangePasswordModal: false,
    userId: '',
    userPermissions: []
}

export default class UsersTab extends Component {
    constructor() {
        super()
        this.state = {
            ...initialState,
            phoneTypes: [],
            emailTypes: [],
            accountUsers: []
        }
        this.addAccountUser = this.addAccountUser.bind(this)
        this.deleteAccountUser = this.deleteAccountUser.bind(this)
        this.editAccountUser = this.editAccountUser.bind(this)
        this.handleChanges = this.handleChanges.bind(this)
        this.handlePermissionChange = this.handlePermissionChange.bind(this)
        this.impersonate = this.impersonate.bind(this)
        this.refreshAccountUsers = this.refreshAccountUsers.bind(this)
        this.storeAccountUser = this.storeAccountUser.bind(this)
    }

    addAccountUser() {
        if(!this.props.canCreateAccountUsers)
            return

        makeAjaxRequest('/users/getAccountUserModel/' + this.props.accountId, 'GET', null, response => {
            response = JSON.parse(response)
            const userInfo = {
                ...initialState,
                accountUserPermissions: response.account_user_model_permissions,
                emailAddresses: response.contact.emails,
                emailTypes: response.contact.email_types,
                permissions: response.permissions,
                phoneNumbers: response.contact.phone_numbers,
                phoneTypes: response.contact.phone_types,
                showAccountUserModal: true
            }
            this.setState(userInfo)
        })
    }

    componentDidMount() {
        this.refreshAccountUsers()
    }

    componentDidUpdate(prevProps) {
        if(prevProps.accountId != this.props.accountId) {
            this.setState({accountUsers: []})
            this.refreshAccountUsers()
        }
    }

    deleteAccountUser(contactId) {
        if(!this.props.canDeleteAccountUsers && contactId != this.props.authenticatedUserContact.contact_id)
            return

        const accountUser = this.state.accountUsers.find(user => user.contact_id === contactId)
        if(this.state.accountUsers.length <= 1)
            return
        if(confirm('Are you sure you wish to delete account user ' + accountUser.name + '?\nThis action can not be undone\n\n' + 
            'WARNING - If this user belongs to more than one account, they will need to be deleted on each account separately')) {
            makeAjaxRequest('/users/deleteAccountUser/' + contactId + '/' + this.props.accountId, 'GET', null, response => {
                this.refreshAccountUsers()
            })
        }
    }

    editAccountUser(contactId) {
        if(!this.props.canEditAccountUsers && contactId != this.props.authenticatedUserContact.contact_id)
            return;

        makeAjaxRequest('/users/getAccountUserModel/' + this.props.accountId + '/' + contactId, 'GET', null, response => {
            response = JSON.parse(response)
            const userInfo = {
                ...initialState,
                accountId: this.props.accountId,
                accountUsers: this.state.accountUsers,
                accountUserPermissions: response.account_user_model_permissions,
                activityLog: response.activity_log,
                belongsTo: response.belongs_to,
                contactId: response.contact.contact_id,
                emailAddresses: response.contact.emails,
                emailTypes: response.contact.email_types,
                firstName: response.contact.first_name,
                lastName: response.contact.last_name,
                permissions: response.permissions,
                position: response.contact.position,
                phoneNumbers: response.contact.phone_numbers,
                phoneTypes: response.contact.phone_types,
                showAccountUserModal: true
            }
            this.setState(userInfo)
        })
    }

    handleChanges(events) {
        if(!Array.isArray(events))
            events = [events]
        var temp = {}
        events.forEach(event => {
            const {name, type, value, checked} = event.target
            temp[name] = type === 'checkbox' ? checked : value
        })
        this.setState(temp)
    }

    handlePermissionChange(event) {
        const {name, value, checked} = event.target

        const accountUserPermissions = {...this.state.accountUserPermissions, [name]: checked}

        this.setState({accountUserPermissions: accountUserPermissions})
    }

    impersonate(contact_id) {
        const data = {
            contact_id,
            account_id: this.props.accountId
        }
        makeAjaxRequest('/users/impersonate', 'POST', data, response => {
            location.reload()
        })
    }

    refreshAccountUsers() {
        makeAjaxRequest('/users/getAccountUsers/' + this.props.accountId, 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({accountUsers: response, showAccountUserModal: false})
        })
    }

    storeAccountUser() {
        toastr.clear()
        const data = {
            account_id: this.props.accountId,
            contact_id: this.state.contactId,
            emails: this.state.emailAddresses,
            first_name: this.state.firstName,
            last_name: this.state.lastName,
            permissions: this.state.accountUserPermissions,
            phone_numbers: this.state.phoneNumbers,
            position: this.state.position,
        }

        if(this.state.contactId) {
            makeAjaxRequest('/users/storeAccountUser', 'POST', data, response => {
                this.refreshAccountUsers()
            })
        } else
            makeAjaxRequest('/users/checkIfAccountUserExists', 'POST', data, response => {
                if(response.email_in_use) {
                    if(confirm(`This email is already in use by another user: ${response.name} \n\n On accounts:\n ${response.accounts.map(account => `\t${account.account_number} - ${account.label}`)} \n\n Would you instead like to link the existing user to this account?`))
                        makeAjaxRequest('/users/linkAccountUser/' + response.contact_id + '/' + this.props.accountId, 'GET', null, response => {
                            this.refreshAccountUsers()
                        })
                } else
                    makeAjaxRequest('/users/storeAccountUser', 'POST', data, response => {
                        this.refreshAccountUsers()
                    })
            })
    }

    render() {
        return (
            <Row>
                <Col md={12}>
                    <Card>
                        <Card.Header><Card.Title>Manage Users</Card.Title></Card.Header>
                        <Card.Body>
                            <Table striped bordered>
                                <thead>
                                    <tr>
                                        <th>{this.props.canCreateAccountUsers && <Button size='sm' variant='success' onClick={this.addAccountUser} ><i className='fas fa-user-plus'></i></Button>}</th>
                                        <th>Name</th>
                                        <th>Primary Email</th>
                                        <th>Primary Phone</th>
                                        <th>Position</th>
                                        <th>Email Types</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {this.state.accountUsers.map(user =>
                                        <tr key={user.name}>
                                            <td>
                                                <ButtonGroup size='sm'>
                                                    {this.props.canDeleteAccountUsers &&
                                                        <Button
                                                            title={user.belongs_to_count == 1 ? 'Delete' : 'Unlink'}
                                                            variant='danger'
                                                            disabled={this.state.accountUsers.length <= 1 || !this.props.canDeleteAccountUsers || user.contact_id == this.props.authenticatedUserContact.contact_id}
                                                            onClick={() => this.deleteAccountUser(user.contact_id)}
                                                        >
                                                            <i className={user.belongs_to_count == 1 ? 'fas fa-trash' : 'fas fa-unlink'}></i>
                                                        </Button>
                                                    }
                                                    <Button title='Edit' variant='warning' disabled={!this.props.canEditAccountUsers && user.contact_id != this.props.authenticatedUserContact.contact_id} onClick={() => this.editAccountUser(user.contact_id)}><i className='fas fa-edit'></i></Button>
                                                    <Button
                                                        title='Change Password'
                                                        variant='primary'
                                                        disabled={!this.props.canEditAccountUserPermissions && user.contact_id != this.props.authenticatedUserContact.contact_id}
                                                        onClick={() => this.handleChanges([{target: {name: 'showChangePasswordModal', type: 'boolean', value: true}}, {target: {name: 'userId', type: 'number', value: user.user_id}}])}
                                                    >
                                                        <i className='fas fa-key'></i>
                                                    </Button>
                                                    {this.props.canImpersonateAccountUsers &&
                                                        <Button
                                                            title='Impersonate'
                                                            variant='info'
                                                            onClick={() => this.impersonate(user.contact_id)}
                                                        >
                                                            <i className='fas fa-people-arrows'></i>
                                                        </Button>
                                                    }
                                                </ButtonGroup>
                                            </td>
                                            <td>{user.enabled ? <Badge bg='success'>Enabled</Badge> : <Badge bg='secondary'>Disabled</Badge>} {user.name}</td>
                                            <td>{user.primary_email}</td>
                                            <td>{user.primary_phone}</td>
                                            <td>{user.position}</td>
                                            <td>{user.roles.map(role => <Badge pill bg='info' text='dark'>{role}</Badge>)}</td>
                                        </tr>
                                    )}
                                </tbody>
                            </Table>
                        </Card.Body>
                    </Card>
                </Col>
                <Modal show={this.state.showAccountUserModal} onHide={() => this.handleChanges({target: {name: 'showAccountUserModal', type: 'boolean', value: false}})} size='xl'>
                    <Modal.Header closeButton>
                        <Modal.Title>{this.state.contactId ? 'Edit User ' + this.state.firstName + ' ' + this.state.lastName : 'Create User'}</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <Row>
                            <Col md={12}>
                                <Tabs id='accountUserTabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.handleChanges({target: {name: 'key', type: 'string', value: key}})}>
                                    <Tab eventKey='contact' title={<h4>Contact Info</h4>}>
                                        <Contact
                                            addressId='accountUser'
                                            firstName={this.state.firstName}
                                            lastName={this.state.lastName}
                                            position={this.state.position}
                                            address={{
                                                type: 'Address',
                                                name: this.state.accountUserAddressName,
                                                formatted: this.state.accountUserAddressFormatted,
                                                lat: this.state.accountUserAddressLat,
                                                lng: this.state.accountUserAddressLng,
                                                placeId: this.state.accountUserAddressPlaceId
                                            }}
                                            phoneNumbers={this.state.phoneNumbers}
                                            emailAddresses={this.state.emailAddresses}
                                            phoneTypes={this.state.phoneTypes}
                                            emailTypes={this.state.emailTypes}

                                            handleChanges={this.handleChanges}
                                            readOnly={!this.state.permissions.editBasic && (!this.state.contactId && !this.props.canCreateAccountUsers)}
                                        />
                                    </Tab>
                                    {(this.state.permissions.viewPermissions || this.state.permissions.editPermissions) &&
                                        <Tab eventKey='permissions' title={<h4>Permissions</h4>}>
                                            <UserPermissionTab
                                                belongsTo={this.state.belongsTo}
                                                canBeParent={this.props.canBeParent}
                                                accountUserPermissions={this.state.accountUserPermissions}

                                                handlePermissionChange={this.handlePermissionChange}
                                                readOnly={!this.state.permissions.editPermissions}
                                            />
                                        </Tab>
                                    }
                                    {this.state.activityLog && this.state.permissions.viewActivityLog &&
                                        <Tab eventKey='activityLog' title={<h4>Activity Log</h4>}>
                                            <ActivityLogTab
                                                activityLog={this.state.activityLog}
                                            />
                                        </Tab>
                                    }
                                </Tabs>
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer className='justify-content-md-center'>
                        <ButtonGroup>
                            <Button variant='light' onClick={() => this.handleChanges({target: {name: 'showAccountUserModal', type: 'boolean', value: false}})}>Cancel</Button>
                            <Button variant='success' onClick={this.storeAccountUser}>Submit</Button>
                        </ButtonGroup>
                    </Modal.Footer>
                </Modal>
                <ChangePasswordModal
                    show={this.state.showChangePasswordModal}
                    userId={this.state.userId}
                    toggleModal={() => this.handleChanges({target: {name: 'showChangePasswordModal', type: 'boolean', value: false}})}
                />
            </Row>
        )
    }
}
