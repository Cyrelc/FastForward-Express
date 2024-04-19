import React, {useState, useEffect} from 'react'
import {Button, Card, Table} from 'react-bootstrap'

import InterlinerModal from './InterlinerModal'

import {useAPI} from '../../contexts/APIContext'

export default function InterlinersTab(props) {
    const [interlinerId, setInterlinerId] = useState(null)
    const [addressName, setAddressName] = useState('')
    const [addressFormatted, setAddressFormatted] = useState('')
    const [addressLat, setAddressLat] = useState('')
    const [addressLng, setAddressLng] = useState('')
    const [addressPlaceId, setPlaceId] = useState('')
    const [interlinerName, setInterlinerName] = useState('')
    const [showInterlinerModal, setShowInterlinerModal] = useState(false)

    const api = useAPI()

    const storeInterliner = () => {
        const data = {
            interliner_id: interlinerId,
            name: interlinerName,
            address_formatted: addressFormatted,
            address_lat: addressLat,
            address_lng: addressLng,
            address_name: addressName,
            address_place_id: addressPlaceId
        }
        api.post(`/interliners`, data)
            .then(response => {
                props.setInterliners(response.interliners)
                setShowInterlinerModal(false)
            })
    }

    const toggleInterlinerModal = (interliner = null) => {
        console.log(interliner)
        setAddressFormatted(interliner ? interliner.address_formatted : '')
        setAddressLat(interliner ? interliner.address_lat : '')
        setAddressLng(interliner ? interliner.address_lng : '')
        setAddressName(interliner ? interliner.address_name : '')
        setPlaceId(interliner ? interliner.address_place_id : '')
        setInterlinerId(interliner ? interliner.interliner_id : '')
        setInterlinerName(interliner ? interliner.interliner_name : '')
        setShowInterlinerModal(!showInterlinerModal)
    }

    return (
        <Card border='dark'>
            <Card.Header><h4 className='text-muted'>Interliners</h4></Card.Header>
            <Card.Body>
                <Table size='sm'>
                    <thead>
                        <tr>
                            <th><Button variant='success' size='sm' onClick={toggleInterlinerModal}><i className='fas fa-plus'></i></Button></th>
                            <th>Interliner ID</th>
                            <th>Interliner Name</th>
                            <th>Address Name</th>
                            <th>Address Formatted</th>
                        </tr>
                    </thead>
                    <tbody>
                        {props.interliners.map(interliner =>
                            <tr key={interliner.interliner_id}>
                                <td>
                                    <Button variant='warning' onClick={() => toggleInterlinerModal(interliner)} size='sm'>
                                        <i className='fas fa-edit'></i>
                                    </Button>
                                </td>
                                <td>{interliner.interliner_id}</td>
                                <td>{interliner.interliner_name}</td>
                                <td>{interliner.address_name}</td>
                                <td>{interliner.address_formatted}</td>
                            </tr>
                        )}
                    </tbody>
                </Table>
            </Card.Body>
            <InterlinerModal
                interlinerAddress = {{
                    type: 'Address',
                    name: addressName,
                    formatted: addressFormatted,
                    lat: addressLat,
                    lng: addressLng,
                    placeId: addressPlaceId
                }}
                interlinerId={interlinerId}
                interlinerName={interlinerName}

                setAddressFormatted={setAddressFormatted}
                setAddressLat={setAddressLat}
                setAddressLng={setAddressLng}
                setAddressName={setAddressName}
                setPlaceId={setPlaceId}

                show={showInterlinerModal}
                storeInterliner={storeInterliner}
                toggleModal={() => setShowInterlinerModal(!showInterlinerModal)}
            />
        </Card>
    )
}
