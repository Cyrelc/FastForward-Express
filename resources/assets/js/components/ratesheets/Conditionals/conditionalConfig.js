import {BootstrapConfig} from '@react-awesome-query-builder/bootstrap'

const addressSubfields = (prefix) => {
    return {
        zone: {
            type: '!struct',
            label: 'Zone',
            label2: `${prefix} Zone`,
            subfields: {
                name: {
                    label: `${prefix} Zone Name`,
                    type: 'text',
                    operators: [
                        'equal',
                        'not_equal',
                        'like',
                        'not_like'
                    ]
                },
                type: {
                    label: `${prefix} Zone Type`,
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
            label: `${prefix} Is Mall`,
            type: 'boolean',
            default: true
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
    ...BootstrapConfig,
    settings: {
        ...BootstrapConfig.settings,
    },
    fields: {
        package: {
            type: '!struct',
            label: 'Package',
            subfields: {
                is_pallet: {
                    label: 'Is Pallet',
                    type: 'boolean',
                    default: true
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
            subfields: addressSubfields('Delivery Address')
        },
        pickup_address: {
            type: '!struct',
            label: 'Pickup Address',
            subfields: addressSubfields('Pickup Address')
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
        value: '900 kg'
    },
    {
        dbName: 'longest_side',
        description: 'The longest dimension of the largest package',
        name: 'Longest Side',
        type: 'number',
        value: '3 m'
    }
]

export const valueTypes = [
    {value: 'percent', label: 'Percent'},
    {value: 'amount', label: 'Amount'},
    {value: 'equation', label: 'Equation'}
]

export default config
