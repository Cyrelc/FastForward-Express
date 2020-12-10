/**
 * Accounts table view reducer
 * 
 */

import { createRef } from 'react'
import * as actionTypes from '../actions'
import * as commonTableFunctions from '../partials/commonTableFunctions'

/**
 * Table functions (must be located here for column persistence)
 */

function toggleAccountActive(cell) {
    const active = cell.getRow().getData().active
    if(confirm('Are you sure you wish to ' + (active ? 'DEACTIVATE' : 'ACTIVATE') + ' account ' + cell.getRow().getData().name + '?')) {
        const url = '/accounts/toggleActive/' + cell.getRow().getData().account_id
        makeFetchRequest(url, data => {
            location.reload()
        })
    }
}

/**
 * Table constants including definitions
 */

const columns = [
    {formatter: (cell) => {
        const active = cell.getRow().getData().active;
        if(active)
            return "<button class='btn btn-sm btn-danger' title='Deactivate'><i class='far fa-times-circle'></i></button>"
        else
            return "<button class='btn btn-sm btn-success'  title='Activate'><i class='far fa-check-circle'></i></button>"
    }, width: 50, hozAlign: 'center', cellClick:(e, cell) => toggleAccountActive(cell), headerSort: false, print: false},
    {title: 'Account ID', field: 'account_id', formatter: (cell, formatterParams) => commonTableFunctions.fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', urlPrefix:'/app/accounts/edit/'}, sorter: 'number'},
    {title: 'Account Number', field: 'account_number'},
    {title: 'Parent Account', field: 'parent_id', formatter: (cell, formatterParams) => commonTableFunctions.fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', labelField: 'parent_name', urlPrefix:'/app/accounts/edit/'}},
    {title: 'Account Name', field: 'account_id', formatter: (cell, formatterParams) => commonTableFunctions.fakeLinkFormatter(cell, formatterParams), formatterParams:{type: 'fakeLink', labelField: 'name', urlPrefix:'/app/accounts/edit/'}, sorter: 'number'},
    {title: 'Invoice Interval', field: 'invoice_interval'},
    {title: 'Primary Contact', field: 'primary_contact_name'},
    {title: 'Primary Contact Phone', field: 'primary_contact_phone', headerSort: false},
    {title: 'Shipping Address Name', field: 'shipping_address_name', visible: false},
    {title: 'Shipping Address', field: 'shipping_address', visible: false},
    {title: 'Billing Address Name', field: 'billing_address_name'},
    {title: 'Billing Address', field: 'billing_address', visible: false}
]

const groupByOptions = [
    {label: 'None', value: null},
    {label: 'Parent Account', value: 'parent_id', groupHeader: (value, count, data, group) => {return value + ' - ' + data[0].parent_name}},
]

const initialState = {
    columns: columns,
    groupBy: groupByOptions[0],
    groupByOptions: groupByOptions,
    accountsTable: [],
    queryString: '?filter[active]=true',
    sortedList: [],
    tableRef: createRef()
}

const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.SET_ACCOUNTS_QUERY_STRING:
            return {...state, queryString: action.payload}
        case actionTypes.SET_ACCOUNTS_SORTED_LIST:
            return {...state, sortedList: action.payload}
        case actionTypes.TOGGLE_ACCOUNTS_COLUMN_VISIBILITY:
            return {...state, columns: commonTableFunctions.toggleColumnVisibility(state.columns, action)}
        case actionTypes.UPDATE_ACCOUNTS_TABLE:
            return {...state, accountsTable: action.payload}
        case actionTypes.UPDATE_ACCOUNTS_GROUP_BY:
            return {...state, groupBy: commonTableFunctions.updateGroupBy(state.tableRef, state.groupByOptions, action)}
    }
    return state
}

export async function fetchAccounts(dispatch, getState) {
    makeAjaxRequest('/accounts/buildTable' + getState().accounts.queryString, 'GET', null, response => {
        const accounts = JSON.parse(response)
        dispatch({type: actionTypes.UPDATE_ACCOUNTS_TABLE, payload: accounts === undefined ? [] : accounts})
    })
}

export default reducer
