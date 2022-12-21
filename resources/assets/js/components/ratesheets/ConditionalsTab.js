import React, {useEffect, useState} from 'react'
import {Button, Card, Col, Row, Table} from 'react-bootstrap'

import ConditionalModal from './ConditionalModal'

const formatCondition = condition => {
    return 'friendly formatted condition'
}

const formatResult = result => {
    return 'friendly formatted result'
}

export default function ConditionalsTab(props) {
    const [conditionals, setConditionals] = useState([])
    const [isLoading, setIsLoading] = useState(true)
    const [editConditional, setEditConditional] = useState(false)
    const [showConditionalModal, setShowConditionalModal] = useState(false)

    const deleteConditional = conditional => {
        makeAjaxRequest(`/ratesheets/conditional/${conditional.conditional_id}`, 'DELETE', null, response => {
            reload()
        })
    }

    const edit = conditional => {
        setEditConditional(conditional)
        setShowConditionalModal(true)
    }

    const reload = () => {
        setIsLoading(true)
        makeAjaxRequest(`/ratesheets/conditionals/${props.ratesheetId}`, 'GET', null, response => {
            setConditionals(JSON.parse(response).map(conditional => {
                return {...conditional, value_type: JSON.parse(conditional.value_type), action: JSON.parse(conditional.action)}
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
                            onClick={() => setShowConditionalModal(!showConditionalModal)}
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
                <Table>
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
                                            onClick={() => edit(conditional)}
                                            variant='warning'
                                            size='sm'
                                        >
                                            <i className='fas fa-edit'></i>
                                        </Button>
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
                                    <td>{conditional.value_type['label']}</td>
                                    <td>
                                        {conditional.value_type['value'] == 'amount'
                                            ? conditional.value.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})
                                            : `${conditional.value}%`
                                        }
                                    </td>
                                </tr>
                            )
                        })}
                    </tbody>
                </Table>
            </Card.Body>
            <ConditionalModal
                conditional={editConditional}
                mapZones={props.mapZones}
                onHide={() => setShowConditionalModal(false)}
                ratesheetId={props.ratesheetId}
                reload={reload}
                show={showConditionalModal}
            />
        </Card>
    )
}
