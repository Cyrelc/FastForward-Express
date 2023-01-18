import React, {useEffect, useState} from 'react'
import {Button, Card, Col, InputGroup, FormControl, Modal, Row} from 'react-bootstrap'
import {Query, Builder, Fields, Utils as QbUtils} from '@react-awesome-query-builder/ui'

import {BootstrapConfig} from '@react-awesome-query-builder/bootstrap'
import Select from 'react-select'

const renderBuilder = (props) => (
    <div className='query-builder-container' style={{padding: '10px'}}>
        <div className='query-builder'>
            <Builder {...props} />
        </div>
    </div>
)

const config = {
    ...BootstrapConfig,
    fields: {
        delivery_address: {
            type: '!struct',
            label: 'Delivery Address',
            subfields: {
                zone_type: {
                    label: 'Zone Type',
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
        pickup_address: {
            type: '!struct',
            label: 'Pickup Address',
            subfields: {
                zone_type: {
                    label: 'Zone Type',
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
        package: {
            type: '!struct',
            label: 'Package',
            subfields: {
                is_pallet: {
                    label: 'Is Pallet',
                    type: 'boolean',
                }
            }
        }
    }
}

const ConditionalModal = props => {
    const [queryTree, setQueryTree] = useState(QbUtils.checkTree(QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}), config))
    const [name, setName] = useState('')
    const [resultAction, setResultAction] = useState({value: 'charge', label: 'Charge'})
    const [valueType, setValueType] = useState({value: 'amount', label: 'Amount'})
    const [resultValue, setResultValue] = useState()
    const [isLoading, setIsLoading] = useState(true)

    const {ratesheetId} = props
    const {conditional_id} = props.conditional

    useEffect(() => {
        setQueryTree(QbUtils.checkTree(props.conditional ? QbUtils.loadFromJsonLogic(JSON.parse(props.conditional.json_logic), config) : QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}), config))
        setName(props.conditional?.name)
        setResultAction(props.conditional?.action ?? {value: 'charge', label: 'charge'})
        setValueType(props.conditional?.value_type ?? {value: 'amount', label: 'Amount'})
        setResultValue(props.conditional?.value ?? 0)
    }, [props.conditional])

    const storeConditional = () => {
        try {
            const humanReadable = JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)
            const jsonLogic = QbUtils.jsonLogicFormat(queryTree, config)

            if(jsonLogic['errors'].length)
                throw 'Error encountered forming JSON logic'

            const data = {
                action: resultAction,
                ratesheet_id: ratesheetId,
                json_logic: jsonLogic['logic'],
                human_readable: humanReadable,
                name,
                value: resultValue,
                value_type: valueType
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
                        <text>{JSON.stringify(QbUtils.queryString(queryTree, config, true), undefined, 2)}</text>
                    </Row>
                    <hr/>
                    <Row>
                        <Col md={2}>
                            <h2 className='text-muted'>Then</h2>
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
                                options={[
                                    {value: 'percent', label: 'Percent'},
                                    {value: 'amount', label: 'Amount'}
                                ]}
                                value={valueType}
                                onChange={setValueType}
                            />
                        </Col>
                        <Col md={3}>
                            <InputGroup style={{paddingTop: '0px'}}>
                                {valueType.value == 'amount' && <InputGroup.Text>$</InputGroup.Text>}
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
                    </Row>
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
        </Modal>
    )
}

export default ConditionalModal;
