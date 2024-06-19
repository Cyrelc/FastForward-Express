import React, {useEffect, useState} from 'react'

import useMath from '../../partials/Hooks/useMath'
import {useUser} from '../../../contexts/UserContext'

const newPackage = {count: 1, weight: '', length: '', width: '', height: '', totalWeight: '', cubedWeight: ''}

export default function usePackages() {
    const math = useMath()
    const user = useUser()

    const [packageArray, setPackageArray] = useState([newPackage])
    const [packageIsMinimum, setPackageIsMinimum] = useState(false)
    const [packageIsPallet, setPackageIsPallet] = useState(false)
    const [requireProofOfDelivery, setRequireProofOfDelivery] = useState(false)
    const [useImperial, setUseImperial] = useState(user.settings.use_imperial_default ?? false)

    useEffect(() => {
        setPackageArray(packageArray.map(parcel => {
            return calculateWeightAndCubedWeight(parcel)
        }))
    }, [useImperial])

    const collect = () => {
        return {
            is_min_weight_size: packageIsMinimum,
            is_pallet: packageIsPallet,
            packages: packageArray,
            proof_of_delivery_required: requireProofOfDelivery,
            use_imperial: useImperial,
        }
    }

    const setup = bill => {
        setPackageIsMinimum(bill.is_min_weight_size)
        setPackageIsPallet(bill.is_pallet)
        setRequireProofOfDelivery(bill.proof_of_delivery_required)
        setUseImperial(bill.use_imperial)
        if(bill.packages && bill.packages.length)
            setPackageArray(bill.packages)
    }

    const addPackage = () => {
        setPackageArray([...packageArray, newPackage])
    }

    const calculateWeightAndCubedWeight = parcel => {
        const {weight, count, length, height, width} = parcel

        let totalWeight = parseInt(count) * parseFloat(weight)
        let cubedWeight = 0
        if(useImperial) {
            cubedWeight = math.getCubedWeightFromInches(parseFloat(length), parseFloat(width), parseFloat(height)) * parseInt(count)
            totalWeight = math.poundsToKilograms(totalWeight)
        } else
            cubedWeight = math.getCubedWeightFromCentimeters(parseFloat(length), parseFloat(width), parseFloat(height)) * parseInt(count)

        if(!count) {
            totalWeight = NaN
            cubedWeight = NaN
        } else if(!weight)
            totalWeight = NaN
        else if (!length || !width || !height)
            cubedWeight = NaN

        return {...parcel, totalWeight: totalWeight ? totalWeight.toFixed(2) : '', cubedWeight: cubedWeight ? cubedWeight.toFixed(2) : ''}
    }

    const deletePackage = index => {
        if(packageArray.length == 1)
            return
        setPackageArray(packageArray.filter((element, i) => i != index))
    }

    const handlePackageUpdate = event => {
        const {name, value, dataset} = event.target
        setPackageArray(packageArray.map((parcel, index) => {
            if(index == dataset.packageid)
                return calculateWeightAndCubedWeight({...parcel, [name]: value})
            return parcel
        }))
    }

    return {
        addPackage,
        collect,
        deletePackage,
        handlePackageUpdate,
        packageArray,
        packageIsMinimum,
        packageIsPallet,
        requireProofOfDelivery,
        setPackageIsMinimum,
        setPackageIsPallet,
        setPackageArray,
        setRequireProofOfDelivery,
        setup,
        setUseImperial,
        useImperial,
    }
}

