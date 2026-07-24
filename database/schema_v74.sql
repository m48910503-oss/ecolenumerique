-- Schema v74 : support visiteur sans connexion.
--
-- Objectif :
-- 1. autoriser les conversations support sans `users.id`,
-- 2. conserver un identifiant de session visiteur pour retrouver le fil,
-- 3. garder les conversations / messages de comptes authentifies inchanges.

SET @has_support_viewer_key := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'support_conversations'
      AND COLUMN_NAME = 'viewer_key'
);
SET @sql := IF(
    @has_support_viewer_key = 0,
    'ALTER TABLE support_conversations
        MODIFY COLUMN created_by BIGINT UNSIGNED NULL,
        ADD COLUMN viewer_key VARCHAR(96) NULL AFTER created_by,
        ADD COLUMN guest_name VARCHAR(120) NULL AFTER viewer_key,
        ADD COLUMN guest_email VARCHAR(190) NULL AFTER guest_name',
    'ALTER TABLE support_conversations
        MODIFY COLUMN created_by BIGINT UNSIGNED NULL'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*)
     FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'support_conversations'
       AND INDEX_NAME = 'idx_support_conv_viewer') = 0,
    'CREATE INDEX idx_support_conv_viewer ON support_conversations (viewer_key, last_message_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_support_sender_nullable := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'support_messages'
      AND COLUMN_NAME = 'sender_id'
      AND IS_NULLABLE = 'YES'
);
SET @sql := IF(
    @has_support_sender_nullable = 0,
    'ALTER TABLE support_messages
        MODIFY COLUMN sender_id BIGINT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
