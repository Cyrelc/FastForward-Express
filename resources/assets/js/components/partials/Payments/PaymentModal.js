import React, {Fragment, useEffect, useState} from 'react'
import {Button, ButtonGroup, Container, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'
import CurrencyInput from 'react-currency-input-field'

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
    const [accountPaymentMethod, setAccountPaymentMethod] = useState(0)
    const [cardsOnFile, setCardsOnFile] = useState([])
    const [clientSecret, setClientSecret]  = useState(null)
    const [isLoading, setIsLoading] = useState(true)
    const [paymentAmount, setPaymentAmount] = useState(props.invoiceBalanceOwing)
    const [paymentMethod, setPaymentMethod] = useState('')
    const [prepaidPaymentMethods, setPrepaidPaymentMethods] = useState([])
    const [stripePendingPaymentMethod, setStripePendingPaymentMethod] = useState([])

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

                setCardsOnFile(response.payment_methods.cards_on_file)
                setPrepaidPaymentMethods(response.payment_methods.prepaid)
                setAccountPaymentMethod(response.payment_methods.account)
                setStripePendingPaymentMethod(response.payment_methods.stripe_pending)

                setIsLoading(false)
            })
        } else {
            setCardsOnFile([])
            setPrepaidPaymentMethods([])
            setIsLoading(false)
        }
    }, [props.show])

    useEffect(() => {
        if(paymentAmount < 0 || paymentAmount > props.invoiceBalanceOwing)
            setPaymentAmount(props.invoiceBalanceOwing)
    }, [paymentAmount])
    
    const hideModal = () => {
        setPaymentMethod('')
        setPrepaidPaymentMethods([])
        setCardsOnFile([])
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
                paymentAmount={paymentAmount}
                invoiceId={props.invoiceId}
            />
        return <PaymentMethodSelectBody />
    }

    const payFromAccount = () => {
        const accountBalance = parseFloat(accountPaymentMethod.account_balance)

        const localPaymentAmount = accountBalance > paymentAmount ? paymentAmount : accountBalance

        const data = {
            amount: localPaymentAmount,
            payment_method: accountPaymentMethod,
        }

        if(confirm(`Are you sure you would like to pay ${localPaymentAmount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})} towards invoice ${props.invoiceId}?`)) {
            makeAjaxRequest(`/payments/${props.invoiceId}`, 'POST', data, response => {
                props.refresh()
                hideModal()
            })
        }
    }

    const PaymentMethodSelectBody = () => {
        return (
            <Fragment>
                <Modal.Body>
                    <Container>
                        <Row className='justify-content-md-center'>
                            {(accountPaymentMethod?.account_balance > 0) &&
                                <Fragment>
                                    <Col md={3}>
                                        <h4>Account Credit</h4>
                                    </Col>
                                    <Col>
                                        <Button
                                            variant='primary'
                                            onClick={payFromAccount}
                                        >
                                            Account (${accountPaymentMethod.account_balance.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})})
                                        </Button>
                                    </Col>
                                    <Col md={12}>
                                        <hr/>
                                    </Col>
                                </Fragment>
                            }
                            {cardsOnFile?.length > 0 &&
                                <Fragment>
                                    <Col md={3}><h4>Cards on File</h4></Col>
                                    {cardsOnFile.map(card =>
                                        <Col key={card.name}>
                                            <Button variant='primary' onClick={() => setPaymentMethod(card)}>{card.name}</Button>
                                        </Col>
                                    )}
                                    <Col md={12}>
                                        <hr/>
                                    </Col>
                                </Fragment>
                            }
                            <Col md={3}><h4>One Time Charge</h4></Col>
                            <Col>
                                <Button variant='primary' onClick={() => setPaymentMethod(stripePendingPaymentMethod)}>
                                    <i className='fab fa-stripe fa-lg fa-border'></i> Process one-time Credit Card transaction
                                </Button>
                            </Col>
                            {prepaidPaymentMethods.length > 0 &&
                                <Fragment>
                                    <Col md={12}>
                                        <hr/>
                                    </Col>
                                    <Col md={3}><h4>Prepaid Methods</h4></Col>
                                    {prepaidPaymentMethods.map(paymentMethod =>
                                        <Col key={paymentMethod.name}>
                                            <Button variant='primary' onClick={() => setPaymentMethod(paymentMethod)}>
                                                {paymentMethod.name}
                                            </Button>
                                        </Col>
                                    )}
                                </Fragment>
                            }
                        </Row>
                    </Container>
                </Modal.Body>
                <Modal.Footer className='justify-content-md-center'>
                    <ButtonGroup>
                        <Button variant='light' onClick={hideModal}>Cancel</Button>
                    </ButtonGroup>
                </Modal.Footer>
            </Fragment>
        )
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
                <Container>
                    <Row>
                        <Col>
                            <Modal.Title>Payment On Invoice #{props.invoiceId}</Modal.Title>
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
