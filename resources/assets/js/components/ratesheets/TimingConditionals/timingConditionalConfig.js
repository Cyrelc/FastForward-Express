import {MuiConfig} from '@react-awesome-query-builder/mui'
import {
    buildAddressSubfields,
    buildDeliveryTypeListValues,
    buildGroupListValues,
    relationFlagFields,
    sharedQueryBuilderSettings,
} from '../conditionalFieldShared'

export const timeActions = [
    {value: 'set', label: 'Set Base Time'},
    {value: 'add', label: 'Add Time'}
]

export const timeValueTypes = [
    {value: 'hours', label: 'Hours'},
    {value: 'field', label: 'Field'}
]

const buildZoneTimeSubfields = (deliveryTypes) => {
    const subfields = {}
    ;(deliveryTypes ?? []).forEach(type => {
        if(type?.value === undefined || type?.value === null)
            return
        subfields[`type_${type.value}`] = {
            label: `Time (${type.label})`,
            type: 'number'
        }
    })
    return subfields
}

export const buildTimingConditionalConfig = ({mapZones, deliveryTypes, extraZoneIds, zoneGroups}) => {
    // Timing adds its per-type zone-time map alongside the shared zone/address fields.
    const zoneTimeSubfield = {
        zone_time: {
            type: '!struct',
            label: 'Zone Time',
            subfields: buildZoneTimeSubfields(deliveryTypes)
        }
    }

    return {
        ...MuiConfig,
        settings: {
            ...MuiConfig.settings,
            ...sharedQueryBuilderSettings,
        },
        fields: {
            ...relationFlagFields(),
            zones_crossed: {
                label: 'Zones Crossed',
                type: 'number'
            },
            crossing_group: {
                label: 'Crossing Group',
                type: 'select',
                fieldSettings: {listValues: buildGroupListValues(zoneGroups)},
            },
            base_time: {
                type: '!struct',
                label: 'Base Time',
                subfields: {
                    default: {
                        label: 'Default Time (Selected Delivery Type)',
                        type: 'number'
                    }
                }
            },
            timing_inputs: {
                type: '!struct',
                label: 'Timing Inputs',
                subfields: {
                    zones_crossed_time: {label: 'Zone Crossing Time (Selected Type)', type: 'number'},
                    pickup_zone_selected_type_time: {label: 'Pickup Zone Time (Selected Type)', type: 'number'},
                    delivery_zone_selected_type_time: {label: 'Delivery Zone Time (Selected Type)', type: 'number'}
                }
            },
            delivery_type: {
                label: 'Delivery Type',
                type: 'select',
                fieldSettings: {listValues: buildDeliveryTypeListValues(deliveryTypes)}
            },
            pickup_address: {
                type: '!struct',
                label: 'Pickup Address',
                subfields: buildAddressSubfields('Pickup', {mapZones, extraZoneIds, zoneGroups, extraSubfields: zoneTimeSubfield})
            },
            delivery_address: {
                type: '!struct',
                label: 'Delivery Address',
                subfields: buildAddressSubfields('Delivery', {mapZones, extraZoneIds, zoneGroups, extraSubfields: zoneTimeSubfield})
            }
        }
    }
}
