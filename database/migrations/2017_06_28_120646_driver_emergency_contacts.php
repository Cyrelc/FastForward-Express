<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DriverEmergencyContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_emergency_contacts', function (Blueprint $table) {
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('contact_id');

            $table->foreign('driver_id')->references('driver_id')->on('drivers');
            $table->foreign('contact_id')->references('contact_id')->on('contacts');
            $table->primary(array('driver_id', 'contact_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('driver_emergency_contacts');
    }
}
