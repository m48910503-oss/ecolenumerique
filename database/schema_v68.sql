-- Schema v68 : experience pedagogique sur les candidatures enseignant.

ALTER TABLE teacher_applications
    ADD COLUMN IF NOT EXISTS teaching_experience VARCHAR(80) NULL AFTER bio;
