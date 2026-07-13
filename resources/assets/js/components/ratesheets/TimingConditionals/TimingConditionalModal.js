import React, {useMemo, useState} from 'react'
import {Button, Card, Col, InputGroup, FormCheck, FormControl, Modal, Row} from 'react-bootstrap'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/mui'
import Select from 'react-select'
import {toast} from 'react-toastify'

import {useAPI} from '../../../contexts/APIContext'
import {buildTimingConditionalConfig, timeActions, timeValueTypes} from './timingConditionalConfig'
import {extractZoneIdsFromLogic, flattenConditionFields} from '../conditionalFieldShared'
import useTimingConditional from './useTimingConditional'

const renderBuilder = (props) => (
    <div className='query-builder-container' style={{padding: '10px'}}>
        <div className='query-builder'>
            <Builder {...props} />
        </div>
    </div>
)

const actionDescriptions = {
    set: 'Replace the current calculated time with the value below. Use this to reset the running total to a known baseline.',
    add: 'Add the value below to the current calculated time. Use this for surcharges or additive adjustments.'
}

const valueTypeDescriptions = {
    hours: 'Use a fixed numeric number of hours that you enter manually.',
    field: 'Use a value from bill/ratesheet context (zone modifiers, base time, zone-crossing time, etc).'
}

const conditionFieldDescriptions = {
    zones_same: 'True when pickup and delivery resolve to the exact same zone id.',
    zones_crossed: 'How many zones lie between pickup and delivery, counted within the group they share. Empty when they share no group.',
    same_group: 'True when the pickup and delivery zones belong to a common zone group.',
    'timing_inputs.zones_crossed_time': 'How long the zone crossing takes, from the shared group\'s crossing rates, for the selected delivery type. Pair with "Set Base Time" and a "Share a Zone Group" condition.',
    'base_time.default': 'Default time for the currently selected delivery type before conditional adjustments.',
    'timing_inputs.pickup_zone_selected_type_time': 'Pickup zone time modifier for the selected delivery type.',
    'timing_inputs.delivery_zone_selected_type_time': 'Delivery zone time modifier for the selected delivery type.',
    delivery_type: 'The currently selected delivery type id (Regular, Rush, Direct, etc).',
    'pickup_address.zone.zone_id': 'Resolved pickup zone id from pickup coordinates and the active ratesheet map.',
    'pickup_address.zone.groups': 'The zone groups the pickup zone belongs to.',
    'pickup_address.zone.tags': 'Pickup zone tags as a normalized list.',
    'delivery_address.zone.zone_id': 'Resolved delivery zone id from delivery coordinates and the active ratesheet map.',
    'delivery_address.zone.groups': 'The zone groups the delivery zone belongs to.',
    'delivery_address.zone.tags': 'Delivery zone tags as a normalized list.',
}

