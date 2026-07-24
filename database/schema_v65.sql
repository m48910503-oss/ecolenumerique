-- Schema v65 : categorie support pour guider les tickets.

SET @sql := IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'support_conversations'
       AND COLUMN_NAME = 'category') = 0,
    'ALTER TABLE support_conversations
        ADD COLUMN category ENUM(''technical'',''billing'',''certificate'',''course'',''lesson'',''meeting'',''withdrawal'',''other'') NOT NULL DEFAULT ''other'' AFTER priority',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

