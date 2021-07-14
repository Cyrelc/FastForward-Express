<?php
namespace App\Http\Validation;

class RatesheetValidationRules {
    public function GetValidationRules($req) {
        $rules = [
            'deliveryTypes.*.time' => 'required|numeric',
            'name' => 'required|alpha_dash|unique:ratesheets,name,' . $req->ratesheet_id . ',ratesheet_id',
            'palletRate.palletBasePrice' => 'required|numeric',
            'palletRate.palletBaseWeightKgs' => 'required|numeric',
            'palletRate.palletBaseWeightLbs' => 'required|numeric',
            'palletRate.palletAdditionalCharge' => 'required|numeric',
            'palletRate.palletAdditionalWeightKgs' => 'required|numeric',
            'palletRate.palletAdditionalWeightLbs' => 'required|numeric',
            'weightRates' => 'required|array|min:2',
        ];

        $messages = [
            'name.required' => 'Name is a required field',
            'name.unique' => 'That name is already taken. Please try another',
            'name.alpha_dash' => 'Ratesheet name can only include A-Z, 0-9, \'-\', and \'_\'',
            'weightRates.min' => 'You must include at least one valid weight rate',
        ];

        if($req->useInternalZonesCalc) {
            foreach($req->zoneRates as $key => $rate)
            if($key != sizeOf($req->zoneRates) - 1) {
                foreach(['regularCost' => 'Regular cost', 'rushCost' => 'Rush cost', 'directCost' => 'Direct cost', 'directRushCost' => 'Direct Rush cost'] as $field => $message) {
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

        foreach($req->weightRates as $key => $rate) {
            if($key != sizeOf($req->weightRates) - 1) {
                $rules['weightRates.' . $key . '.kgmax'] = 'required|numeric|min:' . ($key > 0 ? $req->weightRates[$key - 1]['kgmax'] : '0.01');
                $rules['weightRates.' . $key . '.cost'] = 'required|numeric|min:0';
                $messages['weightRates.' . $key . '.kgmax.required'] = 'Only the last Weight Rate can remain empty';
                $messages['weightRates.' . $key . '.kgmax.min'] = 'Weight Rate ' . ($key + 1) . ' must be higher value than the one before it';
                $messages['weightRates.' . $key . '.cost.required'] = 'Weight Rate ' . ($key + 1) . ' cost is required';
            }
        }

        $count = array('internal' => 0, 'peripheral' => 0, 'outlying' => 0);
        if($req->mapZones)
            foreach($req->mapZones as $key => $zone) {
                $rules['mapZones.' . $key . '.name'] = 'required';
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
                // if($req->useZonesCrossedCalc) {
                //     $rules['mapZones.minInternal'] = 'required|';
                // }
            }
        return ['rules' => $rules, 'messages' => $messages];
    }
}

?>
