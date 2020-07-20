import React, {Component} from 'react'
import {Col, InputGroup, FormControl} from 'react-bootstrap'
import Select from 'react-select'

export default class SelectFilter extends Component {
    constructor() {
        super()
        this.state = {
            dbField: '',
            selectedOptions: undefined
        }
        this.handleFilterChange = this.handleFilterChange.bind(this)
    }

    componentDidMount() {
        this.setState({dbField: this.props.filter.value})
    }

    componentDidUpdate(prevProps, prevState) {
        if(!prevProps.filter.filterOptions && this.props.filter.filterOptions && window.location.search.includes('filter[' + this.props.filter.value + ']=')) {
            const filterValue = window.location.search.split('[' + this.props.filter.value + ']=')[1].split('&')[0]
            const values = filterValue.split(',')
            // const selectedOptions = this.props.filter.filterOptions.filter(option => values.includes(option.value))
            const selectedOptions = this.props.filter.filterOptions.filter(option => values.includes(option.value.toString()))
            this.handleFilterChange(selectedOptions)
        }
    }

    handleFilterChange(selectedOptions) {
        var queryString = null
        if(selectedOptions) {
            queryString = 'filter[' + this.state.dbField + ']='
            for(const [key, value] of Object.entries(selectedOptions))
                queryString += value.value + ','
        }
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
                    <Select
                        options={this.props.filter.filterOptions}
                        value={this.state.selectedOptions}
                        onChange={selectedOptions => this.handleFilterChange(selectedOptions)}
                        isMulti={this.props.filter.isMulti}
                    />
                </InputGroup>
            </Col>
        )
    }
}
