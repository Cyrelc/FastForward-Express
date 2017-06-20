<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::create('commissions', function (Blueprint $table) {
			$table->increments('commission_id');
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('account_id');
            $table->decimal('commission');
            $table->decimal('depreciation_amount');
            $table->integer('years');
            $table->date('start_date');

			$table->foreign('driver_id')->references('driver_id')->on('drivers');
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
        Schema::drop('commissions');
    }
}
