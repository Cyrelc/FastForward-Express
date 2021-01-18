import React, {Component} from 'react'
import {Button, ButtonGroup, Card, Col, Modal, Row, Tab, Table, Tabs} from 'react-bootstrap'

import ChangePasswordModal from '../partials/ChangePasswordModal'

import ActivityLogTab from '../partials/ActivityLogTab'
import Contact from '../partials/Contact'
import UserPermissionTab from './UserPermissionTab'

const initialState = {
    firstName: '',
    key: 'contact',
    lastName: '',
    position: '',
    accounts: [],
    accountUserAddressFormatted: '',
    accountUserAddressLat: '',
    accountUserAddressLng: '',
    accountUserAddressName: '',
    accountUserAddressPlaceId: '',
    activityLog: [],
    belongsTo: [],
    phoneNumbers: [{phone: '', extension: '', type: '', is_primary: true}],
    emailAddresses: [{email: '', type: '', is_primary: true}],
    mode: 'create',
    contactId: '',
    showAccountUserModal: false,
    showChangePasswordModal: false,
    userId: ''
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
        this.refreshAccountUsers = this.refreshAccountUsers.bind(this)
        this.storeAccountUser = this.storeAccountUser.bind(this)
    }

    addAccountUser() {
        this.setState({
            ...initialState,
            showAccountUserModal: true
        })
    }

    componentDidMount() {
        makeAjaxRequest('/getList/selections/phone_type', 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({phoneTypes: response})
        })
        makeAjaxRequest('/getList/selections/contact_type', 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({emailTypes: response})
        })
        this.refreshAccountUsers()
    }

    componentDidUpdate(prevProps) {
        if(prevProps.accountId != this.props.accountId) {
            this.setState({accountUsers: []})
            this.refreshAccountUsers()
        }
    }

    deleteAccountUser(contactId) {
        const accountUser = this.state.accountUsers.find(user => user.contact_id === contactId)
        if(this.state.accountUsers.length <= 1)
            return
        if(confirm('Are you sure you wish to delete account user ' + accountUser.name + '?\nThis action can not be undone')) {
            makeAjaxRequest('/users/deleteAccountUser/' + contactId, 'GET', null, response => {
                this.refreshAccountUsers()
            })
        }
    }

    editAccountUser(contactId) {
        makeAjaxRequest('/users/getAccountUserModel/' + contactId, 'GET', null, response => {
            response = JSON.parse(response)
            const userInfo = {
                ...initialState,
                accountId: response.account_id,
                accountUsers: this.state.accountUsers,
                activityLog: response.activity_log,
                belongsTo: response.belongs_to,
                contactId: response.contact.contact_id,
                firstName: response.contact.first_name,
                lastName: response.contact.last_name,
                position: response.contact.position,
                phoneNumbers: response.contact.phone_numbers,
                emailAddresses: response.contact.emails,
                mode: 'edit',
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
            if(name === 'key')
                window.location.hash = value
            temp[name] = type === 'checkbox' ? checked : value
        })
        this.setState(temp)
    }

    refreshAccountUsers() {
        makeAjaxRequest('/users/getAccountUsers/' + this.props.accountId, 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({accountUsers: response, showAccountUserModal: false})
        })
    }

    storeAccountUser() {
        const data = {
            account_id: this.props.accountId,
            contact_id: this.state.contactId,
            emails: this.state.emailAddresses,
            first_name: this.state.firstName,
            last_name: this.state.lastName,
            phone_numbers: this.state.phoneNumbers,
            position: this.state.position,
        }
        makeAjaxRequest('/users/storeAccountUser', 'POST', data, response => {
            this.refreshAccountUsers()
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
                                        <th><Button size='sm' variant='success' onClick={this.addAccountUser}><i className='fas fa-user-plus'></i></Button></th>
                                        <th>Name</th>
                                        <th>Primary Email</th>
                                        <th>Primary Phone</th>
                                        <th>Position</th>
                                        <th>Roles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {this.state.accountUsers.map(user =>
                                        <tr key={user.name}>
                                            <td>
                                                <ButtonGroup size='sm'>
                                                    <Button title='Delete' variant='danger' disabled={this.state.accountUsers.length <= 1 || this.props.readOnly} onClick={() => this.deleteAccountUser(user.contact_id)}><i className='fas fa-trash'></i></Button>
                                                    <Button title='Edit' variant='warning' disabled={this.props.readOnly} onClick={() => this.editAccountUser(user.contact_id)}><i className='fas fa-edit'></i></Button>
                                                    <Button
                                                        title='Change Password'
                                                        variant='primary'
                                                        disabled={this.props.readOnly}
                                                        onClick={() => this.handleChanges([{target: {name: 'showChangePasswordModal', type: 'boolean', value: true}}, {target: {name: 'userId', type: 'number', value: user.user_id}}])}
                                                    ><i className='fas fa-key'></i></Button>
                                                </ButtonGroup>
                                            </td>
                                            <td>{user.name}</td>
                                            <td>{user.primary_email}</td>
                                            <td>{user.primary_phone}</td>
                                            <td>{user.position}</td>
                                            <td></td>
                                        </tr>
                                    )}
                                </tbody>
                            </Table>
                        </Card.Body>
                    </Card>
                </Col>
                <Modal show={this.state.showAccountUserModal} onHide={() => this.handleChanges({target: {name: 'showAccountUserModal', type: 'boolean', value: false}})} size='xl'>
                    <Modal.Header closeButton>
                        <Modal.Title>{this.state.mode === 'edit' ? 'Edit User ' + this.state.firstName + ' ' + this.state.lastName : 'Create User'}</Modal.Title>
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
                                            readOnly={this.props.readOnly}
                                        />
                                    </Tab>
                                    <Tab eventKey='permissions' title={<h4>Permissions</h4>}>
                                        <UserPermissionTab
                                            accounts={this.state.accounts}
                                            belongsTo={this.state.belongsTo}
                                        />
                                    </Tab>
                                    {this.state.activityLog &&
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
