import React from 'react'
import {Badge, Card} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'
import ReactDOM from 'react-dom'

export default function PaymentTable(props) {
    const formatPaymentIntentStatus = cell => {
        if(!cell.getValue())
            return ''

        const element = document.createElement('div')

        const data = cell.getRow().getData();
        let status = data.is_stripe_transaction ? data.payment_intent_status : 'Succeeded'

        if(status.includes('.'))
            status = status.split('.')[1]
        status = status.split('_').map(word => word.charAt(0).toUpperCase() + word.substring(1)).join(' ')
        let variant = 'danger'
        if(status == 'Succeeded')
            variant = 'success'
        else if(status == 'Processing')
            variant = 'primary'

        ReactDOM.render(<Badge bg={variant}>{status}</Badge>, element)

        return element
    }

    const revertPayment = cell => {
        if(!props.canRevertPayments) {
            console.log("Does not have permission to undo payments")
            return
        }
        const rowData = cell.getRow().getData()
        if(!rowData.can_be_reverted) {
            console.log("This payment may not be reverted at this time.")
            return
        }

        if(confirm(`Are you certain you would like to undo the payment from ${rowData.date.toLocaleString()} for ${rowData.amount.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}? \n\n This action can not be undone.`))
            makeAjaxRequest(`/payments/${rowData.payment_id}`, 'DELETE', null, response => {
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
                cellClick: (e, cell) => revertPayment(cell)
            }
        ] : [],
        {title: 'Payment ID', field: 'payment_id', visible: false},
        ...props.showAccountFields ? [
            {title: 'Invoice ID', field: 'invoice_id', formatter: props.canViewInvoices ? 'link' : 'none', formatterParams:{urlPrefix: '/invoices/'}, sorter: 'number', headerFilter: true},
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
        {title: 'Payment Status', field: 'payment_intent_status', formatter: formatPaymentIntentStatus},
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
        </Card.Body>
    )
}




