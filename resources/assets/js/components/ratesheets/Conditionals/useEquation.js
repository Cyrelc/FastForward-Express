import React, {useCallback, useEffect, useState} from 'react'
import {debounce} from 'lodash'
import {
    absDependencies,
    addDependencies,
    create,
    createUnitDependencies,
    divideDependencies,
    evaluateDependencies,
    maxDependencies,
    minDependencies,
    multiplyDependencies,
    powDependencies,
    roundDependencies,
    sqrtDependencies,
    subtractDependencies,
    unitDependencies,
} from 'mathjs';

const math = create({
    abs: absDependencies,
    add: addDependencies,
    createUnit: createUnitDependencies,
    divide: divideDependencies,
    evaluate: evaluateDependencies,
    max: maxDependencies,
    min: minDependencies,
    multiply: multiplyDependencies,
    pow: powDependencies,
    round: roundDependencies,
    sqrt: sqrtDependencies,
    subtract: subtractDependencies,
    unit: unitDependencies,
}, {
    matrix: 'Array',
    number: 'number'
});

math.createUnit('CAD')
math.createUnit('kgs', '1 kg')

import config, {availableTestVariables} from './conditionalConfig'

const imperialToMetric = {
    'lb': 'kg',
    'lbs': 'kg',
    'kgs': 'kg',
    'kg': 1,
    'oz': 'g',
    'in': 'cm',
    'ft': 'm',
    'yd': 'm',
    'mi': 'km',
    'sqft': 'm2',
    'CAD': 1
}

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
                // Add space between number and unit: 50lb → 50 lb
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

            // Replace leading dollar sign with trailing CAD
            let equation = normalized.replace(/\$(\d+(\.\d+)?)/g, (match, value) => {
                return value + " CAD";
            });

            // Track what we've recognized
            const recognized = {
                variables: [],
                numbers: [],
                units: [],
                operators: []
            }

            let regex = /(\b[a-zA-Z_][a-zA-Z0-9_]*\b|\d+(\.\d+)?)\s*(\b[a-zA-Z_][a-zA-Z0-9_]*\b)?/g;
            let match;
            // Match all strings followed by a space or number then another string
            // Work with it in these chunks (XXXX lbs for example)
            while ((match = regex.exec(equation)) !== null) {
                const value = match[1]
                const unit = match[3]

                // check if it's a variable or a valid number
                const isVariable = availableTestVariables.some(variable => variable.dbName === value)
                const isValue = !isNaN(parseFloat(value))

                if (isVariable) {
                    recognized.variables.push(value)
                } else if (isValue) {
                    recognized.numbers.push(value)
                }

                if (unit && Object.keys(imperialToMetric).includes(unit)) {
                    recognized.units.push(unit)
                }

                // it is neither a variable, nor a valid number we have an error
                if(!isVariable && !isValue) {
                    setDemoResult('Invalid variable: ' + value)
                    setValidationMessage('❌ Invalid variable: ' + value)
                    return
                }
                // otherwise, the unit must either be in the imperial to metric conversion chart, or else be a metric unit already
                // or we have another error
                else if(unit && !Object.keys(imperialToMetric).includes(unit) && imperialToMetric[unit] !== 1) {
                    setDemoResult(`Missing or invalid unit near: ${value}`);
                    setValidationMessage(`❌ Missing or invalid unit near: ${value}`);
                    return
                }
            }

            // Set validation message showing what was recognized
            const validationParts = []
            if (recognized.variables.length > 0) {
                validationParts.push(`Variables: ${recognized.variables.join(', ')}`)
            }
            if (recognized.units.length > 0) {
                validationParts.push(`Units: ${recognized.units.join(', ')}`)
            }
            if (validationParts.length > 0) {
                setValidationMessage('✓ Recognized: ' + validationParts.join(' | '))
            } else {
                setValidationMessage('⚠️ No variables or units detected')
            }

            // match: The entire matched substring
            // _: This variable captures the entire group within the outer parentheses.
            // number: Captures a sequence of digits and/or decimal points
            // unit: Captures any letters (a-z, A-Z) or dollar signs ($) following the number
            // variable: Captures a valid variable name (starting with a letter or underscore, followed by any alphanumeric characters or underscores)
            // varUnit: Captures any letters (a-z, A-Z) or dollar signs ($) following the variable
            const demoEquation = equation.replace(/(([\d.]+)\s*([a-zA-Z$]+)|(\b[a-zA-Z_][a-zA-Z0-9_]*\b)\s*([a-zA-Z$]*))/g, (match, _, number, unit, variable, varUnit) => {
                if (variable) {
                    let value = testVariables.find(v => v.dbName == variable)?.value
                    if (value) {
                        if (varUnit && imperialToMetric.hasOwnProperty(varUnit) && imperialToMetric[varUnit] != 1) {
                            try {
                                let metricUnit = imperialToMetric[varUnit];
                                value = math.unit(value, varUnit).toNumber(metricUnit);
                                return `${value}`;
                            } catch (error) {
                                console.error(error);
                                return match;
                            }
                        } else {
                            return `${value}`.trim();
                        }
                    } else {
                        setDemoResult(`Unknown variable: ${variable}`);
                        return match;
                    }
                } else {
                    number = parseFloat(number);
                    if (imperialToMetric.hasOwnProperty(unit) && imperialToMetric[unit] != 1) {
                        try {
                            let metricUnit = imperialToMetric[unit];
                            let newValue = math.unit(number, unit).toNumber(metricUnit);
                            return `${newValue}`;
                        } catch (error) {
                            return match;
                        }
                    } else {
                        return `${number}`;
                    }
                }
            });

            const serverEquation = equation.replace(/(([\d.]+)\s*([a-zA-Z$]+)|(\b[a-zA-Z_][a-zA-Z0-9_]*\b)\s*([a-zA-Z$]*))/g, (match, _, number, unit, variable, varUnit) => {
                if (variable) {
                    return variable;
                } else {
                    number = parseFloat(number);
                    if (imperialToMetric.hasOwnProperty(unit) && imperialToMetric[unit] != 1) {
                        try {
                            let metricUnit = imperialToMetric[unit];
                            let newValue = math.unit(number, unit).toNumber(metricUnit);
                            return `${newValue}`;
                        } catch (error) {
                            return match;
                        }
                    } else {
                        return `${number}`;
                    }
                }
            });

            try {
                const variables = testVariables.reduce((accumulator, variable) => {
                    const {dbName, value} = variable
                    return {...accumulator, [dbName]: value}
                }, {})
                let result = math.evaluate(demoEquation, variables)
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

