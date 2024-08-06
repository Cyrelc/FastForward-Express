import {DateTime} from 'luxon'
import {toast} from 'react-toastify'

const getDeliveryEstimates = (deliveryTypes, pickupZone = null, deliveryZone = null) => {
    return deliveryTypes.map(deliveryType => {
        const originalTime = deliveryType.originalTime ? deliveryType.originalTime : parseFloat(deliveryType.time)
        let time = originalTime
        if(pickupZone?.additional_time)
            time += parseFloat(pickupZone.additional_time)
        if(deliveryZone?.additional_time)
            time += parseFloat(deliveryZone.additional_time)
        return {
            ...deliveryType,
            originalTime,
            time
        }}
    )
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
    isMall: false,
    personName: '',
    referenceValue: '',
    timeActual: '',
    timeScheduled: new Date(),
    zone: null
}

export const initialState = {
    acceptTermsAndConditions: false,
    accounts: [],
    addressTypes: ['Search', 'Account', 'Manual'],
    applyRestrictions: true,
    billId: '',
    billNumber: '',
    businessHoursMin: DateTime.now(),
    businessHoursMax: DateTime.now(),
    delivery: initialPickupDelivery,
    deliveryType: '',
    deliveryTypes: [],
    description: '',
    incompleteFields: [],
    internalComments: '',
    isLoading: true,
    isTemplate: false,
    key: 'basic',
    nextBillId: null,
    permissions: [],
    persistFields: initialPersistFields,
    pickup: initialPickupDelivery,
    prevBillId: null,
    // readOnly: true,
    repeatInterval: undefined,
    repeatIntervals: [],
    skipInvoicing: false,
    timeCallReceived: null,
    timeDispatched: null,
    timeTenFoured: null,
    updatedAt: ''
}

