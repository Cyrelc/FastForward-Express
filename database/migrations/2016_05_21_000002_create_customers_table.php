<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration {
    public function up() {
        Schema::create('customers', function(Blueprint $table) {
            $table->increments('id');
            $table->string('company_name');
            $table->string('address');
            $table->string('postal_code');
            $table->string('bill_address');
            $table->string('bill_postal_code');
            $table->string('contact_name');
            $table->string('phone_nums');
            $table->string('email');

            $table->integer('parent_id')
                    ->references('id')
                    ->on('customers')
                    ->unsigned()
                    ->nullable();

            $table->integer('rate_type_id')->unsigned()->nullable();
            $table->foreign('rate_type_id')
                    ->references('id')
                    ->on('rate_types');

            $table->integer('invoice_interval_id')->unsigned()->nullable();
            $table->foreign('invoice_interval_id')
                    ->references('id')
                    ->on('invoice_intervals');

            $table->date('invoice_start');

            $table->boolean('autonumber_bills')->default(true);
            $table->boolean('has_reference_field')->default(false);
            $table->boolean('tax_exempt')->default(false);
            $table->boolean('apply_interest')->default(false);

            $table->integer('driver_comm_id')->nullable();
            $table->integer('comm_id')->nullable();

            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('customers');
    }
}

