<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

use App\Http\Collectors;
use App\Http\Repos;
use App\Http\Services;
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

        $this->guardAgainstDeletingAReferencedRule(
            $conditionalRepo->GetByRatesheetId($conditional->ratesheet_id),
            $conditionalId,
            'conditional_id'
        );

        $conditionalRepo->Delete($conditionalId);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Refuses to delete a rule that other rules are written against.
     *
     * Allowing it would not error - it would silently change what the survivors mean. Delete the
     * middle weight bracket and the rule that said "not the middle bracket, and under 999" quietly
     * widens to cover the range that bracket used to own, mispricing every bill in it with no warning.
     * Rewrite or delete the dependents first.
     */
    private function guardAgainstDeletingAReferencedRule($allRules, $id, $idKey) {
        $rules = [];
        foreach($allRules as $rule)
            // data_get, not (array): casting an Eloquent model exposes its internals, not its columns.
            $rules[] = [
                'id' => data_get($rule, $idKey),
                'name' => data_get($rule, 'name'),
                'json_logic' => data_get($rule, 'json_logic'),
            ];

        $dependents = Services\RuleReferences::Dependents($rules, $id);

        if($dependents)
            abort(400, 'This rule cannot be deleted: ' . implode(', ', array_map(fn($name) => '"' . $name . '"', $dependents))
                . (count($dependents) === 1 ? ' refers to it.' : ' refer to it.')
                . ' Change those rules first, or they would silently start covering this one\'s cases.');
    }

    /**
     * Refuses a rule whose references would close a loop ("A needs B, B needs A").
     *
     * Caught here rather than at pricing time: a cycle has no answer, and the place to find that out
     * is while writing the rule, not on a customer's bill. The rule being saved is folded into the
     * sheet's existing rules first, so the check sees the graph as it would be.
     */
    private function guardAgainstCircularReferences($existingRules, $id, $jsonLogic, $name, $idKey) {
        $references = [];
        $names = [];

        foreach($existingRules as $rule) {
            $ruleId = (int) data_get($rule, $idKey);

            // The version being saved replaces the stored one.
            if($id && $ruleId === (int) $id)
                continue;

            $references[$ruleId] = Services\RuleReferences::ReferencedIds(data_get($rule, 'json_logic'));
            $names[$ruleId] = data_get($rule, 'name');
        }

        // A new rule has no id yet, so it cannot be referenced and cannot close a loop - but it can
        // still be checked for referring to itself once saved, which a placeholder id makes visible.
        $pendingId = $id ? (int) $id : -1;
        $references[$pendingId] = Services\RuleReferences::ReferencedIds($jsonLogic);
        $names[$pendingId] = $name;

        if(in_array($pendingId, $references[$pendingId], true))
            abort(400, 'A rule cannot refer to itself.');

        try {
            Services\RuleReferences::DependencyOrder($references, $names);
        } catch(\RuntimeException $e) {
            abort(400, $e->getMessage());
        }
    }

    public function deleteTimingConditional(Request $req, $timingConditionalId) {
        $timingConditionalRepo = new Repos\TimingConditionalRepo();
        $ratesheetRepo = new Repos\RatesheetRepo();

        $timingConditional = $timingConditionalRepo->GetById($timingConditionalId);
        $ratesheet = $ratesheetRepo->GetById($timingConditional->ratesheet_id);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        $this->guardAgainstDeletingAReferencedRule(
            $timingConditionalRepo->GetByRatesheetId($timingConditional->ratesheet_id),
            $timingConditionalId,
            'timing_conditional_id'
        );

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

        // No ratesheet-level switch any more: a count exists whenever the two zones share a group.
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
        $ratesheet = $ratesheetCollector->Collect($req);

        if($isEdit)
            $ratesheetId = $ratesheetRepo->Update($ratesheet)->ratesheet_id;
        else
            $ratesheetId = $ratesheetRepo->Insert($ratesheet)->ratesheet_id;

        DB::commit();
        return response()->json([
            'success' => true,
            'ratesheet_id' => $ratesheetId,
        ]);
    }

    /**
     * Saves one zone. Zones used to be saved only as part of the whole ratesheet, where the payload
     * was the complete zone list and anything missing from it was deleted - so a partial or buggy
     * payload silently destroyed zones, and two people editing one map meant last-save-wins. Zones
     * are now addressed individually and deletion is explicit (see destroyZone).
     *
     * @param $zoneId - null to create
     * @return the saved zone_id, so the client can stop treating a newly drawn zone as new
     */
    public function storeZone(Request $req, $ratesheetId, $zoneId = null) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($ratesheetId);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        $validation = new Validation\RatesheetValidationRules();
        $zoneRules = $validation->GetZoneValidationRules($req);
        $this->validate($req, $zoneRules['rules'], $zoneRules['messages']);

        DB::beginTransaction();

        $zoneCollector = new Collectors\ZoneCollector();
        $zoneGroupRepo = new Repos\ZoneGroupRepo();

        $collected = $zoneCollector->Collect([$req->zone], $ratesheetId);
        $zone = $collected[0];
        $zone['zone_id'] = $zoneId ?: null;

        $groupIds = $zone['group_ids'];
        unset($zone['group_ids']);

        $saved = $zone['zone_id']
            ? $ratesheetRepo->UpdateZone($zone)
            : $ratesheetRepo->InsertZone($zone);

        $zoneGroupRepo->SetZoneGroups($saved->zone_id, $this->resolveGroupIds($groupIds, $ratesheetId));

        // Adjacency is symmetric geometry, so moving one zone can make or break its neighbours' links
        // as well as its own. Only this zone's row of the adjacency matrix can have changed, so the
        // recompute is scoped to it rather than sweeping the whole map.
        $this->updateNeighboursForZone($ratesheetId, $saved->zone_id);

        DB::commit();

        return response()->json([
            'success' => true,
            'zone_id' => $saved->zone_id,
        ]);
    }

    public function destroyZone(Request $req, $ratesheetId, $zoneId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($ratesheetId);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        DB::beginTransaction();

        $ratesheetRepo->DeleteZone($zoneId);
        // The zone is gone from every neighbour list it appeared in.
        $this->pruneNeighbour($ratesheetId, $zoneId);

        DB::commit();

        return response()->json(['success' => true]);
    }

    /**
     * Saves one zone group (name, colour, and its zone-crossing rate table).
     *
     * @param $zoneGroupId - null to create
     */
    public function storeZoneGroup(Request $req, $ratesheetId, $zoneGroupId = null) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($ratesheetId);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        $validation = new Validation\ZoneGroupValidationRules();
        $zoneGroupRules = $validation->GetValidationRules($req, json_decode($ratesheet->delivery_types, true) ?? []);
        $this->validate($req, $zoneGroupRules['rules'], $zoneGroupRules['messages']);

        DB::beginTransaction();

        $zoneGroupCollector = new Collectors\ZoneGroupCollector();
        $zoneGroupRepo = new Repos\ZoneGroupRepo();

        $collected = $zoneGroupCollector->Collect([$req->zoneGroup], $ratesheetId);
        $zoneGroup = $collected[0];
        $zoneGroup['zone_group_id'] = $zoneGroupId ?: null;

        $saved = $zoneGroup['zone_group_id']
            ? $zoneGroupRepo->Update($zoneGroup)
            : $zoneGroupRepo->Insert($zoneGroup);

        DB::commit();

        return response()->json([
            'success' => true,
            'zone_group_id' => $saved->zone_group_id,
        ]);
    }

    public function destroyZoneGroup(Request $req, $ratesheetId, $zoneGroupId) {
        $ratesheetRepo = new Repos\RatesheetRepo();
        $ratesheet = $ratesheetRepo->GetById($ratesheetId);

        if($req->user()->cannot('update', $ratesheet))
            abort(403);

        DB::beginTransaction();

        $zoneGroupRepo = new Repos\ZoneGroupRepo();
        $zoneIds = $zoneGroupRepo->GetZoneIds($zoneGroupId);
        $zoneGroupRepo->Delete($zoneGroupId);

        // Losing a group can leave its former members ungrouped, and an ungrouped zone is never
        // traversable, so their adjacency has to be rebuilt.
        foreach($zoneIds as $zoneId)
            $this->updateNeighboursForZone($ratesheetId, $zoneId);

        DB::commit();

        return response()->json(['success' => true]);
    }

    /**
     * Maps the client's group ids to real ones. A group created in the browser carries a negative
     * placeholder id until it is saved; by the time a zone references it, it must be real.
     */
    private function resolveGroupIds($groupIds, $ratesheetId) {
        $zoneGroupRepo = new Repos\ZoneGroupRepo();
        $realIds = $zoneGroupRepo->GetByRatesheetId($ratesheetId)->pluck('zone_group_id')->all();

        return array_values(array_filter($groupIds, function($groupId) use ($realIds) {
            return in_array($groupId, $realIds);
        }));
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

        $this->guardAgainstCircularReferences(
            $conditionalRepo->GetByRatesheetId($req->ratesheet_id),
            $conditionalId,
            $req->json_logic,
            $req->name,
            'conditional_id'
        );

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

        $this->guardAgainstCircularReferences(
            $timingConditionalRepo->GetByRatesheetId($req->ratesheet_id),
            $timingConditionalId,
            $req->json_logic,
            $req->name,
            'timing_conditional_id'
        );

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

    /**
     * Recomputes adjacency for a single zone, and fixes up the other side of every link it gains or
     * loses. Adjacency is symmetric, so only this zone's row of the matrix can have changed when only
     * this zone moved - which makes the work O(zones) instead of the O(zones^2) whole-map sweep.
     *
     * An ungrouped zone can never be traversed, so it holds no adjacency at all.
     */
    private function updateNeighboursForZone($ratesheetId, $zoneId) {
        $ratesheetRepo = new Repos\RatesheetRepo();

        $mapZones = $ratesheetRepo->GetMapZones($ratesheetId);
        $zone = $mapZones->firstWhere('zone_id', $zoneId);

        if(!$zone)
            return;

        $isGrouped = !empty($zone->group_ids);
        $coordinates = json_decode($zone->coordinates);

        $newNeighbourIds = [];
        if($isGrouped)
            foreach($mapZones as $other) {
                if($other->zone_id === $zone->zone_id || empty($other->group_ids))
                    continue;

                if($this->zonesTouch($coordinates, json_decode($other->coordinates)))
                    $newNeighbourIds[] = $other->zone_id;
            }

        $ratesheetRepo->SetZoneNeighbours($zoneId, $newNeighbourIds ? json_encode($newNeighbourIds) : null);

        // Mirror the change onto everyone else: add this zone to its new neighbours, and remove it
        // from anyone it is no longer touching.
        foreach($mapZones as $other) {
            if($other->zone_id === $zone->zone_id)
                continue;

            $otherNeighbours = !empty($other->neighbours) ? json_decode($other->neighbours, true) : [];
            $shouldContain = in_array($other->zone_id, $newNeighbourIds);
            $doesContain = in_array($zoneId, $otherNeighbours);

            if($shouldContain === $doesContain)
                continue;

            if($shouldContain)
                $otherNeighbours[] = (int) $zoneId;
            else
                $otherNeighbours = array_values(array_filter($otherNeighbours, function($neighbourId) use ($zoneId) {
                    return (int) $neighbourId !== (int) $zoneId;
                }));

            $ratesheetRepo->SetZoneNeighbours($other->zone_id, $otherNeighbours ? json_encode($otherNeighbours) : null);
        }
    }

    /**
     * Drops a deleted zone out of every neighbour list that still mentions it.
     */
    private function pruneNeighbour($ratesheetId, $zoneId) {
        $ratesheetRepo = new Repos\RatesheetRepo();

        foreach($ratesheetRepo->GetMapZones($ratesheetId) as $zone) {
            $neighbours = !empty($zone->neighbours) ? json_decode($zone->neighbours, true) : [];
            if(!in_array($zoneId, $neighbours))
                continue;

            $neighbours = array_values(array_filter($neighbours, function($neighbourId) use ($zoneId) {
                return (int) $neighbourId !== (int) $zoneId;
            }));

            $ratesheetRepo->SetZoneNeighbours($zone->zone_id, $neighbours ? json_encode($neighbours) : null);
        }
    }

    /**
     * Two zones are neighbours if they share at least one polygon vertex.
     */
    private function zonesTouch($coordinates, $otherCoordinates) {
        foreach($coordinates as $coordinate)
            foreach($otherCoordinates as $otherCoordinate)
                if($coordinate->lat === $otherCoordinate->lat && $coordinate->lng === $otherCoordinate->lng)
                    return true;

        return false;
    }
}
