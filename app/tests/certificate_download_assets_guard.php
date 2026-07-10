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
$webView = (string) file_get_contents($rootDir . '/app/Views/web/certificate.php');
$adminView = (string) file_get_contents($rootDir . '/app/Views/admin/certificate_preview.php');
$localAsset = $rootDir . '/assets/vendor/html2pdf/html2pdf.bundle.min.js';

assert_true(is_file($localAsset), 'Local html2pdf bundle should exist in assets/vendor/html2pdf.', $errors);
assert_true(
    str_contains($webView, 'assets/vendor/html2pdf/html2pdf.bundle.min.js'),
    'Web certificate view should load the local html2pdf bundle.',
    $errors
);
assert_true(
    str_contains($adminView, 'assets/vendor/html2pdf/html2pdf.bundle.min.js'),
    'Admin certificate preview should load the local html2pdf bundle.',
    $errors
);
assert_true(
    !str_contains($webView, 'cdnjs.cloudflare.com/ajax/libs/html2pdf'),
    'Web certificate view should not depend on the html2pdf CDN.',
    $errors
);
assert_true(
    !str_contains($adminView, 'cdnjs.cloudflare.com/ajax/libs/html2pdf'),
    'Admin certificate preview should not depend on the html2pdf CDN.',
    $errors
);

if ($errors !== []) {
    echo "Certificate download assets guard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Certificate download assets guard OK\n";
exit(0);
