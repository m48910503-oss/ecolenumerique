-- Schema v42 : moderation des avis (masquage enseignant).

ALTER TABLE course_ratings
    ADD COLUMN is_hidden_by_teacher TINYINT(1) NOT NULL DEFAULT 0 AFTER comment,
    ADD COLUMN hidden_by_user_id BIGINT UNSIGNED NULL AFTER is_hidden_by_teacher,
    ADD COLUMN hidden_at DATETIME NULL AFTER hidden_by_user_id,
    ADD INDEX idx_rating_course_hidden (course_id, is_hidden_by_teacher, created_at),
    ADD CONSTRAINT fk_rating_hidden_by_user FOREIGN KEY (hidden_by_user_id) REFERENCES users(id) ON DELETE SET NULL;
