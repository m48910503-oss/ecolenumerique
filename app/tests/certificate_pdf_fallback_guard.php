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
$builder = (string) file_get_contents($rootDir . '/app/Services/Web/CertificatePdfBuilder.php');
$webController = (string) file_get_contents($rootDir . '/app/Controllers/Web/CertificateController.php');
$adminController = (string) file_get_contents($rootDir . '/app/Controllers/Admin/CertificatePreviewController.php');

assert_true(str_contains($builder, 'buildFallbackPdf('), 'Certificate PDF builder should include a PHP fallback PDF generator.', $errors);
assert_true(str_contains($builder, 'assembleSimplePdf('), 'Certificate PDF builder should assemble a standalone PDF.', $errors);
assert_true(
    str_contains($webController, 'buildFromUrl($htmlSourceUrl, $fallbackDocument)'),
    'Web certificate controller should pass fallback data to the PDF builder.',
    $errors
);
assert_true(
    str_contains($adminController, 'buildFromUrl($renderUrl, $fallbackDocument)'),
    'Admin certificate preview controller should pass fallback data to the PDF builder.',
    $errors
);

if ($errors !== []) {
    echo "Certificate PDF fallback guard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Certificate PDF fallback guard OK\n";
exit(0);
