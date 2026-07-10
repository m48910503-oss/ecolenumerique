<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'error-log-context-' . bin2hex(random_bytes(8));
if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
    fwrite(STDERR, "Unable to create temp dir.\n");
    exit(1);
}

define('APP_ERROR_LOG_PATH', $tempDir . DIRECTORY_SEPARATOR . 'application-error.log');

$context = json_encode([
    'method' => 'POST',
    'path' => '/transactions',
    'fedapay_env' => 'live',
    'status' => 0,
    'detail' => 'SSL certificate problem: self-signed certificate in certificate chain',
    'ca_bundle' => 'C:\\xampp\\phpMyAdmin\\vendor\\composer\\ca-bundle\\res\\cacert.pem',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$logLines = [
    '[2026-07-10T08:00:00+00:00] ERROR fedapay.api Echec reseau lors d un appel FedaPay. | ' . $context,
];
file_put_contents(APP_ERROR_LOG_PATH, implode(PHP_EOL, $logLines) . PHP_EOL);

require $rootDir . '/app/bootstrap.php';

$service = new \App\Services\Admin\ErrorLogsService();
$data = $service->buildViewData([
    'log' => 'application',
    'limit' => 50,
    'filter' => 'fedapay',
]);

$errors = [];
$entries = is_array($data['logEntries'] ?? null) ? $data['logEntries'] : [];
$insights = is_array($data['logInsights'] ?? null) ? $data['logInsights'] : [];

if (($data['filteredLineCount'] ?? 0) !== 1) {
    $errors[] = 'Fedapay context test must keep one filtered entry.';
}

$entry = $entries[0] ?? [];
$contextData = is_array($entry['contextData'] ?? null) ? $entry['contextData'] : [];
$contextSummary = (string) ($entry['contextSummary'] ?? '');

if (($contextData['method'] ?? '') !== 'POST') {
    $errors[] = 'Context data must expose the request method.';
}
if (($contextData['ca_bundle'] ?? '') !== 'C:\\xampp\\phpMyAdmin\\vendor\\composer\\ca-bundle\\res\\cacert.pem') {
    $errors[] = 'Context data must expose the CA bundle path.';
}
if ($contextSummary === '' || strpos($contextSummary, 'method: POST') === false) {
    $errors[] = 'Context summary must mention the method.';
}
if (strpos($contextSummary, 'path: /transactions') === false) {
    $errors[] = 'Context summary must mention the transaction path.';
}
if (strpos($contextSummary, 'status: 0') === false) {
    $errors[] = 'Context summary must mention the status code.';
}
if (strpos($contextSummary, 'SSL certificate problem') === false) {
    $errors[] = 'Context summary must mention the SSL error detail.';
}
if ($insights === [] || strpos((string) ($insights[0] ?? ''), 'bundle CA') === false) {
    $errors[] = 'Fedapay log insight must explain the CA bundle fix.';
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "error_logs_context_functional: OK\n";
