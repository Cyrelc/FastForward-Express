import {useState} from 'react'

import useAddress from './useAddress'

export default function usePickupDelivery() {
    const address = useAddress()

    const [account, setAccount] = useState({})
    const [driver, setDriver] = useState({})
    const [driverCommission, setDriverCommission] = useState('')
    const [personName, setPersonName] = useState('')
    const [referenceValue, setReferenceValue] = useState('')
    const [timeActual, setTimeActual] = useState(new Date())
    const [timeScheduled, setTimeScheduled] = useState(new Date())
    const [zone, setZone] = useState({})

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
