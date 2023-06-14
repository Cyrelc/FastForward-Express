import React, {Fragment, useEffect, useState} from 'react'
import { Modal, Row, Col, Button, ButtonGroup, Table } from "react-bootstrap"

import Contact from '../partials/Contact'

export default function EmergencyContacts (props) {
    const [showEmergencyContactModal, setShowEmergencyContactModal] = useState(false)
    const [contactId, setContactId] = useState(null)
    const [firstName, setFirstName] = useState('')
    const [lastName, setLastName] = useState('')
    const [position, setPosition] = useState('')
    const [phoneNumbers, setPhoneNumbers] = useState([])
    const [phoneTypes, setPhoneTypes] = useState([])
    const [emailAddresses, setEmailAddresses] = useState([])
    const [addressFormatted, setAddressFormatted] = useState('')
    const [addressLat, setAddressLat] = useState('')
    const [addressLng, setAddressLng] = useState('')
    const [addressName, setAddressName] = useState('')
    const [addressPlaceId, setAddressPlaceId] = useState('')

    const addEmergencyContact = () => {
        fetch('/employees/emergencyContacts')
        .then(response => {return response.json()})
        .then(data => {
            setContactId('')
            setEmailAddresses(data.emails)
            setPhoneTypes(data.phoneTypes)
            setPhoneNumbers(data.phone_numbers)
            setShowEmergencyContactModal(true)
        })
    }

    const deleteEmergencyContact = contactId => {
        const emergencyContact = props.emergencyContacts.filter(contact => contact.contact_id === contactId)[0]
        if(props.emergencyContacts.length <= 1)
            return
        if(confirm(`Are you sure you wish to delete contact ${emergencyContact.name}?\nThis action can not be undone`)) {
            const data = {
                contact_id: contactId,
                employee_id: this.props.employeeId
            }
            makeAjaxRequest('/employees/emergencyContacts', 'DELETE', data, response => {
                props.handleChanges({target: {name:'emergencyContacts', type: 'object', value: response.emergency_contacts}})
            })
        }
    }

    const editEmergencyContact = emergencyContactId => {
        makeAjaxRequest(`/employees/emergencyContacts/${emergencyContactId}`, 'GET', null, response => {
            response = JSON.parse(response)
            console.log(response.phone_numbers)
            setContactId(response.contact_id)
            setPhoneTypes(response.phone_types)
            setPhoneNumbers(response.phone_numbers)
            setEmailAddresses(response.emails ?? [])
            setFirstName(response.first_name)
            setLastName(response.last_name)
            setPosition(response.position)
            setAddressFormatted(response.address.formatted)
            setAddressLat(response.address.lat)
            setAddressLng(response.address.lng)
            setAddressName(response.address.name)
            setAddressPlaceId(response.address.place_id)
            setShowEmergencyContactModal(true)
        });
    }

    const hideModal = () => {
        setShowEmergencyContactModal(false)
        setAddressFormatted('')
        setAddressLat('')
        setAddressLng('')
        setAddressName('')
        setAddressPlaceId('')
        setFirstName('')
        setLastName('')
        setPosition('')
        setContactId(null)
        setPhoneNumbers([])
        setEmailAddresses([])
    }


    const handleChanges = events => {
        console.log(events)
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
            if(name.includes('AddressFormatted'))
                setAddressFormatted(value)
            if(name.includes('AddressLat'))
                setAddressLat(value)
            if(name.includes('AddressLng'))
                setAddressLng(value)
            if(name.includes('AddressName'))
                setAddressName(value)
            if(name.includes('AddressPlaceId'))
                setAddressPlaceId(value)
        });
    }

    const storeEmergencyContact = () => {
        const data = {
            address_formatted: addressFormatted,
            address_lat: addressLat,
            address_lng: addressLng,
            address_name: addressName,
            address_place_id: addressPlaceId,
            contact_id: contactId,
            emails: emailAddresses,
            employee_id: props.employeeId,
            first_name: firstName,
            last_name: lastName,
            phone_numbers: phoneNumbers,
            position: position
        }

        makeAjaxRequest('/employees/emergencyContacts', 'POST', data, response => {
            toastr.clear()
            toastr.success(`Contact "${firstName} ${lastName}" successfully ${contactId ? 'updated' : 'created'}`, 'Success')

            setShowEmergencyContactModal(false)
            props.handleChanges({target: {name:'emergencyContacts', type: 'object', value: response.emergency_contacts}})
        })
    }

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
                            {props.emergencyContacts.map((emergencyContact, index) =>
                                <tr key={index}>
                                    <td align='center' width='5%'>
                                        <ButtonGroup size='sm'>
                                            <Button
                                                title='Delete'
                                                variant='danger'
                                                disabled={props.emergencyContacts.length <= 1 || props.readOnly}
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
                    <Modal.Title>{contactId ? 'Edit' : 'Create'} Emergency Contact</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Contact
                        addressId='emergencyContact'
                        firstName={firstName}
                        lastName={lastName}
                        position={position}
                        address={{
                            type: 'Address',
                            name: addressName,
                            formatted: addressFormatted,
                            lat: addressLat,
                            lng: addressLng,
                            placeId: addressPlaceId
                        }}
                        phoneNumbers={phoneNumbers}
                        phoneTypes={phoneTypes}
                        emailAddresses={emailAddresses}
                        handleChanges={handleChanges}
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
