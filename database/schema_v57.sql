-- Schema v57 : annonces admin avancees (ciblage, importance, workflow, stats).

ALTER TABLE admin_announcements
    ADD COLUMN IF NOT EXISTS status ENUM('draft','pending_review','published','archived') NOT NULL DEFAULT 'draft',
    ADD COLUMN IF NOT EXISTS audience ENUM('all','student','teacher') NOT NULL DEFAULT 'all',
    ADD COLUMN IF NOT EXISTS category ENUM('general','system','learning','support','promo') NOT NULL DEFAULT 'general',
    ADD COLUMN IF NOT EXISTS importance ENUM('info','important','urgent') NOT NULL DEFAULT 'info',
    ADD COLUMN IF NOT EXISTS summary VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS title_b VARCHAR(190) NULL,
    ADD COLUMN IF NOT EXISTS ab_enabled TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS sent_count_b INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS read_count INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS read_count_b INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS click_count INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS click_count_b INT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS published_by BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS approved_by BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS published_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL,
    ADD INDEX idx_admin_announcements_status (status),
    ADD INDEX idx_admin_announcements_audience (audience),
    ADD INDEX idx_admin_announcements_importance (importance),
    ADD INDEX idx_admin_announcements_category (category),
    ADD INDEX idx_admin_announcements_published (published_at);

CREATE TABLE IF NOT EXISTS admin_announcement_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id BIGINT UNSIGNED NOT NULL,
    actor_id BIGINT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ann_events_announcement (announcement_id),
    INDEX idx_ann_events_action (action),
    CONSTRAINT fk_ann_events_announcement FOREIGN KEY (announcement_id) REFERENCES admin_announcements(id) ON DELETE CASCADE,
    CONSTRAINT fk_ann_events_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS announcement_id BIGINT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS importance ENUM('info','important','urgent') NOT NULL DEFAULT 'info',
    ADD COLUMN IF NOT EXISTS summary VARCHAR(200) NULL,
    ADD COLUMN IF NOT EXISTS variant CHAR(1) NULL,
    ADD COLUMN IF NOT EXISTS read_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS clicked_at DATETIME NULL,
    ADD INDEX idx_notifications_announcement (announcement_id),
    ADD INDEX idx_notifications_importance (importance),
    ADD INDEX idx_notifications_read_at (read_at),
    ADD CONSTRAINT fk_notifications_announcement FOREIGN KEY (announcement_id) REFERENCES admin_announcements(id) ON DELETE SET NULL;
