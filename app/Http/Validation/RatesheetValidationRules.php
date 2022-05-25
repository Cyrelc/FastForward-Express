<?php
namespace App\Http\Validation;

class RatesheetValidationRules {
    public function GetValidationRules($req) {
        $rules = [
            'name' => 'required|min:3|max:255',
            'deliveryTypes.*.time' => 'required|numeric',
            'weightRates' => 'required|array|min:2',
            'weightRates.*.name' => 'required',
            'timeRates.*.name' => 'required',
            'timeRates.*.price' => 'required|min:0'
        ];

        $messages = [
            'name.required' => 'Name is a required field',
            'name.unique' => 'Ratesheet Name is already taken. Please try another',
        ];

        if($req->useInternalZonesCalc === 'true') {
            foreach($req->zoneRates as $key => $rate)
            if($key != sizeOf($req->zoneRates) - 1) {
                foreach(['regular_cost' => 'Regular cost', 'rush_cost' => 'Rush cost', 'direct_cost' => 'Direct cost', 'direct_rush_cost' => 'Direct Rush cost'] as $field => $message) {
                    $rules['zoneRates.' . $key . '.' . $field] = 'required|numeric|min:0';
                    $messages['zoneRates.' . $key . '.' . $field . '.required'] = $rate['zones'] . ' Zone(s) ' . $message . ' can not be blank';
                    $messages['zoneRates.' . $key . '.' . $field . '.numeric'] = $rate['zones'] . ' Zone(s) ' . $message . ' must be a numeric value';
                    $messages['zoneRates.' . $key . '.' . $field . '.min'] = $rate['zones'] . ' Zone(s) ' . $message . ' must be at least 0';
                }
            }
        } else {
            foreach($req->deliveryTypes as $key => $deliveryType) {
                $rules['deliveryTypes.' . $key . '.time'] = 'required|numeric|min:0';
                $rules['deliveryTypes.' . $key . '.cost'] = 'required|numeric|min:0';
                $messages['deliveryTypes.' . $key . '.time.required'] = 'Delivery type ' . $deliveryType['friendlyName'] . ' requires a default price';
                $messages['deliveryTypes.' . $key . '.time.min'] = 'Delivery type ' . $deliveryType['friendlyName'] . ' cost must be greater than 0';
                $messages['deliveryTypes.' . $key . '.cost.required'] = 'Delivery type ' . $deliveryType['friendlyName'] . ' additional cost cannot be blank';
                $messages['deliveryTypes.' . $key . '.cost.min'] = 'Delivery type ' . $deliveryType['friendlyName'] . ' additional cost must be at least 0';
            }
        }

        foreach($req->weightRates as $key => $weightRate) {
            foreach($weightRate['brackets'] as $bracketIndex => $bracket) {
                $rules['weightRates.' . $key . '.brackets.' . $bracketIndex . '.kgmax'] = 'required|numeric|min:' . ($bracketIndex > 0 ? $weightRate['brackets'][$bracketIndex - 1]['kgmax'] : '0');
                $rules['weightRates.' . $key . '.brackets.' . $bracketIndex . '.basePrice'] = 'required|numeric|min:0';
                $rules['weightRates.' . $key . '.brackets.' . $bracketIndex . '.additionalXKgs'] = 'required|numeric|min:0|max:' . ($bracketIndex > 0 ? $bracket['kgmax'] - $weightRate['brackets'][$bracketIndex - 1]['kgmax'] : $bracket['kgmax']);
                $messages['weightRates.' . $key . '.brackets.' . $bracketIndex . '.kgmax.required'] = $weightRate['name'] . ' kgmax ' . $bracketIndex . ' is required';
                $messages['weightRates.' . $key . '.brackets.' . $bracketIndex . '.basePrice.required'] = $weightRate['name'] . ' base price ' . $bracketIndex . ' is required';
            }
        }

        foreach($req->timeRates as $key => $timeRate) {
            foreach($timeRate['brackets'] as $bracketIndex => $bracket) {
                $rules['timeRates.' . $key . '.brackets.' . $bracketIndex . '.startDayOfWeek'] = 'required|min:0|max:6';
                $rules['timeRates.' . $key . '.brackets.' . $bracketIndex . '.endDayOfWeek'] = 'required|min:0|max:6';
                $rules['timeRates.' . $key . '.' . $bracketIndex . '.startTime'] = 'date';
                $rules['timeRates.' . $key . '.' . $bracketIndex . '.endTime'] = 'date';
            }
        }

        $count = array('internal' => 0, 'peripheral' => 0, 'outlying' => 0);
        if($req->mapZones)
            foreach($req->mapZones as $key => $zone) {
                $rules['mapZones.' . $key . '.name'] = 'required';
                $rules['mapZones.' . $key . '.coordinates'] = 'required|min:3';
                $messages['mapZones.' . $key . '.name.required'] = 'You must enter a name for new map zone #' . ($key + 1);
                if($zone['type'] === 'peripheral') {
                    $rules['mapZones.' . $key . '.regularCost'] = 'required|numeric|min:0';
                    $rules['mapZones.' . $key . '.additionalTime'] = 'required|numeric|min:0';
                    $messages['mapZones.' . $key . '.regularCost.required'] = 'You must enter a cost associated with peripheral zone: ' . $zone['name'];
                    $messages['mapZones.' . $key . '.regularCost.additionalTime'] = 'You must enter a time associated with peripheral zone: ' . $zone['name'];
                    $count['peripheral']++;
                } else if ($zone['type'] === 'outlying') {
                    foreach(['additionalTime' => 'time', 'regularCost' => 'Regular cost', 'rushCost' => 'Rush cost', 'directCost' => 'Direct cost', 'directRushCost' => 'Direct Rush cost'] as $field => $message) {
                        $rules['mapZones.' . $key . '.' . $field] = 'required|numeric|min:0';
                        $messages['mapZones.' . $key . '.' . $field . '.required'] = 'You must enter a ' . $message . ' associated with outlying zone: ' . $zone['name'];
                        $messages['mapZones.' . $key . '.' . $field . '.numeric'] = $zone['name'] . ' ' . $message . ' must be a numeric value ONLY';
                        $messages['mapZones.' . $key . '.' . $field . '.min'] = $zone['name'] . ' ' . $message . ' must be a value greater than 0';
                    }
                    $count['outlying']++;
                } else
                    $count['internal']++;
            }
        return ['rules' => $rules, 'messages' => $messages];
    }
}

?>
