<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChargebackModificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chargeback_modifications', function (Blueprint $table) {
            $table->unsignedInteger('driver_chargeback_id');
            $table->unsignedInteger('modification_id');

			$table->foreign('driver_chargeback_id')->references('driver_chargeback_id')->on('driver_chargebacks');
			$table->foreign('modification_id')->references('modification_id')->on('modifications');
			$table->primary(array('driver_chargeback_id', 'modification_id'), 'chargeback_modifications_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('chargeback_modifications');
    }
}
