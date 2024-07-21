import React, {Fragment, useState} from 'react'
import {Button, Card, Col, InputGroup, FormControl, Modal, Row, Table} from 'react-bootstrap'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/ui'
import Select from 'react-select'
import {toast} from 'react-toastify'

import {useAPI} from '../../../contexts/APIContext'
import config, {availableTestVariables, valueTypes} from './conditionalConfig'
import useConditional from './useConditional'
import {useLists} from '../../../contexts/ListsContext'

const renderBuilder = (props) => (
    <div className='query-builder-container' style={{padding: '10px'}}>
        <div className='query-builder'>
            <Builder {...props} />
        </div>
    </div>
)

const ConditionalModal = props => {
    const {conditional: {conditional_id} = null, ratesheetId} = props
    const {
        action,
        demoResult,
        equationString,
        name,
        priority,
        queryTree,
        resultValue,
        serverEquationString,
        setAction,
        setEquationString,
        setName,
        setPriority,
        setQueryTree,
        setResultValue,
        setServerEquationString,
        setTestVariables,
        setType,
        setValueType,
        testVariables,
        type,
        valueType,
    } = useConditional({conditional: props.conditional})

    const api = useAPI()
    const {chargeTypes} = useLists()

    const allowedVariableNameTooltip = `
    The following variable names are currently supported:
    -----------------------------------------------------
    ${availableTestVariables.map(variable => `${variable.name}: ${variable.description}\n`)}
    `

    const handleVariableValueChange = (event, key) => {
        const updatedVariables = testVariables.map(variable => {
            if(variable.dbName == key)
                return {...variable, value: Number(event.target.value)}
            else
                return variable
        })
        setTestVariables(updatedVariables)
    }

    const storeConditional = () => {
        try {
            const humanReadable = JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)
            const jsonLogic = QbUtils.jsonLogicFormat(queryTree, config)

            if(jsonLogic['errors'].length)
                throw 'Error encountered forming JSON logic'

            const data = {
                action: action,
                ratesheet_id: ratesheetId,
                json_logic: JSON.stringify(jsonLogic['logic']),
                human_readable: humanReadable,
                name,
                original_equation_string: equationString,
                priority: priority,
                type: type.value,
                value: resultValue,
                value_type: valueType.value
            }

            api.post(`/ratesheets/conditional${conditional_id ? `/${conditional_id}` : ''}`, data).then(response => {
                toast.success(`Successfully stored conditional "${name}"`)
                props.reload()
                props.onHide()
            })
        } catch (e) {
            console.log(e)
            return
        }
    }

    return (
        <Modal show={props.show} onHide={props.onHide} size='xl'>
            <Row>
                <Col md={2}>
                    <ul>
                        <li>Supported Units</li>
                        <ul>
                            <li>Currency</li>
                                <ul>
                                    <li>$</li>
                                    <li>CAD</li>
                                </ul>
                            <li>Weight</li>
                                <ul>
                                    <li>lb</li>
                                    <li>kg</li>
                                </ul>
                            <li>Length</li>
                                <ul>
                                    <li>cm</li>
                                    <li>in</li>
                                    <li>ft</li>
                                    <li>m</li>
                                </ul>
                        </ul>
                    </ul>
                    <ul>
                        <li>Variables</li>
                        <ul>
                            <li>total_weight</li>
                            <li>longest_side</li>
                        </ul>
                    </ul>
                </Col>
                <Col md={10}>
                    <Card>
                        <Card.Header>
                            <Row>
                                <Col md={4}>
                                    <h4 className='text-muted'>
                                        {`${conditional_id ? 'Edit' : 'Create'} Conditional ${conditional_id ?? ''}`}
                                    </h4>
                                </Col>
                                <Col md={8}>
                                    <InputGroup>
                                        <InputGroup.Text>Name: </InputGroup.Text>
                                        <FormControl
                                            name='name'
                                            value={name}
                                            onChange={event => setName(event.target.value)}
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={6}>
                                    <InputGroup>
                                        <InputGroup.Text>Type:</InputGroup.Text>
                                        <Select
                                            options={chargeTypes}
                                            value={type}
                                            onChange={setType}
                                        />
                                    </InputGroup>
                                </Col>
                                <Col md={6}>
                                    <InputGroup>
                                        <InputGroup.Text>Priority:</InputGroup.Text>
                                        <FormControl
                                            type='number'
                                            step='1'
                                            min='0'
                                            value={priority}
                                            onChange={event => setPriority(event.target.value)}
                                        />
                                    </InputGroup>
                                </Col>
                            </Row>
                        </Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={1}>
                                    <h4 className='text-muted' style={{padding: 0}}>If</h4>
                                </Col>
                                <Col md={11}>
                                    <p>{JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)}</p>
                                </Col>
                                <Col md={12} style={{padding: 0}}>
                                    <Query
                                        {...config}
                                        value={queryTree}
                                        onChange={setQueryTree}
                                        renderBuilder={renderBuilder}
                                    />
                                </Col>
                            </Row>
                            <hr/>
                            <Row>
                                <Col md={2}>
                                    <h4 className='text-muted'>Then</h4>
                                </Col>
                                <Col md={3}>
                                    <Select
                                        options={[
                                            {value: 'charge', label: 'Charge'},
                                            {value: 'discount', label: 'Discount'},
                                        ]}
                                        value={action}
                                        onChange={setAction}
                                    />
                                </Col>
                                <Col md={3}>
                                    <Select
                                        options={valueTypes}
                                        value={valueType}
                                        onChange={setValueType}
                                    />
                                </Col>
                                {valueType.value != 'equation' &&
                                    <Col md={3}>
                                        <InputGroup style={{paddingTop: '0px'}}>
                                            {valueType.value != 'percent' && <InputGroup.Text>$</InputGroup.Text>}
                                            <FormControl
                                                type='number'
                                                step='0.01'
                                                min='0'
                                                onChange={event => setResultValue(event.target.value)}
                                                value={resultValue}
                                            />
                                            {valueType.value == 'percent' && <InputGroup.Text>%</InputGroup.Text>}
                                        </InputGroup>
                                    </Col>
                                }
                            </Row>
                        </Card.Body>
                            {valueType.value == 'equation' &&
                                <Card.Body>
                                    <hr/>
                                    <Row className='bottom15'>
                                        <Col md={2}>
                                            <h4 className='text-muted'>Equation</h4>
                                        </Col>
                                        <Col md={10}>
                                            <Row>
                                                <Col md={12} style={{paddingBottom: 15}}>
                                                    <FormControl
                                                        onChange={event => setEquationString(event.target.value)}
                                                        value={equationString}
                                                    />
                                                </Col>
                                                <Col md={12}>
                                                    <FormControl
                                                        value={serverEquationString}
                                                        readOnly
                                                        disabled
                                                    />
                                                </Col>
                                            </Row>
                                        </Col>
                                    </Row>
                                    <Row>
                                        <Col md={2}>
                                            <h5 className='text-muted'>
                                                Test Variables <i className='fas fa-question-circle' title={allowedVariableNameTooltip}></i>
                                            </h5>
                                        </Col>
                                        <Col md={10}>
                                            {testVariables.map(variable => (
                                                <Col md={12} key={variable.dbName}>
                                                    <Table variant='striped' size='sm'>
                                                        <thead>
                                                            <tr>
                                                                <th>{variable.name}</th>
                                                                <th>Result</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>
                                                                    <FormControl
                                                                        type='number'
                                                                        onChange={event => handleVariableValueChange(event, variable.dbName)}
                                                                        value={variable.value}
                                                                    />
                                                                </td>
                                                                <td>{demoResult}</td>
                                                            </tr>
                                                        </tbody>
                                                    </Table>
                                                </Col>
                                                )
                                            )}
                                        </Col>
                                    </Row>
                                </Card.Body>
                            }
                        <Card.Footer style={{textAlign: 'center'}}>
                            <Button
                                onClick={storeConditional}
                                variant='success'
                            >
                                Submit
                            </Button>
                        </Card.Footer>
                    </Card>
                </Col>
            </Row>
        </Modal>
    )
}

export default ConditionalModal;
