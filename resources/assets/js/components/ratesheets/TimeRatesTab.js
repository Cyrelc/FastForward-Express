import React from 'react'
import {Button, Card, Col, Row} from 'react-bootstrap'

import TimeRate from './TimeRate'

export default function TimeRatesTab(props) {
    const {timeRates, setTimeRates} = props

    function addTimeRate() {
        setTimeRates(timeRates.concat([{
            name: '',
            price: 0.00,
            brackets: [{startDay: null, startTime: 0, endDay: null, endTime: 0}]
        }]))
    }

    function deleteTimeRate(index) {
        if(confirm('Are you sure you wish to delete this Time Rate?\n\nThis action can not be undone'))
            setTimeRates(props.timeRates.filter((timeRate, i) => i != index))
    }

    function handleTimeRateChange(modifiedTimeRate, index) {
        const updated = props.timeRates.map((timeRate, i) => {
            if(i == index)
                return modifiedTimeRate
            return timeRate
        })
        setTimeRates(updated)
    }

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <Button variant='success' onClick={addTimeRate}><i className='fas fa-plus'></i> New</Button>
                    </Col>
                    <Col md={10}><h4 className='text-muted'>Time Rates</h4></Col>
                </Row>
            </Card.Header>
            <Card.Body>
                {timeRates && timeRates.map((timeRate, index) => 
                    <TimeRate
                        deleteTimeRate={deleteTimeRate}
                        handleTimeRateChange={handleTimeRateChange}
                        key={index}
                        index={index}
                        timeRate={timeRate}
                    />
                )}
            </Card.Body>
        </Card>
    )
}

