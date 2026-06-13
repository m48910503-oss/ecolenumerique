-- Schema v29 : historique des promotions par cours.
CREATE TABLE IF NOT EXISTS course_promotions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    promo_type ENUM('amount','percent') NOT NULL,
    promo_value DECIMAL(10,2) NOT NULL,
    promo_start_at DATETIME NOT NULL,
    promo_end_at DATETIME NOT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_course_promotions_course (course_id),
    INDEX idx_course_promotions_teacher (teacher_id),
    INDEX idx_course_promotions_end (promo_end_at),
    CONSTRAINT fk_course_promotions_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_course_promotions_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
