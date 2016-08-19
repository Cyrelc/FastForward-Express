<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChargebacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chargebacks', function (Blueprint $table) {
            $table->increments('chargeback_id');
            $table->decimal('default_amount');
            $table->string('name');
            $table->string('gl_code');
            $table->string('frequency');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('chargebacks');
    }
}
