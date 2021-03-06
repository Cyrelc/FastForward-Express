import React from 'react'
import {Card, Col, FormCheck, FormControl, InputGroup, Row} from 'react-bootstrap'
import {ReactTabulator} from 'react-tabulator'
import Select from 'react-select'

const addressFormattingTooltip = 'Addresses will begin a new line on commas'

export default function InvoicingTab(props) {
    const columns = [
        {rowHandle: true, formatter: 'handle', headerSort: false, frozen: true, width: 30, minWidth: 30},
        {formatter: 'rownum', headerSort: false, width: 40, minWidth: 40},
        {title: 'Field Name', field: 'friendly_name', headerHozAlign: 'center', headerSort: false},
        {title: 'Database Field', field: 'database_field_name', headerSort: false, visible: false},
        {title: 'InvoiceSortOptionId', field: 'invoice_sort_option_id', headerSort: false, visible: false},
        {title: 'Contingent Field', field: 'contingent_field', headerSort: false, visible: false},
        {title: 'Priority', field: 'priority', headerSort: false, visible: false},
        {title: 'Subtotal By', field: 'group_by', formatter: 'tickCross', formatterParams: {allowEmpty: true}, headerHozAlign: 'center', hozAlign: 'center', headerSort: false, cellClick: ((e, cell) => {
            const data = cell.getRow().getData()
            const invoiceSortOrder = props.invoiceSortOrder.map(option => {
                if(option.database_field_name === data.database_field_name && option.can_be_subtotaled == '1')
                    return {...option, group_by: option.group_by == true ? false : true}
                return {...option, group_by: option.can_be_subtotaled == '1' ? false : null}
            })
            props.handleChanges({target: {name: 'invoiceSortOrder', type: 'array', value: invoiceSortOrder}})
        })}
    ]

    return (
        <Card>
            <Card.Header>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Options</h4>
                    </Col>
                    <Col md={9}>
                        <Row>
                            <Col md={4}>
                                <InputGroup>
                                    <InputGroup.Prepend><InputGroup.Text>Invoice Interval</InputGroup.Text></InputGroup.Prepend>
                                    <Select
                                        options={props.invoiceIntervals}
                                        getOptionLabel={type => type.name}
                                        getOptionValue={type => type.value}
                                        onChange={value => props.handleChanges({target: {name: 'invoiceInterval', type: 'object', value: value}})}
                                        value={props.invoiceInterval}
                                        isDisabled={props.readOnly}
                                    />
                                    <InputGroup.Append><InputGroup.Text><i className='fas fa-question' title='How often you would like to receive invoices for activity on your account'></i></InputGroup.Text></InputGroup.Append>
                                </InputGroup>
                            </Col>
                            <Col md={2} style={{paddingTop: '20px'}}>
                                <FormCheck
                                    name='sendPaperInvoices'
                                    label='Send Paper Invoices'
                                    checked={props.sendPaperInvoices}
                                    onChange={props.handleChanges}
                                    disabled={!props.sendEmailInvoices || props.readOnly}
                                />
                            </Col>
                            <Col md={2} style={{paddingTop: '20px'}}>
                                <FormCheck
                                    name='sendEmailInvoices'
                                    label='Send Email Invoices'
                                    checked={props.sendEmailInvoices}
                                    onChange={props.handleChanges}
                                    disabled={!props.sendPaperInvoices || props.readOnly}
                                />
                            </Col>
                            <Col md={6}>
                                <InputGroup>
                                    <InputGroup.Prepend><InputGroup.Text>Custom Tracking Field</InputGroup.Text></InputGroup.Prepend>
                                    <FormControl
                                        name='customTrackingField'
                                        value={props.customTrackingField}
                                        onChange={props.handleChanges}
                                        readOnly={props.readOnly}
                                    />
                                    <InputGroup.Append><InputGroup.Text><i className='fas fa-question' title='If you have an internal tracking number you wish to be able to reference, enter the name of it here. For example "PO Number", etc.'></i></InputGroup.Text></InputGroup.Append>
                                </InputGroup>
                            </Col>
                            <Col md={4} style={{paddingTop: '20px'}}>
                                <FormCheck
                                    name='customFieldMandatory'
                                    label='Custom Tracking Field is Mandatory'
                                    checked={props.customFieldMandatory}
                                    onChange={props.handleChanges}
                                    disabled={!props.customTrackingField || props.readOnly}
                                />
                            </Col>
                        </Row>
                    </Col>
                </Row>
                <Row style={{paddingTop: '20px'}}>
                    <Col md={2}>
                        <h4 className='text-muted'>Invoice Comment</h4>
                    </Col>
                    <Col md={10}>
                        <FormControl
                            name='invoiceComment'
                            as='textarea'
                            rows={3}
                            value={props.invoiceComment}
                            onChange={props.handleChanges}
                            placeholder='A comment that will appear on every invoice. For example: "ATTN Ritchie"'
                            readOnly={props.readOnly}
                        />
                    </Col>
                </Row>
            </Card.Header>
            <Card.Body>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Order Bills By</h4>
                    </Col>
                    <Col md={5}>
                        <ReactTabulator
                            columns={columns}
                            data={props.invoiceSortOrder.filter(invoiceSortItem => {
                                if(invoiceSortItem.contingent_field === 'can_be_parent' && !props.canBeParent)
                                    return false;
                                if(invoiceSortItem.contingent_field === 'custom_field' && props.customTrackingField == '')
                                    return false;
                                return true;
                            })}
                            options={{
                                layout: 'fitColumns',
                                movableRows: props.readOnly ? false : true
                            }}
                            initialSort={[{field: 'priority', dir: 'asc'}]}
                            rowMoved={row => props.handleInvoiceSortOrderChange(row)}
                        />
                    </Col>
                </Row>
            </Card.Body>
            <Card.Footer>
                <Row>
                    <Col md={2}>
                        <h4 className='text-muted'>Address Preview <i className='fas fa-question-circle' title={addressFormattingTooltip}></i></h4>
                    </Col>
                    <Col md={props.useShippingForBillingAddress ? 10 : 5}>
                        <strong>Shipping Address Formatted Preview</strong>
                        <FormControl
                            as='textarea'
                            rows={5}
                            value={props.shippingAddressName + '\n' + props.shippingAddressFormatted.replaceAll(',', '\n')}
                            disabled={true}
                        />
                    </Col>
                    {!props.useShippingForBillingAddress &&
                        <Col md={5}>
                            <strong>Billing Address Formatted Preview</strong>
                            <FormControl
                                as='textarea'
                                rows={5}
                                value={props.billingAddressName + '\n' + props.billingAddressFormatted.replaceAll(',', '\n')}
                                disabled={true}
                            />
                        </Col>
                    }
                </Row>
            </Card.Footer>
        </Card>
    )
}
