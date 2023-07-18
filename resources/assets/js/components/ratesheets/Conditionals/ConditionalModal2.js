

import React, {Fragment, useEffect, useRef, useState} from 'react'
import {Button, ButtonGroup, Card, Col, InputGroup, FormControl, Modal, Row} from 'react-bootstrap'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/ui'
// import {EquationEvaluate, EquationOptions, defaultErrorHandler} from 'react-equation'
// import {defaultVariables, defaultFunctions} from 'equation-resolver'
import {BootstrapConfig} from '@react-awesome-query-builder/bootstrap'
import Select from 'react-select'

const renderBuilder = (props) => (
    <div className='query-builder-container' style={{padding: '10px'}}>
        <div className='query-builder'>
            <Builder {...props} />
        </div>
    </div>
)

const addressSubfields = (prefix) => {
    // console.log(prefix)
    return {
        zone: {
            type: '!struct',
            label: 'Zone',
            label2: `${prefix} Zone`,
            subfields: {
                name: {
                    label: `${prefix} Zone Name`,
                    type: 'text',
                    operators: [
                        'equal',
                        'not_equal',
                        'like',
                        'not_like'
                    ]
                },
                type: {
                    label: `${prefix} Zone Type`,
                    type: 'select',
                    fieldSettings: {
                        listValues: [
                            {value: 'internal', title: 'Internal'},
                            {value: 'peripheral', title: 'Peripheral'},
                            {value: 'outlying', title: 'Outlying'},
                        ]
                    }
                }
            }
        }
    }
}

const availableTestVariables = [
    {
        dbName: 'total_weight',
        description: 'The total weight of all packages in the delivery',
        name: `Total Weight`,
        type: 'number',
        value: 2000
    }
]

// const availableTestVariables = {
//     total_weight: {
//         dbName: 'total_weight',
//         description: 'The total weight of all packages in the delivery',
//         name: `Total Weight`,
//         type: 'number',
//         value: 2000
//     }
// }

const allowedVariableNameTooltip = `
The following variable names are currently supported:
-----------------------------------------------------
${availableTestVariables.map((variable, values) => `${values.name}: ${values.description}\n`)}
`

const config = {
    ...BootstrapConfig,
    settings: {
        ...BootstrapConfig.settings,
    },
    fields: {
        package: {
            type: '!struct',
            label: 'Package',
            subfields: {
                is_pallet: {
                    label: 'Is Pallet',
                    type: 'boolean',
                    default: true
                },
                total_weight: {
                    label: 'Total Weight',
                    type: 'number'
                }
            }
        },
        delivery_address: {
            type: '!struct',
            label: 'Delivery Address',
            subfields: addressSubfields('Delivery Address')
        },
        pickup_address: {
            type: '!struct',
            label: 'Pickup Address',
            subfields: addressSubfields('Pickup Address')
        },
    }
}

const valueTypes = [
    {value: 'percent', label: 'Percent'},
    {value: 'amount', label: 'Amount'},
    {value: 'equation', label: 'Equation'}
]

