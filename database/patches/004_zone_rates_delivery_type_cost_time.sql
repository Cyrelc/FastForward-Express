-- Run with: mysql -u <username> -p <database_name> < database/patches/004_zone_rates_delivery_type_cost_time.sql

-- Convert legacy zone_rates entries from fixed *_cost fields into
-- delivery-type keyed objects with both cost and time:
-- {
--   "id": 0,
--   "zones": 1,
--   "Regular": {"cost": 10.00, "time": 0},
--   "Rush": {"cost": 20.00, "time": 0},
--   "Direct": {"cost": 30.00, "time": 0},
--   "Direct Rush": {"cost": 40.00, "time": 0}
-- }
UPDATE ratesheets r
SET r.zone_rates = (
    SELECT JSON_ARRAYAGG(
        JSON_OBJECT(
            'id', CAST(
                CASE
                    WHEN TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.id')), '')) REGEXP '^[0-9]+$'
                        THEN TRIM(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.id')))
                    ELSE '0'
                END
            AS UNSIGNED),
            'zones', CAST(
                CASE
                    WHEN TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.zones')), '')) REGEXP '^[0-9]+$'
                        THEN TRIM(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.zones')))
                    ELSE '0'
                END
            AS UNSIGNED),
            'Regular', JSON_OBJECT(
                'cost', CAST(
                    CASE
                        WHEN TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.regular_cost')), '')) REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                            THEN TRIM(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.regular_cost')))
                        ELSE '0'
                    END
                AS DECIMAL(10, 2)),
                'time', 0
            ),
            'Rush', JSON_OBJECT(
                'cost', CAST(
                    CASE
                        WHEN TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.rush_cost')), '')) REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                            THEN TRIM(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.rush_cost')))
                        ELSE '0'
                    END
                AS DECIMAL(10, 2)),
                'time', 0
            ),
            'Direct', JSON_OBJECT(
                'cost', CAST(
                    CASE
                        WHEN TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.direct_cost')), '')) REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                            THEN TRIM(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.direct_cost')))
                        ELSE '0'
                    END
                AS DECIMAL(10, 2)),
                'time', 0
            ),
            'Direct Rush', JSON_OBJECT(
                'cost', CAST(
                    CASE
                        WHEN TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.direct_rush_cost')), '')) REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
                            THEN TRIM(JSON_UNQUOTE(JSON_EXTRACT(j.entry, '$.direct_rush_cost')))
                        ELSE '0'
                    END
                AS DECIMAL(10, 2)),
                'time', 0
            )
        )
    )
    FROM JSON_TABLE(r.zone_rates, '$[*]' COLUMNS (entry JSON PATH '$')) AS j
)
WHERE r.zone_rates IS NOT NULL
  AND JSON_VALID(r.zone_rates)
  AND JSON_EXTRACT(r.zone_rates, '$[0].regular_cost') IS NOT NULL;
