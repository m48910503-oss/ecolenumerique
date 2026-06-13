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
    $pdo->sqliteCreateFunction('NOW', static fn(): string => date('Y-m-d H:i:s'), 0);
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.TABLES (TABLE_SCHEMA TEXT, TABLE_NAME TEXT)');
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.COLUMNS (TABLE_SCHEMA TEXT, TABLE_NAME TEXT, COLUMN_NAME TEXT)');

    return $pdo;
}

function build_withdrawals_service(PDO $pdo): \App\Services\Teacher\WithdrawalsService
{
    \App\Core\PdoRegistry::set($pdo);

    return new \App\Services\Teacher\WithdrawalsService(
        new \App\Models\TeacherWithdrawalsModel($pdo),
        new \App\Models\TeacherReferralsModel($pdo),
        new \App\Models\ReferralWalletModel($pdo),
        new \App\Services\Shared\PlatformSettingsService(new \App\Models\SharedPlatformSettingsModel($pdo)),
        new \App\Services\Shared\ReferralService(new \App\Models\SharedReferralModel($pdo))
    );
}

$errors = [];
$_SESSION = [];
$_GET = [];
$_POST = [];
$_SERVER = [];

$pdo = build_sqlite_pdo();

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    roles_json TEXT
)');
register_sqlite_schema($pdo, 'users', ['id', 'first_name', 'last_name', 'email', 'roles_json']);

$pdo->exec('CREATE TABLE platform_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT
)');
register_sqlite_schema($pdo, 'platform_settings', ['setting_key', 'setting_value']);

$pdo->exec('CREATE TABLE withdrawals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    withdrawal_type TEXT,
    amount_xof INTEGER,
    method TEXT,
    destination TEXT,
    status TEXT,
    reviewed_at TEXT,
    reviewed_by INTEGER,
    payout_reference TEXT,
    reason TEXT,
    created_at TEXT
)');
register_sqlite_schema($pdo, 'withdrawals', ['id', 'user_id', 'withdrawal_type', 'amount_xof', 'method', 'destination', 'status', 'reviewed_at', 'reviewed_by', 'payout_reference', 'reason', 'created_at']);

$pdo->exec('CREATE TABLE referral_commissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    course_id INTEGER,
    teacher_id INTEGER,
    buyer_id INTEGER,
    referrer_id INTEGER,
    level INTEGER,
    rate_percent REAL,
    amount_xof INTEGER,
    status TEXT,
    created_at TEXT
)');
register_sqlite_schema($pdo, 'referral_commissions', ['id', 'order_id', 'course_id', 'teacher_id', 'buyer_id', 'referrer_id', 'level', 'rate_percent', 'amount_xof', 'status', 'created_at']);

$pdo->exec('CREATE TABLE referral_redemptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    order_id INTEGER,
    amount_xof INTEGER,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
)');
register_sqlite_schema($pdo, 'referral_redemptions', ['id', 'user_id', 'order_id', 'amount_xof', 'status', 'created_at', 'updated_at']);

$pdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY,
    teacher_id INTEGER,
    title TEXT,
    status TEXT,
    created_at TEXT
)');
register_sqlite_schema($pdo, 'courses', ['id', 'teacher_id', 'title', 'status', 'created_at']);

$pdo->exec('CREATE TABLE orders (
    id INTEGER PRIMARY KEY,
    status TEXT,
    updated_at TEXT
)');
register_sqlite_schema($pdo, 'orders', ['id', 'status', 'updated_at']);

$pdo->exec('CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    course_id INTEGER,
    quantity INTEGER,
    unit_price_xof INTEGER
)');
register_sqlite_schema($pdo, 'order_items', ['id', 'order_id', 'course_id', 'quantity', 'unit_price_xof']);

$pdo->exec('CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT,
    body TEXT,
    url TEXT,
    created_at TEXT
)');
register_sqlite_schema($pdo, 'notifications', ['id', 'user_id', 'title', 'body', 'url', 'created_at']);

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, roles_json) VALUES
    (1, 'Admin', 'Root', 'admin@example.test', '[\"admin\"]'),
    (10, 'Alice', 'Teacher', 'alice@example.test', '[\"teacher\"]'),
    (20, 'Bruno', 'Referrer', 'bruno@example.test', '[\"student\"]'),
    (30, 'Claire', 'Partner', 'claire@example.test', '[\"teacher\"]')
");

