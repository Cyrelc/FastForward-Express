import React from 'react'
import {Col, InputGroup} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import {DateTime} from 'luxon'

const DATE_FORMAT = 'yyyy-MM-dd'

const parseDate = value => {
    if(!value)
        return null
    const date = DateTime.fromFormat(value, DATE_FORMAT)
    return date.isValid ? date.toJSDate() : null
}

const formatDate = date => date ? DateTime.fromJSDate(date).toFormat(DATE_FORMAT) : ''

// Value format (parsed by App\Http\Filters\DateBetween): "start", "start,end" or ",end"
const DateBetweenFilter = (props) => {
    const [start, end] = props.filter.value ? props.filter.value.split(',') : []
    const startDate = parseDate(start)
    const endDate = parseDate(end)

    const setDates = (newStartDate, newEndDate) => {
        const value = `${formatDate(newStartDate)}${newEndDate ? `,${formatDate(newEndDate)}` : ''}`
        props.handleFilterValueChange({...props.filter, value})
    }

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
                    startDate={startDate}
                    onChange={date => setDates(date, endDate)}
                />
                <InputGroup.Text>And</InputGroup.Text>
                <DatePicker
                    wrapperClassName='form-control'
                    className='form-control'
                    dateFormat='MMMM d, yyyy'
                    endDate={endDate}
                    isClearable
                    placeholderText='Before'
                    minDate={startDate}
                    selected={endDate}
                    selectsEnd
                    startDate={startDate}
                    onChange={date => setDates(startDate, date)}
                />
            </InputGroup>
        </Col>
    )
}

export default DateBetweenFilter
