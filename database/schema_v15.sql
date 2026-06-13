-- Schéma v15 : ajout d'un avatar (URL) pour les utilisateurs.

ALTER TABLE users
    ADD COLUMN avatar_url VARCHAR(255) NULL AFTER email;
