import React, {Component} from 'react'
import {Card, Col, InputGroup, Row} from 'react-bootstrap'

import DateBetweenFilter from '../tableFilters/DateBetweenFilter'
import SelectFilter from '../tableFilters/SelectFilter'
import NumberBetweenFilter from '../tableFilters/NumberBetweenFilter'

export default class TableFilters extends Component {
    constructor() {
        super()
        this.state = {

        }
        this.handleFilterQueryStringChange = this.handleFilterQueryStringChange.bind(this)
    }

    // On mount we have to parse the query string in the URL to see if any values were set. 
    // Those filters that are matched, are set to active
    componentDidMount() {
        const search = window.location.search
        const filters = this.props.filters.map(filter => {
            if(search.includes('filter[' + filter.value + ']=')) {
                return {...filter, active: true}
            }
            return filter
        })
        this.props.handleChange({target: {name: 'filters', type: 'array', value: filters}})
    }

    handleFilterQueryStringChange(event) {
        const {name, value} = event.target
        const filters = this.props.filters.map(filter => {
            if(filter.value === name)
                return {...filter, queryString: value}
            return filter
        })
        this.props.handleChange({target: {name: 'filters', type: 'array', value: filters}})
    }

    render () {
        return (
            <Row>
                {
                    this.props.filters && this.props.filters.map(filter => {
                        if(filter.active)
                            switch(filter.type) {
                                case 'DateBetweenFilter':
                                    return <DateBetweenFilter
                                        key={filter.value}
                                        filter={filter}
                                        handleFilterQueryStringChange={this.handleFilterQueryStringChange}
                                    />
                                    break
                                case 'NumberBetweenFilter':
                                    return <NumberBetweenFilter
                                        key={filter.value}
                                        filter={filter}
                                        handleFilterQueryStringChange={this.handleFilterQueryStringChange}
                                    />
                                    break
                                case 'SelectFilter':
                                    return <SelectFilter
                                        key={filter.value}
                                        filter={filter}
                                        handleFilterQueryStringChange={this.handleFilterQueryStringChange}
                                    />
                                    break
                                default:
                                    break
                            }
                    }
                )}
            </Row>
        )
    }
}

// function formatSelectFilter(event, filterSettings, handleChange) {
//     console.log(event);
//     handleChange({[filterSettings.selectedStateName]: event})
// }

/*
 * @param[in] filterSettings: object containing the following attributes: 
 *  databaseField: name of the database field exactly
 *  isMulti: boolean telling whether this select field is multi or not
 *  label: the database object field to use as the label for the selectpicker
 *  filterStringPrefix: the prefix that the server will accept for the filter; optional if using databaseField; example: 'filter[account_id]='
 *  name: the display name for the filter
 *  selectedStateName: where to store the selected options in state
 * @param[in] options: the array of options to display in the filter dropdown
 * @param[in] selected: the state object containing the currently selected values
 * 
 */
// export function SelectFilter(props) {
//     return (
//         <Row>
//             <Col md={12}>
//                 {props.filterSettings.name}<br/>
//             </Col>
//             <Col md={12}>
//                 <Select
//                     options={props.options}
//                     value={props.selected}
//                     getOptionLabel={type => type[props.filterSettings.label]}
//                     onChange={(event) => formatSelectFilter(event, props.filterSettings, props.handleChange)}
//                     isMulti={props.filterSettings.isMulti}
//                 />
//             </Col>
//             <hr/>
//         </Row>
//     )
// }

