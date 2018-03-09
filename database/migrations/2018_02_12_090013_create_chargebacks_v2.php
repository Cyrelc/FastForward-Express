<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChargebacksV2 extends Migration
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
            $table->unsignedInteger('employee_id');
            $table->float('amount');
            $table->string('gl_code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('continuous')->default(false);
            $table->unsignedInteger('count_remaining');
            $table->date('start_date');

            $table->foreign('employee_id')->references('employee_id')->on('employees');
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
