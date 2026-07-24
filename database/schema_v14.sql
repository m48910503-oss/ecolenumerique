-- Schéma v14 : association d'un rendez-vous à un chapitre du cours.

ALTER TABLE course_meetings
    ADD COLUMN chapter_id BIGINT UNSIGNED NULL AFTER course_id,
    ADD CONSTRAINT fk_course_meetings_chapter FOREIGN KEY (chapter_id) REFERENCES course_chapters(id) ON DELETE SET NULL;

