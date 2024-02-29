import React, {createContext, useContext, useEffect, useState} from 'react'
import {useAPI} from './APIContext'

const ListsContext = createContext()

export const ListsProvider = ({children}) => {
    const [accounts, setAccounts] = useState([])
    const [drivers, setDrivers] = useState([])
    const [employees, setEmployees] = useState([])
    const [invoiceIntervals, setInvoiceIntervals] = useState([])
    const [parentAccounts, setParentAccounts] = useState([])
    const [paymentTypes, setPaymentTypes] = useState([])
    const [repeatIntervals, setRepeatIntervals] = useState([])

    const api = useAPI()

    useEffect(() => {
        api.get('/lists')
            .then(data => {
                data = data.data
                setAccounts(data.accounts)
                setEmployees(data.employees)
                console.log(data)
                // setDriverList(data.drivers)
                // setInvoiceIntervals(data.invoice_intervals)
                // setParentAccounts(data.parent_accounts)
                // setPaymentTypes(data.payment_types)
                // setRepeatIntervals(data.repeatIntervals)
            })
    }, [])

    return (
        <ListsContext.Provider value={{
            accounts,
            // appUpdated,
            drivers,
            employees,
            invoiceIntervals,
            parentAccounts,
            paymentTypes,
            repeatIntervals
        }}>
            {children}
        </ListsContext.Provider>)
}

export const useLists = () => useContext(ListsContext)
