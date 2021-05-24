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
    authenticatedAccountUsers: {},
    authenticatedEmployee: {},
    authenticatedUserContact: {},
    employees: [],
    frontEndPermissions: {
        accounts: {},
        administration: {},
        appSettings: {},
        bills: {},
        chargebacks: {},
        drivers: {},
        employees: {},
        invoices: {},
        manifests: {},
        ratesheets: {}
    },
    invoiceIntervals: [],
    parentAccounts: []
}

const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.APP_CONFIGURATION_LOADED:
            return {
                ...state,
                accounts: action.payload.accounts,
                authenticatedAccountUsers: action.payload.authenticatedAccountUsers,
                authenticatedEmployee: action.payload.authenticatedEmployee,
                authenticatedUserContact: action.payload.contact,
                drivers: action.payload.drivers,
                employees: action.payload.employees,
                frontEndPermissions: action.payload.frontEndPermissions,
                invoiceIntervals: action.payload.invoice_intervals,
                parentAccounts: action.payload.parent_accounts
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
