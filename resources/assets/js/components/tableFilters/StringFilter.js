import React, {useEffect, useState} from 'react'
import {Col, InputGroup, FormControl} from 'react-bootstrap'

export default function StringFilter(props) {
    const [myString, setMyString] = useState('')

    useEffect(() => {
        setMyString(props.filter.value ?? '')
    }, [props.filter.value])

    const handleStringChange = (newString) => {
        props.handleFilterValueChange({...props.filter, value: newString})
    }

    return (
        <Col md={4}>
            <InputGroup>
                <InputGroup.Text>{props.filter.name}</InputGroup.Text>
                <FormControl
                    value={myString}
                    onChange={event => handleStringChange(event.target.value)}
                    onBlur={event => handleStringChange(event.target.value)}
                    placeholder={props.filter.name}
                />
            </InputGroup>
        </Col>
    )
}

