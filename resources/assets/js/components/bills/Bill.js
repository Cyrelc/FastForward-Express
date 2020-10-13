import React, {Component} from 'react'
import {Button, Col, Dropdown, FormControl, InputGroup, ListGroup, Row, Tab, Tabs} from 'react-bootstrap'

import BasicTab from './BasicTab'
import DispatchTab from './DispatchTab'
import BillingTab from './BillingTab'
import ActivityLogTab from '../partials/ActivityLogTab'

export default class Bill extends Component {
    constructor() {
        super()
        this.state = {
            //basic information
            admin: false,
            amount: '',
            applyRestrictions: true,
            billId: null,
            billNumber: '',
            businessHoursMin: '',
            businessHoursMax: '',
            chargeAccount: undefined,
            chargeReferenceValue: '',
            chargeEmployee: undefined,
            deliveryType: 'regular',
            description: '',
            formType: 'create',
            incompleteFields: '',
            key: 'basic',
            packages: [],
            packageIsMinimum: false,
            packageIsPallet: false,
            paymentType: '',
            readOnly: true,
            repeatIntervals: [],
            skipInvoicing: false,
            timeCallReceived: null,
            timeDispatched: null,
            useImperial: false,
            //editONLY
            invoiceId: null,
            deliveryManifestId: null,
            pickupManifestId: null,
            percentComplete: null,
            incompleteFields: null,
            repeatIntervals: {},
            //delivery
            deliveryAccount: '',
            deliveryAddressFormatted: '',
            deliveryAddressLat: '',
            deliveryAddressLng: '',
            deliveryAddressName: '',
            deliveryAddressPlaceId: null,
            deliveryAddressType: 'Address',
            deliveryEmployeeCommission: '',
            deliveryEmployee: null,
            deliveryReferenceValue: '',
            deliveryTimeActual: null,
            deliveryTimeExpected: null,
            deliveryTimeMax: null,
            deliveryTimeMin: null,
            //pickup
            pickupAccount: '',
            pickupAddressFormatted: '',
            pickupAddressLat: '', 
            pickupAddressLng: '',
            pickupAddressName: '',
            pickupAddressPlaceId: null,
            pickupAddressType: 'Address',
            pickupEmployeeCommission: '',
            pickupEmployee: null,
            pickupReferenceValue: '',
            pickupTimeActual: null,
            pickupTimeExpected: null,
            pickupTimeMax: null,
            pickupTimeMin: null,
            //interliner
            interliner: undefined,
            interlinerActualCost: '',
            interlinerTrackingId: '',
            interlinerCostToCustomer: '',
            //ratesheet
            deliveryTypes: undefined,
            ratesheetId: '',
            weightRates: undefined,
            timeRates: undefined,
            //immutable lists
            accounts: undefined,
            activityLog: undefined,
            addressTypes: ['Address', 'Account'],
            drivers: undefined,
            timeRates: undefined,
            interliners: undefined,
            paymentTypes: undefined,
        }
        this.addPackage = this.addPackage.bind(this)
        this.deletePackage = this.deletePackage.bind(this)
        this.handleChanges = this.handleChanges.bind(this)
        this.getRatesheet = this.getRatesheet.bind(this)
        this.configureBill = this.configureBill.bind(this)
        this.store = this.store.bind(this)
    }

    addPackage() {
        var packages = this.state.packages.slice();
        const newId = this.state.packages ? packages[packages.length - 1].packageId + 1 : 0
        packages.push({packageId: newId, packageCount: 1, packageWeight: '', packageLength: '', packageWidth: '', packageHeight: ''})
        this.setState({packages: packages})
    }

