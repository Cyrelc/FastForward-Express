import React from 'react'
import { ToastHeader } from 'react-bootstrap'
import Table from '../partials/Table'

function cellContextMenu(cell) {
    const data = cell.getData()
    if(!data.invoice_id)
        return undefined
    var menuItems = [
        {label: 'Delete Invoice', action: () => deleteInvoice(cell), disabled: (data.payment_count != 0 || data.finalized === 1)},
        {label: data.finalized ? 'Undo Finalize' : 'Finalize Invoice', action: () => toggleInvoiceFinalized([cell.getRow()]), disabled: (data.payment_count !== 0)},
        {label: 'Print', action: () => printInvoices([cell.getRow()]), disabled: data.finalized === 0}
    ]

    return menuItems
}

function cellContextMenuFormatter(cell) {
    if(cell.getData().invoice_id)
        return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"</button>'
}

function deleteInvoice(cell) {
    const data = cell.getData()
    if(data.payment_count == 0 && confirm('Are you sure you wish to delete invoice ' + data.invoice_id + '?\nThis action can not be undone')) {
        makeFetchRequest('/invoices/delete/' + data.invoice_id, response => {
            location.reload()
        })
    }
}

function finalizeInvoices(selectedRows) {
    const unfinalizedRows = selectedRows.filter(row => row.getData().finalized !== 1)
    toggleInvoiceFinalized(unfinalizedRows)
}

function toggleInvoiceFinalized(selectedRows = null) {
    if(!selectedRows || selectedRows.length === 0) {
        toastr.warning('Please select at least one row to operate on')
        return
    }
    const data = selectedRows.map(selectedRow => {return selectedRow.getData().invoice_id})
    makeFetchRequest('/invoices/finalize/' + data, response => {
        selectedRows.map(row => row.update({'finalized': row.getData().finalized === 1 ? 0 : 1}))
    })
}

function printInvoices(selectedRows = null) {
    if(!selectedRows || selectedRows.length === 0) {
        toastr.warning('Please select at least one row to operate on')
        return
    }
    const data = selectedRows.map(selectedRow => {return selectedRow.getData().invoice_id})
    if(selectedRows.length === 1)
        window.open('/invoices/print/' + data[0], "_blank")
    else
        window.open('/invoices/printMass/' + data)
}

function undoFinalizeInvoices(selectedRows) {
    const finalizedRows = selectedRows.filter(row => row.getData().finalized === 1)
    toggleInvoiceFinalized(finalizedRows)
}

const columns = [
    {formatter: cell => cellContextMenuFormatter(cell), width:50, hozAlign:'center', clickMenu: cell => cellContextMenu(cell), headerSort: false, print: false},
    {formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign: 'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
    {title: 'Invoice ID', field: 'invoice_id', formatter: 'link', formatterParams:{labelField:'invoice_id', urlPrefix:'/app/invoices/view/'}, sorter:'number'},
    {title: 'Account Number', field: 'account_number', formatter: 'link', formatterParams:{urlPrefix:'/accounts/edit/N'}},
    {title: 'Account', field: 'account_id', formatter: 'link', formatterParams:{labelField:'account_name', urlPrefix:'/accounts/edit/'}},
    {title: 'Date Run', field: 'date_run', sorter:'date', visible: false},
    {title: 'Bill Start Date', field: 'bill_start_date', sorter:'date', visible: false},
    {title: 'Bill End Date', field: 'bill_end_date', sorter:'date'},
    {title: 'Balance Owing', field: 'balance_owing', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision:2}, sorter:'number'},
    {title: 'Bill Cost', field: 'bill_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:'sum', topCalcParams:{precision: 2}, sorter:'number'},
    {title: 'Total Cost', field: 'total_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision:2}, sorter:'number'},
    {title: 'Bill Count', field: 'bill_count', sorter: 'number', topCalc:'sum', visible: false},
    {title: 'Finalized', field: 'finalized', hozAlign: 'center', formatter: 'tickCross', width: 100}
]

const filters = [
    {
        name: 'Bill End Date',
        value: 'bill_end_date',
        type: 'DateBetweenFilter',
    },
    {
        name: 'Bill Start Date',
        value: 'bill_start_date',
        type: 'DateBetweenFilter'
    },
    {
        name: 'Date Run',
        value: 'date_run',
        type: 'DateBetweenFilter',
    },
    {
        fetchUrl: '/getList/accounts',
        name: 'Account',
        value: 'account_id',
        type: 'SelectFilter',
        isMulti: true
    },
    {
        name: 'Balance Owing',
        value: 'balance_owing',
        type: 'NumberBetweenFilter',
        step: 0.01,
    },
    {
        name: 'Finalized',
        value: 'finalized',
        type: 'BooleanFilter'
    }
]

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Account', value: 'account_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].account_name}},
    {label: 'Bill End Date', value: 'bill_end_date'}
]

const initialSort = [{column:'invoice_id', dir: 'desc'}]

const withSelected = [
    {
        label: 'Finalize',
        onClick: finalizeInvoices
    },
    {
        label: 'Undo Finalize',
        onClick: undoFinalizeInvoices
    },
    {
        label: 'Print',
        onClick: printInvoices
    }
]

export default function Invoices(props) {
    return (
        <Table
            baseRoute='/invoices/buildTable'
            columns={columns}
            filters={filters}
            groupByOptions={groupByOptions}
            initialSort={initialSort}
            pageTitle='Invoices'
            selectable='highlight'
            location={props.location}
            history={props.history}
            withSelected={withSelected}
        />
    )
}
