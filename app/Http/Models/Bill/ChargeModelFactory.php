<?php

namespace App\Http\Models\Bill;

use App\Http\Models;
use App\Http\Repos;
use App\Http\MapLogic;
use App\Http\Services;
use Illuminate\Support\Facades\Log;
use JWadhams\JsonLogic;

use MathParser\StdMathParser;
use Matex\Evaluator;
use \PhpUnitsOfMeasure\PhysicalQuantity\Mass;

class ChargeModelFactory {
    public function GenerateCharges($req) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($req->ratesheet_id);

        $bill = $this->mockBill($req);

        return $this->buildChargesForType($ratesheet, $bill);
    }

    /**
     * Computes the full line-item set for a single bill (with $bill->delivery_type already set).
     *
     * Weight is no longer charged here. It used to come from generatePackageCharges, reading the
     * ratesheet's weight brackets - which only ever applied a bracket's flat base price, ignored the
     * per-increment columns entirely, and threw a fatal for anything above the top bracket. Weight is
     * now priced by conditionals like everything else (see the 2026_07_12_000005 backfill), which is
     * what lets a rule charge "$2 for every whole 100 lb over 100".
     *
     * @param $ratesheet - standard ratesheet object
     * @param $bill - mock bill object with delivery_type set to the type being priced
     *
     * @return array of LineItemModel
     */
    private function buildChargesForType($ratesheet, $bill, &$breakdown = null) {
        $distanceCharges = $this->generateDistanceCharges($ratesheet, $bill);

        $distanceBasePrice = 0;
        foreach($distanceCharges as $charge)
            $distanceBasePrice += $charge->getPrice();

        $conditionalResult = $this->generateConditionalCharges($ratesheet, $bill, null, $distanceBasePrice, $distanceBasePrice);

        // A 'set' conditional supplies its own base price (e.g. an origin zone's price), so the
        // default distance base is dropped to avoid double-charging; otherwise it is kept.
        $baseCharges = $conditionalResult['suppressBase'] ? [] : $distanceCharges;

        // Handed back through a reference rather than the return value: GenerateCharges is public and
        // its callers expect a plain list of line items.
        $breakdown = $conditionalResult['breakdown'];
        if($conditionalResult['suppressBase'] && isset($breakdown[0]))
            $breakdown[0]['note'] = 'Dropped — a rule below set the base price instead';

        return array_merge($baseCharges, $conditionalResult['lineItems']);
    }

    /**
     * Finds the zone a given point falls within
     */
    public function GetZone($ratesheetId, $lat, $lng) {
        $pointInPolygonAcceptableResponses = ['inside', 'vertex', 'boundary'];

        $pointLocation = new MapLogic\PointLocation;
        $ratesheetRepo = new Repos\RatesheetRepo;

        $zones = $ratesheetRepo->GetMapZones($ratesheetId);

        foreach($zones as $key => $zone) {
            $jsonCoordinates = json_decode($zone['coordinates']);
            $coordinateArray = array();
            foreach($jsonCoordinates as $coordinate)
                $coordinateArray[] = $coordinate->lat . ' ' . $coordinate->lng;
            if($coordinateArray[0] != end($coordinateArray))
                $coordinateArray[] = $coordinateArray[0];

            if(in_array($pointLocation->pointInPolygon($lat . ' ' . $lng, $coordinateArray), $pointInPolygonAcceptableResponses))
                return $this->prepareZone($zone);
        }
    }

    /**
     * Counts the zones crossed between two zones, traversing only within a single zone group.
     *
     * zones.neighbours is pure geometry (which zones physically touch), computed for every grouped
     * zone regardless of group. The group is the mask applied here: a zone may belong to several
     * groups, so a BFS through group A must never route through a zone that is only in group B.
     * Passing no $zoneGroupId traverses the raw geometric graph (used by the zonesCrossed endpoint).
     *
     * A zone is 1 zone from itself, preserving the original semantics.
     */
    public function GetZonesCrossed($ratesheetId, $pickupZoneId, $deliveryZoneId, $zoneGroupId = null) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $zones = $ratesheetRepo->GetMapZones($ratesheetId)->toArray();

        $traversable = null;
        if($zoneGroupId !== null) {
            $zoneGroupRepo = new Repos\ZoneGroupRepo();
            $traversable = array_flip($zoneGroupRepo->GetZoneIds($zoneGroupId));
        }

        // Build adjacency map: zone_id => [neighbour_ids]
        $graph = [];
        foreach ($zones as $zone) {
            $graph[$zone['zone_id']] = !empty($zone['neighbours'])
                ? json_decode($zone['neighbours'], true)
                : [];
        }

        $startId = (int) $pickupZoneId;
        $targetId = (int) $deliveryZoneId;

        if(!$startId || !$targetId)
            return null;

        if($traversable !== null && (!isset($traversable[$startId]) || !isset($traversable[$targetId])))
            return null;

        // BFS structures
        $queue = [ $startId ];
        $visited = [ $startId => 1 ]; // zone_id => distance

        while (!empty($queue)) {
            $currentId = array_shift($queue);
            $currentDistance = $visited[$currentId];

            if ($currentId === $targetId) {
                return $currentDistance;
            }

            foreach ($graph[$currentId] ?? [] as $neighbourId) {
                if (isset($visited[$neighbourId]))
                    continue;
                if ($traversable !== null && !isset($traversable[$neighbourId]))
                    continue;

                $visited[$neighbourId] = $currentDistance + 1;
                $queue[] = $neighbourId;
            }
        }

        return null;
    }

    /**
     * For every group the pickup and delivery zones have in common, works out what crossing that
     * group would cost and take for the given delivery type. A group only produces a candidate if
     * the BFS finds a route AND the group's rate table has a row for that many zones.
     *
     * A group existing does not mean crossing applies - these are just the numbers a conditional may
     * choose to reference via the zones_crossed_cost / zones_crossed_time fields.
     *
     * The BFS does not depend on the delivery type, so candidates are built once per route and the
     * per-type cost/time are read off the carried rate row by SelectCandidate.
     *
     * @return array of ['zone_group_id', 'name', 'zones_crossed', 'rate']
     */
    private function BuildZoneCrossingCandidates($ratesheetId, $pickupZone, $deliveryZone) {
        if(!$pickupZone || !$deliveryZone)
            return [];

        $sharedGroupIds = array_values(array_intersect(
            $this->NormalizeGroupIds($pickupZone),
            $this->NormalizeGroupIds($deliveryZone)
        ));

        if(!$sharedGroupIds)
            return [];

        $zoneGroupRepo = new Repos\ZoneGroupRepo();
        $candidates = [];

        foreach($sharedGroupIds as $zoneGroupId) {
            $zoneGroup = $zoneGroupRepo->GetById($zoneGroupId);
            if(!$zoneGroup)
                continue;

            $zonesCrossed = $this->GetZonesCrossed($ratesheetId, $pickupZone->zone_id, $deliveryZone->zone_id, $zoneGroupId);
            if($zonesCrossed === null)
                continue;

            $zoneRate = $this->GetZoneRateByDistance($zoneGroup->zone_rates, $zonesCrossed);
            if(!$zoneRate)
                continue;

            $candidates[] = [
                'zone_group_id' => $zoneGroupId,
                'name' => $zoneGroup->name,
                'zones_crossed' => $zonesCrossed,
                'rate' => $zoneRate,
            ];
        }

        return $candidates;
    }

    /**
     * Picks the candidate a context should use, for one delivery type. Pickup and delivery can share
     * more than one group; pricing takes the cheapest crossing and timing takes the fastest, each on
     * its own (timing runs in contexts where nothing is priced at all, so it cannot defer to the
     * price winner).
     *
     * A candidate whose figure is null - the group has a row for this distance but nothing entered
     * for this delivery type - is not eligible. null means "not configured", not "free", and a rule
     * that asks for an unconfigured crossing aborts rather than quietly charging nothing.
     *
     * @return array{value: float, zones_crossed: int, zone_group_id: int, name: string}|null
     */
    private function SelectCandidate($candidates, $deliveryTypeName, $field) {
        $eligible = [];

        foreach($candidates as $candidate) {
            $value = $this->GetZoneRateTypedValue($candidate['rate'], $deliveryTypeName, $field);
            if($value === null)
                continue;

            $eligible[] = array_merge($candidate, ['value' => $value]);
        }

        if(!$eligible)
            return null;

        usort($eligible, function($a, $b) {
            return $a['value'] <=> $b['value'];
        });

        return $eligible[0];
    }

    private function NormalizeGroupIds($zone) {
        $groupIds = $zone->group_ids ?? [];

        if(is_string($groupIds))
            $groupIds = json_decode($groupIds, true) ?: [];

        return array_map('intval', (array) $groupIds);
    }

    /**
     * Convenience wrapper that resolves zones by id then computes per-type times.
     */
    public function EstimateDeliveryTimes($ratesheet, $pickupZoneId = null, $deliveryZoneId = null) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $pickupZone = $pickupZoneId ? $ratesheetRepo->GetMapZoneById($ratesheet->ratesheet_id, $pickupZoneId) : null;
        $deliveryZone = $deliveryZoneId ? $ratesheetRepo->GetMapZoneById($ratesheet->ratesheet_id, $deliveryZoneId) : null;

        return $this->ComputeDeliveryTimes($ratesheet, $pickupZone, $deliveryZone);
    }

    /**
     * Computes the route-adjusted delivery time for every delivery type given already-resolved
     * zone objects (avoids re-fetching zones the caller has already looked up). The type-independent
     * zone context is built once and reused across all delivery types.
     *
     * @return array of delivery types, each with its computed `time`
     */
    public function ComputeDeliveryTimes($ratesheet, $pickupZone, $deliveryZone) {
        $timingConditionalRepo = new Repos\TimingConditionalRepo();

        $deliveryTypes = json_decode($ratesheet->delivery_types, true) ?? [];
        $timingConditionals = $timingConditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id)->toArray();

        // Crossing candidates depend only on the route, not the delivery type — built once. Having a
        // candidate does not mean crossing applies; a timing conditional decides that by referencing
        // timing_inputs.zones_crossed_time.
        $candidates = $this->BuildZoneCrossingCandidates($ratesheet->ratesheet_id, $pickupZone, $deliveryZone);

        // Type-independent zone context (tags, names, groups, zone-time maps) — built once.
        $zoneContext = $this->BuildZoneContext($pickupZone, $deliveryZone, $deliveryTypes, $candidates);

        $results = [];
        foreach($deliveryTypes as $deliveryType) {
            $defaultTime = isset($deliveryType['time']) ? (float) $deliveryType['time'] : 0;
            $timingContext = $this->BuildTimingContext($deliveryType, $zoneContext, $candidates, $defaultTime);

            $timing = $this->ApplyTimingConditionals($defaultTime, $timingConditionals, $timingContext);

            $deliveryType['time'] = $timing['time'];
            // How the time was arrived at, so staff can see which rules fired (and which did not).
            $deliveryType['time_breakdown'] = $timing['breakdown'];
            $results[] = $deliveryType;
        }

        return $results;
    }

    /**
     * Merged estimate: returns, for every delivery type, both a time estimate and a full price
     * quote (plus the line items that make up that price). Pricing is resilient — a type that
     * cannot be auto-priced (e.g. address outside a zone, weight over limits) returns a null
     * price with a reason instead of failing the whole response, so time estimates always return.
     *
     * @param $req - request carrying ratesheet_id, pickup/delivery addresses, packages, times, flags
     *
     * @return array of delivery types, each augmented with time, price,
     *         price_unavailable_reason, fits_today, earliest_delivery and line_items
     */
    public function EstimateDeliveryQuotes($req) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();
        $ratesheet = $ratesheetRepo->GetById($req->ratesheet_id);

        // Resolve zones once from the addresses, then reuse those objects for the timing path
        // (no second zone lookup) as well as for pricing.
        $bill = $this->mockBill($req);

        $timeResults = $this->ComputeDeliveryTimes($ratesheet, $bill->pickup_address->zone ?? null, $bill->delivery_address->zone ?? null);
        $timeById = [];
        foreach($timeResults as $timeResult)
            $timeById[$timeResult['id']] = $timeResult;

        $deliveryTypes = json_decode($ratesheet->delivery_types, true) ?? [];

        // Business hours + pickup time drive per-type feasibility (does this type's minimum land
        // within today's business hours). Server is the authority for business hours.
        $openParts = explode(':', config('ffe_config.business_hours_open'));
        $closeParts = explode(':', config('ffe_config.business_hours_close'));
        $pickupDateTime = null;
        try {
            $pickupDateTime = $req->time_pickup_scheduled ? new \DateTime($req->time_pickup_scheduled) : null;
        } catch(\Throwable $e) {
            $pickupDateTime = null;
        }

        $results = [];
        foreach($deliveryTypes as $deliveryType) {
            $typeId = $deliveryType['id'] ?? null;
            $timeEntry = ($typeId !== null && isset($timeById[$typeId])) ? $timeById[$typeId] : null;
            $fallbackTime = isset($deliveryType['time']) ? (float) $deliveryType['time'] : null;

            $price = null;
            $reason = null;
            $lineItems = [];
            $priceBreakdown = [];

            {
                try {
                    $bill->delivery_type = $selectionsRepo->GetSelectionByTypeAndValue('delivery_type', $typeId);
                    $lineItems = $this->buildChargesForType($ratesheet, $bill, $priceBreakdown);
                    $price = 0;
                    foreach($lineItems as $lineItem)
                        $price += $lineItem->getPrice();
                    $price = round($price, 2);
                } catch (\Throwable $e) {
                    $price = null;
                    $reason = $e->getMessage();
                    $lineItems = [];
                    $priceBreakdown = [];
                }
            }

            $minimumTime = $timeEntry['time'] ?? $fallbackTime;
            $feasibility = $this->ComputeFeasibility($pickupDateTime, $minimumTime, $closeParts);

            $results[] = array_merge($deliveryType, [
                'time' => $minimumTime,
                'price' => $price,
                'price_unavailable_reason' => $reason,
                'fits_today' => $feasibility['fits_today'],
                'earliest_delivery' => $feasibility['earliest_delivery'],
                'line_items' => $lineItems,
                // Which rules fired, which were skipped and why, and which did not match at all. Not
                // stored on the bill - both are recomputed with every quote - but they are the only way
                // to see why a price or a time came out as it did, or which rule is wrongly (not)
                // applying. Same shape for both engines.
                'price_breakdown' => $priceBreakdown,
                'time_breakdown' => $timeEntry['time_breakdown'] ?? [],
            ]);
        }

        return $results;
    }

    /**
     * Given a pickup time and a delivery type's minimum (hours), determines whether the
     * earliest possible delivery lands within business hours on the pickup day.
     *
     * @return array{fits_today: ?bool, earliest_delivery: ?string}
     */
    private function ComputeFeasibility($pickupDateTime, $minimumHours, $closeParts) {
        if(!$pickupDateTime || !is_numeric($minimumHours))
            return ['fits_today' => null, 'earliest_delivery' => null];

        $earliest = (clone $pickupDateTime)->modify('+' . (int) round($minimumHours * 60) . ' minutes');
        $closeToday = (clone $pickupDateTime)->setTime((int) $closeParts[0], (int) ($closeParts[1] ?? 0));
        $isWeekend = in_array((int) $pickupDateTime->format('N'), [6, 7], true);

        $fitsToday = !$isWeekend && $pickupDateTime <= $closeToday && $earliest <= $closeToday;

        return [
            'fits_today' => $fitsToday,
            'earliest_delivery' => $earliest->format(\DateTime::ATOM),
        ];
    }

    /**
     * The message of the first refusal rule that matches this bill, or null if none do.
     *
     * A refusal means "we cannot carry this - call us", and it has to be enforced where the booking is
     * actually accepted, not just where it is quoted: BillController::store takes its line items from
     * the request and never re-prices, so a customer who saw the refusal on the quote screen could
     * still submit the bill with no charges on it.
     *
     * Deliberately NOT applied to employees. A refusal reads to staff as "this cannot be auto-priced",
     * and staff are supposed to be able to book that bill and price it by hand - the same escape hatch
     * they already have for an address outside every zone. See BillValidationRules, which picks a
     * relaxed rule set for employees and the restricted one for account users.
     *
     * @param $req - the bill submit request (flat *_address_lat fields), not a pricing request
     */
    public function GetRefusalReason($ratesheet, $req) {
        $conditionalRepo = new Repos\ConditionalRepo();
        $refusals = $conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id, ['message']);

        if(!count($refusals))
            return null;

        $deliveryTypeId = is_array($req->delivery_type)
            ? ($req->delivery_type['id'] ?? null)
            : $req->delivery_type;

        if($deliveryTypeId === null)
            return null;

        // The pricing context is built from a quote-shaped request, so the bill's flat fields are
        // mapped onto that shape rather than duplicating how the context is assembled.
        $quoteRequest = new \Illuminate\Http\Request([
            'ratesheet_id' => $ratesheet->ratesheet_id,
            'delivery_type_id' => $deliveryTypeId,
            'packages' => $req->packages ?? [],
            'package_is_pallet' => $req->is_pallet ?? false,
            'package_is_minimum' => $req->is_min_weight_size ?? false,
            'use_imperial' => $req->use_imperial ?? false,
            'pickup_address' => [
                'is_mall' => $req->pickup_address_is_mall ?? false,
                'lat' => $req->pickup_address_lat,
                'lng' => $req->pickup_address_lng,
            ],
            'delivery_address' => [
                'is_mall' => $req->delivery_address_is_mall ?? false,
                'lat' => $req->delivery_address_lat,
                'lng' => $req->delivery_address_lng,
            ],
            'time_pickup_scheduled' => $req->time_pickup_scheduled,
            'time_delivery_scheduled' => $req->time_delivery_scheduled,
        ]);

        try {
            $context = $this->BuildPricingContext($ratesheet, $this->mockBill($quoteRequest));
        } catch (\Throwable $e) {
            // The trip could not be resolved at all (an address outside every zone, say). That is a
            // different problem with its own handling; it is not this rule's job to reject it.
            return null;
        }

        foreach($refusals as $refusal) {
            $rules = json_decode($refusal->json_logic, true);

            if(JsonLogic::apply($rules, $context))
                return $refusal->value ?: $refusal->name;
        }

        return null;
    }

    /**
     * Server-authoritative minimum delivery time (hours) for a single delivery type on a route,
     * resolved from addresses. Used by submit-side validation so the chosen delivery time can be
     * checked against a value the client cannot tamper with. Returns null when the route cannot be
     * auto-resolved (caller should let staff price/schedule manually in that case).
     */
    public function GetComputedDeliveryTime($ratesheet, $pickupLat, $pickupLng, $deliveryLat, $deliveryLng, $deliveryTypeId) {
        $pickupZone = ($pickupLat !== null && $pickupLng !== null) ? $this->GetZone($ratesheet->ratesheet_id, $pickupLat, $pickupLng) : null;
        $deliveryZone = ($deliveryLat !== null && $deliveryLng !== null) ? $this->GetZone($ratesheet->ratesheet_id, $deliveryLat, $deliveryLng) : null;

        $times = $this->ComputeDeliveryTimes($ratesheet, $pickupZone, $deliveryZone);
        foreach($times as $time)
            if(($time['id'] ?? null) == $deliveryTypeId)
                return is_numeric($time['time'] ?? null) ? (float) $time['time'] : null;

        return null;
    }

    /**
     * Applies the timing rules and records HOW it got to the answer.
     *
     * A delivery time is now the product of rules rather than a single number on the ratesheet, so
     * "why is this 4 hours?" needs an answer. The breakdown lists every rule that fired, what it did,
     * and the running total afterwards - which is what makes it possible to see a rule applying that
     * should not be, or not applying when it should.
     *
     * @return array{time: float, breakdown: array}
     */
    private function ApplyTimingConditionals($originalTime, $timingConditionals, $timingContext) {
        $breakdown = [[
            'name' => 'Base time for this delivery type',
            'action' => 'base',
            'value' => (float) $originalTime,
            'running_total' => (float) $originalTime,
            'note' => null,
        ]];

        if(!$timingConditionals || !count($timingConditionals))
            return ['time' => $originalTime, 'breakdown' => $breakdown];

        // Same as pricing: resolve which timing rules' conditions match, so one timing rule can be
        // written against another. Timing rules reference only timing rules.
        $this->ResolveMatchedRules($timingConditionals, $timingContext);

        $currentTime = $originalTime;
        $timeWasSet = false;

        usort($timingConditionals, function($a, $b) {
            $priorityA = isset($a['priority']) ? (int) $a['priority'] : 0;
            $priorityB = isset($b['priority']) ? (int) $b['priority'] : 0;
            return $priorityA <=> $priorityB;
        });

        foreach($timingConditionals as $conditional) {
            $rules = json_decode($conditional['json_logic'], true);
            if(!$rules)
                continue;

            // Rules that did not match are listed too, as they are in the pricing breakdown. "Why
            // didn't my rule fire?" is the question actually asked, and a list of the rules that DID
            // fire cannot answer it.
            if(!JsonLogic::apply($rules, $timingContext)) {
                $breakdown[] = [
                    'name' => $this->RenderConditionalName($conditional['name'] ?? '(unnamed rule)', $timingContext),
                    'action' => 'not_matched',
                    'value' => null,
                    'running_total' => (float) $currentTime,
                    'note' => 'Its conditions were not met by this delivery',
                ];
                continue;
            }

            {
                $action = json_decode($conditional['action']);
                $isAdd = $action && $action->value === 'add';

                // First 'set' wins, matching the pricing engine: priority ranks which rule owns the
                // time, and a lower-ranked one is skipped rather than overwriting the winner.
                if(!$isAdd && $timeWasSet) {
                    $breakdown[] = [
                        'name' => $this->RenderConditionalName($conditional['name'] ?? '(unnamed rule)', $timingContext),
                        'action' => 'skipped',
                        'value' => null,
                        'running_total' => (float) $currentTime,
                        'note' => 'Matched, but an earlier rule already set the time',
                    ];
                    continue;
                }

                $value = $this->ResolveTimingValue($conditional, $timingContext);

                if($isAdd) {
                    $currentTime += $value;
                } else {
                    $currentTime = $value;
                    $timeWasSet = true;
                }

                $stopped = !$this->ParseContinueOn($conditional['continue_on'] ?? null);

                $breakdown[] = [
                    'name' => $this->RenderConditionalName($conditional['name'] ?? '(unnamed rule)', $timingContext),
                    'action' => $isAdd ? 'add' : 'set',
                    'value' => (float) $value,
                    'running_total' => (float) $currentTime,
                    'note' => $stopped ? 'Stopped here — no later rules were considered' : null,
                ];

                if($stopped)
                    return ['time' => $currentTime, 'breakdown' => $breakdown];
            }
        }

        return ['time' => $currentTime, 'breakdown' => $breakdown];
    }

    private function ResolveTimingValue($conditional, $timingContext) {
        if(($conditional['value_type'] ?? null) === 'field')
            return $this->ResolveFieldValue($timingContext, $conditional['value'] ?? '', $conditional['name'] ?? '');

        return is_numeric($conditional['value']) ? (float) $conditional['value'] : 0;
    }

    private function GetValueByPath($source, $path) {
        if(!$path)
            return null;

        $segments = explode('.', $path);
        $current = $source;
        foreach($segments as $segment) {
            if(is_array($current)) {
                if(!array_key_exists($segment, $current))
                    return null;
                $current = $current[$segment];
            } else if(is_object($current)) {
                if(!isset($current->{$segment}))
                    return null;
                $current = $current->{$segment};
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Builds the parts of the timing context that do not depend on the delivery type being priced
     * (zone types/names/tags, the per-type zone-time maps, zone-crossing count). Built once per
     * route and reused across every delivery type.
     */
    /**
     * Computes the zone-relationship flags shared by the pricing and timing contexts from two resolved
     * zone objects. Single source of truth so both engines agree on what "same zone" and "same group"
     * mean. (Zone type is gone entirely: a rule that wants what internal/external used to say now asks
     * about the tag or the group directly.)
     */
    private function ComputeZoneRelationFlags($pickupZone, $deliveryZone) {
        $pickupZoneId = $pickupZone->zone_id ?? null;
        $deliveryZoneId = $deliveryZone->zone_id ?? null;

        $sharedGroupIds = array_values(array_intersect(
            $this->NormalizeGroupIds($pickupZone),
            $this->NormalizeGroupIds($deliveryZone)
        ));

        return [
            'zones_same' => $pickupZoneId && $deliveryZoneId ? $pickupZoneId === $deliveryZoneId : false,
            'same_group' => (bool) $sharedGroupIds,
            'shared_group_ids' => $sharedGroupIds,
        ];
    }

    /**
     * The nested per-side zone shape shared by the pricing and timing contexts.
     */
    private function BuildZoneNode($zone) {
        return [
            'zone_id' => $zone->zone_id ?? null,
            'name' => $zone->name ?? null,
            'tags' => $this->NormalizeZoneTags($zone->tags ?? null),
            'groups' => array_values((array) ($zone->groups ?? [])),
            'group_ids' => $this->NormalizeGroupIds($zone),
        ];
    }

    /**
     * Parses a raw continue_on value (from an array or object conditional) into a bool. Defaults to
     * true (continue) when unset. Shared by the pricing and timing engines.
     */
    private function ParseContinueOn($continueOnValue) {
        if($continueOnValue === null)
            return true;

        $parsed = filter_var($continueOnValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed === null ? ((int) $continueOnValue === 1) : $parsed;
    }

    private function BuildZoneContext($pickupZone, $deliveryZone, $deliveryTypes, $candidates) {
        $flags = $this->ComputeZoneRelationFlags($pickupZone, $deliveryZone);

        return [
            'zones_same' => $flags['zones_same'],
            'same_group' => $flags['same_group'],
            'delivery_address' => [
                'zone' => $this->BuildZoneNode($deliveryZone),
                'zone_time' => $this->BuildZoneTime($deliveryZone, $deliveryTypes)
            ],
            'pickup_address' => [
                'zone' => $this->BuildZoneNode($pickupZone),
                'zone_time' => $this->BuildZoneTime($pickupZone, $deliveryTypes)
            ]
        ];
    }

    /**
     * Merges the type-independent zone context with the fields that depend on the delivery type being
     * timed (base time, selected-type zone times, zone-crossing time).
     *
     * zones_crossed and zones_crossed_time come from the fastest shared group for this delivery type.
     * Both are null when no group produces a routable, configured crossing — a timing rule that
     * references a null crossing aborts rather than silently timing the trip at zero.
     */
    private function BuildTimingContext($deliveryType, $zoneContext, $candidates, $defaultTime) {
        $selectedTypeKey = 'type_' . ($deliveryType['id'] ?? '');
        $pickupZoneTime = $zoneContext['pickup_address']['zone_time'];
        $deliveryZoneTime = $zoneContext['delivery_address']['zone_time'];

        $pickupSelectedTypeTime = is_numeric($pickupZoneTime[$selectedTypeKey] ?? null) ? (float) $pickupZoneTime[$selectedTypeKey] : 0;
        $deliverySelectedTypeTime = is_numeric($deliveryZoneTime[$selectedTypeKey] ?? null) ? (float) $deliveryZoneTime[$selectedTypeKey] : 0;

        $friendlyName = $deliveryType['friendlyName'] ?? $deliveryType['name'] ?? null;
        $fastest = $this->SelectCandidate($candidates, $friendlyName, 'time');

        return array_merge($zoneContext, [
            'delivery_type' => $deliveryType['id'] ?? null,
            'zones_crossed' => $fastest ? $fastest['zones_crossed'] : null,
            'crossing_group' => $fastest ? $fastest['name'] : null,
            'base_time' => [
                'default' => (float) $defaultTime,
            ],
            'timing_inputs' => [
                'zones_crossed_time' => $fastest ? $fastest['value'] : null,
                'pickup_zone_selected_type_time' => $pickupSelectedTypeTime,
                'delivery_zone_selected_type_time' => $deliverySelectedTypeTime,
            ],
        ]);
    }

    private function normalizeModifiers($modifiers) {
        if(is_string($modifiers))
            return json_decode($modifiers, true) ?: [];
        if(is_array($modifiers))
            return $modifiers;
        if(is_object($modifiers))
            return json_decode(json_encode($modifiers), true) ?: [];

        return [];
    }

    private function NormalizeZoneTags($tags) {
        if(!$tags)
            return [];

        if(is_array($tags))
            return array_values(array_filter(array_map('trim', $tags)));

        return array_values(array_filter(array_map('trim', explode('|', $tags))));
    }

    private function GetZoneRateByDistance($zoneRatesJson, $zonesCrossed) {
        $zoneRates = json_decode($zoneRatesJson, true);
        if(!is_array($zoneRates))
            return null;

        foreach($zoneRates as $zoneRate)
            if((int) ($zoneRate['zones'] ?? 0) === (int) $zonesCrossed)
                return $zoneRate;

        return null;
    }

    /**
     * Returns null (not 0) when this delivery type has no figure on the rate row, so that an
     * unconfigured crossing is distinguishable from a free one.
     */
    private function GetZoneRateTypedValue($zoneRate, $deliveryTypeFriendlyName, $field) {
        $typedRate = $zoneRate[$deliveryTypeFriendlyName] ?? null;
        if(!is_array($typedRate))
            return null;

        $value = $typedRate[$field] ?? null;
        return is_numeric($value) ? (float) $value : null;
    }

    private function BuildZoneTime($zone, $deliveryTypes) {
        $zoneTime = [];

        if(!$zone)
            return $zoneTime;

        // delivery_type_modifiers may arrive as a JSON string (zones fetched by id) or already
        // decoded (zones resolved via point-in-polygon) — normalize either to an assoc array.
        $deliveryTypeModifiers = $this->normalizeModifiers($zone->delivery_type_modifiers ?? null);

        foreach($deliveryTypes as $deliveryType) {
            if(!isset($deliveryType['id']))
                continue;
            $key = 'type_' . $deliveryType['id'];
            $deliveryTypeName = $deliveryType['friendlyName'] ?? $deliveryType['name'] ?? null;
            $value = $deliveryTypeName ? ($deliveryTypeModifiers[$deliveryTypeName]['time'] ?? null) : null;
            $zoneTime[$key] = is_numeric($value) ? (float) $value : 0;
        }

        return $zoneTime;
    }

    /**
     * Resolves a zone's per-delivery-type cost from its delivery_type_modifiers, for the given
     * delivery type friendly name. Returns 0 when the zone has no modifier for that type. Works
     * for any zone type (internal zones may carry a base price here too).
     */
    private function ResolveZoneModifierCost($zone, $deliveryTypeName) {
        if(!$zone || !$deliveryTypeName)
            return 0;

        $modifiers = (array) ($zone->delivery_type_modifiers ?? []);
        $modifier = $modifiers[$deliveryTypeName] ?? null;

        return $modifier && isset($modifier->cost) && is_numeric($modifier->cost) ? (float) $modifier->cost : 0;
    }

    /**
     * Augments the mock bill with the fields pricing conditionals evaluate against. The nested
     * bill shape (pickup_address.zone.type, package.total_weight, time_*.day_of_the_week, ...) is
     * preserved so existing saved conditionals keep working; the resolved per-side, per-selected-
     * type zone costs are added on top so rules can pull the relevant delivery type's price.
     *
     * Because pricing is computed once per delivery type (bill->delivery_type is already set), the
     * "selected type" costs are resolved here rather than left as a lookup for the rule author.
     */
    private function BuildPricingContext($ratesheet, $bill) {
        $deliveryTypeName = $bill->delivery_type->name ?? null;

        $pickupZone = $bill->pickup_address->zone ?? null;
        $deliveryZone = $bill->delivery_address->zone ?? null;

        $pickupCost = $this->ResolveZoneModifierCost($pickupZone, $deliveryTypeName);
        $deliveryCost = $this->ResolveZoneModifierCost($deliveryZone, $deliveryTypeName);

        // Shared zone-relationship flags (same_group, zones_same) so pricing rules can gate on the same
        // conditions timing offers.
        $flags = $this->ComputeZoneRelationFlags($pickupZone, $deliveryZone);
        $bill->zones_same = $flags['zones_same'];
        $bill->same_group = $flags['same_group'];

        // Zone crossing: resolved from the cheapest group the two zones share, for the delivery type
        // currently being priced. A group merely makes this computable — a conditional referencing
        // zones_crossed_cost is what actually applies it.
        $candidates = $this->BuildZoneCrossingCandidates($ratesheet->ratesheet_id, $pickupZone, $deliveryZone);
        $cheapest = $this->SelectCandidate($candidates, $deliveryTypeName, 'cost');

        $bill->zones_crossed = $cheapest ? $cheapest['zones_crossed'] : null;
        $bill->zones_crossed_cost = $cheapest ? $cheapest['value'] : null;
        $bill->crossing_group = $cheapest ? $cheapest['name'] : null;

        $bill->pickup_zone_selected_type_cost = $pickupCost;
        $bill->delivery_zone_selected_type_cost = $deliveryCost;

        // The same weight, in the other unit. See KilogramsToPounds for why this is a second field
        // rather than a conversion applied when a rule is loaded or saved.
        if(isset($bill->package))
            $bill->package->total_weight_lbs = $this->KilogramsToPounds($bill->package->total_weight ?? 0);

        return $bill;
    }

    /**
     * Weight is stored in kilograms, always. Pounds are offered alongside as a second, derived view of
     * the same number, so a rule can be authored in whichever unit its author thinks in.
     *
     * Deliberately not a display-time conversion. A conditional's value may be a free-form equation
     * ("max(10, total_weight_kgs * 0.5)"), and reinterpreting a variable inside a formula would mean
     * rewriting the author's coefficients - 0.5 per kg is 0.2268 per lb, while the 10 stays put. There
     * is no safe general transform for that. Exposing both units instead keeps every stored rule
     * explicit about which unit it means, and converts nothing on save or load.
     */
    private function KilogramsToPounds($kilograms) {
        if(!is_numeric($kilograms))
            return 0;

        return (new Mass($kilograms, 'kg'))->toUnit('pounds');
    }

    private function EvaluateFunctionalEquation($conditional, $mockBill) {
        $evaluator = new Evaluator();

        $totalWeight = $mockBill->package->total_weight;

        // The unit is part of the variable name, never a suffix in the formula: an equation says
        // total_weight_kgs or total_weight_lbs, so it records which unit it means and nothing has to be
        // reinterpreted. (The kg variable was once just "total_weight", which invited "total_weight lbs"
        // - a single token to the evaluator, and an instant Unknown variable.)
        $evaluator->variables = [
            'total_weight_kgs' => $totalWeight,
            'total_weight_lbs' => $this->KilogramsToPounds($totalWeight),
        ];

        // Only offered when they actually resolved. A variable present-but-null is worse than absent:
        // Matex would read it as 0, and "$0.18 * 0 km" is a fuel surcharge that silently vanishes.
        // Left out, Matex raises Unknown variable, which becomes the same "please price manually"
        // refusal a missing field gets - the equation fails loudly or not at all.
        if($mockBill->distance_km !== null)
            $evaluator->variables['distance_km'] = $mockBill->distance_km;
        if(($mockBill->drive_time_minutes ?? null) !== null)
            $evaluator->variables['drive_time_minutes'] = $mockBill->drive_time_minutes;

        // Enable math functions to match frontend capabilities
        $evaluator->functions = [
            'max' => ['ref' => 'max', 'arc' => null],      // max(a, b, c, ...)
            'min' => ['ref' => 'min', 'arc' => null],      // min(a, b, c, ...)
            'round' => ['ref' => 'round', 'arc' => 2],     // round(number, precision)
            // Weight brackets charge per WHOLE increment ("$2 per 100 lbs"), so a rule needs to be
            // able to throw away the part-increment: floor((total_weight_lbs - 100) / 100) * 2 + 16.
            // Without floor, a 150 lb parcel would be charged half an increment.
            'floor' => ['ref' => 'floor', 'arc' => 1],     // floor(number)
            'ceil' => ['ref' => 'ceil', 'arc' => 1],       // ceil(number)
            'abs' => ['ref' => 'abs', 'arc' => 1],         // abs(number)
            'sqrt' => ['ref' => 'sqrt', 'arc' => 1],       // sqrt(number)
            'pow' => ['ref' => 'pow', 'arc' => 2],         // pow(base, exponent)
        ];

        // Matex reports an unresolvable variable by throwing, which would otherwise surface as a 500.
        // Turn it into the same 400 an unresolvable field gets: the author named something this trip
        // does not have (distance with no route, or simply a typo), and the honest answer is to refuse
        // to price rather than guess.
        try {
            return $evaluator->execute($conditional->original_equation_string);
        } catch(\Throwable $e) {
            abort(400, 'Rule "' . $conditional->name . '" could not be worked out for this trip ('
                . $e->getMessage() . '). Please price manually.');
        }
    }

    /**
     * Writes matched_rules onto a context, given the sheet's rules in whatever shape their repo hands
     * back (Eloquent models for pricing, arrays for timing).
     *
     * Pricing and timing rules are separate namespaces on purpose: a timing rule referencing a pricing
     * rule would be a category error, and the ids would collide besides.
     */
    private function ResolveMatchedRules($conditionals, &$context) {
        $rules = [];

        foreach($conditionals as $conditional) {
            // data_get, not (array) - casting an Eloquent model yields its NUL-prefixed internals,
            // not its attributes, so every id would read as null and no rule would ever match.
            // Pricing rules key on conditional_id, timing rules (plain arrays) on timing_conditional_id.
            $id = data_get($conditional, 'conditional_id') ?? data_get($conditional, 'timing_conditional_id');
            if($id === null)
                continue;

            $rules[] = [
                'id' => $id,
                'name' => data_get($conditional, 'name'),
                'json_logic' => data_get($conditional, 'json_logic'),
            ];
        }

        try {
            return Services\RuleReferences::ResolveMatched($rules, $context);
        } catch(\RuntimeException $e) {
            // Cycles are rejected when a rule is saved, so reaching this means the data predates that
            // check. Refuse the quote rather than looping: a circular reference has no answer.
            abort(400, $e->getMessage() . '. Please price manually.');
        }
    }

    /**
     * Calculates which conditional charges apply by creating an object and comparing against stored json_logic data
     * @param $ratesheet - standard ratesheet object pulled from database
     * @param $req - the request, which will be used to generate a standard object for comparisons
     *
     * @return $results - an array of charges to add to the bill
     */
    private function generateConditionalCharges($ratesheet, $bill, $req, $currentPrice, $distanceBasePrice = 0) {
        $conditionalRepo = new Repos\ConditionalRepo();

        // Amount-family (amount/equation/field) rules run in priority order and can add to, set, or
        // discount the running price. Percent rules run afterward on the accumulated total so their
        // long-standing "percent of everything else" behavior is preserved.
        $amountConditionals = $conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id, ['amount', 'equation', 'field'])->all();
        $percentConditionals = $conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id, ['percent']);
        $refusals = $conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id, ['message']);

        $context = $this->BuildPricingContext($ratesheet, $bill);

        // Resolve which rules' conditions match, so a rule can be written against another rule
        // ("matched_rules NOT CONTAINS [the 0-49lb bracket]"). Every rule on the sheet takes part,
        // refusals included, and the result is written onto the context as matched_rules.
        //
        // Conditions are pure - they read the delivery, not the running price - so this pass produces
        // the same answers the loops below will, and the loops need no change. It runs per delivery
        // type because the conditions can read the delivery type.
        $this->ResolveMatchedRules($conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id), $context);

        // Refusals run first, in their own pass, and priority does not enter into it. A rule that says
        // "we cannot carry this" is not competing for a slot in the pricing order - it decides whether
        // there is a price at all. Ordering it with the rest would mean accumulating charges only to
        // throw them away, and would make the outcome depend on where the author happened to put it.
        //
        // The refusal surfaces the same way as an address outside every zone: a 400 that the UI shows
        // as "please price manually", carrying the author's own message.
        foreach($refusals as $refusal) {
            $rules = json_decode($refusal->json_logic, true);

            // The message is interpolated too: "We cannot carry {{package.total_weight_lbs}} lb" tells
            // the customer what actually tripped the rule.
            if(JsonLogic::apply($rules, $context))
                abort(400, $this->RenderConditionalName($refusal->value ?: $refusal->name, $context));
        }

        $results = array();
        $suppressBase = false;

        // Mirrors the timing engine's breakdown: what each rule did to the running price, and - just as
        // importantly - what the rules that did nothing did not do. "Why didn't my rule fire?" is the
        // question that actually gets asked, and a list of the charges that happened cannot answer it.
        $breakdown = [[
            'name' => 'Base price for this delivery type',
            'action' => 'base',
            'value' => (float) $distanceBasePrice,
            'running_total' => (float) $currentPrice,
            'note' => null,
        ]];

        usort($amountConditionals, function($a, $b) {
            return (isset($a->priority) ? (int) $a->priority : 0) <=> (isset($b->priority) ? (int) $b->priority : 0);
        });

        foreach($amountConditionals as $conditional) {
            $rules = json_decode($conditional->json_logic, true);

            if(!JsonLogic::apply($rules, $context)) {
                $breakdown[] = [
                    'name' => $this->RenderConditionalName($conditional->name, $context),
                    'action' => 'not_matched',
                    'value' => null,
                    'running_total' => (float) $currentPrice,
                    'note' => 'Its conditions were not met by this delivery',
                ];
                continue;
            }

            $action = json_decode($conditional->action)->value;

            // First 'set' wins: base selection is a ranking, expressed as priority. A later 'set'
            // is a lower-ranked candidate for the same slot, so it is skipped rather than added on
            // top (which would double-charge). This is what lets a map say "use the home hub's
            // price if the trip touches it, else the hub's, else the zone-crossing rate" without
            // short-circuiting the surcharges that must still run afterwards.
            if($action == 'set' && $suppressBase) {
                $breakdown[] = [
                    'name' => $this->RenderConditionalName($conditional->name, $context),
                    'action' => 'skipped',
                    'value' => null,
                    'running_total' => (float) $currentPrice,
                    'note' => 'Matched, but an earlier rule already set the base price',
                ];
                continue;
            }

            {
                $value = $this->ResolvePricingValue($conditional, $context);
                $label = $this->RenderConditionalName($conditional->name, $context);

                if($action == 'discount') {
                    $currentPrice = ($currentPrice - $value) > 0 ? $currentPrice - $value : 0;
                    $results[] = new LineItemModel($label, 'conditionalRate', $value);
                } else if($action == 'set') {
                    // This rule supplies the base price, so drop the default distance base.
                    $currentPrice -= $distanceBasePrice;
                    $suppressBase = true;
                    $currentPrice += $value;
                    $results[] = new LineItemModel($label, 'conditionalRate', $value);
                } else {
                    // 'add' — the "Add Charge" action (and legacy 'charge', treated identically)
                    $currentPrice += $value;
                    $results[] = new LineItemModel($label, 'conditionalRate', $value);
                }

                $stopped = !$this->ParseContinueOn($conditional->continue_on ?? null);

                $breakdown[] = [
                    'name' => $label,
                    'action' => $action == 'set' ? 'set' : ($action == 'discount' ? 'discount' : 'add'),
                    'value' => (float) $value,
                    'running_total' => (float) $currentPrice,
                    'note' => $stopped ? 'Stopped here — no later rules were considered' : null,
                ];

                if($stopped)
                    break;
            }
        }

        foreach($percentConditionals as $conditional) {
            $rules = json_decode($conditional->json_logic, true);

            if(!JsonLogic::apply($rules, $context)) {
                $breakdown[] = [
                    'name' => $this->RenderConditionalName($conditional->name, $context),
                    'action' => 'not_matched',
                    'value' => null,
                    'running_total' => (float) $currentPrice,
                    'note' => 'Its conditions were not met by this delivery',
                ];
                continue;
            }

            $action = json_decode($conditional->action)->value;
            $value = $conditional->value;

            $price = round($value / 100 * $currentPrice, 2);
            if($action == 'discount')
                $price *= -1;

            $label = $this->RenderConditionalName($conditional->name, $context);
            $results[] = new LineItemModel($label, 'conditionalRate', $price);

            // Percent rules do not feed back into the running total - they are each a percentage of the
            // total the amount rules produced, not of one another - so running_total is left where the
            // amount pass left it, and the row shows the dollars this rule actually added.
            $breakdown[] = [
                'name' => $label,
                'action' => 'percent',
                'value' => (float) $price,
                'running_total' => (float) $currentPrice,
                'note' => $value . '% of ' . number_format($currentPrice, 2),
            ];
        }

        return ['lineItems' => $results, 'suppressBase' => $suppressBase, 'breakdown' => $breakdown];
    }

    /**
     * Interpolates {{context.path}} placeholders in a rule's name.
     *
     * A line item is named after the rule that produced it, which describes the rule rather than the
     * trip: every crossing on the sheet reads "Zone Crossing Base Price". Naming a rule
     * "{{zones_crossed}} zones crossed" lets it describe the delivery in front of it instead, the way
     * the old hardcoded engine's labels did.
     *
     * These names go on invoices, so an unresolvable placeholder must never reach the customer as
     * braces: it renders as nothing and the surrounding whitespace is collapsed, degrading the label
     * ("zones crossed") rather than breaking it. Interpolation is not a way to charge - the price
     * still comes from value/value_type - so a missing value is cosmetic and must not abort, unlike
     * ResolveFieldValue.
     */
    private function RenderConditionalName($name, $context) {
        if(!is_string($name) || strpos($name, '{{') === false)
            return $name;

        $rendered = preg_replace_callback('/\{\{\s*([A-Za-z0-9_.]+)\s*\}\}/', function($match) use ($context) {
            return $this->FormatContextValue($this->GetValueByPath($context, $match[1]));
        }, $name);

        return trim(preg_replace('/\s{2,}/', ' ', $rendered));
    }

    /** Renders a context value the way a customer should read it on an invoice line. */
    private function FormatContextValue($value) {
        if($value === null || $value === '')
            return '';

        if(is_bool($value))
            return $value ? 'yes' : 'no';

        if(is_array($value))
            return implode(', ', array_map([$this, 'FormatContextValue'], $value));

        // Trailing zeros read as false precision on an invoice: 24.00 -> 24, 11.0231 -> 11.02.
        if(is_numeric($value) && !is_string($value))
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return (string) $value;
    }

    /**
     * Resolves the numeric value a pricing conditional contributes, by value_type:
     *   - equation : evaluated against package inputs
     *   - field    : pulled from the pricing context by path (e.g. pickup_zone_selected_type_cost)
     *   - amount   : the literal stored value
     */
    private function ResolvePricingValue($conditional, $context) {
        if($conditional->value_type === 'equation')
            return $this->EvaluateFunctionalEquation($conditional, $context);

        if($conditional->value_type === 'field')
            return $this->ResolveFieldValue($context, $conditional->value ?? '', $conditional->name);

        return is_numeric($conditional->value) ? (float) $conditional->value : 0;
    }

    /**
     * Resolves a 'field' conditional's value from the context by path.
     *
     * A rule that fires but whose field cannot be resolved to a number is a misconfiguration, not a
     * zero: a zone-crossing rule on a map whose adjacency graph has no route, or whose rate table has
     * no figure for this delivery type, would otherwise quietly price the trip at $0. Refuse to price
     * it instead, the same as an address that falls outside every zone.
     */
    private function ResolveFieldValue($context, $path, $conditionalName) {
        $resolved = $this->GetValueByPath($context, $path);

        if(!is_numeric($resolved))
            abort(400, 'Rule "' . $conditionalName . '" needs ' . $path . ', which could not be worked out for this trip. Please price manually.');

        return (float) $resolved;
    }


    /**
     * Calculates distance charges based on ratesheet specifications in two primary modes
     * 1) Basic Mode
     * 2) Internal Zones Mode
     * 
     * For descriptions of these two modes, please see the FFE documentation
     * 
     * @param $ratesheet - standard ratesheet object gathered from the database
     * @param $pickupAddress - lat and lng of pickup address
     * @param $deliveryAddress - lat and lng of delivery address
     * @param $deliveryType - type of delivery as found in the value field of the selections table
     * 
     * @return $results - an array of charges to add to the bill
     *
     */

    private function generateDistanceCharges($ratesheet, $bill) {
        $pointLocation = new MapLogic\PointLocation;
        /**
         * If one or both requests are outside of a programmed deliverable area, then we throw an exception: The system cannot automatically calculate the pricing, this must be done manually
         */
        if(!$bill->pickup_address->zone)
            abort(400, 'Requested pickup was not in a designated zone! Please price manually');
        else if (!$bill->delivery_address->zone)
            abort(400, 'Requested delivery was not in a designated zone! Please price manually');

        // The flat delivery-type cost is the default base for every trip. Everything that used to
        // branch here is now a conditional: zone crossing (a 'set' rule reading zones_crossed_cost,
        // which replaces this base) and outlying surcharges (an 'add' rule reading the zone's
        // per-delivery-type modifier cost). A trip whose zones share no group, or whose map has no
        // crossing rule, simply keeps this base.
        $results = array();

        $deliveryTypes = json_decode($ratesheet->delivery_types);
        $chargeDeliveryTypeIndex = array_search($bill->delivery_type->value, array_column($deliveryTypes, 'id'));
        $chargeDeliveryType = $chargeDeliveryTypeIndex !== false ? $deliveryTypes[$chargeDeliveryTypeIndex] : null;

        if($chargeDeliveryType)
            $results[] = new LineItemModel($bill->delivery_type->name, 'distanceRate', $chargeDeliveryType->cost ?? 0);

        return $results;
    }

    private function GetSecondsSinceMidnight($dateTime) {
        $dateTime = new \DateTime($dateTime);
        $midnight = (clone($dateTime))->setTime(0, 0);

        $interval = $midnight->diff($dateTime);
        return $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }

    private function mockBill($req) {
        $selectionsRepo = new Repos\SelectionsRepo();

        $bill = (object) [
            'delivery_address' => (object) [
                'is_mall' => filter_var($req->delivery_address['is_mall'], FILTER_VALIDATE_BOOLEAN),
                'zone' => $this->GetZone($req->ratesheet_id, $req->delivery_address['lat'], $req->delivery_address['lng']),
            ],
            'delivery_type' => $selectionsRepo->GetSelectionByTypeAndValue('delivery_type', $req->delivery_type_id),
            'package' => (object) [
                'is_pallet' => filter_var($req->package_is_pallet, FILTER_VALIDATE_BOOLEAN),
                // Already drives whether weight is charged at all (see GenerateCharges); exposed here
                // so a rule can also condition on it.
                'is_minimum' => filter_var($req->package_is_minimum, FILTER_VALIDATE_BOOLEAN),
                'total_weight' => $req->packages ? array_reduce($req->packages, function($carry, $package) {return $carry + $package['totalWeight'];}, 0) : 0
            ],
            'pickup_address' => (object) [
                'is_mall' => filter_var($req->pickup_address['is_mall'], FILTER_VALIDATE_BOOLEAN),
                'zone' =>  $this->GetZone($req->ratesheet_id, $req->pickup_address['lat'], $req->pickup_address['lng'])
            ],
            'time_delivery_scheduled' =>  (object) [
                'day_of_the_week' => (new \DateTime($req->time_delivery_scheduled))->format('w'),
                'time' => $this->GetSecondsSinceMidnight($req->time_delivery_scheduled)
            ],
            'time_pickup_scheduled' => (object) [
                'day_of_the_week' => (new \DateTime($req->time_pickup_scheduled))->format('w'),
                'time' => $this->GetSecondsSinceMidnight($req->time_pickup_scheduled)
            ]
        ];

        // Weight is NOT converted here, despite use_imperial being on the request.
        //
        // The client converts already: usePackages runs poundsToKilograms before it ever leaves the
        // browser, so packages[].totalWeight is kilograms whatever the toggle says - and that same value
        // is what gets stored on the bill. Converting again here treated those kilograms as pounds and
        // converted a second time, so with the imperial toggle on, a 100 lb parcel was priced as 45 lb:
        // every weight rule undercharged by a factor of 2.2, silently, for imperial users only.
        //
        // use_imperial remains on the request because it still describes what the author typed; it just
        // has no bearing on a value that arrives already metric.

        // Road distance is resolved here, not in BuildPricingContext, because this runs once per
        // route while BuildPricingContext runs once per delivery type. Distance does not depend on the
        // delivery type, so resolving it there would mean four identical lookups per quote - and the
        // quote endpoint fires on a debounce as the bill is typed.
        //
        // Null when it cannot be established (no key, API down, no drivable route). A rule that
        // references it then refuses to price; a sheet that does not reference it is unaffected.
        $route = (new Services\RouteDistanceService())->Between(
            $req->pickup_address['lat'] ?? null,
            $req->pickup_address['lng'] ?? null,
            $req->delivery_address['lat'] ?? null,
            $req->delivery_address['lng'] ?? null
        );

        $bill->distance_km = $route['km'] ?? null;
        $bill->drive_time_minutes = $route['minutes'] ?? null;

        return $bill;
    }

    private function prepareZone($zone) {
        return (object) [
            'zone_id' => $zone->zone_id,
            'name' => $zone->name,
            'delivery_type_modifiers' => json_decode($zone->delivery_type_modifiers ?? '{}'),
            // Must be a list, not the raw pipe-delimited string: tag rules are authored as CONTAINS,
            // which compiles to json-logic "some" and quietly matches nothing against a scalar.
            'tags' => $this->NormalizeZoneTags($zone->tags ?? null),
            'colour' => $zone->colour ?? null,
            'groups' => $zone->groups ?? [],
            'group_ids' => $zone->group_ids ?? [],
            'neighbours' => $zone->neighbours
        ];
    }
}
