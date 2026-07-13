<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The hardcoded outlying/peripheral surcharge was removed from ChargeModelFactory::generateDistanceCharges
 * and is now applied by conditionals. To preserve the current price on every existing map, this
 * backfill creates, per ratesheet, the two conditionals that reproduce the old block exactly:
 * add each non-internal side's per-delivery-type modifier cost (resolved via the pricing context's
 * pickup/delivery_zone_selected_type_cost fields).
 *
 * Idempotent: skips a side if a conditional of that name already exists for the ratesheet.
 * St Albert (and any map needing different behavior) is hand-corrected afterward.
 */
class BackfillOutlyingSurchargeConditionals extends Migration
{
    private $sides = [
        [
            'name' => 'Pickup Zone Surcharge',
            'zonePath' => 'pickup_address.zone.type',
            'valueField' => 'pickup_zone_selected_type_cost',
        ],
        [
            'name' => 'Delivery Zone Surcharge',
            'zonePath' => 'delivery_address.zone.type',
            'valueField' => 'delivery_zone_selected_type_cost',
        ],
    ];

    public function up()
    {
        // Categorize the backfilled rules as Distance Rate (cosmetic; the type is not used in
        // pricing but must be set so the rule renders with a type in the UI).
        $distanceRateType = DB::table('selections')
            ->where('type', 'charge_type')
            ->where('value', 'distanceRate')
            ->value('selection_id');

        $ratesheetIds = DB::table('ratesheets')->pluck('ratesheet_id');

        foreach($ratesheetIds as $ratesheetId) {
            foreach($this->sides as $side) {
                $exists = DB::table('conditionals')
                    ->where('ratesheet_id', $ratesheetId)
                    ->where('name', $side['name'])
                    ->exists();

                if($exists)
                    continue;

                DB::table('conditionals')->insert([
                    'action' => json_encode(['value' => 'add', 'label' => 'Add Charge']),
                    'continue_on' => true,
                    'human_readable' => 'Add the ' . strtolower(explode(' ', $side['name'])[0]) . ' zone surcharge for non-internal zones',
                    'json_logic' => json_encode(['!=' => [['var' => $side['zonePath']], 'internal']]),
                    'name' => $side['name'],
                    'original_equation_string' => null,
                    // Runs after any base-setting ('set') rules a map may later add.
                    'priority' => 100,
                    'ratesheet_id' => $ratesheetId,
                    'type' => $distanceRateType,
                    'value' => $side['valueField'],
                    'value_type' => 'field',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        foreach($this->sides as $side)
            DB::table('conditionals')
                ->where('name', $side['name'])
                ->where('value_type', 'field')
                ->where('value', $side['valueField'])
                ->delete();
    }
}
