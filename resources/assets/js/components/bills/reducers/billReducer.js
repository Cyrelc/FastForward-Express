import {DateTime} from 'luxon'

/**
 * Checks if a requested time is a valid request based on: business hours, and deliveryTypes
 * @param {DateTime} dateTime (luxon DateTime object) 
 * @param {DateTime} businessHoursMin
 * @param {DateTime} businessHoursMax
 * @param {Array[Object]} deliveryTypes
 * 
 * @returns {Boolean}
 */
const isPickupTimeValid = (dateTime, businessHoursMin, businessHoursMax, deliveryTypes) => {
    // If requested time is in the past
    if(dateTime.diffNow('minutes').minutes < 0)
        return false
    // If requested time is a weekend (6 = Saturday, 7 = Sunday) See moment.github.io/luxon/api-docs/index.html#datetimeweekday for more details
    if(dateTime.weekday === 6 || dateTime.weekday === 7)
        return false
    // If requested time is AFTER business hours - (modified for shortest delivery window)
    const minimumTimeToDoADelivery = deliveryTypes.reduce((minimum, type) => type.time < minimum ? type.time : minimum, deliveryTypes[0].time)
    const luxonBusinessHoursMax = (DateTime.fromJSDate(businessHoursMax)).minus({hours: minimumTimeToDoADelivery})
    const lastPickupTime = dateTime.set({hour: luxonBusinessHoursMax.hour, minute: luxonBusinessHoursMax.minute})
    if(dateTime.diff(lastPickupTime, 'minutes').minutes > 0)
        return false
    // If requested time is BEFORE business hours - (no modification required)
    const luxonBusinessHoursMin = DateTime.fromJSDate(businessHoursMin)
    const firstPickupTime = dateTime.set({hour: luxonBusinessHoursMin.hour, minute: luxonBusinessHoursMin.minute})
    if(dateTime.diff(firstPickupTime, 'minutes').minutes < 0)
        return false

    return true
}

/**
 * Takes the current state, and a requested time value, and will return the **next** valid option 
 * after testing recursively in 15 minute increments
 * @param {object} state
 * @param {DateTime} time
 * @returns {DateTime} result
 */
const getValidPickupTime = (time, businessHoursMin, businessHoursMax, deliveryTypes) => {
    //if valid, return, otherwise recursively add 15 minutes until you find one that is valid!
    if(isPickupTimeValid(time, businessHoursMin, businessHoursMax, deliveryTypes) === true)
        return time
    else
        return getValidPickupTime(time.plus({minutes: 15}), businessHoursMin, businessHoursMax, deliveryTypes)
}

const initialPersistFields = [
    // {name: 'chargeAccount', label: 'Charge Account', checked: false},
    // {name: 'deliveryAccount', label: 'Delivery Account', checked: false},
    {name: 'deliveryDriver', label: 'Delivery Driver', checked: false},
    {name: 'deliveryDriverCommission', label: 'Delivery Driver Commission', checked: false},
    {name: 'deliveryTimeExpected', label: 'Delivery Time (Scheduled)', checked: false},
    {name: 'deliveryType', label: 'Delivery Type', checked: false},
    // {name: 'pickupAccount', label: 'Pickup Account', checked: false},
    {name: 'pickupDriver', label: 'Pickup Driver', checked: false},
    {name: 'pickupTimeExpected', label: 'Pickup Time (Scheduled)', checked: false},
    {name: 'pickupDriverCommission', label: 'Pickup Driver Commission', checked: false}
]

const initialPickupDelivery = {
    account: '',
    addressFormatted: '',
    addressLat: '',
    addressLng: '',
    addressName: '',
    addressPlaceId: '',
    addressType: 'Search',
    driver: '',
    driverCommission: '',
    referenceValue: '',
    timeActual: '',
    timeScheduled: new Date(),
}

