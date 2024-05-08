import React from 'react'
import {Button, Card, Col, Row} from 'react-bootstrap'

import WeightRate from './WeightRate'

export default function WeightRatesTab(props) {
    const {weightRates, setWeightRates} = props

    function addWeightRate() {
        setWeightRates(weightRates.concat([{
            name: '',
            brackets: [{price: 0.00, kgmax: 0.00, lbmax: 0.00, additionalXKgs: 0.00, additionalXLbs: 0.00}],
        }]))
    }

    function deleteWeightRate(index) {
        if(confirm('Are you sure you wish to delete this weightRate?\n\nThis action cannot be undone.'))
            setWeightRates(weightRates.filter((weightRate, i) => i !== index))
    }
    
    function handleWeightRateChange(modifiedWeightRate, index) {
        if(index === undefined) {
            console.log("ERROR: handleWeightRateChange called with invalid index. Aborting")
            return
        }
        const updated = weightRates.map((weightRate, i) => {
            if(i === index)
                return modifiedWeightRate
            else
                return weightRate
        })
        setWeightRates(updated)
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
                {weightRates && weightRates.map((weightRate, index) =>
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

