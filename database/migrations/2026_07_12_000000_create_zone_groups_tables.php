<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A zone group is the unit over which "zones crossed" is computed. It replaces the hardcoded
 * internal/peripheral zone-type gate: the BFS traverses only zones that belong to the group, and
 * the group owns the count -> {cost, time} rate table that used to live on ratesheets.zone_rates.
 *
 * Membership is many-to-many: a zone may belong to several groups (a map may split zones into
 * groups for organizational reasons as well as for crossing). When pickup and delivery share more
 * than one group, every shared group yields a candidate and the engine takes the cheapest (pricing)
 * or the fastest (timing) - see ChargeModelFactory::BuildZoneCrossingCandidates.
 *
 * A group existing does NOT make crossing apply. It only makes the count computable; a pricing
 * and/or timing conditional decides whether to use it.
 */
class CreateZoneGroupsTables extends Migration
{
    public function up()
    {
        if(!Schema::hasTable('zone_groups')) {
            Schema::create('zone_groups', function (Blueprint $table) {
                $table->increments('zone_group_id');
                $table->unsignedInteger('ratesheet_id');
                $table->string('name');
                // Map display colour (fill). Stroke is derived by darkening at render time.
                $table->string('colour', 7)->nullable();
                // [{"zones": 1, "Regular": {"cost": 6.80, "time": 1}, ...}, ...]
                $table->text('zone_rates')->nullable();
                $table->timestamps();

                $table->foreign('ratesheet_id')->references('ratesheet_id')->on('ratesheets')->onDelete('cascade');
                $table->unique(['ratesheet_id', 'name']);
            });
        }

        if(!Schema::hasTable('zone_group_zone')) {
            Schema::create('zone_group_zone', function (Blueprint $table) {
                $table->unsignedInteger('zone_group_id');
                $table->unsignedInteger('zone_id');

                $table->primary(['zone_group_id', 'zone_id']);
                $table->foreign('zone_group_id')->references('zone_group_id')->on('zone_groups')->onDelete('cascade');
                $table->foreign('zone_id')->references('zone_id')->on('zones')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('zone_group_zone');
        Schema::dropIfExists('zone_groups');
    }
}
