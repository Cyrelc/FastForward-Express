import React, {useState} from 'react'

export default function useRatesheet() {
    const [deliveryTypes, setDeliveryTypes] = useState([])
    const [conditionals, setConditionals] = useState([])
    const [miscRates, setMiscRates] = useState([])
    const [palletRate, setPalletRate] = useState([])
    const [ratesheetId, setRatesheetId] = useState('')
    const [name, setName] = useState('')
    const [timeRates, setTimeRates] = useState([])
    const [timingConditionals, setTimingConditionals] = useState([])
    const [useInternalZonesCalc, setUseInternalZonesCalc] = useState(true)
    const [volumeRates, setVolumeRates] = useState([])
    const [weightRates, setWeightRates] = useState([])
    const [zoneRates, setZoneRates] = useState([])

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
        setTimeRates,
        setTimingConditionals,
        setUseInternalZonesCalc,
        setVolumeRates,
        setWeightRates,
        setZoneRates,
        timeRates,
        timingConditionals,
        useInternalZonesCalc,
        volumeRates,
        weightRates,
        zoneRates,
    }
}
