import React, {Fragment, useState} from 'react'
import {Button, Card, Col, InputGroup, FormControl, Modal, Row, Table} from 'react-bootstrap'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/mui'
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

// Syntax highlighter for equation input
const HighlightedEquationInput = ({value, onChange, placeholder}) => {
    const imperialToMetric = {
        'lb': true, 'lbs': true, 'kg': true, 'kgs': true, 'oz': true, 'g': true,
        'in': true, 'ft': true, 'cm': true, 'm': true, 'yd': true, 'mi': true, 'km': true,
        'sqft': true, 'm2': true, 'CAD': true
    }
    
    const functions = ['max', 'min', 'round', 'abs', 'sqrt', 'pow']
    
    const highlightSyntax = (text) => {
        if (!text) return null
        
        const tokens = []
        const regex = /(\d+\.?\d*)|([a-zA-Z_][a-zA-Z0-9_]*)|([+\-*/^()$])|(\s+)/g
        let match
        let currentPos = 0
        
        while ((match = regex.exec(text)) !== null) {
            const [fullMatch, number, word, operator, space] = match
            const isVariable = availableTestVariables.some(v => v.dbName === word)
            const isFunction = functions.includes(word?.toLowerCase())
            const isUnit = imperialToMetric[word]
            
            let style = {}
            
            if (number) {
                style = {color: '#0066cc', fontWeight: '500'}
            } else if (isVariable) {
                style = {color: '#22863a', fontWeight: '600', backgroundColor: '#e6f7ff', padding: '0 2px', borderRadius: '2px'}
            } else if (isFunction) {
                style = {color: '#6f42c1', fontWeight: '600'}
            } else if (isUnit) {
                style = {color: '#005cc5', fontWeight: '500'}
            } else if (operator) {
                style = {color: '#d73a49', fontWeight: '600'}
            } else if (word && !space) {
                // Error: unrecognized word
                style = {color: '#fff', backgroundColor: '#dc3545', fontWeight: '600', borderRadius: '3px', padding: '1px 4px'}
            }
            
            tokens.push(<span key={currentPos} style={style}>{fullMatch}</span>)
            currentPos = match.index + fullMatch.length
        }
        
        return tokens
    }
    
    return (
        <div style={{position: 'relative'}}>
            {/* Actual input */}
            <FormControl
                onChange={onChange}
                value={value}
                placeholder={placeholder}
                style={{
                    position: 'relative',
                    zIndex: 2,
                    backgroundColor: value ? 'rgba(255, 255, 255, 0.05)' : 'white',
                    fontFamily: 'Consolas, Monaco, "Courier New", monospace',
                    fontSize: '1rem',
                    color: value ? 'transparent' : '#495057',
                    caretColor: '#000'
                }}
                spellCheck={false}
            />
            
            {/* Highlighted overlay */}
            <div style={{
                position: 'absolute',
                top: '1px',
                left: '1px',
                right: '1px',
                pointerEvents: 'none',
                padding: '0.375rem 0.75rem',
                whiteSpace: 'pre-wrap',
                wordWrap: 'break-word',
                fontFamily: 'Consolas, Monaco, "Courier New", monospace',
                fontSize: '1rem',
                lineHeight: '1.5',
                zIndex: 1
            }}>
                {highlightSyntax(value)}
            </div>
        </div>
    )
}

