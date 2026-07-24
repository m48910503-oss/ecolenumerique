-- Schema v17 : duree (minutes) pour les rendez-vous de cours.

ALTER TABLE course_meetings
    ADD COLUMN duration_min INT UNSIGNED NULL AFTER mode;

