<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInterlinersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interliners', function (Blueprint $table) {
            $table->increments('interliner_id');
            $table->unsignedInteger('address_id');
            $table->string('name');

			$table->foreign('address_id')->references('address_id')->on('addresses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('interliners');
    }
}
