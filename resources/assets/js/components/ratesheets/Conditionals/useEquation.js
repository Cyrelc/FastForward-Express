import React, {useCallback, useEffect, useState} from 'react'
import {Query, Builder, Utils as QbUtils} from '@react-awesome-query-builder/ui'
import {debounce} from 'lodash'

import config, {availableTestVariables} from './conditionalConfig'

const imperialToMetric = {
    'lb': 'kg',
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

    // const debouncedEvaluateEquationString = useCallback(
    //     debounce(() => {
    //         // Ensure every number is followed by a unit or a variable by a bracket or operator
    //         let equation = equationString.replace(/\$(\d+(\.\d+)?)/g, (match, value) => {
    //             return value + " CAD";
    //         });

    //         let regex = /(\b[a-zA-Z_][a-zA-Z0-9_]*\b|\d+(\.\d+)?)\s*(\b[a-zA-Z_][a-zA-Z0-9_]*\b)?/g;
    //         let match;
    //         while ((match = regex.exec(equation)) !== null) {
    //             const value = match[1]
    //             const unit = match[3]

    //             const isVariable = availableTestVariables.some(variable => variable.dbName == value)
    //             const isValue = !isNaN(parseFloat(value))

    //             console.log(unit, imperialToMetric[unit], Object.keys(imperialToMetric).includes(unit))

    //             if(!isVariable && !isValue) {
    //                 setDemoResult('Invalid variable: ', value)
    //             } else if(!Object.keys(imperialToMetric).includes(unit) && imperialToMetric[unit] != 1) {
    //                 setDemoResult(`Missing unit, invalid unit, or incorrect placement in equation near: ${value}`);
    //             }
    //         }

    //         const demoEquation = equation.replace(/(([\d.]+)\s*([a-zA-Z$]+)|(\b[a-zA-Z_][a-zA-Z0-9_]*\b)\s*([a-zA-Z$]*))/g, (match, _, number, unit, variable, varUnit) => {
    //             if (variable) {
    //                 let value = testVariables.find(v => v.dbName == variable)?.value
    //                 if (value) {
    //                     if (varUnit && imperialToMetric.hasOwnProperty(varUnit) && imperialToMetric[varUnit] != 1) {
    //                         try {
    //                             let metricUnit = imperialToMetric[varUnit];
    //                             value = math.unit(value, varUnit).toNumber(metricUnit);
    //                             return `${value}`;
    //                         } catch (error) {
    //                             console.log(error);
    //                             return match;
    //                         }
    //                     } else {
    //                         console.log('else return ${value}.trim()')
    //                         return `${value}`.trim();
    //                     }
    //                 } else {
    //                     console.log(`Unknown variable: ${variable}`)
    //                     setDemoResult(`Unknown variable: ${variable}`);
    //                     return match;
    //                 }
    //             } else {
    //                 number = parseFloat(number);
    //                 if (imperialToMetric.hasOwnProperty(unit) && imperialToMetric[unit] != 1) {
    //                     try {
    //                         let metricUnit = imperialToMetric[unit];
    //                         let newValue = math.unit(number, unit).toNumber(metricUnit);
    //                         return `${newValue}`;
    //                     } catch (error) {
    //                         return match;
    //                     }
    //                 } else {
    //                     return `${number}`;
    //                 }
    //             }
    //         });

    //         const serverEquation = equation.replace(/(([\d.]+)\s*([a-zA-Z$]+)|(\b[a-zA-Z_][a-zA-Z0-9_]*\b)\s*([a-zA-Z$]*))/g, (match, _, number, unit, variable, varUnit) => {
    //             if (variable) {
    //                 return variable;
    //             } else {
    //                 number = parseFloat(number);
    //                 if (imperialToMetric.hasOwnProperty(unit) && imperialToMetric[unit] != 1) {
    //                     try {
    //                         let metricUnit = imperialToMetric[unit];
    //                         let newValue = math.unit(number, unit).toNumber(metricUnit);
    //                         return `${newValue}`;
    //                     } catch (error) {
    //                         return match;
    //                     }
    //                 } else {
    //                     return `${number}`;
    //                 }
    //             }
    //         });
    //         console.log(serverEquation)

    //         try {
    //             let result = math.evaluate(demoEquation)
    //             // Verify that the result is a number
    //             if (isNaN(result)) {
    //                 setDemoResult('Invalid equation');
    //             } else {
    //                 setDemoResult('$' + result.toFixed(2))
    //             }
    //         } catch (error) {
    //             setDemoResult(`Error in evaluating the equation: ${error.message}`);
    //         }

    //         setDemoEquationString(demoEquation)
    //         setServerEquationString(serverEquation)
    //     }, 500), [equationString, testVariables])

    // useEffect(() => {
    //     debouncedEvaluateEquationString()
    // }, [equationString, testVariables])

    return {
        //getters
        demoEquationString,
        demoResult,
        equationString,
        resultValue,
        serverEquationString,
        testVariables,
        //setters
        //functions
    }
}

