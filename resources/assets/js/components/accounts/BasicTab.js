import React from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row} from 'react-bootstrap'

import Address from '../partials/Address'

export default function BasicTab(props) {
    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Basic Info</h4>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Prepend><InputGroup.Text>Name</InputGroup.Text></InputGroup.Prepend>
                            <FormControl
                                name='accountName'
                                value={props.accountName}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        Account ID: {props.accountId}
                    </Col>
                    <Col md={3}>
                        Account Number: {props.accountNumber}
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row className='justify-content-md-center'>
                    <Col md={6}>
                        <Card>
                            <Card.Header style={{textAlign: 'center'}}>
                                <Card.Title>Shipping Address</Card.Title>
                                <hr/>
                            </Card.Header>
                            <Card.Body>
                                <Address
                                    id='shipping'
                                    address={props.shippingAddress}
                                    handleChanges={props.handleChanges}
                                    showAddressSearch={true}
                                    readOnly={props.readOnly}
                                />
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={6}>
                        <Card>
                            <Card.Header style={{textAlign: 'center'}}>
                                <Card.Title>Billing Address</Card.Title>
                                <FormCheck
                                    name='useShippingForBillingAddress'
                                    label='Same as shipping address'
                                    checked={props.useShippingForBillingAddress}
                                    onChange={props.handleChanges}
                                    disabled={props.readOnly}
                                />
                            </Card.Header>
                            <Card.Body>
                                <Address
                                    id='billing'
                                    address={props.billingAddress}
                                    handleChanges={props.handleChanges}
                                    showAddressSearch={true}
                                    readOnly={ props.readOnly || props.useShippingForBillingAddress }
                                />
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
