/**
 * User reducer
 */
import * as actionTypes from '../actions'

/**
 * Initial State
 */

const initialState = {
    authenticatedAccountUsers: {},
    authenticatedEmployee: {},
    authenticatedUserContact: {},
    authenticatedUserId: null,
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
    isImpersonating: false,
    userSettings: {}
}

const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.USER_CONFIGURATION_LOADED:
            return {
                ...state,
                authenticatedAccountUsers: action.payload.authenticatedAccountUsers,
                authenticatedEmployee: action.payload.authenticatedEmployee,
                authenticatedUserContact: action.payload.contact,
                authenticatedUserId: action.payload.authenticatedUserId,
                frontEndPermissions: action.payload.frontEndPermissions,
                isImpersonating: action.payload.is_impersonating,
                userSettings: action.payload.user_settings
            }
    }
    return state
}

export async function fetchUserConfiguration(dispatch, getState) {
    makeAjaxRequest('/users/getConfiguration', 'GET', null, response => {
        const data = JSON.parse(response)
        dispatch({type: actionTypes.USER_CONFIGURATION_LOADED, payload: data})
    })
}

export default reducer
