import React, {useState} from 'react'

export default function useRatesheet() {
    const [deliveryTypes, setDeliveryTypes] = useState([])
    const [conditionals, setConditionals] = useState([])
    const [miscRates, setMiscRates] = useState([])
    const [palletRate, setPalletRate] = useState([])
    const [ratesheetId, setRatesheetId] = useState('')
    const [name, setName] = useState('')
    const [timingConditionals, setTimingConditionals] = useState([])
    const [volumeRates, setVolumeRates] = useState([])

    return {
        deliveryTypes,
        conditionals,
        miscRates,
        name,
        palletRate,
        ratesheetId,
        setDeliveryTypes,
        setConditionals,
        setMiscRates,
        setName,
        setPalletRate,
        setRatesheetId,
        setTimingConditionals,
        setVolumeRates,
        timingConditionals,
        volumeRates,
    }
}
