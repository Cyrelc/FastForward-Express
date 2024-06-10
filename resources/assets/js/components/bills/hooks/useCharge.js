import React, {useState} from 'react'

// const basicCharge = (chargeType) => {
//     return {
//         chargeType: chargeType,
//         charge_type_id: chargeType.payment_type_id,
//         charge_reference_value: '',
//         lineItems: [],
//     }
// }


export default function useCharge() {
    const [type, setType] = useState('')
    const [chargeType, setChargeType] = useState({})
    const [lineItems, setLineItems] = useState([])

    const collect = () => {
        return 'charge formatted for submission'
    }

    return {
        //getters
        chargeType,
        lineItems,
        type,
        //setters
        setChargeType,
        // setLineItems,
        setType,
        //functions
        collect,
    }
}
