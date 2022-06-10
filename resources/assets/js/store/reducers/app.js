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
    authenticatedUserId: null,
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
    isImpersonating: false,
    parentAccounts: [],
    paymentTypes: []
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
                authenticatedUserId: action.payload.authenticatedUserId,
                drivers: action.payload.drivers,
                employees: action.payload.employees,
                frontEndPermissions: action.payload.frontEndPermissions,
                invoiceIntervals: action.payload.invoice_intervals,
                isImpersonating: action.payload.is_impersonating,
                parentAccounts: action.payload.parent_accounts,
                paymentTypes: action.payload.payment_types
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
