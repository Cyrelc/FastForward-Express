import React, {useEffect, useState} from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'
import {Map as GoogleMap, AdvancedMarker, LoadScript} from '@vis.gl/react-google-maps'
import Select from 'react-select'

const defaultCenter = {lat: 53.544389, lng: -113.49072669}

const libraries = ['places']

export default function Address(props) {
    const [autocomplete, setAutocomplete] = useState(null)
    const [clearAutoComplete, setClearAutoComplete] = useState(true)
    const [mapCenter, setMapCenter] = useState(defaultCenter)
    const [markerCoords, setMarkerCoords] = useState(null)
    const [zoom, setZoom] = useState(10)

    const {accounts, readOnly, showAddressSearch} = props

    const {account, formatted, isMall, lat, lng, name, type, referenceValue} = props.data

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
        <Card>
            <Card.Header>
                <Row>
                    <Col>
                        <Card.Title style={{display: 'inline'}}>
                            <h5 style={{display: 'inline'}}>{props.header}</h5>
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
                                onChange={event => props.handleChange({target: {name: 'isMall', value: !isMall}})}
                            />
                        </Col>
                    }
                    <Col className='justify-content-end'>
                        <ToggleButtonGroup
                            type='radio'
                            name={`${props.id}AddressType`}
                            value={type}
                            onChange={value => props.handleChange({target: {name: 'addressType', value}})}
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
                            {props.accounts?.length &&
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
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row className='justify-content-md-center'>
                    {(showAddressSearch && clearAutoComplete) &&
                        <Col md={12} style={{display: type === 'Search' ? 'block' : 'none'}}>
                            <InputGroup>
                                <InputGroup.Text>Search: </InputGroup.Text>
                                {/* <Autocomplete
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
                                </Autocomplete> */}
                            </InputGroup>
                        </Col>
                    }
                    {type === 'Manual' ?
                        <Col md={12} style={{backgroundColor: 'orange', fontSize: 12}}>
                            Having trouble finding an address in search? Use "Manual" mode to enter your address by hand, and click on the map to indicate a point for delivery.
                            <br/>
                        </Col>
                        : null
                    }
                    {(props.accounts?.length && type === 'Account') &&
                        <Col md={12}>
                            <InputGroup style={{width: '100%'}}>
                                <InputGroup.Text>Select Account: </InputGroup.Text>
                                <Select
                                    options={accounts}
                                    isSearchable
                                    value={account}
                                    onChange={props.handleAccountChange}
                                    isDisabled={readOnly}
                                    style={{flex: 1}}
                                />
                            </InputGroup>
                        </Col>
                    }
                    {(props.accounts?.length && type === 'Account' && account?.custom_field) &&
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
                                onChange={props.handleChange}
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
                                onChange={props.handleChange}
                                readOnly={readOnly || type != 'Manual'}
                            />
                        </InputGroup>
                    </Col>
                    <br/>
                    <Col md={12}>
                        <LoadScript>
                            <GoogleMap
                                center={mapCenter}
                                loading='async'
                                mapContainerStyle={{height: '250px', marginTop: 20}}
                                onClick={handleMapClickEvent}
                                options={{
                                    disableDefaultUI: true
                                }}
                                zoom={zoom}
                            >
                                {markerCoords && <AdvancedMarker position={markerCoords}/>}
                            </GoogleMap>
                        </LoadScript>
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
