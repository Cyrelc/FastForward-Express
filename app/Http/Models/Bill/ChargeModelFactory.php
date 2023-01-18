<?php

namespace App\Http\Models\Bill;

use App\Http\Models;
use App\Http\Repos;
use App\Http\MapLogic;
use Illuminate\Support\Facades\Log;
use JWadhams\JsonLogic;

class ChargeModelFactory {
    public function GenerateCharges($req) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($req->ratesheet_id);

        $distanceCharges = $this->generateDistanceCharges($ratesheet, $req->pickup_address, $req->delivery_address, $req->delivery_type_id);
        $packageCharges = filter_var($req->package_is_minimum, FILTER_VALIDATE_BOOLEAN) ? [] : $this->generatePackageCharges($ratesheet, $req->packages, $req->package_is_pallet, $req->use_imperial);
        $timeCharges = $this->generateTimeCharges($ratesheet, $req->time_pickup_scheduled, $req->time_delivery_scheduled);

        $currentCharges = array_merge($distanceCharges, $packageCharges, $timeCharges);
        $currentPrice = 0;
        foreach($currentCharges as $charge) {
            $currentPrice += $charge->getPrice();
        }

        $conditionalCharges = $this->generateConditionalCharges($ratesheet, $req, $currentPrice);

        return array_merge($distanceCharges, $packageCharges, $timeCharges, $conditionalCharges);
    }

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
     * Calculates which conditional charges apply by creating an object and comparing against stored json_logic data
     * @param $ratesheet - standard ratesheet object pulled from database
     * @param $req - the request, which will be used to generate a standard object for comparisons
     *
     * @return $results - an array of charges to add to the bill
     */
    private function generateConditionalCharges($ratesheet, $req, $currentPrice) {
        $conditionalRepo = new Repos\ConditionalRepo();

        $amountConditionals = $conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id, 'amount');
        $percentConditionals = $conditionalRepo->GetByRatesheetId($ratesheet->ratesheet_id, 'percent');

        $pickupZone = $this->GetZone($ratesheet->ratesheet_id, $req->pickup_address['lat'], $req->pickup_address['lng']);
        $deliveryZone = $this->GetZone($ratesheet->ratesheet_id, $req->delivery_address['lat'], $req->delivery_address['lng']);

        $bill = [
            'delivery_address' => [
                'zone_type' => $deliveryZone->type
            ],
            'package' => [
                'is_pallet' => filter_var($req->package_is_pallet, FILTER_VALIDATE_BOOLEAN)
            ],
            'pickup_address' => [
                'zone_type' => $pickupZone->type
            ],
        ];

        $results = array();

        foreach($amountConditionals as $conditional) {
            $rules = json_decode($conditional->json_logic, true);

            if(JsonLogic::apply($rules, $bill)) {
                $action = json_decode($conditional->action)->value;
                $valueType = json_decode($conditional->value_type)->value;

                if($action == 'discount') {
                    if(($currentPrice - $conditional->value) > 0)
                        $currentPrice -= $conditional->value;
                    else
                        $currentPrice = 0;
                }
                else
                    $currentPrice += $conditional->value;

                $results[] = new LineItemModel($conditional->name, 'conditionalRate', $conditional->value);
            }
        }

        foreach($percentConditionals as $conditional) {
            $rules = json_decode($conditional->json_logic, true);

            if(JsonLogic::apply($rules, $bill)) {
                $action = json_decode($conditional->action)->value;
                $valueType = json_decode($conditional->value_type)->value;

                $price = number_format(round($conditional->value / 100 * $currentPrice, 2), 2);
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

    private function generateDistanceCharges($ratesheet, $pickupAddress, $deliveryAddress, $deliveryType) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $zones = $ratesheetRepo->GetMapZones($ratesheet->ratesheet_id);

        $pointLocation = new MapLogic\PointLocation;

        $pickupCoordinates = $pickupAddress['lat'] . ' ' . $pickupAddress['lng'];
        $deliveryCoordinates = $deliveryAddress['lat'] . ' ' . $deliveryAddress['lng'];

        /**
         * Find the zones containing the pickup and delivery locations
         */
        $pickupZone = $this->GetZone($ratesheet->ratesheet_id, $pickupAddress['lat'], $pickupAddress['lng']);
        $deliveryZone = $this->GetZone($ratesheet->ratesheet_id, $deliveryAddress['lat'], $deliveryAddress['lng']);

        /**
         * If one or both requests are outside of a programmed deliverable area, then we throw an exception: The system cannot automatically calculate the pricing, this must be done manually
         */
        if(!$pickupZone)
            abort(400, 'Requested pickup was not in a designated zone! Please price manually');
        else if (!$deliveryZone)
            abort(400, 'Requested delivery was not in a designated zone! Please price manually');

        $results = array();
        $crossableZoneTypes = ['internal', 'peripheral'];

        if(in_array($pickupZone->type, $crossableZoneTypes) && in_array($deliveryZone->type, $crossableZoneTypes))
            if(filter_var($ratesheet->use_internal_zones_calc, FILTER_VALIDATE_BOOLEAN))
                $results[] = $this->countZonesCrossed($pickupZone, $deliveryZone, $zones, $ratesheet, $deliveryType);
            else {
                $deliveryTypeFriendlyName = $selectionsRepo->GetSelectionByTypeAndValue('delivery_type', $deliveryType)->name;
                $deliveryTypes = json_decode($ratesheet->delivery_types);
                $chargeDeliveryTypeIndex = array_search($deliveryType, array_column($deliveryTypes, 'id'));
                $chargeDeliveryType = $deliveryTypes[$chargeDeliveryTypeIndex];

                $results[] = new LineItemModel($deliveryTypeFriendlyName, 'distanceRate', $chargeDeliveryType->cost);
            }
        /**
         * If the pickup or delivery is in a peripheral or outlying zone, additional charges apply
         */
        if($pickupZone->type == 'peripheral')
            $results[] = new LineItemModel('Peripheral Zone: ' . $pickupZone->zone_name, 'distanceRate', $pickupZone->additional_costs->regular);
        else if ($pickupZone->type == 'outlying')
            $results[] = new LineItemModel('Outlying Zone: ' . $pickupZone->zone_name, 'distanceRate', $pickupZone->additional_costs->$deliveryType);

        if($deliveryZone->type == 'peripheral')
            $results[] = new LineItemModel('Peripheral Zone: ' . $deliveryZone->zone_name, 'distanceRate', $deliveryZone->additional_costs->regular);
        else if ($deliveryZone->type == 'outlying')
            $results[] = new LineItemModel('Outlying Zone: ' . $deliveryZone->zone_name, 'distanceRate', $deliveryZone->additional_costs->$deliveryType);

        return $results;
    }

    private function generateTimeCharges($ratesheet, $timePickupScheduled, $timeDeliveryScheduled) {
        $results = array();
        $timePickupScheduled = \DateTime::createFromFormat('D M d Y H:i:s e+', $timePickupScheduled);
        $timeDeliveryScheduled = \DateTime::createFromFormat('D M d Y H:i:s e+', $timeDeliveryScheduled);
        foreach(json_decode($ratesheet->time_rates) as $timeRate)
            foreach($timeRate->brackets as $bracket) {
                $startTime = date_timestamp_get(\DateTime::createFromFormat('D M d Y H:i:s e+', $bracket->startTime));
                $endTime = date_timestamp_get(\DateTime::createFromFormat('D M d Y H:i:s e+', $bracket->endTime));
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
        foreach($packages as $package)
            $totalWeight += ($package['weight'] * $package['count']);

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

    /**
     * If using internal zone crossed calculation, we must calculate how many zones have been crossed
     * We do this by creating a list of all zones as unvisited, then one that has been visited, and place the pickup location in the visited
     * Then systematically, we remove adjacent zones and add them to the visited list until we reach the destination,
     * at which point we can return the distance (number of zones required to be crossed)
     * 
     * @param $pickupZone - the complete zone object wherein the pickup will occur
     * @param $deliveryZone - as pickup zone but for delivery location
     * @param $zones - the list of zones pertaining to this ratesheet, as they have already been gathered by the calling function no need to make a second database call
     * @param $ratesheet - the ratesheet against which to check pricing
     * @param $deliveryType - the deliveryType to check against the ratesheet for pricing
     * 
     */

    private function countZonesCrossed($pickupZone, $deliveryZone, $ratesheetId, $deliveryType) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $zones = $ratesheetRepo->GetMapZones($ratesheetId);

        $unvisitedSet = $zones->toArray();
        $startIndex = array_search($pickupZone->zone_id, array_column($unvisitedSet, 'zone_id'));
        $visitedSet = array();
        $visitedSet[] = $unvisitedSet[$startIndex];
        $visitedSet[0]['distance'] = $distance = 1;
        unset($unvisitedSet[$startIndex]);

        while(!empty($unvisitedSet) && !in_array($deliveryZone->zone_id, array_column($visitedSet, 'zone_id'))) {
            activity('system_debug')->log('unvisitedset count:' . count($unvisitedSet));
            foreach($visitedSet as $visitedZone)
                if($visitedZone['distance'] === $distance && isset($visitedZone['neighbours'])) {
                    foreach(json_decode($visitedZone['neighbours']) as $neighbourZoneId) {
                        $neighbourZoneIndex = array_search($neighbourZoneId, array_column($unvisitedSet, 'zone_id'));
                        if($neighbourZoneIndex) {
                            $unvisitedSet[$neighbourZoneIndex]['distance'] = $distance + 1;
                            $visitedSet[] = ($unvisitedSet[$neighbourZoneIndex]);
                            unset($unvisitedSet[$neighbourZoneIndex]);
                        }
                    }
                }
            $distance++;
        }

        $zoneRates = json_decode($ratesheet->zone_rates);
        $zoneRateId = array_search($distance, array_column($zoneRates, 'zones'));
        $zoneRate = $zoneRates[$zoneRateId];
        $deliveryTypeFriendlyName = $selectionsRepo->GetSelectionByTypeAndValue('delivery_type', $deliveryType)->name;
        $deliveryType .= '_cost';

        return new LineItemModel($deliveryTypeFriendlyName . ' - ' . $distance . ' zones', 'distanceRate', $zoneRate->$deliveryType);
    }

    private function prepareZone($zone) {
        return (object) [
            'zone_id' => $zone->zone_id,
            'zone_name' => $zone->name,
            'additional_costs' => json_decode($zone->additional_costs),
            'additional_time' => $zone->additional_time,
            'type' => $zone->type,
            'neighbours' => $zone->neighbours
        ];
    }
}
