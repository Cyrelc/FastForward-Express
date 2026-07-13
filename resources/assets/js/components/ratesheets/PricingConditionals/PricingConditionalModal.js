import React, {Fragment, useCallback, useMemo, useState} from 'react'
import {Button, Card, Col, InputGroup, FormCheck, FormControl, Modal, Row, Table} from 'react-bootstrap'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/mui'
import Select from 'react-select'
import {toast} from 'react-toastify'

import {useAPI} from '../../../contexts/APIContext'
import {
    buildPricingConditionalConfig,
    availableTestVariables,
    valueTypes,
    actions,
    actionDescriptions,
    valueTypeDescriptions,
    fieldValueOptions,
    nameVariables,
    REFUSE_ACTION,
} from './pricingConditionalConfig'
import {EQUATION_FUNCTIONS, EQUATION_FUNCTION_DESCRIPTIONS} from './useEquation'
import {
    useTokenAutocomplete,
    SuggestionList,
    matchIdentifier,
    matchPlaceholder,
    spliceIdentifier,
    splicePlaceholder,
} from '../TokenAutocomplete'
import usePricingConditional from './usePricingConditional'
import {useLists} from '../../../contexts/ListsContext'
import {extractZoneIdsFromLogic} from '../conditionalFieldShared'

const renderBuilder = (props) => (
    <div className='query-builder-container' style={{padding: '10px'}}>
        <div className='query-builder'>
            <Builder {...props} />
        </div>
    </div>
)

/**
 * Equation input with live syntax highlighting.
 *
 * Units used to be highlighted as valid (kg, lbs, CAD...). They are not: the server reads
 * "total_weight_lbs lbs" as one token and refuses to price. Colouring them blue told the author a
 * broken equation was fine, which is precisely how the three unusable weight rules got written. An
 * unrecognised word is now red, whatever it is.
 */
