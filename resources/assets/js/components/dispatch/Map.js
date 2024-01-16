import React, {Fragment} from 'react'
import {Badge, Card} from 'react-bootstrap'
import {GoogleMap, OverlayView} from '@react-google-maps/api'
import {DateTime} from 'luxon'

const defaultCenter = {lat: 53.544389, lng: -113.49072669}

export default function Map(props) {
    const {bills} = props

    return(
        <Card border='dark'>
            <Card.Body>
                <GoogleMap
                    center={defaultCenter}
                    mapContainerStyle={{height: '85vh', width: '100%'}}
                    options={{disableDefaultUI: true}}
                    zoom={12}
                >
                {bills.filter(bill => bill.view).map(bill =>
                    <Fragment>
                        <OverlayView
                            key={`${bill.bill_id}-pickup`}
                            mapPaneName='markerLayer'
                            position={{lat: bill.pickup_address_lat, lng: bill.pickup_address_lng}}
                        >
                            <Badge bg={props.getBackgroundColour(DateTime.fromSQL(bill.time_pickup_scheduled).diffNow('minutes').minutes, bill.time_picked_up)} style={{fontSize: 12}} text='dark'>
                                <i className='fas fa-arrow-circle-up'></i> {bill.bill_id}
                            </Badge>
                        </OverlayView>
                        <OverlayView
                            key={`${bill.bill_id}-delivery`}
                            mapPaneName='markerLayer'
                            position={{lat: bill.delivery_address_lat, lng: bill.delivery_address_lng}}
                        >
                            <Badge bg={props.getBackgroundColour(DateTime.fromSQL(bill.time_delivery_scheduled).diffNow('minutes').minutes, bill.time_delivered)} style={{fontSize: 12}} text='dark'>
                                <i className='fas fa-arrow-circle-down'></i> {bill.bill_id}
                            </Badge>
                        </OverlayView>
                    </Fragment>
                )}
                </GoogleMap>
            </Card.Body>
        </Card>
    )
}
