<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyBillsAddInvoice extends Migration {
    public function up() {
        Schema::table('bills', function($table) {
            $table->integer('invoice_id')->unsigned()->nullable();
            $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices');
        });
    }

    public function down() {
        Schema::table('bills', function($table) {
            $table->dropColumn('invoice_id');
        });
    }
}

