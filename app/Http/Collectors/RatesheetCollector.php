<?php

namespace App\Http\Collectors;

class RatesheetCollector {
    public function Collect($req) {
        return [
            'ratesheet_id' => isset($req->ratesheetId) ? $req->ratesheetId : null,
            'name' => $req->name,
            'use_internal_zones_calc' => $req->useInternalZonesCalc === "true",
            'delivery_types' => json_encode($req->deliveryTypes),
            'weight_rates' => json_encode($req->weightRates),
            'zone_rates' => json_encode($req->zoneRates),
            'map_zones' => json_encode($req->mapZones)
        ];
    }
}

?>
