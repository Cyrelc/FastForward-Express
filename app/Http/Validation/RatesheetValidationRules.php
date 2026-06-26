<?php
namespace App\Http\Validation;

class RatesheetValidationRules {
    public function GetValidationRules($req) {
        $rules = [
            'name' => 'required|min:3|max:255|unique:ratesheets,name,' . $req->ratesheet_id . ',ratesheet_id',
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

        if($req->useInternalZonesCalc) {
            foreach($req->zoneRates as $key => $rate)
            if($key != sizeOf($req->zoneRates) - 1) {
                foreach($req->deliveryTypes as $deliveryType) {
                    $friendlyName = $deliveryType['friendlyName'] ?? null;
                    if(!$friendlyName)
                        continue;

                    $pathPrefix = 'zoneRates.' . $key . '.' . $friendlyName;
                    $labelPrefix = $rate['zones'] . ' Zone(s) ' . $friendlyName;

                    $rules[$pathPrefix . '.cost'] = 'required|numeric|min:0';
                    $rules[$pathPrefix . '.time'] = 'required|numeric|min:0';

                    $messages[$pathPrefix . '.cost.required'] = $labelPrefix . ' cost can not be blank';
                    $messages[$pathPrefix . '.cost.numeric'] = $labelPrefix . ' cost must be a numeric value';
                    $messages[$pathPrefix . '.cost.min'] = $labelPrefix . ' cost must be at least 0';

                    $messages[$pathPrefix . '.time.required'] = $labelPrefix . ' time can not be blank';
                    $messages[$pathPrefix . '.time.numeric'] = $labelPrefix . ' time must be a numeric value';
                    $messages[$pathPrefix . '.time.min'] = $labelPrefix . ' time must be at least 0';
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
                $rules['mapZones.' . $key . '.region'] = 'nullable|string|max:255';
                $rules['mapZones.' . $key . '.tags'] = 'nullable|string|max:255';
                $messages['mapZones.' . $key . '.name.required'] = 'You must enter a name for new map zone #' . ($key + 1);
                $messages['mapZones.' . $key . '.tags.max'] = 'Zone ' . ($zone['name'] ?? ('#' . ($key + 1))) . ' tags cannot exceed 255 characters total';
                if($zone['type'] !== 'internal') {
                    $rules['mapZones.' . $key . '.deliveryTypeModifiers'] = 'nullable|array';
                    $rules['mapZones.' . $key . '.deliveryTypeModifiers.*.time'] = 'nullable|numeric|min:0';
                    $rules['mapZones.' . $key . '.deliveryTypeModifiers.*.cost'] = 'nullable|numeric|min:0';
                    $messages['mapZones.' . $key . '.deliveryTypeModifiers.*.time.numeric'] = $zone['name'] . ' time must be a numeric value ONLY';
                    $messages['mapZones.' . $key . '.deliveryTypeModifiers.*.cost.numeric'] = $zone['name'] . ' cost must be a numeric value ONLY';
                    $messages['mapZones.' . $key . '.deliveryTypeModifiers.*.time.min'] = $zone['name'] . ' time must be 0 or greater';
                    $messages['mapZones.' . $key . '.deliveryTypeModifiers.*.cost.min'] = $zone['name'] . ' cost must be 0 or greater';
                    if($zone['type'] === 'peripheral')
                        $count['peripheral']++;
                    else if($zone['type'] === 'outlying')
                        $count['outlying']++;
                } else
                    $count['internal']++;
            }
        return ['rules' => $rules, 'messages' => $messages];
    }

    public function GetZoneRequestValidationRules($req) {
        $rules = [
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric']
        ];

        $messages = [];

        return ['rules' => $rules, 'messages' => $messages];
    }
}

?>
