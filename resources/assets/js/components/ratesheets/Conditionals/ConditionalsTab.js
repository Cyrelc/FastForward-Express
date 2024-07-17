import React, {useEffect, useState} from 'react'
import {Button, ButtonGroup, Card, Col, Row, Table} from 'react-bootstrap'
import {AgGridReact} from 'ag-grid-react'

import ConditionalModal from './ConditionalModal'
import {useAPI} from '../../../contexts/APIContext'
import {useLists} from '../../../contexts/ListsContext'

const formatCondition = condition => {
    return 'friendly formatted condition'
}

const formatResult = result => {
    return 'friendly formatted result'
}

export default function ConditionalsTab(props) {
    const api = useAPI()
    const {chargeTypes} = useLists()

    const [conditionals, setConditionals] = useState([])
    const [isLoading, setIsLoading] = useState(true)
    const [editConditional, setEditConditional] = useState(false)
    const [showConditionalModal, setShowConditionalModal] = useState(false)

    const deleteConditional = conditional => {
        if(confirm(`Are you sure you wish to delete conditional "${conditional.name}"?\n\nThis action can not be undone.`))
            api.delete(`/ratesheets/conditional/${conditional.conditional_id}`).then(response => {
                reload()
            })
    }

    const edit = conditional => {
        setEditConditional(conditional)
        setShowConditionalModal(true)
    }

    const hideModal = () => {
        setShowConditionalModal(false)
        setEditConditional(false)
    }

    const reload = () => {
        setIsLoading(true)
        setEditConditional(false)
        api.get(`/ratesheets/conditionals/${props.ratesheetId}`).then(response => {
            setConditionals(response.map(conditional => {
                return {...conditional, action: JSON.parse(conditional.action)}
            }))
            setIsLoading(false)
        })
    }

    useEffect(() => {
        reload()
    }, [])

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <Button
                            variant='success'
                            onClick={() => setShowConditionalModal(true)}
                        >
                            <i className='fas fa-plus'></i> Create
                        </Button>
                    </Col>
                    <Col md={10}>
                        <Card.Title>
                            <h4 className='text-muted'>Conditionals</h4>
                        </Card.Title>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <div className='ag-theme-quartz-dark'>
                    <AgGridReact
                        rowData={conditionals}
                        columnDefs={[
                            {headerName: 'Actions', field: '', cellRenderer: api => {
                                return (
                                    <ButtonGroup>
                                        <Button onClick={() => edit(api.data)} variant='warning' size='sm'>
                                            <i className='fas fa-edit'></i>
                                        </Button>
                                        <Button variant='danger' size='sm' onClick={() => deleteConditional(api.data)}>
                                            <i className='fas fa-trash'></i>
                                        </Button>
                                    </ButtonGroup>
                                )
                            }},
                            {headerName: 'Conditional Type', field: 'type', valueGetter: api => {
                                const chargeType = chargeTypes.find(chargeType => chargeType.value == api.data.type)
                                return chargeType.label
                            }},
                            {field: 'name'},
                            {headerName: 'Condition', field: 'human_readable', flex: 1},
                            {field: 'action', valueGetter: api => api.data.action.label},
                            {headerName: 'Value Type', field: 'value_type'},
                            {headerName: 'Value', field: 'value', valueGetter: api => {
                                const {value_type, value} = api.data
                                if(value_type == 'amount')
                                    return value.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})
                                else if(value_type == 'percent')
                                    return `${value}%`
                                return value
                            }}
                        ]}
                        domLayout='autoHeight'
                    />
                </div>
                {/* <Table>
                    <thead>
                        <tr>
                            <th>Actions</th>
                            <th>Name</th>
                            <th>Condition</th>
                            <th>Action</th>
                            <th>Value Type</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        {conditionals.map(conditional => {
                            return (
                                <tr key={conditional.conditional_id}>
                                    <td>
                                        <Button
                                            onClick={() => deleteConditional(conditional)}
                                            variant='danger'
                                            size='sm'
                                        >
                                            <i className='fas fa-trash'></i>
                                        </Button>
                                    </td>
                                    <td>{conditional.name}</td>
                                    <td>{conditional.human_readable}</td>
                                    <td>{conditional.action['label']}</td>
                                    <td>{conditional.value_type}</td>
                                    <td>
                                        {conditional.value_type == 'amount' && conditional.value.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}
                                        {conditional.value_type == 'percent' && `${conditional.value}%`}
                                        {conditional.value_type == 'equation' && `${conditional.value}`}
                                    </td>
                                </tr>
                            )
                        })}
                    </tbody>
                </Table> */}
            </Card.Body>
            <ConditionalModal
                conditional={editConditional}
                mapZones={props.mapZones}
                onHide={hideModal}
                ratesheetId={props.ratesheetId}
                reload={reload}
                show={showConditionalModal}
            />
        </Card>
    )
}
