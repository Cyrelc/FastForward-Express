import React, {useEffect, useState} from 'react'
import {Card, Col, Row} from 'react-bootstrap'
import {DateTime} from 'luxon'
import {ResponsiveCalendar} from '@nivo/calendar'
import {ResponsiveLine} from '@nivo/line'
import {ReactTabulator} from 'react-tabulator'
import {LinkContainer} from 'react-router-bootstrap'

export default function AdminDashboard(props) {
    const calendarEndDate = DateTime.now().startOf('year').toJSDate()
    const calendarStartDate = DateTime.now().endOf('year').minus({years: 1}).toJSDate()

    const [calendarHeatChart, setCalendarHeatChart] = useState([])
    const [employeeBirthdays, setEmployeeBirthdays] = useState(undefined)
    const [employeeExpiries, setEmployeeExpiries] = useState(undefined)
    const [loading, setLoading] = useState(true)
    const [upcomingHolidays, setUpcomingHolidays] = useState(undefined)
    const [ytdChart, setYtdChart] = useState([])

    useEffect(() => {
        makeAjaxRequest('/getDashboard', 'GET', null, response => {
            response = JSON.parse(response)

            setCalendarHeatChart(response.calendar_heat_chart)
            setEmployeeBirthdays(response.employee_birthdays)
            setEmployeeExpiries(response.employee_expiries)
            setYtdChart(response.ytd_chart)
            setUpcomingHolidays(response.upcoming_holidays)
            setLoading(false)
        })
    }, [])

    const birthdayFormatter = cell => {
        const date = Date.parse(cell.getValue())
        const today = new Date()
        if(date === today)
            cell.getElement().style.backgroundColor = 'lightgreen'
        return cell.getValue()
    }

    const employeeBirthdayColumns = [
        {title: 'Employee', field: 'employee_name', formatter: cell => birthdayFormatter(cell)},
        {title: 'Birthday', field: 'birthday',formatter: cell => birthdayFormatter(cell)}
    ]

    const employeeExpiryColumns = [
        {title: 'Employee', field: 'employee_id', formatter: 'link', formatterParams: {labelField: 'employee_name', urlPrefix: '/app/employees/'}},
        {title: 'Date', field: 'date', formatter: cell => {
            const date = Date.parse(cell.getValue())
            const today = new Date()
            if(date < today)
                cell.getElement().style.backgroundColor = 'salmon'
            else
                cell.getElement().style.backgroundColor = 'darkorange'
            return cell.getValue()
        }},
        {title: 'Type', field: 'type', visible: false}
    ]

    const holidayColumns = [
        {title: 'Name', field: 'name'},
        {title: 'Date', field: 'value', formatter: row => (new Date(row.getData().value)).toDateString()}
    ]

    return (
        <Row className='justify-content-md-center'>
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
                                    <h4>Employee Birthdays</h4>
                                    <ReactTabulator
                                        columns={employeeBirthdayColumns}
                                        data={employeeBirthdays}
                                    />
                                    <hr/>
                                    <h4>Employee Expiries</h4>
                                    <ReactTabulator
                                        columns={employeeExpiryColumns}
                                        data={employeeExpiries}
                                        options={{
                                            groupBy: 'type'
                                        }}
                                    />
                                    <hr/>
                                    <LinkContainer to='/appSettings#scheduling'>
                                        <a><h4>Upcoming Holidays</h4></a>
                                    </LinkContainer>
                                    <ReactTabulator
                                        columns={holidayColumns}
                                        data={upcomingHolidays}
                                    />
                                </Col>
                                <Col md={9}>
                                    <h4>Bill Counts Per Day Year Over Year</h4>
                                    <div style={{height: '50vh', width: '100%', marginTop: '-60px'}}>
                                        <ResponsiveCalendar
                                            data={calendarHeatChart}
                                            from={calendarStartDate}
                                            to={calendarEndDate}
                                            margin={{top: 100}}
                                        />
                                    </div>
                                    <hr/>
                                    <h4>Income/Outgoing</h4>
                                    <div style={{height: '50vh', width: '100%', marginTop: '-30px'}}>
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
