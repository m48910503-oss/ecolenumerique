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

$service = new \App\Services\Web\PreviewTokenService();
$viewerA = 'g:preview-viewer-a';
$viewerB = 'g:preview-viewer-b';

$token = $service->issueCoursePreviewToken(11, 27, $viewerA, 120);
assert_true($token !== '', 'Preview token should be generated.', $errors);

$validPayload = $service->verifyCoursePreviewToken(11, $viewerA, $token);
assert_true(is_array($validPayload), 'Preview token should be valid for matching viewer/course.', $errors);
assert_true((int) ($validPayload['lesson_id'] ?? 0) === 27, 'Verified preview token should keep lesson id.', $errors);

$invalidViewer = $service->verifyCoursePreviewToken(11, $viewerB, $token);
assert_true($invalidViewer === null, 'Preview token should fail with another viewer key.', $errors);

$invalidCourse = $service->verifyCoursePreviewToken(99, $viewerA, $token);
assert_true($invalidCourse === null, 'Preview token should fail for another course.', $errors);

$tampered = $token !== '' ? substr($token, 0, -1) . 'x' : 'x';
$invalidTampered = $service->verifyCoursePreviewToken(11, $viewerA, $tampered);
assert_true($invalidTampered === null, 'Preview token should fail when tampered.', $errors);

$expiredToken = $service->issueCoursePreviewToken(11, 27, $viewerA, -20);
$expiredPayload = $service->verifyCoursePreviewToken(11, $viewerA, $expiredToken);
assert_true($expiredPayload === null, 'Preview token should expire.', $errors);

$embed = $service->buildYoutubePreviewEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
assert_true(is_string($embed) && str_contains((string) $embed, 'youtube-nocookie.com/embed/dQw4w9WgXcQ'), 'Embed URL should use youtube-nocookie.', $errors);
assert_true(is_string($embed) && str_contains((string) $embed, 'controls=0'), 'Embed URL should hide controls.', $errors);
assert_true(is_string($embed) && str_contains((string) $embed, 'autoplay=1'), 'Embed URL should autoplay from secure opener.', $errors);
assert_true(is_string($embed) && str_contains((string) $embed, 'enablejsapi=1'), 'Embed URL should enable the JS API for player controls.', $errors);

$embedWithOrigin = $service->buildYoutubePreviewEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'http://localhost/ecolenumerique');
assert_true(is_string($embedWithOrigin) && str_contains((string) $embedWithOrigin, 'origin=http%3A%2F%2Flocalhost%2Fecolenumerique'), 'Embed URL should carry the application origin.', $errors);

$routes = require $rootDir . '/app/routes.php';
assert_true(isset($routes['course_preview_open']), 'Route course_preview_open must exist.', $errors);

$courseViewPath = $rootDir . '/app/Views/web/course.php';
$courseView = (string) file_get_contents($courseViewPath);
assert_true(str_contains($courseView, 'data-preview-guard'), 'Course view must expose non-enrolled preview guard flag.', $errors);
assert_true(str_contains($courseView, 'data-preview-open-url'), 'Course view must use signed preview open URL.', $errors);

if ($errors !== []) {
    echo "Course preview security functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Course preview security functional OK\n";
exit(0);
