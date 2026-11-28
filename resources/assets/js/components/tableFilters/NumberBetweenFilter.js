import React from 'react'
import {Col, InputGroup, FormControl} from 'react-bootstrap'

// Value format (parsed by App\Http\Filters\NumberBetween): "lower,upper", where either side may be empty
export default function NumberBetween(props) {
    const [lowerBound = '', upperBound = ''] = props.filter.value ? props.filter.value.split(',') : []

    const setBounds = (lower, upper) => {
        const value = (lower || upper) ? `${lower},${upper}` : ''
        props.handleFilterValueChange({...props.filter, value})
    }

    return(
        <Col md={6}>
            <InputGroup>
                <InputGroup.Text>{props.filter.name} Between: </InputGroup.Text>
                <FormControl
                    type='number'
                    step={props.filter.step}
                    value={lowerBound}
                    name='lowerBound'
                    onChange={event => setBounds(event.target.value, upperBound)}
                    placeholder='More than'
                    min={props.filter.min ?? null}
                    max={props.filter.max ?? null}
                />
                <InputGroup.Text> and </InputGroup.Text>
                <FormControl
                    type='number'
                    step={props.filter.step}
                    value={upperBound}
                    name='upperBound'
                    onChange={event => setBounds(lowerBound, event.target.value)}
                    placeholder='Less than'
                    min={props.filter.min ?? null}
                    max={props.filter.max ?? null}
                />
            </InputGroup>
        </Col>
    )
}
