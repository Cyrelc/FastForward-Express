import React, {Component} from 'react'
import {Col, InputGroup, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'


export default class BooleanFilter extends Component {
    constructor() {
        super()
        this.state = {
            boolState: false,
            dbField: ''
        }
        this.handleChange = this.handleChange.bind(this)
    }

    componentDidMount() {
        if(window.location.search.includes('filter[' + this.props.filter.value + ']=')) {
            const filterValue = window.location.search.split('[' + this.props.filter.value + ']=')[1].split('&')[0]
            this.setState({boolState: filterValue === 'true'})
        }
    }

    handleChange(event) {
        this.setState({boolState: event})
        this.props.handleFilterQueryStringChange({target: {name: this.props.filter.value, type: 'boolean', value: 'filter[' + this.props.filter.value + ']=' + event}})
    }

    render() {
        return(
            <Col md={3}>
                <InputGroup>
                    <InputGroup.Prepend>
                        <InputGroup.Text>{this.props.filter.name}</InputGroup.Text>
                    </InputGroup.Prepend>
                    <ToggleButtonGroup name='boolState' type='radio' onChange={this.handleChange} value={this.state.boolState}>
                        <ToggleButton variant='secondary' value={false}>False</ToggleButton>
                        <ToggleButton variant='secondary' value={true}>True</ToggleButton>
                    </ToggleButtonGroup>
                </InputGroup>
            </Col>
        )
    }
}

