<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverTable extends Migration {
    public function up() {
        Schema::create('drivers', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('number')->unsigned()->unique();
            $table->string('name');
            $table->string('sin');
            $table->string('pager')->nullable();
            $table->boolean('active');
            $table->string('licence')->unique();
            $table->string('address');
            $table->string('postal');
            $table->string('phone');
            $table->string('email');
            $table->date('start');
            $table->float('per_pickup')->default(0.02);
            $table->float('per_dropoff')->default(0.02);
            $table->float('per_comm')->default(0);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('drivers');
    }
}

