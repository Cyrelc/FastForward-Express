import React from 'react'
import {Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import CreatableSelect from 'react-select/creatable'

import Address from './AddressFunctional'
import Emails from './Emails'
import Phones from './Phones'

const pronounSeed = [
    {label: 'He/Him/His', value: 'He/Him/His'},
    {label: 'She/Her/Hers', value: 'She/Her/Hers'},
    {label: 'They/Them/Theirs', value: 'They/Them/Theirs'},
    {label: 'Xe/Xer/Xis', value: 'Xe/Xer/Xis'},
    {label: 'He', value: 'He'},
    {label: 'Her', value: 'Her'},
    {label: 'Him', value: 'Him'},
    {label: 'His', value: 'His'},
    {label: 'She', value: 'She'},
    {label: 'Their', value: 'Their'},
    {label: 'Them', value: 'Them'},
    {label: 'They', value: 'They'},
    {label: 'Xie', value: 'Xie'},
    {label: 'Xer', value: 'Xer'},
    {label: 'Xis', value: 'Xis'}
]

export default function Contact(props) {
    const {inModal = false} = props

    const {
        emailAddresses,
        emailTypes,
        firstName,
        lastName,
        phoneNumbers,
        phoneTypes,
        position,
        preferredName,
        pronouns,
        setEmailAddresses,
        setFirstName,
        setLastName,
        setPhoneNumbers,
        setPosition,
        setPreferredName,
        setPronouns
    } = props.contact

    return (
        <Row>
            <Col md={props.showAddress ? 7 : 12}>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Contact Info</h4>
                    </Col>
                    <Col md={10}>
                        <Row>
                            <Col md={inModal ? 12 : 6}>
                                <InputGroup>
                                    <InputGroup.Text>First Name</InputGroup.Text>
                                    <FormControl
                                        name='firstName'
                                        placeholder='First Name'
                                        value={firstName}
                                        onChange={event => setFirstName(event.target.value)}
                                        readOnly={props.readOnly}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={inModal ? 12 : 6}>
                                <InputGroup>
                                    <InputGroup.Text>Last Name</InputGroup.Text>
                                    <FormControl
                                        name='lastName'
                                        placeholder='Last Name'
                                        value={lastName}
                                        onChange={event => setLastName(event.target.value)}
                                        readOnly={props.readOnly}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={inModal ? 12 : 6}>
                                <InputGroup>
                                    <InputGroup.Text>Preferred Name (opt)</InputGroup.Text>
                                    <FormControl
                                        name='preferredName'
                                        placeholder='If you prefer to be called something other than your legal first name'
                                        value={preferredName}
                                        onChange={event => setPreferredName(event.target.value)}
                                        readOnly={props.readOnly}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={inModal ? 12 : 6}>
                                <InputGroup>
                                    <InputGroup.Text>Pronouns (opt)</InputGroup.Text>
                                    <CreatableSelect
                                        options={pronounSeed}
                                        value={pronouns}
                                        onChange={setPronouns}
                                        isMulti={true}
                                        readOnly={props.readOnly}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={inModal ? 12 : 6}>
                                <InputGroup>
                                    <InputGroup.Text>Position (opt)</InputGroup.Text>
                                    <FormControl
                                        name='position'
                                        placeholder='Position / Title'
                                        value={position}
                                        onChange={event => setPosition(event.target.value)}
                                        readOnly={props.readOnly}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                    </Col>
                    <Col md={12}>
                        <hr/>
                    </Col>
                    <Col md={2}>
                        <h4 className='text-muted'>Emails</h4>
                    </Col>
                    <Col md={10}>
                        <Emails
                            emailAddresses={emailAddresses}
                            emailTypes={emailTypes}
                            setEmailAddresses={setEmailAddresses}
                            handleExistingEmailAddress={props.handleExistingEmailAddress ? props.handleExistingEmailAddress : false}
                            readOnly={props.readOnly}
                        />
                    </Col>
                    <Col md={12}>
                        <hr/>
                    </Col>
                    <Col md={2}>
                        <h4 className='text-muted'>Phone Numbers</h4>
                    </Col>
                    <Col md={10}>
                        <Phones
                            phoneNumbers={phoneNumbers}
                            phoneTypes={phoneTypes}
                            readOnly={props.readOnly}
                            setPhoneNumbers={setPhoneNumbers}
                        />
                    </Col>
                </Row>
            </Col>
            {props.showAddress &&
                <Col md={5}>
                    <Address
                        id={props.addressId}
                        address={props.address}
                        showAddressSearch={true}

                        admin={props.admin}
                    />
                </Col>
            }
        </Row>
    )
}
