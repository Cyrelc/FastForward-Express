import React from 'react'
import {toast} from 'react-toastify'
import {useHistory} from 'react-router-dom'

import {useAPI} from '../../contexts/APIContext'
import {useLists} from '../../contexts/ListsContext'
import {useUser} from '../../contexts/UserContext'

import Table from '../partials/Table'

/**
 * Table functions
 *
 */
function cellContextMenuFormatter(cell) {
    if(cell.getRow().getData().invoice_id)
        return '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"></i></button>'
}

function printInvoices(selectedRows, options) {
    if(!selectedRows || selectedRows.length === 0) {
        toast.warn('Please select at least one row to operate on')
        return
    }
    if(selectedRows.length > 50) {
        selectedRows = selectedRows.slice(0, 49)
        toast.warn('Unable to generate more than 50 Invoices at a time - limiting your request to the first 50 items selected.\nApologies for any inconvenience')
    }
    const data = selectedRows.map(selectedRow => {return selectedRow.getData().invoice_id})

    const printUrl = `/invoices/${options.download ? 'download' : 'print'}/`
    window.open(printUrl + (selectedRows.length === 1 ? data[0] : data), "_blank")
}

/**
 * Table constants including definitions
 */

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Account', value: 'account_number', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].account_name}},
    {label: 'Last Bill Date', value: 'bill_end_date'}
]

const initialSort = [{column:'bill_end_date', dir: 'desc'}, {column:'account_number', dir:'asc'}]

