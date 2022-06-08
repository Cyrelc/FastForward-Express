import React, {useEffect, useState} from 'react'
import {Button, Card, Col, Row, FormControl, InputGroup} from 'react-bootstrap'
import Select from 'react-select'
import {ReactTabulator} from 'react-tabulator'

import LinkLineItemModal from './LinkLineItemModal'

const commonRateNames = ['Refund', 'Other', 'Incorrect Information', 'Interliner']

const repeatingBillsTitleText = 'Daily bills will be generated on and assigned to every weekday until disabled\n' +
'Weekly bills will be generated Sundays, and will be assigned for pickup and delivery on the same day of the week as the original\n' +
'Monthly bills will be generated on and assigned to the first business day of each month\n\n' +
'All repeating bills will have all filled fields copied with the notable exceptions of Waybill Number and Interliner Tracking Number'

function canLineItemBeDeleted(row) {
    const rowData = row.getData()
    if(!rowData.invoice_id && !rowData.pickup_manifest_id && !rowData.delivery_manifest_id && !rowData.paid)
        return true
    return false
}

function chargeTypeFormatter(type) {
    switch(type.name) {
        case 'Account':
            return <i className='fas fa-building'></i>
        case 'Employee':
            return <i className='fas fa-user-ninja'></i>
        case 'Mastercard':
        case 'Visa':
        case 'American Express':
            return <i className='fas fa-credit-card'></i>
        case 'Cash':
            return <i className='fas fa-money-bill-wave'></i>
    }
}

function deleteLineItem(cell) {
    const row = cell.getRow()
    const rowData = row.getData()
    if(!canLineItemBeDeleted(row)) {
        console.log('Unable to delete line item\t' + rowData.invoice_id + '\t' + rowData.manifest_id + '\t' + rowData.paid)
        return
    }
    if(rowData.line_item_id != null) {
        row.update({toBeDeleted: true})
        row.getTable().refreshFilter()
    } else
        row.delete()
}

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

const groupBy = (data, charge) => {
    const value = charge.chargeType.name === 'Account' ? 'invoice_id' : charge.chargeType.name === 'Employee' ? 'manifest_id' : 'paid'
    return value
}

function groupHeaderFormatter(key, count, data, group) {
    const styledCount = '<span style="color:blue">(' + count + ')</span>'
    const value = data[0][key]
    if(key === 'invoice_id')
        return `${value ? 'Invoice #' : 'Not Yet Invoiced'}${styledCount}`
    else if(key === 'manifest_id')
        return `${value ? 'Manifest #' : 'Not Yet Manifested'}${styledCount}`
    else if (key === 'paid')
        return `${value ? 'Paid' : 'Unpaid'}${styledCount}`
    return
}

function payOffAllLineItems(charge) {
    const table = charge.tableRef.current
    const rows = table.getRows()
    rows.forEach(row => row.update({paid: true}))
}

