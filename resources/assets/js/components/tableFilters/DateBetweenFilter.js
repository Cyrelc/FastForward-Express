import React, {useEffect, useState} from 'react'
import {Col, InputGroup} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import {DateTime} from 'luxon'

const DateBetweenFilter = (props) => {
    const [endDate, setEndDate] = useState(null)
    const [startDate, setStartDate] = useState(null)

    useEffect(() => {
        var dates = null
        if(window.location.search.includes(`filter[${props.filter.value}]=`)) {
            const filterValue = window.location.search.split(`[${props.filter.value}]=`)[1].split('&')[0]
            dates = filterValue.split(',')
        }
        if(dates) {
            if(dates[0])
                setStartDate(DateTime.fromFormat(dates[0], 'yyyy-mm-dd').toJSDate())
            if(dates[1])
                setEndDate(DateTime.fromFormat(dates[1], 'yyyy-mm-dd').toJSDate())
        }
    }, [])

    useEffect(() => {
        const formattedStartDate = startDate ? DateTime.fromJSDate(startDate).toFormat('yyyy-mm-dd') : ''
        const formattedEndDate = endDate ? DateTime.fromJSDate(endDate).toFormat('yyyy-mm-dd') : ''
        const filterQueryString = `filter[${props.filter.value}]=${formattedStartDate}${endDate ? ',' + formattedEndDate : ''}`

        props.handleFilterQueryStringChange({target: {name: props.filter.value, type: 'string', value: filterQueryString}})
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
