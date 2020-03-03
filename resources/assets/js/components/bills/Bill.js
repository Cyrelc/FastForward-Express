import React, {Component} from 'react'
import ReactDom from 'react-dom'
import {Tabs, Tab, Row, Col, ListGroup, Button} from 'react-bootstrap'
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
            billId: null,
            billNumber: '',
            businessHoursMin: '',
            businessHoursMax: '',
            chargeAccount: undefined,
            chargeReferenceValue: '',
            chargeEmployee: undefined,
            deliveryType: 'regular',
            description: '',
            incompleteFields: '',
            key: 'basic',
            packages: [],
            packageIsMinimum: false,
            packageIsPallet: false,
            paymentType: '',
            readOnly: true,
            skipInvoicing: false,
            timeCallReceived: null,
            timeDispatched: null,
            useImperial: false,
            //editONLY
            invoiceId: null,
            deliveryManifestId: null,
            pickupManifestId: null,
            percentComplete: null,
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

    configureBill(data, edit = false) {
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
        }
        if(edit)
            setup = {...setup,
                activityLog: data.activity_log,
                amount: data.bill.amount,
                billId: data.bill.bill_id,
                billNumber: data.bill.bill_number,
                chargeAccount: data.accounts.find(account => account.account_id === data.bill.charge_account_id),
                chargeReferenceValue: data.bill.charge_reference_value,
                chargeEmployee: data.chargeback ? data.employees.find(employee => employee.employee_id === data.chargeback.employee_id) : undefined,
                description: data.bill.description,
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
                skipInvoicing: data.bill.skip_invoicing,
                interliner: data.interliners.find(interliner => interliner.interliner_id === data.bill.interliner_id),
                interlinerActualCost: data.bill.interliner_cost,
                interlinerCostToCustomer: data.bill.interliner_cost_to_customer,
                interlinerTrackingId: data.bill.interliner_reference_value,
            }

        this.setState(setup, () => this.getRatesheet(this.state.ratesheetId, true));
    }

    componentDidMount() {
        //check if create or edit or viewOnly
        const formType = window.location.href.split('/')[4]
        if(formType === 'edit' || formType === 'view') {
            document.title = formType === 'edit' ? 'Edit Bill - ' + document.title : 'View bill - ' + document.title
            fetch('/bills/getModel/' + window.location.href.split('/')[5])
            .then(response => {return response.json()})
            .then(data => this.configureBill(data, true));
        } else {
            document.title = 'Create Bill - ' + document.title
            fetch('/bills/getModel') //fetch data necessary to populate the form
            .then(response => {return response.json()})
            .then(data => this.configureBill(data));
        }
    }

    deletePackage(packageId) {
        if(this.state.packages.length <= 1)
            return
        const packages = this.state.packages.filter(parcel => {return parcel.packageId !== packageId})
        this.setState({packages: packages})
    }

    getRatesheet(id, initialize = false) {
        fetch('/ratesheets/getModel/' + id)
            .then(response => {return response.json()})
            .then(data => {
                var deliveryType
                if(initialize)
                    deliveryType = data.deliveryTypes.find(type => type.id === this.state.deliveryType)

                const ratesheet = {
                    deliveryTypes: data.deliveryTypes,
                    deliveryType: deliveryType
                }
                this.setState(ratesheet,
                    () => this.handleChanges({target: {name: 'deliveryTimeExpected', type: 'time', value: roundTimeToNextFifteenMinutes().addHours(this.state.deliveryType.time)}})
                )
            });
    }

    handleAccountEvent(events, accountEvent) {
        const {name, value} = accountEvent.target
        const prefix = name === 'pickupAccount' ? 'pickup' : 'delivery'
        const account = value === '' ? null : value
        if(account && this.state.chargeAccount === '' && this.state.pickupAccount === '' && this.state.deliveryAccount === '') {
            events['chargeAccount'] = account
            events['paymentType'] = this.state.paymentTypes.filter(type => type.name === 'Account')[0]
            //default back to cash ratesheet where applicable
            if(this.state.ratesheetId != account.ratesheet_id)
                this.getRatesheet(account.ratesheet_id)
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
            events[prefix + 'AddressLat'] = account.billing_address ? account.billing_address_lat : account.shipping_address_lat
            events[prefix + 'AddressLng'] = account.billing_address ? account.billing_address_lng : account.shipping_address_lng
            events[prefix + 'AddressFormatted'] = account.billing_address ? account.billing_address : account.shipping_address
            events[prefix + 'AddressName'] = account.billing_address ? account.billing_address_name : account.shipping_address_name
            events[prefix + 'AddressPlaceId'] = account.billing_address ? account.billing_address_place_id : account.shipping_address_place_id
        }
        return events
    }

    handleChanges(events) {
        if(!Array.isArray(events))
            events = [events]
        var temp = {}
        events.forEach(event => {
            const {name, value, type, checked} = event.target
            switch(name) {
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
            if(this.state.pickupEmployeeCommission === '')
                events['pickupEmployeeCommission'] = value.driver.pickup_commission
            if(this.state.deliveryEmployee === null && this.state.deliveryEmployeeCommission === '') {
                events['deliveryEmployee'] = value
                events['deliveryEmployeeCommission'] = value.driver.delivery_commission
                events['timeDispatched'] = new Date()
            }
        } else if (name === 'deliveryEmployee' && this.state.deliveryEmployeeCommission === '') {
            events['deliveryEmployeeCommission'] = value.driver.delivery_commission
        }
        events[name] = value
        return events
    }

    handleEstimatedTimeEvent(events, estimateTimeEvent) {
        const {name, value} = estimateTimeEvent.target

        events['deliveryType'] = name === 'deliveryType' ? value : this.state.deliveryType
        events['deliveryTimeExpected'] = name === 'deliveryTimeExpected' ? value : this.state.deliveryTimeExpected
        events['pickupTimeExpected'] = new Date(events['deliveryTimeExpected']).addHours(-events['deliveryType'].time)
        const sortedDeliveryTypes = this.state.deliveryTypes.sort((a, b) => a.time < b.time ? 1 : -1);
        const minTimeDifference = sortedDeliveryTypes[sortedDeliveryTypes.length - 1].time
        const today = new Date().setHours(0,0,0,0)
        const deliveryDate = new Date(events['deliveryTimeExpected']).setHours(0,0,0,0)
        const currentTime = today === deliveryDate ? roundTimeToNextFifteenMinutes() : new Date(this.state.businessHoursMin)
        //set parameters: min/max pickup and delivery times
        //only today has special rules, as you can't ask for a time prior to when you are making the request (check against current time)
        if(this.state.billId === null) {
            if(deliveryDate === today && this.state.bill_id === null) {
                events['pickupTimeMin'] = currentTime > this.state.businessHoursMin ? currentTime : this.state.businessHoursMin
                events['deliveryTimeMin'] = new Date(currentTime).addHours(minTimeDifference)
                events['pickupTimeMax'] = new Date(this.state.businessHoursMax).addHours(-minTimeDifference)
                events['deliveryTimeMax'] = this.state.businessHoursMax
            } else {
                events['pickupTimeMin'] = new Date(deliveryDate).setHours(this.state.businessHoursMin.getHours(), this.state.businessHoursMin.getMinutes())
                events['deliveryTimeMin'] = new Date(deliveryDate).setHours(this.state.businessHoursMin.getHours() + minTimeDifference, this.state.businessHoursMin.getMinutes())
                events['pickupTimeMax'] = new Date(deliveryDate).setHours(this.state.businessHoursMin.getHours() - minTimeDifference, this.state.businessHoursMin.getMinutes())
                events['deliveryTimeMax'] = new Date(deliveryDate).setHours(this.state.businessHoursMax.getHours(), this.state.businessHoursMax.getMinutes())
            }
            if(!this.state.admin && events['deliveryTimeMax'] < events['deliveryTimeExpected'])
                events['deliveryTimeExpected'] = new Date(events['deliveryTimeMax'])
            const hoursBetweenEarliestPickupAndDelivery = getDatetimeDifferenceInHours(events['deliveryTimeExpected'], events['pickupTimeMin'])
            //if there is physically not enough time left in the day
            if(hoursBetweenEarliestPickupAndDelivery < minTimeDifference) {
                toastr.warning('There is not enough time left today for any type of delivery. Delivery automatically set to next business day. If this is an emergency or special circumstance, please contact us', '', {'timeOut' : 600, 'extendedTImeout' : 600})
                var hours = 24
                //if Friday
                if(events['deliveryTimeExpected'].getDay() === 5)
                    hours *= 3
                //if Saturday (why are you ordering a thing on Saturday, what's wrong with you? Go back to bed...)
                else if (events['deliveryTimeExpected'].getDay() === 6) {
                    hours *= 2
                    toastr.info('Why are you doing a delivery on a Saturday? Go back to bed...')
                }
                events['deliveryType'] = sortedDeliveryTypes[0]
                events['pickupTimeExpected'] = new Date(this.state.businessHoursMin).addHours(hours)
                events['deliveryTimeExpected'] = new Date(events['pickupTimeExpected']).addHours(events['deliveryType'].time)
                events['deliveryTimeMin'] = new Date(this.state.businessHoursMin).addHours(minTimeDifference)
            } else if (name === 'deliveryType' && events['deliveryType'].time <= hoursBetweenEarliestPickupAndDelivery) {
    
            } else {
                sortedDeliveryTypes.some(type => {
                    if(type.time <= hoursBetweenEarliestPickupAndDelivery) {
                        events['deliveryType'] = type
                        events['pickupTimeExpected'] = new Date(events['deliveryTimeExpected']).addHours(-type.time)
                        return true
                    }
                    return false
                })
            }
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
                            {}
                            <ListGroup.Item variant='warning'><h4>Price: {this.state.amount}</h4></ListGroup.Item>
                        </ListGroup>
                    </Col>
                    <Col md={11}>
                        <Tabs id='bill-tabs' className='nav-justified' activeKey={this.state.key} onSelect={key => this.setState({key})}>
                            <Tab eventKey='basic' title={<h4>Pickup/Delivery Info  <i className='fas fa-map-pin'></i></h4>}>
                                <BasicTab 
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
                                    addressTypes={this.state.addressTypes}
                                    accounts={this.state.accounts}
                                    deliveryType={this.state.deliveryType}
                                    description={this.state.description}
                                    packages={this.state.packages}
                                    packageIsMinimum={this.state.packageIsMinimum}
                                    packageIsPallet={this.state.packageIsPallet}
                                    timeRates={this.state.timeRates}
                                    useImperial={this.state.useImperial}

                                    addPackage={this.addPackage}
                                    deletePackage={this.deletePackage}
                                    handleChanges={this.handleChanges}
                                    minTimestamp={this.state.minTimestamp}
                                    readOnly={this.state.readOnly}
                                    admin={this.state.admin}
                                />
                            </Tab>
                            {this.state.admin &&
                                <Tab eventKey='dispatch' title={<h4>Dispatch  <i className='fas fa-truck'></i></h4>}>
                                    <DispatchTab 
                                        drivers={this.state.drivers}
                                        pickupEmployee={this.state.pickupEmployee}
                                        pickupEmployeeCommission={this.state.pickupEmployeeCommission}
                                        pickupTimeActual={this.state.pickupTimeActual}
                                        deliveryEmployee={this.state.deliveryEmployee}
                                        deliveryEmployeeCommission={this.state.deliveryEmployeeCommission}
                                        deliveryTimeActual={this.state.deliveryTimeActual}
                                        interlinerActualCost={this.state.interlinerActualCost}
                                        interlinerCostToCustomer={this.state.interlinerCostToCustomer}
                                        interliner={this.state.interliner}
                                        interliners={this.state.interliners}
                                        interlinerTrackingId={this.state.interlinerTrackingId}
                                        timeCallReceived={this.state.timeCallReceived}
                                        timeDispatched={this.state.timeDispatched}
                                        readOnly={this.state.readOnly}

                                        handleChanges={this.handleChanges}
                                    />
                                </Tab>
                            }
                            {this.state.admin && 
                                <Tab eventKey='billing' title={<h4>Billing  <i className='fas fa-credit-card'></i></h4>}>
                                    <BillingTab 
                                        accounts={this.state.accounts}
                                        drivers={this.state.drivers}
                                        paymentTypes={this.state.paymentTypes}
                                        
                                        amount={this.state.amount}
                                        billNumber={this.state.billNumber}
                                        chargeAccount={this.state.chargeAccount}
                                        chargeReferenceValue={this.state.chargeReferenceValue}
                                        chargeEmployee={this.state.chargeEmployee}
                                        paymentType={this.state.paymentType}
                                        prepaidReferenceField={this.state.prepaidReferenceField}
                                        prepaidReferenceValue={this.state.prepaidReferenceValue}
                                        skipInvoicing={this.state.skipInvoicing}
                                        
                                        readOnly={this.state.readOnly}
                                        handleChanges={this.handleChanges}
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
                        <Button variant='primary' onClick={this.store}>Submit</Button>
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
            is_min_weight_size: this.state.packageIsMinimum,
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
            skip_invoicing: this.state.skipInvoicing,
            time_call_received: this.state.timeCallReceived ? this.state.timeCallReceived.toLocaleString("en-US") : new Date().toLocaleString("en-US"),
            time_delivery_scheduled: this.state.deliveryTimeExpected.toLocaleString("en-US"),
            time_dispatched: this.state.timeDispatched ? this.state.timeDispatched.toLocaleString("en-US") : null,
            time_pickup_scheduled: this.state.pickupTimeExpected.toLocaleString("en-US"),
            use_imperial: this.state.useImperial,
        }
        $.ajax({
            'url': '/bills/store',
            'type': 'POST',
            'data': data,
            'success': response => {
                toastr.clear()
                if(this.state.billId)
                    toastr.success('Bill ' + this.state.billId + ' was successfully updated!', 'Success')
                else
                    toastr.success('Bill ' + response.id + ' was successfully created', 'Success', {
                        'progressBar': true,
                        'positionClass': 'toast-top-full-width',
                        'showDuration': 500,
                    })
            },
            'error': response => handleErrorResponse(response)
        })
    }
}

ReactDom.render(<Bill />, document.getElementById('bill'))
