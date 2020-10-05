import React, {Component} from 'react'
import {Col, InputGroup} from 'react-bootstrap'
import Select from 'react-select'
import CreatableSelect from 'react-select/creatable'

export default class SelectFilter extends Component {
    constructor() {
        super()
        this.state = {
            dbField: '',
            options: [],
            selectedOptions: undefined
        }
        this.handleFilterChange = this.handleFilterChange.bind(this)
    }

    // Three types of select: 
    // 1) Options are from fetch call
    // 2) Options are passed as an array of objects
    // 3) Source is creatable (i.e. any value goes)
    componentDidMount() {
        const filterValue = window.location.search.includes('filter[' + this.props.filter.value + ']=') ? window.location.search.split('[' + this.props.filter.value + ']=')[1].split('&')[0] : undefined
        const selectedValues = filterValue === undefined ? [] : filterValue.split(',').filter(value => value)
        if(this.props.filter.fetchUrl)
            makeFetchRequest(this.props.filter.fetchUrl, data => {
                var options = data
                if(this.props.filter.optionName || this.props.filter.optionValue)
                    options = data.map(option => {
                        return {
                            label: this.props.filter.optionName ? option[this.props.filter.optionName] : option.label,
                            value: this.props.filter.optionValue ? option[this.props.filter.optionValue] : option.value
                        }
                    })
                // we use .some, so that we can use the == operator, and compare regardless of type (ints to strings)
                const selectedOptions = options.filter(option => selectedValues.some(selectedValue => selectedValue == option.value))
                this.setState({dbField: this.props.filter.value, options: options, selectedOptions: selectedOptions})
                this.handleFilterChange(selectedOptions)
            })
        // this.setState({dbField: this.props.filter.value}, () => {
        //     if(this.props.filter.creatable && window.location.href.indexOf('[' + this.props.filter.value + ']=') > -1) {
        //         const filterValue = window.location.search.split('[' + this.props.filter.value + ']=')[1].split('&')[0]
        //         const values = filterValue.split(',').filter(value => value).map(value => {
        //             return {label: value, value: value}
        //         })
        //         this.handleFilterChange(values)
        //     }
        // })
    }

    handleFilterChange(selectedOptions) {
        var queryString = null
        if(selectedOptions) {
            queryString = 'filter[' + this.state.dbField + ']='
            for(const [key, value] of Object.entries(selectedOptions))
                queryString += value.value + ','
            // chop off the trailing comma
            queryString = queryString.substr(0, queryString.length - 1)
        } else
            queryString = ""
        this.props.handleFilterQueryStringChange({target: {name: this.state.dbField, type: 'string', value: queryString}})
        this.setState({selectedOptions: selectedOptions})
    }

    render() {
        return(
            <Col md={6}>
                <InputGroup>
                    <InputGroup.Prepend>
                        <InputGroup.Text>{this.props.filter.name}</InputGroup.Text>
                    </InputGroup.Prepend>
                    {this.props.filter.creatable ?
                        <CreatableSelect
                            options={this.state.options}
                            value={this.state.selectedOptions}
                            onChange={selectedOptions => this.handleFilterChange(selectedOptions)}
                            isMulti={this.props.filter.isMulti}
                        /> :
                        <Select
                            options={this.state.options}
                            value={this.state.selectedOptions}
                            onChange={selectedOptions => this.handleFilterChange(selectedOptions)}
                            isMulti={this.props.filter.isMulti}
                        />
                    }
                </InputGroup>
            </Col>
        )
    }
}
