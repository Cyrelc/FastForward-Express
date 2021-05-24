import React, {Component} from 'react'
import {Button, Card, Table} from 'react-bootstrap'

import InterlinerModal from './InterlinerModal'

export default class InterlinersTab extends Component {
    constructor() {
        super()
        this.state = {
            interlinerAddressName: '',
            interlinerAddressFormatted: '',
            interlinerAddressLat: '',
            interlinerAddressLng: '',
            interlinerAddressPlaceId: '',
            interlinerId: '',
            interlinerName: '',
            showInterlinerModal: false
        }
        this.handleChanges = this.handleChanges.bind(this)
        this.storeInterliner = this.storeInterliner.bind(this)
        this.toggleInterlinerModal = this.toggleInterlinerModal.bind(this)
    }

    handleChanges(events) {
        if(!Array.isArray(events))
            events = [events]
        var temp = {}
        events.forEach(event => {
            const {name, value, type, checked} = event.target
            temp[name] = type === 'checkbox' ? checked : value
        })
        this.setState(temp)
    }

    storeInterliner() {
        const data = {
            interliner_id: this.state.interlinerId,
            name: this.state.interlinerName,
            address_formatted: this.state.interlinerAddressFormatted,
            address_lat: this.state.interlinerAddressLat,
            address_lng: this.state.interlinerAddressLng,
            address_name: this.state.interlinerAddressName,
            address_place_id: this.state.interlinerAddressPlaceId
        }
        makeAjaxRequest('/interliners/store', 'POST', data, response => {
            this.props.handleChange({target: {name: 'interliners', type: 'object', value: response.interliners}})
            this.setState({showInterlinerModal: false})
        })
    }

    toggleInterlinerModal(interliner = null) {
        this.setState({
            interlinerAddressFormatted: interliner ? interliner.address_formatted : '',
            interlinerAddressLat: interliner ? interliner.address_lat : '',
            interlinerAddressLng: interliner ? interliner.address_lng : '',
            interlinerAddressName: interliner ? interliner.address_name : '',
            interlinerAddressPlaceId: interliner ? interliner.address_place_id : '',
            interlinerId: interliner ? interliner.interliner_id : '',
            interlinerName: interliner ? interliner.interliner_name : '',
            showInterlinerModal: !this.state.showInterlinerModal
        })
    }

    render() {
        return (
            <Card border='dark'>
                <Card.Header><h4 className='text-muted'>Interliners</h4></Card.Header>
                <Card.Body>
                    <Table size='sm'>
                        <thead>
                            <tr>
                                <th><Button variant='success' size='sm' onClick={this.toggleInterlinerModal}><i className='fas fa-plus'></i></Button></th>
                                <th>Interliner ID</th>
                                <th>Interliner Name</th>
                                <th>Address Name</th>
                                <th>Address Formatted</th>
                            </tr>
                        </thead>
                        <tbody>
                            {this.props.interliners.map(interliner =>
                                <tr key={interliner.interliner_id}>
                                    <td><Button variant='warning' onClick={() => this.toggleInterlinerModal(interliner)} size='sm'><i className='fas fa-edit'></i></Button></td>
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
                        name: this.state.interlinerAddressName,
                        formatted: this.state.interlinerAddressFormatted,
                        lat: this.state.interlinerAddressLat,
                        lng: this.state.interlinerAddressLng,
                        placeId: this.state.interlinerAddressPlaceId
                    }}
                    interlinerId={this.state.interlinerId}
                    interlinerName={this.state.interlinerName}

                    handleChanges={this.handleChanges}
                    show={this.state.showInterlinerModal}
                    storeInterliner={this.storeInterliner}
                    toggleModal={this.toggleInterlinerModal}
                />
            </Card>
        )
    }
}
