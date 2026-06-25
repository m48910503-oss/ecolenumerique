-- Script de création/admin (à exécuter après avoir appliqué schema_v1/v2/v3).
-- Remplacez les valeurs ci-dessous avant import (email + mot de passe hashé).
-- Pour générer le hash : php -r "echo password_hash('VotreMotDePasseFort', PASSWORD_DEFAULT), \"\n\";"

INSERT INTO users (
    first_name,
    last_name,
    email,
    password_hash,
    status,
    email_verified_at,
    roles_json,
    phone,
    country,
    created_at,
    updated_at
) VALUES (
    'Admin',
    'Principal',
    'admin@example.com',        -- remplacez l'email
    '$2y$10$A_REMPLACER_PAR_VOTRE_HASH', -- remplacez par le hash du mot de passe
    'active',
    NOW(),
    '["admin"]',
    NULL,
    NULL,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    roles_json = '["admin"]',
    status = 'active',
    email_verified_at = NOW();
