import React from 'react'
import {Button, Col, InputGroup, FormControl, Row, Table} from 'react-bootstrap'
import DatePicker from 'react-datepicker'
import Select from 'react-select'

const daysOfTheWeek = [
    {label: 'Sunday', value: 0},
    {label: 'Monday', value: 1},
    {label: 'Tuesday', value: 2},
    {label: 'Wednesday', value: 3},
    {label: 'Thursday', value: 4},
    {label: 'Friday', value: 5},
    {label: 'Saturday', value: 6}
]

export default function TimeRate(props) {

    function addTimeBracket() {
        const brackets = props.timeRate.brackets.concat([{startDayOfWeek: null, startTime: new Date(), endDayOfWeek: null, endTime: new Date()}])
        props.handleTimeRateChange({...props.timeRate, brackets: brackets}, props.index)
    }

    function deleteTimeBracket(index) {
        if(index == 0)
            return
        const brackets = props.timeRate.brackets.filter((bracket, i) => i != index)
        props.handleTimeRateChange({...props.timeRate, brackets: brackets}, props.index)
    }

    function handleTimeRateChange(event) {
        const {name, value} = event.target
        if(name === 'name' || name === 'price')
            props.handleTimeRateChange({...props.timeRate, [name]: value}, props.index)
        else {
            const brackets = props.timeRate.brackets.map((bracket, index) => {
                if(event.target.dataset.timebracketindex === index)
                    return {...bracket, [name]: value}
                return bracket
            })
            props.handleTimeRateChange({...props.timeRate, brackets: brackets}, props.index)
        }
    }

    return (
        <Row>
            <Col md={3}>
                <InputGroup size='sm'>
                    <Button variant='danger' onClick={() => props.deleteTimeRate(props.index)}><i className='fas fa-trash'></i></Button>
                    <InputGroup.Text>Name</InputGroup.Text>
                    <FormControl
                        name='name'
                        value={props.timeRate.name}
                        onChange={handleTimeRateChange}
                    />
                </InputGroup>
                <InputGroup size='sm'>
                    <InputGroup.Text>Price: $</InputGroup.Text>
                    <FormControl
                        key={props.id + '-price'}
                        type='number'
                        step='0.01'
                        name='price'
                        value={props.timeRate.price}
                        onChange={handleTimeRateChange}
                    />
                </InputGroup>
            </Col>
            <Col md={9}>
                <Table size='sm'>
                    <thead>
                        <tr>
                            <th><Button variant='success' onClick={addTimeBracket} size='sm'><i className='fas fa-plus'></i></Button></th>
                            <th>Start Day/Time</th>
                            <th>End Day/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                    {props.timeRate.brackets.map((bracket, index) => 
                        <tr key={props.timeRate.name + '.bracket.' + index}>
                            <td>
                                <Button variant='danger' onClick={() => deleteTimeBracket(index)} size='sm'><i className='fas fa-trash'></i></Button>
                            </td>
                            <td>
                                <InputGroup size='sm'>
                                    <InputGroup.Text>Start: </InputGroup.Text>
                                    <Select
                                        isClearable
                                        options={daysOfTheWeek}
                                        value={bracket.startDayOfWeek}
                                        name='startDayOfWeek'
                                        isSearchable
                                        onChange={value => handleTimeRateChange({target: {name: 'startDayOfWeek', type: 'date', value: value, dataset: {timebracketindex: index}}})}
                                    />
                                    <DatePicker
                                        key={props.id + '-start'}
                                        showTimeSelect
                                        showTimeSelectOnly
                                        timeIntervals={15}
                                        dateFormat='h:mm aa'
                                        selected={bracket.startTime}
                                        value={bracket.startTime}
                                        onChange={datetime => handleTimeRateChange({target: {name: 'startTime', type:'date', value: datetime, dataset: {timebracketindex: index}}})}
                                        className='form-control'
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            </td>
                            <td>
                                <InputGroup size='sm'>
                                    <InputGroup.Text>End: </InputGroup.Text>
                                    <Select
                                        isClearable
                                        options={daysOfTheWeek}
                                        value={bracket.endDayOfWeek}
                                        name='endDayOfWeek'
                                        isSearchable
                                        onChange={value => handleTimeRateChange({target: {name: 'endDayOfWeek', type: 'date', value: value, dataset: {timebracketindex: index}}})}
                                    />
                                    <DatePicker
                                        key={props.id + '-end'}
                                        showTimeSelect
                                        showTimeSelectOnly
                                        timeIntervals={15}
                                        dateFormat='h:mm aa'
                                        selected={bracket.endTime}
                                        value={bracket.endTime}
                                        onChange={datetime => handleTimeRateChange({target: {name: 'endTime', type:'date', value: datetime, dataset: {timebracketindex: index}}})}
                                        className='form-control'
                                        wrapperClassName='form-control'
                                    />
                                </InputGroup>
                            </td>
                        </tr>
                    )}
                    </tbody>
                </Table>
            </Col>
            <Col md={12}>
                <hr/>
            </Col>
        </Row>
    )
}
