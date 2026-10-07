<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ChargeModelFactory emits line items with type 'conditionalRate' (zone/conditional pricing),
 * but no matching 'charge_type' selection was ever seeded. LineItem::typeName() resolves the
 * friendly name via Selection::...->firstOrFail(), so printing any bill with a conditionalRate
 * line item threw ModelNotFoundException -> 404 on /bills/print/{id}. Seed the missing selection.
 *
 * Idempotent: skips the insert if the selection already exists.
 */
class SeedConditionalRateSelection extends Migration
{
    public function up()
    {
        $exists = DB::table('selections')
            ->where('type', 'charge_type')
            ->where('value', 'conditionalRate')
            ->exists();

        if(!$exists)
            DB::table('selections')->insert([
                'name' => 'Conditional Rate',
                'value' => 'conditionalRate',
                'type' => 'charge_type',
            ]);
    }

    public function down()
    {
        DB::table('selections')
            ->where('type', 'charge_type')
            ->where('value', 'conditionalRate')
            ->delete();
    }
}
