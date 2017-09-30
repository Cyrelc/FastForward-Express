<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EmployeeEmergencyContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('contact_id');
            $table->boolean('is_primary');

            $table->foreign('employee_id')->references('employee_id')->on('employees');
            $table->foreign('contact_id')->references('contact_id')->on('contacts');
            $table->primary(array('employee_id', 'contact_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('employee_emergency_contacts');
    }
}
