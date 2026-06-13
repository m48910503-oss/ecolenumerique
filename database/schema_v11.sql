-- Schéma v11 : ajout du versionnage des cours (lien vers le cours d'origine).

ALTER TABLE courses
    ADD COLUMN version_of BIGINT UNSIGNED NULL AFTER seo_description,
    ADD CONSTRAINT fk_courses_version_of FOREIGN KEY (version_of) REFERENCES courses(id) ON DELETE SET NULL;

