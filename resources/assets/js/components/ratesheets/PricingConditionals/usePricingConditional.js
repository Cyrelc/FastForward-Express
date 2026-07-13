import React, {useEffect, useRef, useState} from 'react'
import useEquation from './useEquation'
import {valueTypes, actions, REFUSE_ACTION, MESSAGE_VALUE_TYPE} from './pricingConditionalConfig'
import {emptyTreeWithRule} from '../conditionalFieldShared'
import {Utils as QbUtils} from '@react-awesome-query-builder/ui'

import {useLists} from '../../../contexts/ListsContext'

const DEFAULT_ACTION = {value: 'add', label: 'Add Charge'}
const DEFAULT_VALUE_TYPE = {value: 'amount', label: 'Amount'}

export default function usePricingConditional({conditional, config, nextPriority = 0}) {
    const [type, setType] = useState(null)
    const [name, setName] = useState('')
    const [priority, setPriority] = useState(0)
    const [continueOn, setContinueOn] = useState(true)
    const [queryTree, setQueryTree] = useState(emptyTreeWithRule(QbUtils, config))
    const [action, setAction] = useState(DEFAULT_ACTION)
    const [valueType, setValueType] = useState(DEFAULT_VALUE_TYPE)
    const [resultValue, setResultValue] = useState(0)

    // Tracked here rather than in the modal because this is where every setter lives - the modal can
    // only see the values, not the fact that one of them was touched. Without it, closing the modal
    // after editing a rule throws the edit away silently.
    const [isDirty, setIsDirty] = useState(false)
    const justLoadedRef = useRef(true)

    const {chargeTypes} = useLists()
    const equation = useEquation(conditional)

    // The loading effect below uses the raw setters, so filling the form from the server never marks
    // it dirty. Only these wrapped setters - the ones the modal actually calls - do.
    const touch = (setter) => (value) => {
        setIsDirty(true)
        setter(value)
    }

    // A refusal has no charge, so it has no amount and no charge type - its "value" is the message
    // staff are shown. Picking the action therefore settles the value type, rather than leaving the
    // author to work out that "Refuse to Price" needs to be paired with "Message".
    //
    // Priority 0 by default: refusals are evaluated before all pricing rules regardless, so they sit
    // at the front of the list where they read as the dealbreakers they are.
    const changeAction = (action) => {
        setIsDirty(true)
        setAction(action)

        if(action?.value === REFUSE_ACTION) {
            setValueType(valueTypes.find(valueType => valueType.value === MESSAGE_VALUE_TYPE))
            setResultValue('')
            setType(null)
            if(!conditional)
                setPriority(0)
        } else if(valueType?.value === MESSAGE_VALUE_TYPE) {
            // Leaving refusal behind: a message is meaningless for a rule that charges.
            setValueType(DEFAULT_VALUE_TYPE)
            setResultValue(0)
            if(!conditional)
                setPriority(nextPriority)
        }
    }

    // The query builder normalises its tree once on mount and calls onChange, which is not a user
    // edit. Swallowing exactly that first call keeps the modal from claiming unsaved changes the
    // moment it opens.
    const touchQueryTree = (tree) => {
        if(justLoadedRef.current)
            justLoadedRef.current = false
        else
            setIsDirty(true)

        setQueryTree(tree)
    }

    // On page load, load the condition into the tree by parsing it from JSON logic
    // (or set an empty logic tree, if creating)
    useEffect(() => {
        const tree = conditional
            ? QbUtils.loadFromJsonLogic(JSON.parse(conditional.json_logic), config)
            : emptyTreeWithRule(QbUtils, config)
        setQueryTree(tree)

        setName(conditional?.name ?? '')
        setAction(conditional?.action ?? DEFAULT_ACTION)
        setValueType(conditional?.value_type ? valueTypes.find(valueType => conditional.value_type == valueType.value) ?? DEFAULT_VALUE_TYPE : DEFAULT_VALUE_TYPE)
        setResultValue(conditional?.value ?? 0)
        // An existing rule keeps its own priority; a new one starts past the end of the list.
        setPriority(conditional?.priority ?? nextPriority)
        setContinueOn(conditional?.continue_on ?? true)
        setType(conditional?.type ? chargeTypes.find(chargeType => chargeType.value == conditional.type) : null)

        // Load equation string directly here
        const equationValue = conditional?.equation_string || conditional?.original_equation_string
        if(equationValue) {
            equation.setEquationString(equationValue)
        }

        justLoadedRef.current = true
        setIsDirty(false)
    }, [conditional, config, nextPriority])

    const reset = () => {
        setName('')
        setType(null)
        setPriority(nextPriority)
        setContinueOn(true)
        setQueryTree(emptyTreeWithRule(QbUtils, config))
        setAction(DEFAULT_ACTION)
        setValueType(DEFAULT_VALUE_TYPE)
        setResultValue(0)
        equation.reset()

        justLoadedRef.current = true
        setIsDirty(false)
    }

    const isRefusal = action?.value === REFUSE_ACTION

    return {
        ...equation,
        //getters
        action,
        isRefusal,
        continueOn,
        isDirty,
        name,
        priority,
        queryTree,
        resultValue,
        type,
        valueType,
        //setters — wrapped so that touching any of them flags unsaved changes
        setAction: changeAction,
        setContinueOn: touch(setContinueOn),
        setName: touch(setName),
        setPriority: touch(setPriority),
        setQueryTree: touchQueryTree,
        // Comes in unwrapped via the ...equation spread above, so it has to be re-wrapped or an edited
        // equation would not count as an unsaved change.
        setEquationString: touch(equation.setEquationString),
        setResultValue: touch(setResultValue),
        setType: touch(setType),
        setValueType: touch(setValueType),
        //functions
        markSaved: () => setIsDirty(false),
        reset,
    }
}
