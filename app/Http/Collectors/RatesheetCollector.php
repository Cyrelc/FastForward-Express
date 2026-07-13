<?php

namespace App\Http\Collectors;

class RatesheetCollector {
    public function Collect($req) {
        return [
            'ratesheet_id' => isset($req->ratesheet_id) ? $req->ratesheet_id : null,
            'name' => $req->name,
            'delivery_types' => json_encode($req->deliveryTypes),
            'pallet_rate' => json_encode($req->palletRate),
            'misc_rates' => json_encode($req->miscRates),
            // Legacy, write-once-on-create columns. Zone-crossing rates live on zone_groups, weight is
            // priced by conditionals, and crossing is opt-in per conditional; these are kept only so a
            // rollback has data to read.
            'use_internal_zones_calc' => false,
            'zone_rates' => json_encode([]),
            'weight_rates' => json_encode([]),
            'time_rates' => json_encode([]),
        ];
    }
}

?>
