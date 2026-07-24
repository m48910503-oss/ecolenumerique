-- Schema v22 : stockage des metadonnees Drive pour les remises.

ALTER TABLE exam_files
    ADD COLUMN drive_file_id VARCHAR(128) NULL AFTER drive_link,
    ADD COLUMN drive_view_link VARCHAR(255) NULL AFTER drive_file_id,
    ADD COLUMN original_name VARCHAR(255) NULL AFTER drive_view_link,
    ADD COLUMN mime_type VARCHAR(120) NULL AFTER original_name,
    ADD COLUMN size_bytes BIGINT UNSIGNED NULL AFTER mime_type;
