import React, {useEffect, useRef, useState} from 'react'
import {Button, ButtonGroup, Card, Col, Dropdown, FormControl, InputGroup, Row} from 'react-bootstrap'
import {useHistory} from 'react-router-dom'
import {ReactTabulator} from 'react-tabulator'

import LinkLineItemModal from './modals/LinkLineItemModal'
// import PriceAdjustModal from './modals/PriceAdjustModal'
// import ReassignChargesModal from './ReassignChargesModal'

function canLineItemBeDeleted(row) {
    const rowData = row.getData()
    if(!rowData.invoice_id && !rowData.pickup_manifest_id && !rowData.delivery_manifest_id)
        return true
    return false
}

function canLineItemBeEdited(row) {
    const rowData = row.getData()
    if(!rowData.pickup_manifest_id && !rowData.delivery_manifest_id && !rowData.invoice_is_finalized)
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
        case 'Accounts Payable':
            return <i className='fas fa-funnel-dollar'></i>
    }
}

function deleteLineItem(cell) {
    const row = cell.getRow()
    const rowData = row.getData()
    if(!canLineItemBeDeleted(row)) {
        console.log(`Unable to delete line item\t${rowData.invoice_id}\t${rowData.pickup_manifest_id}\t${rowData.delivery_manifest_id}`)
        return
    }
    if(rowData.line_item_id != null) {
        row.update({toBeDeleted: true})
        row.getTable().refreshFilter()
    } else
        row.delete()
}

const groupBy = (data, charge) => {
    const value = charge.chargeType.name
    switch(value) {
        case 'Employee':
        case 'Accounts Payable':
            return 'manifest_id'
        default:
            return 'invoice_id'
    }
}

function groupHeaderFormatter(key, count, data, group) {
    const styledCount = '<span style="color:blue">(' + count + ')</span>'
    const value = data[0][key]
    if(key === 'invoice_id')
        return `${value ? `Invoice #${value}` : 'Not Yet Invoiced'}${styledCount}`
    else if(key === 'manifest_id')
        return `${value ? `Manifest #${value}` : 'Not Yet Manifested'}${styledCount}`
    return false
}

