import React, {useEffect, useRef, useState} from 'react'
import {TabulatorFull as Tabulator} from 'tabulator-tables'
import {Card} from 'react-bootstrap'
import {DateTime} from 'luxon'

export default function BillTable(props) {
    const [table, setTable] = useState(null)
    const [tableBuilt, setTableBuilt] = useState(false)

    const tableRef = useRef(null)
    const {driver} = props

    const columns = [
        {rowHandle: true, formatter: 'handle', headerSort: false, frozen: true, width: 30, minWidth: 30},
        {title: 'Bill ID', field: 'bill_id', cellDblClick: (event, cell) => window.open(`/bills/${cell.getValue()}`)},
        {
            cellClick: (event, cell) => props.setTimeModalView(cell),
            field: 'time_pickup_scheduled',
            formatter: cell => {
                const data = cell.getRow().getData()
                return pickupDeliveryTimeFormatter(cell, data.time_pickup_scheduled, data.time_picked_up)
            },
            title: 'Pickup',
            hozAlign: 'right'
        },
        {
            cellClick: (event, cell) => props.setTimeModalView(cell),
            title: 'Delivery',
            field: 'time_delivery_scheduled',
            formatter: cell => {
                const data = cell.getRow().getData()
                return pickupDeliveryTimeFormatter(cell, data.time_delivery_scheduled, data.time_delivered)
            },
            hozAlign: 'right',
        },
        {
            title: 'View',
            field: null,
            headerSort: false,
            formatter: (cell) => {return cell.getRow().getData().view ? '<i class="fas fa-eye"></i>' : '<i class="far fa-eye-slash"></i>'},
            width: 60,
            cellClick: (event, cell) => props.toggleBillView(cell),
            headerClick: (event, column) => props.toggleTableBillView(column.getTable().element.getAttribute('data-employeeid'))
        },
        {title: 'Picked Up', field: 'time_picked_up', visible: false},
        {title: 'Delivered', field: 'time_delivered', visible: false},
        {title: 'Current Time', field: 'current_time', visible: false}
    ]

    useEffect(() => {
        if(tableRef.current && !table) {
            const newTabulator = new Tabulator(tableRef.current, {
                columns: columns,
                data: props.bills,
                layout: 'fitColumns',
                movableRows: true,
                movableRowsConnectedTables: ['#unassigned-bills-table', '#driver-table'],
            })

            newTabulator.on('movableRowsReceived', (fromRow, toRow, fromTable) => props.assignBill(fromRow.getData().bill_id, driver?.employee_id ?? null))
            newTabulator.on('movableRowsSendingStart', () => props.setRowInTransit(true))
            newTabulator.on('movableRowsSendingStop', () => props.setRowInTransit(false))
            newTabulator.on('tableBuilt', () => setTableBuilt(true))

            setTable(newTabulator)
        }
    }, [tableRef])

    useEffect(() => {
        if(table && tableBuilt) {
            table.setData(props.bills)
        }
    }, [props.bills, tableBuilt])

    const pickupDeliveryTimeFormatter = (cell, timeScheduled, actualTime) => {
        if(actualTime)
            actualTime = DateTime.fromSQL(actualTime)
        const timeRemaining = DateTime.fromSQL(timeScheduled).diffNow().shiftTo('days', 'hours', 'minutes')
        const color = props.getBackgroundColour(timeRemaining, actualTime)
        cell.getElement().style.backgroundColor = color
    
        if(actualTime)
            return `${actualTime.toLocaleString(DateTime.TIME_SIMPLE)}`
    
        const days = timeRemaining.days.toFixed(0)
        const hours = timeRemaining.hours.toFixed(0)
        const minutes = timeRemaining.minutes.toFixed(0)

        return `${minutes < 0 ? '-' : ''}${Math.abs(days) > 0 ? Math.abs(days) + 'd,' : ''} ${Math.abs(hours) > 0 ? Math.abs(hours) + 'h,' : ''} ${Math.abs(minutes)}m`
    }
    
    return (
        <Card style={{padding:'5px'}}>
            {driver &&
                <Card.Header>
                    <h6>{driver.label}</h6>
                </Card.Header>
            }
            <Card.Body style={{padding: 0}}>
                <div id='driver-table' ref={tableRef} data-employeeid={driver?.value ?? null}></div>
            </Card.Body>
        </Card>
    )
}

