<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'error-log-filter-' . bin2hex(random_bytes(8));
if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
    fwrite(STDERR, "Unable to create temp dir.\n");
    exit(1);
}

define('APP_ERROR_LOG_PATH', $tempDir . DIRECTORY_SEPARATOR . 'application-error.log');

$logLines = [
    '[2026-04-19T10:00:00+00:00] ERROR google.oauth Echec Google',
    '[2026-04-19T10:01:00+00:00] WARNING drive.oauth Etat OAuth invalide',
    '[2026-04-19T10:02:00+00:00] ERROR fedapay.api Cle manquante',
    '[2026-04-19T10:03:00+00:00] ERROR mail.delivery SMTP failed',
];
file_put_contents(APP_ERROR_LOG_PATH, implode(PHP_EOL, $logLines) . PHP_EOL);

require $rootDir . '/app/bootstrap.php';

$service = new \App\Services\Admin\ErrorLogsService();
$googleData = $service->buildViewData([
    'log' => 'application',
    'limit' => 50,
    'filter' => 'google',
]);
$searchData = $service->buildViewData([
    'log' => 'application',
    'limit' => 50,
    'q' => 'SMTP',
]);

$errors = [];
if (($googleData['filteredLineCount'] ?? 0) !== 1) {
    $errors[] = 'Google filter must return exactly one line.';
}
if (($searchData['filteredLineCount'] ?? 0) !== 1) {
    $errors[] = 'Search filter must return exactly one matching line.';
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "error_logs_filter_functional: OK\n";
