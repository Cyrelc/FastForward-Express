import React, {Fragment, useEffect, useMemo, useState} from 'react'
import {Button, ButtonGroup, Card, Col, Dropdown, FormControl, InputGroup, Row} from 'react-bootstrap'
import {useHistory} from 'react-router-dom'
// import {TabulatorFull as Tabulator} from 'tabulator-tables'
import {toast} from 'react-toastify'
import {InputLabel, ListSubheader, Menu, MenuItem, Select} from '@mui/material'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'

import {CurrencyCellRenderer} from '../../utils/table_cell_renderers'
import CurrencyEditor from '../partials/Table/CurrencyEditor'
import LinkLineItemModal from './modals/LinkLineItemModal'
import {useAPI} from '../../contexts/APIContext'
import {useLists} from '../../contexts/ListsContext'
// import PriceAdjustModal from './modals/PriceAdjustModal'
// import ReassignChargesModal from './ReassignChargesModal'

const canLineItemBeDeleted = row => {
    const data = row.original
    if(!data.invoice_id && !data.pickup_manifest_id && !data.delivery_manifest_id)
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

const LineItemTypeRenderer = ({row}) => {
    switch(row.original.type) {
        case 'commonRate':
            return <i className='fas fa-infinity fa-lg' title='Common'></i>
        case 'distanceRate':
            return <i className='fas fa-route fa-lg' title='Distance'></i>
        case 'legacyRate':
            return <i className='fab fa-fort-awesome-alt fa-lg' title='Legacy'></i>
        case 'miscellaneousRate':
            return <i className='fas fa-comment-dollar fa-lg' title='Miscellaneous'></i>
        case 'timeRate':
            return <i className='fas fa-clock fa-lg' title='Time'></i>
        case 'weightRate':
            return <i className='fas fa-weight fa-lg' title='Weight'></i>
        case 'conditionalRate':
            return <i className='fas fa-code-branch' title='Conditional'></i>
        default:
            return <i className='fas fa-exclamation-triangle fa-lg'></i>
    }
}

// function groupHeaderFormatter(key, count, data, group) {
//     const styledCount = '<span style="color:blue">(' + count + ')</span>'
//     const value = data[0][key]
//     if(key === 'invoice_id')
//         return `${value ? `Invoice #${value}` : 'Not Yet Invoiced'}${styledCount}`
//     else if(key === 'manifest_id')
//         return `${value ? `Manifest #${value}` : 'Not Yet Manifested'}${styledCount}`
//     return false
// }

export default function Charge(props) {
    const [linkLineItemCell, setLinkLineItemCell] = useState('')
    const [linkLineItemToType, setLinkLineItemToType] = useState('')
    const [showDetails, setShowDetails] = useState(false)
    const [showLinkLineItemModal, setShowLinkLineItemModal] = useState(false)
    const [showPriceAdjustModal, setShowPriceAdjustModal] = useState(false)

    const api = useAPI()
    const {employees} = useLists()

    const drivers = employees.filter(employee => {
        if(true)
            return true
    })

    const {
        charge,
        chargeCount,
        index,
        rateTable,
        readOnly
    } = props

    const {lineItems} = charge

    const ActionCellContextMenu = ({row}) => {
        const [anchorElement, setAnchorElement] = useState(null)
        const isOpen = Boolean(anchorElement)

        const data = row.original

        const handleClick = event => {
            if(anchorElement)
                handleClose()
            else
                setAnchorElement(event.currentTarget)
        }

        const handleClose = event => {
            setAnchorElement(null)
        }

        if(readOnly) {
            return <div></div>
        }
        if(data.line_item_id) {
            return (
                <div>
                    <Button onClick={handleClick}><i className='fas fa-bars'></i></Button>
                    <Menu open={isOpen} onClose={handleClose} anchorEl={anchorElement}>
                        {canLineItemBeDeleted(row) &&
                            <MenuItem onClick={() => deleteLineItem(row)}><i className='fas fa-trash'></i> Delete Line Item</MenuItem>
                        }
                        {!data.invoice_is_finalized && data.invoice_id ?
                            <MenuItem onClick={() => removeLink(row, 'Invoice')} disabled={data.finalized}><i className='fas fa-unlink'></i>Remove Invoice Link (ID: {data.invoice_id})</MenuItem>
                            :
                            <MenuItem onClick={() => linkTo(row, 'Invoice')}><i className='fas fa-link'></i>{`\t`}Link To Invoice</MenuItem>
                        }
                        {data.pickup_manifest_id ?
                            <MenuItem onClick={() => removeLink(row, 'Pickup Manifest')}><i className='fas fa-unlink'></i> Remove Pickup Manifest Link (ID: {data.pickup_manifest_id})</MenuItem>
                            :
                            <MenuItem onClick={() => linkTo(row, 'Pickup Manifest')}><i className='fas fa-link'></i> Link to Pickup Manifest</MenuItem>
                        }
                        {data.delivery_manifest_id ?
                            <MenuItem onClick={() => removeLink(row, 'Delivery Manifest')}><i className='fas fa-unlink'></i> Remove Delivery Manifest Link (ID: {data.delivery_manifest_id})</MenuItem>
                            :
                            <MenuItem onClick={() => linkTo(row, 'Delivery Manifest')}><i className='fas fa-link'></i> Link to Delivery Manifest</MenuItem>
                        }
                    </Menu>
                </div>
            )
        }

        return (
            <Menu open={isOpen} onClose={handleClose} anchorEl={anchorElement}>
                <MenuItem onClick={() => deleteLineItem(row)}><i className='fas fa-trash'></i> Delete Line Item</MenuItem>
            </Menu>
        )
    }

    const columns = useMemo(() => {
        return [
            {
                enableColumnActions: false,
                id: 'test',
                Cell: ({row}) => <ActionCellContextMenu row={row} />,
                Header: <div></div>,
                size: 45,
                enableEditing: false,
            },
            {header: 'Line Item ID', accessorKey: 'line_item_id', enableEditing: false},
            {header: 'Type', accessorKey: 'type', Cell: LineItemTypeRenderer, enableColumnActions: false, size: 45},
            {header: 'Name', accessorKey: 'name'},
            {
                header: 'Price',
                accessorKey: 'price',
                editVariant: 'custom',
                Cell: CurrencyCellRenderer,
                Edit: ({row, setData}) => <CurrencyEditor row={row} setData={setData} data={charge.lineItems} />
            },
            {header: 'Driver Amount', accessorKey: 'driver_amount', Cell: CurrencyCellRenderer},
            // ...charge.chargeType.name === 'Account' ? [
            //     {header: 'Invoice ID', accessorKey: 'invoice_id', Cell: ({renderedCellValue, row}) => <LinkCellRenderer renderedCellValue={renderedCallValue} row={row} urlPrefix='/invoices/' />}
            // ] : charge.chargeType.name === 'Employee' ? [
            //     {header: 'Manifest ID', accessorKey: 'manifest_id', Cell: ({renderedCellValue, row}) => <LinkCellRenderer renderedCellValue={renderedCallValue} row={row} urlPrefix='/manifests/' />}
            // ] : [],
            // {header: 'Invoice Is Finalized', accessorKey: 'invoice_is_finalized'},
            // {
            //     editor:'select',
            //     editorParams: {
            //         listItemFormatter: (value, title) => {
            //             return title
            //         },
            //         values: drivers
            //     },
            //     field: 'pickup_driver_id',
            //     formatter: cell => {
            //         const driver = drivers.find(driver => driver.value === cell.getValue())
            //         return driver ? driver.label : null
            //     },
            //     title: 'Pickup Driver ID',
            //     visible: showDetails,
            // },
            // {title: 'Pickup Manifest ID', field: 'pickup_manifest_id', visible: showDetails, cellClick: (e, cell) => redirectToCellValue('manifests', cell)},
            // {
            //     editor:'select',
            //     editorParams: {
            //         listItemFormatter: (value, title) => {
            //             return title
            //         },
            //         values: drivers
            //     },
            //     field: 'delivery_driver_id',
            //     formatter: cell => {
            //         const driver = drivers.find(driver => driver.value === cell.getValue())
            //         return driver ? driver.label : null
            //     },
            //     title: 'Delivery Driver ID',
            //     visible: showDetails,
            // },
            // {title: 'Delivery Manifest ID', field: 'delivery_manifest_id', visible: showDetails, cellClick: (e, cell) => redirectToCellValue('manifests', cell)},
            // {title: 'Invoice ID', field: 'invoice_id', visible: showDetails, cellClick: (e, cell) => redirectToCellValue('invoices', cell)}
        ]
    }, [charge.chargeType])

    const table = useMaterialReactTable({
        columns,
        data: charge.lineItems,
        enableEditing: true,
        editDisplayMode: 'cell',
        initialState: {
            density: 'compact',
            columnVisibility: {
                manifest_id: false,
                invoice_id: false
            }
        }
    })
    // useEffect(() => {
    //     if(!table && tableRef.current) {
    //         const newTabulator = new Tabulator(tableRef.current, {
    //             columns: chargeTableColumns(charge.chargeType),
    //             data: charge.lineItems,
    //             groupBy: (data) => groupBy(data, charge),
    //             groupHeader: groupHeaderFormatter,
    //             initialFilter: [{field: 'toBeDeleted', type: '!=', value: true}],
    //             layout: 'fitColumns',
    //             movableRows: true,
    //             movableRowsConnectedTables: ['#lineItemSource'],
    //             movableRowsReceiver: 'add'
    //         })

    //         newTabulator.on('cellEdited', cell => {
    //             const field = cell.getField()
    //             const row = cell.getRow()
    //             const rowData = row.getData()
    //             if(field === 'price' && (!rowData['driver_amount'] || cell.getOldValue() === rowData['driver_amount']))
    //                 row.update({driver_amount: cell.getValue()})
    //             props.chargeDispatch({'type': 'UPDATE_LINE_ITEMS', 'payload': {'data': row.getTable().getData(), index}})
    //         })

    //         newTabulator.on('rowAdded', row => {
    //             row.getTable().setGroupBy(data => groupBy(data, charge))
    //             const data = row.getTable().getData()
    //             props.chargeDispatch({type: 'UPDATE_LINE_ITEMS', payload: {data, index}})
    //             props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
    //         })

    //         newTabulator.on('rowDeleted', row => {
    //             props.chargeDispatch({type: 'CHECK_FOR_INTERLINER'})
    //             const data = row.getTable().getData()
    //             props.chargeDispatch({type: 'UPDATE_LINE_ITEMS', payload: {data, index}})
    //         })

    //         setTable(newTabulator)
    //     }
    // })

    // useEffect(() => {
    //     if(table)
    //         table.setData(charge.lineItems)
    // }, [charge.lineItems])

    // useEffect(() => {
    //     if(table) {
    //         table.setColumns(chargeTableColumns(charge.chargeType))
    //         table.setGroupHeader(groupHeaderFormatter)
    //     }
    // }, [showDetails])

    // const actionCellContextMenuFormatter = cell => {
    //     return readOnly ? null : '<button class="btn btn-sm btn-dark"><i class="fas fa-bars"></i></button>'
    // }

    function canChargeTableBeDeleted(charge) {
        if(!charge || !!props.readOnly)
            return false
        return !charge.lineItems.some(lineItem => (lineItem.invoice_id || lineItem.pickup_manifest_id || lineItem.delivery_manifest_id) ? true : false)
    }

    const deleteChargeTable = charge => {
        if(!canChargeTableBeDeleted(charge)) {
            const errorMessage = 'ERROR - charge table cannot be deleted - at least one item has been invoiced or manifested'
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
            taostr.warning('Selected charge has unsaved line items. Please save the bill then try again')
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

    // const moneyColumnStandardParams = {
    //     editor:'number',
    //     editorParams: {
    //         onWheel: (e) => e.target.blur(),
    //         selectContents: true,
    //         step: 0.01,
    //         verticalNavigation: 'table',
    //     },
    //     formatter: 'money',
    //     formatterParams: {thousand: ',', symbol: '$', selectContents: true},
    //     hozAlign: 'right',
    //     sorter: 'number',
    //     topCalc: 'sum',
    //     topCalcParams: {precision: 2},
    //     topCalcFormatter: 'money',
    //     topCalcFormatterParams: {thousand: ',', symbol: '$'},
    //     editable: cell => {
    //         return props.readOnly ? false : canLineItemBeEdited(cell.getRow())
    //     }
    // }

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
                        <Col md={6}>
                            <h5 className='text-muted'>{chargeTypeFormatter(charge.chargeType)} {charge.name}</h5>
                        </Col>
                        <Col md={4}>
                            {/* <InputGroup>
                                <InputLabel>Line Items</InputLabel>
                                <Select
                                    label="Line Items"
                                    value={[]}
                                    onChange={console.log}
                                >
                                    {Object.keys(rateTable).map(lineItemType => {
                                        return [
                                            <ListSubheader>{lineItemType}</ListSubheader>,
                                            ...rateTable[lineItemType].map(lineItem => <MenuItem value={lineItem}>{lineItem.name}</MenuItem>)
                                        ]
                                    })}
                                </Select>
                                <Button
                                    variant='success'
                                    onClick={() => props.generateCharges(index)}
                                    disabled={props.readOnly}
                                >Auto-price</Button>
                            </InputGroup> */}
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
                    <MaterialReactTable table={table} />
                </Card.Body>
                {/* {showLinkLineItemModal &&
                    <LinkLineItemModal
                        hide={hideLinkTo}
                        linkLineItemCell={linkLineItemCell}
                        linkLineItemToType={linkLineItemToType}
                        show={showLinkLineItemModal}
                    />
                } */}
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

