import React from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import DatePicker from 'react-datepicker'

export default function AdvancedTab(props) {
    return(
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Misc</h4>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Account Number</InputGroup.Text>
                            <FormControl
                                name='accountNumber'
                                value={props.accountNumber}
                                onChange={props.handleChanges}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Parent Account</InputGroup.Text>
                            <Select
                                isClearable
                                options={props.parentAccounts}
                                value={props.parentAccount}
                                onChange={value => props.handleChanges({target: {name: 'parentAccount', type: 'object', value: value}})}
                                isSearchable
                                isDisabled={props.readOnly || props.canBeParent}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Start Date</InputGroup.Text>
                            <DatePicker
                                className='form-control'
                                dateFormat='MMM d, yyy'
                                onChange={value => props.handleChanges({target: {name: 'startDate', type: 'date', value: value}})}
                                selected={props.startDate}
                                showMonthDropdown
                                scrollableMonthDropdown
                                showYearDropdown
                                scrollableYearDropdown
                                wrapperClassName='form-control'
                                yearDropdownItemNumber={100}
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Accounting</h4>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Ratesheet</InputGroup.Text>
                            <Select
                                options={props.ratesheets}
                                value={props.ratesheet}
                                onChange={value => props.handleChanges({target: {name: 'ratesheet', type: 'object', value: value}})}
                                isDisabled={props.readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Min Invoice Amount</InputGroup.Text>
                            <FormControl
                                name='minInvoiceAmount'
                                value={props.minInvoiceAmount}
                                onChange={props.handleChanges}
                                type='number'
                                min={0}
                                step={0.01}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={2}>
                        <InputGroup>
                            <InputGroup.Text>Discount</InputGroup.Text>
                            <FormControl
                                name='discount'
                                value={props.discount}
                                onChange={props.handleChanges}
                                type='number'
                                min={0}
                                step={0.01}
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Card.Body>
            <Card.Footer>
                <Row>
                    <Col md={2}>
                        <FormCheck
                            name='isGstExempt'
                            label='Is GST Exempt'
                            checked={props.isGstExempt}
                            onChange={props.handleChanges}
                        />
                    </Col>
                    <Col md={2}>
                        <FormCheck
                            name='canBeParent'
                            label='Can be Parent'
                            checked={props.canBeParent}
                            onChange={props.handleChanges}
                            disabled={props.parentAccount || (props.childAccountList && props.childAccountList.length > 0)}
                        />
                    </Col>
                    <Col md={2}>
                        <FormCheck
                            name='sendBills'
                            label='Send Bills'
                            checked={props.sendBills}
                            onChange={props.handleChanges}
                        />
                    </Col>
                    <Col md={2}>
                        <FormCheck
                            name='invoiceSeparatelyFromParent'
                            label='Invoice Separately From Parent'
                            checked={props.invoiceSeparatelyFromParent}
                            onChange={props.handleChanges}
                            disabled={props.readOnly || props.parentAccount === {}}
                        />
                    </Col>
                </Row>
            </Card.Footer>
        </Card>
    )
}
