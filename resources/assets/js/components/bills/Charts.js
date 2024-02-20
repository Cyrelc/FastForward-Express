import React, {useEffect, useState} from 'react'
import {Card, Col, Row, InputGroup} from 'react-bootstrap'
import {ResponsiveBar} from '@nivo/bar'
import DatePicker from 'react-datepicker'
import Select from 'react-select'
import {useAPI} from '../../contexts/APIContext'

const dateGroupOptions = [
    {label: 'Year', value: 'year'},
    {label: 'Month', value: 'month'},
    {label: 'Day', value: 'day'}
]

const groupByOptions = [
    {label: 'None', value: 'none'},
    {label: 'Employee', value: 'employee_name'},
    {label: 'Delivery Type', value: 'delivery_type'}
]

export default function Charts(props) {
    const [data, setData] = useState([])
    const [dateGroupBy, setDateGroupBy] = useState({label: 'Month', value: 'month'})
    const [endDate, setEndDate] = useState(new Date())
    const [groupBy, setGroupBy] = useState({label: 'Employee', value: 'employee_name'})
    const [keys, setKeys] = useState([])
    const [startDate, setStartDate] = useState(new Date(new Date().setFullYear(new Date().getFullYear() - 1)))
    const [summationOptions, setSummationOptions] = useState([{label: 'Count', value: 'count'}, {label: 'Total Cost', value: 'amount'}, {label: 'Employee Income', value: 'driver_income'}])
    const [summationType, setSummationType] = useState({label: 'Count', value: 'count'})

    const api = useAPI()

    useEffect(() => {
        fetchChart()
    }, [])

    useEffect(() => {
        fetchChart()
    }, [dateGroupBy, summationType, groupBy, startDate, endDate])

    const fetchChart = async () =>  {
        const queryString = new URLSearchParams({
            dateGroupBy: dateGroupBy.value,
            endDate: endDate.toLocaleDateString(),
            groupBy: groupBy ? groupBy.value : null,
            startDate: startDate.toLocaleDateString(),
            summationType: summationType.value
        }).toString()

        await api.get(`/bills/chart?${queryString}`)
            .then(response => {
                if(response.bills) {
                    const results = Object.values(response.bills).map(value => {return value})
                    setData(results)
                    setKeys(response.keys)
                }
            })
    }

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
                                        onChange={setSummationType}
                                        options={summationOptions}
                                        value={summationType}
                                    />
                                    <InputGroup.Text> Per </InputGroup.Text>
                                    <Select
                                        onChange={setDateGroupBy}
                                        options={dateGroupOptions}
                                        value={dateGroupBy}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>Start Date:</InputGroup.Text>
                                    <DatePicker
                                        className='form-control'
                                        dateFormat="MMMM, yyyy"
                                        selected={startDate}
                                        showMonthYearPicker
                                        onChange={setStartDate}
                                        readOnly={dateGroupBy.value === 'day'}
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
                                        selected={endDate}
                                        showMonthYearPicker
                                        onChange={setEndDate}
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>Group By: </InputGroup.Text>
                                    <Select
                                        onChange={setGroupBy}
                                        options={groupByOptions}
                                        value={groupBy}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                    </Card.Header>
                    <Card.Body>
                        <div style={{height: '80vh', width: '80vw'}}>
                            <ResponsiveBar
                                axisBottom={{tickRotation: 45}}
                                data={data}
                                keys={keys}
                                indexBy='indexKey'
                                label={group => ['amount', 'driver_income'].includes(summationType.value) ? group.value.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'}) : group.value}
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
                                margin={{ top: 50, right: 160, bottom: 80, left: 60}}
                                tooltip={data => {
                                    if(summationType.value === 'amount')
                                        return `${data.id} - $${data.value.toLocaleString()}`
                                    else
                                        return `${data.id} : ${data.value}`
                                }}
                            />
                            {
                                summationType.value === 'driver_income' &&
                                <label>Note: Driver income here is based on bills by date of delivery <strong>NOT</strong> date manifested. In addition, chargebacks are not considered here.</label>
                            }
                        </div>
                    </Card.Body>
                </Card>
            </Col>
        </Row>
    )
}
