import React, {useEffect, useState} from 'react'
import {Elements, PaymentElement, useElements, useStripe} from '@stripe/react-stripe-js'
import {loadStripe} from '@stripe/stripe-js'
import {toast} from 'react-toastify'

import {Button, Col, Dropdown, Modal, Row, Table} from 'react-bootstrap'

const stripePromise = loadStripe(process.env.MIX_STRIPE_KEY)

export default function ManagePaymentMethodsModal(props) {
    const [clientSecret, setClientSecret] = useState(null)
    const [paymentMethods, setPaymentMethods] = useState([])
    const [showCreatePaymentMethod, setShowCreatePaymentMethod] = useState(false)
    const [tableIsLoading, setTableIsLoading] = useState(true)

    useEffect(() => {
        if(props.show)
            fetchPaymentMethods()
        else {
            setClientSecret(null)
            setPaymentMethods([])
        }
    }, [props.show])

    useEffect(() => {
        if(!clientSecret && showCreatePaymentMethod)
            makeAjaxRequest(`/paymentMethods/${props.accountId}/create`, 'GET', null, response => {
                setClientSecret(response.client_secret)
            })
    }, [showCreatePaymentMethod])

    const deleteCreditCard = card => {
        if(confirm(`Are you sure you would like to delete the credit card ${card.name}?\n\nThis action can not be undone`)) {
            setTableIsLoading(true)
            makeAjaxRequest(`/paymentMethods/${props.accountId}`, 'DELETE', {payment_method_id: card.payment_method_id}, response => {
                fetchPaymentMethods()
                setTableIsLoading(false)
            })
        }
    }

    const fetchPaymentMethods = () => {
        setTableIsLoading(true)
        makeAjaxRequest(`/paymentMethods/${props.accountId}`, 'GET', null, response => {
            if(response.payment_methods?.length)
                setPaymentMethods(response.payment_methods.map(paymentMethod => {
                    return {...paymentMethod, expiry_date: new Date(paymentMethod.expiry_date)}
                }))
            else
                setShowCreatePaymentMethod(true)
            setTableIsLoading(false)
        })
    }

    const getCardIcon = type => {
        if(type === 'visa')
            return <i className='fab fa-cc-visa fa-2x'></i>
        if(type === 'mastercard')
            return <i className='fab fa-cc-mastercard fa-2x'></i>
    }

    const hideCreateBody = () => {
        setShowCreatePaymentMethod(false)
        makeAjaxRequest(`/paymentMethods/${props.accountId}/create`, 'GET', null, response => {
            setClientSecret(response.client_secret)
        })
    }

    const setDefaultCard = card => {
        setTableIsLoading(true)
        makeAjaxRequest(`/paymentMethods/${props.accountId}/setDefault`, 'POST', {payment_method_id: card.payment_method_id}, response => {
            fetchPaymentMethods()
            setTableIsLoading(false)
        })
    }

    return (
        <Modal show={props.show} onHide={props.hide} size='lg'>
            <Modal.Header closeButton>
                <Modal.Title>Manage Payment Methods</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                {tableIsLoading ?
                    <Row className='justify-content-md-center'>
                        <Col md={3}><h4><i className='fas fa-cog fa-spin'></i>  Loading...</h4></Col>
                    </Row> :
                    !!paymentMethods?.length ?
                    <Row>
                        <Col md={12}>
                            <Table striped bordered size='sm'>
                                <thead>
                                    <tr>
                                        <th width={50}></th>
                                        <th width={50}>Type</th>
                                        <th width={70}>Default</th>
                                        <th>Card #</th>
                                        <th>Expiry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {paymentMethods.map(card =>
                                        <tr key={card.name}>
                                            <td>
                                                <Dropdown>
                                                    <Dropdown.Toggle variant='secondary' id='payment-type-menu'>
                                                        <i className='fas fa-bars'></i>
                                                    </Dropdown.Toggle>
                                                    <Dropdown.Menu>
                                                        {!card.is_default &&
                                                            <Dropdown.Item onClick={() => setDefaultCard(card)}>
                                                                <i className='fas fa-star'></i> Set As Default
                                                            </Dropdown.Item>
                                                        }
                                                        <Dropdown.Item onClick={() => deleteCreditCard(card)} disabled={card.is_default}>
                                                            <i className='fas fa-trash'></i> Delete
                                                        </Dropdown.Item>
                                                    </Dropdown.Menu>
                                                </Dropdown>
                                            </td>
                                            <td style={{textAlign: 'center'}}>{getCardIcon(card.brand)}</td>
                                            <td>{card.is_default && <i className='fas fa-star fa-lg'></i>}</td>
                                            <td style={card.expired ? {color: 'red'} : {}}>{card.name}</td>
                                            <td style={card.expired ? {color: 'red'} : {}}>{card.expiry_date.toLocaleDateString('en-US', {month: '2-digit', year: 'numeric'})}</td>
                                        </tr>
                                    )}
                                </tbody>
                            </Table>
                        </Col>
                    </Row> : null
                }
            </Modal.Body>
            <Modal.Footer className='justify-content-md-center'>
                {clientSecret &&
                    showCreatePaymentMethod ?
                        <Elements stripe={stripePromise} options={{clientSecret}}>
                            <AddCreditCard
                                clientSecret={clientSecret}
                                fetchPaymentMethods={fetchPaymentMethods}
                                hideCreateBody={hideCreateBody}
                                setShowCreatePaymentMethod={setShowCreatePaymentMethod}
                            />
                        </Elements> :
                        <Button variant='success' onClick={event => setShowCreatePaymentMethod(true)}>Add new payment method</Button>
                }
            </Modal.Footer>
        </Modal>
    )
}

const AddCreditCard = (props) => {
    const stripe = useStripe()
    const elements = useElements()

    async function storeCreditCard() {
        const result = await stripe.confirmSetup({
            elements: elements,
            redirect: 'if_required',
        })

        if(result.error) {
            toast.error(result.error.message)
        } else {
            props.fetchPaymentMethods()
            props.hideCreateBody()
        }
    }

    return (
        <Row>
            <Col md={3} className='text-muted'>
                <h5>Add Credit Card</h5>
            </Col>
            <Col md={9}>
                <PaymentElement />
            </Col>
            <Col md={12} style={{textAlign: 'center'}}>
                <Button variant='success' onClick={storeCreditCard} style={{marginTop: '10px'}}>Submit</Button>
            </Col>
        </Row>
    )
}
