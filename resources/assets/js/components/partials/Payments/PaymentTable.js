import React, {Fragment, useState} from 'react'
import {Badge, Button, ButtonGroup, Card, FormControl, InputGroup, Modal,} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'
import ReactDOM from 'react-dom'
import {useHistory} from 'react-router-dom'
import {toast} from 'react-toastify'

import {useAPI} from '../../../contexts/APIContext'

const parseTitle = (data) => {
    let title = ''
    if(data.stripe_payment_intent_id)
        title += `Payment Intent ID: ${data.stripe_payment_intent_id}\n${data.error ?? ''}\n`
    if(data.stripe_refund_id)
        title += `Refund ID: ${data.stripe_refund_id} \n ${data.error ?? ''}`

    return title
}

export default function PaymentTable(props) {
    const [revertReason, setRevertReason] = useState('')
    const [revertRow, setRevertRow] = useState(null)

    const api = useAPI()
    const history = useHistory()

    const formatPaymentIntentStatus = cell => {
        if(!cell.getValue())
            return ''

        const element = document.createElement('div')

        const data = cell.getRow().getData();
        let status = data.is_stripe_transaction ? data.stripe_status : 'Succeeded'

        if(status.includes('.'))
            status = status.split('.')[1]
        status = status.split('_').map(word => word.charAt(0).toUpperCase() + word.substring(1)).join(' ')
        let variant = 'danger'
        if(status == 'Succeeded' || status == 'Refunded')
            variant = 'success'
        else if(status == 'Processing')
            variant = 'primary'

        ReactDOM.render(
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
        , element)

        return element
    }

    const revertPayment = revertReason => {
        if(!props.canRevertPayments) {
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

        const rowData = revertRow.getData()
        if(!rowData.can_be_reverted) {
            console.error("This payment may not be reverted at this time.")
            return
        }

        const data = {reason: revertReason}
        if(confirm(`Are you certain you would like to revert the payment from ${rowData.date.toLocaleString()} for ${rowData.amount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}? \n\n This action can not be undone.`))
            api.delete(`/payments/${rowData.payment_id}`, data).then(response => {
                setRevertRow(null)
                setRevertReason('')
                props.refresh()
            })
    }

    const columns = [
        ...props.canRevertPayments ? [
            {
                formatter: cell => {
                    if(cell.getRow().getData().can_be_reverted)
                        return "<button class='btn btn-sm btn-warning'><i class='fas fa-undo'></i></button>"
                    return ''
                },
                titleFormatter: () => "<i class='fas fa-undo'></i>",
                width: 50,
                hozAlign: 'center',
                headerHozAlign: 'center',
                headerSort: false,
                print: false,
                cellClick: (e, cell) => setRevertRow(cell.getRow())
            }
        ] : [],
        {title: 'Payment ID', field: 'payment_id', visible: false},
        ...props.showAccountFields ? [
            {title: 'Invoice ID', field: 'invoice_id', ...configureFakeLink('/invoices/', history.push), sorter: 'number', headerFilter: true},
            {title: 'Invoice Date', field: 'invoice_date'},
        ] : [],
        {title: 'Payment Received On', field: 'date'},
        {title: 'Payment Method', field: 'payment_type', headerFilter: true, formatter: cell => {
            const data = cell.getRow().getData()
            if(data.has_stripe_transaction)
                return `${cell.getValue()}     <i class='fab fa-stripe fa-lg fa-border' style='float: right'></i>`
            return cell.getValue()
        }},
        {title: 'Reference Number', field: 'reference_value', headerFilter: true},
        {title: 'Comment', field: 'comment', formatter: 'textarea'},
        {title: 'Amount', field: 'amount', formatter: 'money', formatterParams: {thousand: ',', symbol: '$'}, sorter: 'number'},
        {title: 'Payment Status', field: 'stripe_status', formatter: formatPaymentIntentStatus},
        {title: 'Error', field: 'error', visible: false},
        {title: 'stripe_payment_intent_id', field: 'stripe_payment_intent_id', visible: false},
        {title: 'stripe_refund_id', field: 'stripe_refund_id', visible: false},
        {title: 'receipt_url', field: 'receipt_url', visible: false},
        {title: 'can_be_reverted', field: 'can_be_reverted', visible: false},
    ]

    return (
        <Card.Body>
            <ReactTabulator
                data={props.payments}
                columns={columns}
                initialSort={[{column: 'date', dir: 'desc'}]}
                maxHeight='75vh'
                options={{
                    layout: 'fitColumns',
                    pagination: 'local',
                    paginationSize: 10
                }}
                printAsHtml={true}
                printStyled={true}
            />
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




