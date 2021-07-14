<?php

namespace App\Http\Collectors;

class RatesheetCollector {
    public function Collect($req) {
        //TODO - clean up timeRates and pull dates out of them (time value is needed only)
        return [
            'ratesheet_id' => isset($req->ratesheet_id) ? $req->ratesheet_id : null,
            'name' => $req->name,
            'use_internal_zones_calc' => filter_var($req->useInternalZonesCalc, FILTER_VALIDATE_BOOLEAN),
            'weekend_rate' => $req->weekendRate,
            'holiday_rate' => $req->holidayRate,
            'delivery_types' => json_encode($req->deliveryTypes),
            'pallet_rate' => json_encode($req->palletRate),
            'time_rates' => json_encode($req->timeRates),
            'weight_rates' => json_encode($req->weightRates),
            'zone_rates' => json_encode($req->zoneRates)
        ];
    }
}

?>
