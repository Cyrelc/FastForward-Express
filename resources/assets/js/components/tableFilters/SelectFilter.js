import React, {Component} from 'react'
import {Col, InputGroup} from 'react-bootstrap'
import Select from 'react-select'
import CreatableSelect from 'react-select/creatable'

export default class SelectFilter extends Component {
    constructor() {
        super()
        this.state = {
            dbField: '',
            selections: [],
            selected: undefined
        }
        this.handleFilterChange = this.handleFilterChange.bind(this)
    }

    componentDidUpdate(prevProps) {
        if(prevProps.filter.selections == this.props.filter.selections)
            return
        // we use .some, so that we can use the == operator, and compare regardless of type (ints to strings)
        const selected = this.props.filter.selections.filter(selection => selectedValues.some(selectedValue => selectedValue == selection.value))
        this.setState({dbField: this.props.filter.value, selections: this.props.filter.selections, selected: selected})
        this.handleFilterChange(selected)
    }

    // Two types of select:
    // 1) Selectable options are passed as an array of objects
    // 2) Source is creatable (i.e. any value goes)
    componentDidMount() {
        const filterValue = window.location.search.includes('filter[' + this.props.filter.value + ']=') ? window.location.search.split('[' + this.props.filter.value + ']=')[1].split('&')[0] : undefined

        const selectedValues = filterValue === undefined ? [] : filterValue.split(',').filter(value => value)
        if(this.props.filter.selections) {
            const selected = this.props.filter.selections.filter(selection => selectedValues.some(selectedValue => selectedValue == selection.value))
            this.setState({dbField: this.props.filter.value, selections: this.props.filter.selections, selected: selected})
        } else if(this.props.filter.creatable)
            this.setState({dbField: this.props.filter.value, selections: [], selected: filterValue})
    }

    handleFilterChange(selected) {
        var queryString = null
        if(selected) {
            queryString = 'filter[' + this.state.dbField + ']='
            for(const [key, value] of Object.entries(selected))
                queryString += value.value + ','
            // chop off the trailing comma
            queryString = queryString.substr(0, queryString.length - 1)
        } else
            queryString = ""
        this.props.handleFilterQueryStringChange({target: {name: this.state.dbField, type: 'string', value: queryString}})
        this.setState({selected: selected})
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
                            options={this.state.selections}
                            value={this.state.selected}
                            onChange={selected => this.handleFilterChange(selected)}
                            isMulti={this.props.filter.isMulti}
                        /> :
                        <Select
                            options={this.state.selections}
                            value={this.state.selected}
                            onChange={selected => this.handleFilterChange(selected)}
                            isMulti={this.props.filter.isMulti}
                        />
                    }
                </InputGroup>
            </Col>
        )
    }
}
