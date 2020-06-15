import React, {Component} from 'react'
import ReactDom from 'react-dom'
import {Row, Col, InputGroup, Button, Modal, ToastHeader} from 'react-bootstrap'
import * as moment from 'moment/moment'
import DatePicker from 'react-datepicker'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

import Bills from './Bills'
import Driver from './Driver'
import Map from './Map'

export default class Dispatch extends Component {
    constructor() {
        window.moment = moment
        super()
        this.state = {
            bills: [],
            drivers: [],
            endDate: undefined,
            rowInTransit: false,
            setTimeModalBillId: null,
            setTimeModalTime: new Date(),
            setTimeModalField: null,
            setTimeModalView: false,
            startDate: null,
            viewUnassigned: true,
            billColumns: [
                {rowHandle: true, formatter: 'handle', headerSort: false, frozen: true, width: 30, minWidth: 30},
                {
                    title: 'Bill ID',
                    field: 'bill_id',
                    cellDblClick: (event, row) => window.open('/bills/edit/' + row.getData().bill_id)
                },
                {
                    title: 'Pickup',
                    field: 'time_pickup_scheduled',
                    formatter: 'datetimediff',
                    formatterParams: {humanize: true, suffix: true},
                    cellClick: (event, cell) => {this.handleChange({target: {name: 'setTimeModalView', type: 'checkbox', checked: true, value: cell}})}
                },
                {
                    title: 'Delivery',
                    field: 'time_delivery_scheduled',
                    formatter: 'datetimediff',
                    formatterParams: {humanize: true, suffix: true},
                    cellClick: (event, cell) => {this.handleChange({target: {name: 'setTimeModalView', type: 'checkbox', checked: true, value: cell}})}
                },
                {
                    title: 'View',
                    field: null,
                    headerSort: false,
                    formatter: (cell) => {return cell.getRow().getData().view ? '<i class="fas fa-eye"></i>' : '<i class="far fa-eye-slash"></i>'},
                    cellClick: (event, cell) => {this.handleChange({target: {name: 'viewBill', type: 'checkbox', value: cell.getRow().getData().bill_id}})},
                    headerClick: (event, column) => {this.handleChange({target: {name: 'viewDriver', type: 'checkbox', value: column.getTable().element.getAttribute('data-driverid')}})},
                },
                {title: '', field: 'timeUntilPickup', visible: false},
                {title: '', field: 'timeUntilDelivery', visible: false},
                {title: '', field: 'view', visible: false},
            ]
        }
        this.handleChange = this.handleChange.bind(this)
        this.handleStartDateEvent = this.handleStartDateEvent.bind(this)
        this.refreshBills = this.refreshBills.bind(this)
    }

