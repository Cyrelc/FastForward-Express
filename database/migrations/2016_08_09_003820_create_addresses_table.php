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
            $table->string('formatted');
            $table->float('lat')->nullable();
            $table->float('lng')->nullable();
            $table->string('place_id')->nullable()->default(null);
            $table->string('street'); //to be deprecated
            $table->string('street2'); //to be deprecated
            $table->string('city'); //to be deprecated
            $table->string('zip_postal'); //to be deprecated
            $table->string('state_province'); //to be deprecated
            $table->string('country'); //to be deprecated
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
