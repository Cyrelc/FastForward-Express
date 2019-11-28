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

    public function ListAll() {
        $ratesheets = Ratesheet::select('name', 'ratesheet_id', 'delivery_types', 'weekend_rate', 'holiday_rate');

        return $ratesheets->get();
    }

    public function ListAllNameAndId() {
        $ratesheets = Ratesheet::select('name', 'ratesheet_id');

        return $ratesheets->get();
    }

    public function Update($ratesheet) {
        $old = Ratesheet::where('ratesheet_id', $ratesheet['ratesheet_id'])->first();

        $old->name = $ratesheet['name'];
        $old->weekend_rate = $ratesheet['weekend_rate'];
        $old->holiday_rate = $ratesheet['holiday_rate'];
        $old->time_rates = $ratesheet['time_rates'];
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
