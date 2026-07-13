import React, {useState} from 'react'

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
    // Coerced to a real boolean: the setting arrives from the API as 1/0, and the stored bill's flag as
    // 1/0 too. Comparing those to decide whether a conversion is needed (true === 1 is false) would
    // convert a bill that was already in the right unit, off by 2.2x, every single time.
    const [useImperial, setUseImperial] = useState(!!(user.settings.use_imperial_default ?? false))

    /**
     * Two decimals, because a weight is a weight and not a physics constant - but the precision here is
     * load-bearing, so do not "tidy" it away.
     *
     * Converting a stored weight into the reader's unit and back again snaps it onto the coarser of the
     * two grids: a kilogram is 2.2 lb, so 0.01 kg is 0.022 lb. At two decimals that snap happens ONCE
     * (21.15 lb -> 9.59 kg -> 21.14 lb) and the value is then a fixed point - re-opening the bill a
     * hundred times in alternating units never moves it again, and it never oscillates. Verified across
     * every hundredth of a pound from 0.1 to 1000. Drop to one decimal and the one-time snap grows to
     * ~0.1 lb; it stays bounded, but it stops being invisible.
     */
    const round2 = value => {
        const number = parseFloat(value)
        return Number.isFinite(number) ? +number.toFixed(2) : value
    }

    /**
     * Re-expresses a parcel's typed figures in a different unit.
     *
     * Note this converts BOTH weight and the dimensions. useImperial governs inches-vs-centimetres as
     * well as pounds-vs-kilograms, so converting only the weight would leave a parcel measured in
     * inches being read as centimetres - a 2.5x error in its cubed weight, which is the figure that can
     * outweigh the real one and set the price.
     */
    const convertParcels = (parcels, toImperial) => {
        const weight = toImperial ? math.kilogramsToPounds : math.poundsToKilograms
        const length = toImperial ? math.centimetersToInches : math.inchesToCentimeters

        return (parcels ?? []).map(parcel => calculateWeightAndCubedWeight({
            ...parcel,
            weight: parcel.weight ? round2(weight(parseFloat(parcel.weight))) : parcel.weight,
            length: parcel.length ? round2(length(parseFloat(parcel.length))) : parcel.length,
            width: parcel.width ? round2(length(parseFloat(parcel.width))) : parcel.width,
            height: parcel.height ? round2(length(parseFloat(parcel.height))) : parcel.height,
        }, toImperial))
    }

    const collect = () => {
        return {
            is_min_weight_size: packageIsMinimum,
            is_pallet: packageIsPallet,
            packages: packageArray,
            proof_of_delivery_required: requireProofOfDelivery,
            // The unit the figures being saved are expressed in - i.e. the one whoever last edited the
            // bill was working in, not necessarily the one it was first written in.
            use_imperial: useImperial,
        }
    }

    /**
     * Loads a stored bill into the reader's preferred unit.
     *
     * The bill records the unit it was typed in (bills.use_imperial), and the reader has a preference.
     * The reader's wins: an employee who thinks in pounds should not have to read a customer's
     * kilograms. So the stored figures are CONVERTED, not reinterpreted - reinterpreting 45.36 kg as
     * "45.36 lb" would be off by a factor of 2.2, which is exactly the class of bug this replaces.
     */
    const setup = bill => {
        setPackageIsMinimum(bill.is_min_weight_size)
        setPackageIsPallet(bill.is_pallet)
        setRequireProofOfDelivery(bill.proof_of_delivery_required)

        if(!bill.packages || !bill.packages.length)
            return

        const storedImperial = !!bill.use_imperial
        const readerImperial = !!useImperial

        setPackageArray(storedImperial === readerImperial
            ? bill.packages
            : convertParcels(bill.packages, readerImperial))
    }

    /**
     * Flipping the toggle converts what is on screen, rather than reinterpreting it.
     *
     * It used to reinterpret: the numbers stayed and only their meaning changed, so a parcel typed as
     * 100 lb silently became 100 kg. That made sense when the toggle meant "the unit I am typing in";
     * it does not now that it means "the unit I want to read in".
     */
    const changeUseImperial = toImperial => {
        setUseImperial(toImperial)
        setPackageArray(convertParcels(packageArray, toImperial))
    }

    const addPackage = () => {
        setPackageArray([...packageArray, newPackage])
    }

    /**
     * @param imperial - the unit the parcel's figures are in. Passed explicitly because a conversion
     *   recalculates parcels for a unit that useImperial has not caught up to yet (setState is async),
     *   and reading the stale value would compute the totals in the unit we are leaving.
     *
     * totalWeight is ALWAYS kilograms, whatever the parcel was typed in. That is the contract the
     * pricing engine relies on - it does not convert - so it must hold on every path through here.
     */
    const calculateWeightAndCubedWeight = (parcel, imperial = useImperial) => {
        const {weight, count, length, height, width} = parcel

        let totalWeight = parseInt(count) * parseFloat(weight)
        let cubedWeight = 0
        if(imperial) {
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
        // Converts what is on screen rather than reinterpreting it - see changeUseImperial.
        setUseImperial: changeUseImperial,
        useImperial,
    }
}

