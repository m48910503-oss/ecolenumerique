<?php
// Configuration centrale (modifiable selon votre environnement local ou serveur).
// Remplacez les identifiants par ceux de votre instance MySQL.
return [
    'app' => [
        // URL absolue de base (utilisée pour les emails de vérification/reset). Ajustez selon votre environnement.
        'base_url' => 'http://localhost/ecolenumerique',
        'error_log_path' => __DIR__ . '/../storage/logs/application-error.log',
        'from_email' => 'no-reply@ecolenumerique.test',
        'from_name' => 'École Numérique',
    ], 
    'mail' => [ 
        // driver:
        // - 'log'  : ecrit dans storage/mail.log (dev local)
        // - 'smtp' : envoi SMTP reel (recommande production)
        // - 'mail' : fallback mail() natif PHP
        'driver' => 'log',
        'log_path' => __DIR__ . '/../storage/mail.log',
        'smtp' => [
            'host' => '',
            'port' => 587,
            // tls | ssl | none
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'timeout' => 15,
        ],
    ],
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=ecolenumerique;charset=utf8mb4',
        'user' => 'root',
        'pass' => '',
        // Options PDO sécurisées : pas d'émulation, exceptions activées, fetch associatif.
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    // Google Drive (option A : depot direct via Service Account).
    'drive' => [
        // Chemin absolu vers le JSON du service account (obligatoirement hors depot/web root).
        'service_account_json' => trim((string) (getenv('APP_DRIVE_SERVICE_ACCOUNT_JSON') ?: '')),
        // ID du dossier Drive de destination (partagez ce dossier avec le service account).
        'root_folder_id' => getenv('APP_DRIVE_ROOT_FOLDER_ID') ?: '',
        // Rendre les fichiers lisibles via lien public (recommande pour la correction).
        'public_link' => true,
        // Taille max d'upload (Mo) cote application.
        'max_upload_mb' => 25,
        // OAuth (Drive perso). Remplir apres creation des identifiants OAuth.
        'oauth_client_id' => getenv('APP_GOOGLE_OAUTH_CLIENT_ID') ?: '',
        'oauth_client_secret' => getenv('APP_GOOGLE_OAUTH_CLIENT_SECRET') ?: '',
        'oauth_token_path' => trim((string) (getenv('APP_DRIVE_OAUTH_TOKEN_PATH') ?: '')),
    ],
    // OAuth social login.
    'oauth' => [
        // Google Sign-In (optionnel). Si vide, fallback sur drive.oauth_client_id/client_secret.
        'google_client_id' => '',
        'google_client_secret' => '',
    ],
    // Paiements (FedaPay).
    'payments' => [
        'fedapay' => [
            // Cle publique (optionnelle pour API server-side).
            'public_key' => getenv('PAY_FEDAPAY_PUBLIC_KEY') ?: '',
            // Cle secrete API (obligatoire pour encaissement).
            'secret_key' => getenv('PAY_FEDAPAY_SECRET_KEY') ?: '',
            // sandbox | live | production | dev
            'environment' => getenv('PAY_FEDAPAY_ENV') ?: 'sandbox',
            // Secret de webhook (X-FEDAPAY-SIGNATURE).
            'webhook_secret' => getenv('PAY_FEDAPAY_WEBHOOK_SECRET') ?: '',
        ],
        
    ],
    // Web Push (VAPID). Necessite HTTPS en production.
    'webpush' => [
        'enabled' => true,
        // Cle publique VAPID (Base64URL, format non compresse 65 octets).
        'vapid_public_key' => 'BLwLa7GyYhT1cyWUYsC--IDll-No2OfnYxqMSgPZQBLRFJ7c_3znWwB64y7DBIlmoSviPmK8E2K3ywAf06gAQOk',
        // Chemin vers la cle privee VAPID au format PEM.
        'vapid_private_key_path' => __DIR__ . '/../storage/webpush/vapid_private.pem',
        // Contact requis par VAPID (mailto:... ou https://...).
        'vapid_subject' => 'mailto:no-reply@ecolenumerique.test',
    ],
];
