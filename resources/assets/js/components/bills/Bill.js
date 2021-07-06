import React, {Component} from 'react'
import {Button, ButtonGroup, Col, Dropdown, DropdownButton, FormControl, InputGroup, ListGroup, Row, Tab, Tabs} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'
import { connect } from 'react-redux'

import BasicTab from './BasicTab'
import DispatchTab from './DispatchTab'
import BillingTab from './BillingTab'
import ActivityLogTab from '../partials/ActivityLogTab'

const initialState = {
    //basic information
    amount: '',
    applyRestrictions: true,
    billId: undefined,
    billNumber: '',
    businessHoursMin: '',
    businessHoursMax: '',
    chargeAccount: '',
    chargeReferenceValue: '',
    chargeEmployee: '',
    deliveryType: 'regular',
    description: '',
    incompleteFields: '',
    internalNotes: '',
    key: 'basic',
    packages: [],
    packageIsMinimum: false,
    packageIsPallet: false,
    paymentType: '',
    readOnly: true,
    repeatIntervals: [],
    skipInvoicing: false,
    timeCallReceived: '',
    timeDispatched: '',
    useImperial: false,
    //editONLY
    invoiceId: undefined,
    deliveryManifestId: undefined,
    pickupManifestId: undefined,
    percentComplete: undefined,
    incompleteFields: undefined,
    repeatIntervals: {},
    //delivery
    deliveryAccount: '',
    deliveryAddressFormatted: '',
    deliveryAddressLat: '',
    deliveryAddressLng: '',
    deliveryAddressName: '',
    deliveryAddressPlaceId: undefined,
    deliveryAddressType: 'Address',
    deliveryEmployeeCommission: '',
    deliveryEmployee: '',
    deliveryReferenceValue: '',
    deliveryTimeActual: '',
    deliveryTimeExpected: '',
    deliveryTimeMax: undefined,
    deliveryTimeMin: undefined,
    //pickup
    pickupAccount: '',
    pickupAddressFormatted: '',
    pickupAddressLat: '',
    pickupAddressLng: '',
    pickupAddressName: '',
    pickupAddressPlaceId: undefined,
    pickupAddressType: 'Address',
    pickupEmployeeCommission: '',
    pickupEmployee: '',
    pickupReferenceValue: '',
    pickupTimeActual: '',
    pickupTimeExpected: '',
    pickupTimeMax: undefined,
    pickupTimeMin: undefined,
    //interliner
    interliner: '',
    interlinerActualCost: '',
    interlinerTrackingId: '',
    interlinerCostToCustomer: '',
    //ratesheet
    deliveryTypes: '',
    ratesheetId: '',
    ratesheets: [],
    // weightRates: undefined,
    // timeRates: undefined,
    //immutable lists
    accounts: [],
    activityLog: undefined,
    addressTypes: ['Address', 'Account'],
    interliners: undefined,
    paymentTypes: undefined,
    permissions: {},
    timeRates: undefined,
    updatedAt: ''
}

class Bill extends Component {
    constructor() {
        super()
        this.state = {
            ...initialState
        }
        this.addPackage = this.addPackage.bind(this)
        this.deletePackage = this.deletePackage.bind(this)
        this.handleChanges = this.handleChanges.bind(this)
        this.setRatesheet = this.setRatesheet.bind(this)
        this.configureBill = this.configureBill.bind(this)
        this.store = this.store.bind(this)
        this.getStoreButton = this.getStoreButton.bind(this)
    }

    addPackage() {
        var packages = this.state.packages.slice();
        const newId = this.state.packages ? packages[packages.length - 1].packageId + 1 : 0
        packages.push({packageId: newId, packageCount: 1, packageWeight: '', packageLength: '', packageWidth: '', packageHeight: ''})
        this.setState({packages: packages})
    }

