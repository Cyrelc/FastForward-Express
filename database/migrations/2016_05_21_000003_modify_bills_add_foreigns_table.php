<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyBillsAddForeignsTable extends Migration {
    public function up() {
        Schema::table('bills', function($table) {
            $table->integer('customer_id')->unsigned()->default(0);
            $table->foreign('customer_id')
                    ->references('id')->on('customers');

            $table->integer('driver_pickup_id')->unsigned()->default(0);
            $table->foreign('driver_pickup_id')
                    ->references('id')->on('drivers');

            $table->integer('driver_dropoff_id')->unsigned()->default(0);
            $table->foreign('driver_dropoff_id')
                    ->references('id')->on('drivers');

            $table->float('pickup_amount')->default(0);
            $table->float('dropoff_amount')->default(0);
            $table->float('driver_comm')->default(0);
            $table->float('comm')->default(0);
            // Non Driver commission?
        });
    }

    public function down() {
        Schema::table('bills', function($table) {
            $table->dropForeign([
                    'customer_id',
                    'driver_pickup_id',
                    'driver_dropoff_id'
            ]);

            $table->dropColumn([
                    'pickup_amount',
                    'dropoff_amount',
                    'driver_comm',
                    'comm'
            ]);
        });
    }
}

