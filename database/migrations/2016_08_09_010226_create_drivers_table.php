<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->increments('driver_id');
			$table->unsignedInteger('employee_id')->unique();
			$table->string('drivers_license_number');
			$table->date('license_expiration');
			$table->string('license_plate_number');
			$table->date('license_plate_expiration');
			$table->string('insurance_number');
			$table->date('insurance_expiration');
			$table->decimal('pickup_commission');
			$table->decimal('delivery_commission');

            $table->foreign('employee_id')->references('employee_id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('drivers');
    }
}
