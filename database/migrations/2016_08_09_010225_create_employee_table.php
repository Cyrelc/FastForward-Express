<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->timestamps();
            $table->increments('employee_id');

            $table->boolean('active')->default(true);
            $table->string('company_name')->nullable();
            $table->unsignedInteger('contact_id');
            $table->float('delivery_commission')->nullable();
            $table->datetime('drivers_license_expiration_date')->nullable();
            $table->string('drivers_license_number')->nullable();
			$table->date('dob');
            $table->string('employee_number')->nullable();
            $table->datetime('insurance_expiration_date')->nullable();
            $table->string('insurance_number')->nullable();
            $table->boolean('is_driver')->default(false);
            $table->datetime('license_plate_expiration_date')->nullable();
            $table->string('license_plate_number')->nullable();
            $table->float('pickup_commission')->nullable();
            $table->string('sin')->nullable();
            $table->date('start_date');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('vehicle_type_id')->nullable();

            $table->unique('employee_number');
            $table->foreign('contact_id')->references('contact_id')->on('contacts');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('vehicle_type_id')->references('selection_id')->on('selections');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('employees');
    }
}
