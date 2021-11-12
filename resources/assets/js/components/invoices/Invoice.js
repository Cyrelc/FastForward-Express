import React, {Component} from 'react'
import {Badge, Button, ButtonGroup, Col, Dropdown, DropdownButton, FormCheck, Row, Table} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'
import { connect } from 'react-redux'

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
            showLineItems: true,
            amendmentsOnly: false
        }
        this.getInvoice = this.getInvoice.bind(this)
        this.handleChange = this.handleChange.bind(this)
        this.regather = this.regather.bind(this)
        this.toggleFinalized = this.toggleFinalized.bind(this)
    }

    componentDidMount() {
        this.getInvoice()
    }

    componentDidUpdate(prevProps) {
        if(prevProps.location.pathname != this.props.location.pathname)
            this.getInvoice()
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
                amendments: response.amendments,
                amount: response.amount,
                billCount: response.bill_count,
                billCountWithMissedLineItems: response.bill_count_with_missed_line_items,
                invoice: response.invoice,
                nextInvoiceId: nextInvoiceId,
                parent: response.parent,
                permissions: response.permissions,
                prevInvoiceId: prevInvoiceId,
                showLineItems: response.parent.show_invoice_line_items,
                tables: response.tables,
                unpaidInvoices: response.unpaid_invoices
            })
        })
    }

    handleChange(event) {
        const {checked, name, value, type} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    regather() {
        makeAjaxRequest('/invoices/regather/' + this.state.invoice.invoice_id, 'GET', null, response => {
            response = JSON.parse(response)
            if(response.count > 0)
                toastr.success('Success', response.count + ' line items successfully added to invoice')
            else
                toastr.error('Warning', 'No matching line items were found for this invoice');
        })
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
                    <h5>
                        <Badge pill variant={(this.state.invoice && this.state.invoice.finalized) ? 'success' : 'danger'}>{(this.state.invoice && this.state.invoice.finalized) ? 'Finalized' : 'Not Finalized'}</Badge>
                        {this.state.amendments && <Badge variant='warning'>Amended</Badge>}
                    </h5>
                </Col>
                <Col md={2}>
                    <ButtonGroup>
                        <LinkContainer to={'/app/invoices/' + this.state.prevInvoiceId}><Button variant='info' disabled={!this.state.prevInvoiceId} size='sm'><i className='fas fa-arrow-circle-left'></i> Back - {this.state.prevInvoiceId}</Button></LinkContainer>
                        <LinkContainer to={'/app/invoices/' + this.state.nextInvoiceId}><Button variant='info' disabled={!this.state.nextInvoiceId} size='sm'>Next - {this.state.nextInvoiceId} <i className='fas fa-arrow-circle-right'></i></Button></LinkContainer>
                    </ButtonGroup>
                </Col>
                <Col md={2}>
                    <FormCheck
                        name='showLineItems'
                        label='Show Line Items'
                        checked={this.state.showLineItems}
                        onChange={this.handleChange}
                    />
                    {this.state.amendments && 
                        <FormCheck
                            name='amendmentsOnly'
                            label='Amendments Only'
                            checked={this.state.amendmentsOnly}
                            onChange={this.handleChange}
                        />
                    }
                </Col>
                <Col md={4} style={{textAlign: 'right'}}>
                    <ButtonGroup>
                        <Button
                            href={this.state.invoice ? '/invoices/print/' + this.state.invoice.invoice_id + '?show_line_items=' + this.state.showLineItems + '&amendments_only=' + this.state.amendmentsOnly : null}
                            target='_blank'
                            variant='success'
                        ><i className='fas fa-print'> Generate PDF</i></Button>
                        {(this.state.invoice && this.state.permissions.edit) &&
                            this.state.invoice.finalized ? 
                                <Button variant='danger' onClick={this.toggleFinalized}><i className='fas fa-unlock'></i> Unfinalize</Button> :
                                <Button variant='success' onClick={this.toggleFinalized}><i className='fas fa-lock'></i> Finalize</Button>
                        }
                        {(this.state.invoice && this.state.permissions.edit) &&
                            <Button variant='warning' onClick={this.regather} disabled={this.state.invoice.bill_count_with_missed_line_items == 0}>
                                <i className='fas fa-sync-alt'></i> {this.state.finalized ? 'Gather Amendments' : 'Regather Bills'} <Badge pill bg='secondary'>{this.state.invoice.bill_count_with_missed_line_items}</Badge>
                            </Button>
                        }
                    </ButtonGroup>
                </Col>
                <Col md={11}>
                    <hr/>
                </Col>
                <Col md={11}>
                    <table style={{width: '100%'}}>
                        <tr>
                            <td style={{width: '40%'}}><h3><LinkContainer to={'/app/accounts/' + this.state.accountId}><a>{this.state.parent && (this.state.parent.account_number + ' - ' + this.state.parent.name)}</a></LinkContainer></h3></td>
                            <td style={{...headerTDStyle, backgroundColor: '#ADD8E6'}}>{'Bill Count\n' + (this.state.invoice && this.state.invoice.bill_count)}</td>
                            <td style={{...headerTDStyle, backgroundColor: '#ADD8E6'}}>{'Invoice Total\n' + (this.state.invoice && parseFloat(this.state.invoice.total_cost).toLocaleString('en-US', {style: 'currency', currency: 'USD', symbol: '$'}))}</td>
                            <td style={{...headerTDStyle, backgroundColor: 'orange'}}>{'Account Balance\n' + (this.state.accountOwing && parseFloat(this.state.accountOwing).toLocaleString('en-US', {style: 'currency', currency: 'USD', symbol: '$'}))}</td>
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
                {(!this.state.amendmentsOnly && this.state.tables) &&
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
                                                        if(this.state.showLineItems) {
                                                            return <td width='15%' style={{textAlign: 'right'}}>
                                                                <table style={{border: 'none', width: '100%'}}>
                                                                    <tbody>
                                                                        {bill.line_items.map(line_item =>
                                                                            <tr key={line_item.line_item_id}>
                                                                                <td style={{textAlign: 'left'}}>{line_item.name}</td>
                                                                                <td>{parseFloat(line_item.price).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                                                            </tr>
                                                                        )}
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr>
                                                                            <td style={{textAlign: 'left'}}><b>Total: </b></td>
                                                                            <td><b>{parseFloat(bill.amount).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b></td>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </td>
                                                        } else
                                                            return <td width='10%' style={{textAlign: 'right'}}>{parseFloat(bill.amount).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                                    case 'bill_id':
                                                        if(this.state.permissions.viewBills)
                                                            return <td width='8%'><LinkContainer to={'/app/bills/' + bill.bill_id}><a>{bill.bill_id}</a></LinkContainer></td>
                                                        else
                                                            return <td width='8%'>{bill.bill_id}</td>
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
                                            <td style={{textAlign: 'right'}}><b>{parseFloat(this.state.tables[key].subtotal).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b></td>
                                        </tr>
                                    }
                                    {Object.keys(this.state.tables).length > 1 &&
                                        <tr>
                                            <td><b>Tax: </b></td>
                                            <td style={{textAlign: 'right'}}><b>{parseFloat(this.state.tables[key].tax).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b></td>
                                        </tr>
                                    }
                                    {Object.keys(this.state.tables).length > 1 &&
                                        <tr>
                                            <td><b>Subtotal: </b></td>
                                            <td style={{textAlign: 'right'}}><b>{parseFloat(this.state.tables[key].total).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b></td>
                                        </tr>
                                    }
                                </tbody>
                            </Table>
                        </Col>
                    )
                }
                <Col md={8}>
                    <Row>
                        {this.state.amendments &&
                            <Col md={11}>
                                <b>Amendments</b>
                                <Table width='100%'>
                                    <thead>
                                        <tr>
                                            <td>Bill ID</td>
                                            <td style={{textAlign: 'right'}}>Amount</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {this.state.amendments.map(amendment =>
                                            <tr>
                                                <td width='10%'><LinkContainer to={'/app/bills/' + amendment.bill_id}><a>{amendment.bill_id}</a></LinkContainer></td>
                                                <td style={{textAlign: this.state.showLineItems ? '' : 'right'}}>
                                                    {(this.state.showLineItems && amendment.line_items) ?
                                                        <Table bordered size='sm' striped>
                                                            {amendment.line_items.map(lineItem =>
                                                                <tr>
                                                                    <td>{lineItem.name}</td>
                                                                    <td style={{textAlign: 'right'}}>{parseFloat(lineItem.price).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                                                </tr>
                                                            )}
                                                        </Table> :
                                                        parseFloat(amendment.amount).toLocaleString('en-US', {style: 'currency', currency: 'USD'})
                                                    }
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </Table>
                            </Col>
                        }
                        {this.state.unpaidInvoices &&
                            <Col md={11}>
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
                                                <td>{parseFloat(unpaidInvoice.total_cost).toLocaleString('en-US', {style: 'currency', currency: 'USD', symbol: '$'})}</td>
                                                <td>{parseFloat(unpaidInvoice.balance_owing).toLocaleString('en-US', {style: 'currency', currency: 'USD', symbol: '$'})}</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </Table>
                            </Col>
                        }
                    </Row>
                </Col>
                <Col md={3}>
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
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}><b>{this.state.invoice && parseFloat(this.state.invoice.bill_cost).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b></td>
                                </tr>
                                <tr>
                                    <td style={{...invoiceTotalsStyle}}><b>Tax: </b></td>
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}><b>{this.state.invoice && parseFloat(this.state.invoice.tax).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b></td>
                                </tr>
                                <tr>
                                    <td style={{...invoiceTotalsStyle}}><b>Invoice Total:</b></td>
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}><b>{this.state.invoice && parseFloat(this.state.invoice.total_cost).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b></td>
                                </tr>
                            </tbody>
                        </Table>
                    </div>
                </Col>
            </Row>
        )
    }
}

const mapStateToProps = store => {
    return {
        frontEndPermissions: store.app.frontEndPermissions,
        sortedInvoices: store.invoices.sortedList
    }
}

export default connect(mapStateToProps)(Invoice)
