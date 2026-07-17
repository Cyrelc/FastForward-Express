import {MuiConfig} from '@react-awesome-query-builder/mui'
import {
    buildAddressSubfields,
    buildDeliveryTypeListValues,
    buildGroupListValues,
    matchedRulesField,
    relationFlagFields,
    sharedQueryBuilderSettings,
} from '../conditionalFieldShared'

const timeSubfields = (prefix) => ({
    day_of_the_week: {
        label: `${prefix} Day of the Week`,
        type: 'select',
        fieldSettings: {
            listValues: [
                {value: 0, title: 'Sunday'},
                {value: 1, title: 'Monday'},
                {value: 2, title: 'Tuesday'},
                {value: 3, title: 'Wednesday'},
                {value: 4, title: 'Thursday'},
                {value: 5, title: 'Friday'},
                {value: 6, title: 'Saturday'},
            ]
        }
    },
    time: {
        label: `${prefix} Time (Scheduled)`,
        type: 'time'
    }
})

/**
 * Builds the pricing conditional query-builder config from the live ratesheet map so zones are
 * chosen from a dropdown (by id) rather than typed by name. Zone/address fields and the dropdown
 * value lists are shared with the timing builder via conditionalFieldShared.
 */
export const buildPricingConditionalConfig = ({mapZones, deliveryTypes, extraZoneIds, zoneGroups, conditionals, conditionalId}) => ({
    ...MuiConfig,
    settings: {
        ...MuiConfig.settings,
        ...sharedQueryBuilderSettings,
    },
    fields: {
        ...relationFlagFields(),
        ...matchedRulesField(conditionals, conditionalId, 'conditional_id'),
        // Road distance for the trip, from Google's recommended driving route - not straight-line, and
        // not the zone-crossing count. Empty when it could not be established, in which case any rule
        // that uses it refuses to price rather than treating the trip as 0 km.
        distance_km: {label: 'Distance Travelled (km)', type: 'number'},
        drive_time_minutes: {label: 'Drive Time (minutes)', type: 'number'},
        delivery_type: {
            label: 'Delivery Type',
            type: 'select',
            fieldSettings: {listValues: buildDeliveryTypeListValues(deliveryTypes)}
        },
        package: {
            type: '!struct',
            label: 'Package',
            subfields: {
                is_pallet: {label: 'Is Pallet', type: 'boolean', default: true},
                is_minimum: {label: 'Is Minimum (no weight charge)', type: 'boolean', defaultValue: true},
                total_weight: {label: 'Total Weight (kg)', type: 'number'},
                // The same weight in pounds, derived from the stored kilograms. Both units are offered
                // so a rule records which one it means, rather than being converted on the way in and
                // out - see ChargeModelFactory::KilogramsToPounds.
                total_weight_lbs: {label: 'Total Weight (lbs)', type: 'number'},
                // Dimensional weight and longest side, each in both units. These used to be offered as a
                // single unitless field apiece and were never populated by the engine at all, so any rule
                // that used them silently never fired; they are wired in now, unit-explicit like weight.
                cubed_weight_kgs: {label: 'Cubed Weight (kg)', type: 'number'},
                cubed_weight_lbs: {label: 'Cubed Weight (lbs)', type: 'number'},
                largest_dimension_cm: {label: 'Longest Side (cm)', type: 'number'},
                largest_dimension_in: {label: 'Longest Side (in)', type: 'number'},
            }
        },
        delivery_address: {
            type: '!struct',
            label: 'Delivery Address',
            subfields: buildAddressSubfields('Delivery', {
                mapZones, extraZoneIds, zoneGroups,
                extraSubfields: {is_mall: {label: 'Delivery Is Mall', type: 'boolean', default: true}}
            })
        },
        pickup_address: {
            type: '!struct',
            label: 'Pickup Address',
            subfields: buildAddressSubfields('Pickup', {
                mapZones, extraZoneIds, zoneGroups,
                extraSubfields: {is_mall: {label: 'Pickup Is Mall', type: 'boolean', default: true}}
            })
        },
        // Resolved per-side, per-selected-type zone costs — usable both as conditions and as the
        // value a 'field' rule contributes.
        pickup_zone_selected_type_cost: {label: 'Pickup Zone Cost (Selected Type)', type: 'number'},
        delivery_zone_selected_type_cost: {label: 'Delivery Zone Cost (Selected Type)', type: 'number'},
        // Zone crossing, resolved from the cheapest group the two zones share. Usable as a condition
        // ("crossed more than 6 zones") or, more usually, as the value of a Set Base Price rule.
        zones_crossed: {label: 'Zones Crossed', type: 'number'},
        zones_crossed_cost: {label: 'Zone Crossing Cost (Selected Type)', type: 'number'},
        crossing_group: {
            label: 'Crossing Group',
            type: 'select',
            fieldSettings: {listValues: buildGroupListValues(zoneGroups)},
        },
        time_delivery_scheduled: {
            type: '!struct',
            label: 'Delivery Time',
            subfields: timeSubfields('Delivery')
        },
        time_pickup_scheduled: {
            type: '!struct',
            label: 'Pickup Time',
            subfields: timeSubfields('Pickup')
        }
    }
})

