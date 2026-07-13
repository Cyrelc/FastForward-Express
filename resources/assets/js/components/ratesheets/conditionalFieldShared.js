// Shared field/list builders for the pricing (Conditionals) and timing (TimingConditionals)
// query-builder configs. Both engines evaluate against the same nested context shape
// (pickup_address.zone.tags, pickup_address.zone.zone_id, convenience flags, ...), so their
// builders share one definition of the zone/address fields and the dropdown value lists.

/**
 * Query-builder settings both engines share.
 *
 * showErrorMessage is the important one, and it is not really about error messages. With it false
 * (the library's default) the builder "fixes" any value that fails an operator's validateValues by
 * silently reverting it - and Between validates values[0] <= values[1] on every keystroke. So
 * retyping a lower bound of 23 as 44 gets undone the moment the first "4" makes it exceed the upper
 * bound, and the field reads as though it will not accept typing at all. Turning it on keeps what was
 * typed and reports the problem instead, which is the right time to complain: when the edit is done.
 */
export const sharedQueryBuilderSettings = {
    showErrorMessage: true,
}

/**
 * The priority a new rule should start at: the lowest positive integer not already taken on this
 * ratesheet. Priorities 1, 2, 3, 4, 100, 105 give 5.
 *
 * Priority is the order rules run in, and it decides which rule wins when several want to set the base
 * price (first 'set' wins) - so leaving every new rule on 0 makes ties that get settled arbitrarily.
 *
 * Filling the first gap rather than continuing past the highest keeps the sequence dense and, more to
 * the point, keeps a deliberately-high priority from dragging everything after it. A rule parked at
 * 100 to mean "run last" would otherwise make the next rule 101, and the one after that 102, until
 * every new rule outranks the one that was supposed to be last.
 *
 * Computed from the list the tab has already loaded; there is no call to make for this.
 */
export const nextPriorityFor = (conditionals) => {
    const taken = new Set((conditionals ?? [])
        .map(conditional => parseInt(conditional?.priority, 10))
        .filter(priority => !Number.isNaN(priority)))

    let priority = 1
    while(taken.has(priority))
        priority++

    return priority
}

/**
 * The starting tree for a brand new rule: one group holding one blank condition.
 *
 * A conditional is meaningless without at least one condition, so the author would always have had to
 * click "Add rule" first - the builder starts with an empty group otherwise. This just does it for
 * them.
 *
 * The blank condition survives because the library's removeIncompleteRulesOnLoad defaults to false;
 * were it on, checkTree would strip the very rule we are adding.
 */
export const emptyTreeWithRule = (QbUtils, config) => QbUtils.checkTree(
    QbUtils.loadTree({
        id: QbUtils.uuid(),
        type: 'group',
        children1: {
            [QbUtils.uuid()]: {
                type: 'rule',
                properties: {field: null, operator: null, value: [], valueSrc: []},
            },
        },
    }),
    config
)

const dedupeById = (items, getId) => {
    const seen = new Set()
    const result = []
    ;(items ?? []).forEach(item => {
        const id = getId(item)
        if(id === undefined || id === null || seen.has(id))
            return
        seen.add(id)
        result.push(item)
    })
    return result
}

export const buildZoneListValues = (mapZones, extraZoneIds) => {
    const zones = []
    const zoneIds = new Set()

    ;(mapZones ?? []).forEach(zone => {
        const zoneId = zone.zoneId ?? zone.zone_id
        if(zoneId === undefined || zoneId === null || zoneIds.has(zoneId))
            return
        zoneIds.add(zoneId)
        zones.push({value: zoneId, title: zone.name})
    })

    // Zones referenced by a saved rule but no longer on the map (deleted) still need a label so
    // the rule renders instead of silently dropping its condition.
    ;(extraZoneIds ?? []).forEach(zoneId => {
        if(zoneIds.has(zoneId))
            return
        zones.push({value: zoneId, title: `Unknown Zone #${zoneId} (deleted)`})
    })

    return zones
}

export const buildDeliveryTypeListValues = (deliveryTypes) =>
    dedupeById(deliveryTypes, t => t?.value)
        .filter(t => t?.value !== undefined && t?.value !== null)
        .map(t => ({value: t.value, title: t.label}))

// Groups are picked from the ratesheet's own groups - never typed as free text.
export const buildGroupListValues = (zoneGroups) =>
    (zoneGroups ?? [])
        .filter(zoneGroup => zoneGroup?.zoneGroupId !== undefined && zoneGroup?.zoneGroupId !== null)
        .map(zoneGroup => ({value: zoneGroup.name, title: zoneGroup.name}))

