<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Collectors;
use App\Http\Repos;
use App\Http\Models\Ratesheet;
use \App\Http\Validation\Utils;
use \App\Http\Validation;
use App\Http\Models\Bill\ChargeModelFactory;


class RatesheetController extends Controller {
    public function buildTable(Request $req) {
        if($req->user()->cannot('viewAny', Ratesheet::class))
            abort(403);

        $ratesheetModelFactory = new Ratesheet\RatesheetModelFactory();
        $model = $ratesheetModelFactory->ListAll();
        return json_encode($model);
    }

    public function deleteConditional(Request $req, $conditionalId) {
        $conditionalRepo = new Repos\ConditionalRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();

        $conditional = $conditionalRepo->GetById($conditionalId);
        $ratesheet = $ratesheetRepo->GetById($conditional->ratesheet_id);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        $conditionalRepo->Delete($conditionalId);

        return response()->json([
            'success' => true
        ]);
    }

    public function deleteTimingConditional(Request $req, $timingConditionalId) {
        $timingConditionalRepo = new Repos\TimingConditionalRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();

        $timingConditional = $timingConditionalRepo->GetById($timingConditionalId);
        $ratesheet = $ratesheetRepo->GetById($timingConditional->ratesheet_id);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        $timingConditionalRepo->Delete($timingConditionalId);

        return response()->json([
            'success' => true
        ]);
    }

    public function getModel(Request $req, $ratesheetId = null) {
        $modelFactory = new Ratesheet\RatesheetModelFactory();
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        if($ratesheetId)
            $ratesheetModel = $modelFactory->GetEditModel($ratesheetId);
        else
            $ratesheetModel = $modelFactory->GetCreateModel();

        return json_encode($ratesheetModel);
    }

    public function getConditional(Request $req, $conditionalId) {
        $conditionalRepo = new Repos\ConditionalRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();

        $conditional = $conditionalRepo->GetById($conditionalId);
        $ratesheet = $ratesheetRepo->GetById($conditional->ratesheet_id);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        return json_encode($conditional);
    }

    public function getTimingConditional(Request $req, $timingConditionalId) {
        $timingConditionalRepo = new Repos\TimingConditionalRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();

        $timingConditional = $timingConditionalRepo->GetById($timingConditionalId);
        $ratesheet = $ratesheetRepo->GetById($timingConditional->ratesheet_id);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        return json_encode($timingConditional);
    }

    public function getZone(Request $req, $ratesheetId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($ratesheetId);

        if($req->user()->cannot('getChargesFrom', $ratesheet))
            abort(403);

        $validation = new Validation\RatesheetValidationRules();
        $zoneRequestValidation = $validation->GetZoneRequestValidationRules($req);

        $this->validate($req, $zoneRequestValidation['rules'], $zoneRequestValidation['messages']);

        $chargeModelFactory = new ChargeModelFactory();

        return $chargeModelFactory->GetZone($ratesheetId, $req->lat, $req->lng);
    }

    public function getZonesCrossed(Request $req, $ratesheetId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($ratesheetId);

        if($req->user()->cannot('getChargesFrom', $ratesheet))
            abort(403);

        $this->validate($req, [
            'pickup_zone_id' => 'required|numeric',
            'delivery_zone_id' => 'required|numeric'
        ]);

        if(!filter_var($ratesheet->use_internal_zones_calc, FILTER_VALIDATE_BOOLEAN)) {
            return response()->json([
                'zones_crossed' => null
            ]);
        }

        $chargeModelFactory = new ChargeModelFactory();
        $zonesCrossed = $chargeModelFactory->GetZonesCrossed($ratesheetId, $req->pickup_zone_id, $req->delivery_zone_id);

        return response()->json([
            'zones_crossed' => $zonesCrossed
        ]);
    }

    public function listConditionals(Request $req, $ratesheetId) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $conditionalRepo = new Repos\ConditionalRepo();

