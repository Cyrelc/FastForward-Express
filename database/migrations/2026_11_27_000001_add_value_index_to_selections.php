<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bill queries join selections on value (e.g. selections.value = bills.delivery_type) to get a type's display name.
 * Without an index on value, MySQL drove those joins from selections and re-read every matching row once per
 * selection - an invoice's bill table query read its 675 line items 39 times over (29 subtotal groups: 2.3s -> 0.14s).
 */
return new class extends Migration {
    public function up() {
        Schema::table('selections', function (Blueprint $table) {
            $table->index('value');
        });
    }

    public function down() {
        Schema::table('selections', function (Blueprint $table) {
            $table->dropIndex(['value']);
        });
    }
};
