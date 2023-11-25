<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_types', function (Blueprint $table) {
            $table->increments('payment_type_id');

            $table->unsignedInteger('default_ratesheet_id');
            $table->string('name');
            $table->string('required_field')->nullable()->default(null);
            $table->string('type');

            $table->foreign('default_ratesheet_id')->references('ratesheet_id')->on('ratesheets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_types');
    }
}
