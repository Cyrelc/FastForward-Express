import React, {Component} from 'react'
import {Card, Col, InputGroup, Row} from 'react-bootstrap'

import BooleanFilter from '../tableFilters/BooleanFilter'
import DateBetweenFilter from '../tableFilters/DateBetweenFilter'
import NumberBetweenFilter from '../tableFilters/NumberBetweenFilter'
import SelectFilter from '../tableFilters/SelectFilter'

export default function TableFilters(props) {
    function handleFilterQueryStringChange(event) {
        const {name, value} = event.target
        const filters = props.filters.map(filter => {
            if(filter.value === name)
                return {...filter, queryString: value}
            return filter
        })
        props.handleChange({target: {name: 'filters', type: 'array', value: filters}})
    }

    return (
        <Row>
            {props.filters && props.filters.map(filter => {
                if(filter.active)
                    switch(filter.type) {
                        case 'BooleanFilter':
                            return <BooleanFilter
                                key={filter.value}
                                filter={filter}
                                handleFilterQueryStringChange={handleFilterQueryStringChange}
                            />
                        case 'DateBetweenFilter':
                            return <DateBetweenFilter
                                key={filter.value}
                                filter={filter}
                                handleFilterQueryStringChange={handleFilterQueryStringChange}
                            />
                        case 'NumberBetweenFilter':
                            return <NumberBetweenFilter
                                key={filter.value}
                                filter={filter}
                                handleFilterQueryStringChange={handleFilterQueryStringChange}
                            />
                        case 'SelectFilter':
                            return <SelectFilter
                                key={filter.value}
                                filter={filter}
                                handleFilterQueryStringChange={handleFilterQueryStringChange}
                            />
                        case 'StringSearchFilter':
                            break
                        default:
                            break
                    }
                }
            )}
        </Row>
    )
}

