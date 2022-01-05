import React, {Component} from 'react'
import {Col, InputGroup, FormControl} from 'react-bootstrap'

export default class StringFilter extends Component {
    constructor() {
        super()
        this.state = {
            string: ''
        }
        this.handleChange = this.handleChange.bind(this)
    }

    componentDidMount() {
        if(window.location.search.includes('filter[' + this.props.filter.value + ']=')) {
            const filterValue = window.location.search.split('[' + this.props.filter.value + ']=')[1].split('&')[0]
            this.setState({string: filterValue})
        }
    }

    handleChange(event) {
        const {name, value} = event.target
        // const filterQueryString = 'filter[' + this.props.filter.value + ']=' + value
        this.setState({string: value})
        // this.props.handleFilterQueryStringChange({target: {name: this.props.filter.value, type: 'string', value: filterQueryString}})
    }

    render() {
        return (
            <Col md={4}>
                <InputGroup>
                    <InputGroup.Text>{this.props.filter.name}</InputGroup.Text>
                    <FormControl
                        value={this.state.string}
                        onChange={this.handleChange}
                        onBlur={event => this.props.handleFilterQueryStringChange({target: {name: this.props.filter.value, type: 'string', value: 'filter[' + this.props.filter.value + ']=' + event.target.value}})}
                        placeholder={this.props.filter.name}
                    />
                </InputGroup>
            </Col>
        )
    }
}