    configureBill() {
        const {match: {params}} = this.props

        var fetchUrl = '/bills/getModel'
        if(params.billId) {
            document.title = 'Manage Bill: ' + params.billId
            fetchUrl += '/' + params.billId
        } else {
            document.title = 'Create Bill - Fast Forward Express'
        }
        makeAjaxRequest(fetchUrl, 'GET', null, data => {
            data = JSON.parse(data)
            // the setup that happens regardless of new or edit
            var setup = {
                ...initialState,
                accounts: data.accounts,
                businessHoursMin: Date.parse(data.time_min.date),
                businessHoursMax: Date.parse(data.time_max.date),
                chargeAccount: this.state.billId ? '' : data.accounts.length === 1 ? data.accounts[0] : '',
                deliveryTypes: JSON.parse(data.ratesheets.find(ratesheet => ratesheet.ratesheet_id === data.ratesheet_id).delivery_types),
                interliners: data.interliners,
                packageIsMinimum: data.permissions.createFull,
                key: this.state.key ? this.state.key : initialState.key,
                packages: data.packages,
                packageIsMinimum: data.permissions.createFull,
                paymentType: data.payment_types.length === 1 ? data.payment_types[0] : '',
                paymentTypes: data.payment_types,
                permissions: data.permissions,
                ratesheet: data.ratesheets.find(ratesheet => ratesheet.ratesheet_id === data.ratesheet_id),
                ratesheets: data.ratesheets,
                repeatIntervals: data.repeat_intervals,
                readOnly: false,
            }
            this.setState(setup);
            if(params.billId) {
                const thisBillIndex = this.props.sortedBills.findIndex(bill_id => bill_id === data.bill.bill_id)
                const prevBillId = thisBillIndex <= 0 ? null : this.props.sortedBills[thisBillIndex - 1]
                const nextBillId = (thisBillIndex < 0 || thisBillIndex === this.props.sortedBills.length - 1) ? null : this.props.sortedBills[thisBillIndex + 1]
                setup = {...setup,
                    billId: data.bill.bill_id,
                    chargeAccount: data.accounts.find(account => account.account_id === data.bill.charge_account_id),
                    chargeReferenceValue: data.bill.charge_reference_value,
                    deliveryAccount: data.bill.delivery_account_id ? data.accounts.find(account => account.account_id === data.bill.delivery_account_id) : '',
                    deliveryAddressFormatted: data.delivery_address.formatted,
                    deliveryAddressLat: data.delivery_address.lat,
                    deliveryAddressLng: data.delivery_address.lng,
                    deliveryAddressName: data.delivery_address.name,
                    deliveryAddressPlaceId: data.delivery_address.place_id,
                    deliveryAddressType: data.bill.delivery_account_id === null ? 'Address' : 'Account',
                    deliveryReferenceValue: data.bill.delivery_reference_value,
                    deliveryTimeExpected: Date.parse(data.bill.time_delivery_scheduled),
                    deliveryType: this.state.deliveryTypes.find(deliveryType => deliveryType.id === data.bill.delivery_type),
                    description: data.bill.description,
                    incompleteFields: data.bill.incomplete_fields,
                    nextBillId: nextBillId,
                    packages: data.bill.packages,
                    packageIsMinimum: data.bill.is_min_weight_size,
                    packageIsPallet: data.bill.is_pallet,
                    paymentType: data.payment_types.find(payment_type => payment_type.payment_type_id === data.bill.payment_type_id),
                    percentComplete: data.bill.percentage_complete,
                    pickupAccount: data.bill.pickup_account_id ? data.accounts.find(account => account.account_id === data.bill.pickup_account_id) : '',
                    pickupAddressFormatted: data.pickup_address.formatted,
                    pickupAddressLat: data.pickup_address.lat,
                    pickupAddressLng: data.pickup_address.lng,
                    pickupAddressName: data.pickup_address.name,
                    pickupAddressPlaceId: data.pickup_address.place_id,
                    pickupAddressType: data.bill.pickup_account_id === null ? 'Address' : 'Account',
                    pickupReferenceValue: data.bill.pickup_reference_value,
                    pickupTimeExpected: Date.parse(data.bill.time_pickup_scheduled),
                    prevBillId: prevBillId,
                    updatedAt: Date.parse(data.bill.updated_at),
                    useImperial: data.bill.use_imperial,
                }

                if(data.permissions.viewActivityLog)
                    setup = {...setup, activityLog: data.activity_log}

                if(data.permissions.viewDispatch)
                    setup = {...setup,
                        billNumber: data.bill.bill_number,
                        deliveryEmployee: data.bill.delivery_driver_id ? this.props.drivers.find(driver => driver.employee_id === data.bill.delivery_driver_id) : '',
                        deliveryEmployeeCommission: data.bill.delivery_driver_commission,
                        deliveryTimeActual: data.bill.time_delivered ? Date.parse(data.bill.time_delivered) : '',
                        internalNotes: data.bill.internal_comments,
                        pickupEmployee: data.bill.pickup_driver_id ? this.props.drivers.find(driver => driver.employee_id === data.bill.pickup_driver_id) : '',
                        pickupEmployeeCommission: data.bill.pickup_driver_commission,
                        pickupTimeActual: data.bill.time_picked_up ? Date.parse(data.bill.time_picked_up) : '',
                        timeCallReceived: Date.parse(data.bill.time_call_received),
                        timeDispatched: data.bill.time_dispatched ? Date.parse(data.bill.time_dispatched) : '',
                    }

                if(data.permissions.viewBilling)
                    setup = {...setup,
                        amount: data.bill.amount,
                        chargeEmployee: data.chargeback ? this.props.employees.find(employee => employee.value === data.chargeback.employee_id) : undefined,
                        deliveryManifestId: data.bill.delivery_manifest_id,
                        interliner: data.bill.interliner_id ? data.interliners.find(interliner => interliner.value === data.bill.interliner_id) : '',
                        interlinerActualCost: data.bill.interliner_cost,
                        interlinerCostToCustomer: data.bill.interliner_cost_to_customer,
                        interlinerTrackingId: data.bill.interliner_reference_value,
                        invoiceId: data.bill.invoice_id,
                        pickupManifestId: data.bill.pickup_manifest_id,
                        repeatInterval: data.bill.repeat_interval ? data.repeat_intervals.filter(interval => interval.selection_id === data.bill.repeat_interval) : '',
                        skipInvoicing: data.bill.skip_invoicing,
                    }
            }
            this.setState(setup, () => this.setRatesheet(this.state.ratesheetId, true));
        })
    }

