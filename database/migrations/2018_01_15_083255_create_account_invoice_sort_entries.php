<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountInvoiceSortEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_invoice_sort_entries', function (Blueprint $table) {
            $table->increments('account_invoice_sort_entry_id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('invoice_sort_option_id');
            $table->integer('priority');
            $table->boolean('subtotal')->default(false);

            $table->foreign('account_id')->references('account_id')->on('accounts');
            $table->foreign('invoice_sort_option_id')->references('invoice_sort_option_id')->on('invoice_sort_options');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('account_invoice_sort_entries');
    }
}
