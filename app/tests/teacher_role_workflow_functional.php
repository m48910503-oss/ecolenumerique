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

/**
 * @param array<int,string> $columns
 */
function register_sqlite_schema(PDO $pdo, string $table, array $columns): void
{
    $stmtTable = $pdo->prepare('INSERT INTO INFORMATION_SCHEMA.TABLES (TABLE_SCHEMA, TABLE_NAME) VALUES (:schema, :table)');
    $stmtColumn = $pdo->prepare('INSERT INTO INFORMATION_SCHEMA.COLUMNS (TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME) VALUES (:schema, :table, :column)');

    $stmtTable->execute([
        ':schema' => 'main',
        ':table' => $table,
    ]);

    foreach ($columns as $column) {
        $stmtColumn->execute([
            ':schema' => 'main',
            ':table' => $table,
            ':column' => $column,
        ]);
    }
}

function build_sqlite_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("ATTACH DATABASE ':memory:' AS INFORMATION_SCHEMA");
    $pdo->sqliteCreateFunction('DATABASE', static fn(): string => 'main', 0);
    $pdo->sqliteCreateFunction('NOW', static fn(): string => '2026-04-13 10:00:00', 0);
    $pdo->sqliteCreateFunction('FIELD', static function (string $value, string $first, string $second, string $third): int {
        $values = [$first, $second, $third];
        $position = array_search($value, $values, true);
        return $position === false ? count($values) + 1 : (int) $position + 1;
    }, 4);
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.TABLES (TABLE_SCHEMA TEXT, TABLE_NAME TEXT)');
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.COLUMNS (TABLE_SCHEMA TEXT, TABLE_NAME TEXT, COLUMN_NAME TEXT)');

    return $pdo;
}

$errors = [];
$_SESSION = [];
$_GET = [];
$_POST = [];
$_COOKIE = [];
$_SERVER = [];

$pdo = build_sqlite_pdo();
\App\Core\PdoRegistry::set($pdo);

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    password_hash TEXT,
    status TEXT,
    email_verified_at TEXT,
    roles_json TEXT
)');
register_sqlite_schema($pdo, 'users', ['id', 'first_name', 'last_name', 'email', 'password_hash', 'status', 'email_verified_at', 'roles_json']);

$pdo->exec('CREATE TABLE email_verifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    token_hash TEXT,
    expires_at TEXT
)');
register_sqlite_schema($pdo, 'email_verifications', ['id', 'user_id', 'token_hash', 'expires_at']);

$pdo->exec('CREATE TABLE companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    admin_user_id INTEGER,
    status TEXT,
    company_email TEXT,
    company_phone TEXT,
    country TEXT,
    city TEXT,
    address_line TEXT,
    logo_path TEXT,
    created_at TEXT,
    updated_at TEXT
)');
register_sqlite_schema($pdo, 'companies', ['id', 'name', 'admin_user_id', 'status', 'company_email', 'company_phone', 'country', 'city', 'address_line', 'logo_path', 'created_at', 'updated_at']);

$pdo->exec('CREATE TABLE company_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    user_id INTEGER,
    role TEXT,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
)');
register_sqlite_schema($pdo, 'company_members', ['id', 'company_id', 'user_id', 'role', 'status', 'created_at', 'updated_at']);

$pdo->exec('CREATE TABLE teacher_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    status TEXT,
    reason TEXT,
    decided_by INTEGER,
    decided_at TEXT,
    created_at TEXT
)');
register_sqlite_schema($pdo, 'teacher_applications', ['id', 'user_id', 'status', 'reason', 'decided_by', 'decided_at', 'created_at']);

$pdo->exec('CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT,
    body TEXT,
    url TEXT,
    created_at TEXT
)');
register_sqlite_schema($pdo, 'notifications', ['id', 'user_id', 'title', 'body', 'url', 'created_at']);

$registerService = new \App\Services\Auth\RegisterService(
    new \App\Models\AuthRegisterModel($pdo),
    new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo)),
    new \App\Services\Shared\ReferralService(new \App\Models\SharedReferralModel($pdo))
);

$registerResult = $registerService->handle('POST', [
    'first_name' => 'Lina',
    'last_name' => 'Diallo',
    'email' => 'lina@example.test',
    'password' => 'Azerty1!',
    'password_confirm' => 'Azerty1!',
    'role_teacher' => '1',
    'account_type' => 'personal',
    'terms' => '1',
]);

assert_true((string) ($registerResult['redirectTo'] ?? '') === '/ecolenumerique/register', 'Registration should still redirect to register on success.', $errors);

$userStmt = $pdo->query("SELECT id, roles_json FROM users WHERE email = 'lina@example.test' LIMIT 1");
$userRow = $userStmt ? $userStmt->fetch(PDO::FETCH_ASSOC) : false;
assert_true(is_array($userRow), 'Registered user should be persisted.', $errors);

$userId = (int) ($userRow['id'] ?? 0);
$roles = is_array($userRow) ? (json_decode((string) ($userRow['roles_json'] ?? '[]'), true) ?: []) : [];
assert_true(in_array('student', $roles, true), 'Registered teacher candidate should keep student role.', $errors);
assert_true(in_array('teacher_candidate', $roles, true), 'Teacher intent at registration should create teacher_candidate, not direct teacher access.', $errors);
assert_true(!in_array('teacher', $roles, true), 'Registration must not directly grant teacher role.', $errors);

