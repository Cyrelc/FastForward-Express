import React from 'react'
import {Col, InputGroup, FormControl} from 'react-bootstrap'

export default function StringFilter(props) {
    return (
        <Col md={4}>
            <InputGroup>
                <InputGroup.Text>{props.filter.name}</InputGroup.Text>
                <FormControl
                    value={props.filter.value ?? ''}
                    onChange={event => props.handleFilterValueChange({...props.filter, value: event.target.value})}
                    placeholder={props.filter.name}
                />
            </InputGroup>
        </Col>
    )
}
