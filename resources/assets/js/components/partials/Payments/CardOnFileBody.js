import React, {Fragment, seState} from 'react'
import { Modal } from 'react-bootstrap'

export default function CardOnFileBody(props) {
    const [comment, setComment] = useState('')

    const storePayment = () => {
        const data = {
            amount: props.paymentAmount,
            comment: comment ?? null,
            invoice_id: props.invoiceId,
            payment_method: props.paymentMethod,
            reference_value: props.paymentMethod.name
        }

        // const data = {
        //     amount: props.paymentAmount,
        //     comment: comment ?? null,
        //     invoice_id: props.invoiceId,
        //     payment_method: props.paymentMethod,
        //     reference_value: props.paymentMethod.required_field ? paymentReferenceValue : null
        // }

        // const data = {
        //     outstanding_invoices: outstandingInvoices,
        //     payment_amount: paymentAmount,
        //     payment_type_id: selectedPaymentMethod.payment_type_id,
        //     payment_method_id: selectedPaymentMethod.payment_method_id,
        //     payment_method_on_file: selectedPaymentMethod.payment_method_on_file ? true : false,
        //     reference_value: selectedPaymentMethod.payment_method_on_file ? selectedPaymentMethod.name : paymentReferenceValue,
        //     comment: comment ?? null
        // }

        makeAjaxRequest('/payments/accountPayment', 'POST', data, response => {
            props.refreshPaymentsTab()
            props.setAccountBalance(response.account_balance)
            props.setBalanceOwing(response.balance_owing)
            setIsLoading(false)
            hideModal()
        }, () => hideModal())

        makeAjaxRequest('/payments', 'POST', data, response => {
            props.hideModal()
        })
    }

    return (
        <Fragment>
            <Modal.Body>
                <Row>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Card on File:</InputGroup.Text>
                            <FormControl
                                value={props.paymentMethod.name}
                                disabled
                            />
                        </InputGroup>
                    </Col>
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


