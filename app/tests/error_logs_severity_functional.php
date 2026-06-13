<?php
declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'error-log-severity-' . bin2hex(random_bytes(8));
if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
    fwrite(STDERR, "Unable to create temp dir.\n");
    exit(1);
}

define('APP_ERROR_LOG_PATH', $tempDir . DIRECTORY_SEPARATOR . 'application-error.log');

$logLines = [
    '[2026-04-19T11:00:00+00:00] CRITICAL fedapay.api Paiement critique',
    '[2026-04-19T11:01:00+00:00] ERROR google.oauth Echec Google',
    '[2026-04-19T11:02:00+00:00] WARNING drive.oauth Etat OAuth invalide',
    '[2026-04-19T11:03:00+00:00] INFO mail.delivery Message envoye',
    '[2026-04-19T11:04:00+00:00] Ligne sans niveau explicite',
];
file_put_contents(APP_ERROR_LOG_PATH, implode(PHP_EOL, $logLines) . PHP_EOL);

require $rootDir . '/app/bootstrap.php';

$service = new \App\Services\Admin\ErrorLogsService();
$data = $service->buildViewData([
    'log' => 'application',
    'limit' => 50,
]);

$errors = [];
$summary = is_array($data['severitySummary'] ?? null) ? $data['severitySummary'] : [];
$entries = is_array($data['logEntries'] ?? null) ? $data['logEntries'] : [];

if (($summary['critical'] ?? 0) !== 1) {
    $errors[] = 'Severity summary must count one critical entry.';
}
if (($summary['error'] ?? 0) !== 1) {
    $errors[] = 'Severity summary must count one error entry.';
}
if (($summary['warning'] ?? 0) !== 1) {
    $errors[] = 'Severity summary must count one warning entry.';
}
if (($summary['info'] ?? 0) !== 1) {
    $errors[] = 'Severity summary must count one info entry.';
}
if (($summary['unknown'] ?? 0) !== 1) {
    $errors[] = 'Severity summary must count one unknown entry.';
}

$labelsByChannel = [];
$unknownLineLabel = '';
foreach ($entries as $entry) {
    $labelsByChannel[(string) ($entry['channel'] ?? 'system')] = (string) ($entry['severityLabel'] ?? '');
    if (str_contains((string) ($entry['raw'] ?? ''), 'Ligne sans niveau explicite')) {
        $unknownLineLabel = (string) ($entry['severityLabel'] ?? '');
    }
}

if (($labelsByChannel['fedapay.api'] ?? '') !== 'Critique') {
    $errors[] = 'fedapay.api entry must be labeled Critique.';
}
if (($labelsByChannel['google.oauth'] ?? '') !== 'Erreur') {
    $errors[] = 'google.oauth entry must be labeled Erreur.';
}
if (($labelsByChannel['drive.oauth'] ?? '') !== 'Warning') {
    $errors[] = 'drive.oauth entry must be labeled Warning.';
}
if (($labelsByChannel['mail.delivery'] ?? '') !== 'Info') {
    $errors[] = 'mail.delivery entry must be labeled Info.';
}
if ($unknownLineLabel !== 'Inconnu') {
    $errors[] = 'Unstructured entry must be labeled Inconnu.';
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "error_logs_severity_functional: OK\n";
