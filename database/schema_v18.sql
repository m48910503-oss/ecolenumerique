-- Schema v18 : indicateur de mise a jour pour cours publies.
ALTER TABLE courses
    ADD COLUMN update_in_progress TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
