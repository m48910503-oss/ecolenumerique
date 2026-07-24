-- Schema v35 : favoris de cours (liste d envies avant achat).

CREATE TABLE IF NOT EXISTS course_favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_course_favorites_user_course (user_id, course_id),
    INDEX idx_course_favorites_user_created (user_id, created_at),
    INDEX idx_course_favorites_course (course_id),
    CONSTRAINT fk_course_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_course_favorites_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
