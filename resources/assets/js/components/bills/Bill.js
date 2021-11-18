import React, {Component, createRef} from 'react'
import {Button, ButtonGroup, Col, Dropdown, DropdownButton, FormControl, InputGroup, ListGroup, Row, Tab, Tabs} from 'react-bootstrap'
import { LinkContainer } from 'react-router-bootstrap'
import { connect } from 'react-redux'

import BasicTab from './BasicTab'
import DispatchTab from './DispatchTab'
import BillingTab from './BillingTab'
import ActivityLogTab from '../partials/ActivityLogTab'

const initialState = {
    //basic information
    applyRestrictions: true,
    billId: undefined,
    businessHoursMin: '',
    businessHoursMax: '',
    deliveryType: null,
    deliveryTypeId: null,
    description: '',
    incompleteFields: '',
    internalNotes: '',
    key: 'basic',
    packages: [],
    packageIsMinimum: false,
    packageIsPallet: false,
    readOnly: true,
    timeCallReceived: '',
    timeDispatched: '',
    useImperial: false,
    //billing
    billNumber: '',
    chargeAccount: null,
    chargeEmployee: null,
    charges: [],
    chargeType: '',
    linkLineItemCell: null,
    linkLineItemId: null,
    linkLineItemToTargetId: null,
    linkLineItemToTargetObject: null,
    linkLineItemToType: null,
    showLinkLineItemModal: false,
    skipInvoicing: false,
    //editONLY
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
    interlinerTrackingId: '',
    //ratesheet
    deliveryTypes: [],
    ratesheets: [],
    activeRatesheet: undefined,
    //immutable lists
    accounts: [],
    activityLog: undefined,
    addressTypes: ['Address', 'Account'],
    chargeTypes: undefined,
    interliners: undefined,
    permissions: {},
    repeatIntervals: [],
    timeRates: undefined,
    updatedAt: ''
}

class Bill extends Component {
    constructor() {
        super()
        this.state = {
            ...initialState
        }
        this.addChargeTable = this.addChargeTable.bind(this)
        this.addPackage = this.addPackage.bind(this)
        this.chargeTableUpdated = this.chargeTableUpdated.bind(this)
        this.deletePackage = this.deletePackage.bind(this)
        this.generateCharges = this.generateCharges.bind(this)
        this.handleChanges = this.handleChanges.bind(this)
        this.handleRatesheetSelection = this.handleRatesheetSelection.bind(this)
        this.configureBill = this.configureBill.bind(this)
        this.store = this.store.bind(this)
        this.getStoreButton = this.getStoreButton.bind(this)
    }

    addChargeTable() {
        if(!this.state.chargeType) {
            console.log('chargeType can not be empty. Aborting')
            return
        }
        const basicCharge = {
            chargeType: this.state.chargeType,
            tableRef: createRef(),
            charge_reference_value: '',
            lineItems: []
        }
        const charge = (() => {
            switch(this.state.chargeType.name) {
                case 'Account':
                    if(!this.state.chargeAccount) {
                        console.log('chargeAccount can not be empty. Aborting')
                        return
                    }
                    return {
                        ...basicCharge,
                        charge_account_id: this.state.chargeAccount.account_id,
                        name: this.state.chargeAccount.name,
                        charge_reference_value_required: this.state.chargeAccount.is_custom_field_required ? true : false,
                        charge_reference_value_label: this.state.chargeAccount.custom_field ? this.state.chargeAccount.custom_field : null,
                    }
                case 'Employee':
                    if(!this.state.chargeEmployee) {
                        console.log('chargeEmployee can not be empty. Aborting')
                        return
                    }
                    return {
                        ...basicCharge,
                        charge_employee_id: this.state.chargeEmployee.value,
                        name: this.state.chargeEmployee.label,
                        charge_reference_value_required: false,
                        charge_reference_value_label: null,
                    }
                default:
                    return {
                        ...basicCharge,
                        name: this.state.chargeType.name,
                        charge_reference_value_required: this.state.chargeType.required_field ? true : false,
                        charge_reference_value_label: this.state.chargeType.required_field ? this.state.chargeType.required_field : null,
                }
            }
        })();
        this.setState({charges: this.state.charges.concat([charge])})
    }

    addPackage() {
        var packages = this.state.packages.slice();
        const newId = this.state.packages ? packages[packages.length - 1].packageId + 1 : 0
        packages.push({packageId: newId, packageCount: 1, packageWeight: '', packageLength: '', packageWidth: '', packageHeight: ''})
        this.setState({packages: packages})
    }

