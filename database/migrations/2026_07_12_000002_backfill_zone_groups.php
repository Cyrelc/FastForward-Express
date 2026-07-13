<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recreates the old single implicit "crossable zones" graph as an explicit group, per ratesheet:
 * every internal or peripheral zone joins one group named 'Internal Zones', and that group takes
 * over the ratesheet's zone_rates table.
 *
 * zone_rates shape drift is repaired on the way. The engine reads {"Regular": {"cost": x, "time": y}}
 * but some sheets are still stored in the pre-existing flat shape ({"regular_cost": "6.80"}) - which
 * silently priced zone crossings at $0. Legacy costs are lifted into the current shape; a legacy row
 * has no times, so time falls back to the delivery type's default (which is what the ratesheet editor
 * has been showing for these rows all along).
 *
 * Creating a group does not turn crossing on - 2026_07_12_000003 adds the conditionals that do.
 */
class BackfillZoneGroups extends Migration
{
    private $palette = ['#8491c9', '#3bd82d', '#e8a466', '#c98491', '#84c9c1', '#c9b884'];

    public function up()
    {
        $crossableSelectionIds = DB::table('selections')
            ->where('type', 'zone_type')
            ->whereIn('value', ['internal', 'peripheral'])
            ->pluck('selection_id');

        if($crossableSelectionIds->isEmpty())
            return;

        $ratesheets = DB::table('ratesheets')->get();

        foreach($ratesheets as $index => $ratesheet) {
            $zoneIds = DB::table('zones')
                ->where('ratesheet_id', $ratesheet->ratesheet_id)
                ->whereIn('type', $crossableSelectionIds)
                ->pluck('zone_id');

            if($zoneIds->isEmpty())
                continue;

            $exists = DB::table('zone_groups')
                ->where('ratesheet_id', $ratesheet->ratesheet_id)
                ->where('name', 'Internal Zones')
                ->exists();

            if($exists)
                continue;

            $deliveryTypes = json_decode($ratesheet->delivery_types, true) ?? [];

            $zoneGroupId = DB::table('zone_groups')->insertGetId([
                'ratesheet_id' => $ratesheet->ratesheet_id,
                'name' => 'Internal Zones',
                'colour' => $this->palette[$index % count($this->palette)],
                'zone_rates' => json_encode($this->normalizeZoneRates($ratesheet->zone_rates, $deliveryTypes)),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'zone_group_id');

            foreach($zoneIds as $zoneId)
                DB::table('zone_group_zone')->insert([
                    'zone_group_id' => $zoneGroupId,
                    'zone_id' => $zoneId,
                ]);
        }
    }

    /**
     * Lifts a ratesheet's zone_rates into the canonical {friendlyName: {cost, time}} shape,
     * tolerating the legacy flat shape ({regular_cost: x}) and already-canonical rows alike.
     */
    private function normalizeZoneRates($zoneRatesJson, $deliveryTypes)
    {
        $zoneRates = json_decode($zoneRatesJson, true);
        if(!is_array($zoneRates))
            return [];

        $normalized = [];

        foreach($zoneRates as $index => $zoneRate) {
            $row = [
                'id' => $zoneRate['id'] ?? $index,
                'zones' => $zoneRate['zones'] ?? ($index + 1),
            ];

            foreach($deliveryTypes as $deliveryType) {
                $friendlyName = $deliveryType['friendlyName'] ?? $deliveryType['name'] ?? null;
                if(!$friendlyName)
                    continue;

                $existing = $zoneRate[$friendlyName] ?? null;

                if(is_array($existing)) {
                    $row[$friendlyName] = [
                        'cost' => $existing['cost'] ?? null,
                        'time' => $existing['time'] ?? null,
                    ];
                    continue;
                }

                // Legacy flat shape: "Direct Rush" => direct_rush_cost
                $legacyKey = strtolower(str_replace(' ', '_', $friendlyName)) . '_cost';
                $legacyCost = $zoneRate[$legacyKey] ?? null;
                $defaultTime = $deliveryType['time'] ?? null;

                $row[$friendlyName] = [
                    'cost' => is_numeric($legacyCost) ? (float) $legacyCost : null,
                    'time' => is_numeric($defaultTime) ? (float) $defaultTime : null,
                ];
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    public function down()
    {
        DB::table('zone_groups')->where('name', 'Internal Zones')->delete();
    }
}
