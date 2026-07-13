import React, {useCallback, useEffect, useState} from 'react'
import {debounce} from 'lodash'
import {
    absDependencies,
    addDependencies,
    ceilDependencies,
    create,
    divideDependencies,
    evaluateDependencies,
    floorDependencies,
    maxDependencies,
    minDependencies,
    multiplyDependencies,
    powDependencies,
    roundDependencies,
    sqrtDependencies,
    subtractDependencies,
} from 'mathjs';

const math = create({
    abs: absDependencies,
    add: addDependencies,
    // Weight brackets charge per whole increment ("$2 per 100 lbs"), so a rule has to be able to
    // discard the part-increment: floor((weight - 100) / 100) * 2. Without floor, 150 lbs would be
    // charged 0.5 of an increment.
    ceil: ceilDependencies,
    divide: divideDependencies,
    evaluate: evaluateDependencies,
    floor: floorDependencies,
    max: maxDependencies,
    min: minDependencies,
    multiply: multiplyDependencies,
    pow: powDependencies,
    round: roundDependencies,
    sqrt: sqrtDependencies,
    subtract: subtractDependencies,
}, {
    matrix: 'Array',
    number: 'number'
});

// Kept in step with the functions registered on `math` above, and with the backend's Matex
// evaluator - an equation has to mean the same thing in the preview and on the server.
export const EQUATION_FUNCTIONS = ['max', 'min', 'round', 'floor', 'ceil', 'abs', 'sqrt', 'pow']

// Written for someone who is pricing freight, not writing code. floor's wording is doing real work: it
// is the function every per-increment weight bracket needs and the one whose absence silently charges
// a part-increment.
export const EQUATION_FUNCTION_DESCRIPTIONS = {
    max: 'The larger of the values — max(0, x) is how you stop a subtraction going negative',
    min: 'The smaller of the values — min(x, 50) caps a charge at 50',
    round: 'Round to the nearest — round(x, 2) for cents',
    floor: 'Round DOWN to a whole number — needed to charge per whole 100 lb rather than part of one',
    ceil: 'Round UP to a whole number — charges a full increment for any part of one',
    abs: 'Drop the minus sign',
    sqrt: 'Square root',
    pow: 'Raise to a power — pow(x, 2) is x squared',
}

import {availableTestVariables} from './pricingConditionalConfig'

// Units were removed from equations entirely. They used to be written as suffixes ("total_weight lbs",
// "2.5 CAD") and converted here before preview - but the server's evaluator has no notion of units, so
// the preview and the real price were computed from different strings. The unit now lives in the
// variable name (total_weight_kgs, total_weight_lbs), which is unambiguous and needs no conversion.
// A suffix is now simply an error, reported below.

