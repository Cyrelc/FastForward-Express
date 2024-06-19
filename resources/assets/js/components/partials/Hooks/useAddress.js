import {useState} from 'react'

export default function useAddress() {
    const [addressId, setAddressId] = useState('')
    const [formatted, setFormatted] = useState('')
    const [isMall, setIsMall] = useState(false)
    const [lat, setLat] = useState('')
    const [lng, setLng] = useState('')
    const [name, setName] = useState('')
    const [placeId, setPlaceId] = useState('')
    const [type, setType] = useState('Search')
    const [zone, setZone] = useState({})

    const collect = () => {
        return {
            'address_id': addressId,
            'formatted':  formatted,
            'is_mall': isMall,
            'lat': lat,
            'lng': lng,
            'name': name,
            'place_id': placeId
        }
    }

    const setFromAccount = account => {
        setFormatted(account.shipping_address ? account.shipping_address_formatted : account.billing_address_formatted)
        setLat(account.shipping_address ? account.shipping_address_lat : account.billing_address_lat)
        setLng(account.shipping_address ? account.shipping_address_lng : account.billing_address_lng)
        setName(account.shipping_address ? account.shipping_address_name : account.billing_address_name),
        setPlaceId(account.shipping_address ? account.shipping_address_place_id : account.billing_address_place_id)
    }

    const reset = () => {
        setAddressId('')
        setFormatted('')
        setIsMall(false)
        setLat('')
        setLng('')
        setName('')
        setPlaceId('')
        setType('Search')
    }

    const setup = address => {
        setAddressId(address.address_id)
        setFormatted(address.formatted)
        setIsMall(address.is_mall)
        setLat(address.lat)
        setLng(address.lng)
        setName(address.name)
        setPlaceId(address.place_id)
    }

    return {
        addressId,
        formatted,
        isMall,
        lat,
        lng,
        name,
        placeId,
        type,
        zone,

        collect,
        reset,
        setFormatted,
        setFromAccount,
        setIsMall,
        setLat,
        setLng,
        setName,
        setPlaceId,
        setType,
        setZone,
        setup,
    }
}

