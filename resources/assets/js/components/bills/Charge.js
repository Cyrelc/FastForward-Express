import React, {useEffect, useRef, useState} from 'react'
import {Button, ButtonGroup, Card, Col, Dropdown, FormControl, InputGroup, Row} from 'react-bootstrap'
import {useHistory} from 'react-router-dom'
import {TabulatorFull as Tabulator} from 'tabulator-tables'
import {toast} from 'react-toastify'

import LinkLineItemModal from './modals/LinkLineItemModal'
import {useAPI} from '../../contexts/APIContext'
// import PriceAdjustModal from './modals/PriceAdjustModal'
// import ReassignChargesModal from './ReassignChargesModal'

function canLineItemBeDeleted(row) {
    const rowData = row.getData()
    if(!rowData.invoice_id && !rowData.pickup_manifest_id && !rowData.delivery_manifest_id && !rowData.chargeback_id)
        return true
    return false
}

function canLineItemBeEdited(row) {
    const rowData = row.getData()
    // A charged back line item has been (or is being) deducted from an employee's pay at its current price
    if(!rowData.pickup_manifest_id && !rowData.delivery_manifest_id && !rowData.invoice_is_finalized && !rowData.chargeback_id)
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
        console.log(`Unable to delete line item\t${rowData.invoice_id}\t${rowData.pickup_manifest_id}\t${rowData.delivery_manifest_id}\t${rowData.chargeback_id}`)
        return
    }
    if(rowData.line_item_id != null) {
        row.update({toBeDeleted: true})
        row.getTable().refreshFilter()
    } else
        row.delete()
}

// The group "key" names the field the header reports on; every row in a charge shares the one group
const groupBy = (data, charge) => {
    const value = charge.chargeType.name
    switch(value) {
        case 'Employee':
            return 'chargeback_id'
        case 'Accounts Payable':
            return 'accounts_payable'
        default:
            return 'invoice_id'
    }
}

/**
 * Employee charges settle by chargeback: the line item links to the chargeback schedule, and each deduction is
 * recorded against a manifest (chargeback_manifest_ids, from the backend).
 */
function chargebackStatus(row) {
    if(!row.chargeback_id)
        return 'Not Yet Charged Back'
    if(row.chargeback_continuous || row.chargeback_count_remaining > 0)
        return `Chargeback #${row.chargeback_id} - Pending Deduction`
    if(row.chargeback_manifest_ids) {
        const manifestIds = String(row.chargeback_manifest_ids).split(',')
        return `Charged Back on Manifest${manifestIds.length > 1 ? 's' : ''} ${manifestIds.map(id => `#${parseInt(id)}`).join(', ')}`
    }
    return `Chargeback #${row.chargeback_id} - Deducted`
}

function groupHeaderFormatter(key, count, data, group) {
    const styledCount = '<span style="color:blue">(' + count + ')</span>'
    const value = data[0][key]
    if(key === 'invoice_id')
        return `${value ? `Invoice #${value}` : 'Not Yet Invoiced'}${styledCount}`
    else if(key === 'chargeback_id')
        return `${chargebackStatus(data[0])}${styledCount}`
    else if(key === 'accounts_payable')
        // Reported by date range on the Accounts Payable page; there is no settled state to show per bill
        return `Accounts Payable${styledCount}`
    return false
}

