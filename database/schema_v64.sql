-- Schema v64 : rattrapage des tables/indexes encore manquants
-- pour aligner la base locale avec la couverture de schema du depot.

CREATE TABLE IF NOT EXISTS company_metrics_daily (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    metric_date DATE NOT NULL,
    members_total INT UNSIGNED NOT NULL DEFAULT 0,
    members_active INT UNSIGNED NOT NULL DEFAULT 0,
    invites_pending INT UNSIGNED NOT NULL DEFAULT 0,
    activation_30d DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    wau INT UNSIGNED NOT NULL DEFAULT 0,
    mau INT UNSIGNED NOT NULL DEFAULT 0,
    wau_mau_ratio DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    retention_90d DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    expansion_30d INT NOT NULL DEFAULT 0,
    churn_30d DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_metrics_daily (company_id, metric_date),
    INDEX idx_company_metrics_daily_date (metric_date),
    CONSTRAINT fk_company_metrics_daily_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_auth_provider') = 0,
    'CREATE INDEX idx_users_auth_provider ON users (auth_provider)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_mfa_enabled') = 0,
    'CREATE INDEX idx_users_mfa_enabled ON users (mfa_enabled)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_referral_code') = 0,
    'CREATE UNIQUE INDEX uq_users_referral_code ON users (referral_code)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND INDEX_NAME = 'idx_order_items_promo_scope') = 0,
    'CREATE INDEX idx_order_items_promo_scope ON order_items (promo_scope)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND INDEX_NAME = 'idx_order_items_coupon_code') = 0,
    'CREATE INDEX idx_order_items_coupon_code ON order_items (coupon_code)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND INDEX_NAME = 'idx_order_items_promotion_id') = 0,
    'CREATE INDEX idx_order_items_promotion_id ON order_items (promotion_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND CONSTRAINT_NAME = 'fk_order_items_promotion') = 0,
    'ALTER TABLE order_items ADD CONSTRAINT fk_order_items_promotion FOREIGN KEY (promotion_id) REFERENCES course_promotions(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND CONSTRAINT_NAME = 'fk_order_items_company') = 0,
    'ALTER TABLE order_items ADD CONSTRAINT fk_order_items_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_webhook_events' AND INDEX_NAME = 'uq_payment_webhook_events_provider_key') = 0,
    'CREATE UNIQUE INDEX uq_payment_webhook_events_provider_key ON payment_webhook_events (provider, event_key)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_webhook_events' AND INDEX_NAME = 'idx_payment_webhook_events_processed') = 0,
    'CREATE INDEX idx_payment_webhook_events_processed ON payment_webhook_events (processed_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_payouts' AND INDEX_NAME = 'idx_teacher_payouts_teacher') = 0,
    'CREATE INDEX idx_teacher_payouts_teacher ON teacher_payouts (teacher_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_payouts' AND INDEX_NAME = 'idx_teacher_payouts_status') = 0,
    'CREATE INDEX idx_teacher_payouts_status ON teacher_payouts (status)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_payouts' AND CONSTRAINT_NAME = 'fk_teacher_payouts_teacher') = 0,
    'ALTER TABLE teacher_payouts ADD CONSTRAINT fk_teacher_payouts_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_payouts' AND CONSTRAINT_NAME = 'fk_teacher_payouts_admin') = 0,
    'ALTER TABLE teacher_payouts ADD CONSTRAINT fk_teacher_payouts_admin FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