export const initialState = {
    acceptTermsAndConditions: false,
    accounts: [],
    addressTypes: ['Search', 'Account', 'Manual'],
    applyRestrictions: true,
    billId: null,
    billNumber: '',
    businessHoursMin: null,
    businessHoursMax: null,
    delivery: initialPickupDelivery,
    deliveryType: '',
    deliveryTypes: [],
    description: '',
    incompleteFields: [],
    internalComments: '',
    key: 'basic',
    permissions: [],
    persistFields: initialPersistFields,
    pickup: initialPickupDelivery,
    repeatInterval: null,
    repeatIntervals: [],
    skipInvoicing: false,
    timeDispatched: null,
    timeCallReceived: null,
    updatedAt: ''
}

export default function billReducer(state, action) {
    const {type, payload} = action
    switch(action.type) {
        case 'CHECK_REFERENCE_VALUES': {
            const {account, value, prevValue} = payload
            return Object.assign({}, state, {
                pickup: {...state.pickup, referenceValue: (state.pickup.account.account_id === account.account_id && state.pickup.referenceValue === prevValue) ? value : state.pickup.referenceValue},
                delivery: {...state.delivery, referenceValue: (state.delivery.account.account_id === account.account_id && state.delivery.referenceValue === prevValue) ? value : state.delivery.referenceValue}
            })
        }
        case 'CONFIGURE_BILL':
            return Object.assign({}, state, {
                ...initialState,
                accounts: payload.accounts,
                activeRatesheet: payload.ratesheets[0],
                businessHoursMax: Date.parse(payload.time_max),
                businessHoursMin: Date.parse(payload.time_min),
                chargeTypes: payload.charge_types,
                drivers: payload.drivers,
                employees: payload.employees,
                key: window.location.hash ? window.location.hash.substr(1) : initialState.key,
                permissions: payload.permissions,
                ratesheets: payload.ratesheets
            })
        case 'CONFIGURE_EXISTING': {
            const {
                accounts,
                activity_log,
                bill,
                delivery_address,
                permissions,
                pickup_address,
                repeat_intervals
            } = payload

            // const thisBillIndex = payload.sortedBills
            let newState = {
                accounts: accounts,
                billId: bill.bill_id,
                delivery: {
                    ...state.delivery,
                    account: state.accounts.find(account => account.account_id === bill.delivery_account_id),
                    addressFormatted: delivery_address.formatted,
                    addressLat: delivery_address.lat,
                    addressLng: delivery_address.lng,
                    addressName: delivery_address.name,
                    addressPlaceId: delivery_address.place_id,
                    addressType: bill.delivery_account_id ? 'Account' : 'Search',
                    reference_value: bill.delivery_reference_value,
                    timeActual: Date.parse(bill.time_delivered),
                    timeScheduled: Date.parse(bill.time_delivery_scheduled)
                },
                deliveryType: state.deliveryTypes.find(type => type.id === bill.delivery_type),
                description: bill.description ? bill.description : '',
                percentComplete: bill.percentage_complete,
                permissions: permissions,
                pickup: {
                    ...state.pickup,
                    account: state.accounts.find(account => account.account_id === bill.pickup_account_id),
                    addressFormatted: pickup_address.formatted,
                    addressLat: pickup_address.lat,
                    addressLng: pickup_address.lng,
                    addressName: pickup_address.name,
                    addressPlaceId: pickup_address.place_id,
                    addressType: bill.pickup_account_id ? 'Account' : 'Search',
                    referenceValue: bill.pickup_reference_value,
                    timeActual: Date.parse(bill.time_picked_up),
                    timeScheduled: Date.parse(bill.time_pickup_scheduled)
                },
                readOnly: permissions.viewBasic && !permissions.editBasic
            }

            if(permissions.viewActivityLog)
                newState.activityLog = activity_log

            if(permissions.viewDispatch) {
                newState.delivery.driver = state.drivers.find(driver => driver.employee_id === bill.delivery_driver_id)
                newState.delivery.driverCommission = bill.delivery_driver_commission
                newState.internalComments = bill.internal_comments ? bill.internal_comments : ''
                newState.pickup.driver = state.drivers.find(driver => driver.employee_id === bill.pickup_driver_id)
                newState.pickup.driverCommission = bill.pickup_driver_commission
                newState.timeDispatched = Date.parse(bill.time_dispatched)
                newState.timeCallReceived = Date.parse(bill.time_call_received)
            }

            if(permissions.editBasic || permissions.editBilling || permissions.editDispatch) {
                newState.incompleteFields = JSON.parse(bill.incomplete_fields)
                newState.updatedAt = Date.parse(bill.updated_at)
            }

            if(permissions.viewBilling) {
                newState.repeatInterval = bill.repeat_interval ? repeat_intervals.find(interval => interval.selection_id === bill.repeat_interval) : ''
                newState.repeatIntervals = repeat_intervals
                newState.skipInvoicing = bill.skip_invoicing
            }

            return Object.assign({}, state, newState)
        }
        case 'SET_ACTIVE_RATESHEET': {
            // CREDIT CARDS WILL USE THE RATESHEET OF THE ACCOUNT THEY ARE LINKED TO :-D
            const deliveryTypes = JSON.parse(payload.delivery_types).map(deliveryType => {return {...deliveryType, time: parseInt(deliveryType.time)}})
            const deliveryType = state.deliveryType ? deliveryTypes.find(type => type.id === state.deliveryType.id) : deliveryTypes[0]
            return Object.assign({}, state, {
                deliveryType,
                deliveryTypes
            })
        }
        case 'SET_BILL_NUMBER':
            return Object.assign({}, state, {billNumber: payload})
        case 'SET_DELIVERY_ACCOUNT': {
            return Object.assign({}, state, {
                delivery: {
                    ...state.delivery,
                    account: payload,
                    addressLat: payload.shipping_address ? payload.shipping_address_lat : payload.billing_address_lat,
                    addressLng: payload.shipping_address ? payload.shipping_address_lng : payload.billing_address_lng,
                    addressFormatted: payload.shipping_address ? payload.shipping_address : payload.billing_address,
                    addressName: payload.shipping_address ? payload.shipping_address_name : payload.billing_address_name,
                    addressPlaceId: payload.shipping_address ? payload.shipping_address_place_id : payload.billing_address_place_id
                }
            })
        }
        case 'SET_DELIVERY_DRIVER':
            return Object.assign({}, state, {
                timeDispatched: state.timeDispatched ? state.timeDispatched : new Date(),
                delivery: {
                    ...state.delivery,
                    driver: payload,
                    driverCommission: state.delivery.driverCommission ? state.delivery.driverCommission : parseInt(payload.delivery_commission)}
            })
        case 'SET_DELIVERY_TIME_EXPECTED':
            return Object.assign({}, state, {delivery: {...state.delivery, timeScheduled: payload}})
        case 'SET_DELIVERY_TYPE':
            if(state.applyRestrictions)
                return Object.assign({}, state, {
                    deliveryType: payload,
                    delivery: {
                        ...state.delivery,
                        timeScheduled: DateTime.fromJSDate(state.pickup.timeScheduled).plus({hours: payload.time}).toJSDate()
                    }
                })
            else
                return Object.assign({}, state, {deliveryType: payload})
        case 'SET_DELIVERY_VALUE':
            return Object.assign({}, state, {
                delivery: {...state.delivery, [payload.name]: payload.value}
            })
        case 'SET_DESCRIPTION':
            return Object.assign({}, state, {description: payload})
        case 'SET_INTERNAL_COMMENTS':
            return Object.assign({}, state, {internalComments: payload})
        case 'SET_PICKUP_ACCOUNT': {
            return Object.assign({}, state, {
                pickup: {
                    ...state.pickup,
                    account: payload,
                    addressLat: payload.shipping_address ? payload.shipping_address_lat : payload.billing_address_lat,
                    addressLng: payload.shipping_address ? payload.shipping_address_lng : payload.billing_address_lng,
                    addressFormatted: payload.shipping_address ? payload.shipping_address : payload.billing_address,
                    addressName: payload.shipping_address ? payload.shipping_address_name : payload.billing_address_name,
                    addressPlaceId: payload.shipping_address ? payload.shipping_address_place_id : payload.billing_address_place_id
                }
            })
        }
        case 'SET_PICKUP_DRIVER':
            return Object.assign({}, state, {
                timeDispatched: state.timeDispatched ? state.timeDispatched : new Date(),
                pickup: {
                    ...state.pickup,
                    driver: payload,
                    driverCommission: state.pickup.driverCommission ? state.pickup.driverCommission : parseInt(payload.pickup_commission)
                },
                delivery: state.delivery.driver ? state.delivery : {
                    ...state.delivery,
                    driver: payload,
                    driverCommission: parseInt(payload.delivery_commission)
                }
            })
        case 'SET_PICKUP_TIME_EXPECTED': {
            if(state.applyRestrictions) {
                const {businessHoursMax, businessHoursMin, deliveryTypes} = state
                // round to nearest 15 minutes
                let pickupTimeScheduled = DateTime.fromJSDate(payload)
                if(pickupTimeScheduled.minute % 15 != 0) {
                    const intervals = Math.ceil(pickupTimeScheduled.minute / 15)
                    pickupTimeScheduled = intervals == 4 ? pickupTimeScheduled.set({minute: 0}).plus({hours: 1}) : pickupTimeScheduled.set({minute: intervals * 15})
                }
                pickupTimeScheduled = getValidPickupTime(pickupTimeScheduled, businessHoursMin, businessHoursMax, deliveryTypes)
                const luxonBusinessHoursMax = DateTime.fromJSDate(businessHoursMax)
                const lastDeliveryTime = pickupTimeScheduled.set({hour: luxonBusinessHoursMax.hour, minute: luxonBusinessHoursMax.minute})
                const hoursBetweenPickupAndEndOfDay = lastDeliveryTime.diff(pickupTimeScheduled, 'minutes').as('hours')
                const sortedDeliveryTypes = deliveryTypes.sort((a, b) => a.time < b.time ? 1 : -1)
                const deliveryType = sortedDeliveryTypes.find(type => type.time <= hoursBetweenPickupAndEndOfDay)
                if(deliveryType) {
                    const deliveryTimeScheduled = pickupTimeScheduled.plus({hours: deliveryType.time})
                    return Object.assign({}, state, {
                        pickup: {...state.pickup, timeScheduled: pickupTimeScheduled.toJSDate()},
                        delivery: {...state.delivery, timeScheduled: deliveryTimeScheduled.toJSDate()},
                        deliveryType: deliveryType
                    })
                } else {
                    toastr.error('An error has occurred with restricted time settings. Please contact support and describe the action you were attempting to perform')
                    return state
                }
            } else
                return Object.assign({}, state, {pickup: {...state.pickup, timeScheduled: payload}})
        }
        case 'SET_PICKUP_VALUE':
            return Object.assign({}, state, {pickup: {...state.pickup, [payload.name]: payload.value}})
        case 'SET_REPEAT_INTERVAL':
            return Object.assign({}, state, {repeatInterval: payload})
        case 'SET_TAB_KEY':
            return Object.assign({}, state, {key: payload})
        case 'SET_TIME_CALL_RECEIVED':
            return Object.assign({}, state, {timeCallReceived: payload})
        case 'TOGGLE_ACCEPT_TERMS_AND_CONDITIONS':
            return Object.assign({}, state , {acceptTermsAndConditions: !state.acceptTermsAndConditions})
        case 'TOGGLE_PERSIST_FIELD': {
            const persistFields = state.persistFields.map(persistField => {
                if(action.name === persistField.name)
                    return {...persistField, checked: action.checked}
                return persistField
            })
            localStorage.setItem("persistFields", JSON.stringify(persistFields))
            return Object.assign({}, state, {persistFields})
        }
        case 'TOGGLE_READ_ONLY':
            return Object.assign({}, state, {readOnly: payload})
        case 'TOGGLE_RESTRICTIONS':
            toastr.clear()
            if(state.applyRestrictions)
                toastr.error('Restrictions lifted, some autocomplete functionality has been disabled. Please review all work carefully for accuracy before submitting', 'WARNING', {'timeOut' : 0, 'extendedTImeout' : 0, positionClass: 'toast-top-center'});
            return Object.assign({}, state, {
                applyRestrictions: !state.applyRestrictions
            })
        case 'TOGGLE_SKIP_INVOICING':
            return Object.assign({}, state, {
                skipInvoicing: !state.skipInvoicing
            })
        default:
            console.log(`ERROR - action of type ${type} was not found`)
            return state
    }
}