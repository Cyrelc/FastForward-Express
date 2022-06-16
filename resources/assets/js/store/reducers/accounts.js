/**
 * Accounts table view reducer
 * 
 */
import * as actionTypes from '../actions'
import * as commonTableFunctions from '../partials/commonTableFunctions'

const initialState = {
    columns: [],
    accountsTable: [],
    queryString: localStorage.getItem('accountsQueryString'),
    sortedList: []
}

const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.SET_ACCOUNTS_QUERY_STRING:
            localStorage.setItem('accountsQueryString', action.payload)
            return {...state, queryString: action.payload}
        case actionTypes.SET_ACCOUNTS_SORTED_LIST:
            return {...state, sortedList: action.payload}
        case actionTypes.TOGGLE_ACCOUNTS_COLUMN_VISIBILITY:
            return {...state, columns: commonTableFunctions.toggleColumnVisibility(action)}
        case actionTypes.UPDATE_ACCOUNTS_TABLE:
            return {...state, accountsTable: action.payload}
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
