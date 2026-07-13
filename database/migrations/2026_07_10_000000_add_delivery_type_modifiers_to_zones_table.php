<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciles schema drift: `zones.delivery_type_modifiers` is read/written throughout the
 * pricing code (see RatesheetRepo::GetMapZones and ChargeModelFactory) but was never covered by
 * a migration — production has it, a fresh `migrate` did not. Guarded so it is a no-op where the
 * column already exists.
 */
class AddDeliveryTypeModifiersToZonesTable extends Migration
{
    public function up()
    {
        if(!Schema::hasColumn('zones', 'delivery_type_modifiers')) {
            Schema::table('zones', function (Blueprint $table) {
                $table->text('delivery_type_modifiers')->nullable();
            });
        }
    }

    public function down()
    {
        if(Schema::hasColumn('zones', 'delivery_type_modifiers')) {
            Schema::table('zones', function (Blueprint $table) {
                $table->dropColumn('delivery_type_modifiers');
            });
        }
    }
}
