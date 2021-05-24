import React, {Component} from 'react'
import {Button, ButtonGroup, Card, Col, FormCheck, FormControl, InputGroup, Modal, Row, Table, ToastHeader} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'
import Select from 'react-select'

const initialState = {
    autoCalc: true,
    creditAgainstBillId: '',
    creditAmount: '',
    creditReason: '',
    paymentAmount: '',
    paymentComment: '',
    invoicePayments: [],
    outstandingInvoices: [],
    payments: [],
    selectedPaymentType: {},
    showAdjustAccountCreditModal: false,
    showPaymentModal: false
}

export default class PaymentsTab extends Component {
    constructor() {
        super()
        this.state = {
            ...initialState,
            paymentTypes: [],
        }
        this.handleAutoCalcChange = this.handleAutoCalcChange.bind(this)
        this.handleChange = this.handleChange.bind(this)
        this.handleInvoicePaymentAmountChange = this.handleInvoicePaymentAmountChange.bind(this)
        this.payOffInvoice = this.payOffInvoice.bind(this)
        this.refreshPaymentsTab = this.refreshPaymentsTab.bind(this)
        this.storeAccountCredit = this.storeAccountCredit.bind(this)
        this.storePayment = this.storePayment.bind(this)
    }

    componentDidMount() {
        this.refreshPaymentsTab()
    }

    componentDidUpdate(prevProps) {
        if(prevProps.accountId != this.props.accountId) {
            this.setState({payments: [], outstandingInvoices: []})
            this.refreshPaymentsTab()
        }
    }

    handleChange(event) {
        const {name, value, type, checked} = event.target
        console.log(name, value, type, checked)
        var temp = {[name] : type === 'checkbox' ? checked : value}
        if(name === 'paymentAmount' && this.state.autoCalc) {
            temp = this.handleAutoCalcChange(event, temp)
        } else if (name === 'invoicePaymentAmount') {
            temp = this.handleInvoicePaymentAmountChange(event, temp)
        } else if(name === 'showPaymentModal' && value === true) {
            temp = {
                outstandingInvoices: this.state.outstandingInvoices.map(invoice => {return {...invoice, payment_amount: ''}}),
                paymentAmount: '',
                selectedPaymentType: '',
                paymentReferenceValue: '',
                showPaymentModal: value
            }
        }
        this.setState(temp)
    }

    handleAutoCalcChange(event, temp) {
        const {name, value, type, checked} = event.target
        const perfectPayments = this.state.outstandingInvoices.filter(invoice => invoice.balance_owing == value)
        if(perfectPayments.length)
            temp = {
                ...temp,
                outstandingInvoices: this.state.outstandingInvoices.map(invoice => invoice.invoice_id === perfectPayments[0].invoice_id ? {...invoice, payment_amount: value} : {...invoice, payment_amount: ''})
            }
        else {
            var balance = value === '' ? 0 : value
            temp = {
                ...temp,
                outstandingInvoices: this.state.outstandingInvoices.map(invoice => {
                    if(balance <= 0)
                        return {...invoice, payment_amount: ''}
                    else {
                        if(invoice.balance_owing > balance) {
                            const paymentAmount = balance
                            balance = 0
                            return {...invoice, payment_amount: paymentAmount}
                        } else {
                            balance -= invoice.balance_owing
                            return {...invoice, payment_amount: invoice.balance_owing}
                        }
                    }
                })
            }
        }
        return temp
    }

    handleInvoicePaymentAmountChange(event, temp) {
        const value = event.target.value.replace(',|$', '')
        const invoiceId = event.target.dataset.invoiceId
        const outstandingInvoices = this.state.outstandingInvoices.map(invoice => {
            if(invoice.invoice_id == invoiceId) {
                if(value > invoice.balance_owing)
                    return {...invoice, payment_amount: invoice.balance_owing}
                else
                    return {...invoice, payment_amount: value}
            }
            else
                return invoice
        })
        temp = {outstandingInvoices: outstandingInvoices}
        return temp
    }

    payOffInvoice(event) {
        if(!this.props.editPayments)
            return
        const {name, type, checked} = event.target
        var total = parseFloat(this.state.paymentAmount === '' ? 0 : this.state.paymentAmount)
        const outstandingInvoices = this.state.outstandingInvoices.map(invoice => {
            if(invoice.invoice_id == name && checked) {
                total += parseFloat(invoice.balance_owing)
                return {...invoice, payment_amount: invoice.balance_owing}
            } else if(invoice.invoice_id == name) {
                total -= parseFloat(invoice.balance_owing)
                return {...invoice, payment_amount: ''}
            } else {
                return invoice
            }
        })
        this.setState({paymentAmount: total.toFixed(2), outstandingInvoices: outstandingInvoices})
    }

