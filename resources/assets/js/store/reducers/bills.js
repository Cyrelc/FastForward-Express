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
    queryString: '?filter[percentage_complete]=,100',
    sortedList: [],
    billsTable: []
}

/**
 * Reducer
 */
const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.SET_BILLS_QUERY_STRING:
            return {...state, queryString: action.payload}
        case actionTypes.SET_BILLS_SORTED_LIST:
            return {...state, sortedList: action.payload}
        case actionTypes.TOGGLE_BILLS_COLUMN_VISIBILITY:
            return {...state, columns: commonTableFunctions.toggleColumnVisibility(action)}
        case actionTypes.UPDATE_BILLS_TABLE:
            return {...state, billsTable: action.payload}
    }
    return state
}

export async function fetchBills(dispatch, getState) {
    makeAjaxRequest('/bills/buildTable' + getState().bills.queryString, 'GET', null, response => {
        const bills = JSON.parse(response)
        dispatch({type: actionTypes.UPDATE_BILLS_TABLE, payload: bills == undefined ? [] : bills})
    })
}

export default reducer