    configureBill(data, formType = 'create') {
        // the setup that happens regardless of new or edit
        var setup = {
            accounts: data.accounts,
            admin: data.admin,
            businessHoursMin: Date.parse(data.time_min.date),
            businessHoursMax: Date.parse(data.time_max.date),
            drivers: data.employees, 
            deliveryTypes: data.delivery_types,
            interliners: data.interliners,
            packages: data.packages,
            packageIsMinimum: data.admin,
            paymentTypes: data.payment_types,
            readOnly: data.read_only,
            ratesheetId: data.ratesheet_id,
            repeatIntervals: data.repeat_intervals,
            key: window.location.hash ? window.location.hash.substr(1) : 'basic'
        }
        if(formType === 'edit' || formType === 'view')
            setup = {...setup,
                activityLog: data.activity_log,
                amount: data.bill.amount,
                billId: data.bill.bill_id,
                billNumber: data.bill.bill_number,
                chargeAccount: data.accounts.find(account => account.account_id === data.bill.charge_account_id),
                chargeReferenceValue: data.bill.charge_reference_value,
                chargeEmployee: data.chargeback ? data.employees.find(employee => employee.employee_id === data.chargeback.employee_id) : undefined,
                description: data.bill.description,
                formType: formType,
                packages: data.bill.packages,
                packageIsMinimum: data.bill.is_min_weight_size,
                packageIsPallet: data.bill.is_pallet,
                paymentType: data.payment_types.find(payment_type => payment_type.payment_type_id === data.bill.payment_type_id),
                timeCallReceived: Date.parse(data.bill.time_call_received),
                timeDispatched: data.bill.time_dispatched ? Date.parse(data.bill.time_dispatched) : null,
                useImperial: data.bill.use_imperial,
                incompleteFields: data.bill.incomplete_fields,
                invoiceId: data.bill.invoice_id,
                deliveryManifestId: data.bill.delivery_manifest_id,
                pickupManifestId: data.bill.pickup_manifest_id,
                percentComplete: data.bill.percentage_complete,
                deliveryAccount: data.bill.delivery_account_id ? data.accounts.find(account => account.account_id === data.bill.delivery_account_id) : '',
                deliveryAddressFormatted: data.delivery_address.formatted,
                deliveryAddressLat: data.delivery_address.lat,
                deliveryAddressLng: data.delivery_address.lng,
                deliveryAddressName: data.delivery_address.name,
                deliveryAddressPlaceId: data.delivery_address.place_id,
                deliveryAddressType: data.bill.delivery_account_id === null ? 'Address' : 'Account',
                deliveryEmployeeCommission: data.bill.delivery_driver_commission,
                deliveryEmployee: data.employees.find(employee => employee.employee_id === data.bill.delivery_driver_id),
                deliveryReferenceValue: data.bill.delivery_reference_value,
                deliveryTimeActual: data.bill.time_delivered ? Date.parse(data.bill.time_delivered) : null,
                deliveryTimeExpected: Date.parse(data.bill.time_delivery_scheduled),
                deliveryType: data.bill.delivery_type,
                interliner: data.interliners.find(interliner => interliner.interliner_id === data.bill.interliner_id),
                interlinerActualCost: data.bill.interliner_cost,
                interlinerCostToCustomer: data.bill.interliner_cost_to_customer,
                interlinerTrackingId: data.bill.interliner_reference_value,
                pickupAccount: data.bill.pickup_account_id ? data.accounts.find(account => account.account_id === data.bill.pickup_account_id) : '',
                pickupAddressFormatted: data.pickup_address.formatted,
                pickupAddressLat: data.pickup_address.lat,
                pickupAddressLng: data.pickup_address.lng,
                pickupAddressName: data.pickup_address.name,
                pickupAddressPlaceId: data.pickup_address.place_id,
                pickupAddressType: data.bill.pickup_account_id === null ? 'Address' : 'Account',
                pickupEmployee: data.employees.find(employee => employee.employee_id === data.bill.pickup_driver_id),
                pickupEmployeeCommission: data.bill.pickup_driver_commission,
                pickupReferenceValue: data.bill.pickup_reference_value,
                pickupTimeActual: data.bill.time_picked_up ? Date.parse(data.bill.time_picked_up) : null,
                pickupTimeExpected: Date.parse(data.bill.time_pickup_scheduled),
                readOnly: data.read_only,
                repeatInterval: data.repeat_intervals.filter(interval => interval.selection_id === data.bill.repeat_interval),
                skipInvoicing: data.bill.skip_invoicing,
            }

        this.setState(setup, () => this.getRatesheet(this.state.ratesheetId, true));
    }

