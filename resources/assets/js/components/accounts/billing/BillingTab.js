import React, {Fragment, useEffect, useRef, useState} from 'react'
import {Button, Card, Col, Row} from 'react-bootstrap'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import AdjustAccountCreditModal from './AdjustAccountCreditModal'
import LoadingSpinner from '../../partials/LoadingSpinner'
import ManagePaymentMethodsModal from './ManagePaymentMethodsModal'
import PaymentTable from '../../partials/Payments/PaymentTable'
import PaymentModal from '../../partials/Payments/PaymentModal'
import {useAPI} from '../../../contexts/APIContext'
import {CurrencyCellRenderer, LinkCellRenderer} from '../../../utils/table_cell_renderers'

export default function BillingTab(props) {
    const [isLoading, setIsLoading] = useState(true)
    const [outstandingInvoices, setOutstandingInvoices] = useState([])
    const [paymentInvoice, setPaymentInvoice] = useState({})
    const [payments, setPayments] = useState([])
    const [showAdjustAccountCreditModal, setShowAdjustAccountCreditModal] = useState(false)
    const [showManagePaymentMethodsModal, setShowManagePaymentMethodsModal] = useState(false)
    const [showPaymentModal, setShowPaymentModal] = useState(false)

    const api = useAPI()

    const invoiceColumns = [
        {
            header: 'Invoice ID',
            accessorKey: 'invoice_id',
            Cell: ({renderedCellValue, row}) => (
                <LinkCellRenderer renderedCellValue={renderedCellValue} row={row} urlPrefix='/invoices/' />
            )
        },
        {header: 'Last Bill Date', accessorKey: 'bill_end_date'},
        {
            header: 'Balance Owing',
            accessorKey: 'balance_owing',
            Cell: CurrencyCellRenderer
        },
        {
            header: 'Process Payment',
            Cell: ({row}) => (
            <Button className='success' onClick={() => makePayment(row)}><i className='fas fa-hand-holding-usd'></i></Button>
            ),
            size: 50,
            enableSorting: false,
            enableHiding: false
        }
    ]

    const invoiceTable = useMaterialReactTable({
        columns: invoiceColumns,
        data: outstandingInvoices,
        enableTopToolbar: false,
        initialState: {
            density: 'compact'
        }
    })

    useEffect(() => {
        refreshModel()
    }, [])

    const makePayment = cell => {
        setPaymentInvoice(cell.getData())
        setShowPaymentModal(!showPaymentModal)
    }

    const refreshModel = () => {
        setIsLoading(true)
        api.get(`/accounts/billing/${props.accountId}`)
            .then(response => {
                setPayments(response.payments)
                setOutstandingInvoices(response.outstanding_invoices)
            }).finally(
                () => setIsLoading(false)
            )
    }
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
                            {isLoading && <LoadingSpinner />}
                            <MaterialReactTable table={invoiceTable} />
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
