<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonerisTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('moneris_transactions', function (Blueprint $table) {
            $table->increments('moneris_transaction_id');
            $table->unsignedInteger('credit_card_id');
            $table->string('type');
            $table->string('order_id');
            $table->unsignedInteger('invoice_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->foreign('credit_card_id')->references('credit_card_id')->on('credit_cards');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('invoice_id')->references('invoice_id')->on('invoices');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('moneris_transactions');
    }
}