    componentDidMount() {
        this.configureBill()
    }

    componentDidUpdate(prevProps) {
        if(prevProps.location.pathname != this.props.location.pathname)
            this.configureBill()
    }

    deletePackage(packageId) {
        if(this.state.packages.length <= 1)
            return
        const packages = this.state.packages.filter(parcel => {return parcel.packageId !== packageId})
        this.setState({packages: packages})
    }

    getStoreButton() {
        if(this.state.billId) {
            if(this.state.permissions.editBasic || this.state.permissions.editDispatch || this.state.permissions.editBilling)
                return <Button variant='primary' onClick={this.store}>Update</Button>
        } else {
            if(this.state.permissions.createBasic || this.state.permissions.createFull)
                return <Button variant='primary' onClick={this.store}>Create</Button>
            else
                return <Button variant='primary' disabled>{this.state.billId ? 'Update' : 'Create'}</Button>
        }
    }

    setRatesheet(ratesheetId, initialize = false) {
        var deliveryType
        const ratesheet = this.state.ratesheets.find(ratesheet => ratesheet.ratesheet_id === ratesheetId)

        if(initialize)
            deliveryType = this.state.deliveryTypes.find(type => type.id === this.state.deliveryType)

        if(!this.props.match.params.billId)
            this.setState(ratesheet,
                () => this.handleChanges({target: {name: 'pickupTimeExpected', type: 'time', value: roundTimeToNextFifteenMinutes()}})
            )
        else
            this.setState(ratesheet)
    }

    handleAccountEvent(events, accountEvent) {
        const {name, value} = accountEvent.target
        const prefix = name === 'pickupAccount' ? 'pickup' : 'delivery'
        const account = value === '' ? null : value
        if(account && !this.state.chargeAccount && !this.state.pickupAccount && !this.state.deliveryAccount) {
            events['chargeAccount'] = account
            events['paymentType'] = this.state.paymentTypes.filter(type => type.name === 'Account')[0]
            //default back to cash ratesheet where applicable
            // if(this.state.ratesheetId != account.ratesheet_id)
            //     this.getRatesheet(account.ratesheet_id)
        }
        if (account === null && this.state[prefix + 'Account'] === this.state.chargeAccount) {
            events[name] = ''
            events[prefix + '']
            events[prefix + 'AddressLat'] = ''
            events[prefix + 'AddressLng'] = ''
            events[prefix + 'AddressFormatted'] = ''
            events[prefix + 'AddressName'] = ''
            events[prefix + 'ReferenceValue'] = ''
            events[prefix + 'PlaceId'] = ''
            events['chargeAccount'] = ''
            events['chargeReferenceValue'] = ''
            events['paymentType'] = ''
        } else {
            events[name] = account
            events[prefix + 'AddressLat'] = account.shipping_address ? account.shipping_address_lat : account.billing_address_lat
            events[prefix + 'AddressLng'] = account.shipping_address ? account.shipping_address_lng : account.billing_address_lng
            events[prefix + 'AddressFormatted'] = account.shipping_address ? account.shipping_address : account.billing_address
            events[prefix + 'AddressName'] = account.shipping_address ? account.shipping_address_name : account.billing_address_name
            events[prefix + 'AddressPlaceId'] = account.shipping_address ? account.shipping_address_place_id : account.billing_address_place_id
        }
        return events
    }

    handleApplyRestrictionsEvent(temp, event) {
        if(!this.state.permissions.createFull)
            return

        if(this.state.applyRestrictions) {
            toastr.clear()
            toastr.error('Restrictions lifted, some autocomplete functionality has been disabled. Please review all work carefully for accuracy before submitting', 'WARNING', {'timeOut' : 0, 'extendedTImeout' : 0, positionClass: 'toast-top-center'});
        }

        return {applyRestrictions: !this.state.applyRestrictions}
    }

