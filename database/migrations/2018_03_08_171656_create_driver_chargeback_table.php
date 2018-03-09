<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverChargebackTable extends Migration
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
            $table->unsignedInteger('chargeback_id');
            $table->unsignedInteger('manifest_id');

            $table->foreign('chargeback_id')->references('chargeback_id')->on('chargebacks');
            $table->foreign('manifest_id')->references('manifest_id')->on('manifests');
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
