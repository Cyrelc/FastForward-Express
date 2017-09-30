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
            $table->smallInteger('grace_days');
            $table->unsignedInteger('selection_id');
            $table->boolean('mandatory')->default(false);
            $table->boolean('should_notify')->default(true);
            $table->string('notification_type');

			$table->foreign('selection_id')->references('selection_id')->on('selections');
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
