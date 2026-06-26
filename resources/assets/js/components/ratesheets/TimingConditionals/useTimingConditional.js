import React, {useEffect, useState} from 'react'
import {timeValueTypes, timeActions} from './timingConditionalConfig'
import {Utils as QbUtils} from '@react-awesome-query-builder/ui'

const emptyTree = (config) => QbUtils.checkTree(QbUtils.loadTree({'id': QbUtils.uuid(), 'type': 'group'}), config)

const parseAction = (action) => {
    if(!action)
        return timeActions[0]
    if(typeof action === 'string') {
        try {
            return JSON.parse(action)
        } catch (error) {
            return timeActions[0]
        }
    }
    return action
}

export default function useTimingConditional({conditional, config}) {
    const [name, setName] = useState('')
    const [priority, setPriority] = useState(0)
    const [queryTree, setQueryTree] = useState(emptyTree(config))
    const [action, setAction] = useState(timeActions[0])
    const [continueOn, setContinueOn] = useState(true)
    const [valueType, setValueType] = useState(timeValueTypes[0])
    const [resultValue, setResultValue] = useState(0)

    useEffect(() => {
        const tree = conditional ? QbUtils.loadFromJsonLogic(JSON.parse(conditional.json_logic), config) : emptyTree(config)
        setQueryTree(tree)

        setName(conditional?.name ?? '')
        setAction(parseAction(conditional?.action))
        const continueOnValue = conditional?.continue_on
        const normalizedContinueOn = continueOnValue === undefined || continueOnValue === null ? 1 : Number(continueOnValue)
        setContinueOn(normalizedContinueOn === 1)
        setValueType(conditional?.value_type ? timeValueTypes.find(valueType => conditional.value_type == valueType.value) : timeValueTypes[0])
        setResultValue(conditional?.value ?? 0)
        setPriority(conditional?.priority ?? 0)
    }, [conditional])

    const reset = () => {
        setName('')
        setPriority(0)
        setQueryTree(emptyTree(config))
        setAction(timeActions[0])
        setContinueOn(true)
        setValueType(timeValueTypes[0])
        setResultValue(0)
    }

    return {
        action,
        continueOn,
        name,
        priority,
        queryTree,
        resultValue,
        valueType,
        setAction,
        setContinueOn,
        setName,
        setPriority,
        setQueryTree,
        setResultValue,
        setValueType,
        reset,
    }
}
