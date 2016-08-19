<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactEmailAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_email_addresses', function (Blueprint $table) {
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('email_address_id');
            $table->boolean('is_primary');

			$table->foreign('contact_id')->references('contact_id')->on('contacts');
			$table->foreign('email_address_id')->references('email_address_id')->on('email_addresses');
			$table->primary(array('contact_id', 'email_address_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('contact_email_addresses');
    }
}
