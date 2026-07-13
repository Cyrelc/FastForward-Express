import React, {useEffect, useRef, useState} from 'react'
import {timeValueTypes, timeActions} from './timingConditionalConfig'
import {emptyTreeWithRule} from '../conditionalFieldShared'
import {Utils as QbUtils} from '@react-awesome-query-builder/ui'

const emptyTree = (config) => emptyTreeWithRule(QbUtils, config)

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

export default function useTimingConditional({conditional, config, nextPriority = 0}) {
    const [name, setName] = useState('')
    const [priority, setPriority] = useState(0)
    const [queryTree, setQueryTree] = useState(emptyTree(config))
    const [action, setAction] = useState(timeActions[0])
    const [continueOn, setContinueOn] = useState(true)
    const [valueType, setValueType] = useState(timeValueTypes[0])
    const [resultValue, setResultValue] = useState(0)

    // Tracked here because this is where the setters live; the modal can see the values but not the
    // fact that one was touched. Rules save one at a time, so an unnoticed edit is a lost edit.
    const [isDirty, setIsDirty] = useState(false)
    const justLoadedRef = useRef(true)

    // The loading effect uses the raw setters, so filling the form from the server never marks it
    // dirty. Only the wrapped setters the modal calls do.
    const touch = (setter) => (value) => {
        setIsDirty(true)
        setter(value)
    }

    // The query builder normalises its tree once on mount and fires onChange; that is not a user
    // edit, so the first call is swallowed.
    const touchQueryTree = (tree) => {
        if(justLoadedRef.current)
            justLoadedRef.current = false
        else
            setIsDirty(true)

        setQueryTree(tree)
    }

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
        // An existing rule keeps its own priority; a new one starts past the end of the list.
        setPriority(conditional?.priority ?? nextPriority)

        justLoadedRef.current = true
        setIsDirty(false)
    }, [conditional, nextPriority])

    const reset = () => {
        setName('')
        setPriority(nextPriority)
        setQueryTree(emptyTree(config))
        setAction(timeActions[0])
        setContinueOn(true)
        setValueType(timeValueTypes[0])
        setResultValue(0)

        justLoadedRef.current = true
        setIsDirty(false)
    }

    return {
        action,
        continueOn,
        isDirty,
        name,
        priority,
        queryTree,
        resultValue,
        valueType,
        setAction: touch(setAction),
        setContinueOn: touch(setContinueOn),
        setName: touch(setName),
        setPriority: touch(setPriority),
        setQueryTree: touchQueryTree,
        setResultValue: touch(setResultValue),
        setValueType: touch(setValueType),
        markSaved: () => setIsDirty(false),
        reset,
    }
}
