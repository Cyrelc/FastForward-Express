<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_commissions', function (Blueprint $table) {
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('account_id');
            $table->decimal('commission');

			$table->foreign('driver_id')->references('driver_id')->on('drivers');
			$table->foreign('account_id')->references('account_id')->on('accounts');
			$table->primary(array('driver_id', 'account_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('driver_commissions');
    }
}
