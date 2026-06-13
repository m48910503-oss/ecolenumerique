-- Schema v36 : fournisseur d'authentification utilisateur (local/google).

ALTER TABLE users
    ADD COLUMN auth_provider ENUM('local','google') NOT NULL DEFAULT 'local' AFTER email_verified_at,
    ADD INDEX idx_users_auth_provider (auth_provider);