const HighlightedEquationInput = ({value, onChange, placeholder, inputRef, onKeyDown, onKeyUp, onClick, onBlur}) => {
    const highlightSyntax = (text) => {
        if (!text) return null

        const tokens = []
        const regex = /(\d+\.?\d*)|([a-zA-Z_][a-zA-Z0-9_]*)|([+\-*/^()$])|(\s+)/g
        let match
        let currentPos = 0

        while ((match = regex.exec(text)) !== null) {
            const [fullMatch, number, word, operator, space] = match
            const isVariable = availableTestVariables.some(v => v.dbName === word)
            const isFunction = EQUATION_FUNCTIONS.includes(word?.toLowerCase())

            let style = {}

            if (number) {
                style = {color: '#0066cc', fontWeight: '500'}
            } else if (isVariable) {
                style = {color: '#22863a', fontWeight: '600', backgroundColor: '#e6f7ff', padding: '0 2px', borderRadius: '2px'}
            } else if (isFunction) {
                style = {color: '#6f42c1', fontWeight: '600'}
            } else if (operator) {
                style = {color: '#d73a49', fontWeight: '600'}
            } else if (word && !space) {
                // Unrecognised word - including a unit suffix, which is the whole point.
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
                ref={inputRef}
                onChange={onChange}
                onKeyDown={onKeyDown}
                onKeyUp={onKeyUp}
                onClick={onClick}
                onBlur={onBlur}
                autoComplete='off'
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

const PricingConditionalModal = props => {
    const {conditional: {conditional_id} = null, ratesheetId, deliveryTypes, mapZones, zoneGroups} = props

    const extraZoneIds = useMemo(() => {
        if(!props.conditional?.json_logic)
            return []
        try {
            const logic = typeof props.conditional.json_logic === 'string'
                ? JSON.parse(props.conditional.json_logic)
                : props.conditional.json_logic
            return extractZoneIdsFromLogic(logic)
        } catch (error) {
            return []
        }
    }, [props.conditional?.json_logic])

    const config = useMemo(() => {
        return buildPricingConditionalConfig({
            mapZones, deliveryTypes, extraZoneIds, zoneGroups,
            conditionals: props.conditionals, conditionalId: conditional_id,
        })
    }, [mapZones, deliveryTypes, extraZoneIds, zoneGroups, props.conditionals, conditional_id])

    const {
        action,
        continueOn,
        demoResult,
        equationString,
        name,
        priority,
        queryTree,
        reset,
        isDirty,
        isRefusal,
        markSaved,
        resultValue,
        serverEquationString,
        setAction,
        setContinueOn,
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
    } = usePricingConditional({conditional: props.conditional, config, nextPriority: props.nextPriority})

    const selectedFieldValueOption = useMemo(() => {
        return fieldValueOptions.find(option => option.value === resultValue) ?? null
    }, [resultValue])

    // Type-ahead for the name's {{...}} placeholders. Triggers on "{{", so it is out of the way until
    // it is wanted.
    const nameAutocomplete = useTokenAutocomplete({
        value: name,
        onChange: setName,
        matchToken: matchPlaceholder,
        buildSuggestions: useCallback(query => nameVariables
            .filter(variable => variable.token.toLowerCase().includes(query.toLowerCase()))
            .map(variable => ({
                insert: variable.token,
                label: variable.token,
                description: `${variable.label} — e.g. "${variable.example}"`,
            })), []),
        applyToken: splicePlaceholder,
    })

    // Type-ahead for the equation. Suggests the variables and the functions, and only ever replaces the
    // identifier under the caret - it cannot rewrite a working equation into a broken one.
    const equationAutocomplete = useTokenAutocomplete({
        value: equationString,
        onChange: setEquationString,
        matchToken: matchIdentifier,
        buildSuggestions: useCallback(query => {
            const lowered = query.toLowerCase()

            const variables = availableTestVariables
                .filter(variable => variable.dbName.toLowerCase().includes(lowered))
                .map(variable => ({
                    insert: variable.dbName,
                    label: variable.dbName,
                    kind: 'variable',
                    description: `${variable.description} (${variable.unit})`,
                }))

            const functions = EQUATION_FUNCTIONS
                .filter(equationFunction => equationFunction.toLowerCase().includes(lowered))
                .map(equationFunction => ({
                    // Insert the opening bracket too and park the caret inside it: nobody wants to type
                    // "floor" and then go back for the "(".
                    insert: `${equationFunction}(`,
                    caretOffset: equationFunction.length + 1,
                    label: `${equationFunction}()`,
                    kind: 'function',
                    description: EQUATION_FUNCTION_DESCRIPTIONS[equationFunction],
                }))

            return [...variables, ...functions]
        }, []),
        applyToken: spliceIdentifier,
    })

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

    // Rules save one at a time, so clicking away from an edited rule would otherwise bin the edit
    // without a word.
    const requestClose = () => {
        if(isDirty && !confirm('This rule has unsaved changes. Close anyway and lose them?'))
            return
        hideModal()
    }

    const storeConditional = () => {
        // Validation
        if(!name || name.trim() === '') {
            toast.error('Name is required')
            return
        }

        // A refusal produces no charge, so it has no charge type to categorise.
        if(!isRefusal && (!type || !type.value)) {
            toast.error('Pricing conditional type is required')
            return
        }

        // 0 is allowed: it is the default for a refusal, which runs ahead of every pricing rule.
        if(priority === '' || priority === null || priority < 0 || !Number.isInteger(Number(priority))) {
            toast.error('Priority must be 0 or a positive integer')
            return
        }

        if(isRefusal) {
            if(!resultValue || `${resultValue}`.trim() === '') {
                toast.error('A refusal needs a message to show staff')
                return
            }
        } else if(valueType.value === 'field') {
            if(!resultValue || `${resultValue}`.trim() === '') {
                toast.error('A field is required for the field value type')
                return
            }
        } else if(valueType.value === 'equation') {
            if(!equationString || equationString.trim() === '') {
                toast.error('Equation string is required for equation value type')
                return
            }
        } else if(!resultValue || resultValue < 0) {
            toast.error('Value must be a positive number')
            return
        }

        try {
            const humanReadable = JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)
            const jsonLogic = QbUtils.jsonLogicFormat(queryTree, config)

            if(jsonLogic['errors'].length) {
                toast.error('Error encountered forming JSON logic')
                return
            }

            let value
            if(valueType.value === 'equation')
                value = 0
            else if(isRefusal)
                value = `${resultValue}`.trim() // the message staff are shown when the trip is refused
            else if(valueType.value === 'field')
                value = resultValue // a context field path, e.g. pickup_zone_selected_type_cost
            else
                value = Number(resultValue)

            const data = {
                action: action,
                continue_on: continueOn,
                ratesheet_id: ratesheetId,
                json_logic: JSON.stringify(jsonLogic['logic']),
                human_readable: humanReadable,
                name: name.trim(),
                priority: Number(priority),
                type: isRefusal ? null : type.value,
                value: value,
                value_type: valueType.value
            }

            // Only include equation string if value type is equation
            if(valueType.value === 'equation') {
                data.original_equation_string = equationString
            }

            setIsSubmitting(true)

            api.post(`/ratesheets/conditional${conditional_id ? `/${conditional_id}` : ''}`, data).then(response => {
                toast.success(`Successfully stored conditional "${name}"`)
                markSaved()
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
        <Modal show={props.show} onHide={requestClose} size='xl' dialogClassName='modal-90w'>
            {/* <Row>
                <Col md={12}> */}
                    <Card>
                        <Card.Header>
                            <Row>
                                <Col md={4}>
                                    <h4 className='text-muted'>
                                        {`${conditional_id ? 'Edit' : 'Create'} Pricing Conditional ${conditional_id ?? ''}`}
                                    </h4>
                                </Col>
                                <Col md={8}>
                                    <div style={{position: 'relative'}}>
                                        <InputGroup>
                                            <InputGroup.Text>Name: </InputGroup.Text>
                                            <FormControl
                                                name='name'
                                                ref={nameAutocomplete.inputRef}
                                                value={name}
                                                onChange={nameAutocomplete.handleChange}
                                                onKeyDown={nameAutocomplete.handleKeyDown}
                                                onKeyUp={nameAutocomplete.handleCaretMove}
                                                onClick={nameAutocomplete.handleCaretMove}
                                                onBlur={nameAutocomplete.close}
                                                autoComplete='off'
                                            />
                                        </InputGroup>
                                        <SuggestionList
                                            open={nameAutocomplete.open}
                                            items={nameAutocomplete.items}
                                            activeIndex={nameAutocomplete.activeIndex}
                                            onPick={nameAutocomplete.accept}
                                            onHover={nameAutocomplete.setActiveIndex}
                                        />
                                    </div>
                                    <small className='text-muted'>
                                        Type <code>{'{{'}</code> to put a value from the delivery into the
                                        name &mdash; e.g. <code>{'{{zones_crossed}} zones crossed'}</code> prints
                                        as &ldquo;4 zones crossed&rdquo; on the invoice.
                                    </small>
                                </Col>
                                <Col md={6}>
                                    <InputGroup style={{display: 'flex', width: '100%'}}>
                                        <InputGroup.Text>Type:</InputGroup.Text>
                                        <Select
                                            options={chargeTypes}
                                            value={type}
                                            onChange={setType}
                                            isDisabled={isRefusal}
                                            placeholder={isRefusal ? 'Not used — this rule makes no charge' : 'Select...'}
                                            styles={{
                                                container: (baseStyles) => ({
                                                    ...baseStyles,
                                                    flexGrow: 1
                                                })
                                            }}
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
                                <Col md={2}>
                                    <Select
                                        options={actions}
                                        value={action}
                                        onChange={setAction}
                                    />
                                </Col>
                                {!isRefusal &&
                                    <Col md={2}>
                                        <Select
                                            options={valueTypes.filter(option => option.value !== 'message')}
                                            value={valueType}
                                            onChange={setValueType}
                                        />
                                    </Col>
                                }
                                {/* Explains only what is currently selected. A permanent table of every
                                    action and every value type would say the same things, but would say
                                    them all the time - and be ignored all the time. */}
                                <Col md={12} className='mt-2'>
                                    {actionDescriptions[action?.value] &&
                                        <small className='text-muted d-block'>
                                            <strong>{action.label}:</strong> {actionDescriptions[action.value]}
                                        </small>
                                    }
                                    {!isRefusal && valueTypeDescriptions[valueType?.value] &&
                                        <small className='text-muted d-block'>
                                            <strong>{valueType.label}:</strong> {valueTypeDescriptions[valueType.value]}
                                        </small>
                                    }
                                </Col>
                                {/* A refusal produces no charge - it stops the quote and shows this to
                                    staff, exactly as an address outside every zone does. It is checked
                                    before any pricing rule, whatever its priority. */}
                                {isRefusal &&
                                    <Col md={6}>
                                        <InputGroup>
                                            <InputGroup.Text>Message</InputGroup.Text>
                                            <FormControl
                                                type='text'
                                                placeholder='e.g. Exceeds trailer capacity — please call us'
                                                value={resultValue}
                                                onChange={event => setResultValue(event.target.value)}
                                            />
                                        </InputGroup>
                                    </Col>
                                }
                                {!isRefusal && valueType.value === 'field' &&
                                    <Col md={4}>
                                        <Select
                                            options={fieldValueOptions}
                                            value={selectedFieldValueOption}
                                            onChange={option => setResultValue(option ? option.value : '')}
                                            placeholder='Select a field to pull the value from...'
                                        />
                                    </Col>
                                }
                                {!isRefusal && (valueType.value === 'amount' || valueType.value === 'percent') &&
                                    <Col md={4}>
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
                                <Col md={2}>
                                    <FormCheck
                                        type='switch'
                                        label='Continue'
                                        title='When on, later rules keep being evaluated after this one matches. Turn off to stop at this rule.'
                                        checked={continueOn}
                                        onChange={event => setContinueOn(event.target.checked)}
                                    />
                                </Col>
                            </Row>
                            {valueType.value === 'field' && selectedFieldValueOption &&
                                <Row>
                                    <Col md={2}></Col>
                                    <Col md={10}>
                                        <small className='text-muted'>{selectedFieldValueOption.description}</small>
                                    </Col>
                                </Row>
                            }
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
                                                    <div style={{position: 'relative'}}>
                                                        <HighlightedEquationInput
                                                            inputRef={equationAutocomplete.inputRef}
                                                            onChange={equationAutocomplete.handleChange}
                                                            onKeyDown={equationAutocomplete.handleKeyDown}
                                                            onKeyUp={equationAutocomplete.handleCaretMove}
                                                            onClick={equationAutocomplete.handleCaretMove}
                                                            onBlur={equationAutocomplete.close}
                                                            value={equationString}
                                                            placeholder="Example: floor(max(0, total_weight_lbs - 100) / 100) * 2 + 16"
                                                        />
                                                        <SuggestionList
                                                            open={equationAutocomplete.open}
                                                            items={equationAutocomplete.items}
                                                            activeIndex={equationAutocomplete.activeIndex}
                                                            onPick={equationAutocomplete.accept}
                                                            onHover={equationAutocomplete.setActiveIndex}
                                                        />
                                                    </div>
                                                    {validationMessage && (
                                                        <small style={{color: validationMessage.startsWith('✓') ? '#28a745' : validationMessage.startsWith('❌') ? '#dc3545' : '#ffc107', display: 'block', marginTop: '5px'}}>
                                                            {validationMessage}
                                                        </small>
                                                    )}
                                                    {/* The four reference lines that used to live here are gone: the type-ahead is
                                                        the reference now, and it is in the author's way at exactly the moment it
                                                        helps rather than taking up the modal permanently. */}
                                                    <small className='text-muted' style={{display: 'block', marginTop: '5px'}}>
                                                        Start typing to autocomplete variables and functions. Operators: + - * / ^ ( ).
                                                        Units are never written in the equation &mdash; they are part of the variable
                                                        name (<code>total_weight_kgs</code> vs <code>total_weight_lbs</code>).
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

export default PricingConditionalModal;