    chargeTableUpdated() {
        console.log('charge table update detected!!')
        const charges = this.state.charges.map(charge => {
            const groupByField = charge.tableRef.current.table.getGroups()[0].getField()
            charge.tableRef.current.table.setGroupBy(groupByField)
            return {...charge, lineItems: charge.tableRef.current.table.getData()}
        })
        this.setState({charges: charges})
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
                chargeAccount: (!params.billId && data.accounts.length === 1) ? data.accounts[0] : '',
                chargeType: data.charge_types.length === 1 ? data.charge_types[0] : '',
                chargeTypes: data.charge_types,
                deliveryTimeExpected: new Date(),
                interliners: data.interliners,
                key: this.state.key ? this.state.key : initialState.key,
                packageIsMinimum: data.permissions.createFull,
                packages: data.packages,
                packageIsMinimum: data.permissions.createFull,
                permissions: data.permissions,
                pickupTimeExpected: new Date(),
                ratesheets: data.ratesheets,
                repeatIntervals: data.repeat_intervals,
                readOnly: false,
            }
            this.setState(setup);
            // var activeRatesheet = data.ratesheets[0];
            if(params.billId) {
                const thisBillIndex = this.props.sortedBills.findIndex(bill_id => bill_id === data.bill.bill_id)
                const prevBillId = thisBillIndex <= 0 ? null : this.props.sortedBills[thisBillIndex - 1]
                const nextBillId = (thisBillIndex < 0 || thisBillIndex === this.props.sortedBills.length - 1) ? null : this.props.sortedBills[thisBillIndex + 1]
                setup = {...setup,
                    billId: data.bill.bill_id,
                    chargeReferenceValue: data.bill.charge_reference_value,
                    charges: data.charges.map(charge => {
                        return {
                            ...charge,
                            chargeType: data.charge_types.find(type => charge.charge_type_id === type.payment_type_id),
                            tableRef: createRef()
                        }
                    }),
                    deliveryAccount: data.bill.delivery_account_id ? data.accounts.find(account => account.account_id === data.bill.delivery_account_id) : '',
                    deliveryAddressFormatted: data.delivery_address.formatted,
                    deliveryAddressLat: data.delivery_address.lat,
                    deliveryAddressLng: data.delivery_address.lng,
                    deliveryAddressName: data.delivery_address.name,
                    deliveryAddressPlaceId: data.delivery_address.place_id,
                    deliveryAddressType: data.bill.delivery_account_id === null ? 'Address' : 'Account',
                    deliveryReferenceValue: data.bill.delivery_reference_value,
                    deliveryTimeExpected: Date.parse(data.bill.time_delivery_scheduled),
                    deliveryTypeId: data.bill.delivery_type,
                    description: data.bill.description,
                    incompleteFields: data.bill.incomplete_fields,
                    nextBillId: nextBillId,
                    packages: data.bill.packages,
                    packageIsMinimum: data.bill.is_min_weight_size,
                    packageIsPallet: data.bill.is_pallet,
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

                // if(setup.charges.length == 1 && setup.charges[0].chargeType.name == 'Account')
                //     activeRatesheet = data.ratesheets.find(ratesheet => ratesheet.ratesheet_id == data.charges[0].chargeType.default_ratesheet_id)[0]

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
                        interliner: data.bill.interliner_id ? data.interliners.find(interliner => interliner.value === data.bill.interliner_id) : '',
                        interlinerTrackingId: data.bill.interliner_reference_value,
                        repeatInterval: data.bill.repeat_interval ? data.repeat_intervals.filter(interval => interval.selection_id === data.bill.repeat_interval) : '',
                        skipInvoicing: data.bill.skip_invoicing,
                    }
                this.setState(setup, this.handleRatesheetSelection(this.state.ratesheets[0]));
            }
            // this.handleRatesheetSelection(activeRatesheet)
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

