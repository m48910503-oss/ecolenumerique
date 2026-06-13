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
    (1, 'Admin', 'Company', 'admin@corp.test', '[\"company_admin\",\"company_member\"]', datetime('now')),
    (2, 'Alice', 'Staff', 'alice@corp.test', '[\"company_member\"]', datetime('now')),
    (3, 'Bob', 'Blocked', 'bob@corp.test', '[\"company_member\"]', datetime('now'))");

$pdo->exec("INSERT INTO notifications (user_id, is_read) VALUES (1,0), (1,0), (1,1)");
$pdo->exec("INSERT INTO companies (id, name, admin_user_id, status, created_at, updated_at) VALUES
    (10, 'Acme Corp', 1, 'active', datetime('now', '-30 day'), datetime('now'))");
$pdo->exec("INSERT INTO company_members (id, company_id, user_id, role, status, created_at, updated_at) VALUES
    (100, 10, 1, 'admin', 'active', datetime('now', '-20 day'), datetime('now')),
    (101, 10, 2, 'member', 'active', datetime('now', '-10 day'), datetime('now')),
    (102, 10, 3, 'member', 'blocked', datetime('now', '-5 day'), datetime('now'))");

$hashPending = hash('sha256', 'pending-token');
$hashExpired = hash('sha256', 'expired-token');
$hashAccepted = hash('sha256', 'accepted-token');
$pdo->exec("INSERT INTO company_invites (id, company_id, email, token_hash, status, expires_at, invited_by, created_at, updated_at) VALUES
    (200, 10, 'new@corp.test', '{$hashPending}', 'pending', datetime('now', '+2 day'), 1, datetime('now', '-1 day'), datetime('now')),
    (201, 10, 'late@corp.test', '{$hashExpired}', 'pending', datetime('now', '-1 day'), 1, datetime('now', '-3 day'), datetime('now')),
    (202, 10, 'ok@corp.test', '{$hashAccepted}', 'accepted', datetime('now', '+4 day'), 1, datetime('now', '-2 day'), datetime('now'))");

$service = new \App\Services\Company\DashboardService(
    new \App\Models\CompanyDashboardModel($pdo),
    new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
);

$state = $service->buildInitialState(['id' => 1]);

assert_true((int) ($state['unreadCount'] ?? 0) === 2, 'Unread notifications should be counted.', $errors);
assert_true((int) (($state['memberSummary']['total'] ?? 0)) === 3, 'Member total should be 3.', $errors);
assert_true((int) (($state['memberSummary']['active'] ?? 0)) === 2, 'Active members should be 2.', $errors);
assert_true((int) (($state['memberSummary']['blocked'] ?? 0)) === 1, 'Blocked members should be 1.', $errors);
assert_true((int) (($state['memberSummary']['admins'] ?? 0)) === 1, 'Admins should be 1.', $errors);
assert_true((int) (($state['inviteSummary']['pending'] ?? 0)) === 1, 'Pending invites should be 1 (effective).', $errors);
assert_true((int) (($state['inviteSummary']['expired'] ?? 0)) === 1, 'Expired invites should be 1 (effective).', $errors);
assert_true((int) (($state['inviteSummary']['accepted'] ?? 0)) === 1, 'Accepted invites should be 1.', $errors);
$expiredRowFound = false;
foreach ((array) ($state['inviteRows'] ?? []) as $row) {
    if ((int) ($row['id'] ?? 0) === 201 && (string) ($row['status'] ?? '') === 'expired') {
        $expiredRowFound = true;
    }
}
assert_true($expiredRowFound, 'Expired pending invite should be exposed as effective expired.', $errors);

$postInvalidInvite = $service->handlePost($state, ['id' => 1], [
    'action' => 'invite_member',
    'email' => 'bad-email',
]);
assert_true(!empty($postInvalidInvite['errors']), 'Invalid email should fail.', $errors);

$postResend = $service->handlePost($state, ['id' => 1], [
    'action' => 'resend_invite',
    'invite_id' => '200',
]);
assert_true((string) ($postResend['successMessage'] ?? '') !== '', 'Resend invite should succeed.', $errors);
assert_true(str_contains((string) ($postResend['inviteLink'] ?? ''), 'company/invite_accept?token='), 'Resend should return a shareable invite link.', $errors);

$postRevoke = $service->handlePost($state, ['id' => 1], [
    'action' => 'revoke_invite',
    'invite_id' => '200',
]);
assert_true((string) ($postRevoke['successMessage'] ?? '') === 'Invitation annulée.', 'Revoke invite should succeed.', $errors);

if ($errors !== []) {
    echo "Company dashboard functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Company dashboard functional OK\n";
exit(0);
