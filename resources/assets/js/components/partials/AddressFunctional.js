import React, {useEffect, useState} from 'react'
import {Col, FormControl, InputGroup, Row} from 'react-bootstrap'
import {Autocomplete, GoogleMap, Marker} from '@react-google-maps/api'

const defaultCenter = {lat: 53.544389, lng: -113.49072669}

const libraries = ['places']

export default function Address(props) {
    const [mapCenter, setMapCenter] = useState(defaultCenter)
    const [markerCoords, setMarkerCoords] = useState(null)
    const [autocomplete, setAutocomplete] = useState(null)
    const [clearAutoComplete, setClearAutoComplete] = useState(true)
    const [zoom, setZoom] = useState(10)

    const {readOnly, showAddressSearch} = props

    const {formatted, lat, lng, name, type} = props.address

    useEffect(() => {
        const newCoordinates = {lat: lat ? lat : 53.544389, lng: lng ? lng : -113.49072669}
        setZoom(lat && lng ? 14 : 10)
        if(type != 'Manual')
            setMapCenter(lat && lng ? newCoordinates : defaultCenter)
        setMarkerCoords(lat && lng ? newCoordinates : null)
    }, [lat, lng])

    const handleMapClickEvent = event => {
        if(type === 'Manual') {
            const lat = event.latLng.lat()
            const lng = event.latLng.lng()
            props.handleChange({target: {name: 'addressLat', type: 'text', value: lat}})
            props.handleChange({target: {name: 'addressLng', type: 'text', value: lng}})
            props.handleChange({target: {name: 'placeId', type: 'text', value: `MAN:lat:${lat}:lng:${lng}`}})
        }
        else
            return
    }

    const updateAddress = () => {
        if(!autocomplete)
            return
        const place = autocomplete.getPlace()
        const lat = place.geometry.location.lat()
        const lng = place.geometry.location.lng()

        setZoom(14)
        setMapCenter({lat, lng})
        setMarkerCoords({lat, lng})
        props.handleChange({target: {name: 'addressLat', type: 'text', value: lat}})
        props.handleChange({target: {name: 'addressLng', type: 'text', value: lng}})
        props.handleChange({target: {name: 'placeId', type: 'text', value: place.place_id === '' ? null : place.place_id}})
        props.handleChange({target: {name: 'addressName', type: 'text', value: place.name}})
        props.handleChange({target: {name: 'addressFormatted', type: 'text', value: place.formatted_address}})
        setClearAutoComplete(false)
        setClearAutoComplete(true)
    }

    return (
        <Row className='justify-content-md-center'>
            {(showAddressSearch && clearAutoComplete) &&
                <Col md={11} style={{display: type === 'Search' ? 'block' : 'none'}}>
                    <InputGroup>
                        <InputGroup.Text>Address Search: </InputGroup.Text>
                        <Autocomplete
                            className='form-control autocomplete-wrapper'
                            fields={['geometry', 'name', 'formatted_address', 'place_id']}
                            onChange={console.log}
                            onLoad={setAutocomplete}
                            onPlaceChanged={updateAddress}
                        >
                            <FormControl type='text' readOnly={readOnly}/>
                        </Autocomplete>
                    </InputGroup>
                </Col>
            }
            {type === 'Manual' ?
                <Col md={11} style={{backgroundColor: 'orange'}}>
                    Having trouble finding an address in search? Use "Manual" mode to enter your address by hand, and click on the map to indicate a point for delivery.
                    <br/>
                </Col>
                : null
            }
            <Col md={11}>
                <InputGroup>
                    <InputGroup.Text>Name: </InputGroup.Text>
                    <FormControl 
                        type='text' 
                        name={'addressName'}
                        value={name}
                        onChange={props.handleChange}
                        readOnly={readOnly}
                    />
                </InputGroup>
            </Col>
            <Col md={11}>
                <InputGroup>
                    <InputGroup.Text>Address: </InputGroup.Text>
                    <FormControl
                        type='text'
                        name={'addressFormatted'}
                        value={formatted}
                        onChange={props.handleChange}
                        readOnly={readOnly || type != 'Manual'}
                    />
                </InputGroup>
            </Col>
            <br/>
            <Col md={11}>
                <GoogleMap
                    center={mapCenter}
                    mapContainerStyle={{height: '300px', marginTop: 20}}
                    onClick={handleMapClickEvent}
                    options={{
                        disableDefaultUI: true
                    }}
                    zoom={zoom}
                >
                    {markerCoords && <Marker position={markerCoords}/>}
                </GoogleMap>
            </Col>
        </Row>
    )
}
