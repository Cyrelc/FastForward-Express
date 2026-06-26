-- Run with: mysql -u <username> -p <database_name> < database/patches/001_zone_delivery_type_modifiers.sql

-- Add region column to zones
ALTER TABLE zones ADD COLUMN region VARCHAR(255) NULL AFTER ratesheet_id;

-- Add tags column to zones
ALTER TABLE zones ADD COLUMN tags VARCHAR(255) NULL AFTER region;

-- Rename additional_costs to delivery_type_modifiers
ALTER TABLE zones RENAME COLUMN additional_costs TO delivery_type_modifiers;

-- Combine old flat additional_costs structure and additional_time into unified delivery_type_modifiers JSON
-- for zones that had both costs and a non-null additional_time value
UPDATE zones
SET delivery_type_modifiers = JSON_OBJECT(
    'Regular', JSON_OBJECT(
        'time', CAST(COALESCE(additional_time, 0) AS DECIMAL(10, 2)),
        'cost', CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(delivery_type_modifiers, '$.regular')), 0) AS DECIMAL(10, 2))
    ),
    'Rush', JSON_OBJECT(
        'time', CAST(COALESCE(additional_time, 0) AS DECIMAL(10, 2)),
        'cost', CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(delivery_type_modifiers, '$.rush')), 0) AS DECIMAL(10, 2))
    ),
    'Direct', JSON_OBJECT(
        'time', CAST(COALESCE(additional_time, 0) AS DECIMAL(10, 2)),
        'cost', CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(delivery_type_modifiers, '$.direct')), 0) AS DECIMAL(10, 2))
    ),
    'Direct Rush', JSON_OBJECT(
        'time', CAST(COALESCE(additional_time, 0) AS DECIMAL(10, 2)),
        'cost', CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(delivery_type_modifiers, '$.direct_rush')), 0) AS DECIMAL(10, 2))
    )
)
WHERE delivery_type_modifiers IS NOT NULL
    AND additional_time IS NOT NULL
    AND JSON_VALID(delivery_type_modifiers)
    AND JSON_EXTRACT(delivery_type_modifiers, '$.regular') IS NOT NULL;

-- Same normalization for zones that had additional_costs data but no additional_time (time defaults to 0)
UPDATE zones
SET delivery_type_modifiers = JSON_OBJECT(
    'Regular', JSON_OBJECT(
        'time', 0,
        'cost', CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(delivery_type_modifiers, '$.regular')), 0) AS DECIMAL(10, 2))
    ),
    'Rush', JSON_OBJECT(
        'time', 0,
        'cost', CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(delivery_type_modifiers, '$.rush')), 0) AS DECIMAL(10, 2))
    ),
    'Direct', JSON_OBJECT(
        'time', 0,
        'cost', CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(delivery_type_modifiers, '$.direct')), 0) AS DECIMAL(10, 2))
    ),
    'Direct Rush', JSON_OBJECT(
        'time', 0,
        'cost', CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(delivery_type_modifiers, '$.direct_rush')), 0) AS DECIMAL(10, 2))
    )
)
WHERE delivery_type_modifiers IS NOT NULL
    AND (additional_time IS NULL OR additional_time = 0)
    AND JSON_VALID(delivery_type_modifiers)
    AND JSON_EXTRACT(delivery_type_modifiers, '$.regular') IS NOT NULL;
