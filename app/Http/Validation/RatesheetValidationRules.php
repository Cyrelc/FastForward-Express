<?php
namespace App\Http\Validation;

class RatesheetValidationRules {
    // Kept in step with TAG_STORAGE_LIMIT / TAG_MAX_LENGTH in MapZone.js. The storage limit is the
    // zones.tags varchar(255) the pipe-joined tags are written to; the length limit is a house rule on
    // any single tag, so the browser cap is a convenience and this is the one that actually holds.
    const TAG_STORAGE_LIMIT = 255;
    const TAG_MAX_LENGTH = 12;

    /**
     * Rules for one zone's pipe-joined tag string: the whole thing has to fit the column, and no one
     * tag may exceed the per-tag limit.
     */
    private function TagRules() {
        return ['nullable', 'string', 'max:' . self::TAG_STORAGE_LIMIT, function($attribute, $value, $fail) {
            foreach(explode('|', (string) $value) as $tag) {
                $tag = trim($tag);
                if(strlen($tag) > self::TAG_MAX_LENGTH)
                    $fail('Tag "' . $tag . '" is longer than ' . self::TAG_MAX_LENGTH . ' characters');
            }
        }];
    }

    /**
     * Rules for the single-zone save the map uses (storeZone), as opposed to the whole-ratesheet save.
     */
    public function GetZoneValidationRules($req) {
        $zoneName = $req->zone['name'] ?? 'zone';

        return [
            'rules' => ['zone.tags' => $this->TagRules()],
            'messages' => [
                'zone.tags.max' => 'Zone ' . $zoneName . ' tags cannot exceed ' . self::TAG_STORAGE_LIMIT . ' characters total',
            ],
        ];
    }

    public function GetValidationRules($req) {
        $rules = [
            'name' => 'required|min:3|max:255|unique:ratesheets,name,' . $req->ratesheet_id . ',ratesheet_id',
            'deliveryTypes.*.time' => 'required|numeric',
        ];

        $messages = [
            'name.required' => 'Name is a required field',
            'name.unique' => 'Ratesheet Name is already taken. Please try another',
        ];

        // Every ratesheet now needs a flat delivery-type cost: it is the base for any trip that does
        // not have a 'Set Base Price' conditional covering it (which is every trip on a map with no
        // zone-crossing rule at all).
        foreach($req->deliveryTypes as $key => $deliveryType) {
            $rules['deliveryTypes.' . $key . '.time'] = 'required|numeric|min:0';
            $rules['deliveryTypes.' . $key . '.cost'] = 'required|numeric|min:0';
            $messages['deliveryTypes.' . $key . '.time.required'] = 'Delivery type ' . $deliveryType['friendlyName'] . ' requires a default price';
            $messages['deliveryTypes.' . $key . '.time.min'] = 'Delivery type ' . $deliveryType['friendlyName'] . ' cost must be greater than 0';
            $messages['deliveryTypes.' . $key . '.cost.required'] = 'Delivery type ' . $deliveryType['friendlyName'] . ' additional cost cannot be blank';
            $messages['deliveryTypes.' . $key . '.cost.min'] = 'Delivery type ' . $deliveryType['friendlyName'] . ' additional cost must be at least 0';
        }



        $count = array('internal' => 0, 'peripheral' => 0, 'outlying' => 0);
        if($req->mapZones)
            foreach($req->mapZones as $key => $zone) {
                $rules['mapZones.' . $key . '.name'] = 'required';
                $rules['mapZones.' . $key . '.coordinates'] = 'required|min:3';
                $rules['mapZones.' . $key . '.tags'] = $this->TagRules();
                $messages['mapZones.' . $key . '.name.required'] = 'You must enter a name for new map zone #' . ($key + 1);
                $messages['mapZones.' . $key . '.tags.max'] = 'Zone ' . ($zone['name'] ?? ('#' . ($key + 1))) . ' tags cannot exceed ' . self::TAG_STORAGE_LIMIT . ' characters total';
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
