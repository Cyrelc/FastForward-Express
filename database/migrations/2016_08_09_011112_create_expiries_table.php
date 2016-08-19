<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExpiriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expiries', function (Blueprint $table) {
            $table->increments('expiry_id');
            $table->string('description');
            $table->smallInteger('grace');
            $table->unsignedInteger('severity_id');
            $table->boolean('mandatory');
            $table->boolean('should_notify');
            $table->string('notification_type');

			$table->foreign('severity_id')->references('severity_id')->on('severities');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('expiries');
    }
}
