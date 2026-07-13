<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The ratesheets.use_internal_zones_calc flag is retired: zone crossing is no longer something a
 * ratesheet does automatically, it is something a conditional asks for. This reproduces the old
 * behaviour exactly, for the maps that had the flag on, by authoring the two rules the engine used
 * to hardcode:
 *
 *   pricing: if the two zones share a group -> Set Base Price = zones_crossed_cost
 *   timing:  if the two zones share a group -> Set Time       = zones_crossed_time
 *
 * Maps that had the flag off get no rule, so their pricing and timing are bit-for-bit unchanged
 * (they keep falling back to the flat delivery-type cost/time) even though 2026_07_12_000002 gave
 * them a group.
 *
 * Priority 50: after any base-selecting hub/home rules a map may add (which run in the 10-20 range
 * and win under first-set-wins), before the outlying surcharges at 100.
 */
class BackfillZonesCrossedConditionals extends Migration
{
    private $sameGroupLogic = ['==' => [['var' => 'same_group'], true]];

    public function up()
    {
        $distanceRateType = DB::table('selections')
            ->where('type', 'charge_type')
            ->where('value', 'distanceRate')
            ->value('selection_id');

        $ratesheetIds = DB::table('ratesheets')
            ->where('use_internal_zones_calc', 1)
            ->pluck('ratesheet_id');

        foreach($ratesheetIds as $ratesheetId) {
            // Only meaningful if the map actually has a group to cross within.
            $hasGroup = DB::table('zone_groups')->where('ratesheet_id', $ratesheetId)->exists();
            if(!$hasGroup)
                continue;

            if(!DB::table('conditionals')->where('ratesheet_id', $ratesheetId)->where('name', 'Zone Crossing Base Price')->exists())
                DB::table('conditionals')->insert([
                    'action' => json_encode(['value' => 'set', 'label' => 'Set Base Price']),
                    'continue_on' => true,
                    'human_readable' => 'When pickup and delivery share a zone group, use the zone-crossing rate as the base price',
                    'json_logic' => json_encode($this->sameGroupLogic),
                    'name' => 'Zone Crossing Base Price',
                    'original_equation_string' => null,
                    'priority' => 50,
                    'ratesheet_id' => $ratesheetId,
                    'type' => $distanceRateType,
                    'value' => 'zones_crossed_cost',
                    'value_type' => 'field',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            if(!DB::table('timing_conditionals')->where('ratesheet_id', $ratesheetId)->where('name', 'Zone Crossing Time')->exists())
                DB::table('timing_conditionals')->insert([
                    'action' => json_encode(['value' => 'set', 'label' => 'Set Time']),
                    'continue_on' => true,
                    'human_readable' => 'When pickup and delivery share a zone group, use the zone-crossing time',
                    'json_logic' => json_encode($this->sameGroupLogic),
                    'name' => 'Zone Crossing Time',
                    'priority' => 50,
                    'ratesheet_id' => $ratesheetId,
                    'value' => 'timing_inputs.zones_crossed_time',
                    'value_type' => 'field',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down()
    {
        DB::table('conditionals')->where('name', 'Zone Crossing Base Price')->delete();
        DB::table('timing_conditionals')->where('name', 'Zone Crossing Time')->delete();
    }
}
