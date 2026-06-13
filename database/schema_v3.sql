-- Schéma v3 : ajoute le pays et des colonnes optionnelles pour gérer les formats téléphoniques.

ALTER TABLE users
    ADD COLUMN phone_country_code VARCHAR(8) NULL AFTER phone,
    ADD COLUMN phone_e164 VARCHAR(32) NULL AFTER phone_country_code,
    ADD COLUMN country VARCHAR(120) NULL AFTER city;
