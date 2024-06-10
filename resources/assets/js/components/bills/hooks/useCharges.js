import React, {useEffect, useState} from 'react'
import {toast} from 'react-toastify'

function canChargeTableBeDeleted(charge) {
    // TODO - have to figure out how to get readOnly status
    // if(!charge || !!props.readOnly)
        return false
    return !charge.lineItems.some(lineItem => (lineItem.invoice_id || lineItem.pickup_manifest_id || lineItem.delivery_manifest_id) ? true : false)
}

export default function useCharges() {
    const [activeRatesheet, setActiveRatesheet] = useState({})
    const [charges, setCharges] = useState([])
    const [chargeReferenceValue, setChargeReferenceValue] = useState('')
    const [chargeTypes, setChargeTypes] = useState([])
    const [hasInterliner, setHasInterliner] = useState(false)
    const [interliner, setInterliner] = useState({})
    const [interlinerActualCost, setInterlinerActualCost] = useState('')
    const [interlinerReferenceValue, setInterlinerReferenceValue] = useState('')
    const [interliners, setInterliners] = useState([])
    // const [invoiceIds, setInvoiceIds] = useState([])
    // const [isDeliveryManifested, setIsDeliveryManifested] = useState(false)
    const [isInvoiced, setIsInvoiced] = useState(false)
    // const [isPickupManifested, setIsPickupManifested] = useState(false)
    const [manifestIds, setManifestIds] = useState([])
    const [ratesheets, setRatesheets] = useState([])

    const addCharge = () => {
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

    const setup = (data) => {
        const {activeRatesheet, chargeAccount, chargeType, chargeTypes, interliners, ratesheets} = data

        setChargeTypes(chargeTypes)
        setInterliners(interliners)
        setRatesheets(ratesheets)
        // setActiveRatesheet(activeRatesheet)

        if(data.bill?.bill_id) {
            setChargeAccount(chargeAccount)
            setChargeType(chargeType)
        }
    }

    return {
        //getters
        activeRatesheet,
        //setters
        setActiveRatesheet,
        //functions
        addCharge,
        deleteCharge,
        setup,
    }
}
