<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRatesheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ratesheets', function (Blueprint $table) {
            $table->increments('ratesheet_id');

            $table->text('delivery_types');
            $table->float('holiday_rate');
            $table->string('name');
            $table->text('pallet_rate');
            $table->text('time_rates')->nullable();
            $table->boolean('use_internal_zones_calc');
            $table->text('weight_rates');
            $table->float('weekend_rate');
            $table->text('zone_rates');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ratesheets');
    }
}