    componentDidMount() {
        fetch('/dispatch/GetDrivers')
        .then(response => {return response.json()})
        .then(data => {return data.map(driver => {return {...driver, view: true}})})
        .then(data => this.setState({drivers: data}, this.handleStartDateEvent({target: {name: 'startDate', type: 'date', value: new Date()}})))

        var timer = setInterval(() => {this.refreshBills()}, 20000)

        const options = {
            broadcaster: 'pusher',
            key: 'c6a722255496d5cc54e4',
            cluster: 'us3',
            forceTLS: true
        }

        const echo = new Echo(options)

        echo.private('dispatch').listen('BillCreated', e => {
            const startDate = moment(this.state.startDate)
            if(moment(e.time_pickup_scheduled.date).isSame(startDate, 'day') || moment(e.time_delivery_scheduled.date).isSame(startDate, 'day')) {
                fetch('/bills/getModel/' + e.bill_id)
                .then(response => {return response.json()})
                .then(data => {
                    const bill = this.formatNewBill(data)
                    const bills = this.state.bills
                    bills[bills.length] = bill
                    this.setState({bills: bills})
                })
            }
        }).listen('BillUpdated', e => {
            console.log('Received bill updated event for bill_id: ' + e.bill_id)
            const startDate = moment(this.state.startDate)
            const bill = this.state.bills.filter(bill => bill.bill_id === e.bill_id)
            const matchesCurrentDate = (moment(e.time_pickup_scheduled.date).isSame(startDate, 'day') || moment(e.time_delivery_scheduled.date).isSame(startDate, 'day'))
            /*
            * Potential cases:
            * 1. Bill is in the list, and something other than date has changed, such as address -> update the bill details
            * 2. Bill is in the list, and the current date NO LONGER matches our current watch date -> remove bill from list
            * 3. Bill is not in list, but has had the date modify to match our filter criteria -> add bill to list
            */
            if(bill[0] && matchesCurrentDate) {
                console.log('Bill found. Date matches. Updating data')
                fetch('/bills/getModel/' + e.bill_id)
                .then(response => {return response.json()})
                .then(data => {
                    const bills = this.state.bills.map(b => {
                        if(b.bill_id === e.bill_id)
                            return this.updateBill(b, data)
                        else
                            return b;
                    })
                    this.setState({bills: bills})
                })
            } else if (bill[0]) {
                console.log('Bill found. Date no longer matches. Removing from view')
                const bills = this.state.bills.filter(bill => bill.bill_id != e.bill_id)
                this.setState({bills: bills})
            } else if (matchesCurrentDate) {
                console.log('Bill not found. Date matches. Adding to view')
                fetch('/bills/getModel/' + e.bill_id)
                .then(response => {return response.json()})
                .then(data => {
                    var bills = this.state.bills
                    bills[bills.length] = this.formatNewBill(data)
                    this.setState({bills: bills})
                })
            }
        })
    }

    formatNewBill(data) {
        return {
            bill_id: data.bill.bill_id,
            pickup_address_lat: data.pickup_address.lat,
            pickup_address_lng: data.pickup_address.lng,
            delivery_address_lat: data.delivery_address.lat,
            delivery_address_lng: data.delivery_address.lng,
            pickup_driver_id: data.bill.pickup_driver_id,
            delivery_driver_id: data.bill.delivery_driver_id,
            time_picked_up: data.bill.time_picked_up,
            time_delivered: data.bill.time_delivered,
            time_pickup_scheduled: data.bill.time_pickup_scheduled,
            time_delivery_scheduled: data.bill.time_delivery_scheduled
        }
    }

    getBackgroundColor(time_action_performed, time_remaining) {
        if(time_action_performed != null)
            return 'lightgreen'
        else if(time_remaining < 0)
            return 'tomato'
        else if(time_remaining < 1200)
            return 'orange'
        else if (time_remaining < 2400)
            return 'gold'
        else
            return 'undefined'
    }

    refreshBills() {
        console.log('refresh bills called. Row in transit: ' + this.state.rowInTransit)
        if(this.state.rowInTransit)
            return
        const bills = this.state.bills.map(bill => {
            const timeUntilPickup = Math.floor(moment(bill.time_pickup_scheduled).diff(moment()) / 1000)
            const timeUntilDelivery = Math.floor(moment(bill.time_delivery_scheduled).diff(moment()) / 1000)
            const pickupBackgroundColor = this.getBackgroundColor(bill.time_picked_up, timeUntilPickup)
            const deliveryBackgroundColor = this.getBackgroundColor(bill.time_delivered, timeUntilDelivery)
            return {
                ...bill,
                view: bill.view === undefined ? true : bill.view,
                timeUntilPickup: timeUntilPickup,
                timeUntilDelivery: timeUntilDelivery,
                pickupBackgroundColor: pickupBackgroundColor,
                deliveryBackgroundColor: deliveryBackgroundColor
            }
        })
        this.setState({bills: bills})
    }

    rowFormatter(row) {
        const data = row.getData()
        const pickupElement = row.getCell('time_pickup_scheduled').getElement()
        const deliveryElement = row.getCell('time_delivery_scheduled').getElement()
        pickupElement.style.backgroundColor = data.pickupBackgroundColor
        deliveryElement.style.backgroundColor = data.deliveryBackgroundColor
    }

    //change handlers

