import React, {useMemo} from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row} from 'react-bootstrap'
import {MaterialReactTable, useMaterialReactTable} from 'material-react-table'
import Checkbox from '@mui/material/Checkbox'
import Select from 'react-select'

const addressFormattingTooltip = 'Addresses will begin a new line on commas'

export default function InvoicingTab(props) {
    const {
        canBeParent,
        customTrackingField,
        invoiceSortOrder,

        readOnly
    } = props

    const columns = useMemo(() => [
        {header: 'Field Name', accessorKey: 'friendly_name'},
        {
            header: 'Subtotal By',
            accessorKey: 'subtotal_by',
            Cell: ({row}) => {
                if(row.original.can_be_subtotaled)
                    return <Checkbox checked={!!row.original.subtotal_by} onClick={() => handleSubtotalByChange(row)} />
            }
        }
    ], [])

    const filteredData = useMemo(() => {
        return invoiceSortOrder.filter(sortOption => {
            if(sortOption.database_field_name == 'charge_reference_value' && !customTrackingField)
                return false
            if(sortOption.database_field_name == 'charge_account_id' && !canBeParent)
                return false
            return true
        })
    }, [invoiceSortOrder, customTrackingField, canBeParent])

    const invoiceSortOrderTable = useMaterialReactTable({
        columns,
        data: filteredData,
        enableRowOrdering: true,
        enableRowNumbers: true,
        enableSorting: false,
        enableTopToolbar: false,
        enableBottomToolbar: false,
        manualFiltering: true,
        muiRowDragHandleProps: ({table}) => ({
            onDragEnd: () => {
                const {draggingRow, hoveredRow} = table.getState()
                if(hoveredRow && draggingRow) {
                    const newInvoiceSortOrder = [...invoiceSortOrder]
                    newInvoiceSortOrder.splice(
                        hoveredRow.index,
                        0,
                        newInvoiceSortOrder.splice(draggingRow.index, 1)[0]
                    )
                    props.setInvoiceSortOrder(newInvoiceSortOrder)
                }
            }
        })
    })

    const handleSubtotalByChange = row => {
        console.log(row, row.original)
        const data = row.original
        if(!data.can_be_subtotaled)
            return
        const newInvoiceSortOrder = invoiceSortOrder.map(option => {
            if(option.database_field_name === data.database_field_name && option.can_be_subtotaled == '1')
                return {...option, subtotal_by: option.subtotal_by ? 0 : 1}
            return {...option, subtotal_by: option.can_be_subtotaled == '1' ? false : null}
        })
        props.handleInvoiceSortOrderChange(newInvoiceSortOrder)
    }

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <h5 className='text-muted'>Options</h5>
                    </Col>
                    <Col md={10}>
                        <Row>
                            <Col md={3}>
                                <InputGroup>
                                    <InputGroup.Text>Invoice Interval</InputGroup.Text>
                                    <Select
                                        options={props.invoiceIntervals}
                                        getOptionLabel={type => type.name}
                                        getOptionValue={type => type.value}
                                        onChange={props.setInvoiceInterval}
                                        value={props.invoiceInterval}
                                        isDisabled={props.readOnly}
                                    />
                                    <InputGroup.Text>
                                        <i className='fas fa-question' title='How often you would like to receive invoices for activity on your account'></i>
                                    </InputGroup.Text>
                                </InputGroup>
                            </Col>
                            <Col md={3} style={{paddingTop: '20px'}}>
                                <FormCheck
                                    type='switch'
                                    name='sendPaperInvoices'
                                    label='Send Paper Invoices'
                                    checked={props.sendPaperInvoices}
                                    onChange={() => props.setSendPaperInvoices(!props.sendPaperInvoices)}
                                    disabled={!props.sendEmailInvoices || props.readOnly}
                                />
                            </Col>
                            <Col md={3} style={{paddingTop: '20px'}}>
                                <FormCheck
                                    type='switch'
                                    name='sendEmailInvoices'
                                    label='Digital Invoice Notifications'
                                    checked={props.sendEmailInvoices}
                                    onChange={() => props.setSendEmailInvoices(!props.sendEmailInvoices)}
                                    disabled={!props.sendPaperInvoices || props.readOnly}
                                />
                            </Col>
                            <Col md={3} style={{paddingTop: '20px'}}>
                                <FormCheck
                                    type='switch'
                                    name='showInvoiceLineItems'
                                    label='Show Invoice Line Items'
                                    checked={props.showInvoiceLineItems}
                                    onChange={() => props.setShowInvoiceLineItems(!props.showInvoiceLineItems)}
                                    disabled={props.readOnly}
                                />
                            </Col>
                            <Col md={3} style={{paddingTop: '20px'}}>
                                <FormCheck
                                    type='switch'
                                    name='showPickupAndDeliveryAddress'
                                    label='Show Both Pickup and Delivery Address'
                                    checked={props.showPickupAndDeliveryAddress}
                                    onChange={() => props.setShowPickupAndDeliveryAddress(!props.showPickupAndDeliveryAddress)}
                                    disabled={props.readOnly}
                                />
                            </Col>
                        </Row>
                    </Col>
                    <Col md={12}><hr/></Col>
                </Row>
                <Row>
                    <Col md={2}>
                        <h5 className='text-muted'>Custom Tracking Field</h5>
                    </Col>
                    <Col md={6}>
                        <InputGroup>
                            <InputGroup.Text>Name: </InputGroup.Text>
                            <FormControl
                                name='customTrackingField'
                                value={props.customTrackingField}
                                onChange={event => props.setCustomTrackingField(event.target.value)}
                                placeholder='Tracking Field Name (Optional)'
                                readOnly={props.readOnly}
                            />
                            <InputGroup.Text>
                                <i
                                    className='fas fa-question'
                                    title='If you have an internal tracking number you wish to be able to reference, enter the name of it here. For example "PO Number"'
                                ></i>
                            </InputGroup.Text>
                        </InputGroup>
                    </Col>
                    <Col md={4} style={{paddingTop: '20px'}}>
                        <FormCheck
                            name='customFieldMandatory'
                            label='Custom Tracking Field is Mandatory'
                            checked={props.customFieldMandatory}
                            onChange={() => props.setCustomFieldMandatory(!props.customFieldMandatory)}
                            disabled={!props.customTrackingField || props.readOnly}
                        />
                    </Col>
                    <Col md={12}><hr/></Col>
                </Row>
                <Row style={{paddingTop: '20px'}}>
                    <Col md={2}>
                        <h5 className='text-muted'>Invoice Comment</h5>
                    </Col>
                    <Col md={10}>
                        <FormControl
                            name='invoiceComment'
                            as='textarea'
                            rows={3}
                            value={props.invoiceComment}
                            onChange={event => props.setInvoiceComment(event.target.value)}
                            placeholder='A comment that will appear on every invoice. For example: "ATTN Ritchie"'
                            readOnly={props.readOnly}
                        />
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}>
                        <h5 className='text-muted'>Order Bills By</h5>
                    </Col>
                    <Col md={5} key={props.handleInvoiceSortOrderChange}>
                        <MaterialReactTable table={invoiceSortOrderTable} />
                    </Col>
                </Row>
            </Card.Body>
            <Card.Footer>
                <Row>
                    <Col md={2}>
                        <h5 className='text-muted'>Address Preview <i className='fas fa-question-circle' title={addressFormattingTooltip}></i></h5>
                    </Col>
                    <Col md={props.useShippingForBillingAddress ? 10 : 5}>
                        <strong>Shipping Address Formatted Preview</strong>
                        <FormControl
                            as='textarea'
                            rows={5}
                            value={props.shippingAddress.name + '\n' + props.shippingAddress.formatted.replaceAll(', ', '\n').replaceAll(',', '\n')}
                            disabled={true}
                        />
                    </Col>
                    {!props.useShippingForBillingAddress &&
                        <Col md={5}>
                            <strong>Billing Address Formatted Preview</strong>
                            <FormControl
                                as='textarea'
                                rows={5}
                                value={props.billingAddress.name + '\n' + props.billingAddress.formatted.replaceAll(', ', '\n').replaceAll(',', '\n')}
                                disabled={true}
                            />
                        </Col>
                    }
                </Row>
            </Card.Footer>
        </Card>
    )
}
