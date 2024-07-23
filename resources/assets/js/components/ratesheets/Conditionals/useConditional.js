import React, {useEffect, useState} from 'react'
import useEquation from './useEquation'
import config, {valueTypes} from './conditionalConfig'
import {Utils as QbUtils} from '@react-awesome-query-builder/ui'

import {useLists} from '../../../contexts/ListsContext'

export default function useConditional({conditional}) {
    const [type, setType] = useState('')
    const [name, setName] = useState('')
    const [priority, setPriority] = useState(0)
    const [queryTree, setQueryTree] = useState(QbUtils.checkTree(QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}), config))
    const [action, setAction] = useState({value: 'charge', label: 'Charge'})
    const [valueType, setValueType] = useState({value: 'amount', label: 'Amount'})
    const [resultValue, setResultValue] = useState(0)

    const {chargeTypes} = useLists()
    const equation = useEquation(conditional)

    // On page load, load the condition into the tree by parsing it from JSON logic
    // (or set an empty logic tree, if creating)
    useEffect(() => {
        const tree = conditional ? QbUtils.loadFromJsonLogic(JSON.parse(conditional.json_logic), config) : QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}, config)
        setQueryTree(tree)

        setName(conditional?.name)
        setAction(conditional?.action ?? {value: 'charge', label: 'Charge'})
        setValueType(conditional?.value_type ? valueTypes.find(valueType => conditional.value_type == valueType.value) : {value: 'amount', label: 'Amount'})
        setResultValue(conditional?.value ?? 0)
        // setEquationString(conditional?.value_type == 'equation' ? conditional.original_equation_string : '')
        // setEquationString(conditional?.value_type == 'equation' ? conditional.equation_string : '')
        setPriority(conditional?.priority ?? 0)
        setType(conditional?.type ? chargeTypes.find(chargeType => chargeType.value == conditional.type) : {})
    }, [conditional])

    const reset = () => {
        setName('')
        setType('')
        setPriority(0)
        setQueryTree(QbUtils.checkTree(QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}), config))
        setAction({value: 'charge', label: 'Charge'})
        setValueType({value: 'amount', label: 'Amount'})
        setResultValue(0)
        equation.reset()
    }

    return {
        ...equation,
        //getters
        action,
        name,
        priority,
        queryTree,
        resultValue,
        type,
        valueType,
        //setters
        setAction,
        setName,
        setPriority,
        setQueryTree,
        setResultValue,
        setType,
        setValueType,
        //functions
        reset,
    }
}
