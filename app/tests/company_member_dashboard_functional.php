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
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    roles_json TEXT,
    updated_at TEXT
)');
$pdo->exec('CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT,
    body TEXT,
    is_read INTEGER DEFAULT 0,
    created_at TEXT,
    url TEXT
)');
$pdo->exec('CREATE TABLE companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
)');
$pdo->exec('CREATE TABLE company_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    user_id INTEGER,
    role TEXT,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
)');
$pdo->exec('CREATE TABLE company_teams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    name TEXT,
    status TEXT
)');
$pdo->exec('CREATE TABLE company_team_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    team_id INTEGER,
    member_id INTEGER,
    assigned_by INTEGER,
    created_at TEXT,
    updated_at TEXT
)');
$pdo->exec('CREATE TABLE company_course_seat_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    course_id INTEGER,
    member_user_id INTEGER,
    status TEXT,
    assigned_at TEXT
)');
$pdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    slug TEXT,
    cover_path TEXT,
    cover_alt TEXT,
    level TEXT,
    status TEXT
)');
$pdo->exec('CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    course_id INTEGER,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
)');
$pdo->exec('CREATE TABLE course_chapters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER
)');
$pdo->exec('CREATE TABLE course_lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chapter_id INTEGER
)');
$pdo->exec('CREATE TABLE lesson_completions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    course_id INTEGER,
    lesson_id INTEGER
)');

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, roles_json, updated_at) VALUES
    (1, 'Admin', 'Corp', 'admin@corp.test', '[\"company_admin\"]', datetime('now')),
    (2, 'Membre', 'Equipe', 'member@corp.test', '[\"student\",\"company_member\"]', datetime('now'))");

$pdo->exec("INSERT INTO notifications (user_id, title, body, is_read, created_at, url) VALUES
    (2, 'Cours attribue par votre entreprise', 'Votre entreprise vous a attribue le cours Produit.', 0, datetime('now', '-1 hour'), 'company/dashboard#assigned-courses'),
    (2, 'Information generale', 'Mise a jour de votre espace.', 1, datetime('now', '-2 hour'), 'notifications')");

$pdo->exec("INSERT INTO companies (id, name, status, created_at, updated_at) VALUES
    (10, 'Acme Corp', 'active', datetime('now', '-30 day'), datetime('now'))");

$pdo->exec("INSERT INTO company_members (id, company_id, user_id, role, status, created_at, updated_at) VALUES
    (100, 10, 1, 'admin', 'active', datetime('now', '-20 day'), datetime('now')),
    (101, 10, 2, 'member', 'active', datetime('now', '-10 day'), datetime('now'))");

$pdo->exec("INSERT INTO company_teams (id, company_id, name, status) VALUES
    (500, 10, 'Produit', 'active')");
$pdo->exec("INSERT INTO company_team_members (company_id, team_id, member_id, assigned_by, created_at, updated_at) VALUES
    (10, 500, 101, 1, datetime('now', '-8 day'), datetime('now'))");

$pdo->exec("INSERT INTO courses (id, title, slug, cover_path, cover_alt, level, status) VALUES
    (200, 'Produit', 'produit', '', '', 'intermediate', 'approved'),
    (201, 'Culture digitale', 'culture-digitale', '', '', 'beginner', 'approved')");

$pdo->exec("INSERT INTO company_course_seat_assignments (company_id, course_id, member_user_id, status, assigned_at) VALUES
    (10, 200, 2, 'active', datetime('now', '-7 day'))");

$pdo->exec("INSERT INTO enrollments (user_id, course_id, status, created_at, updated_at) VALUES
    (2, 200, 'active', datetime('now', '-7 day'), datetime('now')),
    (2, 201, 'active', datetime('now', '-3 day'), datetime('now'))");

$pdo->exec("INSERT INTO course_chapters (id, course_id) VALUES
    (300, 200),
    (301, 201)");
$pdo->exec("INSERT INTO course_lessons (id, chapter_id) VALUES
    (400, 300),
    (401, 300),
    (402, 301)");
$pdo->exec("INSERT INTO lesson_completions (user_id, course_id, lesson_id) VALUES
    (2, 200, 400)");

$service = new \App\Services\Company\MemberDashboardService(
    new \App\Models\CompanyDashboardModel($pdo),
    new \App\Models\StudentDashboardModel($pdo),
    new \App\Models\EnrollmentModel($pdo)
);

$state = $service->buildViewData([
    'id' => 2,
    'first_name' => 'Membre',
    'last_name' => 'Equipe',
    'email' => 'member@corp.test',
    'roles_json' => '["student","company_member"]',
]);

assert_true($state['errors'] === [], 'Member dashboard should load without errors.', $errors);
assert_true((int) (($state['company']['id'] ?? 0)) === 10, 'Company should be resolved for company_member.', $errors);
assert_true((string) (($state['member']['role_label'] ?? '')) === 'Collaborateur', 'Member role label should be mapped.', $errors);
assert_true((string) (($state['team']['name'] ?? '')) === 'Produit', 'Team should be resolved for member.', $errors);
assert_true((int) ($state['unreadCount'] ?? 0) === 1, 'Unread notifications should be counted.', $errors);
assert_true(count((array) ($state['companyAdmins'] ?? [])) === 1, 'Admin contacts should be exposed.', $errors);
assert_true(count((array) ($state['assignedCourses'] ?? [])) === 1, 'Assigned company courses should be exposed.', $errors);
assert_true((int) (($state['assignedCourses'][0]['progress_pct'] ?? 0)) === 50, 'Assigned course progress should be merged from enrollments.', $errors);
assert_true(count((array) ($state['otherCourses'] ?? [])) === 1, 'Non-company active courses should remain visible separately.', $errors);
assert_true(count((array) ($state['kpiCards'] ?? [])) >= 5, 'Member dashboard KPI cards should be present.', $errors);

if ($errors !== []) {
    echo "Company member dashboard functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Company member dashboard functional OK\n";
exit(0);
