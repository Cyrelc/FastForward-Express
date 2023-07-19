import React from 'react'
import {Button, Card, Col, Row} from 'react-bootstrap'

import WeightRate from './WeightRate'

export default function WeightRatesTab(props) {

    function addWeightRate() {
        props.handleChange({target: {name: 'weightRates', type: 'object', value: props.weightRates.concat([{
            name: '',
            brackets: [{price: 0.00, kgmax: 0.00, lbmax: 0.00, additionalXKgs: 0.00, additionalXLbs: 0.00}],
        }])}})
    }

    function deleteWeightRate(index) {
        if(confirm('Are you sure you wish to delete this weightRate?\n\nThis action cannot be undone.'))
            props.handleChange({target: {name: 'weightRates', type: 'object', value: props.weightRates.filter((weightRate, i) => i != index)}})
    }
    
    function handleWeightRateChange(modifiedWeightRate, index) {
        if(index === undefined) {
            console.log("ERROR: handleWeightRateChange called with invalid index. Aborting")
            return
        }
        const weightRates = props.weightRates.map((weightRate, i) => {
            if(i === index)
                return modifiedWeightRate
            else
                return weightRate
        })
        props.handleChange({target: {name: 'weightRates', type: 'object', value: weightRates}})
    }

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}><Button variant='success' onClick={addWeightRate}><i className='fas fa-plus'></i> New</Button></Col>
                    <Col md={10}><h4 className='text-muted'>Weight Rates</h4></Col>
                </Row>
            </Card.Header>
            <Card.Body>
                {props.weightRates && props.weightRates.map((weightRate, index) =>
                    <WeightRate
                        deleteWeightRate={deleteWeightRate}
                        handleWeightRateChange={handleWeightRateChange}
                        index={index}
                        key={index}
                        weightRate={weightRate}
                    />
                )}
            </Card.Body>
        </Card>
    )
}

