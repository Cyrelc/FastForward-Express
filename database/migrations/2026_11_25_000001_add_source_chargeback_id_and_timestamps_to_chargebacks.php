<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - source_chargeback_id: a manifest's chargeback deductions are copies of the chargeback they were taken from.
 *   Without a link back, deleting a manifest could not hand the deduction back, and the charge was silently
 *   forgiven. (A driver_chargebacks join table held this link until late 2020.)
 * - created_at / updated_at: chargebacks had none. Existing rows stay NULL; we can't know when they were made.
 */
return new class extends Migration {
    public function up() {
        Schema::table('chargebacks', function (Blueprint $table) {
            if(!Schema::hasColumn('chargebacks', 'source_chargeback_id'))
                $table->unsignedInteger('source_chargeback_id')->nullable()->index();
            if(!Schema::hasColumn('chargebacks', 'created_at'))
                $table->nullableTimestamps();
        });
    }

    public function down() {
        Schema::table('chargebacks', function (Blueprint $table) {
            if(Schema::hasColumn('chargebacks', 'source_chargeback_id'))
                $table->dropColumn('source_chargeback_id');
            if(Schema::hasColumn('chargebacks', 'created_at'))
                $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
