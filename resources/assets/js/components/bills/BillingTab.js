import React from 'react'
import {Card, Col, Row, FormControl, InputGroup} from 'react-bootstrap'
import Select from 'react-select'

const repeatingBillsTitleText = 'Daily bills will be generated on and assigned to every weekday until disabled\n' +
'Weekly bills will be generated Sundays, and will be assigned for pickup and delivery on the same day of the week as the original\n' +
'Monthly bills will be generated on and assigned to the first business day of each month\n\n' +
'All repeating bills will have all filled fields copied with the notable exceptions of Waybill Number and Interliner Tracking Number'

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
                                        readOnly={props.readOnly || props.invoiceId || props.paymentTypes.length === 1}
                                    />
                                </InputGroup>
                            </Col>
                        }
                        {props.paymentType.name === 'Employee' &&
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend>
                                        <InputGroup.Text>Employee: </InputGroup.Text>
                                    </InputGroup.Prepend>
                                    <Select
                                        options={props.employees}
                                        isSearchable
                                        value={props.chargeEmployee}
                                        onChange={employee => props.handleChanges({target: {name: 'chargeEmployee', type: 'object', value: employee}})}
                                        isDisabled={props.readOnly || props.pickupManifestId || props.deliveryManifestId}
                                        />
                                </InputGroup>
                            </Col>
                        }
                    </Row>
                    <Row>
                        <Col md={6}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>Repeat Interval: </InputGroup.Text></InputGroup.Prepend>
                                <Select
                                    options={props.repeatIntervals}
                                    isClearable
                                    isSearchable
                                    getOptionLabel={interval => interval.name}
                                    getOptionValue={interval => interval.selection_id}
                                    onChange={interval => props.handleChanges({target: {name: 'repeatInterval', type: 'object', value: interval}})}
                                    isDisabled={props.readOnly}
                                    value={props.repeatInterval}
                                />
                                <InputGroup.Append><InputGroup.Text><i className='fas fa-question' title={repeatingBillsTitleText}></i></InputGroup.Text></InputGroup.Append>
                            </InputGroup>
                        </Col>
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
                </Col>
            </Row>
            <hr/>
            <Row> {/* Interliner */}
                <Col md={2}>
                    <h4 className='text-muted'>Interliner</h4>
                </Col>
                <Col md={9}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Interliner: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <Select
                            options={props.interliners}
                            isSearchable
                            value={props.interliner}
                            onChange={interliner => props.handleChanges({target: {name: 'interliner', type: 'object', value: interliner}})}
                            isDisabled={props.readOnly || props.invoiceId}
                        />
                        <InputGroup.Prepend>
                            <InputGroup.Text>Tracking #</InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='text'
                            placeholder='Tracking Number'
                            name='interlinerTrackingId'
                            value={props.interlinerTrackingId}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.invoiceId}
                        />
                        <InputGroup.Prepend>
                            <InputGroup.Text>Cost To Customer: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            step={0.01}
                            min={0}
                            name='interlinerCostToCustomer'
                            value={props.interlinerCostToCustomer}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.invoiceId}
                        />
                        <InputGroup.Prepend>
                            <InputGroup.Text>Actual Cost: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='number'
                            step={0.01}
                            min={0}
                            name='interlinerActualCost'
                            value={props.interlinerActualCost}
                            onChange={props.handleChanges}
                            readOnly={props.readOnly || props.invoiceId}
                        />
                    </InputGroup>
                </Col>
            </Row>
        </Card>
    )
}