$companyRegisterResult = $registerService->handle('POST', [
    'first_name' => 'Corp',
    'last_name' => 'Admin',
    'email' => 'company-admin@example.test',
    'password' => 'Azerty1!',
    'password_confirm' => 'Azerty1!',
    'role_teacher' => '1',
    'account_type' => 'company',
    'company_name' => 'Corp Academy',
    'terms' => '1',
]);

assert_true((string) ($companyRegisterResult['redirectTo'] ?? '') === '/ecolenumerique/register', 'Company registration should still complete through the email verification step.', $errors);

$companyUserStmt = $pdo->query("SELECT id, roles_json FROM users WHERE email = 'company-admin@example.test' LIMIT 1");
$companyUserRow = $companyUserStmt ? $companyUserStmt->fetch(PDO::FETCH_ASSOC) : false;
assert_true(is_array($companyUserRow), 'Company user should be persisted.', $errors);

$companyRoles = is_array($companyUserRow) ? (json_decode((string) ($companyUserRow['roles_json'] ?? '[]'), true) ?: []) : [];
assert_true(in_array('company_admin', $companyRoles, true), 'Company registration should grant company admin role.', $errors);
assert_true(!in_array('teacher_candidate', $companyRoles, true), 'Company registration must ignore teacher intent.', $errors);
assert_true(!in_array('teacher', $companyRoles, true), 'Company registration must not grant teacher role.', $errors);

$pdo->prepare('INSERT INTO teacher_applications (user_id, status, reason, decided_by, decided_at, created_at) VALUES (:uid, :status, NULL, NULL, NULL, :created_at)')
    ->execute([
        ':uid' => $userId,
        ':status' => 'pending',
        ':created_at' => '2026-04-13 09:00:00',
    ]);

$approvalService = new \App\Services\Admin\TeacherApplicationsService(
    $pdo,
    new \App\Models\AdminTeacherApplicationsModel($pdo)
);
$approvalResult = $approvalService->handle(
    ['id' => 99],
    'POST',
    [
        'app_id' => 1,
        'action' => 'approve',
    ]
);

assert_true((string) ($approvalResult['redirectTo'] ?? '') === '/ecolenumerique/admin/teacher_applications?tab=applications', 'Approval should redirect back to teacher applications.', $errors);

$approvedRolesRow = $pdo->query("SELECT roles_json FROM users WHERE id = {$userId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$approvedRoles = json_decode((string) ($approvedRolesRow['roles_json'] ?? '[]'), true) ?: [];
assert_true(in_array('student', $approvedRoles, true), 'Approval should preserve student role.', $errors);
assert_true(in_array('teacher', $approvedRoles, true), 'Approval should grant teacher role.', $errors);
assert_true(!in_array('teacher_candidate', $approvedRoles, true), 'Approval should remove transitional teacher_candidate role.', $errors);

$statusRow = $pdo->query('SELECT status FROM teacher_applications WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
assert_true((string) ($statusRow['status'] ?? '') === 'approved', 'Application status should switch to approved.', $errors);

$notificationRow = $pdo->query("SELECT url FROM notifications WHERE user_id = {$userId} ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assert_true((string) ($notificationRow['url'] ?? '') === '/ecolenumerique/teacher/dashboard', 'Approval notification should point to teacher dashboard.', $errors);

$pdo->exec("INSERT INTO users (first_name, last_name, email, password_hash, status, email_verified_at, roles_json) VALUES
    ('Corp', 'Member', 'corp@example.test', '', 'active', '2026-04-13 08:00:00', '[\"student\",\"company_member\"]')
");
$companyUserId = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO teacher_applications (user_id, status, reason, decided_by, decided_at, created_at) VALUES (:uid, :status, NULL, NULL, NULL, :created_at)')
    ->execute([
        ':uid' => $companyUserId,
        ':status' => 'pending',
        ':created_at' => '2026-04-13 09:30:00',
    ]);
$companyApplicationId = (int) $pdo->lastInsertId();

$companyApprovalResult = $approvalService->handle(
    ['id' => 99],
    'POST',
    [
        'app_id' => $companyApplicationId,
        'action' => 'approve',
    ]
);

assert_true($companyApprovalResult['redirectTo'] === null, 'Company account approval should stay on the applications page with an error.', $errors);
$companyApprovalErrors = is_array($companyApprovalResult['view']['errors'] ?? null) ? $companyApprovalResult['view']['errors'] : [];
assert_true(
    in_array('Impossible d’approuver cette demande : les comptes entreprise ne peuvent pas devenir enseignants.', $companyApprovalErrors, true),
    'Company account approval should explain why it is rejected.',
    $errors
);

$companyRolesRow = $pdo->query("SELECT roles_json FROM users WHERE id = {$companyUserId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$companyRoles = json_decode((string) ($companyRolesRow['roles_json'] ?? '[]'), true) ?: [];
assert_true(!in_array('teacher', $companyRoles, true), 'Company account approval must not grant teacher role.', $errors);

$companyStatusRow = $pdo->query("SELECT status FROM teacher_applications WHERE id = {$companyApplicationId} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
assert_true((string) ($companyStatusRow['status'] ?? '') === 'pending', 'Company account application should remain pending after blocked approval.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "teacher_role_workflow_functional: OK\n");
