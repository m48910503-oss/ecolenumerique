-- Schema v66 : motif explicite pour les retraits rejetés.

SET @sql := IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'withdrawals'
       AND COLUMN_NAME = 'reason') = 0,
    'ALTER TABLE withdrawals
        ADD COLUMN reason TEXT NULL AFTER payout_reference',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
