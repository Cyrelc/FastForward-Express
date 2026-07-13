<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the pricing `conditionals` table in line with `timing_conditionals` so pricing rules can
 * use the same mechanics:
 *   - `value` becomes text so it can hold a field path (value_type = 'field'), not just a number.
 *     Existing numeric values are preserved as their string form and still coerce in arithmetic.
 *   - `continue_on` lets a matched rule stop further rule processing (short-circuit), matching
 *     the timing engine.
 */
class UpgradeConditionalsForFieldValues extends Migration
{
    public function up()
    {
        Schema::table('conditionals', function (Blueprint $table) {
            $table->text('value')->change();

            if(!Schema::hasColumn('conditionals', 'continue_on'))
                $table->boolean('continue_on')->default(true);
        });
    }

    public function down()
    {
        Schema::table('conditionals', function (Blueprint $table) {
            if(Schema::hasColumn('conditionals', 'continue_on'))
                $table->dropColumn('continue_on');

            $table->double('value', 8, 2)->change();
        });
    }
}
