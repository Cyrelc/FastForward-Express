import React from 'react'
import CurrencyInput from 'react-currency-input-field'
import {Button, FormControl, InputGroup} from 'react-bootstrap'

export default function ZoneRate(props) {
    const {zoneRate, deliveryTypes = []} = props

    return (
        <tr>
            <th>
                <h5 className='text-muted'>{zoneRate.zones} {zoneRate.zones > 1 ? 'zones' : 'zone'}</h5>
            </th>
            {deliveryTypes.map(type => {
                const rate = zoneRate?.[type.friendlyName] ?? {cost: '', time: ''}
                return (
                    <td key={`${zoneRate.id}-${type.id}`}>
                        <InputGroup size='sm' style={{marginBottom: '4px'}}>
                            <InputGroup.Text>Cost</InputGroup.Text>
                            <CurrencyInput
                                decimalsLimit={2}
                                decimalScale={2}
                                min={0}
                                key={`${props.id}-${type.id}-cost`}
                                value={rate.cost ?? ''}
                                onValueChange={value => props.handleZoneRateChange(props.id, type.friendlyName, 'cost', value)}
                                prefix='$'
                                step={0.01}
                            />
                        </InputGroup>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Time</InputGroup.Text>
                            <FormControl
                                type='number'
                                min={0}
                                step='0.1'
                                value={rate.time ?? ''}
                                onChange={event => props.handleZoneRateChange(props.id, type.friendlyName, 'time', event.target.value)}
                            />
                            <InputGroup.Text>hrs</InputGroup.Text>
                        </InputGroup>
                    </td>
                )
            })}
            {props.onRemove &&
                <td>
                    <Button size='sm' variant='outline-danger' onClick={props.onRemove} title='Remove this zone count'>
                        <i className='fas fa-trash' />
                    </Button>
                </td>
            }
        </tr>
    )
}
