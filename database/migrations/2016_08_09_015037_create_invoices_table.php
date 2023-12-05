<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('invoice_id');
            $table->unsignedInteger('account_id')->nullable();
            $table->date('bill_start_date');
            $table->date('bill_end_date');
            $table->float('balance_owing');
            $table->float('bill_cost');
            $table->date('date');
            $table->float('discount')->nullable();
            $table->boolean('finalized')->default(0);
            $table->float('min_invoice_amount')->nullable();
            $table->boolean('notification_sent')->default(0);
            $table->unsignedInteger('payment_type_id')->nullable();
            $table->float('tax');
            $table->float('total_cost');

            $table->foreign('account_id')->references('account_id')->on('accounts');
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
        Schema::drop('invoices');
    }
}
