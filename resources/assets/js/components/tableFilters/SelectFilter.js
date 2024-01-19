import React, {useEffect, useState} from 'react'
import {Col, InputGroup} from 'react-bootstrap'
import Select from 'react-select'
import CreatableSelect from 'react-select/creatable'

// Two types of select:
// 1) Selectable options are passed as an array of objects
// 2) Source is creatable (i.e. any value goes)
export default function SelectFilter(props) {
    const [selections, setSelections] = useState([])
    const [selected, setSelected] = useState([])

    useEffect(() => {
        setSelections(props.filter.selections)
        const value = props.filter.value || ''
        const values = value.split(',').map(value => value)
        const selectedValues = props.filter.selections.filter(selection => values.some(selectedValue => selectedValue == selection.value))
        setSelected(selectedValues)
    }, [props.filter.value])

    const handleFilterChange = selected => {
        const value = selected.map(option => option.value)
        props.handleFilterValueChange({...props.filter, value: value.toString()})
    }

    return(
        <Col md={6}>
            <InputGroup style={{display: 'flex', width: '100%'}}>
                <InputGroup.Text>{props.filter.name}</InputGroup.Text>
                {props.filter.creatable ?
                    <CreatableSelect
                        options={selections}
                        value={selected}
                        onChange={selected => handleFilterChange(selected)}
                        isMulti={props.filter.isMulti}
                        styles={{
                            container: (baseStyles, state) => ({
                                ...baseStyles,
                                flexGrow: 1
                            })
                        }}
                    /> :
                    <Select
                        options={selections}
                        value={selected}
                        onChange={selected => handleFilterChange(selected)}
                        isMulti={props.filter.isMulti}
                        styles={{
                            container: (baseStyles, state) => ({
                                ...baseStyles,
                                flexGrow: 1
                            })
                        }}
                    />
                }
            </InputGroup>
        </Col>
    )
}