    handleAssignBillEvent(event) {
        const csrfToken = document.head.querySelector("[name~=csrf-token][content]").content
        const {value, driver_id} = event.target

        console.log(value, driver_id)

        const bills = this.state.bills.map(bill => {
            if(bill.bill_id === value)
                return {...bill, pickup_driver_id: driver_id, delivery_driver_id: driver_id}
            else
                return bill
        })

        fetch('/dispatch/assignBillToDriver', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({driver_id: driver_id, bill_id: value})
        }).then(response => {
            if(!response.ok) {
                toastr.clear()
                toastr.error(response.statusText, 'Error', {'timeOut': 0, 'extendedTImeout': 0})
            }
        })

        return {bills: bills}
    }

    handleChange(event) {
        const {name, value, type, checked} = event.target
        var temp = {}
        switch(name) {
            case 'assignBill':
                temp = this.handleAssignBillEvent(event)
                break
            case 'setTimeModalView':
                temp = this.handleSetTimeModalViewEvent(event)
                break
            case 'startDate':
                temp = this.handleStartDateEvent(event)
                break
            case 'viewBill':
                temp = this.handleViewBillEvent(event)
                break
            case 'viewDriver':
                temp = this.handleViewDriverEvent(event)
                break
            case 'setTime':
                temp = this.handleSetTimeEvent(event)
                break;
            default:
                temp[name] = type === 'checkbox' ? checked : value
        }
        console.log(name, temp)
        this.setState(temp)
    }

    handleSetTimeModalViewEvent(event) {
        const {name, value, type, checked} = event.target
        var temp = {setTimeModalView: checked}
        if(checked) {
            const bill_id = value.getRow().getData().bill_id
            const field = value.getField()
            const time = new Date()
            temp = {...temp, setTimeModalBillId: bill_id, setTimeModalField: field, setTimeModalTime: time}
        }
        return temp
    }

    handleSetTimeEvent(event) {
        const csrfToken = document.head.querySelector("[name~=csrf-token][content]").content
        const type = this.state.setTimeModalField === 'time_pickup_scheduled' ? 'pickup' : 'delivery'
        const data = {
            bill_id: this.state.setTimeModalBillId,
            type: type,
            time: this.state.setTimeModalTime.toLocaleString("en-US")
        }
        $.ajax({
            'url': '/dispatch/setBillPickupOrDeliveryTime',
            'type': 'POST',
            'data': data,
            'success': response => {
                toastr.clear()
            },
            'error': response => handleErrorReponse(response)
        })

        var bills = this.state.bills.map(bill => {
            if(bill.bill_id === this.state.setTimeModalBillId)
                if(this.state.setTimeModalField === 'time_pickup_scheduled')
                    return {...bill, time_picked_up: this.state.setTimeModalTime, pickupBackgroundColor: 'lightgreen'}
                else
                    return {...bill, time_delivered: this.state.setTimeModalTime, deliveryBackgroundColor: 'lightgreen'}
            else
                return bill
        })
        //If a bill has been picked up and delivered, remove it from our view it no longer belongs there
        bills = bills.filter(bill => {return (bill.time_picked_up == null || bill.time_delivered == null || bill.pickup_driver_id == null || bill.delivery_driver_id == null)})
        return {bills: bills, setTimeModalView: false}
    }

    handleStartDateEvent(event) {
        const {name, value} = event.target
        this.setState({startDate: value}, () => {
            fetch('/bills/buildTable?filter[dispatch]&filter[date_between]=' + moment(this.state.startDate).format('YYYY-MM-DD') + ',' + moment(this.state.startDate).add(1, 'd').format('YYYY-MM-DD'))
            .then(response => {return response.json()})
            .then(data => {this.setState({bills: data}, this.refreshBills)})
        })
    }

    handleViewBillEvent(event) {
        const {name, value, type, checked} = event.target
        const bills = this.state.bills.map(bill => {
            if(bill.bill_id === value)
                return {...bill, view: !bill.view}
            else
                return bill
        })

        return {bills: bills}
    }

    handleViewDriverEvent(event) {
        const {name, value, type, checked} = event.target
        console.log(value)
        var viewUnassigned = value === null ? !this.state.viewUnassigned : this.state.viewUnassigned
        var view = viewUnassigned
        const drivers = this.state.drivers.map(driver => {
            if(driver.employee_id == value) {
                view = !driver.view
                return {...driver, view: !driver.view}
            }
            else
                return driver
        })
        const bills = this.state.bills.map(bill => {
            if(bill.pickup_driver_id == value || bill.delivery_driver_id == value)
                return {...bill, view: view}
            else
                return bill
        })
        return {drivers: drivers, bills: bills, viewUnassigned: viewUnassigned}
    }

    render() {
        return (
            <span>
                <Row>
                    <Col md={9}>
                        <Map
                            bills={this.state.bills}
                        />
                    </Col>
                    <hr/><hr/>
                    <Col md={3}>
                        <Row>
                            <Col md={11} className='text-center'>
                                <InputGroup>
                                    <InputGroup.Prepend>
                                        <InputGroup.Text>Date</InputGroup.Text>
                                    </InputGroup.Prepend>
                                    <DatePicker
                                        dateFormat='MMMM d, yyyy'
                                        className='form-control'
                                        onChange={value => this.handleChange({target: {name: 'startDate', type: 'datetime', value: value}})}
                                        selected={this.state.startDate}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                        <Row>
                            <Col md={12} className='text-center'>
                                <h4 className='text-muted'>New Bills</h4>
                            </Col>
                        </Row>
                        <Row style={{height: '25vh', overflowY: 'scroll'}}>
                            <Col md={12}>
                                <Bills
                                    bills={this.state.bills}
                                    billColumns={this.state.billColumns}
                                    drivers={this.state.drivers}
                                    handleChange={this.handleChange}

                                    rowFormatter={this.rowFormatter}
                                />
                            </Col>
                        </Row>
                        <Row>
                            <Col md={12} className={'text-center'}>
                                <h4 className='text-muted'>Drivers</h4>
                            </Col>
                        </Row>
                        <Row style={{maxHeight: '55vh', overflowY: 'scroll'}}>
                            <Col md={12}>
                                {this.state.drivers.map(driver =>
                                    <Driver
                                        key={driver.employee_id}
                                        billColumns={this.state.billColumns}
                                        bills={this.state.bills}
                                        driver={driver}

                                        handleChange={this.handleChange}
                                        rowFormatter={this.rowFormatter}
                                    />
                                )}
                            </Col>
                        </Row>
                    </Col>
                </Row>
                <Modal show={this.state.setTimeModalView} onHide={() => this.handleChange({target: {name: 'setTimeModalView', type: 'checkbox', checked: false}})}>
                    <Modal.Header closeButton>
                        <Modal.Title>Set actual {this.state.setTimeModalField === 'time_pickup_scheduled' ? 'pickup' : 'delivery'} time for bill {this.state.setTimeModalBillId}</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <InputGroup>
                            <DatePicker
                                showTimeSelect
                                timeIntervals={5}
                                dateFormat='MMMM d, yyyy h:mm aa'
                                className='form-control'
                                selected={this.state.setTimeModalTime}
                                onChange={value => this.handleChange({target: {name: 'setTimeModalTime', type: 'datetime', value: value}})}
                            />
                            <InputGroup.Append>
                                <Button onClick={() => this.handleChange({target: {name: 'setTime'}})}>Set</Button>
                            </InputGroup.Append>
                        </InputGroup>
                    </Modal.Body>
                </Modal>
            </span>
        )
    }

    updateBill(old, data) {
        return {
            ...old,
            pickup_address_lat: data.pickup_address.lat,
            pickup_address_lng: data.pickup_address.lng,
            delivery_address_lat: data.delivery_address.lat,
            delivery_address_lng: data.delivery_address.lng,
            pickup_driver_id: data.bill.pickup_driver_id,
            delivery_driver_id: data.bill.delivery_driver_id,
            time_picked_up: data.bill.time_picked_up,
            time_delivered: data.bill.time_delivered,
            time_pickup_scheduled: data.bill.time_pickup_scheduled,
            time_delivery_scheduled: data.bill.time_delivery_scheduled
        }
    }
}

ReactDom.render(<Dispatch />, document.getElementById('dispatch'))
