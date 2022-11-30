import {DateTime} from 'luxon'
/**
 * Invoice table view reducer
 */
import * as actionTypes from '../actions'
import * as commonTableFunctions from '../partials/commonTableFunctions'

/**
 * Initial State
 */

const initialState = {
    billsTable: [],
    columns: [],
    customFieldName: 'Custom Field',
    queryString: localStorage.getItem('billsQueryString'),
    sortedList: [],
    tableLoading: true
}

/**
 * Reducer
 */
const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.SET_BILLS_CUSTOM_FIELD_NAME:
            return {...state, customFieldName: action.payload}
        case actionTypes.SET_BILLS_QUERY_STRING:
            localStorage.setItem('billsQueryString', action.payload)
            return {...state, queryString: action.payload}
        case actionTypes.SET_BILLS_SORTED_LIST:
            return {...state, sortedList: action.payload}
        case actionTypes.SET_BILLS_TABLE_LOADING:
            return {...state, tableLoading: action.payload}
        case actionTypes.TOGGLE_BILLS_COLUMN_VISIBILITY:
            return {...state, columns: commonTableFunctions.toggleColumnVisibility(action)}
        case actionTypes.UPDATE_BILLS_TABLE:
            return {...state, billsTable: action.payload}
    }
    return state
}

export async function fetchBills(dispatch, getState) {
    dispatch({type: actionTypes.SET_BILLS_TABLE_LOADING, payload: true})
    makeAjaxRequest(`/bills${getState().bills.queryString}`, 'GET', null, response => {
        const {bills, custom_field_name} = response
        if(custom_field_name != getState().bills.customFieldName)
            if(custom_field_name)
                dispatch({type: actionTypes.SET_BILLS_CUSTOM_FIELD_NAME, payload: custom_field_name})
            else
                dispatch({type: actionTypes.SET_BILLS_CUSTOM_FIELD_NAME, payload: 'Custom Field'})
        dispatch({type: actionTypes.UPDATE_BILLS_TABLE, payload: bills == undefined ? [] : bills})
        dispatch({type: actionTypes.SET_BILLS_TABLE_LOADING, payload: false})
    },
    () => dispatch({type: actionTypes.SET_BILLS_TABLE_LOADING, payload: false})
    )
}

export default reducer
