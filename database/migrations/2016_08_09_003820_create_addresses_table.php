<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->increments('address_id');
            $table->string('name')->nullable();
            $table->string('formatted_address');
            $table->float('lat')->nullable();
            $table->float('lng')->nullable();
            $table->string('street');
            $table->string('street2');
            $table->string('city');
            $table->string('zip_postal');
            $table->string('state_province');
            $table->string('country');
            $table->boolean('is_primary')->default(true);
            $table->unsignedInteger('contact_id')->nullable();

            $table->foreign('contact_id')->references('contact_id')->on('contacts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('addresses');
    }
}
