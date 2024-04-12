import React, {useState} from 'react'

export default function useRatesheet() {
    const [deliveryTypes, setDeliveryTypes] = useState([])
    const [miscRates, setMiscRates] = useState([])
    const [palletRate, setPalletRate] = useState([])
    const [ratesheetId, setRatesheetId] = useState('')
    const [name, setName] = useState('')
    const [timeRates, setTimeRates] = useState([])
    const [useInternalZonesCalc, setUseInternalZonesCalc] = useState(true)
    const [volumeRates, setVolumeRates] = useState([])
    const [weightRates, setWeightRates] = useState([])
    const [zoneRates, setZoneRates] = useState([])

    return {
        deliveryTypes,
        miscRates,
        name,
        palletRate,
        ratesheetId,
        setDeliveryTypes,
        setMiscRates,
        setName,
        setPalletRate,
        setRatesheetId,
        setTimeRates,
        setUseInternalZonesCalc,
        setVolumeRates,
        setWeightRates,
        setZoneRates,
        timeRates,
        useInternalZonesCalc,
        volumeRates,
        weightRates,
        zoneRates,
    }
}
