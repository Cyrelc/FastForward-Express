import React from 'react'
import {InputGroup, FormControl, Row, Col} from 'react-bootstrap'

export default function ZoneRate(props) {
    return (
        <tr>
            <th>
                <h5 className='text-muted'>{props.zones} {props.zones > 1 ? 'zones' : 'zone'}</h5>
            </th>
            <td>
                <FormControl
                    type='number'
                    step={0.01}
                    key={props.id + '-regularCost'}
                    name='regular_cost'
                    value={props.regularCost}
                    onChange={event => props.handleZoneRateChange(event, props.id)}
                    size='sm'
                />
            </td>
            <td>
                <FormControl
                    type='number'
                    step={0.01}
                    key={props.id + '-rushCost'}
                    name='rush_cost'
                    value={props.rushCost}
                    onChange={event => props.handleZoneRateChange(event, props.id)}
                    size='sm'
                />
            </td>
            <td>
                <FormControl
                    type='number'
                    step={0.01}
                    key={props.id + '-directCost'}
                    name='direct_cost'
                    value={props.directCost}
                    onChange={event => props.handleZoneRateChange(event, props.id)}
                    size='sm'
                />
            </td>
            <td>
                <FormControl
                    type='number'
                    step={0.01}
                    key={props.id + '-directRushCost'}
                    name='direct_rush_cost'
                    value={props.directRushCost}
                    onChange={event => props.handleZoneRateChange(event, props.id)}
                    size='sm'
                />
            </td>
        </tr>
    )
}
