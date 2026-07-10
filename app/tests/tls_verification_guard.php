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
$fedapay = (string) file_get_contents($rootDir . '/app/Services/Shared/FedapayService.php');
$webPush = (string) file_get_contents($rootDir . '/app/Services/Shared/WebPushService.php');
$receiptBuilder = (string) file_get_contents($rootDir . '/app/Services/Web/ReceiptPdfDocumentBuilder.php');

foreach ([
    'FedaPay' => $fedapay,
    'Web Push' => $webPush,
    'Receipt PDF' => $receiptBuilder,
] as $label => $content) {
    assert_true(!str_contains($content, 'CURLOPT_SSL_VERIFYPEER => false'), $label . ' should not disable peer verification.', $errors);
    assert_true(!str_contains($content, 'CURLOPT_SSL_VERIFYHOST => false'), $label . ' should not disable host verification.', $errors);
    assert_true(!str_contains($content, 'CURLOPT_SSL_VERIFYPEER, false'), $label . ' should not set peer verification to false.', $errors);
    assert_true(!str_contains($content, 'CURLOPT_SSL_VERIFYHOST, 0'), $label . ' should not set host verification to 0.', $errors);
}

assert_true(!str_contains($fedapay, 'shouldRetryWithoutSslVerification'), 'FedaPay should not retry without SSL verification.', $errors);
assert_true(!str_contains($fedapay, 'localInsecureSslAllowed'), 'FedaPay should not rely on host-based local SSL heuristics.', $errors);
assert_true(!str_contains($webPush, 'shouldRetryWithoutSslVerification'), 'Web Push should not retry without SSL verification.', $errors);
assert_true(!str_contains($webPush, 'localInsecureSslAllowed'), 'Web Push should not rely on host-based local SSL heuristics.', $errors);

assert_true(str_contains($fedapay, 'CURLOPT_SSL_VERIFYPEER, true'), 'FedaPay should keep peer verification enabled.', $errors);
assert_true(str_contains($fedapay, 'CURLOPT_SSL_VERIFYHOST, 2'), 'FedaPay should keep host verification enabled.', $errors);
assert_true(str_contains($webPush, 'CURLOPT_SSL_VERIFYPEER => true'), 'Web Push should keep peer verification enabled.', $errors);
assert_true(str_contains($webPush, 'CURLOPT_SSL_VERIFYHOST => 2'), 'Web Push should keep host verification enabled.', $errors);
assert_true(str_contains($receiptBuilder, 'CURLOPT_SSL_VERIFYPEER => true'), 'Receipt PDF builder should keep peer verification enabled.', $errors);
assert_true(str_contains($receiptBuilder, 'CURLOPT_SSL_VERIFYHOST => 2'), 'Receipt PDF builder should keep host verification enabled.', $errors);

if ($errors !== []) {
    echo "TLS verification guard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "TLS verification guard OK\n";
exit(0);
