<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use App\Http\Middleware\VerifyCsrfToken;
use DB;

use App\Models\User;

/**
 * Zones and zone groups save individually. Two things about that are easy to get wrong and expensive
 * to get wrong, so they are pinned here:
 *
 *   1. Adjacency. Saving one zone can make or break links on BOTH sides, and the recompute is scoped
 *      to the saved zone for speed - so the mirrored half of every link has to be maintained by hand.
 *      A stale neighbour list silently corrupts zone-crossing prices.
 *   2. Deletion. It used to be implicit ("anything absent from the payload is deleted"), which meant a
 *      partial payload could wipe a map. It is now an explicit call, and a saved zone must survive a
 *      save that does not mention it.
 */
class ZoneSaveTest extends TestCase {
    use RefreshDatabase;

    /**
     * APP_URL carries a /app path (the SPA is served from there) and Laravel's test client prepends it
     * to every relative URL - which would miss these routes, because the API is not mounted under
     * /app. Absolute URLs are passed through untouched, so the tests hit the same paths the browser
     * calls.
     */
    private const HOST = 'http://localhost';

    private $ratesheetId;

    protected function setUp(): void {
        parent::setUp();

        // phpunit.xml does not set APP_ENV=testing, so Laravel does not recognise that it is running
        // tests and VerifyCsrfToken stays active - every POST would come back 419. Disabled here
        // rather than in phpunit.xml so this change cannot alter how the rest of the suite runs.
        $this->withoutMiddleware(VerifyCsrfToken::class);

        Permission::findOrCreate('ratesheets.edit.*.*', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('ratesheets.edit.*.*');
        $this->actingAs($user);

        $this->ratesheetId = DB::table('ratesheets')->insertGetId([
            'delivery_types' => json_encode([
                ['id' => 'regular', 'name' => 'Regular', 'friendlyName' => 'Regular', 'cost' => 25, 'time' => 2],
            ]), 'name' => 'Zone Save Test', 'time_rates' => '[]',
            'use_internal_zones_calc' => false, 'weight_rates' => '[]', 'zone_rates' => '[]',
        ], 'ratesheet_id');
    }

    /** Squares in a row: (0,0)-(10,10), (10,0)-(20,10), ... so consecutive squares share an edge. */
    private function squareCoordinates($minLat, $size = 10) {
        return [
            ['lat' => $minLat, 'lng' => 0],
            ['lat' => $minLat, 'lng' => $size],
            ['lat' => $minLat + $size, 'lng' => $size],
            ['lat' => $minLat + $size, 'lng' => 0],
        ];
    }

    private function makeGroup($name = 'Core') {
        return DB::table('zone_groups')->insertGetId([
            'ratesheet_id' => $this->ratesheetId,
            'name' => $name,
            'colour' => '#8491c9',
            'zone_rates' => json_encode([]),
            'created_at' => now(), 'updated_at' => now(),
        ], 'zone_group_id');
    }

    private function saveZone($name, $minLat, $groupIds, $zoneId = null) {
        $url = self::HOST . "/ratesheets/{$this->ratesheetId}/zone" . ($zoneId ? "/{$zoneId}" : '');

        $response = $this->postJson($url, [
            'zone' => [
                'name' => $name,
                'colour' => null,
                'coordinates' => json_encode($this->squareCoordinates($minLat)),
                'deliveryTypeModifiers' => [],
                'groupIds' => $groupIds,
                'tags' => null,
                'zoneId' => $zoneId,
            ],
        ]);

        $response->assertOk();
        return $response->json('zone_id');
    }

    private function neighbours($zoneId) {
        $raw = DB::table('zones')->where('zone_id', $zoneId)->value('neighbours');
        return $raw ? json_decode($raw, true) : [];
    }

    public function testAdjacencyIsMirroredOnBothSidesWhenAZoneIsSaved() {
        $groupId = $this->makeGroup();

        $a = $this->saveZone('A', 0, [$groupId]);
        $b = $this->saveZone('B', 10, [$groupId]); // shares the y=10 edge with A

        // B's save must have added itself to A's list as well as building its own.
        $this->assertEquals([$b], $this->neighbours($a));
        $this->assertEquals([$a], $this->neighbours($b));
    }

    public function testMovingAZoneAwayClearsTheLinkOnBothSides() {
        $groupId = $this->makeGroup();

        $a = $this->saveZone('A', 0, [$groupId]);
        $b = $this->saveZone('B', 10, [$groupId]);
        $this->assertEquals([$b], $this->neighbours($a));

        // Move B so it no longer touches A. The stale link must be dropped from A too - if only B's
        // own row were recomputed, A would still claim B as a neighbour and the BFS would cross a gap.
        $this->saveZone('B', 50, [$groupId], $b);

        $this->assertEquals([], $this->neighbours($a));
        $this->assertEquals([], $this->neighbours($b));
    }

    public function testUngroupedZonesHoldNoAdjacency() {
        $groupId = $this->makeGroup();

        $a = $this->saveZone('A', 0, [$groupId]);
        $b = $this->saveZone('B', 10, []); // touches A geometrically, but is in no group

        // An ungrouped zone can never be traversed, so it is not a neighbour of anything.
        $this->assertEquals([], $this->neighbours($a));
        $this->assertEquals([], $this->neighbours($b));
    }

    public function testDeletingAZonePrunesItFromItsNeighbours() {
        $groupId = $this->makeGroup();

        $a = $this->saveZone('A', 0, [$groupId]);
        $b = $this->saveZone('B', 10, [$groupId]);
        $this->assertEquals([$b], $this->neighbours($a));

        $this->deleteJson(self::HOST . "/ratesheets/{$this->ratesheetId}/zone/{$b}")->assertOk();

        $this->assertDatabaseMissing('zones', ['zone_id' => $b]);
        // A must not be left pointing at a zone that no longer exists.
        $this->assertEquals([], $this->neighbours($a));
    }

    public function testSavingOneZoneDoesNotDeleteTheOthers() {
        $groupId = $this->makeGroup();

        $a = $this->saveZone('A', 0, [$groupId]);
        $b = $this->saveZone('B', 10, [$groupId]);

        // The old whole-ratesheet save deleted every zone absent from the payload. Saving A alone must
        // now leave B completely untouched.
        $this->saveZone('A renamed', 0, [$groupId], $a);

        $this->assertDatabaseHas('zones', ['zone_id' => $b, 'name' => 'B']);
        $this->assertDatabaseHas('zones', ['zone_id' => $a, 'name' => 'A renamed']);
    }

    public function testSavingAZoneTwiceUpdatesRatherThanDuplicating() {
        $groupId = $this->makeGroup();

        $a = $this->saveZone('A', 0, [$groupId]);
        // The client writes the returned id back, so the second save is an update. Without that, a
        // newly drawn zone would be inserted again every time it was saved.
        $again = $this->saveZone('A', 0, [$groupId], $a);

        $this->assertEquals($a, $again);
        $this->assertEquals(1, DB::table('zones')->where('ratesheet_id', $this->ratesheetId)->count());
    }
}
