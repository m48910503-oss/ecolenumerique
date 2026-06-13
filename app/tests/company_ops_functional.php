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
    is_read INTEGER DEFAULT 0
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
$pdo->exec('CREATE TABLE company_invites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    email TEXT,
    token_hash TEXT,
    status TEXT,
    expires_at TEXT,
    invited_by INTEGER,
    created_at TEXT,
    updated_at TEXT
)');

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, roles_json, updated_at) VALUES
    (1, 'Admin', 'Company', 'admin@corp.test', '[\"company_admin\"]', datetime('now')),
    (2, 'Alice', 'Staff', 'alice@corp.test', '[\"company_member\"]', datetime('now'))");
$pdo->exec("INSERT INTO notifications (user_id, is_read) VALUES (1,0), (1,1)");
$pdo->exec("INSERT INTO companies (id, name, admin_user_id, status, created_at, updated_at) VALUES
    (10, 'Acme Corp', 1, 'active', datetime('now', '-30 day'), datetime('now'))");
$pdo->exec("INSERT INTO company_members (id, company_id, user_id, role, status, created_at, updated_at) VALUES
    (100, 10, 1, 'admin', 'active', datetime('now', '-20 day'), datetime('now')),
    (101, 10, 2, 'member', 'active', datetime('now', '-5 day'), datetime('now'))");
$hashPending = hash('sha256', 'pending-token');
$pdo->exec("INSERT INTO company_invites (id, company_id, email, token_hash, status, expires_at, invited_by, created_at, updated_at) VALUES
    (200, 10, 'new@corp.test', '{$hashPending}', 'pending', datetime('now', '+2 day'), 1, datetime('now', '-1 day'), datetime('now'))");

$service = new \App\Services\Company\DashboardService(
    new \App\Models\CompanyDashboardModel($pdo),
    new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
);

$state = $service->buildOpsState(['id' => 1]);

assert_true((int) (($state['memberSummary']['active'] ?? 0)) === 2, 'Ops route should reuse the simple company state.', $errors);
assert_true((int) (($state['inviteSummary']['pending'] ?? 0)) === 1, 'Pending invitations should remain visible in simple ops fallback.', $errors);

if ($errors !== []) {
    echo "Company ops functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Company ops functional OK\n";
exit(0);
