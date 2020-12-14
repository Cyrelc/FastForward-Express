import React, {Component} from 'react'
import {Badge, Button, ButtonGroup, Col, Row, Table} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'
import { connect } from 'react-redux'

import InvoiceAmendmentModal from './InvoiceAmendmentModal'

const headerTDStyle = {width: '20%', textAlign: 'center', border: 'grey solid', whiteSpace: 'pre', paddingTop: '10px', paddingBottom: '10px'}
const invoiceTotalsStyle = {backgroundColor: 'orange', border: 'orange solid'}

function getCorrectAddress(bill) {
    if(bill.charge_account_id != bill.pickup_account_id)
        if(bill.pickup_address_name)
            return bill.pickup_address_name
        else
            return bill.pickup_address_formatted
    else if(bill.delivery_address_name)
        return bill.delivery_address_name
    return bill.delivery_address_formatted
}

class Invoice extends Component {
    constructor() {
        super()
        this.state = {
            showAmendmentModal: false
        }
        this.deleteAmendment = this.deleteAmendment.bind(this)
        this.getInvoice = this.getInvoice.bind(this)
        this.toggleAmendmentModal = this.toggleAmendmentModal.bind(this)
        this.toggleFinalized = this.toggleFinalized.bind(this)
    }

    componentDidMount() {
        this.getInvoice()
    }

    componentDidUpdate(prevProps) {
        if(prevProps.location.pathname != this.props.location.pathname)
            this.getInvoice()
    }

    deleteAmendment(amendmentId) {
        if(confirm('Are you sure you wish to delete Amendment ' + amendmentId + '?\nThis action can not be undone'))
            makeAjaxRequest('/invoices/deleteAmendment/' + amendmentId, 'GET', null, response =>
                this.getInvoice()
            )
    }

    getInvoice() {
        makeAjaxRequest('/invoices/getModel/' + this.props.match.params.invoiceId, 'GET', null, response => {
            response = JSON.parse(response)
            document.title = 'View Invoice ' + response.invoice.invoice_id
            const thisInvoiceIndex = this.props.sortedInvoices.findIndex(invoice_id => invoice_id === response.invoice.invoice_id)
            const prevInvoiceId = thisInvoiceIndex <= 0 ? null : this.props.sortedInvoices[thisInvoiceIndex - 1]
            const nextInvoiceId = (thisInvoiceIndex < 0 || thisInvoiceIndex === this.props.sortedInvoices.length - 1) ? null : this.props.sortedInvoices[thisInvoiceIndex + 1]
            this.setState({
                accountId: response.invoice.account_id,
                accountOwing: response.account_owing,
                amendments: response.amendments ? response.amendments : null,
                amount: response.amount,
                bill_count: response.bill_count,
                invoice: response.invoice,
                nextInvoiceId: nextInvoiceId,
                parent: response.parent,
                prevInvoiceId: prevInvoiceId,
                tables: response.tables,
                unpaidInvoices: response.unpaid_invoices
            })
        })
    }

    toggleAmendmentModal() {
        this.setState({showAmendmentModal: !this.state.showAmendmentModal})
    }

    toggleFinalized() {
        makeAjaxRequest('/invoices/finalize/' + [this.state.invoice.invoice_id], 'GET', null, response =>
            this.setState({invoice: {...this.state.invoice, finalized: !this.state.invoice.finalized}})
        )
    }

