// Portable copy/paste format for pricing conditionals. A rule is serialized to a single JSON
// string that carries everything needed to recreate it — name, priority, continue_on, the "then"
// instructions (action / value_type / value / equation), the condition (json_logic), and the
// charge type by *label* rather than its database id so it survives being pasted into a different
// database. Zone conditions reference zone ids, which differ across databases; on import those are
// preserved verbatim and surfaced in the builder (as "Unknown Zone #… (deleted)") for remapping,
// rather than being silently mismatched.

const FORMAT = 'ffe.conditional.v1'

const asObject = (maybeJson, fallback) => {
    if(maybeJson === null || maybeJson === undefined)
        return fallback
    if(typeof maybeJson === 'string') {
        try {
            return JSON.parse(maybeJson)
        } catch (error) {
            return fallback
        }
    }
    return maybeJson
}

const DEFAULT_ACTION = {value: 'add', label: 'Add Charge'}

// Folds the legacy 'charge' action and any stale label into the single canonical "Add Charge",
// so rules copied from older data import with the current naming.
const normalizeAction = (action) => {
    const resolved = action ?? DEFAULT_ACTION
    if(resolved.value === 'add' || resolved.value === 'charge')
        return {value: 'add', label: 'Add Charge'}
    return resolved
}

/**
 * Serializes a conditional (as it appears in the PricingConditionalsTab table) to a portable string.
 * @param conditional - a rule row; action may be an object or JSON string, json_logic is a string
 * @param chargeTypes - the target list ({label, value}) used to record the charge type by label
 */
export const exportConditionalString = (conditional, chargeTypes = []) => {
    const chargeType = (chargeTypes ?? []).find(ct => `${ct.value}` === `${conditional.type}`)

    const envelope = {
        _format: FORMAT,
        name: conditional.name ?? '',
        priority: conditional.priority ?? 0,
        continue_on: conditional.continue_on ?? true,
        action: normalizeAction(asObject(conditional.action, DEFAULT_ACTION)),
        value_type: conditional.value_type ?? 'amount',
        value: conditional.value ?? 0,
        original_equation_string: conditional.original_equation_string ?? null,
        charge_type_label: chargeType ? chargeType.label : null,
        json_logic: asObject(conditional.json_logic, true),
    }

    return JSON.stringify(envelope)
}

/**
 * Parses a portable string into a conditional shaped for the PricingConditionalModal (no conditional_id,
 * so saving it creates a new rule). Throws with a friendly message if the string isn't a rule.
 * @param str - the pasted string
 * @param chargeTypes - the target list ({label, value}); used to re-resolve the charge type id
 */
export const parseConditionalString = (str, chargeTypes = []) => {
    let envelope
    try {
        envelope = JSON.parse(`${str}`.trim())
    } catch (error) {
        throw new Error('That doesn\'t look like a rule — it isn\'t valid JSON.')
    }

    if(!envelope || envelope._format !== FORMAT)
        throw new Error('Unrecognized rule string (missing or wrong format marker).')

    const chargeType = (chargeTypes ?? []).find(ct => ct.label === envelope.charge_type_label)

    return {
        // Intentionally no conditional_id — the modal treats this as a new rule.
        name: envelope.name ?? '',
        priority: envelope.priority ?? 0,
        continue_on: envelope.continue_on ?? true,
        action: normalizeAction(asObject(envelope.action, DEFAULT_ACTION)),
        value_type: envelope.value_type ?? 'amount',
        value: envelope.value ?? 0,
        original_equation_string: envelope.original_equation_string ?? null,
        type: chargeType ? chargeType.value : ((chargeTypes ?? [])[0]?.value ?? null),
        json_logic: JSON.stringify(asObject(envelope.json_logic, true)),
    }
}

export const isConditionalString = (str) => {
    try {
        return JSON.parse(`${str}`.trim())?._format === FORMAT
    } catch (error) {
        return false
    }
}
