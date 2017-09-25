<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceOption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_options', function (Blueprint $table) {
            $table->increments('invoice_option_id');
            $table->string('name');
            $table->string('field');
            $table->boolean('subtotal');
            $table->integer('priority');
            $table->unsignedInteger('account_id');

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
        Schema::drop('invoice_options');
    }
}
