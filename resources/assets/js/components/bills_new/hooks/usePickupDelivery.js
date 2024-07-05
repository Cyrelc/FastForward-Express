import React, {useEffect, useState} from 'react'

import useAddress from '../../partials/Hooks/useAddress'

export default function usePickupDelivery({accounts, activeRatesheet, isPickup = true}) {
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

    // let newPickupCommission = driver.commission
    // if(!state.pickup.driverCommission || state.pickup.driver.delivery_commission == state.pickup.driverCommission)
    //     newPickupCommission = payload ? parseInt(payload.pickup_commission) : 0
    // let newDeliveryCommission = state.delivery.driverCommission
    // if(!state.delivery.driverCommission || state.delivery.driver.delivery_commission == state.delivery.driverCommission)
    //     newDeliveryCommission = payload ? parseInt(payload.delivery_commission) : 0
    // return Object.assign({}, state, {
    //     timeDispatched: state.timeDispatched ? state.timeDispatched : new Date(),
    //     pickup: {
    //         ...state.pickup,
    //         driver: payload,
    //         driverCommission: newPickupCommission
    //     },
    //     delivery: state.delivery.driver ? state.delivery : {
    //         ...state.delivery,
    //         driver: payload,
    //         driverCommission: newDeliveryCommission
    //     }
    // })

    const changeDriver = newDriver => {
        const newDriverCommission = isPickup ? driver.pickup_commission : driver.delivery_commission
        if(!driver || (isPickup ? driver.pickup_commission == driverCommission : driver.delivery_commission == driverCommission)) {
            setDriverCommission(newDriverCommission)
        }
        setDriver(newDriver)
    }

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
        //getters
        ...address,
        account,
        driver,
        driverCommission,
        personName,
        referenceValue,
        timeActual,
        timeScheduled,
        zone,
        //setters
        setAccount,
        setDriverCommission,
        setPersonName,
        setReferenceValue,
        setTimeActual,
        setTimeScheduled,
        setZone,
        //functions
        changeDriver,
        setup,
    }
}
