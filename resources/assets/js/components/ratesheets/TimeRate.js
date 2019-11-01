import React from 'react'
import {Row, Col, InputGroup, FormControl} from 'react-bootstrap'
import DatePicker from 'react-datepicker'

export default function TimeRate(props) {
    return (
        <Row>
            <Col md={3}>
                <DatePicker 
                    key={props.id + '-start'}
                    showTimeSelect
                    showTimeSelectOnly
                    timeIntervals={30}
                    dateFormat='h:mm aa'
                    selected={props.startTime}
                    value={props.startTime}
                    onChange={datetime => props.handleTimeRateChange({target: {name: 'startTime', type:'date', value: datetime}}, props.id)}
                    className='form-control'
                />
            </Col>
            <Col md={3}>
                <DatePicker
                    key={props.id + '-end'}
                    showTimeSelect
                    showTimeSelectOnly
                    timeIntervals={30}
                    dateFormat='h:mm aa'
                    selected={props.endTime}
                    value={props.endTime}
                    onChange={datetime => props.handleTimeRateChange({target: {name: 'endTime', type:'date', value: datetime}}, props.id)}
                    className='form-control'
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
