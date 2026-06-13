-- Schéma v5 : ajoute un fichier optionnel général par cours.

ALTER TABLE courses
    ADD COLUMN general_file_path VARCHAR(255) NULL AFTER cover_variants_json,
    ADD COLUMN general_file_mime VARCHAR(120) NULL AFTER general_file_path,
    ADD COLUMN general_file_size BIGINT UNSIGNED NULL AFTER general_file_mime;