export default function Charge(props) {
    const [isTableReady, setIsTableReady] = useState(false)
    const [linkLineItemCell, setLinkLineItemCell] = useState('')
    const [linkLineItemToType, setLinkLineItemToType] = useState('')
    const [showDetails, setShowDetails] = useState(false)
    const [showLinkLineItemModal, setShowLinkLineItemModal] = useState(false)
    const [showPriceAdjustModal, setShowPriceAdjustModal] = useState(false)

    const api = useAPI()
    const tableRef = useRef(null)
    const tabulatorRef = useRef(null)
    const tableReadyRef = useRef(false)
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
        if (tabulatorRef.current)
            return
        if (!tableRef.current)
            return

        const newTabulator = new Tabulator(tableRef.current, {
            columns: chargeTableColumns(charge.chargeType),
            data: charge.lineItems,
            groupBy: (data) => groupBy(data, charge),
            groupHeader: groupHeaderFormatter,
            initialFilter: [{field: 'toBeDeleted', type: '!=', value: true}],
            layout: 'fitColumns',
            movableRows: true,
            movableRowsConnectedTables: ['#lineItemSource'],
            movableRowsReceiver: 'add'
        })

        newTabulator.on('cellEdited', cell => {
            const field = cell.getField()
            const row = cell.getRow()
            const rowData = row.getData()
            if(field === 'price' && (!rowData['driver_amount'] || cell.getOldValue() === rowData['driver_amount']))
                row.update({driver_amount: cell.getValue()})
            props.chargeDispatch({'type': 'UPDATE_LINE_ITEMS', 'payload': {'data': row.getTable().getData(), index}})
        })

        newTabulator.on('rowAdded', row => {
            row.getTable().setGroupBy(data => groupBy(data, charge))
            const data = row.getTable().getData()
            props.chargeDispatch({type: 'UPDATE_LINE_ITEMS', payload: {data, index}})
            props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
        })

        newTabulator.on('rowDeleted', row => {
            props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
            const data = row.getTable().getData()
            props.chargeDispatch({type: 'UPDATE_LINE_ITEMS', payload: {data, index}})
        })

        newTabulator.on('tableBuilt', () => {
            tableReadyRef.current = true
            setIsTableReady(true)
        })

        tabulatorRef.current = newTabulator;
    })

    useEffect(() => {
        if(!tabulatorRef.current)
            return
        if(!tableReadyRef.current)
            return

        tabulatorRef.current.replaceData(charge.lineItems)
    }, [charge.lineItems, isTableReady])

    // The column definitions close over showDetails, drivers and readOnly, so rebuild them whenever any of those change
    useEffect(() => {
        if(!tabulatorRef.current)
            return
        if(!tableReadyRef.current)
            return

        tabulatorRef.current.setColumns(chargeTableColumns(charge.chargeType))
        tabulatorRef.current.setGroupHeader(groupHeaderFormatter)
    }, [showDetails, drivers, readOnly, isTableReady])

    const actionCellContextMenu = cell => {
        const data = cell.getRow().getData()
        var menuItems = readOnly ? [] : data.line_item_id ? [
            ... canLineItemBeDeleted(cell.getRow()) ? [
                {label: "<i class='fas fa-trash fa-sm'></i> Delete Line Item", action: (event, cell) => deleteLineItem(cell)}
            ] : [],
            ... data.invoice_is_finalized ? [] : data.invoice_id ? [
                {label: `<i class="fas fa-unlink"></i> Remove Invoice Link (ID: ${data.invoice_id})`, action: () => removeLink(cell, 'Invoice'), disabled: data.finalized},
            ] : [
                {label: '<i class="fas fa-link"></i> Link To Invoice', action: (event, cell) => linkTo(cell, 'Invoice')}
            ],
            ... data.pickup_manifest_id ? [
                {label: `<i class="fas fa-unlink"></i> Remove Pickup Manifest Link (ID: ${data.pickup_manifest_id})`, action: () => removeLink(cell, 'Pickup Manifest')},
            ] : [
                {label: '<i class="fas fa-link"></i> Link To Pickup Manifest', action: (event, cell) => linkTo(cell, 'Pickup Manifest')}
            ],
            ... data.delivery_manifest_id ? [
                {label: `<i class="fas fa-unlink"></i> Remove Delivery Manifest Link (ID: ${data.delivery_manifest_id})`, action: () => removeLink(cell, 'Delivery Manifest')}
            ] : [
                {label: '<i class="fas fa-link"></i> Link To Delivery Manifest', action: (event, cell) => linkTo(cell, 'Delivery Manifest')}
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
        return !charge.lineItems.some(lineItem => (lineItem.invoice_id || lineItem.pickup_manifest_id || lineItem.delivery_manifest_id || lineItem.chargeback_id) ? true : false)
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
                editor: 'list',
                editorParams: {
                    itemFormatter: label => escapeHtml(label),
                    values: drivers
                },
                editable: cell => !readOnly && !cell.getRow().getData().pickup_manifest_id,
                field: 'pickup_driver_id',
                formatter: cell => {
                    const driver = drivers.find(driver => driver.value === cell.getValue())
                    return driver ? escapeHtml(driver.label) : null
                },
                title: 'Pickup Driver ID',
                visible: showDetails,
            },
            {title: 'Pickup Manifest ID', field: 'pickup_manifest_id', visible: showDetails, cellClick: (e, cell) => redirectToCellValue('manifests', cell)},
            {
                editor: 'list',
                editorParams: {
                    itemFormatter: label => escapeHtml(label),
                    values: drivers
                },
                editable: cell => !readOnly && !cell.getRow().getData().delivery_manifest_id,
                field: 'delivery_driver_id',
                formatter: cell => {
                    const driver = drivers.find(driver => driver.value === cell.getValue())
                    return driver ? escapeHtml(driver.label) : null
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
            const errorMessage = 'ERROR - charge table cannot be deleted - at least one item has been invoiced, manifested or charged back'
            toast.error(errorMessage)
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
            toast.error('Unable to invoice non-prepaid type as one-off call. Aborting')
            return
        }

        if(!charge?.lineItems) {
            toast.error('Unable to invoice as a one-off call. Charge has no line items')
            return
        }

        if(charge.lineItems.find(lineItem => lineItem.line_item_id == null)) {
            toast.warn('Selected charge has unsaved line items. Please save the bill then try again')
            return
        }

        api.post('/invoices/createFromCharge', {charge_id: charge.charge_id})
            .then(response => {
                toast.success(`Successfully created invoice I${response.invoice_id}`)
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
        history.push(`/${path}/${value}`)
    }

    function removeLink(cell, type) {
        const data = {
            action: 'remove_link',
            line_item_id: cell.getRow().getData()['line_item_id'],
            link_type: type
        }
        api.post('/bills/manageLineItemLinks', data)
            .then(response => {
                cell.getRow().update(response)
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
                    <div ref={tableRef} id='lineItemDestination'></div>
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

