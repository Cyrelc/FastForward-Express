<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExpiryModificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expiry_modifications', function (Blueprint $table) {
            $table->unsignedInteger('driver_expiry_id');
            $table->unsignedInteger('modification_id');

            $table->foreign('driver_expiry_id')->references('driver_expiry_id')->on('driver_expiries');
            $table->foreign('modification_id')->references('modification_id')->on('modifications');
			$table->primary(array('driver_expiry_id', 'modification_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('expiry_modifications');
    }
}
