import React, {Component} from 'react'
import {Button, ButtonGroup, Col, FormControl, FormGroup, InputGroup, Modal, Row} from 'react-bootstrap'

export default class InvoiceAmendmentModal extends Component {
    constructor() {
        super()
        this.state = {
            billId: '',
            description: '',
            amount: '',
            disableSubmitButton: true
        }
        this.handleChange = this.handleChange.bind(this)
        this.storeAmendment = this.storeAmendment.bind(this)
        this.verifyBill = this.verifyBill.bind(this)
    }

    handleChange(event) {
        const {name, value, type} = event.target
        this.setState({[name]: value})
    }

    storeAmendment() {
        const data = {
            invoice_id: this.props.invoice.invoice_id,
            description: this.state.description,
            bill_id: this.state.billId,
            amount: this.state.amount
        }
        console.log(data)
        makeAjaxRequest('/invoices/createAmendment', 'POST', data, response => {
            toastr.clear()
            toastr.success('Amendment created', 'Success')
            this.props.refreshInvoice()
            this.props.toggle()
        })
    }

    verifyBill(event) {
        this.setState({disableSubmitButton: true})
        makeAjaxRequest('/bills/getModel/' + event.target.value, 'GET', null, response => {
            const bill = JSON.parse(response);
            toastr.clear();
            if(!bill) {
                toastr.error('Bill requested does not exist. Please check input for errors', 'Error', {'timeOut' : 0, 'extendedTImeout' : 0})
            } else if(!bill.bill.invoice_id) {
                toastr.error('Requested bill has not been invoiced yet. Unable to enter an amendment against an uninvoiced bill', 'Error', {'timeOut' : 0, 'extendedTImeout' : 0})
            } else if(bill.bill.invoice_id != this.state.invoice_id) {
                this.setState({disableSubmitButton: false})
                toastr.warning('Bill is not part of the current invoice. This should only be performed if the bill was incorrectly assigned to another account and is being reassigned to this one.', 'Warning', {'timeOut' : 0, 'extendedTImeout' : 0})
            }
            else
                this.setState({disableSubmitButton: false})
        })
    }

    render() {
        return (
            <Modal show={this.props.show} onHide={this.props.toggle}>
                <Modal.Header closeButton>
                    <Modal.Title>Create Amendment</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Row className='justify-content-md-center'>
                        <Col md={11}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>Bill ID: </InputGroup.Text></InputGroup.Prepend>
                                <FormControl
                                    name='billId'
                                    onChange={this.handleChange}
                                    value={this.state.billId}
                                    type='number'
                                    min={1}
                                    onBlur={this.verifyBill}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={11}>
                            Description: 
                            <FormGroup>
                                <FormControl
                                    name='description'
                                    as='textarea'
                                    rows={3}
                                    onChange={this.handleChange}
                                    value={this.state.description}
                                />
                            </FormGroup>
                        </Col>
                        <Col md={11}>
                            <InputGroup>
                                <InputGroup.Prepend><InputGroup.Text>Amount: </InputGroup.Text></InputGroup.Prepend>
                                <FormControl
                                    type='number'
                                    name='amount'
                                    value={this.state.amount}
                                    onChange={this.handleChange}
                                    step={0.01}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                </Modal.Body>
                <Modal.Footer className='justify-content-md-center'>
                    <ButtonGroup>
                        <Button variant='light' onClick={this.props.toggle}>Cancel</Button>
                        <Button variant='success' disabled={this.state.disableSubmitButton} onClick={this.storeAmendment}>Submit</Button>
                    </ButtonGroup>
                </Modal.Footer>
            </Modal>
        )
    }
}

