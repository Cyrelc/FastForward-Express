import React, {useEffect, useState} from 'react'
import {Button, Card, Col, FormControl, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import {ReactTabulator} from 'react-tabulator'

import {useAPI} from '../../contexts/APIContext'

/**
 * Note - severity, employee_type, zone_type, and charge type deliberately excluded here
 * because they have underlying business logic that would not support simply inserting new values
 */

const columns = [
    {title: 'Selection Id', field: 'selection_id'},
    {title: 'Name', field: 'name'},
    {title: 'Value', field: 'value'},
    {title: 'Type', field: 'type', formatter: cell => {
        const data = cell.getData()
        console.log(data.value)
        const selectionType = selectionTypes.find(t => t.value == data.type)
        console.log(selectionType)
        return selectionType?.label || data.type
    }}
]

const selectionTypes = [
    {label: 'Invoice Interval', value: 'invoice_interval', creatable: true},
    {label: 'Phone Type', value: 'phone_type', creatable: true},
    {label: 'Contact Type', value: 'contact_type', creatable: true},
    {label: 'Vehicle Type', value: 'vehicle_type', creatable: true},
    {label: 'Delivery Type', value: 'delivery_type', creatable: false}
]

const creatableSelectionTypes = selectionTypes.filter(type => type.creatable)

export default function SelectionsTab(props) {
    const [isSubmitDisabled, setIsSubmitDisabled] = useState(true)
    const [selections, setSelections] = useState([])
    const [selectionName, setSelectionName] = useState('')
    const [selectionType, setSelectionType] = useState({})
    const [selectionValue, setSelectionValue] = useState('')

    const api = useAPI()

    useEffect(() => {
        const transformedName = selectionName.replace(/\W+/g, '_')
        setSelectionValue(transformedName.toLowerCase())
    }, [selectionName])

    useEffect(() => {
        getSelections()
    }, [])

    useEffect(() => {
        if(selectionName && selectionType && selectionValue)
            setIsSubmitDisabled(false)
        else
            setIsSubmitDisabled(true)
    }, [selectionName, selectionType, selectionValue])

    const getSelections = () => {
        api.get('/appsettings/selections')
            .then(response => {
                setSelections(response)
            })
    }

    const storeSelection = () => {
        const data = {
            type: selectionType.value,
            name: selectionName,
            value: selectionValue
        }

        api.post('/appsettings/selections', data)
            .then(response => {
                setSelections(response)
                setSelectionName('')
                setSelectionType({})
                setSelectionValue('')
            })
    }

    return (
        <Card>
            <Card.Header>
                <Card.Title>Selections</Card.Title>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col>
                        <InputGroup>
                            <InputGroup.Text>Type</InputGroup.Text>
                            <Select
                                options={creatableSelectionTypes}
                                value={selectionType}
                                onChange={setSelectionType}
                            />
                        </InputGroup>
                    </Col>
                    <Col>
                        <InputGroup>
                            <InputGroup.Text>Friendly Name</InputGroup.Text>
                            <FormControl
                                name='selection_name'
                                value={selectionName}
                                onChange={event => setSelectionName(event.target.value)}                            
                            />
                        </InputGroup>
                    </Col>
                    <Col>
                        <InputGroup>
                            <InputGroup.Text>Value</InputGroup.Text>
                            <FormControl
                                name='selection_value'
                                value={selectionValue}
                                onChange={event => setSelectionValue(event.target.value)}
                                disabled={true}
                            />
                        </InputGroup>
                    </Col>
                    <Col>
                        <Button onClick={storeSelection} disabled={isSubmitDisabled}>
                            <i className='fas fa-save'></i> Submit
                        </Button>
                    </Col>
                </Row>
            </Card.Body>
            <Card.Body>
                <ReactTabulator
                    columns={columns}
                    data={selections}
                    options={{
                        layout: 'fitColumns',
                        pagination: 'local',
                        paginationSize: 20
                    }}
                />
            </Card.Body>
        </Card>
    )
}

