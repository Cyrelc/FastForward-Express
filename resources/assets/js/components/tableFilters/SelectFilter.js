import React from 'react'
import {Col, InputGroup} from 'react-bootstrap'
import Select from 'react-select'
import CreatableSelect from 'react-select/creatable'

// Two types of select:
// 1) Selectable options are passed as an array of objects
// 2) Source is creatable (i.e. any value goes)
// The value is a comma-separated list of option values. Selections may arrive after the value (loaded asynchronously),
// so the selected options are derived on every render rather than captured once.
export default function SelectFilter(props) {
    const selections = props.filter.selections ?? []
    const values = props.filter.value ? props.filter.value.split(',').filter(value => value !== '') : []

    const selected = values.map(value => {
        const selection = selections.find(selection => selection.value == value)
        if(selection)
            return selection
        // Creatable values (e.g. a typed invoice ID) don't exist in the selections, so show them as typed
        return props.filter.creatable ? {label: value, value} : null
    }).filter(option => option)

    const handleFilterChange = newSelected => {
        const options = Array.isArray(newSelected) ? newSelected : (newSelected ? [newSelected] : [])
        props.handleFilterValueChange({...props.filter, value: options.map(option => option.value).join(',')})
    }

    const SelectComponent = props.filter.creatable ? CreatableSelect : Select

    return(
        <Col md={6}>
            <InputGroup style={{display: 'flex', width: '100%'}}>
                <InputGroup.Text>{props.filter.name}</InputGroup.Text>
                <SelectComponent
                    options={selections}
                    value={props.filter.isMulti ? selected : (selected[0] ?? null)}
                    onChange={handleFilterChange}
                    isClearable={!props.filter.isMulti}
                    isMulti={props.filter.isMulti}
                    styles={{
                        container: (baseStyles, state) => ({
                            ...baseStyles,
                            flexGrow: 1
                        })
                    }}
                />
            </InputGroup>
        </Col>
    )
}
