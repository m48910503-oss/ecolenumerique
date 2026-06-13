-- Schema v21 : variantes QCM (3 versions par examen).

ALTER TABLE exam_questions
    ADD COLUMN variant TINYINT(1) NOT NULL DEFAULT 1 AFTER exam_id;

CREATE INDEX idx_exam_questions_variant ON exam_questions (exam_id, variant, order_index);

ALTER TABLE exam_attempts
    ADD COLUMN variant_index TINYINT(1) NOT NULL DEFAULT 1 AFTER user_id;

CREATE INDEX idx_exam_attempts_variant ON exam_attempts (exam_id, user_id, variant_index);