    generateCharges() {
        if(!this.state.charges || this.state.charges.length > 1) {
            toastr.error('Unable to generate charges where there is not exactly one charge recipient present')
            return
        }

        const data = {
            charge_account_id: this.state.chargeAccount ? this.state.chargeAccount.account_id : null,
            delivery_address: {lat: this.state.deliveryAddressLat, lng: this.state.deliveryAddressLng},
            delivery_type_id: this.state.deliveryType.id,
            package_is_minimum: this.state.packageIsMinimum,
            package_is_pallet: this.state.packageIsPallet,
            packages: this.state.packages,
            pickup_address: {lat: this.state.pickupAddressLat, lng: this.state.pickupAddressLng},
            ratesheet_id: this.state.activeRatesheet ? this.state.activeRatesheet.ratesheet_id : null,
            time_pickup_scheduled: this.state.pickupTimeExpected,
            time_delivery_scheduled: this.state.deliveryTimeExpected,
            use_imperial: this.state.useImperial
        }

        makeAjaxRequest('/bills/generateCharges', 'POST', data, response => {
            response = JSON.parse(response)
            response.forEach(charge => {
                this.state.charges[0].tableRef.current.table.addRow(charge);
            })
            toastr.warning('This feature is currently experimental. Please review the charges generated carefully for any inconsistencies')
        })
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

    handleAccountEvent(events, accountEvent) {
        const {name, value} = accountEvent.target
        const prefix = name === 'pickupAccount' ? 'pickup' : 'delivery'
        const account = value === '' ? null : value
        if(account && !this.state.chargeAccount && !this.state.pickupAccount && !this.state.deliveryAccount) {
            this.setState({
                chargeAccount: account,
                chargeType: this.state.chargeTypes.filter(type => type.name === 'Account')[0],
            }, () => {
                this.addChargeTable()
                if(account.ratesheet_id && account.ratesheet_id != this.state.activeRatesheet.ratesheet_id)
                    this.handleRatesheetSelection(this.state.ratesheets.filter(ratesheet => ratesheet.ratesheet_id === account.ratesheet_id)[0])
            })
            //TODO: default back to cash ratesheet where applicable
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
            events['chargeType'] = ''
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
                case 'deliveryTypes':
                case 'pickupTimeExpected':
                    temp = this.handleEstimatedTimeEvent(temp, event);
                    break
                case 'pickupReferenceValue':
                case 'deliveryReferenceValue':
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
        /**
         * if, after state is saved, there is enough data to request a price, then do so 
         * required fields: pickupAddressLat & Lng, deliveryAddressLat & Lng, weight, deliveryType, ratesheet(chargeType), pickup Datetime, Delivery Datetime
         * do this any time these requirements are met, including updating the price if a field is changed (for example deliveryType)
         * but do not make this call if another type of update is done (one that does not affect price) to minimize API calls to the server
         * 
         */
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

        events['deliveryTypes'] = name === 'deliveryTypes' ? value.sort((a, b) => a.time < b.time ? 1 : -1) : this.state.deliveryTypes
        if(name === 'deliveryType')
            events['deliveryType'] = value
        else if(name === 'deliveryTypes') {
            events['deliveryType'] = this.state.deliveryType ? value.find(type => type.id = this.state.deliveryType.id) : value.find(type => type.id = 'regular')
        } else
            events['deliveryType'] = this.state.deliveryType
        events['pickupTimeExpected'] = name === 'pickupTimeExpected' ? value : this.state.pickupTimeExpected
        events['deliveryTimeExpected'] = name === 'deliveryTimeExpected' ? value : this.state.deliveryTimeExpected
        if(this.state.applyRestrictions) {
            const minTimeDifference = events['deliveryTypes'][events['deliveryTypes'].length - 1].time
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
                if(!this.state.pickupTimeExpected || events['pickupTimeExpected'] < events['pickupTimeMin']) {
                    console.log('pickupTime requested was too early.')
                    events['pickupTimeExpected'] = events['pickupTimeMin']
                } else while (events['pickupTimeExpected'] > events['pickupTimeMax'] || new Date(events['pickupTimeExpected']).getDay() === 6 || new Date(events['pickupTimeExpected']).getDay() === 0) {
                    console.log('pickupTime requested too late = ', events['pickupTimeExpected'] > events['pickuptTimeMax'], '   pickupTime day was   ', new Date(events['pickupTimeExpected']).getDay())
                    const nextAvailablePickupTime = new Date(events['pickupTimeExpected'])
                    nextAvailablePickupTime.addDays(1)
                    nextAvailablePickupTime.setHours(this.state.businessHoursMin.getHours(), this.state.businessHoursMin.getMinutes(), 0, 0)
                    events['pickupTimeExpected'] = nextAvailablePickupTime
                }
                /*
                *   Iterate through the possible delivery type values, and disable those that are invalid (not an option) for the selected pickup time
                *   In addition, set the delivery type automatically to the highest possible type that still fits within the window given
                *   (i.e. at 3:00 PM with the business closing at 5:00, a 3 hr long delivery request is invalid, but a 2 or 1 hour long window is valid. Select the highest, and make it active)
                */
                const hoursBetweenRequestedPickupAndEndOfDay = getDatetimeDifferenceInHours(events['pickupTimeExpected'], events['deliveryTimeMax'])
                events['deliveryTypes'] = events['deliveryTypes'].map(type => {
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

    handleRatesheetSelection(ratesheet) {
        const deliveryTypes = JSON.parse(ratesheet.delivery_types)

        const commonRateNames = ['Refund', 'Other', 'Incorrect Information', 'Interliner']
        const miscRates = JSON.parse(ratesheet.misc_rates).map(rate => {return {...rate, type: 'miscellaneousRate', driver_amount: rate.price, paid: false}})
        const timeRates = JSON.parse(ratesheet.time_rates).map(rate => {return {...rate, type: 'timeRate', driver_amount: rate.price, paid: false}})
        const weightRates = JSON.parse(ratesheet.weight_rates).map(rate => {return {...rate, type: 'weightRate', driver_amount: rate.price, paid: false}})
        const commonRates = commonRateNames.map(name => {return {name: name, price: 0, type: 'commonRate', driver_amount: 0, paid: false}})
        const distanceRates = []
        if(ratesheet.distance_rates) {
            JSON.parse(ratesheet.distance_rates).map(rate => {
                distanceRates.push({name: 'Regular - ' + rate.zones + ' zones', price: rate.regular_cost, type: 'distanceRate', driver_amount: rate.regular_cost, paid: false})
                distanceRates.push({name: 'Rush - ' + rate.zones + ' zones', price: rate.rush_cost, type: 'distanceRate', driver_amount: rate.rush_cost, paid: false})
                distanceRates.push({name: 'Direct - ' + rate.zones + ' zones', price: rate.direct_cost, type: 'distanceRate', driver_amount: rate.direct_cost, paid: false})
                distanceRates.push({name: 'Direct Rush - ' + rate.zones + ' zones', price: rate.direct_rush_cost, type: 'distanceRate', driver_amount: rate.direct_rush_cost, paid: false})
            });
        }
        this.handleChanges([
            {target: {name: 'activeRatesheet', type: 'object', value: {...ratesheet, rates: [...commonRates.sortBy('name'), ...miscRates.sortBy('name'), ...timeRates.sortBy('name'), ...weightRates.sortBy('name'), ...distanceRates.sortBy('name')]}}},
            {target: {name: 'deliveryTypes', type: 'array', value: deliveryTypes}}
        ])
    }

    handleReferenceValueEvent(events, referenceValueEvent) {
        const {name, value} = referenceValueEvent.target
        if(name === 'pickupReferenceValue' && this.state.pickupAccount.account_id) {
            events['charges'] = this.state.charges.map(charge => {
                if(charge.chargeType.name === 'Account' && charge.account_id == this.state.pickupAccount.account_id)
                    return {...charge, charge_reference_value: value}
                return charge
            })
        } else if (name === 'deliveryReferenceValue' && this.state.deliveryAccount.account_id) {
            events['charges'] = this.state.charges.map(charge => {
                if(charge.chargeType.name === 'Account' && charge.account_id == this.state.deliveryAccount.account_id)
                    return {...charge, charge_reference_value: value}
                return charge
            })
        }
        events[name] = value
        return events
    }

    render() {
        if(this.state.activeRatesheet === null)
            return "Ratesheet not found. I'm sorry an error has occurred, please try again"
        else
            return (
                <Row md={11} className='justify-content-md-center'>
                    <Col md={11} className='d-flex justify-content-center'>
                        <ListGroup className='list-group-horizontal' as='ul'>
                            <ListGroup.Item variant='primary'><h4>Bill: {this.state.billId === null ? 'Create' : this.state.billId}</h4></ListGroup.Item>
                            {(this.state.billId && this.state.charges) &&
                                <ListGroup.Item variant='warning'><h4>Price: {this.state.charges.reduce((previousValue, charge) => previousValue + charge.lineItems.reduce((sum, lineItem) => sum + Number(lineItem.price), 0), 0).toLocaleString('en-US', {style: 'currency', currency: 'USD'})}</h4></ListGroup.Item>
                            }
                            {this.state.billId &&
                                <ListGroup.Item variant='success' title={this.state.incompleteFields}><h4>{this.state.percentComplete}% Complete <i className='fas fa-question-circle' title={this.state.incompleteFields}></i></h4></ListGroup.Item>
                            }
                            {this.state.permissions.createFull &&
                                <ListGroup.Item>
                                    <Button
                                        variant={this.state.applyRestrictions ? 'dark' : 'danger'}
                                        onClick={() => this.handleChanges({target: {name: 'applyRestrictions', type: 'checkbox', checked: !this.state.applyRestrictions}})}
                                        style={{backgroundColor: this.state.applyRestrictions ? 'tomato' : 'black', color: this.state.applyRestrictions ? 'black' : 'white'}}
                                        title='Toggle restrictions'
                                    ><i className={this.state.applyRestrictions ? 'fas fa-lock' : 'fas fa-unlock'}></i> {this.state.applyRestrictions ? 'Remove Time Restrictions' : 'Restore Time Restrictions'}</Button>
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
                                    chargeType={this.state.chargeType}
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
                                    chargeTypes={this.state.chargeTypes}
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
                                        drivers={this.props.drivers}
                                        invoiceId={this.state.invoiceId}
                                        isDeliveryManifested={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.delivery_manifest_id))}
                                        isPickupManifested={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.pickup_manifest_id))}
                                        readOnly={this.state.billId ? !this.state.permissions.editDispatch : !this.state.permissions.createFull}
                                    />
                                </Tab>
                            }
                            {(this.state.billId ? this.state.permissions.viewBilling : this.state.permissions.createFull) &&
                                <Tab eventKey='billing' title={<h4>Billing  <i className='fas fa-credit-card'></i></h4>}>
                                    <BillingTab
                                        //mutable values
                                        activeRatesheet={this.state.activeRatesheet}
                                        billNumber={this.state.billNumber}
                                        charges={this.state.charges}
                                        chargeAccount={this.state.chargeAccount}
                                        chargeEmployee={this.state.chargeEmployee}
                                        chargeType={this.state.chargeType}
                                        interliner={this.state.interliner}
                                        interlinerActualCost={this.state.interlinerActualCost}
                                        interlinerCostToCustomer={this.state.interlinerCostToCustomer}
                                        interlinerTrackingId={this.state.interlinerTrackingId}
                                        linkLineItemCell={this.state.linkLineItemCell}
                                        linkLineItemId={this.state.linkLineItemId}
                                        linkLineItemToTargetId={this.state.linkLineItemToTargetId}
                                        linkLineItemToTargetObject={this.state.linkLineItemToTargetObject}
                                        linkLineItemToType={this.state.linkLineItemToType}
                                        pickupManifestId={this.state.pickupManifestId}
                                        prepaidReferenceField={this.state.prepaidReferenceField}
                                        prepaidReferenceValue={this.state.prepaidReferenceValue}
                                        repeatInterval={this.state.repeatInterval}
                                        showLinkLineItemModal={this.state.showLinkLineItemModal}
                                        skipInvoicing={this.state.skipInvoicing}

                                        //functions
                                        addChargeTable={this.addChargeTable}
                                        chargeDeleted={this.chargeDeleted}
                                        chargeTableUpdated={this.chargeTableUpdated}
                                        generateCharges={this.generateCharges}
                                        handleChanges={this.handleChanges}
                                        handleRatesheetSelection={this.handleRatesheetSelection}

                                        //value only (immutable by recipient function)
                                        accounts={this.state.accounts}
                                        chargeTypes={this.state.chargeTypes}
                                        employees={this.props.employees}
                                        interliners={this.state.interliners}
                                        isDeliveryManifested={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.delivery_manifest_id))}
                                        isInvoiced={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.invoice_id))}
                                        isPickupManifested={this.state.charges.some(charge => charge.lineItems.some(lineItem => lineItem.pickup_manifest_id))}
                                        ratesheets={this.state.ratesheets}
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
                charge_account_id: this.state.chargeAccount.account_id,
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
                use_imperial: this.state.useImperial
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

        if(this.state.billId ? this.state.permissions.editBilling : this.state.permissions.createFull) {
            data = {...data,
                charges: this.state.charges.slice(),
                interliner_cost: this.state.interlinerActualCost,
                interliner_id: this.state.interliner ? this.state.interliner.value : undefined,
                interliner_reference_value: this.state.interlinerTrackingId,
                repeat_interval: this.state.repeatInterval ? this.state.repeatInterval.selection_id : null,
                skip_invoicing: this.state.skipInvoicing,
            }
            data.charges.forEach(charge => delete charge.tableRef)
        }

        makeAjaxRequest('/bills/store', 'POST', data, response => {
            toastr.clear()
            if(this.state.billId) {
                toastr.success('Bill ' + this.state.billId + ' was successfully updated!', 'Success')
                this.configureBill()
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
