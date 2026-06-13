<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fedapay-error-log-' . bin2hex(random_bytes(8));
if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
    fwrite(STDERR, "Unable to create temp dir.\n");
    exit(1);
}

define('APP_ERROR_LOG_PATH', $tempDir . DIRECTORY_SEPARATOR . 'application-error.log');
define('PAY_FEDAPAY_ENV', 'sandbox');

require $rootDir . '/app/bootstrap.php';
require $rootDir . '/includes/helpers.php';

$service = new \App\Services\Shared\FedapayService();
$result = $service->request('GET', '/transactions', null);

$errors = [];
if (!is_array($result) || !empty($result['ok'])) {
    $errors[] = 'Fedapay request must fail without secret key.';
}
if (($result['error'] ?? '') !== 'missing_key') {
    $errors[] = 'Fedapay request must expose missing_key.';
}

$logContents = is_file(APP_ERROR_LOG_PATH) ? (string) file_get_contents(APP_ERROR_LOG_PATH) : '';
if ($logContents === '') {
    $errors[] = 'Fedapay missing key must be logged.';
}
if ($logContents !== '' && strpos($logContents, 'fedapay.api') === false) {
    $errors[] = 'Fedapay log entry must mention fedapay.api channel.';
}
if ($logContents !== '' && strpos($logContents, 'Cle secrete FedaPay manquante.') === false) {
    $errors[] = 'Fedapay log entry must mention missing secret key.';
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "fedapay_error_logging_functional: OK\n";
