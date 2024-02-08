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

const getHomePageRoute = payload => {
    if(payload.frontEndPermissions.appSettings.edit)
        return '/adminDashboard'
    else if(payload.authenticatedEmployee)
        return `/employees/${action.payload.authenticatedEmployee.employee_id}`
    else if(payload?.authenticatedAccountUsers?.length > 1)
        return `/accounts`
    else
        return `/accounts/${action.payload.authenticatedAccountUsers[0].account_id}`
}

const reducer = (state = initialState, action) => {
    switch(action.type) {
        case actionTypes.USER_CONFIGURATION_LOADED:
            const homePage = getHomePageRoute(action.payload)
            return {
                ...state,
                authenticatedAccountUsers: action.payload.authenticatedAccountUsers,
                authenticatedEmployee: action.payload.authenticatedEmployee,
                authenticatedUserContact: action.payload.contact,
                authenticatedUserId: action.payload.authenticatedUserId,
                frontEndPermissions: action.payload.frontEndPermissions,
                isImpersonating: action.payload.is_impersonating,
                homePage: homePage,
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
