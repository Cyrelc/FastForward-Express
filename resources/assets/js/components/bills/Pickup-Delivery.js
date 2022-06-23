import React from 'react'
import {Card, InputGroup, Row, Col, ToggleButtonGroup, ToggleButton, FormControl, OverlayTrigger, Tooltip} from 'react-bootstrap'
import Select from 'react-select'
import DatePicker from 'react-datepicker'
import {DateTime} from 'luxon'

import Address from '../partials/AddressFunctional'

export default function Pickup_Delivery(props) {
    const filterDates = date => {
        const dateTime = DateTime.fromJSDate(date)
        if(dateTime.hasSame(DateTime.local(), "days"))
            return true
        if(dateTime.diffNow("days") < 0)
            return false
        const day = date.getDay()
        return day !== 0 && day !== 6
    }

    const {
        accounts,
        addressTypes,
        applyRestrictions,
        dateTimeReadOnly,
        handleTimeChange,
        handleValueChange,
        readOnly,
        timeTooltip
    } = props

    const {
        account,
        addressType,
        dateMin,
        referenceValue,
        timeScheduled,
        timeMax,
        timeMin
    } = props.data

    return (
        <Card>
            <Card.Header>
                <Row className='justify-content-md-center'>
                    {props.header}
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
                                onChange={handleTimeChange}
                                showMonthDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={timeScheduled}
                                readOnly={dateTimeReadOnly || readOnly}
                                className='form-control'
                                //Rules for non-admins only
                                filterDate={applyRestrictions && filterDates}
                                filterTime={applyRestrictions && props.timeFilter}
                                wrapperClassName='form-control'
                            />
                            {timeTooltip &&
                                <OverlayTrigger placement='right' overlay={<Tooltip>{timeTooltip}</Tooltip>}>
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
                                value={addressType}
                                name={`${props.id}-addressType`}
                                onChange={value => handleValueChange({target: {name: 'addressType', value}})}
                                disabled={readOnly}
                            >
                            {addressTypes.map(type =>
                                <ToggleButton
                                    id={props.id + '.address.type.' + type}
                                    value={type}
                                    key={type}
                                    variant='outline-secondary'
                                    disabled={readOnly}
                                    size='sm'
                                >{type}</ToggleButton>
                            )}
                            </ToggleButtonGroup>
                        </InputGroup>
                    </Col>
                </Row>
                {addressType === 'Account' &&
                    <Row>
                        <Col md={12}>
                            <InputGroup>
                                <InputGroup.Text>Select Account: </InputGroup.Text>
                                <Select
                                    options={accounts}
                                    isSearchable
                                    value={account}
                                    onChange={props.handleAccountChange}
                                    isDisabled={readOnly}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                }
                {(addressType === 'Account' && account?.custom_field) &&
                    <Row>
                        <Col md={12}>
                            <InputGroup>
                                <InputGroup.Text>{props.data.account.custom_field}</InputGroup.Text>
                                <FormControl
                                    name={`${props.id}ReferenceValue`}
                                    value={referenceValue}
                                    onChange={event => props.handleReferenceValueChange(account, referenceValue, event.target.value)}
                                    readOnly={readOnly}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                }
            </Card.Body>
            <Card.Footer>
                <Address 
                    id={props.id}
                    address={{
                        formatted: props.data.addressFormatted,
                        lat: props.data.addressLat,
                        lng: props.data.addressLng,
                        name: props.data.addressName,
                        placeId: props.data.placeId,
                        type: props.data.addressType
                    }}
                    handleChange={handleValueChange}
                    accounts={accounts}
                    accountId={account?.account_id}
                    readOnly={readOnly}
                    showAddressSearch={true}

                    admin={props.admin}
                />
            </Card.Footer>
        </Card>
    )
}
