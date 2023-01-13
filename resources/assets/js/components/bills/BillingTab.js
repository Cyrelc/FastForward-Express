import React, {useEffect, useState} from 'react'
import {Button, Card, Col, FormControl, InputGroup, Row} from 'react-bootstrap'
import Select from 'react-select'
import {ReactTabulator} from 'react-tabulator'

import Charge from './Charge'

const commonRateNames = ['Adjustment', 'Refund', 'Other', 'Incorrect Information', 'Interliner', 'GST']

const repeatingBillsTitleText = 'Daily bills will be generated on and assigned to every weekday until disabled\n' +
'Weekly bills will be generated Sundays, and will be assigned for pickup and delivery on the same day of the week as the original\n' +
'Monthly bills will be generated on and assigned to the first business day of each month\n\n' +
'All repeating bills will have all filled fields copied with the notable exceptions of Waybill Number and Interliner Tracking Number'

function lineItemTypeFormatter(value) {
    switch(value) {
        case 'commonRate':
            return "<i class='fas fa-infinity fa-lg' title='Common'></i>"
        case 'distanceRate':
            return "<i class='fas fa-route fa-lg' title='Distance'></i>"
        case 'legacyRate':
            return "<i class='fab fa-fort-awesome-alt fa-lg' title='Legacy'></i>"
        case 'miscellaneousRate':
            return "<i class='fas fa-comment-dollar fa-lg' title='Miscellaneous'></i>"
        case 'timeRate':
            return "<i class='fas fa-clock fa-lg' title='Time'></i>"
        case 'weightRate':
            return "<i class='fas fa-weight fa-lg' title='Weight'></i>"
        case 'conditionalRate':
            return "<i class='fas fa-code-branch' title='Conditional'></i>"
        default:
            return "<i class='fas fa-exclamation-triangle fa-lg'></i>"
    }
}

function lineItemTypeGroupFormatter(value) {
    const icon = lineItemTypeFormatter(value)
    switch(value) {
        case 'miscellaneousRate':
            return icon + ' Miscellaneous'
        case 'distanceRate':
            return icon + ' Distance Rate'
        case 'timeRate':
            return icon + ' Time Rate'
        case 'weightRate':
            return icon + ' Weight Rate'
        case 'commonRate':
            return icon + ' Common Rate'
        default:
            console.log('Invalid rate type found: ' + value)
            return 'Invalid Type'
    }
}

