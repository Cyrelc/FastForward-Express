import React, {Component} from 'react'
import {Card, Col, Row, InputGroup} from 'react-bootstrap'
import {ResponsiveBar} from '@nivo/bar'
import DatePicker from 'react-datepicker'
import Select from 'react-select'

export default class Charts extends Component {
    constructor() {
        super()
        this.state = {
            data: [],
            dateGroupBy: {label: 'Month', value: 'month'},
            dateGroupOptions: [{label: 'Year', value: 'year'}, {label: 'Month', value: 'month'}, {label: 'Day', value: 'day'}],
            endDate: new Date(),
            groupBy: {label: 'Employee', value: 'employee_name'},
            groupOptions: [{label: 'None', value: 'none'}, {label: 'Employee', value: 'employee_name'}, {label: 'Delivery Type', value: 'delivery_type'}],
            keys: [],
            startDate: new Date(new Date().setFullYear(new Date().getFullYear() - 1)),
            summationOptions: [{label: 'Count', value: 'count'}, {label: 'Total Cost', value: 'amount'}, {label: 'Employee Income', value: 'driver_income'}],
            summationType: {label: 'Count', value: 'count'}
        }
        this.fetchChart = this.fetchChart.bind(this)
        this.handleChange = this.handleChange.bind(this)
    }

    componentDidMount() {
        this.fetchChart();
    }

    fetchChart() {
        var url = '/bills/chart?type=monthlyBills'
        url += '&dateGroupBy=' + this.state.dateGroupBy.value
        url += '&startDate=' + this.state.startDate.toISOString().substring(0, 10) 
        if(this.state.dateGroupBy != 'day')
            url += '&endDate=' + this.state.endDate.toISOString().substring(0, 10)
        url += '&summationType=' + this.state.summationType.value
        if(this.state.groupBy)
            url += ('&groupBy=' + this.state.groupBy.value)
        makeFetchRequest(url, data => {
            if(data.bills) {
                const results = Object.values(data.bills).map(value => {return value})
                this.setState({data: results, keys: data.keys, legend: data.legend}, console.log(results))
            } else
                toastr.error('No bills found for the selected time period. Please try again')
        })
    }

    handleChange(event) {
        const {name, value, type, checked} = event.target
        this.setState({[name]: type === 'checkbox' ? checked : value}, () => this.fetchChart())
    }

    render() {
        return (
            <Row className='justify-content-md-center'>
                <Col md={11} className='d-flex justify-content-center'>
                    <Card border='dark'>
                        <Card.Header>
                            <Row>
                                <Col md={3}>
                                    <InputGroup>
                                        <InputGroup.Text>Bill </InputGroup.Text>
                                        <Select
                                            onChange={item => this.handleChange({target: {name: 'summationType', type: 'select', value: item}})}
                                            options={this.state.summationOptions}
                                            value={this.state.summationType}
                                        />
                                        <InputGroup.Text> Per </InputGroup.Text>
                                        <Select
                                            onChange={item => this.handleChange({target: {name: 'dateGroupBy', type: 'select', value: item}})}
                                            options={this.state.dateGroupOptions}
                                            value={this.state.dateGroupBy}
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={3}>
                                    <InputGroup>
                                        <InputGroup.Text>Start Date:</InputGroup.Text>
                                        <DatePicker
                                            className='form-control'
                                            dateFormat="MMMM, yyyy"
                                            selected={this.state.startDate}
                                            showMonthYearPicker
                                            onChange={value => this.handleChange({target: {name: 'startDate', type: 'date', value: value}})}
                                            readOnly={this.state.dateGroupBy.value === 'day'}
                                            wrapperClassName='form-control'
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>End Date:</InputGroup.Text>
                                        <DatePicker
                                            className='form-control'
                                            dateFormat="MMMM, yyyy"
                                            selected={this.state.endDate}
                                            showMonthYearPicker
                                            onChange={value => this.handleChange({target: {name: 'endDate', type: 'date', value: value}})}
                                            wrapperClassName='form-control'
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={3}>
                                    <InputGroup>
                                        <InputGroup.Text>Group By: </InputGroup.Text>
                                        <Select
                                            onChange={item => this.handleChange({target: {name: 'groupBy', type: 'select', value: item}})}
                                            options={this.state.groupOptions}
                                            value={this.state.groupBy}
                                        />
                                    </InputGroup>
                                </Col>
                            </Row>
                        </Card.Header>
                        <Card.Body>
                            <div style={{height: '75vh', width: '90vw'}}>
                                <ResponsiveBar
                                    data={this.state.data}
                                    keys={this.state.keys}
                                    indexBy='indexKey'
                                    labelFormat={(this.state.summationType.value === 'amount' || this.state.summationType.value === 'driver_income') ? '$.2f' : ''}
                                    legends={[{
                                        dataFrom: 'keys',
                                        anchor: 'bottom-right',
                                        direction: 'column', 
                                        justify: false,
                                        translateX: 120, 
                                        translateY: 0,
                                        itemsSpacing: 2,
                                        itemWidth: 100,
                                        itemHeight: 20,
                                        itemDirection: 'left-to-right',
                                        itemOpacity: 0.85,
                                        symbolSize: 20,
                                    }]}
                                    margin={{ top: 50, right: 160, bottom: 50, left: 60}}
                                    tooltip={data => {
                                        if(this.state.summationType.value === 'amount')
                                            return data.id + ' - $' + data.value.toLocaleString()
                                        else
                                            return data.id + ' : ' + data.value
                                    }}
                                />
                                {
                                    this.state.summationType.value === 'driver_income' &&
                                    <label>Note: Driver income here is based on bills by date of delivery <strong>NOT</strong> date manifested. In addition, chargebacks are not considered here.</label>
                                }
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        )
    }
}
