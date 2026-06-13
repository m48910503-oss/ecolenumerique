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
$_SESSION = [];
$_GET = [];
$_SERVER = [];

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    roles_json TEXT,
    status TEXT,
    email_verified_at TEXT
)');
$pdo->exec('CREATE TABLE categories (
    id INTEGER PRIMARY KEY,
    name TEXT
)');
$pdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY,
    teacher_id INTEGER,
    category_id INTEGER,
    title TEXT,
    slug TEXT,
    status TEXT,
    deleted_at TEXT
)');

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, roles_json, status, email_verified_at) VALUES
    (10, 'Alice', 'Teacher', 'alice@example.test', '[\"teacher\"]', 'active', '2026-01-01 00:00:00'),
    (20, 'Bob', 'Student', 'bob@example.test', '[\"student\"]', 'active', '2026-01-01 00:00:00'),
    (30, 'Cara', 'Admin', 'cara@example.test', '[\"admin\"]', 'active', '2026-01-01 00:00:00')
");
$pdo->exec("INSERT INTO categories (id, name) VALUES (1, 'Test')");
$pdo->exec("INSERT INTO courses (id, teacher_id, category_id, title, slug, status, deleted_at) VALUES
    (101, 10, 1, 'Cours archive', 'cours-archive', 'approved', '2026-03-01 12:00:00'),
    (102, 10, 1, 'Cours public', 'cours-public', 'approved', NULL)
");

\App\Core\PdoRegistry::set($pdo);

$buildService = static function () use ($pdo): \App\Services\Web\CourseService {
    return new \App\Services\Web\CourseService(
        new \App\Models\WebCourseModel($pdo),
        new \App\Services\Shared\ReferralService(new \App\Models\SharedReferralModel($pdo)),
        new \App\Services\Shared\PromotionsService(
            new \App\Models\SharedPromotionModel($pdo),
            new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
        )
    );
};

$_SESSION['user_id'] = 10;
$teacherResult = $buildService()->handle('cours-archive', 0, 'GET', [], []);
assert_true(
    (string) ($teacherResult['redirectTo'] ?? '') === '/ecolenumerique/teacher/course_preview?id=101',
    'Archived owner course should redirect to teacher preview.',
    $errors
);
assert_true(
    (int) ($teacherResult['redirectStatus'] ?? 0) === 302,
    'Archived owner course redirect should use HTTP 302.',
    $errors
);

$_SESSION['user_id'] = 30;
$adminResult = $buildService()->handle('cours-archive', 0, 'GET', [], []);
assert_true(
    (string) ($adminResult['redirectTo'] ?? '') === '/ecolenumerique/admin/course_preview?id=101',
    'Archived course should redirect admins to admin preview.',
    $errors
);

$_SESSION['user_id'] = 20;
$studentResult = $buildService()->handle('cours-archive', 0, 'GET', [], []);
assert_true(
    (int) ($studentResult['httpStatus'] ?? 0) === 404,
    'Archived course should stay unavailable for unauthorized users.',
    $errors
);
assert_true(
    (string) ($studentResult['abortTitle'] ?? '') === 'Cours indisponible',
    'Archived course should expose a clear unavailable title.',
    $errors
);
assert_true(
    str_contains((string) ($studentResult['abortMessage'] ?? ''), "n'est plus accessible publiquement"),
    'Archived course should expose a clearer unavailable message.',
    $errors
);

unset($_SESSION['user_id']);
$missingLinkResult = $buildService()->handle(null, 0, 'GET', [], []);
assert_true(
    (int) ($missingLinkResult['httpStatus'] ?? 0) === 404,
    'Missing course link should return 404.',
    $errors
);
assert_true(
    (string) ($missingLinkResult['abortTitle'] ?? '') === 'Lien de cours invalide',
    'Missing course link should expose a dedicated title.',
    $errors
);
assert_true(
    str_contains((string) ($missingLinkResult['abortMessage'] ?? ''), 'incomplet ou incorrect'),
    'Missing course link should expose a dedicated message.',
    $errors
);

if ($errors !== []) {
    echo "Course private access functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Course private access functional OK\n";
exit(0);