    refreshPaymentsTab() {
        makeAjaxRequest('/payments/getModelByAccountId/' + this.props.accountId, 'GET', null, response => {
            response = JSON.parse(response)
            this.setState({
                ...initialState,
                outstandingInvoices: response.outstanding_invoices ? response.outstanding_invoices.map(invoice => {return {...invoice, payment_amount: ''}}) : [],
                paymentTypes: response.payment_types,
                payments: response.payments,
                selectedPaymentType: '',
                showAdjustAccountCreditModal: false,
                showPaymentModal: false
            })
        })
    }

    storeAccountCredit() {
        if(!this.props.editPayments)
            return
        const data = {
            account_id: this.props.accountId,
            bill_id: this.state.creditAgainstBillId,
            credit_amount: this.state.creditAmount,
            description: this.state.creditReason
        }
        makeAjaxRequest('/accounts/adjustCredit', 'POST', data, response => {
            this.props.handleChanges({target: {name: 'accountBalance', type: 'number', value: response.new_account_balance}})
            this.refreshPaymentsTab()
        })
    }

    storePayment() {
        if(!this.props.editPayments)
            return
        if(this.state.selectedPaymentType == '') {
            toastr.error('Please select a payment method')
            return
        }
        const data = {
            account_id: this.props.accountId,
            outstanding_invoices: this.state.outstandingInvoices,
            payment_amount: this.state.paymentAmount,
            payment_type_id: this.state.selectedPaymentType.payment_type_id,
            account_credit: this.state.account_credit,
            reference_value: this.state.paymentReferenceValue,
            comment: this.state.paymentComment
        }
        makeAjaxRequest('/payments/accountPayment', 'POST', data, response => {
            this.refreshPaymentsTab()
        })
    }

