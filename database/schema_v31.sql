-- Schema v31 : promotion ciblee par utilisateur.

CREATE TABLE IF NOT EXISTS course_promotion_targets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    promotion_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_course_promotion_targets (promotion_id, user_id),
    INDEX idx_course_promotion_targets_user (user_id),
    CONSTRAINT fk_course_promotion_targets_promo FOREIGN KEY (promotion_id) REFERENCES course_promotions(id) ON DELETE CASCADE,
    CONSTRAINT fk_course_promotion_targets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
