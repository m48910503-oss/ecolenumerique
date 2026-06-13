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
    company_email TEXT,
    company_phone TEXT,
    country TEXT,
    city TEXT,
    address_line TEXT,
    logo_path TEXT,
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

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, roles_json, updated_at) VALUES
    (1, 'Admin', 'Company', 'admin@corp.test', '[\"company_admin\"]', datetime('now'))");
$pdo->exec("INSERT INTO notifications (user_id, is_read) VALUES (1,0)");
$pdo->exec("INSERT INTO companies (id, name, admin_user_id, status, company_email, company_phone, country, city, address_line, created_at, updated_at) VALUES
    (10, 'Acme Corp', 1, 'active', 'old@corp.test', '000000', 'FR', 'Paris', 'Old street', datetime('now', '-30 day'), datetime('now'))");
$pdo->exec("INSERT INTO company_members (id, company_id, user_id, role, status, created_at, updated_at) VALUES
    (100, 10, 1, 'admin', 'active', datetime('now', '-20 day'), datetime('now'))");

$service = new \App\Services\Company\DashboardService(
    new \App\Models\CompanyDashboardModel($pdo),
    new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
);

$state = $service->buildInitialState(['id' => 1]);

$post = $service->handlePost($state, ['id' => 1], [
    'action' => 'update_company_profile',
    'company_email' => 'contact@acme.test',
    'company_phone' => '0123456789',
    'country' => 'FR',
    'city' => 'Lyon',
    'address_line' => '1 Rue des Tests',
]);

assert_true(!empty($post['errors']), 'Company profile update should be unavailable in the simple company version.', $errors);

$row = $pdo->query("SELECT company_email, city, address_line FROM companies WHERE id = 10")->fetch(PDO::FETCH_ASSOC) ?: [];
assert_true(($row['company_email'] ?? '') === 'old@corp.test', 'Company email should stay unchanged.', $errors);
assert_true(($row['city'] ?? '') === 'Paris', 'Company city should stay unchanged.', $errors);
assert_true(($row['address_line'] ?? '') === 'Old street', 'Company address should stay unchanged.', $errors);

if ($errors !== []) {
    echo "Company settings functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Company settings functional OK\n";
exit(0);
