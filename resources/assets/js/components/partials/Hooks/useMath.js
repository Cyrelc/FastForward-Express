import React from 'react'

export default function useMath() {
    function poundsToKilograms(pounds){
        return +(pounds / 2.2046).toFixed(3);
    }

    function kilogramsToPounds(kilograms){
        return +(kilograms * 2.2046).toFixed(3);
    }

    function inchesToCentimeters(inches) {
        return +(inches * 2.54).toFixed(3)
    }

    function getCubedWeightFromInches(length, width, height) {
        return getCubedWeightFromCentimeters(inchesToCentimeters(length), inchesToCentimeters(width), inchesToCentimeters(height))
    }

    function getCubedWeightFromCentimeters(length, width, height) {
        // toDo - 20 is a constant, get it from ratesheet settings most likely (and pass as parameter maybe?)
        // Also it is currently incorrect, as it is for cubic feet, and the result is in cubic meters
        return +(length * width * height / 20 * 0.0283168466).toFixed(3)
    }

    return {
        poundsToKilograms,
        kilogramsToPounds,
        getCubedWeightFromCentimeters,
        getCubedWeightFromInches,
    }
}
