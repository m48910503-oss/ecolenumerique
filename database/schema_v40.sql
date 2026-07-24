-- Schema v40 : reglages avances des examens (settings JSON).

ALTER TABLE exam_sessions
    ADD COLUMN settings_json LONGTEXT NULL AFTER drive_subject_name;

