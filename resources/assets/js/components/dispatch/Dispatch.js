import React, {useEffect, useRef, useState} from 'react'
import {Button, Card, Col, InputGroup, Modal, Row} from 'react-bootstrap'
import {DateTime} from 'luxon'
import DatePicker from 'react-datepicker'
import {ReactTabulator, reactFormatter} from 'react-tabulator'
import Countdown from 'react-countdown'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

import Map from './Map'

const durationRenderer = ({days, hours, minutes}, cell) => {
    const data = cell.getRow().getData()
    const field = cell.getField()
    const actualTime = field === 'time_pickup_scheduled' ? data.time_picked_up : data.time_delivered
    const scheduledTime = DateTime.fromJSDate(cell.getValue())
    const timeRemaining = scheduledTime.diffNow('minutes')

    if(!!actualTime)
        return <span>Done <i className='far fa-check-square'></i></span>
    else {
        return <span>{`${timeRemaining < 0 ? '- ' : ''} ${days > 0 ? days + 'd,' : ''} ${hours > 0 ? hours +'h,' : ''} ${minutes}m`}</span>
    }
}

function DurationFormatter(props) {
    return (
        <Countdown
            date={props.cell.getValue()}
            intervalDelay={60000}
            overtime={true}
            renderer={stuff => durationRenderer(stuff, props.cell)}
        />
    )
}

const formatBill = data => {
    return {
        bill_id: data.bill.bill_id,
        pickup_address_lat: data.pickup_address.lat,
        pickup_address_lng: data.pickup_address.lng,
        delivery_address_lat: data.delivery_address.lat,
        delivery_address_lng: data.delivery_address.lng,
        pickup_driver_id: data.bill.pickup_driver_id,
        delivery_driver_id: data.bill.delivery_driver_id,
        time_picked_up: Date.parse(data.bill.time_picked_up),
        time_delivered: Date.parse(data.bill.time_delivered),
        time_pickup_scheduled: Date.parse(data.bill.time_pickup_scheduled),
        time_delivery_scheduled: Date.parse(data.bill.time_delivery_scheduled),
        view: true
    }
}

const getBackgroundColour = (timeRemaining, actualTime) => {
    if(actualTime != null)
        return 'lightgreen'
    else {
        if(timeRemaining < 0)
            return 'tomato'
        else if(timeRemaining < 60)
            return 'orange'
        else if (timeRemaining < 120)
            return 'gold'
    }
    return 'gainsboro'
}

const paintBills = bills => {
    return bills.map(bill => {
        const timeUntilPickup = DateTime.fromJSDate(bill.time_pickup_scheduled).diffNow('minutes').minutes
        const timeUntilDelivery = DateTime.fromJSDate(bill.time_delivery_scheduled).diffNow('minutes').minutes
        return {
            ...bill,
            pickupBackgroundColour: getBackgroundColour(timeUntilPickup, bill.time_picked_up),
            deliveryBackgroundColour: getBackgroundColour(timeUntilDelivery, bill.time_delivered)
        }
    })
}

const rowFormatter = row => {
    const data = row.getData()
    const pickupElement = row.getCell('time_pickup_scheduled').getElement()
    const deliveryElement = row.getCell('time_delivery_scheduled').getElement()
    pickupElement.style.backgroundColor = data.pickupBackgroundColour
    deliveryElement.style.backgroundColor = data.deliveryBackgroundColour
}

