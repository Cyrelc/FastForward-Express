import {createRef} from 'react'

export const initialState = {
    packageIsMinimum: false,
    packageIsPallet: false,
    packages: [{count: 1, weight: '', length: '', width: '', height: '', totalWeight: '', totalVolume: ''}],
    tableRef: createRef(),
    useImperial: false
}

export default function packageReducer(state, action) {
    const {type, payload} = action

    switch(type) {
        case 'CONFIGURE_PACKAGES': {
            state.tableRef?.current.setData(initialState.packages)
            return Object.assign({}, state, {...initialState})
        }
        case 'CONFIGURE_EXISTING':
            state.tableRef?.current.setData(initialState.packages)
            return Object.assign({}, state, {
                packageIsMinimum: payload.bill.is_min_weight_size,
                packageIsPallet: payload.bill.is_pallet,
                packages: payload.bill.packages,
                useImperial: payload.bill.use_imperial
            })
        case 'DELETE_PACKAGE':
            return Object.assign({}, state, {
                packages: state.packages.filter(parcel => parcel.id != payload)
            })
        case 'SET_TABLE_REF':
            return Object.assign({}, state, {
                tableRef: payload
            })
        case 'TOGGLE_PACKAGE_IS_MINIMUM':
            return Object.assign({}, state, {
                packageIsMinimum: !state.packageIsMinimum
            })
        case 'TOGGLE_PACKAGE_IS_PALLET':
            return Object.assign({}, state, {
                packageIsPallet: !state.packageIsPallet
            })
        case 'TOGGLE_USE_IMPERIAL':
            return Object.assign({}, state, {
                useImperial: !state.useImperial
            })
        default:
            console.log(`ERROR - action of type ${type} was not found`)
            return state
    }
}
