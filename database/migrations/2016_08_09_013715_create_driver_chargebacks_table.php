<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverChargebacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_chargebacks', function (Blueprint $table) {
            $table->increments('driver_chargeback_id');
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('chargeback_id');
            $table->date('charge_date');
            $table->decimal('amount');

			$table->foreign('chargeback_id')->references('chargeback_id')->on('chargebacks');
			$table->foreign('driver_id')->references('driver_id')->on('drivers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('driver_chargebacks');
    }
}
