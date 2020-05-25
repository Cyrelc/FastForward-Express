import React from 'react'
import {Row, Col, Card} from 'react-bootstrap'
import SnazzyInfoWindow from 'snazzy-info-window'

export default class Address extends React.Component {
    constructor() {
        super()
        this.state = {
            map: '',
            markers: {},
        }
    }

    componentDidMount() {
        const center = new google.maps.LatLng(53.544389, -113.49072669)
        const map = new google.maps.Map(document.getElementById('map'), {disableDefaultUI: true, center: center, zoom: 12});
        this.setState({map: map})
    }

    componentDidUpdate(prevProps) {
        if (this.props === prevProps)
            return
        var markers = []
        this.props.bills.forEach(bill => {
            const pickup = this.createInfoWindow(new google.maps.LatLng(bill.pickup_address_lat, bill.pickup_address_lng), bill.bill_id + ' <i class="fas fa-truck-loading"></i>', bill.pickupBackgroundColor)
            const delivery = this.createInfoWindow(new google.maps.LatLng(bill.delivery_address_lat, bill.delivery_address_lng), bill.bill_id + ' <i class="fas fa-box-open"></i>', bill.deliveryBackgroundColor)
            markers[bill.bill_id] = {pickup: pickup, delivery: delivery}
            //decide whether the infowindow should be open or closed
            if(bill.view || bill.timeUntilPickup < 0)
                markers[bill.bill_id].pickup.open()
            else
                markers[bill.bill_id].pickup.close()

            if(bill.view || bill.timeUntilDelivery < 0)
                markers[bill.bill_id].delivery.open()
            else
                markers[bill.bill_id].delivery.close()
        })
        for(let [key, value] of Object.entries(this.state.markers)) {
            value.pickup.destroy()
            value.delivery.destroy()
        }
        this.setState({markers: markers})
    }

    createInfoWindow(position, content, backgroundColor) {
        return new SnazzyInfoWindow({
            map: this.state.map,
            content: content,
            backgroundColor: backgroundColor,
            position: position,
            showCloseButton: false,
            panOnOpen: false,
            padding: '7px',
            borderRadius: '10px 10px'
        })
    }

    render() {
        return(
            <Card border='dark'>
                <Card.Body>
                    <div id='map' style={{height: '85vh'}}></div>
                </Card.Body>
            </Card>
        )
    }
}
  