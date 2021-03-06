import React from 'react'
import {Button, Col, FormControl, InputGroup, Row} from 'react-bootstrap'

export default class Address extends React.Component {
    constructor() {
        super()
        this.state = {
            map: '',
            search: '',
            marker: '',
            manual: false,
            loading: true
        }
        this.handleChange = this.handleChange.bind(this)
        this.handleMapClickEvent = this.handleMapClickEvent.bind(this)
    }

    componentDidMount() {
        const map = new google.maps.Map(document.getElementById(this.props.id + '-map'), {disableDefaultUI: true});
        const marker = new google.maps.Marker({position: map.getCenter()})

        const search = new google.maps.places.Autocomplete(document.getElementById(this.props.id + '-search'));
        search.setFields(['geometry', 'name', 'formatted_address', 'place_id']);
        search.addListener('place_changed', () => this.updateAddress());
        map.addListener('click', event => this.handleMapClickEvent(event))

        this.setState({map: map, search: search, marker: marker, loading: false}, () => this.drawMap());
    }

    componentDidUpdate(prevProps) {
        if(this.props.address.lat !== prevProps.address.lat || this.props.address.lng != prevProps.address.lng) {
            if(this.props.address.lat === '' && this.props.address.lng === '')
                $('#' + this.props.id + '-search').val('')
            this.drawMap()
        }
        if(prevProps.address.type !== this.props.address.type)
            if(this.props.address.type === 'Address' && this.props.address.formatted !== '') {
                this.props.handleChanges({target: {name: this.props.id + 'AccountId', value: ''}})
                this.updateAddress()
            }
            else if(this.props.address.type === 'Account') {
                $('#' + this.props.id + '-search').val('')
            }
    }

    drawMap() {
        const zoom = (this.props.address.lat === '' && this.props.address.lng === '') ? 10 : 15
        const lat = this.props.address.lat === '' ? 53.544389 : this.props.address.lat
        const lng = this.props.address.lng === '' ? -113.49072669 : this.props.address.lng
        const position = new google.maps.LatLng(lat, lng);

        this.state.map.setCenter(position);
        this.state.map.setZoom(zoom);
        this.state.marker.setPosition(position);
        this.state.marker.setMap((this.props.address.lat === '' && this.props.address.lng === '') ? null : this.state.map);
    }

    handleChange(event) {
        const {name, type, value, checked} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value})
    }

    handleLegacyAddress() {
        console.log('lat/lng data not found, searching based on formatted address');
        var request = {query: this.props.address.formatted, fields:['id']}
        const service = new google.maps.places.PlacesService(document.createElement('div'));
        service.textSearch(request, (results, status) => {
            if(status === google.maps.places.PlacesServiceStatus.OK && results.length === 1) {
                request = {placeId: results[0].place_id, fields: ['name', 'address_components', 'geometry', 'id', 'formatted_address']};
                service.getDetails(request, (results, status) => {
                    if(status === google.maps.places.PlacesServiceStatus.OK) {
                        this.state.search.set('place', results);
                        $('#' + this.props.id + '-search').val(results.formatted_address);
                        this.updateAddress(results);
                    }
                })
            }
        })
    }

    handleMapClickEvent(event) {
        if(this.state.manual) {
            const lat = event.latLng.lat()
            const lng = event.latLng.lng()
            this.props.handleChanges([
                {target: {name: this.props.id + 'AddressLat', type: 'text', value: lat}},
                {target: {name: this.props.id + 'AddressLng', type: 'text', value: lng}},
                {target: {name: this.props.id + 'AddressPlaceId', type: 'text', value: 'MAN:lat:' + lat + ':lng:' + lng}}
            ])
        }
        else
            return
    }

    updateAddress() {
        const place = this.state.search.getPlace();
        if(place === undefined)
            return

        const events = [
            {target: {name: this.props.id + 'AddressLat', type: 'text', value: place.geometry.location.lat()}},
            {target: {name: this.props.id + 'AddressLng', type: 'text', value: place.geometry.location.lng()}},
            {target: {name: this.props.id + 'AddressPlaceId', type: 'text', value: place.place_id === '' ? null : place.place_id}},
            {target: {name: this.props.id + 'AddressName', type: 'text', value: place.name}},
            {target: {name: this.props.id + 'AddressFormatted', type: 'text', value: place.formatted_address}}
        ];
        this.props.handleChanges(events);
    }

    render() {
        return (
            <Row className='justify-content-md-center'>
                {this.props.showAddressSearch && 
                <Col md={11} style={{display: this.props.address.type === 'Address' ? 'block' : 'none'}}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Address Search: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl 
                            type='text' 
                            id={this.props.id + '-search'}
                            name='formatted'
                            readOnly={this.props.readOnly}
                        />
                        <InputGroup.Append>
                            <Button variant={this.state.manual ? 'success' : 'secondary'} onClick={() => this.handleChange({target: {name: 'manual', type: 'checkbox', checked: !this.state.manual}})} disabled={this.props.readOnly}>Manual</Button>
                        </InputGroup.Append>
                        <InputGroup.Append>
                            <InputGroup.Text><i className='fas fa-question-circle' title='Having trouble finding an address in search? Enter "Manual" mode to enter your address by hand, and click on the map to indicate a point for delivery.'></i></InputGroup.Text>
                        </InputGroup.Append>
                    </InputGroup>
                </Col>
                }
                <Col md={11}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Name: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl 
                            type='text' 
                            name={this.props.id + 'AddressName'}
                            value={this.props.address.name}
                            onChange={event => this.props.handleChanges(event)}
                            readOnly={this.props.readOnly}
                        />
                    </InputGroup>
                </Col>
                <Col md={11}>
                    <InputGroup>
                        <InputGroup.Prepend>
                            <InputGroup.Text>Address: </InputGroup.Text>
                        </InputGroup.Prepend>
                        <FormControl
                            type='text'
                            name={this.props.id + 'AddressFormatted'}
                            value={this.props.address.formatted}
                            onChange={event => this.props.handleChanges(event)}
                            readOnly={ this.props.readOnly || !this.state.manual }
                        />
                    </InputGroup>
                </Col>
                <br/>
                <Col id={this.props.id + '-map'} style={{height: 300, marginTop: 20}} md={11}></Col>
            </Row>
        )
    }
}
