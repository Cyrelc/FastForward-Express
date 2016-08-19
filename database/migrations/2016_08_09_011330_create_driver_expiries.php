<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverExpiries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_expiries', function (Blueprint $table) {
            $table->increments('driver_expiry_id');
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('expiry_id');

            $table->foreign('driver_id')->references('driver_id')->on('drivers');
            $table->foreign('expiry_id')->references('expiry_id')->on('expiries');
            $table->unique(array('expiry_id', 'driver_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('driver_expiries');
    }
}
