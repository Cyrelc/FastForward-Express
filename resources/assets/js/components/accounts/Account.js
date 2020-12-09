import React, {Component} from 'react'
import {Badge, Button, Col, Row, Tab, Tabs} from 'react-bootstrap'

import ActivityLogTab from '../partials/ActivityLogTab'
import AdvancedTab from './AdvancedTab'
import BasicTab from './BasicTab'
import InvoicingTab from './InvoicingTab'
import PaymentsTab from './PaymentsTab'
import UsersTab from './UsersTab'

const initialState = {
    accountBalance: '',
    accountName: '',
    accountNumber: '',
    action: 'create',
    balanceOwing: '',
    billingAddressFormatted: '',
    billingAddressLat: '',
    billingAddressLng: '',
    billingAddressName: '',
    billingAddressPlaceId: '',
    canBeParent: false,
    childAccountCount: 0,
    customTrackingField: '',
    discount: '',
    invoiceComment: '',
    invoiceSortOrder: [],
    isGstExempt: false,
    minInvoiceAmount: '',
    parentAccount: '',
    parentAccounts: [],
    payments: [],
    ratesheet: '',
    ratesheets: [],
    sendBills: true,
    sendEmailInvoices: true,
    sendPaperInvoices: false,
    shippingAddressFormatted: '',
    shippingAddressLat: '',
    shippingAddressLng: '',
    shippingAddressName: '',
    shippingAddressPlaceId: '',
    showInvoiceLayoutModal: false,
    startDate: new Date(),
    useShippingForBillingAddress: true,
    useParentRatesheet: false,
    users: []
}

export default class Account extends Component {
    constructor() {
        super()
        this.state = {
            ...initialState
        }
        this.configureAccount = this.configureAccount.bind(this)
        this.handleChanges = this.handleChanges.bind(this)
        this.handleInvoiceSortOrderChange = this.handleInvoiceSortOrderChange.bind(this)
        this.storeAccount = this.storeAccount.bind(this)
    }

    configureAccount() {
        const {match: {params}} = this.props
        var fetchUrl = '/accounts/getModel'
        if(params.action === 'edit' || params.action === 'view') {
            document.title = params.action === 'edit' ? 'Edit Account ' + params.accountId : 'View Account ' + params.accountId
            fetchUrl += '/' + params.accountId
        } else {
            document.title = 'Create Account - Fast Forward Express'
        }
        makeAjaxRequest(fetchUrl, 'GET', null, response => {
            response = JSON.parse(response)
            var setup = {
                ...initialState,
                action: params.action,
                invoiceIntervals: response.invoice_intervals,
                parentAccounts: response.parent_accounts,
                ratesheets: response.ratesheets,
                invoiceInterval: response.invoice_intervals.find(invoiceInterval => invoiceInterval.value === 'monthly'),
                invoiceSortOrder: response.account.invoice_sort_order
            }
            this.setState(setup)
            if(params.action === 'edit' || params.action === 'view')
                setup ={
                    ...setup,
                    accountBalance: response.account.account_balance,
                    accountId: response.account.account_id,
                    accountName: response.account.name,
                    accountNumber: response.account.account_number,
                    active: response.account.active,
                    activityLog: response.activity_log,
                    balanceOwing: response.balance_owing,
                    canBeParent: response.child_account_count > 0 ? true : response.account.can_be_parent,
                    childAccountCount: response.child_account_count,
                    customTrackingField: response.account.custom_field ? response.account.custom_field : '',
                    discount: response.account.discount ? response.account.discount : '',
                    invoiceComment: response.account.invoice_comment ? response.account.invoice_comment : '',
                    invoiceInterval: response.invoice_intervals.find(invoiceInterval => invoiceInterval.value === response.account.invoice_interval),
                    isGstExempt: response.account.gst_exempt,
                    minInvoiceAmount: response.account.minInvoiceAmount,
                    ratesheet: response.ratesheets.find(ratesheet => ratesheet.value === response.account.ratesheet_id),
                    parentAccount: response.account.parent_account_id ? response.parent_accounts.find(parentAccount => parentAccount.value === response.account.parent_account_id) : {},
                    shippingAddressFormatted: response.shipping_address.formatted,
                    shippingAddressLat: response.shipping_address.lat,
                    shippingAddressLng: response.shipping_address.lng,
                    shippingAddressName: response.shipping_address.name,
                    shippingAddressPlaceId: response.shipping_address.place_id,
                    startDate: Date.parse(response.account.start_date),
                    useShippingForBillingAddress: response.account.billing_address_id === null,
                    useParentRatesheet: response.account.use_parent_ratesheet
                }
            if(response.billing_address != null)
                setup = {
                    ...setup,
                    billingAddressFormatted: response.billing_address.formatted,
                    billingAddressLat: response.billing_address.lat,
                    billingAddressLng: response.billing_address.lng,
                    billingAddressName: response.billing_address.name,
                    billingAddressPlaceId: response.billing_address.place_id
                }
            this.setState(setup)
        })
    }

