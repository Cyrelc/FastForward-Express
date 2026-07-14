<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the ratesheets table back in line with what the application actually uses - and, more to the
 * point, back in line with itself.
 *
 * The schema had drifted in BOTH directions, by hand, with no migration recording either change:
 *
 *   production          holiday_rate, weekend_rate, pallet_rate dropped;  misc_rates added
 *   built from migrations   all three still present;                      misc_rates absent
 *
 * So the migrations no longer reproduced production, and a freshly built environment was broken in the
 * opposite direction to the live one: holiday_rate and weekend_rate are float NOT NULL with no default,
 * so Ratesheet::create() (which cannot supply them - they are not fillable) fails outright, and
 * RatesheetRepo::Update writes misc_rates, which did not exist. Nobody noticed because prod is never
 * built from migrations and the tests never exercised either path.
 *
 * The three rate columns are genuinely dead: nothing in the pricing engine has read holiday_rate or
 * weekend_rate in living memory - whatever holiday and weekend surcharging the ratesheet appears to
 * offer has never been applied - and pallet_rate was collected on save but silently discarded by the
 * model's $fillable. All three are expressible as conditionals now ("Pickup Day of the Week is
 * Saturday -> Add Charge"), which is where that behaviour belongs.
 *
 * Every step is guarded, so this is a no-op on whichever side of the drift a given database sits on and
 * safe to run against both.
 */
return new class extends Migration {
    public function up() {
        Schema::table('ratesheets', function(Blueprint $table) {
            foreach(['holiday_rate', 'weekend_rate', 'pallet_rate'] as $column)
                if(Schema::hasColumn('ratesheets', $column))
                    $table->dropColumn($column);
        });

        // Live on production, and read by the Billing tab, but never migrated. Reconstructed here so a
        // fresh environment is not missing a column the application writes on every ratesheet save.
        if(!Schema::hasColumn('ratesheets', 'misc_rates'))
            Schema::table('ratesheets', function(Blueprint $table) {
                $table->text('misc_rates')->nullable();
            });
    }

    /**
     * Asymmetric on purpose.
     *
     * The three rate columns come back nullable rather than NOT NULL: their data is long gone on
     * production, and recreating them as required would make a rollback fail on the first ratesheet
     * save. misc_rates is deliberately NOT dropped - it holds live data that this migration did not
     * create, and a down() that destroys real rates in order to tidy a schema is not a rollback.
     */
    public function down() {
        Schema::table('ratesheets', function(Blueprint $table) {
            if(!Schema::hasColumn('ratesheets', 'holiday_rate'))
                $table->float('holiday_rate')->nullable();
            if(!Schema::hasColumn('ratesheets', 'weekend_rate'))
                $table->float('weekend_rate')->nullable();
            if(!Schema::hasColumn('ratesheets', 'pallet_rate'))
                $table->text('pallet_rate')->nullable();
        });
    }
};
