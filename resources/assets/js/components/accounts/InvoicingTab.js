import React, {useEffect, useRef} from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'
import Select from 'react-select'

const addressFormattingTooltip = 'Addresses will begin a new line on commas'

export default function InvoicingTab(props) {
    const {
        canBeParent,
        customTrackingField,
        invoiceSortOrder,

        readOnly
    } = props

    const columns = [
        {rowHandle: true, formatter: 'handle', headerSort: false, frozen: true, width: 30, minWidth: 30},
        {formatter: 'rownum', headerSort: false, width: 40, minWidth: 40},
        {title: 'Field Name', field: 'friendly_name', headerHozAlign: 'center', headerSort: false},
        {title: 'Database Field', field: 'database_field_name', headerSort: false, visible: false},
        {title: 'InvoiceSortOptionId', field: 'invoice_sort_option_id', headerSort: false, visible: false},
        {title: 'Contingent Field', field: 'contingent_field', headerSort: false, visible: false},
        {title: 'Priority', field: 'priority', headerSort: false, visible: false},
        {title: 'Subtotal By', field: 'subtotal_by', formatter: 'tickCross', formatterParams: {allowEmpty: true}, headerHozAlign: 'center', hozAlign: 'center', headerSort: false, cellClick: ((e, cell) => {
            handleSubtotalByChange(cell)
        })}
    ]

    const handleInvoiceSortOrderChange = row => {
        const data = row.getTable().getData()
        props.setInvoiceSortOrder(data)
    }

    const handleSubtotalByChange = cell => {
        const data = cell.getRow().getData()
        const tableData = cell.getRow().getTable().getData()
        if(!data.can_be_subtotaled)
            return
        const newInvoiceSortOrder = tableData.map(option => {
            if(option.database_field_name === data.database_field_name && option.can_be_subtotaled == '1')
                return {...option, subtotal_by: !option.subtotal_by}
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
                        {!props.isLoading && invoiceSortOrder?.length > 0 &&
                            <ReactTabulator
                                columns={columns}
                                data={invoiceSortOrder}
                                options={{
                                    height: '150px',
                                    layout: 'fitColumns',
                                    movableRows: !readOnly,
                                    rowMoved: handleInvoiceSortOrderChange
                                }}
                                initialFilter={[{field: 'isValid', type:'=', value: true}]}
                                initialSort={[{field: 'priority', dir: 'asc'}]}
                            />
                        }
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
