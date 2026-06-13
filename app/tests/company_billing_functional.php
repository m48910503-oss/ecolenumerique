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
$pdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    status TEXT
)');
$pdo->exec('CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    status TEXT,
    total_xof INTEGER,
    provider TEXT,
    reference TEXT,
    created_at TEXT
)');
$pdo->exec('CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    course_id INTEGER,
    company_id INTEGER,
    quantity INTEGER,
    unit_price_xof INTEGER
)');
$pdo->exec('CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    provider TEXT,
    reference TEXT,
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
    created_at TEXT
)');

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, roles_json, updated_at) VALUES
    (1, 'Admin', 'Company', 'admin@corp.test', '[\"company_admin\",\"company_member\"]', datetime('now')),
    (2, 'Buyer', 'Ops', 'buyer@corp.test', '[\"company_member\"]', datetime('now'))");

$pdo->exec("INSERT INTO notifications (user_id, is_read) VALUES (1,0), (1,1)");
$pdo->exec("INSERT INTO companies (id, name, admin_user_id, status, created_at, updated_at) VALUES
    (10, 'Acme Corp', 1, 'active', datetime('now', '-30 day'), datetime('now'))");
$pdo->exec("INSERT INTO company_members (id, company_id, user_id, role, status, created_at, updated_at) VALUES
    (100, 10, 1, 'admin', 'active', datetime('now', '-20 day'), datetime('now')),
    (101, 10, 2, 'member', 'active', datetime('now', '-12 day'), datetime('now'))");

$pdo->exec("INSERT INTO courses (id, title, status) VALUES
    (300, 'Leadership', 'approved'),
    (301, 'Productivite', 'approved')");

$pdo->exec("INSERT INTO orders (id, user_id, status, total_xof, provider, reference, created_at) VALUES
    (700, 1, 'paid', 60000, 'fedapay', 'ORD-PAID', datetime('now', '-3 day')),
    (710, 1, 'pending', 40000, 'fedapay', 'ORD-PENDING', datetime('now', '-1 day'))");

$pdo->exec("INSERT INTO order_items (id, order_id, course_id, company_id, quantity, unit_price_xof) VALUES
    (701, 700, 300, 10, 3, 20000),
    (711, 710, 301, 10, 2, 20000)");

$pdo->exec("INSERT INTO payments (id, order_id, provider, reference, status) VALUES
    (702, 700, 'fedapay', 'PAY-PAID', 'paid'),
    (712, 710, 'fedapay', 'PAY-PENDING', 'pending')");

$pdo->exec("INSERT INTO company_course_seat_purchases (
    id, company_id, course_id, order_id, order_item_id, purchased_by, seats_total, unit_price_xof, total_xof, status, created_at
) VALUES
    (703, 10, 300, 700, 701, 1, 3, 20000, 60000, 'active', datetime('now', '-3 day'))");

$service = new \App\Services\Company\DashboardService(
    new \App\Models\CompanyDashboardModel($pdo),
    new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
);

$state = $service->buildInitialState(['id' => 1]);

assert_true((int) ($state['unreadCount'] ?? 0) === 1, 'Unread notifications should still be counted.', $errors);
assert_true(count((array) ($state['billingRows'] ?? [])) === 2, 'Billing history should expose paid and pending company orders.', $errors);
assert_true((int) (($state['billingSummary']['orders'] ?? 0)) === 2, 'Billing summary should count company order rows.', $errors);
assert_true((int) (($state['billingSummary']['seats_total'] ?? 0)) === 5, 'Billing summary should total purchased seats from order items.', $errors);
assert_true((int) (($state['billingSummary']['total_xof'] ?? 0)) === 100000, 'Total amount should match company order items.', $errors);
assert_true((string) (($state['billingRows'][0]['order_reference'] ?? '')) === 'ORD-PENDING', 'Latest pending order should be first in billing history.', $errors);

$paidRowFound = false;
foreach ((array) ($state['billingRows'] ?? []) as $row) {
    if ((string) ($row['order_reference'] ?? '') === 'ORD-PAID') {
        $paidRowFound = ((string) ($row['order_status'] ?? '') === 'paid')
            && ((string) ($row['payment_status'] ?? '') === 'paid');
    }
}
assert_true($paidRowFound, 'Paid company order should expose raw order and payment statuses.', $errors);

if ($errors !== []) {
    echo "Company billing functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Company billing functional OK\n";
exit(0);
