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
            // dd(gettype($mapZone['coordinates']));
            $zone = [
                'coordinates' => $mapZone['coordinates'],
                //todo -> ability to clone coordinates
                'inherits_coordinates_from' => null,
                'name' => $mapZone['name'],
                'ratesheet_id' => $ratesheetId,
                'type' => $zoneTypes[$mapZone['type']],
                'zone_id' => $mapZone['zoneId'] ? $mapZone['zoneId'] : null
            ];
            if($mapZone['type'] === 'internal') {
                $zone = array_merge($zone, ['additional_costs' => null, 'additional_time' => null]);
            }else if($mapZone['type'] === 'peripheral') {
                $zone = array_merge($zone, ['additional_costs' => json_encode(['regular' => (float) $mapZone['regularCost']]), 'additional_time' => $mapZone['additionalTime']]);
            } else if ($mapZone['type'] === 'outlying') {
                $zone = array_merge($zone, [
                    'additional_costs' => json_encode([
                        'direct' => (float) $mapZone['directCost'],
                        'directRush' => (float) $mapZone['directRushCost'],
                        'regular' => (float) $mapZone['regularCost'],
                        'rush' => (float) $mapZone['rushCost']
                    ]),
                    'additional_time' => $mapZone['additionalTime']
                ]);
            }

            $zones = array_merge($zones, [$zone]);
        }

        return $zones;
    }
}

?>
