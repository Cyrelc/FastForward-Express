<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateManifestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manifests', function (Blueprint $table) {
            $table->increments('manifest_id');

            $table->date('date_run');
            $table->unsignedInteger('employee_id');
            $table->date('end_date');
            $table->date('start_date');

            $table->foreign('employee_id')->references('employee_id')->on('employees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('manifests');
    }
}
