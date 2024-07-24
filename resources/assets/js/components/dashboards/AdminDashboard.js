import React, {useEffect, useMemo, useState} from 'react'
import {Card, Col, Row} from 'react-bootstrap'
import {DateTime} from 'luxon'
import {ResponsiveCalendar} from '@nivo/calendar'
import {ResponsiveLine} from '@nivo/line'
import {LinkContainer} from 'react-router-bootstrap'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import {LinkCellRenderer} from '../../utils/table_cell_renderers'
import {useAPI} from '../../contexts/APIContext'

const commonTableSettings = {
    enableBottomToolbar: false,
    enableColumnActions: false,
    enablePagination: false,
    enableToolbarInternalActions: false,
    enableTopToolbar: false,
}

export default function AdminDashboard(props) {
    const calendarEndDate = DateTime.now().startOf('year').toJSDate()
    const calendarStartDate = DateTime.now().endOf('year').minus({years: 1}).toJSDate()

    const [calendarHeatChart, setCalendarHeatChart] = useState([])
    const [employeeBirthdays, setEmployeeBirthdays] = useState([])
    const [employeeExpiries, setEmployeeExpiries] = useState([])
    const [loading, setLoading] = useState(true)
    const [upcomingHolidays, setUpcomingHolidays] = useState([])
    const [ytdChart, setYtdChart] = useState([])

    const api = useAPI()

    useEffect(() => {
        api.get('/getDashboard')
            .then(response => {
                setCalendarHeatChart(response.calendar_heat_chart)
                setEmployeeBirthdays(response.employee_birthdays)
                setEmployeeExpiries(response.employee_expiries)
                setYtdChart(response.ytd_chart)
                setUpcomingHolidays(response.upcoming_holidays)
            }).finally(
                setLoading(false)
            )
    }, [])

    const employeeBirthdayColumns = useMemo(() => [
        {accessorKey: 'employee_name', header: 'Employee'},
        {accessorKey: 'birthday', header: 'Birthday'}
    ], [])

    const employeeBirthdayTable = useMaterialReactTable({
        ...commonTableSettings,
        columns: employeeBirthdayColumns,
        data: employeeBirthdays,
        initialState: {
            density: 'compact',
        },
    })

    const employeeExpiryColumns = useMemo(() => [
        {
            header: 'Employee',
            accessorKey: 'employee_id',
            Cell: ({renderedCellValue, row}) => (
                <LinkCellRenderer renderedCellValue={renderedCellValue} row={row} urlPrefix='/employees/' labelField='employee_name' />
            ),
            size: 130
        },
        {header: 'Date', accessorKey: 'date', size: 100, grow: false},
        {header: 'Type', accessorKey: 'type', size: 130}
    ])

    const employeeExpiriesTable = useMaterialReactTable({
        ...commonTableSettings,
        columns: employeeExpiryColumns,
        data: employeeExpiries,
        initialState: {
            density: 'compact',
        }
    })

    const holidayColumns = useMemo(() => ([
        {header: 'Name', accessorKey: 'name'},
        {header: 'Date', accessorKey: 'value', Cell: props => (new Date(props.value)).toDateString()}
    ]), [])

    const holidayTable = useMaterialReactTable({
        ...commonTableSettings,
        columns: holidayColumns,
        data: upcomingHolidays,
        initialState: {
            density: 'compact',
            sorting: [{id: 'date', asc: true}]
        }
    })

    return (
        <Row className='justify-content-md-center' style={{margin: 0}}>
            <Col md={12}>
                <Card>
                    <Card.Header>
                        <Card.Title>Admin Dashboard</Card.Title>
                    </Card.Header>
                    {loading ?
                        <Card.Body>
                            <h4>Requesting data, please wait... <i className='fas fa-spinner fa-spin'></i></h4>
                        </Card.Body> :
                        <Card.Body>
                            <Row>
                                <Col md={3}>
                                    <h4 className='text-muted'>Employee Birthdays</h4>
                                    <MaterialReactTable table={employeeBirthdayTable} />
                                    <hr/>
                                    <h4 className='text-muted'>Employee Expiries</h4>
                                    <MaterialReactTable table={employeeExpiriesTable} />
                                    <hr/>
                                    <LinkContainer to='/appSettings#scheduling'>
                                        <a><h4 className='text-muted'>Upcoming Holidays</h4></a>
                                    </LinkContainer>
                                    <MaterialReactTable table={holidayTable} />
                                </Col>
                                <Col md={9}>
                                    <h4>Bill Counts Per Day Year Over Year</h4>
                                    <div style={{height: '50%', width: '100%', marginTop: '-80px'}}>
                                        <ResponsiveCalendar
                                            data={calendarHeatChart}
                                            from={calendarStartDate}
                                            to={calendarEndDate}
                                            margin={{top: 100}}
                                        />
                                    </div>
                                    <hr/>
                                    <h4>Income/Outgoing</h4>
                                    <div style={{height: '40vh', width: '100%', marginTop: '-30px'}}>
                                        <ResponsiveLine
                                            animate={true}
                                            axisBottom={{
                                                orient: 'bottom',
                                                legend: 'Month',
                                                legendOffset: 36,
                                                legendPosition: 'middle'
                                            }}
                                            axisLeft={{
                                                orient: 'left',
                                                legend: 'Dollar Amount',
                                                legendOffset: -60,
                                                legendPosition: 'middle'
                                            }}
                                            data={ytdChart}
                                            enableSlices='x'
                                            margin={{ top: 50, right: 160, bottom: 50, left: 70}}
                                            yFormat={value => {return value.toLocaleString('en-US', {currency: 'CAD', currencyDisplay: 'symbol'})}}
                                        />
                                    </div>
                                    <hr/>
                                </Col>
                            </Row>
                        </Card.Body>
                    }
                </Card>
            </Col>
        </Row>
    )
}
