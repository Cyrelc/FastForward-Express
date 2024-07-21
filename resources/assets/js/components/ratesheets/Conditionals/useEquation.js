import React, {useCallback, useEffect, useState} from 'react'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/ui'
import {debounce} from 'lodash'
const math = require('mathjs')
math.createUnit('CAD')
// math.createUnit('lb', '1 lbs')
// math.createUnit('kg', '1 kgs')

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

    // useEffect(() => {
    //     const foundVariables = availableTestVariables
    //         .filter(variable => equationString.includes(variable.dbName))

    //     setTestVariables(foundVariables)
    // }, [equationString])

    const debouncedEvaluateEquationString = useCallback(
        debounce(() => {
            // Ensure every number is followed by a unit or a variable by a bracket or operator
            // Here we replace the leading dollar sign with a trailing CAD, which looks more like other units (and allows us to conform to "X UNITS" structure)
            let equation = equationString.replace(/\$(\d+(\.\d+)?)/g, (match, value) => {
                return value + " CAD";
            });

            let regex = /(\b[a-zA-Z_][a-zA-Z0-9_]*\b|\d+(\.\d+)?)\s*(\b[a-zA-Z_][a-zA-Z0-9_]*\b)?/g;
            let match;
            // Match all strings followed by a space or number then another string
            // Work with it in these chunks (XXXX lbs for example)
            while ((match = regex.exec(equation)) !== null) {
                const value = match[1]
                const unit = match[3]

                // check if it's a variable or a valid number
                const isVariable = availableTestVariables.some(variable => variable.dbName == value)
                const isValue = !isNaN(parseFloat(value))

                console.log(unit, imperialToMetric[unit], Object.keys(imperialToMetric).includes(unit))

                // it is neither a variable, nor a valid number we have an error
                if(!isVariable && !isValue) {
                    setDemoResult('Invalid variable: ', value)
                }
                // otherwise, the unit must either be in the imperial to metric conversion chart, or else be a metric unit already
                // or we have another error
                else if(!Object.keys(imperialToMetric).includes(unit) && imperialToMetric[unit] != 1) {
                    setDemoResult(`Missing unit, invalid unit, or incorrect placement in equation near: ${value}`);
                }
            }

            // match: The entire matched substring
            // _: This variable captures the entire group within the outer parentheses.
            // number: Captures a sequence of digits and/or decimal points
            // unit: Captures any letters (a-z, A-Z) or dollar signs ($) following the number
            // variable: Captures a valid variable name (starting with a letter or underscore, followed by any alphanumeric characters or underscores)
            // varUnit: Captures any letters (a-z, A-Z) or dollar signs ($) following the variable
            const demoEquation = equation.replace(/(([\d.]+)\s*([a-zA-Z$]+)|(\b[a-zA-Z_][a-zA-Z0-9_]*\b)\s*([a-zA-Z$]*))/g, (match, _, number, unit, variable, varUnit) => {
                if (variable) {
                    console.log('variable found', variable)
                    let value = testVariables.find(v => v.dbName == variable)?.value
                    if (value) {
                        if (varUnit && imperialToMetric.hasOwnProperty(varUnit) && imperialToMetric[varUnit] != 1) {
                            try {
                                let metricUnit = imperialToMetric[varUnit];
                                value = math.unit(value, varUnit).toNumber(metricUnit);
                                console.log('converting imperial value: ', value, varUnit, ' to metric', metricUnit)
                                return `${value}`;
                            } catch (error) {
                                console.log(error);
                                return match;
                            }
                        } else {
                            console.log('else return ${value}.trim()')
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
            console.log(serverEquation)

            try {
                let result = math.evaluate(demoEquation, testVariables)
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
        //setters
        setEquationString,
        setTestVariables,
        //functions
    }
}

