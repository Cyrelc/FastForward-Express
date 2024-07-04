import React, {useEffect, useState} from 'react'

import useAddress from '../../partials/Hooks/useAddress'

export default function usePickupDelivery({accounts, activeRatesheet}) {
    const address = useAddress()

    const [account, setAccount] = useState({})
    const [driver, setDriver] = useState({})
    const [driverCommission, setDriverCommission] = useState('')
    const [personName, setPersonName] = useState('')
    const [referenceValue, setReferenceValue] = useState('')
    const [timeActual, setTimeActual] = useState(new Date())
    const [timeScheduled, setTimeScheduled] = useState(new Date())
    const [zone, setZone] = useState({})

    useEffect(() => {
        address.setFromAccount(account)
    }, [account])

    useEffect(() => {
        if(address.lat && address.lng && activeRatesheet) {
            api.get(`/ratesheets/${activeRatesheet.ratesheet_id}/getZone?lat=${address.lat}&lng=${address.lng}`)
                .then(response => {
                    setZone(response)
                    // if(!billId && applyRestrictions)
                    //     props.setPickupTimeExpected(new Date())
                })
        }
    }, [address.lat, address.lng, activeRatesheet])

    const setup = data => {
        if(data.account) {
            setAccount(data.account)
            address.setType('Account')
        }
        setDriver(driver)
        setDriverCommission(data.driver_commission)
        address.setup(data.address)
    }

    return {
        ...address,
        account,
        driver,
        driverCommission,
        personName,
        referenceValue,
        timeActual,
        timeScheduled,
        zone,
        setup,
        setAccount,
        setDriver,
        setDriverCommission,
        setPersonName,
        setReferenceValue,
        setTimeActual,
        setTimeScheduled,
        setZone
    }
}
