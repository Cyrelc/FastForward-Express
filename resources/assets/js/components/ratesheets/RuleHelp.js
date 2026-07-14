import React, {useState} from 'react'
import {Col, Collapse, Row} from 'react-bootstrap'

/**
 * The in-modal help for a rule's "Then" row, shared by the pricing and timing editors.
 *
 * Two deliberate choices:
 *
 * It explains only what is CURRENTLY SELECTED. A permanent table of every action and every value type
 * says the same things, but says them all the time - so it is read once and ignored forever, while
 * eating the modal height that the author actually needs for the query builder.
 *
 * The condition-field reference is collapsed. It is genuinely useful ("what does 'Share a Zone Group'
 * mean?") and genuinely long, and the answer to that tension is a disclosure triangle, not a decision
 * about whether the author deserves the information.
 */
export default function RuleHelp({action, actionDescriptions, valueType, valueTypeDescriptions, selectedValueOption, conditionFields}) {
    const [showFields, setShowFields] = useState(false)

    return (
        <Row>
            <Col md={2}></Col>
            <Col md={10}>
                {actionDescriptions?.[action?.value] &&
                    <small className='text-muted d-block'>
                        <strong>{action.label}:</strong> {actionDescriptions[action.value]}
                    </small>
                }
                {valueTypeDescriptions?.[valueType?.value] &&
                    <small className='text-muted d-block'>
                        <strong>{valueType.label}:</strong> {valueTypeDescriptions[valueType.value]}
                    </small>
                }
                {selectedValueOption &&
                    <small className='text-muted d-block'>
                        <strong>{selectedValueOption.label}:</strong> {selectedValueOption.description}
                    </small>
                }

                {!!conditionFields?.length &&
                    <div className='mt-1'>
                        <small>
                            <a
                                href='#'
                                className='text-muted'
                                onClick={event => {
                                    event.preventDefault()
                                    setShowFields(!showFields)
                                }}
                            >
                                <i className={`fas fa-caret-${showFields ? 'down' : 'right'} me-1`}></i>
                                What can I test against? ({conditionFields.length} fields)
                            </a>
                        </small>
                        <Collapse in={showFields}>
                            <div>
                                <ul className='text-muted mt-1' style={{maxHeight: 200, overflowY: 'auto', fontSize: '0.8rem'}}>
                                    {conditionFields.map(field =>
                                        <li key={field.path}>
                                            <strong>{field.label}</strong>: {field.description}
                                        </li>
                                    )}
                                </ul>
                            </div>
                        </Collapse>
                    </div>
                }
            </Col>
        </Row>
    )
}
