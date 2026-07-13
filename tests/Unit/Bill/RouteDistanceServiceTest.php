<?php

namespace Tests\Unit\Bill;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use DB;

use App\Http\Services\RouteDistanceService;

/**
 * The Routes API is never actually called here - Http::fake stands in for it.
 *
 * The cache is the point of this class, not an optimisation: the quote endpoint fires on a debounce as
 * a bill is typed and prices every delivery type from one request, so an uncached lookup would bill
 * Google per keystroke. "Does not call twice" is therefore a correctness test, not a performance one.
 */
class RouteDistanceServiceTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        config(['services.google.routes_api_key' => 'test-key']);
    }

    private function fakeRoute($metres, $duration = '1440s') {
        Http::fake([
            'routes.googleapis.com/*' => Http::response([
                'routes' => [['distanceMeters' => $metres, 'duration' => $duration]],
            ], 200),
        ]);
    }

    public function testReturnsKilometresAndMinutesFromTheRecommendedRoute() {
        $this->fakeRoute(18234, '1440s');

        $route = (new RouteDistanceService())->Between(53.54, -113.49, 53.63, -113.63);

        $this->assertEquals(18.23, $route['km']);
        $this->assertEquals(24, $route['minutes']);
    }

    public function testCachesTheLookupSoTheSecondQuoteCostsNothing() {
        $this->fakeRoute(18234);

        $service = new RouteDistanceService();
        $first = $service->Between(53.54, -113.49, 53.63, -113.63);
        $second = $service->Between(53.54, -113.49, 53.63, -113.63);

        $this->assertEquals($first, $second);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('route_distances', 1);
    }

    /**
     * Re-geocoding the same doorway can wobble in the far decimal places. An exact-match cache would
     * miss on every one of those and pay Google again, so coordinates are rounded to ~11m.
     */
    public function testNearIdenticalCoordinatesHitTheSameCacheRow() {
        $this->fakeRoute(18234);

        $service = new RouteDistanceService();
        $service->Between(53.540001, -113.490002, 53.630001, -113.630003);
        $service->Between(53.540004, -113.490001, 53.630002, -113.630001);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('route_distances', 1);
    }

    public function testReturnsNullWhenGoogleFails() {
        Http::fake(['routes.googleapis.com/*' => Http::response('upstream exploded', 500)]);

        $this->assertNull((new RouteDistanceService())->Between(53.54, -113.49, 53.63, -113.63));
        $this->assertDatabaseCount('route_distances', 0);
    }

    /**
     * Two points with no drivable route between them come back 200 with an empty routes list. That is
     * not a zero-kilometre trip, and caching it as one would price every such bill at $0 forever.
     */
    public function testReturnsNullWhenNoRouteExists() {
        Http::fake(['routes.googleapis.com/*' => Http::response(['routes' => []], 200)]);

        $this->assertNull((new RouteDistanceService())->Between(53.54, -113.49, 0.0, 0.0));
        $this->assertDatabaseCount('route_distances', 0);
    }

    /** No key configured must not throw, and must not invent a distance. */
    public function testReturnsNullWithoutAnApiKey() {
        config(['services.google.routes_api_key' => null]);
        Http::fake();

        $this->assertNull((new RouteDistanceService())->Between(53.54, -113.49, 53.63, -113.63));
        Http::assertNothingSent();
    }
}
