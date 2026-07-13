<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Road distance and drive time between two points, via Google's Routes API.
 *
 * Distance sets a price, so this is deliberately server-side. The browser already holds a Google key
 * (MIX_APP_PLACES_API_KEY, compiled into the bundle) and could compute this itself - but a distance
 * posted by the client is a customer editing their own fuel surcharge, so it is never trusted.
 *
 * Failure returns null rather than an estimate. A guessed distance is indistinguishable from a real
 * one once it is on an invoice, and straight-line distance runs 20-40% short of road distance. Null
 * flows into the pricing context, and any rule that actually references it then refuses to price
 * (ResolveFieldValue) - so a sheet that uses distance fails closed, and a sheet that does not is
 * unaffected.
 */
class RouteDistanceService {
    /**
     * Decimals kept when caching coordinates. 4dp is ~11m at this latitude: fine enough that two
     * addresses never collide, coarse enough that re-geocoding the same doorway still hits the cache.
     */
    const CACHE_PRECISION = 4;

    const ENDPOINT = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    const TIMEOUT_SECONDS = 5;

    /**
     * @return array{km: float, minutes: float|null}|null - null when the distance cannot be established
     */
    public function Between($pickupLat, $pickupLng, $deliveryLat, $deliveryLng) {
        if(!is_numeric($pickupLat) || !is_numeric($pickupLng) || !is_numeric($deliveryLat) || !is_numeric($deliveryLng))
            return null;

        $key = [
            'pickup_lat' => round((float) $pickupLat, self::CACHE_PRECISION),
            'pickup_lng' => round((float) $pickupLng, self::CACHE_PRECISION),
            'delivery_lat' => round((float) $deliveryLat, self::CACHE_PRECISION),
            'delivery_lng' => round((float) $deliveryLng, self::CACHE_PRECISION),
        ];

        $cached = DB::table('route_distances')->where($key)->first();
        if($cached)
            return ['km' => (float) $cached->distance_km, 'minutes' => $cached->drive_time_minutes === null ? null : (float) $cached->drive_time_minutes];

        $fetched = $this->Fetch($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
        if($fetched === null)
            return null;

        // updateOrInsert, not insert: two quotes for the same route can race, and losing that race
        // must not throw away a good answer we already paid Google for.
        DB::table('route_distances')->updateOrInsert($key, [
            'distance_km' => $fetched['km'],
            'drive_time_minutes' => $fetched['minutes'],
            'updated_at' => now(),
            'created_at' => now(),
        ]);

        return $fetched;
    }

    /**
     * One Routes API call for the recommended route.
     *
     * Alternatives are not requested. They are, by definition, worse than the optimum, so averaging
     * them does not produce a more typical distance - it produces a systematically inflated one. If
     * the business wants padding for imperfect driving, that belongs in a rule where it is visible
     * and controlled, not buried in how the distance was measured.
     */
    private function Fetch($pickupLat, $pickupLng, $deliveryLat, $deliveryLng) {
        $apiKey = config('services.google.routes_api_key');

        if(!$apiKey) {
            Log::warning('RouteDistanceService: no google.routes_api_key configured; distance unavailable.');
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                    // Routes bills by the fields asked for, so ask for exactly two.
                    'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration',
                ])
                ->post(self::ENDPOINT, [
                    'origin' => ['location' => ['latLng' => ['latitude' => (float) $pickupLat, 'longitude' => (float) $pickupLng]]],
                    'destination' => ['location' => ['latLng' => ['latitude' => (float) $deliveryLat, 'longitude' => (float) $deliveryLng]]],
                    'travelMode' => 'DRIVE',
                    'units' => 'METRIC',
                ]);
        } catch(\Throwable $e) {
            Log::warning('RouteDistanceService: request failed - ' . $e->getMessage());
            return null;
        }

        if(!$response->successful()) {
            Log::warning('RouteDistanceService: Routes API returned ' . $response->status() . ' - ' . $response->body());
            return null;
        }

        $route = $response->json('routes.0');

        // A pair of coordinates with no drivable route between them (an island, a typo) comes back
        // 200 with an empty routes list. That is not a zero-kilometre trip.
        if(!$route || !isset($route['distanceMeters']))
            return null;

        return [
            'km' => round(((float) $route['distanceMeters']) / 1000, 2),
            'minutes' => $this->ParseDuration($route['duration'] ?? null),
        ];
    }

    /** Routes returns duration as a protobuf duration string, e.g. "1234s". */
    private function ParseDuration($duration) {
        if(!is_string($duration) || !preg_match('/^(\d+(?:\.\d+)?)s$/', $duration, $match))
            return null;

        return round(((float) $match[1]) / 60, 2);
    }
}
