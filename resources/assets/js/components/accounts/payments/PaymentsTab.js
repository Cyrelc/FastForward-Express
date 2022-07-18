import React, {Fragment, useEffect, useState} from 'react'
import {Badge, Button, Card, Col, Row} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'

import AdjustAccountCreditModal from './AdjustAccountCreditModal'
import ManageCreditCardsModal from './ManageCreditCardsModal'
import PaymentModal from './PaymentModal'

export default function PaymentsTab(props) {

    const [outstandingInvoiceCount, setOutstandingInvoiceCount] = useState(0)
    const [payments, setPayments] = useState([])
    const [showAdjustAccountCreditModal, setShowAdjustAccountCreditModal] = useState(false)
    const [showManageCreditCardsModal, setShowManageCreditCardsModal] = useState(false)
    const [showPaymentModal, setShowPaymentModal] = useState(false)

    const columns = [
        {title: 'Invoice ID', field: 'invoice_id', formatter: props.viewInvoices ? 'link' : 'none', formatterParams:{urlPrefix: '/app/invoices/'}, sorter: 'number', headerFilter: true},
        {title: 'Invoice Date', field: 'invoice_date'},
        {title: 'Payment Received On', field: 'date'},
        {title: 'Payment Method', field: 'payment_type', headerFilter: true},
        {title: 'Reference Number', field: 'reference_value', headerFilter: true},
        {title: 'Comment', field: 'comment'},
        {title: 'Amount', field: 'amount', formatter: 'money', formatterParams: {thousand: ',', symbol: '$'}, sorter: 'number'}
    ]

    const refreshModel = () => {
        makeAjaxRequest(`/payments/${props.accountId}`, 'GET', null, response => {
            response = JSON.parse(response)
            setOutstandingInvoiceCount(response.outstandingInvoiceCount)
            setPayments(response.payments)
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
                                    ><i className='fas fa-money-check-alt'></i> Receive Payment <Badge bg='secondary'>{outstandingInvoiceCount}</Badge>
                                    </Button>
                                </Col>
                            }
                            {props.canEditPaymentMethods &&
                                <Col>
                                    <Button variant='info' onClick={() => setShowManageCreditCardsModal(true)}><i className='fas fa-solid fa-credit-card'></i> Manage Credit Cards</Button>
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
                        initialSort={[{column:'date', dir:'desc'}]}
                        maxHeight='80vh'
                        options={{
                            layout: 'fitColumns',
                            pagination: 'local',
                            paginationSize: 20
                        }}
                        printAsHtml={true}
                        printStyled={true}
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
                <ManageCreditCardsModal
                    accountId={props.accountId}
                    hide={() => setShowManageCreditCardsModal(false)}
                    show={showManageCreditCardsModal}
                />
            }
            {props.canEditPayments &&
                <PaymentModal
                    accountBalance={props.accountBalance}
                    accountId={props.accountId}
                    canEditPayments={props.canEditPayments}
                    hide={() => setShowPaymentModal(false)}
                    refreshPaymentsTab={refreshModel}
                    show={showPaymentModal}
                />
            }
        </Fragment>
    )
}
