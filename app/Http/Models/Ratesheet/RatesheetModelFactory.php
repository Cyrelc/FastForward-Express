<?php
namespace App\Http\Models\Ratesheet;

use App\Http\Repos;
use App\Http\Models;
use App\Http\Models\Ratesheet;

class RatesheetModelFactory {
    public function ListAll() {
        $ratesheetRepo = new Repos\RatesheetRepo();
        return $ratesheetRepo->ListAll();
    }

    public function GetById($id) {
        return null;
    }

    public function GetCreateModel() {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();
        $conditionalRepo = new Repos\ConditionalRepo();

        $model = new RatesheetFormModel();

        $deliveryTypes = $selectionsRepo->GetSelectionsByType('delivery_type');
        $model->ratesheets = $ratesheetRepo->ListAll();

        $model->name = '';
        $model->deliveryTypes = array_map(array($this, 'PrepareDeliveryType'), $deliveryTypes->toArray());
        // $model->timeRates = array(['name' => 'Evenings', 'price' => '', 'brackets' => array(['startTime' => '', 'endTime' => '', 'startDayOfWeek' => '', 'endDayOfWeek' => ''])],
        //                         ['name' => 'Weekends', 'price' => '', 'brackets' => array(['startTime' => '', 'endTime' => '', 'startDayOfWeek' => '', 'endDayOfWeek' => ''])]);
        $model->timeRates = array();
        $model->weightRates = array(['name' => 'Basic Weight Rate', 'brackets' => array(['lbmax' => '', 'kbmax' => '', 'additionalXKgs' => '', 'additionalXLbs' => '', 'price' => ''])],
                                    ['name' => 'Pallet Weight Rate', 'brackets' => array(['lbmax' => '', 'kbmax' => '', 'additionalXKgs' => '', 'additionalXLbs' => '', 'price' => ''])]);
        $model->zoneRates = array([$this->PrepareZoneRate(0, 1, $model->deliveryTypes)]);
        $model->miscRates = array();
        $model->useInternalZonesCalc = false;
        $model->conditionals = array();

        return $model;
    }

    public function GetEditModel($ratesheetId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $conditionalRepo = new Repos\ConditionalRepo();

        $ratesheet = $ratesheetRepo->GetById($ratesheetId);
        $model = new RatesheetFormModel();
        $model->name = $ratesheet->name;
        $model->useInternalZonesCalc = filter_var($ratesheet->use_internal_zones_calc, FILTER_VALIDATE_BOOLEAN);
        $model->deliveryTypes = json_decode($ratesheet->delivery_types);
        $model->timeRates = json_decode($ratesheet->time_rates);
        $model->weightRates = json_decode($ratesheet->weight_rates);
        $decodedZoneRates = json_decode($ratesheet->zone_rates, true) ?? [];
        $model->zoneRates = array_map(function($zoneRate, $index) use ($model) {
            return $this->PrepareZoneRate($zoneRate['id'] ?? $index, $zoneRate['zones'] ?? ($index + 1), $model->deliveryTypes, $zoneRate);
        }, $decodedZoneRates, array_keys($decodedZoneRates));
        $model->mapZones = $ratesheetRepo->GetMapZones($ratesheetId);
        $model->miscRates = json_decode($ratesheet->misc_rates);
        $model->ratesheets = $ratesheetRepo->ListAll();
        $model->conditionals = $conditionalRepo->GetByRatesheetId($ratesheetId);

        foreach($model->mapZones as $mapZone)
            $mapZone->coordinates = json_decode($mapZone->coordinates);

        return $model;
    }

    static function PrepareDeliveryType($type, $new = true) {
        if($new)
            return [
                'friendlyName' => $type['name'],
                'id' => $type['value'],
                'time' => '',
                'cost' => ''
            ];
        return [
            'friendlyName' => $type['name'],
            'id' => $type['id'],
            'time' => $type['time'],
            'cost' => $type['cost']
        ];
    }

    private function PrepareZoneRate($id, $zones, $deliveryTypes, $existing = null) {
        $zoneRate = [
            'id' => $id,
            'zones' => $zones,
        ];

        foreach(($deliveryTypes ?? []) as $deliveryType) {
            $friendlyName = is_array($deliveryType)
                ? ($deliveryType['friendlyName'] ?? null)
                : ($deliveryType->friendlyName ?? null);

            if(!$friendlyName)
                continue;

            $existingTypeRate = is_array($existing)
                ? ($existing[$friendlyName] ?? null)
                : null;

            if(!is_array($existingTypeRate) && is_array($existing)) {
                $legacyKey = strtolower(str_replace(' ', '_', $friendlyName)) . '_cost';
                $legacyCost = $existing[$legacyKey] ?? null;
                $defaultTime = is_array($deliveryType)
                    ? ($deliveryType['time'] ?? '')
                    : ($deliveryType->time ?? '');

                $existingTypeRate = [
                    'cost' => is_numeric($legacyCost) ? $legacyCost : '',
                    'time' => is_numeric($defaultTime) ? $defaultTime : '',
                ];
            }

            $zoneRate[$friendlyName] = [
                'cost' => is_array($existingTypeRate) ? ($existingTypeRate['cost'] ?? '') : '',
                'time' => is_array($existingTypeRate) ? ($existingTypeRate['time'] ?? '') : '',
            ];
        }

        return $zoneRate;
    }
}

?>
