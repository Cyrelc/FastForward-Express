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
            $table->unsignedInteger('account_id');
            $table->date('date');
            $table->date('bill_start_date');
            $table->date('bill_end_date');
            $table->float('balance_owing');
            $table->float('bill_cost');
            $table->float('tax');
            $table->float('total_cost');
            $table->float('fuel_surcharge');
            $table->float('discount')->nullable();
            $table->float('min_invoice_amount')->nullable();
            $table->boolean('finalized')->default(0);

			$table->foreign('account_id')->references('account_id')->on('accounts');
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
