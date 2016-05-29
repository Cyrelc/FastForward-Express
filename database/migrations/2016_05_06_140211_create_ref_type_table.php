<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRefTypeTable extends Migration {
    public function up() {
        Schema::create('ref_types', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('ref_types');
    }
}

