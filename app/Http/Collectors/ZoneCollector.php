<?php

namespace App\Http\Collectors;

use App\Http\Repos;

class ZoneCollector {
    public function Collect($mapZones, $ratesheetId) {
        $zones = [];

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
                'colour' => $this->normalizeColour($mapZone['colour'] ?? null),
                'coordinates' => $mapZone['coordinates'],
                'delivery_type_modifiers' => json_encode($deliveryTypeModifiers),
                'inherits_coordinates_from' => null,
                'name' => $mapZone['name'],
                'ratesheet_id' => $ratesheetId,
                'tags' => $mapZone['tags'] ?? null,
                'zone_id' => $mapZone['zoneId'] ? $mapZone['zoneId'] : null,
                // Not a zones column. The controller writes these to the zone_group_zone pivot after
                // groups have been upserted, since a newly created group has no id until then.
                'group_ids' => array_values($mapZone['groupIds'] ?? []),
            ];

            $zones = array_merge($zones, [$zone]);
        }

        return $zones;
    }

    /**
     * Zone colour is a user-picked hex fill. Anything that is not a #rrggbb is dropped rather than
     * stored, so the renderer falls back to the zone's group colour.
     */
    private function normalizeColour($colour) {
        if(!is_string($colour))
            return null;

        $colour = trim($colour);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $colour) ? strtolower($colour) : null;
    }
}

?>
