<?php
declare(strict_types=1);

// Application bootstrap: secure session, headers, config, PDO and global helpers.

// Default timezone.
date_default_timezone_set('Africa/Porto-Novo');

// Build BASE_PATH from APP_BASE_URL to support subfolders.
$config = require __DIR__ . '/config.php';
$parsedPath = parse_url($config['app']['base_url'] ?? '', PHP_URL_PATH);
$basePath = $parsedPath !== null ? rtrim($parsedPath, '/') : '';
define('BASE_PATH', $basePath === '' ? '/' : $basePath);
 
// Secure session cookies.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if ($isHttps) {
    ini_set('session.cookie_secure', '1');
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
$sessionError = null;
set_error_handler(function (int $errno, string $errstr) use (&$sessionError): bool {
    if (stripos($errstr, 'session_start(): Failed to decode session object') !== false) {
        $sessionError = $errstr;
        return true;
    }
    return false;
});
$sessionOk = session_start();
restore_error_handler();

if (!$sessionOk || $sessionError !== null) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    session_id('');
    session_start();
}

// Security headers.
header_remove('X-Powered-By');
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Permitted-Cross-Domain-Policies: none');
header('Permissions-Policy: accelerometer=(), autoplay=(self "https://www.youtube.com" "https://www.youtube-nocookie.com"), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
header('Cross-Origin-Resource-Policy: same-origin');
if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// CSP adapted to current integrations (Tailwind CDN, YouTube, Google Fonts, FedaPay).
$csp = "default-src 'self'; frame-src https://www.youtube.com https://www.youtube-nocookie.com https://docs.google.com https://checkout.fedapay.com https://sandbox-checkout.fedapay.com https://dev-checkout.fedapay.com https://process.fedapay.com https://sandbox-process.fedapay.com https://dev-process.fedapay.com; frame-ancestors 'self'; img-src 'self' data: blob: https://i.ytimg.com https://cdn.fedapay.com https://images.pexels.com https://i.pravatar.cc; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; script-src 'self' https://cdn.tailwindcss.com https://www.youtube.com https://www.gstatic.com https://cdn.fedapay.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://www.gstatic.com https://cdnjs.cloudflare.com; object-src 'none'; base-uri 'self'; form-action 'self' https://checkout.fedapay.com https://sandbox-checkout.fedapay.com https://dev-checkout.fedapay.com https://process.fedapay.com https://sandbox-process.fedapay.com https://dev-process.fedapay.com";
if ($isHttps) {
    $csp .= '; upgrade-insecure-requests';
}
header('Content-Security-Policy: ' . $csp);

// Shared constants.
define('APP_BASE_URL', rtrim($config['app']['base_url'] ?? '', '/'));
define('APP_FROM_EMAIL', $config['app']['from_email'] ?? 'no-reply@example.test');
define('APP_FROM_NAME', $config['app']['from_name'] ?? 'Application');
define('APP_ERROR_LOG_PATH', $config['app']['error_log_path'] ?? (__DIR__ . '/../storage/logs/application-error.log'));
define('APP_ADMIN_REGISTER_SECRET', trim((string) getenv('ADMIN_REGISTER_SECRET')));
define('APP_MAIL_DRIVER', $config['mail']['driver'] ?? 'smtp');
define('APP_MAIL_LOG_PATH', $config['mail']['log_path'] ?? (__DIR__ . '/../storage/mail.log'));
define('APP_MAIL_SMTP_HOST', $config['mail']['smtp']['host'] ?? '');
define('APP_MAIL_SMTP_PORT', (int) ($config['mail']['smtp']['port'] ?? 587));
define('APP_MAIL_SMTP_ENCRYPTION', $config['mail']['smtp']['encryption'] ?? 'tls');
define('APP_MAIL_SMTP_USERNAME', $config['mail']['smtp']['username'] ?? '');
define('APP_MAIL_SMTP_PASSWORD', $config['mail']['smtp']['password'] ?? '');
define('APP_MAIL_SMTP_TIMEOUT', (int) ($config['mail']['smtp']['timeout'] ?? 15));
$envAppKey = getenv('APP_KEY');
if (!is_string($envAppKey) || trim($envAppKey) === '') {
    $envAppKey = (PHP_SAPI === 'cli') ? hash('sha256', __FILE__) : '';
}
define('APP_KEY', $envAppKey);
define('APP_DRIVE_SERVICE_ACCOUNT_JSON', $config['drive']['service_account_json'] ?? '');
define('APP_DRIVE_ROOT_FOLDER_ID', $config['drive']['root_folder_id'] ?? '');
define('APP_DRIVE_PUBLIC_LINK', (bool) ($config['drive']['public_link'] ?? true));
define('APP_DRIVE_MAX_UPLOAD_MB', (int) ($config['drive']['max_upload_mb'] ?? 25));
define('APP_DRIVE_OAUTH_CLIENT_ID', $config['drive']['oauth_client_id'] ?? '');
define('APP_DRIVE_OAUTH_CLIENT_SECRET', $config['drive']['oauth_client_secret'] ?? '');
define('APP_DRIVE_OAUTH_TOKEN_PATH', $config['drive']['oauth_token_path'] ?? '');

$oauthConfig = is_array($config['oauth'] ?? null) ? $config['oauth'] : [];
$googleOauthClientId = trim((string) ($oauthConfig['google_client_id'] ?? ''));
if ($googleOauthClientId === '') {
    $googleOauthClientId = trim((string) ($config['drive']['oauth_client_id'] ?? ''));
}
$googleOauthClientSecret = trim((string) ($oauthConfig['google_client_secret'] ?? ''));
if ($googleOauthClientSecret === '') {
    $googleOauthClientSecret = trim((string) ($config['drive']['oauth_client_secret'] ?? ''));
}
define('APP_GOOGLE_OAUTH_CLIENT_ID', $googleOauthClientId);
define('APP_GOOGLE_OAUTH_CLIENT_SECRET', $googleOauthClientSecret);

define('PAY_FEDAPAY_PUBLIC_KEY', $config['payments']['fedapay']['public_key'] ?? '');
define('PAY_FEDAPAY_SECRET_KEY', $config['payments']['fedapay']['secret_key'] ?? '');
define('PAY_FEDAPAY_ENV', $config['payments']['fedapay']['environment'] ?? 'sandbox');
define('PAY_FEDAPAY_WEBHOOK_SECRET', $config['payments']['fedapay']['webhook_secret'] ?? '');
define('PAY_CRYPTO_ENABLED', (bool) ($config['payments']['crypto']['enabled'] ?? false));
define('PAY_CRYPTO_LABEL', $config['payments']['crypto']['label'] ?? 'Crypto');
define('PAY_CRYPTO_ADDRESS', $config['payments']['crypto']['address'] ?? '');
define('PAY_CRYPTO_NETWORK', $config['payments']['crypto']['network'] ?? '');
define('PAY_CRYPTO_RATE_XOF', (float) ($config['payments']['crypto']['rate_xof'] ?? 0));
define('PAY_CRYPTO_INSTRUCTIONS', $config['payments']['crypto']['instructions'] ?? '');

$webpushConfig = is_array($config['webpush'] ?? null) ? $config['webpush'] : [];
define('WEBPUSH_ENABLED', (bool) ($webpushConfig['enabled'] ?? false));
define('WEBPUSH_VAPID_PUBLIC_KEY', trim((string) ($webpushConfig['vapid_public_key'] ?? '')));
define('WEBPUSH_VAPID_PRIVATE_KEY_PATH', trim((string) ($webpushConfig['vapid_private_key_path'] ?? '')));
define('WEBPUSH_VAPID_SUBJECT', trim((string) ($webpushConfig['vapid_subject'] ?? '')));

require_once __DIR__ . '/db.php';
$pdo = db($config['db']);

// Make sure App classes are autoloadable before registering shared PDO.
require_once __DIR__ . '/../app/bootstrap.php';
\App\Core\PdoRegistry::set($pdo);

if (PHP_SAPI !== 'cli' && getenv('APP_SCHEMA_ENFORCE') === '1') {
    $health = new \App\Core\SchemaHealth($pdo);
    $missing = $health->check();
    if ($missing['tables'] || $missing['columns'] || $missing['indexes']) {
        http_response_code(500);
        echo 'Schema incomplete. Apply SQL migrations.';
        exit;
    }
}

// Global helpers.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/phone.php';

if (function_exists('capture_referral_code')) {
    capture_referral_code();
}
