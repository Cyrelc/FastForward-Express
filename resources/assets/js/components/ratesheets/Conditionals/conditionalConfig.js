import {MuiConfig} from '@react-awesome-query-builder/mui'

// Labels are intentionally unprefixed: the parent struct ("Delivery Address" /
// "Pickup Address") already supplies the context, so the field reads as
// "Delivery Address / Is Mall" rather than the redundant
// "Delivery Address / Delivery Address Is Mall".
const addressSubfields = () => {
    return {
        zone: {
            type: '!struct',
            label: 'Zone',
            subfields: {
                name: {
                    label: 'Name',
                    type: 'text',
                    operators: [
                        'equal',
                        'not_equal',
                        'like',
                        'not_like'
                    ]
                },
                type: {
                    label: 'Type',
                    type: 'select',
                    fieldSettings: {
                        listValues: [
                            {value: 'internal', title: 'Internal'},
                            {value: 'peripheral', title: 'Peripheral'},
                            {value: 'outlying', title: 'Outlying'},
                        ]
                    }
                }
            }
        },
        is_mall: {
            label: 'Is Mall',
            type: 'boolean',
            defaultValue: true
        }
    }
}

const timeSubfields = (prefix) => {
    return {
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
    }
}

const config = {
    ...MuiConfig,
    settings: {
        ...MuiConfig.settings,
        // The field picker is a MUI Autocomplete whose width is fixed by this
        // setting (library default: 200px), which truncated long field labels
        // (e.g. "The longest side of the object"). Widen it to fit our labels.
        // NB: do NOT switch this to a <Select> via customFieldSelectProps
        // showSearch:false — that swaps in MuiFieldSelect, which infinite-loops
        // when selecting these nested/!struct fields and freezes the tab.
        defaultSelectWidth: '280px',
    },
    fields: {
        package: {
            type: '!struct',
            label: 'Package',
            subfields: {
                is_pallet: {
                    label: 'Is Pallet',
                    type: 'boolean',
                    defaultValue: true
                },
                total_weight: {
                    label: 'Total Weight (kg)',
                    type: 'number'
                },
                largest_dimension: {
                    label: 'The longest side of the object',
                    type: 'number'
                },
                cubed_weight: {
                    label: 'The total cubed weight',
                    type: 'number'
                }
            }
        },
        delivery_address: {
            type: '!struct',
            label: 'Delivery Address',
            subfields: addressSubfields()
        },
        pickup_address: {
            type: '!struct',
            label: 'Pickup Address',
            subfields: addressSubfields()
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
}

export const availableTestVariables = [
    {
        dbName: 'total_weight',
        description: 'The total weight of all packages in the delivery',
        name: `Total Weight`,
        type: 'number',
        value: '900',
        unit: 'kg'
    },
    {
        dbName: 'longest_side',
        description: 'The longest dimension of the largest package',
        name: 'Longest Side',
        type: 'number',
        value: '3',
        unit: 'm'
    }
]

export const valueTypes = [
    {value: 'percent', label: 'Percent'},
    {value: 'amount', label: 'Amount'},
    {value: 'equation', label: 'Equation'}
]

export default config
