import React, {Fragment, useEffect, useState} from 'react'

import {Button, ButtonGroup, Col, FormCheck, FormControl, InputGroup, Modal, Row, Table} from 'react-bootstrap'
import CurrencyInput from 'react-currency-input-field'
import Select from 'react-select'

export default function PaymentModal(props) {
    const [accountAdjustment, setAccountAdjustment] = useState(0);
    const [autoCalc, setAutoCalc] = useState(true);
    const [comment, setComment] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [outstandingInvoices, setOutstandingInvoices] = useState([]);
    const [paymentReferenceValue, setPaymentReferenceValue] = useState('');
    const [paymentAmount, setPaymentAmount] = useState(undefined);
    const [paymentMethods, setPaymentMethods] = useState([])
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(undefined);

    useEffect(() => {
        if(props.show) {
            setIsLoading(true)
            makeAjaxRequest(`/payments/accountPayment/${props.accountId}`, 'GET', null, response => {
                response = JSON.parse(response)
                setOutstandingInvoices(response.outstanding_invoices)
                setPaymentMethods(response.payment_methods)
                const defaultPaymentMethod = response.payment_methods.find(paymentMethod => {
                    return paymentMethod.is_default
                })
                if(defaultPaymentMethod)
                    setSelectedPaymentMethod(defaultPaymentMethod)
                setPaymentReferenceValue('')
                setIsLoading(false)
            })
        } else {
            setPaymentMethods([])
            setOutstandingInvoices([])
            setIsLoading(false)
        }
    }, [props.show])

    useEffect(() => {
        const startingValue = paymentAmount ? toFixedNumber(paymentAmount, 2) : 0
        const adjustment = outstandingInvoices.reduce((remainder, invoice) => remainder -= (invoice.payment_amount ? parseFloat(invoice.payment_amount) : 0), startingValue)
        if(selectedPaymentMethod?.name === 'Account')
            setAccountAdjustment(paymentAmount ? -paymentAmount : 0)
        else
            setAccountAdjustment(adjustment ? adjustment : 0)
    }, [paymentAmount, outstandingInvoices])

    useEffect(() => {
        if(autoCalc) {
            const perfectPayments = outstandingInvoices.filter(invoice => invoice.balance_owing == paymentAmount)
            if(perfectPayments.length)
                setOutstandingInvoices(outstandingInvoices.map(invoice => invoice.invoice_id === perfectPayments[0].invoice_id ? {...invoice, payment_amount: paymentAmount} : {...invoice, payment_amount: ''}))
            else {
                var balance = paymentAmount ? toFixedNumber(paymentAmount, 2) : 0
                setOutstandingInvoices(outstandingInvoices.map(invoice => {
                    if(balance <= 0) {
                        return {...invoice, payment_amount: ''}
                    }
                    else if(invoice.balance_owing > balance) {
                        const temp = balance
                        balance = 0
                        return {...invoice, payment_amount: toFixedNumber(temp, 2)}
                    } else {
                        balance -= toFixedNumber(invoice.balance_owing, 2)
                        return {...invoice, payment_amount: toFixedNumber(invoice.balance_owing, 2)}
                    }
                }))
                setAccountAdjustment(balance)
            }
        }
    }, [paymentAmount, autoCalc])

    const getPaymentTypeOptionLabel = option => {
        if(option.name === 'Account')
            return `Account (${props.accountBalance.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})})`
        if(option.payment_method_on_file) {
            return <Fragment>
                {option.is_default && <i className='fas fa-star'></i>}
                {option.brand == 'visa' && <Fragment><i className='fab fa-cc-visa fa-lg'></i> {option.name}</Fragment>}
                {option.brand == 'mastercard' && <Fragment><i className='fab fa-cc-mastercard fa-lg'></i> {option.name}</Fragment>}
            </Fragment>
        }
        return option.name
    }

    const handleInvoicePaymentAmountChange = (invoiceId, value) => {
        setOutstandingInvoices(outstandingInvoices.map(invoice => {
            if(invoice.invoice_id == invoiceId) {
                if(value > invoice.balance_owing)
                    return {...invoice, payment_amount: invoice.balance_owing}
                else
                    return {...invoice, payment_amount: value}
            }
            else
                return invoice
        }))
    }

    const hideModal = () => {
        setPaymentAmount('')
        setIsLoading(false)
        props.hide()
    }

    const toggleAutoCalc = () => {
        setAccountAdjustment(0)
        setAutoCalc(!autoCalc)
        setPaymentAmount('')
        setOutstandingInvoices(outstandingInvoices.map(invoice => { return {...invoice, payment_amount: ''}}))
    }

    const storePayment = () => {
        if(!props.canEditPayments)
            return
        if(selectedPaymentMethod == '' || selectedPaymentMethod == undefined) {
            toastr.error('Please select a payment method')
            return
        }
        setIsLoading(true)
        const data = {
            account_id: props.accountId,
            outstanding_invoices: outstandingInvoices,
            payment_amount: paymentAmount,
            payment_type_id: selectedPaymentMethod.payment_type_id,
            payment_method_id: selectedPaymentMethod.payment_method_id,
            payment_method_on_file: selectedPaymentMethod.payment_method_on_file ? true : false,
            reference_value: selectedPaymentMethod.payment_method_on_file ? selectedPaymentMethod.name : paymentReferenceValue,
            comment: comment
        }

        makeAjaxRequest('/payments/accountPayment', 'POST', data, response => {
            props.refreshPaymentsTab()
            props.setAccountBalance(response.account_balance)
            props.setBalanceOwing(response.balance_owing)
            setIsLoading(false)
            hideModal()
        })
    }

    const togglePayOffInvoice = invoiceId => {
        const invoices = outstandingInvoices.map(invoice => {
            if(invoice.invoice_id === invoiceId) {
                const isPayOffChecked = !invoice?.isPayOffChecked
                return {
                    ...invoice,
                    isPayOffChecked,
                    payment_amount: isPayOffChecked ? parseFloat(invoice.balance_owing) : 0
                }
            }
            return invoice
        })
        const newPaymentAmount = invoices.reduce((runningTotal, invoice) =>
            runningTotal += invoice?.isPayOffChecked ? parseFloat(invoice.balance_owing) : 0
        , 0.00)
        setPaymentAmount(newPaymentAmount.toFixed(2))
        setOutstandingInvoices(invoices)
    }

    return (
        <Modal show={props.show} onHide={hideModal} size='lg'>
            <Modal.Header closeButton>
                <Modal.Title>Receive Payment</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                {isLoading ? 
                    <Row className='justify-content-md-center'>
                        <Col md={3}>
                            <h4><i className='fas fa-cog fa-spin'></i>  Loading...</h4>
                        </Col>
                    </Row> :
                    <Row>
                        <Col md={6}>
                            <InputGroup>
                                <InputGroup.Text>Payment Type</InputGroup.Text>
                                <Select
                                    getOptionLabel={option => getPaymentTypeOptionLabel(option)}
                                    getOptionValue={option => option.payment_type_id}
                                    options={props.accountBalance > 0 ? paymentMethods : paymentMethods.filter(paymentType => paymentType.name != 'Account')}
                                    onChange={setSelectedPaymentMethod}
                                    value={selectedPaymentMethod}
                                />
                            </InputGroup>
                        </Col>
                        {selectedPaymentMethod?.required_field &&
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Text>{selectedPaymentMethod.required_field}</InputGroup.Text>
                                    <FormControl
                                        name='paymentReferenceValue'
                                        onChange={event => setPaymentReferenceValue(event.target.value)}
                                        value={paymentReferenceValue}
                                    />
                                </InputGroup>
                            </Col>
                        }
                    </Row>
                }
                <Row>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Payment Amount:</InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='paymentAmount'
                                onValueChange={setPaymentAmount}
                                prefix='$'
                                step={0.01}
                                value={paymentAmount}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <FormCheck
                            name='autoCalc'
                            label='Match exact payment amount; otherwise automatically pay oldest invoices first'
                            checked={autoCalc}
                            onChange={toggleAutoCalc}
                        />
                    </Col>
                </Row>
                <Row className='justify-content-md-center'>
                    <Col md={12}>
                        <h4 className='text-muted'>Outstanding Invoices</h4>
                    </Col>
                    {isLoading ? 
                        <Col md={3}>
                            <h4><i className='fas fa-cog fa-spin'></i>  Loading...</h4>
                        </Col> :
                        <Col md={12}>
                            <Table striped bordered size='sm'>
                                <thead>
                                    <tr>
                                        {!autoCalc && <th></th>}
                                        <th>Invoice ID</th>
                                        <th>Invoice Date</th>
                                        <th>Balance Owing</th>
                                        <th>Payment Amount <i className='fas fa-info-circle' title='Tip: When manually entering payment amounts, hit the "space" bar if you wish to autofill with balance owing'></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {outstandingInvoices.map(invoice =>
                                        <tr key={invoice.invoice_id}>
                                            {!autoCalc &&
                                                <td>
                                                    <FormCheck
                                                        checked={invoice?.isPayOffChecked}
                                                        onChange={() => togglePayOffInvoice(invoice.invoice_id)}
                                                    />
                                                </td>
                                            }
                                            <td>{invoice.invoice_id}</td>
                                            <td>{invoice.bill_end_date}</td>
                                            <td style={{textAlign: 'right'}}>{invoice.balance_owing.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                                            <td>
                                                <CurrencyInput
                                                    decimalsLimit={2}
                                                    decimalScale={2}
                                                    disabled={autoCalc}
                                                    name='invoicePaymentAmount'
                                                    prefix='$'
                                                    step={0.01}
                                                    onKeyDown={event => {
                                                        if(event.code === 'Space' && !invoice.payment_amount)
                                                            handleInvoicePaymentAmountChange(invoice.invoice_id, parseFloat(invoice.balance_owing))
                                                    }}
                                                    onValueChange={value => handleInvoicePaymentAmountChange(invoice.invoice_id, value)}
                                                    value={invoice.payment_amount}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </Table>
                            <Table>
                                <thead>
                                    <tr>
                                        <td>Account Info</td>
                                        <td>Current Balance</td>
                                        <td>Estimated End Balance</td>
                                        <td>Adjustment</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td>
                                            <CurrencyInput
                                                decimalsLimit={2}
                                                decimalScale={2}
                                                disabled={true}
                                                isInvalid={props.accountBalance < 0}
                                                prefix='$'
                                                step={0.01}
                                                value={props.accountBalance ? toFixedNumber(props.accountBalance, 2) : 0}
                                            />
                                        </td>
                                        <td>
                                            <CurrencyInput
                                                decimalsLimit={2}
                                                decimalScale={2}
                                                disabled={true}
                                                isInvalid={(props.accountBalance ? toFixedNumber(props.accountBalance, 2) : 0 + accountAdjustment) < 0}
                                                prefix='$'
                                                step={0.01}
                                                value={(props.accountBalance ? toFixedNumber(props.accountBalance, 2) : 0) + accountAdjustment}
                                            />
                                        </td>
                                        <td>
                                            <CurrencyInput
                                                decimalsLimit={2}
                                                decimalScale={2}
                                                disabled={true}
                                                isInvalid={accountAdjustment < 0}
                                                name='paymentRemainder'
                                                prefix='$'
                                                step={0.01}
                                                value={accountAdjustment}
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </Table>
                        </Col>
                    }
                    <Col md={12}>
                        Comments
                        <FormControl
                            as='textarea'
                            label='Comment / Notes'
                            name='paymentComment'
                            onChange={event => setComment(event.target.value)}
                            placeholder={'(Optional)'}
                            rows={3}
                            value={comment}
                        />
                    </Col>
                </Row>
            </Modal.Body>
            <Modal.Footer className='justify-content-md-center'>
                <ButtonGroup>
                    <Button variant='light' onClick={hideModal}>Cancel</Button>
                    <Button variant='success' onClick={storePayment}>Submit</Button>
                </ButtonGroup>
            </Modal.Footer>
        </Modal>
    )
}
