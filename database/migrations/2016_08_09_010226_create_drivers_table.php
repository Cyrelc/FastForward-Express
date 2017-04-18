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
			$table->unsignedInteger('contact_id');
			$table->unsignedInteger('user_id');
			$table->string('driver_number')->nullable();
			$table->string('stripe_id')->nullable();
			$table->date('start_date');
			$table->string('drivers_license_number');
			$table->date('license_expiration');
			$table->string('license_plate_number');
			$table->date('license_plate_expiration');
			$table->string('insurance_number');
			$table->date('insurance_expiration');
			$table->string('sin');
			$table->date('dob');
			$table->boolean('active')->default(true);
			$table->decimal('pickup_commission');
			$table->decimal('delivery_commission');

			$table->unique('driver_number');
			$table->foreign('contact_id')->references('contact_id')->on('contacts');
			$table->foreign('user_id')->references('user_id')->on('users');
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
