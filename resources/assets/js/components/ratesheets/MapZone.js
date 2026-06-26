import React, {useMemo, useState} from 'react'
import {Card, Col, Collapse, Dropdown, FormControl, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import CreatableSelect from 'react-select/creatable'

export default function MapZone(props) {
    const deliveryTypes = props.deliveryTypes ?? []
    const {
        deliveryTypeModifiers,
        fillColour,
        name,
        region,
        tags,
        type,
    } = props.zone
    const {zIndex} = props.zone.polygon
    const zoneTypes = props.zoneTypes ?? []
    const regionOptions = props.regionOptions ?? []
    const tagOptions = props.tagOptions ?? []
    const [tagError, setTagError] = useState(null)

    const selectedTagOptions = useMemo(() => {
        return (tags ?? []).map(tag => ({value: tag, label: tag}))
    }, [tags])

    const serializedTagLength = useMemo(() => {
        const normalizedTags = (tags ?? []).map(tag => `${tag}`.trim()).filter(tag => !!tag)
        return normalizedTags.join('|').length
    }, [tags])

    const updateTags = (selectedOptions) => {
        const normalizedTags = Array.from(new Set((selectedOptions ?? [])
            .map(option => `${option.value}`.trim())
            .filter(tag => !!tag)))

        const serializedLength = normalizedTags.join('|').length
        if(serializedLength > 255) {
            setTagError(`Tags exceed max storage length (255). Current: ${serializedLength}`)
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
                <InputGroup size='sm'>
                    <InputGroup.Text>Zone Type</InputGroup.Text>
                    <Select
                        options={zoneTypes}
                        onChange={zoneType => props.handleZoneChange({target: {name: 'type', type: 'string', value: zoneType.value}}, zIndex)}
                        value={zoneTypes.find(zoneType => zoneType.value === props.zone.type)}
                    />
                </InputGroup>
                <InputGroup size='sm' style={{marginTop: '8px'}}>
                    <InputGroup.Text>Region</InputGroup.Text>
                    <CreatableSelect
                        options={regionOptions}
                        onChange={regionOption => props.handleZoneChange({target: {name: 'region', type: 'string', value: regionOption?.value ?? ''}}, zIndex)}
                        value={region ? {value: region, label: region} : null}
                        isClearable
                        placeholder='Select or create a region'
                    />
                </InputGroup>
                <InputGroup size='sm' style={{marginTop: '8px'}}>
                    <InputGroup.Text>Tags</InputGroup.Text>
                    <div style={{flex: 1}}>
                        <CreatableSelect
                            isMulti
                            options={tagOptions}
                            value={selectedTagOptions}
                            onChange={updateTags}
                            placeholder='Add one or more tags'
                        />
                        <small className={serializedTagLength > 255 ? 'text-danger' : 'text-muted'}>
                            Serialized length: {serializedTagLength}/255
                        </small>
                        {tagError &&
                            <small className='text-danger' style={{display: 'block'}}>{tagError}</small>
                        }
                    </div>
                </InputGroup>
            </Card.Body>
            <Collapse in={true}>
                <Card.Body>
                    {type !== 'internal' && (
                        <>
                            <hr/>
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
