import React, {useEffect, useMemo, useState, forwardRef, useImperativeHandle} from 'react'
import {Button, ButtonGroup, Card, Col, Row} from 'react-bootstrap'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import TimingConditionalModal from './TimingConditionalModal'
import {useAPI} from '../../../contexts/APIContext'
import {useLists} from '../../../contexts/ListsContext'



const TimingConditionalsTab = forwardRef(function TimingConditionalsTab(props, ref) {
    const api = useAPI()
    const {deliveryTypes, mapZones, ratesheetId} = props
    const {zoneTypes} = useLists()

    const [timingConditionals, setTimingConditionals] = useState([])
    const [isLoading, setIsLoading] = useState(true)
    const [editTimingConditional, setEditTimingConditional] = useState(false)
    const [showTimingConditionalModal, setShowTimingConditionalModal] = useState(false)
    const [loadError, setLoadError] = useState(false)

    const deliveryTypeOptions = useMemo(() => {
        return (deliveryTypes ?? []).map(type => ({
            value: type.id,
            label: type.friendlyName
        }))
    }, [deliveryTypes])

    const table = useMaterialReactTable({
        data: timingConditionals,
        columns: [
            {header: 'Actions', enableColumnActions: false, size: 100, Cell: ({row}) => (
                <ButtonGroup>
                    <Button onClick={() => edit(row.original)} variant='primary' size='sm'>
                        <i className='fas fa-edit'></i>
                    </Button>
                    <Button variant='danger' size='sm' onClick={() => deleteTimingConditional(row.original)}>
                        <i className='fas fa-trash'></i>
                    </Button>
                </ButtonGroup>
            )},
            {header: 'Priority', accessorKey: 'priority', size: 110, enableColumnActions: false},
            {accessorKey: 'name', header: 'Name', grow: 1},
            {header: 'Condition', accessorKey: 'human_readable', grow: 4},
            {accessorKey: 'action', header: 'Action', Cell: ({row}) => row.original.action.label, size: 150},
            {header: 'Flow', accessorKey: 'continue_on', size: 160, Cell: ({row}) => Number(row.original.continue_on) === 1 ? 'Apply & Continue' : 'Apply & Stop'},
            {header: 'Value', accessorKey: 'value', size: 180, Cell: ({row}) => {
                if(row.original.value_type === 'field')
                    return row.original.value
                return `${row.original.value} hrs`
            }}
        ],
        layoutMode: 'grid-no-grow',
        state: {
            isLoading
        }
    })

    const deleteTimingConditional = timingConditional => {
        if(confirm(`Are you sure you wish to delete timing conditional "${timingConditional.name}"?\n\nThis action can not be undone.`))
            api.delete(`/ratesheets/time-conditional/${timingConditional.timing_conditional_id}`).then(response => {
                reload()
            })
    }

    const edit = timingConditional => {
        setEditTimingConditional(timingConditional)
        setShowTimingConditionalModal(true)
    }

    const hideModal = () => {
        setShowTimingConditionalModal(false)
        setEditTimingConditional(false)
    }

    const reload = () => {
        if(!ratesheetId) {
            setIsLoading(false)
            setLoadError(false)
            return
        }
        setIsLoading(true)
        setEditTimingConditional(false)
        setLoadError(false)
        api.get(`/ratesheets/time-conditionals/${ratesheetId}`)
            .then(response => {
                setTimingConditionals(response.map(conditional => {
                    return {
                        ...conditional,
                        action: JSON.parse(conditional.action),
                        continue_on: conditional.continue_on ?? 1
                    }
                }))
                setLoadError(false)
            })
            .catch(() => {
                setTimingConditionals([])
                setLoadError(true)
            })
            .finally(() => {
                setIsLoading(false)
            })
    }

    useImperativeHandle(ref, () => ({ reload, loadError }))

    useEffect(() => {
        reload()
    }, [ratesheetId])

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <Button
                            variant='success'
                            onClick={() => setShowTimingConditionalModal(true)}
                        >
                            <i className='fas fa-plus'></i> Create
                        </Button>
                    </Col>
                    <Col md={10}>
                        <Card.Title>
                            <h4 className='text-muted'>Timing Conditionals</h4>
                        </Card.Title>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <MaterialReactTable table={table} />
            </Card.Body>
            <TimingConditionalModal
                conditional={editTimingConditional}
                deliveryTypes={deliveryTypeOptions}
                mapZones={mapZones}
                zoneTypes={zoneTypes}
                onHide={hideModal}
                ratesheetId={ratesheetId}
                reload={reload}
                show={showTimingConditionalModal}
            />
        </Card>
    );
})

export default TimingConditionalsTab;
