-- Run with: mysql -u <username> -p <database_name> < database/patches/005_seed_default_zone_timing_conditionals.sql

-- Patch 005: Seed default zone-time timing conditionals for existing ratesheets
--
-- Implements the backwards-compatible rule:
--   1. Start from base delivery type time
--   2. If pickup zone has additional time configured for the selected delivery type → add it (continue)
--   3. If delivery zone has additional time configured for the selected delivery type → add it (stop)
--
-- Only inserts into ratesheets that have NO existing timing conditionals.
-- All rows are generated from the same pre-INSERT snapshot of the table, so the
-- WHERE NOT EXISTS check is consistent across both halves of the UNION ALL.

INSERT INTO timing_conditionals
    (created_at, updated_at, action, human_readable, json_logic, name, priority, ratesheet_id, value, value_type, continue_on)
SELECT
    NOW(), NOW(),
    '{"value":"set"}',
    'Start with base delivery type time',
    '{"==":[1,1]}',
    'Base Delivery Type Time',
    0,
    r.ratesheet_id,
    'base_time.default',
    'field',
    1
FROM ratesheets r
WHERE NOT EXISTS (SELECT 1 FROM timing_conditionals tc WHERE tc.ratesheet_id = r.ratesheet_id)

UNION ALL

SELECT
    NOW(), NOW(),
    '{"value":"add"}',
    'If pickup zone has additional time for this delivery type, add it',
    '{">":[{"var":"timing_inputs.pickup_zone_selected_type_time"},0]}',
    'Pickup Zone Time',
    10,
    r.ratesheet_id,
    'timing_inputs.pickup_zone_selected_type_time',
    'field',
    1
FROM ratesheets r
WHERE NOT EXISTS (SELECT 1 FROM timing_conditionals tc WHERE tc.ratesheet_id = r.ratesheet_id)

UNION ALL

SELECT
    NOW(), NOW(),
    '{"value":"add"}',
    'If delivery zone has additional time for this delivery type, add it',
    '{">":[{"var":"timing_inputs.delivery_zone_selected_type_time"},0]}',
    'Delivery Zone Time',
    20,
    r.ratesheet_id,
    'timing_inputs.delivery_zone_selected_type_time',
    'field',
    0
FROM ratesheets r
WHERE NOT EXISTS (SELECT 1 FROM timing_conditionals tc WHERE tc.ratesheet_id = r.ratesheet_id);
