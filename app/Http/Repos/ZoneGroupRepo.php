<?php
namespace App\Http\Repos;

use App\Models\ZoneGroup;
use Illuminate\Support\Facades\DB;

class ZoneGroupRepo {
    public function GetByRatesheetId($ratesheetId) {
        return ZoneGroup::where('ratesheet_id', $ratesheetId)
            ->orderBy('zone_group_id')
            ->get();
    }

    public function GetById($zoneGroupId) {
        return ZoneGroup::where('zone_group_id', $zoneGroupId)->first();
    }

    /**
     * zone_id => [zone_group_id, ...] for every zone on the ratesheet that belongs to a group.
     * Group membership is many-to-many, so this is the map the pricing engine intersects to find
     * the groups a pickup and delivery zone have in common.
     */
    public function GetGroupIdsByZone($ratesheetId) {
        $rows = DB::table('zone_group_zone')
            ->join('zone_groups', 'zone_groups.zone_group_id', '=', 'zone_group_zone.zone_group_id')
            ->where('zone_groups.ratesheet_id', $ratesheetId)
            ->select('zone_group_zone.zone_id', 'zone_group_zone.zone_group_id')
            ->get();

        $groupIdsByZone = [];
        foreach($rows as $row)
            $groupIdsByZone[$row->zone_id][] = $row->zone_group_id;

        return $groupIdsByZone;
    }

    /**
     * The zone_ids belonging to a group - the traversal universe for that group's zones-crossed BFS.
     */
    public function GetZoneIds($zoneGroupId) {
        return DB::table('zone_group_zone')
            ->where('zone_group_id', $zoneGroupId)
            ->pluck('zone_id')
            ->all();
    }

    public function Insert($zoneGroup) {
        $new = new ZoneGroup;

        return $new->create($zoneGroup);
    }

    public function Update($zoneGroup) {
        $old = ZoneGroup::where('zone_group_id', $zoneGroup['zone_group_id'])->first();

        $old->name = $zoneGroup['name'];
        $old->colour = $zoneGroup['colour'];
        $old->zone_rates = $zoneGroup['zone_rates'];

        $old->save();

        return $old;
    }

    public function Delete($zoneGroupId) {
        DB::table('zone_group_zone')->where('zone_group_id', $zoneGroupId)->delete();
        ZoneGroup::where('zone_group_id', $zoneGroupId)->delete();
    }

    /**
     * Replaces a zone's group membership wholesale.
     */
    public function SetZoneGroups($zoneId, $zoneGroupIds) {
        DB::table('zone_group_zone')->where('zone_id', $zoneId)->delete();

        foreach(array_unique($zoneGroupIds ?? []) as $zoneGroupId)
            DB::table('zone_group_zone')->insert([
                'zone_group_id' => $zoneGroupId,
                'zone_id' => $zoneId,
            ]);
    }
}
