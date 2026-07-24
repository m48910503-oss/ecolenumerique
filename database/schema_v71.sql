-- Schema v71 : suppression des champs expertise et années d'expérience des enseignants.

ALTER TABLE teacher_applications
    DROP COLUMN IF EXISTS expertise;

ALTER TABLE teacher_profiles
    DROP COLUMN IF EXISTS expertise,
    DROP COLUMN IF EXISTS years_experience;
