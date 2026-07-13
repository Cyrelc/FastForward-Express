import React, {useEffect, useState, forwardRef, useImperativeHandle} from 'react'
import {Button, ButtonGroup, Card, Col, FormControl, Modal, Row} from 'react-bootstrap'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'
import {toast} from 'react-toastify'

import PricingConditionalModal from './PricingConditionalModal'
import {exportConditionalString, parseConditionalString} from './pricingConditionalPortability'
import {useAPI} from '../../../contexts/APIContext'
import {useLists} from '../../../contexts/ListsContext'
import {nextPriorityFor} from '../conditionalFieldShared'
import RuleTesterModal from '../RuleTesterModal'



const PricingConditionalsTab = forwardRef(function PricingConditionalsTab(props, ref) {
    const api = useAPI()
    const {chargeTypes} = useLists()

    const [conditionals, setConditionals] = useState([])
    const [showRuleTester, setShowRuleTester] = useState(false)
    const [isLoading, setIsLoading] = useState(true)
    const [editConditional, setEditConditional] = useState(false)
    const [showConditionalModal, setShowConditionalModal] = useState(false)
    const [showImportModal, setShowImportModal] = useState(false)
    const [importText, setImportText] = useState('')

    const table = useMaterialReactTable({
        data: conditionals,
        columns: [
            {header: 'Actions', enableColumnActions: false, size: 140, Cell: ({row}) => (
                <ButtonGroup>
                    <Button onClick={() => edit(row.original)} variant='primary' size='sm'>
                        <i className='fas fa-edit'></i>
                    </Button>
                    <Button onClick={() => copyRule(row.original)} variant='secondary' size='sm' title='Copy rule to clipboard'>
                        <i className='fas fa-copy'></i>
                    </Button>
                    <Button variant='danger' size='sm' onClick={() => deleteConditional(row.original)}>
                        <i className='fas fa-trash'></i>
                    </Button>
                </ButtonGroup>
            )},
            {header: 'Priority', accessorKey: 'priority', size: 110, enableColumnActions: false,
                // Priority is the order the engine runs rules in, and ties are common (the value is
                // free-form), so the id breaks them - which puts equal-priority rules in the order they
                // were created. Sorted numerically too: the default comparator is alphanumeric, which
                // would put 100 before 9.
                sortingFn: (rowA, rowB) => {
                    const priority = row => Number(row.original.priority ?? 0)
                    const id = row => Number(row.original.conditional_id ?? 0)

                    return (priority(rowA) - priority(rowB)) || (id(rowA) - id(rowB))
                }},
            {header: 'Conditional Type', id: 'type', size: 220, accessorFn: (row) => {
                const chargeType = chargeTypes.find(chargeType => chargeType.value === row.type)
                return chargeType ? chargeType.label : 'Unknown'
            }},
            {accessorKey: 'name', header: 'Name', grow: 1},
            {header: 'Condition', accessorKey: 'human_readable', grow: 4},
            {header: 'Action', id: 'action', size: 120, accessorFn: (row) => row.action?.label ?? ''},
            {header: 'Value Type', accessorKey: 'value_type', size: 120},
            {header: 'Value', accessorKey: 'value', size: 120, Cell: ({row}) => {
                const {value_type, value} = row.original
                if(value_type === 'amount')
                    return Number(value).toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})
                else if(value_type === 'percent')
                    return `${value}%`
                return value
            }}
        ],
        layoutMode: 'grid-no-grow',
        initialState: {
            sorting: [{id: 'priority', desc: false}],
        },
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

    const copyRule = conditional => {
        try {
            const str = exportConditionalString(conditional, chargeTypes)
            if(navigator.clipboard && navigator.clipboard.writeText)
                navigator.clipboard.writeText(str)
            toast.success(`Copied rule "${conditional.name}" to clipboard`)
        } catch (error) {
            toast.error('Failed to copy rule')
        }
    }

    const importRule = () => {
        let parsed
        try {
            parsed = parseConditionalString(importText, chargeTypes)
        } catch (error) {
            toast.error(error.message || 'Could not read that rule string')
            return
        }
        // Open the editor pre-filled so the user can review/remap zones before saving as a new rule.
        setShowImportModal(false)
        setImportText('')
        setEditConditional(parsed)
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

    useImperativeHandle(ref, () => ({ reload }))

    useEffect(() => {
        reload()
    }, [])

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={4}>
                        <ButtonGroup>
                            <Button
                                variant='success'
                                onClick={() => setShowConditionalModal(true)}
                            >
                                <i className='fas fa-plus'></i> Create
                            </Button>
                            {/* Sits with the rules rather than on its own tab: the loop that matters is
                                write a rule -> see what it does -> fix it, without leaving the page. */}
                            <Button
                                variant='outline-primary'
                                onClick={() => setShowRuleTester(true)}
                                title='Price a pretend delivery against this ratesheet'
                            >
                                <i className='fas fa-vial'></i> Test a Delivery
                            </Button>
                            {/* Two different imports: paste a single exported rule, or copy rules
                                across from another ratesheet. */}
                            <Button
                                variant='outline-secondary'
                                onClick={() => setShowImportModal(true)}
                                title='Paste an exported rule'
                            >
                                <i className='fas fa-paste'></i> Paste Rule
                            </Button>
                            <Button
                                variant='outline-secondary'
                                onClick={props.openImport}
                                title='Copy rules from another ratesheet'
                            >
                                <i className='fas fa-file-import'></i> Import
                            </Button>
                        </ButtonGroup>
                    </Col>
                    <Col md={8}>
                        <Card.Title>
                            <h4 className='text-muted'>Pricing Conditionals</h4>
                        </Card.Title>
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <MaterialReactTable table={table} />
            </Card.Body>
            <RuleTesterModal
                show={showRuleTester}
                ratesheetId={props.ratesheetId}
                onHide={() => setShowRuleTester(false)}
            />
            <PricingConditionalModal
                conditional={editConditional}
                conditionals={conditionals}
                nextPriority={nextPriorityFor(conditionals)}
                deliveryTypes={props.deliveryTypes}
                mapZones={props.mapZones}
                zoneGroups={props.zoneGroups}
                onHide={hideModal}
                ratesheetId={props.ratesheetId}
                reload={reload}
                show={showConditionalModal}
            />
            <Modal show={showImportModal} onHide={() => setShowImportModal(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Import Rule</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <p className='text-muted'>
                        Paste a copied rule string below. It opens in the editor so you can review it
                        (and remap any zones from another map) before saving.
                    </p>
                    <FormControl
                        as='textarea'
                        rows={6}
                        value={importText}
                        onChange={event => setImportText(event.target.value)}
                        placeholder='Paste rule string here...'
                    />
                </Modal.Body>
                <Modal.Footer>
                    <Button variant='secondary' onClick={() => setShowImportModal(false)}>Cancel</Button>
                    <Button variant='success' onClick={importRule} disabled={!importText.trim()}>
                        Review &amp; Import
                    </Button>
                </Modal.Footer>
            </Modal>
        </Card>
    )
})

export default PricingConditionalsTab;