    componentDidMount() {
        this.configureAccount()
    }

    componentDidUpdate(prevProps) {
        const {match: {params}} = this.props
        if(prevProps.match.params.action != params.action || prevProps.match.params.accountId != params.accountId)
            this.configureAccount()
    }

    handleChanges(events) {
        if(!Array.isArray(events))
            events = [events]
        var temp = {}
        events.forEach(event => {
            const {name, type, value, checked} = event.target
            if(name === 'parentAccount' && this.state.parentAccount == '') {
                temp['useParentRatesheet'] = true
            }
            temp[name] = type === 'checkbox' ? checked : value
        })
        this.setState(temp)
    }

    handleInvoiceSortOrderChange(row) {
        const data = row.getTable().getData()
        const invoiceSortOrder = this.state.invoiceSortOrder.map(sortItem => {
            const index = data.findIndex(item => item.database_field_name === sortItem.database_field_name)
            if(index >= 0)
                return {...sortItem, priority: index}
            return {...sortItem, priority: null}
        }).sort((a, b) => a.priority - b.priority)
        this.handleChanges({target: {name: 'invoiceSortOrder', type: 'array', value: invoiceSortOrder}})
    }

    storeAccount() {
        const data = {
            account_id: this.state.accountId,
            account_name: this.state.accountName,
            account_number: this.state.accountNumber,
            billing_address_formatted: this.state.billingAddressFormatted,
            billing_address_lat: this.state.billingAddressLat,
            billing_address_lng: this.state.billingAddressLng,
            billing_address_name: this.state.billingAddressName,
            billing_address_place_id: this.state.billingAddressPlaceId,
            can_be_parent: this.state.canBeParent,
            custom_field: this.state.customTrackingField,
            shipping_address_formatted: this.state.shippingAddressFormatted,
            shipping_address_lat: this.state.shippingAddressLat,
            shipping_address_lng: this.state.shippingAddressLng,
            shipping_address_name: this.state.shippingAddressName,
            shipping_address_place_id: this.state.shippingAddressPlaceId,
            discount: this.state.discount,
            invoice_comment: this.state.invoiceComment,
            invoice_interval: this.state.invoiceInterval.value,
            invoice_sort_order: this.state.invoiceSortOrder,
            is_gst_exempt: this.state.isGstExempt,
            min_invoice_amount: this.state.minInvoiceAmount,
            parent_account_id: this.state.parentAccount ? this.state.parentAccount.value : null,
            ratesheet_id: this.state.ratesheet ? this.state.ratesheet.value : null,
            start_date: this.state.startDate.toLocaleString(),
            send_bills: this.state.sendBills,
            send_email_invoices: this.state.sendEmailInvoices,
            send_paper_invoices: this.state.sendPaperInvoices,
            use_shipping_for_billing_address: this.state.useShippingForBillingAddress,
            use_parent_ratesheet: this.state.useParentRatesheet
        }

        makeAjaxRequest('/accounts/store', 'POST', data, response => {
            toastr.clear()
            toastr.success('Account ' + response.account_id + 'successfully ' + this.state.action === 'create' ? 'created' : 'updated', 'Success')
        })
    }

