import React, {useEffect, useState} from 'react'
import {Button, ButtonGroup, Card, Col, Row} from 'react-bootstrap'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import ConditionalModal from './ConditionalModal'
import {useAPI} from '../../../contexts/APIContext'
import {useLists} from '../../../contexts/ListsContext'

export default function ConditionalsTab(props) {
    const api = useAPI()
    const {chargeTypes} = useLists()

    const [conditionals, setConditionals] = useState([])
    const [isLoading, setIsLoading] = useState(true)
    const [editConditional, setEditConditional] = useState(false)
    const [showConditionalModal, setShowConditionalModal] = useState(false)

    const table = useMaterialReactTable({
        data: conditionals,
        columns: [
            {header: 'Actions', size: 120, Cell: ({row}) => (
                <ButtonGroup>
                    <Button onClick={() => edit(row.original)} variant='primary' size='sm'>
                        <i className='fas fa-edit'></i>
                    </Button>
                    <Button variant='danger' size='sm' onClick={() => deleteConditional(row.original)}>
                        <i className='fas fa-trash'></i>
                    </Button>
                </ButtonGroup>
            )},
            {header: 'Priority', accessorKey: 'priority', size: 120},
            {header: 'Conditional Type', accessorKey: 'type', size: 250, Cell: ({row}) => {
                const chargeType = chargeTypes.find(chargeType => chargeType.value == row.original.type)
                return chargeType.label
            }},
            {accessorKey: 'name', header: 'Name', grow: true},
            {header: 'Condition', accessorKey: 'human_readable', grow: true},
            {accessorKey: 'action', header: 'Action', Cell: ({row}) => row.original.action.label},
            {header: 'Value Type', accessorKey: 'value_type'},
            {header: 'Value', accessorKey: 'value', Cell: ({row}) => {
                const {value_type, value} = row.original
                if(value_type == 'amount')
                    return value.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})
                else if(value_type == 'percent')
                    return `${value}%`
                return value
            }}
        ],
        layoutMode: 'grid-no-grow',
    })

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
                <MaterialReactTable table={table} />
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
