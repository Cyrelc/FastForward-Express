<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConditionalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conditionals', function (Blueprint $table) {
            $table->increments('conditional_id');
            $table->timestamps();
            $table->text('action');
            $table->text('equation_string')->nullable();
            $table->text('human_readable');
            $table->text('json_logic');
            $table->text('name');
            $table->unsignedInteger('ratesheet_id');
            $table->float('value');
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
        Schema::dropIfExists('conditionals');
    }
}
