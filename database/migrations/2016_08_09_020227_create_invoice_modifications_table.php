<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceModificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_modifications', function (Blueprint $table) {
            $table->unsignedInteger('invoice_id');
            $table->unsignedInteger('modification_id');

			$table->foreign('invoice_id')->references('invoice_id')->on('invoices');
			$table->foreign('modification_id')->references('modification_id')->on('modifications');
			$table->primary(array('invoice_id', 'modification_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('invoice_modifications');
    }
}
