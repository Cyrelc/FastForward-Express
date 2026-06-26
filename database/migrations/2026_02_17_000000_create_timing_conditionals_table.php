<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimingConditionalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timing_conditionals', function (Blueprint $table) {
            $table->increments('timing_conditional_id');
            $table->timestamps();
            $table->text('action');
            $table->boolean('continue_on')->default(true);
            $table->text('human_readable');
            $table->text('json_logic');
            $table->text('name');
            $table->unsignedInteger('priority')->nullable();
            $table->unsignedInteger('ratesheet_id');
            $table->text('value');
            $table->text('value_type');

            $table->foreign('ratesheet_id')->references('ratesheet_id')->on('ratesheets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timing_conditionals');
    }
}
