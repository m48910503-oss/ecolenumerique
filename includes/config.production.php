<?php
// Copie de configuration preparee pour la production.
// Usage recommande:
// 1. Completer les placeholders secrets ci-dessous.
// 2. Sauvegarder une copie de l'actuel includes/config.php.
// 3. Remplacer includes/config.php par ce fichier une fois pret.
//
// Secrets a completer manuellement avant mise en ligne:
// - SMTP password
// - MySQL password
// - Google OAuth client secret
// - FedaPay public key
// - FedaPay secret key
// - FedaPay webhook secret
//
// Variables serveur a definir en plus:
// - APP_ENV=production
// - ADMIN_BOOTSTRAP_REGISTER=0
// - ADMIN_REGISTER_SECRET=...
// - APP_KEY=...
// - APP_SUPPORT_STORAGE_DIR=...
// - APP_DRIVE_OAUTH_TOKEN_PATH=...
// - APP_SCHEMA_ENFORCE=1

return [
    'app' => [
        'base_url' => 'https://www.slidecenter.bj',
        'error_log_path' => __DIR__ . '/../storage/logs/application-error.log',
        'from_email' => 'support@slidecenter.bj',
        'from_name' => 'SlideCenter',
    ],
    'mail' => [
        'driver' => 'smtp',
        'log_path' => __DIR__ . '/../storage/mail.log',
        'smtp' => [
            'host' => 'mail.slidecenter.bj',
            'port' => 465,
            // ssl | tls | none
            'encryption' => 'ssl',
            'username' => 'support@slidecenter.bj',
            'password' => 'A_REMPLIR_MANUELLEMENT',
            'timeout' => 15,
        ],
    ],
    'db' => [
        'dsn' => 'mysql:host=localhost;port=3306;dbname=slidece1_ecolenumerique;charset=utf8mb4',
        'user' => 'slidece1_ecolenumerique',
        'pass' => 'A_REMPLIR_MANUELLEMENT',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    'drive' => [
        // Mode retenu: OAuth Drive utilisateur.
        // Le token OAuth de prod doit etre stocke hors du repo via APP_DRIVE_OAUTH_TOKEN_PATH.
        'service_account_json' => trim((string) (getenv('APP_DRIVE_SERVICE_ACCOUNT_JSON') ?: '')),
        'root_folder_id' => getenv('APP_DRIVE_ROOT_FOLDER_ID') ?: '',
        'public_link' => true,
        'max_upload_mb' => 25,
        // Meme client OAuth reutilise pour Google Login + Drive OAuth.
        'oauth_client_id' => getenv('APP_GOOGLE_OAUTH_CLIENT_ID') ?: '823877393962-kn8o39p4rb28305o13pm5l3q28tmsurm.apps.googleusercontent.com',
        'oauth_client_secret' => getenv('APP_GOOGLE_OAUTH_CLIENT_SECRET') ?: 'A_REMPLIR_MANUELLEMENT',
        'oauth_token_path' => trim((string) (getenv('APP_DRIVE_OAUTH_TOKEN_PATH') ?: '')),
    ],
    'oauth' => [
        'google_client_id' => getenv('APP_GOOGLE_OAUTH_CLIENT_ID') ?: '823877393962-kn8o39p4rb28305o13pm5l3q28tmsurm.apps.googleusercontent.com',
        'google_client_secret' => getenv('APP_GOOGLE_OAUTH_CLIENT_SECRET') ?: 'A_REMPLIR_MANUELLEMENT',
    ],
    'payments' => [
        'fedapay' => [
            'public_key' => getenv('PAY_FEDAPAY_PUBLIC_KEY') ?: 'A_REMPLIR_MANUELLEMENT',
            'secret_key' => getenv('PAY_FEDAPAY_SECRET_KEY') ?: 'A_REMPLIR_MANUELLEMENT',
            'environment' => getenv('PAY_FEDAPAY_ENV') ?: 'live',
            'webhook_secret' => getenv('PAY_FEDAPAY_WEBHOOK_SECRET') ?: 'A_REMPLIR_MANUELLEMENT',
        ],
        
    ],
    'webpush' => [
        // Laisser desactive tant que les cles VAPID ne sont pas configurees.
        'enabled' => false,
        'vapid_public_key' => '',
        'vapid_private_key_path' => '',
        'vapid_subject' => 'mailto:support@slidecenter.bj',
    ],
];
