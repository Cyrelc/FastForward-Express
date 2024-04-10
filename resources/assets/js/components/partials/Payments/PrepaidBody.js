import React, {Fragment, useState} from 'react'
import {Button, ButtonGroup, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'

import {useAPI} from '../../../contexts/APIContext'

export default function PrepaidBody(props) {
    const [comment, setComment] = useState('')
    const [paymentReferenceValue, setPaymentReferenceValue] = useState('')

    const api = useAPI()

    const storePayment = () => {
        // TODO: Change invoice list to individual, remove payment_method_id, and payment_method_on_file, as this can be split to only cards_on_file type transactions
        // remove account_id as a requirement, as it can be inferred from the invoice_id
        const data = {
            amount: props.paymentAmount,
            comment: comment ?? null,
            invoice_id: props.invoiceId,
            payment_method: props.paymentMethod,
            reference_value: props.paymentMethod.required_field ? paymentReferenceValue : null
        }

        api.post(`/payments/${props.invoiceId}`, data)
            .then(response => {
                props.hideModal()
            })
    }

    return (
        <Fragment>
            <Modal.Body>
                <Row>
                    {props.paymentMethod?.required_field &&
                        <Col md={12}>
                            <InputGroup>
                                <InputGroup.Text>{props.paymentMethod.required_field}</InputGroup.Text>
                                <FormControl
                                    name='paymentReferenceValue'
                                    onChange={event => setPaymentReferenceValue(event.target.value)}
                                    value={paymentReferenceValue}
                                />
                            </InputGroup>
                        </Col>
                    }
                </Row>
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

