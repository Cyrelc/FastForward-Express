<?php

namespace App\Http\Models\Bill;

use App\Http\Models;
use App\Http\Repos;
use App\Http\MapLogic;
use Illuminate\Support\Facades\Log;

class ChargeModelFactory {
    public function GenerateCharges($req) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($req->ratesheet_id);

        $distanceCharges = $this->generateDistanceCharges($ratesheet, $req->pickup_address, $req->delivery_address);
        dd($distanceCharges);
        $packageCharges = array();
        $timeCharges = array();

        return array_merge($distanceCharges, $packageCharges, $timeCharges);
    }

    /**
     * 
     */

    private function generateDistanceCharges($ratesheet, $pickupAddress, $deliveryAddress) {
        $ratesheetRepo = new Repos\RatesheetRepo();

        $zones = $ratesheetRepo->GetMapZones($ratesheet->ratesheet_id);

        $pointLocation = new MapLogic\PointLocation;

        $pickupCoordinates = $pickupAddress['lat'] . ' ' . $pickupAddress['lng'];
        $deliveryCoordinates = $deliveryAddress['lat'] . ' ' . $deliveryAddress['lng'];

        $pickupZone = null;
        $deliveryZone = null;

        foreach($zones as $key => $zone) {
            $jsonCoordinates = json_decode($zone['coordinates']);
            $coordinateArray = array();
            foreach($jsonCoordinates as $coordinate)
                $coordinateArray[] = $coordinate->lat . ' ' . $coordinate->lng;
            if($coordinateArray[0] != end($coordinateArray))
                $coordinateArray[] = $coordinateArray[0];

            if($pickupZone == null) {
                if($pointLocation->pointInPolygon($pickupCoordinates, $coordinateArray) == 'inside')
                    $pickupZone = ['zone_id' => $zone->zone_id, 'zone_name' => $zone->name, 'additional_costs' => $zone->additional_costs, 'additional_time' => $zone->additional_time, 'type' => $zone->type, 'neighbours' => $zone->neighbours];
            }
            if($deliveryZone == null) {
                if($pointLocation->pointInPolygon($deliveryCoordinates, $coordinateArray) == 'inside')
                    $deliveryZone = ['zone_id' => $zone->zone_id, 'zone_name' => $zone->name, 'additional_costs' => $zone->additional_costs, 'additional_time' => $zone->additional_time, 'type' => $zone->type, 'neighbours' => $zone->neighbours];
            }
            if($pickupZone && $deliveryZone)
                break;
        }

        if(filter_var($ratesheet->use_internal_zones_calc, FILTER_VALIDATE_BOOLEAN)) {
            // $unvisitedSet = array();
            // foreach($zones as $zone)
            //     $unvisitedSet[] = ['zone_id' => $zone->zone_id, 'neighbours' => $zone->neighbours, 'tentative_distance' => $zone->zone_id == $pickupZone->zone_id ? 0 : null];
                
            return ['pickup_zone' => $pickupZone, 'delivery_zone' => $deliveryZone];
        } else {
            return ['pickup_zone' => $pickupZone, 'delivery_zone' => $deliveryZone];
        }
    }

    private function generateTimeCharges($ratesheet, $timePickupScheduled, $timeDeliveryScheduled) {

    }

    private function generatePackageCharges($ratesheet, $packages, $packageIsMinimum, $packageIsPallet) {

    }
}
