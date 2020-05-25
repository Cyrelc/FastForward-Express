import React from 'react'
import {Card, Row, Col, ListGroup} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'

export default function Bills(props) {
    return (
        <ReactTabulator
            id='billsTable'
            columns={props.billColumns}
            data={props.bills.filter(bill => (bill.pickup_driver_id === null || bill.delivery_driver_id === null))}
            data-employee={{driver_id: null, view: true}}
            options={{
                invalidOptionWarnings: false,
                layout: 'fitColumns',
                movableRows: true,
                movableRowsConnectedTables: '#driverTables',
                rowFormatter: props.rowFormatter,
                movableRowsSendingStart: () => props.handleChange({target: {name: 'rowInTransit', type: 'checkbox', checked: true}}),
                movableRowsSendingStop: () => props.handleChange({target: {name: 'rowInTransit', type: 'checkbox', checked: false}})
            }}
        />
    )
}
