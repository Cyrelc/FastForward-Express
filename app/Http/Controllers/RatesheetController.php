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

    public function index(Request $req) {
        return view('ratesheets.ratesheets');
    }

    public function buildTable() {
        $ratesheetModelFactory = new Ratesheet\RatesheetModelFactory();
        $model = $ratesheetModelFactory->ListAll();
        return json_encode($model);
    }

    public function create() {
        //Check permissions
        return view('ratesheets.ratesheet');
    }

    public function edit($id) {
        //Check permissions
        return view('ratesheets.ratesheet');
    }

    private function findNeighbours($mapZones) {
        //TODO: only test against zones with type === "internal"
        $neighbours = array();
        foreach($mapZones as $zone1) {
            if($zone1['type'] === 'internal' || $zone1['type'] === 'peripheral') {
            foreach($mapZones as $zone2) {
                if($zone2['type'] === 'internal' || $zone2['type'] === 'peripheral') {
                    if($zone1['id'] === $zone2['id'] || (array_key_exists($zone1['id'], $neighbours) && in_array($zone2['id'], $neighbours[$zone1['id']])) || (array_key_exists($zone2['id'], $neighbours) && in_array($zone1['id'], $neighbours[$zone2['id']])))
                        continue;
                    $count = 0;
                    foreach(json_decode($zone1['coordinates']) as $coord1)
                        foreach(json_decode($zone2['coordinates']) as $coord2) {
                            if($coord1->lat === $coord2->lat && $coord1->lng === $coord2->lng)
                                $count++;
                                //TO DO: this may be able to be simplified (corners are a thing)
                            if($count === 1) {
                                array_key_exists($zone1['id'], $neighbours) ? $neighbours[$zone1['id']][] = $zone2['id'] : $neighbours[$zone1['id']] = [$zone2['id']];
                                array_key_exists($zone2['id'], $neighbours) ? $neighbours[$zone2['id']][] = $zone1['id'] : $neighbours[$zone2['id']] = [$zone1['id']];
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        foreach($mapZones as $key => $zone)
            if($zone['type'] === 'internal' || $zone['type'] === 'peripheral')
                $mapZones[$key]['neighbours'] = array_key_exists($zone['id'], $neighbours) ? $neighbours[$zone['id']] : null;
        return $mapZones;
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

            if(sizeof($req->mapZones) > 1)
                $req->mapZones = $this->findNeighbours($req->mapZones);

            $ratesheetCollector = new Collectors\RatesheetCollector();
            $ratesheet = $ratesheetCollector->Collect($req);

            $ratesheetRepo = new Repos\RatesheetRepo();
            if($isEdit)
                $ratesheetRepo->Update($ratesheet);
            else
                $ratesheetRepo->Insert($ratesheet);
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
