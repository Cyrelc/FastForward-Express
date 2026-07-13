import React, {useMemo, useState} from 'react'
import {Button, Card, Col, Collapse, Dropdown, FormControl, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import CreatableSelect from 'react-select/creatable'
import {DEFAULT_ZONE_COLOUR, resolveZoneColour} from './Classes/Zone'
import {menuPortalProps} from './selectMenuPortal'

// Two separate ceilings. TAG_STORAGE_LIMIT is imposed by the database - tags live pipe-joined in a
// varchar(255) - and applies to all of a zone's tags together. TAG_MAX_LENGTH is a house rule on any
// single tag, to keep them short enough to read in a dropdown; the longest in use is "peripheral".
const TAG_STORAGE_LIMIT = 255
const TAG_MAX_LENGTH = 12

export default function MapZone(props) {
    const deliveryTypes = props.deliveryTypes ?? []
    const {
        deliveryTypeModifiers,
        fillColour,
        groupIds,
        name,
        tags,
    } = props.zone
    const {zIndex} = props.zone.polygon
    const zoneGroups = props.zoneGroups ?? []
    const tagOptions = props.tagOptions ?? []
    const [tagError, setTagError] = useState(null)

    const groupOptions = useMemo(() => {
        return zoneGroups.map(zoneGroup => ({value: zoneGroup.zoneGroupId, label: zoneGroup.name}))
    }, [zoneGroups])

    const selectedGroupOptions = useMemo(() => {
        return groupOptions.filter(option => (groupIds ?? []).includes(option.value))
    }, [groupOptions, groupIds])

    // Exactly what the zone is painted on the map: its group's colour if it has one, else its own.
    // Resolved rather than read off `colour` so the swatch can never disagree with the map.
    const effectiveColour = resolveZoneColour(props.zone, zoneGroups) ?? DEFAULT_ZONE_COLOUR

    const updateGroups = (selectedOptions) => {
        props.handleZoneChange(
            {target: {name: 'groupIds', type: 'array', value: (selectedOptions ?? []).map(option => option.value)}},
            zIndex
        )
    }

    const selectedTagOptions = useMemo(() => {
        return (tags ?? []).map(tag => ({value: tag, label: tag}))
    }, [tags])

    const serializedTagLength = useMemo(() => {
        const normalizedTags = (tags ?? []).map(tag => `${tag}`.trim()).filter(tag => !!tag)
        return normalizedTags.join('|').length
    }, [tags])

    // The longest tag that would still fit: the budget, less what is already stored, less the
    // separator the new tag brings with it (only needed if it is not the first).
    const remainingTagLength = useMemo(() => {
        const separator = (tags ?? []).length ? 1 : 0
        return TAG_STORAGE_LIMIT - serializedTagLength - separator
    }, [serializedTagLength, tags])

    // A tag has to clear both ceilings: its own length, and what the zone has left of the 255.
    const maxNewTagLength = Math.min(TAG_MAX_LENGTH, remainingTagLength)

    const tagFits = (tag) => {
        const trimmed = `${tag}`.trim()
        return !!trimmed && trimmed.length <= maxNewTagLength
    }

    const updateTags = (selectedOptions) => {
        const normalizedTags = Array.from(new Set((selectedOptions ?? [])
            .map(option => `${option.value}`.trim())
            .filter(tag => !!tag)))

        // The select will not offer a tag that overflows, so this only catches a tag arriving by some
        // other route (a paste, a zone loaded with tags saved before the limit was enforced). Removing
        // tags must always be allowed, or an over-budget zone could never be brought back under it.
        const serializedLength = normalizedTags.join('|').length
        if(serializedLength > TAG_STORAGE_LIMIT && normalizedTags.length >= (tags ?? []).length) {
            setTagError(`Tags exceed max storage length (${TAG_STORAGE_LIMIT}). Current: ${serializedLength}`)
            return
        }

        setTagError(null)
        props.handleZoneChange({target: {name: 'tags', type: 'array', value: normalizedTags}}, zIndex)
    }

    const updateModifier = (deliveryType, field, rawValue) => {
        const key = deliveryType.friendlyName
        const updatedModifiers = {
            ...(deliveryTypeModifiers ?? {})
        }

        const currentModifier = updatedModifiers[key] ?? {time: 0, cost: 0}
        const parsedValue = rawValue === '' ? '' : Number(rawValue)
        updatedModifiers[key] = {
            ...currentModifier,
            [field]: Number.isNaN(parsedValue) ? '' : parsedValue,
        }

        props.handleZoneChange({target: {name: 'deliveryTypeModifiers', type: 'object', value: updatedModifiers}}, zIndex)
    }

    return(
        <Card>
            <Card.Header style={{backgroundColor: fillColour}}>
                <Row>
                    <Col>
                        <InputGroup size='sm'>
                            <InputGroup.Text>Name: </InputGroup.Text>
                            <FormControl
                                type='text'
                                name='name'
                                value={name}
                                onChange={event => props.handleZoneChange(event, zIndex)}
                            />
                        </InputGroup>
                    </Col>
                    <Col md='auto'>
                        <Button
                            size='sm'
                            variant={props.zone.isDirty ? 'warning' : 'outline-secondary'}
                            disabled={!props.zone.isDirty || props.isSaving}
                            onClick={() => props.saveZone(props.zone)}
                            title={props.zone.isDirty ? 'Save this zone' : 'No unsaved changes'}
                            style={{marginRight: '4px'}}
                        >
                            <i className='fas fa-save' /> {props.zone.isDirty ? 'Save' : 'Saved'}
                        </Button>
                    </Col>
                    <Col md='auto'>
                        <Dropdown>
                            <Dropdown.Toggle size='sm' variant='secondary' id='zone-options'>
                                <i className='fas fa-bars'></i>
                            </Dropdown.Toggle>
                            <Dropdown.Menu>
                                <Dropdown.Item onClick={() => props.deleteZone(zIndex)}>
                                    <i className='fas fa-trash'></i> Delete
                                </Dropdown.Item>
                                <Dropdown.Item onClick={props.zone.smooth}>
                                    <i className='fas fa-chart-line' />Smooth
                                </Dropdown.Item>
                                <Dropdown.Item onClick={() => props.zone.match(props.mapZones)}>
                                    <i className='fas fa-check' />Match
                                </Dropdown.Item>
                                <Dropdown.Item onClick={props.zone.removeDuplicates}>
                                    Dedup
                                </Dropdown.Item>
                            </Dropdown.Menu>
                        </Dropdown>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                {/*
                  * Help text sits below the InputGroup, never inside it: an input group stretches its
                  * children to a common height, so a label beside a taller-than-the-input column grows
                  * to match the whole column.
                  */}
                <InputGroup size='sm'>
                    <InputGroup.Text>Groups</InputGroup.Text>
                    <div style={{flex: 1}}>
                        <Select
                            isMulti
                            options={groupOptions}
                            value={selectedGroupOptions}
                            onChange={updateGroups}
                            placeholder='Not in any group'
                            {...menuPortalProps}
                        />
                    </div>
                </InputGroup>
                <small className='text-muted d-block' style={{marginTop: '4px'}}>
                    Zones in the same group can have their crossing counted between them.
                </small>
                {/*
                  * A grouped zone takes its group's colour, so there is nothing to pick here - showing
                  * a picker that the group overrides would just lie about what the map will do.
                  */}
                {selectedGroupOptions.length > 0
                    ? <InputGroup size='sm' style={{marginTop: '8px'}}>
                        <InputGroup.Text>Colour</InputGroup.Text>
                        <div className='form-control d-flex align-items-center' style={{gap: '8px'}}>
                            <span
                                style={{
                                    backgroundColor: effectiveColour,
                                    border: '1px solid rgba(0,0,0,.2)',
                                    borderRadius: '3px',
                                    display: 'inline-block',
                                    height: '14px',
                                    width: '14px',
                                }}
                            />
                            <small className='text-muted'>from {selectedGroupOptions[0].label}</small>
                        </div>
                    </InputGroup>
                    : <InputGroup size='sm' style={{marginTop: '8px'}}>
                        <InputGroup.Text>Colour</InputGroup.Text>
                        <FormControl
                            type='color'
                            value={effectiveColour}
                            title='Zone colour on the map'
                            onChange={event => props.handleZoneChange({target: {name: 'colour', type: 'string', value: event.target.value}}, zIndex)}
                        />
                    </InputGroup>
                }
                <InputGroup size='sm' style={{marginTop: '8px'}}>
                    <InputGroup.Text>Tags</InputGroup.Text>
                    <div style={{flex: 1}}>
                        <CreatableSelect
                            isMulti
                            options={tagOptions}
                            value={selectedTagOptions}
                            onChange={updateTags}
                            placeholder='Add one or more tags'
                            {...menuPortalProps}
                            // No "Create ..." entry for a tag that will not fit, and no existing tag
                            // offered that will not fit either - the limit is a thing you cannot cross
                            // rather than an error you are told about after crossing it.
                            isValidNewOption={inputValue => tagFits(inputValue)}
                            isOptionDisabled={option => !tagFits(option.value)}
                            // Say which ceiling was hit, since "no options" on its own reads as a bug.
                            noOptionsMessage={({inputValue}) => {
                                if(!inputValue || tagFits(inputValue))
                                    return 'No tags'
                                return inputValue.trim().length > TAG_MAX_LENGTH
                                    ? `Tags are limited to ${TAG_MAX_LENGTH} characters`
                                    : `Not enough room left — ${remainingTagLength} character${remainingTagLength === 1 ? '' : 's'} of ${TAG_STORAGE_LIMIT}`
                            }}
                        />
                    </div>
                </InputGroup>
                <small
                    className={`d-block ${serializedTagLength > TAG_STORAGE_LIMIT ? 'text-danger' : 'text-muted'}`}
                    style={{marginTop: '4px'}}
                >
                    Max {TAG_MAX_LENGTH} characters per tag. Serialized length: {serializedTagLength}/{TAG_STORAGE_LIMIT}
                </small>
                {tagError &&
                    <small className='text-danger d-block'>{tagError}</small>
                }
            </Card.Body>
            <Collapse in={true}>
                <Card.Body>
                    {(
                        <>
                            <hr/>
                            <small className='text-muted' style={{display: 'block', marginBottom: '6px'}}>
                                Per-delivery-type cost/time for this zone. A conditional decides how it is
                                used &mdash; as this zone's base price, or as a surcharge added on top.
                            </small>
                            <table>
                                <tr>
                                    <td></td>
                                    <td>Time (hours)</td>
                                    <td>Cost</td>
                                </tr>
                                {deliveryTypes.map(deliveryType => {
                                    const modifier = deliveryTypeModifiers?.[deliveryType.friendlyName] ?? {time: '', cost: ''}
                                    return (
                                        <tr key={deliveryType.id} style={{marginBottom: '6px'}}>
                                            <td>
                                                {deliveryType.friendlyName}
                                            </td>
                                            <td>
                                                <InputGroup size='sm'>
                                                    <FormControl
                                                        type='number'
                                                        step={0.1}
                                                        min={0}
                                                        value={modifier.time ?? ''}
                                                        onChange={event => updateModifier(deliveryType, 'time', event.target.value)}
                                                    />
                                                </InputGroup>
                                            </td>
                                            <td>
                                                <InputGroup size='sm'>
                                                    <FormControl
                                                        type='number'
                                                        step={0.01}
                                                        min={0}
                                                        value={modifier.cost ?? ''}
                                                        onChange={event => updateModifier(deliveryType, 'cost', event.target.value)}
                                                    />
                                                </InputGroup>
                                            </td>
                                        </tr>
                                    )
                                })}
                            </table>
                        </>
                    )}
                </Card.Body>
            </Collapse>
        </Card>
    )
}