        $conditionals = $conditionalRepo->GetByRatesheetId($ratesheetId);

        return json_encode($conditionals);
    }

    public function listZoneRegions(Request $req) {
        if($req->user()->cannot('appSettings.edit.*.*'))
            abort(403);

        $ratesheetRepo = new Repos\RatesheetRepo();

        return response()->json($ratesheetRepo->ListZoneRegions()->values());
    }

    public function importConditionals(Request $req) {
        $this->validate($req, [
            'target_ratesheet_id' => 'required|numeric',
            'source_ratesheet_id' => 'required|numeric',
            'conditional_ids' => 'required|array'
        ]);

        $ratesheetRepo = new Repos\RatesheetRepo();
        $targetRatesheet = $ratesheetRepo->GetById($req->target_ratesheet_id);

        if($req->user()->cannot('update', $targetRatesheet))
            abort(403);

        $conditionalRepo = new Repos\ConditionalRepo();
        $replace = filter_var($req->replace, FILTER_VALIDATE_BOOLEAN);

        DB::beginTransaction();

        foreach($req->conditional_ids as $conditionalId) {
            $conditional = $conditionalRepo->GetById($conditionalId);
            if(!$conditional || $conditional->ratesheet_id != $req->source_ratesheet_id)
                continue;

            if($replace) {
                $existing = $conditionalRepo->GetByRatesheetIdAndName($req->target_ratesheet_id, $conditional->name);
                if($existing)
                    $conditionalRepo->Delete($existing->conditional_id);
            }

            $clone = $conditional->toArray();
            unset($clone['conditional_id']);
            $clone['ratesheet_id'] = $req->target_ratesheet_id;

            $conditionalRepo->Insert($clone);
        }

        DB::commit();

        return response()->json([
            'success' => true
        ]);
    }

    public function listTimingConditionals(Request $req, $ratesheetId) {
        $timingConditionalRepo = new Repos\TimingConditionalRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();

        $ratesheet = $ratesheetRepo->GetById($ratesheetId);

        if($req->user()->cannot('appSettings.edit.*.*') && $req->user()->cannot('getChargesFrom', $ratesheet))
            abort(403);

        $timingConditionals = $timingConditionalRepo->GetByRatesheetId($ratesheetId);

        return json_encode($timingConditionals);
    }

    public function importTimingConditionals(Request $req) {
        $this->validate($req, [
            'target_ratesheet_id' => 'required|numeric',
            'source_ratesheet_id' => 'required|numeric',
            'timing_conditional_ids' => 'required|array'
        ]);

        $ratesheetRepo = new Repos\RatesheetRepo();
        $targetRatesheet = $ratesheetRepo->GetById($req->target_ratesheet_id);

        if($req->user()->cannot('update', $targetRatesheet))
            abort(403);

        $timingConditionalRepo = new Repos\TimingConditionalRepo();
        $replace = filter_var($req->replace, FILTER_VALIDATE_BOOLEAN);

        DB::beginTransaction();

        foreach($req->timing_conditional_ids as $timingConditionalId) {
            $timingConditional = $timingConditionalRepo->GetById($timingConditionalId);
            if(!$timingConditional || $timingConditional->ratesheet_id != $req->source_ratesheet_id)
                continue;

            if($replace) {
                $existing = $timingConditionalRepo->GetByRatesheetIdAndName($req->target_ratesheet_id, $timingConditional->name);
                if($existing)
                    $timingConditionalRepo->Delete($existing->timing_conditional_id);
            }

            $clone = $timingConditional->toArray();
            unset($clone['timing_conditional_id']);
            $clone['ratesheet_id'] = $req->target_ratesheet_id;

            $timingConditionalRepo->Insert($clone);
        }

        DB::commit();

        return response()->json([
            'success' => true
        ]);
    }

    public function store(Request $req) {
        DB::beginTransaction();

        $ratesheetRepo = new Repos\RatesheetRepo();

        if($req->ratesheet_id) {
            $originalRatesheet = $ratesheetRepo->GetById($req->ratesheet_id);
            if($req->user()->cannot('update', $originalRatesheet))
                abort(403);
            $isEdit = true;
        } else {
            if($req->user()->cannot('create', Ratesheet::class))
                abort(403);
            $isEdit = false;
        }

        $validation = new Validation\RatesheetValidationRules();
        $ratesheetRules = $validation->GetValidationRules($req);

        $this->validate($req, $ratesheetRules['rules'], $ratesheetRules['messages']);

        $ratesheetCollector = new Collectors\RatesheetCollector();
        $zoneCollector = new Collectors\ZoneCollector();
        $ratesheet = $ratesheetCollector->Collect($req);

        if($isEdit)
            $ratesheetId = $ratesheetRepo->Update($ratesheet)->ratesheet_id;
        else
            $ratesheetId = $ratesheetRepo->Insert($ratesheet)->ratesheet_id;

        // handle zone changes
        $zones = $zoneCollector->Collect($req->mapZones, $ratesheetId);
        $zonesToBeDeleted = $ratesheetRepo->GetMapZones($ratesheetId)->toArray();

        foreach($zones as $zone)
            if($zone['zone_id']) {
                $zonesToBeDeleted = array_filter($zonesToBeDeleted, function($mapZone) use ($zone) {
                    return $zone['zone_id'] != $mapZone['zone_id'];
                });
                $ratesheetRepo->UpdateZone($zone);
            } else
                $ratesheetRepo->InsertZone($zone);

        foreach($zonesToBeDeleted as $deleteZone)
            $ratesheetRepo->DeleteZone($deleteZone['zone_id']);

        $this->updateNeighbours($ratesheetId);

        DB::commit();
        return response()->json([
            'success' => true,
        ]);
    }

    public function storeConditional(Request $req, $conditionalId = null) {
        $ratesheetRepo = new Repos\RatesheetRepo();

        $ratesheet = $ratesheetRepo->GetById($req->ratesheet_id);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        DB::beginTransaction();

        $conditionalValidationRules = new Validation\ConditionalValidationRules();
        $conditionalRules = $conditionalValidationRules->GetValidationRules($req, $conditionalId);
        $this->validate($req, $conditionalRules['rules'], $conditionalRules['messages']);

        $conditionalCollector = new Collectors\ConditionalCollector();

        $conditional = $conditionalCollector->Collect($req, $conditionalId);

        $conditionalRepo = new Repos\ConditionalRepo();

        if($conditionalId) {
            $stored = $conditionalRepo->Update($conditional);
        } else {
            $stored = $conditionalRepo->Insert($conditional);
        }

        DB::commit();

        return response()->json([
            'conditional_id' => $stored->conditional_id,
            'success' => true
        ]);
    }

    public function storeTimingConditional(Request $req, $timingConditionalId = null) {
        $ratesheetRepo = new Repos\RatesheetRepo();

        $ratesheet = $ratesheetRepo->GetById($req->ratesheet_id);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        DB::beginTransaction();

        $timingConditionalValidationRules = new Validation\TimingConditionalValidationRules();
        $timingRules = $timingConditionalValidationRules->GetValidationRules($req, $timingConditionalId);
        $this->validate($req, $timingRules['rules'], $timingRules['messages']);

        $timingConditionalCollector = new Collectors\TimingConditionalCollector();

        $timingConditional = $timingConditionalCollector->Collect($req, $timingConditionalId);

        $timingConditionalRepo = new Repos\TimingConditionalRepo();

        if($timingConditionalId) {
            $stored = $timingConditionalRepo->Update($timingConditional);
        } else {
            $stored = $timingConditionalRepo->Insert($timingConditional);
        }

        DB::commit();

        return response()->json([
            'timing_conditional_id' => $stored->timing_conditional_id,
            'success' => true
        ]);
    }

    /**
     * Private functions
     */

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
}
