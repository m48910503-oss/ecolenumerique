-- Schema v26 : parrainage (codes, relations, settings, commissions).
ALTER TABLE users
    ADD COLUMN referral_code VARCHAR(32) NULL AFTER email,
    ADD UNIQUE KEY uq_users_referral_code (referral_code);

CREATE TABLE IF NOT EXISTS referrals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_user_id BIGINT UNSIGNED NOT NULL,
    referred_user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_referrals_referred (referred_user_id),
    INDEX idx_referrals_referrer (referrer_user_id),
    CONSTRAINT fk_referrals_referrer FOREIGN KEY (referrer_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_referrals_referred FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teacher_referral_settings (
    teacher_id BIGINT UNSIGNED PRIMARY KEY,
    l1_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    l2_rate DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    l2_enabled TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_teacher_referral_settings_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referral_commissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    order_item_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    buyer_id BIGINT UNSIGNED NOT NULL,
    referrer_id BIGINT UNSIGNED NOT NULL,
    level TINYINT(1) NOT NULL,
    rate_percent DECIMAL(5,2) NOT NULL,
    amount_xof INT UNSIGNED NOT NULL,
    status ENUM('earned','reversed') NOT NULL DEFAULT 'earned',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ref_comm_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_ref_comm_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_ref_comm_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_ref_comm_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ref_comm_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ref_comm_referrer FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ref_comm_teacher (teacher_id),
    INDEX idx_ref_comm_referrer (referrer_id),
    INDEX idx_ref_comm_order_item (order_item_id),
    INDEX idx_ref_comm_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
