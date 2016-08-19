<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_addresses', function (Blueprint $table) {
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('address_id');
            $table->boolean('is_primary');

			$table->foreign('address_id')->references('address_id')->on('addresses');
			$table->foreign('contact_id')->references('contact_id')->on('contacts');
			$table->primary(array('contact_id', 'address_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('contact_addresses');
    }
}