export default function Dispatch(props) {
    const [billDate, setBillDate] = useState(new Date())
    const [bills, setBills] = useState([])
    const [drivers, setDrivers] = useState([])
    const [pendingBillEvents, setPendingBillEvents] = useState(new Array())
    const [rowInTransit, setRowInTransit] = useState(false)

    const [showTimeModal, setShowTimeModal] = useState(false)
    const [timeModalField, setTimeModalField] = useState('')
    const [timeModalActualTime, setTimeModalActualTime] = useState(new Date())
    const [timeModalBillId, setTimeModalBillId] = useState(null)

    const handleBillEventRef = useRef()
    handleBillEventRef.current = billEvent => {
        const currentDate = DateTime.fromJSDate(billDate)
        const timePickupScheduled = DateTime.fromSQL(billEvent.time_pickup_scheduled)
        const timeDeliveryScheduled = DateTime.fromSQL(billEvent.time_delivery_scheduled)
        const existingBill = bills.find(bill => bill.bill_id === billEvent.bill_id)
        const matchesCurrentDate = currentDate.hasSame(timePickupScheduled, 'day') || currentDate.hasSame(timeDeliveryScheduled, 'day')
        // Potential cases:
        // 1. Bill is in the list, and the current date NO LONGER matches our current watch date -> remove bill from list
        // 2. If bill matches our date insert/update functionality will be handled by the pending bills logic
        // first instance is the only one where we don't require an ajax call for more information, that's why it's broken out this way
        if(existingBill && !matchesCurrentDate) {
            setPendingBillEvents(pendingBillEvents => pendingBillEvents.concat([{type: 'remove', bill: billEvent}]))
        } else if (matchesCurrentDate) {
            makeAjaxRequest(`/bills/${billEvent.bill_id}`, 'GET', null, response => {
                const data = JSON.parse(response)
                const pendingBill = formatBill(data)
                if(existingBill) {
                    setPendingBillEvents(pendingBillEvents => pendingBillEvents.concat([{type: 'update', bill: pendingBill}]))
                } else {
                    setPendingBillEvents(pendingBillEvents => pendingBillEvents.concat([{type: 'add', bill: pendingBill}]))
                }
            })
        }
    }

    useEffect(() => {
        makeAjaxRequest('/dispatch/getDrivers', 'GET', null, response => {
            response = JSON.parse(response)
            setDrivers(Object.values(response).map(driver => {return {...driver, view: true}}))
        })

        window.echo = new Echo({
            broadcaster: 'pusher',
            key: 'c6a722255496d5cc54e4',
            cluster: 'us3',
            forceTLS: false,
            // enable when using laravel-websockets
            // wsHost: window.location.hostname,
            // wsPort: 6001,
            // //enable when using SSL
            // encrypted: true,
        })

        window.echo.private('dispatch')
            .listen('BillUpdated', event => handleBillEventRef.current(event))
            .listen('BillCreated', event => handleBillEventRef.current(event))

        const interval = setInterval(() => {
            if(!rowInTransit)
                setBills(bills => paintBills(bills))
        }, 60000)
    }, [])

    useEffect(() => {
        const date = DateTime.fromJSDate(billDate).toFormat('yyyy-MM-dd')
        makeAjaxRequest(`/dispatch/getBills?filter[dispatch]=true&filter[time_pickup_scheduled]=${date},${date}`, 'GET', null, response => {
            response = JSON.parse(response)
            const bills = response.map(bill => {
                return {...bill,
                    view: bill.view === undefined ? true : bill.view,
                    time_pickup_scheduled: Date.parse(bill.time_pickup_scheduled),
                    time_delivery_scheduled: Date.parse(bill.time_delivery_scheduled)
                }
            })
            setBills(paintBills(bills))
        })
    }, [billDate])

    useEffect(() => {
        if(!rowInTransit && pendingBillEvents.length) {
            pendingBillEvents.forEach(event => {
                if(event.type === 'remove')
                    setBills(bills => paintBills(bills.filter(bill => bill.bill_id != event.bill.bill_id)))
                else if(event.type === 'add')
                    setBills(bills => paintBills(bills.concat([event.bill])))
                else if(event.type === 'update')
                    setBills(bills => paintBills(bills.map(bill => {
                        if(bill.bill_id === event.bill.bill_id)
                            return event.bill
                        return bill
                    })))
            })
            setPendingBillEvents(new Array())
        }
    }, [pendingBillEvents, rowInTransit])

    const assignBill = (bill_id, employee_id = null) => {
        const data = {bill_id, employee_id}
        makeAjaxRequest('/dispatch/assignBillToDriver', 'POST', data, response => {
            toastr.clear()
        })
    }

    const billColumns = [
        {rowHandle: true, formatter: 'handle', headerSort: false, frozen: true, width: 30, minWidth: 30},
        {title: 'Bill ID', field: 'bill_id', cellDblClick: (event, cell) => window.open(`/app/bills/${cell.getValue()}`)},
        {
            cellClick: (event, cell) => setTimeModalView(cell),
            field: 'time_pickup_scheduled',
            formatter: reactFormatter(<DurationFormatter/>),
            title: 'Pickup',
            hozAlign: 'right'
        },
        {
            cellClick: (event, cell) => setTimeModalView(cell),
            title: 'Delivery',
            field: 'time_delivery_scheduled',
            formatter: reactFormatter(<DurationFormatter/>),
            hozAlign: 'right',
        },
        {
            title: 'View',
            field: null,
            headerSort: false,
            formatter: (cell) => {return cell.getRow().getData().view ? '<i class="fas fa-eye"></i>' : '<i class="far fa-eye-slash"></i>'},
            width: 60,
            cellClick: (event, cell) => toggleBillView(cell),
            headerClick: (event, column) => toggleTableBillView(column.getTable().element.getAttribute('data-employeeid'))
            // headerClick: (event, column) => {this.handleChange({target: {name: 'viewDriver', type: 'checkbox', value: column.getTable().element.getAttribute('data-employeeid')}})},
        },
        {title: 'Picked Up', field: 'time_picked_up', visible: false},
        {title: 'Delivered', field: 'time_delivered', visible: false}
    ]

    const setTimeModalView = cell => {
        setTimeModalActualTime(new Date())
        setTimeModalField(cell.getField() === 'time_pickup_scheduled' ? 'pickup' : 'delivery')
        setTimeModalBillId(cell.getRow().getData().bill_id)
        setShowTimeModal(true)
    }

    const submitActualTime = () => {
        const data = {bill_id: timeModalBillId, type: timeModalField, time: timeModalActualTime.toLocaleString('en-US')}
        makeAjaxRequest('/dispatch/setBillPickupOrDeliveryTime', 'POST', data, response => {
            toastr.clear()
            const refreshedBills = bills.map(bill => {
                if(bill.bill_id === timeModalBillId) {
                    if(timeModalField === 'pickup')
                        return {...bill, time_picked_up: timeModalActualTime, pickupBackgroundColour: 'lightgreen'}
                    return {...bill, time_delivered: timeModalActualTime, deliveryBackgroundColour: 'lightgreen'}
                }
                return bill
            })
            setBills(paintBills(refreshedBills.filter(bill => (bill.time_picked_up == null || bill.time_delivered == null || bill.delivery_driver == null || bill.pickup_driver == null))))
        })
        setShowTimeModal(false)
    }

    const toggleBillView = cell => {
        const data = cell.getRow().getData()
        setBills(paintBills(bills.map(bill => {
            const isPastPickupTime = DateTime.fromJSDate(data.pickup_time_scheduled).diffNow('seconds') < 0
            const isPastDeliveryTime = DateTime.fromJSDate(data.delivery_time_scheduled).diffNow('seconds') < 0
            if(bill.bill_id === data.bill_id && !isPastPickupTime && !isPastDeliveryTime)
                return {...bill, view: !bill.view}
            return bill
        })))
    }

    const toggleTableBillView = employeeId => {
        let view = false
        setDrivers(drivers.map(driver => {
            if(driver.employee_id == employeeId) {
                view = !driver.view
                return {...driver, view}
            }
            return driver
        }))
        setBills(paintBills(bills.map(bill => {
            const isPastPickupTime = DateTime.fromJSDate(bill.pickup_time_scheduled).diffNow('seconds') < 0
            const isPastDeliveryTime = DateTime.fromJSDate(bill.delivery_time_scheduled).diffNow('seconds') < 0
            if((bill.pickup_driver_id == employeeId || bill.delivery_driver_id == employeeId) && !isPastPickupTime && !isPastDeliveryTime)
                return {...bill, view}
            return bill
        })))
    }

    return (
        <Row>
            <Col md={9}>
                <Map
                    bills={bills}
                />
            </Col>
            <Col md={3}>
                <Row>
                    <Col md={12} className='text-center'>
                        <InputGroup>
                            <InputGroup.Text>Date</InputGroup.Text>
                            <DatePicker
                                dateFormat='MMMM d, yyyy'
                                className='form-control'
                                onChange={setBillDate}
                                selected={billDate}
                                wrapperClassName='form-control'
                            />
                        </InputGroup>
                    </Col>
                    <Col md={12} className='text-center'>
                        <h4 className='text-muted'>New Bills</h4>
                    </Col>
                    <Col md={12} style={{maxHeight: '25vh', overflowY: 'scroll'}}>
                        <ReactTabulator
                            columns={billColumns}
                            data={bills.filter(bill => !bill.pickup_driver_id || !bill.delivery_driver_id)}
                            id='unassigned-bills-table'
                            options={{
                                layout: 'fitColumns',
                                movableRows: true,
                                movableRowsConnectedTables: '#driver-table',
                                movableRowsSender: 'delete',
                                movableRowsReceiver: 'add',
                                movableRowsReceived: row => assignBill(row.getData().bill_id),
                                movableRowsSendingStart: () => setRowInTransit(true),
                                movableRowsSendingStop: () => setRowInTransit(false),
                                rowFormatter: rowFormatter
                            }}
                        />
                    </Col>
                    <Col md={12} className='text-center'>
                        <h4 className='text-muted'>Drivers</h4>
                    </Col>
                    <Row style={{maxHeight: '55vh', overflowY: 'scroll'}}>
                        {drivers && drivers.map(driver =>
                            <Col md={12} key={driver.employee_id}>
                                <Card style={{padding:'5px'}}>
                                    <Card.Header><h6>{`${driver.employee_number} - `} {driver.first_name} {driver.last_name}{driver.company_name ? ` (${driver.company_name})` : ''}</h6></Card.Header>
                                    <Card.Body style={{padding: 0}}>
                                        <ReactTabulator
                                            id='driver-table'
                                            columns={billColumns}
                                            data={bills.filter(bill => bill.pickup_driver_id === driver.employee_id || bill.delivery_driver_id === driver.employee_id)}
                                            data-employeeid={driver.employee_id}
                                            options={{
                                                movableRows: true,
                                                movableRowsReceiver: 'add',
                                                movableRowsSender: 'delete',
                                                movableRowsConnectedTables: ['#unassigned-bills-table', '#driver-table'],
                                                movableRowsReceived: row => assignBill(row.getData().bill_id, driver.employee_id),
                                                movableRowsSendingStart: () => setRowInTransit(true),
                                                movableRowsSendingStop: () => setRowInTransit(false),
                                                rowFormatter: rowFormatter
                                            }}
                                        />
                                    </Card.Body>
                                </Card>
                            </Col>
                        )}
                    </Row>
                </Row>
            </Col>
            <Modal show={showTimeModal} onHide={() => setShowTimeModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Set actual {timeModalField} time for bill {timeModalBillId}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <InputGroup>
                        <DatePicker
                            showTimeSelect
                            timeIntervals={5}
                            dateFormat='MMMM d, yyyy h:mm aa'
                            className='form-control'
                            selected={timeModalActualTime}
                            onChange={value => setTimeModalActualTime(value)}
                            wrapperClassName='form-control'
                        />
                        <Button onClick={submitActualTime}>Set</Button>
                    </InputGroup>
                </Modal.Body>
            </Modal>
        </Row>
    )
}
