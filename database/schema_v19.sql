-- Schema v19 : lier les tentatives d'examens aux creneaux (examens type meeting).

ALTER TABLE exam_attempts
    ADD COLUMN meeting_id BIGINT UNSIGNED NULL AFTER user_id;

CREATE INDEX idx_exam_attempts_meeting_id ON exam_attempts (meeting_id);

