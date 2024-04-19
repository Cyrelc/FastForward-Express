import React, {Fragment, useEffect, useState} from 'react'
import {Button, Modal} from 'react-bootstrap'
import {Elements, PaymentElement, useElements, useStripe} from '@stripe/react-stripe-js'
import {loadStripe} from '@stripe/stripe-js'

import {useAPI} from '../../../contexts/APIContext'

const stripePromise = loadStripe(process.env.MIX_STRIPE_KEY)

const StripeForm = (props) => {
    const api = useAPI()
    const elements = useElements()
    const stripe = useStripe()

    const submitPayment = async (event) => {
        if(!stripe || !elements) {
            console.error('Stripe or elements failed to load', stripe, elements)
            return
        }

        try {
            const result = await stripe.confirmPayment({
                elements, redirect: 'if_required',
            })

            toast.success('Payment successful!')
            props.hideModal()
        } catch (error) {
            console.error('Failed to submit stripe payment', error)
            toast.error(error.message, {
                autoClose: false
            })
        }
    }

    return (
        <Fragment>
            {stripe ?
                <div style={{textAlign: 'center'}}>
                    <PaymentElement />
                    <Button style={{marginTop: '15px'}} onClick={submitPayment}>Process Payment for {props.paymentAmount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</Button>
                </div>
                : <h4>
                    Requesting data, please wait... <i className='fas fa-spinner fa-spin'></i>
                </h4>
            }
        </Fragment>
    )
}

export default function StripePaymentBody(props) {
    const [clientSecret, setClientSecret]  = useState(null)

    useEffect(() => {
        console.log('called StripePaymentBody')
        const data = {
            amount: props.paymentAmount,
            invoice_id: props.invoiceId,
        }

        api.post('/paymentMethods/getPaymentIntent', data)
            .then(response => {
                setClientSecret(response.client_secret)
            })
    }, [])

    const options = {
        appearance: {
            theme: 'flat'
        },
        clientSecret: clientSecret,
    }

    return (
        <Modal.Body>
            {clientSecret ?
                <Elements stripe={stripePromise} options={options}>
                    <StripeForm
                        clientSecret={clientSecret}
                        hideModal={props.hideModal}
                        paymentAmount={props.paymentAmount}
                    />
                </Elements>
                : <h4>
                    Requesting data, please wait... <i className='fas fa-spinner fa-spin'></i>
                </h4>
            }
        </Modal.Body>
    )
}
