import React, {Fragment, useCallback, useEffect, useState} from 'react'
import {Badge, Button, ButtonGroup, Col, Container, Nav, Navbar, Row, Tab, Tabs} from 'react-bootstrap'
import {LinkContainer} from 'react-router-bootstrap'
import {useHistory, useLocation, useParams} from 'react-router-dom'
import {toast} from 'react-toastify'

import AccountUsersTab from './account_users/AccountUsersTab'
import ActivityLogTab from '../partials/ActivityLogTab'
import AdvancedTab from './AdvancedTab'
import BasicTab from './BasicTab'
import Charts from './Charts'
import ChildAccounts from './ChildAccounts'
import InvoicingTab from './InvoicingTab'
import BillingTab from './billing/BillingTab'

import useAddress from '../partials/Hooks/useAddress'
import {useAPI} from '../../contexts/APIContext'
import {useUser} from '../../contexts/UserContext'

export default function Account(props) {
    const [accountId, setAccountId] = useState('')
    const [accountBalance, setAccountBalance] = useState('')
    const [accountName, setAccountName] = useState('')
    const [accountNumber, setAccountNumber] = useState('')
    const [activityLog, setActivityLog] = useState([])
    const [balanceOwing, setBalanceOwing] = useState('')
    const [canBeParent, setCanBeParent] = useState(false)
    const [childAccountList, setChildAccountList] = useState([])
    const [customFieldMandatory, setCustomFieldMandatory] = useState(false)
    const [customTrackingField, setCustomTrackingField] = useState('')
    const [discount, setDiscount] = useState('')
    const [invoiceComment, setInvoiceComment] = useState('')
    const [invoiceInterval, setInvoiceInterval] = useState({})
    const [invoiceIntervals, setInvoiceIntervals] = useState([])
    const [invoiceSeparatelyFromParent, setInvoiceSeparatelyFromParent] = useState(false)
    const [invoiceSortOrder, setInvoiceSortOrder] = useState([])
    const [isActive, setIsActive] = useState(true)
    const [isGstExempt, setIsGstExempt] = useState(false)
    const [isLoading, setIsLoading] = useState(true)
    const [key, setKey] = useState('basic')
    const [minInvoiceAmount, setMinInvoiceAmount] = useState('')
    const [nextAccountIndex, setNextAccountIndex] = useState(null)
    const [parentAccount, setParentAccount] = useState('')
    const [parentAccounts, setParentAccounts] = useState([])
    const [permissions, setPermissions] = useState([])
    const [prevAccountIndex, setPrevAccountIndex] = useState(null)
    const [ratesheet, setRatesheet] = useState('')
    const [ratesheets, setRatesheets] = useState([])
    const [sendBills, setSendBills] = useState(true)
    const [sendEmailInvoices, setSendEmailInvoices] = useState(true)
    const [sendPaperInvoices, setSendPaperInvoices] = useState(false)
    // const [showInvoiceLayoutModal, setShowInvoiceLayoutModal] = useState(false)
    const [showInvoiceLineItems, setShowInvoiceLineItems] = useState(false)
    const [showPickupAndDeliveryAddress, setShowPickupAndDeliveryAddress] = useState(false)
    const [startDate, setStartDate] = useState(new Date())
    const [useShippingForBillingAddress, setUseShippingForBillingAddress] = useState(true)

    const api = useAPI()
    const billingAddress = useAddress()
    const shippingAddress = useAddress()
    const {accountId: paramAccountId} = useParams()
    const history = useHistory()
    const location = useLocation()
    const {authenticatedUser} = useUser()

    useEffect(() => {
        configureAccount()
    }, [paramAccountId])

    useEffect(() => {
        handleInvoiceSortOrderChange(invoiceSortOrder)
    }, [canBeParent, customTrackingField])

    useEffect(() => {
        setKey(location.hash.substring(1))
    }, [location.hash])

    const configureAccount = () => {
        const {match: {params}} = props
        var fetchUrl = `/accounts/getModel`

        if(params.accountId) {
            document.title = 'Manage Account ' + params.accountId
            fetchUrl += '/' + params.accountId
        } else
            document.title = 'Create Account - Fast Forward Express'

        setIsLoading(true)

        api.get(fetchUrl)
            .then(response => {
                billingAddress.reset()
                shippingAddress.reset()

                setInvoiceInterval(response.invoice_intervals.find(invoiceInterval => invoiceInterval.value === 'monthly'))
                setInvoiceIntervals(response.invoice_intervals)
                handleInvoiceSortOrderChange(response.account.invoice_sort_order)
                setParentAccounts(response.parent_accounts)
                setPermissions(response.permissions)
                setRatesheets(response.ratesheets)
                setKey(window.location.hash?.substr(1) || 'basic')

                if(params.accountId) {
                    let sortedAccounts = localStorage.getItem('accounts.sortedList')
                    if(sortedAccounts) {
                        sortedAccounts = sortedAccounts.split(',').map(index => parseInt(index))
                        const thisAccountIndex = sortedAccounts.findIndex(account_id => account_id === response.account.account_id)
                        setPrevAccountIndex(thisAccountIndex <= 0 ? null : sortedAccounts[thisAccountIndex - 1])
                        setNextAccountIndex((thisAccountIndex < 0 || thisAccountIndex === sortedAccounts.length - 1) ? null : sortedAccounts[thisAccountIndex + 1])
                    }

                    setAccountBalance(parseFloat(response.account.account_balance))
                    setAccountId(response.account.account_id)
                    setAccountName(response.account.name)
                    setAccountNumber(response.account.account_number)
                    setIsActive(response.account.active)
                    setActivityLog(response.activity_log)
                    setBalanceOwing(response.balance_owing)
                    setCanBeParent(!!response.account.can_be_parent)
                    setCustomFieldMandatory(response.account.is_custom_field_mandatory)
                    setCustomTrackingField(response.account.custom_field || '')
                    setDiscount(response.account.discount || '')
                    setInvoiceComment(response.account.invoice_comment || '')
                    setInvoiceInterval(response.invoice_intervals.find(interval => interval.value == response.account.invoice_interval))
                    setInvoiceSeparatelyFromParent(response.account.invoice_separately_from_parent)
                    setIsGstExempt(response.account.gst_exempt)
                    setMinInvoiceAmount(response.account.min_invoice_amount)
                    setParentAccount((response.account.parent_account_id && response.parent_accounts) ? response.parent_accounts.find(account => account.value === response.account.parent_account_id) : null)
                    setSendEmailInvoices(response.account.send_email_invoices)
                    setSendPaperInvoices(response.account.send_paper_invoices)
                    shippingAddress.setup(response.shipping_address)
                    setShowInvoiceLineItems(response.account.show_invoice_line_items)
                    setShowPickupAndDeliveryAddress(response.account.show_pickup_and_delivery_address)
                    setStartDate(new Date(response.account.start_date))
                    setUseShippingForBillingAddress(response.account.billing_address_id === null)

                    if(response.billing_address)
                        billingAddress.setup(response.billing_address)
                }

                if(response.permissions.editAdvanced)
                    setRatesheet(response.ratesheets.find(ratesheet => ratesheet.value === response.account.ratesheet_id))

                if(response.permissions.viewChildren) {
                    setCanBeParent(response.child_account_list.length > 0 ? true : response.account.can_be_parent)
                    setChildAccountList(response.child_account_list)
                }
                setIsLoading(false)
        })
    }

    const handleInvoiceSortOrderChange = useCallback(newInvoiceSortOrder => {
        const temp = newInvoiceSortOrder.map((option, index) => {
            if(option.contingent_field === 'can_be_parent') {
                return {...option, isValid: canBeParent, priority: index}
            }
            if(option.contingent_field === 'custom_field') {
                return {...option, isValid: !!customTrackingField, priority: index}
            }
            return {...option, isValid: true, priority: index}
        })
        setInvoiceSortOrder(temp)
    }, [canBeParent, customTrackingField])

    const storeAccount = () => {
        if(accountId ? !(permissions.editBasic || permissions.editInvoicing || permissions.editAdvanced) : !permissions.create) {
            toast.error('User does not have permissions to update this account', {toastId: `${accountId}-no-edit-permission`})
            return
        }

        var data = {
            account_id: accountId
        }
        if(accountId ? permissions.editBasic : permissions.create)
            data = {
                ...data,
                account_name: accountName,
                billing_address_formatted: billingAddress.formatted,
                billing_address_lat: billingAddress.lat,
                billing_address_lng: billingAddress.lng,
                billing_address_name: billingAddress.name,
                billing_address_place_id: billingAddress.placeId,
                shipping_address_formatted: shippingAddress.formatted,
                shipping_address_lat: shippingAddress.lat,
                shipping_address_lng: shippingAddress.lng,
                shipping_address_name: shippingAddress.name,
                shipping_address_place_id: shippingAddress.placeId,
                use_shipping_for_billing_address: useShippingForBillingAddress,
            }

        if(accountId ? permissions.editInvoicing : permissions.create) {
            data = {
                ...data,
                custom_field: customTrackingField,
                invoice_comment: invoiceComment,
                invoice_interval: invoiceInterval.value,
                invoice_sort_order: invoiceSortOrder,
                is_custom_field_mandatory: customFieldMandatory,
                send_bills: sendBills,
                send_email_invoices: sendEmailInvoices,
                send_paper_invoices: sendPaperInvoices,
                show_invoice_line_items: showInvoiceLineItems,
                show_pickup_and_delivery_address: showPickupAndDeliveryAddress
            }
        }

        if(accountId ? permissions.editAdvanced : permissions.create)
            data = {
                ...data,
                account_number: accountNumber,
                can_be_parent: canBeParent,
                discount: discount,
                is_gst_exempt: isGstExempt,
                min_invoice_amount: minInvoiceAmount,
                parent_account_id: parentAccount ? parentAccount.value : null,
                ratesheet_id: ratesheet ? ratesheet.value : null,
                start_date: startDate.toLocaleString(),
            }

        api.post('/accounts', data)
            .then(response => {
            toast.success(`Account ${response.account_id} successfully ${accountId  ? 'updated' : 'created'}`, {
                onClose: () => {
                    if(!accountId)
                        history.push(`/accounts/${response.account_id}`)
                }
            })
        })
    }

    if(isLoading)
        return <h4>Requesting data, please wait... <i className='fas fa-spinner fa-spin'></i></h4>

    return (
        <Row className='justify-content-md-center'>
            <Col md={12}>
                <Navbar expand='md' variant='dark' bg='dark'>
                    <Container>
                        <Nav>
                            <Navbar.Brand style={{paddingLeft: '15px'}} align='start'>
                                {accountId ?
                                    <Fragment>
                                        {parentAccount?.value &&
                                            <h4>
                                                Parent: <LinkContainer to={`/accounts/${parentAccount.value}`}><a>{parentAccount.label}</a></LinkContainer>
                                            </h4>
                                        }
                                        <h4>{`Manage Account: A${accountId} - ${accountName}`}</h4>
                                    </Fragment>
                                    : <h4>Create Account</h4>
                                }
                            </Navbar.Brand>
                        </Nav>
                        {accountId &&
                            <Nav>
                                {permissions.editAdvanced ? 
                                    <Button variant={isActive ? 'success' : 'danger'} style={{marginRight: '15px'}} onClick={() => {
                                        if(confirm(`Are you sure you wish to ${isActive ? 'DEACTIVATE' : 'ACTIVATE'} account ${accountName}?`)) {
                                            api.get(`/accounts/toggleActive/${accountId}`)
                                                .then(response => {
                                                    setIsActive(!isActive)
                                                }
                                            )
                                        }
                                    }}>{isActive ? 'Active' : 'Inactive'}</Button>
                                    : <Badge variant={isActive ? 'success' : 'danger'}>{isActive ? 'Active' : 'Inactive'}</Badge>
                                }
                                {permissions.viewPayments && accountBalance &&
                                    <Badge
                                        bg={accountBalance >= 0 ? 'success' : 'danger'}
                                        style={{marginRight: '15px'}}
                                    >
                                        <h6>
                                            Account Credit: {accountBalance.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}
                                        </h6>
                                    </Badge>
                                }
                                {permissions.viewPayments && balanceOwing != undefined &&
                                    <Badge bg={balanceOwing > 0 ? 'danger' : 'success'} style={{marginRight: '15px'}}>
                                        <h6>
                                            Balance Owing: {balanceOwing.toLocaleString('en-CA', {style: 'currency', currency: 'CAD'})}
                                        </h6>
                                    </Badge>
                                }
                                {permissions.viewBills &&
                                    <Nav.Link onClick={() => history.push(`/bills?filter[charge_account_id]=${accountId}`)} variant='secondary' >Bills</Nav.Link>
                                }
                                {permissions.viewInvoices &&
                                    <Nav.Link onClick={() => history.push(`/invoices?filter[account_id]=${accountId}`)} variant='secondary' >Invoices</Nav.Link>
                                }
                            </Nav>
                        }
                    </Container>
                </Navbar>
            </Col>
            <Col md={12}>
                <Tabs id='accountTabs' className='nav-justified' activeKey={key} onSelect={key => {window.location.hash = key; setKey(key)}}>
                    <Tab eventKey='basic' title={<h4>Basic Info</h4>}>
                        <BasicTab
                            accountId={accountId}
                            accountName={accountName}
                            accountNumber={accountNumber}
                            billingAddress={billingAddress}
                            shippingAddress={shippingAddress}
                            useShippingForBillingAddress={useShippingForBillingAddress}

                            setAccountName={setAccountName}
                            setAccountNumber={setAccountNumber}
                            setUseShippingForBillingAddress={setUseShippingForBillingAddress}

                            readOnly={accountId ? !permissions.editBasic : false}
                        />
                    </Tab>
                    <Tab eventKey='invoicing' title={<h4>Invoice Settings</h4>}>
                        <InvoicingTab
                            billingAddress={billingAddress}
                            canBeParent={canBeParent}
                            customFieldMandatory={customFieldMandatory}
                            customTrackingField={customTrackingField}
                            invoiceComment={invoiceComment}
                            invoiceInterval={invoiceInterval}
                            invoiceSeparatelyFromParent={invoiceSeparatelyFromParent}
                            invoiceSortOrder={invoiceSortOrder}
                            isLoading={isLoading}
                            sendEmailInvoices={sendEmailInvoices}
                            sendPaperInvoices={sendPaperInvoices}
                            shippingAddress={shippingAddress}
                            showInvoiceLineItems={showInvoiceLineItems}
                            showPickupAndDeliveryAddress={showPickupAndDeliveryAddress}
                            useShippingForBillingAddress={useShippingForBillingAddress}

                            handleInvoiceSortOrderChange={handleInvoiceSortOrderChange}

                            setCanBeParent={setCanBeParent}
                            setCustomFieldMandatory={setCustomFieldMandatory}
                            setCustomTrackingField={setCustomTrackingField}
                            setInvoiceComment={setInvoiceComment}
                            setInvoiceInterval={setInvoiceInterval}
                            setInvoiceSeparatelyFromParent={setInvoiceSeparatelyFromParent}
                            setInvoiceSortOrder={setInvoiceSortOrder}
                            setSendEmailInvoices={setSendEmailInvoices}
                            setSendPaperInvoices={setSendPaperInvoices}
                            setShowInvoiceLineItems={setShowInvoiceLineItems}
                            setShowPickupAndDeliveryAddress={setShowPickupAndDeliveryAddress}

                            invoiceIntervals={invoiceIntervals}
                            readOnly={!accountId && permissions.create ? false : !permissions.editInvoicing}
                        />
                    </Tab>
                    {(permissions.editAdvanced || !accountId && permissions.create ) &&
                        <Tab eventKey='advanced' title={<h4>Advanced</h4>}>
                            <AdvancedTab
                                accountNumber={accountNumber}
                                canBeParent={canBeParent}
                                childAccountList={childAccountList}
                                discount={discount}
                                isGstExempt={isGstExempt}
                                minInvoiceAmount={minInvoiceAmount}
                                parentAccount={parentAccount}
                                ratesheet={ratesheet}
                                sendBills={sendBills}
                                startDate={startDate}

                                parentAccounts={parentAccounts}
                                ratesheets={ratesheets}

                                setAccountNumber={setAccountNumber}
                                setCanBeParent={setCanBeParent}
                                // setChildAccountList={setChildAccountList}
                                setDiscount={setDiscount}
                                setIsGstExempt={setIsGstExempt}
                                setMinInvoiceAmount={setMinInvoiceAmount}
                                setParentAccount={setParentAccount}
                                setRatesheet={setRatesheet}
                                setSendBills={setSendBills}
                                setStartDate={setStartDate}
                            />
                        </Tab>
                    }
                    {accountId &&
                        <Tab eventKey='users' title={<h4>Users</h4>}>
                            <AccountUsersTab
                                accountId={props.match.params.accountId}
                                authenticatedUserContact={authenticatedUser.contact}
                                canBeParent={canBeParent}

                                canCreateAccountUsers={permissions.createAccountUsers}
                                canDeleteAccountUsers={permissions.deleteAccountUsers}
                                canEditAccountUsers={permissions.editAccountUsersBasic}
                                canEditAccountUserPermissions={permissions.editAccountUserPermissions}
                                canImpersonateAccountUsers={permissions.impersonateAccountUsers}
                                canViewAccountUserActivityLogs={permissions.viewAccountUserActivityLogs}
                            />
                        </Tab>
                    }
                    {accountId && permissions.viewPayments &&
                        <Tab eventKey='billing' title={<h4>Billing</h4>}>
                            <BillingTab
                                accountBalance={accountBalance}
                                accountId={props.match.params.accountId}

                                setAccountBalance={setAccountBalance}
                                setBalanceOwing={setBalanceOwing}

                                createPayments={permissions.createPayments}
                                canEditPaymentMethods={permissions.editPaymentMethods}
                                canEditPayments={permissions.editPayments}
                                canRevertPayments={permissions.revertPayments}
                                canViewInvoices={permissions.viewInvoices}
                            />
                        </Tab>
                    }
                    {childAccountList?.length > 0 && permissions.viewChildren &&
                        <Tab eventKey='childAccounts' title={<h4>Child Accounts</h4>}>
                            <ChildAccounts
                                childAccountList={childAccountList}
                            />
                        </Tab>
                    }
                    {permissions.viewPayments &&
                        <Tab eventKey='analytics' title={<h4>Analytics</h4>}>
                            <Charts accountId={accountId} />
                        </Tab>
                    }
                    {activityLog && permissions.viewActivityLog &&
                        <Tab eventKey='activityLog' title={<h4>Activity Log</h4>}>
                            <ActivityLogTab
                                activityLog={activityLog}
                            />
                        </Tab>
                    }
                </Tabs>
            </Col>
            <Col md={4} style={{textAlign: 'center'}}>
                <ButtonGroup>
                    {(accountId && permissions.viewChildren != false) &&
                        <LinkContainer to={`/accounts/${prevAccountIndex}${window.location.hash}`}>
                            <Button variant='info' disabled={!prevAccountIndex}>
                                <i className='fas fa-arrow-circle-left'></i> Back - {prevAccountIndex}
                            </Button>
                        </LinkContainer>
                    }
                    {(permissions.editBasic || permissions.editInvoicing || permissions.editAdvanced || (!accountId && permissions.create)) &&
                        <Button variant='primary' onClick={storeAccount}>Submit</Button>
                    }
                    {accountId && permissions.viewChildren != false &&
                        <LinkContainer to={`/accounts/${nextAccountIndex}${window.location.hash}`}>
                            <Button variant='info' disabled={!nextAccountIndex}>
                                Next - {nextAccountIndex} <i className='fas fa-arrow-circle-right'></i>
                            </Button>
                        </LinkContainer>
                    }
                </ButtonGroup>
            </Col>
        </Row>
    )
}
