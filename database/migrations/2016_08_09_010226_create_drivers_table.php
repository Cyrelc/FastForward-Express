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
			$table->string('driver_number');
			$table->string('stripe_id');
			$table->timestamp('start_date');
			$table->string('license_plate_number');
			$table->boolean('active');
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