const ConditionalModal = props => {
    const {conditional: {conditional_id} = null, ratesheetId} = props
    const {
        action,
        demoResult,
        equationString,
        name,
        priority,
        queryTree,
        reset,
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
        validationMessage,
        valueType,
    } = useConditional({conditional: props.conditional})

    const api = useAPI()
    const {chargeTypes} = useLists()
    const [isSubmitting, setIsSubmitting] = useState(false)

    const allowedVariableNameTooltip = `
    The following variable names are currently supported:
    -----------------------------------------------------
    ${availableTestVariables.map(variable => `${variable.name}: ${variable.description}\n`)}
    `

    const handleVariableValueChange = (event, key) => {
        const updatedVariables = testVariables.map(variable => {
            if(variable.dbName === key)
                return {...variable, value: Number(event.target.value)}
            else
                return variable
        })
        setTestVariables(updatedVariables)
    }

    const hideModal = () => {
        reset()
        props.onHide()
    }

    const storeConditional = () => {
        // Validation
        if(!name || name.trim() === '') {
            toast.error('Name is required')
            return
        }

        if(!type || !type.value) {
            toast.error('Conditional type is required')
            return
        }

        if(!priority || priority < 0 || !Number.isInteger(Number(priority))) {
            toast.error('Priority must be a positive integer')
            return
        }

        if(valueType.value !== 'equation' && (!resultValue || resultValue < 0)) {
            toast.error('Value must be a positive number')
            return
        }

        if(valueType.value === 'equation' && (!equationString || equationString.trim() === '')) {
            toast.error('Equation string is required for equation value type')
            return
        }

        try {
            const humanReadable = JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)
            const jsonLogic = QbUtils.jsonLogicFormat(queryTree, config)

            if(jsonLogic['errors'].length) {
                toast.error('Error encountered forming JSON logic')
                return
            }

            const data = {
                action: action,
                ratesheet_id: ratesheetId,
                json_logic: JSON.stringify(jsonLogic['logic']),
                human_readable: humanReadable,
                name: name.trim(),
                priority: Number(priority),
                type: type.value,
                value: valueType.value === 'equation' ? 0 : Number(resultValue),
                value_type: valueType.value
            }

            // Only include equation string if value type is equation
            if(valueType.value === 'equation') {
                data.original_equation_string = equationString
            }

            setIsSubmitting(true)

            api.post(`/ratesheets/conditional${conditional_id ? `/${conditional_id}` : ''}`, data).then(response => {
                toast.success(`Successfully stored conditional "${name}"`)
                props.reload()
                hideModal()
            }).catch(error => {
                toast.error(error.response?.data?.message || 'Failed to save conditional')
            }).finally(() => {
                setIsSubmitting(false)
            })
        } catch (e) {
            toast.error(e.message || 'An error occurred')
            setIsSubmitting(false)
        }
    }

    return (
        <Modal show={props.show} onHide={hideModal} size='xl' dialogClassName='modal-90w'>
            {/* <Row>
                <Col md={12}> */}
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
                                {valueType.value !== 'equation' &&
                                    <Col md={3}>
                                        <InputGroup style={{paddingTop: '0px'}}>
                                            {valueType.value !== 'percent' && <InputGroup.Text>$</InputGroup.Text>}
                                            <FormControl
                                                type='number'
                                                step='0.01'
                                                min='0'
                                                onChange={event => setResultValue(event.target.value)}
                                                value={resultValue}
                                            />
                                            {valueType.value === 'percent' && <InputGroup.Text>%</InputGroup.Text>}
                                        </InputGroup>
                                    </Col>
                                }
                            </Row>
                        </Card.Body>
                            {valueType.value === 'equation' &&
                                <Card.Body>
                                    <hr/>
                                    <Row className='bottom15'>
                                        <Col md={2}>
                                            <h4 className='text-muted'>Equation</h4>
                                        </Col>
                                        <Col md={10}>
                                            <Row>
                                                <Col md={12} style={{paddingBottom: 15}}>
                                                    <HighlightedEquationInput
                                                        onChange={event => setEquationString(event.target.value)}
                                                        value={equationString}
                                                        placeholder="Example: max(total_weight kg, 500 kg) * 2.5 CAD + 15 CAD"
                                                    />
                                                    {validationMessage && (
                                                        <small style={{color: validationMessage.startsWith('✓') ? '#28a745' : validationMessage.startsWith('❌') ? '#dc3545' : '#ffc107', display: 'block', marginTop: '5px'}}>
                                                            {validationMessage}
                                                        </small>
                                                    )}
                                                    <small style={{color: '#6c757d', display: 'block', marginTop: '5px'}}>
                                                        Operators: + - * / ^ ( )
                                                    </small>
                                                    <small style={{color: '#6c757d', display: 'block', marginTop: '5px'}}>
                                                        Functions: max() min() round() abs() sqrt() pow()
                                                    </small>
                                                    <small style={{color: '#6c757d', display: 'block', marginTop: '5px'}}>
                                                        Variables: total_weight, longest_side
                                                    </small>
                                                    <small style={{color: '#6c757d', display: 'block', marginTop: '5px'}}>
                                                        Units: kg, lb, m, cm, CAD, $
                                                    </small>
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
                                            {testVariables.filter(variable => equationString.includes(variable.dbName)).length > 0 ? (
                                                <>
                                                    <Table variant='striped' size='sm'>
                                                        <thead>
                                                            <tr>
                                                                <th>Variable</th>
                                                                <th>Test Value</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {testVariables.filter(variable => equationString.includes(variable.dbName)).map(variable => (
                                                                <tr key={variable.dbName}>
                                                                    <td><strong>{variable.name}</strong></td>
                                                                    <td>
                                                                        <InputGroup>
                                                                            <FormControl
                                                                                type='number'
                                                                                onChange={event => handleVariableValueChange(event, variable.dbName)}
                                                                                value={variable.value}
                                                                            />
                                                                            <InputGroup.Text>{variable.unit}</InputGroup.Text>
                                                                        </InputGroup>
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </Table>
                                                    {demoResult && (
                                                        <div style={{marginTop: '15px', padding: '10px', backgroundColor: '#f8f9fa', borderRadius: '4px'}}>
                                                            <strong>Calculated Result: </strong>
                                                            <span style={{fontSize: '1.2em', color: '#28a745'}}>{demoResult}</span>
                                                        </div>
                                                    )}
                                                </>
                                            ) : (
                                                <p className='text-muted'>Enter variables in your equation to test them</p>
                                            )}
                                        </Col>
                                    </Row>
                                </Card.Body>
                            }
                        <Card.Footer style={{textAlign: 'center'}}>
                            <Button
                                onClick={storeConditional}
                                variant='success'
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Submitting...' : 'Submit'}
                            </Button>
                        </Card.Footer>
                    </Card>
                {/* </Col>
            </Row> */}
        </Modal>
    )
}

export default ConditionalModal;
