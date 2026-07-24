-- Schema v24 : sujet d'examen Drive (lien + nom du fichier).
ALTER TABLE exam_sessions
    ADD COLUMN drive_subject_link VARCHAR(255) NULL AFTER drive_expected,
    ADD COLUMN drive_subject_name VARCHAR(255) NULL AFTER drive_subject_link;
