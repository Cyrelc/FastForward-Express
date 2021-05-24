import React, { Component } from 'react'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'

import ReduxTable from '../partials/ReduxTable'
import { fetchInvoices } from '../../store/reducers/invoices'
import * as actionTypes from '../../store/actions'

/**
 * Table functions
 *
 */
function cellContextMenu(cell, canEdit = false) {
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
    {formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign:'center', headerHozAlign: 'center', headerSort: false, print: false, width: 50},
    {title: 'Invoice ID', field: 'invoice_id', formatter: fakeLinkFormatter, formatterParams:{type:'fakeLink', urlPrefix:'/app/invoices/'}, sorter:'number'},
    // {title: 'Account ID', field: 'account_id', formatter: fakeLinkFormatter, formatterParams:{type:'fakeLink', urlPrefix:'/app/accounts/'}},
    {title: 'Account Number', field: 'account_number', formatter: fakeLinkFormatter, formatterParams:{type:'fakeLink', urlPrefix:'/app/accounts/N'}, visible: false},
    {title: 'Account', field: 'account_id', formatter: fakeLinkFormatter, formatterParams:{type:'fakeLink', labelField:'account_name', urlPrefix:'/app/accounts/'}},
    {title: 'First Bill Date', field: 'bill_start_date', visible: false},
    {title: 'Last Bill Date', field: 'bill_end_date'},
    {title: 'Balance Owing', field: 'balance_owing', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision:2}, topCalcFormatter: 'money', topCalcFormatterParams: {thousand: ',', symbol: '$'}, sorter:'number'},
    {title: 'Bill Cost', field: 'bill_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:'sum', topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}, sorter:'number'},
    {title: 'Total Cost', field: 'total_cost', formatter: 'money', formatterParams:{thousand: ',', symbol: '$'}, topCalc:"sum", topCalcParams:{precision: 2}, topCalcFormatter: 'money', topCalcFormatterParams:{thousand: ',', symbol: '$'}, sorter:'number'},
    {title: 'Bill Count', field: 'bill_count', sorter: 'number', topCalc:'sum', visible: false},
    {title: 'Finalized', field: 'finalized', hozAlign: 'center', formatter: 'tickCross', width: 100}
]

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Account', value: 'account_number', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].account_name}},
    {label: 'Last Bill Date', value: 'bill_end_date'}
]

const initialSort = [{column:'bill_end_date', dir: 'desc'}, {column:'account_number', dir:'asc'}]

const adminWithSelected = [
    {
        label: 'Finalize',
        onClick: finalizeInvoices
    },
    {
        label: 'Undo Finalize',
        onClick: undoFinalizeInvoices
    }
]
const withSelected = [
    {
        label: 'Print',
        onClick: printInvoices
    }
]

class Invoices extends Component {
    constructor() {
        super()
        this.state = {
            columns: [],
            filters: [],
            withSelected: []
        }
    }

    componentDidMount() {
        const actionColumn = this.props.frontEndPermissions.invoices.edit ? [
            {formatter: cell => cellContextMenuFormatter(cell), width:50, hozAlign:'center', clickMenu: cell => cellContextMenu(cell), headerSort: false, print: false}
        ] : [
            {formatter: cell => {return "<button class='btn btn-sm btn-success' title='Print'><i class='fas fa-print'></i></button>"}, width: 50, hozAlign:'center', cellClick:(e, cell) => printInvoices([cell.getRow()])}
        ]
        const adminColumns = this.props.frontEndPermissions.invoices.edit ? [
            {title: 'Date Run', field: 'date_run', visible: false},
        ] : []

        const adminFilters = this.props.frontEndPermissions.invoices.edit ? [
            {
                name: 'Date Run',
                value: 'date_run',
                type: 'DateBetweenFilter',
            },
            {
                name: 'Finalized',
                value: 'finalized',
                type: 'BooleanFilter',
            }
        ] : []
        const basicFilters = [
            {
                name: 'Last Bill Date',
                value: 'bill_end_date',
                type: 'DateBetweenFilter',
            },
            {
                name: 'First Bill Date',
                value: 'bill_start_date',
                type: 'DateBetweenFilter'
            },
            {
                selections: this.props.accounts,
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
        ]

        this.setState({
            columns: Array.prototype.concat(actionColumn, columns, adminColumns),
            filters: Array.prototype.concat(adminFilters, basicFilters),
            withSelected: Array.prototype.concat(this.props.frontEndPermissions.invoices.edit ? adminWithSelected : [], withSelected)
        })
    }

    render() {
        return <ReduxTable
            columns={this.props.columns.length ? this.props.columns : this.state.columns}
            fetchTableData={this.props.fetchTableData}
            filters={this.state.filters}
            groupByOptions={groupByOptions}
            indexName='invoice_id'
            initialSort={initialSort}
            pageTitle='Invoices'
            reduxQueryString={this.props.reduxQueryString}
            redirect={this.props.redirect}
            selectable='highlight'
            setReduxQueryString={this.props.setQueryString}
            setSortedList={this.props.setSortedList}
            tableData={this.props.invoiceTable}
            toggleColumnVisibility={this.props.toggleColumnVisibility}
            withSelected={withSelected}
        />
    }
}

const matchDispatchToProps = dispatch => {
    return {
        fetchTableData: () => dispatch(fetchInvoices),
        redirect: url => dispatch(push(url)),
        setQueryString: queryString => dispatch({type: actionTypes.SET_INVOICES_QUERY_STRING, payload: queryString}),
        setSortedList: sortedList => dispatch({type: actionTypes.SET_INVOICES_SORTED_LIST, payload: sortedList}),
        toggleColumnVisibility: (columns, toggleColumn) => dispatch({type: actionTypes.TOGGLE_INVOICES_COLUMN_VISIBILITY, payload: {columns: columns, toggleColumn: toggleColumn}}),
    }
}

const mapStateToProps = store => {
    return {
        accounts: store.app.accounts,
        columns: store.invoices.columns,
        frontEndPermissions: store.app.frontEndPermissions,
        invoiceTable: store.invoices.invoiceTable,
        reduxQueryString: store.invoices.queryString,
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Invoices)
