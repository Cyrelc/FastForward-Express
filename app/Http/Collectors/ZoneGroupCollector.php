<?php

namespace App\Http\Collectors;

class ZoneGroupCollector {
    public function Collect($zoneGroups, $ratesheetId) {
        $collected = [];

        foreach(($zoneGroups ?? []) as $zoneGroup) {
            $collected[] = [
                // The client's id for this group: a real zone_group_id, or a negative placeholder for
                // a group created in the browser this session. Zones reference groups by this id, so
                // the controller keeps it around to remap membership after the insert.
                'client_id' => $zoneGroup['zoneGroupId'] ?? null,
                'zone_group_id' => (isset($zoneGroup['zoneGroupId']) && $zoneGroup['zoneGroupId'] > 0)
                    ? $zoneGroup['zoneGroupId']
                    : null,
                'ratesheet_id' => $ratesheetId,
                'name' => trim($zoneGroup['name'] ?? ''),
                'colour' => $this->normalizeColour($zoneGroup['colour'] ?? null),
                'zone_rates' => json_encode($this->normalizeZoneRates($zoneGroup['zoneRates'] ?? [])),
            ];
        }

        return $collected;
    }

    /**
     * Rate rows are [{id, zones, "<Delivery Type>": {cost, time}}, ...]. Blank inputs are stored as
     * null rather than 0 so that "not configured" stays distinguishable from "free" - the engine
     * refuses to price a crossing whose cost is null instead of silently charging nothing.
     */
    private function normalizeZoneRates($zoneRates) {
        $normalized = [];

        foreach($zoneRates as $index => $zoneRate) {
            $row = [
                'id' => $zoneRate['id'] ?? $index,
                'zones' => (int) ($zoneRate['zones'] ?? ($index + 1)),
            ];

            foreach($zoneRate as $key => $value) {
                if(in_array($key, ['id', 'zones'], true) || !is_array($value))
                    continue;

                $cost = $value['cost'] ?? null;
                $time = $value['time'] ?? null;

                $row[$key] = [
                    'cost' => is_numeric($cost) ? (float) $cost : null,
                    'time' => is_numeric($time) ? (float) $time : null,
                ];
            }

            $normalized[] = $row;
        }

        return $normalized;
    }

    private function normalizeColour($colour) {
        if(!is_string($colour))
            return null;

        $colour = trim($colour);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $colour) ? strtolower($colour) : null;
    }
}

?>
