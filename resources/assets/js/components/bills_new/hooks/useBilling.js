import React, {useCallback, useEffect, useState} from 'react'
import {toast} from 'react-toastify'

function canChargeTableBeDeleted(charge) {
    // TODO - have to figure out how to get readOnly status
    // oh... why not just check readOnly before calling the function? :think:
    // if(!charge || !!props.readOnly)
        return false
    return !charge.lineItems.some(lineItem => (lineItem.invoice_id || lineItem.pickup_manifest_id || lineItem.delivery_manifest_id) ? true : false)
}

export default function useBilling({billId, permissions}) {
    const [activeRatesheet, setActiveRatesheet] = useState({})
    const [chargeAccount, setChargeAccount] = useState()
    const [charges, setCharges] = useState([])
    const [chargeReferenceValue, setChargeReferenceValue] = useState('')
    const [chargeType, setChargeType] = useState({})
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
    const [repeatInterval, setRepeatInterval] = useState()
    const [repeatIntervals, setRepeatIntervals] = useState([])
    const [skipInvoicing, setSkipInvoicing] = useState(false)

    useEffect(() => {
        let updatedInvoiceIds = []
        let updatedIsPickupManifested = false
        let updatedIsDeliveryManifested = false
        let updatedManifestIds = []
        let updatedHasInterliner = false
        charges.forEach(charge => {
            charge.lineItems.forEach(lineItem => {
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
                if(lineItem.name === 'Interliner' && !lineItem.toBeDeleted)
                    hasInterliner = true
            })
        })
        setInvoiceIds(updatedInvoiceIds)
        setIsPickupManifested(updatedIsPickupManifested)
        setIsDeliveryManifested(updatedIsDeliveryManifested)
        setHasInterliner(updatedHasInterliner)
        setIsInvoiced(updatedInvoiceIds.length)
    }, [charges])

    // Set the ratesheet (for purposes of delivery type time primarily) - based on the currently selected charge Account on the basic page
    useEffect(() => {
        if(permissions.createBasic && billId && chargeAccount?.ratesheet_id != null && chargeAccount?.ratesheet_id != activeRatesheet?.ratesheet_id) {
            const ratesheet = ratesheets.find(ratesheet => ratesheet.ratesheet_id === chargeAccount.ratesheet_id)
            if(ratesheet) {
                setActiveRatesheet(ratesheet)
            }
        }
    }, [chargeAccount])

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
        const {activeRatesheet, chargeAccount, chargeType, charges} = data
        console.log(data)

        // console.log(charges, chargeTypes)
        setChargeTypes(data.charge_types)
        setInterliners(data.interliners)
        setRatesheets(data.ratesheets)
        setActiveRatesheet(data.ratesheets[0])
        setRepeatIntervals(data.repeat_intervals)

        if(data.bill?.bill_id) {
            // setChargeAccount(chargeAccount)
            // setChargeType(chargeType)
            setRepeatInterval(data.bill.repeat_interval ?? '')
            setCharges(data.charges)
            setSkipInvoicing(data.bill.skip_invoicing)
        }
    }

    return {
        //getters
        activeRatesheet,
        chargeAccount,
        charges,
        chargeType,
        chargeTypes,
        hasInterliner,
        interliner,
        interlinerActualCost,
        interlinerReferenceValue,
        interliners,
        invoiceIds,
        isPickupManifested,
        isDeliveryManifested,
        isInvoiced,
        manifestIds,
        ratesheets,
        repeatInterval,
        repeatIntervals,
        //setters
        setChargeAccount,
        setChargeType,
        setActiveRatesheet,
        setInterliner,
        setInterlinerActualCost,
        setInterlinerReferenceValue,
        setRepeatInterval,
        //functions
        addCharge,
        deleteCharge,
        setup,
    }
}
