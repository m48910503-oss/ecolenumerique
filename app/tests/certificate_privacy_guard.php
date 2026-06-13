<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

/**
 * @param array<int,string> $errors
 */
function assert_true(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

$errors = [];
$bootstrap = (string) file_get_contents($rootDir . '/includes/bootstrap.php');
$service = (string) file_get_contents($rootDir . '/app/Services/Web/CertificateService.php');
$view = (string) file_get_contents($rootDir . '/app/Views/web/certificate.php');

assert_true(!str_contains($bootstrap, 'api.qrserver.com'), 'CSP should not allow qrserver anymore.', $errors);
assert_true(!str_contains($service, 'api.qrserver.com'), 'Certificate service must not call qrserver.', $errors);
assert_true(str_contains($service, "\$qrVerifyUrl = '';"), 'Certificate service should disable third-party QR URLs.', $errors);
assert_true(str_contains($view, 'Verification locale uniquement') || str_contains($view, 'Vérification locale uniquement'), 'Certificate view should mention local-only verification.', $errors);

if ($errors !== []) {
    echo "Certificate privacy guard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Certificate privacy guard OK\n";
exit(0);
