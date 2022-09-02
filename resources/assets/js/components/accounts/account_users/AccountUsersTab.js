import React, {useEffect, useState} from 'react'
import {Badge, Button, ButtonGroup, Card, Col, Dropdown, Row, Table} from 'react-bootstrap'

import ChangePasswordModal from '../../partials/ChangePasswordModal'
import EditAccountUser from './EditAccountUser'

export default function AccountUsersTab(props) {
    const [accountUsers, setAccountUsers] = useState([])
    const [contactId, setContactId] = useState('')
    const [showAccountUserModal, setShowAccountUserModal] = useState(false)
    const [showChangePasswordModal, setShowChangePasswordModal] = useState(false)
    const [userId, setUserId] = useState(undefined)

    useEffect(() => {
        refreshAccountUsers()
    }, [])

    useEffect(() => {
        setAccountUsers([])
        refreshAccountUsers()
    }, [props.accountId])

    const addAccountUser = () => {
        if(!props.canCreateAccountUsers)
            return
        setShowAccountUserModal(true)
    }

    const deleteAccountUser = contactId => {
        if(!props.canDeleteAccountUsers && contactId != props.authenticatedUserContact.contact_id)
            return

        const accountUser = accountUsers.find(user => user.contact_id === contactId)
        if(accountUsers.length <= 1)
            return
        // TODO - this account user length check is incorrect
        if(confirm(`Are you sure you wish to ${accountUser.belongs_to_count > 1 ? 'delete' : 'unlink'} account user ${accountUser.name}?\nThis action can not be undone\n\n` + 
            'WARNING - If this user belongs to more than one account, they will need to be deleted on each account separately')) {
            makeAjaxRequest(`/users/deleteAccountUser/${contactId}/${props.accountId}`, 'GET', null, response => {
                refreshAccountUsers()
            })
        }
    }

    const editAccountUser = contactId => {
        if(!props.canEditAccountUsers && contactId != props.authenticatedUserContact.contact_id)
            return;
        
        setContactId(contactId)
        setShowAccountUserModal(true)
    }

    const hasAnyPermissions = contact_id => {
        const testPermissions = [
            props.canDeleteAccountUsers,
            props.canImpersonateAccountUsers,
            props.canEditAccountUsers,
            props.canEditAccountUserPermissions,
            contact_id === props.authenticatedUserContact.contact_id
        ]

        return testPermissions.some(element => element == true)
    }

    const hideAccountUserModal = () => {
        setShowAccountUserModal(false)
        setContactId('')
    }

    const impersonate = contact_id => {
        const data = {
            contact_id,
            account_id: props.accountId
        }
        makeAjaxRequest('/users/impersonate', 'POST', data, response => {
            location.reload()
        })
    }

    const refreshAccountUsers = () => {
        makeAjaxRequest(`/users/getAccountUsers/${props.accountId}`, 'GET', null, response => {
            response = JSON.parse(response)
            setAccountUsers(response)
            setShowAccountUserModal(false)
        })
    }

    const setPrimary = contactId => {
        makeAjaxRequest(`/users/${props.accountId}/${contactId}/setPrimary`, 'POST', null, response => {
            refreshAccountUsers()
        })
    }

    return (
        <Row>
            <Col md={12}>
                <Card>
                    <Card.Header><Card.Title>Manage Users</Card.Title></Card.Header>
                    <Card.Body>
                        <Table striped bordered>
                            <thead>
                                <tr>
                                    <th width='50px'>
                                        {props.canCreateAccountUsers &&
                                            <Button size='sm' variant='success' onClick={addAccountUser} >
                                                <i className='fas fa-user-plus'></i>
                                            </Button>
                                        }
                                    </th>
                                    <th>Name</th>
                                    <th>Primary Email</th>
                                    <th>Primary Phone</th>
                                    <th>Position</th>
                                    <th>Email Types</th>
                                </tr>
                            </thead>
                            <tbody>
                                {accountUsers.map(user =>
                                    <tr key={user.name}>
                                        <td>
                                            {hasAnyPermissions(user.contact_id) && 
                                                <Dropdown>
                                                    <Dropdown.Toggle size='sm' variant='secondary' id='manage-account-user-menu'>
                                                        <i className='fas fa-bars'></i>
                                                    </Dropdown.Toggle>
                                                    <Dropdown.Menu>
                                                        {(props.canEditAccountUsers || user.contact_id == props.authenticatedUserContact.contact_id) &&
                                                            <Dropdown.Item
                                                                onClick={() => editAccountUser(user.contact_id)}
                                                                title='Edit'
                                                            >
                                                                <i className='fas fa-edit'></i> Edit
                                                            </Dropdown.Item>
                                                        }
                                                        {(props.canEditAccountUserPermissions || user.contact_id == props.authenticatedUserContact.contact_id) &&
                                                            <Dropdown.Item
                                                                onClick={() => {
                                                                    setUserId(user.user_id)
                                                                    setShowChangePasswordModal(true)
                                                                }}
                                                                title='Change Password'
                                                            >
                                                                <i className='fas fa-key'></i> Change Password
                                                            </Dropdown.Item>
                                                        }
                                                        {props.canEditAccountUsers && !user.is_primary &&
                                                            <Dropdown.Item
                                                                onClick={() => setPrimary(user.contact_id)}
                                                                title='Set As Primary'
                                                            >
                                                                <i className='fas fa-star'></i> Set Primary
                                                            </Dropdown.Item>
                                                        }
                                                        {props.canDeleteAccountUsers &&
                                                            <Dropdown.Item
                                                                disabled={accountUsers.length <= 1 || ! props.canDeleteAccountUsers || user.contact_id === props.authenticatedUserContact.contact_id}
                                                                onClick={() => deleteAccountUser(user.contact_id)}
                                                                variant='danger'
                                                                title={user.belongs_to_count == 1 ? 'Delete' : 'Unlink'}
                                                        >
                                                                <i className='fas fa-trash'></i> Delete
                                                            </Dropdown.Item>
                                                        }
                                                        {props.canImpersonateAccountUsers &&
                                                            <Dropdown.Item
                                                                title='Impersonate'
                                                                variant='info'
                                                                onClick={() => impersonate(user.contact_id)}
                                                            >
                                                                <i className='fas fa-people-arrows'></i> Impersonate
                                                            </Dropdown.Item>
                                                        }
                                                    </Dropdown.Menu>
                                                </Dropdown>
                                            }
                                        </td>
                                        <td>
                                            {<Badge bg={user.enabled ? 'success' : 'secondary'} style={{marginRight: '7px'}}>{user.enabled ? 'Enabled' : 'Disabled'}</Badge>}
                                            {user.name}
                                            {user.is_primary ? <Badge bg='warning' text='dark' style={{float: 'right'}}><i className='fas fa-star'></i> Primary</Badge> : null}
                                        </td>
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
            {showAccountUserModal &&
                <EditAccountUser
                    accountId={props.accountId}
                    canBeParent={props.canBeParent}
                    canCreateAccountUsers={props.canCreateAccountUsers}
                    contactId={contactId}
                    hide={hideAccountUserModal}
                    refreshAccountUsers={refreshAccountUsers}
                    show={showAccountUserModal}
                />
            }
            <ChangePasswordModal
                show={showChangePasswordModal}
                userId={userId}
                toggleModal={() => setShowChangePasswordModal(false)}
            />
        </Row>
    )
}