<?php

namespace App\Http\Collectors;

class RatesheetCollector {
    public function Collect($req) {
        //TODO - clean up timeRates and pull dates out of them (time value is needed only)
        return [
            'ratesheet_id' => isset($req->ratesheetId) ? $req->ratesheetId : null,
            'name' => $req->name,
            'use_internal_zones_calc' => $req->useInternalZonesCalc === "true",
            'weekend_rate' => $req->weekendRate,
            'holiday_rate' => $req->holidayRate,
            'delivery_types' => json_encode($req->deliveryTypes),
            'pallet_rate' => json_encode($req->palletRate),
            'time_rates' => json_encode($req->timeRates),
            'weight_rates' => json_encode($req->weightRates),
            'zone_rates' => json_encode($req->zoneRates),
            'map_zones' => json_encode($req->mapZones)
        ];
    }
}

?>