    render() {
        const columns = [
            {title: 'Invoice ID', field: 'invoice_id', formatter: this.props.viewInvoices ? 'link' : 'none', formatterParams:{urlPrefix: '/app/invoices/'}, sorter: 'number', headerFilter: true},
            {title: 'Payment Received On', field: 'date'},
            {title: 'Payment Method', field: 'payment_type', headerFilter: true},
            {title: 'Reference Number', field: 'reference_value', headerFilter: true},
            {title: 'Comment', field: 'comment'},
            {title: 'Amount', field: 'amount', formatter: 'money', formatterParams: {thousand: ',', symbol: '$'}, sorter: 'number'}
        ]

        return (
            <div>
                <Card>
                    {this.props.editPayments &&
                        <Card.Header>
                            <Row>
                                {this.props.editPayments &&
                                    <Col>
                                        <Button variant='primary' onClick={() => this.handleChange({target: {name: 'showPaymentModal', type: 'boolean', value: true}})} disabled={this.state.outstandingInvoices.length == 0}><i className='fas fa-money-check-alt'></i> New Payment</Button>
                                    </Col>
                                }
                                <Col style={{textAlign: 'right'}}>
                                    {this.props.editPayments &&
                                        <Button variant='dark' onClick={() => this.handleChange({target: {name: 'showAdjustAccountCreditModal', type: 'boolean', value: true}})}><i className='fas fa-money-bill-wave'></i> Adjust Account Credit</Button>
                                    }
                                </Col>
                            </Row>
                        </Card.Header>
                    }
                    <Card.Body>
                        <ReactTabulator
                            data={this.state.payments}
                            columns={columns}
                            initialSort={[{column:'date', dir:'desc'}]}
                            maxHeight='80vh'
                            options={{
                                layout: 'fitColumns',
                                pagination: 'local',
                                paginationSize: 50
                            }}
                            printAsHtml={true}
                            printStyled={true}
                        />
                    </Card.Body>
                </Card>
                <Modal show={this.state.showAdjustAccountCreditModal} onHide={() => this.handleChange({target: {name: 'showAdjustAccountCreditModal', type: 'boolean', value: false}})} size='lg'>
                    <Modal.Header closeButton>
                        <Modal.Title>Adjust Account Credit</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <Row>
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend><InputGroup.Text>Credit Amount </InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name='creditAmount'
                                        type='number'
                                        step='0.01'
                                        value={this.state.creditAmount}
                                        onChange={this.handleChange}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend><InputGroup.Text>Bill ID </InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name='creditAgainstBillId'
                                        type='number'
                                        step='1'
                                        value={this.state.creditAgainstBillId}
                                        onChange={this.handleChange}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={12}>
                                <InputGroup>
                                    <InputGroup.Prepend><InputGroup.Text>Reason </InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name='creditReason'
                                        value={this.state.creditReason}
                                        onChange={this.handleChange}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer className='justify-content-md-center'>
                        <ButtonGroup>
                            <Button variant='light' onClick={() => this.handleChange({target: {name: 'showAdjustAccountCreditModal', type: 'boolean', value: false}})}>Cancel</Button>
                            <Button variant='success' onClick={this.storeAccountCredit}>Submit</Button>
                        </ButtonGroup>
                    </Modal.Footer>
                </Modal>
                <Modal show={this.state.showPaymentModal} onHide={() => this.handleChange({target: {name: 'showPaymentModal', type: 'boolean', value: false}})} size='lg'>
                    <Modal.Header closeButton>
                        <Modal.Title>New Payment</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <Row>
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend>
                                        <InputGroup.Text>Payment Type</InputGroup.Text>
                                    </InputGroup.Prepend>
                                    <Select
                                        getOptionLabel={option => option.name}
                                        getOptionValue={option => option.payment_type_id}
                                        options={this.props.accountBalance > 0 ? this.state.paymentTypes : this.state.paymentTypes.filter(paymentType => paymentType.name != 'Account')}
                                        onChange={value => this.handleChange({target: {name: 'selectedPaymentType', type: 'object', value: value}})}
                                    />
                                </InputGroup>
                            </Col>
                            {(this.state.selectedPaymentType && this.state.selectedPaymentType.required_field) &&
                                <Col md={6}>
                                    <InputGroup>
                                        <InputGroup.Prepend><InputGroup.Text>{this.state.selectedPaymentType.required_field}</InputGroup.Text></InputGroup.Prepend>
                                        <FormControl
                                            name='paymentReferenceValue'
                                            onChange={this.handleChange}
                                            value={this.state.paymentReferenceValue}
                                        />
                                    </InputGroup>
                                </Col>
                            }
                        </Row>
                        <Row>
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend><InputGroup.Text>Payment Amount</InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name='paymentAmount'
                                        type='number'
                                        min={0.01}
                                        step={0.01}
                                        value={this.state.paymentAmount}
                                        onChange={this.handleChange}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={6}>
                                <FormCheck
                                    name='autoCalc'
                                    label='Match exact payment amount; otherwise automatically pay oldest invoices first'
                                    checked={this.state.autoCalc}
                                    onChange={this.handleChange}
                                />
                            </Col>
                        </Row>
                        <Row>
                            <Col md={12}>
                                <h4 className='text-muted'>Outstanding Invoices</h4>
                            </Col>
                            <Col md={12}>
                                <Table striped bordered size='sm'>
                                    <thead>
                                        <tr>
                                            <th>Pay Off</th>
                                            <th>Invoice ID</th>
                                            <th>Invoice Date</th>
                                            <th>Balance Owing</th>
                                            <th>Payment Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {this.state.outstandingInvoices.map(invoice =>
                                            <tr key={invoice.invoice_id}>
                                                <td style={{textAlign: 'center'}}>
                                                    <FormCheck
                                                        name={invoice.invoice_id}
                                                        onClick={this.payOffInvoice}
                                                    />
                                                </td>
                                                <td>{invoice.invoice_id}</td>
                                                <td>{invoice.bill_end_date}</td>
                                                <td style={{textAlign: 'right'}}>{invoice.balance_owing.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}</td>
                                                <td>
                                                    <FormControl
                                                        name='invoicePaymentAmount'
                                                        data-invoice-id={invoice.invoice_id}
                                                        value={invoice.payment_amount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}
                                                        disabled={this.state.autoCalc}
                                                        onChange={this.handleChange}
                                                        size='sm'
                                                    />
                                                </td>
                                            </tr>
                                        )}
                                        <tr>
                                            <td colSpan={4}>Adjustment to account balance</td>
                                            <td>
                                                <FormControl
                                                    name='paymentRemainder'
                                                    type='number'
                                                    step={0.01}
                                                    disabled={true}
                                                    value={(this.state.paymentAmount - this.state.outstandingInvoices.reduce((accumulator, currentValue) => accumulator + currentValue.payment_amount, 0)).toFixed(2)}
                                                    isInvalid={(this.state.paymentAmount - this.state.outstandingInvoices.reduce((accumulator, currentValue) => accumulator + currentValue.payment_amount, 0)).toFixed(2) < 0}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </Table>
                            </Col>
                            <Col md={12}>
                                Comments
                                <FormControl
                                    as='textarea'
                                    rows={3}
                                    name='paymentComment'
                                    value={this.state.paymentComment}
                                    onChange={this.handleChange}
                                    label='Comment / Notes'
                                />
                            </Col>
                        </Row>
                    </Modal.Body>
                    <Modal.Footer className='justify-content-md-center'>
                        <ButtonGroup>
                            <Button variant='light' onClick={() => this.handleChange({target: {name: 'showPaymentModal', type: 'boolean', value: false}})}>Cancel</Button>
                            <Button variant='success' onClick={this.storePayment}>Submit</Button>
                        </ButtonGroup>
                    </Modal.Footer>
                </Modal>
            </div>
        )
    }
}
