import React from 'react'
import CurrencyInput from 'react-currency-input-field'

export default function ZoneRate(props) {
    return (
        <tr>
            <th>
                <h5 className='text-muted'>{props.zones} {props.zones > 1 ? 'zones' : 'zone'}</h5>
            </th>
            <td>
                <CurrencyInput
                    decimalsLimit={2}
                    decimalScale={2}
                    min={0.01}
                    key={props.id + '-regularCost'}
                    name='regular_cost'
                    value={props.regularCost}
                    onValueChange={value => props.handleZoneRateChange(props.id, 'regular_cost', value)}
                    prefix='$'
                    step={0.01}
                />
            </td>
            <td>
                <CurrencyInput
                    decimalsLimit={2}
                    decimalScale={2}
                    min={0.01}
                    key={props.id + '-rushCost'}
                    name='rush_cost'
                    value={props.rushCost}
                    onValueChange={value => props.handleZoneRateChange(props.id, 'rush_cost', value)}
                    prefix='$'
                    step={0.01}
                />
            </td>
            <td>
                <CurrencyInput
                    decimalsLimit={2}
                    decimalScale={2}
                    min={0.01}
                    key={props.id + '-directCost'}
                    name='direct_cost'
                    value={props.directCost}
                    onValueChange={value => props.handleZoneRateChange(props.id, 'direct_cost', value)}
                    prefix='$'
                    step={0.01}
                />
            </td>
            <td>
                <CurrencyInput
                    decimalsLimit={2}
                    decimalScale={2}
                    min={0.01}
                    key={props.id + '-directRushCost'}
                    name='direct_rush_cost'
                    value={props.directRushCost}
                    onValueChange={value => props.handleZoneRateChange(props.id, 'direct_rush_cost', value)}
                    prefix='$'
                    step={0.01}
                />
            </td>
        </tr>
    )
}
