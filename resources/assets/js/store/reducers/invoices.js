/**
 * Invoice table view reducer
 */
import * as actionTypes from '../actions'
import * as commonTableFunctions from '../partials/commonTableFunctions'

/**
 * Initial State
 */

const initialState = {
    columns: [],
    queryString: '?filter[finalized]=false',
    sortedList: [],
    invoiceTable: []
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
            return {...state, sortedList: action.payload}
        case actionTypes.TOGGLE_INVOICES_COLUMN_VISIBILITY:
            return {...state, columns: commonTableFunctions.toggleColumnVisibility(action)}
        case actionTypes.UPDATE_INVOICES_TABLE:
            return {...state, invoiceTable: action.payload}
    }
    return state
}

export async function fetchInvoices(dispatch, getState) {
    makeAjaxRequest('/invoices/buildTable' + getState().invoices.queryString, 'GET', null, response => {
        const invoices = JSON.parse(response)
        dispatch({type: actionTypes.UPDATE_INVOICES_TABLE, payload: invoices == undefined ? [] : invoices})
    })
}

export default reducer
