<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->increments('zone_id');

            $table->text('additional_costs')->nullable();
            $table->float('additional_time')->nullable();
            $table->text('coordinates');
            $table->unsignedInteger('inherits_coordinates_from')->nullable();
            $table->string('name');
            $table->text('neighbours')->nullable();
            $table->unsignedInteger('ratesheet_id');
            $table->unsignedInteger('type');

            $table->foreign('inherits_coordinates_from')->references('zone_id')->on('zones');
            $table->foreign('ratesheet_id')->references('ratesheet_id')->on('ratesheets');
            $table->foreign('type')->references('selection_id')->on('selections');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zones');
    }
}
