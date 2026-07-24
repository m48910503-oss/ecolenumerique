-- Schema v12 : rendez-vous obligatoires pour les cours (en ligne ou presentiel).

CREATE TABLE IF NOT EXISTS course_meetings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    mode ENUM('online','onsite') NOT NULL DEFAULT 'online',
    start_at DATETIME NOT NULL,
    end_at DATETIME NULL,
    meeting_link VARCHAR(255) NULL,
    meeting_place VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_course_meetings_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    KEY idx_course_meetings_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
