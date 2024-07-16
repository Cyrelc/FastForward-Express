import React, {useEffect, useState} from 'react'

// import {useUser} from '../../../contexts/UserContext'
import {DateTime} from 'luxon'

import useCharges from './useCharges'
import usePackages from './usePackages'
import usePickupDelivery from './usePickupDelivery'
import {useLists} from '../../../contexts/ListsContext'

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
    const [activityLog, setActivityLog] = useState([])
    const [applyRestrictions, setApplyRestrictions] = useState(true)
    const [billId, setBillId] = useState(null)
    const [businessHoursMax, setBusinessHoursMax] = useState(DateTime.now())
    const [businessHoursMin, setBusinessHoursMin] = useState(DateTime.now())
    const [deliveryType, setDeliveryType] = useState(null)
    const [deliveryTypes, setDeliveryTypes] = useState([])
    const [description, setDescription] = useState('')
    const [incompleteFields, setIncompleteFields] = useState([])
    const [internalComments, setInternalComments] = useState('')
    const [isTemplate, setIsTemplate] = useState(false)
    const [percentComplete, setPercentComplete] = useState(null)
    const [permissions, setPermissions] = useState([])
    const [persistFields, setPersistFields] = useState([])
    // TODO - statically set, needs logic
    const [readOnly, setReadOnly] = useState(false)
    const [timeDispatched, setTimeDispatched] = useState()
    const [timeCallReceived, setTimeCallReceived] = useState()
    const [timeTenFoured, setTimeTenFoured] = useState()
    const [viewTermsAndConditions, setViewTermsAndConditions] = useState(false)

    const charges = useCharges(activeRatesheet)
    const delivery = usePickupDelivery({accounts, activeRatesheet, isPickup: false})
    const packages = usePackages()
    const pickup = usePickupDelivery(accounts, activeRatesheet)
    const {employees} = useLists()

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

    useEffect(() => {
        if(activeRatesheet?.delivery_types) {
            const deliveryTypes = JSON.parse(activeRatesheet.delivery_types)
            setDeliveryTypes(deliveryTypes)
            if(deliveryType) {
                setDeliveryType(deliveryTypes.find(activeRatesheetDeliveryType => activeRatesheetDeliveryType.id == deliveryType.value))
            } else {
                setDeliveryType(deliveryTypes[0])
            }
        }
    }, [activeRatesheet])

    const setup = data => {
        setAccounts(data.accounts)
        setActiveRatesheet(data.ratesheets[0])
        setBusinessHoursMax(DateTime.fromISO(data.time_max))
        setBusinessHoursMin(DateTime.fromISO(data.time_min))
        // setDeliveryType(data.ratesheets[0].delivery_types[0])
        setPermissions(data.permissions)
        // businessHoursMax: DateTime.fromISO(payload.time_max),
        // businessHoursMin: DateTime.fromISO(payload.time_min),

        if(data.bill?.bill_id)
            setupExisting(data)
        else {
            const localPersistFields = localStorage.getItem('bill.persistFields')
            setPersistFields(localPersistFields ? JSON.parse(localPersistFields) : initialPersistFields)
            charges.setup(data)
        }
    }

    // TODO - maybe delete, might be redundant (all "create" logic may be done in setup regardless)
    // const configureCreate = data => {

    // }

    // internal function, never needs to be returned
    const setupExisting = data => {
        // setActiveRatesheet()
        setActivityLog(data.activity_log)
        setBillId(data.bill.bill_id)
        setDeliveryType(data.delivery_types.find(deliveryType => deliveryType.value == data.bill.delivery_type))
        setDescription(data.bill.description)
        setIncompleteFields(JSON.parse(data.bill.incomplete_fields))
        setInternalComments(data.bill.internal_comments)
        setIsTemplate(data.bill.is_template)
        setPercentComplete(data.bill.percentage_complete)
        setTimeCallReceived(Date.parse(data.bill.time_call_received))
        setTimeDispatched(Date.parse(data.bill.time_dispatched))
        setTimeTenFoured(Date.parse(data.bill.time_ten_foured))

        // charges.setup(data)
        packages.setup(data.bill)
        delivery.setup({
            account: data.accounts.find(account => account.account_id === data.bill.delivery_account_id),
            address: data.delivery_address,
            driver: data.bill.delivery_driver_id ? employees.find(employee => employee.employee_id == data.bill.delivery_driver_id) : {},
            driver_commission: data.bill.delivery_driver_commission,
            person_name: data.bill.delivery_person_name
        })
        pickup.setup({
            account: data.accounts.find(account => account.account_id === data.bill.pickup_account_id),
            address: data.pickup_address,
            driver: data.bill.pickup_driver_id ? employees.find(employee => employee.employee_id == data.bill.pickup_driver_id) : {},
            driver_commission: data.bill.pickup_driver_commission,
            person_name: data.bill.pickup_person_name
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
            activityLog,
            applyRestrictions,
            billId,
            businessHoursMax,
            businessHoursMin,
            deliveryType,
            deliveryTypes,
            description,
            incompleteFields,
            internalComments,
            isTemplate,
            percentComplete,
            permissions,
            persistFields,
            readOnly,
            timeCallReceived,
            timeDispatched,
            timeTenFoured,
            viewTermsAndConditions,
            //setters,
            setActiveRatesheet,
            setDeliveryType,
            setDescription,
            setInternalComments,
            setTimeDispatched,
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
