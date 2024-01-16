import React, {useEffect, useState} from 'react'
import {Col, InputGroup} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import {DateTime} from 'luxon'
import {useLocation} from 'react-router-dom'

const DateBetweenFilter = (props) => {
    const [endDate, setEndDate] = useState(null)
    const [startDate, setStartDate] = useState(null)

    const location = useLocation()

    useEffect(() => {
        var dates = null
        if(props.filter.value) {
            dates = props.filter.value.split(',')
        }
        if(dates) {
            setStartDate(dates[0] ? DateTime.fromFormat(dates[0], 'yyyy-MM-dd').toJSDate() : null)
            setEndDate(dates[1] ? DateTime.fromFormat(dates[1], 'yyyy-MM-dd').toJSDate() : null)
        }
    }, [props.filter.value])

    useEffect(() => {
        const formattedStartDate = startDate ? DateTime.fromJSDate(startDate).toFormat('yyyy-MM-dd') : ''
        const formattedEndDate = endDate ? DateTime.fromJSDate(endDate).toFormat('yyyy-MM-dd') : ''
        const value = `${formattedStartDate}${endDate ? `,${formattedEndDate}` : ''}`

        props.handleFilterValueChange({...props.filter, value: value})
    }, [startDate, endDate])

    return(
        <Col md={6}>
            <InputGroup>
                <InputGroup.Text>{props.filter.name} Between: </InputGroup.Text>
                <DatePicker
                    wrapperClassName='form-control'
                    className='form-control'
                    dateFormat='MMMM d, yyyy'
                    endDate={endDate}
                    isClearable
                    placeholderText='After'
                    selectsStart
                    selected={startDate}
                    onChange={setStartDate}
                    wrapperClassName='form-control'
                />
                <InputGroup.Text>And</InputGroup.Text>
                <DatePicker
                    wrapperClassName='form-control'
                    className='form-control'
                    dateFormat='MMMM d, yyyy'
                    isClearable
                    placeholderText='Before'
                    minDate={startDate}
                    selected={endDate}
                    selectsEnd
                    startDate={startDate}
                    onChange={setEndDate}
                    wrapperClassName='form-control'
                />
            </InputGroup>
        </Col>
    )
}

export default DateBetweenFilter;
