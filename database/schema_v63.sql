-- Schema v63 : reparation globale des colonnes id degradees
-- (PRIMARY KEY / AUTO_INCREMENT) sur les tables restantes de la base.
--
-- Cette migration cible le probleme structurel observe sur certaines bases
-- XAMPP ou des tables ont perdu l'attribut AUTO_INCREMENT, voire leur
-- PRIMARY KEY sur id.

-- -------------------------------------------------------------------------
-- 1) Remap des dernieres lignes encore en id=0.
-- -------------------------------------------------------------------------
SET @admin_event_next_id := (
    SELECT COALESCE(MAX(id), 0)
    FROM admin_announcement_events
    WHERE id > 0
);
UPDATE admin_announcement_events
SET id = (@admin_event_next_id := @admin_event_next_id + 1)
WHERE id = 0
ORDER BY created_at ASC, announcement_id ASC;

SET @audit_log_next_id := (
    SELECT COALESCE(MAX(id), 0)
    FROM audit_logs
    WHERE id > 0
);
UPDATE audit_logs
SET id = (@audit_log_next_id := @audit_log_next_id + 1)
WHERE id = 0
ORDER BY created_at ASC;

SET @course_view_next_id := (
    SELECT COALESCE(MAX(id), 0)
    FROM course_views
    WHERE id > 0
);
UPDATE course_views
SET id = (@course_view_next_id := @course_view_next_id + 1)
WHERE id = 0
ORDER BY created_at ASC, course_id ASC, viewer_key ASC;

-- Repare la seule reference applicative restante en target_id=0 observee
-- apres le remap du cours legacy "bonjours".
SET @legacy_bonjours_course_id := (
    SELECT id
    FROM courses
    WHERE slug = 'bonjours'
    ORDER BY id DESC
    LIMIT 1
);
UPDATE audit_logs
SET target_id = @legacy_bonjours_course_id
WHERE target_type = 'course'
  AND action = 'course_create'
  AND target_id = 0
  AND @legacy_bonjours_course_id IS NOT NULL;

-- -------------------------------------------------------------------------
-- 2) Procedure generique : remet PK+AUTO_INCREMENT sur toutes les tables
--    ayant une colonne id numerique encore degradee.
-- -------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS repair_remaining_id_columns;

DELIMITER $$
CREATE PROCEDURE repair_remaining_id_columns()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE table_name_value VARCHAR(128);
    DECLARE has_any_primary_key INT DEFAULT 0;

    DECLARE cur CURSOR FOR
        SELECT c.TABLE_NAME
        FROM information_schema.COLUMNS c
        WHERE c.TABLE_SCHEMA = DATABASE()
          AND c.COLUMN_NAME = 'id'
          AND c.DATA_TYPE IN ('tinyint', 'smallint', 'mediumint', 'int', 'bigint')
          AND (
              c.EXTRA NOT LIKE '%auto_increment%'
              OR NOT EXISTS (
                  SELECT 1
                  FROM information_schema.KEY_COLUMN_USAGE k
                  WHERE k.TABLE_SCHEMA = c.TABLE_SCHEMA
                    AND k.TABLE_NAME = c.TABLE_NAME
                    AND k.COLUMN_NAME = c.COLUMN_NAME
                    AND k.CONSTRAINT_NAME = 'PRIMARY'
              )
          )
        ORDER BY c.TABLE_NAME;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;

    repair_loop: LOOP
        FETCH cur INTO table_name_value;
        IF done = 1 THEN
            LEAVE repair_loop;
        END IF;

        SET has_any_primary_key = (
            SELECT COUNT(*)
            FROM information_schema.TABLE_CONSTRAINTS tc
            WHERE tc.TABLE_SCHEMA = DATABASE()
              AND tc.TABLE_NAME = table_name_value
              AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
        );

        IF has_any_primary_key = 0 THEN
            SET @sql = CONCAT(
                'ALTER TABLE `',
                REPLACE(table_name_value, '`', '``'),
                '` ADD PRIMARY KEY (`id`)'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;

        SET @sql = CONCAT(
            'ALTER TABLE `',
            REPLACE(table_name_value, '`', '``'),
            '` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;

    CLOSE cur;
END$$
DELIMITER ;

CALL repair_remaining_id_columns();
DROP PROCEDURE IF EXISTS repair_remaining_id_columns;