export default function BillingTab(props) {
    const [rateTable, setRateTable] = useState([])
    const [showLinkLineItemModal, setShowLinkLineItemModal] = useState(false)
    const [linkLineItemToType, setLinkLineItemToType] = useState('')
    const [linkLineItemCell, setLinkLineItemCell] = useState('')

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

    const actionCellContextMenu = cell => {
        const data = cell.getRow().getData()
        var menuItems = readOnly ? [] : data.line_item_id ? [
            ... canLineItemBeDeleted(cell.getRow()) ? [
                {label: "<i class='fas fa-trash fa-sm'></i> Delete Line Item", action: (event, cell) => deleteLineItem(cell)}
            ] : [],
            ... data.invoice_id ? [
                {label: '<i class="fas fa-unlink"></i> Remove Invoice Link', action: () => removeLink(cell, 'Invoice'), disabled: data.finalized},
            ] : [
                {label: '<i class="fas fa-link"></i> Link To Invoice', action: (e, cell) => linkTo(cell, 'Invoice')}
            ],
            ... data.pickup_manifest_id ? [
                {label: '<i class="fas fa-unlink"></i> Remove Pickup Manifest Link', action: () => removeLink(cell, 'Pickup Manifest')},
            ] : [
                {label: '<i class="fas fa-link"></i> Link To Pickup Manifest', action: (e, cell) => linkTo(cell, 'Pickup Manifest')}
            ],
            ... data.delivery_manifest_id ? [
                {label: '<i class="fas fa-unlink"></i> Remove Delivery Manifest Link', action: () => removeLink(cell, 'Delivery Manifest')}
            ] : [
                {label: '<i class="fas fa-link"></i> Link To Delivery Manifest', action: (e, cell) => linkTo(cell, 'Delivery Manifest')}
            ]
        ] : [
            {label: "<i class='fas fa-trash fa-sm'></i> Delete Line Item", action: (event, cell) => deleteLineItem(cell)}
        ]
        return menuItems
    }

    const actionCellContextMenuFormatter = cell => {
        return readOnly ? null : '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"></i></button>'
    }

    const canChargeTableBeDeleted = charge => {
        if(!charge || !!props.readOnly)
            return false
        return !charge.lineItems.some(lineItem => (lineItem.invoice_id || lineItem.pickup_manifest_id || lineItem.delivery_manifest_id || lineItem.paid) ? true : false)
    }

    const chargeTableColumns = chargeType => {
        return [
            {
                clickMenu: cell => actionCellContextMenu(cell),
                formatter: cell => actionCellContextMenuFormatter(cell),
                headerSort: false,
                hozAlign: 'center',
                print: false,
                width: 45
            },
            {title: 'Name', field: 'name'},
            {title: 'Type', field: 'type', formatter: cell => lineItemTypeFormatter(cell.getValue()), headerSort: false, hozAlign: 'center', width: 45},
            {title: 'Price', field: 'price', ...moneyColumnStandardParams},
            {title: 'Driver Amount', field: 'driver_amount', ...moneyColumnStandardParams},
            ... chargeType.name === 'Account' ? [{title: 'Invoice ID', field: 'invoice_id', visible: false}] : chargeType.name === 'Employee' ? [{title: 'Manifest ID', field: 'manifest_id', visible: false}] : [],
            {title: 'Paid?', field: 'paid', formatter: 'tickCross', cellClick: (e, cell) => {cell.setValue(!cell.getValue())}, width: 45, hozAlign: 'center', headerSort: false},
            {title: 'Line Item ID', field: 'line_item_id', visible: false},
            {title: 'Invoice ID', field: 'invoice_id', visible: false},
            {title: 'Pickup Manifest ID', field: 'pickup_manifest_id', visible: false},
            {title: 'Delivery Manifest ID', field: 'delivery_manifest_id', visible: false}
        ]
    }

    const deleteChargeTable = index => {
        if(!canChargeTableBeDeleted(charges.filter((charge, i) => i == index)[0])) {
            const errorMessage = 'ERROR - charge table cannot be deleted - at least one item has been invoiced, manifested, or paid'
            toastr.error(errorMessage)
            console.log(errorMessage)
            return
        }
        if(confirm('Are you sure you wish to delete this charge group?\n This action can not be undone')) {
            props.chargeDispatch({type: 'DELETE_CHARGE_TABLE', payload: index})
        }
    }

    function hideLinkTo() {
        setLinkLineItemCell(null)
        setLinkLineItemToType(null)
        setShowLinkLineItemModal(false)
        props.chargeDispatch({type: 'CHECK_INVOICES_AND_MANIFESTS'})
    }

    function linkTo(cell, type) {
        setLinkLineItemCell(cell)
        setLinkLineItemToType(type)
        setShowLinkLineItemModal(true)
    }

    const moneyColumnStandardParams = {
        editor:'number',
        formatter: 'money',
        formatterParams: {thousand: ',', symbol: '$'},
        editorParams: {step: 0.01},
        hozAlign: 'right',
        topCalc: 'sum',
        topCalcParams: {precision: 2},
        topCalcFormatter: 'money',
        topCalcFormatterParams: {thousand: ',', symbol: '$'},
        sorter: 'number',
        editable: cell => {
            return props.readOnly ? false : canLineItemBeDeleted(cell.getRow())
        }
    }

    function removeLink(cell, type) {
        const data = {
            action: 'remove_link',
            line_item_id: cell.getRow().getData('line_item_id'),
            link_type: type
        }
        makeAjaxRequest('/bills/manageLineItemLinks', 'POST', data, response => {
            cell.getRow().update(JSON.parse(response))
        })
    }

    return (
        <Card border='dark'>
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
            <hr/>
            {hasInterliner &&
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
            }
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
                                isDisabled={readOnly || pickupManifestId || deliveryManifestId}
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
                <Col md={2}>
                    <Button
                        variant='warning'
                        onClick={props.generateCharges}
                        disabled={readOnly || isPickupManifested || isDeliveryManifested || isInvoiced || charges?.length !== 1}
                    >Auto-price (BETA)</Button>
                </Col>
            </Row>
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
                        {charges && charges.map((charge, index, charges) =>
                            <Col
                                style={{display: charge.toBeDeleted ? 'none' : ''}}
                                key={index + '.' + charge.name}
                                md={charges.reduce((counter, cur) => {return cur.toBeDeleted ? counter : ++counter}, 0) === 1 ? 12 :
                                    charges.reduce((counter, cur) => {return cur.toBeDeleted ? counter : ++counter}, 0) === 2 ? 6 : 4}
                            >
                                <Card border='dark' style={{padding: '0px'}}>
                                    <Card.Header>
                                        <Row>
                                            <Col md={(charge.lineItems && canChargeTableBeDeleted(charge)) ? 10 : 11}>
                                                <h5 className='text-muted'>{chargeTypeFormatter(charge.chargeType)} {charge.name}</h5>
                                            </Col>
                                            {!readOnly &&
                                                <Col md={1}>
                                                    <Button variant='success' size='sm' onClick={() => payOffAllLineItems(charge)}>
                                                        <i className='fas fa-hand-holding-usd' title='Mark all as paid'></i>
                                                    </Button>
                                                </Col>
                                            }
                                            {(charge.lineItems && canChargeTableBeDeleted(charge)) &&
                                                <Col md={1}>
                                                    <Button variant='danger' size='sm' onClick={() => deleteChargeTable(index)}><i className='fas fa-trash fa-sm'></i></Button>
                                                </Col>
                                            }
                                            {charge.charge_reference_value_label !== null &&
                                                <Col md={12}>
                                                    <InputGroup>
                                                        <InputGroup.Text>{charge.charge_reference_value_label}: </InputGroup.Text>
                                                        <FormControl
                                                            name='charge_reference_value'
                                                            value={charge.charge_reference_value}
                                                            onChange={event => props.chargeDispatch({type: 'SET_CHARGE_REFERENCE_VALUE', payload: {index, value: event.target.value}})}
                                                            readOnly={readOnly}
                                                            data-chargeindex={index}
                                                        />
                                                    </InputGroup>
                                                </Col>
                                            }
                                        </Row>
                                    </Card.Header>
                                    <Card.Body style={{padding: '0px'}}>
                                        <ReactTabulator
                                            onRef={ref => charge.tableRef.current = ref.current}
                                            id={'lineItemDestination'}
                                            columns={chargeTableColumns(charge.chargeType)}
                                            data={charge.lineItems}
                                            data-index={index}
                                            events={{
                                                cellEdited: cell => {
                                                    const field = cell.getField()
                                                    const row = cell.getRow()
                                                    const rowData = row.getData()
                                                    if(field === 'price' && (!rowData['driver_amount'] || cell.getOldValue() === rowData['driver_amount']))
                                                        row.update({driver_amount: cell.getValue()})
                                                },
                                                rowAdded: row => {
                                                    row.getTable().setGroupBy(data => groupBy(data, charge))
                                                    props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
                                                },
                                                rowDeleted: row => {
                                                    props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
                                                    charge.tableRef.current.redraw()
                                                }
                                            }}
                                            options={{
                                                groupBy: data => groupBy(data, charge),
                                                groupHeader: (value, count, data, group) => groupHeaderFormatter(value, count, data, group),
                                                initialFilter: [{field: 'toBeDeleted', type: '!=', value: true}],
                                                layout: 'fitColumns',
                                                movableRowsReceiver: 'add',
                                                reactiveData: false
                                            }}
                                        />
                                    </Card.Body>
                                </Card>
                            </Col>
                        )}
                    </Row>
                </Col>
            </Row>
            <LinkLineItemModal
                hide={hideLinkTo}
                linkLineItemCell={linkLineItemCell}
                linkLineItemToType={linkLineItemToType}
                show={showLinkLineItemModal}
            />
        </Card>
    )
}
