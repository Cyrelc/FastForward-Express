import React, {Component} from 'react'
import {Col, InputGroup, FormControl} from 'react-bootstrap'

export default class NumberBetween extends Component {
    constructor() {
        super()
        this.state = {
            lowerBound: '',
            upperBound: ''
        }
        this.handleChange = this.handleChange.bind(this)
    }

    componentDidMount() {
        if(window.location.search.includes('filter[' + this.props.filter.value + ']=')) {
            const filterValue = window.location.search.split('[' + this.props.filter.value + ']=')[1].split('&')[0]
            const bounds = filterValue.split(',')
            const lowerBound = bounds[0] ? bounds[0] : ''
            const upperBound = bounds[1] ? bounds[1] : ''
            const filterQueryString = 'filter[' + this.props.filter.value + ']=' + lowerBound + ',' + upperBound
            this.props.handleFilterQueryStringChange({target: {name: this.props.filter.value, type: 'string', value: filterQueryString}})
            this.setState({lowerBound: lowerBound, upperBound: upperBound})
        } else if (this.props.filter.defaultLowerBound || this.props.filter.defaultUpperBound) {
            const lowerBound = this.props.filter.defaultLowerBound ? this.props.filter.defaultLowerBound : ''
            const upperBound = this.props.filter.defaultUpperBound ? this.props.filter.defaultUpperBound : ''
            const filterQueryString = 'filter[' + this.props.filter.value + ']=' + lowerBound + ',' + upperBound
            this.props.handleFilterQueryStringChange({target: {name: this.props.filter.value, type: 'string', value: filterQueryString}})
            this.setState({lowerBound: lowerBound, upperBound: upperBound})
        }
    }

    handleChange(event) {
        const {name, value, type} = event.target
        var filterQueryString = null
        if(name == 'lowerBound')
            filterQueryString = 'filter[' + this.props.filter.value + ']=' + value + ',' + this.state.upperBound
        else
            filterQueryString = 'filter[' + this.props.filter.value + ']=' + this.state.lowerBound + ',' + value
        this.props.handleFilterQueryStringChange({target: {name: this.props.filter.value, type: 'string', value: filterQueryString}})
        this.setState({[name]: value})
    }

    render() {
        return(
            <Col md={4}>
                <InputGroup>
                    <InputGroup.Text>{this.props.filter.name} Between: </InputGroup.Text>
                    <FormControl
                        type='number'
                        step={this.props.filter.step}
                        value={this.state.lowerBound}
                        name='lowerBound'
                        onChange={this.handleChange}
                        placeholder='More than'
                        min={this.props.filter.min ? this.props.filter.min : null}
                        max={this.props.filter.max ? this.props.filter.max : null}
                    />
                    <InputGroup.Text> and </InputGroup.Text>
                    <FormControl
                        type='number'
                        step={this.props.filter.step}
                        value={this.state.upperBound}
                        name='upperBound'
                        onChange={this.handleChange}
                        placeholder='Less than'
                        min={this.props.filter.min ? this.props.filter.min : null}
                        max={this.props.filter.max ? this.props.filter.max : null}
                    />
                </InputGroup>
            </Col>
        )
    }
}


