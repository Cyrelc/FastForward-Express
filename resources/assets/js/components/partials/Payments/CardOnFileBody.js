import React, {Fragment, useState} from 'react'
import {Button, ButtonGroup, Col, FormControl, Modal, Row} from 'react-bootstrap'
import {useAPI} from '../../../contexts/APIContext'

export default function CardOnFileBody(props) {
    const [comment, setComment] = useState('')

    const api = useAPI()

    const storePayment = () => {
        const data = {
            amount: props.paymentAmount,
            comment: comment ?? null,
            payment_method: props.paymentMethod,
            reference_value: props.paymentMethod.name
        }

        api.post(`/payments/${props.invoiceId}`, data).then(response => {
            props.hideModal()
        }, () => props.hideModal())
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


