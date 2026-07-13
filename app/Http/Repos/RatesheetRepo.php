<?php
namespace App\Http\Repos;

use App\Models\Ratesheet;
use App\Models\Zone;
use Illuminate\Support\Facades\Auth;

class RatesheetRepo {
    private $myAccountIds;

    public function __construct() {
        $user = Auth::user();

        $accountRepo = new AccountRepo();
        $this->myAccountIds = $user->accountUsers ? $accountRepo->GetMyAccountIds($user, $user->can('bills.view.basic.children')) : null;
    }

    public function DeleteZone($zoneId) {
        $zone = Zone::where('zone_id', $zoneId)->first();
        $zone->delete();

        return;
    }

    public function GetById($id) {
        $ratesheet = Ratesheet::where('ratesheet_id', $id)->first();

        return $ratesheet;
    }

    private $zoneFields = [
        'colour',
        'coordinates',
        'delivery_type_modifiers',
        'inherits_coordinates_from',
        'zones.name',
        'neighbours',
        'tags',
        'zone_id',
    ];

    public function GetMapZones($ratesheet_id) {
        $zones = Zone::where('ratesheet_id', $ratesheet_id)
            ->select($this->zoneFields)
            ->get();

        return $this->attachGroups($zones, $ratesheet_id);
    }

    public function GetMapZoneById($ratesheet_id, $zoneId) {
        $zone = Zone::where('ratesheet_id', $ratesheet_id)
            ->where('zone_id', $zoneId)
            ->select($this->zoneFields)
            ->first();

        if(!$zone)
            return null;

        return $this->attachGroups(collect([$zone]), $ratesheet_id)->first();
    }

    /**
     * Hangs each zone's group membership off the zone as `group_ids` / `groups` (names). Zones are
     * many-to-many with groups, so this cannot be a join without fanning out rows.
     */
    private function attachGroups($zones, $ratesheet_id) {
        $zoneGroupRepo = new ZoneGroupRepo();
        $groupIdsByZone = $zoneGroupRepo->GetGroupIdsByZone($ratesheet_id);
        $groupNamesById = $zoneGroupRepo->GetByRatesheetId($ratesheet_id)->pluck('name', 'zone_group_id');

        foreach($zones as $zone) {
            $groupIds = $groupIdsByZone[$zone->zone_id] ?? [];
            $zone->group_ids = array_values($groupIds);
            $zone->groups = array_values(array_filter(array_map(function($groupId) use ($groupNamesById) {
                return $groupNamesById[$groupId] ?? null;
            }, $groupIds)));
        }

        return $zones;
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
        $ratesheets = Ratesheet::select('name', 'ratesheet_id', 'delivery_types');

        return $ratesheets->get();
    }

    public function ListAllNameAndId() {
        $ratesheets = Ratesheet::select('name', 'ratesheet_id');

        return $ratesheets->get();
    }

    public function GetForBillsPage($accountId = null, $children = false) {
        $fieldArray = array('name', 'ratesheet_id', 'delivery_types');
        $canSeeRates = Auth::user()->can('createFull', Bill::class) || Auth::user()->can('viewBilling', Bill::class);
        if($canSeeRates)
            $fieldArray = array_merge($fieldArray, array('misc_rates'));

        $ratesheets = Ratesheet::select($fieldArray);

        if($this->myAccountIds) {
            $accountRepo = new AccountRepo();
            $paymentRepo = new PaymentRepo();
            $accountPaymentType = $paymentRepo->GetAccountPaymentType();
            $ratesheetIds = $accountRepo->GetMyRatesheetIds($this->myAccountIds);
            foreach($ratesheetIds as $key => $ratesheetId)
                if($ratesheetId == null)
                    $ratesheetIds[$key] = $accountPaymentType->default_ratesheet_id;

            $ratesheets->whereIn('ratesheet_id', $ratesheetIds);
        }

        $ratesheets = $ratesheets->get();

        // The manual rate picker offers zone-crossing rates as pickable line items. They used to come
        // from ratesheets.zone_rates; they now belong to each zone group.
        if($canSeeRates) {
            $zoneGroupRepo = new ZoneGroupRepo();
            foreach($ratesheets as $ratesheet)
                $ratesheet->zone_groups = $zoneGroupRepo->GetByRatesheetId($ratesheet->ratesheet_id)
                    ->map(function($zoneGroup) {
                        return [
                            'name' => $zoneGroup->name,
                            'zone_rates' => json_decode($zoneGroup->zone_rates, true) ?? [],
                        ];
                    });
        }

        return $ratesheets;
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
        $old->delivery_types = $ratesheet['delivery_types'];
        $old->misc_rates = $ratesheet['misc_rates'];
        // weight_rates is not written: weight is priced by conditionals now. The column is left as-is
        // so it still holds the old brackets for a rollback (and for ratesheets:rebuild-weight-conditionals).
        // zone_rates is not written: zone-crossing rates belong to zone_groups now. The column is
        // left as-is so it still holds the old rates for a rollback.
        // use_internal_zones_calc is retired (zone crossing is now opt-in per conditional). The
        // column is left untouched so it still reads true for a rollback.

        $old->save();
        return $old;
    }

    public function UpdateZone($zone) {
        $old = Zone::find($zone['zone_id']);

        $old->colour = $zone['colour'];
        $old->delivery_type_modifiers = $zone['delivery_type_modifiers'];
        $old->coordinates = $zone['coordinates'];
        $old->inherits_coordinates_from = $zone['inherits_coordinates_from'];
        $old->name = $zone['name'];
        $old->tags = $zone['tags'];

        $old->save();

        return $old;
    }
}

?>
