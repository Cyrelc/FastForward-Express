/**
 * App level reducer
 */
import * as actionTypes from '../actions'

/**
 * Initial State
 */
const initialState = {
    accounts: [],
    drivers: [],
    employees: [],
    invoiceIntervals: [],
    parentAccounts: [],
    paymentTypes: [],
    repeatIntervals: []
}

const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.APP_CONFIGURATION_LOADED:
            return {
                ...state,
                accounts: action.payload.accounts,
                drivers: action.payload.drivers,
                employees: action.payload.employees,
                invoiceIntervals: action.payload.invoice_intervals,
                parentAccounts: action.payload.parent_accounts,
                paymentTypes: action.payload.payment_types,
                repeatIntervals: action.payload.repeat_intervals
            }
    }
    return state
}

export async function fetchAppConfiguration(dispatch, getState) {
    makeAjaxRequest('/getAppConfiguration', 'GET', null, response => {
        const data = JSON.parse(response)
        dispatch({type: actionTypes.APP_CONFIGURATION_LOADED, payload: data})
    })
}

export default reducer
