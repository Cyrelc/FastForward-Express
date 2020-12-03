import React from 'react'
import ReduxTable from '../partials/ReduxTable'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'

import { fetchInvoices } from '../../store/reducers/invoices'
import * as actionTypes from '../../store/actions'

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
        type: 'BooleanFilter',
    }
]

const initialSort = [{column:'bill_end_date', dir: 'desc'},{column:'account_number', dir:'asc'}]

function Invoices(props) {
    return (
        <ReduxTable
            columns={props.columns}
            fetchTableData={props.fetchTableData}
            filters={filters}
            groupBy={props.groupBy}
            groupByOptions={props.groupByOptions}
            indexName='invoice_id'
            initialSort={initialSort}
            pageTitle='Invoices'
            reduxQueryString={props.reduxQueryString}
            redirect={props.redirect}
            selectable='highlight'
            setReduxQueryString={props.setQueryString}
            setSortedList={props.setSortedList}
            tableData={props.invoiceTable}
            tableRef={props.tableRef}
            toggleColumnVisibility={props.toggleColumnVisibility}
            updateGroupByOptions={props.updateGroupByOptions}
            withSelected={props.withSelected}
        />
    )
}

const matchDispatchToProps = dispatch => {
    return {
        fetchTableData: () => dispatch(fetchInvoices),
        redirect: url => {dispatch(push(url))},
        setQueryString: queryString => dispatch({type: actionTypes.SET_INVOICES_QUERY_STRING, payload: queryString}),
        setSortedList: sortedList => dispatch({type: actionTypes.SET_INVOICES_SORTED_LIST, payload: sortedList}),
        toggleColumnVisibility: column => dispatch({type: actionTypes.TOGGLE_INVOICES_COLUMN_VISIBILITY, payload: column}),
        updateGroupByOptions: option => dispatch({type: actionTypes.UPDATE_INVOICES_GROUP_BY, payload: option})
    }
}

const mapStateToProps = store => {
    return {
        columns: store.invoices.columns,
        groupBy: store.invoices.groupBy,
        groupByOptions: store.invoices.groupByOptions,
        invoiceTable: store.invoices.invoiceTable,
        reduxQueryString: store.invoices.queryString,
        tableRef: store.invoices.tableRef,
        withSelected: store.invoices.withSelected
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Invoices)
