<?php

namespace Tests\Unit\Ratesheets;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use DB;

use App\Http\Repos\ConditionalRepo;

class ConditionalRepoTest extends TestCase {
    use RefreshDatabase;

    private function makeRatesheetId() {
        return DB::table('ratesheets')->insertGetId([
            'delivery_types' => '[]', 'name' => 'RS',
            'time_rates' => '[]', 'use_internal_zones_calc' => false, 'weight_rates' => '[]', 'zone_rates' => '[]',
        ], 'ratesheet_id');
    }

    private function chargeTypeId($value) {
        return DB::table('selections')->where('type', 'charge_type')->where('value', $value)->value('selection_id');
    }

    public function testUpdatePersistsType() {
        $repo = new ConditionalRepo();
        $ratesheetId = $this->makeRatesheetId();
        $distanceRate = $this->chargeTypeId('distanceRate');
        $weightRate = $this->chargeTypeId('weightRate');

        $created = $repo->Insert([
            'action' => json_encode(['value' => 'add']),
            'continue_on' => true,
            'human_readable' => 'x',
            'json_logic' => json_encode(true),
            'name' => 'Rule',
            'priority' => 1,
            'ratesheet_id' => $ratesheetId,
            'type' => $distanceRate,
            'value' => '5',
            'value_type' => 'amount',
        ]);

        // Change the type and update.
        $repo->Update([
            'conditional_id' => $created->conditional_id,
            'action' => json_encode(['value' => 'add']),
            'continue_on' => true,
            'human_readable' => 'x',
            'json_logic' => json_encode(true),
            'name' => 'Rule',
            'priority' => 1,
            'type' => $weightRate,
            'value' => '5',
            'value_type' => 'amount',
        ]);

        $this->assertDatabaseHas('conditionals', [
            'conditional_id' => $created->conditional_id,
            'type' => $weightRate,
        ]);
    }
}
