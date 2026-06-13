-- Schema v62 : reparation du bloc users / notifications / push / support
-- sur bases legacy ayant perdu PRIMARY KEY / AUTO_INCREMENT.
--
-- Objectifs :
-- 1. remettre un identifiant sain sur users.id (pre-requis aux foreign keys),
-- 2. remapper les lignes notifications / push / support en id=0,
-- 3. dedupliquer support_conversation_reads,
-- 4. restaurer les indexes et contraintes du module.

-- -------------------------------------------------------------------------
-- 1) users : cle primaire exploitable pour restaurer les relations.
-- -------------------------------------------------------------------------
SET @has_users_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_users_pk = 0, 'ALTER TABLE users ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE users
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

-- -------------------------------------------------------------------------
-- 2) Tables temporaires de mapping pour les ids legacy a 0.
-- -------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_support_conv_zero_map;
CREATE TEMPORARY TABLE tmp_support_conv_zero_map (
    seq BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    created_by BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(190) NOT NULL,
    status ENUM('open','waiting_support','waiting_user','closed') NOT NULL,
    priority ENUM('low','normal','high') NOT NULL,
    assigned_to BIGINT UNSIGNED NULL,
    last_message_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    new_id BIGINT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_support_conv_zero_map (
    created_by,
    subject,
    status,
    priority,
    assigned_to,
    last_message_at,
    created_at,
    updated_at
)
SELECT
    created_by,
    subject,
    status,
    priority,
    assigned_to,
    last_message_at,
    created_at,
    updated_at
FROM support_conversations
WHERE id = 0
ORDER BY created_at ASC, created_by ASC, subject ASC, updated_at ASC;

SET @support_conv_base_id := (SELECT COALESCE(MAX(id), 0) FROM support_conversations WHERE id > 0);
UPDATE tmp_support_conv_zero_map
SET new_id = @support_conv_base_id + seq;

DROP TEMPORARY TABLE IF EXISTS tmp_support_msg_zero_map;
CREATE TEMPORARY TABLE tmp_support_msg_zero_map (
    seq BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sender_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    original_conversation_id BIGINT UNSIGNED NOT NULL,
    new_id BIGINT UNSIGNED NULL,
    new_conversation_id BIGINT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_support_msg_zero_map (
    sender_id,
    body,
    created_at,
    original_conversation_id
)
SELECT
    sender_id,
    body,
    created_at,
    conversation_id
FROM support_messages
WHERE id = 0 OR conversation_id = 0
ORDER BY created_at ASC, sender_id ASC;

SET @support_msg_base_id := (SELECT COALESCE(MAX(id), 0) FROM support_messages WHERE id > 0);
UPDATE tmp_support_msg_zero_map
SET new_id = @support_msg_base_id + seq;

UPDATE tmp_support_msg_zero_map sm
SET sm.new_conversation_id = (
    SELECT sc.new_id
    FROM tmp_support_conv_zero_map sc
    WHERE sc.created_by = sm.sender_id
      AND sc.created_at <= sm.created_at
    ORDER BY sc.created_at DESC, sc.seq DESC
    LIMIT 1
);

DROP TEMPORARY TABLE IF EXISTS tmp_notifications_zero_map;
CREATE TEMPORARY TABLE tmp_notifications_zero_map (
    seq BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    body TEXT NULL,
    url VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL,
    popup_shown_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    announcement_id BIGINT UNSIGNED NULL,
    importance ENUM('info','important','urgent') NOT NULL,
    summary VARCHAR(200) NULL,
    variant CHAR(1) NULL,
    read_at DATETIME NULL,
    clicked_at DATETIME NULL,
    new_id BIGINT UNSIGNED NULL,
    new_support_conversation_id BIGINT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_notifications_zero_map (
    user_id,
    title,
    body,
    url,
    is_read,
    popup_shown_at,
    created_at,
    announcement_id,
    importance,
    summary,
    variant,
    read_at,
    clicked_at
)
SELECT
    user_id,
    title,
    body,
    url,
    is_read,
    popup_shown_at,
    created_at,
    announcement_id,
    importance,
    summary,
    variant,
    read_at,
    clicked_at
FROM notifications
WHERE id = 0
ORDER BY created_at ASC, user_id ASC, title ASC, url ASC;

SET @notifications_base_id := (SELECT COALESCE(MAX(id), 0) FROM notifications WHERE id > 0);
UPDATE tmp_notifications_zero_map
SET new_id = @notifications_base_id + seq;

UPDATE tmp_notifications_zero_map n
SET n.new_support_conversation_id = (
    SELECT sc.new_id
    FROM tmp_support_conv_zero_map sc
    WHERE n.url = 'admin/support?conv=0'
      AND n.title = 'Nouvelle demande support'
      AND n.body = CONCAT('Sujet: ', sc.subject)
      AND sc.created_at <= n.created_at
    ORDER BY sc.created_at DESC, sc.seq DESC
    LIMIT 1
);

DROP TEMPORARY TABLE IF EXISTS tmp_push_queue_zero_map;
CREATE TEMPORARY TABLE tmp_push_queue_zero_map (
    seq BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    body TEXT NULL,
    url VARCHAR(255) NULL,
    status ENUM('pending','processing','sent','failed') NOT NULL,
    attempt_count INT UNSIGNED NOT NULL,
    last_error VARCHAR(255) NULL,
    next_attempt_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    new_id BIGINT UNSIGNED NULL,
    new_notification_id BIGINT UNSIGNED NULL,
    new_support_conversation_id BIGINT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_push_queue_zero_map (
    notification_id,
    user_id,
    title,
    body,
    url,
    status,
    attempt_count,
    last_error,
    next_attempt_at,
    sent_at,
    created_at,
    updated_at
)
SELECT
    notification_id,
    user_id,
    title,
    body,
    url,
    status,
    attempt_count,
    last_error,
    next_attempt_at,
    sent_at,
    created_at,
    updated_at
FROM push_notification_queue
WHERE id = 0
ORDER BY created_at ASC, user_id ASC, notification_id ASC, title ASC;

SET @push_queue_base_id := (SELECT COALESCE(MAX(id), 0) FROM push_notification_queue WHERE id > 0);
UPDATE tmp_push_queue_zero_map
SET new_id = @push_queue_base_id + seq;

UPDATE tmp_push_queue_zero_map q
SET
    q.new_notification_id = CASE
        WHEN q.notification_id > 0 THEN q.notification_id
        ELSE (
            SELECT n.new_id
            FROM tmp_notifications_zero_map n
            WHERE n.user_id = q.user_id
              AND n.title = q.title
              AND ((n.body IS NULL AND q.body IS NULL) OR n.body = q.body)
              AND n.created_at = q.created_at
            ORDER BY n.seq ASC
            LIMIT 1
        )
    END,
    q.new_support_conversation_id = (
        SELECT n.new_support_conversation_id
        FROM tmp_notifications_zero_map n
        WHERE n.user_id = q.user_id
          AND n.title = q.title
          AND ((n.body IS NULL AND q.body IS NULL) OR n.body = q.body)
          AND n.created_at = q.created_at
        ORDER BY n.seq ASC
        LIMIT 1
    );

-- -------------------------------------------------------------------------
-- 3) Remappage des ids et references legacy.
-- -------------------------------------------------------------------------
UPDATE support_conversations sc
JOIN tmp_support_conv_zero_map map
  ON sc.id = 0
 AND sc.created_by = map.created_by
 AND sc.subject = map.subject
 AND sc.status = map.status
 AND sc.priority = map.priority
 AND ((sc.assigned_to IS NULL AND map.assigned_to IS NULL) OR sc.assigned_to = map.assigned_to)
 AND sc.last_message_at = map.last_message_at
 AND sc.created_at = map.created_at
 AND sc.updated_at = map.updated_at
SET sc.id = map.new_id;

UPDATE support_messages sm
JOIN tmp_support_msg_zero_map map
  ON sm.sender_id = map.sender_id
 AND sm.body = map.body
 AND sm.created_at = map.created_at
 AND (sm.id = 0 OR sm.conversation_id = 0)
SET sm.conversation_id = COALESCE(map.new_conversation_id, sm.conversation_id);

UPDATE support_messages sm
JOIN tmp_support_msg_zero_map map
  ON sm.id = 0
 AND sm.sender_id = map.sender_id
 AND sm.body = map.body
 AND sm.created_at = map.created_at
SET sm.id = map.new_id;

UPDATE support_conversation_reads r
SET r.conversation_id = (
    SELECT sc.new_id
    FROM tmp_support_conv_zero_map sc
    WHERE sc.created_by = r.user_id
      AND sc.created_at <= r.updated_at
    ORDER BY sc.created_at DESC, sc.seq DESC
    LIMIT 1
)
WHERE r.conversation_id = 0;

UPDATE support_conversation_reads r
SET r.last_read_message_id = (
    SELECT sm.new_id
    FROM tmp_support_msg_zero_map sm
    WHERE sm.sender_id = r.user_id
      AND sm.new_conversation_id = r.conversation_id
      AND sm.created_at <= r.updated_at
    ORDER BY sm.created_at DESC, sm.seq DESC
    LIMIT 1
)
WHERE r.last_read_message_id = 0;

UPDATE notifications n
JOIN tmp_notifications_zero_map map
  ON n.id = 0
 AND n.user_id = map.user_id
 AND n.title = map.title
 AND ((n.body IS NULL AND map.body IS NULL) OR n.body = map.body)
 AND ((n.url IS NULL AND map.url IS NULL) OR n.url = map.url)
 AND n.created_at = map.created_at
SET
    n.id = map.new_id,
    n.url = CASE
        WHEN map.new_support_conversation_id IS NOT NULL THEN CONCAT('admin/support?conv=', map.new_support_conversation_id)
        ELSE n.url
    END;

UPDATE push_notification_queue q
JOIN tmp_push_queue_zero_map map
  ON q.id = 0
 AND q.user_id = map.user_id
 AND q.notification_id = map.notification_id
 AND q.title = map.title
 AND ((q.body IS NULL AND map.body IS NULL) OR q.body = map.body)
 AND ((q.url IS NULL AND map.url IS NULL) OR q.url = map.url)
 AND q.created_at = map.created_at
SET
    q.id = map.new_id,
    q.notification_id = COALESCE(map.new_notification_id, q.notification_id),
    q.url = CASE
        WHEN map.new_support_conversation_id IS NOT NULL THEN CONCAT('admin/support?conv=', map.new_support_conversation_id)
        ELSE q.url
    END;

-- Deduplique les lectures support pour permettre la PRIMARY KEY composite.
DROP TEMPORARY TABLE IF EXISTS tmp_support_reads_dedup;
CREATE TEMPORARY TABLE tmp_support_reads_dedup (
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    last_read_message_id BIGINT UNSIGNED NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (conversation_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_support_reads_dedup (
    conversation_id,
    user_id,
    last_read_message_id,
    updated_at
)
SELECT
    r.conversation_id,
    r.user_id,
    CAST(SUBSTRING_INDEX(
        GROUP_CONCAT(COALESCE(r.last_read_message_id, 0) ORDER BY r.updated_at DESC SEPARATOR ','),
        ',',
        1
    ) AS UNSIGNED) AS last_read_message_id,
    MAX(r.updated_at) AS updated_at
FROM support_conversation_reads r
GROUP BY r.conversation_id, r.user_id;

DELETE FROM support_conversation_reads;

INSERT INTO support_conversation_reads (
    conversation_id,
    user_id,
    last_read_message_id,
    updated_at
)
SELECT
    conversation_id,
    user_id,
    NULLIF(last_read_message_id, 0),
    updated_at
FROM tmp_support_reads_dedup;

-- -------------------------------------------------------------------------
-- 4) Restoration des PRIMARY KEY / AUTO_INCREMENT.
-- -------------------------------------------------------------------------
SET @has_notifications_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notifications'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_notifications_pk = 0, 'ALTER TABLE notifications ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE notifications
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_push_queue_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'push_notification_queue'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_push_queue_pk = 0, 'ALTER TABLE push_notification_queue ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE push_notification_queue
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_push_subscriptions_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'push_subscriptions'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_push_subscriptions_pk = 0, 'ALTER TABLE push_subscriptions ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE push_subscriptions
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_support_conv_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'support_conversations'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_support_conv_pk = 0, 'ALTER TABLE support_conversations ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE support_conversations
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_support_msg_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'support_messages'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_support_msg_pk = 0, 'ALTER TABLE support_messages ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE support_messages
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_support_attach_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'support_message_attachments'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_support_attach_pk = 0, 'ALTER TABLE support_message_attachments ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE support_message_attachments
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_support_reads_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'support_conversation_reads'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(
    @has_support_reads_pk = 0,
    'ALTER TABLE support_conversation_reads ADD PRIMARY KEY (conversation_id, user_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------------------------
-- 5) Indexes attendus.
-- -------------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_user_read') = 0,
    'CREATE INDEX idx_notifications_user_read ON notifications (user_id, is_read)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_created') = 0,
    'CREATE INDEX idx_notifications_created ON notifications (created_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_popup_shown') = 0,
    'CREATE INDEX idx_notifications_popup_shown ON notifications (user_id, is_read, popup_shown_at, id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_announcement') = 0,
    'CREATE INDEX idx_notifications_announcement ON notifications (announcement_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_importance') = 0,
    'CREATE INDEX idx_notifications_importance ON notifications (importance)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notifications_read_at') = 0,
    'CREATE INDEX idx_notifications_read_at ON notifications (read_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'push_subscriptions' AND INDEX_NAME = 'uq_push_subscriptions_endpoint_hash') = 0,
    'CREATE UNIQUE INDEX uq_push_subscriptions_endpoint_hash ON push_subscriptions (endpoint_hash)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'push_subscriptions' AND INDEX_NAME = 'idx_push_subscriptions_user_active') = 0,
    'CREATE INDEX idx_push_subscriptions_user_active ON push_subscriptions (user_id, is_active)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'push_notification_queue' AND INDEX_NAME = 'idx_push_queue_status_next') = 0,
    'CREATE INDEX idx_push_queue_status_next ON push_notification_queue (status, next_attempt_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'push_notification_queue' AND INDEX_NAME = 'idx_push_queue_user') = 0,
    'CREATE INDEX idx_push_queue_user ON push_notification_queue (user_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'push_notification_queue' AND INDEX_NAME = 'idx_push_queue_notification') = 0,
    'CREATE INDEX idx_push_queue_notification ON push_notification_queue (notification_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_conversations' AND INDEX_NAME = 'idx_support_conv_status') = 0,
    'CREATE INDEX idx_support_conv_status ON support_conversations (status, last_message_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_conversations' AND INDEX_NAME = 'idx_support_conv_creator') = 0,
    'CREATE INDEX idx_support_conv_creator ON support_conversations (created_by, last_message_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_conversations' AND INDEX_NAME = 'idx_support_conv_assigned') = 0,
    'CREATE INDEX idx_support_conv_assigned ON support_conversations (assigned_to, last_message_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_messages' AND INDEX_NAME = 'idx_support_msg_conv') = 0,
    'CREATE INDEX idx_support_msg_conv ON support_messages (conversation_id, id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_messages' AND INDEX_NAME = 'idx_support_msg_sender') = 0,
    'CREATE INDEX idx_support_msg_sender ON support_messages (sender_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_message_attachments' AND INDEX_NAME = 'idx_support_attach_msg') = 0,
    'CREATE INDEX idx_support_attach_msg ON support_message_attachments (message_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------------------------
