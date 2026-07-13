<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The pricing "Add" and legacy "Charge" actions are one operation and are now presented as a single
 * "Add Charge". This normalizes existing rows so any rule whose action is 'add' or 'charge' stores
 * the canonical {value: 'add', label: 'Add Charge'} — fixing stale labels ("Add"/"Charge") in the
 * table and the edit dropdown. 'set' and 'discount' rules are left untouched.
 */
class NormalizeConditionalAddChargeAction extends Migration
{
    public function up()
    {
        $canonical = json_encode(['value' => 'add', 'label' => 'Add Charge']);

        foreach(DB::table('conditionals')->select('conditional_id', 'action')->get() as $conditional) {
            $action = json_decode($conditional->action, true);
            $value = $action['value'] ?? null;

            if($value === 'add' || $value === 'charge') {
                DB::table('conditionals')
                    ->where('conditional_id', $conditional->conditional_id)
                    ->update(['action' => $canonical]);
            }
        }
    }

    public function down()
    {
        // No-op: the pre-normalization split between 'add' and 'charge' carried no behavioral meaning.
    }
}
