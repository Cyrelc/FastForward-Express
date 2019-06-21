<?php
namespace App\Http\Repos;

use App\Ratesheet;

class RatesheetRepo {
    public function GetById($id) {
        $ratesheet = Ratesheet::where('ratesheet_id', $id)->first();

        return $ratesheet;
    }

    public function Insert($ratesheet) {
        $new = new Ratesheet;
        return ($new->create($ratesheet));
    }

    public function Update($ratesheet) {
        $old = Ratesheet::where('ratesheet_id', $ratesheet['ratesheet_id'])->first();

        $old->name = $ratesheet['name'];
        $old->delivery_types = $ratesheet['delivery_types'];
        $old->weight_rates = $ratesheet['weight_rates'];
        $old->zone_rates = $ratesheet['zone_rates'];
        $old->map_zones = $ratesheet['map_zones'];
        $old->use_internal_zones_calc = $ratesheet['use_internal_zones_calc'];

        $old->save();
        return $old;
    }
}

?>
