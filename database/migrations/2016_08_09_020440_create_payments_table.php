<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('payment_id');
            $table->unsignedInteger('invoice_id');
            $table->unsignedInteger('payment_method_id');
            $table->float('amount');
            $table->timestamp('date');
            $table->string('comment')->nullable();

			$table->foreign('invoice_id')->references('invoice_id')->on('invoices');
			$table->foreign('payment_method_id')->references('payment_method_id')->on('payment_methods');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('payments');
    }
}
