import React from 'react'
import {Row} from 'react-bootstrap'

import BooleanFilter from '../tableFilters/BooleanFilter'
import DateBetweenFilter from '../tableFilters/DateBetweenFilter'
import NumberBetweenFilter from '../tableFilters/NumberBetweenFilter'
import SelectFilter from '../tableFilters/SelectFilter'

export default function ReduxTableFilters(props) {
    return (
        <Row>
            {props.filters && props.filters.map(filter => {
                if(filter.active)
                    switch(filter.type) {
                        case 'BooleanFilter':
                            return <BooleanFilter
                                key={filter.value}
                                filter={filter}
                                setFilterQueryString={props.setFilterQueryString}
                            />
                        case 'DateBetweenFilter':
                            return <DateBetweenFilter
                                key={filter.value}
                                filter={filter}
                                setFilterQueryString={props.setFilterQueryString}
                            />
                        case 'NumberBetweenFilter':
                            return <NumberBetweenFilter
                                key={filter.value}
                                filter={filter}
                                setFilterQueryString={props.setFilterQueryString}
                            />
                        case 'SelectFilter':
                            return <SelectFilter
                                key={filter.value}
                                filter={filter}
                                setFilterQueryString={props.setFilterQueryString}
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

