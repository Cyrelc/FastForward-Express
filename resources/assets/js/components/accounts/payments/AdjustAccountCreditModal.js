import React, {useState} from 'react'

import {Button, ButtonGroup, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'
import CurrencyInput from 'react-currency-input-field'

export default function AdjustAccountCreditModal(props) {

    const [creditAmount, setCreditAmount] = useState('')
    const [billId, setBillId] = useState('')
    const [comment, setComment] = useState('')

    const storeAccountCredit = () => {
        if(!props.editPayments)
            return
        const data = {
            account_id: props.accountId,
            bill_id: billId,
            credit_amount: creditAmount,
            description: comment
        }
        makeAjaxRequest('/accounts/adjustCredit', 'POST', data, response => {
            props.setAccountBalance(response.new_account_balance)
            props.refreshPaymentsTab()
            props.hide()
        })
    }

    return (
        <Modal show={props.show} onHide={props.hide} size='lg'>
            <Modal.Header closeButton>
                <Modal.Title>Adjust Account Credit</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Credit Amount </InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0.01}
                                name='paymentAmount'
                                onValueChange={setCreditAmount}
                                prefix='$'
                                step={0.01}
                                value={creditAmount}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Bill ID </InputGroup.Text>
                            <FormControl
                                name='creditAgainstBillId'
                                type='number'
                                step='1'
                                value={billId}
                                onChange={event => setBillId(event.target.value)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={12}>
                        <InputGroup>
                            <InputGroup.Text>Reason </InputGroup.Text>
                            <FormControl
                                name='creditReason'
                                value={comment}
                                onChange={event => setComment(event.target.value)}
                            />
                        </InputGroup>
                    </Col>
                </Row>
            </Modal.Body>
            <Modal.Footer className='justify-content-md-center'>
                <ButtonGroup>
                    <Button variant='light' onClick={props.hide}>Cancel</Button>
                    <Button variant='success' onClick={storeAccountCredit}>Submit</Button>
                </ButtonGroup>
            </Modal.Footer>
        </Modal>
    )
}
