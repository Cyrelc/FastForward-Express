import React, {useMemo} from 'react'
import {Button, Col, FormControl, InputGroup, Modal, Row, Table} from 'react-bootstrap'

import ZoneRate from './ZoneRate'

/**
 * Edits one zone group: its name, its map colour, and its zone-crossing rate table (what it costs and
 * how long it takes to cross N zones within this group, per delivery type).
 *
 * These rates used to live on the ratesheet and applied automatically whenever both zones were
 * internal. They now belong to the group that is being crossed, and only apply if a pricing or timing
 * conditional asks for them (zones_crossed_cost / zones_crossed_time).
 */
export default function ZoneGroupModal(props) {
    const {deliveryTypes = [], zoneGroup, zoneCount, isDirty, isSaving} = props

    if(!zoneGroup)
        return null

    const zoneRates = zoneGroup.zoneRates ?? []

    // Groups save on their own now, so closing with unsaved edits would silently discard them.
    const close = () => {
        if(isDirty && !confirm('This group has unsaved changes. Close anyway and lose them?'))
            return
        props.onClose()
    }

    const ensureZoneRateShape = (zoneRate) => {
        const normalizedZoneRate = {...zoneRate}

        deliveryTypes.forEach(type => {
            const existing = normalizedZoneRate?.[type.friendlyName]
            normalizedZoneRate[type.friendlyName] = (existing && typeof existing === 'object')
                ? {cost: existing.cost ?? '', time: existing.time ?? ''}
                : {cost: '', time: ''}
        })

        return normalizedZoneRate
    }

    const update = (changes) => props.onChange({...zoneGroup, ...changes})

    const handleZoneRateChange = (id, typeName, field, value) => {
        update({
            zoneRates: zoneRates.map(zoneRate => {
                if(zoneRate.id != id)
                    return zoneRate

                const normalized = ensureZoneRateShape(zoneRate)
                return {
                    ...normalized,
                    [typeName]: {...normalized[typeName], [field]: value ?? ''},
                }
            })
        })
    }

    const nextId = useMemo(() => {
        return zoneRates.reduce((highest, zoneRate) => Math.max(highest, parseInt(zoneRate.id, 10) || 0), -1) + 1
    }, [zoneRates])

    const addZoneRate = () => {
        const nextZones = zoneRates.reduce((highest, zoneRate) => Math.max(highest, parseInt(zoneRate.zones, 10) || 0), 0) + 1
        update({zoneRates: [...zoneRates, ensureZoneRateShape({id: nextId, zones: nextZones})]})
    }

    const removeZoneRate = (id) => {
        update({zoneRates: zoneRates.filter(zoneRate => zoneRate.id != id)})
    }

    return (
        <Modal show={true} onHide={close} size='xl'>
            <Modal.Header closeButton>
                <Modal.Title>
                    Edit Zone Group
                    {isDirty && <small className='text-warning' style={{marginLeft: '8px'}}>unsaved changes</small>}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <Row>
                    <Col md={6}>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Name</InputGroup.Text>
                            <FormControl
                                type='text'
                                value={zoneGroup.name ?? ''}
                                onChange={event => update({name: event.target.value})}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Colour</InputGroup.Text>
                            <FormControl
                                type='color'
                                value={zoneGroup.colour ?? '#8491c9'}
                                onChange={event => update({colour: event.target.value})}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3} className='text-muted'>
                        <small>{zoneCount} zone{zoneCount === 1 ? '' : 's'} in this group</small>
                    </Col>
                </Row>

                <hr/>

                <h5 className='text-muted'>Zone Crossing Rates</h5>
                <small className='text-muted' style={{display: 'block', marginBottom: '8px'}}>
                    What it costs and how long it takes to cross this many zones inside this group. These
                    only apply if a pricing or timing conditional asks for them &mdash; a group on its own
                    does not change any price.
                </small>

                {zoneRates.length === 0 &&
                    <p className='text-muted'><em>No crossing rates yet.</em></p>
                }

                {zoneRates.length > 0 &&
                    <Table size='sm'>
                        <thead>
                            <tr>
                                <td></td>
                                {deliveryTypes.map(type =>
                                    <td key={type.friendlyName}>
                                        <h5 className='text-muted'>{type.friendlyName}</h5>
                                    </td>
                                )}
                                <td></td>
                            </tr>
                        </thead>
                        <tbody>
                            {zoneRates.map(zoneRate =>
                                <ZoneRate
                                    key={zoneRate.id}
                                    deliveryTypes={deliveryTypes}
                                    id={zoneRate.id}
                                    zoneRate={ensureZoneRateShape(zoneRate)}
                                    handleZoneRateChange={handleZoneRateChange}
                                    onRemove={() => removeZoneRate(zoneRate.id)}
                                />
                            )}
                        </tbody>
                    </Table>
                }

                <Button size='sm' variant='outline-primary' onClick={addZoneRate}>
                    <i className='fas fa-plus' /> Add a zone count
                </Button>
            </Modal.Body>
            <Modal.Footer>
                <Button variant='danger' onClick={() => props.onDelete(zoneGroup)} disabled={isSaving}>
                    Delete Group
                </Button>
                <div style={{flex: 1}} />
                <Button variant='secondary' onClick={close} disabled={isSaving}>Close</Button>
                <Button
                    variant='primary'
                    onClick={() => props.onSave(zoneGroup)}
                    disabled={!isDirty || isSaving}
                >
                    {isSaving ? 'Saving…' : 'Save Group'}
                </Button>
            </Modal.Footer>
        </Modal>
    )
}
