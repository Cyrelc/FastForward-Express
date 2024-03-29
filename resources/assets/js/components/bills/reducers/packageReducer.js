export const initialState = {
    packageIsMinimum: false,
    packageIsPallet: false,
    packages: [{count: 1, weight: '', length: '', width: '', height: '', totalWeight: '', totalVolume: ''}],
    proofOfDeliveryRequired: false,
    useImperial: false
}

export default function packageReducer(state, action) {
    const {type, payload} = action

    switch(type) {
        case 'CONFIGURE_PACKAGES': {
            return Object.assign({}, state, {...initialState, useImperial: payload.use_imperial})
        }
        case 'CONFIGURE_EXISTING':
            return Object.assign({}, state, {
                packageIsMinimum: payload.bill.is_min_weight_size,
                packageIsPallet: payload.bill.is_pallet,
                packages: payload.bill.packages ?? initialState.packages,
                proofOfDeliveryRequired: payload.bill.proof_of_delivery_required,
                useImperial: payload.bill.use_imperial
            })
        case 'DELETE_PACKAGE':
            return Object.assign({}, state, {
                packages: state.packages.filter(parcel => parcel.id != payload)
            })
        case 'TOGGLE_PACKAGE_IS_MINIMUM':
            return Object.assign({}, state, {
                packageIsMinimum: !state.packageIsMinimum
            })
        case 'TOGGLE_PACKAGE_IS_PALLET':
            return Object.assign({}, state, {
                packageIsPallet: !state.packageIsPallet
            })
        case 'TOGGLE_PROOF_OF_DELIVERY':
            return Object.assign({}, state, {
                proofOfDeliveryRequired: !state.proofOfDeliveryRequired
            })
        case 'TOGGLE_USE_IMPERIAL':
            return Object.assign({}, state, {
                useImperial: !state.useImperial
            })
        case 'UPDATE_PACKAGES':
            return Object.assign({}, state, {
                packages: payload
            })
        default:
            console.log(`ERROR - action of type ${type} was not found`)
            return state
    }
}