export default function BillingTab(props) {
    const [rateTable, setRateTable] = useState([])

    const {
        activeRatesheet,
        charges,
        chargeAccount,
        chargeEmployee,
        chargeType,
        chargeTypes,
        hasInterliner,
        interliner,
        interlinerActualCost,
        interlinerReferenceValue,
        interliners,
        isDeliveryManifested,
        isInvoiced,
        isPickupManifested
    } = props.chargeState

    const {
        accounts,
        billNumber,
        employees,
        ratesheets,
        readOnly,
        repeatInterval,
        repeatIntervals,
        skipInvoicing
    } = props.billState

    useEffect(() => {
        if(!activeRatesheet)
            return []
        const miscRates = activeRatesheet.miscRates
            ? JSON.parse(activeRatesheet.misc_rates).map(rate => {return {...rate, type: 'miscellaneousRate', driver_amount: rate.price, paid: false}})
            : []
        const timeRates = activeRatesheet.timeRates
            ? JSON.parse(activeRatesheet.time_rates).map(rate => {return {...rate, type: 'timeRate', driver_amount: rate.price, paid: false}})
            : []
        const weightRates = activeRatesheet.weight_rates
            ? JSON.parse(activeRatesheet.weight_rates).map(rate => {return {...rate, type: 'weightRate', driver_amount: rate.price, paid: false}})
            : []
        const commonRates = commonRateNames.map(name => {return {name: name, price: 0, type: 'commonRate', driver_amount: 0, paid: false}})
        const distanceRates = []
        if(activeRatesheet.distance_rates) {
            JSON.parse(activeRatesheet.distance_rates).map(rate => {
                distanceRates.push({name: 'Regular - ' + rate.zones + (rate.zones == 1 ? ' zone' : ' zones'), price: rate.regular_cost, type: 'distanceRate', driver_amount: rate.regular_cost, paid: false})
                distanceRates.push({name: 'Rush - ' + rate.zones + (rate.zones == 1 ? ' zone' : ' zones'), price: rate.rush_cost, type: 'distanceRate', driver_amount: rate.rush_cost, paid: false})
                distanceRates.push({name: 'Direct - ' + rate.zones + (rate.zones == 1 ? ' zone' : ' zones'), price: rate.direct_cost, type: 'distanceRate', driver_amount: rate.direct_cost, paid: false})
                distanceRates.push({name: 'Direct Rush - ' + rate.zones + (rate.zones == 1 ? ' zone' : ' zones'), price: rate.direct_rush_cost, type: 'distanceRate', driver_amount: rate.direct_rush_cost, paid: false})
            });
        }
        setRateTable(commonRates.concat(miscRates, timeRates, weightRates, distanceRates))
    }, [activeRatesheet])

    return (
        <Card border='dark'>
            <Card.Header>
                <Row> {/* Settings */}
                    <Col md={2}>
                        <h4 className='text-muted'>Settings</h4>
                    </Col>
                    <Col md={10}>
                        <Row>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>Waybill #: </InputGroup.Text>
                                    <FormControl
                                        name='billNumber'
                                        value={billNumber}
                                        onChange={event => props.billDispatch({type: 'SET_BILL_NUMBER', payload: event.target.value})}
                                        readOnly={readOnly}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>Ratesheet: </InputGroup.Text>
                                    <Select
                                        getOptionLabel={ratesheet => ratesheet.name}
                                        getOptionValue={ratesheet => ratesheet.ratesheet_id}
                                        options={ratesheets}
                                        value={activeRatesheet}
                                        onChange={ratesheet => props.chargeDispatch({type: 'SET_ACTIVE_RATESHEET', payload: ratesheet})}
                                    />
                                </InputGroup>
                            </Col>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>Repeat: </InputGroup.Text>
                                    <Select
                                        options={repeatIntervals}
                                        isClearable
                                        getOptionLabel={interval => interval.name}
                                        getOptionValue={interval => interval.selection_id}
                                        onChange={interval => props.billDispatch({type: 'SET_REPEAT_INTERVAL', payload: interval})}
                                        isDisabled={readOnly}
                                        value={repeatInterval}
                                    />
                                    <InputGroup.Text><i className='fas fa-question' title={repeatingBillsTitleText}></i></InputGroup.Text>
                                </InputGroup>
                            </Col>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>Skip Invoicing</InputGroup.Text>
                                    <InputGroup.Checkbox
                                        type='checkbox'
                                        checked={skipInvoicing}
                                        onChange={event => props.billDispatch({type: 'TOGGLE_SKIP_INVOICING'})}
                                        value={skipInvoicing}
                                        name='skipInvoicing'
                                        disabled={readOnly || isInvoiced}
                                    />
                                </InputGroup>
                            </Col>
                        </Row>
                    </Col>
                </Row>
            </Card.Header>
            {hasInterliner &&
                <Card.Body>
                    <Row> {/* Interliner */}
                        <Col md={2}>
                            <h4 className='text-muted'>Interliner</h4>
                        </Col>
                        <Col md={9}>
                            <InputGroup>
                                <InputGroup.Text>Interliner: </InputGroup.Text>
                                <Select
                                    options={interliners}
                                    isSearchable
                                    value={interliner}
                                    onChange={interliner => props.chargeDispatch({type: 'SET_INTERLINER', payload: interliner})}
                                    isDisabled={readOnly || isInvoiced}
                                />
                                <InputGroup.Text>Tracking #</InputGroup.Text>
                                <FormControl
                                    type='text'
                                    placeholder='Tracking Number'
                                    name='interlinerReferenceValue'
                                    value={interlinerReferenceValue}
                                    onChange={event => props.chargeDispatch({type: 'SET_INTERLINER_REFERENCE_VALUE', payload: event.target.value})}
                                    readOnly={readOnly || isInvoiced}
                                />
                                <InputGroup.Text>Actual Cost: </InputGroup.Text>
                                <FormControl
                                    type='number'
                                    step={0.01}
                                    min={0}
                                    name='interlinerActualCost'
                                    value={interlinerActualCost}
                                    onChange={event => props.chargeDispatch({type: 'SET_INTERLINER_ACTUAL_COST', payload: event.target.value})}
                                    readOnly={readOnly || isInvoiced}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={12}>
                            <hr/>
                        </Col>
                    </Row>
                </Card.Body>
            }
            <Card.Body>
                <Row> {/* Charges */}
                    <Col md={2}>
                        <h4 className='text-muted'>Charges</h4>
                    </Col>
                    <Col md={2}>
                        <InputGroup>
                            <InputGroup.Text>Type: </InputGroup.Text>
                            <Select
                                options={chargeTypes}
                                getOptionLabel={type => type.name}
                                getOptionValue={type => type.payment_type_id}
                                value={chargeType}
                                onChange={chargeType => props.chargeDispatch({type: 'SET_CHARGE_TYPE', payload: chargeType})}
                                isDisabled={readOnly || isInvoiced}
                            />
                        </InputGroup>
                    </Col>
                    {chargeType?.name === 'Account' &&
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Account: </InputGroup.Text>
                                <Select
                                    options={accounts}
                                    getOptionLabel={account => account.account_number + ' - ' + account.name}
                                    getOptionValue={account => account.account_id}
                                    isSearchable
                                    onChange={account => props.chargeDispatch({type: 'SET_CHARGE_ACCOUNT', payload: account})}
                                    value={chargeAccount}
                                    isDisabled={readOnly || isInvoiced}
                                />
                            </InputGroup>
                        </Col>
                    }
                    {chargeType?.name === 'Employee' &&
                        <Col md={4}>
                            <InputGroup>
                                <InputGroup.Text>Employee: </InputGroup.Text>
                                <Select
                                    options={employees}
                                    isSearchable
                                    getOptionLabel={employee => employee.label}
                                    getOptionValue={employee => employee.value}
                                    value={chargeEmployee}
                                    onChange={employee => props.chargeDispatch({type: 'SET_CHARGE_EMPLOYEE', payload: employee})}
                                    isDisabled={readOnly}
                                />
                            </InputGroup>
                        </Col>
                    }
                    <Col md={1}>
                        <Button
                            variant='success'
                            onClick={() => props.chargeDispatch({type: 'ADD_CHARGE_TABLE'})}
                            disabled={
                                chargeType?.name === 'Account' ? !chargeAccount :
                                chargeType?.name === 'Employee' ? !chargeEmployee :
                                !chargeType
                            }
                        ><i className='fas fa-plus'></i> Add</Button>
                    </Col>
                </Row>
            </Card.Body>
            <hr/>
            <Row>
                <Col md={2}>
                    <Card border='dark' style={{padding: '0px'}}>
                        <Card.Header><h4 className='text-muted'>Line Items</h4></Card.Header>
                        <Card.Body style={{padding: '0px'}}>
                            {(activeRatesheet && rateTable?.length) &&
                                <ReactTabulator
                                    id='lineItemSource'
                                    columns={[
                                        {title: 'Name', field: 'name', headerSort: false},
                                        {title: 'Type', field: 'type', formatter: cell => lineItemTypeFormatter(cell.getValue()), hozAlign: 'center', width: 45, headerSort: false, visible: false}
                                    ]}
                                    data={rateTable}
                                    options={{
                                        groupBy: 'type',
                                        groupHeader: (value, count, data, group) => lineItemTypeGroupFormatter(value, count, data, group),
                                        index: 'line_item_id',
                                        maxHeight: '700px',
                                        movableRows: true,
                                        movableRowsReceiver: false,
                                        movableRowsSender: true,
                                        movableRowsConnectedTables: ['#lineItemDestination'],
                                        layout: 'fitColumns'
                                    }}
                                />
                            }
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={10}>
                    <Row>
                        {charges?.map((charge, index, charges) =>
                            <Charge
                                charge={charge}
                                chargeCount={charges.filter(charge => !charge.toBeDeleted).length}
                                chargeDispatch={props.chargeDispatch}
                                charges={charges}
                                delivery={props.billState.delivery}
                                drivers={props.billState.drivers}
                                generateCharges={props.generateCharges}
                                index={index}
                                key={index}
                                lineItemTypeFormatter={lineItemTypeFormatter}
                                pickup={props.billState.pickup}
                                readOnly={readOnly || isPickupManifested || isDeliveryManifested || isInvoiced}
                            />
                        )}
                    </Row>
                </Col>
            </Row>
        </Card>
    )
}
