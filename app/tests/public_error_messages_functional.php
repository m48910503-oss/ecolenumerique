<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/ecolenumerique');
}
if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', 'http://localhost/ecolenumerique');
}

require_once $rootDir . '/app/bootstrap.php';
require_once $rootDir . '/includes/helpers.php';

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

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
\App\Core\PdoRegistry::set($pdo);

$shareService = new \App\Services\Web\ShareService(
    new \App\Models\WebShareModel($pdo),
    new \App\Services\Shared\PromotionsService(
        new \App\Models\SharedPromotionModel($pdo),
        new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
    )
);
$shareResult = $shareService->handle('', '', '');
assert_true((int) ($shareResult['httpStatus'] ?? 0) === 404, 'Share: invalid token should return 404.', $errors);
assert_true((string) ($shareResult['abortTitle'] ?? '') === 'Lien de partage invalide', 'Share: invalid token should expose a dedicated title.', $errors);
assert_true(str_contains((string) ($shareResult['abortMessage'] ?? ''), 'incomplet'), 'Share: invalid token should expose a clearer message.', $errors);

$certificateService = new \App\Services\Web\CertificateService(
    new \App\Models\WebCertificateModel($pdo),
    new \App\Services\Shared\PlatformSettingsService(new \App\Models\SharedPlatformSettingsModel($pdo)),
    new \App\Services\Shared\CertificateOpsService(new \App\Models\SharedCertificateModel($pdo))
);
$certificateResult = $certificateService->handle('', 'html');
assert_true((int) ($certificateResult['httpStatus'] ?? 0) === 404, 'Certificate: empty code should return 404.', $errors);
assert_true((string) ($certificateResult['abortTitle'] ?? '') === 'Certificat introuvable', 'Certificate: empty code should expose a dedicated title.', $errors);
assert_true(str_contains((string) ($certificateResult['abortMessage'] ?? ''), 'code demande'), 'Certificate: empty code should expose a clearer message.', $errors);

$previewService = new \App\Services\Web\CoursePreviewOpenService(
    new \App\Models\WebCourseModel($pdo),
    new \App\Services\Web\PreviewTokenService()
);
$previewResult = $previewService->handle(null, 0, '');
assert_true((int) ($previewResult['httpStatus'] ?? 0) === 400, 'Preview: invalid params should return 400.', $errors);
assert_true((string) ($previewResult['errorTitle'] ?? '') === "Lien d'apercu invalide", 'Preview: invalid params should expose a dedicated title.', $errors);
assert_true(str_contains((string) ($previewResult['error'] ?? ''), 'incompletes'), 'Preview: invalid params should expose a clearer message.', $errors);

$publicErrorViewPath = $rootDir . '/app/Views/web/public_error.php';
assert_true(is_file($publicErrorViewPath), 'Public error view must exist.', $errors);
$publicErrorView = (string) file_get_contents($publicErrorViewPath);
assert_true(str_contains($publicErrorView, 'errorTitle'), 'Public error view must render the title.', $errors);
assert_true(str_contains($publicErrorView, 'errorActions'), 'Public error view must render recovery actions.', $errors);

if ($errors !== []) {
    echo "Public error messages functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Public error messages functional OK\n";
exit(0);
