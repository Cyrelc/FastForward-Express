import React, {Fragment, useState} from 'react'
import {Button, ButtonGroup, Col, FormControl, Modal, Row} from 'react-bootstrap'

export default function AccountCreditBody(props) {
    const [comment, setComment] = useState('')

    const storePayment = () => {
        const accountBalance = parseFloat(props.paymentMethod.account_balance)

        const localPaymentAmount = accountBalance > props.paymentAmount ? props.paymentAmount : accountBalance

        const data = {
            amount: localPaymentAmount,
            payment_method: props.paymentMethod,
        }

        if(confirm(`Are you sure you would like to pay ${localPaymentAmount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})} towards invoice ${props.invoiceId}?`)) {
            makeAjaxRequest(`/payments/${props.invoiceId}`, 'POST', data, response => {
                props.hideModal()
            })
        }
    }

    return (
        <Fragment>
            <Modal.Body>
                <Row className='justify-content-md-center'>
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
                    <Button variant='light' onClick={props.hideModal}>Cancel</Button>
                    <Button variant='success' onClick={storePayment}>Submit</Button>
                </ButtonGroup>
            </Modal.Footer>
        </Fragment>
    )
}

