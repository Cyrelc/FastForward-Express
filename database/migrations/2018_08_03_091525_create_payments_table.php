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
        Schema::create('payments', function(Blueprint $table) {
            $table->softDeletes();

            $table->increments('payment_id');

            $table->unsignedInteger('account_id')->nullable();
            $table->float('amount');
            $table->string('comment')->nullable();
            $table->date('date');
            $table->string('error')->nullable();
            $table->unsignedInteger('invoice_id')->nullable();
            $table->unsignedInteger('payment_type_id');
            $table->string('payment_intent_id')->nullable();
            $table->string('reference_value')->nullable();

            $table->foreign('account_id')->references('account_id')->on('accounts');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices');
            $table->foreign('payment_type_id')->references('payment_type_id')->on('payment_types');
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
