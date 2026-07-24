-- Schema v51 : reponse enseignant sur les avis de cours.

ALTER TABLE course_ratings
    ADD COLUMN IF NOT EXISTS teacher_reply_text TEXT NULL AFTER hidden_at,
    ADD COLUMN IF NOT EXISTS teacher_reply_user_id BIGINT UNSIGNED NULL AFTER teacher_reply_text,
    ADD COLUMN IF NOT EXISTS teacher_reply_at DATETIME NULL AFTER teacher_reply_user_id,
    ADD INDEX idx_rating_course_reply (course_id, teacher_reply_at),
    ADD CONSTRAINT fk_rating_teacher_reply_user FOREIGN KEY (teacher_reply_user_id) REFERENCES users(id) ON DELETE SET NULL;
