<?php
namespace App\Http\Validation;

class ZoneGroupValidationRules {
    /**
     * Rules for saving a single zone group. Delivery types come from the ratesheet, since the
     * crossing table has a cost/time column per type.
     *
     * The last rate row is exempt, as it always was: it is the open-ended "this many zones or more"
     * row the editor appends, and it is expected to be blank.
     */
    public function GetValidationRules($req, $deliveryTypes) {
        $rules = [
            'zoneGroup.name' => 'required|max:255',
        ];

        $messages = [
            'zoneGroup.name.required' => 'A zone group needs a name',
        ];

        $zoneRates = $req->zoneGroup['zoneRates'] ?? [];

        foreach($zoneRates as $key => $rate) {
            if($key == sizeOf($zoneRates) - 1)
                continue;

            foreach($deliveryTypes as $deliveryType) {
                $friendlyName = $deliveryType['friendlyName'] ?? $deliveryType['name'] ?? null;
                if(!$friendlyName)
                    continue;

                $pathPrefix = 'zoneGroup.zoneRates.' . $key . '.' . $friendlyName;
                $labelPrefix = ($rate['zones'] ?? '?') . ' Zone(s) ' . $friendlyName;

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

        return ['rules' => $rules, 'messages' => $messages];
    }
}

?>
