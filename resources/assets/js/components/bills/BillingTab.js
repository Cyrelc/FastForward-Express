import React from 'react'
import {Button, Card, Col, Row, FormControl, InputGroup} from 'react-bootstrap'
import Select from 'react-select'
import {ReactTabulator} from 'react-tabulator'

import LinkLineItemModal from './LinkLineItemModal'

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

function groupHeaderFormatter(value, count, data, group) {
    const styledCount = '<span style="color:blue">(' + count + ')</span>'
    const field = group.getField()
    if(field === 'invoice_id')
        return value ? 'Invoice #' + value + styledCount : 'Not Yet Invoiced'
    else if(field === 'manifest_id')
        return value ? 'Manifest #' + value + styledCount : 'Not Yet Manifested'
    else if (field === 'paid')
        return value ? ('Paid' + styledCount) : ('Unpaid' + styledCount) 
    return
}

export default function BillingTab(props) {
    function actionCellContextMenu(cell) {
        const data = cell.getRow().getData()
        var menuItems = props.readOnly ? [] : data.line_item_id ? [
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

    function actionCellContextMenuFormatter(cell) {
        return props.readOnly ? null : '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"></i></button>'
    }

    function canChargeTableBeDeleted(charge) {
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

    function deleteChargeTable(index) {
        if(!canChargeTableBeDeleted(props.charges.filter((charge, i) => i == index)[0])) {
            console.log('ERROR - charge table cannot be deleted - at least one item has been invoiced, manifested, or paid')
            return
        }
        if(confirm('Are you sure you wish to delete this charge group?\n This action can not be undone')) {
            const charges = props.charges.map((chargeTable, i) => {
                if(i == index)
                    return {...chargeTable, toBeDeleted: true}
                return chargeTable
            })
            props.handleChanges([
                {target: {name: 'charges', type: 'array', value: charges}},
            ])
        }
    }

    function handleChange(event) {
        const {name, value, type, checked} = event.target
        const charges = props.charges.map((charge, index) => {
            if(index == event.target.dataset.chargeindex)
                return {...charge, [name]: value}
            return charge
        })
        props.handleChanges({target: {name: 'charges', type: 'array', value: charges}})
    }

    function handleLineItemAdded(row) {
        const rowData = row.getData()
        if(rowData.type === 'weightRate') {
            console.log('weightRate')
            // API call?
            // row.update({amount: 10000})
        }
        else if(rowData.type === 'timeRate') {
            console.log('timeRate')
            // API call?
            // row.update({amount: 5000})
        }
        else if(rowData.type === 'distanceRate') {
            console.log('distanceRate')
            // API call?
            // row.update({amount: 2000})
        }
        else
            console.log('ELSE: ' + rowData.type)
    }

    function linkTo(cell, type) {
        const changes = [
            {target: {name: 'showLinkLineItemModal', type: 'boolean', value: true}},
            {target: {name: 'linkLineItemCell', type: 'object', value: cell}},
            {target: {name: 'linkLineItemToType', type: 'string', value: type}}
        ]
        props.handleChanges(changes)
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

    function payOffAll(payOffIndex) {
        console.log('Pay off all: ' + payOffIndex)
        const charges = props.charges.map((charge, index) => {
            if(index == payOffIndex)
                return {...charge, lineItems: charge.lineItems.map(lineItem => {return {...lineItem, paid: true}})}
            return charge
        })
        props.handleChanges({target: {name: 'charges', type: 'array', value: charges}})
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
                                    value={props.billNumber}
                                    onChange={props.handleChanges}
                                    readOnly={props.readOnly}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={3}>
                            <InputGroup>
                                <InputGroup.Text>Ratesheet: </InputGroup.Text>
                                <Select
                                    options={props.ratesheets.map(ratesheet => {return {label: ratesheet.name, value: ratesheet.ratesheet_id}})}
                                    value={props.activeRatesheet ? {label: props.activeRatesheet.name, value: props.activeRatesheet.ratesheet_id} : undefined}
                                    onChange={ratesheet => props.handleRatesheetSelection(ratesheet.value)}
                                />
                            </InputGroup>
                        </Col>
                        <Col md={3}>
                            <InputGroup>
                                <InputGroup.Text>Repeat: </InputGroup.Text>
                                <Select
                                    options={props.repeatIntervals}
                                    isClearable
                                    getOptionLabel={interval => interval.name}
                                    getOptionValue={interval => interval.selection_id}
                                    onChange={interval => props.handleChanges({target: {name: 'repeatInterval', type: 'object', value: interval}})}
                                    isDisabled={props.readOnly}
                                    value={props.repeatInterval}
                                />
                                <InputGroup.Text><i className='fas fa-question' title={repeatingBillsTitleText}></i></InputGroup.Text>
                            </InputGroup>
                        </Col>
                        <Col md={3}>
                            <InputGroup>
                                <InputGroup.Text>Skip Invoicing</InputGroup.Text>
                                <InputGroup.Checkbox
                                    type='checkbox'
                                    checked={props.skipInvoicing}
                                    onChange={props.handleChanges}
                                    value={props.skipInvoicing}
                                    name='skipInvoicing'
                                    disabled={props.readOnly || props.invoiceId}
                                />
                            </InputGroup>
                        </Col>
                    </Row>
                </Col>
            </Row>
            <hr/>
            {props.charges && props.charges.some(charge => charge.lineItems.some(lineItem => lineItem.name === 'Interliner')) &&
                <Row> {/* Interliner */}
                    <Col md={2}>
                        <h4 className='text-muted'>Interliner</h4>
                    </Col>
                    <Col md={9}>
                        <InputGroup>
                            <InputGroup.Text>Interliner: </InputGroup.Text>
                            <Select
                                options={props.interliners}
                                isSearchable
                                value={props.interliner}
                                onChange={interliner => props.handleChanges({target: {name: 'interliner', type: 'object', value: interliner}})}
                                isDisabled={props.readOnly || props.invoiceId}
                            />
                            <InputGroup.Text>Tracking #</InputGroup.Text>
                            <FormControl
                                type='text'
                                placeholder='Tracking Number'
                                name='interlinerTrackingId'
                                value={props.interlinerTrackingId}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly || props.invoiceId}
                            />
                            <InputGroup.Text>Actual Cost: </InputGroup.Text>
                            <FormControl
                                type='number'
                                step={0.01}
                                min={0}
                                name='interlinerActualCost'
                                value={props.interlinerActualCost}
                                onChange={props.handleChanges}
                                readOnly={props.readOnly || props.invoiceId}
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
                            options={props.chargeTypes}
                            getOptionLabel={type => type.name}
                            value={props.chargeType}
                            onChange={chargeType => props.handleChanges({target: {name: 'chargeType', type: 'text', value: chargeType}})}
                            isDisabled={props.readOnly || props.invoiceId}
                        />
                    </InputGroup>
                </Col>
                {props.chargeType.name === 'Account' &&
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Account: </InputGroup.Text>
                            <Select
                                options={props.accounts}
                                getOptionLabel={account => account.account_number + ' - ' + account.name}
                                getOptionValue={account => account.account_id}
                                isSearchable
                                onChange={account => props.handleChanges({target: {name: 'chargeAccount', type: 'object', value: account}})}
                                value={props.chargeAccount}
                                isDisabled={props.readOnly || props.invoiceId}
                            />
                        </InputGroup>
                    </Col>
                }
                {props.chargeType.name === 'Employee' &&
                    <Col md={4}>
                        <InputGroup>
                            <InputGroup.Text>Employee: </InputGroup.Text>
                            <Select
                                options={props.employees}
                                isSearchable
                                value={props.chargeEmployee}
                                onChange={employee => props.handleChanges({target: {name: 'chargeEmployee', type: 'object', value: employee}})}
                                isDisabled={props.readOnly || props.pickupManifestId || props.deliveryManifestId}
                            />
                        </InputGroup>
                    </Col>
                }
                <Col md={1}>
                    <Button
                        variant='success'
                        onClick={props.addChargeTable}
                        disabled={
                            props.chargeType.name === 'Account' ? !props.chargeAccount :
                            props.chargeType.name === 'Employee' ? !props.chargeEmployee :
                            !props.chargeType
                        }
                    ><i className='fas fa-plus'></i> Add</Button>
                </Col>
                <Col md={2}>
                    <Button
                        variant='warning'
                        onClick={props.generateCharges}
                        disabled={props.readOnly || props.isPickupManifested || props.isDeliveryManifested || props.isInvoiced || props.charges.length > 1}
                    >Auto-price (BETA)</Button>
                </Col>
            </Row>
            <hr/>
            <Row>
                <Col md={2}>
                    <Card border='dark' style={{padding: '0px'}}>
                        <Card.Header><h4 className='text-muted'>Charges</h4></Card.Header>
                        <Card.Body style={{padding: '0px'}}>
                            {props.activeRatesheet &&
                                <ReactTabulator
                                    id='lineItemSource'
                                    columns={[
                                        {title: 'Name', field: 'name', headerSort: false},
                                        {title: 'Type', field: 'type', formatter: cell => lineItemTypeFormatter(cell.getValue()), hozAlign: 'center', width: 45, headerSort: false, visible: false}
                                    ]}
                                    data={props.activeRatesheet ? props.activeRatesheet.rates : []}
                                    options={{
                                        groupBy: 'type',
                                        groupHeader: (value, count, data, group) => lineItemTypeGroupFormatter(value, count, data, group),
                                        index: 'line_item_id',
                                        maxHeight: '700px',
                                        movableRows: true,
                                        movableRowsReceiver: false,
                                        movableRowsSender: true,
                                        movableRowsConnectedTables: ['#chargesDestination'],
                                        layout: 'fitColumns'
                                    }}
                                />
                            }
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={10}>
                    <Row>
                        {props.charges && props.charges.map((charge, index, charges) =>
                            <Col
                                style={{display: charge.toBeDeleted ? 'none' : ''}}
                                key={index + '.' + charge.name}
                                md={charges.reduce((counter, cur) => {return cur.toBeDeleted ? counter : ++counter}, 0) === 1 ? 12 : 
                                    charges.reduce((counter, cur) => {return cur.toBeDeleted ? counter : ++counter}, 0) === 2 ? 6 : 4}
                            >
                                <Card border='dark' style={{padding: '0px'}}>
                                    <Card.Header>
                                        <Row>
                                            <Col md={canChargeTableBeDeleted(charge) ? 10 : 11}>
                                                <h5 className='text-muted'>{chargeTypeFormatter(charge.chargeType)} {charge.name}</h5>
                                            </Col>
                                            {!props.readOnly &&
                                                <Col md={1}>
                                                    <Button variant='success' size='sm' onClick={() => payOffAll(index)}><i className='fas fa-hand-holding-usd' title='Mark all as paid'></i></Button>
                                                </Col>
                                            }
                                            {canChargeTableBeDeleted(charge) &&
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
                                                            onChange={handleChange}
                                                            readOnly={props.readOnly}
                                                            data-chargeindex={index}
                                                        />
                                                    </InputGroup>
                                                </Col>
                                            }
                                        </Row>
                                    </Card.Header>
                                    <Card.Body style={{padding: '0px'}}>
                                        <ReactTabulator
                                            ref={charge.tableRef}
                                            id={'chargesDestination'}
                                            columns={chargeTableColumns(charge.chargeType)}
                                            data={charge.lineItems}
                                            data-index={index}
                                            options={{
                                                cellEdited: cell => {
                                                    const field = cell.getField()
                                                    const row = cell.getRow()
                                                    const rowData = row.getData()
                                                    if(field === 'price' && (!rowData['driver_amount'] || cell.getOldValue() === rowData['driver_amount']))
                                                        row.update({driver_amount: cell.getValue()})
                                                },
                                                dataUpdated: props.chargeTableUpdated,
                                                groupBy: charge.chargeType.name === 'Account' ? 'invoice_id' : charge.chargeType.name === 'Employee' ? 'manifest_id' : 'paid',
                                                groupHeader: (value, count, data, group) => groupHeaderFormatter(value, count, data, group),
                                                initialFilter: [{field: 'toBeDeleted', type: '!=', value: true}],
                                                layout: 'fitColumns',
                                                movableRowsReceiver: 'add',
                                                rowAdded: props.chargeTableUpdated,
                                                rowDeleted: props.chargeTableUpdated,
                                                rowUpdated: props.chargeTableUpdated
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
                handleChanges={props.handleChanges}
                linkLineItemCell={props.linkLineItemCell}
                linkLineItemToType={props.linkLineItemToType}
                show={props.showLinkLineItemModal}
            />
        </Card>
    )
}
