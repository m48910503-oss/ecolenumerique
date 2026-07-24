-- Schéma v3 : supprime l'indicatif et le format téléphonique séparé du profil utilisateur.

ALTER TABLE users
    DROP COLUMN IF EXISTS phone_country_code,
    DROP COLUMN IF EXISTS phone_e164;