-- 6) Foreign keys restaurables sur ce perimetre.
-- -------------------------------------------------------------------------
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND CONSTRAINT_NAME = 'fk_notifications_user') = 0,
    'ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND CONSTRAINT_NAME = 'fk_notifications_announcement') = 0,
    'ALTER TABLE notifications ADD CONSTRAINT fk_notifications_announcement FOREIGN KEY (announcement_id) REFERENCES admin_announcements(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'push_subscriptions' AND CONSTRAINT_NAME = 'fk_push_subscriptions_user') = 0,
    'ALTER TABLE push_subscriptions ADD CONSTRAINT fk_push_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'push_notification_queue' AND CONSTRAINT_NAME = 'fk_push_queue_notification') = 0,
    'ALTER TABLE push_notification_queue ADD CONSTRAINT fk_push_queue_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'push_notification_queue' AND CONSTRAINT_NAME = 'fk_push_queue_user') = 0,
    'ALTER TABLE push_notification_queue ADD CONSTRAINT fk_push_queue_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_conversations' AND CONSTRAINT_NAME = 'fk_support_conv_creator') = 0,
    'ALTER TABLE support_conversations ADD CONSTRAINT fk_support_conv_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_conversations' AND CONSTRAINT_NAME = 'fk_support_conv_assigned') = 0,
    'ALTER TABLE support_conversations ADD CONSTRAINT fk_support_conv_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_messages' AND CONSTRAINT_NAME = 'fk_support_msg_conv') = 0,
    'ALTER TABLE support_messages ADD CONSTRAINT fk_support_msg_conv FOREIGN KEY (conversation_id) REFERENCES support_conversations(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_messages' AND CONSTRAINT_NAME = 'fk_support_msg_sender') = 0,
    'ALTER TABLE support_messages ADD CONSTRAINT fk_support_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_message_attachments' AND CONSTRAINT_NAME = 'fk_support_attach_msg') = 0,
    'ALTER TABLE support_message_attachments ADD CONSTRAINT fk_support_attach_msg FOREIGN KEY (message_id) REFERENCES support_messages(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_conversation_reads' AND CONSTRAINT_NAME = 'fk_support_reads_conv') = 0,
    'ALTER TABLE support_conversation_reads ADD CONSTRAINT fk_support_reads_conv FOREIGN KEY (conversation_id) REFERENCES support_conversations(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_conversation_reads' AND CONSTRAINT_NAME = 'fk_support_reads_user') = 0,
    'ALTER TABLE support_conversation_reads ADD CONSTRAINT fk_support_reads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
