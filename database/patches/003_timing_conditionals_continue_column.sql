-- Run with: mysql -u <username> -p <database_name> < database/patches/003_timing_conditionals_continue_column.sql

-- Add flow behavior to timing conditionals so matched rules can continue or stop evaluation.
SET @has_continue_on := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'timing_conditionals'
      AND COLUMN_NAME = 'continue_on'
);

SET @add_continue_on_sql := IF(
    @has_continue_on = 0,
    'ALTER TABLE timing_conditionals ADD COLUMN continue_on TINYINT(1) NOT NULL DEFAULT 1 AFTER action',
    'SELECT 1'
);
PREPARE add_continue_on_stmt FROM @add_continue_on_sql;
EXECUTE add_continue_on_stmt;
DEALLOCATE PREPARE add_continue_on_stmt;

-- If legacy flow_type exists, map it to continue_on.
SET @has_flow_type := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'timing_conditionals'
      AND COLUMN_NAME = 'flow_type'
);

SET @map_flow_type_sql := IF(
    @has_flow_type = 1,
    'UPDATE timing_conditionals SET continue_on = CASE WHEN flow_type = ''continue'' THEN 1 WHEN flow_type = ''stop'' THEN 0 ELSE 1 END',
    'UPDATE timing_conditionals SET continue_on = 1 WHERE continue_on IS NULL'
);
PREPARE map_flow_type_stmt FROM @map_flow_type_sql;
EXECUTE map_flow_type_stmt;
DEALLOCATE PREPARE map_flow_type_stmt;
