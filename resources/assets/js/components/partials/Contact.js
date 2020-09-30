import React from 'react'
import {Row, Col, InputGroup, FormControl} from 'react-bootstrap'

import Address from './Address'
import Emails from './Emails'
import Phones from './Phones'

export default function Contact(props) {
    return (
        <Row>
            <Col md={6}>
                <Row>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>First Name</InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                name='firstName'
                                placeholder='First Name'
                                value={props.firstName}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Last Name</InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                name='lastName'
                                placeholder='Last Name'
                                value={props.lastName}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={12}>
                        <InputGroup>
                            <InputGroup.Prepend>
                                <InputGroup.Text>Position</InputGroup.Text>
                            </InputGroup.Prepend>
                            <FormControl
                                name='position'
                                placeholder='Position / Title'
                                value={props.position}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={12}>
                        <hr/>
                        <Emails
                            emailAddresses={props.emailAddresses}
                            handleChanges={props.handleChanges}
                            readOnly={props.readOnly}
                        />
                    </Col>
                    <Col md={12}>
                        <Phones
                            handleChanges={props.handleChanges}
                            phoneNumbers={props.phoneNumbers}
                            phoneTypes={props.phoneTypes}
                            readOnly={props.readOnly}
                        />
                    </Col>
                </Row>
            </Col>
            <Col md={6}>
                <Address
                    id={props.addressId}
                    address={props.address}
                    handleChanges={props.handleChanges}
                    showAddressSearch={true}

                    admin={props.admin}
                />
            </Col>
        </Row>
    )
}
