-- Schema v13 : rendez-vous flexibles (dates optionnelles, statut, confirmation).

ALTER TABLE course_meetings
    MODIFY start_at DATETIME NULL,
    MODIFY end_at DATETIME NULL,
    ADD COLUMN status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending' AFTER mode,
    ADD COLUMN student_id BIGINT UNSIGNED NULL AFTER status,
    ADD COLUMN confirmed_by BIGINT UNSIGNED NULL AFTER student_id,
    ADD CONSTRAINT fk_course_meetings_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_course_meetings_confirmed FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL;
