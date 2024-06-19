import React, {useEffect, useState} from 'react'

import {useUser} from '../../../contexts/UserContext'

import useCharges from './useCharges'
import usePackages from './usePackages'
import usePickupDelivery from './usePickupDelivery'

const initialPersistFields = [
    // {name: 'chargeAccount', label: 'Charge Account', checked: false},
    // {name: 'deliveryAccount', label: 'Delivery Account', checked: false},
    {name: 'deliveryDriver', label: 'Delivery Driver', checked: false},
    {name: 'deliveryDriverCommission', label: 'Delivery Driver Commission', checked: false},
    {name: 'deliveryTimeExpected', label: 'Delivery Time (Scheduled)', checked: false},
    {name: 'deliveryType', label: 'Delivery Type', checked: false},
    // {name: 'pickupAccount', label: 'Pickup Account', checked: false},
    {name: 'pickupDriver', label: 'Pickup Driver', checked: false},
    {name: 'pickupTimeExpected', label: 'Pickup Time (Scheduled)', checked: false},
    {name: 'pickupDriverCommission', label: 'Pickup Driver Commission', checked: false}
]

export default function useBill() {
    const [acceptTermsAndConditions, setAcceptTermsAndConditions] = useState(false)
    const [accounts, setAccounts] = useState([])
    const [activeRatesheet, setActiveRatesheet] = useState({})
    const [applyRestrictions, setApplyRestrictions] = useState(true)
    const [billId, setBillId] = useState(null)
    const [description, setDescription] = useState('')
    const [incompleteFields, setIncompleteFields] = useState([])
    const [isTemplate, setIsTemplate] = useState(false)
    const [percentComplete, setPercentComplete] = useState(null)
    const [permissions, setPermissions] = useState([])
    const [persistFields, setPersistFields] = useState([])
    const [viewTermsAndConditions, setViewTermsAndConditions] = useState(false)

    const charges = useCharges()
    const delivery = usePickupDelivery(activeRatesheet)
    const packages = usePackages()
    const pickup = usePickupDelivery(activeRatesheet)

    // In the event a new pickup or delivery account has been set and there is no charge account, automatically populate the charge account
    useEffect(() => {
        if(!billId && !charges.charges?.length && pickup.account?.account_id) {
            charges.setChargeAccount(pickup.account)
            charges.setChargeType(charges.chargeTypes.find(chargeType => chargeType.name === 'Account'))
            charges.addCharge()
            if(pickup.account?.ratesheet_id && pickup.account?.ratesheet_id != activeRatesheet.ratesheet_id)
                setActiveRatesheet(ratesheets.find(ratesheet => ratesheet.ratesheet_id == pickup.account.ratesheet_id))
        }
    }, [pickup.account])

    useEffect(() => {
        if(!billId && !charges.charges?.length && delivery.account?.account_id) {
            charges.setChargeAccount(delivery.account)
            charges.setChargeType(charges.chargeTypes.find(chargeType => chargeType.name === 'Account'))
            charges.addCharge()
            if(delivery.account?.ratesheet_id && delivery.account?.ratesheet_id != activeRatesheet.ratesheet_id)
                setActiveRatesheet(ratesheets.find(ratesheet => ratesheet.ratesheet_id == delivery.account.ratesheet_id))
        }
    }, [delivery.account])

    const setup = data => {
        setAccounts(data.accounts)
        setPermissions(data.permissions)
        if(data.bill?.bill_id)
            setupExisting(data)
        else {
            const localPersistFields = localStorage.getItem('bill.persistFields')
            setPersistFields(localPersistFields ? JSON.parse(localPersistFields) : initialPersistFields)
        }
    }

    // TODO - maybe delete, might be redundant
    // const configureCreate = data => {

    // }

    // internal function, never needs to be returned
    const setupExisting = data => {
        setBillId(data.bill.bill_id)
        setDescription(data.bill.description)
        setIncompleteFields(JSON.parse(data.bill.incomplete_fields))
        setIsTemplate(data.bill.is_template)
        setPercentComplete(data.bill.percentage_complete)

        // charges.setup(data)
        packages.setup(data.bill)
        delivery.setup({
            account: data.bill.delivery_account_id,
            address: data.delivery_address,
            driver: data.bill.delivery_driver_id ? employees.find(employee => employee.employee_id == data.bill.delivery_driver_id) : {},
            driver_comission: data.bill.delivery_driver_commission,
        })
        pickup.setup({
            account: data.bill.pickup_account_id,
            address: data.pickup_address,
            driver: data.bill.pickup_driver_id ? employees.find(employee => employee.employee_id == data.bill.pickup_driver_id) : {},
            driver_comission: data.bill.pickup_driver_commission,
        })
    }
    
    const toggleIsTemplate = () => {
        setIsTemplate(!isTemplate)
    }

    const togglePersistField = field => {
        const updatedPersistFields = persistFields.map(persistField => {
            if(field.name === persistField.name)
                return {...persistField, checked: !persistField.checked}
            return persistField
        })
        localStorage.setItem("bill.persistFields", JSON.stringify(updatedPersistFields))
        setPersistFields(updatedPersistFields)
    }

    const toggleRestrictions = () => {
        setApplyRestrictions(!applyRestrictions)
    }

    const toggleAcceptTermsAndConditions = () => {
        setAcceptTermsAndConditions(!acceptTermsAndConditions)
    }

    const toggleViewTermsAndConditions = () => {
        setViewTermsAndConditions(!viewTermsAndConditions)
    }

    return {
        //getters,
        bill: {
            acceptTermsAndConditions,
            accounts,
            applyRestrictions,
            billId,
            description,
            incompleteFields,
            isTemplate,
            percentComplete,
            permissions,
            persistFields,
            viewTermsAndConditions,
            //setters,
            setDescription,
            //functions,
            setup,
            toggleAcceptTermsAndConditions,
            toggleIsTemplate,
            togglePersistField,
            toggleRestrictions,
            toggleViewTermsAndConditions,
        },
        charges,
        delivery,
        packages,
        pickup,
    }
}
