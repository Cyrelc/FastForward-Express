import React from 'react'
import {Card, InputGroup, Row, Col, ToggleButtonGroup, ToggleButton, FormControl, OverlayTrigger, Tooltip, Button} from 'react-bootstrap'
import Select from 'react-select'
import DatePicker from 'react-datepicker'

import Address from '../partials/Address'

export default function Pickup_Delivery(props) {
    const isWeekday = date => {
        const day = date.getDay()
        return day !== 0 && day !== 6
    }

    return (
        <Card>
            <Card.Header>
                <Row className='justify-content-md-center'>
                    <h4>{props.friendlyName}</h4>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row className='justify-content-md-center'>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Time: </InputGroup.Text>
                            <DatePicker 
                                showTimeSelect
                                timeIntervals={15}
                                dateFormat='MMMM d, yyyy h:mm aa'
                                onChange={value => props.handleChanges({target: {name: props.id + 'TimeExpected', value: value}})}
                                showMonthDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={props.data.timeExpected}
                                readOnly={props.dateTimeReadOnly || props.readOnly}
                                className='form-control'
                                //Rules for non-admins only
                                filterDate={props.applyRestrictions && isWeekday}
                                minDate={props.applyRestrictions && props.data.timeMin}
                                minTime={props.applyRestrictions && props.data.timeMin}
                                maxTime={props.applyRestrictions && props.data.timeMax}
                            />
                            {props.timeTooltip && 
                                <OverlayTrigger placement='right' overlay={<Tooltip>{props.timeTooltip}</Tooltip>}>
                                    <InputGroup.Text><i className='fas fa-info-circle'></i></InputGroup.Text>
                                </OverlayTrigger>
                            }
                        </InputGroup>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Address Type: </InputGroup.Text>
                            <ToggleButtonGroup
                                type='radio'
                                value={props.data.address.type}
                                name={props.id + '.address.type'}
                                disabled={props.readOnly}
                                onChange={value => props.handleChanges({target: {name: props.id + 'AddressType', type: 'text', value: value}})}
                            >
                            {props.addressTypes.map(type =>
                                <ToggleButton
                                    id={props.id + '.address.type.' + type}
                                    value={type}
                                    key={type}
                                    variant='outline-secondary'
                                    disabled={props.readOnly}
                                >{type}</ToggleButton>
                            )}
                            </ToggleButtonGroup>
                        </InputGroup>
                    </Col>
                </Row>
                {props.data.address.type === 'Account' &&
                    <Row>
                        <Col md={11}>
                            <InputGroup>
                                <InputGroup.Text>Select Account: </InputGroup.Text>
                                <Select
                                    options={props.accounts}
                                    isSearchable
                                    value={props.data.account}
                                    onChange={account => props.handleChanges({target: {name: props.id + 'Account', type: 'number', value: account}})}
                                    isDisabled={props.readOnly}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                }
                {(props.data.address.type === 'Account' && props.data.account !== '' && props.data.account.custom_field) &&
                    <Row>
                        <Col md={11}>
                            <InputGroup>
                                <InputGroup.Text>{props.data.account.custom_field}</InputGroup.Text>
                                <FormControl
                                    name={props.id + 'ReferenceValue'}
                                    value={props.data.referenceValue}
                                    onChange={props.handleChanges}
                                    readOnly={props.readOnly}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                }
            </Card.Body>
            <Card.Footer>
                <Address 
                    id={props.id}
                    address={props.data.address}
                    handleChanges={props.handleChanges}
                    accounts={props.accounts}
                    accountId={props.data.accountId}
                    readOnly={props.readOnly}
                    showAddressSearch={true}

                    admin={props.admin}
                />
            </Card.Footer>
        </Card>
    )
}
