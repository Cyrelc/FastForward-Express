/**
 * App level reducer
 */
const initialState = {
    accounts: [],
    employees: []
}

const reducer = (state = initialState, action) => {
    switch(action.type) {
        case 'ACCOUNT_SELECTION_LIST_LOADED':
            return {...state, accounts: action.payload}
        case 'EMPLOYEE_SELECTION_LIST_LOADED':
            return {...state, employees: action.payload}
    }
    return state
}

export async function fetchAccountsSelectionList(dispatch, getState) {
    makeAjaxRequest('/getList/accounts', 'GET', null, response => {
        const accounts = JSON.parse(response)
        dispatch({type: 'ACCOUNT_SELECTION_LIST_LOADED', payload: accounts})
    })
}

export async function fetchEmployeesSelectionList(dispatch, getState) {
    makeAjaxRequest('/getList/employees', 'GET', null, response => {
        const employees = JSON.parse(response)
        dispatch({type: 'EMPLOYEE_SELECTION_LIST_LOADED', payload: employees})
    })
}

export default reducer
