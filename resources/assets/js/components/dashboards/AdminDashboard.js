import React, {Component} from 'react'
import {Card, Col, Row} from 'react-bootstrap'
import { ResponsiveLine } from '@nivo/line'
import {ReactTabulator} from 'react-tabulator'

export default class AdminDashboard extends Component {
    constructor() {
        super()
        this.state = {
            employeeBirthdays: [],
            employeeExpiries: [],
            ytdChart: []
        }
    }

    componentDidMount() {
        makeFetchRequest('/getDashboard', data => {
            this.setState({
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
                                <Col md={4}>
                                    <h4>Employee Expiries</h4>
                                    <ReactTabulator
                                        columns={employeeExpiryColumns}
                                        data={this.state.employeeExpiries}
                                        options={{
                                            groupBy: 'type'
                                        }}
                                    />
                                </Col>
                                <Col md={4}>
                                    <h4>Driver Birthdays</h4>
                                    <ReactTabulator
                                        columns={employeeBirthdayColumns}
                                        data={this.state.employeeBirthdays}
                                    />
                                </Col>
                                <Col md={4}>
                                    <h4>Financial Quickview</h4>
                                    {/* <Row>
                                        <Col md={6}>
                                            <label>Accounts Receivable: </label>
                                        </Col>
                                        <Col md={6}>{this.state.accountsReceivable}</Col>
                                    </Row>
                                    <Row>
                                        <Col md={6}>
                                            <label>YTD Income: </label>
                                        </Col>
                                        <Col md={6}>{this.state.ytdIncome}</Col>
                                    </Row>
                                    <Row>
                                        <Col md={6}>
                                            <label>YTD Driver Pay: </label>
                                        </Col>
                                        <Col md={6}>{this.state.ytdDriverPay}</Col>
                                    </Row>
                                    <Row>
                                        <Col md={6}>
                                            <label>YTD GST: </label>
                                        </Col>
                                        <Col md={6}>{this.state.ytdGst}</Col>
                                    </Row> */}
                                </Col>
                                <Col md={12}>
                                    <div style={{height: '50vh', width: '90vw'}}>
                                        <h4>Charts. So many charts. Much chart. Charts for all and all for chart.</h4>
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
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        )
    }
}
