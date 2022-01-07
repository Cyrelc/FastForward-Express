import React from 'react'
import {InputGroup, FormControl} from 'react-bootstrap'

export default function RateOption(props) {
    return (
        <tr>
            <td>
                <label>{props.friendlyName}</label>
            </td>
            <td>
                <InputGroup size='sm'>
                    <InputGroup.Text>$</InputGroup.Text>
                    <FormControl id={props.id} type='number' min='0' step='0.01' name='cost' value={props.cost} onChange={event => props.handleChange(event, 'deliveryTypes', props.id)} />
                </InputGroup>
            </td>
            <td>
                <FormControl type='number' min='0.1' step='0.1' name='time' value={props.time} onChange={event => props.handleChange(event, 'deliveryTypes', props.id)} size='sm'/>
            </td>
        </tr>
    )
}
