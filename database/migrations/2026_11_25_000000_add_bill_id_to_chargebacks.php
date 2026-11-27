<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * chargebacks.bill_id exists on prod (added outside the migrations at some point) but in no migration, so
 * fresh databases lacked it and ManifestRepo's bill chargebacks silently dropped it. Bills use it to find
 * the manifest(s) their chargeback was deducted on.
 */
return new class extends Migration {
    public function up() {
        if(!Schema::hasColumn('chargebacks', 'bill_id'))
            Schema::table('chargebacks', function (Blueprint $table) {
                $table->unsignedInteger('bill_id')->nullable()->index();
            });
    }

    public function down() {
        // Not dropped: on prod the column predates this migration
    }
};
