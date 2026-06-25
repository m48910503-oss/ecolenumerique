-- Schéma v73 : nettoyage final des champs ville et indicatif sur users.

ALTER TABLE users
    DROP COLUMN IF EXISTS city,
    DROP COLUMN IF EXISTS phone_country_code,
    DROP COLUMN IF EXISTS phone_e164;
