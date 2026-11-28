import React from 'react'
import {Row} from 'react-bootstrap'

import BooleanFilter from '../tableFilters/BooleanFilter'
import DateBetweenFilter from '../tableFilters/DateBetweenFilter'
import NumberBetweenFilter from '../tableFilters/NumberBetweenFilter'
import SelectFilter from '../tableFilters/SelectFilter'
import StringFilter from '../tableFilters/StringFilter'

export default function TableFilters(props) {
    // Use the functional form so several filters updating in the same render can't overwrite each other with a stale list
    function handleFilterValueChange(updatedFilter) {
        const {db_field, value} = updatedFilter
        props.setFilters(current => current.map(filter => {
            if(filter.db_field === db_field)
                return {...filter, value: value}
            return filter
        }))
    }

    return (
        <Row>
            {props.filters && props.filters.map(filter => {
                if(filter.active)
                    switch(filter.type) {
                        case 'BooleanFilter':
                            return <BooleanFilter
                                key={filter.db_field}
                                filter={filter}
                                handleFilterValueChange={handleFilterValueChange}
                            />
                        case 'DateBetweenFilter':
                            return <DateBetweenFilter
                                key={filter.db_field}
                                filter={filter}
                                handleFilterValueChange={handleFilterValueChange}
                            />
                        case 'NumberBetweenFilter':
                            return <NumberBetweenFilter
                                key={filter.db_field}
                                filter={filter}
                                handleFilterValueChange={handleFilterValueChange}
                            />
                        case 'SelectFilter':
                            return <SelectFilter
                                key={filter.db_field}
                                filter={filter}
                                handleFilterValueChange={handleFilterValueChange}
                            />
                        case 'StringFilter':
                            return <StringFilter
                                key={filter.db_field}
                                filter={filter}
                                handleFilterValueChange={handleFilterValueChange}
                            />
                        default:
                            return <div></div>
                            break
                    }
                }
            )}
        </Row>
    )
}

