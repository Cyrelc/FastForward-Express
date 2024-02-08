import React, {Fragment, useEffect, useState} from 'react'
import {Button, Card, Col, Row} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'

import AdjustAccountCreditModal from './AdjustAccountCreditModal'
import LoadingSpinner from '../../partials/LoadingSpinner'
import ManagePaymentMethodsModal from './ManagePaymentMethodsModal'
import PaymentTable from '../../partials/Payments/PaymentTable'
import PaymentModal from '../../partials/Payments/PaymentModal'

export default function BillingTab(props) {
    const [isLoading, setIsLoading] = useState(true)
    const [outstandingInvoices, setOutstandingInvoices] = useState([])
    const [paymentInvoice, setPaymentInvoice] = useState({})
    const [payments, setPayments] = useState([])
    const [showAdjustAccountCreditModal, setShowAdjustAccountCreditModal] = useState(false)
    const [showManagePaymentMethodsModal, setShowManagePaymentMethodsModal] = useState(false)
    const [showPaymentModal, setShowPaymentModal] = useState(false)

    const invoiceColumns = [
        {title: 'Invoice ID', field: 'invoice_id', formatter: props.canViewInvoices ? 'link' : 'none', formatterParams:{urlPrefix: '/invoices/'}, sorter: 'number'},
        {title: 'Last Bill Date', field: 'bill_end_date'},
        {title: 'Balance Owing', field: 'balance_owing', formatter: 'money',formatterParams: {thousand:',', symbol: '$'}, sorter: 'number', topCalc: 'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams: {thousand: ',', symbol: '$'}},
        {formatter: cell => {
            return "<button class='btn btn-sm btn-success'><i class='fas fa-hand-holding-usd'></i></button>"
        }, width: 50, hozAlign: 'center', headerHozAlign: 'center', headerSort: false, print: false, cellClick: (e, cell) => makePayment(cell)}
    ]

    const makePayment = cell => {
        setPaymentInvoice(cell.getData())
        setShowPaymentModal(!showPaymentModal)
    }

    const refreshModel = () => {
        setIsLoading(true)
        makeAjaxRequest(`/accounts/billing/${props.accountId}`, 'GET', null, response => {
            response = JSON.parse(response)
            setPayments(response.payments)
            setOutstandingInvoices(response.outstanding_invoices)
            setIsLoading(false)
        }, () => setIsLoading(false))
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
                            {props.canEditPaymentMethods &&
                                <Col>
                                    <Button variant='info' onClick={() => setShowManagePaymentMethodsModal(true)}>
                                        <i className='fas fa-solid fa-credit-card'></i> Manage Payment Methods
                                    </Button>
                                </Col>
                            }
                            {props.canEditPayments &&
                                <Col style={{textAlign: 'right'}}>
                                    <Button variant='dark' onClick={() => setShowAdjustAccountCreditModal(true)}>
                                        <i className='fas fa-money-bill-wave'></i> Adjust Account Credit
                                    </Button>
                                </Col>
                            }
                        </Row>
                    </Card.Header>
                }
                <Card.Body>
                    <Row>
                        <Col>
                            <h5>Outstanding Invoices</h5>
                        </Col>
                        <Col md={10}>
                            {isLoading ? <LoadingSpinner /> :
                                <ReactTabulator
                                    data={outstandingInvoices}
                                    columns={invoiceColumns}
                                    maxHeight='75vh'
                                    options={{
                                    layout: 'fitColumns',
                                        pagination: 'local',
                                        paginationSize: 10,
                                    }}
                                    printAsHtml={true}
                                    printStyled={true}
                                />
                            }
                        </Col>
                    </Row>
                    <hr/>
                </Card.Body>
                <Card.Body>
                    <Row>
                        <Col>
                            <h5>Payments</h5>
                        </Col>
                        <Col md={10}>
                            {isLoading ? <LoadingSpinner /> :
                                <PaymentTable
                                    canRevertPayments={props.canRevertPayments}
                                    canViewInvoices={props.canViewInvoices}
                                    payments={payments}
                                    refresh={refreshModel}
                                    showAccountFields={true}
                                />
                            }
                        </Col>
                    </Row>
                </Card.Body>
            </Card>
            {props.canEditPayments &&
                <AdjustAccountCreditModal
                    accountId={props.accountId}
                    canEditPayments={props.canEditPayments}
                    hide={() => setShowAdjustAccountCreditModal(false)}
                    refreshPaymentsTab={refreshModel}
                    setAccountBalance={props.setAccountBalance}
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
                    hide={() => setShowPaymentModal(false)}
                    invoiceBalanceOwing={paymentInvoice.balance_owing}
                    invoiceId={paymentInvoice.invoice_id}
                    refresh={refreshModel}
                    show={showPaymentModal}
                />
            }
        </Fragment>
    )
}
