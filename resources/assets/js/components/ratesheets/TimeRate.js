import React from 'react'
import {Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import Moment from 'moment'
import momentLocalizer from 'react-widgets-moment'
import {DateTimePicker} from 'react-widgets'

export default function TimeRate(props) {
    Moment.locale('en')
    momentLocalizer()

    return (
        <Row>
            <Col md={3}>
                <DateTimePicker
                    key={props.id + '-start'}
                    step={30}
                    date={false}
                    value={props.start_time}
                    onChange={datetime => props.handleTimeRateChange({target: {name: 'startTime', type:'date', value: datetime}}, props.id)}
                    className='pad-top'
                />
            </Col>
            <Col md={3}>
                <DateTimePicker
                    key={props.id + '-end'}
                    step={30}
                    date={false}
                    value={props.end_time}
                    onChange={datetime => props.handleTimeRateChange({target: {name: 'startTime', type:'date', value: datetime}}, props.id)}
                    className='pad-top'
                />
            </Col>
            <Col md={4}>
                <InputGroup>
                    <InputGroup.Prepend>
                        <InputGroup.Text>Price: </InputGroup.Text>
                    </InputGroup.Prepend>
                    <FormControl
                        key={props.id + '-cost'}
                        type='number'
                        step='0.01'
                        name='cost'
                        value={props.cost}
                        onChange={event => props.handleTimeRateChange(event, props.id)}
                    />
                </InputGroup>
            </Col>
        </Row>
    )
}
