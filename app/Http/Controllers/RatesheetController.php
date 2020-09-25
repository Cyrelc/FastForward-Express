<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Collectors;
use App\Http\Repos;
use App\Http\Models\Ratesheet;
use \App\Http\Validation\Utils;
use \App\Http\Validation;

class RatesheetController extends Controller {

    public function buildTable() {
        $ratesheetModelFactory = new Ratesheet\RatesheetModelFactory();
        $model = $ratesheetModelFactory->ListAll();
        return json_encode($model);
    }

    private function updateNeighbours($ratesheetId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $selectionsRepo = new Repos\SelectionsRepo();

        $mapZones = $ratesheetRepo->GetMapZones($ratesheetId);
        $neighbours = array();
        foreach($mapZones as $zone1) {
            if($zone1->type === 'internal' || $zone1->type === 'peripheral') {
                foreach($mapZones as $zone2) {
                    if($zone2->type === 'internal' || $zone2->type === 'peripheral') {
                        if($zone1->zone_id === $zone2->zone_id || (array_key_exists($zone1->zone_id, $neighbours) && in_array($zone2->zone_id, $neighbours[$zone1->zone_id])) || (array_key_exists($zone2->zone_id, $neighbours) && in_array($zone1->zone_id, $neighbours[$zone2->zone_id])))
                            continue;
                        $count = 0;
                        foreach(json_decode($zone1->coordinates) as $coord1) {
                            foreach(json_decode($zone2->coordinates) as $coord2) {
                                if($coord1->lat === $coord2->lat && $coord1->lng === $coord2->lng) {
                                    array_key_exists($zone1->zone_id, $neighbours) ? $neighbours[$zone1->zone_id][] = $zone2->zone_id : $neighbours[$zone1->zone_id] = [$zone2->zone_id];
                                    array_key_exists($zone2->zone_id, $neighbours) ? $neighbours[$zone2->zone_id][] = $zone1->zone_id : $neighbours[$zone2->zone_id] = [$zone1->zone_id];
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }
        foreach($mapZones as $key => $zone)
            if($zone->type === 'internal' || $zone->type === 'peripheral')
                $ratesheetRepo->SetZoneNeighbours($zone->zone_id, array_key_exists($zone->zone_id, $neighbours) ? json_encode($neighbours[$zone->zone_id]) : null);
    }

    public function getModel(Request $req, $id = null) {
        $modelFactory = new Ratesheet\RatesheetModelFactory();
        if($id)
            $ratesheetModel = $modelFactory->GetEditModel($id);
        else
            $ratesheetModel = $modelFactory->GetCreateModel();
        return json_encode($ratesheetModel);
    }

    public function store(Request $req) {
        DB::beginTransaction();
        try {
            $validation = new Validation\RatesheetValidationRules();
            $ratesheetRules = $validation->GetValidationRules($req);

            $this->validate($req, $ratesheetRules['rules'], $ratesheetRules['messages']);

            if($req->ratesheetId === '') {
                //Can I create this?
                $isEdit = false;
            } else {
                //Can I edit this?
                $isEdit = true;
            }

            $ratesheetCollector = new Collectors\RatesheetCollector();
            $zoneCollector = new Collectors\ZoneCollector();
            $ratesheet = $ratesheetCollector->Collect($req);

            $ratesheetRepo = new Repos\RatesheetRepo();
            if($isEdit)
                $ratesheetId = $ratesheetRepo->Update($ratesheet)->ratesheet_id;
            else
                $ratesheetId = $ratesheetRepo->Insert($ratesheet)->ratesheet_id;

            // handle zone changes
            $zones = $zoneCollector->Collect($req->mapZones, $ratesheetId);
            foreach($zones as $zone)
                if($zone['zone_id'])
                    $ratesheetRepo->UpdateZone($zone);
                else
                    $ratesheetRepo->InsertZone($zone);

            $mapZones = $ratesheetRepo->GetMapZones($ratesheetId);

            foreach($mapZones as $mapZone) {
                foreach($zones as $index => $zone) {
                    if($zone['zone_id'] == $mapZone->zone_id || $zone['zone_id'] == null) {
                        break;
                    } else if($index == sizeof($zones) - 1) {
                        $ratesheetRepo->DeleteZone($mapZone->zone_id);
                    }
                }
            }

            $this->updateNeighbours($ratesheetId);

            DB::commit();
        } catch(Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