    handleChanges(events) {
        if(!Array.isArray(events))
            events = [events]
        var temp = {}
        events.forEach(event => {
            const {name, value, type, checked} = event.target
            switch(name) {
                case 'applyRestrictions':
                    temp = this.handleApplyRestrictionsEvent(temp, event);
                    break;
                case 'deliveryTimeExpected':
                case 'deliveryType':
                case 'pickupTimeExpected':
                    temp = this.handleEstimatedTimeEvent(temp, event);
                    break
                case 'pickupReferenceValue':
                case 'deliveryReferenceValue':
                case 'chargeReferenceValue':
                    temp = this.handleReferenceValueEvent(temp, event)
                    break
                case 'packageCount':
                case 'packageWeight':
                case 'packageLength':
                case 'packageWidth':
                case 'packageHeight':
                    temp = this.handlePackageEvent(temp, event)
                    break
                case 'pickupAccount':
                case 'deliveryAccount':
                    temp = this.handleAccountEvent(temp, event)
                    break
                case 'pickupEmployee':
                case 'deliveryEmployee':
                    temp = this.handleDriverEvent(temp, event)
                    break
                default:
                    temp[name] = type === 'checkbox' ? checked : value
            }
        })
        this.setState(temp)
        //if, after state is saved, there is enough data to request a price, then do so
        //required fields: pickupAddressLat & Lng, deliveryAddressLat & Lng, weight, deliveryType, ratesheet(paymentType), pickup Datetime, Delivery Datetime
        //do this any time these requirements are met, including updating the price if a field is changed (for example deliveryType)
        //but do not make this call if another type of update is done (one that does not affect price) to minimize API calls to the server
    }

    handleDriverEvent(events, driverEvent) {
        const {name, value} = driverEvent.target
        if(name === 'pickupEmployee') {
            events['pickupEmployeeCommission'] = value.pickup_commission
            if(!this.state.deliveryEmployee) {
                events['deliveryEmployee'] = value
                events['deliveryEmployeeCommission'] = value.delivery_commission
            }
        } else if (name === 'deliveryEmployee') {
            events['deliveryEmployeeCommission'] = value.delivery_commission
        }
        if(this.state.timeDispatched === null)
            events['timeDispatched'] = new Date()
        events[name] = value
        return events
    }

    handleEstimatedTimeEvent(events, estimateTimeEvent) {
        const {name, value} = estimateTimeEvent.target

        events['deliveryType'] = name === 'deliveryType' ? value : this.state.deliveryType
        events['pickupTimeExpected'] = name === 'pickupTimeExpected' ? value : this.state.pickupTimeExpected
        events['deliveryTimeExpected'] = name === 'deliveryTimeExpected' ? value : this.state.deliveryTimeExpected
        if(this.state.applyRestrictions) {
            const sortedDeliveryTypes = this.state.deliveryTypes.sort((a, b) => a.time < b.time ? 1 : -1);
            const minTimeDifference = sortedDeliveryTypes[sortedDeliveryTypes.length - 1].time
            const today = new Date().setHours(0,0,0,0)
            const pickupDate = new Date(events['pickupTimeExpected']).setHours(0,0,0,0)
            const currentTime = today === pickupDate ? roundTimeToNextFifteenMinutes() : new Date(this.state.businessHoursMin)
            //set parameters: min/max pickup and delivery times
            //only today has special rules, as you can't ask for a time prior to when you are making the request (check against current time)
            if(!this.state.billId) {
                if(pickupDate === today) {
                    events['pickupTimeMin'] = currentTime > this.state.businessHoursMin ? currentTime : this.state.businessHoursMin
                    events['deliveryTimeMin'] = new Date(currentTime).addHours(minTimeDifference)
                    events['pickupTimeMax'] = new Date(this.state.businessHoursMax).addHours(-minTimeDifference)
                    events['deliveryTimeMax'] = this.state.businessHoursMax
                } else {
                    events['pickupTimeMin'] = new Date(pickupDate).setHours(this.state.businessHoursMin.getHours(), this.state.businessHoursMin.getMinutes())
                    events['deliveryTimeMin'] = new Date(pickupDate).setHours(this.state.businessHoursMin.getHours() + minTimeDifference, this.state.businessHoursMin.getMinutes())
                    events['pickupTimeMax'] = new Date(pickupDate).setHours(this.state.businessHoursMax.getHours() - minTimeDifference, this.state.businessHoursMin.getMinutes())
                    events['deliveryTimeMax'] = new Date(pickupDate).setHours(this.state.businessHoursMax.getHours(), this.state.businessHoursMax.getMinutes())
                }
                /* Special cases:
                *   1. User (or auto-fill on page load) has selected a time earlier than business hours, on a day that IS valid
                *   2. User (or more likely auto-fill on page load) has selected either: a time after business hours where the next day is valid, or a valid time on a weekend day
                *       In the event of case 2, we simply iterate through the "earliest" pickup times for the following days, until we find one which is valid by calling the function over again with the new attempt
                *       This should allow for future checks, for example to see if dates fall on holidays
                */
                if(events['pickupTimeExpected'] < events['pickupTimeMin']) {
                    console.log('pickupTime requested was too early.')
                    const nextAvailablePickupTime = events['pickupTimeMin']
                    this.handleChanges({target: {name: 'pickupTimeExpected', type: 'time', value: nextAvailablePickupTime}})
                    return
                } else if (events['pickupTimeExpected'] > events['pickupTimeMax'] || new Date(events['pickupTimeExpected']).getDay() === 6 || new Date(events['pickupTimeExpected']).getDay() === 0) {
                    console.log('pickupTime requested too late = ', events['pickupTimeExpected'] > events['pickuptTimeMax'], '   pickupTime day was   ', new Date(events['pickupTimeExpected']).getDay())
                    const nextAvailablePickupTime = new Date(events['pickupTimeExpected'])
                    nextAvailablePickupTime.addDays(1)
                    nextAvailablePickupTime.setHours(this.state.businessHoursMin.getHours(), this.state.businessHoursMin.getMinutes(), 0, 0)
                    this.handleChanges({target: {name: 'pickupTimeExpected', type: 'time', value: nextAvailablePickupTime}})
                    return
                }
                /*
                *   Iterate through the possible delivery type values, and disable those that are invalid (not an option) for the selected pickup time
                *   In addition, set the delivery type automatically to the highest possible type that still fits within the window given
                *   (i.e. at 3:00 PM with the business closing at 5:00, a 3 hr long delivery request is invalid, but a 2 or 1 hour long window is valid. Select the highest, and make it active)
                */
                const hoursBetweenRequestedPickupAndEndOfDay = getDatetimeDifferenceInHours(events['pickupTimeExpected'], events['deliveryTimeMax'])
                events['deliveryTypes'] = sortedDeliveryTypes.map(type => {
                    if(type.time > hoursBetweenRequestedPickupAndEndOfDay)
                        return {...type, isDisabled: true}
                    else
                        return {...type, isDisabled: false}
                })
                if(name === 'pickupTimeExpected')
                    events['deliveryType'] = events['deliveryTypes'].find(type => type.time <= hoursBetweenRequestedPickupAndEndOfDay)
            }

            events['deliveryTimeExpected'] = new Date(events['pickupTimeExpected']).addHours(events['deliveryType'].time)
        }

        return events
    }

