<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zone map colour was previously derived from zone type (polyColours in Classes/Zone.js). Type is
 * being dropped in favour of tags, so colour becomes a stored, user-editable attribute instead.
 *
 * Backfilled with the exact fill colours the map renders today, so no map changes appearance.
 * Zones with no colour of their own fall back to their group's colour at render time.
 *
 * Must run BEFORE 2026_07_12_000004, which drops zones.type.
 */
class AddColourToZonesTable extends Migration
{
    // Mirrors polyColours in resources/assets/js/components/ratesheets/Classes/Zone.js
    private $typeFillColours = [
        'internal' => '#8491c9',
        'outlying' => '#e8a466',
        'peripheral' => '#3bd82d',
    ];

    public function up()
    {
        if(!Schema::hasColumn('zones', 'colour')) {
            Schema::table('zones', function (Blueprint $table) {
                $table->string('colour', 7)->nullable()->after('coordinates');
            });
        }

        if(!Schema::hasColumn('zones', 'type'))
            return;

        foreach($this->typeFillColours as $typeValue => $colour) {
            $selectionId = DB::table('selections')
                ->where('type', 'zone_type')
                ->where('value', $typeValue)
                ->value('selection_id');

            if(!$selectionId)
                continue;

            DB::table('zones')
                ->where('type', $selectionId)
                ->whereNull('colour')
                ->update(['colour' => $colour]);
        }
    }

    public function down()
    {
        if(Schema::hasColumn('zones', 'colour'))
            Schema::table('zones', function (Blueprint $table) {
                $table->dropColumn('colour');
            });
    }
}
