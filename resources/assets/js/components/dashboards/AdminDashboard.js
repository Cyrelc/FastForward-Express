import React, {Component} from 'react'
import {Card, Col, Row} from 'react-bootstrap'
import {ResponsiveCalendar} from '@nivo/calendar'
import {ResponsiveLine} from '@nivo/line'
import {ReactTabulator} from 'react-tabulator'

export default class AdminDashboard extends Component {
    constructor() {
        super()
        this.state = {
            calendarHeatChart: [],
            employeeBirthdays: [],
            employeeExpiries: [],
            ytdChart: [],
            calendarStartDate: new Date(),
            calendarEndDate: new Date()
        }
    }

    componentDidMount() {
        makeFetchRequest('/getDashboard', data => {
            const startDate = new Date()
            const endDate = new Date()
            startDate.setMonth(0)
            startDate.setDate(0)
            endDate.setMonth(11)
            endDate.setDate(31)
            this.setState({
                calendarStartDate: startDate,
                calendarEndDate: endDate,
                calendarHeatChart: data.calendar_heat_chart,
                employeeExpiries: data.employee_expiries,
                employeeBirthdays: data.employee_birthdays,
                ytdChart: data.ytd_chart
            })
        })
    }

    render() {
        function birthdayFormatter(cell) {
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
            {title: 'Employee', field: 'employee_id', formatter: 'link', formatterParams: {labelField: 'employee_name', urlPrefix: '/app/employees/edit/'}},
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

        return (
            <Row md={11} className='justify-content-md-center'>
                <Col md={11}>
                    <Card>
                        <Card.Header><Card.Title>Admin Dashboard</Card.Title></Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={3}>
                                    <h4>Driver Birthdays</h4>
                                    <ReactTabulator
                                        columns={employeeBirthdayColumns}
                                        data={this.state.employeeBirthdays}
                                    />
                                    <h4>Employee Expiries</h4>
                                    <ReactTabulator
                                        columns={employeeExpiryColumns}
                                        data={this.state.employeeExpiries}
                                        options={{
                                            groupBy: 'type'
                                        }}
                                    />
                                </Col>
                                <Col md={9}>
                                    <h4>Bill Counts Per Day Year Over Year</h4>
                                    <div style={{height: '50vh', width: '65vw'}}>
                                        <ResponsiveCalendar
                                            data={this.state.calendarHeatChart}
                                            from={this.state.calendarStartDate}
                                            to={this.state.calendarEndDate}
                                            margin={{ top: 100 }}
                                        />
                                    </div>
                                    <hr/>
                                    <h4>Income/Outgoing</h4>
                                    <div style={{height: '50vh', width: '70vw'}}>
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
                                            data={this.state.ytdChart}
                                            enableSlices='x'
                                            margin={{ top: 50, right: 160, bottom: 50, left: 70}}
                                            yFormat={value => {return value.toLocaleString('en-US', {currency: 'CAD', currencyDisplay: 'symbol'})}}
                                        />
                                    </div>
                                    <hr/>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        )
    }
}
