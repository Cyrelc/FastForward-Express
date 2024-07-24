import React, {Fragment, useMemo, useState} from 'react'
import {Badge, Button, ButtonGroup, Card, FormControl, InputGroup, Modal,} from 'react-bootstrap'
import {createRoot} from 'react-dom/client'
import {useHistory} from 'react-router-dom'
import {toast} from 'react-toastify'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import {CurrencyCellRenderer, LinkCellRenderer} from '../../../utils/table_cell_renderers'
import {useAPI} from '../../../contexts/APIContext'

const parseTitle = (data) => {
    let title = ''
    if(data.stripe_payment_intent_id)
        title += `Payment Intent ID: ${data.stripe_payment_intent_id}\n${data.error ?? ''}\n`
    if(data.stripe_refund_id)
        title += `Refund ID: ${data.stripe_refund_id} \n ${data.error ?? ''}`

    return title
}

const PaymentIntentStatusRenderer = ({renderedCellValue, row}) => {
    if(!renderedCellValue)
        return ''

    const element = document.createElement('div')
    const root = createRoot(element)

    const data = row.original
    let status = data.is_stripe_transaction ? data.stripe_status : 'Succeeded'

    if(status.includes('.'))
        status = status.split('.')[1]
    status = status.split('_').map(word => word.charAt(0).toUpperCase() + word.substring(1)).join(' ')
    let variant = 'danger'
    if(status == 'Succeeded' || status == 'Refunded')
        variant = 'success'
    else if(status == 'Processing')
        variant = 'primary'

    return (
        <Fragment>
            <Badge
                bg={variant}
                title={parseTitle(data)}
                onClick={() => {
                    if(window.location.protocol === 'https:') {
                        if(data.stripe_object_type == 'payment_intent')
                            navigator.clipboard.writeText(data.stripe_payment_intent_id)}
                        else
                            navigator.clipboard.writeText(data.stripe_refund_id)
                        toast.success('Stripe ID copied to clipboard')
                    }
                }
            >{status}</Badge>
            {data.receipt_url && <Button size='sm' href={data.receipt_url} target='none' style={{float: 'right'}}><i className='fas fa-receipt' /></Button>}
        </Fragment>
    )
}

export default function PaymentTable(props) {
    const [revertReason, setRevertReason] = useState('')
    const [revertRow, setRevertRow] = useState(null)

    const api = useAPI()

    const {canRevertPayments, showAccountFields, payments} = props

    const columns = useMemo(() => [
        ...canRevertPayments ? [
            {
                id: 'revert',
                header: <i className='fas fa-undo'></i>,
                Cell: ({row}) => {
                    if(row.original.can_be_reverted)
                        return <Button className='warning' size='sm' onClick={() => setRevertRow(row.original)}><i className='fas fa-undo'></i></Button>
                    return ''
                },
                size: 50,
                enableSorting: false,
                enableHiding: false
            }
        ] : [],
        ...showAccountFields ? [
            {
                header: 'Invoice ID',
                accessorKey: 'invoice_id',
                Cell: ({renderedCellValue, row}) => (
                    <LinkCellRenderer renderedCellValue={renderedCellValue} row={row} urlPrefix='/invoices/' />
                )
            },
            {header: 'Invoice Date', accessorKey: 'invoice_date'},
        ] : [],
        {header: 'Payment Received On', accessorKey: 'date'},
        {header: 'Payment Method', accessorKey: 'payment_type', Cell: ({renderedCellValue, row}) => {
            if(row.original.is_stripe_transaction)
                return `${renderedCellValue} <i class='fab fa-stripe fa-lg fa-border' style='float: right'></i>`
            return renderedCellValue
        }},
        {header: 'Reference Number', accessorKey: 'reference_value'},
        {header: 'Comment', accessorKey: 'comment'},
        {header: 'Amount', accessorKey: 'amount', Cell: CurrencyCellRenderer},
        {header: 'Payment Status', accessorKey: 'stripe_status', Cell: PaymentIntentStatusRenderer},
        {header: 'Error', accessorKey: 'error'},
    ], [props.canRevertPayments, showAccountFields])

    const paymentsTable = useMaterialReactTable({
        columns,
        data: payments,
        initialState: {
            density: 'compact',
            sorting: [{id: 'date', asc: true}],
            columnOrder: [
                ...canRevertPayments ? ['revert'] : [],
                ...showAccountFields ? ['invoice_id', 'invoice_date'] : [],
            ],
            columnVisibility: {
                invoice_date: false,
                error: false
            }
        }
    })

    const revertPayment = revertReason => {
        if(!canRevertPayments) {
            console.error("Does not have permission to revert payments")
            return
        }
        if(!revertRow) {
            console.error('No payment set to be reverted')
            return
        }
        if(!revertReason) {
            toast.error('Must provide a reason for reverting this payment. Please try again.')
            return
        }

        if(!revertRow.can_be_reverted) {
            console.error("This payment may not be reverted at this time.")
            return
        }

        const data = {reason: revertReason}
        if(confirm(`Are you certain you would like to revert the payment from ${revertRow.date.toLocaleString()} for ${revertRow.amount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}? \n\n This action can not be undone.`))
            api.delete(`/payments/${revertRow.payment_id}`, data).then(response => {
                setRevertRow(null)
                setRevertReason('')
                props.refresh()
            })
    }

    return (
        <Card.Body>
            <MaterialReactTable table={paymentsTable} />
            <Modal show={revertRow != null} onHide={() => setRevertRow(null)}>
                <Modal.Header closeButton>
                    <Modal.Title>Please select a reason</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <ButtonGroup>
                        <Button onClick={() => revertPayment('duplicate')}>
                            Duplicate
                        </Button>
                        <Button onClick={() => revertPayment('requested_by_customer')}>
                            Requested by Customer
                        </Button>
                        <Button onClick={() => revertPayment(revertReason)}>
                            Other
                        </Button>
                    </ButtonGroup>
                    <InputGroup>
                        <InputGroup.Text>Reason</InputGroup.Text>
                        <FormControl
                            value={revertReason}
                            onChange={event => setRevertReason(event.target.value)}
                            placeholder='Required only for "other"'
                        />
                    </InputGroup>
                </Modal.Body>
            </Modal>
        </Card.Body>
    )
}