const TimingConditionalModal = props => {
    const {conditional: {timing_conditional_id} = null, ratesheetId, deliveryTypes, mapZones, zoneGroups} = props
    const extraZoneIds = useMemo(() => {
        if(!props.conditional?.json_logic)
            return []
        try {
            const logic = typeof props.conditional.json_logic === 'string'
                ? JSON.parse(props.conditional.json_logic)
                : props.conditional.json_logic
            return extractZoneIdsFromLogic(logic)
        } catch (error) {
            return []
        }
    }, [props.conditional?.json_logic])

    const config = useMemo(() => {
        return buildTimingConditionalConfig({mapZones, deliveryTypes, extraZoneIds, zoneGroups})
    }, [mapZones, deliveryTypes, extraZoneIds, zoneGroups])

    const timingValueOptions = useMemo(() => {
        const options = [
            {
                value: 'base_time.default',
                label: 'Base Time (Default Delivery Type Time)',
                description: 'Uses the selected delivery type default time as the result value.'
            },
            {
                // The counterpart of the pricing side's zones_crossed_cost. Without this the group's
                // own crossing table is unreachable from the timing rules: only the per-ZONE modifiers
                // were offered, so there was no way to say "take the time the group charges for
                // crossing this many zones" - which is the whole point of a group having a rate table.
                value: 'timing_inputs.zones_crossed_time',
                label: 'Zone Crossing Time (Selected Type)',
                description: 'How long the crossing takes, taken from the shared zone group\'s crossing '
                    + 'table: the row for the number of zones actually crossed, in the column for the '
                    + 'currently selected delivery type. Pair with a "Share a Zone Group" condition. If '
                    + 'the zones share more than one group, the fastest crossing is used.'
            },
            {
                value: 'timing_inputs.pickup_zone_selected_type_time',
                label: 'Pickup Zone Time (Selected Type)',
                description: 'Uses the pickup zone time modifier for the currently selected delivery type.'
            },
            {
                value: 'timing_inputs.delivery_zone_selected_type_time',
                label: 'Delivery Zone Time (Selected Type)',
                description: 'Uses the delivery zone time modifier for the currently selected delivery type.'
            }
        ]

        ;(deliveryTypes ?? []).forEach(type => {
            options.push({
                value: `pickup_address.zone_time.type_${type.value}`,
                label: `Pickup Zone Time (${type.label})`,
                description: `Uses the pickup zone time modifier for ${type.label}, regardless of the currently selected delivery type.`
            })
            options.push({
                value: `delivery_address.zone_time.type_${type.value}`,
                label: `Delivery Zone Time (${type.label})`,
                description: `Uses the delivery zone time modifier for ${type.label}, regardless of the currently selected delivery type.`
            })
        })

        return options
    }, [deliveryTypes])

    const conditionReferenceRows = useMemo(() => {
        return flattenConditionFields(config?.fields ?? {}, conditionFieldDescriptions)
    }, [config])

    const {
        action,
        continueOn,
        name,
        priority,
        queryTree,
        reset,
        resultValue,
        setAction,
        setContinueOn,
        setName,
        setPriority,
        setQueryTree,
        isDirty,
        markSaved,
        setResultValue,
        setValueType,
        valueType,
    } = useTimingConditional({conditional: props.conditional, config, nextPriority: props.nextPriority})

    const selectedTimingValueOption = useMemo(() => {
        return timingValueOptions.find(option => option.value === resultValue) ?? null
    }, [timingValueOptions, resultValue])

    const api = useAPI()
    const [isSubmitting, setIsSubmitting] = useState(false)

    // Rules save one at a time, so clicking away from an edited rule would otherwise bin the edit.
    const requestClose = () => {
        if(isDirty && !confirm('This rule has unsaved changes. Close anyway and lose them?'))
            return
        hideModal()
    }

    const hideModal = () => {
        reset()
        props.onHide()
    }

    const storeTimingConditional = () => {
        if(!name || name.trim() === '') {
            toast.error('Name is required')
            return
        }

        if(!priority || priority < 0 || !Number.isInteger(Number(priority))) {
            toast.error('Priority must be a positive integer')
            return
        }

        if(valueType.value === 'field') {
            if(!resultValue || resultValue.trim() === '') {
                toast.error('Field is required')
                return
            }
        } else if(!resultValue || resultValue < 0) {
            toast.error('Value must be a positive number')
            return
        }

        try {
            const humanReadable = JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)
            const jsonLogic = QbUtils.jsonLogicFormat(queryTree, config)

            if(jsonLogic['errors'].length) {
                toast.error('Error encountered forming JSON logic')
                return
            }

            const data = {
                action: action,
                continue_on: continueOn ? 1 : 0,
                ratesheet_id: ratesheetId,
                json_logic: JSON.stringify(jsonLogic['logic']),
                human_readable: humanReadable,
                name: name.trim(),
                priority: Number(priority),
                value: valueType.value === 'field' ? resultValue : Number(resultValue),
                value_type: valueType.value
            }

            setIsSubmitting(true)

            api.post(`/ratesheets/time-conditional${timing_conditional_id ? `/${timing_conditional_id}` : ''}`, data).then(response => {
                toast.success(`Successfully stored timing conditional "${name}"`)
                markSaved()
                props.reload()
                hideModal()
            }).catch(error => {
                toast.error(error.response?.data?.message || 'Failed to save timing conditional')
            }).finally(() => {
                setIsSubmitting(false)
            })
        } catch (e) {
            toast.error(e.message || 'An error occurred')
            setIsSubmitting(false)
        }
    }

    return (
        <Modal show={props.show} onHide={requestClose} size='xl' dialogClassName='modal-90w'>
            <Card>
                <Card.Header>
                    <Row>
                        <Col md={4}>
                            <h4 className='text-muted'>
                                {`${timing_conditional_id ? 'Edit' : 'Create'} Timing Conditional ${timing_conditional_id ?? ''}`}
                            </h4>
                        </Col>
                        <Col md={8}>
                            <InputGroup>
                                <InputGroup.Text>Name: </InputGroup.Text>
                                <FormControl
                                    name='name'
                                    value={name}
                                    onChange={event => setName(event.target.value)}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={6}></Col>
                        <Col md={6}>
                            <InputGroup>
                                <InputGroup.Text>Priority:</InputGroup.Text>
                                <FormControl
                                    type='number'
                                    step='1'
                                    min='0'
                                    value={priority}
                                    onChange={event => setPriority(event.target.value)}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={6}></Col>
                        <Col md={6}>
                            <InputGroup>
                                <InputGroup.Text>Continue:</InputGroup.Text>
                                <div style={{display: 'flex', alignItems: 'center', paddingLeft: '10px'}}>
                                    <FormCheck
                                        type='checkbox'
                                        checked={!!continueOn}
                                        onChange={event => setContinueOn(event.target.checked)}
                                        label={continueOn ? 'Yes (keep evaluating next rules)' : 'No (stop after this rule)'}
                                    />
                                </div>
                            </InputGroup>
                        </Col>
                    </Row>
                </Card.Header>
                <Card.Body>
                    <Row>
                        <Col md={1}>
                            <h4 className='text-muted' style={{padding: 0}}>If</h4>
                        </Col>
                        <Col md={11}>
                            <p>{JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)}</p>
                        </Col>
                        <Col md={12} style={{padding: 0}}>
                            <Query
                                {...config}
                                value={queryTree}
                                onChange={setQueryTree}
                                renderBuilder={renderBuilder}
                            />
                        </Col>
                    </Row>
                    <hr/>
                    <Row>
                        <Col md={2}>
                            <h4 className='text-muted'>Then</h4>
                        </Col>
                        <Col md={3}>
                            <Select
                                options={timeActions}
                                value={action}
                                onChange={setAction}
                            />
                        </Col>
                        <Col md={3}>
                            <Select
                                options={timeValueTypes}
                                value={valueType}
                                onChange={setValueType}
                            />
                        </Col>
                        <Col md={3}>
                            {valueType.value === 'field' ? (
                                <Select
                                    options={timingValueOptions}
                                    value={selectedTimingValueOption}
                                    onChange={option => setResultValue(option?.value ?? '')}
                                />
                            ) : (
                                <InputGroup style={{paddingTop: '0px'}}>
                                    <FormControl
                                        type='number'
                                        step='0.1'
                                        min='0'
                                        onChange={event => setResultValue(event.target.value)}
                                        value={resultValue}
                                    />
                                    <InputGroup.Text>hrs</InputGroup.Text>
                                </InputGroup>
                            )}
                        </Col>
                        <Col md={12}>
                            <Card style={{marginTop: '12px'}}>
                                <Card.Body style={{padding: '12px 15px'}}>
                                    <Row>
                                        <Col md={12}>
                                            <h6 className='text-muted'>Rule Explanation</h6>
                                            <ul style={{marginBottom: '10px'}}>
                                                <li><strong>Action:</strong> {actionDescriptions[action?.value] ?? 'Controls whether this rule sets or adds time.'}</li>
                                                <li><strong>Value Type:</strong> {valueTypeDescriptions[valueType?.value] ?? 'Controls whether the result comes from a fixed number or a context field.'}</li>
                                                {valueType?.value === 'field' &&
                                                    <li><strong>Selected Field:</strong> {selectedTimingValueOption?.description ?? 'Field-based value selected from timing context.'}</li>
                                                }
                                            </ul>
                                        </Col>
                                        <Col md={6}>
                                            <h6 className='text-muted'>Condition Fields</h6>
                                            <div style={{maxHeight: '220px', overflowY: 'auto'}}>
                                                <ul style={{marginBottom: 0}}>
                                                    {conditionReferenceRows.map(row =>
                                                        <li key={row.path}>
                                                            <strong>{row.label}</strong>: {row.description}
                                                        </li>
                                                    )}
                                                </ul>
                                            </div>
                                        </Col>
                                        <Col md={6}>
                                            <h6 className='text-muted'>Then Value Fields</h6>
                                            <div style={{maxHeight: '220px', overflowY: 'auto'}}>
                                                <ul style={{marginBottom: 0}}>
                                                    {timingValueOptions.map(option =>
                                                        <li key={option.value}>
                                                            <strong>{option.label}</strong>: {option.description}
                                                        </li>
                                                    )}
                                                </ul>
                                            </div>
                                        </Col>
                                    </Row>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>
                </Card.Body>
                <Card.Footer style={{textAlign: 'center'}}>
                    <Button
                        onClick={storeTimingConditional}
                        variant='success'
                        disabled={isSubmitting}
                    >
                        {isSubmitting ? 'Submitting...' : 'Submit'}
                    </Button>
                </Card.Footer>
            </Card>
        </Modal>
    )
}

export default TimingConditionalModal
