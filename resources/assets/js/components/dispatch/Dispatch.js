import React, {useEffect, useRef, useState} from 'react'
import {Button, Col, InputGroup, Modal, Row} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import {DateTime} from 'luxon'

import Map from './Map'
import BillTable from './BillTable'

const getBackgroundColour = (timeRemaining, actualTime = null) => {
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

const formatBill = data => {
    return {
        bill_id: data.bill.bill_id,
        current_time: Date.now(),
        pickup_address_lat: data.pickup_address.lat,
        pickup_address_lng: data.pickup_address.lng,
        delivery_address_lat: data.delivery_address.lat,
        delivery_address_lng: data.delivery_address.lng,
        pickup_driver_id: data.bill.pickup_driver_id,
        delivery_driver_id: data.bill.delivery_driver_id,
        time_picked_up: data.bill.time_picked_up,
        time_delivered: data.bill.time_delivered,
        time_pickup_scheduled: data.bill.time_pickup_scheduled,
        time_delivery_scheduled: data.bill.time_delivery_scheduled,
        view: true
    }
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
    const billRef = useRef(null)
    const driverRef = useRef(null)
    billRef.current = bills
    driverRef.current = drivers

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
        makeAjaxRequest('/dispatch', 'GET', null, response => {
            setDrivers(Object.values(response.drivers).map(driver => {return {...driver, view: true}}))

            window.echo = new Echo({
                broadcaster: 'pusher',
                key: response.pusher_key,
                cluster: response.pusher_cluster,
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
        })

        const interval = setInterval(() => {
            if(!rowInTransit)
                setBills(bills => bills.map(bill => {
                    return {...bill, currentTime: Date.now()}
                }))
        }, 60000)
    }, [])

    useEffect(() => {
        const date = DateTime.fromJSDate(billDate).toFormat('yyyy-MM-dd')
        makeAjaxRequest(`/dispatch/getBills?filter[dispatch]=true&filter[time_pickup_scheduled]=${date},${date}`, 'GET', null, response => {
            response = JSON.parse(response)
            const bills = response.map(bill => {
                return {...bill,
                    view: bill.view === undefined ? true : bill.view,
                    current_time: Date.now()
                }
            })
            setBills(bills)
        })
    }, [billDate])

    useEffect(() => {
        if(!rowInTransit && pendingBillEvents.length) {
            pendingBillEvents.forEach(event => {
                if(event.type === 'remove')
                    setBills(bills => bills.filter(bill => bill.bill_id != event.bill.bill_id))
                else if(event.type === 'add')
                    setBills(bills => bills.concat([event.bill]))
                else if(event.type === 'update')
                    setBills(bills => bills.map(bill => {
                        if(bill.bill_id === event.bill.bill_id)
                            return event.bill
                        return bill
                    }))
            })
            setPendingBillEvents(new Array())
        }
    }, [pendingBillEvents, rowInTransit])

    const assignBill = (billId, employeeId = null) => {
        const data = {
            bill_id: billId,
            employee_id: employeeId
        }
        makeAjaxRequest('/dispatch/assignBillToDriver', 'POST', data, response => {
            toastr.clear()
        })
    }

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
                        return {...bill, time_picked_up: timeModalActualTime.toISOString().slice(0, 19).replace('T', ' ')}
                    return {...bill, time_delivered: timeModalActualTime.toISOString().slice(0, 19).replace('T', ' ')}
                }
                return bill
            })
            setBills(
                refreshedBills.filter(bill => {
                    return (bill.time_picked_up == null || bill.time_delivered == null || bill.delivery_driver == null || bill.pickup_driver == null)
                })
            )
        })
        setShowTimeModal(false)
    }

    const toggleBillView = cell => {
        const data = cell.getRow().getData()
        setBills(billRef.current.map(bill => {
            const isPastPickupTime = DateTime.fromSQL(data.time_pickup_scheduled).diffNow('seconds') < 0
            const isPastDeliveryTime = DateTime.fromSQL(data.time_delivery_scheduled).diffNow('seconds') < 0
            if(bill.bill_id === data.bill_id && !isPastPickupTime && !isPastDeliveryTime)
                return {...bill, view: !bill.view}
            return bill
        }))
    }

    const toggleTableBillView = employeeId => {
        let view = false
        setDrivers(driverRef.current.map(driver => {
            if(driver.employee_id == employeeId) {
                view = !driver.view
                return {...driver, view}
            }
            return driver
        }))
        const hiddenBills = billRef.current.map(bill => {
            const isPastPickupTime = DateTime.fromSQL(bill.time_pickup_scheduled).diffNow('seconds') < 0
            const isPastDeliveryTime = DateTime.fromSQL(bill.time_delivery_scheduled).diffNow('seconds') < 0
            if((bill.pickup_driver_id == employeeId || bill.delivery_driver_id == employeeId) && !isPastPickupTime && !isPastDeliveryTime) {
                return {...bill, view}
            }
            return bill
        })
        setBills(hiddenBills)
    }

    return (
        <Row>
            <Col md={9}>
                <Map
                    bills={bills}
                    getBackgroundColour={getBackgroundColour}
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
                        <BillTable
                            assignBill={assignBill}
                            bills={bills.filter(bill => bill.pickup_driver_id === null || bill.delivery_driver_id === null)}
                            getBackgroundColour={getBackgroundColour}
                            setRowInTransit={setRowInTransit}
                            setTimeModalView={setTimeModalView}
                            toggleTableBillView={toggleTableBillView}
                            toggleBillView={toggleBillView}
                        />
                    </Col>
                    <Col md={12} className='text-center'>
                        <h4 className='text-muted'>Drivers</h4>
                    </Col>
                    <Row style={{maxHeight: '55vh', overflowY: 'scroll'}}>
                        {drivers && drivers.map(driver =>
                            <Col md={12} key={driver.employee_id}>
                                <BillTable
                                    assignBill={assignBill}
                                    bills={bills.filter(bill => bill.pickup_driver_id === driver.employee_id || bill.delivery_driver_id === driver.employee_id)}
                                    driver={driver}
                                    getBackgroundColour={getBackgroundColour}
                                    setRowInTransit={setRowInTransit}
                                    setTimeModalView={setTimeModalView}
                                    toggleTableBillView={toggleTableBillView}
                                    toggleBillView={toggleBillView}
                                />
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
