<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceSortOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_sort_options', function (Blueprint $table) {
            $table->increments('invoice_sort_option_id');
            $table->boolean('can_be_subtotaled')->default(false);
            $table->string('contingent_field')->nullable();
            $table->string('database_field_name')->unique();
            $table->string('friendly_name')->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('invoice_sort_options');
    }
}
