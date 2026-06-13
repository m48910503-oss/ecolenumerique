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
    url TEXT,
    is_read INTEGER DEFAULT 0,
    created_at TEXT
)');
$pdo->exec('CREATE TABLE companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    admin_user_id INTEGER,
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
$pdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    status TEXT
)');
$pdo->exec('CREATE TABLE company_course_seat_purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    course_id INTEGER,
    order_id INTEGER,
    order_item_id INTEGER,
    purchased_by INTEGER,
    seats_total INTEGER,
    unit_price_xof INTEGER,
    total_xof INTEGER,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
)');
$pdo->exec('CREATE TABLE company_course_seat_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    course_id INTEGER,
    member_user_id INTEGER,
    assigned_by INTEGER,
    status TEXT,
    assigned_at TEXT,
    revoked_at TEXT,
    created_at TEXT,
    updated_at TEXT
)');
$pdo->exec('CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    course_id INTEGER,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
)');

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, roles_json, updated_at) VALUES
    (1, 'Admin', 'Company', 'admin@corp.test', '[\"company_admin\",\"company_member\"]', datetime('now')),
    (2, 'Alice', 'Staff', 'alice@corp.test', '[\"company_member\"]', datetime('now'))");
$pdo->exec("INSERT INTO companies (id, name, admin_user_id, status, created_at, updated_at) VALUES
    (10, 'Acme Corp', 1, 'active', datetime('now', '-30 day'), datetime('now'))");
$pdo->exec("INSERT INTO company_members (id, company_id, user_id, role, status, created_at, updated_at) VALUES
    (100, 10, 1, 'admin', 'active', datetime('now', '-20 day'), datetime('now')),
    (101, 10, 2, 'member', 'active', datetime('now', '-5 day'), datetime('now'))");
$pdo->exec("INSERT INTO courses (id, title, status) VALUES
    (300, 'Leadership', 'approved')");
$pdo->exec("INSERT INTO company_course_seat_purchases (
    id, company_id, course_id, order_id, order_item_id, purchased_by, seats_total, unit_price_xof, total_xof, status, created_at, updated_at
) VALUES
    (500, 10, 300, 700, 701, 1, 2, 20000, 40000, 'active', datetime('now'), datetime('now'))");

$service = new \App\Services\Company\DashboardService(
    new \App\Models\CompanyDashboardModel($pdo),
    new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
);

$state = $service->buildSeatsState(['id' => 1]);
assert_true((int) (($state['seatSummary']['seats_available'] ?? 0)) === 2, 'Two seats should be available initially.', $errors);

$assigned = $service->handlePost($state, ['id' => 1], [
    'action' => 'assign_course_seat',
    'course_id' => '300',
    'member_user_id' => '2',
], [], 'seats');

assert_true((string) ($assigned['successMessage'] ?? '') === 'Place attribuée au collaborateur.', 'Seat assignment should succeed.', $errors);
assert_true((int) $pdo->query("SELECT COUNT(*) FROM company_course_seat_assignments WHERE company_id = 10 AND course_id = 300 AND member_user_id = 2 AND status = 'active'")->fetchColumn() === 1, 'Seat assignment should be active.', $errors);
assert_true((int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id = 2 AND course_id = 300 AND status = 'active'")->fetchColumn() === 1, 'Enrollment should be activated.', $errors);

$duplicate = $service->handlePost($assigned, ['id' => 1], [
    'action' => 'assign_course_seat',
    'course_id' => '300',
    'member_user_id' => '2',
], [], 'seats');
assert_true(!empty($duplicate['errors']), 'Duplicate assignment should fail.', $errors);

$revoked = $service->handlePost($assigned, ['id' => 1], [
    'action' => 'revoke_course_seat',
    'course_id' => '300',
    'member_user_id' => '2',
], [], 'seats');

assert_true((string) ($revoked['successMessage'] ?? '') === 'Place retirée.', 'Seat revocation should succeed.', $errors);
assert_true((int) $pdo->query("SELECT COUNT(*) FROM company_course_seat_assignments WHERE company_id = 10 AND course_id = 300 AND member_user_id = 2 AND status = 'revoked'")->fetchColumn() === 1, 'Seat assignment should be revoked.', $errors);
assert_true((int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE user_id = 2 AND course_id = 300 AND status = 'revoked'")->fetchColumn() === 1, 'Enrollment should be revoked.', $errors);

if ($errors !== []) {
    echo "Company seats functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Company seats functional OK\n";
exit(0);
