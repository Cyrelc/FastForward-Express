import React, {Fragment, useEffect, useState} from 'react'
import {Button, ButtonGroup, Col, Modal, Tab, Tabs, Row} from 'react-bootstrap'

import ActivityLogTab from '../../partials/ActivityLogTab'
import Contact from '../../partials/Contact'
import LoadingSpinner from '../../partials/LoadingSpinner'
import UserPermissionTab from './UserPermissionTab'

import useContact from '../../partials/Hooks/useContact'
import {useAPI} from '../../../contexts/APIContext'

export default function EditAccountUser(props) {
    const [accountUserPermissions, setAccountUserPermissions] = useState([])
    const [activityLog, setActivityLog] = useState('')
    const [belongsTo, setBelongsTo] = useState([])
    const [isLoading, setIsLoading] = useState(true)
    const [key, setKey] = useState('contact')
    const [permissions, setPermissions] = useState([])

    const api = useAPI()
    const contact = useContact()

    useEffect(() => {
        setIsLoading(true)
        if(props.contactId) {
            api.get(`/accountUsers/${props.accountId}/${props.contactId}`)
                .then(response => {
                    configureModal(response)
                    setIsLoading(false)
                })
        } else {
            if(!props.canCreateAccountUsers)
                return
            api.get(`/accountUsers/${props.accountId}`)
                .then(response => {
                    configureModal(response)
                    setIsLoading(false)
                }, () => {setIsLoading(false)})
        }
    }, [props.show])

    const configureModal = response => {
        setAccountUserPermissions(response.account_user_model_permissions)
        setPermissions(response.permissions)

        contact.setup(response.contact)

        if(props.contactId) {
            setActivityLog(response.activity_log)
            setBelongsTo(response.belongs_to)
        }
    }

    const handlePermissionChange = event => {
        const {name, value, checked} = event.target

        setAccountUserPermissions({...accountUserPermissions, [name] : checked})
    }

    const storeAccountUser = () => {
        const data = {
            ...contact.collect(),
            account_id: props.accountId,
            contact_id: props.contactId,
            permissions: accountUserPermissions,
        }

        if(contact.contactId) {
            api.post('/accountUsers', data)
                .then(response => {
                    props.refreshAccountUsers()
                })
        } else
            api.post('/accountUsers/checkIfExists', data)
                .then(response => {
                    if(response.email_in_use) {
                        if(confirm(`This email is already in use by another user: ${response.name} \n\n On accounts:\n ${response.accounts.map(account => `\t${account.account_number} - ${account.label}`)} \n\n Would you instead like to link the existing user to this account?`))
                            api.get(`/accountUsers/link/${response.contact_id}/${props.accountId}`)
                                .then(response => {
                                    props.refreshAccountUsers()
                                })
                    } else
                        api.post('/accountUsers', data)
                            .then(response => {
                                props.refreshAccountUsers()
                            })
                })
    }

    return (
        <Modal show={props.show} onHide={props.hide} size='xl'>
            {isLoading ? <LoadingSpinner /> :
                <Fragment>
                    <Modal.Header closeButton>
                        <Modal.Title>{props.contactId ? `Edit User ${contact.firstName} ${contact.lastName}` : 'Create User'}</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <Row>
                            <Col md={12}>
                                <Tabs id='accountUserTabs' className='nav-justified' activeKey={key} onSelect={setKey}>
                                    <Tab eventKey='contact' title={<h4>Contact Info</h4>}>
                                        <Contact
                                            contact={contact}
                                            inModal={true}
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
                </Fragment>
            }
        </Modal>
    )
}

