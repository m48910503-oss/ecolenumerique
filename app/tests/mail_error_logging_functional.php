<?php
declare(strict_types=1);

$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mail-error-log-' . bin2hex(random_bytes(8));
if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
    fwrite(STDERR, "Unable to create temp dir.\n");
    exit(1);
}

define('APP_ERROR_LOG_PATH', $tempDir . DIRECTORY_SEPARATOR . 'application-error.log');
define('APP_MAIL_DRIVER', 'smtp');
define('APP_MAIL_LOG_PATH', $tempDir . DIRECTORY_SEPARATOR . 'mail.log');
define('APP_FROM_EMAIL', 'no-reply@example.test');
define('APP_FROM_NAME', 'Test App');
define('APP_MAIL_SMTP_HOST', '');
define('APP_MAIL_SMTP_PORT', 587);
define('APP_MAIL_SMTP_ENCRYPTION', 'tls');
define('APP_MAIL_SMTP_USERNAME', '');
define('APP_MAIL_SMTP_PASSWORD', '');
define('APP_MAIL_SMTP_TIMEOUT', 5);

require dirname(__DIR__, 2) . '/includes/helpers.php';

$errors = [];

$sent = send_email('user@example.test', 'SMTP check', 'Body');
if ($sent !== false) {
    $errors[] = 'send_email must fail when SMTP host is missing.';
}

$mailError = function_exists('last_mail_error') ? last_mail_error() : [];
if (!is_array($mailError) || $mailError === []) {
    $errors[] = 'last_mail_error must expose the delivery failure.';
}

$logContents = is_file(APP_ERROR_LOG_PATH) ? (string) file_get_contents(APP_ERROR_LOG_PATH) : '';
if ($logContents === '') {
    $errors[] = 'application error log must be written.';
}
if ($logContents !== '' && strpos($logContents, 'mail.delivery') === false) {
    $errors[] = 'application error log must mention the mail.delivery channel.';
}
if ($logContents !== '' && strpos($logContents, 'Configuration SMTP incomplete: host absent.') === false) {
    $errors[] = 'application error log must mention the SMTP host failure.';
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}

echo "mail_error_logging_functional: OK\n";
