import React, {createContext, useContext, useEffect, useState} from 'react'
import {useAPI} from './APIContext'

const ListsContext = createContext()

export const ListsProvider = ({children}) => {
    const [accounts, setAccounts] = useState([])
    const [chargeTypes, setChargeTypes] = useState([])
    const [emailTypes, setEmailTypes] = useState([])
    const [employees, setEmployees] = useState([])
    const [invoiceIntervals, setInvoiceIntervals] = useState([])
    const [paymentTypes, setPaymentTypes] = useState([])
    const [phoneTypes, setPhoneTypes] = useState([])
    const [repeatIntervals, setRepeatIntervals] = useState([])
    const [vehicleTypes, setVehicleTypes] = useState([])

    const api = useAPI()

    useEffect(() => {
        api.get('/lists')
            .then(data => {
                data = data.data
                setAccounts(data.accounts)
                setChargeTypes(data.charge_types ?? [])
                setEmailTypes(data.email_types)
                setEmployees(data.employees)
                setInvoiceIntervals(data.invoice_intervals ?? [])
                setPaymentTypes(data.payment_types?? [])
                setPhoneTypes(data.phone_types)
                setRepeatIntervals(data.repeat_intervals ?? [])
                setVehicleTypes(data.vehicle_types ?? [])
            })
    }, [])

    return (
        <ListsContext.Provider value={{
            accounts,
            // appUpdated,
            chargeTypes,
            emailTypes,
            employees,
            invoiceIntervals,
            paymentTypes,
            phoneTypes,
            repeatIntervals,
            vehicleTypes,
        }}>
            {children}
        </ListsContext.Provider>)
}

export const useLists = () => useContext(ListsContext)