export const buildTagListValues = (mapZones) => {
    const values = []
    const seen = new Set()
    ;(mapZones ?? []).forEach(zone => {
        const rawTags = zone.tags
        const tags = Array.isArray(rawTags) ? rawTags : `${rawTags ?? ''}`.split('|')
        tags.forEach(tag => {
            const normalizedTag = `${tag}`.trim()
            if(!normalizedTag || seen.has(normalizedTag))
                return
            seen.add(normalizedTag)
            values.push({value: normalizedTag, title: normalizedTag})
        })
    })
    return values
}

// Top-level convenience boolean flags shared by both engines.
//
// There is deliberately no "which groups do they share" field here. It looked useful, but it was
// derived from the routable crossings rather than raw membership - so two zones in one group with no
// route between them made same_group true while the shared-group list came back empty. Two fields
// that sound like the same question must not answer it differently. To name a specific group, test
// the pickup/delivery zone's own Groups; to see which group actually priced the trip, use
// crossing_group.
export const relationFlagFields = () => ({
    zones_same: {label: 'Pickup and Delivery Zones Match', type: 'boolean', default: true},
    same_group: {label: 'Pickup and Delivery Share a Zone Group', type: 'boolean', default: true},
})

/**
 * "Matched Rules" - which other rules on this sheet matched this delivery. Lets one rule be written
 * against another ("NOT CONTAINS [the 0-49lb bracket] AND under 99 lb") instead of restating its
 * condition, so moving a boundary moves it everywhere.
 *
 * A multiselect, matched with CONTAINS - deliberately the same shape as tags and zone groups, so the
 * builder needs no custom operator and the saved logic is the one shape the engine already knows.
 *
 * "Matched" is the rule's CONDITION being true, not it producing a charge: a Set Base Price rule that
 * loses first-set-wins still counts. That keeps a reference a function of the delivery rather than of
 * run order, which is why references need no priority juggling.
 *
 * @param currentId - the rule being edited, excluded so it cannot refer to itself
 */
export const matchedRulesField = (conditionals, currentId, idKey = 'conditional_id') => ({
    matched_rules: {
        label: 'Matched Rules',
        type: 'multiselect',
        fieldSettings: {
            listValues: (conditionals ?? [])
                .filter(conditional => conditional?.[idKey] != null && conditional[idKey] != currentId)
                .map(conditional => ({value: conditional[idKey], title: conditional.name})),
        },
    },
})

/**
 * The nested zone/address fields both builders share. `prefix` is the human label prefix
 * ("Pickup"/"Delivery"); `extraSubfields` lets each builder add engine-specific fields
 * (pricing: is_mall; timing: zone_time). Note: there is intentionally no free-text zone name
 * field — zones are matched by id via the dropdown to eliminate name typos.
 */
export const buildAddressSubfields = (prefix, {mapZones, extraZoneIds, zoneGroups, extraSubfields = {}}) => ({
    zone: {
        type: '!struct',
        label: `${prefix} Zone`,
        subfields: {
            zone_id: {
                label: `${prefix} Zone`,
                type: 'select',
                fieldSettings: {listValues: buildZoneListValues(mapZones, extraZoneIds)},
            },
            groups: {
                label: `${prefix} Zone Groups`,
                type: 'multiselect',
                fieldSettings: {listValues: buildGroupListValues(zoneGroups)},
            },
            tags: {
                label: `${prefix} Zone Tags`,
                type: 'multiselect',
                fieldSettings: {listValues: buildTagListValues(mapZones)},
            },
        },
    },
    ...extraSubfields,
})

// Walks a saved rule's json_logic for zone-id comparisons so deleted zones can still be labelled.
export const extractZoneIdsFromLogic = (jsonLogic) => {
    const zoneIds = new Set()
    const isZoneVar = (node) =>
        node?.var === 'pickup_address.zone.zone_id' || node?.var === 'delivery_address.zone.zone_id'

    const walk = (node) => {
        if(Array.isArray(node)) {
            node.forEach(walk)
            return
        }
        if(!node || typeof node !== 'object' || isZoneVar(node))
            return
        Object.entries(node).forEach(([, value]) => {
            if(Array.isArray(value) && value.some(item => isZoneVar(item))) {
                value.forEach(item => {
                    if(typeof item === 'number')
                        zoneIds.add(item)
                    if(typeof item === 'string' && !isNaN(Number(item)))
                        zoneIds.add(Number(item))
                })
            }
            walk(value)
        })
    }

    walk(jsonLogic)
    return [...zoneIds]
}

export const flattenConditionFields = (fields, descriptions = {}, prefix = '') => {
    const values = []
    Object.entries(fields ?? {}).forEach(([key, field]) => {
        const path = prefix ? `${prefix}.${key}` : key
        if(field?.type === '!struct') {
            values.push(...flattenConditionFields(field.subfields ?? {}, descriptions, path))
            return
        }
        values.push({
            path,
            label: field?.label ?? path,
            description: descriptions[path] ?? 'Context value available for query conditions.',
        })
    })
    return values
}
