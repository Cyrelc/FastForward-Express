import React, {useEffect, useState} from 'react'
import {Col, InputGroup, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'

export default function BooleanFilter(props) {
    const [boolState, setBoolState] = useState(false)

    useEffect(() => {
        if(props.filter.value != undefined) {
            const trueOrFalse = props.filter.value == true || props.filter.value == 'true'
            setBoolState(trueOrFalse)
            props.handleFilterValueChange({...props.filter, value: trueOrFalse.toString()})
        } else if (props.filter.default) {
            setBoolState(props.filter.default)
            props.handleFilterValueChange({...props.filter, value: props.filter.default.toString()})
        }
    }, [props.filter.value])

    const setFilterValue = (newBoolState) => {
        props.handleFilterValueChange({...props.filter, value: newBoolState.toString()})
    }

    return(
        <Col md={3}>
            <InputGroup>
                <InputGroup.Text>{props.filter.name}</InputGroup.Text>
                <ToggleButtonGroup name='boolState' type='radio' onChange={setFilterValue} value={boolState}>
                    <ToggleButton
                        checked={!boolState}
                        id={props.filter.db_field + '.false'}
                        variant='secondary'
                        value={false}
                    >False</ToggleButton>
                    <ToggleButton
                        checked={boolState}
                        id={props.filter.db_field + '.true'}
                        variant='secondary'
                        value={true}
                    >True</ToggleButton>
                </ToggleButtonGroup>
            </InputGroup>
        </Col>
    )
}

