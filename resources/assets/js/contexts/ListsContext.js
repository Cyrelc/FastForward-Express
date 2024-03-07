import React, {createContext, useContext, useEffect, useState} from 'react'
import {useAPI} from './APIContext'

const ListsContext = createContext()

export const ListsProvider = ({children}) => {
    const [accounts, setAccounts] = useState([])
    const [employees, setEmployees] = useState([])
    const [invoiceIntervals, setInvoiceIntervals] = useState([])
    const [paymentTypes, setPaymentTypes] = useState([])
    const [repeatIntervals, setRepeatIntervals] = useState([])
    const [vehicleTypes, setVehicleTypes] = useState([])

    const api = useAPI()

    useEffect(() => {
        api.get('/lists')
            .then(data => {
                data = data.data
                setAccounts(data.accounts)
                setEmployees(data.employees)
                setInvoiceIntervals(data.invoice_intervals ?? [])
                setPaymentTypes(data.payment_types?? [])
                setRepeatIntervals(data.repeat_intervals ?? [])
                setVehicleTypes(data.vehicle_types ?? [])
            })
    }, [])

    return (
        <ListsContext.Provider value={{
            accounts,
            // appUpdated,
            employees,
            invoiceIntervals,
            paymentTypes,
            repeatIntervals,
            vehicleTypes,
        }}>
            {children}
        </ListsContext.Provider>)
}

export const useLists = () => useContext(ListsContext)