export const availableTestVariables = [
    {
        dbName: 'total_weight_kgs',
        description: 'The total weight of all packages in the delivery, in kilograms',
        name: `Total Weight (kg)`,
        type: 'number',
        value: '900',
        unit: 'kg'
    },
    {
        dbName: 'total_weight_lbs',
        description: 'The total weight of all packages in the delivery, in pounds',
        name: `Total Weight (lbs)`,
        type: 'number',
        value: '1984',
        unit: 'lbs'
    },
    {
        dbName: 'cubed_weight_kgs',
        description: 'Dimensional (volumetric) weight of all packages, in kilograms',
        name: 'Cubed Weight (kg)',
        type: 'number',
        value: '30',
        unit: 'kg'
    },
    {
        dbName: 'cubed_weight_lbs',
        description: 'Dimensional (volumetric) weight of all packages, in pounds',
        name: 'Cubed Weight (lbs)',
        type: 'number',
        value: '66',
        unit: 'lbs'
    },
    {
        dbName: 'largest_dimension_cm',
        description: 'The single longest side across every package, in centimetres',
        name: 'Longest Side (cm)',
        type: 'number',
        value: '120',
        unit: 'cm'
    },
    {
        dbName: 'largest_dimension_in',
        description: 'The single longest side across every package, in inches',
        name: 'Longest Side (in)',
        type: 'number',
        value: '47',
        unit: 'in'
    },
    {
        dbName: 'distance_km',
        description: 'Road distance of the trip, by Google\'s recommended driving route',
        name: 'Distance Travelled',
        type: 'number',
        value: '18.2',
        unit: 'km'
    },
    {
        dbName: 'drive_time_minutes',
        description: 'Estimated driving time for that route',
        name: 'Drive Time',
        type: 'number',
        value: '24',
        unit: 'min'
    }
]

// 'add' and the legacy 'charge' are one and the same operation (add to the price); they are
// combined here into a single "Add Charge" option. The engine still treats a stored 'charge' as
// an add for any older rows.
export const actions = [
    {value: 'add', label: 'Add Charge'},
    {value: 'set', label: 'Set Base Price'},
    {value: 'discount', label: 'Discount'},
    // Produces no charge at all: it refuses to price the trip and shows its message to staff, the same
    // way an address outside every zone does. Evaluated before any pricing rule, whatever its priority.
    {value: 'refuse', label: 'Refuse to Price'},
]

export const REFUSE_ACTION = 'refuse'
export const MESSAGE_VALUE_TYPE = 'message'

// One line each, shown under the selector the author just used. Deliberately phrased as what the rule
// will DO to this bill, not as what the option is called - the label already says that.
export const actionDescriptions = {
    add: 'Adds a line item to the bill. The usual choice: surcharges, weight, after hours.',
    set: 'Replaces the base price. If an earlier rule (lower priority number) already set the base, this one is skipped rather than added on top.',
    discount: 'Subtracts from the running total. Never takes the bill below $0.',
    refuse: 'Produces no price at all. Shows your message and blocks the customer from submitting. Runs before every pricing rule, whatever its priority.',
}

export const valueTypeDescriptions = {
    amount: 'A fixed dollar figure you type in.',
    percent: 'A percentage of the rest of the bill. Percent rules always run last, on the accumulated total.',
    field: 'A figure taken from the delivery itself - a zone\'s rate, or the shared group\'s crossing cost. Use this instead of retyping numbers that already live on the map.',
    equation: 'A formula, e.g. floor(max(0, total_weight_lbs - 100) / 100) * 2 + 16.',
    message: 'The text staff and customers are shown when this rule refuses the trip.',
}

export const valueTypes = [
    {value: 'amount', label: 'Amount'},
    {value: 'field', label: 'Field'},
    {value: 'percent', label: 'Percent'},
    {value: 'equation', label: 'Equation'},
    // Only ever paired with the Refuse action; the "value" is the message staff are shown.
    {value: MESSAGE_VALUE_TYPE, label: 'Message'},
]

