import React from 'react'
import {Card, Row, Col, ListGroup} from 'react-bootstrap'
import 'react-tabulator/lib/styles.css'
import {ReactTabulator} from 'react-tabulator'
import * as moment from 'moment/moment'

export default function Driver(props) {
    return (
        <Row>
            <Col md={12}>
                <p>{props.driver.employee_name} - {props.driver.employee_number}</p>
            </Col>
            <Col md={12}>
                <ReactTabulator
                    id={'driverTables'}
                    columns={props.billColumns}
                    data-driverid={props.driver.driver_id}
                    data={props.bills.filter(bill => (bill.pickup_driver_id === props.driver.driver_id || bill.delivery_driver_id === props.driver.driver_id))}
                    options={{
                        invalidOptionWarnings: false,
                        layout: 'fitColumns',
                        movableRows: true,
                        movableRowsConnectedTables: '#driverTables',
                        movableRowsReceived: row => props.handleChange({target: {name: 'assignBill', type: 'number', value: row._row.data.bill_id, driver_id: props.driver.driver_id}}),
                        movableRowsSendingStart: () => props.handleChange({target: {name: 'rowInTransit', type: 'checkbox', checked: true}}),
                        movableRowsSendingStop: () => props.handleChange({target: {name: 'rowInTransit', type: 'checkbox', checked: false}}),
                        rowFormatter: props.rowFormatter,
                    }}
                />
            </Col>
        </Row>
    )
}
