import React from 'react'
import {Row, Col, InputGroup, FormControl} from 'react-bootstrap'

import Address from './Address'
import Emails from './Emails'
import Phones from './Phones'

export default function Contact(props) {
    return (
        <Row>
            <Col md={props.showAddress ? 7 : 12}>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Contact Info</h4>
                    </Col>
                    <Col md={10}>
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
                            emailAddresses={props.emailAddresses}
                            emailTypes={props.emailTypes}
                            handleChanges={props.handleChanges}
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
                            handleChanges={props.handleChanges}
                            phoneNumbers={props.phoneNumbers}
                            phoneTypes={props.phoneTypes}
                            readOnly={props.readOnly}
                        />
                    </Col>
                </Row>
            </Col>
            {props.showAddress &&
                <Col md={5}>
                    <Address
                        id={props.addressId}
                        address={props.address}
                        handleChanges={props.handleChanges}
                        showAddressSearch={true}

                        admin={props.admin}
                    />
                </Col>
            }
        </Row>
    )
}
