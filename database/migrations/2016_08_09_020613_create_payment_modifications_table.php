<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentModificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_modifications', function (Blueprint $table) {
            $table->unsignedInteger('payment_id');
            $table->unsignedInteger('modification_id');

			$table->foreign('payment_id')->references('payment_id')->on('payments');
			$table->foreign('modification_id')->references('modification_id')->on('modifications');
			$table->primary(array('payment_id', 'modification_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('payment_modifications');
    }
}
