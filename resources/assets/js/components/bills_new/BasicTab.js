import React, {Fragment, useEffect} from 'react'
import {Button, Card, Col, FormControl, FormCheck, InputGroup, OverlayTrigger, Row, Table, Tooltip} from 'react-bootstrap'
import Select from 'react-select'
import {DateTime} from 'luxon'
import DatePicker from 'react-datepicker'

import Address from '../partials/AddressFunctional'
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

export default function BasicTab({bill, charges, delivery, packages, pickup}) {
    const api = useAPI()

    const {
        accounts,
        addressTypes,
        applyRestrictions,
        billId,
        businessHoursMax,
        businessHoursMin,
        deliveryType,
        deliveryTypes,
        description,
        permissions,
        readOnly,
        setDescription,
        setDeliveryType
    } = bill

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
    } = packages

    const {chargeAccount, chargeReferenceValue, chargeType, chargeTypes, invoiceIds} = charges

    const totalWeight = packageArray.reduce((acc, parcel) => acc + parcel.totalWeight ? parseFloat(parcel.totalWeight) : 0, 0)
    const totalCubedWeight = packageArray.reduce((acc, parcel) => acc + parcel.cubedWeight ? parseFloat(parcel.cubedWeight) : 0, 0)

    const includeTimes = []
    for (let hour = businessHoursMin.hour; hour < businessHoursMax.hour; hour++) {
        for(let minute = 0; minute < 60; minute += 15)
            includeTimes.push(new Date().setHours(hour, minute))
    }

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

    // useEffect(() => {
    //     if(delivery.addressLat && delivery.addressLng && props.chargeState.activeRatesheet)
    //         api.get(`/ratesheets/${props.chargeState.activeRatesheet.ratesheet_id}/getZone?lat=${delivery.addressLat}&lng=${delivery.addressLng}`)
    //             .then(response => {
    //                 props.billDispatch({type: 'SET_DELIVERY_ZONE', payload: response})
    //                 if(!billId && applyRestrictions)
    //                     props.setPickupTimeExpected(new Date())
    //             })
    // }, [delivery.addressLat, delivery.addressLng])

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
                                    id='proofOfDeliveryRequired'
                                    label='Proof of Delivery Required'
                                    value={requireProofOfDelivery}
                                    checked={requireProofOfDelivery}
                                    disabled={readOnly || invoiceIds.length > 0}
                                    onChange={event => setRequireProofOfDelivery(!requireProofOfDelivery)}
                                />
                            </Col>
                            <Col md={3}>
                                <FormCheck
                                    name='packageIsMinimum'
                                    id='packageIsMinimum'
                                    label='Package is smaller than 30 cm&#179; (1 foot&#179;)'
                                    value={packageIsMinimum}
                                    checked={packageIsMinimum}
                                    disabled={readOnly || packageIsPallet || invoiceIds.length > 0}
                                    onChange={event => setPackageIsMinimum(!packageIsMinimum)}
                                />
                            </Col>
                            {!packageIsMinimum &&
                                <Col md={3}>
                                    <FormCheck
                                        name='packageIsPallet'
                                        id='packageIsPallet'
                                        label='Is a pallet'
                                        value={packageIsPallet}
                                        checked={packageIsPallet}
                                        disabled={readOnly || invoiceIds.length > 0}
                                        onChange={event => setPackageIsPallet(!packageIsPallet)}
                                    />
                                </Col>
                            }
                            {!packageIsMinimum &&
                                <Col md={3}>
                                    <FormCheck
                                        id='useImperial'
                                        name='useImperial'
                                        label='Use Imperial Measurements'
                                        value={useImperial}
                                        checked={useImperial}
                                        disabled={readOnly || invoiceIds.length > 0}
                                        onChange={event => setUseImperial(!useImperial)}
                                    />
                                </Col>
                            }
                            {!packageIsMinimum &&
                                <Col md={12}>
                                    <Table striped bordered size='sm'>
                                        <thead>
                                            <tr>
                                                <th><Button size='sm' variant='success' onClick={packages.addPackage}><i className='fas fa-plus'/></Button></th>
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
                                                        <Button size='sm' variant='danger' onClick={() => packages.deletePackage(index)} disabled={packageArray.length < 2}>
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
                                                            onChange={event => packages.handlePackageUpdate(event)}
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
                                                            onChange={event => packages.handlePackageUpdate(event)}
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
                                                            onChange={event => packages.handlePackageUpdate(event)}
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
                                                            onChange={event => packages.handlePackageUpdate(event)}
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
                                                            onChange={event => packages.handlePackageUpdate(event)}
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
                            account={pickup.account}
                            accounts={accounts}
                            address={pickup}
                            addressTypes={addressTypes}
                            header='Pickup'
                            readOnly={readOnly}
                            showAddressSearch={true}
                            useIsMall
                        />
                    </Col>
                    <Col md={5}>
                        <Address
                            id='delivery'
                            account={delivery.account}
                            accounts={accounts}
                            address={delivery}
                            addressTypes={addressTypes}
                            header='Delivery'
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
                            {/* <Select
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
                                onChange={item => setDeliveryType(deliveryTypes[item.key])}
                                isDisabled={readOnly || invoiceIds.length > 0}
                                isOptionDisabled={option => applyRestrictions ? option.isDisabled : false}
                            /> */}
                        </InputGroup>
                    </Col>
                    <Col md={3}>
                        <InputGroup>
                            <InputGroup.Text>Package Ready: </InputGroup.Text>
                            <DatePicker
                                showTimeSelect
                                timeIntervals={15}
                                dateFormat='MMMM d, yyyy h:mm aa'
                                onChange={value => pickup.setTimeScheduled(value)}
                                showMonthDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={pickup.timeScheduled}
                                readOnly={readOnly}
                                className='form-control'
                                //Rules for non-admins only
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
                                onChange={value => delivery.setTimeScheduled(value)}
                                showMonthDropdown
                                monthDropdownItemNumber={15}
                                scrollableMonthDropdown
                                selected={delivery.timeScheduled}
                                readOnly={applyRestrictions || readOnly}
                                className='form-control'
                                //Rules for non-admins only
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
                {/* {(!permissions.viewBilling && !permissions.createFull) &&
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
                } */}
                <Row className='pad-top'>
                    <Col md={2}><h4 className='text-muted'>Notes</h4></Col>
                    <Col md={10}>
                        <FormControl 
                            as='textarea'
                            placeholder='Oversized or unusually shaped delivery? Special delivery instructions? Contact information on delivery? Let us know here!'
                            name='description'
                            value={description}
                            onChange={event => setDescription(event.target.value)}
                            readOnly={readOnly || invoiceIds.length > 0}
                        />
                    </Col>
                </Row>
            </Card.Body>
        </Card>
    )
}
