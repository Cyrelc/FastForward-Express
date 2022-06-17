<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_addresses', function (Blueprint $table) {
            $table->increments('email_address_id');
            $table->unsignedInteger('contact_id');
            $table->string('email');
            $table->boolean('is_primary')->default(true);
            $table->string('type')->nullable();

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
        Schema::drop('email_addresses');
    }
}
