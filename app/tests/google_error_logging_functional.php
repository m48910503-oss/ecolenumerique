<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'google-error-log-' . bin2hex(random_bytes(8));
if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
    fwrite(STDERR, "Unable to create temp dir.\n");
    exit(1);
}

define('BASE_PATH', '/');
define('APP_BASE_URL', 'http://localhost');
define('APP_ERROR_LOG_PATH', $tempDir . DIRECTORY_SEPARATOR . 'application-error.log');
define('APP_DRIVE_OAUTH_CLIENT_ID', '');
define('APP_DRIVE_OAUTH_CLIENT_SECRET', '');
define('APP_DRIVE_OAUTH_TOKEN_PATH', $tempDir . DIRECTORY_SEPARATOR . 'drive-token.json');
define('APP_DRIVE_SERVICE_ACCOUNT_JSON', '');
define('APP_DRIVE_ROOT_FOLDER_ID', '');
define('APP_DRIVE_PUBLIC_LINK', true);
define('APP_DRIVE_MAX_UPLOAD_MB', 25);
define('APP_GOOGLE_OAUTH_CLIENT_ID', '');
define('APP_GOOGLE_OAUTH_CLIENT_SECRET', '');

$_SESSION = [];

require $rootDir . '/app/bootstrap.php';
require $rootDir . '/includes/helpers.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$googleService = new \App\Services\Oauth\GoogleService(
    new \App\Models\OauthGoogleModel($pdo),
    new \App\Services\Shared\ReferralService(new \App\Models\SharedReferralModel($pdo))
);
$googleResult = $googleService->handle([]);

$driveService = new \App\Services\Drive\OauthConnectService();
$driveResult = $driveService->buildAuthorizeUrl();

$errors = [];
if (($googleResult['redirectTo'] ?? '') !== '/login') {
    $errors[] = 'GoogleService should redirect to /login when config is missing.';
}
if (!is_array($driveResult) || !empty($driveResult['ok'])) {
    $errors[] = 'Drive OAuth connect should fail when config is missing.';
}

$logContents = is_file(APP_ERROR_LOG_PATH) ? (string) file_get_contents(APP_ERROR_LOG_PATH) : '';
if ($logContents === '') {
    $errors[] = 'Google/Drive configuration failures must be logged.';
}
if ($logContents !== '' && strpos($logContents, 'google.oauth') === false) {
    $errors[] = 'Google OAuth failure must mention google.oauth channel.';
}
if ($logContents !== '' && strpos($logContents, 'drive.oauth') === false) {
    $errors[] = 'Drive OAuth failure must mention drive.oauth channel.';
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "google_error_logging_functional: OK\n";
