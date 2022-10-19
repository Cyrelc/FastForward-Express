import React, {Component} from 'react'
import { Modal, Row, Col, Button, ButtonGroup, Table } from "react-bootstrap"

import Contact from '../partials/Contact'

export default class EmergencyContacts extends Component {
    constructor() {
        super()
        this.state = {
            showEmergencyContactModal: false,
            employeeId: '',
            contactId: null,
            firstName: '',
            lastName: '',
            position: '',
            phoneNumbers: [],
            emailAddresses: [],
            emergencyContactAddressFormatted: '',
            emergencyContactAddressLat: '',
            emergencyContactAddressLng: '',
            emergencyContactAddressName: '',
            emergencyContactAddressPlaceId: '',
            mode: 'create',
            phoneTypes: [],
            emailTypes: []
        }
        this.addEmergencyContact = this.addEmergencyContact.bind(this)
        this.deleteEmergencyContact = this.deleteEmergencyContact.bind(this)
        this.editEmergencyContact = this.editEmergencyContact.bind(this)
        this.handleChanges = this.handleChanges.bind(this)
        this.storeEmergencyContact = this.storeEmergencyContact.bind(this)
    }

    addEmergencyContact() {
        fetch('/employees/emergencyContacts')
        .then(response => {return response.json()})
        .then(data => {
            this.setState({
                contactId: '',
                emailAddresses: data.emails,
                mode: 'create',
                phoneTypes: data.phone_types,
                phoneNumbers: data.phone_numbers,
                showEmergencyContactModal: true,
            })
        })
    }

    deleteEmergencyContact(contactId) {
        const emergencyContact = this.props.emergencyContacts.filter(contact => contact.contact_id === contactId)[0]
        if(this.props.emergencyContacts.length <= 1)
            return
        if(confirm(`Are you sure you wish to delete contact ${emergencyContact.name}?\nThis action can not be undone`)) {
            const data = {
                contact_id: contactId,
                employee_id: this.props.employeeId
            }
            makeAjaxRequest('/employees/emergencyContacts', 'DELETE', data, response => {
                this.props.handleChanges({target: {name:'emergencyContacts', type: 'object', value: response.emergency_contacts}})
            })
        }
    }

    editEmergencyContact(emergencyContactId) {
        makeAjaxRequest(`/employees/emergencyContacts/${emergencyContactId}`, 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({
                contactId: response.contact_id,
                phoneTypes: response.phone_types,
                phoneNumbers: response.phone_numbers,
                emailAddresses: response.emails ? response.emails : [],
                firstName: response.first_name,
                lastName: response.last_name,
                position: response.position,
                emergencyContactAddressFormatted: response.address.formatted,
                emergencyContactAddressLat: response.address.lat,
                emergencyContactAddressLng: response.address.lng,
                emergencyContactAddressName: response.address.name,
                emergencyContactAddressPlaceId: response.address.place_id,
                showEmergencyContactModal: true,
                mode: 'edit'
            })
        });
    }

    handleChanges(events) {
        if(!Array.isArray(events))
            events = [events]
        var temp = {}
        events.forEach(event => {
            const {name, value, type, checked} = event.target
            temp[name] = type === 'checkbox' ? checked : value
        })
        this.setState(temp)
    }

    storeEmergencyContact() {
        const data = {
            address_formatted: this.state.emergencyContactAddressFormatted,
            address_lat: this.state.emergencyContactAddressLat,
            address_lng: this.state.emergencyContactAddressLng,
            address_name: this.state.emergencyContactAddressName,
            address_place_id: this.state.emergencyContactAddressPlaceId,
            contact_id: this.state.contactId,
            emails: this.state.emailAddresses,
            employee_id: this.props.employeeId,
            first_name: this.state.firstName,
            last_name: this.state.lastName,
            phone_numbers: this.state.phoneNumbers,
            position: this.state.position
        }
        $.ajax({
            'url': '/employees/emergencyContacts',
            'type': 'POST',
            'data': data,
            'success': response => {
                toastr.clear()
                if(this.state.contactId)
                    toastr.success('Contact "' + this.state.firstName + ' ' + this.state.lastName + '" successfully updated', 'Success')
                else {
                    toastr.success('Contact "' + this.state.firstName + ' ' + this.state.lastName + '" succesfully created', 'Success', {
                    })
                }
                this.setState({showEmergencyContactModal: false})
                this.props.handleChanges({target: {name:'emergencyContacts', type: 'object', value: response.emergency_contacts}})
            },
            'error': response => handleErrorResponse(response)
        })
    }

    render() {
        return (
            <span>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Emergency Contacts</h4></Col>
                    <Col md={10}>
                        <Table striped bordered size='sm'>
                            <thead>
                                <tr>
                                    <td><Button size='sm' variant='success' onClick={this.addEmergencyContact}><i className='fas fa-user-plus'></i></Button></td>
                                    <td>Name</td>
                                    <td>Primary Email</td>
                                    <td>Primary Phone</td>
                                    <td>Relationship</td>
                                </tr>
                            </thead>
                            <tbody>
                                {this.props.emergencyContacts.map((emergencyContact, index) =>
                                    <tr key={index}>
                                        <td align='center' width='5%'>
                                            <ButtonGroup size='sm'>
                                                <Button title='Delete' variant='danger' disabled={this.props.emergencyContacts.length <= 1 || this.props.readOnly} onClick={() => this.deleteEmergencyContact(emergencyContact.contact_id)}><i className='fas fa-trash'></i></Button>
                                                <Button title='Edit' variant='warning' disabled={this.props.readOnly} onClick={() => this.editEmergencyContact(emergencyContact.contact_id)}><i className='fas fa-edit'></i></Button>
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
                <Modal show={this.state.showEmergencyContactModal} onHide={() => this.handleChanges({target: {name: 'showEmergencyContactModal', type: 'checkbox', value: false}})} size='xl'>
                    <Modal.Header closeButton>
                        <Modal.Title>{this.props.mode === 'edit' ? 'Edit' : 'Create'} Emergency Contact</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <Contact
                            addressId='emergencyContact'
                            firstName={this.state.firstName}
                            lastName={this.state.lastName}
                            position={this.state.position}
                            address={{
                                type: 'Address',
                                name: this.state.emergencyContactAddressName,
                                formatted: this.state.emergencyContactAddressFormatted,
                                lat: this.state.emergencyContactAddressLat,
                                lng: this.state.emergencyContactAddressLng,
                                placeId: this.state.emergencyContactAddressPlaceId
                            }}
                            phoneNumbers={this.state.phoneNumbers}
                            phoneTypes={this.state.phoneTypes}
                            emailAddresses={this.state.emailAddresses}
                            handleChanges={this.handleChanges}
                            readOnly={this.props.readOnly}
                            showAddress={true}
                        />
                    </Modal.Body>
                    <Modal.Footer className='justify-content-md-right'>
                        <ButtonGroup>
                            <Button variant='light' onClick={() => this.handleChanges({target: {name: 'showEmergencyContactModal', type: 'checkbox', checked: false}})}>Cancel</Button>
                            <Button variant='success' onClick={this.storeEmergencyContact}><i className='fas fa-save'></i> Submit</Button>
                        </ButtonGroup>
                    </Modal.Footer>
                </Modal>
            </span>
        )
    }
}
