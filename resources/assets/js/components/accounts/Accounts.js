import React from 'react'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'
import ReduxTable from '../partials/ReduxTable'

import { fetchAccounts } from '../../store/reducers/accounts'
import * as actionTypes from '../../store/actions'

const filters = [
    {
        name: 'Account',
        value: 'account_id',
        type: 'SelectFilter',
        isMulti: true,
        fetchUrl: '/getList/accounts'
    },
    {
        name: 'Active',
        value: 'active',
        type: 'BooleanFilter'
    },
    {
        name: 'Has Parent',
        value: 'has_parent',
        type: 'BooleanFilter'
    },
    {
        fetchUrl: '/getList/selections/invoice_interval',
        isMulti: true,
        name: 'Invoice Interval',
        type: 'SelectFilter',
        value: 'invoice_interval'
    },
    {
        fetchUrl: '/getList/parent_accounts',
        isMulti: true,
        name: 'Parent Account',
        type: 'SelectFilter', 
        value: 'parent_id'
    }
]

const initialSort = [{column: 'account_id', dir: 'asc'}]

function Accounts(props) {
    return (
        <ReduxTable
            columns={props.columns}
            fetchTableData={props.fetchTableData}
            filters={filters}
            groupBy={props.groupBy}
            groupByOptions={props.groupByOptions}
            indexName='account_id'
            initialSort={initialSort}
            pageTitle='Accounts'
            reduxQueryString={props.reduxQueryString}
            redirect={props.redirect}
            selectable={false}
            setReduxQueryString={props.setQueryString}
            setSortedList={props.setSortedList}
            tableData={props.accountsTable}
            tableRef={props.tableRef}
            toggleColumnVisibility={props.toggleColumnVisibility}
            updateGroupByOptions={props.updateGroupByOptions}
        />
    )
}

const matchDispatchToProps = dispatch => {
    return {
        fetchTableData: () => dispatch(fetchAccounts),
        redirect: url => {dispatch(push(url))},
        setQueryString: queryString => dispatch({type: actionTypes.SET_ACCOUNTS_QUERY_STRING, payload: queryString}),
        setSortedList: sortedList => dispatch({type: actionTypes.SET_ACCOUNTS_SORTED_LIST, payload: sortedList}),
        toggleColumnVisibility: column => dispatch({type: actionTypes.TOGGLE_ACCOUNTS_COLUMN_VISIBILITY, payload: column}),
        updateGroupByOptions: option => dispatch({type: actionTypes.UPDATE_ACCOUNTS_GROUP_BY, payload: option})
    }
}

const mapStateToProps = store => {
    return {
        columns: store.accounts.columns,
        groupBy: store.accounts.groupBy,
        groupByOptions: store.accounts.groupByOptions,
        accountsTable: store.accounts.accountsTable,
        reduxQueryString: store.accounts.queryString,
        tableRef: store.invoices.tableRef
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Accounts)
