import React from 'react'
import {Col, InputGroup, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'

export default function BooleanFilter(props) {
    const boolState = props.filter.value === true || props.filter.value === 'true'

    const setFilterValue = newBoolState => {
        props.handleFilterValueChange({...props.filter, value: newBoolState.toString()})
    }

    return(
        <Col md={3}>
            <InputGroup>
                <InputGroup.Text>{props.filter.name}</InputGroup.Text>
                <ToggleButtonGroup name={props.filter.db_field + '.boolState'} type='radio' onChange={setFilterValue} value={boolState}>
                    <ToggleButton
                        id={props.filter.db_field + '.false'}
                        variant='secondary'
                        value={false}
                    >False</ToggleButton>
                    <ToggleButton
                        id={props.filter.db_field + '.true'}
                        variant='secondary'
                        value={true}
                    >True</ToggleButton>
                </ToggleButtonGroup>
            </InputGroup>
        </Col>
    )
}
