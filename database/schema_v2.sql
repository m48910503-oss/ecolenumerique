-- Schéma v2 : supprime le champ ville du profil utilisateur.

ALTER TABLE users
    DROP COLUMN IF EXISTS city;
