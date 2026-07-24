-- Schéma v6 : ajoute le mode de diffusion du cours et un lieu optionnel pour le présentiel.

ALTER TABLE courses
    ADD COLUMN delivery_mode ENUM('online','presential') NOT NULL DEFAULT 'online' AFTER level,
    ADD COLUMN course_location VARCHAR(190) NULL AFTER delivery_mode;
