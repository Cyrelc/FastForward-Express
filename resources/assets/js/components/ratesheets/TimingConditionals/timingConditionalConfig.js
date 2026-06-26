import {MuiConfig} from '@react-awesome-query-builder/mui'

export const timeActions = [
    {value: 'set', label: 'Set Base Time'},
    {value: 'add', label: 'Add Time'}
]

export const timeValueTypes = [
    {value: 'hours', label: 'Hours'},
    {value: 'field', label: 'Field'}
]

const buildZoneListValues = (mapZones, extraZoneIds) => {
    const zones = []
    const zoneIds = new Set()

    ;(mapZones ?? []).forEach(zone => {
        const zoneId = zone.zoneId ?? zone.zone_id
        if(zoneId === undefined || zoneId === null)
            return
        if(zoneIds.has(zoneId))
            return
        zoneIds.add(zoneId)
        zones.push({
            value: zoneId,
            title: zone.name
        })
    })

    ;(extraZoneIds ?? []).forEach(zoneId => {
        if(zoneIds.has(zoneId))
            return
        zones.push({
            value: zoneId,
            title: `Unknown Zone #${zoneId} (deleted)`
        })
    })

    return zones
}

const buildDeliveryTypeListValues = (deliveryTypes) => {
    const values = []
    const seen = new Set()
    ;(deliveryTypes ?? []).forEach(type => {
        if(type?.value === undefined || type?.value === null)
            return
        if(seen.has(type.value))
            return
        seen.add(type.value)
        values.push({
            value: type.value,
            title: type.label
        })
    })
    return values
}

const buildZoneTypeListValues = (zoneTypes) => {
    const values = []
    const seen = new Set()
    ;(zoneTypes ?? []).forEach(zoneType => {
        if(zoneType?.value === undefined || zoneType?.value === null)
            return
        if(seen.has(zoneType.value))
            return
        seen.add(zoneType.value)
        values.push({
            value: zoneType.value,
            title: zoneType.label
        })
    })
    return values
}

const buildRegionListValues = (mapZones) => {
    const values = []
    const seen = new Set()

    ;(mapZones ?? []).forEach(zone => {
        const region = `${zone.region ?? ''}`.trim()
        if(!region || seen.has(region))
            return
        seen.add(region)
        values.push({
            value: region,
            title: region
        })
    })

    return values
}

const buildTagListValues = (mapZones) => {
    const values = []
    const seen = new Set()

    ;(mapZones ?? []).forEach(zone => {
        const rawTags = zone.tags
        const tags = Array.isArray(rawTags)
            ? rawTags
            : `${rawTags ?? ''}`.split('|')

        tags.forEach(tag => {
            const normalizedTag = `${tag}`.trim()
            if(!normalizedTag || seen.has(normalizedTag))
                return
            seen.add(normalizedTag)
            values.push({
                value: normalizedTag,
                title: normalizedTag
            })
        })
    })

    return values
}

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

