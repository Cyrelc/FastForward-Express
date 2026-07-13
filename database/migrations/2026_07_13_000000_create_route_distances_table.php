<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache of road distances between two points, so quoting a bill does not bill Google per keystroke.
 *
 * The estimate endpoint fires on a debounce every time someone edits a bill, and prices every delivery
 * type from the same request. Road distance depends on neither the delivery type nor anything else on
 * the bill - only the two coordinates - so it is looked up once per route and kept.
 *
 * Coordinates are stored rounded (see RouteDistanceService::CACHE_PRECISION): geocoding the same
 * doorway twice can differ in the last decimal places, and an exact-match cache would miss every time.
 */
return new class extends Migration {
    public function up() {
        Schema::create('route_distances', function(Blueprint $table) {
            $table->id('route_distance_id');

            // decimal(9,6) rather than float: a float cache key does not compare reliably.
            $table->decimal('pickup_lat', 9, 6);
            $table->decimal('pickup_lng', 9, 6);
            $table->decimal('delivery_lat', 9, 6);
            $table->decimal('delivery_lng', 9, 6);

            $table->decimal('distance_km', 8, 2);
            $table->decimal('drive_time_minutes', 8, 2)->nullable();

            $table->timestamps();

            $table->unique(
                ['pickup_lat', 'pickup_lng', 'delivery_lat', 'delivery_lng'],
                'route_distances_coordinates_unique'
            );
        });
    }

    public function down() {
        Schema::dropIfExists('route_distances');
    }
};
