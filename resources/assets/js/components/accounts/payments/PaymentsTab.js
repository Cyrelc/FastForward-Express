import React, {Fragment, useEffect, useRef, useState} from 'react'
import {Badge, Button, Card, Col, Row} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'

import AdjustAccountCreditModal from './AdjustAccountCreditModal'
import ManagePaymentMethodsModal from './ManagePaymentMethodsModal'
import PaymentModal from './PaymentModal'

const initialSort = [{column: 'date', dir: 'desc'}, {column: 'payment_id', dir:'desc'}]

export default function PaymentsTab(props) {
    const [outstandingInvoiceCount, setOutstandingInvoiceCount] = useState(null)
    const [payments, setPayments] = useState([])
    const [showAdjustAccountCreditModal, setShowAdjustAccountCreditModal] = useState(false)
    const [showManagePaymentMethodsModal, setShowManagePaymentMethodsModal] = useState(false)
    const [showPaymentModal, setShowPaymentModal] = useState(false)

    const tableRef = useRef()

    const undoPayment = cell => {
        if(!props.canUndoPayments) {
            console.log("Does not have permission to undo payments")
            return
        }
        const rowData = cell.getRow().getData()
        if(rowData.has_stripe_transaction) {
            console.log('Unable to undo payment, as it is a stripe transaction')
            return
        }
        const data = {
            payment_id: rowData.payment_id
        }
        if(confirm(`Are you certain you would like to undo the payment from ${rowData.date.toLocaleString()} for ${rowData.amount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}? \n\n This action can not be undone.`))
            makeAjaxRequest(`/payments/undo`, 'DELETE', data, response => {
                refreshModel()
            })
    }

    const columns = [
        ...props.canUndoPayments ? [
            {
                formatter: cell => {if(!cell.getRow().getData().has_stripe_transaction) return "<button class='btn btn-sm btn-danger'><i class='fas fa-undo'></i></button>"},
                titleFormatter: () => "<i class='fas fa-undo'></i>",
                width: 50,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                print: false,
                cellClick: (e, cell) => undoPayment(cell)
            }
        ] : [],
        {title: 'Payment ID', field: 'payment_id', visible: false},
        {title: 'Invoice ID', field: 'invoice_id', formatter: props.viewInvoices ? 'link' : 'none', formatterParams:{urlPrefix: '/app/invoices/'}, sorter: 'number', headerFilter: true},
        {title: 'Invoice Date', field: 'invoice_date'},
        {title: 'Payment Received On', field: 'date'},
        {title: 'Payment Method', field: 'payment_type', headerFilter: true, formatter: cell => {
            const data = cell.getRow().getData()
            if(data.has_stripe_transaction)
                return `${cell.getValue()}     <i class='fab fa-stripe fa-lg fa-border' style='float: right'></i>`
            return cell.getValue()
        }},
        {title: 'Reference Number', field: 'reference_value', headerFilter: true},
        {title: 'Comment', field: 'comment'},
        {title: 'Amount', field: 'amount', formatter: 'money', formatterParams: {thousand: ',', symbol: '$'}, sorter: 'number'}
    ]

    const refreshModel = () => {
        makeAjaxRequest(`/payments/${props.accountId}`, 'GET', null, response => {
            response = JSON.parse(response)
            setOutstandingInvoiceCount(response.outstanding_invoice_count)
            setPayments(response.payments)
            tableRef.current.table.setSort(initialSort)
        })
    }

    useEffect(() => {
        refreshModel()
    }, []);

    return (
        <Fragment>
            <Card>
                {props.canEditPayments &&
                    <Card.Header>
                        <Row>
                            {props.canEditPayments &&
                                <Col md={2}>
                                    <Button
                                        variant='primary'
                                        onClick={() => setShowPaymentModal(true)}
                                        disabled={outstandingInvoiceCount <= 0}
                                    >
                                        <i className='fas fa-money-check-alt'></i> Process Payment
                                        <Badge bg='secondary' style={{marginLeft: '10px'}}>{outstandingInvoiceCount == null ? <i className='fas fa-spinner fa-spin'></i> : outstandingInvoiceCount}</Badge>
                                    </Button>
                                </Col>
                            }
                            {props.canEditPaymentMethods &&
                                <Col>
                                    <Button variant='info' onClick={() => setShowManagePaymentMethodsModal(true)}><i className='fas fa-solid fa-credit-card'></i> Manage Payment Methods</Button>
                                </Col>
                            }
                            {props.canEditPayments &&
                                <Col style={{textAlign: 'right'}}>
                                        <Button variant='dark' onClick={() => setShowAdjustAccountCreditModal(true)}><i className='fas fa-money-bill-wave'></i> Adjust Account Credit</Button>
                                </Col>
                            }
                        </Row>
                    </Card.Header>
                }
                <Card.Body>
                    <ReactTabulator
                        data={payments}
                        columns={columns}
                        maxHeight='75vh'
                        options={{
                            layout: 'fitColumns',
                            pagination: 'local',
                            paginationSize: 15
                        }}
                        printAsHtml={true}
                        printStyled={true}
                        ref={tableRef}
                    />
                </Card.Body>
            </Card>
            {props.canEditPayments &&
                <AdjustAccountCreditModal
                    accountId={props.accountId}
                    canEditPayments={props.canEditPayments}
                    hide={() => setShowAdjustAccountCreditModal(false)}
                    refreshPaymentsTab={refreshModel}
                    setAccountBalance={(value) => props.handleChanges({target: {name: 'accountBalance', type: 'number', value: value}})}
                    show={showAdjustAccountCreditModal}
                />
            }
            {props.canEditPaymentMethods &&
                <ManagePaymentMethodsModal
                    accountId={props.accountId}
                    hide={() => setShowManagePaymentMethodsModal(false)}
                    show={showManagePaymentMethodsModal}
                />
            }
            {props.canEditPayments &&
                <PaymentModal
                    accountBalance={props.accountBalance}
                    accountId={props.accountId}
                    canEditPayments={props.canEditPayments}
                    handleChanges={props.handleChanges}
                    hide={() => setShowPaymentModal(false)}
                    refreshPaymentsTab={refreshModel}
                    show={showPaymentModal}
                />
            }
        </Fragment>
    )
}
