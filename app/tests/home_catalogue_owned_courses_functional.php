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

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT
)');
$pdo->exec('CREATE TABLE categories (
    id INTEGER PRIMARY KEY,
    name TEXT
)');
$pdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY,
    teacher_id INTEGER,
    category_id INTEGER,
    version_of INTEGER,
    title TEXT,
    slug TEXT,
    description TEXT,
    price_xof INTEGER,
    level TEXT,
    promo_type TEXT,
    promo_value REAL,
    promo_start_at TEXT,
    promo_end_at TEXT,
    duration_hours REAL,
    cover_path TEXT,
    cover_alt TEXT,
    status TEXT,
    created_at TEXT,
    updated_at TEXT,
    deleted_at TEXT
)');
$pdo->exec('CREATE TABLE enrollments (
    user_id INTEGER,
    course_id INTEGER,
    status TEXT
)');
$pdo->exec('CREATE TABLE course_ratings (
    course_id INTEGER,
    rating REAL,
    is_hidden_by_teacher INTEGER
)');
$pdo->exec('CREATE TABLE course_chapters (
    id INTEGER PRIMARY KEY,
    course_id INTEGER,
    position INTEGER
)');
$pdo->exec('CREATE TABLE course_lessons (
    id INTEGER PRIMARY KEY,
    chapter_id INTEGER,
    duration_estimate_min INTEGER
)');

$pdo->exec("INSERT INTO users (id, first_name, last_name) VALUES
    (1, 'Amina', 'Teacher'),
    (20, 'Karim', 'Student')
");
$pdo->exec("INSERT INTO categories (id, name) VALUES
    (1, 'Developpement')
");
$pdo->exec("INSERT INTO courses (
    id, teacher_id, category_id, version_of, title, slug, description, price_xof, level,
    promo_type, promo_value, promo_start_at, promo_end_at, duration_hours, cover_path,
    cover_alt, status, created_at, updated_at, deleted_at
) VALUES
    (101, 1, 1, NULL, 'Cours achete', 'cours-achete', 'Cours deja achete par l''utilisateur', 50000, 'beginner', NULL, NULL, NULL, NULL, 4.5, NULL, NULL, 'approved', '2026-05-03 10:00:00', '2026-05-03 10:00:00', NULL),
    (102, 1, 1, NULL, 'Cours visible', 'cours-visible', 'Cours encore disponible', 60000, 'intermediate', NULL, NULL, NULL, NULL, 6.0, NULL, NULL, 'approved', '2026-04-01 10:00:00', '2026-04-01 10:00:00', NULL)
");
$pdo->exec("INSERT INTO enrollments (user_id, course_id, status) VALUES
    (20, 101, 'active')
");

$model = new \App\Models\WebHomeModel($pdo);

$guestCourses = $model->fetchPublicCoursesForHome(10, null);
assert_true(count($guestCourses) === 2, 'Guest home list should include both public courses.', $errors);
assert_true((int) ($guestCourses[0]['id'] ?? 0) === 101, 'Newest course should be first for guests.', $errors);
assert_true((int) ($guestCourses[1]['id'] ?? 0) === 102, 'Second guest course should remain visible.', $errors);
assert_true((int) ($guestCourses[0]['enrolled_count'] ?? -1) === 1, 'Guest home list should expose the active enrollment count.', $errors);

$studentCourses = $model->fetchPublicCoursesForHome(10, 20);
assert_true(count($studentCourses) === 1, 'Logged-in student should not see owned courses.', $errors);
assert_true((int) ($studentCourses[0]['id'] ?? 0) === 102, 'Only the unowned course should remain in the list.', $errors);
assert_true((int) ($studentCourses[0]['enrolled_count'] ?? -1) === 0, 'Remaining course should preserve its enrollment count.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "home_catalogue_owned_courses_functional: OK\n");
