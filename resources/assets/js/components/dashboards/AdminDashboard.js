import React, {useEffect, useState} from 'react'
import {Card, Col, Row} from 'react-bootstrap'
import {DateTime} from 'luxon'
import {ResponsiveCalendar} from '@nivo/calendar'
import {ResponsiveLine} from '@nivo/line'
import {AgGridReact} from 'ag-grid-react'
import {TabulatorFull as Tabulator} from 'tabulator-tables'
import {LinkContainer} from 'react-router-bootstrap'
import {LinkCellRenderer} from '../../utils/utils'

import {useAPI} from '../../contexts/APIContext'

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

    const employeeBirthdayColumns = [
        {headerName: 'Employee', field: 'employee_name', flex: 1},
        {field: 'birthday', width: 120}
    ]

    const employeeExpiryColumns = [
        {headerName: 'Employee', field: 'employee_id', cellRenderer: LinkCellRenderer, cellRendererParams: {labelField: 'employee_name', urlPrefix: '/employees/'}, width: 130},
        {headerName: 'Date', field: 'date', sort: 'asc', width: 120},
        {headerName: 'Type', field: 'type', width: 130}
    ]

    const holidayColumns = [
        {headerName: 'Name', field: 'name'},
        {headerName: 'Date', field: 'value', cellRenderer: props => (new Date(props.value)).toDateString()}
    ]

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
                                    <div className='ag-theme-quartz-dark' style={{maxHeight: '25%'}}>
                                        <AgGridReact
                                            rowData={employeeBirthdays}
                                            columnDefs={employeeBirthdayColumns}
                                            domLayout='autoHeight'
                                        />
                                    </div>
                                    <hr/>
                                    <h4 className='text-muted'>Employee Expiries</h4>
                                    <div className='ag-theme-quartz-dark' style={{maxHeight: '25%', overflowY: 'auto'}}>
                                        <AgGridReact
                                            rowData={employeeExpiries}
                                            columnDefs={employeeExpiryColumns}
                                            domLayout='autoHeight'
                                        />
                                    </div>
                                    <hr/>
                                    <LinkContainer to='/appSettings#scheduling'>
                                        <a><h4 className='text-muted'>Upcoming Holidays</h4></a>
                                    </LinkContainer>
                                    <div className='ag-theme-quartz-dark' style={{maxHeight: '25%', overflowY: 'auto'}}>
                                        <AgGridReact
                                            rowData={upcomingHolidays}
                                            columnDefs={holidayColumns}
                                            domLayout='autoHeight'
                                        />
                                    </div>
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
