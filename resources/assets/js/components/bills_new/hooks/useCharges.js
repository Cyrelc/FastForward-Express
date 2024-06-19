import React, {useCallback, useEffect, useState} from 'react'
import {toast} from 'react-toastify'

function canChargeTableBeDeleted(charge) {
    // TODO - have to figure out how to get readOnly status
    // if(!charge || !!props.readOnly)
        return false
    return !charge.lineItems.some(lineItem => (lineItem.invoice_id || lineItem.pickup_manifest_id || lineItem.delivery_manifest_id) ? true : false)
}

export default function useCharges() {
    const [activeRatesheet, setActiveRatesheet] = useState({})
    const [chargeAccount, setChargeAccount] = useState({})
    const [charges, setCharges] = useState([])
    const [chargeReferenceValue, setChargeReferenceValue] = useState('')
    const [chargeTypes, setChargeTypes] = useState([])
    const [hasInterliner, setHasInterliner] = useState(false)
    const [interliner, setInterliner] = useState({})
    const [interlinerActualCost, setInterlinerActualCost] = useState('')
    const [interlinerReferenceValue, setInterlinerReferenceValue] = useState('')
    const [interliners, setInterliners] = useState([])
    const [invoiceIds, setInvoiceIds] = useState([])
    const [isDeliveryManifested, setIsDeliveryManifested] = useState(false)
    const [isInvoiced, setIsInvoiced] = useState(false)
    const [isPickupManifested, setIsPickupManifested] = useState(false)
    const [manifestIds, setManifestIds] = useState([])
    const [ratesheets, setRatesheets] = useState([])

    useEffect(() => {
        let updatedInvoiceIds = []
        let updatedIsPickupManifested = false
        let updatedIsDeliveryManifested = false
        let updatedManifestIds = []
        charges.forEach(charge => {
            charge.line_items.forEach(lineItem => {
                if(lineItem.invoice_id && !updatedInvoiceIds.contains(lineItem.invoice_id))
                    updatedInvoiceIds.push(lineItem.invoice_id)
                if(lineItem.pickup_manifest_id) {
                    updatedIsPickupManifested = true
                    if(!manifestIds.contains(lineItem.pickup_manifest_id))
                        updatedManifestIds.push(lineItem.pickup_manifest_id)
                }
                if(lineItem.delivery_manifest_id) {
                    updatedIsDeliveryManifested = true
                    if(!manifestIds.contains(lineItem.delivery_manifest_id))
                        updatedManifestIds.push(lineItem.delivery_manifest_id)
                }
            })
        })
        setInvoiceIds(updatedInvoiceIds)
        setIsPickupManifested(updatedIsPickupManifested)
        setIsDeliveryManifested(updatedIsDeliveryManifested)
    }, [charges])

    const addCharge = () => {
        console.error('addCharge is not yet implemented')
        // case 'ADD_CHARGE_TABLE':
        //     const {chargeAccount, chargeEmployee, chargeType} = state
        //     if(!chargeType) {
        //         toast.warn('Charge type selector may not be empty')
        //         console.log('chargeType may not be empty. Aborting')
        //         return
        //     }

        //     let newCharge = basicCharge(chargeType)
        //     switch(chargeType.name) {
        //         case 'Account':
        //             if(!chargeAccount) {
        //                 toast.warn('Charge account can not be empty')
        //                 console.log('chargeAccount may not be empty. Aborting')
        //                 return
        //             }
        //             newCharge = {
        //                 ...newCharge,
        //                 charge_account_id: chargeAccount.account_id,
        //                 name: chargeAccount.account_number + ' - ' + chargeAccount.name,
        //                 charge_reference_value_required: chargeAccount.is_custom_field_required ? true : false,
        //                 charge_reference_value_label: chargeAccount.custom_field ? chargeAccount.custom_field : null,
        //             }
        //             break;
        //         case 'Employee':
        //             if(!chargeEmployee) {
        //                 toast.warn('Charge Employee may not be empty')
        //                 console.log('chargeEmployee may not be empty. Aborting')
        //                 return
        //             }
        //             newCharge = {
        //                 ...newCharge,
        //                 charge_employee_id: chargeEmployee.value,
        //                 name: chargeEmployee.label,
        //                 charge_reference_value_required: false,
        //                 charge_reference_value_label: null,
        //             }
        //             break;
        //         default:
        //             newCharge =  {
        //                 ...newCharge,
        //                 name: chargeType.name,
        //                 charge_reference_value_required: chargeType.required_field ? true : false,
        //                 charge_reference_value_label: chargeType.required_field ? chargeType.required_field : null,
        //             }
        //         }
        //     return Object.assign({}, state, {
        //         charges: state.charges.concat([newCharge])
        //     })
    }

    const debouncedGenerateCharges = useCallback(() => {}
        // debounce((chargeIndex, overwrite = false) => {
    //         const charge = charges[chargeIndex]

    //         const data = {
    //             charge_account_id: charge?.account_id,
    //             delivery_address: {lat: deliveryAddressLat, lng: deliveryAddressLng, is_mall: deliveryAddressIsMall},
    //             delivery_type_id: deliveryType.id,
    //             package_is_minimum: packageIsMinimum,
    //             package_is_pallet: packageIsPallet,
    //             packages: packageIsMinimum ? [] : packageArray,
    //             pickup_address: {lat: pickupAddressLat, lng: pickupAddressLng, is_mall: pickupAddressIsMall},
    //             // TODO: replace this with ratesheet logic (mine > parents > default)
    //             ratesheet_id: activeRatesheet ? activeRatesheet.ratesheet_id : null,
    //             time_pickup_scheduled: pickupTimeScheduled.toLocaleString('en-US'),
    //             time_delivery_scheduled: deliveryTimeScheduled.toLocaleString('en-US'),
    //             use_imperial: useImperial
    //         }
    //         setAwaitingCharges(true)

    //         api.post('/bills/generateCharges', data)
    //             .then(response => {
    //                 if(overwrite) {
    //                     chargeDispatch({type: 'UPDATE_LINE_ITEMS', payload: {index: chargeIndex, data: response}})
    //                 } else {
    //                     chargeDispatch({type: 'ADD_LINE_ITEMS', payload: {index: chargeIndex, data: response}})
    //                 }
    //                 setAwaitingCharges(false)
    //                 toast.warn(
    //                     'Automatic Pricing is currently experimental. Please review the charges generated carefully for any inconsistencies',
    //                     {
    //                         position: 'top-center',
    //                         showDuration: 300,
    //                         timeOut: 5000,
    //                         extendedTImeout: 5000
    //                     }
    //                 )
    //             })
        // }, 500), [
        //     // activeRatesheet,
        //     // chargeAccount,
        //     // charges,
        //     // deliveryAddressIsMall,
        //     // deliveryAddressLat,
        //     // deliveryAddressLng,
        //     // deliveryTimeScheduled,
        //     // deliveryType,
        //     // packageIsMinimum,
        //     // packageIsPallet,
        //     // packageArray,
        //     // pickupAddressIsMall,
        //     // pickupAddressLat,
        //     // pickupAddressLng,
        //     // pickupTimeScheduled,
        //     // useImperial,
        // ]
    )

    const deleteCharge = index => {
        const deleteCharge = charges[index]
        if(!deleteCharge || !canChargeTableBeDeleted(deleteCharge)) {
            const errorMessage = 'ERROR - charge table cannot be deleted - at least one item has been invoiced or manifested'
            toast.error(errorMessage)
            console.log(errorMessage)
            return
        }
        if(confirm('Are you sure you wish to delete this charge group?\n This action can not be undone')) {
            setCharges(charges.filter((charge, i) => i != index))
        }
    }

    const updateCharge = (index, updatedCharge) => {
        setCharges(charges.map((charge, i) => {
            if(i == index)
                return updatedCharge
            return charge
        }))
    }

    const setup = data => {
        const {activeRatesheet, chargeAccount, chargeType, chargeTypes, charges, interliners, ratesheets} = data

        console.log(charges)
        setChargeTypes(chargeTypes)
        setInterliners(interliners)
        setRatesheets(ratesheets)
        // setActiveRatesheet(activeRatesheet)

        if(data.bill?.bill_id) {
            // setChargeAccount(chargeAccount)
            // setChargeType(chargeType)
            setCharges(charges)
        }
    }

    return {
        //getters
        activeRatesheet,
        charges,
        invoiceIds,
        isPickupManifested,
        isDeliveryManifested,
        manifestIds,
        //setters
        setActiveRatesheet,
        //functions
        addCharge,
        deleteCharge,
        setup,
    }
}
