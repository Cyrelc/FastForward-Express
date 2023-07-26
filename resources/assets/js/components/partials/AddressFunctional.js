import React, {useEffect, useState} from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'
import {Autocomplete, GoogleMap, LoadScript, Marker} from '@react-google-maps/api'
import Select from 'react-select'

const defaultCenter = {lat: 53.544389, lng: -113.49072669}

const libraries = ['places']

export default function Address(props) {
    const [autocomplete, setAutocomplete] = useState(null)
    const [clearAutoComplete, setClearAutoComplete] = useState(true)
    const [mapCenter, setMapCenter] = useState(defaultCenter)
    const [markerCoords, setMarkerCoords] = useState(null)
    const [zoom, setZoom] = useState(10)

    const {accounts, readOnly, showAddressSearch, usePickupDelivery = false} = props

    // const {account, referenceValue} = props.data
    const {
        // account,
        formatted,
        isMall,
        lat,
        lng,
        name,
        type,
        setFormatted,
        setLat,
        setLng,
        setIsMall,
        setName,
        setPlaceId,
        setType
        // referenceValue
    } = props.address

    useEffect(() => {
        const newCoordinates = {lat: lat ? lat : 53.544389, lng: lng ? lng : -113.49072669}
        setZoom(lat && lng ? 14 : 10)
        if(type != 'Manual')
            setMapCenter(lat && lng ? newCoordinates : defaultCenter)
        setMarkerCoords(lat && lng ? newCoordinates : null)
    }, [lat, lng])

    const resetAutoComplete = () => {
        setClearAutoComplete(false)
        setClearAutoComplete(true)
    }

    const handleMapClickEvent = event => {
        if(type === 'Manual') {
            const lat = event.latLng.lat()
            const lng = event.latLng.lng()
            setLat(lat)
            setLng(lng)
            setPlaceId(`MAN:lat:${lat}:lng:${lng}`)
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
        setLat(lat)
        setLng(lng)
        setPlaceId(place.place_id ?? null)
        setName(place.name)
        setFormatted(place.formatted_address)
        resetAutoComplete()
    }

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col>
                        <Card.Title>
                            <h4>{props.header}</h4>
                        </Card.Title>
                    </Col>
                    {props.useIsMall &&
                        <Col>
                            <FormCheck
                                name='isMall'
                                label='Location In Mall'
                                value={isMall}
                                checked={isMall}
                                disabled={readOnly}
                                onChange={event => setIsMall(!isMall)}
                            />
                        </Col>
                    }
                    <Col md='auto' className='ml-auto'>
                        <InputGroup style={{paddingTop: 0}}>
                            <InputGroup.Text>Type: </InputGroup.Text>
                            <ToggleButtonGroup
                                type='radio'
                                name={`${props.id}AddressType`}
                                value={type}
                                onChange={setType}
                                disabled={readOnly}
                            >
                                <ToggleButton
                                    id={`${props.id}.address.type.search`}
                                    value='Search'
                                    key='Search'
                                    variant='outline-secondary'
                                    disabled={readOnly}
                                    size='sm'
                                >Search</ToggleButton>
                                {accounts?.length &&
                                    <ToggleButton
                                        id={`${props.id}.address.type.account`}
                                        value='Account'
                                        key='Account'
                                        variant='outline-secondary'
                                        disabled={readOnly}
                                        size='sm'
                                    >Account</ToggleButton>
                                }
                                <ToggleButton
                                    id={`${props.id}.address.type.manual`}
                                    value='Manual'
                                    key='Manual'
                                    variant='outline-secondary'
                                    disabled={readOnly}
                                    size='sm'
                                >Manual</ToggleButton>
                            </ToggleButtonGroup>
                        </InputGroup>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row className='justify-content-md-center'>
                    {(showAddressSearch && clearAutoComplete) &&
                        // <LoadScript
                        //     googleMapsApiKey={process.env.MIX_APP_PLACES_API_KEY}
                        //     libraries={['places', 'drawing', 'geometry']}
                        // >
                            <Col md={12} style={{display: type === 'Search' ? 'block' : 'none'}}>
                                <InputGroup>
                                    <InputGroup.Text>Search: </InputGroup.Text>
                                    <Autocomplete
                                        bounds={{north: mapCenter.lat + 1, south: mapCenter.lat - 1, east: mapCenter.lng + 1, west: mapCenter.lng - 1}}
                                        className='form-control autocomplete-wrapper'
                                        fields={['geometry', 'name', 'formatted_address', 'place_id']}
                                        location={mapCenter}
                                        onChange={console.log}
                                        onLoad={setAutocomplete}
                                        onPlaceChanged={updateAddress}
                                        radius={100}
                                    >
                                        <FormControl type='text' readOnly={readOnly}/>
                                    </Autocomplete>
                                </InputGroup>
                            </Col>
                        // </LoadScript>
                    }
                    {type === 'Manual' ?
                        <Col md={12} style={{backgroundColor: 'orange', fontSize: 12}}>
                            Having trouble finding an address in search? Use "Manual" mode to enter your address by hand, and click on the map to indicate a point for delivery.
                            <br/>
                        </Col>
                        : null
                    }
                    {(accounts?.length && type === 'Account') &&
                        <Col md={12}>
                            <InputGroup>
                                <InputGroup.Text>Select Account: </InputGroup.Text>
                                <Select
                                    options={accounts}
                                    isSearchable
                                    value={account}
                                    onChange={props.handleAccountChange}
                                    isDisabled={readOnly}
                                />
                            </InputGroup>
                        </Col>
                    }
                    {(accounts?.length && type === 'Account' && account?.custom_field) &&
                        <Col md={12}>
                            <InputGroup>
                                <InputGroup.Text>{props.data?.account?.custom_field}</InputGroup.Text>
                                <FormControl
                                    name={`${props.id}ReferenceValue`}
                                    value={referenceValue}
                                    onChange={event => props.handleReferenceValueChange(account, referenceValue, event.target.value)}
                                    readOnly={readOnly}
                                />
                            </InputGroup>
                        </Col>
                    }
                    <Col md={12}>
                        <InputGroup>
                            <InputGroup.Text>Name: </InputGroup.Text>
                            <FormControl
                                type='text'
                                name={'addressName'}
                                value={name}
                                onChange={event => setName(event.target.value)}
                                readOnly={readOnly}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={12}>
                        <InputGroup>
                            <InputGroup.Text>Address: </InputGroup.Text>
                            <FormControl
                                type='text'
                                name={'addressFormatted'}
                                value={formatted}
                                onChange={event => setFormatted(event.target.value)}
                                readOnly={readOnly || type != 'Manual'}
                            />
                        </InputGroup>
                    </Col>
                    <br/>
                    <Col md={12}>
                        {/* Disabling until the entire react component can be switched to GoogleMapAPI library as this interferes */}
                        {/* <LoadScript
                            googleMapsApiKey={process.env.MIX_APP_PLACES_API_KEY}
                            libraries={['places', 'drawing', 'geometry']}
                        > */}
                            <GoogleMap
                                center={mapCenter}
                                mapContainerStyle={{height: '250px', marginTop: 20}}
                                onClick={handleMapClickEvent}
                                options={{
                                    disableDefaultUI: true
                                }}
                                zoom={zoom}
                            >
                                {markerCoords && <Marker position={markerCoords}/>}
                            </GoogleMap>
                        {/* </LoadScript> */}
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