export const buildTimingConditionalConfig = ({mapZones, deliveryTypes, extraZoneIds, zoneTypes}) => {
    return {
        ...MuiConfig,
        settings: {
            ...MuiConfig.settings,
        },
        fields: {
            zones_same: {
                label: 'Pickup and Delivery Zones Match',
                type: 'boolean',
                default: true
            },
            zones_crossed: {
                label: 'Zones Crossed',
                type: 'number'
            },
            both_internal: {
                label: 'Pickup and Delivery Both Internal',
                type: 'boolean',
                default: false
            },
            both_external: {
                label: 'Pickup and Delivery Both External',
                type: 'boolean',
                default: false
            },
            one_internal_one_external: {
                label: 'One Internal and One External',
                type: 'boolean',
                default: false
            },
            ratesheet: {
                type: '!struct',
                label: 'Ratesheet',
                subfields: {
                    use_internal_zones_calc: {
                        label: 'Uses Zone Count Crossed',
                        type: 'boolean',
                        default: true
                    }
                }
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
                    external_zone_time_total: {
                        label: 'External Zone Time Total',
                        type: 'number'
                    },
                    default_plus_external_zone_time_total: {
                        label: 'Default + External Zone Time Total',
                        type: 'number'
                    },
                    internal_zone_distance_time: {
                        label: 'Internal Zone Distance Time',
                        type: 'number'
                    },
                    pickup_zone_selected_type_time: {
                        label: 'Pickup Zone Time (Selected Type)',
                        type: 'number'
                    },
                    delivery_zone_selected_type_time: {
                        label: 'Delivery Zone Time (Selected Type)',
                        type: 'number'
                    }
                }
            },
            delivery_type: {
                label: 'Delivery Type',
                type: 'select',
                fieldSettings: {
                    listValues: buildDeliveryTypeListValues(deliveryTypes)
                }
            },
            pickup_address: {
                type: '!struct',
                label: 'Pickup Address',
                subfields: {
                    zone_id: {
                        label: 'Pickup Zone',
                        type: 'select',
                        fieldSettings: {
                            listValues: buildZoneListValues(mapZones, extraZoneIds)
                        }
                    },
                    zone_type: {
                        label: 'Pickup Zone Type',
                        type: 'select',
                        fieldSettings: {
                            listValues: buildZoneTypeListValues(zoneTypes)
                        }
                    },
                    is_internal: {
                        label: 'Pickup Zone Is Internal',
                        type: 'boolean',
                        default: false
                    },
                    is_external: {
                        label: 'Pickup Zone Is External',
                        type: 'boolean',
                        default: false
                    },
                    zone: {
                        type: '!struct',
                        label: 'Pickup Zone Details',
                        subfields: {
                            name: {
                                label: 'Pickup Zone Name',
                                type: 'text',
                                operators: ['equal', 'not_equal', 'like', 'not_like']
                            },
                            region: {
                                label: 'Pickup Zone Region',
                                type: 'select',
                                fieldSettings: {
                                    listValues: buildRegionListValues(mapZones)
                                }
                            },
                            tags_text: {
                                label: 'Pickup Zone Tags (Text)',
                                type: 'text',
                                operators: ['like', 'not_like', 'equal', 'not_equal']
                            },
                            tags: {
                                label: 'Pickup Zone Tags',
                                type: 'multiselect',
                                fieldSettings: {
                                    listValues: buildTagListValues(mapZones)
                                }
                            }
                        }
                    },
                    zone_time: {
                        type: '!struct',
                        label: 'Pickup Zone Time',
                        subfields: buildZoneTimeSubfields(deliveryTypes)
                    }
                }
            },
            delivery_address: {
                type: '!struct',
                label: 'Delivery Address',
                subfields: {
                    zone_id: {
                        label: 'Delivery Zone',
                        type: 'select',
                        fieldSettings: {
                            listValues: buildZoneListValues(mapZones, extraZoneIds)
                        }
                    },
                    zone_type: {
                        label: 'Delivery Zone Type',
                        type: 'select',
                        fieldSettings: {
                            listValues: buildZoneTypeListValues(zoneTypes)
                        }
                    },
                    is_internal: {
                        label: 'Delivery Zone Is Internal',
                        type: 'boolean',
                        default: false
                    },
                    is_external: {
                        label: 'Delivery Zone Is External',
                        type: 'boolean',
                        default: false
                    },
                    zone: {
                        type: '!struct',
                        label: 'Delivery Zone Details',
                        subfields: {
                            name: {
                                label: 'Delivery Zone Name',
                                type: 'text',
                                operators: ['equal', 'not_equal', 'like', 'not_like']
                            },
                            region: {
                                label: 'Delivery Zone Region',
                                type: 'select',
                                fieldSettings: {
                                    listValues: buildRegionListValues(mapZones)
                                }
                            },
                            tags_text: {
                                label: 'Delivery Zone Tags (Text)',
                                type: 'text',
                                operators: ['like', 'not_like', 'equal', 'not_equal']
                            },
                            tags: {
                                label: 'Delivery Zone Tags',
                                type: 'multiselect',
                                fieldSettings: {
                                    listValues: buildTagListValues(mapZones)
                                }
                            }
                        }
                    },
                    zone_time: {
                        type: '!struct',
                        label: 'Delivery Zone Time',
                        subfields: buildZoneTimeSubfields(deliveryTypes)
                    }
                }
            }
        }
    }
}
