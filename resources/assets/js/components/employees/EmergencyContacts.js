import React, {Fragment, useEffect, useState} from 'react'
import { Modal, Row, Col, Button, ButtonGroup, Table } from "react-bootstrap"
import {toast} from 'react-toastify'

import Contact from '../partials/Contact'
import LoadingSpinner from '../partials/LoadingSpinner'
import useAddress from '../partials/Hooks/useAddress'
import useContact from '../partials/Hooks/useContact'
import {useAPI} from '../../contexts/APIContext'

export default function EmergencyContacts (props) {
    const [emergencyContacts, setEmergencyContacts] = useState([])
    const [isLoading, setIsLoading] = useState(true)
    const [showEmergencyContactModal, setShowEmergencyContactModal] = useState(false)

    const address = useAddress()
    const api = useAPI()
    const contact = useContact()

    const {
        employeeId,
    } = props

    useEffect(() => {
        api.get(`/employees/${employeeId}/emergencyContacts`).then(response => {
            setEmergencyContacts(response.emergency_contacts)
            setIsLoading(false)
        }, () => setIsLoading(false))
    }, [])

    useEffect(() => {
        if(emergencyContacts.length < 2 && !isLoading)
            toast.error('Please provide a minimum of 2 emergency contacts', {autoClose: false})
    }, [emergencyContacts, isLoading])

    const addEmergencyContact = () => {
        fetch('/employees/emergencyContacts')
        .then(response => {return response.json()})
        .then(data => {
            contact.setup(data)
            setShowEmergencyContactModal(true)
        })
    }

    const deleteEmergencyContact = contactId => {
        const emergencyContact = emergencyContacts.filter(contact => contact.contact_id === contactId)[0]
        if(emergencyContacts.length <= 1)
            return
        if(confirm(`Are you sure you wish to delete contact ${emergencyContact.name}?\nThis action can not be undone`)) {
            setIsLoading(true)
            api.delete(`/employees/${employeeId}/emergencyContacts/${emergencyContact.contact_id}`).then(response => {
                setEmergencyContacts(response.emergencyContacts)
                setIsLoading(false)
            }, () => setIsLoading(false))
        }
    }

    const editEmergencyContact = emergencyContactId => {
        api.get(`/employees/emergencyContacts/${emergencyContactId}`).then(response => {
            contact.setup(response)
            address.setup(response.address)
            setShowEmergencyContactModal(true)
        });
    }

    const hideModal = () => {
        address.reset()
        contact.reset()
        setShowEmergencyContactModal(false)
    }

    const storeEmergencyContact = () => {
        const data = {
            ...contact.collect(),
            address_formatted: address.formatted,
            address_lat: address.lat,
            address_lng: address.lng,
            address_name: address.name,
            address_place_id: address.placeId,
        }

        api.post(`/employees/${employeeId}/emergencyContacts`).then(response => {
            toast.success(`Contact "${contact.firstName} ${contact.lastName}" successfully ${contact.contactId ? 'updated' : 'created'}`)

            setShowEmergencyContactModal(false)
            setEmergencyContacts(response.emergency_contacts)
        })
    }

    if(isLoading)
        return <LoadingSpinner />

    return (
        <Fragment>
            <Row>
                <Col md={2}><h4 className='text-muted'>Emergency Contacts</h4></Col>
                <Col md={10}>
                    <Table striped bordered size='sm'>
                        <thead>
                            <tr>
                                <td><Button size='sm' variant='success' onClick={addEmergencyContact}><i className='fas fa-user-plus'></i></Button></td>
                                <td>Name</td>
                                <td>Primary Email</td>
                                <td>Primary Phone</td>
                                <td>Relationship</td>
                            </tr>
                        </thead>
                        <tbody>
                            {emergencyContacts.map((emergencyContact, index) =>
                                <tr key={index}>
                                    <td align='center' width='5%'>
                                        <ButtonGroup size='sm'>
                                            <Button
                                                title='Delete'
                                                variant='danger'
                                                disabled={emergencyContacts.length <= 1 || props.readOnly}
                                                onClick={() => deleteEmergencyContact(emergencyContact.contact_id)}
                                            ><i className='fas fa-trash'></i></Button>
                                            <Button
                                                title='Edit'
                                                variant='warning'
                                                disabled={props.readOnly}
                                                onClick={() => editEmergencyContact(emergencyContact.contact_id)}
                                            ><i className='fas fa-edit'></i></Button>
                                        </ButtonGroup>
                                    </td>
                                    <td>{emergencyContact.name}</td>
                                    <td>{emergencyContact.primary_email}</td>
                                    <td>{emergencyContact.primary_phone}</td>
                                    <td>{emergencyContact.position}</td>
                                </tr>
                            )}
                        </tbody>
                    </Table>
                </Col>
            </Row>
            <Modal show={showEmergencyContactModal} onHide={hideModal} size='xl'>
                <Modal.Header closeButton>
                    <Modal.Title>{contact.contactId ? 'Edit' : 'Create'} Emergency Contact</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Contact
                        address={address}
                        contact={contact}
                        inModal={true}
                        readOnly={props.readOnly}
                        showAddress={true}
                    />
                </Modal.Body>
                <Modal.Footer className='justify-content-md-right'>
                    <ButtonGroup>
                        <Button variant='light' onClick={hideModal}>Cancel</Button>
                        <Button variant='success' onClick={storeEmergencyContact}>
                            <i className='fas fa-save'></i> Submit
                        </Button>
                    </ButtonGroup>
                </Modal.Footer>
            </Modal>
        </Fragment>
    )
}