export default function billReducer(state, action) {
    const {type, payload} = action

    /**
     * Checks if a requested time is a valid request based on: business hours, and deliveryTypes
     * @param {DateTime} dateTime (luxon DateTime object)
     * @param {DateTime} businessHoursMin
     * @param {DateTime} businessHoursMax
     * @param {Array[Object]} deliveryTypes
     *
     * @returns {Boolean}
     */
    const isPickupTimeValid = (dateTime, deliveryTypes, startDate = null) => {
        const compareDate = startDate ?? DateTime.now();
        // If requested time is in the past
        if(dateTime.diff(compareDate, 'minutes').minutes < 0)
            return false
        // If requested time is a weekend (6 = Saturday, 7 = Sunday) See moment.github.io/luxon/api-docs/index.html#datetimeweekday for more details
        if(dateTime.weekday === 6 || dateTime.weekday === 7)
            return false
        // If requested time is AFTER business hours - (modified for shortest delivery window)
        const minimumTimeToDoADelivery = deliveryTypes.reduce((minimum, type) => type.time < minimum ? type.time : minimum, deliveryTypes[0].time)
        const adjustedBusinessHoursMax = state.businessHoursMax.minus({hours: minimumTimeToDoADelivery})
        const lastPickupTime = dateTime.set({hour: adjustedBusinessHoursMax.hour, minute: adjustedBusinessHoursMax.minute})
        if(dateTime.diff(lastPickupTime, 'minutes').minutes > 0)
            return false
        // If requested time is BEFORE business hours - (no modification required)
        const firstPickupTime = dateTime.set({hour: state.businessHoursMin.hour, minute: state.businessHoursMin.minute})
        if(dateTime.diff(firstPickupTime, 'minutes').minutes < 0)
            return false

        return true
    }

    /**
     * Takes the current state, and a requested time value, and will return the **next** valid option 
     * after testing recursively in 15 minute increments
     * @param {DateTime} time
     * @param {array} deliveryTypes
     * @returns {DateTime}
     */
    const getValidPickupTime = (time, deliveryTypes, prevTime = null) => {
        const originalTime = time
        prevTime = DateTime.fromJSDate(prevTime)
        if(time instanceof Date)
            time = DateTime.fromJSDate(time)
        //if valid, return, otherwise recursively add 15 minutes until you find one that is valid!
        while(!isPickupTimeValid(time, deliveryTypes, prevTime))
            time = time.plus({minutes: 15})

        if(!time.hasSame(originalTime, 'day')) {
            toast.warn(
                'There is insufficient time remaining today to perform the delivery you have requested, so we have automatically assigned it to the next business day.\n\nIf you believe you are receiving this in error, please give us a call',
                {
                    position: 'top-center',
                    toastId: `${state.billId}-insufficient-time-remaining`,
                }
            )
        }

        return time
    }

    switch(action.type) {
        case 'CHECK_REFERENCE_VALUES': {
            const {account, value, prevValue} = payload
            return Object.assign({}, state, {
                pickup: {...state.pickup, referenceValue: (state.pickup.account?.account_id === account.account_id && state.pickup.referenceValue === prevValue) ? value : state.pickup.referenceValue},
                delivery: {...state.delivery, referenceValue: (state.delivery.account?.account_id === account.account_id && state.delivery.referenceValue === prevValue) ? value : state.delivery.referenceValue}
            })
        }
        case 'CONFIGURE_BILL':
            return Object.assign({}, state, {
                ...initialState,
                accounts: payload.accounts,
                activeRatesheet: payload.ratesheets[0],
                businessHoursMax: DateTime.fromISO(payload.time_max),
                businessHoursMin: DateTime.fromISO(payload.time_min),
                chargeTypes: payload.charge_types,
                drivers: payload.drivers,
                employees: payload.employees,
                key: window.location.hash ? window.location.hash.substr(1) : initialState.key,
                permissions: payload.permissions,
                ratesheets: payload.ratesheets,
                activityLog: null
            })
        case 'CONFIGURE_COPY': {
            let newState = {
                pickup: {
                    account: payload.bill?.pickup_account_id ? state.accounts.find(account => payload.bill.pickup_account_id == account.account_id) : '',
                    addressFormatted: payload.pickup_address?.formatted ?? state.pickup.addressFormatted,
                    isMall: payload.pickup_address?.is_mall ?? state.pickup.addressIsMall,
                    addressLat: payload.pickup_address?.lat ??  state.pickup.addressLat,
                    addressLng: payload.pickup_address?.lng ?? state.pickup.addressLng,
                    addressName: payload.pickup_address?.name ?? state.pickup.addressName ,
                    addressPlaceId: payload.pickup_address?.place_id ?? state.pickup.addressPlaceId,
                    addressType: payload.bill?.pickup_account_id ? 'Account' : 'Search',
                    driver: '',
                    driverCommission: '',
                    referenceValue: payload.bill?.pickup_reference_value ?? state.pickup.referenceValue,
                    timeActual: '',
                    timeScheduled: new Date(),
                    zone: null
                },
                delivery: {
                    account: payload.bill?.delivery_account_id ? state.accounts.find(account => payload.bill.delivery_account_id == account.account_id) : '',
                    addressFormatted: payload.delivery_address?.formatted ?? state.delivery.addressFormatted,
                    isMall: payload.delivery_address?.is_mall ?? state.delivery.addressIsMall,
                    addressLat: payload.delivery_address?.lat ??  state.delivery.addressLat,
                    addressLng: payload.delivery_address?.lng ?? state.delivery.addressLng,
                    addressName: payload.delivery_address?.name ?? state.delivery.addressName ,
                    addressPlaceId: payload.delivery_address?.place_id ?? state.delivery.addressPlaceId,
                    addressType: payload.bill?.delivery_account_id ? 'Account' : 'Search',
                    driver: '',
                    driverCommission: '',
                    referenceValue: payload.bill?.delivery_reference_value ?? state.delivery.referenceValue,
                    timeActual: '',
                    timeScheduled: new Date(),
                    zone: null
                },
                readOnly: payload.permissions.create
            }

            return Object.assign({}, state, newState)
        }
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

            let newState = {
                accounts: accounts,
                billId: bill.bill_id,
                billNumber: bill.bill_number,
                delivery: {
                    ...state.delivery,
                    account: state.accounts.find(account => account.account_id === bill.delivery_account_id),
                    addressFormatted: delivery_address.formatted,
                    addressLat: delivery_address.lat,
                    addressLng: delivery_address.lng,
                    addressName: delivery_address.name,
                    addressPlaceId: delivery_address.place_id,
                    addressType: bill.delivery_account_id ? 'Account' : 'Search',
                    isMall: delivery_address.is_mall,
                    personName: bill.delivery_person_name,
                    referenceValue: bill.delivery_reference_value,
                    timeActual: bill.time_delivered ? new Date(bill.time_delivered) : '',
                    timeScheduled: new Date(bill.time_delivery_scheduled)
                },
                deliveryType: state.deliveryTypes.find(type => type.id === bill.delivery_type),
                description: bill.description ? bill.description : '',
                isTemplate: bill.is_template,
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
                    isMall: pickup_address.is_mall,
                    personName: bill.pickup_person_name,
                    referenceValue: bill.pickup_reference_value,
                    timeActual: bill.time_picked_up ? new Date(bill.time_picked_up) : '',
                    timeScheduled: new Date(bill.time_pickup_scheduled)
                },
                readOnly: permissions.viewBasic && !permissions.editBasic,
                timeTenFoured: bill.time_ten_coured ? new Date(bill.time_ten_foured) : ''
            }

            if(permissions.viewActivityLog)
                newState.activityLog = activity_log

            if(permissions.viewDispatch) {
                newState.delivery.driver = bill.delivery_driver_id ? state.drivers.find(driver => driver.value === bill.delivery_driver_id) : ''
                newState.delivery.driverCommission = bill.delivery_driver_commission
                newState.internalComments = bill.internal_comments ? bill.internal_comments : ''
                newState.pickup.driver = bill.pickup_driver_id ? state.drivers.find(driver => driver.value === bill.pickup_driver_id) : ''
                newState.pickup.driverCommission = bill.pickup_driver_commission
                newState.timeDispatched = bill.time_dispatched ? new Date(bill.time_dispatched) : null,
                newState.timeCallReceived = new Date(bill.time_call_received)
            }

            if(permissions.editBasic || permissions.editBilling || permissions.editDispatch) {
                newState.incompleteFields = JSON.parse(bill.incomplete_fields)
                newState.updatedAt = new Date(bill.updated_at)
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
            const deliveryTypes = getDeliveryEstimates(JSON.parse(payload.delivery_types))
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
            let newCommission = state.delivery.driverCommission
            if(!state.delivery.driverCommission || state.delivery.driver.delivery_commission == state.delivery.driverCommission)
                newCommission = payload ? parseInt(payload.delivery_commission) : 0
            return Object.assign({}, state, {
                timeDispatched: state.timeDispatched ?? new Date(),
                delivery: {
                    ...state.delivery,
                    driver: payload,
                    driverCommission: newCommission
                }
            })
        case 'SET_DELIVERY_TIME_EXPECTED':
            return Object.assign({}, state, {delivery: {...state.delivery, timeScheduled: payload}})
        case 'SET_DELIVERY_TYPE':
            if(state.applyRestrictions) {
                const pickupTimeScheduled = getValidPickupTime(state.pickup.timeScheduled, [payload], state.timeCallReceived)
                return Object.assign({}, state, {
                    deliveryType: payload,
                    delivery: {
                        ...state.delivery,
                        timeScheduled: pickupTimeScheduled.plus({hours: payload.time}).toJSDate()
                    },
                    pickup: {
                        ...state.pickup,
                        timeScheduled: pickupTimeScheduled.toJSDate()
                    }
                })
            } else
                return Object.assign({}, state, {deliveryType: payload})
        case 'SET_DELIVERY_VALUE':
            return Object.assign({}, state, {
                delivery: {...state.delivery, [payload.name]: payload.value}
            })
        case 'SET_DELIVERY_ZONE': {
            const deliveryTypes = getDeliveryEstimates(state.deliveryTypes, payload, state.pickup.zone)
            return Object.assign({}, state, {
                delivery: {...state.delivery, zone: payload},
                deliveryTypes: deliveryTypes,
                deliveryType: deliveryTypes.find(type => type.id == state.deliveryType.id)
            })
        }
        case 'SET_DESCRIPTION':
            return Object.assign({}, state, {description: payload})
        case 'SET_INTERNAL_COMMENTS':
            return Object.assign({}, state, {internalComments: payload})
        case 'SET_IS_TEMPLATE':
            return Object.assign({}, state, {isTemplate: payload})
        case 'SET_IS_LOADING':
            return Object.assign({}, state, {isLoading: payload})
        case 'SET_NEXT_BILL_ID':
            return Object.assign({}, state, {nextBillId: payload})
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
            let newPickupCommission = state.pickup.driverCommission
            if(!state.pickup.driverCommission || state.pickup.driver.delivery_commission == state.pickup.driverCommission)
                newPickupCommission = payload ? parseInt(payload.pickup_commission) : 0
            let newDeliveryCommission = state.delivery.driverCommission
            if(!state.delivery.driverCommission || state.delivery.driver.delivery_commission == state.delivery.driverCommission)
                newDeliveryCommission = payload ? parseInt(payload.delivery_commission) : 0
            return Object.assign({}, state, {
                timeDispatched: state.timeDispatched ?? new Date(),
                pickup: {
                    ...state.pickup,
                    driver: payload,
                    driverCommission: newPickupCommission
                },
                delivery: state.delivery.driver ? state.delivery : {
                    ...state.delivery,
                    driver: payload,
                    driverCommission: newDeliveryCommission
                }
            })
        case 'SET_PICKUP_TIME_EXPECTED': {
            if(state.applyRestrictions) {
                const {businessHoursMax, deliveryTypes} = state
                // round to nearest 15 minutes
                let pickupTimeScheduled = DateTime.fromJSDate(payload)
                if(pickupTimeScheduled.minute % 15 != 0) {
                    const intervals = Math.ceil(pickupTimeScheduled.minute / 15)
                    pickupTimeScheduled = intervals == 4 ? pickupTimeScheduled.set({minute: 0}).plus({hours: 1}) : pickupTimeScheduled.set({minute: intervals * 15})
                }
                pickupTimeScheduled = getValidPickupTime(pickupTimeScheduled, deliveryTypes, state.timeCallReceived)
                const lastDeliveryTime = pickupTimeScheduled.set({hour: businessHoursMax.hour, minute: businessHoursMax.minute})
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
                    toast.error('An error has occurred with restricted time settings. Please contact support and describe the action you were attempting to perform')
                    return state
                }
            } else
                return Object.assign({}, state, {pickup: {...state.pickup, timeScheduled: payload}})
        }
        case 'SET_PICKUP_VALUE':
            return Object.assign({}, state, {pickup: {...state.pickup, [payload.name]: payload.value}})
        case 'SET_PICKUP_ZONE': {
            const deliveryTypes = getDeliveryEstimates(state.deliveryTypes, payload, state.delivery.zone)
            return Object.assign({}, state, {
                deliveryType: deliveryTypes.find(type => type.id == state.deliveryType.id),
                deliveryTypes: deliveryTypes,
                pickup: {...state.pickup, zone: payload}
            })
        }
        case 'SET_PREV_BILL_ID':
            return Object.assign({}, state, {prevBillId: payload})
        case 'SET_REPEAT_INTERVAL':
            return Object.assign({}, state, {repeatInterval: payload})
        case 'SET_TAB_KEY':
            window.location.hash = payload
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
            if(state.applyRestrictions)
                toast.error('Restrictions lifted, some autocomplete functionality has been disabled. Please review all work carefully for accuracy before submitting',
                {
                    position: 'top-center',
                    autoClose: false
                });
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
