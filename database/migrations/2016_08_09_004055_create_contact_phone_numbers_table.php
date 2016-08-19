<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactPhoneNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_phone_numbers', function (Blueprint $table) {
            $table->increments('contact_phone_number_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('phone_number_id');
            $table->boolean('is_primary');

			$table->foreign('contact_id')->references('contact_id')->on('contacts');
			$table->foreign('phone_number_id')->references('phone_number_id')->on('phone_numbers');
			$table->unique(array('contact_id', 'phone_number_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('contact_phone_numbers');
    }
}
