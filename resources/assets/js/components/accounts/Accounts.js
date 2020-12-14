import React from 'react'
import { connect } from 'react-redux'
import { push } from 'connected-react-router'
import ReduxTable from '../partials/ReduxTable'

import { fetchAccounts } from '../../store/reducers/accounts'
import * as actionTypes from '../../store/actions'

/**
 * Table functions (must be located here for column persistence)
 */

function toggleAccountActive(cell) {
    const active = cell.getRow().getData().active
    if(confirm('Are you sure you wish to ' + (active ? 'DEACTIVATE' : 'ACTIVATE') + ' account ' + cell.getRow().getData().name + '?')) {
        makeAjaxRequest('/accounts/toggleActive/' + cell.getRow().getData().account_id, 'GET', null, response => {
            fetchAccounts()
        })
    }
}

/**
 * Table constants including definitions
 */

const columns = [
    {formatter: (cell) => {
        if(cell.getValue())
            return "<button class='btn btn-sm btn-danger' title='Deactivate'><i class='far fa-times-circle'></i></button>"
        else
            return "<button class='btn btn-sm btn-success'  title='Activate'><i class='far fa-check-circle'></i></button>"
    }, width: 50, hozAlign: 'center', cellClick:(e, cell) => toggleAccountActive(cell), headerSort: false, print: false},
    {title: 'Account ID', field: 'account_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', urlPrefix:'/app/accounts/edit/'}, sorter: 'number'},
    {title: 'Account Number', field: 'account_number'},
    {title: 'Parent Account', field: 'parent_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', labelField: 'parent_name', urlPrefix:'/app/accounts/edit/'}},
    {title: 'Account Name', field: 'account_id', formatter: (cell, formatterParams) => fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', labelField: 'name', urlPrefix:'/app/accounts/edit/'}, sorter: 'number'},
    {title: 'Invoice Interval', field: 'invoice_interval'},
    {title: 'Primary Contact', field: 'primary_contact_name'},
    {title: 'Primary Contact Phone', field: 'primary_contact_phone', headerSort: false},
    {title: 'Shipping Address Name', field: 'shipping_address_name', visible: false},
    {title: 'Shipping Address', field: 'shipping_address', visible: false},
    {title: 'Billing Address Name', field: 'billing_address_name'},
    {title: 'Billing Address', field: 'billing_address', visible: false}
]

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

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Parent Account', value: 'parent_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].parent_name}},
]

const initialSort = [{column: 'account_id', dir: 'asc'}]

function Accounts(props) {
    return (
        <ReduxTable
            columns={props.columns.length ? props.columns : columns}
            fetchTableData={props.fetchTableData}
            filters={filters}
            groupByOptions={groupByOptions}
            indexName='account_id'
            initialSort={initialSort}
            pageTitle='Accounts'
            reduxQueryString={props.reduxQueryString}
            redirect={props.redirect}
            selectable={false}
            setReduxQueryString={props.setQueryString}
            setSortedList={props.setSortedList}
            tableData={props.accountsTable}
            toggleColumnVisibility={props.toggleColumnVisibility}
        />
    )
}

const matchDispatchToProps = dispatch => {
    return {
        fetchTableData: () => dispatch(fetchAccounts),
        redirect: url => dispatch(push(url)),
        setQueryString: queryString => dispatch({type: actionTypes.SET_ACCOUNTS_QUERY_STRING, payload: queryString}),
        setSortedList: sortedList => dispatch({type: actionTypes.SET_ACCOUNTS_SORTED_LIST, payload: sortedList}),
        toggleColumnVisibility: (columns, toggleColumn) => dispatch({type: actionTypes.TOGGLE_ACCOUNTS_COLUMN_VISIBILITY, payload: {columns: columns, toggleColumn: toggleColumn}}),
    }
}

const mapStateToProps = store => {
    return {
        columns: store.accounts.columns,
        accountsTable: store.accounts.accountsTable,
        reduxQueryString: store.accounts.queryString,
    }
}

export default connect(mapStateToProps, matchDispatchToProps)(Accounts)
