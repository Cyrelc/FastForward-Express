import React, {useEffect, useState} from 'react'
import {Button, ButtonGroup, Col, Modal, Tab, Tabs, Row} from 'react-bootstrap'


import ActivityLogTab from '../../partials/ActivityLogTab'
import Contact from '../../partials/Contact'
import UserPermissionTab from './UserPermissionTab'

export default function EditAccountUser(props) {
    const [accountUserPermissions, setAccountUserPermissions] = useState([])
    const [activityLog, setActivityLog] = useState('')
    const [belongsTo, setBelongsTo] = useState([])
    const [emailAddresses, setEmailAddresses] = useState([])
    const [emailTypes, setEmailTypes] = useState([])
    const [firstName, setFirstName] = useState([])
    const [key, setKey] = useState('contact')
    const [lastName, setLastName] = useState('')
    const [permissions, setPermissions] = useState([])
    const [phoneNumbers, setPhoneNumbers] = useState([])
    const [phoneTypes, setPhoneTypes] = useState([])
    const [position, setPosition] = useState([])

    useEffect(() => {
        if(props.contactId) {
            makeAjaxRequest(`/users/getAccountUserModel/${props.accountId}/${props.contactId}`, 'GET', null, response => {
                response = JSON.parse(response)
                configureModal(response)
            })
        } else {
            if(!props.canCreateAccountUsers)
                return
            makeAjaxRequest(`/users/getAccountUserModel/${props.accountId}`, 'GET', null, response => {
                response = JSON.parse(response)
                configureModal(response)
            })
        }
    }, [props.show])

    const configureModal = response => {
        setAccountUserPermissions(response.account_user_model_permissions)
        setEmailAddresses(response.contact.emails)
        setEmailTypes(response.contact.email_types)
        setPermissions(response.permissions)
        setPhoneNumbers(response.contact.phone_numbers)
        setPhoneTypes(response.contact.phone_types)
        
        if(props.contactId) {
            setActivityLog(response.activity_log)
            setBelongsTo(response.belongs_to)
            setFirstName(response.contact.first_name)
            setLastName(response.contact.last_name)
            setPosition(response.contact.position)
        }
    }

    const handleChanges = events => {
        if(!Array.isArray(events))
            events = [events]
        events.forEach(event => {
            const {name, type, value, checked} = event.target
            if(name == 'firstName')
                setFirstName(value)
            if(name == 'lastName')
                setLastName(value)
            if(name == 'position')
                setPosition(value)
            if(name == 'emailAddresses')
                setEmailAddresses(value)
            if(name == 'phoneNumbers')
                setPhoneNumbers(value)
        });
    }

    const handlePermissionChange = event => {
        const {name, value, checked} = event.target

        setAccountUserPermissions({...accountUserPermissions, [name] : checked})
    }

    const storeAccountUser = () => {
        toastr.clear()
        const data = {
            account_id: props.accountId,
            contact_id: props.contactId,
            emails: emailAddresses,
            first_name: firstName,
            last_name: lastName,
            permissions: accountUserPermissions,
            phone_numbers: phoneNumbers,
            position: position,
        }

        if(props.contactId) {
            makeAjaxRequest('/users/storeAccountUser', 'POST', data, response => {
                props.refreshAccountUsers()
            })
        } else
            makeAjaxRequest('/users/checkIfAccountUserExists', 'POST', data, response => {
                if(response.email_in_use) {
                    if(confirm(`This email is already in use by another user: ${response.name} \n\n On accounts:\n ${response.accounts.map(account => `\t${account.account_number} - ${account.label}`)} \n\n Would you instead like to link the existing user to this account?`))
                        makeAjaxRequest(`/users/linkAccountUser/${response.contact_id}/${props.accountId}`, 'GET', null, response => {
                            props.refreshAccountUsers()
                        })
                } else
                    makeAjaxRequest('/users/storeAccountUser', 'POST', data, response => {
                        props.refreshAccountUsers()
                    })
            })
    }


    return (
        <Modal show={props.show} onHide={props.hide} size='xl'>
            <Modal.Header closeButton>
                <Modal.Title>{props.contactId ? `Edit User ${firstName} ${lastName}` : 'Create User'}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={12}>
                        <Tabs id='accountUserTabs' className='nav-justified' activeKey={key} onSelect={setKey}>
                            <Tab eventKey='contact' title={<h4>Contact Info</h4>}>
                                <Contact
                                    firstName={firstName}
                                    lastName={lastName}
                                    position={position}
                                    phoneNumbers={phoneNumbers}
                                    emailAddresses={emailAddresses}
                                    phoneTypes={phoneTypes}
                                    emailTypes={emailTypes}

                                    handleChanges={handleChanges}
                                    readOnly={!permissions.editBasic && (!props.contactId && !props.canCreateAccountUsers)}
                                />
                            </Tab>
                            {(props.contactId && permissions.viewPermissions || permissions.editPermissions) &&
                                <Tab eventKey='permissions' title={<h4>Permissions</h4>}>
                                    <UserPermissionTab
                                        belongsTo={belongsTo}
                                        canBeParent={props.canBeParent}
                                        accountUserPermissions={accountUserPermissions}

                                        handlePermissionChange={handlePermissionChange}
                                        readOnly={!permissions.editPermissions}
                                    />
                                </Tab>
                            }
                            {activityLog && permissions.viewActivityLog &&
                                <Tab eventKey='activityLog' title={<h4>Activity Log</h4>}>
                                    <ActivityLogTab
                                        activityLog={activityLog}
                                    />
                                </Tab>
                            }
                        </Tabs>
                    </Col>
                </Row>
            </Modal.Body>
            <Modal.Footer className='justify-content-md-center'>
                <ButtonGroup>
                    <Button variant='light' onClick={props.hide}>Cancel</Button>
                    <Button variant='success' onClick={storeAccountUser}>Submit</Button>
                </ButtonGroup>
            </Modal.Footer>
        </Modal>
    )
}