$pdo->exec("INSERT INTO platform_settings (setting_key, setting_value) VALUES
    ('commission_percent', '20'),
    ('teacher_min_withdrawal_xof', '1000'),
    ('referral_min_withdrawal_xof', '300'),
    ('teacher_withdrawal_methods', 'fedapay')
");

$pdo->exec("INSERT INTO courses (id, teacher_id, title, status, created_at) VALUES
    (101, 10, 'Cours Word', 'approved', '2026-01-01 09:00:00')
");

$pdo->exec("INSERT INTO orders (id, status, updated_at) VALUES
    (5001, 'paid', '2026-05-10 10:00:00'),
    (5002, 'paid', '2026-05-11 10:00:00')
");

$pdo->exec("INSERT INTO order_items (order_id, course_id, quantity, unit_price_xof) VALUES
    (5001, 101, 1, 4000),
    (5002, 101, 1, 6000)
");

$pdo->exec("INSERT INTO referral_commissions (order_id, course_id, teacher_id, buyer_id, referrer_id, level, rate_percent, amount_xof, status, created_at) VALUES
    (5001, 101, 10, 200, 10, 1, 5, 400, 'earned', '2026-05-10 10:15:00'),
    (5002, 101, 10, 201, 20, 1, 5, 500, 'earned', '2026-05-11 10:15:00'),
    (7001, 301, 30, 202, 10, 1, 5, 600, 'earned', '2026-05-12 10:15:00')
");

$pdo->exec("INSERT INTO withdrawals (user_id, withdrawal_type, amount_xof, method, destination, status, reviewed_at, reviewed_by, payout_reference, reason, created_at) VALUES
    (10, 'teacher_sales', 1000, 'fedapay', '+22997000000', 'paid', '2026-05-08 09:00:00', 1, 'RET-001', NULL, '2026-05-08 08:00:00'),
    (10, 'teacher_sales', 500, 'fedapay', '+22997000000', 'requested', NULL, NULL, 'RET-002', NULL, '2026-05-15 08:00:00'),
    (10, 'referral', 200, 'fedapay', '+22997000000', 'paid', '2026-05-07 09:00:00', 1, 'RET-003', NULL, '2026-05-07 08:00:00'),
    (10, 'referral', 100, 'fedapay', '+22997000000', 'requested', NULL, NULL, 'RET-004', NULL, '2026-05-16 08:00:00')
");

$pdo->exec("INSERT INTO referral_redemptions (user_id, order_id, amount_xof, status, created_at, updated_at) VALUES
    (10, 9001, 50, 'reserved', '2026-05-16 09:00:00', '2026-05-16 09:00:00')
");

$service = build_withdrawals_service($pdo);
$user = [
    'id' => 10,
    'first_name' => 'Alice',
    'last_name' => 'Teacher',
    'email' => 'alice@example.test',
    'phone_country_code' => '+229',
    'phone' => '97000000',
];

$result = $service->handle($user, 'GET', [], ['period' => 'all']);
$view = is_array($result['view'] ?? null) ? $result['view'] : [];

assert_true((int) ($view['grossTotal'] ?? 0) === 10000, 'Teacher sales gross total should come from paid course sales.', $errors);
assert_true((int) ($view['referralTotal'] ?? 0) === 900, 'Referral total on withdrawals should represent referral costs on teacher courses.', $errors);
assert_true((int) ($view['referralEarnings'] ?? 0) === 1000, 'Referral earnings should represent commissions where the teacher is the referrer.', $errors);
assert_true((int) ($view['available'] ?? 0) === 5600, 'Available sales balance should subtract platform fee, referral costs and locked sales withdrawals.', $errors);
assert_true((int) ($view['salesAvailable'] ?? 0) === 5600, 'Sales available helper should stay aligned with the main sales balance.', $errors);
assert_true((int) ($view['referralWithdrawAvailable'] ?? 0) === 650, 'Referral withdrawal balance should exclude paid, pending and locked referral amounts only.', $errors);
assert_true((int) ($view['pendingReferralWithdrawals'] ?? 0) === 100, 'Pending referral withdrawals should be tracked separately.', $errors);
assert_true(count((array) ($view['withdrawals'] ?? [])) === 4, 'Withdrawals history should expose both sales and referral requests.', $errors);

$postResult = $service->handle($user, 'POST', [
    'period' => 'all',
    'amount_xof' => '300',
    'method' => 'fedapay',
    'withdrawal_type' => 'referral',
], []);

$redirectTo = (string) ($postResult['redirectTo'] ?? '');
assert_true($redirectTo !== '' && str_contains($redirectTo, '/teacher/withdrawals') && str_contains($redirectTo, 'period=all'), 'Referral withdrawal request should redirect with the preserved period.', $errors);

$referralRequestCount = (int) $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE user_id = 10 AND withdrawal_type = 'referral'")->fetchColumn();
assert_true($referralRequestCount === 3, 'Referral withdrawal POST should create a dedicated referral withdrawal row.', $errors);

$latestReferralAmount = (int) $pdo->query("SELECT amount_xof FROM withdrawals WHERE user_id = 10 AND withdrawal_type = 'referral' ORDER BY id DESC LIMIT 1")->fetchColumn();
assert_true($latestReferralAmount === 300, 'Referral withdrawal POST should persist the requested amount.', $errors);

$viewHtml = (string) @file_get_contents($rootDir . '/app/Views/teacher/withdrawals.php');
assert_true(str_contains($viewHtml, 'Coût de parrainage'), 'Withdrawals view should label referral deductions as a cost.', $errors);
assert_true(str_contains($viewHtml, 'Retirer mes gains de parrainage'), 'Withdrawals view should expose a dedicated referral withdrawal action.', $errors);
assert_true(str_contains($viewHtml, 'name="withdrawal_type"'), 'Withdrawals modal should submit the withdrawal type.', $errors);

if ($errors !== []) {
    echo "Teacher withdrawals functional FAILED\n";
    foreach ($errors as $error) {
        echo ' - ' . $error . "\n";
    }
    exit(1);
}

echo "Teacher withdrawals functional OK\n";
