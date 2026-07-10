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

if (!function_exists('format_duration_hours_hm')) {
    function format_duration_hours_hm(float $hours, bool $alwaysShowHours = false): string
    {
        $hours = max(0.0, $hours);
        $minutes = (int) round($hours * 60);
        $wholeHours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($wholeHours <= 0 && !$alwaysShowHours) {
            return $remainingMinutes . ' min';
        }

        if ($remainingMinutes === 0) {
            return $wholeHours . ' h';
        }

        return $wholeHours . ' h ' . str_pad((string) $remainingMinutes, 2, '0', STR_PAD_LEFT) . ' min';
    }
}

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
$pdo->sqliteCreateFunction('NOW', static fn (): string => '2026-07-04 15:20:00', 0);

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    referral_code TEXT
)');
$pdo->exec('CREATE TABLE notifications (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    title TEXT,
    body TEXT,
    url TEXT,
    is_read INTEGER,
    created_at TEXT,
    archived_at TEXT
)');
$pdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY,
    teacher_id INTEGER,
    title TEXT,
    slug TEXT,
    level TEXT,
    cover_path TEXT,
    cover_alt TEXT,
    status TEXT,
    created_at TEXT,
    deleted_at TEXT
)');
$pdo->exec('CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    course_id INTEGER,
    status TEXT,
    created_at TEXT
)');
$pdo->exec('CREATE TABLE course_chapters (
    id INTEGER PRIMARY KEY,
    course_id INTEGER,
    title TEXT
)');
$pdo->exec('CREATE TABLE course_lessons (
    id INTEGER PRIMARY KEY,
    chapter_id INTEGER,
    title TEXT
)');
$pdo->exec('CREATE TABLE lesson_completions (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    course_id INTEGER,
    lesson_id INTEGER,
    created_at TEXT
)');
$pdo->exec('CREATE TABLE course_ratings (
    id INTEGER PRIMARY KEY,
    course_id INTEGER,
    user_id INTEGER,
    rating INTEGER,
    comment TEXT
)');
$pdo->exec('CREATE TABLE course_meetings (
    id INTEGER PRIMARY KEY,
    student_id INTEGER,
    status TEXT,
    start_at TEXT
)');

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, referral_code) VALUES
    (1, 'Michael', 'Teacher', 'teacher@example.com', NULL),
    (20, 'Michael', 'Ouwade', 'ouwade@gmail.com', 'REFTEST123')
");
$pdo->exec("INSERT INTO courses (id, teacher_id, title, slug, level, cover_path, cover_alt, status, created_at, deleted_at) VALUES
    (101, 1, 'word', 'word', 'beginner', NULL, NULL, 'approved', '2026-07-01 09:00:00', NULL)
");
$pdo->exec("INSERT INTO enrollments (id, user_id, course_id, status, created_at) VALUES
    (1, 20, 101, 'active', '2026-07-04 08:00:00')
");
$pdo->exec("INSERT INTO course_chapters (id, course_id, title) VALUES
    (1, 101, 'Introduction')
");
$pdo->exec("INSERT INTO course_lessons (id, chapter_id, title) VALUES
    (1, 1, 'Lesson 1')
");
$pdo->exec("INSERT INTO lesson_completions (id, user_id, course_id, lesson_id, created_at) VALUES
    (1, 20, 101, 1, '2026-07-04 09:00:00')
");
$pdo->exec("INSERT INTO course_ratings (id, course_id, user_id, rating, comment) VALUES
    (1, 101, 20, 5, 'Great course')
");

$service = new \App\Services\Student\DashboardService(
    new \App\Models\StudentDashboardModel($pdo),
    new \App\Models\EnrollmentModel($pdo),
    new \App\Services\Shared\ReferralService(new \App\Models\SharedReferralModel($pdo)),
    new \App\Services\Shared\CertificateOpsService(new \App\Models\SharedCertificateModel($pdo))
);

$view = $service->buildViewData([
    'id' => 20,
    'first_name' => 'Michael',
    'last_name' => 'Ouwade',
    'email' => 'ouwade@gmail.com',
    'roles_json' => '["student"]',
]);

$continueItems = is_array($view['continueItems'] ?? null) ? $view['continueItems'] : [];
assert_true($continueItems !== [], 'Dashboard should expose a continue item.', $errors);

$primaryItem = is_array($continueItems[0] ?? null) ? $continueItems[0] : [];
assert_true((string) ($primaryItem['title'] ?? '') === 'word', 'Primary item should keep the enrolled course title.', $errors);
assert_true((int) ($primaryItem['progress_pct'] ?? 0) === 100, 'Primary item should be marked completed in the fixture.', $errors);
assert_true(
    (string) ($primaryItem['url'] ?? '') === '/ecolenumerique/course/watch?slug=word',
    'Primary item URL should not duplicate the base path.',
    $errors
);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "student_dashboard_course_url_functional: OK\n");
