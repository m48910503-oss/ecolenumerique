-- Schéma v2 : ajoute les colonnes phone et city sur la table users.

ALTER TABLE users
    ADD COLUMN phone VARCHAR(30) NULL AFTER email_verified_at,
    ADD COLUMN city VARCHAR(120) NULL AFTER phone;