// The context fields a 'field' value type can resolve its value from. Money only: whatever is picked
// here becomes a price, so a field that is not a cost (zones_crossed, a count) has no business being
// offered - it remains available as a *condition*, which is what a count is actually for.
/**
 * Values a rule's name may interpolate with {{...}}, so a line item can describe the delivery rather
 * than the rule: "{{zones_crossed}} zones crossed" reads as "4 zones crossed" on the customer's
 * invoice, where "Zones Crossed" told them nothing.
 *
 * Any path in the pricing context works - these are just the ones worth offering. A placeholder that
 * cannot be resolved for a given trip renders as nothing rather than leaking braces onto an invoice,
 * so a name that leans on a value the trip lacks degrades to a shorter label instead of breaking.
 */
export const nameVariables = [
    {token: 'zones_crossed', label: 'Zones crossed', example: '4'},
    {token: 'crossing_group', label: 'Zone group that priced the crossing', example: 'City of Edmonton'},
    {token: 'zones_crossed_cost', label: 'Crossing cost', example: '10.30'},
    {token: 'pickup_address.zone.name', label: 'Pickup zone name', example: 'Zone 1'},
    {token: 'delivery_address.zone.name', label: 'Delivery zone name', example: 'Spruce Grove'},
    {token: 'delivery_type.name', label: 'Delivery type', example: 'Regular'},
    {token: 'package.total_weight_lbs', label: 'Total weight (lbs)', example: '150'},
    {token: 'package.total_weight', label: 'Total weight (kg)', example: '68'},
    {token: 'distance_km', label: 'Distance travelled (km)', example: '18.2'},
    {token: 'drive_time_minutes', label: 'Drive time (minutes)', example: '24'},
]

export const fieldValueOptions = [
    {
        value: 'pickup_zone_selected_type_cost',
        label: 'Pickup Zone Cost (Selected Type)',
        description: 'The pickup zone\'s cost modifier for the currently selected delivery type.'
    },
    {
        value: 'delivery_zone_selected_type_cost',
        label: 'Delivery Zone Cost (Selected Type)',
        description: 'The delivery zone\'s cost modifier for the currently selected delivery type.'
    },
    {
        value: 'zones_crossed_cost',
        label: 'Zone Crossing Cost (Selected Type)',
        description: 'What it costs to cross the zones between pickup and delivery, from the shared '
            + 'group\'s crossing rates, for the selected delivery type. Pair this with "Set Base Price" '
            + 'and a "Share a Zone Group" condition. If the two zones share several groups, the '
            + 'cheapest crossing is used. The trip cannot be priced automatically if the zones share a '
            + 'group but no route or rate exists for it.'
    },
]

export const conditionFieldDescriptions = {
    zones_same: 'True when pickup and delivery resolve to the exact same zone id.',
    distance_km: 'Road distance between pickup and delivery, by Google\'s recommended driving route. '
        + 'This is real road distance, not straight-line, and has nothing to do with zones crossed - it '
        + 'works even when an address falls outside every zone. Empty if it could not be established '
        + '(no route, or Google unreachable), and a rule that uses it then refuses to price rather than '
        + 'treating the trip as 0 km.',
    drive_time_minutes: 'Estimated driving time for that same route, in minutes. Free traffic-unaware '
        + 'estimate from the same lookup as the distance.',
    delivery_type: 'The currently selected delivery type id (Regular, Rush, Direct, etc).',
    'package.is_minimum': 'True when the bill is priced at the minimum, which skips the weight charge entirely.',
    'package.total_weight': 'Total weight of all packages, in kilograms — the unit it is stored in.',
    'package.total_weight_lbs': 'The same total weight, in pounds. Author in whichever unit you think in: both read the one stored weight, so nothing is converted or rounded when the rule is saved.',
    'pickup_address.zone.zone_id': 'Resolved pickup zone id from pickup coordinates and the active ratesheet map.',
    'pickup_address.zone.groups': 'The zone groups the pickup zone belongs to.',
    'pickup_address.zone.tags': 'Pickup zone tags as a normalized list.',
    'delivery_address.zone.zone_id': 'Resolved delivery zone id from delivery coordinates and the active ratesheet map.',
    'delivery_address.zone.groups': 'The zone groups the delivery zone belongs to.',
    'delivery_address.zone.tags': 'Delivery zone tags as a normalized list.',
    'pickup_zone_selected_type_cost': 'Pickup zone cost modifier for the selected delivery type.',
    'delivery_zone_selected_type_cost': 'Delivery zone cost modifier for the selected delivery type.',
}
