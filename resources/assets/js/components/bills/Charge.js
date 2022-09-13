import React, {useEffect, useRef, useState} from 'react'
import {Button, ButtonGroup, Card, Col, Dropdown, FormControl, InputGroup, Row} from 'react-bootstrap'
import {useHistory} from 'react-router-dom'
import {ReactTabulator} from 'react-tabulator'

import LinkLineItemModal from './modals/LinkLineItemModal'
import PriceAdjustModal from './modals/PriceAdjustModal'
// import ReassignChargesModal from './ReassignChargesModal'

function canLineItemBeDeleted(row) {
    const rowData = row.getData()
    if(!rowData.invoice_id && !rowData.pickup_manifest_id && !rowData.delivery_manifest_id && !rowData.paid)
        return true
    return false
}

function canLineItemBeEdited(row) {
    const rowData = row.getData()
    if(!rowData.pickup_manifest_id && !rowData.delivery_manifest_id && !rowData.paid && !rowData.invoice_is_finalized)
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

export default function Charge(props) {
    const [linkLineItemCell, setLinkLineItemCell] = useState('')
    const [linkLineItemToType, setLinkLineItemToType] = useState('')
    const [showLinkLineItemModal, setShowLinkLineItemModal] = useState(false)
    const [showPriceAdjustModal, setShowPriceAdjustModal] = useState(false)

    const tableRef = useRef()
    const history = useHistory()

    const {
        charge,
        charges,
        index,
        lineItemTypeFormatter,
        readOnly
    } = props

    useEffect(() => {
        console.log('trigger redraw')
        console.log(tableRef.current.table)
        tableRef?.current?.table?.redraw()
    }, [charges.length])

    const actionCellContextMenu = cell => {
        const data = cell.getRow().getData()
        var menuItems = readOnly ? [] : data.line_item_id ? [
            ... canLineItemBeDeleted(cell.getRow()) ? [
                {label: "<i class='fas fa-trash fa-sm'></i> Delete Line Item", action: (event, cell) => deleteLineItem(cell)}
            ] : [],
            ... data.invoice_id ? [
                {label: `<i class="fas fa-unlink"></i> Remove Invoice Link (ID: ${data.invoice_id})`, action: () => removeLink(cell, 'Invoice'), disabled: data.finalized},
            ] : [
                {label: '<i class="fas fa-link"></i> Link To Invoice', action: (e, cell) => linkTo(cell, 'Invoice')}
            ],
            ... data.pickup_manifest_id ? [
                {label: `<i class="fas fa-unlink"></i> Remove Pickup Manifest Link (ID: ${data.pickup_manifest_id})`, action: () => removeLink(cell, 'Pickup Manifest')},
            ] : [
                {label: '<i class="fas fa-link"></i> Link To Pickup Manifest', action: (e, cell) => linkTo(cell, 'Pickup Manifest')}
            ],
            ... data.delivery_manifest_id ? [
                {label: `<i class="fas fa-unlink"></i> Remove Delivery Manifest Link (ID: ${data.delivery_manifest_id})`, action: () => removeLink(cell, 'Delivery Manifest')}
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
            {title: 'Line Item ID', field: 'line_item_id', visible: false},
            {title: 'Name', field: 'name'},
            {title: 'Type', field: 'type', formatter: cell => lineItemTypeFormatter(cell.getValue()), headerSort: false, hozAlign: 'center', width: 45},
            {title: 'Price', field: 'price', ...moneyColumnStandardParams},
            {title: 'Driver Amount', field: 'driver_amount', ...moneyColumnStandardParams},
            ... chargeType.name === 'Account' ? [{title: 'Invoice ID', field: 'invoice_id', visible: false}] : chargeType.name === 'Employee' ? [{title: 'Manifest ID', field: 'manifest_id', visible: false}] : [],
            {title: 'Paid?', field: 'paid', formatter: 'tickCross', cellClick: (e, cell) => {cell.setValue(!cell.getValue())}, width: 45, hozAlign: 'center', headerSort: false},
            {title: 'Invoice ID', field: 'invoice_id', visible: false, cellClick: (e, cell) => {redirectToCellValue('invoices', cell)}},
            {title: 'Invoice Is Finalized', field: 'invoice_is_finalized', visible: false, formatter: 'tickCross'},
            {title: 'Pickup Driver ID', field: 'pickup_driver_id', visible: false, cellClick: (e, cell) => redirectToCellValue('employees', cell)},
            {title: 'Pickup Manifest ID', field: 'pickup_manifest_id', visible: false, cellClick: (e, cell) => redirectToCellValue('manifests', cell)},
            {title: 'Delivery Driver ID', field: 'delivery_driver_id', visible: false, cellClick: (e, cell) => redirectToCellValue('employees', cell)},
            {title: 'Delivery Manifest ID', field: 'delivery_manifest_id', visible: false, cellClick: (e, cell) => redirectToCellValue('manifests', cell)},
        ]
    }

    const deleteChargeTable = charge => {
        if(!canChargeTableBeDeleted(charge)) {
            const errorMessage = 'ERROR - charge table cannot be deleted - at least one item has been invoiced, manifested, or paid'
            toastr.error(errorMessage)
            console.log(errorMessage)
            return
        }
        if(confirm('Are you sure you wish to delete this charge group?\n This action can not be undone')) {
            props.chargeDispatch({type: 'DELETE_CHARGE_TABLE', payload: index})
        }
    }

    const hidePriceAdjustModal = () => {
        setShowPriceAdjustModal(false)
    }

    const hideLinkTo = () => {
        setLinkLineItemCell(null)
        setLinkLineItemToType(null)
        setShowLinkLineItemModal(false)
        props.chargeDispatch({type: 'CHECK_INVOICES_AND_MANIFESTS'})
    }

    const linkTo = (cell, type) => {
        setLinkLineItemCell(cell)
        setLinkLineItemToType(type)
        setShowLinkLineItemModal(true)
    }

    const makePriceAdjustment = (charge, index) => {
        setShowPriceAdjustModal(true)
    }

    const moneyColumnStandardParams = {
        editor:'number',
        formatter: 'money',
        formatterParams: {thousand: ',', symbol: '$', selectContents: true},
        editorParams: {step: 0.01, verticalNavigation: 'table', onWheel: (e) => e.target.blur()},
        hozAlign: 'right',
        topCalc: 'sum',
        topCalcParams: {precision: 2},
        topCalcFormatter: 'money',
        topCalcFormatterParams: {thousand: ',', symbol: '$'},
        sorter: 'number',
        editable: cell => {
            return props.readOnly ? false : canLineItemBeEdited(cell.getRow())
        }
    }

    function payOffAllLineItems(charge) {
        const table = tableRef.current.table
        const rows = table.getRows()
        rows.forEach(row => row.update({paid: true}))
    }

    const redirectToCellValue = (path, cell) => {
        const value = cell.getValue()
        if(!value)
            return
        history.push(`/app/${path}/${value}`)
    }

    function removeLink(cell, type) {
        const data = {
            action: 'remove_link',
            line_item_id: cell.getRow().getData()['line_item_id'],
            link_type: type
        }
        makeAjaxRequest('/bills/manageLineItemLinks', 'POST', data, response => {
            cell.getRow().update(JSON.parse(response))
        })
    }

    const toggleDetails = () => {
        tableRef.current.table.toggleColumn('line_item_id')
        tableRef.current.table.toggleColumn('invoice_id')
        tableRef.current.table.toggleColumn('invoice_is_finalized')
        tableRef.current.table.toggleColumn('pickup_driver_id')
        tableRef.current.table.toggleColumn('pickup_manifest_id')
        tableRef.current.table.toggleColumn('delivery_driver_id')
        tableRef.current.table.toggleColumn('delivery_manifest_id')
        tableRef.current.table.setGroupHeader(groupHeaderFormatter)
        tableRef.current.table.redraw()
    }

    return (
        <Card border='dark'>
            <Card.Header>
                <Row>
                    <Col md={9}>
                        <h5 className='text-muted'>{chargeTypeFormatter(charge.chargeType)} {charge.name}</h5>
                    </Col>
                    <Col md={3}>
                        <Dropdown
                            align='end'
                            as={ButtonGroup}
                            size='sm'
                            style={{float: 'right'}}
                        >
                            <Button
                                onClick={toggleDetails}
                                variant='secondary'
                            >Toggle Details</Button>
                            <Dropdown.Toggle variant='secondary' id={`amendment-dropdown-${charge.charge_id}`}>
                                <Dropdown.Menu>
                                    <Dropdown.Item onClick={() => payOffAllLineItems(charge)}>
                                        <i className='fas fa-tags'></i> Mark as Paid
                                    </Dropdown.Item>
                                    {/* <Dropdown.Item onClick={() => reassignDriver(charge)}>
                                        <i className='fas fa-exchange-alt'></i> Reassign Driver
                                    </Dropdown.Item> */}
                                    <Dropdown.Item onClick={() => makePriceAdjustment(charge, index)}>
                                        <i className='fas fa-eraser'></i> Price Adjustment
                                    </Dropdown.Item>
                                    {/* <Dropdown.Item onClick={() => reassignCharge(charge)}>
                                        <i className='fas fa-exchange-alt'></i> Reassign Charge
                                    </Dropdown.Item>} */}
                                    {(charge.lineItems && canChargeTableBeDeleted(charge)) &&
                                        <Dropdown.Item onClick={() => deleteChargeTable(charge)}><i className='fas fa-trash fa-sm'></i> Delete</Dropdown.Item>
                                    }
                                </Dropdown.Menu>
                            </Dropdown.Toggle>
                        </Dropdown>
                    </Col>
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
            <Card.Body>
                <ReactTabulator
                    ref={tableRef}
                    columns={chargeTableColumns(charge.chargeType)}
                    data={charge.lineItems}
                    id='lineItemDestination'
                    options={{
                        cellEdited: cell => {
                            const field = cell.getField()
                            const row = cell.getRow()
                            const rowData = row.getData()
                            if(field === 'price' && (!rowData['driver_amount'] || cell.getOldValue() === rowData['driver_amount']))
                                row.update({driver_amount: cell.getValue()})
                            props.chargeDispatch({'type': 'UPDATE_LINE_ITEMS', 'payload': {'data': row.getTable().getData(), index}})
                        },
                        groupBy: data => groupBy(data, charge),
                        groupHeader: (value, count, data, group) => groupHeaderFormatter(value, count, data, group),
                        initialFilter: [{field: 'toBeDeleted', type: '!=', value: true}],
                        layout: 'fitColumns',
                        movableRows: true,
                        movableRowsConnectedTables: ['#lineItemSource'],
                        movableRowsReceiver: 'add',
                        rowAdded: row => {
                            row.getTable().setGroupBy(data => groupBy(data, charge))
                            const data = row.getTable().getData()
                            props.chargeDispatch({type: 'UPDATE_LINE_ITEMS', 'payload': {data, index}})
                            props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
                        },
                        rowDeleted: row => {
                            props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
                            const data = row.getTable().getData()
                            props.chargeDispatch({type: 'UPDATE_LINE_ITEMS', 'payload': {data, index}})
                        }
                    }}
                />
            </Card.Body>
            {showLinkLineItemModal &&
                <LinkLineItemModal
                    hide={hideLinkTo}
                    linkLineItemCell={linkLineItemCell}
                    linkLineItemToType={linkLineItemToType}
                    show={showLinkLineItemModal}
                />
            }
            {showPriceAdjustModal &&
                <PriceAdjustModal
                    charge={charge}
                    delivery={props.delivery}
                    hide={hidePriceAdjustModal}
                    pickup={props.pickup}
                    show={showPriceAdjustModal}
                    tableRef={tableRef}
                />
            }
        </Card>
    )
}

