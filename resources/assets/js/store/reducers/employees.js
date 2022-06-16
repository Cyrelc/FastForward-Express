/**
 * Employees table view reducer
 */
import * as actionTypes from '../actions'
import * as commonTableFunctions from '../partials/commonTableFunctions'

/**
 * Table constants including definitions
 */

/**
 * Initial State
 */
const initialState = {
    columns: [],
    employeesTable: [],
    queryString: localStorage.getItem('employeesQueryString'),
    sortedList: [],
}
/**
 * Reducer
 */
const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.SET_EMPLOYEES_QUERY_STRING:
            localStorage.setItem('employeesQueryString', action.payload)
            return {...state, queryString: action.payload}
        case actionTypes.SET_EMPLOYEES_SORTED_LIST:
            return {...state, sortedList: action.payload}
        case actionTypes.TOGGLE_EMPLOYEES_COLUMN_VISIBILITY:
            return {...state, columns: commonTableFunctions.toggleColumnVisibility(action)}
        case actionTypes.UPDATE_EMPLOYEES_TABLE:
            return {...state, employeesTable: action.payload}
    }
    return state
}

export async function fetchEmployees(dispatch, getState) {
    makeAjaxRequest('/employees/buildTable' + getState().employees.queryString, 'GET', null, response => {
        const employees = JSON.parse(response)
        dispatch({type: actionTypes.UPDATE_EMPLOYEES_TABLE, payload: employees == undefined ? [] : employees})
    })
}

export default reducer
