import React from 'react'
import {InputGroup, FormControl, Row, Col} from 'react-bootstrap'
import Cleave from 'cleave.js/react'

export default function WeightRate(props) {
    return(
        <tr>
            <td>
                <InputGroup>
                    <InputGroup.Prepend>
                        <InputGroup.Text key={props.id + '-kgmin'}>{props.kgmin} kg to </InputGroup.Text>
                    </InputGroup.Prepend>
                    <FormControl 
                        key={props.id + '-kgmax'}
                        type='number' 
                        min={props.kgmin} 
                        step={0.1} 
                        name='kgmax' 
                        value={props.kgmax} 
                        onChange={(event) => props.handleWeightRateChange(event, props.id)} />
                    <InputGroup.Append>
                        <InputGroup.Text>kg</InputGroup.Text>
                    </InputGroup.Append>
                </InputGroup>
            </td>
            <td>
                <InputGroup>
                    <InputGroup.Prepend>
                        <InputGroup.Text key={props.id + '-lbmin'}>{props.lbmin} lb to </InputGroup.Text>
                    </InputGroup.Prepend>
                    <FormControl
                        key={props.id + 'lbmax'}
                        type='number' 
                        min={props.lbmin} 
                        step={0.1} 
                        name='lbmax' 
                        value={props.lbmax} 
                        onChange={(event) => props.handleWeightRateChange(event, props.id)} />
                    <InputGroup.Append>
                        <InputGroup.Text>lb</InputGroup.Text>
                    </InputGroup.Append>
                </InputGroup>
            </td>
            <td>
                <InputGroup>
                    <InputGroup.Prepend>
                        <InputGroup.Text>Price: $</InputGroup.Text>
                    </InputGroup.Prepend>
                    <FormControl
                        key={props.id + '-price'}
                        type='number'
                        min='0' 
                        step={0.01} 
                        name='cost' 
                        value={props.cost} 
                        onChange={(event) => props.handleChange(event, 'weightRates', props.id)} />
                </InputGroup>
            </td>
        </tr>
    )
}
