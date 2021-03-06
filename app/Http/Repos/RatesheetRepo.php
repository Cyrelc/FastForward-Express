<?php
namespace App\Http\Repos;

use App\Ratesheet;
use App\Zone;

class RatesheetRepo {
    public function DeleteZone($zoneId) {
        $zone = Zone::where('zone_id', $zoneId)->first();
        $zone->delete();

        return;
    }

    public function GetById($id) {
        $ratesheet = Ratesheet::where('ratesheet_id', $id)->first();

        return $ratesheet;
    }

    public function GetMapZones($ratesheet_id) {
        $zones = Zone::where('ratesheet_id', $ratesheet_id)
            ->join('selections', 'selections.selection_id', '=', 'zones.type')
            ->select(
                'additional_costs',
                'additional_time',
                'coordinates',
                'inherits_coordinates_from',
                'zones.name',
                'selections.value as type',
                'zone_id'
            );

        return $zones->get();
    }

    public function Insert($ratesheet) {
        $new = new Ratesheet;

        return ($new->create($ratesheet));
    }

    public function InsertZone($zone) {
        $new = new Zone;

        return ($new->create($zone));
    }

    public function ListAll() {
        $ratesheets = Ratesheet::select('name', 'ratesheet_id', 'delivery_types', 'weekend_rate', 'holiday_rate');

        return $ratesheets->get();
    }

    public function ListAllNameAndId() {
        $ratesheets = Ratesheet::select('name', 'ratesheet_id');

        return $ratesheets->get();
    }

    public function GetForBillsPage($accountId = null, $children = false) {
        $ratesheets = Ratesheet::select(
            'name',
            'ratesheet_id',
            'delivery_types'
        );

        if($accountId) {
            $ratesheetIds = \App\Account::where('account_id', $accountId);
            if($children)
                $ratesheetIds->orWhere('parent_account_id', $accountId);
            $ratesheetIds = $ratesheetIds->pluck('ratesheet_id');
            $ratesheets->whereIn('ratesheet_id', $ratesheetIds);
        }

        return $ratesheets->get();
    }

    public function GetRatesheetSelectList() {
        $ratesheets = Ratesheet::select(
            'name as label',
            'ratesheet_id as value'
        );

        return $ratesheets->get();
    }

    public function SetZoneNeighbours($zoneId, $neighbours) {
        $zone = Zone::where('zone_id', $zoneId)->first();

        $zone->neighbours = $neighbours;

        $zone->save();
        return $zone;
    }

    public function Update($ratesheet) {
        $old = Ratesheet::where('ratesheet_id', $ratesheet['ratesheet_id'])->first();

        $old->name = $ratesheet['name'];
        $old->weekend_rate = $ratesheet['weekend_rate'];
        $old->holiday_rate = $ratesheet['holiday_rate'];
        $old->time_rates = $ratesheet['time_rates'];
        $old->delivery_types = $ratesheet['delivery_types'];
        $old->pallet_rate = $ratesheet['pallet_rate'];
        $old->weight_rates = $ratesheet['weight_rates'];
        $old->zone_rates = $ratesheet['zone_rates'];
        $old->use_internal_zones_calc = $ratesheet['use_internal_zones_calc'];

        $old->save();
        return $old;
    }

    public function UpdateZone($zone) {
        $old = Zone::where('zone_id', $zone['zone_id'])->first();

        $old->additional_costs = $zone['additional_costs'];
        $old->additional_time = $zone['additional_time'];
        $old->coordinates = $zone['coordinates'];
        $old->inherits_coordinates_from = $zone['inherits_coordinates_from'];
        $old->name = $zone['name'];
        // Currently, zones cannot be reassigned between ratesheets; Only created or deleted
        // $old->ratesheet_id = $zone['ratesheet_id'];
        // $old->type = $zone['type'];

        $old->save();
        return $old;
    }
}

?>
