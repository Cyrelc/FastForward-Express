import {createRef} from 'react'

const basicCharge = (chargeType) => {
    return {
        chargeType: chargeType,
        charge_type_id: chargeType.payment_type_id,
        charge_reference_value: '',
        lineItems: [],
        tableRef: createRef(null),
        testCounter: 0
    }
}

export const initialState = {
    activeRatesheet: '',
    charges: [],
    chargeAccount: '',
    chargeReferenceValue: '',
    chargeEmployee: '',
    chargeType: '',
    chargeTypes: [],
    hasInterliner: false,
    interliner: '',
    interlinerActualCost: '',
    interlinerReferenceValue: '',
    interliners: [],
    invoiceIds: [],
    isDeliveryManifested: false,
    isInvoiced: false,
    isPickupManifested: false,
    manifestIds: [],
    ratesheets: []
}

export default function chargeReducer(state, action) {
    const {type, payload} = action
    switch(type) {
        case 'ADD_CHARGE_TABLE':
            const {chargeAccount, chargeEmployee, chargeType} = state
            if(!chargeType) {
                toastr.clear()
                toastr.warning('Charge type selector may not be empty')
                console.log('chargeType may not be empty. Aborting')
                return
            }

            let newCharge = basicCharge(chargeType)
            switch(chargeType.name) {
                case 'Account':
                    if(!chargeAccount) {
                        toastr.clear()
                        toastr.warning('Charge account can not be empty')
                        console.log('chargeAccount may not be empty. Aborting')
                        return
                    }
                    newCharge = {
                        ...newCharge,
                        charge_account_id: chargeAccount.account_id,
                        name: chargeAccount.account_number + ' - ' + chargeAccount.name,
                        charge_reference_value_required: chargeAccount.is_custom_field_required ? true : false,
                        charge_reference_value_label: chargeAccount.custom_field ? chargeAccount.custom_field : null,
                    }
                    break;
                case 'Employee':
                    if(!chargeEmployee) {
                        toastr.clear()
                        toastr.warning('Charge Employee may not be empty')
                        console.log('chargeEmployee may not be empty. Aborting')
                        return
                    }
                    newCharge = {
                        ...newCharge,
                        charge_employee_id: chargeEmployee.value,
                        name: chargeEmployee.label,
                        charge_reference_value_required: false,
                        charge_reference_value_label: null,
                    }
                    break;
                default:
                    newCharge =  {
                        ...newCharge,
                        name: chargeType.name,
                        charge_reference_value_required: chargeType.required_field ? true : false,
                        charge_reference_value_label: chargeType.required_field ? chargeType.required_field : null,
                    }
                }
            return Object.assign({}, state, {
                charges: state.charges.concat([newCharge])
            })
        case 'CHECK_FOR_INTERLINER': {
            let hasInterliner = false
            state.charges.map(charge => {
                const data = charge.tableRef.current.table.getData()
                data.forEach(row => {
                    if(row.name === 'Interliner' && !row.toBeDeleted)
                        hasInterliner = true
                })
            })
            return Object.assign({}, state, {
                hasInterliner
            })
        }
        case 'CHECK_REFERENCE_VALUES': {
            const {account, value, prevValue} = payload
            return Object.assign({}, state, {
                charges: state.charges?.length ? state.charges.map(charge => {
                    if(charge.chargeType.name === 'Account' && charge.charge_account_id === account.account_id && charge.charge_reference_value === prevValue)
                        return {...charge, charge_reference_value: value}
                    return charge
                }) : state.charges,
                chargeReferenceValue: (state.chargeAccount?.account_id == account.account_id && state.chargeReferenceValue == prevValue) ? value : state.chargeReferenceValue
            })
        }
        case 'CONFIGURE_CHARGES':
            return Object.assign({}, state, {
                ...initialState,
                activeRatesheet: payload.activeRatesheet,
                chargeAccount: payload.accounts.length === 1 ? payload.accounts[0] : '',
                chargeType: payload.chargeTypes.length === 1 ? payload.chargeTypes[0] : '',
                chargeTypes: payload.chargeTypes,
                interliners: payload.interliners,
                ratesheets: payload.ratesheets
            })
        case 'CONFIGURE_EXISTING': {
            const {accounts, bill, charges, charge_types, permissions} = payload
            let newState = {charges: [], invoiceIds: [], manifestIds: []}
            charges?.forEach(charge => {
                newState.charges.push({...charge, chargeType: state.chargeTypes.find(chargeType => chargeType.payment_type_id === charge.charge_type_id), tableRef: createRef()})
                charge.lineItems?.forEach(lineItem => {
                    const {invoice_id} = lineItem
                    if(invoice_id && !newState.invoiceIds.includes(invoice_id)) {
                        newState.invoiceIds.push(invoice_id)
                        newState.isInvoiced = true
                    }
                    if(lineItem.name === 'Interliner')
                        newState.hasInterliner = true
                })
            })
            if(permissions.viewBasic && !permissions.viewBilling) {
                newState.chargeType = charge_types.find(chargeType => chargeType.name === 'Account')
                const chargeAccountId = charges.find(charge => charge.charge_account_id != '').charge_account_id
                newState.chargeAccount = accounts.find(account => account.account_id === chargeAccountId)
            }
            if(permissions.viewBilling) {
                charges.lineItems?.forEach(lineItem => {
                    const {pickup_manifest_id, delivery_manifest_id} = lineItem
                    if(pickup_manifest_id && !newState.manifestIds.includes(pickup_manifest_id)) {
                        newState.manifestIds.push(pickup_manifest_id)
                        newState.isPickupManifested = true
                    }
                    if(delivery_manifest_id && !newState.manifestIds.includes(delivery_manifest_id)) {
                        newState.manifestIds.push(delivery_manifest_id)
                        newState.isDeliveryManifested = true
                    }
                })
                newState.interliner = bill.interliner_id ? state.interliners.find(interliner => interliner.value === bill.interliner_id) : ''
                newState.interlinerActualCost = bill.interliner_cost
                newState.interlinerReferenceValue = bill.interliner_reference_value
            }
            return Object.assign({}, state, newState)
        }
        case 'DELETE_CHARGE_TABLE': {
            // If the table has no ID (has not been stored) we can delete it straight out, otherwise mark it as to be deleted
            // TODO - Rules?!?!!? Don't just delete things that we aren't allowed, bad!
            let charges = state.charges.map((charge, index) => {
                if(index === payload)
                    return {...charge, toBeDeleted: true}
                return charge
            })
            charges = charges.filter(charge => charge.toBeDeleted == true ? !!charge.charge_id : true)
            return Object.assign({}, state, {
                charges
            })
        }
        case 'DELETE_LINE_ITEM': {
            return Object.assign({}, state, {
                charge: state.charges.map((charge, index) => {
                    if(index === payload.index)
                        return charge
                    return charge
                })
            })
        }
        case 'SET_ACTIVE_RATESHEET':
            return Object.assign({}, state, {
                activeRatesheet: payload
            })
        case 'SET_CHARGE_ACCOUNT':
            return Object.assign({}, state, {
                chargeAccount: payload,
                activeRatesheet: payload.ratesheet_id ? state.ratesheets.find(ratesheet => ratesheet.ratesheet_id === payload.ratesheet_id) : state.activeRatesheet
            })
        case 'SET_CHARGE_EMPLOYEE':
            return Object.assign({}, state, {
                chargeEmployee: payload
            })
        case 'SET_CHARGE_REFERENCE_VALUE':
            if(payload.index != undefined)
                 return Object.assign({}, state, {
                    charges: state.charges.map((charge, index) => {
                        if(index === payload.index)
                            return {...charge, charge_reference_value: payload.value}
                        return charge
                    })
                })
            else
                return Object.assign({}, state, {
                    chargeReferenceValue: payload
                })
        case 'SET_CHARGE_TYPE':
            return Object.assign({}, state, {
                chargeType: payload
            })
        case 'SET_INTERLINER':
            return Object.assign({}, state, {interliner: payload})
        case 'SET_INTERLINER_ACTUAL_COST':
            return Object.assign({}, state, {interlinerActualCost: payload})
        case 'SET_INTERLINER_REFERENCE_VALUE':
            return Object.assign({}, state, {interlinerReferenceValue: payload})
        case 'CHECK_INVOICES_AND_MANIFESTS': {
            let isDeliveryManifested = false
            let isInvoiced = false
            let isPickupManifested = false
            state.charges.forEach(charge => {
                charge.lineItems.forEach(lineItem => {
                    if(lineItem.invoice_id)
                        isInvoiced = true
                    if(lineItem.pickup_manifest_id)
                        isPickupManifested = true
                    if(lineItem.delivery_manifest_id)
                        isDeliveryManifested = true
                })
            })
            return Object.assign({}, state, {isDeliveryManifested, isInvoiced, isPickupManifested})
        }
        case 'UPDATE_LINE_ITEMS': {
            const lineItems = payload.data.filter(lineItem => lineItem.line_item_id ? true : lineItem.deleted != true)
            const charges = state.charges.map((charge, index) => {
                if(index === payload.index)
                    return {...charge, lineItems: lineItems}
                return charge
            })
            const hasInterliner = charges.some(charge => charge.lineItems.some(lineItem => lineItem.deleted != true && lineItem.name === 'Interliner'))
            return Object.assign({}, state, {
                charges,
                hasInterliner
            })
        }
        default:
            console.log(`ERROR - action of type ${action.type} was not found`)
            return state
    }
}