export default function Charge(props) {
    const [linkLineItemCell, setLinkLineItemCell] = useState('')
    const [linkLineItemToType, setLinkLineItemToType] = useState('')
    const [showDetails, setShowDetails] = useState(false)
    const [showLinkLineItemModal, setShowLinkLineItemModal] = useState(false)
    const [showPriceAdjustModal, setShowPriceAdjustModal] = useState(false)

    const tableRef = useRef()
    const history = useHistory()

    const {
        charge,
        chargeCount,
        drivers,
        index,
        lineItemTypeFormatter,
        readOnly
    } = props

    useEffect(() => {
        if(tableRef?.current?.table)
            tableRef.current.table.setGroupHeader(groupHeaderFormatter)
    }, [showDetails])

    const actionCellContextMenu = cell => {
        const data = cell.getRow().getData()
        var menuItems = readOnly ? [] : data.line_item_id ? [
            ... canLineItemBeDeleted(cell.getRow()) ? [
                {label: "<i class='fas fa-trash fa-sm'></i> Delete Line Item", action: (cell) => deleteLineItem(cell)}
            ] : [],
            ... data.invoice_is_finalized ? [] : data.invoice_id ? [
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
            {label: "<i class='fas fa-trash fa-sm'></i> Delete Line Item", action: (cell) => deleteLineItem(cell)}
        ]
        return menuItems
    }

    const actionCellContextMenuFormatter = cell => {
        return readOnly ? null : '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"></i></button>'
    }

    function canChargeTableBeDeleted(charge) {
        if(!charge || !!props.readOnly)
            return false
        return !charge.lineItems.some(lineItem => (lineItem.invoice_id || lineItem.pickup_manifest_id || lineItem.delivery_manifest_id) ? true : false)
    }

    const chargeTableColumns = chargeType => {
        return [
            {
                clickMenu: (event, cell) => actionCellContextMenu(cell),
                formatter: (cell) => actionCellContextMenuFormatter(cell),
                headerSort: false,
                hozAlign: 'center',
                print: false,
                width: 45
            },
            {title: 'Line Item ID', field: 'line_item_id', visible: showDetails},
            {title: 'Name', field: 'name', editor: 'input'},
            {title: 'Type', field: 'type', formatter: cell => lineItemTypeFormatter(cell.getValue()), headerSort: false, hozAlign: 'center', width: 45},
            {title: 'Price', field: 'price', ...moneyColumnStandardParams},
            {title: 'Driver Amount', field: 'driver_amount', ...moneyColumnStandardParams},
            ... chargeType.name === 'Account' ? [
                {title: 'Invoice ID', field: 'invoice_id', visible: showDetails, cellClick: (event, cell) => redirectToCellValue('invoices', cell)}
            ] : chargeType.name === 'Employee' ? [
                {title: 'Manifest ID', field: 'manifest_id', visible: showDetails, cellClick: (event, cell) => redirectToCellValue('manifests', cell)}
            ] : [],
            {title: 'Invoice Is Finalized', field: 'invoice_is_finalized', visible: showDetails, formatter: 'tickCross'},
            {
                editor:'select',
                editorParams: {
                    listItemFormatter: (value, title) => {
                        return title
                    },
                    values: drivers
                },
                field: 'pickup_driver_id',
                formatter: cell => {
                    const driver = drivers.find(driver => driver.value === cell.getValue())
                    return driver ? driver.label : null
                },
                title: 'Pickup Driver ID',
                visible: showDetails,
            },
            {title: 'Pickup Manifest ID', field: 'pickup_manifest_id', visible: showDetails, cellClick: (e, cell) => redirectToCellValue('manifests', cell)},
            {
                editor:'select',
                editorParams: {
                    listItemFormatter: (value, title) => {
                        return title
                    },
                    values: drivers
                },
                field: 'delivery_driver_id',
                formatter: cell => {
                    const driver = drivers.find(driver => driver.value === cell.getValue())
                    return driver ? driver.label : null
                },
                title: 'Delivery Driver ID',
                visible: showDetails,
            },
            {title: 'Delivery Manifest ID', field: 'delivery_manifest_id', visible: showDetails, cellClick: (e, cell) => redirectToCellValue('manifests', cell)},
            {title: 'Invoice ID', field: 'invoice_id', visible: showDetails, cellClick: (e, cell) => redirectToCellValue('invoices', cell)}
        ]
    }

    const deleteChargeTable = charge => {
        if(!canChargeTableBeDeleted(charge)) {
            const errorMessage = 'ERROR - charge table cannot be deleted - at least one item has been invoiced or manifested'
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

    const invoiceAsOneOff = () => {
        if(charge.chargeType.type != 'prepaid') {
            toastr.error('Unable to invoice non-prepaid type as one-off call. Aborting')
            return
        }

        if(!charge?.lineItems) {
            toastr.error('Unable to invoice as a one-off call. Charge has no line items')
            return
        }

        if(charge.lineItems.find(lineItem => lineItem.line_item_id == null)) {
            taostr.warning('Selected charge has unsaved line items. Please save the bill then try again')
            return
        }

        makeAjaxRequest('/invoices/createFromCharge', 'POST', {charge_id: charge.charge_id}, response => {
            response = JSON.parse(response)
            toastr.success(`Successfully created invoice I${response.invoice_id}`, 'Success')
        })
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
        editorParams: {
            onWheel: (e) => e.target.blur(),
            selectContents: true,
            step: 0.01,
            verticalNavigation: 'table',
        },
        formatter: 'money',
        formatterParams: {thousand: ',', symbol: '$', selectContents: true},
        hozAlign: 'right',
        sorter: 'number',
        topCalc: 'sum',
        topCalcParams: {precision: 2},
        topCalcFormatter: 'money',
        topCalcFormatterParams: {thousand: ',', symbol: '$'},
        editable: cell => {
            return props.readOnly ? false : canLineItemBeEdited(cell.getRow())
        }
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

    return (
        <Col
            style={{display: charge.toBeDeleted ? 'none' : ''}}
            key={`${index}.${charge.name}`}
            md={(chargeCount > 1 && !showDetails) ? 6 : 12}
        >
            <Card border='dark'>
                <Card.Header style={{backgroundColor: charge.name == 'Accounts Payable' ? 'darksalmon' : null}}>
                    <Row>
                        <Col md={8}>
                            <h5 className='text-muted'>{chargeTypeFormatter(charge.chargeType)} {charge.name}</h5>
                        </Col>
                        <Col md={2}>
                            <Button
                                variant='warning'
                                onClick={() => props.generateCharges(index)}
                                disabled={props.readOnly}
                            >Auto-price (BETA)</Button>
                        </Col>
                        <Col md={2}>
                            <Dropdown
                                align='end'
                                as={ButtonGroup}
                                size='sm'
                                style={{float: 'right'}}
                            >
                                <Button
                                    onClick={() => setShowDetails(!showDetails)}
                                    variant='secondary'
                                >
                                    <i className={`fas fa-${showDetails ? 'compress' : 'expand'}-alt`}></i> Toggle Details
                                </Button>
                                <Dropdown.Toggle variant='secondary' id={`amendment-dropdown-${charge.charge_id}`}>
                                    <Dropdown.Menu>
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
                                        {(charge.chargeType.type == 'prepaid' && charge.lineItems?.find(lineItem => lineItem.invoice_id == null)) ?
                                            <Dropdown.Item onClick={invoiceAsOneOff}>
                                                <i className='fas fa-file-invoice-dollar fa-lg'></i> Invoice as One-off
                                            </Dropdown.Item> : null
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
                        events={{
                            cellEdited: cell => {
                                const field = cell.getField()
                                const row = cell.getRow()
                                const rowData = row.getData()
                                if(field === 'price' && (!rowData['driver_amount'] || cell.getOldValue() === rowData['driver_amount']))
                                    row.update({driver_amount: cell.getValue()})
                                props.chargeDispatch({'type': 'UPDATE_LINE_ITEMS', 'payload': {'data': row.getTable().getData(), index}})
                            },
                            rowAdded: row => {
                                row.getTable().setGroupBy(data => groupBy(data, charge))
                                const data = row.getTable().getData()
                                props.chargeDispatch({type: 'UPDATE_LINE_ITEMS', payload: {data, index}})
                                props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
                            },
                            rowDeleted: row => {
                                props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
                                const data = row.getTable().getData()
                                props.chargeDispatch({type: 'UPDATE_LINE_ITEMS', payload: {data, index}})
                            }
                        }}
                        id='lineItemDestination'
                        options={{
                            groupBy: data => groupBy(data, charge),
                            groupHeader: (value, count, data, group) => groupHeaderFormatter(value, count, data, group),
                            initialFilter: [{field: 'toBeDeleted', type: '!=', value: true}],
                            layout: 'fitColumns',
                            movableRows: true,
                            movableRowsConnectedTables: ['#lineItemSource'],
                            movableRowsReceiver: 'add',
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
                {/* {showPriceAdjustModal &&
                    <PriceAdjustModal
                        charge={charge}
                        delivery={props.delivery}
                        hide={hidePriceAdjustModal}
                        pickup={props.pickup}
                        show={showPriceAdjustModal}
                        tableRef={tableRef}
                    />
                } */}
            </Card>
        </Col>
    )
}

