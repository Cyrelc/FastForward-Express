<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class ZoneCollector {
    public function Collect($mapZones, $ratesheetId) {
        $zones = [];
        $zoneTypes = [];

        $selectionsRepo = new Repos\SelectionsRepo();
        foreach($selectionsRepo->GetSelectionsByType('zone_type') as $zoneType) {
            $zoneTypes = array_merge($zoneTypes, [$zoneType->value => $zoneType->selection_id]);
        }

        foreach($mapZones as $mapZone) {
            $deliveryTypeModifiers = [];
            foreach(($mapZone['deliveryTypeModifiers'] ?? []) as $deliveryTypeName => $modifier) {
                if(!$deliveryTypeName)
                    continue;
                $cost = isset($modifier['cost']) && $modifier['cost'] !== '' ? $modifier['cost'] : 0;
                $time = isset($modifier['time']) && $modifier['time'] !== '' ? $modifier['time'] : 0;
                $deliveryTypeModifiers[$deliveryTypeName] = [
                    'cost' => is_numeric($cost) ? (float) $cost : 0,
                    'time' => is_numeric($time) ? (float) $time : 0,
                ];
            }

            $zone = [
                'coordinates' => $mapZone['coordinates'],
                'delivery_type_modifiers' => json_encode($deliveryTypeModifiers),
                'inherits_coordinates_from' => null,
                'name' => $mapZone['name'],
                'ratesheet_id' => $ratesheetId,
                'region' => isset($mapZone['region']) ? trim($mapZone['region']) : null,
                'tags' => $mapZone['tags'] ?? null,
                'type' => $zoneTypes[$mapZone['type']] ?? null,
                'zone_id' => $mapZone['zoneId'] ? $mapZone['zoneId'] : null
            ];

            $zones = array_merge($zones, [$zone]);
        }

        return $zones;
    }
}

?>
