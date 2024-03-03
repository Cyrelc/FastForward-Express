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
    if(payload.front_end_permissions.appSettings.edit)
        return '/adminDashboard'
    else if(payload.employee)
        return `/employees/${payload.employee.employee_id}`
    else if(payload?.account_users?.length > 1)
        return `/accounts`
    else
        return `/accounts/${payload.account_users[0].account_id}`
}

const reducer = (state = initialState, action) => {
    if(action.payload)
        console.log(action.payload.front_end_permissions)
    switch(action.type) {
        case actionTypes.USER_CONFIGURATION_LOADED:
            const homePage = getHomePageRoute(action.payload)
            return {
                ...state,
                authenticatedAccountUsers: action.payload.account_users,
                authenticatedEmployee: action.payload.employee,
                authenticatedUserContact: action.payload.contact,
                authenticatedUserId: action.payload.user_id,
                frontEndPermissions: action.payload.front_end_permissions,
                isImpersonating: action.payload.is_impersonating,
                homePage: homePage,
                userSettings: action.payload.user_settings
            }
    }
    return state
}

export async function fetchUserConfiguration(dispatch, getState) {
    makeAjaxRequest('/users/getConfiguration', 'GET', null, response => {
        const {data} = response
        console.log(data)
        dispatch({type: actionTypes.USER_CONFIGURATION_LOADED, payload: data})
    })
}

export default reducer
