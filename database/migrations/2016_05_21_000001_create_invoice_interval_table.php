<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceIntervalTable extends Migration {
    public function up() {
        Schema::create('invoice_intervals', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('num_days')->default(0);
            $table->integer('num_months')->default(0);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('invoice_intervals');
    }
}

