import React from 'react'
import {Card, Col, Row, FormControl, InputGroup} from 'react-bootstrap'
import Select from 'react-select'

export default function BillingTab(props) {
    return(
        <Card border='dark'>
            <Row>
                <Col md={9}>
                    <Row>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Prepend>
                                    <InputGroup.Text>Payment Type: </InputGroup.Text>
                                </InputGroup.Prepend>
                                <Select
                                    options={props.paymentTypes}
                                    getOptionLabel={type => type.name}
                                    value={props.paymentType}
                                    onChange={paymentType => props.handleChanges({target: {name: 'paymentType', type: 'text', value: paymentType}})}
                                    isDisabled={props.readOnly || props.invoiceId}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Prepend>
                                    <InputGroup.Text>Waybill Number: </InputGroup.Text>
                                </InputGroup.Prepend>
                                <FormControl
                                    name="billNumber"
                                    value={props.billNumber}
                                    onChange={props.handleChanges}
                                    readOnly={props.readOnly}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Prepend>
                                    <InputGroup.Text>Skip Invoicing</InputGroup.Text>
                                </InputGroup.Prepend>
                                <InputGroup.Checkbox
                                    type='checkbox'
                                    checked={props.skipInvoicing}
                                    onChange={props.handleChanges}
                                    value={props.skipInvoicing}
                                    name='skipInvoicing'
                                    disabled={props.readOnly || props.invoiceId}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                    <Row>
                        {props.paymentType.name === 'Account' &&
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend>
                                        <InputGroup.Text>Account: </InputGroup.Text>
                                    </InputGroup.Prepend>
                                    <Select
                                        options={props.accounts}
                                        getOptionLabel={account => account.account_number + ' - ' + account.name}
                                        getOptionValue={account => account.account_id}
                                        isSearchable
                                        onChange={account => props.handleChanges({target: {name: 'chargeAccount', type: 'object', value: account}})}
                                        value={props.chargeAccount}
                                        isDisabled={props.readOnly || props.invoiceId}
                                    />
                                </InputGroup>
                            </Col>
                        }
                        {((props.chargeAccount && props.chargeAccount.custom_field !== null)
                            || (props.paymentType !== '' && props.paymentType.required_field !== null)) && 
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend>
                                        <InputGroup.Text>{props.paymentType.name === 'Account' ? props.chargeAccount.custom_field : props.paymentType.required_field}: </InputGroup.Text>
                                    </InputGroup.Prepend>
                                    <FormControl
                                        name='chargeReferenceValue'
                                        value={props.chargeReferenceValue}
                                        onChange={props.handleChanges}
                                        readOnly={props.readOnly || props.invoiceId}
                                    />
                                </InputGroup> 
                            </Col>
                        }
                        {props.paymentType.name === 'Driver' &&
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend>
                                        <InputGroup.Text>Driver: </InputGroup.Text>
                                    </InputGroup.Prepend>
                                    <Select
                                        options={props.drivers}
                                        isSearchable
                                        getOptionLabel={driver => driver.employee_number + ' - ' + driver.contact.first_name + ' ' + driver.contact.last_name}
                                        value={props.chargeEmployee}
                                        onChange={driver => props.handleChanges({target: {name: 'chargeEmployee', type: 'object', value: driver}})}
                                        isDisabled={props.readOnly || props.pickupManifestId || props.deliveryManifestId}
                                        />
                                </InputGroup>
                            </Col>
                        }
                    </Row>
                </Col>
                <Col md={3}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Total Cost To Customer: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            min={0}
                            name='total'
                            value={(parseFloat(props.amount ? props.amount : 0) + parseFloat(props.interlinerCostToCustomer ? props.interlinerCostToCustomer : 0)).toFixed(2)}
                            readOnly={true}
                            className='form-control-plaintext'
                        />
                    </InputGroup>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Driver Charge: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            min={0}
                            name='amount'
                            value={props.amount}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.invoiceId}
                        />
                    </InputGroup>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Interliner Cost to Customer: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            min={0}
                            name='amount'
                            value={props.interlinerCostToCustomer}
                            readOnly={true}
                            className='form-control-plaintext'
                        />
                    </InputGroup>
                </Col>
            </Row>
        </Card>
    )
}
