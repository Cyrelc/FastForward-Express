import React, {Fragment, useEffect, useState} from 'react'
import {Badge, Button, ButtonGroup, Col, Container, FormCheck, Row, Table} from 'react-bootstrap'
import {LinkContainer} from 'react-router-bootstrap'
import {connect} from 'react-redux'

import PaymentModal from '../partials/Payments/PaymentModal'
import PaymentTable from '../partials/Payments/PaymentTable'
import LoadingSpinner from '../partials/LoadingSpinner'

const headerTDStyle = {width: '20%', textAlign: 'center', border: 'grey solid', whiteSpace: 'pre', paddingTop: '10px', paddingBottom: '10px'}
const invoiceTotalsStyle = {backgroundColor: 'orange', border: 'orange solid'}

function getCorrectAddress(bill) {
    if(!bill?.charge_account_id || bill.charge_account_id != bill.delivery_account_id)
        return bill.delivery_address_name ?? bill.delivery_address_formatted
    return bill.pickup_address_name ? bill.pickup_address_name : bill.pickup_address_formatted
}

function Invoice(props) {
    const [amendmentsOnly, setAmendmentsOnly] = useState(false)
    const [accountId, setAccountId] = useState('')
    const [amendments, setAmendments] = useState([])
    const [billCountWithMissedLineItems, setBillCountWithMissedLineItems] = useState(0)
    const [hideOutstandingInvoices, setHideOutstandingInvoices] = useState(false)
    const [invoice, setInvoice] = useState({})
    const [isFinalized, setIsFinalized] = useState(true)
    const [isLoading, setIsLoading] = useState(true)
    const [isPrepaid, setIsPrepaid] = useState(false)
    const [nextInvoiceId, setNextInvoiceId] = useState(null)
    const [parent, setParent] = useState({})
    const [paymentAmount, setPaymentAmount] = useState(null)
    const [payments, setPayments] = useState([])
    const [permissions, setPermissions] = useState({})
    const [prevInvoiceId, setPrevInvoiceId] = useState(null)
    const [queryString, setQueryString] = useState('')
    const [showLineItems, setShowLineItems] = useState(true)
    const [showPickupAndDeliveryAddress, setShowPickupAndDeliveryAddress] = useState(false)
    const [showPaymentModal, setShowPaymentModal] = useState(false)
    const [tables, setTables] = useState([])
    const [unpaidInvoices, setUnpaidInvoices] = useState([])

    const {match: {params}} = props

    useEffect(() => {
        getInvoice(params?.invoiceId)
    }, [params.invoiceId])

    useEffect(() => {
        setQueryString(`?show_line_items=${showLineItems}&amendments_only=${amendmentsOnly}&hide_outstanding_invoices=${hideOutstandingInvoices}&showPickupAndDeliveryAddress=${showPickupAndDeliveryAddress}`)
    }, [showLineItems, showPickupAndDeliveryAddress, hideOutstandingInvoices, amendmentsOnly])

    const getInvoice = () => {
        setIsLoading(true)
        makeAjaxRequest(`/invoices/getModel/${props.match.params.invoiceId}`, 'GET', null, response => {
            response = JSON.parse(response)
            document.title = `View Invoice ${response.invoice.invoice_id}`
            const thisInvoiceIndex = props.sortedInvoices.findIndex(invoice_id => invoice_id === response.invoice.invoice_id)
            const prevInvoiceId = thisInvoiceIndex <= 0 ? null : props.sortedInvoices[thisInvoiceIndex - 1]
            const nextInvoiceId = (thisInvoiceIndex < 0 || thisInvoiceIndex === props.sortedInvoices.length - 1) ? null : props.sortedInvoices[thisInvoiceIndex + 1]
            setAccountId(response.invoice.account_id)
            setAmendments(response.amendments)
            setBillCountWithMissedLineItems(response.bill_count_with_missed_line_items)
            setInvoice(response.invoice)
            setIsFinalized(response.invoice.finalized)
            setIsPrepaid(response.is_prepaid)
            setNextInvoiceId(nextInvoiceId)
            setParent(response.parent)
            setPaymentAmount(response.account_owing)
            setPayments(response.payments)
            setPermissions(response.permissions)
            setPrevInvoiceId(prevInvoiceId)
            setShowLineItems(response.parent?.show_invoice_line_items ?? true)
            setShowPickupAndDeliveryAddress(response.parent?.show_pickup_and_delivery_address ?? true)
            setTables(response.tables)
            setUnpaidInvoices(response.unpaid_invoices)

            setIsLoading(false)
        })
    }

    const regather = () => {
        makeAjaxRequest(`/invoices/regather/${invoice.invoice_id}`, 'GET', null, response => {
            response = JSON.parse(response)
            if(response.count > 0)
                toastr.success('Success', `${response.count} line items successfully added to invoice`)
            else
                toastr.error('Warning', 'No matching line items were found for this invoice');
        })
    }

    const toggleFinalized = () => {
        makeAjaxRequest(`/invoices/finalize/${params.invoiceId}`, 'GET', null, response => {
            const finalized = response.invoices[params.invoiceId]['finalized']
            setIsFinalized(finalized)
        })
    }

    if(isLoading)
        return <LoadingSpinner />

    return (
        <Fragment>
            <Container fluid>
                <Row style={{paddingTop: '20px'}} className='justify-content-md-center'>
                    <Col md={2}>
                        <h3>Invoice {invoice?.invoice_id}</h3>
                        <h5>
                            <ButtonGroup size='sm' className='rounded-pill'>
                                <Button
                                    disabled={true}
                                    variant={isFinalized ? 'success' : 'danger'}
                                >{isFinalized ? 'Finalized' : 'Not Finalized'}</Button>
                                {(invoice && permissions.edit) &&
                                    <Button
                                        variant={isFinalized ? 'danger' : 'success'}
                                        size='sm'
                                        onClick={toggleFinalized}
                                    >
                                        <i className={isFinalized ? 'fas fa-unlock' : 'fas fa-lock'}></i>
                                    </Button>
                                }
                            </ButtonGroup>
                            {amendments && <Badge variant='warning'>Amended</Badge>}
                        </h5>
                    </Col>
                    <Col md={2}>
                        <ButtonGroup>
                            <LinkContainer to={`/app/invoices/${prevInvoiceId}`}>
                                <Button variant='info' disabled={!prevInvoiceId} size='sm'>
                                    <i className='fas fa-arrow-circle-left'></i> Back - {prevInvoiceId}
                                </Button>
                            </LinkContainer>
                            <LinkContainer to={`/app/invoices/${nextInvoiceId}`}>
                                <Button variant='info' disabled={!nextInvoiceId} size='sm'>
                                    Next - {nextInvoiceId} <i className='fas fa-arrow-circle-right'></i>
                                </Button>
                            </LinkContainer>
                        </ButtonGroup>
                    </Col>
                    <Col md={4}>
                        <FormCheck
                            type='switch'
                            name='showLineItems'
                            label='Show Line Items'
                            checked={showLineItems}
                            onChange={() => setShowLineItems(!showLineItems)}
                        />
                        <FormCheck
                            type='switch'
                            name='showPickupAndDeliveryAddress'
                            label='Show Pickup And Delivery Address'
                            checked={showPickupAndDeliveryAddress}
                            onChange={() => setShowPickupAndDeliveryAddress(!showPickupAndDeliveryAddress)}
                        />
                        {unpaidInvoices &&
                            <FormCheck
                                type='switch'
                                name='hideOutstandingInvoices'
                                label='Hide Outstanding Invoices'
                                checked={hideOutstandingInvoices}
                                onChange={() => setHideOutstandingInvoices(!hideOutstandingInvoices)}
                            />
                        }
                        {amendments?.length &&
                            <FormCheck
                                type='switch'
                                name='amendmentsOnly'
                                label='Amendments Only'
                                checked={amendmentsOnly}
                                onChange={() => setAmendmentsOnly(!amendmentsOnly)}
                            />
                        }
                    </Col>
                    <Col md={4}>
                        <ButtonGroup size='sm'>
                            <Button
                                variant='secondary'
                                href={invoice ? `/invoices/print/${invoice.invoice_id}${queryString}` : null}
                                target='_blank'
                            ><i className='fas fa-print'></i> Generate PDF</Button>
                            {(invoice && permissions.edit) &&
                                <Button variant='warning' onClick={regather} disabled={billCountWithMissedLineItems == 0}>
                                    <i className='fas fa-sync-alt'></i> {isFinalized ? 'Gather Amendments' : 'Regather Bills'} <Badge pill bg='secondary'>{invoice.bill_count_with_missed_line_items}</Badge>
                                </Button>
                            }
                            {(permissions.processPayments && isFinalized && invoice.balance_owing > 0) &&
                                <Button variant='success' disabled={invoice.balance_owing == 0} onClick={() => setShowPaymentModal(!showPaymentModal)}>
                                    <i className='fas fa-hand-holding-usd'></i> Process Payment
                                </Button>
                            }
                        </ButtonGroup>
                    </Col>
                </Row>
            </Container>
            <Container fluid>
                <Row className='justify-content-md-center'>
                    <Col md={11}>
                        <hr/>
                    </Col>
                    <Col md={11}>
                        <table style={{width: '100%'}}>
                            <tbody>
                                <tr>
                                    <td style={{width: '40%'}}>
                                        <h3>
                                            <LinkContainer to={`/app/accounts/${accountId}`}>
                                                <a>{`${parent?.account_number} - ${parent?.name}`}</a>
                                            </LinkContainer>
                                        </h3>
                                    </td>
                                    <td style={{...headerTDStyle, backgroundColor: '#ADD8E6'}}>
                                        {`Bill Count\n${invoice.bill_count}`}
                                    </td>
                                    <td style={{...headerTDStyle, backgroundColor: '#ADD8E6'}}>
                                        {`Invoice Total\n${parseFloat(invoice.total_cost).toLocaleString('en-US', {style: 'currency', currency: 'USD', symbol: '$'})}`}
                                    </td>
                                    <td style={{...headerTDStyle, backgroundColor: 'orange'}}>
                                        {`Invoice Balance\n${parseFloat(invoice.balance_owing).toLocaleString('en-US', {style: 'currency', currency: 'USD', symbol: '$'})}`}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </Col>
                    <Col md={11}>
                        <hr/>
                    </Col>
                    {(parent?.invoice_comment) &&
                        <Fragment>
                            <Col md={11}>
                                {parent.invoice_comment}
                            </Col>
                            <Col md={11}>
                                <hr/>
                            </Col>
                        </Fragment>
                    }
                    {(!amendmentsOnly && tables) &&
                        Object.keys(tables).map(key =>
                            <Col key={key} md={11}>
                                <Table striped bordered size='sm'>
                                    <thead>
                                        <tr>
                                            {Object.keys(tables[key].headers)
                                                .filter(headerKey => showPickupAndDeliveryAddress ? true : headerKey != 'Pickup Address')
                                                    .map(headerKey => {
                                                        if(headerKey === 'Amount')
                                                            return <td key={headerKey} style={{textAlign: 'right'}}>{headerKey}</td>
                                                        return <td key={headerKey}>{headerKey}</td>
                                                    })
                                            }
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {tables[key].bills.map(bill =>
                                            <tr key={bill.bill_id}>
                                                {Object.values(tables[key].headers).map(headerValue => {
                                                    switch(headerValue) {
                                                        case 'delivery_address_name':
                                                            if(showPickupAndDeliveryAddress)
                                                                return <td>{bill.delivery_address_name}</td>
                                                            return <td>{getCorrectAddress(bill)}</td>
                                                        case 'pickup_address_name':
                                                            if(showPickupAndDeliveryAddress)
                                                                return <td>{bill.pickup_address_name}</td>
                                                            return
                                                        case 'amount':
                                                            if(showLineItems) {
                                                                return (
                                                                    <td width='15%' style={{textAlign: 'right'}}>
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
                                                                )
                                                            } else
                                                                return <td width='10%' style={{textAlign: 'right'}}>{parseFloat(bill.amount).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</td>
                                                        case 'bill_id':
                                                            if(permissions.viewBills)
                                                                return (
                                                                    <td width='8%'>
                                                                        <LinkContainer to={`/app/bills/${bill.bill_id}`}>
                                                                            <a>{bill.bill_id}</a>
                                                                        </LinkContainer>
                                                                    </td>
                                                                )
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
                                        {Object.keys(tables).length > 1 &&
                                            <tr>
                                                <td colSpan={Object.keys(tables[key].headers).length - (showPickupAndDeliveryAddress ? 2 : 3)} rowSpan={3} style={{textAlign: 'center', verticalAlign: 'middle'}}>
                                                    <b>Subtotal for {key}</b>
                                                </td>
                                                <td>
                                                    <b>Bill Subtotal: </b>
                                                </td>
                                                <td style={{textAlign: 'right'}}>
                                                    <b>{parseFloat(tables[key].subtotal).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b>
                                                </td>
                                            </tr>
                                        }
                                        {Object.keys(tables).length > 1 &&
                                            <tr>
                                                <td>
                                                    <b>Tax: </b>
                                                </td>
                                                <td style={{textAlign: 'right'}}>
                                                    <b>{parseFloat(tables[key].tax).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b>
                                                </td>
                                            </tr>
                                        }
                                        {Object.keys(tables).length > 1 &&
                                            <tr>
                                                <td>
                                                    <b>Subtotal: </b>
                                                </td>
                                                <td style={{textAlign: 'right'}}>
                                                    <b>{parseFloat(tables[key].total).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b>
                                                </td>
                                            </tr>
                                        }
                                    </tbody>
                                </Table>
                            </Col>
                        )
                    }
                    <Col md={11}>
                        <hr/>
                    </Col>
                    <Col md={8}>
                        {amendments &&
                            <Col md={11}>
                                <Table width='100%'>
                                    <thead>
                                        <tr>
                                            <td colSpan={2}><b>Amendments</b></td>
                                        </tr>
                                    </thead>
                                    <thead>
                                        <tr>
                                            <td>Bill ID</td>
                                            <td style={{textAlign: 'right'}}>Amount</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {amendments.map(amendment =>
                                            <tr>
                                                <td width='10%'>
                                                    <LinkContainer to={`/app/bills/${amendment.bill_id}`}>
                                                        <a>{amendment.bill_id}</a>
                                                    </LinkContainer>
                                                </td>
                                                <td style={{textAlign: showLineItems ? '' : 'right'}}>
                                                    {(showLineItems && amendment.line_items) ?
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
                        {(payments && payments.length > 0) &&
                            <Fragment>
                                <h4>Payments</h4>
                                <PaymentTable
                                    canUndoPayments={permissions.undoPayments}
                                    payments={payments}
                                    refresh={getInvoice}
                                    viewInvoices={true}
                                />
                                <hr/>
                            </Fragment>
                        }
                        {(unpaidInvoices && unpaidInvoices.length > 0 && !hideOutstandingInvoices) &&
                            <Col md={12}>
                                <Table striped bordered size='sm'>
                                    <thead>
                                        <tr>
                                            <td colSpan={4} style={{textAlign: 'center'}}><b>All Invoices with Balance Owing for Account {parent?.name}</b></td>
                                        </tr>
                                    </thead>
                                    <thead>
                                        <tr>
                                            <td>Invoice ID</td>
                                            <td>Date</td>
                                            <td>Invoice Total</td>
                                            <td>Balance Owing</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {unpaidInvoices.map(unpaidInvoice =>
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
                        </Col>
                    <Col md={{span: 2, offset: 1}}>
                        <Table striped bordered size='sm'>
                            <tbody>
                                {(invoice?.min_invoice_amount) &&
                                    <tr style={{border: 'tomato solid'}}>
                                        <td colSpan={2} style={{backgroundColor: 'tomato', textAlign: 'center'}}>
                                            <b>Minimum Billing Applied</b>
                                        </td>
                                    </tr>
                                }
                                <tr>
                                    <td style={{...invoiceTotalsStyle}}>
                                        <b>Bill Subtotal: </b>
                                    </td>
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}>
                                        <b>{parseFloat(invoice?.bill_cost).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{...invoiceTotalsStyle}}>
                                        <b>Tax: </b>
                                    </td>
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}>
                                        <b>{parseFloat(invoice?.tax).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td style={{...invoiceTotalsStyle}}>
                                        <b>Invoice Total:</b>
                                    </td>
                                    <td style={{...invoiceTotalsStyle, textAlign: 'right'}}>
                                        <b>{parseFloat(invoice?.total_cost).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</b>
                                    </td>
                                </tr>
                            </tbody>
                        </Table>
                    </Col>
                </Row>
            </Container>
            <PaymentModal
                hide={() => setShowPaymentModal(false)}
                invoiceBalanceOwing={invoice.balance_owing}
                invoiceId={invoice.invoice_id}
                refresh={getInvoice}
                show={showPaymentModal}
            />
        </Fragment>
    )
}

const mapStateToProps = store => {
    return {
        frontEndPermissions: store.user.frontEndPermissions,
        sortedInvoices: store.invoices.sortedList
    }
}

export default connect(mapStateToProps)(Invoice)
