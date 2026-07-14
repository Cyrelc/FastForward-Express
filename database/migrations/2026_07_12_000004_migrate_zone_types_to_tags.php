<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zone type is retired in favour of tags. Nothing reads type for money any more - the two gates that
 * used it (the crossable internal|peripheral check in generateDistanceCharges, and the neighbour
 * gate in RatesheetController) are both replaced by zone groups, and colour is now stored per zone.
 * All that is left is the conditionals that test it, which is data.
 *
 * Three steps, in order:
 *   1. every zone gains a tag equal to its type value ('internal' / 'peripheral' / 'outlying')
 *   2. every conditional (pricing and timing) that tests *.zone.type is rewritten to test that tag
 *   3. zones.type is dropped
 *
 * Step 2 handles ==, != and in against a zone-type var, at any depth inside the json_logic tree:
 *   {"!=": [{"var": "pickup_address.zone.type"}, "internal"]}
 *     -> {"!": {"in": ["internal", {"var": "pickup_address.zone.tags"}]}}
 *
 * tags is a pipe-joined varchar(255); a zone whose tags would overflow keeps its existing tags and
 * is reported, rather than being silently truncated.
 */
class MigrateZoneTypesToTags extends Migration
{
    public function up()
    {
        if(!Schema::hasColumn('zones', 'type'))
            return;

        $typeValuesBySelectionId = DB::table('selections')
            ->where('type', 'zone_type')
            ->pluck('value', 'selection_id');

        $overflowed = [];

        foreach(DB::table('zones')->get() as $zone) {
            $typeValue = $typeValuesBySelectionId[$zone->type] ?? null;
            if(!$typeValue)
                continue;

            $tags = array_values(array_filter(array_map('trim', explode('|', (string) $zone->tags))));
            if(in_array($typeValue, $tags, true))
                continue;

            $tags[] = $typeValue;
            $serialized = implode('|', $tags);

            if(strlen($serialized) > 255) {
                $overflowed[] = $zone->zone_id;
                continue;
            }

            DB::table('zones')->where('zone_id', $zone->zone_id)->update(['tags' => $serialized]);
        }

        if($overflowed)
            echo "\n  WARNING: zones " . implode(', ', $overflowed) . " already have 255 chars of tags; "
                . "their type tag was NOT added. Add it by hand after shortening their tags.\n";

        $this->rewriteConditionals('conditionals', 'conditional_id');
        $this->rewriteConditionals('timing_conditionals', 'timing_conditional_id');

        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeign(['type']);
            $table->dropColumn('type');
        });
    }

    /**
     * "this zone has any of these tags", in the EXACT shape the query builder emits for a multiselect
     * CONTAINS - not the equivalent-but-different {"in": [tag, {"var": path}]}.
     *
     * The shape matters as much as the meaning. Both forms evaluate identically in the engine, but the
     * builder can only load back the one it writes: given the other, it renders the rule with a blank
     * condition, and the first person to open and re-save it silently replaces the rule with whatever
     * they rebuilt by hand. A migrated rule nobody can edit without destroying it is not migrated.
     */
    private function hasAnyTag($tagsPath, array $tags)
    {
        return ['some' => [
            ['var' => $tagsPath],
            ['in' => [['var' => ''], array_values($tags)]],
        ]];
    }

    private function rewriteConditionals($table, $keyColumn)
    {
        foreach(DB::table($table)->get() as $conditional) {
            $rules = json_decode($conditional->json_logic, true);
            if(!is_array($rules))
                continue;

            $rewritten = $this->rewriteRules($rules);
            if($rewritten === $rules)
                continue;

            DB::table($table)
                ->where($keyColumn, $conditional->{$keyColumn})
                ->update(['json_logic' => json_encode($rewritten)]);
        }
    }

    /**
     * Recursively rewrites zone-type tests into tag-membership tests.
     */
    private function rewriteRules($node)
    {
        if(!is_array($node))
            return $node;

        foreach(['==', '!=', 'in'] as $operator) {
            if(!isset($node[$operator]) || !is_array($node[$operator]) || count($node[$operator]) !== 2)
                continue;

            [$left, $right] = $node[$operator];
            $tagsPath = $this->zoneTagsPathFor($left);

            if(!$tagsPath)
                continue;

            // 'in' against a zone-type var means "type is one of [...]" -> has any of those tags.
            if($operator === 'in') {
                if(!is_array($right))
                    continue;

                return $this->hasAnyTag($tagsPath, $right);
            }

            if(!is_string($right))
                continue;

            $hasTag = $this->hasAnyTag($tagsPath, [$right]);

            // != 'internal' becomes NOT(has the internal tag) - NOT "has the outlying tag". Those are
            // different sets: a peripheral zone is neither internal nor outlying, and the old engine
            // surcharged it (its test was literally type != 'internal'). Peripheral zones carry a real
            // $6-$10 modifier on every ratesheet, so getting this wrong silently drops that charge.
            return $operator === '==' ? $hasTag : ['!' => $hasTag];
        }

        $rewritten = [];
        foreach($node as $key => $value)
            $rewritten[$key] = is_array($value)
                ? (array_is_list($value) ? array_map([$this, 'rewriteRules'], $value) : $this->rewriteRules($value))
                : $value;

        return $rewritten;
    }

    /**
     * If the operand is {"var": "<side>.zone.type"}, returns "<side>.zone.tags". Otherwise null.
     */
    private function zoneTagsPathFor($operand)
    {
        if(!is_array($operand) || !isset($operand['var']) || !is_string($operand['var']))
            return null;

        if(!str_ends_with($operand['var'], '.zone.type'))
            return null;

        return substr($operand['var'], 0, -strlen('.type')) . '.tags';
    }

    public function down()
    {
        // The type -> tag mapping is not reversible without guessing which tag was the type, and the
        // zone_type FK data is gone. Restore from a dump if this needs to be undone.
        throw new \RuntimeException('migrate_zone_types_to_tags cannot be reversed; restore from a backup.');
    }
}