    handlePackageEvent(events, packageEvent) {
        const {name, value} = packageEvent.target
        const packageId = packageEvent.target.dataset.packageid
        const packages = this.state.packages.map(parcel => {
            if(parcel.packageId == packageId)
                return {...parcel, [name]: value}
            else
                return parcel
        })
        events['packages'] = packages
        return events
    }

    handleReferenceValueEvent(events, referenceValueEvent) {
        const {name, value} = referenceValueEvent.target
        if((name === 'pickupReferenceValue' && this.state.pickupAccount.account_id === this.state.chargeAccount.account_id)
            || (name === 'deliveryReferenceValue' && this.state.deliveryAccount.account_id === this.state.chargeAccount.account_id)) {
                events['chargeReferenceValue'] = value
            }
        else if (this.state.paymentType.name === 'Account' && name === 'chargeReferenceValue' && this.state.chargeAccount.account_id === this.state.pickupAccount.account_id)
            events['pickupReferenceValue'] = value
        else if (this.state.paymentType.name === 'Account' && name === 'chargeReferenceValue' && this.state.chargeAccount.account_id === this.state.deliveryAccount.account_id)
            events['deliveryReferenceValue'] = value
        events[name] = value
        return events
    }

    render() {
        if(this.state.ratesheetId === null)
            return "Ratesheet not found. I'm sorry an error has occurred, please try again"
        else
            return (
                <Row md={11} className='justify-content-md-center'>
                    <Col md={11} className='d-flex justify-content-center'>
                        <ListGroup className='list-group-horizontal' as='ul'>
                            <ListGroup.Item variant='primary'><h4>Bill: {this.state.billId === null ? 'Create' : this.state.billId}</h4></ListGroup.Item>
                            {(this.state.invoiceId != null && this.props.frontEndPermissions.invoices.view) &&
                                <LinkContainer to={'/app/invoices/view/' + this.state.invoiceId}><ListGroup.Item variant='secondary'><h4>Invoice Id: <a className='fakeLink'>{this.state.invoiceId}</a></h4></ListGroup.Item></LinkContainer>
                            }
                            {(this.state.pickupManifestId !== null && this.props.frontEndPermissions.manifests.viewAny) &&
                                <LinkContainer to={'/app/manifests/view/' + this.state.pickupManifestId}><ListGroup.Item variant='secondary'><h4>Pickup Manifest ID: <a className='fakeLink'>{this.state.pickupManifestId}</a></h4></ListGroup.Item></LinkContainer>
                            }
                            {(this.state.deliveryManifestId !== null && this.props.frontEndPermissions.manifests.viewAny) &&
                                <LinkContainer to={'/app/manifests/view/' + this.state.deliveryManifestId}><ListGroup.Item variant='secondary'><h4>Delivery Manifest ID: <a className='fakeLink'>{this.state.deliveryManifestId}</a></h4></ListGroup.Item></LinkContainer>
                            }
                            {this.state.billId !== null &&
                                <ListGroup.Item variant='success' title={this.state.incompleteFields}><h4>{this.state.percentComplete}% Complete</h4></ListGroup.Item>
                            }
                            <ListGroup.Item variant='warning'><h4>Price: {(parseFloat(this.state.amount ? this.state.amount : 0) + parseFloat(this.state.interlinerCostToCustomer ? this.state.interlinerCostToCustomer : 0)).toFixed(2)}</h4></ListGroup.Item>
                            {this.state.permissions.createFull &&
                                <ListGroup.Item>
                                    <Dropdown>
                                        <Dropdown.Toggle variant='primary' id='bill_functions' align='right'>Admin Functions</Dropdown.Toggle>
                                        <Dropdown.Menu>
                                            <Dropdown.Item
                                                key='applyRestrictions'
                                                variant={this.state.applyRestrictions ? 'dark' : 'danger'}
                                                onClick={() => this.handleChanges({target: {name: 'applyRestrictions', type: 'checkbox', checked: !this.state.applyRestrictions}})}
                                                style={{backgroundColor: this.state.applyRestrictions ? 'tomato' : 'black', color: this.state.applyRestrictions ? 'black' : 'white'}}
                                                title='Toggle restrictions'
                                                type='checkbox'
                                            ><i className={this.state.applyRestrictions ? 'fas fa-lock' : 'fas fa-unlock'}></i> Toggle Restrictions</Dropdown.Item>
                                            {(this.state.percentComplete === 100 && this.state.invoiceId === null) &&
                                                <InputGroup style={{paddingLeft: '10px', paddingRight: '10px', width: '450px'}}>
                                                    <FormControl
                                                        name='assignBillToInvoiceId'
                                                        type='number'
                                                        value={this.state.assignBillToInvoiceId}
                                                        onChange={this.handleChanges}
                                                        onKeyPress={event => {
                                                            if(event.key === 'Enter' && this.state.assignBillToInvoiceId)
                                                                makeAjaxRequest('/bills/assignToInvoice/' + this.state.billId + '/' + this.state.assignBillToInvoiceId, 'GET', null, response => {
                                                                    this.setState({invoiceId: this.state.assignBillToInvoiceId})
                                                                })
                                                        }}
                                                    />
                                                    <InputGroup.Append>
                                                        <Button
                                                            disabled={!this.state.assignBillToInvoiceId}
                                                            onClick={() => makeAjaxRequest('/bills/assignToInvoice/' + this.state.billId + '/' + this.state.assignBillToInvoiceId, 'GET', null, () => {
                                                                toastr.clear()
                                                                toastr.success('Bill linked to invoice ' + this.state.assignBillToInvoiceId, 'Success')
                                                            })}
                                                        ><i className='fas fa-link'></i> Assign Bill To Invoice</Button>
                                                    </InputGroup.Append>
                                                </InputGroup>
                                            }
                                            {this.state.invoiceId != null &&
                                                <Dropdown.Item
                                                    name='detatchBillFromInvoice'
                                                    onClick={() => makeAjaxRequest('/bills/removeFromInvoice/' + this.state.billId, 'GET', null, response => {
                                                        toastr.clear();
                                                        this.setState({invoiceId: null})
                                                        toastr.success('Bill successfully removed from invoice', 'Success')
                                                    })}
                                                ><i className='fas fa-unlink'></i> Detach Bill From Invoice</Dropdown.Item>
                                            }
                                        </Dropdown.Menu>
                                    </Dropdown>
                                </ListGroup.Item>
                            }
                        </ListGroup>
                    </Col>
                    <Col md={11}>
                        <Tabs id='bill-tabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.handleChanges({target: {name: 'key', type: 'string', value: key}})}>
                            <Tab eventKey='basic' title={<h4>Pickup/Delivery Info  <i className='fas fa-map-pin'></i></h4>}>
                                <BasicTab
                                    //mutable values
                                    delivery={{
                                        account: this.state.deliveryAccount,
                                        address: {
                                            type: this.state.deliveryAddressType,
                                            name: this.state.deliveryAddressName,
                                            formatted: this.state.deliveryAddressFormatted,
                                            lat: this.state.deliveryAddressLat, 
                                            lng: this.state.deliveryAddressLng,
                                            placeId: this.state.deliveryAddressPlaceId,
                                        },
                                        referenceValue: this.state.deliveryReferenceValue,
                                        timeExpected: this.state.deliveryTimeExpected,
                                        timeMax: this.state.deliveryTimeMax,
                                        timeMin: this.state.deliveryTimeMin,
                                    }}
                                    pickup={{
                                        account: this.state.pickupAccount,
                                        address: {
                                            type: this.state.pickupAddressType,
                                            name: this.state.pickupAddressName,
                                            formatted: this.state.pickupAddressFormatted,
                                            lat: this.state.pickupAddressLat, 
                                            lng: this.state.pickupAddressLng,
                                            placeId: this.state.pickupAddressPlaceId,
                                        },
                                        referenceValue: this.state.pickupReferenceValue,
                                        timeExpected: this.state.pickupTimeExpected,
                                        timeMax: this.state.pickupTimeMax,
                                        timeMin: this.state.pickupTimeMin,
                                    }}
                                    ratesheet={{
                                        deliveryTypes: this.state.deliveryTypes,
                                        // useInternalZonesCalc: this.state.useInternalZonesCalc,
                                        // weightRates: this.state.weightRates
                                    }}
                                    accounts={this.state.accounts}
                                    addressTypes={this.state.addressTypes}
                                    applyRestrictions={this.state.applyRestrictions}
                                    chargeAccount={this.state.chargeAccount}
                                    chargeReferenceValue={this.state.chargeReferenceValue}
                                    deliveryType={this.state.deliveryType}
                                    description={this.state.description}
                                    packages={this.state.packages}
                                    packageIsMinimum={this.state.packageIsMinimum}
                                    packageIsPallet={this.state.packageIsPallet}
                                    paymentType={this.state.paymentType}
                                    timeRates={this.state.timeRates}
                                    useImperial={this.state.useImperial}

                                    //functions
                                    addPackage={this.addPackage}
                                    deletePackage={this.deletePackage}
                                    handleChanges={this.handleChanges}

                                    //value only (non-mutable by recipient function)
                                    deliveryManifestId={this.state.deliveryManifestId}
                                    invoiceId={this.state.invoiceId}
                                    minTimestamp={this.state.minTimestamp}
                                    paymentTypes={this.state.paymentTypes}
                                    permissions={this.state.permissions}
                                    pickupManifestId={this.state.pickupManifestId}
                                    readOnly={this.state.billId ? !this.state.permissions.editBasic : !this.state.permissions.createBasic}
                                />
                            </Tab>
                            {(this.state.billId ? this.state.permissions.viewDispatch : this.state.permissions.createFull) &&
                                <Tab eventKey='dispatch' title={<h4>Dispatch  <i className='fas fa-truck'></i></h4>}>
                                    <DispatchTab
                                        //mutable values
                                        deliveryEmployee={this.state.deliveryEmployee}
                                        deliveryEmployeeCommission={this.state.deliveryEmployeeCommission}
                                        deliveryTimeActual={this.state.deliveryTimeActual}
                                        pickupEmployee={this.state.pickupEmployee}
                                        pickupEmployeeCommission={this.state.pickupEmployeeCommission}
                                        pickupTimeActual={this.state.pickupTimeActual}
                                        timeCallReceived={this.state.timeCallReceived}
                                        timeDispatched={this.state.timeDispatched}

                                        //functions
                                        handleChanges={this.handleChanges}

                                        //value only (non-mutable by recipient function)
                                        billId={this.state.billId}
                                        deliveryManifestId={this.state.deliveryManifestId}
                                        drivers={this.props.drivers}
                                        invoiceId={this.state.invoiceId}
                                        pickupManifestId={this.state.pickupManifestId}
                                        readOnly={this.state.billId ? !this.state.permissions.editDispatch : !this.state.permissions.createFull}
                                    />
                                </Tab>
                            }
                            {(this.state.billId ? this.state.permissions.viewBilling : this.state.permissions.createFull) &&
                                <Tab eventKey='billing' title={<h4>Billing  <i className='fas fa-credit-card'></i></h4>}>
                                    <BillingTab
                                        //mutable values
                                        amount={this.state.amount}
                                        billNumber={this.state.billNumber}
                                        chargeAccount={this.state.chargeAccount}
                                        chargeReferenceValue={this.state.chargeReferenceValue}
                                        chargeEmployee={this.state.chargeEmployee}
                                        interliner={this.state.interliner}
                                        interlinerActualCost={this.state.interlinerActualCost}
                                        interlinerCostToCustomer={this.state.interlinerCostToCustomer}
                                        interlinerTrackingId={this.state.interlinerTrackingId}
                                        paymentType={this.state.paymentType}
                                        pickupManifestId={this.state.pickupManifestId}
                                        prepaidReferenceField={this.state.prepaidReferenceField}
                                        prepaidReferenceValue={this.state.prepaidReferenceValue}
                                        repeatInterval={this.state.repeatInterval}
                                        skipInvoicing={this.state.skipInvoicing}

                                        //functions
                                        handleChanges={this.handleChanges}

                                        //value only (immutable by recipient function)
                                        accounts={this.state.accounts}
                                        deliveryManifestId={this.state.deliveryManifestId}
                                        employees={this.props.employees}
                                        interliners={this.state.interliners}
                                        invoiceId={this.state.invoiceId}
                                        paymentTypes={this.state.paymentTypes}
                                        readOnly={this.state.billId ? !this.state.permissions.editBilling : !this.state.permissions.createFull}
                                        repeatIntervals={this.state.repeatIntervals}
                                    />
                                </Tab>
                            }
                            {(this.state.permissions.viewActivityLog && this.state.activityLog) &&
                                <Tab eventKey='activity_log' title={<h4>Activity Log  <i className='fas fa-book-open'></i></h4>}>
                                    <ActivityLogTab
                                        activityLog={this.state.activityLog}
                                    />
                                </Tab>
                            }
                        </Tabs>
                    </Col>
                    <Col md={11} className='text-center'>
                        <ButtonGroup>
                            {this.state.billId &&
                                <LinkContainer to={'/app/bills/' + this.state.prevBillId}><Button variant='secondary' disabled={!this.state.prevBillId}><i className='fas fa-arrow-circle-left'></i> Back - {this.state.prevBillId}</Button></LinkContainer>
                            }
                            {this.getStoreButton()}
                            {this.state.billId &&
                                <LinkContainer to={'/app/bills/' + this.state.nextBillId}><Button variant='secondary' disabled={!this.state.nextBillId}>Next - {this.state.nextBillId} <i className='fas fa-arrow-circle-right'></i></Button></LinkContainer>
                            }
                        </ButtonGroup>
                    </Col>
                </Row>
            )
    }

