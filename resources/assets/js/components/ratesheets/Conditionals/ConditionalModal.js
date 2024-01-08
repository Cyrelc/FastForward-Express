import React, {Fragment, useCallback, useEffect, useRef, useState} from 'react'
import {Button, Card, Col, InputGroup, FormControl, Modal, Row} from 'react-bootstrap'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/ui'
import {BootstrapConfig} from '@react-awesome-query-builder/bootstrap'
import Select from 'react-select'
import {debounce} from 'lodash'

const math = require('mathjs')

math.createUnit('CAD')
// math.createUnit('lbs', '1 lb')

const renderBuilder = (props) => (
    <div className='query-builder-container' style={{padding: '10px'}}>
        <div className='query-builder'>
            <Builder {...props} />
        </div>
    </div>
)

const addressSubfields = (prefix) => {
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
        },
        is_mall: {
            label: `${prefix} Is Mall`,
            type: 'boolean',
            default: true
        }
    }
}

const timeSubfields = (prefix) => {
    return {
        day_of_the_week: {
            label: `${prefix} Day of the Week`,
            type: 'select',
            fieldSettings: {
                listValues: [
                    {value: 0, title: 'Sunday'},
                    {value: 1, title: 'Monday'},
                    {value: 2, title: 'Tuesday'},
                    {value: 3, title: 'Wednesday'},
                    {value: 4, title: 'Thursday'},
                    {value: 5, title: 'Friday'},
                    {value: 6, title: 'Saturday'},
                ]
            }
        },
        time: {
            label: `${prefix} Time (Scheduled)`,
            type: 'time'
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

const allowedVariableNameTooltip = `
The following variable names are currently supported:
-----------------------------------------------------
${availableTestVariables.map(variable => `${variable.name}: ${variable.description}\n`)}
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
                    label: 'Total Weight (kg)',
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
        time_delivery_scheduled: {
            type: '!struct',
            label: 'Delivery Time',
            subfields: timeSubfields('Delivery')
        },
        time_pickup_scheduled: {
            type: '!struct',
            label: 'Pickup Time',
            subfields: timeSubfields('Pickup')
        }
    }
}

const imperialToMetric = {
    'lb': 'kg',
    'kgs': 'kg',
    'kg': 1,
    'oz': 'g',
    'in': 'cm',
    'ft': 'm',
    'yd': 'm',
    'mi': 'km',
    'sqft': 'm2',
    'CAD': 1
}

const valueTypes = [
    {value: 'percent', label: 'Percent'},
    {value: 'amount', label: 'Amount'},
    {value: 'equation', label: 'Equation'}
]

const ConditionalModal = props => {
    const [demoEquationString, setDemoEquationString] = useState('')
    const [equationString, setEquationString] = useState('')
    const [serverEquationString, setServerEquationString] = useState('')

    const [demoResult, setDemoResult] = useState('')
    const [name, setName] = useState('')
    const [queryTree, setQueryTree] = useState(QbUtils.checkTree(QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}), config))
    const [resultAction, setResultAction] = useState({value: 'charge', label: 'Charge'})
    const [resultValue, setResultValue] = useState(0)
    const [testVariables, setTestVariables] = useState(availableTestVariables)
    const [valueType, setValueType] = useState({value: 'amount', label: 'Amount'})

    const {ratesheetId} = props
    const {conditional_id} = props.conditional

    const debouncedEvaluateEquationString = useCallback(
        debounce(() => {
            // Ensure every number is followed by a unit or a variable by a bracket or operator
            let equation = equationString.replace(/\$(\d+(\.\d+)?)/g, (match, value) => {
                return value + " CAD";
            });

            let regex = /(\b[a-zA-Z_][a-zA-Z0-9_]*\b|\d+(\.\d+)?)\s*(\b[a-zA-Z_][a-zA-Z0-9_]*\b)?/g;
            let match;
            while ((match = regex.exec(equation)) !== null) {
                const value = match[1]
                const unit = match[3]

                const isVariable = availableTestVariables.some(variable => variable.dbName == value)
                const isValue = !isNaN(parseFloat(value))

                console.log(unit, imperialToMetric[unit], Object.keys(imperialToMetric).includes(unit))

                if(!isVariable && !isValue) {
                    setDemoResult('Invalid variable: ', value)
                } else if(!Object.keys(imperialToMetric).includes(unit) && imperialToMetric[unit] != 1) {
                    setDemoResult(`Missing unit, invalid unit, or incorrect placement in equation near: ${value}`);
                }
            }

            const demoEquation = equation.replace(/(([\d.]+)\s*([a-zA-Z$]+)|(\b[a-zA-Z_][a-zA-Z0-9_]*\b)\s*([a-zA-Z$]*))/g, (match, _, number, unit, variable, varUnit) => {
                if (variable) {
                    let value = testVariables.find(v => v.dbName == variable)?.value
                    if (value) {
                        if (varUnit && imperialToMetric.hasOwnProperty(varUnit) && imperialToMetric[varUnit] != 1) {
                            try {
                                let metricUnit = imperialToMetric[varUnit];
                                value = math.unit(value, varUnit).toNumber(metricUnit);
                                return `${value}`;
                            } catch (error) {
                                console.log(error);
                                return match;
                            }
                        } else {
                            console.log('else return ${value}.trim()')
                            return `${value}`.trim();
                        }
                    } else {
                        console.log(`Unknown variable: ${variable}`)
                        setDemoResult(`Unknown variable: ${variable}`);
                        return match;
                    }
                } else {
                    number = parseFloat(number);
                    if (imperialToMetric.hasOwnProperty(unit) && imperialToMetric[unit] != 1) {
                        try {
                            let metricUnit = imperialToMetric[unit];
                            let newValue = math.unit(number, unit).toNumber(metricUnit);
                            return `${newValue}`;
                        } catch (error) {
                            return match;
                        }
                    } else {
                        return `${number}`;
                    }
                }
            });

            const serverEquation = equation.replace(/(([\d.]+)\s*([a-zA-Z$]+)|(\b[a-zA-Z_][a-zA-Z0-9_]*\b)\s*([a-zA-Z$]*))/g, (match, _, number, unit, variable, varUnit) => {
                if (variable) {
                    return variable;
                } else {
                    number = parseFloat(number);
                    if (imperialToMetric.hasOwnProperty(unit) && imperialToMetric[unit] != 1) {
                        try {
                            let metricUnit = imperialToMetric[unit];
                            let newValue = math.unit(number, unit).toNumber(metricUnit);
                            return `${newValue}`;
                        } catch (error) {
                            return match;
                        }
                    } else {
                        return `${number}`;
                    }
                }
            });
            console.log(serverEquation)

            try {
                let result = math.evaluate(demoEquation)
                // Verify that the result is a number
                if (isNaN(result)) {
                    setDemoResult('Invalid equation');
                } else {
                    setDemoResult('$' + result.toFixed(2))
                }
            } catch (error) {
                setDemoResult(`Error in evaluating the equation: ${error.message}`);
            }

            setDemoEquationString(demoEquation)
            setServerEquationString(serverEquation)
        }, 500), [equationString, testVariables])

    // On page load, load the condition into the tree by parsing it from JSON logic
    // (or set an empty logic tree, if creating)
    useEffect(() => {
        const tree = props.conditional ? QbUtils.loadFromJsonLogic(JSON.parse(props.conditional.json_logic), config) : QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}, config)
        setQueryTree(tree)

        setName(props.conditional?.name)
        setResultAction(props.conditional?.action ?? {value: 'charge', label: 'Charge'})
        setValueType(props.conditional?.value_type ? valueTypes.find(valueType => props.conditional.value_type == valueType.value) : {value: 'amount', label: 'Amount'})
        setResultValue(props.conditional?.value ?? 0)
        setEquationString(props.conditional?.value_type == 'equation' ? props.conditional.original_equation_string : '')
        setEquationString(props.conditional?.value_type == 'equation' ? props.conditional.equation_string : '')
    }, [props.conditional])

    useEffect(() => {
        debouncedEvaluateEquationString()
    }, [equationString, testVariables])

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
                equation_string: serverEquationString,
                ratesheet_id: ratesheetId,
                json_logic: JSON.stringify(jsonLogic['logic']),
                human_readable: humanReadable,
                name,
                original_equation_string: equationString,
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

    return (
        <Modal show={props.show} onHide={props.onHide} size='xl'>
            <Row>
                <Col md={2}>
                    <ul>
                        <li>Units</li>
                        <ul>
                            <li>$</li>
                            <li>lb</li>
                            <li>kg</li>
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
                                            {testVariables
                                                .filter(variable => equationString.includes(variable.dbName))
                                                .map(variable => (
                                                    <Col md={4} key={variable.dbName}>
                                                        <InputGroup>
                                                            <InputGroup.Text>{variable.name}</InputGroup.Text>
                                                            <FormControl
                                                                type='number'
                                                                onChange={event => handleVariableValueChange(event, variable.dbName)}
                                                                value={variable.value}
                                                            />
                                                            {variable.unit && <InputGroup.Text>{variable.unit}</InputGroup.Text>}
                                                        </InputGroup>
                                                    </Col>
                                                )
                                            )}
                                        </Col>
                                        <Col md={2}>
                                            <h5 className='text-muted'>Test Result</h5>
                                        </Col>
                                        <Col md={10}><h4>{demoResult}</h4></Col>
                                    </Row>
                                </Fragment>
                            }
                        </Card.Body>
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
