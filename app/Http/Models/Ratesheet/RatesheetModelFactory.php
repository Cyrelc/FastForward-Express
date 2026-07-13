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
        $model->miscRates = array();
        $model->zoneGroups = array();
        $model->conditionals = array();

        return $model;
    }

    public function GetEditModel($ratesheetId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $conditionalRepo = new Repos\ConditionalRepo();
        $zoneGroupRepo = new Repos\ZoneGroupRepo();

        $ratesheet = $ratesheetRepo->GetById($ratesheetId);
        $model = new RatesheetFormModel();
        $model->name = $ratesheet->name;
        $model->deliveryTypes = json_decode($ratesheet->delivery_types);
        $model->mapZones = $ratesheetRepo->GetMapZones($ratesheetId);
        $model->miscRates = json_decode($ratesheet->misc_rates);
        $model->ratesheets = $ratesheetRepo->ListAll();
        $model->conditionals = $conditionalRepo->GetByRatesheetId($ratesheetId);

        // Zone-crossing rates moved off the ratesheet and onto the group that is crossed.
        $model->zoneGroups = $zoneGroupRepo->GetByRatesheetId($ratesheetId)->map(function($zoneGroup) use ($model, $zoneGroupRepo) {
            $decoded = json_decode($zoneGroup->zone_rates, true) ?? [];

            return [
                'zoneGroupId' => $zoneGroup->zone_group_id,
                'name' => $zoneGroup->name,
                'colour' => $zoneGroup->colour,
                'zoneIds' => $zoneGroupRepo->GetZoneIds($zoneGroup->zone_group_id),
                'zoneRates' => array_map(function($zoneRate, $index) use ($model) {
                    return $this->PrepareZoneRate($zoneRate['id'] ?? $index, $zoneRate['zones'] ?? ($index + 1), $model->deliveryTypes, $zoneRate);
                }, $decoded, array_keys($decoded)),
            ];
        });

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