    store() {
        var data = {bill_id: this.state.billId}
        if(this.state.billId ? this.state.permissions.editBasic : this.state.permissions.createBasic)
            data = {...data,
                charge_account_id: this.state.chargeAccount ? this.state.chargeAccount.account_id : undefined,
                charge_reference_value: this.state.chargeReferenceValue,
                delivery_account_id: this.state.deliveryAccount.account_id,
                delivery_address_formatted: this.state.deliveryAddressFormatted,
                delivery_address_lat: this.state.deliveryAddressLat,
                delivery_address_lng: this.state.deliveryAddressLng,
                delivery_address_name: this.state.deliveryAddressName,
                delivery_address_place_id: this.state.deliveryAddressPlaceId,
                delivery_address_type: this.state.deliveryAddressType,
                delivery_type: this.state.deliveryType,
                delivery_reference_value: this.state.deliveryReferenceValue,
                description: this.state.description,
                is_min_weight_size: this.state.packageIsMinimum ? true : false,
                is_pallet: this.state.packageIsPallet,
                packages: this.state.packages ? this.state.packages.slice() : null,
                payment_type: this.state.paymentType,
                pickup_account_id: this.state.pickupAccount.account_id,
                pickup_address_formatted: this.state.pickupAddressFormatted,
                pickup_address_lat: this.state.pickupAddressLat,
                pickup_address_lng: this.state.pickupAddressLng,
                pickup_address_name: this.state.pickupAddressName,
                pickup_address_place_id: this.state.pickupAddressPlaceId,
                pickup_address_type: this.state.pickupAddressType,
                pickup_reference_value: this.state.pickupReferenceValue,
                time_delivery_scheduled: this.state.deliveryTimeExpected.toLocaleString("en-US"),
                time_pickup_scheduled: this.state.pickupTimeExpected.toLocaleString("en-US"),
                updated_at: this.state.updatedAt.toLocaleString("en-US"),
                use_imperial: this.state.useImperial,
            }

        if(this.state.billId ? this.state.permissions.editDispatch : this.state.permissions.createFull)
            data = {...data,
                bill_number: this.state.billNumber,
                delivery_driver_commission: this.state.deliveryEmployeeCommission,
                delivery_driver_id: this.state.deliveryEmployee ? this.state.deliveryEmployee.employee_id : null,
                internal_comments: this.state.internalNotes,
                pickup_driver_id: this.state.pickupEmployee ? this.state.pickupEmployee.employee_id : null,
                pickup_driver_commission: this.state.pickupEmployeeCommission,
                time_call_received: this.state.timeCallReceived ? this.state.timeCallReceived.toLocaleString("en-US") : new Date().toLocaleString("en-US"),
                time_dispatched: this.state.timeDispatched ? this.state.timeDispatched.toLocaleString("en-US") : null,
            }

        if(this.state.billId ? this.state.permissions.editBilling : this.state.permissions.createFull)
            data = {...data,
                amount: this.state.amount,
                charge_employee_id: this.state.chargeEmployee ? this.state.chargeEmployee.employee_id : null,
                interliner_cost: this.state.interlinerActualCost,
                interliner_cost_to_customer: this.state.interlinerCostToCustomer,
                interliner_id: this.state.interliner ? this.state.interliner.value : undefined,
                interliner_reference_value: this.state.interlinerTrackingId,
                repeat_interval: this.state.repeatInterval ? this.state.repeatInterval.selection_id : null,
                skip_invoicing: this.state.skipInvoicing,
            }

        makeAjaxRequest('/bills/store', 'POST', data, response => {
            toastr.clear()
            if(this.state.billId) {
                toastr.success('Bill ' + this.state.billId + ' was successfully updated!', 'Success')
                this.handleChanges({target: {name: 'updatedAt', type: 'string', value: response.updated_at}})
            }
            else {
                this.setState({readOnly: true})
                toastr.success('Bill ' + response.id + ' was successfully created', 'Success', {
                    'progressBar': true,
                    'positionClass': 'toast-top-full-width',
                    'showDuration': 300,
                    'onHidden': () => {
                        this.handleChanges({target: {name: 'key', type: 'string', value: 'basic'}})
                        this.configureBill()
                    }
                })
            }
        })
    }
}

const mapStateToProps = store => {
    return {
        drivers: store.app.drivers,
        employees: store.app.employees,
        frontEndPermissions: store.app.frontEndPermissions,
        sortedBills: store.bills.sortedList
    }
}

export default connect(mapStateToProps)(Bill)