export default function useEquation({conditional}) {
    const [demoEquationString, setDemoEquationString] = useState('')
    const [demoResult, setDemoResult] = useState('')
    const [equationString, setEquationString] = useState('')
    const [resultValue, setResultValue] = useState(0)
    const [serverEquationString, setServerEquationString] = useState('')
    const [testVariables, setTestVariables] = useState(availableTestVariables)
    const [validationMessage, setValidationMessage] = useState('')

    // Load equation when conditional changes (for editing)
    useEffect(() => {
        // Support both new (equation_string) and old (original_equation_string) field names
        const equationValue = conditional?.equation_string || conditional?.original_equation_string
        if(equationValue) {
            setEquationString(equationValue)
        } else {
            setEquationString('')
        }
    }, [conditional?.conditional_id, conditional?.equation_string, conditional?.original_equation_string])

    // useEffect(() => {
    //     const foundVariables = availableTestVariables
    //         .filter(variable => equationString.includes(variable.dbName))

    //     setTestVariables(foundVariables)
    // }, [equationString])

    const debouncedEvaluateEquationString = useCallback(
        debounce(() => {
            if (!equationString || equationString.trim() === '') {
                setDemoResult('')
                setValidationMessage('')
                setServerEquationString('')
                return
            }

            // Normalize the input: add spaces where needed
            let normalized = equationString
                // Split a number stuck to a word (50lb) so the unit-suffix check below can see it
                .replace(/(\d)([a-zA-Z])/g, '$1 $2')
                // Add space around operators: 50+30 → 50 + 30
                .replace(/(\d)([+\-*/])/g, '$1 $2')
                .replace(/([+\-*/])(\d)/g, '$1 $2')
                // Add space after closing paren before number/letter: )50 → ) 50
                .replace(/\)(\d|[a-zA-Z])/g, ') $1')
                // Add space before opening paren after number/letter: 50( → 50 (
                .replace(/(\d|[a-zA-Z])\(/g, '$1 (')
                // Clean up multiple spaces
                .replace(/\s+/g, ' ')
                .trim()

            // The server evaluates the string exactly as it is stored, with a different expression
            // engine (Matex) that understands neither "$" nor unit suffixes. Anything the preview
            // accepts but the server cannot run is a rule that throws at pricing time, so the editor
            // now refuses both rather than quietly diverging.
            if(equationString.includes('$')) {
                setDemoResult('Remove the $ — the result is always dollars')
                setValidationMessage('❌ Remove the $ — an equation always produces a dollar amount, so write 16, not $16')
                setServerEquationString('')
                return
            }

            let equation = normalized

            // Track what we've recognized
            const recognized = {
                variables: [],
                functions: [],
                numbers: [],
                operators: []
            }

            let regex = /(\b[a-zA-Z_][a-zA-Z0-9_]*\b|\d+(\.\d+)?)\s*(\b[a-zA-Z_][a-zA-Z0-9_]*\b)?/g;
            let match;
            // Match all strings followed by a space or number then another string
            // Work with it in these chunks (XXXX lbs for example)
            while ((match = regex.exec(equation)) !== null) {
                const value = match[1]
                const unit = match[3]

                // check if it's a variable, a function name, or a valid number
                const isVariable = availableTestVariables.some(variable => variable.dbName === value)
                const isValue = !isNaN(parseFloat(value))
                // A function name is an identifier too, and the tokeniser cannot tell them apart - so
                // without this, "floor(...)" (or "max(...)", which the editor has always claimed to
                // support) is reported as an invalid variable and the equation will not validate.
                const isFunction = EQUATION_FUNCTIONS.includes(value)

                if (isVariable) {
                    recognized.variables.push(value)
                } else if (isValue) {
                    recognized.numbers.push(value)
                } else if (isFunction) {
                    recognized.functions.push(value)
                }

                // it is not a variable, a function, or a valid number - that is an error
                if(!isVariable && !isValue && !isFunction) {
                    setDemoResult('Invalid variable: ' + value)
                    setValidationMessage('❌ Invalid variable: ' + value)
                    setServerEquationString('')
                    return
                }

                // A unit written as a suffix ("total_weight_lbs lbs", "100 lbs") is read by the server's
                // evaluator as part of the identifier - "total_weight_lbslbs" - and throws. The unit
                // belongs in the variable name (total_weight_kgs vs total_weight_lbs), which is
                // unambiguous, so a suffix is now simply wrong rather than converted.
                if(unit) {
                    setDemoResult(`Remove "${unit}" after ${value}`)
                    setValidationMessage(
                        `❌ Units are not written in equations - the unit is part of the variable name: `
                        + `total_weight_kgs is kilograms, total_weight_lbs is pounds. `
                        + `Write "total_weight_lbs - 100", not "${value} ${unit}".`
                    )
                    setServerEquationString('')
                    return
                }
            }

            const validationParts = []
            if (recognized.variables.length > 0) {
                validationParts.push(`Variables: ${recognized.variables.join(', ')}`)
            }
            if (recognized.functions.length > 0) {
                validationParts.push(`Functions: ${recognized.functions.join(', ')}`)
            }
            if (validationParts.length > 0) {
                setValidationMessage('✓ Recognized: ' + validationParts.join(' | '))
            } else {
                setValidationMessage('⚠️ No variables detected')
            }

            // The preview substitutes each variable's test value so the author can see the arithmetic
            // that produced the number. With units gone this is a plain word swap - it used to be a
            // regex that also performed unit conversion, which is what let the preview and the server
            // compute from two different strings.
            const demoEquation = equation.replace(/\b[a-zA-Z_][a-zA-Z0-9_]*\b/g, token => {
                if(EQUATION_FUNCTIONS.includes(token))
                    return token

                const variable = testVariables.find(testVariable => testVariable.dbName === token)

                return variable ? `${variable.value}` : token
            })

            // Nothing to translate any more: what you typed is what the server runs. The previous
            // version rewrote the string for the server (stripping "lbs" off total_weight, which
            // silently turned a pounds formula into a kilograms one) and then never saved it anyway.
            const serverEquation = equation

            try {
                let result = math.evaluate(demoEquation)
                // Verify that the result is a number
                if(isNaN(result)) {
                    setDemoResult('Invalid equation');
                } else {
                    setDemoResult('$' + result.toFixed(2))
                }
            } catch (error) {
                setDemoResult(`Error in evaluating the equation: ${error.message}`);
            }

            setDemoEquationString(demoEquation)
            setServerEquationString(serverEquation)
        }, 500), [equationString, testVariables])

    const reset = () => {
        setDemoEquationString('')
        setDemoResult('')
        setEquationString('')
        setResultValue(0)
        setServerEquationString('')
        setTestVariables(availableTestVariables)
    }

    useEffect(() => {
        debouncedEvaluateEquationString()
    }, [equationString, testVariables])

    return {
        //getters
        demoEquationString,
        demoResult,
        equationString,
        resultValue,
        serverEquationString,
        testVariables,
        validationMessage,
        //setters
        setEquationString,
        setTestVariables,
        //functions,
        reset,
    }
}