export default function Invoices(props) {
    const api = useAPI()
    const history = useHistory()
    const lists = useLists()
    const {authenticatedUser, frontEndPermissions} = useUser()

    function deleteInvoice(cell) {
        const data = cell.getData()
        if(data.payment_count == 0 && confirm(`Are you sure you wish to delete invoice ${data.invoice_id}?\nThis action can not be undone`)) {
            api.delete(`/invoices/${data.invoice_id}`).then(response => {
                cell.getRow().delete()
            })
        }
    }

    const finalizeInvoices = selectedRows => {
        const unfinalizedRows = selectedRows.filter(row => row.getData().finalized !== 1)
        toggleInvoiceFinalized(unfinalizedRows)
    }

    const undoFinalizeInvoices = selectedRows => {
        const finalizedRows = selectedRows.filter(row => row.getData().finalized === 1)
        toggleInvoiceFinalized(finalizedRows)
    }

    const adminWithSelected = [
        {
            icon: 'fas fa-check',
            label: 'Finalize',
            onClick: finalizeInvoices
        },
        {
            icon: 'fas fa-undo',
            label: 'Undo Finalize',
            onClick: undoFinalizeInvoices
        }
    ]

    const cellContextMenu = (cell, canEdit = false) => {
        const data = cell.getData()
        if(!data.invoice_id)
            return undefined
        var menuItems = [
            {label: 'Delete Invoice', action: () => deleteInvoice(cell), disabled: (data.payment_count != 0 || data.finalized === 1)},
            {label: data.finalized ? 'Undo Finalize' : 'Finalize Invoice', action: () => finalizeInvoices([cell.getRow()]), disabled: (data.payment_count !== 0)},
            {label: 'Print', action: () => printInvoices([cell.getRow()], {download: false}), disabled: data.finalized === 0}
        ]

        return menuItems
    }

    const toggleInvoiceFinalized = async (selectedRows = null) => {
        if(!selectedRows || selectedRows.length === 0) {
            toast.warn('Please select at least one row to operate on')
            return
        }
        const data = selectedRows.map(selectedRow => {return selectedRow.getData().invoice_id})
        await api.get(`/invoices/finalize/${data}`)
            .then(selectedRows.map(row => row.update({'finalized': row.getData().finalized === 1 ? 0 : 1})))
    }

    const columns = [
        ...frontEndPermissions.invoices.edit ? [
            {
                formatter: cell => cellContextMenuFormatter(cell),
                width:50,
                hozAlign:'center',
                clickMenu: (event, cell) => cellContextMenu(cell),
                headerSort: false,
                print: false
            }
        ] : [
            {formatter: cell => {
                return "<button class='btn btn-sm btn-success' title='Print'><i class='fas fa-print'></i></button>"
            },
            width: 50,
            hozAlign:'center',
            cellClick:(e, cell) => printInvoices([cell.getRow()], {download: false})
        }],
        ...frontEndPermissions.invoices.edit ? [
            {title: 'Date Run', field: 'date_run', visible: false}
        ] : [],
        {formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign:'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
        {title: 'Invoice ID', field: 'invoice_id', ...configureFakeLink('/invoices/', history.push), sorter: 'number'},
        {title: 'Account ID', field: 'account_id', ...configureFakeLink('/accounts/', history.push), sorter: 'number'},
        {title: 'Account Number', field: 'account_id', ...configureFakeLink('/accounts/', history.push), visible: false, formatter: cell => {return cell.getRow().getData().account_number}},
        {title: 'Account', field: 'account_id', ...configureFakeLink('/accounts/', history.push), formatter: cell => {return cell.getRow().getData().account_name}},
        {title: 'First Bill Date', field: 'bill_start_date', visible: false},
        {title: 'Last Bill Date', field: 'bill_end_date'},
        {title: 'Payment Types', field: 'payment_type_list', visible: false},
        {title: 'Balance Owing', field: 'balance_owing', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision:2}, topCalcFormatter: 'money', topCalcFormatterParams: {thousand: ',', symbol: '$'}, sorter:'number'},
        {title: 'Bill Cost', field: 'bill_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}, sorter:'number'},
        {title: 'Total Cost', field: 'total_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}, sorter:'number'},
        {title: 'Bill Count', field: 'bill_count', sorter: 'number', topCalc:'sum', visible: false},
        ...frontEndPermissions.invoices.edit ? [
            {title: 'Send Paper Invoices', field: 'send_paper_invoices', formatter: 'tickCross', visible: false}
        ] : [],
        {title: 'Finalized', field: 'finalized', hozAlign: 'center', formatter: 'tickCross', width: 100}
    ]

    const filters= [
        ...frontEndPermissions.invoices.edit ? [
            {
                name: 'Date Run',
                db_field: 'date_run',
                type: 'DateBetweenFilter',
            },
            {
                name: 'Finalized',
                db_field: 'finalized',
                type: 'BooleanFilter',
                default: false
            },
            {
                name: 'Send Paper Invoices',
                db_field: 'send_paper_invoices',
                type: 'BooleanFilter',
                default: true
            }
        ] : [],
        {
            name: 'Last Bill Date',
            db_field: 'bill_end_date',
            type: 'DateBetweenFilter',
        },
        {
            name: 'First Bill Date',
            db_field: 'bill_start_date',
            type: 'DateBetweenFilter'
        },
        {
            selections: lists.accounts,
            name: 'Account',
            db_field: 'account_id',
            type: 'SelectFilter',
            isMulti: true
        },
        {
            name: 'Balance Owing',
            db_field: 'balance_owing',
            type: 'NumberBetweenFilter',
            step: 0.01,
        },
        {
            name: 'Payment Type',
            db_field: 'payment_type_id',
            type: 'SelectFilter',
            isMulti: true,
            selections: lists.paymentTypes
        },
        {
            name: 'Charge Type',
            db_field: 'charge_type_id',
            type: 'SelectFilter',
            isMulti: true,
            selections: lists.paymentTypes
        }
    ]
    const withSelected = [
        ...frontEndPermissions.invoices.edit ? adminWithSelected : [],
        {
            icon: 'fas fa-save',
            label: 'Download',
            onClick: printInvoices,
            options: {
                download: true
            }
        },
        {
            icon: 'fas fa-print',
            label: 'Print',
            onClick: printInvoices,
            options: {
                download: false
            }
        }
    ]

    const defaultFilterQuery = () => {
        if(authenticatedUser.employee)
            return '?filter[finalized]=false'
        else
            return ''
    }

    return <Table
        baseRoute='/invoices'
        columns={columns}
        defaultFilterQuery={defaultFilterQuery()}
        filters={filters}
        groupByOptions={groupByOptions}
        indexName='invoice_id'
        initialSort={initialSort}
        pageTitle='Invoices'
        selectableRows='highlight'
        tableName='invoices'
        withSelected={withSelected}
    />
}
