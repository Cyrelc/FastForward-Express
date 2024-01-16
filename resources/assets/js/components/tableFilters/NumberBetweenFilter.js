import React, {useEffect, useState} from 'react'
import {Col, InputGroup, FormControl} from 'react-bootstrap'

export default function NumberBetween(props) {
    const [lowerBound, setLowerBound] = useState('')
    const [upperBound, setUpperBound] = useState('')

    useEffect(() => {
        const bounds = props.filter.value?.split(',')
        setLowerBound(bounds[0] || props.filter.defaultLowerBound || '')
        setUpperBound(bounds[1] || props.filter.defaultUpperBound || '')
    }, [props.filter.value])

    useEffect(() => {
        const value = (lowerBound || upperBound) ? `${lowerBound},${upperBound}` : ''
        props.handleFilterValueChange({...props.filter, value: value})
    }, [lowerBound, upperBound])

    return(
        <Col md={6}>
            <InputGroup>
                <InputGroup.Text>{props.filter.name} Between: </InputGroup.Text>
                <FormControl
                    type='number'
                    step={props.filter.step}
                    value={lowerBound}
                    name='lowerBound'
                    onChange={event => setLowerBound(event.target.value)}
                    placeholder='More than'
                    min={props.filter.min ? props.filter.min : null}
                    max={props.filter.max ? props.filter.max : null}
                />
                <InputGroup.Text> and </InputGroup.Text>
                <FormControl
                    type='number'
                    step={props.filter.step}
                    value={upperBound}
                    name='upperBound'
                    onChange={event => setUpperBound(event.target.value)}
                    placeholder='Less than'
                    min={props.filter.min ? props.filter.min : null}
                    max={props.filter.max ? props.filter.max : null}
                />
            </InputGroup>
        </Col>
    )
}


