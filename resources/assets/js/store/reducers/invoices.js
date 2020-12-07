/**
 * Invoice table view reducer
 */
import { createRef } from 'react'
import * as actionTypes from '../actions'
import * as commonTableFunctions from '../partials/commonTableFunctions'

/**
 * Table functions
 * 
 */
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

/**
 * Table constants including definitions
 */
const columns = [
    {formatter: cell => cellContextMenuFormatter(cell), width:50, hozAlign:'center', clickMenu: cell => cellContextMenu(cell), headerSort: false, print: false},
    {formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign:'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
    {title: 'Invoice ID', field: 'invoice_id', formatter: cell => commonTableFunctions.fakeLinkFormatter(cell), formatterParams:{type:'fakeLink', urlPrefix:'/app/invoices/view/'}, sorter:'number'},
    {title: 'Account ID', field: 'account_id', formatter: cell => commonTableFunctions.fakeLinkFormatter(cell), formatterParams:{type:'fakeLink', urlPrefix:'/app/accounts/edit/', labelField:'account_number'}},
    {title: 'Account Number', field: 'account_number', formatter: cell => commonTableFunctions.fakeLinkFormatter(cell), formatterParams:{type:'fakeLink', urlPrefix:'/app/accounts/edit/N'}, visible: false},
    {title: 'Account', field: 'account_id', formatter: (cell, formatterParams) => commonTableFunctions.fakeLinkFormatter(cell, formatterParams), formatterParams:{type:'fakeLink', labelField:'account_name', urlPrefix:'/app/accounts/edit/'}},
    {title: 'Date Run', field: 'date_run', sorter:'date', visible: false},
    {title: 'Bill Start Date', field: 'bill_start_date', sorter:'date', visible: false},
    {title: 'Bill End Date', field: 'bill_end_date', sorter:'date'},
    {title: 'Balance Owing', field: 'balance_owing', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision:2}, sorter:'number'},
    {title: 'Bill Cost', field: 'bill_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:'sum', topCalcParams:{precision: 2}, sorter:'number'},
    {title: 'Total Cost', field: 'total_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision:2}, sorter:'number'},
    {title: 'Bill Count', field: 'bill_count', sorter: 'number', topCalc:'sum', visible: false},
    {title: 'Finalized', field: 'finalized', hozAlign: 'center', formatter: 'tickCross', width: 100}
]

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Account', value: 'account_number', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].account_name}},
    {label: 'Bill End Date', value: 'bill_end_date'}
]

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

/**
 * Initial State
 */

const initialState = {
    columns: columns,
    groupBy: groupByOptions[0],
    groupByOptions: groupByOptions,
    invoiceTable: [],
    queryString: '?filter[finalized]=false',
    sortedList: [],
    tableRef: createRef(),
    withSelected: withSelected
}

/**
 * Reducer
 * 
 */

const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.SET_INVOICES_QUERY_STRING:
            return {...state, queryString: action.payload}
        case actionTypes.SET_INVOICES_SORTED_LIST:
            console.log(action.payload)
            return {...state, sortedList: action.payload}
        case actionTypes.TOGGLE_INVOICES_COLUMN_VISIBILITY:
            return {...state, columns: commonTableFunctions.toggleColumnVisibility(state.columns, action)}
        case actionTypes.UPDATE_INVOICES_TABLE:
            return {...state, invoiceTable: action.payload}
        case actionTypes.UPDATE_INVOICES_GROUP_BY:
            return {...state, groupBy: commonTableFunctions.updateGroupBy(state.tableRef, state.groupByOptions, action)}
    }
    return state
}

export async function fetchInvoices(dispatch, getState) {
    console.log(getState().invoices.queryString)
    makeAjaxRequest('/invoices/buildTable' + getState().invoices.queryString, 'GET', null, response => {
        const invoices = JSON.parse(response)
        dispatch({type: actionTypes.UPDATE_INVOICES_TABLE, payload: invoices == undefined ? [] : invoices})
    })
}

export default reducer
