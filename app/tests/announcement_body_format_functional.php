<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

require_once $rootDir . '/app/bootstrap.php';
require_once $rootDir . '/app/View/helpers.php';

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

$body = "- Premier lien https://example.com\" onclick=\"alert(1)\n\n- Deuxieme ligne";
$html = format_announcement_body($body);

assert_true(str_contains($html, '<ul class="list-disc pl-5 space-y-1">'), 'Announcement lists should render with valid HTML quotes.', $errors);
assert_true(str_contains($html, '<div class="h-2"></div>'), 'Blank announcement lines should render with valid spacing markup.', $errors);
assert_true(str_contains($html, '<a class="notif-body-link" href="https://example.com&quot;" target="_blank" rel="noopener noreferrer">'), 'Announcement URLs should stay escaped inside links.', $errors);
assert_true(str_contains($html, '<button type="button" class="notif-copy-link-btn" data-copy-url="https://example.com&quot;">Copier</button>'), 'Copy buttons should receive a sanitized URL value.', $errors);
assert_true(!str_contains($html, 'onclick="alert(1)'), 'Announcement rendering must not allow attribute injection from message text.', $errors);

$queryHtml = format_announcement_body('Lien utile https://example.com/course?id=12&tab=preview');
assert_true(str_contains($queryHtml, 'href="https://example.com/course?id=12&amp;tab=preview"'), 'Query string URLs should remain usable after sanitization.', $errors);
assert_true(str_contains($queryHtml, 'data-copy-url="https://example.com/course?id=12&amp;tab=preview"'), 'Copy URLs should keep query parameters intact.', $errors);

if ($errors !== []) {
    echo "Announcement body formatting FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Announcement body formatting OK\n";
