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
            this.handleChange(filterValue === 'true')
        } else if (this.props.filter.default) {
            this.setState({boolState: this.props.filter.default})
            this.handleChange(this.props.filter.default)
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
                    <InputGroup.Text>{this.props.filter.name}</InputGroup.Text>
                    <ToggleButtonGroup name='boolState' type='radio' onChange={this.handleChange} value={this.state.boolState}>
                        <ToggleButton
                            checked={!this.state.boolState}
                            id={this.props.filter.value + '.false'}
                            variant='secondary'
                            value={false}
                        >False</ToggleButton>
                        <ToggleButton
                            checked={this.state.boolState}
                            id={this.props.filter.value + '.true'}
                            variant='secondary'
                            value={true}
                        >True</ToggleButton>
                    </ToggleButtonGroup>
                </InputGroup>
            </Col>
        )
    }
}

