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
            $table->string('name');
            $table->boolean('use_internal_zones_calc');
            $table->text('delivery_types');
            $table->text('weight_rates');
            $table->text('zone_rates');
            $table->text('map_zones');
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
