-- Schéma v9 : positions de lecture vidéo (reprise).

CREATE TABLE IF NOT EXISTS lesson_positions (
    user_id     BIGINT UNSIGNED NOT NULL,
    course_id   BIGINT UNSIGNED NOT NULL,
    lesson_id   BIGINT UNSIGNED NOT NULL,
    seconds     INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, lesson_id),
    KEY idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

