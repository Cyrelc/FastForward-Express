import React, {Fragment, useEffect, useRef, useState} from 'react'
import {Card, Col, FormControl, FormCheck, InputGroup, OverlayTrigger, Row, Tooltip} from 'react-bootstrap'
import Select from 'react-select'
import {DateTime} from 'luxon'
import {TabulatorFull as Tabulator} from 'tabulator-tables'
import DatePicker from 'react-datepicker'

import Address from '../partials/Address'

const filterDates = date => {
    const dateTime = DateTime.fromJSDate(date)
    if(dateTime.hasSame(DateTime.local(), "days"))
        return true
    if(dateTime.diffNow("days").days < 0)
        return false
    const day = date.getDay()
    return day !== 6 && day !== 7
}

export default function BasicTab(props) {
    const tableRef = useRef(null)
    const [table, setTable] = useState(null)

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

    const {packageIsMinimum, packageIsPallet, packages, proofOfDeliveryRequired, useImperial} = props.packageState

    const {chargeAccount, chargeReferenceValue, chargeType, chargeTypes, isInvoiced} = props.chargeState

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
        if(tableRef.current && !table && !packageIsMinimum) {
            const newTabulator = new Tabulator(tableRef.current, {
                columns: packageColumns,
                data: packages,
                layout: 'fitColumns'
                // rowAdded: row => {
                //     props.packageDispatch({type: 'UPDATE_PACKAGES', payload: row.getTable().getData()})
                // },
                // rowDeleted: row => {
                //     props.packageDispatch({type: 'UPDATE_PACKAGES', payload: row.getTable().getData()})
                // }
            })

            newTabulator.on('cellEdited', (cell) => {
                const fieldName = cell.getField()
                const row = cell.getRow()
                const rowData = row.getData()
                if(fieldName === 'count' || 'weight') {
                    const totalWeight = parseInt(rowData.count) * parseFloat(rowData.weight)
                    row.update({'totalWeight': isNaN(totalWeight) ? null : totalWeight})
                }
                if(fieldName === 'count' || 'height' || 'width' || 'length') {
                    const totalVolume = parseInt(rowData.count) * parseFloat(rowData.length) * parseInt(rowData.height) * parseInt(rowData.width)
                    row.update({'totalVolume': isNaN(totalVolume) ? null : totalVolume})
                }
                props.packageDispatch({type: 'UPDATE_PACKAGES', payload: row.getTable().getData()})
            })
            newTabulator.on('rowAdded', row => props.packageDispatch({type: 'UPDATE_PACAKGES', payload: row.getTable().getData()}))
            newTabulator.on('rowDeleted', row => props.packageDispatch({type: 'UPDATE_PACAKGES', payload: row.getTable().getData()}))

            setTable(newTabulator)
        } else {
            setTable(null)
        }
    }, [tableRef, packageIsMinimum])

    useEffect(() => {
        if(pickup.addressLat && pickup.addressLng && props.chargeState.activeRatesheet) {
            makeAjaxRequest(`/ratesheets/${props.chargeState.activeRatesheet.ratesheet_id}/getZone?lat=${pickup.addressLat}&lng=${pickup.addressLng}`, 'GET', null, response => {
                props.billDispatch({type: 'SET_PICKUP_ZONE', payload: response})
                if(!billId && applyRestrictions)
                    props.setPickupTimeExpected(new Date())
            })
        }
    }, [pickup.addressLat, pickup.addressLng])

    useEffect(() => {
        if(delivery.addressLat && delivery.addressLng && props.chargeState.activeRatesheet)
            makeAjaxRequest(`/ratesheets/${props.chargeState.activeRatesheet.ratesheet_id}/getZone?lat=${delivery.addressLat}&lng=${delivery.addressLng}`, 'GET', null, response => {
                props.billDispatch({type: 'SET_DELIVERY_ZONE', payload: response})
                if(!billId && applyRestrictions)
                    props.setPickupTimeExpected(new Date())
            })
    }, [delivery.addressLat, delivery.addressLng])

    const packageColumns = [
        {
            formatter: cell => {
                if(cell.getValue() && !readOnly) return '<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>'
            },
            titleFormatter: cell => {
                return readOnly ? '' : `<button class='btn btn-sm btn-success'><i class='fas fa-plus'></i></button>`
            },
            field: 'deletable',
            width: 50,
            hozAlign: 'center',
            cellClick: (e, cell) => {
                const table = cell.getTable()
                if(table.getDataCount() > 1) {
                    cell.getRow().delete()
                    if(table.getDataCount() == 1)
                        table.getRows()[0].update({deletable: false})
                }
            },
            headerClick: (e, column) => {
                e.stopPropagation()
                const table = column.getTable()
                table.getRows().forEach(row => {
                    row.update({"deletable": true})
                })
                table.addData([{count: 1, deletable: true}], true)
            },
            headerSort: false,
            print: false
        },
        {title: 'Count', field: 'count', headerSort: false, topCalc: 'sum', editor: 'number'},
        {title: `Weight ${useImperial ? '(lbs)' : '(kgs)'}`, field: 'weight', headerSort: false, editor: 'number', editorParams: {min: 1, step: 1, selectContents: true}},
        {title: `Length ${useImperial ? '(in)' : '(cm)'}`, field: 'length', headerSort: false, editor: 'number', editorParams: {min: 1, step: 1, selectContents: true}},
        {title: `Width ${useImperial ? '(in)' : '(cm)'}`, field: 'width', headerSort: false, editor: 'number', editorParams: {min: 1, step: 1, selectContents: true}},
        {title: `Height ${useImperial ? '(in)' : '(cm)'}`, field: 'height', headerSort: false, editor: 'number', editorParams: {min: 1, step: 1, selectContents: true}},
        {title: `Total Weight ${useImperial ? '(lbs)' : '(kgs)'}`, field: 'totalWeight', topCalc: 'sum'},
        {title: `Total Volume ${useImperial ? '(in\u00B3)' : '(cm\u00B3)'}`, field: 'totalVolume', topCalc: 'sum'}
    ]

    useEffect(() => {
        if(table)
            table.setColumns(packageColumns)
    }, [useImperial])

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
                                    name='proofOfDeliveryRequired'
                                    label='Proof of Delivery Required'
                                    value={proofOfDeliveryRequired}
                                    checked={proofOfDeliveryRequired}
                                    disabled={readOnly || isInvoiced}
                                    onChange={event => props.packageDispatch({type: 'TOGGLE_PROOF_OF_DELIVERY'})}
                                />
                            </Col>
                            <Col md={3}>
                                <FormCheck
                                    name='packageIsMinimum'
                                    label='Package is smaller than 30 cm&#179; (1 foot&#179;)'
                                    value={packageIsMinimum}
                                    checked={packageIsMinimum}
                                    disabled={readOnly || packageIsPallet || isInvoiced}
                                    onChange={event => props.packageDispatch({type: 'TOGGLE_PACKAGE_IS_MINIMUM'})}
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
                                        onChange={event => props.packageDispatch({type: 'TOGGLE_PACKAGE_IS_PALLET'})}
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
                                        onChange={event => props.packageDispatch({type: 'TOGGLE_USE_IMPERIAL'})}
                                    />
                                </Col>
                            }
                            {!packageIsMinimum &&
                                <Col md={12}>
                                    <div ref={tableRef}></div>
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