    render() {
        return (
            <Row className='justify-content-md-center' style={{paddingTop: '20px'}}>
                <Col md={3}>
                    <h3>{this.state.action === 'edit' ? 'Edit Account ' + this.state.accountId : 'Create Account'}</h3>
                </Col>
                <Col md={8} >
                    <h4>
                        {
                            this.state.accountBalance &&
                            <Badge variant={this.state.accountBalance >= 0 ? 'success' : 'danger'} style={{marginRight: '20px'}}>Account Credit: ${this.state.accountBalance.toLocaleString()}</Badge>
                        }
                        {
                            this.state.balanceOwing &&
                            <Badge variant='danger'>Balance Owing: ${this.state.balanceOwing.toLocaleString()}</Badge>
                        }
                    </h4>
                </Col>
                <Col md={11}>
                    <Tabs id='accountTabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.handleChanges({target: {name: 'key', type: 'string', value: key}})}>
                        <Tab eventKey='basic' title={<h4>Basic Info</h4>}>
                            <BasicTab
                                accountName={this.state.accountName}
                                billingAddress={{
                                    type: 'Address',
                                    name: this.state.billingAddressName,
                                    formatted: this.state.billingAddressFormatted,
                                    lat: this.state.billingAddressLat,
                                    lng: this.state.billingAddressLng,
                                    placeId: this.state.billingAddressPlaceId
                                }}
                                customTrackingField={this.state.customTrackingField}
                                shippingAddress={{
                                    type: 'Address',
                                    name: this.state.shippingAddressName,
                                    formatted: this.state.shippingAddressFormatted,
                                    lat: this.state.shippingAddressLat,
                                    lng: this.state.shippingAddressLng,
                                    placeId: this.state.shippingAddressPlaceId
                                }}
                                showInvoiceLayoutModal={this.state.showInvoiceLayoutModal}
                                useShippingForBillingAddress={this.state.useShippingForBillingAddress}

                                handleChanges={this.handleChanges}
                            />
                        </Tab>
                        <Tab eventKey='invoicing' title={<h4>Invoicing</h4>}>
                            <InvoicingTab
                                accountName={this.state.accountName}
                                canBeParent={this.state.canBeParent}
                                customTrackingField={this.state.customTrackingField}
                                billingAddressFormatted={this.state.billingAddressFormatted}
                                shippingAddressFormatted={this.state.shippingAddressFormatted}
                                invoiceComment={this.state.invoiceComment}
                                invoiceSortOrder={this.state.invoiceSortOrder}
                                showInvoiceLayoutModal={this.state.showInvoiceLayoutModal}
                                useShippingForBillingAddress={this.state.useShippingForBillingAddress}
                                sendPaperInvoices={this.state.sendPaperInvoices}
                                sendEmailInvoices={this.state.sendEmailInvoices}
                                invoiceInterval={this.state.invoiceInterval}

                                handleChanges={this.handleChanges}
                                handleInvoiceSortOrderChange={this.handleInvoiceSortOrderChange}
                                invoiceIntervals={this.state.invoiceIntervals}
                            />
                        </Tab>
                        <Tab eventKey='advanced' title={<h4>Advanced</h4>}>
                            <AdvancedTab
                                accountNumber={this.state.accountNumber}
                                canBeParent={this.state.canBeParent}
                                childAccountCount={this.state.childAccountCount}
                                discount={this.state.discount}
                                isGstExempt={this.state.isGstExempt}
                                minInvoiceAmount={this.state.minInvoiceAmount}
                                parentAccount={this.state.parentAccount}
                                ratesheet={this.state.ratesheet}
                                sendBills={this.state.sendBills}
                                startDate={this.state.startDate}
                                useParentRatesheet={this.state.useParentRatesheet}
                                
                                parentAccounts={this.state.parentAccounts}
                                ratesheets={this.state.ratesheets}
                                handleChanges={this.handleChanges}
                            />
                        </Tab>
                        { this.state.accountId &&
                            <Tab eventKey='users' title={<h4>Users</h4>}>
                                <UsersTab
                                    accountId={this.props.match.params.accountId}
                                />
                            </Tab>
                        }
                        { this.state.accountId &&
                            <Tab eventKey='payments' title={<h4>Payments</h4>}>
                                <PaymentsTab
                                    accountBalance={this.state.accountBalance}
                                    accountId={this.props.match.params.accountId}

                                    handleChanges={this.handleChanges}
                                />
                            </Tab>
                        }
                        {
                            this.state.activityLog &&
                            <Tab eventKey='activityLog' title={<h4>Activity Log</h4>}>
                                <ActivityLogTab
                                    activityLog={this.state.activityLog}
                                />
                            </Tab>
                        }
                    </Tabs>
                </Col>
                <Col md={2} style={{textAlign: 'center'}}>
                    <Button variant='primary' onClick={this.storeAccount}>Submit</Button>
                </Col>
            </Row>
        )
    }
}
