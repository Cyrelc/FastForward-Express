import React, {useState} from 'react'

import {Button, ButtonGroup, Col, FormControl, InputGroup, Modal, Row} from 'react-bootstrap'
import CurrencyInput from 'react-currency-input-field'
import Select from 'react-select'

const trackAgainstOptions = [
    {label: 'Bill ID', value: 'bill'},
    {label: 'Invoice ID', value: 'invoice'}
]

export default function AdjustAccountCreditModal(props) {
    const [creditAmount, setCreditAmount] = useState('')
    const [trackId, setTrackId] = useState('')
    const [comment, setComment] = useState('')
    const [trackAgainst, setTrackAgainst] = useState(trackAgainstOptions[0])

    const storeAccountCredit = () => {
        if(!props.canEditPayments) {
            console.log("Error - missing permission 'Edit payments'")
            return
        }
        const data = {
            account_id: props.accountId,
            credit_amount: creditAmount,
            description: comment,
            track_against_type: trackAgainst.value,
            track_against_id: trackId,
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
                            <Select
                                options={trackAgainstOptions}
                                value={trackAgainst}
                                onChange={setTrackAgainst}
                            />
                            <FormControl
                                name='creditAgainstBillId'
                                type='number'
                                step='1'
                                value={trackId}
                                onChange={event => setTrackId(event.target.value)}
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
