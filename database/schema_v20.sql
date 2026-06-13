-- Schema v20 : liens de partage courts type Udemy pour les cours.

ALTER TABLE courses
    ADD COLUMN share_token VARCHAR(120) NULL AFTER slug;

CREATE UNIQUE INDEX uq_courses_share_token ON courses (share_token);

