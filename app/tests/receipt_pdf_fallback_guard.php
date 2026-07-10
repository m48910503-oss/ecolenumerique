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
$builder = (string) file_get_contents($rootDir . '/app/Services/Web/ReceiptPdfDocumentBuilder.php');
$controller = (string) file_get_contents($rootDir . '/app/Controllers/Student/OrdersController.php');

assert_true(str_contains($builder, 'buildFallbackPdf('), 'Receipt PDF builder should include a PHP fallback PDF generator.', $errors);
assert_true(str_contains($builder, 'assembleSimplePdf('), 'Receipt PDF builder should assemble a standalone PDF.', $errors);
assert_true(
    str_contains($builder, 'buildFromHtml(string $html, ?array $fallbackDocument = null)'),
    'Receipt PDF builder should accept fallback data when rendering HTML fails.',
    $errors
);
assert_true(
    str_contains($builder, 'return $this->buildFallbackPdf($fallbackDocument ?? $receiptData);'),
    'Receipt PDF builder should fall back to the receipt data when browser PDF generation fails.',
    $errors
);
assert_true(
    str_contains($controller, 'build($receiptData, $receiptData)'),
    'Orders controller should pass receipt data to the PDF fallback path.',
    $errors
);

if ($errors !== []) {
    echo "Receipt PDF fallback guard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Receipt PDF fallback guard OK\n";
exit(0);
