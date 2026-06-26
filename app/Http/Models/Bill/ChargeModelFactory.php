<?php

namespace App\Http\Models\Bill;

use App\Http\Models;
use App\Http\Repos;
use App\Http\MapLogic;
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

        $packageCharges = filter_var($req->package_is_minimum, FILTER_VALIDATE_BOOLEAN) ? [] : $this->generatePackageCharges($ratesheet, $req->packages, $req->package_is_pallet, $req->use_imperial);

        return $this->buildChargesForType($ratesheet, $bill, $packageCharges);
    }

    /**
     * Computes the full line-item set for a single bill (with $bill->delivery_type already set).
     * Package charges are passed in because they are delivery-type independent and can be
     * computed once when pricing many types at once (see EstimateDeliveryQuotes).
     *
     * @param $ratesheet - standard ratesheet object
     * @param $bill - mock bill object with delivery_type set to the type being priced
     * @param $packageCharges - pre-computed package line items (delivery-type independent)
     *
     * @return array of LineItemModel
     */
    private function buildChargesForType($ratesheet, $bill, $packageCharges) {
        $distanceCharges = $this->generateDistanceCharges($ratesheet, $bill);
        // $timeCharges = $this->generateTimeCharges($ratesheet, $req->time_pickup_scheduled, $req->time_delivery_scheduled);
        $timeCharges = [];

        $currentCharges = array_merge($distanceCharges, $packageCharges, $timeCharges);
        $currentPrice = 0;
        foreach($currentCharges as $charge) {
            $currentPrice += $charge->getPrice();
        }

        $conditionalCharges = $this->generateConditionalCharges($ratesheet, $bill, null, $currentPrice);

        return array_merge($distanceCharges, $packageCharges, $timeCharges, $conditionalCharges);
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

    public function GetZonesCrossed($ratesheetId, $pickupZoneId, $deliveryZoneId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $zones = $ratesheetRepo->GetMapZones($ratesheetId)->toArray();

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
                if (!isset($visited[$neighbourId])) {
                    $visited[$neighbourId] = $currentDistance + 1;
                    $queue[] = $neighbourId;
                }
            }
        }

        return null;
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

        $useInternalZonesCalc = filter_var($ratesheet->use_internal_zones_calc, FILTER_VALIDATE_BOOLEAN);
        $pickupZoneId = $pickupZone->zone_id ?? null;
        $deliveryZoneId = $deliveryZone->zone_id ?? null;
        $zonesCrossed = ($useInternalZonesCalc && $pickupZoneId && $deliveryZoneId)
            ? $this->GetZonesCrossed($ratesheet->ratesheet_id, $pickupZoneId, $deliveryZoneId)
            : null;

        // Type-independent zone context (zone types, tags, names, zone-time maps) — built once.
        $zoneContext = $this->BuildZoneContext($pickupZone, $deliveryZone, $deliveryTypes, $zonesCrossed, $useInternalZonesCalc);

        $results = [];
        foreach($deliveryTypes as $deliveryType) {
            $defaultTime = isset($deliveryType['time']) ? (float) $deliveryType['time'] : 0;
            $timingContext = $this->BuildTimingContext($deliveryType, $zoneContext, $ratesheet, $zonesCrossed, $defaultTime);

            $deliveryType['time'] = $this->ApplyTimingConditionals($defaultTime, $timingConditionals, $timingContext);
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

        // Package charges are delivery-type independent — compute once and reuse for every type.
        $packageError = null;
        $packageCharges = [];
        try {
            $packageCharges = filter_var($req->package_is_minimum, FILTER_VALIDATE_BOOLEAN)
                ? []
                : $this->generatePackageCharges($ratesheet, $req->packages ?? [], $req->package_is_pallet, $req->use_imperial);
        } catch (\Throwable $e) {
            $packageError = $e->getMessage();
        }

        $results = [];
        foreach($deliveryTypes as $deliveryType) {
            $typeId = $deliveryType['id'] ?? null;
            $timeEntry = ($typeId !== null && isset($timeById[$typeId])) ? $timeById[$typeId] : null;
            $fallbackTime = isset($deliveryType['time']) ? (float) $deliveryType['time'] : null;

            $price = null;
            $reason = $packageError;
            $lineItems = [];

            if($packageError === null) {
                try {
                    $bill->delivery_type = $selectionsRepo->GetSelectionByTypeAndValue('delivery_type', $typeId);
                    $lineItems = $this->buildChargesForType($ratesheet, $bill, $packageCharges);
                    $price = 0;
                    foreach($lineItems as $lineItem)
                        $price += $lineItem->getPrice();
                    $price = round($price, 2);
                } catch (\Throwable $e) {
                    $price = null;
                    $reason = $e->getMessage();
                    $lineItems = [];
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

    private function ApplyTimingConditionals($originalTime, $timingConditionals, $timingContext) {
        if(!$timingConditionals || !count($timingConditionals))
            return $originalTime;

        $currentTime = $originalTime;

        usort($timingConditionals, function($a, $b) {
            $priorityA = isset($a['priority']) ? (int) $a['priority'] : 0;
            $priorityB = isset($b['priority']) ? (int) $b['priority'] : 0;
            return $priorityA <=> $priorityB;
        });

        foreach($timingConditionals as $conditional) {
            $rules = json_decode($conditional['json_logic'], true);
            if(!$rules)
                continue;

            if(JsonLogic::apply($rules, $timingContext)) {
                $action = json_decode($conditional['action']);
                $value = $this->ResolveTimingValue($conditional, $timingContext);

                if($action && $action->value === 'add')
                    $currentTime += $value;
                else
                    $currentTime = $value;

                $continueOnValue = $conditional['continue_on'] ?? null;
                if($continueOnValue === null) {
                    $continueOn = 1;
                } else {
                    $parsed = filter_var($continueOnValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $continueOn = $parsed === null ? ((int) $continueOnValue === 1 ? 1 : 0) : ($parsed ? 1 : 0);
                }

                if(!$continueOn)
                    return $currentTime;
            }
        }

        return $currentTime;
    }

    private function ResolveTimingValue($conditional, $timingContext) {
        if(($conditional['value_type'] ?? null) === 'field') {
            $resolved = $this->GetValueByPath($timingContext, $conditional['value'] ?? '');
            return is_numeric($resolved) ? (float) $resolved : 0;
        }

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
    private function BuildZoneContext($pickupZone, $deliveryZone, $deliveryTypes, $zonesCrossed, $useInternalZonesCalc) {
        $pickupZoneId = $pickupZone ? $pickupZone->zone_id : null;
        $deliveryZoneId = $deliveryZone ? $deliveryZone->zone_id : null;
        $zonesSame = $pickupZoneId && $deliveryZoneId ? $pickupZoneId === $deliveryZoneId : false;

        $pickupZoneType = $pickupZone ? $pickupZone->type : null;
        $deliveryZoneType = $deliveryZone ? $deliveryZone->type : null;
        $pickupIsInternal = $pickupZoneType === 'internal';
        $deliveryIsInternal = $deliveryZoneType === 'internal';
        $pickupIsExternal = $pickupZoneType && $pickupZoneType !== 'internal';
        $deliveryIsExternal = $deliveryZoneType && $deliveryZoneType !== 'internal';

        return [
            'zones_same' => $zonesSame,
            'zones_crossed' => $useInternalZonesCalc ? $zonesCrossed : null,
            'both_internal' => $pickupIsInternal && $deliveryIsInternal,
            'both_external' => $pickupIsExternal && $deliveryIsExternal,
            'one_internal_one_external' => ($pickupIsInternal && $deliveryIsExternal) || ($pickupIsExternal && $deliveryIsInternal),
            'ratesheet' => [
                'use_internal_zones_calc' => (bool) $useInternalZonesCalc
            ],
            'delivery_address' => [
                'zone_id' => $deliveryZoneId,
                'zone_type' => $deliveryZoneType,
                'is_internal' => $deliveryIsInternal,
                'is_external' => $deliveryIsExternal,
                'zone' => [
                    'name' => $deliveryZone ? $deliveryZone->name : null,
                    'region' => $deliveryZone ? $deliveryZone->region : null,
                    'tags' => $this->NormalizeZoneTags($deliveryZone ? $deliveryZone->tags : null),
                    'tags_text' => $deliveryZone && $deliveryZone->tags ? $deliveryZone->tags : '',
                ],
                'zone_time' => $this->BuildZoneTime($deliveryZone, $deliveryTypes)
            ],
            'pickup_address' => [
                'zone_id' => $pickupZoneId,
                'zone_type' => $pickupZoneType,
                'is_internal' => $pickupIsInternal,
                'is_external' => $pickupIsExternal,
                'zone' => [
                    'name' => $pickupZone ? $pickupZone->name : null,
                    'region' => $pickupZone ? $pickupZone->region : null,
                    'tags' => $this->NormalizeZoneTags($pickupZone ? $pickupZone->tags : null),
                    'tags_text' => $pickupZone && $pickupZone->tags ? $pickupZone->tags : '',
                ],
                'zone_time' => $this->BuildZoneTime($pickupZone, $deliveryTypes)
            ]
        ];
    }

    /**
     * Merges the type-independent zone context with the fields that depend on the delivery type
     * being priced (base time, selected-type zone times, internal-zone-distance time).
     */
    private function BuildTimingContext($deliveryType, $zoneContext, $ratesheet, $zonesCrossed, $defaultTime) {
        $selectedTypeKey = 'type_' . ($deliveryType['id'] ?? '');
        $pickupZoneTime = $zoneContext['pickup_address']['zone_time'];
        $deliveryZoneTime = $zoneContext['delivery_address']['zone_time'];

        $pickupSelectedTypeTime = is_numeric($pickupZoneTime[$selectedTypeKey] ?? null) ? (float) $pickupZoneTime[$selectedTypeKey] : 0;
        $deliverySelectedTypeTime = is_numeric($deliveryZoneTime[$selectedTypeKey] ?? null) ? (float) $deliveryZoneTime[$selectedTypeKey] : 0;

        $externalZoneTimeTotal = 0;
        if($zoneContext['pickup_address']['is_external'])
            $externalZoneTimeTotal += $pickupSelectedTypeTime;
        if($zoneContext['delivery_address']['is_external'])
            $externalZoneTimeTotal += $deliverySelectedTypeTime;

        $friendlyName = $deliveryType['friendlyName'] ?? $deliveryType['name'] ?? null;
        $internalZoneDistanceTime = $this->ResolveInternalZoneDistanceTime($ratesheet, $zonesCrossed, $friendlyName);

        return array_merge($zoneContext, [
            'delivery_type' => $deliveryType['id'] ?? null,
            'base_time' => [
                'default' => (float) $defaultTime,
            ],
            'timing_inputs' => [
                'external_zone_time_total' => $externalZoneTimeTotal,
                'default_plus_external_zone_time_total' => (float) $defaultTime + $externalZoneTimeTotal,
                'internal_zone_distance_time' => $internalZoneDistanceTime,
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

    private function ResolveInternalZoneDistanceTime($ratesheet, $zonesCrossed, $deliveryTypeFriendlyName) {
        if(!$ratesheet || !$deliveryTypeFriendlyName || !is_numeric($zonesCrossed) || !$ratesheet->zone_rates)
            return 0;

        $zoneRate = $this->GetZoneRateByDistance($ratesheet->zone_rates, $zonesCrossed);
        if(!$zoneRate)
            return 0;

        return $this->GetZoneRateTypedValue($zoneRate, $deliveryTypeFriendlyName, 'time');
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

    private function GetZoneRateTypedValue($zoneRate, $deliveryTypeFriendlyName, $field) {
        $typedRate = $zoneRate[$deliveryTypeFriendlyName] ?? null;
        if(!is_array($typedRate))
            return 0;

        $value = $typedRate[$field] ?? null;
        return is_numeric($value) ? (float) $value : 0;
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
     * If using internal zone crossed calculation, we must calculate how many zones have been crossed
     * We do this by creating a list of all zones as unvisited, then one that has been visited, and place the pickup location in the visited
     * Then systematically, we remove adjacent zones and add them to the visited list until we reach the destination,
     * at which point we can return the distance (number of zones required to be crossed)
     * 
     * @param $pickupZone - the complete zone object wherein the pickup will occur
     * @param $deliveryZone - as pickup zone but for delivery location
     * @param $ratesheet - the ratesheet against which to check pricing
     * @param $deliveryType - the deliveryType to check against the ratesheet for pricing
     */
    private function countZonesCrossed($pickupZone, $deliveryZone, $ratesheet, $deliveryType) {
        $distance = $this->GetZonesCrossed($ratesheet->ratesheet_id, $pickupZone->zone_id, $deliveryZone->zone_id);
        if($distance === null)
            return null;

        $zoneRate = $this->GetZoneRateByDistance($ratesheet->zone_rates, $distance);
        if(!$zoneRate)
            return null;

        $deliveryTypeFriendlyName = $deliveryType->name;
        $cost = $this->GetZoneRateTypedValue($zoneRate, $deliveryTypeFriendlyName, 'cost');

        return new LineItemModel(
            $deliveryTypeFriendlyName . ' - ' . $distance . ' zones',
            'distanceRate',
            $cost
        );
    }

    private function EvaluateFunctionalEquation($conditional, $mockBill) {
        $evaluator = new Evaluator();

        $evaluator->variables = ['total_weight' => $mockBill->package->total_weight];

        // Enable math functions to match frontend capabilities
        $evaluator->functions = [
            'max' => ['ref' => 'max', 'arc' => null],      // max(a, b, c, ...)
            'min' => ['ref' => 'min', 'arc' => null],      // min(a, b, c, ...)
            'round' => ['ref' => 'round', 'arc' => 2],     // round(number, precision)
            'abs' => ['ref' => 'abs', 'arc' => 1],         // abs(number)
            'sqrt' => ['ref' => 'sqrt', 'arc' => 1],       // sqrt(number)
            'pow' => ['ref' => 'pow', 'arc' => 2],         // pow(base, exponent)
        ];

        return $evaluator->execute($conditional->original_equation_string);
    }

    /**
     * Calculates which conditional charges apply by creating an object and comparing against stored json_logic data
     * @param $ratesheet - standard ratesheet object pulled from database
     * @param $req - the request, which will be used to generate a standard object for comparisons
     *
     * @return $results - an array of charges to add to the bill
     */
    private function generateConditionalCharges($ratesheet, $bill, $req, $currentPrice) {
        $conditionalRepo = new Repos\ConditionalRepo();

        $amountConditionals = $conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id, ['amount', 'equation']);
        $percentConditionals = $conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id, ['percent']);

        $results = array();

        foreach($amountConditionals as $conditional) {
            $rules = json_decode($conditional->json_logic, true);

            if(JsonLogic::apply($rules, $bill)) {
                $action = json_decode($conditional->action)->value;
                $valueType = $conditional->value_type;
                $value = $valueType == 'equation' ? $this->EvaluateFunctionalEquation($conditional, $bill) : $conditional->value;

                if($action == 'discount') {
                    if(($currentPrice - $value) > 0)
                        $currentPrice -= $value;
                    else
                        $currentPrice = 0;
                }
                else
                    $currentPrice += $value;

                $results[] = new LineItemModel($conditional->name, 'conditionalRate', $value);
            }
        }

        foreach($percentConditionals as $conditional) {
            $rules = json_decode($conditional->json_logic, true);

            if(JsonLogic::apply($rules, $bill)) {
                $action = json_decode($conditional->action)->value;
                $value = $conditional->value;

                $price = round($value / 100 * $currentPrice, 2);
                if($action == 'discount')
                    $price *= -1;

                $results[] = new LineItemModel($conditional->name, 'conditionalRate', $price);
            }
        }

        return $results;
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

        $results = array();
        $crossableZoneTypes = ['internal', 'peripheral'];

        $bothCrossable = in_array($bill->pickup_address->zone->type, $crossableZoneTypes)
            && in_array($bill->delivery_address->zone->type, $crossableZoneTypes);

        if($bothCrossable) {
            // Both zones are internal/peripheral — use zone-crossing distance rate
            if(filter_var($ratesheet->use_internal_zones_calc, FILTER_VALIDATE_BOOLEAN)) {
                $results[] = $this->countZonesCrossed($bill->pickup_address->zone, $bill->delivery_address->zone, $ratesheet, $bill->delivery_type);
            } else {
                $deliveryTypes = json_decode($ratesheet->delivery_types);
                $chargeDeliveryTypeIndex = array_search($bill->delivery_type->value, array_column($deliveryTypes, 'id'));
                $chargeDeliveryType = $chargeDeliveryTypeIndex !== false ? $deliveryTypes[$chargeDeliveryTypeIndex] : null;
                if($chargeDeliveryType)
                    $results[] = new LineItemModel($bill->delivery_type->name, 'distanceRate', $chargeDeliveryType->cost);
            }
        } else {
            // At least one zone is outlying — always charge the flat delivery type base cost,
            // since there is no zone-crossing rate to fall back to. Zone modifier surcharges
            // (below) handle the additional cost for the non-internal zone.
            $deliveryTypes = json_decode($ratesheet->delivery_types);
            $chargeDeliveryTypeIndex = array_search($bill->delivery_type->value, array_column($deliveryTypes, 'id'));
            $chargeDeliveryType = $chargeDeliveryTypeIndex !== false ? $deliveryTypes[$chargeDeliveryTypeIndex] : null;
            if($chargeDeliveryType)
                $results[] = new LineItemModel($bill->delivery_type->name, 'distanceRate', $chargeDeliveryType->cost);
        }

        /**
         * If the pickup or delivery is in a peripheral or outlying zone, additional surcharges apply
         */
        if($bill->pickup_address->zone->type != 'internal') {
            $pickupModifiers = (array) ($bill->pickup_address->zone->delivery_type_modifiers ?? []);
            $pickupModifier = $pickupModifiers[$bill->delivery_type->name] ?? null;
            $pickupCost = $pickupModifier && isset($pickupModifier->cost) && is_numeric($pickupModifier->cost)
                ? (float) $pickupModifier->cost
                : 0;
            if($pickupCost > 0)
                $results[] = new LineItemModel(ucfirst($bill->pickup_address->zone->type) . ' Zone: ' . $bill->pickup_address->zone->name, 'distanceRate', $pickupCost);
        }

        if($bill->delivery_address->zone->type != 'internal') {
            $deliveryModifiers = (array) ($bill->delivery_address->zone->delivery_type_modifiers ?? []);
            $deliveryModifier = $deliveryModifiers[$bill->delivery_type->name] ?? null;
            $deliveryCost = $deliveryModifier && isset($deliveryModifier->cost) && is_numeric($deliveryModifier->cost)
                ? (float) $deliveryModifier->cost
                : 0;
            if($deliveryCost > 0)
                $results[] = new LineItemModel(ucfirst($bill->delivery_address->zone->type) . ' Zone: ' . $bill->delivery_address->zone->name, 'distanceRate', $deliveryCost);
        }

        return $results;
    }

    private function generateTimeCharges($ratesheet, $timePickupScheduled, $timeDeliveryScheduled) {
        $results = array();
        $timePickupScheduled = new \DateTime($timePickupScheduled);
        $timeDeliveryScheduled = new \DateTime($timeDeliveryScheduled);
        foreach(json_decode($ratesheet->time_rates) as $timeRate)
            foreach($timeRate->brackets as $bracket) {
                try {
                    $startDate = new \DateTime($bracket->startTime ?? null);
                    $endDate = new \DateTime($bracket->endTime ?? null);
                } catch (\Exception $e) {
                    $startDate = false;
                    $endDate = false;
                }

                if(!$startDate || !$endDate || empty($bracket->startDayOfWeek->label) || empty($bracket->endDayOfWeek->label)) {
                    $context = [
                        'ratesheet_id' => $ratesheet->ratesheet_id ?? null,
                        'time_rate_name' => $timeRate->name ?? null,
                        'bracket' => $bracket,
                        'startTime' => $bracket->startTime ?? null,
                        'endTime' => $bracket->endTime ?? null,
                        'startDayOfWeek' => $bracket->startDayOfWeek ?? null,
                        'endDayOfWeek' => $bracket->endDayOfWeek ?? null,
                    ];

                    Log::error('Invalid time rate bracket datetime format', $context);
                    Log::channel('discord')->error('Invalid time rate bracket datetime format', $context);
                    continue;
                }

                $startTime = $startDate->getTimestamp();
                $endTime = $endDate->getTimestamp();
                $closestStart = clone $timePickupScheduled;
                $closestStart->modify('-4 days')->modify('next ' . $bracket->startDayOfWeek->label)->setTime((int)date('H', $startTime), (int)date('i', $startTime));
                $closestEnd = clone $timeDeliveryScheduled;
                $closestEnd->modify('-4 days')->modify('next ' . $bracket->endDayOfWeek->label)->setTime((int)date('H', $endTime), (int)date('i', $endTime));
                // $results[] = [
                //     'timePickupScheduled' => $timePickupScheduled,
                //     'timeDeliveryScheduled' => $timeDeliveryScheduled,
                //     'closestStart' => $closestStart,
                //     'closestEnd' => $closestEnd,
                //     'startDayOfWeek' => $bracket->startDayOfWeek->label,
                //     'endDayOfWeek' => $bracket->endDayOfWeek->label,
                //     'pickupTime <= closestStart' => $timePickupScheduled >= $closestStart,
                //     'pickupTime >= closestEnd' => $timePickupScheduled <= $closestEnd,
                //     'deliveryTime <= closestStart' => $timeDeliveryScheduled >= $closestStart,
                //     'deliveryTime >= closestEnd' => $timeDeliveryScheduled <= $closestEnd,
                //     'startTime' => $startTime,
                //     'endTime' => $endTime
                // ];
                if(($timePickupScheduled >= $closestStart && $timePickupScheduled <= $closestEnd) || ($timeDeliveryScheduled >= $closestStart && $timeDeliveryScheduled <= $closestEnd)) {
                    $results[] = new LineItemModel($timeRate->name, 'timeRate', $timeRate->price);
                }
            }

        return $results;
    }

    /**
     * Package charges includes such things as weight rates, pallet rates, etc.
     * ALL calculations are done in terms of kilograms, so weight is converted to kilograms if entered in lbs
     */
    private function generatePackageCharges($ratesheet, $packages, $packageIsPallet, $useImperial) {
        $weightRates = json_decode($ratesheet->weight_rates);
        $basicWeightRateIndex = array_search('Basic Weight Rate', array_column($weightRates, 'name'));
        $palletWeightRateIndex = array_search('Pallet Weight Rate', array_column($weightRates, 'name'));
        $weightRate = filter_var($packageIsPallet, FILTER_VALIDATE_BOOLEAN) ? $weightRates[$palletWeightRateIndex] : $weightRates[$basicWeightRateIndex];

        $totalWeight = 0;
        $results = array();
        $maxWidth = 0;
        $maxLength = 0;
        $maxHeight = 0;

        foreach($packages as $package) {
            $maxLength = $package['length'] > $maxLength ? $package['length'] : $maxLength;
            $maxWidth = $package['width'] > $maxWidth ? $package['width'] : $maxWidth;
            $maxHeight = $package['height'] > $maxHeight ? $package['height'] : $maxHeight;

            $totalWeight += ($package['weight'] * $package['count']);
        }

        if(filter_var($useImperial, FILTER_VALIDATE_BOOLEAN))
            $totalWeight *= 0.453592;

        foreach($weightRate->brackets as $key => $bracket) {
            $kgmin = $key ? $weightRate->brackets[$key - 1]->kgmax : 0;
            if($totalWeight > $kgmin && $totalWeight < $bracket->kgmax) {
                $results[] = new LineItemModel($weightRate->name, 'weightRate', $bracket->basePrice);
                break;
            }
            if($bracket->additionalXKgs && $key == count($weightRate->brackets) - 1) {
                $overageWeight = $totalWeight - $bracket['kgmax'];
                $overageCharge = $overageWeight / $bracket->additionalXKgs * $bracket->incrementalPrice;
                $results[] = new LineItemModel($weightRate->name, 'weightRate', $bracket->basePrice + $overageCharge);
                break;
            }
            if($key == count($weightRate->brackets))
                abort(400, 'Weight exceeds available limits, or was input incorrectly. Please price manually');
        }
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

        if($req->use_imperial) {
            $weight = new Mass($bill->package->total_weight, 'pounds');
            $bill->package->total_weight = $weight->toUnit('kg');
        }

        return $bill;
    }

    private function prepareZone($zone) {
        return (object) [
            'zone_id' => $zone->zone_id,
            'name' => $zone->name,
            'delivery_type_modifiers' => json_decode($zone->delivery_type_modifiers),
            'region' => $zone->region ?? null,
            'tags' => $zone->tags ?? null,
            'type' => $zone->type,
            'neighbours' => $zone->neighbours
        ];
    }
}
