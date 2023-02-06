<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountInvoiceSortOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_invoice_sort_order', function (Blueprint $table) {
            $table->increments('account_invoice_sort_order_id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('invoice_sort_option_id');
            $table->unsignedInteger('priority')->nullable();
            $table->boolean('subtotal_by')->default(0);

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
        Schema::dropIfExists('account_invoice_sort_order');
    }
}
