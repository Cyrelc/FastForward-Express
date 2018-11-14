<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->increments('package_id');
            $table->int('count');
            $table->float('weight');
            $table->float('height');
            $table->float('width');
            $table->float('length');
            $table->unsignedInteger('bill_id');

            $table->foreign('bill_id')->references('bill_id')->on('bills');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('packages');
    }
}