const ConditionalModal = props => {
    const [equationString, setEquationString] = useState('')
    const [name, setName] = useState('')
    const [queryTree, setQueryTree] = useState(QbUtils.checkTree(QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}), config))
    const [resultAction, setResultAction] = useState({value: 'charge', label: 'Charge'})
    const [resultValue, setResultValue] = useState(0)
    const [valueType, setValueType] = useState({value: 'amount', label: 'Amount'})
    const [testVariables, setTestVariables] = useState(availableTestVariables)
    const [successfulTestCount, setSuccessfulTestCount] = useState(0)

    const {ratesheetId} = props
    const {conditional_id} = props.conditional

    // On page load, load the condition into the tree by parsing it from JSON logic
    // (or set an empty logic tree, if creating)
    useEffect(() => {
        const tree = props.conditional ? QbUtils.loadFromJsonLogic(JSON.parse(props.conditional.json_logic), config) : QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}, config)
        setQueryTree(tree)

        setName(props.conditional?.name)
        setResultAction(props.conditional?.action ?? {value: 'Charge', label: 'charge'})
        setValueType(props.conditional?.value_type ? valueTypes.find(valueType => props.conditional.value_type == valueType.value) : {value: 'Amount', label: 'Amount'})
        setResultValue(props.conditional?.value ?? 0)
        setEquationString(props.conditional?.value_type == 'equation' ? props.conditional.equation_string : '')
    }, [props.conditional])

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
                action: resultAction,
                equation_string: equationString,
                ratesheet_id: ratesheetId,
                json_logic: jsonLogic['logic'],
                human_readable: humanReadable,
                name,
                value: resultValue,
                value_type: valueType.value
            }

            makeAjaxRequest(`/ratesheets/conditional/${conditional_id ?? ''}`, 'POST', data, response => {
                toastr.clear()
                toastr.success('Success', `Successfully stored conditional ${response.conditional_id}`)
                props.reload()
                props.onHide()
            })
        } catch (e) {
            console.log(e)
            return
        }
    }

    const testEquation = () => {
        let variablesObject = {}
        testVariables.forEach(variable => {
            variablesObject[variable.dbName] = variable.value
        })

        const urlParams = new URLSearchParams({
            expression: equationString,
            ratesheet_id: ratesheetId,
            variables: JSON.stringify(variablesObject)
        })

        makeAjaxRequest(`/charges/conditional?${urlParams.toString()}`, 'GET', null, response => {
            console.log(response)
            // setSuccessfulTestCount(successfulTestCount + 1)
        })

        console.log('Do a test!')
    }

    return (
        <Modal show={props.show} onHide={props.onHide} size='xl'>
            <Row>
                <Col md={2}>
                    <ul>
                        <li>Units</li>
                        <ul>
                            <li>lbs</li>
                            <li>kgs</li>
                        </ul>
                    </ul>
                    <ul>
                        <li>Variables</li>
                        <ul>
                            <li>total_weight</li>
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
                                        <InputGroup.Text>Rule Name: </InputGroup.Text>
                                        <FormControl
                                            name='name'
                                            value={name}
                                            onChange={event => setName(event.target.value)}
                                        />
                                    </InputGroup>
                                </Col>
                            </Row>
                        </Card.Header>
                        <Card.Body>
                            <Row>
                                <Col md={2}>
                                    <h4 className='text-muted'>If</h4>
                                </Col>
                                <Col md={10}>
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
                                <p>{JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)}</p>
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
                                        value={resultAction}
                                        onChange={setResultAction}
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
                            {valueType.value == 'equation' &&
                                <Fragment>
                                    <hr/>
                                    <Row className='bottom15'>
                                        <Col md={2}>
                                            <h4 className='text-muted'>Equation</h4>
                                        </Col>
                                        <Col md={10}>
                                            <InputGroup>
                                                <FormControl
                                                    onChange={event => setEquationString(event.target.value)}
                                                    value={equationString}
                                                />
                                                <Button onClick={testEquation} variant='primary'>Test Equation</Button>
                                            </InputGroup>
                                        </Col>
                                    </Row>
                                    <Row>
                                        <Col md={2}>
                                            <h5 className='text-muted'>
                                                Test Variables <i className='fas fa-question-circle' title={allowedVariableNameTooltip}></i>
                                            </h5>
                                        </Col>
                                        <Col md={10}>
                                            {testVariables
                                                .filter(variable => equationString.includes(variable.dbName))
                                                .map(variable => (
                                                    <Col md={4} key={variable.dbName}>
                                                        <InputGroup>
                                                            <InputGroup.Text>{variable.name}</InputGroup.Text>
                                                            <FormControl
                                                                type='number'
                                                                onChange={event => handleVariableValueChange(event, key)}
                                                                value={variable.value}
                                                            />
                                                        </InputGroup>
                                                    </Col>
                                                )
                                            )}
                                        </Col>
                                    </Row>
                                </Fragment>
                            }
                        </Card.Body>
                        <Card.Footer style={{textAlign: 'center'}}>
                            <Button
                                onClick={storeConditional}
                                variant='success'
                                disabled={valueType.value == 'equation' ? successfulTestCount < 1 : false}
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
