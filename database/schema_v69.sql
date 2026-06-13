-- Schema v69 : repair platform_settings uniqueness.

DROP TEMPORARY TABLE IF EXISTS platform_settings_dedup;

CREATE TEMPORARY TABLE platform_settings_dedup (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=MEMORY;

INSERT INTO platform_settings_dedup (setting_key, setting_value, updated_at)
SELECT
    p.setting_key,
    COALESCE(
        (
            SELECT p2.setting_value
            FROM platform_settings p2
            WHERE p2.setting_key = p.setting_key
            ORDER BY p2.updated_at DESC, (p2.setting_value <> '') DESC
            LIMIT 1
        ),
        ''
    ) AS setting_value,
    MAX(p.updated_at) AS updated_at
FROM platform_settings p
GROUP BY p.setting_key;

DELETE FROM platform_settings;

INSERT INTO platform_settings (setting_key, setting_value, updated_at)
SELECT setting_key, setting_value, updated_at
FROM platform_settings_dedup;

SET @platform_settings_has_primary := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'platform_settings'
      AND index_name = 'PRIMARY'
);

SET @platform_settings_primary_sql := IF(
    @platform_settings_has_primary = 0,
    'ALTER TABLE platform_settings ADD PRIMARY KEY (setting_key)',
    'SELECT 1'
);

PREPARE platform_settings_primary_stmt FROM @platform_settings_primary_sql;
EXECUTE platform_settings_primary_stmt;
DEALLOCATE PREPARE platform_settings_primary_stmt;
