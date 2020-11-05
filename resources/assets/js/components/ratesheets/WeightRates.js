import React from 'react'
import {Row, Col, Table} from 'react-bootstrap'
import WeightRate from './WeightRate'

export default function WeightRates(props) {
    return(
        <Table size='sm'>
            <tbody>
                {props.weightRates.map(rate => 
                    <WeightRate
                        key={rate.id}
                        lbmin={rate.lbmin}
                        lbmax={rate.lbmax}
                        kgmin={rate.kgmin}
                        kgmax={rate.kgmax} 
                        cost={rate.cost}
                        id={rate.id}
                        handleChange={props.handleChange}
                        handleWeightRateChange={props.handleWeightRateChange}
                    />
                )}
            </tbody>
        </Table>
    )
}

