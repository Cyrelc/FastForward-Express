import React, {Fragment, useEffect, useState} from 'react'
import {Button, ButtonGroup, Container, Col, InputGroup, Modal, Row} from 'react-bootstrap'
import CurrencyInput from 'react-currency-input-field'
import Select from 'react-select'

import AccountCreditBody from './AccountCreditBody'
import CardOnFileBody from './CardOnFileBody'
import PrepaidBody from './PrepaidBody'
import StripePaymentBody from './StripePaymentBody'

/**
 *  Modal types will be limited to
 * 'select' - pick a payment method
 * 'card' - a stored credit card for accounts only
 * 'stripe' - a one-off stripe transaction
 * 'prepaid' - a prepaid or pre-received amount of money, such as a bank transfer
 * 
*/

const PaymentModal = props => {
    const [clientSecret, setClientSecret]  = useState(null)
    const [isLoading, setIsLoading] = useState(true)
    const [paymentAmount, setPaymentAmount] = useState(props.invoiceBalanceOwing)
    const [paymentMethod, setPaymentMethod] = useState('')
    const [paymentMethods, setPaymentMethods] = useState([])

    useEffect(() => {
        setPaymentAmount(props.invoiceBalanceOwing)
    }, [props.invoiceBalanceOwing])

    useEffect(() => {
        if(!paymentMethod.stripe_capture)
            return

        const data = {
            amount: paymentAmount,
            invoice_id: props.invoiceId,
        }

        makeAjaxRequest('/paymentMethods/getPaymentIntent', 'POST', data, response => {
            response = JSON.parse(response)
            setClientSecret(response.client_secret)
        })
    }, [paymentMethod])

    const options = {
        appearance: {
            theme: 'flat'
        },
        clientSecret: clientSecret,
    }

    useEffect(() => {
        if(props.show) {
            setIsLoading(true)
            makeAjaxRequest(`/payments/${props.invoiceId}`, 'GET', null, response => {
                response = JSON.parse(response)

                setPaymentMethods(response.payment_methods)

                setIsLoading(false)
            })
        } else {
            setPaymentMethods([])
            setIsLoading(false)
        }
    }, [props.show])

    useEffect(() => {
        if(paymentAmount < 0 || paymentAmount > props.invoiceBalanceOwing)
            setPaymentAmount(props.invoiceBalanceOwing)
    }, [paymentAmount])
    
    const hideModal = () => {
        setPaymentMethods([])
        props.refresh()
        props.hide()
    }

    const renderPaymentMethodContent = () => {
        if(paymentMethod.type == 'card_on_file')
            return <CardOnFileBody
                hideModal={hideModal}
                invoiceId={props.invoiceId}
                paymentAmount={paymentAmount}
                paymentMethod={paymentMethod}
            />
        else if(paymentMethod.type == 'prepaid')
            return <PrepaidBody
                hideModal={hideModal}
                invoiceId={props.invoiceId}
                paymentAmount={paymentAmount}
                paymentMethod={paymentMethod}
            />
        else if(paymentMethod.type == 'stripe_pending')
            return <StripePaymentBody
                hideModal={hideModal}
                invoiceId={props.invoiceId}
                paymentAmount={paymentAmount}
            />
        else if(paymentMethod.type == 'account')
            return <AccountCreditBody
                hideModal={hideModal}
                invoiceId={props.invoiceId}
                paymentAmount={paymentAmount}
                paymentMethod={paymentMethod}
            />
        return null
    }

    if(isLoading)
        return <Row className='justify-content-md-center' style={{paddingTop: '20px'}}>
            <Col md={11}>
                <h1>Loading, please wait... <i className='fas fa-spinner fa-spin'></i></h1>
            </Col>
        </Row>

    return (
        <Modal show={props.show} onHide={hideModal} size='xl'>
            <Modal.Header closeButton>
                <Modal.Title>Payment On Invoice #{props.invoiceId}</Modal.Title>
            </Modal.Header>
            <Modal.Header>
                <Container>
                    <Row>
                        <Col>
                            <InputGroup>
                                <InputGroup.Text>Payment Method</InputGroup.Text>
                                <Select
                                    className='form-control'
                                    getOptionLabel={paymentMethod => {
                                        if(paymentMethod.name == 'Account')
                                            return `${paymentMethod.name} ($${paymentMethod.account_balance.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})})`
                                        return paymentMethod.name
                                    }}
                                    getOptionValue={paymentMethod => paymentMethod.payment_type_id}
                                    onChange={setPaymentMethod}
                                    options={paymentMethods}
                                    value={paymentMethod}
                                />
                            </InputGroup>
                        </Col>
                        <Col>
                            <Modal.Title>
                                <InputGroup>
                                    <InputGroup.Text>Amount </InputGroup.Text>
                                    <CurrencyInput
                                        decimalsLimit={2}
                                        decimalScale={2}
                                        disabled={paymentMethod.stripe_capture}
                                        min={0.01}
                                        name='paymentAmount'
                                        onValueChange={setPaymentAmount}
                                        prefix='$'
                                        step={0.01}
                                        value={paymentAmount}
                                    />
                                </InputGroup>
                            </Modal.Title>
                        </Col>
                    </Row>
                </Container>
            </Modal.Header>
            {renderPaymentMethodContent()}
        </Modal>
    )
}

export default PaymentModal;
