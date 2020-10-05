import React from 'react'
import Table from '../partials/Table'

function deleteInvoice(cell) {
    if(cell.getRow().getData().payment_count == 0 && confirm('Are you sure you wish to delete invoice ' + cell.getRow().getData().invoice_id + '?\nThis action can not be undone')) {
        makeFetchRequest('/invoices/delete/' + cell.getRow().getData().invoice_id, data => {
            location.reload()
        })
    }
}

const columns = [
    {formatter: (cell) => {if(cell.getRow().getData().payment_count == 0) return "<button class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>"}, width:50, align:'center', cellClick:(e, cell) => deleteInvoice(cell)},
    {title: 'Invoice ID', field: 'invoice_id', formatter: 'link', formatterParams:{labelField:'invoice_id', urlPrefix:'/invoices/view/'}, sorter:'number'},
    {title: 'Account', field: 'account_id', formatter: 'link', formatterParams:{labelField:'account_name', urlPrefix:'accounts/edit/'}},
    {title: 'Date Run', field: 'date', sorter:'date', visible: false},
    {title: 'Bill Start Date', field: 'bill_start_date', sorter:'date', visible: false},
    {title: 'Bill End Date', field: 'bill_end_date', sorter:'date'},
    {title: 'Balance Owing', field: 'balance_owing', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision:2}, sorter:'number'},
    {title: 'Bill Cost', field: 'bill_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:'sum', topCalcParams:{precision: 2}, sorter:'number'},
    {title: 'Total Cost', field: 'total_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision:2}, sorter:'number'},
    {title: 'Bill Count', field: 'bill_count', sorter: 'number', topCalc:'sum'}
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
        value: 'date',
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
    }
]

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Account', value: 'account_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].account_name}},
    {label: 'Bill End Date', value: 'bill_end_date'}
]

const initialSort = [{column:'invoice_id', dir: 'desc'}]

export default function Invoices(props) {
    return (
        <Table
            baseRoute='/invoices/buildTable'
            columns={columns}
            filters={filters}
            groupByOptions={groupByOptions}
            initialSort={initialSort}
            pageTitle='Invoices'
        />
    )
}
