<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyBillsAddInvoice extends Migration {
    public function up() {
        Schema::table('bills', function($table) {
            //Use integer in cents, as it's more precise.
            $table->dropColumn([
                'amount', 'int_amount',
                'driver_amount', 'taxes'
            ]);
            $table->integer('amount');
            $table->integer('int_amount');
            $table->integer('driver_amount');
            $table->integer('taxes');
        });
    }

    public function down() {
        Schema::table('bills', function($table) {
            $table->dropColumn([
                'amount', 'int_amount',
                'driver_amount', 'taxes'
            ]);
            $table->float('amount');
            $table->float('int_amount');
            $table->float('driver_amount');
            $table->float('taxes');
        });
    }
}

