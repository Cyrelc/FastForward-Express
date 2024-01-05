import React from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row} from 'react-bootstrap'

import Address from '../partials/AddressFunctional'

export default function BasicTab(props) {
    const {
        accountId,
        accountName,
        accountNumber,
        shippingAddress,
        billingAddress,
        useShippingForBillingAddress,

        setAccountName,
        setUseShippingForBillingAddress,

        readOnly
    } = props

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Basic Info</h4>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Name</InputGroup.Text>
                            <FormControl
                                name='accountName'
                                value={accountName}
                                onChange={event => setAccountName(event.target.value)}
                                readOnly={readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <h5>Account ID: {accountId}</h5>
                    </Col>
                    <Col md={3}>
                        <h5>Account Number: {accountNumber}</h5>
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
                                    address={shippingAddress}
                                    id='shipping'
                                    readOnly={readOnly}
                                    showAddressSearch={true}
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
                                    checked={useShippingForBillingAddress}
                                    onChange={() => setUseShippingForBillingAddress(!useShippingForBillingAddress)}
                                    disabled={readOnly}
                                />
                            </Card.Header>
                            <Card.Body>
                                <Address
                                    address={billingAddress}
                                    id='billing'
                                    readOnly={ readOnly || useShippingForBillingAddress }
                                    showAddressSearch={true}
                                />
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
