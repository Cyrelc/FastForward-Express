import React, {useMemo, useState} from 'react'
import {Button, Card, Col, InputGroup, FormCheck, FormControl, Modal, Row} from 'react-bootstrap'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/mui'
import Select from 'react-select'
import {toast} from 'react-toastify'

import {useAPI} from '../../../contexts/APIContext'
import {buildTimingConditionalConfig, timeActions, timeValueTypes} from './timingConditionalConfig'
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
    zones_crossed: 'Number of zones crossed between pickup and delivery when internal-zone crossing logic applies.',
    both_internal: 'True when both pickup and delivery zones are internal.',
    both_external: 'True when both pickup and delivery zones are external (non-internal types).',
    one_internal_one_external: 'True when one zone is internal and the other is external.',
    'ratesheet.use_internal_zones_calc': 'Whether this ratesheet calculates timing/charges using internal zone crossing distance.',
    'base_time.default': 'Default time for the currently selected delivery type before conditional adjustments.',
    'timing_inputs.external_zone_time_total': 'Combined pickup + delivery external zone time modifiers for the selected delivery type.',
    'timing_inputs.default_plus_external_zone_time_total': 'Base delivery time plus the combined external zone time modifiers.',
    'timing_inputs.internal_zone_distance_time': 'Time derived from internal zone crossing distance and zone-rate configuration.',
    'timing_inputs.pickup_zone_selected_type_time': 'Pickup zone time modifier for the selected delivery type.',
    'timing_inputs.delivery_zone_selected_type_time': 'Delivery zone time modifier for the selected delivery type.',
    delivery_type: 'The currently selected delivery type id (Regular, Rush, Direct, etc).',
    'pickup_address.zone_id': 'Resolved pickup zone id from pickup coordinates and the active ratesheet map.',
    'pickup_address.zone_type': 'Resolved pickup zone type (internal/outlying/peripheral).',
    'pickup_address.is_internal': 'True when pickup zone type is internal.',
    'pickup_address.is_external': 'True when pickup zone type is not internal.',
    'pickup_address.zone.name': 'Resolved pickup zone name.',
    'pickup_address.zone.region': 'Resolved pickup zone region string.',
    'pickup_address.zone.tags_text': 'Raw pickup zone tags as pipe-delimited text.',
    'pickup_address.zone.tags': 'Pickup zone tags as a normalized list.',
    'delivery_address.zone_id': 'Resolved delivery zone id from delivery coordinates and the active ratesheet map.',
    'delivery_address.zone_type': 'Resolved delivery zone type (internal/outlying/peripheral).',
    'delivery_address.is_internal': 'True when delivery zone type is internal.',
    'delivery_address.is_external': 'True when delivery zone type is not internal.',
    'delivery_address.zone.name': 'Resolved delivery zone name.',
    'delivery_address.zone.region': 'Resolved delivery zone region string.',
    'delivery_address.zone.tags_text': 'Raw delivery zone tags as pipe-delimited text.',
    'delivery_address.zone.tags': 'Delivery zone tags as a normalized list.',
}

const flattenConditionFields = (fields, prefix = '') => {
    const values = []

    Object.entries(fields ?? {}).forEach(([key, field]) => {
        const path = prefix ? `${prefix}.${key}` : key
        if(field?.type === '!struct') {
            values.push(...flattenConditionFields(field.subfields ?? {}, path))
            return
        }

        values.push({
            path,
            label: field?.label ?? path,
            description: conditionFieldDescriptions[path] ?? 'Context value available for query conditions.'
        })
    })

    return values
}

const extractZoneIdsFromLogic = (jsonLogic) => {
    const zoneIds = new Set()

    const isZoneVar = (node) => {
        return node?.var === 'pickup_address.zone_id' || node?.var === 'delivery_address.zone_id'
    }

    const walk = (node) => {
        if(Array.isArray(node)) {
            node.forEach(walk)
            return
        }

        if(!node || typeof node !== 'object')
            return

        if(isZoneVar(node))
            return

        Object.entries(node).forEach(([key, value]) => {
            if(Array.isArray(value)) {
                const hasZoneVar = value.some(item => isZoneVar(item))
                if(hasZoneVar) {
                    value.forEach(item => {
                        if(typeof item === 'number')
                            zoneIds.add(item)
                        if(typeof item === 'string' && !isNaN(Number(item)))
                            zoneIds.add(Number(item))
                    })
                }
            }
            walk(value)
        })
    }

    walk(jsonLogic)
    return [...zoneIds]
}

const TimingConditionalModal = props => {
    const {conditional: {timing_conditional_id} = null, ratesheetId, deliveryTypes, mapZones, zoneTypes} = props
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
        return buildTimingConditionalConfig({mapZones, deliveryTypes, extraZoneIds, zoneTypes})
    }, [mapZones, deliveryTypes, extraZoneIds, zoneTypes])

    const timingValueOptions = useMemo(() => {
        const options = [
            {
                value: 'base_time.default',
                label: 'Base Time (Default Delivery Type Time)',
                description: 'Uses the selected delivery type default time as the result value.'
            },
            {
                value: 'timing_inputs.internal_zone_distance_time',
                label: 'Internal Zone Distance Time',
                description: 'Uses internally calculated zone-crossing distance time from zone rates.'
            },
            {
                value: 'timing_inputs.external_zone_time_total',
                label: 'External Zone Time Total',
                description: 'Uses pickup + delivery external zone time modifiers combined.'
            },
            {
                value: 'timing_inputs.default_plus_external_zone_time_total',
                label: 'Default + External Zone Time Total',
                description: 'Uses base delivery type time plus the total external zone time modifiers.'
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
        return flattenConditionFields(config?.fields ?? {})
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
        setResultValue,
        setValueType,
        valueType,
    } = useTimingConditional({conditional: props.conditional, config})

    const selectedTimingValueOption = useMemo(() => {
        return timingValueOptions.find(option => option.value === resultValue) ?? null
    }, [timingValueOptions, resultValue])

    const api = useAPI()
    const [isSubmitting, setIsSubmitting] = useState(false)

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
        <Modal show={props.show} onHide={hideModal} size='xl' dialogClassName='modal-90w'>
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
