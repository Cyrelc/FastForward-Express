import React from 'react'
import {InputGroup, FormControl, Row, Col} from 'react-bootstrap'

export default function ZoneRate(props) {
    return (
        <tr>
            <td>
                <InputGroup>
                    <InputGroup.Prepend>
                        <InputGroup.Text>{props.zones} {props.zones > 1 ? 'zones' : 'zone'}</InputGroup.Text>
                    </InputGroup.Prepend>
                    <FormControl type='number' step={0.01} key={props.id + '-regularCost'} name='regularCost' value={props.regularCost} onChange={event => props.handleZoneRateChange(event, props.id)}/>
                </InputGroup>
            </td>
            <td>
                <FormControl type='number' step={0.01} key={props.id + '-rushCost'} name='rushCost' value={props.rushCost} onChange={event => props.handleZoneRateChange(event, props.id)}/>
            </td>
            <td>
                <FormControl type='number' step={0.01} key={props.id + '-directCost'} name='directCost' value={props.directCost} onChange={event => props.handleZoneRateChange(event, props.id)}/>
            </td>
            <td>
                <FormControl type='number' step={0.01} key={props.id + '-directRushCost'} name='directRushCost' value={props.directRushCost} onChange={event => props.handleZoneRateChange(event, props.id)}/>
            </td>
        </tr>
    )
}
