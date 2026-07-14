<?php

namespace Tests\Unit\Bill;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DB;

use App\Http\Models\Bill\ChargeModelFactory;
use App\Models\User;

/**
 * Pricing and timing tests for ChargeModelFactory.
 *
 * Zone type is gone: zones carry tags, and zone groups own zone crossing. These cover both the
 * behaviour that had to survive that change (outlying surcharges, convenience flags, the St Albert
 * base-selection scenarios) and the behaviour it introduced (group-masked crossing, cheapest-of-
 * groups, first-set-wins, refusing to price an unresolvable crossing).
 *
 * Fixtures use simple non-overlapping square zones so point-in-polygon resolution is trivial:
 *   - zone at minLat 0  -> test point (5,5)
 *   - zone at minLat 20 -> test point (25,5)
 *   - zone at minLat 40 -> test point (45,5)
 */
class ChargeModelFactoryTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // RatesheetRepo's constructor resolves Auth::user(); a plain user (no accountUsers) is enough.
        $this->actingAs(User::factory()->create());
    }

    private function squareCoordinates($minLat, $minLng, $size = 10) {
        return json_encode([
            ['lat' => $minLat, 'lng' => $minLng],
            ['lat' => $minLat, 'lng' => $minLng + $size],
            ['lat' => $minLat + $size, 'lng' => $minLng + $size],
            ['lat' => $minLat + $size, 'lng' => $minLng],
        ]);
    }

    private function makeRatesheetRow($name, $deliveryTypes) {
        return DB::table('ratesheets')->insertGetId([
            'delivery_types' => json_encode($deliveryTypes),
            'name' => $name,
            'time_rates' => '[]',
            'use_internal_zones_calc' => false,
            'weight_rates' => '[]',
            'zone_rates' => '[]',
        ], 'ratesheet_id');
    }

    /**
     * @param $tags - pipe-joined, e.g. 'outlying' or 'home|hub'
     * @param $neighbours - zone_ids this zone physically touches (adjacency is pure geometry now)
     */
    private function makeZone($ratesheetId, $name, $tags, $minLat, $cost = null, $time = null, $neighbours = []) {
        $modifiers = ($cost === null && $time === null)
            ? []
            : ['Regular' => ['cost' => $cost, 'time' => $time]];

        return DB::table('zones')->insertGetId([
            'coordinates' => $this->squareCoordinates($minLat, 0),
            'colour' => null,
            'delivery_type_modifiers' => json_encode($modifiers),
            'name' => $name,
            'neighbours' => json_encode($neighbours),
            'ratesheet_id' => $ratesheetId,
            'tags' => $tags,
        ], 'zone_id');
    }

    private function setNeighbours($zoneId, $neighbours) {
        DB::table('zones')->where('zone_id', $zoneId)->update(['neighbours' => json_encode($neighbours)]);
    }

    private function makeZoneGroup($ratesheetId, $name, $zoneIds, $zoneRates) {
        $zoneGroupId = DB::table('zone_groups')->insertGetId([
            'ratesheet_id' => $ratesheetId,
            'name' => $name,
            'colour' => '#8491c9',
            'zone_rates' => json_encode($zoneRates),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'zone_group_id');

        foreach($zoneIds as $zoneId)
            DB::table('zone_group_zone')->insert([
                'zone_group_id' => $zoneGroupId,
                'zone_id' => $zoneId,
            ]);

        return $zoneGroupId;
    }

    /**
     * Ratesheet with one 'internal' zone and one 'outlying' zone (which carries a Regular modifier
     * cost of $outlyingCost). No groups: nothing here can cross.
     */
    private function makeRatesheet($outlyingCost = 10, $baseCost = 25) {
        $ratesheetId = $this->makeRatesheetRow('Test Ratesheet', [
            ['id' => 'regular', 'name' => 'Regular', 'friendlyName' => 'Regular', 'cost' => $baseCost, 'time' => 2],
        ]);

        return [
            'ratesheetId' => $ratesheetId,
            'internalId' => $this->makeZone($ratesheetId, 'Central', 'internal', 0),
            'outlyingId' => $this->makeZone($ratesheetId, 'Faraway', 'outlying', 20, $outlyingCost, 1),
        ];
    }

    /**
     * Builds a GenerateCharges request. package_is_minimum=true skips package/weight charges so we
     * isolate the distance and conditional lines.
     */
    private function chargeRequest($ratesheetId, $pickupPoint, $deliveryPoint) {
        return new Request([
            'ratesheet_id' => $ratesheetId,
            'delivery_type_id' => 'regular',
            'package_is_minimum' => true,
            'package_is_pallet' => false,
            'use_imperial' => false,
            'packages' => [],
            'pickup_address' => ['is_mall' => false, 'lat' => $pickupPoint[0], 'lng' => $pickupPoint[1]],
            'delivery_address' => ['is_mall' => false, 'lat' => $deliveryPoint[0], 'lng' => $deliveryPoint[1]],
            'time_pickup_scheduled' => '2026-07-10T09:00:00',
            'time_delivery_scheduled' => '2026-07-10T11:00:00',
        ]);
    }

    /** As chargeRequest, but carrying real weight (kg) - is_minimum would zero it. */
    private function weightedRequest($ratesheetId, $pickupPoint, $deliveryPoint, $kilograms) {
        $request = $this->chargeRequest($ratesheetId, $pickupPoint, $deliveryPoint);
        $request->merge([
            'package_is_minimum' => false,
            'packages' => [['count' => 1, 'totalWeight' => $kilograms, 'length' => 10, 'width' => 10, 'height' => 10]],
        ]);

        return $request;
    }

    private function totalPrice($lineItems) {
        return array_reduce($lineItems, fn($carry, $item) => $carry + $item->getPrice(), 0);
    }

    private function insertConditional($ratesheetId, $overrides = []) {
        DB::table('conditionals')->insert(array_merge([
            'action' => json_encode(['value' => 'add']),
            'continue_on' => true,
            'human_readable' => 'test rule',
            'json_logic' => json_encode(true),
            'name' => 'Test Conditional',
            'original_equation_string' => null,
            'priority' => null,
            'ratesheet_id' => $ratesheetId,
            'type' => null,
            'value' => '0',
            'value_type' => 'amount',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function insertTimingConditional($ratesheetId, $overrides = []) {
        DB::table('timing_conditionals')->insert(array_merge([
            'action' => json_encode(['value' => 'add']),
            'continue_on' => true,
            'human_readable' => 'test timing rule',
            'json_logic' => json_encode(true),
            'name' => 'Test Timing Conditional',
            'priority' => null,
            'ratesheet_id' => $ratesheetId,
            'value' => '0',
            'value_type' => 'hours',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /** "this zone has tag X" - the shape the types->tags migration rewrites rules into. */
    private function hasTag($side, $tag) {
        return ['in' => [$tag, ['var' => $side . '_address.zone.tags']]];
    }

    /**
     * "this zone has tag X" as the query builder emits it for a multiselect: CONTAINS -> "some".
     *
     * Distinct from hasTag's "in" on purpose. "in" against a bare string falls back to substring
     * matching, so it keeps working even when tags reach a rule as the raw pipe-delimited column
     * instead of a list - which is exactly how a broken context passed the tests while every rule
     * authored in the UI silently matched nothing. "some" has no such fallback: it is the shape that
     * actually proves tags arrive as a list.
     */
    private function hasTagViaSome($side, $tag) {
        return ['some' => [
            ['var' => $side . '_address.zone.tags'],
            ['in' => [['var' => ''], [$tag]]],
        ]];
    }

    private function lacksTag($side, $tag) {
        return ['!' => $this->hasTag($side, $tag)];
    }

    /** The two backfilled surcharge rules: add each non-internal side's per-type modifier cost. */
    private function addOutlyingSurchargeConditionals($ratesheetId) {
        $this->insertConditional($ratesheetId, [
            'name' => 'Pickup Zone Surcharge', 'priority' => 100,
            'json_logic' => json_encode($this->lacksTag('pickup', 'internal')),
            'action' => json_encode(['value' => 'add']),
            'value_type' => 'field', 'value' => 'pickup_zone_selected_type_cost',
        ]);
        $this->insertConditional($ratesheetId, [
            'name' => 'Delivery Zone Surcharge', 'priority' => 100,
            'json_logic' => json_encode($this->lacksTag('delivery', 'internal')),
            'action' => json_encode(['value' => 'add']),
            'value_type' => 'field', 'value' => 'delivery_zone_selected_type_cost',
        ]);
    }

    // --- Base + surcharge behaviour that had to survive the type -> tag change ---

    public function testInternalToInternalChargesBaseOnly() {
        $fixture = $this->makeRatesheet(10, 25);

        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [5, 5])
        );

        $this->assertEquals(25, $this->totalPrice($charges));
    }

    public function testInternalToOutlyingAddsModifierSurchargeViaConditional() {
        $fixture = $this->makeRatesheet(10, 25);
        $this->addOutlyingSurchargeConditionals($fixture['ratesheetId']);

        // Flat base (25) + the outlying side's Regular modifier (10), applied by the tag-gated rule.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [25, 5])
        );

        $this->assertEquals(35, $this->totalPrice($charges));
    }

    /**
     * The same surcharge, gated by a rule in the shape the UI actually saves (CONTAINS -> "some").
     *
     * Guards the pricing context's zone tags being a list rather than the raw 'a|b' column. "some"
     * iterates nothing over a scalar, so a string here silently prices every outlying delivery at
     * base - no error, just a missing surcharge.
     */
    public function testInternalToOutlyingAddsSurchargeWhenTagRuleUsesContains() {
        $fixture = $this->makeRatesheet(10, 25);
        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Outlying Zone Surcharge - Delivery', 'priority' => 100,
            'json_logic' => json_encode($this->hasTagViaSome('delivery', 'outlying')),
            'action' => json_encode(['value' => 'add']),
            'value_type' => 'field', 'value' => 'delivery_zone_selected_type_cost',
        ]);

        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [25, 5])
        );

        $this->assertEquals(35, $this->totalPrice($charges));
    }

    /** LineItemModel keeps its name private; the invoice reads it back through jsonSerialize. */
    private function lineItemNames($lineItems) {
        return array_map(fn($item) => json_decode(json_encode($item), true)['name'], $lineItems);
    }

    /**
     * The imperial toggle must not change the weight the engine prices.
     *
     * The client converts to kilograms before sending (usePackages -> poundsToKilograms), and that
     * converted value is what gets stored on the bill. The engine used to convert a SECOND time when
     * use_imperial was set, treating those kilograms as pounds - so a 100 lb parcel was priced as 45 lb
     * and every weight rule undercharged by 2.2x, for imperial users only, with nothing to show for it.
     */
    public function testImperialToggleDoesNotChangeTheWeightPriced() {
        $fixture = $this->makeRatesheet(10, 25);
        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Over 50 lb', 'priority' => 1,
            'json_logic' => json_encode(['>' => [['var' => 'package.total_weight_lbs'], 50]]),
            'value_type' => 'amount', 'value' => '8',
        ]);

        $factory = new ChargeModelFactory();

        // 45.36 kg is 100 lb - what the client sends for a 100 lb parcel, in either unit mode.
        foreach([true, false] as $useImperial) {
            $request = $this->weightedRequest($fixture['ratesheetId'], [5, 5], [25, 5], 45.36);
            $request->merge(['use_imperial' => $useImperial]);

            $charges = $factory->GenerateCharges($request);

            $this->assertEquals(33, $this->totalPrice($charges),
                'A 100 lb parcel must price the same with use_imperial=' . var_export($useImperial, true));
        }
    }

    /**
     * The pricing and timing breakdowns are the same structure, and must stay that way: the same UI
     * renders both, and an author troubleshooting a rule should not have to learn two vocabularies for
     * "this one didn't fire".
     */
    public function testPricingAndTimingBreakdownsShareTheSameShape() {
        $fixture = $this->makeRatesheet(10, 25);

        // One rule that fires, one that does not - so every row type appears.
        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Fires', 'priority' => 1,
            'json_logic' => json_encode(['==' => [1, 1]]),
            'value_type' => 'amount', 'value' => '5',
        ]);
        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Does not fire', 'priority' => 2,
            'json_logic' => json_encode(['==' => [1, 2]]),
            'value_type' => 'amount', 'value' => '7',
        ]);
        $this->insertTimingConditional($fixture['ratesheetId'], [
            'name' => 'Timing fires', 'priority' => 1,
            'json_logic' => json_encode(['==' => [1, 1]]),
            'value_type' => 'hours', 'value' => '2',
        ]);
        $this->insertTimingConditional($fixture['ratesheetId'], [
            'name' => 'Timing does not fire', 'priority' => 2,
            'json_logic' => json_encode(['==' => [1, 2]]),
            'value_type' => 'hours', 'value' => '3',
        ]);

        $request = $this->chargeRequest($fixture['ratesheetId'], [5, 5], [25, 5]);
        $quotes = (new ChargeModelFactory())->EstimateDeliveryQuotes($request);
        $quote = $quotes[0];

        $keys = ['name', 'action', 'value', 'running_total', 'note'];

        foreach(['price_breakdown', 'time_breakdown'] as $breakdownKey) {
            $this->assertNotEmpty($quote[$breakdownKey], "$breakdownKey should not be empty");

            foreach($quote[$breakdownKey] as $row)
                foreach($keys as $key)
                    $this->assertArrayHasKey($key, $row, "$breakdownKey rows must carry '$key'");

            $actions = array_column($quote[$breakdownKey], 'action');
            $this->assertContains('base', $actions, "$breakdownKey should open with the base row");
            $this->assertContains('not_matched', $actions, "$breakdownKey must report rules that did NOT fire");
        }
    }

    /** "matched_rules CONTAINS [id]" - the shape the query builder emits for a multiselect. */
    private function ruleMatched($conditionalId) {
        return ['some' => [
            ['var' => 'matched_rules'],
            ['in' => [['var' => ''], [$conditionalId]]],
        ]];
    }

    private function ruleNotMatched($conditionalId) {
        return ['!' => $this->ruleMatched($conditionalId)];
    }

    private function insertConditionalReturningId($ratesheetId, $overrides = []) {
        $this->insertConditional($ratesheetId, $overrides);
        return DB::table('conditionals')->max('conditional_id');
    }

    /**
     * The chained-bracket case: rule B says "not rule A, and under 99 lb".
     *
     * A 30 lb parcel matches A, so B must not fire. This is the whole point of the feature - B never
     * restates A's condition, so moving A's boundary moves B's with it.
     */
    public function testARuleCanBeGatedOnAnotherRuleNotMatching() {
        $fixture = $this->makeRatesheet(10, 25);

        $under50 = $this->insertConditionalReturningId($fixture['ratesheetId'], [
            'name' => 'Under 50 lb', 'priority' => 10,
            'json_logic' => json_encode(['<' => [['var' => 'package.total_weight_lbs'], 50]]),
            'value_type' => 'amount', 'value' => '5',
        ]);

        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Under 99 lb, but not already caught', 'priority' => 20,
            'json_logic' => json_encode(['and' => [
                $this->ruleNotMatched($under50),
                ['<' => [['var' => 'package.total_weight_lbs'], 99]],
            ]]),
            'value_type' => 'amount', 'value' => '9',
        ]);

        // ~30 lb: matches "Under 50", so the second bracket must stay out of it. 25 base + 5.
        $light = (new ChargeModelFactory())->GenerateCharges(
            $this->weightedRequest($fixture['ratesheetId'], [5, 5], [25, 5], 13.6)
        );
        $this->assertEquals(30, $this->totalPrice($light));

        // ~150 lb: over both brackets, neither fires. Base only.
        $heavy = (new ChargeModelFactory())->GenerateCharges(
            $this->weightedRequest($fixture['ratesheetId'], [5, 5], [25, 5], 68)
        );
        $this->assertEquals(25, $this->totalPrice($heavy));

        // ~70 lb: over 50 so "Under 50" does not match, which is what lets the second bracket in.
        $middle = (new ChargeModelFactory())->GenerateCharges(
            $this->weightedRequest($fixture['ratesheetId'], [5, 5], [25, 5], 31.8)
        );
        $this->assertEquals(34, $this->totalPrice($middle));
    }

    /**
     * Rules are resolved in dependency order, so a rule may reference one that is authored later and
     * runs later. Priority is about pricing order and has nothing to do with references.
     */
    public function testReferenceWorksRegardlessOfPriorityOrder() {
        $fixture = $this->makeRatesheet(10, 25);

        // The referenced rule has the HIGHER priority number, so it is evaluated last by the pricing
        // loop - but its condition is still resolved first, because the other rule depends on it.
        $late = $this->insertConditionalReturningId($fixture['ratesheetId'], [
            'name' => 'Runs last', 'priority' => 99,
            'json_logic' => json_encode(['==' => [1, 1]]),
            'value_type' => 'amount', 'value' => '1',
        ]);

        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Runs first, but asks about the last one', 'priority' => 1,
            'json_logic' => json_encode($this->ruleMatched($late)),
            'value_type' => 'amount', 'value' => '4',
        ]);

        // 25 base + 4 (reference matched) + 1 = 30.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [25, 5])
        );
        $this->assertEquals(30, $this->totalPrice($charges));
    }

    /**
     * "Matched" means the condition was true, NOT that a charge was produced.
     *
     * A Set Base Price rule that loses first-set-wins still counts as matched. Defining it the other
     * way would make a reference depend on evaluation order, which is exactly what this design avoids.
     */
    public function testASkippedSetRuleStillCountsAsMatched() {
        $fixture = $this->makeRatesheet(10, 25);

        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Winning base', 'priority' => 1,
            'json_logic' => json_encode(['==' => [1, 1]]),
            'action' => json_encode(['value' => 'set']),
            'value_type' => 'amount', 'value' => '40',
        ]);

        // Matches, but is skipped: 'Winning base' already took the base.
        $skipped = $this->insertConditionalReturningId($fixture['ratesheetId'], [
            'name' => 'Losing base', 'priority' => 2,
            'json_logic' => json_encode(['==' => [1, 1]]),
            'action' => json_encode(['value' => 'set']),
            'value_type' => 'amount', 'value' => '99',
        ]);

        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Asks about the skipped rule', 'priority' => 3,
            'json_logic' => json_encode($this->ruleMatched($skipped)),
            'value_type' => 'amount', 'value' => '3',
        ]);

        // 40 (winning base, flat 25 suppressed) + 3, because the skipped rule still MATCHED.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [25, 5])
        );
        $this->assertEquals(43, $this->totalPrice($charges));
    }

    /** A circular reference has no answer; refuse the quote rather than looping forever. */
    public function testCircularReferencesRefuseToPrice() {
        $fixture = $this->makeRatesheet(10, 25);

        $a = $this->insertConditionalReturningId($fixture['ratesheetId'], [
            'name' => 'A', 'priority' => 1, 'json_logic' => json_encode(['==' => [1, 1]]),
            'value_type' => 'amount', 'value' => '1',
        ]);
        $b = $this->insertConditionalReturningId($fixture['ratesheetId'], [
            'name' => 'B', 'priority' => 2, 'json_logic' => json_encode($this->ruleMatched($a)),
            'value_type' => 'amount', 'value' => '1',
        ]);
        // Close the loop behind the controller's back, as bad data would.
        DB::table('conditionals')->where('conditional_id', $a)
            ->update(['json_logic' => json_encode($this->ruleMatched($b))]);

        $this->expectException(HttpException::class);
        (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [25, 5])
        );
    }

    /**
     * Road distance reaches a pricing rule, so a fuel surcharge can be charged per kilometre.
     *
     * Distance is resolved once per route, in mockBill - not in BuildPricingContext, which runs once
     * per delivery type. A quote prices four types from one request, so resolving it there would mean
     * four identical Google lookups per keystroke.
     */
    public function testDistanceReachesAPricingRule() {
        config(['services.google.routes_api_key' => 'test-key']);
        Http::fake(['routes.googleapis.com/*' => Http::response([
            'routes' => [['distanceMeters' => 20000, 'duration' => '1200s']],
        ], 200)]);

        $fixture = $this->makeRatesheet(10, 25);
        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Fuel Surcharge ({{distance_km}} km)', 'priority' => 200,
            'json_logic' => json_encode(['>' => [['var' => 'distance_km'], 5]]),
            'action' => json_encode(['value' => 'add']),
            'value_type' => 'equation', 'value' => 0,
            'original_equation_string' => 'distance_km * 0.5',
        ]);

        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [5, 6])
        );

        // Flat base 25 + 20 km * $0.50 = $35.
        $this->assertEquals(35, $this->totalPrice($charges));
        $this->assertContains('Fuel Surcharge (20 km)', $this->lineItemNames($charges));
    }

    /**
     * Google unreachable, on a sheet that prices by distance: refuse, do not guess.
     *
     * Straight-line distance runs 20-40% short of road distance and is indistinguishable from a real
     * figure once it is on an invoice, so the engine must not substitute one. Equally, a null must not
     * reach the equation as 0 - "$0.50 * 0 km" is a surcharge that silently disappears.
     */
    public function testRefusesToPriceWhenDistanceIsUnavailableButARuleNeedsIt() {
        config(['services.google.routes_api_key' => 'test-key']);
        Http::fake(['routes.googleapis.com/*' => Http::response('down', 500)]);

        $fixture = $this->makeRatesheet(10, 25);
        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Fuel Surcharge', 'priority' => 200,
            'json_logic' => json_encode(['==' => [1, 1]]),
            'action' => json_encode(['value' => 'add']),
            'value_type' => 'equation', 'value' => 0,
            'original_equation_string' => 'distance_km * 0.5',
        ]);

        $this->expectException(HttpException::class);
        (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [5, 6])
        );
    }

    /** A sheet that never mentions distance prices normally even with Google unreachable. */
    public function testDistanceOutageDoesNotAffectSheetsThatDoNotUseIt() {
        config(['services.google.routes_api_key' => 'test-key']);
        Http::fake(['routes.googleapis.com/*' => Http::response('down', 500)]);

        $fixture = $this->makeRatesheet(10, 25);

        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [5, 6])
        );

        $this->assertEquals(25, $this->totalPrice($charges));
    }

    /**
     * An unresolvable placeholder must degrade, not leak braces onto a customer's invoice. The rule
     * still fires and still charges - interpolation is cosmetic - so the label just loses the value.
     */
    public function testLineItemNameDropsUnresolvablePlaceholderRatherThanShowingBraces() {
        $fixture = $this->makeRatesheet(10, 25);
        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Surcharge for {{crossing_group}} delivery', 'priority' => 100,
            'json_logic' => json_encode($this->hasTagViaSome('delivery', 'outlying')),
            'action' => json_encode(['value' => 'add']),
            'value_type' => 'field', 'value' => 'delivery_zone_selected_type_cost',
        ]);

        // These zones share no group at all, so crossing_group resolves to null.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [25, 5])
        );

        $names = $this->lineItemNames($charges);
        $this->assertContains('Surcharge for delivery', $names);
        foreach($names as $name)
            $this->assertStringNotContainsString('{{', $name);

        // The surcharge still applied: naming is cosmetic and must never move the price.
        $this->assertEquals(35, $this->totalPrice($charges));
    }

    public function testOutlyingToInternalAddsModifierSurchargeViaConditional() {
        $fixture = $this->makeRatesheet(10, 25);
        $this->addOutlyingSurchargeConditionals($fixture['ratesheetId']);

        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [25, 5], [5, 5])
        );

        $this->assertEquals(35, $this->totalPrice($charges));
    }

    public function testLegacyChargeActionBehavesAsAdd() {
        $fixture = $this->makeRatesheet(10, 25);
        $this->insertConditional($fixture['ratesheetId'], [
            'name' => 'Legacy charge', 'value_type' => 'amount', 'value' => '7',
            'action' => json_encode(['value' => 'charge']),
            'json_logic' => json_encode($this->lacksTag('delivery', 'internal')),
        ]);

        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fixture['ratesheetId'], [5, 5], [25, 5])
        );
        $this->assertEquals(32, $this->totalPrice($charges));
    }

    // --- Weight units ---

    /**
     * Weight is stored in kilograms. Pounds are exposed as a second field so a rule can be written in
     * whichever unit its author thinks in - crucially without converting anything on save, which could
     * not be done safely for an equation ("total_weight_kgs * 0.5" cannot be reinterpreted in pounds
     * without rewriting the author's coefficient).
     *
     * A request carrying use_imperial sends pounds and the engine stores kilograms, so these drive the
     * request in pounds and check that both fields describe the same parcel.
     */
    /**
     * A request carrying $weight in the author's unit, shaped the way the CLIENT actually sends it.
     *
     * That last part is the whole point. usePackages converts pounds to kilograms in the browser, so
     * packages[].totalWeight is always metric no matter what the toggle says. This helper used to put
     * raw pounds in totalWeight and let the engine convert - a payload the frontend never produces -
     * which is how it managed to agree with an engine that was double-converting and undercharging
     * every imperial user by 2.2x. A fixture that does not match the real request cannot catch that.
     */
    private function weightRequest($ratesheetId, $weight, $useImperial, $isMinimum = false) {
        // round(.., 2) mirrors usePackages' totalWeight.toFixed(2): the client stores kilograms to two
        // decimals, and that rounding is load-bearing. Without it the lb -> kg -> lb round trip lands a
        // 100 lb parcel on 99.999999, which fails a ">= 100 lb" bracket.
        $kilograms = $useImperial ? round($weight * 0.45359237, 2) : $weight;

        return new Request([
            'ratesheet_id' => $ratesheetId,
            'delivery_type_id' => 'regular',
            'package_is_minimum' => $isMinimum,
            'package_is_pallet' => false,
            'use_imperial' => $useImperial,
            'packages' => [['weight' => $weight, 'totalWeight' => $kilograms, 'count' => 1, 'length' => 1, 'width' => 1, 'height' => 1]],
            'pickup_address' => ['is_mall' => false, 'lat' => 5, 'lng' => 5],
            'delivery_address' => ['is_mall' => false, 'lat' => 5, 'lng' => 5],
            'time_pickup_scheduled' => '2026-07-10T09:00:00',
            'time_delivery_scheduled' => '2026-07-10T11:00:00',
        ]);
    }

    /** One zone, and a weight bracket that charges nothing, so only the rule under test contributes. */
    private function makeWeightRatesheet() {
        $ratesheetId = $this->makeRatesheetRow('Weights', [
            ['id' => 'regular', 'name' => 'Regular', 'friendlyName' => 'Regular', 'cost' => 25, 'time' => 2],
        ]);

        DB::table('ratesheets')->where('ratesheet_id', $ratesheetId)->update([
            'weight_rates' => json_encode([
                ['name' => 'Basic Weight Rate', 'brackets' => [['kgmax' => 10000, 'basePrice' => 0, 'additionalXKgs' => 0, 'incrementalPrice' => 0]]],
                ['name' => 'Pallet Weight Rate', 'brackets' => [['kgmax' => 10000, 'basePrice' => 0, 'additionalXKgs' => 0, 'incrementalPrice' => 0]]],
            ]),
        ]);

        $this->makeZone($ratesheetId, 'Central', 'internal', 0);

        return $ratesheetId;
    }

    public function testPoundsAndKilogramConditionsAgreeOnTheSameParcel() {
        $ratesheetId = $this->makeWeightRatesheet();

        // 100 lb is 45.36 kg. The two rules describe the same threshold in different units, so both
        // must fire on the same parcel - one reading the stored kilograms, the other the derived pounds.
        $this->insertConditional($ratesheetId, [
            'name' => 'Heavy (kg)', 'value_type' => 'amount', 'value' => '10',
            'action' => json_encode(['value' => 'add']),
            'json_logic' => json_encode(['>' => [['var' => 'package.total_weight'], 45]]),
        ]);
        $this->insertConditional($ratesheetId, [
            'name' => 'Heavy (lbs)', 'value_type' => 'amount', 'value' => '10',
            'action' => json_encode(['value' => 'add']),
            'json_logic' => json_encode(['>' => [['var' => 'package.total_weight_lbs'], 99]]),
        ]);

        $charges = (new ChargeModelFactory())->GenerateCharges($this->weightRequest($ratesheetId, 100, true));

        // base 25, plus both rules at 10
        $this->assertEquals(45, $this->totalPrice($charges));
    }

    public function testEquationCanBeAuthoredInPounds() {
        $ratesheetId = $this->makeWeightRatesheet();

        // 50c per pound. Writing this in kilograms would have meant the author converting the rate
        // themselves ($1.10 per kg) - exactly the friction the second unit removes.
        $this->insertConditional($ratesheetId, [
            'name' => 'Per pound', 'value_type' => 'equation',
            'value' => '0', 'original_equation_string' => 'total_weight_lbs * 0.5',
            'action' => json_encode(['value' => 'add']),
            'json_logic' => json_encode(true),
        ]);

        $charges = (new ChargeModelFactory())->GenerateCharges($this->weightRequest($ratesheetId, 100, true));

        // base 25, plus 100 lb at 50c
        $this->assertEquals(75, round($this->totalPrice($charges), 2));
    }

    /**
     * The St Albert weight bracket, written the way the ratesheet always meant it: 100-999 lb costs a
     * $16 base plus $2 for every WHOLE 100 lb above the bracket floor.
     *
     * floor() is what makes it a step rather than a slope - without it a 150 lb parcel is charged half
     * an increment. Pinned here because the equation is evaluated by Matex on the server and mathjs in
     * the editor's preview, and the two have to agree.
     *
     * Note the bracket lands exactly on the next bracket's base ($32 at 999 lb), which is how the rate
     * table was designed - and is not what the engine does today (it charges a flat $16 across the
     * whole bracket, ignoring the increment entirely).
     */
    public function testWeightBracketIncrementChargesPerWholeStep() {
        $ratesheetId = $this->makeWeightRatesheet();

        $this->insertConditional($ratesheetId, [
            'name' => 'Basic Weight 100-999lb', 'value_type' => 'equation',
            'value' => '0',
            'original_equation_string' => 'floor((total_weight_lbs - 100) / 100) * 2 + 16',
            'action' => json_encode(['value' => 'add']),
            'json_logic' => json_encode(['and' => [
                ['==' => [['var' => 'package.is_pallet'], false]],
                ['>=' => [['var' => 'package.total_weight_lbs'], 100]],
                ['<' => [['var' => 'package.total_weight_lbs'], 1000]],
            ]]),
        ]);

        $factory = new ChargeModelFactory();
        $priceAt = function($lbs) use ($factory, $ratesheetId) {
            $charges = $factory->GenerateCharges($this->weightRequest($ratesheetId, $lbs, true));
            return round($this->totalPrice($charges), 2);
        };

        // base 25 + the weight charge
        $this->assertEquals(25 + 16, $priceAt(100), 'at the floor: base only, no increments');
        $this->assertEquals(25 + 16, $priceAt(199), 'a part-increment must not be charged');
        $this->assertEquals(25 + 18, $priceAt(200), 'one whole increment');
        $this->assertEquals(25 + 32, $priceAt(999), 'meets the next bracket base exactly');
    }

    public function testPackageIsMinimumIsAvailableAsACondition() {
        $ratesheetId = $this->makeWeightRatesheet();

        $this->insertConditional($ratesheetId, [
            'name' => 'Minimum surcharge', 'value_type' => 'amount', 'value' => '7',
            'action' => json_encode(['value' => 'add']),
            'json_logic' => json_encode(['==' => [['var' => 'package.is_minimum'], true]]),
        ]);
        $factory = new ChargeModelFactory();

        $notMinimum = $factory->GenerateCharges($this->weightRequest($ratesheetId, 10, false, false));
        $this->assertEquals(25, $this->totalPrice($notMinimum));

        $minimum = $factory->GenerateCharges($this->weightRequest($ratesheetId, 10, false, true));
        $this->assertEquals(32, $this->totalPrice($minimum));
    }

    // --- Refusals ---

    /**
     * A refusal ("we cannot carry this - call us") produces no charge at all: it stops the quote and
     * shows its message, the same way an address outside every zone does.
     *
     * The engine used to hardcode exactly this for weight ("Weight exceeds available limits ... Please
     * price manually"), which went away with generatePackageCharges. This puts it back as something a
     * ratesheet can author for itself - and for anything, not just weight.
     */
    private function insertRefusal($ratesheetId, $message, $jsonLogic, $priority = 0) {
        $this->insertConditional($ratesheetId, [
            'name' => 'Over capacity',
            'action' => json_encode(['value' => 'refuse', 'label' => 'Refuse to Price']),
            'value_type' => 'message',
            'value' => $message,
            'priority' => $priority,
            'type' => null,
            'json_logic' => json_encode($jsonLogic),
        ]);
    }

    public function testRefusalStopsThePriceAndCarriesItsMessage() {
        $ratesheetId = $this->makeWeightRatesheet();

        $this->insertRefusal($ratesheetId, 'Exceeds trailer capacity. Please call us.', [
            '>=' => [['var' => 'package.total_weight_lbs'], 10000],
        ]);

        $factory = new ChargeModelFactory();

        // Under the limit: prices as normal.
        $this->assertEquals(25, $this->totalPrice($factory->GenerateCharges($this->weightRequest($ratesheetId, 9000, true))));

        // Over it: no price at all, and the author's message comes back.
        try {
            $factory->GenerateCharges($this->weightRequest($ratesheetId, 12000, true));
            $this->fail('A parcel over the refusal threshold should not have been priced');
        } catch (HttpException $e) {
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('Exceeds trailer capacity. Please call us.', $e->getMessage());
        }
    }

    public function testRefusalWinsRegardlessOfPriority() {
        $ratesheetId = $this->makeWeightRatesheet();

        // Priority 999 - last in the pricing order - and a charging rule ahead of it at priority 1.
        // Refusals are evaluated in their own pass before any pricing rule, so where the author put it
        // must not matter: the trip is refused, not charged and then refused.
        $this->insertConditional($ratesheetId, [
            'name' => 'Heavy surcharge', 'value_type' => 'amount', 'value' => '50', 'priority' => 1,
            'action' => json_encode(['value' => 'add']),
            'json_logic' => json_encode(['>=' => [['var' => 'package.total_weight_lbs'], 1000]]),
        ]);
        $this->insertRefusal($ratesheetId, 'Too heavy', [
            '>=' => [['var' => 'package.total_weight_lbs'], 10000],
        ], 999);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Too heavy');

        (new ChargeModelFactory())->GenerateCharges($this->weightRequest($ratesheetId, 12000, true));
    }

    // --- Timing ---

    private function regularTime($results) {
        foreach($results as $r)
            if(($r['id'] ?? null) === 'regular')
                return $r['time'];
        return null;
    }

    public function testTimingAddsDeliveryZoneTimeWhenDeliveryLacksInternalTag() {
        $fixture = $this->makeRatesheet(10, 25); // outlying Regular time = 1; delivery type base time = 2
        $this->insertTimingConditional($fixture['ratesheetId'], [
            'action' => json_encode(['value' => 'add']),
            'value_type' => 'field',
            'value' => 'timing_inputs.delivery_zone_selected_type_time',
            'json_logic' => json_encode($this->lacksTag('delivery', 'internal')),
        ]);

        $ratesheet = (new \App\Http\Repos\RatesheetRepo())->GetById($fixture['ratesheetId']);
        $results = (new ChargeModelFactory())->EstimateDeliveryTimes($ratesheet, $fixture['internalId'], $fixture['outlyingId']);

        $this->assertEquals(3, $this->regularTime($results));
    }

    public function testTimingUnchangedWhenNoConditionalMatches() {
        $fixture = $this->makeRatesheet(10, 25);
        $this->insertTimingConditional($fixture['ratesheetId'], [
            'action' => json_encode(['value' => 'add']),
            'value_type' => 'field',
            'value' => 'timing_inputs.delivery_zone_selected_type_time',
            'json_logic' => json_encode($this->lacksTag('delivery', 'internal')),
        ]);

        $ratesheet = (new \App\Http\Repos\RatesheetRepo())->GetById($fixture['ratesheetId']);
        $results = (new ChargeModelFactory())->EstimateDeliveryTimes($ratesheet, $fixture['internalId'], $fixture['internalId']);

        $this->assertEquals(2, $this->regularTime($results));
    }

    // --- Zone groups: crossing ---

    /**
     * Three zones in a row, A-B-C, all mutually reachable through B. Flat delivery-type base is 25.
     * Zone D sits apart and belongs to no group.
     */
    private function makeGroupedRatesheet() {
        $ratesheetId = $this->makeRatesheetRow('Grouped', [
            ['id' => 'regular', 'name' => 'Regular', 'friendlyName' => 'Regular', 'cost' => 25, 'time' => 2],
        ]);

        $a = $this->makeZone($ratesheetId, 'A', 'internal', 0);
        $b = $this->makeZone($ratesheetId, 'B', 'internal', 20);
        $c = $this->makeZone($ratesheetId, 'C', 'internal', 40);
        $d = $this->makeZone($ratesheetId, 'D', 'outlying', 60, 15, 1);

        $this->setNeighbours($a, [$b]);
        $this->setNeighbours($b, [$a, $c]);
        $this->setNeighbours($c, [$b]);

        return [
            'ratesheetId' => $ratesheetId,
            'a' => ['id' => $a, 'point' => [5, 5]],
            'b' => ['id' => $b, 'point' => [25, 5]],
            'c' => ['id' => $c, 'point' => [45, 5]],
            'd' => ['id' => $d, 'point' => [65, 5]],
        ];
    }

    private function crossingRates($oneZone, $twoZone, $threeZone) {
        return [
            ['id' => 0, 'zones' => 1, 'Regular' => ['cost' => $oneZone, 'time' => 1]],
            ['id' => 1, 'zones' => 2, 'Regular' => ['cost' => $twoZone, 'time' => 2]],
            ['id' => 2, 'zones' => 3, 'Regular' => ['cost' => $threeZone, 'time' => 3]],
        ];
    }

    /** if the two zones share a group -> Set Base Price = the crossing cost */
    private function addCrossingBaseConditional($ratesheetId) {
        $this->insertConditional($ratesheetId, [
            'name' => 'Zone Crossing Base Price', 'priority' => 50, 'continue_on' => true,
            'action' => json_encode(['value' => 'set']),
            'value_type' => 'field', 'value' => 'zones_crossed_cost',
            'json_logic' => json_encode(['==' => [['var' => 'same_group'], true]]),
        ]);
    }

    /**
     * A rule's name may interpolate the trip's own values, so the line item can describe the delivery
     * ("2 zones crossed via Core") instead of the rule that priced it, the way the old hardcoded
     * engine's labels did.
     */
    public function testLineItemNameInterpolatesContextValues() {
        $fx = $this->makeGroupedRatesheet();
        $this->makeZoneGroup($fx['ratesheetId'], 'Core', [$fx['a']['id'], $fx['b']['id'], $fx['c']['id']], $this->crossingRates(6, 8, 10));
        $this->insertConditional($fx['ratesheetId'], [
            'name' => '{{zones_crossed}} zones crossed via {{crossing_group}}', 'priority' => 50,
            'action' => json_encode(['value' => 'set']),
            'value_type' => 'field', 'value' => 'zones_crossed_cost',
            'json_logic' => json_encode(['==' => [['var' => 'same_group'], true]]),
        ]);

        // A -> B crosses 2 zones.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['b']['point'])
        );

        $this->assertContains('2 zones crossed via Core', $this->lineItemNames($charges));
        $this->assertEquals(8, $this->totalPrice($charges));
    }

    public function testSameGroupUsesCrossingCostAsBase() {
        $fx = $this->makeGroupedRatesheet();
        $this->makeZoneGroup($fx['ratesheetId'], 'Core', [$fx['a']['id'], $fx['b']['id'], $fx['c']['id']], $this->crossingRates(6, 8, 10));
        $this->addCrossingBaseConditional($fx['ratesheetId']);

        $factory = new ChargeModelFactory();

        // A -> C crosses 3 zones (A, B, C) -> $10, replacing the flat $25 base.
        $far = $factory->GenerateCharges($this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['c']['point']));
        $this->assertEquals(10, $this->totalPrice($far));

        // A -> A is 1 zone -> $6.
        $same = $factory->GenerateCharges($this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['a']['point']));
        $this->assertEquals(6, $this->totalPrice($same));
    }

    public function testGroupWithNoConditionalChangesNothing() {
        $fx = $this->makeGroupedRatesheet();
        // A group on its own only makes crossing computable. Without a rule asking for it, the trip
        // keeps the flat delivery-type base - this is what protects the maps that had the old
        // use_internal_zones_calc flag switched off.
        $this->makeZoneGroup($fx['ratesheetId'], 'Core', [$fx['a']['id'], $fx['b']['id'], $fx['c']['id']], $this->crossingRates(6, 8, 10));

        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['c']['point'])
        );

        $this->assertEquals(25, $this->totalPrice($charges));
    }

    public function testUngroupedZoneFallsBackToFlatBase() {
        $fx = $this->makeGroupedRatesheet();
        $this->makeZoneGroup($fx['ratesheetId'], 'Core', [$fx['a']['id'], $fx['b']['id'], $fx['c']['id']], $this->crossingRates(6, 8, 10));
        $this->addCrossingBaseConditional($fx['ratesheetId']);

        // D is in no group, so it shares none with A: no crossing, flat base stands.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['d']['point'])
        );

        $this->assertEquals(25, $this->totalPrice($charges));
    }

    public function testDifferentGroupsFallBackToFlatBase() {
        $fx = $this->makeGroupedRatesheet();
        $this->makeZoneGroup($fx['ratesheetId'], 'North', [$fx['a']['id'], $fx['b']['id']], $this->crossingRates(6, 8, 10));
        $this->makeZoneGroup($fx['ratesheetId'], 'South', [$fx['c']['id']], $this->crossingRates(1, 2, 3));
        $this->addCrossingBaseConditional($fx['ratesheetId']);

        // A is only in North, C is only in South -> no shared group -> flat base.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['c']['point'])
        );

        $this->assertEquals(25, $this->totalPrice($charges));
    }

    public function testSharedGroupsTakeTheCheapestCrossing() {
        $fx = $this->makeGroupedRatesheet();
        $zones = [$fx['a']['id'], $fx['b']['id'], $fx['c']['id']];
        $this->makeZoneGroup($fx['ratesheetId'], 'Standard', $zones, $this->crossingRates(6, 8, 10));
        $this->makeZoneGroup($fx['ratesheetId'], 'Discount', $zones, $this->crossingRates(4, 5, 7));
        $this->addCrossingBaseConditional($fx['ratesheetId']);

        // Both groups route A -> C in 3 zones; the cheaper table wins.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['c']['point'])
        );

        $this->assertEquals(7, $this->totalPrice($charges));
    }

    public function testBfsIsMaskedByGroupMembership() {
        $fx = $this->makeGroupedRatesheet();
        // Express holds A and C but not the B that physically joins them, so no route exists inside
        // Express. Adjacency is geometry; the group is the mask. Core still routes A-B-C for $10.
        $this->makeZoneGroup($fx['ratesheetId'], 'Express', [$fx['a']['id'], $fx['c']['id']], $this->crossingRates(1, 2, 3));
        $this->makeZoneGroup($fx['ratesheetId'], 'Core', [$fx['a']['id'], $fx['b']['id'], $fx['c']['id']], $this->crossingRates(6, 8, 10));
        $this->addCrossingBaseConditional($fx['ratesheetId']);

        // If the mask leaked, the BFS would hop A -> B -> C inside Express and charge its cheaper $3.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['c']['point'])
        );

        $this->assertEquals(10, $this->totalPrice($charges));
    }

    public function testUnresolvableCrossingRefusesToPrice() {
        $fx = $this->makeGroupedRatesheet();
        // The group's rate table stops at 2 zones, but A -> C crosses 3. A rule that asks for a
        // crossing cost that cannot be worked out must refuse to price rather than charge $0.
        $this->makeZoneGroup($fx['ratesheetId'], 'Core', [$fx['a']['id'], $fx['b']['id'], $fx['c']['id']], [
            ['id' => 0, 'zones' => 1, 'Regular' => ['cost' => 6, 'time' => 1]],
            ['id' => 1, 'zones' => 2, 'Regular' => ['cost' => 8, 'time' => 2]],
        ]);
        $this->addCrossingBaseConditional($fx['ratesheetId']);

        $this->expectException(HttpException::class);

        (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['a']['point'], $fx['c']['point'])
        );
    }

    public function testCrossingTimeComesFromTheGroup() {
        $fx = $this->makeGroupedRatesheet();
        $this->makeZoneGroup($fx['ratesheetId'], 'Core', [$fx['a']['id'], $fx['b']['id'], $fx['c']['id']], $this->crossingRates(6, 8, 10));
        $this->insertTimingConditional($fx['ratesheetId'], [
            'name' => 'Zone Crossing Time', 'priority' => 50,
            'action' => json_encode(['value' => 'set']),
            'value_type' => 'field', 'value' => 'timing_inputs.zones_crossed_time',
            'json_logic' => json_encode(['==' => [['var' => 'same_group'], true]]),
        ]);

        $ratesheet = (new \App\Http\Repos\RatesheetRepo())->GetById($fx['ratesheetId']);
        $results = (new ChargeModelFactory())->EstimateDeliveryTimes($ratesheet, $fx['a']['id'], $fx['c']['id']);

        // 3 zones crossed -> the group's 3-zone time, replacing the delivery type's default of 2.
        $this->assertEquals(3, $this->regularTime($results));
    }

    // --- Base selection by rank: the St Albert scenarios ---

    /**
     * The St Albert map. St Albert is the home hub ($20), Edmonton is a hub ($30), and there is one
     * outlying town ($15). Flat fallback base is $25.
     *
     * Note Edmonton does NOT carry the 'outlying' tag here - on St Albert's own map it is a hub, and
     * tagging it outlying is what made scenario (a) overcharge.
     */
    private function makeStAlbertRatesheet() {
        $ratesheetId = $this->makeRatesheetRow('St Albert', [
            ['id' => 'regular', 'name' => 'Regular', 'friendlyName' => 'Regular', 'cost' => 25, 'time' => 2],
        ]);

        return [
            'ratesheetId' => $ratesheetId,
            'stAlbert' => ['id' => $this->makeZone($ratesheetId, 'St Albert', 'internal|home', 0, 20, 1), 'point' => [5, 5]],
            'edmonton' => ['id' => $this->makeZone($ratesheetId, 'Edmonton', 'internal|hub', 20, 30, 1), 'point' => [25, 5]],
            'outlying' => ['id' => $this->makeZone($ratesheetId, 'Outlying', 'outlying', 40, 15, 1), 'point' => [45, 5]],
        ];
    }

    /**
     * Base price = the price of the highest-ranked zone the trip touches, where rank is priority.
     * Every rule sets the base; first-set-wins means only the highest-ranked one that fires applies,
     * and continue_on stays true so the surcharges below still run.
     */
    private function addRankedBaseConditionals($ratesheetId) {
        $bases = [
            ['name' => 'Home hub (pickup)', 'priority' => 10, 'logic' => $this->hasTag('pickup', 'home'), 'value' => 'pickup_zone_selected_type_cost'],
            ['name' => 'Home hub (delivery)', 'priority' => 11, 'logic' => $this->hasTag('delivery', 'home'), 'value' => 'delivery_zone_selected_type_cost'],
            ['name' => 'Hub (pickup)', 'priority' => 20, 'logic' => $this->hasTag('pickup', 'hub'), 'value' => 'pickup_zone_selected_type_cost'],
            ['name' => 'Hub (delivery)', 'priority' => 21, 'logic' => $this->hasTag('delivery', 'hub'), 'value' => 'delivery_zone_selected_type_cost'],
        ];

        foreach($bases as $base)
            $this->insertConditional($ratesheetId, [
                'name' => $base['name'], 'priority' => $base['priority'], 'continue_on' => true,
                'action' => json_encode(['value' => 'set']),
                'value_type' => 'field', 'value' => $base['value'],
                'json_logic' => json_encode($base['logic']),
            ]);

        foreach([['pickup', 'Pickup'], ['delivery', 'Delivery']] as [$side, $label])
            $this->insertConditional($ratesheetId, [
                'name' => $label . ' outlying surcharge', 'priority' => 100, 'continue_on' => true,
                'action' => json_encode(['value' => 'add']),
                'value_type' => 'field', 'value' => $side . '_zone_selected_type_cost',
                'json_logic' => json_encode($this->hasTag($side, 'outlying')),
            ]);
    }

    public function testScenarioA_StAlbertToEdmontonUsesStAlbertPrice() {
        $fx = $this->makeStAlbertRatesheet();
        $this->addRankedBaseConditionals($fx['ratesheetId']);

        // Home hub outranks hub, so St Albert's $20 wins even though Edmonton's rule also fires.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['stAlbert']['point'], $fx['edmonton']['point'])
        );

        $this->assertEquals(20, $this->totalPrice($charges));
    }

    public function testScenarioB_EdmontonToStAlbertUsesStAlbertPrice() {
        $fx = $this->makeStAlbertRatesheet();
        $this->addRankedBaseConditionals($fx['ratesheetId']);

        // Same $20, with the direction reversed - rank, not direction, picks the base.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['edmonton']['point'], $fx['stAlbert']['point'])
        );

        $this->assertEquals(20, $this->totalPrice($charges));
    }

    public function testScenarioC_EdmontonToOutlyingUsesEdmontonPricePlusOutlying() {
        $fx = $this->makeStAlbertRatesheet();
        $this->addRankedBaseConditionals($fx['ratesheetId']);

        // No home hub on this trip -> Edmonton's $30 base, + the outlying delivery's $15.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['edmonton']['point'], $fx['outlying']['point'])
        );

        $this->assertEquals(45, $this->totalPrice($charges));
    }

    public function testScenarioD_StAlbertToOutlyingUsesStAlbertPricePlusOutlying() {
        $fx = $this->makeStAlbertRatesheet();
        $this->addRankedBaseConditionals($fx['ratesheetId']);

        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['stAlbert']['point'], $fx['outlying']['point'])
        );

        $this->assertEquals(35, $this->totalPrice($charges));
    }

    public function testOutlyingToOutlyingKeepsFlatBaseAndBothSurcharges() {
        $fx = $this->makeStAlbertRatesheet();
        $this->addRankedBaseConditionals($fx['ratesheetId']);

        // No hub of any kind is touched, so no 'set' fires and the flat base stands: 25 + 15 + 15.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['outlying']['point'], $fx['outlying']['point'])
        );

        $this->assertEquals(55, $this->totalPrice($charges));
    }

    public function testFirstSetWinsInsteadOfStackingBases() {
        $fx = $this->makeStAlbertRatesheet();
        $this->addRankedBaseConditionals($fx['ratesheetId']);

        // St Albert -> Edmonton fires FOUR set rules (home pickup, hub delivery, and on the other
        // scenarios more besides). If later sets stacked instead of being skipped, this would be
        // 20 + 30 = 50 rather than 20.
        $charges = (new ChargeModelFactory())->GenerateCharges(
            $this->chargeRequest($fx['ratesheetId'], $fx['stAlbert']['point'], $fx['edmonton']['point'])
        );

        $this->assertEquals(20, $this->totalPrice($charges));
        // and exactly one base line item was emitted
        $baseLines = array_filter($charges, fn($item) => $item->getPrice() == 20);
        $this->assertCount(1, $baseLines);
    }
}
