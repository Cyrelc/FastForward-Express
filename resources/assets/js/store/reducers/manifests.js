/**
 * 
 * Manifest table view reducer
 */
import * as actionTypes from '../actions'
import * as commonTableFunctions from '../partials/commonTableFunctions'

/**
 * Initial State
 */
 const initialState = {
     columns: [],
     queryString: localStorage.getItem('manifestsQueryString'),
     sortedList: [],
     manifestTable: []
 }

 const reducer = (state = initialState, action) => {
     switch(action.type) {
        case actionTypes.SET_MANIFESTS_QUERY_STRING:
            localStorage.setItem('manifestsQueryString', action.payload)
            return {...state, queryString: action.payload}
        case actionTypes.SET_MANIFESTS_SORTED_LIST:
            return {...state, sortedList: action.payload}
        case actionTypes.TOGGLE_MANIFESTS_COLUMN_VISIBILITY:
            return {...state, columns: commonTableFunctions.toggleColumnVisibility(action)}
        case actionTypes.UPDATE_MANIFESTS_TABLE:
            return {...state, manifestTable: action.payload}
     }
     return state
 }

export async function fetchManifests(dispatch, getState) {
    makeAjaxRequest(`/manifests${getState().manifests.queryString}`, 'GET', null, response => {
        const manifests = JSON.parse(response)
        dispatch({type: actionTypes.UPDATE_MANIFESTS_TABLE, payload: manifests == undefined ? [] : manifests})
    })
}

export default reducer