    componentDidMount() {
        const {match: {params}} = this.props

        if(params.action === 'edit' || params.action === 'view') {
            document.title = params.action === 'edit' ? 'Edit Bill - ' + document.title : 'View bill - ' + document.title
            makeFetchRequest('/bills/getModel/' + params.billId, data => {
                this.configureBill(data, params.action)
            })
        } else {
            document.title = 'Create Bill - ' + document.title
            makeFetchRequest('/bills/getModel', data => {
                this.configureBill(data)
            })
        }
    }

    componentDidUpdate(prevProps) {
        const {match: {params}} = this.props
        if(prevProps.match.params.billId != params.billId || prevProps.match.params.action != params.action)
            window.location.reload()
    }

    deletePackage(packageId) {
        if(this.state.packages.length <= 1)
            return
        const packages = this.state.packages.filter(parcel => {return parcel.packageId !== packageId})
        this.setState({packages: packages})
    }

    getRatesheet(id, initialize = false) {
        makeFetchRequest('/ratesheets/getModel/' + id, data => {
            var deliveryType
            if(initialize)
                deliveryType = data.deliveryTypes.find(type => type.id === this.state.deliveryType)

            const ratesheet = {
                deliveryTypes: data.deliveryTypes,
                deliveryType: deliveryType
            }
            if(this.state.formType === 'create')
                this.setState(ratesheet,
                    () => this.handleChanges({target: {name: 'pickupTimeExpected', type: 'time', value: roundTimeToNextFifteenMinutes()}})
                )
            else
                this.setState(ratesheet)
        })
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
            console.log(name, value)
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
                case 'key':
                    temp = {'key': value}
                    window.location.hash = value
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
                events['timeDispatched'] = new Date()
            }
        } else if (name === 'deliveryEmployee') {
            events['deliveryEmployeeCommission'] = value.delivery_commission
        }
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
                            <ListGroup.Item variant='primary'><h4>Bill: {this.state.billId === null ? 'New' : this.state.billId}</h4></ListGroup.Item>
                            {
                                this.state.invoiceId !== null && 
                                    <ListGroup.Item variant='secondary'><h4>Invoice Id: <a href='/invoices/{this.state.invoiceId}'>{this.state.invoiceId}</a></h4></ListGroup.Item>
                            }
                            {
                                this.state.pickupManifestId !== null &&
                                    <ListGroup.Item variant='secondary'><h4>Pickup Manifest ID: <a href='/?'>{this.state.pickupManifestId}</a></h4></ListGroup.Item>
                            }
                            {
                                this.state.deliveryManifestId !== null &&
                                    <ListGroup.Item variant='secondary'><h4>Delivery Manifest ID: <a href='/?'>{this.state.deliveryManifestId}</a></h4></ListGroup.Item>
                            }
                            {
                                this.state.billId !== null &&
                                    <ListGroup.Item variant='success' title={this.state.incompleteFields}><h4>Percent Complete: {this.state.percentComplete}</h4></ListGroup.Item>
                            }
                            <ListGroup.Item variant='warning'><h4>Price: {(parseFloat(this.state.amount ? this.state.amount : 0) + parseFloat(this.state.interlinerCostToCustomer ? this.state.interlinerCostToCustomer : 0)).toFixed(2)}</h4></ListGroup.Item>
                            {this.state.admin &&
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
                                                    />
                                                    <InputGroup.Append>
                                                        <Button
                                                            disabled={!this.state.assignBillToInvoiceId}
                                                            onClick={() => makeFetchRequest('/bills/assignToInvoice/' + this.state.billId + '/' + this.state.assignBillToInvoiceId, () => {
                                                                console.log('success but only in theory apparently')
                                                                toastr.clear()
                                                                toastr.success('Bill linked to invoice ' + this.state.assignBillToInvoiceId, 'Success', {
                                                                    'progressBar': true,
                                                                    'showDuration': 500,
                                                                    'onHidden': function(){location.reload()}
                                                                })
                                                            })}
                                                        ><i className='fas fa-link'></i> Assign Bill To Invoice</Button>
                                                    </InputGroup.Append>
                                                </InputGroup>
                                            }
                                            {this.state.invoiceId != null &&
                                                <Dropdown.Item
                                                    name='detatchBillFromInvoice'
                                                    onClick={() => makeFetchRequest('/bills/removeFromInvoice/' + this.state.billId,
                                                        () => {toastr.clear(); toastr.success('Bill successfully removed from invoice', 'Success', {
                                                            'progressBar': true,
                                                            'showDuration': 500,
                                                            'onHidden': function(){location.reload()}
                                                        }
                                                    )})}
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
                                        useInternalZonesCalc: this.state.useInternalZonesCalc,
                                        weightRates: this.state.weightRates
                                    }}
                                    accounts={this.state.accounts}
                                    addressTypes={this.state.addressTypes}
                                    applyRestrictions={this.state.applyRestrictions}
                                    deliveryType={this.state.deliveryType}
                                    description={this.state.description}
                                    packages={this.state.packages}
                                    packageIsMinimum={this.state.packageIsMinimum}
                                    packageIsPallet={this.state.packageIsPallet}
                                    timeRates={this.state.timeRates}
                                    useImperial={this.state.useImperial}

                                    //functions
                                    addPackage={this.addPackage}
                                    deletePackage={this.deletePackage}
                                    handleChanges={this.handleChanges}

                                    //value only (non-mutable by recipient function)
                                    admin={this.state.admin}
                                    deliveryManifestId={this.state.deliveryManifestId}
                                    invoiceId={this.state.invoiceId}
                                    minTimestamp={this.state.minTimestamp}
                                    pickupManifestId={this.state.pickupManifestId}
                                    readOnly={this.state.readOnly}
                                />
                            </Tab>
                            {this.state.admin &&
                                <Tab eventKey='dispatch' title={<h4>Dispatch  <i className='fas fa-truck'></i></h4>}>
                                    <DispatchTab
                                        //mutable values
                                        deliveryEmployee={this.state.deliveryEmployee}
                                        deliveryEmployeeCommission={this.state.deliveryEmployeeCommission}
                                        deliveryTimeActual={this.state.deliveryTimeActual}
                                        interliner={this.state.interliner}
                                        interlinerActualCost={this.state.interlinerActualCost}
                                        interlinerCostToCustomer={this.state.interlinerCostToCustomer}
                                        interlinerTrackingId={this.state.interlinerTrackingId}
                                        pickupEmployee={this.state.pickupEmployee}
                                        pickupEmployeeCommission={this.state.pickupEmployeeCommission}
                                        pickupTimeActual={this.state.pickupTimeActual}
                                        timeCallReceived={this.state.timeCallReceived}
                                        timeDispatched={this.state.timeDispatched}

                                        //functions
                                        handleChanges={this.handleChanges}

                                        //value only (non-mutable by recipient function)
                                        deliveryManifestId={this.state.deliveryManifestId}
                                        drivers={this.state.drivers}
                                        interliners={this.state.interliners}
                                        invoiceId={this.state.invoiceId}
                                        pickupManifestId={this.state.pickupManifestId}
                                        readOnly={this.state.readOnly}
                                    />
                                </Tab>
                            }
                            {this.state.admin && 
                                <Tab eventKey='billing' title={<h4>Billing  <i className='fas fa-credit-card'></i></h4>}>
                                    <BillingTab
                                        //mutable values
                                        amount={this.state.amount}
                                        billNumber={this.state.billNumber}
                                        chargeAccount={this.state.chargeAccount}
                                        chargeReferenceValue={this.state.chargeReferenceValue}
                                        chargeEmployee={this.state.chargeEmployee}
                                        interlinerCostToCustomer={this.state.interlinerCostToCustomer}
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
                                        drivers={this.state.drivers}
                                        invoiceId={this.state.invoiceId}
                                        paymentTypes={this.state.paymentTypes}
                                        readOnly={this.state.readOnly}
                                        repeatIntervals={this.state.repeatIntervals}
                                    />
                                </Tab>
                            }
                            {(this.state.admin && this.state.activityLog) &&
                                <Tab eventKey='activity_log' title={<h4>Activity Log  <i className='fas fa-book-open'></i></h4>}>
                                    <ActivityLogTab 
                                        activityLog={this.state.activityLog}
                                    />
                                </Tab>
                            }
                        </Tabs>
                    </Col>
                    <Col md={11} className='text-center'>
                        <Button variant='primary' onClick={this.store} disabled={this.state.readOnly}>Submit</Button>
                    </Col>
                </Row>
            )
    }

    store() {
        const data = {
            amount: this.state.amount,
            bill_id: this.state.billId,
            bill_number: this.state.billNumber,
            charge_account_id: this.state.chargeAccount ? this.state.chargeAccount.account_id : undefined,
            charge_employee_id: this.state.chargeEmployee ? this.state.chargeEmployee.employee_id : null,
            charge_reference_value: this.state.chargeReferenceValue,
            delivery_account_id: this.state.deliveryAccount.account_id,
            delivery_address_formatted: this.state.deliveryAddressFormatted,
            delivery_address_lat: this.state.deliveryAddressLat,
            delivery_address_lng: this.state.deliveryAddressLng,
            delivery_address_name: this.state.deliveryAddressName,
            delivery_address_place_id: this.state.deliveryAddressPlaceId,
            delivery_address_type: this.state.deliveryAddressType,
            delivery_driver_commission: this.state.deliveryEmployeeCommission,
            delivery_driver_id: this.state.deliveryEmployee ? this.state.deliveryEmployee.employee_id : null,
            delivery_type: this.state.deliveryType,
            delivery_reference_value: this.state.deliveryReferenceValue,
            description: this.state.description,
            interliner_cost: this.state.interlinerActualCost,
            interliner_cost_to_customer: this.state.interlinerCostToCustomer,
            interliner_id: this.state.interliner ? this.state.interliner.interliner_id : undefined,
            interliner_reference_value: this.state.interlinerTrackingId,
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
            pickup_driver_id: this.state.pickupEmployee ? this.state.pickupEmployee.employee_id : null,
            pickup_driver_commission: this.state.pickupEmployeeCommission,
            pickup_reference_value: this.state.pickupReferenceValue,
            repeat_interval: this.state.repeatInterval ? this.state.repeatInterval.selection_id : null,
            skip_invoicing: this.state.skipInvoicing,
            time_call_received: this.state.timeCallReceived ? this.state.timeCallReceived.toLocaleString("en-US") : new Date().toLocaleString("en-US"),
            time_delivery_scheduled: this.state.deliveryTimeExpected.toLocaleString("en-US"),
            time_dispatched: this.state.timeDispatched ? this.state.timeDispatched.toLocaleString("en-US") : null,
            time_pickup_scheduled: this.state.pickupTimeExpected.toLocaleString("en-US"),
            use_imperial: this.state.useImperial,
        }
        makeAjaxRequest('/bills/store', 'POST', data, response => {
            toastr.clear()
            if(this.state.billId)
                toastr.success('Bill ' + this.state.billId + ' was successfully updated!', 'Success')
            else {
                this.setState({readOnly: true})
                toastr.success('Bill ' + response.id + ' was successfully created', 'Success', {
                    'progressBar': true,
                    'positionClass': 'toast-top-full-width',
                    'showDuration': 500,
                    'onHidden': function(){location.reload()}
                })
            }
        })
    }
}
