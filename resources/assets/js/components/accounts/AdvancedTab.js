import React from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import DatePicker from 'react-datepicker'

export default function AdvancedTab(props) {
    const {
        accountNumber,
        canBeParent,
        childAccountList,
        discount,
        invoiceSeparatelyFromParent,
        isGstExempt,
        minInvoiceAmount,
        parentAccount,
        parentAccounts,
        ratesheet,
        ratesheets,
        sendBills,
        startDate,

        setAccountNumber,
        setCanBeParent,
        setDiscount,
        setInvoiceSeparatelyFromParent,
        setIsGstExempt,
        setMinInvoiceAmount,
        setParentAccount,
        setRatesheet,
        setSendBills,
        setStartDate,

        readOnly
    } = props

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
                                value={accountNumber}
                                onChange={event => setAccountNumber(event.target.value)}
                                readOnly={readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Parent Account</InputGroup.Text>
                            <Select
                                isClearable
                                options={parentAccounts}
                                value={parentAccount}
                                onChange={setParentAccount}
                                isSearchable
                                isDisabled={readOnly || canBeParent}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Start Date</InputGroup.Text>
                            <DatePicker
                                className='form-control'
                                dateFormat='MMM d, yyy'
                                onChange={setStartDate}
                                selected={startDate}
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
                                options={ratesheets}
                                value={ratesheet}
                                onChange={setRatesheet}
                                isDisabled={readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Min Invoice Amount</InputGroup.Text>
                            <FormControl
                                name='minInvoiceAmount'
                                value={minInvoiceAmount}
                                onChange={event => setMinInvoiceAmount(event.target.value)}
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
                                value={discount}
                                onChange={event => setDiscount(event.target.value)}
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
                            checked={isGstExempt}
                            onChange={event => setIsGstExempt(event.target.checked)}
                        />
                    </Col>
                    <Col md={2}>
                        <FormCheck
                            name='canBeParent'
                            label='Can be Parent'
                            checked={canBeParent}
                            onChange={event => setCanBeParent(!canBeParent)}
                            disabled={parentAccount || childAccountList?.length > 0}
                        />
                    </Col>
                    <Col md={2}>
                        <FormCheck
                            name='sendBills'
                            label='Send Bills'
                            checked={sendBills}
                            onChange={event => setSendBills(event.target.checked)}
                        />
                    </Col>
                    <Col md={2}>
                        <FormCheck
                            name='invoiceSeparatelyFromParent'
                            label='Invoice Separately From Parent'
                            checked={invoiceSeparatelyFromParent}
                            onChange={event => setInvoiceSeparatelyFromParent(event.target.checked)}
                            disabled={readOnly || !parentAccount}
                        />
                    </Col>
                </Row>
            </Card.Footer>
        </Card>
    )
}
