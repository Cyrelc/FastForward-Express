import React, {Component} from 'react'
import {Col, InputGroup} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

export default class DateFilterBetween extends Component {
    constructor() {
        super()
        this.state = {
            endDate: null,
            startDate: null,
            // filterString: '',
            dbField: ''
        }
        this.handleDateFilterChange = this.handleDateFilterChange.bind(this)
    }

    componentDidMount() {
        var dates = null
        if(window.location.search.includes('filter[' + this.props.filter.value + ']=')) {
            //get everything between 'filter[db_field_name]=' and the next filter (if there is one) beginning with &
            const filterValue = window.location.search.split('[' + this.props.filter.value + ']=')[1].split('&')[0]
            dates = filterValue.split(',')
        }
        if(dates) {
            const timezoneOffset = new Date().getTimezoneOffset();
            const startDate = new Date(dates[0]).addMinutes(timezoneOffset)
            const endDate = new Date(dates[1]).addMinutes(timezoneOffset)
            this.setState({
                dbField: this.props.filter.value,
                endDate: dates[1] ? endDate : null,
                startDate: dates[0] ? startDate : null
            }, () => this.handleDateFilterChange())
        }
        else
            this.setState({
                dbField: this.props.filter.value,
            }, () => this.handleDateFilterChange())
    }

    handleDateFilterChange(date = null, type = null) {
        console.log(date, type)
        const startDate = type === 'startDate' ? date : this.state.startDate
        const endDate = type === 'endDate' ? date : this.state.endDate
        const formattedStartDate = startDate === null ? '' : new Date(startDate).toISOString().split('T')[0]
        const formattedEndDate = endDate === null ? '' : new Date(endDate).toISOString().split('T')[0]
        this.setState({startDate: startDate, endDate: endDate})
        var filterQueryString = null
        if(formattedStartDate || formattedEndDate)
            filterQueryString = 'filter[' + this.state.dbField + ']=' + formattedStartDate + (endDate ? ',' + formattedEndDate : '')

        this.props.handleFilterQueryStringChange({target: {name: this.props.filter.value, type: 'string', value: filterQueryString}})
    }

    render() {
        return(
            <Col md={6}>
                <InputGroup>
                    <InputGroup.Prepend>
                        <InputGroup.Text>{this.props.filter.name} Between: </InputGroup.Text>
                    </InputGroup.Prepend>
                    <DatePicker
                        className='form-control'
                        dateFormat='MMMM d, yyyy'
                        isClearable
                        placeholderText='After'
                        selected={this.state.startDate}
                        onChange={date => this.handleDateFilterChange(date, 'startDate')}
                        />
                    <InputGroup.Append>
                        <InputGroup.Text>And</InputGroup.Text>
                    </InputGroup.Append>
                    <DatePicker
                        className='form-control'
                        dateFormat='MMMM d, yyyy'
                        isClearable
                        placeholderText='Before'
                        selected={this.state.endDate}
                        onChange={date => this.handleDateFilterChange(date, 'endDate')}
                        />
                </InputGroup>
            </Col>
        )
    }
}