    render() {
        return (
            <Row className='justify-content-md-center' style={{paddingTop: '20px'}}>
                <Col md={2}>
                    <h3>Invoice {this.state.invoice && this.state.invoice.invoice_id}</h3>
                </Col>
                <Col md={2}>
                    <h4>
                        <Badge variant={(this.state.invoice && this.state.invoice.finalized) ? 'success' : 'danger'}>{(this.state.invoice && this.state.invoice.finalized) ? 'Finalized' : 'Not Finalized'}</Badge>
                        {this.state.amendments && <Badge variant='warning'>Amended</Badge>}
                    </h4>
                </Col>
                <Col md={2}>
                    <ButtonGroup>
                        <LinkContainer to={'/app/invoices/view/' + this.state.prevInvoiceId}><Button variant='info' disabled={!this.state.prevInvoiceId}><i className='fas fa-arrow-circle-left'></i> Back - {this.state.prevInvoiceId}</Button></LinkContainer>
                        <LinkContainer to={'/app/invoices/view/' + this.state.nextInvoiceId}><Button variant='info' disabled={!this.state.nextInvoiceId}>Next - {this.state.nextInvoiceId} <i className='fas fa-arrow-circle-right'></i></Button></LinkContainer>
                    </ButtonGroup>
                </Col>
                <Col md={5} style={{textAlign: 'right'}}>
                    <ButtonGroup>
                        <Button href={this.state.invoice ? '/invoices/print/' + this.state.invoice.invoice_id : null} target='_blank' variant='success'><i className='fas fa-print'> Generate PDF</i></Button>
                        {this.state.amendments ? <Button href={this.state.invoice ? '/invoices/print/' + this.state.invoice.invoice_id + '?amendments_only': null} variant='success' target='_blank'><i className='fas fa-print'> Generate PDF - Amendments Only</i></Button> : null}
                        {(this.state.invoice && this.state.invoice.finalized) ? <Button variant='warning' onClick={this.toggleAmendmentModal}><i className='fas fa-eraser'></i> Create Amendment</Button> : null}
                        {this.state.invoice ?
                            this.state.invoice.finalized ? <Button variant='danger' onClick={this.toggleFinalized}><i className='fas fa-unlock'></i> Remove Finalize</Button> : <Button variant='success' onClick={this.toggleFinalized}><i className='fas fa-lock'></i> Finalize</Button> : null
                        }
                    </ButtonGroup>
                </Col>
                <Col md={11}>
                    <hr/>
                </Col>
                <Col md={11}>
                    <table style={{width: '100%'}}>
                        <tr>
                            <td style={{width: '40%'}}><h3><LinkContainer to={'/app/accounts/edit/' + this.state.accountId}><a>{this.state.parent && (this.state.parent.account_number + ' - ' + this.state.parent.name)}</a></LinkContainer></h3></td>
                            <td style={{...headerTDStyle, backgroundColor: '#ADD8E6'}}>{'Bill Count\n' + (this.state.invoice && this.state.invoice.bill_count)}</td>
                            <td style={{...headerTDStyle, backgroundColor: '#ADD8E6'}}>{'Invoice Total\n' + (this.state.invoice && this.state.invoice.total_cost)}</td>
                            <td style={{...headerTDStyle, backgroundColor: 'orange'}}>{'Account Balance\n' + this.state.accountOwing}</td>
                        </tr>
                    </table>
                </Col>
                <Col md={11}>
                    <hr/>
                </Col>
                {(this.state.parent && this.state.parent.invoice_comment) &&
                    <Col md={11}>
                        {this.state.parent.invoice_comment}
                    </Col>
                }
                {(this.state.parent && this.state.parent.invoice_comment) &&
                    <Col md={11}>
                        <hr/>
                    </Col>
                }
                {this.state.tables && 
                    Object.keys(this.state.tables).map(key =>
                        <Col md={11}>
                            <Table striped bordered size='sm'>
                                <thead>
                                    <tr>
                                        {Object.keys(this.state.tables[key].headers).map(headerKey => {
                                            if(headerKey === 'Amount')
                                                return <td style={{textAlign: 'right'}}>{headerKey}</td>
                                            return <td>{headerKey}</td>
                                        })}
                                    </tr>
                                </thead>
                                <tbody>
                                    {this.state.tables[key].bills.map(bill =>
                                        <tr key={bill.bill_id}>
                                            {Object.values(this.state.tables[key].headers).map(headerValue => {
                                                switch(headerValue) {
                                                    case 'address':
                                                        return <td>{getCorrectAddress(bill)}</td>
                                                    case 'amount':
                                                        return <td width='10%' style={{textAlign: 'right'}}>{bill.amount}</td>
                                                    case 'bill_id':
                                                        return <td width='8%'><LinkContainer to={'/app/bills/edit/' + bill.bill_id}><a>{bill.bill_id}</a></LinkContainer></td>
                                                    case 'time_pickup_scheduled':
                                                        return <td width='9%'>{bill.time_pickup_scheduled.substring(0, 16)}</td>
                                                    default:
                                                        return <td width='10%'>{bill[headerValue]}</td>
                                                }
                                            })}
                                        </tr>
                                    )}
                                    {Object.keys(this.state.tables).length > 1 &&
                                        <tr>
                                            <td colSpan={Object.keys(this.state.tables[key].headers).length - 2} rowSpan={3} style={{textAlign: 'center', verticalAlign: 'middle'}}><b>Subtotal for {key}</b></td>
                                            <td><b>Bill Subtotal: </b></td>
                                            <td style={{textAlign: 'right'}}><b>{this.state.tables[key].subtotal}</b></td>
                                        </tr>
                                    }
                                    {Object.keys(this.state.tables).length > 1 &&
                                        <tr>
                                            <td><b>Tax: </b></td>
                                            <td style={{textAlign: 'right'}}><b>{this.state.tables[key].tax}</b></td>
                                        </tr>
                                    }
                                    {Object.keys(this.state.tables).length > 1 &&
                                        <tr>
                                            <td><b>Subtotal: </b></td>
                                            <td style={{textAlign: 'right'}}><b>{this.state.tables[key].total}</b></td>
                                        </tr>
                                    }
                                </tbody>
                            </Table>
                        </Col>
                    )
                }
                {this.state.amendments &&
                    <Col md={11}>
                        <b>Amendments</b>
                        <Table width='100%'>
                            <thead>
                                <tr>
                                    {this.state.invoice && <td></td>}
                                    <td>Bill ID</td>
                                    <td>Description</td>
                                    <td style={{textAlign: 'right'}}>Amount</td>
                                </tr>
                            </thead>
                            <tbody>
                                {
                                    this.state.amendments.map(amendment =>
                                        <tr>
                                            {this.state.invoice &&  <td width='10%'><Button onClick={() => this.deleteAmendment(amendment.amendment_id)} variant='danger'><i className='fas fa-trash'></i></Button></td>}
                                            <td width='10%'>{amendment.bill_id}</td>
                                            <td>{amendment.description}</td>
                                            <td width='10%' style={{textAlign: 'right'}}>{amendment.amount}</td>
                                        </tr>
                                    )
                                }
                            </tbody>
                        </Table>
                    </Col>
                }
                {this.state.unpaidInvoices &&
                    <Col md={6}>
                        <b>All Invoices with Balance Owing for Account {this.state.parent && this.state.parent.name}</b>
                        <Table striped bordered size='sm'>
                            <thead>
                                <tr>
                                    <td>Invoice ID</td>
                                    <td>Date</td>
                                    <td>Invoice Total</td>
                                    <td>Balance Owing</td>
                                </tr>
                            </thead>
                            <tbody>
                                {this.state.unpaidInvoices.map(unpaidInvoice =>
                                    <tr>
                                        <td>{unpaidInvoice.invoice_id}</td>
                                        <td>{unpaidInvoice.bill_end_date}</td>
                                        <td>{unpaidInvoice.total_cost}</td>
                                        <td>{unpaidInvoice.balance_owing}</td>
                                    </tr>
                                )}
                            </tbody>
                        </Table>
                    </Col>
                }
                <Col md={5}>
                    <div style={{float: 'right'}}>
                        <Table striped bordered size='sm' style={{width: '100%'}}>
                            <tbody>
                                {(this.state.invoice && this.state.invoice.min_invoice_amount != null) &&
                                    <tr style={{border: 'tomato solid'}}>
                                        <td colSpan={2} style={{backgroundColor: 'tomato', textAlign: 'center'}}><b>Minimum Billing Applied</b></td>
                                    </tr>
                                }
                                <tr>
                                    <td style={{...invoiceTotalsStyle}}><b>Bill Subtotal: </b></td>
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}><b>{this.state.invoice && this.state.invoice.bill_cost}</b></td>
                                </tr>
                                <tr>
                                    <td style={{...invoiceTotalsStyle}}><b>Tax: </b></td>
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}><b>{this.state.invoice && this.state.invoice.tax}</b></td>
                                </tr>
                                <tr>
                                    <td style={{...invoiceTotalsStyle}}><b>Invoice Total:</b></td>
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}><b>{this.state.invoice && this.state.invoice.total_cost}</b></td>
                                </tr>
                            </tbody>
                        </Table>
                    </div>
                </Col>
                <InvoiceAmendmentModal
                    show={this.state.showAmendmentModal}
                    toggle={this.toggleAmendmentModal}
                    refreshInvoice={this.getInvoice}
                    invoice={this.state.invoice}
                />
            </Row>
        )
    }
}

const mapStateToProps = store => {
    return {
        sortedInvoices: store.invoices.sortedList
    }
}

export default connect(mapStateToProps)(Invoice)
