<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original activity_log migration predates (or was hand-copied without) the subject morph index that
 * spatie/laravel-activitylog's own migration creates, so every lookup by subject scanned the whole table
 * (~770k rows, ~5.5s per bill page load on a production-sized copy).
 *
 * - subject: every "history of X" lookup (bills, charges, line items, addresses, employees...). Same name the
 *   package's nullableMorphs('subject', 'subject') uses.
 * - subject_description: lets GetBillActivityLog search only the "deleted" entries of charges/line items
 *   (the only entries that still name their parent once the row is gone) instead of all of them.
 *
 * InnoDB builds these online - reads and writes carry on while it runs (~20s each on a copy of prod data).
 */
return new class extends Migration {
    public function up() {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'subject');
            $table->index(['subject_type', 'description'], 'subject_description');
        });
    }

    public function down() {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('subject');
            $table->dropIndex('subject_description');
        });
    }
};
