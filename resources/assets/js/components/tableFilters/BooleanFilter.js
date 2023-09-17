import React, {useEffect, useState} from 'react'
import {Col, InputGroup, ToggleButton, ToggleButtonGroup} from 'react-bootstrap'
import {useLocation} from 'react-router-dom'

export default function BooleanFilter(props) {
    const [boolState, setBoolState] = useState(false)

    const filterString = `filter[${props.filter.value}]`
    const location = useLocation()
    const queryParams = new URLSearchParams(location.search)

    useEffect(() => {
        if(queryParams.has(filterString))
            setFilterValue(queryParams.get(filterString) == true)
        else if (props.filter.default)
            setFilterValue(props.filter.default == true)
    }, [])

    const setFilterValue = (newBoolState) => {
        setBoolState(newBoolState)
        props.handleFilterQueryStringChange({target: {name: props.filter.value, type: 'boolean', value: `${filterString}=${newBoolState}`}})
    }

    return(
        <Col md={3}>
            <InputGroup>
                <InputGroup.Text>{props.filter.name}</InputGroup.Text>
                <ToggleButtonGroup name='boolState' type='radio' onChange={setFilterValue} value={boolState}>
                    <ToggleButton
                        checked={!boolState}
                        id={props.filter.value + '.false'}
                        variant='secondary'
                        value={false}
                    >False</ToggleButton>
                    <ToggleButton
                        checked={boolState}
                        id={props.filter.value + '.true'}
                        variant='secondary'
                        value={true}
                    >True</ToggleButton>
                </ToggleButtonGroup>
            </InputGroup>
        </Col>
    )
}

