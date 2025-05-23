import React, {Fragment, useEffect} from 'react'
import {Button, Card, Col, FormControl, FormCheck, InputGroup, OverlayTrigger, Row, Table, Tooltip} from 'react-bootstrap'
import Select from 'react-select'
import {DateTime} from 'luxon'
import DatePicker from 'react-datepicker'

import Address from '../partials/Address'
import {useAPI} from '../../contexts/APIContext'

const filterDates = date => {
    const dateTime = DateTime.fromJSDate(date)
    if(dateTime.hasSame(DateTime.local(), "days"))
        return true
    if(dateTime.diffNow("days").days < 0)
        return false
    const day = date.getDay()
    return day !== 0 && day !== 6
}

export default function BasicTab(props) {
    const api = useAPI()

    const {
        accounts,
        addressTypes,
        applyRestrictions,
        billId,
        businessHoursMax,
        businessHoursMin,
        delivery,
        deliveryType,
        deliveryTypes,
        description,
        permissions,
        pickup,
        readOnly,
    } = props.billState

    const {
        packageArray,
        packageIsMinimum,
        packageIsPallet,
        requireProofOfDelivery,
        setPackageIsMinimum,
        setPackageIsPallet,
        setRequireProofOfDelivery,
        setUseImperial,
        useImperial,
    } = props.packages

    const {chargeAccount, chargeReferenceValue, chargeType, chargeTypes, isInvoiced} = props.chargeState

    const totalWeight = packageArray.reduce((acc, parcel) => {
        if(parcel.totalWeight && parcel.totalWeight != NaN)
            return acc + parseFloat(parcel.totalWeight)
        console.log(parcel.totalWeight)
        return acc
    }, 0)
    const totalCubedWeight = packageArray.reduce((acc, parcel) => {
        if(parcel.cubedWeight && parcel.cubedWeight != NaN && parcel.cubedWeight != "")
            return acc + parseFloat(parcel.cubedWeight)
        console.log(parcel.cubedWeight)
        return acc
    }, 0)
    console.log(totalWeight, totalCubedWeight)

    const pickupTimeFilter = time => {
        const dateTime = DateTime.fromJSDate(time)
        // If requested time is in the past
        if(dateTime.diffNow('minutes').minutes < 0)
            return false
        // If requested time is not a weekend (6 = Saturday, 7 = Sunday) See moment.github.io/luxon/api-docs/index.html#datetimeweekday for more details
        if(dateTime.weekday === 6 || dateTime.weekday === 7)
            return false
        // If requested time is AFTER business hours - (modified for shortest available delivery window)
        const minimumTimeToDoADelivery = deliveryTypes.reduce((minimum, type) => type.time < minimum ? type.time : minimum, deliveryTypes[0].time)
        const adjustedBusinessHoursMax = businessHoursMax.minus({hours: minimumTimeToDoADelivery})
        const lastPickupTime = DateTime.fromJSDate(time).set({hour: adjustedBusinessHoursMax.hour, minute: adjustedBusinessHoursMax.minute})
        if(dateTime.diff(lastPickupTime, 'minutes').minutes > 0)
            return false
        // If requested time is BEFORE business hours - (no modification required)
        const earliestValidPickupTime = DateTime.fromJSDate(time).set({hour: businessHoursMin.hour, minute: businessHoursMin.minute})
        if(dateTime.diff(earliestValidPickupTime, 'minutes').minutes < 0)
            return false

        return true
    }

    const deliveryTimeFilter = time => {
        const dateTime = DateTime.fromJSDate(time)
        // If requested time is in the past
        if(dateTime.diffNow('minutes').minutes < 0)
            return false
        // If requested time is a weekend (6 = Saturday, 7 = Sunday) See moment.github.io/luxon/api-docs/index.html#datetimeweekday for more details
        if(dateTime.weekday === 6 || dateTime.weekday === 7)
            return false
        // If requested time is AFTER business hours - (no modification required)
        const lastDeliveryTime = DateTime.fromJSDate(time).set({hour: businessHoursMax.hour, minute: businessHoursMax.minute})
        if(dateTime.diff(lastDeliveryTime, 'minutes').minutes > 0)
            return false
        // If requested time is BEFORE time_pickup_expected + minimum delivery time (as defined by deliveryTypes)
        const minimumTimeToDoADelivery = deliveryTypes.reduce((minimum, type) => type.time < minimum ? type.time : minimum, deliveryTypes[0].time)
        const earliestValidDeliveryTime = DateTime.fromJSDate(pickup.timeScheduled).plus({hours: minimumTimeToDoADelivery})
        if(dateTime.diff(earliestValidDeliveryTime, 'minutes').minutes <= 1)
            return false

        return true
    }

    const handleReferenceValueChange = (account, prevValue, value) => {
        const payload = {account, prevValue, value}
        props.billDispatch({type: 'CHECK_REFERENCE_VALUES', payload})
        props.chargeDispatch({type: 'CHECK_REFERENCE_VALUES', payload})
    }

    useEffect(() => {
        if(pickup.addressLat && pickup.addressLng && props.chargeState.activeRatesheet) {
            api.get(`/ratesheets/${props.chargeState.activeRatesheet.ratesheet_id}/getZone?lat=${pickup.addressLat}&lng=${pickup.addressLng}`)
                .then(response => {
                    props.billDispatch({type: 'SET_PICKUP_ZONE', payload: response})
                    if(!billId && applyRestrictions)
                        props.setPickupTimeExpected(new Date())
                })
        }
    }, [pickup.addressLat, pickup.addressLng])

    useEffect(() => {
        if(delivery.addressLat && delivery.addressLng && props.chargeState.activeRatesheet)
            api.get(`/ratesheets/${props.chargeState.activeRatesheet.ratesheet_id}/getZone?lat=${delivery.addressLat}&lng=${delivery.addressLng}`)
                .then(response => {
                    props.billDispatch({type: 'SET_DELIVERY_ZONE', payload: response})
                    if(!billId && applyRestrictions)
                        props.setPickupTimeExpected(new Date())
                })
    }, [delivery.addressLat, delivery.addressLng])

    return (
        <Card border='dark'>
            <Card.Body>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Package Info</h4>
                    </Col>
                    <Col md={10}>
                        <Row>
                            <Col md={3}>
                                <FormCheck
                                    label='Proof of Delivery Required'
                                    value={requireProofOfDelivery}
                                    checked={requireProofOfDelivery}
                                    disabled={readOnly || isInvoiced}
                                    onChange={event => setRequireProofOfDelivery(!requireProofOfDelivery)}
                                />
                            </Col>
                            <Col md={3}>
                                <FormCheck
                                    name='packageIsMinimum'
                                    label='Package is smaller than 30 cm&#179; (1 foot&#179;)'
                                    value={packageIsMinimum}
                                    checked={packageIsMinimum}
                                    disabled={readOnly || packageIsPallet || isInvoiced}
                                    onChange={event => setPackageIsMinimum(!packageIsMinimum)}
                                />
                            </Col>
                            {!packageIsMinimum &&
                                <Col md={3}>
                                    <FormCheck
                                        name='packageIsPallet'
                                        label='Is a pallet'
                                        value={packageIsPallet}
                                        checked={packageIsPallet}
                                        disabled={readOnly || isInvoiced}
                                        onChange={event => setPackageIsPallet(!packageIsPallet)}
                                    />
                                </Col>
                            }
                            {!packageIsMinimum &&
                                <Col md={3}>
                                    <FormCheck
                                        name='useImperial'
                                        label='Use Imperial Measurements'
                                        value={useImperial}
                                        checked={useImperial}
                                        disabled={readOnly || isInvoiced}
                                        onChange={event => setUseImperial(!useImperial)}
                                    />
                                </Col>
                            }
                            {!packageIsMinimum &&
                                <Col md={12}>
                                    <Table striped bordered size='sm'>
                                        <thead>
                                            <tr>
                                                <th><Button size='sm' variant='success' onClick={props.packages.addPackage}><i className='fas fa-plus'/></Button></th>
                                                <th>Count</th>
                                                <th>{`Weight ${useImperial ? '(lb)' : '(kg)'}`}</th>
                                                <th>{`Length ${useImperial ? '(in)' : '(cm)'}`}</th>
                                                <th>{`Width ${useImperial ? '(in)' : '(cm)'}`}</th>
                                                <th>{`Height ${useImperial ? '(in)' : '(cm)'}`}</th>
                                                <th>Total Weight (kg)</th>
                                                <th>{`Cubed Weight (m\u00B3)`}</th>
                                            </tr>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th style={{
                                                    ...(totalWeight > totalCubedWeight ? {backgroundColor: 'lightgreen'} : {})
                                                    }}
                                                >
                                                    {totalWeight ? totalWeight.toFixed(2) : ''}
                                                </th>
                                                <th style={{
                                                    ...(totalCubedWeight > totalWeight ? {backgroundColor: 'lightgreen'} : {})
                                                    }}
                                                >
                                                    {totalCubedWeight ? totalCubedWeight.toFixed(2) : ''}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {packageArray.map((parcel, index) =>
                                                <tr key={`parcel.${index}`}>
                                                    <td>
                                                        <Button size='sm' variant='danger' onClick={() => props.packages.deletePackage(index)} disabled={packageArray.length < 2}>
                                                            <i className='fas fa-trash' />
                                                        </Button>
                                                    </td>
                                                    <td>
                                                        <FormControl
                                                            size='sm'
                                                            name='count'
                                                            type='number'
                                                            step='1'
                                                            min='1'
                                                            data-packageid={index}
                                                            value={parcel.count}
                                                            onChange={event => props.packages.handlePackageUpdate(event)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <FormControl
                                                            size='sm'
                                                            name='weight'
                                                            type='number'
                                                            step='0.01'
                                                            min='0.01'
                                                            data-packageid={index}
                                                            value={parcel.weight}
                                                            onChange={event => props.packages.handlePackageUpdate(event)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <FormControl
                                                            size='sm'
                                                            name='length'
                                                            type='number'
                                                            step='0.01'
                                                            min='0.01'
                                                            data-packageid={index}
                                                            value={parcel.length}
                                                            onChange={event => props.packages.handlePackageUpdate(event)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <FormControl
                                                            size='sm'
                                                            name='width'
                                                            type='number'
                                                            step='0.01'
                                                            min='0.01'
                                                            data-packageid={index}
                                                            value={parcel.width}
                                                            onChange={event => props.packages.handlePackageUpdate(event)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <FormControl
                                                            size='sm'
                                                            name='height'
                                                            type='number'
                                                            step='0.01'
                                                            min='0.01'
                                                            data-packageid={index}
                                                            value={parcel.height}
                                                            onChange={event => props.packages.handlePackageUpdate(event)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <FormControl
                                                            size='sm'
                                                            disabled={true}
                                                            value={isNaN(parcel.totalWeight) ? null : parcel.totalWeight}
                                                            // style={{...(parcel.totalWeight && parcel.cubedWeight && parcel.totalWeight > parcel.cubedWeight && {backgroundColor: 'lightgreen'})}}
                                                        />
                                                    </td>
                                                    <td>
                                                        <FormControl
                                                            size='sm'
                                                            disabled={true}
                                                            // style={{...(parcel.totalWeight && parcel.cubedWeight && parcel.totalWeight < parcel.cubedWeight && {backgroundColor: 'lightgreen'})}}
                                                            value={parcel.cubedWeight ?? null}
                                                        />
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </Table>
                                </Col>
                            }
                        </Row>
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Addresses</h4></Col>
                    <Col md={5}>
                        <Address
                            id='pickup'
                            header='Pickup'
                            data={{
                                account: pickup.account,
                                formatted: pickup.addressFormatted,
                                isMall: pickup.isMall,
                                lat: pickup.addressLat,
                                lng: pickup.addressLng,
                                name: pickup.addressName,
                                placeId: pickup.placeId,
                                type: pickup.addressType,
                                referenceValue: pickup.referenceValue
                            }}
                            addressTypes={addressTypes}
                            handleChange={event => props.billDispatch({type: 'SET_PICKUP_VALUE', payload: {name: event.target.name, value: event.target.value}})}
                            handleReferenceValueChange={handleReferenceValueChange}
                            handleAccountChange={account => props.billDispatch({type: 'SET_PICKUP_ACCOUNT', payload: account})}
                            accounts={accounts}
                            readOnly={readOnly}
                            showAddressSearch={true}
                            useIsMall
                        />
                    </Col>
                    <Col md={5}>
                        <Address
                            id='delivery'
                            header='Delivery'
                            data={{
                                account: delivery.account,
                                formatted: delivery.addressFormatted,
                                isMall: delivery.isMall,
                                lat: delivery.addressLat,
                                lng: delivery.addressLng,
                                name: delivery.addressName,
                                placeId: delivery.placeId,
                                type: delivery.addressType,
                                referenceValue: delivery.referenceValue
                            }}
                            addressTypes={addressTypes}
                            handleChange={event => props.billDispatch({type: 'SET_DELIVERY_VALUE', payload: {name: event.target.name, value: event.target.value}})}
                            handleReferenceValueChange={handleReferenceValueChange}
                            handleAccountChange={account => props.billDispatch({type: 'SET_DELIVERY_ACCOUNT', payload: account})}
                            accounts={accounts}
                            readOnly={readOnly}
                            showAddressSearch={true}
                            useIsMall
                        />
                    </Col>
                </Row>
                <hr/>
                <Row>
                    <Col md={2}><h4 className='text-muted'>Scheduling</h4></Col>
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Delivery Type:</InputGroup.Text>
                            <Select
                                options={deliveryTypes.map((type, index) => {
                                    return {
                                        label: `${type.friendlyName} (Est. ~ ${type.time} hours)`,
                                        value: type.id,
                                        key: index
                                    }
                                })}
                                value={{
                                    label: `${deliveryType.friendlyName} (Est. ~${deliveryType.time} hours)`,
                                    value: deliveryType.id,
                                    key: deliveryTypes.findIndex(dt => dt.id === deliveryType.id)
                                }}
                                onChange={item => props.billDispatch({type: 'SET_DELIVERY_TYPE', payload: deliveryTypes[item.key]})}
                                isDisabled={readOnly || isInvoiced}
                                isOptionDisabled={option => applyRestrictions ? option.isDisabled : false}
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Package Ready: </InputGroup.Text>
                            <DatePicker
                                showTimeSelect
                                timeIntervals={15}
                                dateFormat='MMMM d, yyyy h:mm aa'
                                onChange={value => props.setPickupTimeExpected(value)}
                                showMonthDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={pickup.timeScheduled}
                                readOnly={readOnly}
                                className='form-control'
                                //Rules for non-admins only
                                filterDate={applyRestrictions && filterDates}
                                filterTime={applyRestrictions && pickupTimeFilter}
                                wrapperClassName='form-control'
                            />
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Delivery By: </InputGroup.Text>
                            <DatePicker
                                showTimeSelect
                                timeIntervals={15}
                                dateFormat='MMMM d, yyyy h:mm aa'
                                onChange={value => props.billDispatch({type: 'SET_DELIVERY_TIME_EXPECTED', payload: value})}
                                showMonthDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={delivery.timeScheduled}
                                readOnly={applyRestrictions || readOnly}
                                className='form-control'
                                //Rules for non-admins only
                                filterDate={applyRestrictions && filterDates}
                                filterTime={applyRestrictions && deliveryTimeFilter}
                                wrapperClassName='form-control'
                            />
                            <OverlayTrigger
                                overlay={<Tooltip>The estimated time of delivery based on the information entered</Tooltip>}
                                placement='left'
                            >
                                <InputGroup.Text><i className='fas fa-info-circle'></i></InputGroup.Text>
                            </OverlayTrigger>
                        </InputGroup>
                    </Col>
                </Row>
                <hr/>
                {(!permissions.viewBilling && !permissions.createFull) &&
                    <Fragment>
                        <Row>
                            <Col md={2}><h4 className='text-muted'>Billing</h4></Col>
                            <Col md={10}>
                                <Row>
                                    <Col md={4}>
                                        <InputGroup>
                                            <InputGroup.Text>Payment Type: </InputGroup.Text>
                                            <Select
                                                options={chargeTypes}
                                                getOptionLabel={type => type.name}
                                                getOptionValue={type => type.payment_type_id}
                                                value={chargeType}
                                                onChange={paymentType => props.chargeDispatch({type: 'SET_CHARGE_TYPE', payload: paymentType})}
                                                isDisabled={readOnly || chargeTypes.length === 1}
                                                menuPortalTarget={document.body}
                                                menuPosition='fixed'
                                                wrapperClassName='pac-container'
                                            />
                                        </InputGroup>
                                    </Col>
                                    {chargeType?.name === 'Account' &&
                                        <Fragment>
                                            <Col md={4}>
                                                <InputGroup style={{width: '100%'}}>
                                                    <InputGroup.Text>Account: </InputGroup.Text>
                                                    <Select
                                                        options={accounts}
                                                        isSearchable
                                                        onChange={account => props.chargeDispatch({type: 'SET_CHARGE_ACCOUNT', payload: account})}
                                                        value={chargeAccount}
                                                        isDisabled={readOnly || accounts.length === 1}
                                                        menuPortalTarget={document.body}
                                                        menuPosition='fixed'
                                                        style={{flex: 1}}
                                                    />
                                                </InputGroup>
                                            </Col>
                                            <Col md={4}>
                                                {((chargeAccount && chargeAccount.custom_field !== null)
                                                    || (chargeType !== '' && chargeType.required_field !== null)) &&
                                                    <InputGroup>
                                                        <InputGroup.Text>{chargeType.name === 'Account' ? chargeAccount.custom_field : chargeType.required_field}: </InputGroup.Text>
                                                        <FormControl
                                                            name='chargeReferenceValue'
                                                            value={chargeReferenceValue}
                                                            onChange={event => handleReferenceValueChange(chargeAccount, chargeReferenceValue, event.target.value)}
                                                            readOnly={readOnly}
                                                        />
                                                    </InputGroup>
                                                }
                                            </Col>
                                        </Fragment>
                                    }
                                </Row>
                            </Col>
                        </Row>
                        <hr/>
                    </Fragment>
                }
                <Row className='pad-top'>
                    <Col md={2}><h4 className='text-muted'>Notes</h4></Col>
                    <Col md={10}>
                        <FormControl 
                            as='textarea'
                            placeholder='Oversized or unusually shaped delivery? Special delivery instructions? Contact information on delivery? Let us know here!'
                            name='description'
                            value={description}
                            onChange={event => props.billDispatch({type: 'SET_DESCRIPTION', payload: event.target.value})}
                            readOnly={readOnly || isInvoiced}
                        />
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
