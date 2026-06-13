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
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.TABLES (TABLE_SCHEMA TEXT, TABLE_NAME TEXT)');
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.COLUMNS (TABLE_SCHEMA TEXT, TABLE_NAME TEXT, COLUMN_NAME TEXT)');

    return $pdo;
}

/**
 * @param array<int,string> $tables
 */
function build_referrals_service(PDO $pdo): \App\Services\Teacher\ReferralsService
{
    \App\Core\PdoRegistry::set($pdo);

    return new \App\Services\Teacher\ReferralsService(
        new \App\Models\TeacherReferralsModel($pdo),
        new \App\Services\Shared\ReferralService(new \App\Models\SharedReferralModel($pdo)),
        new \App\Services\Shared\PlatformSettingsService(new \App\Models\SharedPlatformSettingsModel($pdo))
    );
}

$errors = [];
$_SESSION = [];
$_GET = [];
$_SERVER = [];

$pdo = build_sqlite_pdo();

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    referral_code TEXT
)');
register_sqlite_schema($pdo, 'users', ['id', 'first_name', 'last_name', 'referral_code']);

$pdo->exec('CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    status TEXT
)');
register_sqlite_schema($pdo, 'enrollments', ['id', 'user_id', 'status']);

$pdo->exec('CREATE TABLE referrals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    referrer_user_id INTEGER,
    referred_user_id INTEGER,
    created_at TEXT
)');
register_sqlite_schema($pdo, 'referrals', ['id', 'referrer_user_id', 'referred_user_id', 'created_at']);

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

$pdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY,
    title TEXT,
    status TEXT
)');
register_sqlite_schema($pdo, 'courses', ['id', 'title', 'status']);

$pdo->exec('CREATE TABLE orders (
    id INTEGER PRIMARY KEY,
    reference TEXT
)');
register_sqlite_schema($pdo, 'orders', ['id', 'reference']);

$pdo->exec('CREATE TABLE platform_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT
)');
register_sqlite_schema($pdo, 'platform_settings', ['setting_key', 'setting_value']);

$pdo->exec("INSERT INTO users (id, first_name, last_name, referral_code) VALUES
    (10, 'Alice', 'Teacher', 'REFTEST001'),
    (20, 'Bruno', 'Coach', 'REFBRUNO'),
    (30, 'Claire', 'Tutor', 'REFCLAIRE')
");

for ($buyerId = 2000; $buyerId < 2055; $buyerId++) {
    $stmt = $pdo->prepare('INSERT INTO users (id, first_name, last_name, referral_code) VALUES (:id, :first, :last, :code)');
    $stmt->execute([
        ':id' => $buyerId,
        ':first' => 'Buyer' . ($buyerId - 1999),
        ':last' => 'Test',
        ':code' => 'BUY' . $buyerId,
    ]);
}

$pdo->exec("INSERT INTO enrollments (user_id, status) VALUES (10, 'active')");
$pdo->exec("INSERT INTO courses (id, title, status) VALUES
    (101, 'Cours Word', 'approved'),
    (102, 'Cours Excel', 'approved'),
    (103, 'Cours SQL', 'approved')
");
$pdo->exec("INSERT INTO platform_settings (setting_key, setting_value) VALUES
    ('referral_l1_rate', '5'),
    ('referral_l2_rate', '2')
");

$insertReferral = $pdo->prepare('INSERT INTO referrals (referrer_user_id, referred_user_id, created_at) VALUES (:referrer, :referred, :created_at)');
for ($i = 1; $i <= 70; $i++) {
    $insertReferral->execute([
        ':referrer' => 10,
        ':referred' => 3000 + $i,
        ':created_at' => sprintf('2026-02-%02d 09:00:00', (($i - 1) % 28) + 1),
    ]);
}

$insertOrder = $pdo->prepare('INSERT INTO orders (id, reference) VALUES (:id, :reference)');
$insertCommission = $pdo->prepare('INSERT INTO referral_commissions (order_id, course_id, teacher_id, buyer_id, referrer_id, level, rate_percent, amount_xof, status, created_at) VALUES (:order_id, :course_id, :teacher_id, :buyer_id, :referrer_id, :level, :rate_percent, :amount_xof, :status, :created_at)');

$expectedTotal = 0;
$expectedLatest = '';
for ($i = 1; $i <= 60; $i++) {
    $buyerId = 2000 + (($i - 1) % 55);
    $courseId = 101 + (($i - 1) % 3);
    $teacherId = $i % 2 === 0 ? 20 : 30;
    $level = $i % 4 === 0 ? 2 : 1;
    $amount = 100 + ($i * 10);
    $createdAt = sprintf('2026-03-%02d 10:%02d:00', (($i - 1) % 28) + 1, ($i - 1) % 60);
    $expectedTotal += $amount;
    if ($createdAt > $expectedLatest) {
        $expectedLatest = $createdAt;
    }

    $insertOrder->execute([
        ':id' => $i,
        ':reference' => 'ORD-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
    ]);
    $insertCommission->execute([
        ':order_id' => $i,
        ':course_id' => $courseId,
        ':teacher_id' => $teacherId,
        ':buyer_id' => $buyerId,
        ':referrer_id' => 10,
        ':level' => $level,
        ':rate_percent' => $level === 1 ? 5 : 2,
        ':amount_xof' => $amount,
        ':status' => 'earned',
        ':created_at' => $createdAt,
    ]);
}

$service = build_referrals_service($pdo);
$user = ['id' => 10];
$result = $service->handle($user);
$view = is_array($result['view'] ?? null) ? $result['view'] : [];

assert_true((string) ($view['warning'] ?? '') === '', 'Exact metrics scenario should not expose a warning.', $errors);
assert_true(!empty($view['referralsTableReady']), 'Referrals table should be reported as ready.', $errors);
assert_true(!empty($view['commissionsTableReady']), 'Commissions table should be reported as ready.', $errors);
assert_true((int) ($view['referralCount'] ?? 0) === 70, 'Referral count should use the full referrals table.', $errors);
assert_true((int) ($view['commissionCount'] ?? 0) === 60, 'Commission count should use the full commissions table.', $errors);
assert_true((int) ($view['activeAffiliateCount'] ?? 0) === 55, 'Active affiliates should count distinct buyers, not only displayed rows.', $errors);
assert_true((int) ($view['referralEarnings'] ?? 0) === $expectedTotal, 'Referral earnings should sum all earned commissions.', $errors);
assert_true((string) ($view['latestCommissionAt'] ?? '') === $expectedLatest, 'Latest commission date should come from the max created_at value.', $errors);
assert_true(count((array) ($view['referralCommissions'] ?? [])) === 50, 'Displayed commissions should stay limited to 50 rows.', $errors);
assert_true(count((array) ($view['referralByUser'] ?? [])) === 50, 'Displayed affiliates should stay limited to 50 rows.', $errors);
assert_true(count((array) ($view['referralRows'] ?? [])) === 8, 'Referral rows should stay limited for the hero table.', $errors);
assert_true((string) ($view['referralLink'] ?? '') === 'http://localhost/ecolenumerique/register?ref=REFTEST001', 'Referral link should reuse the teacher referral code.', $errors);

$pagedResult = $service->handle($user, ['page' => 2], 'referees');
$pagedView = is_array($pagedResult['view'] ?? null) ? $pagedResult['view'] : [];
assert_true((string) ($pagedView['referralsPageMode'] ?? '') === 'referees', 'Dedicated referrals page should expose the referees mode.', $errors);
assert_true((int) (($pagedView['referralRowsPagination']['page'] ?? 0)) === 2, 'Dedicated referrals page should keep the requested page number.', $errors);
assert_true((int) (($pagedView['referralRowsPagination']['total'] ?? 0)) === 70, 'Dedicated referrals page should paginate the full referral total.', $errors);
assert_true(count((array) ($pagedView['referralRows'] ?? [])) === 10, 'Dedicated referrals page should expose a full page of rows.', $errors);

$fallbackPdo = build_sqlite_pdo();

$fallbackPdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    referral_code TEXT
)');
register_sqlite_schema($fallbackPdo, 'users', ['id', 'first_name', 'last_name', 'referral_code']);

$fallbackPdo->exec('CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    status TEXT
)');
register_sqlite_schema($fallbackPdo, 'enrollments', ['id', 'user_id', 'status']);

$fallbackPdo->exec('CREATE TABLE referrals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    referrer_user_id INTEGER,
    referred_user_id INTEGER,
    created_at TEXT
)');
register_sqlite_schema($fallbackPdo, 'referrals', ['id', 'referrer_user_id', 'referred_user_id', 'created_at']);

$fallbackPdo->exec('CREATE TABLE referral_commissions (
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
register_sqlite_schema($fallbackPdo, 'referral_commissions', ['id', 'order_id', 'course_id', 'teacher_id', 'buyer_id', 'referrer_id', 'level', 'rate_percent', 'amount_xof', 'status', 'created_at']);

$fallbackPdo->exec('CREATE TABLE courses (
    id INTEGER PRIMARY KEY,
    title TEXT,
    status TEXT
)');
register_sqlite_schema($fallbackPdo, 'courses', ['id', 'title', 'status']);

$fallbackPdo->exec('CREATE TABLE orders (
    id INTEGER PRIMARY KEY,
    reference TEXT
)');
register_sqlite_schema($fallbackPdo, 'orders', ['id', 'reference']);

$fallbackPdo->exec('CREATE TABLE platform_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT
)');
register_sqlite_schema($fallbackPdo, 'platform_settings', ['setting_key', 'setting_value']);

$fallbackPdo->exec("INSERT INTO users (id, first_name, last_name, referral_code) VALUES
    (1, 'Tutor', 'One', NULL),
    (10, 'Alice', 'Teacher', 'REFFALL001'),
    (2001, 'Maya', 'Buyer', NULL)
");
$fallbackPdo->exec("INSERT INTO enrollments (user_id, status) VALUES (10, 'active')");
$fallbackPdo->exec("INSERT INTO courses (id, title, status) VALUES (101, 'Fallback Course', 'published')");
$fallbackPdo->exec("INSERT INTO orders (id, reference) VALUES (1, 'ORD-FALLBACK-001')");
$fallbackPdo->exec("INSERT INTO referral_commissions (order_id, course_id, teacher_id, buyer_id, referrer_id, level, rate_percent, amount_xof, status, created_at) VALUES
    (1, 101, 1, 2001, 10, 1, 5, 250, 'earned', '2026-04-01 11:30:00')
");
$fallbackPdo->exec("INSERT INTO platform_settings (setting_key, setting_value) VALUES
    ('referral_l1_rate', '5'),
    ('referral_l2_rate', '2')
");

$fallbackService = build_referrals_service($fallbackPdo);
$fallbackResult = $fallbackService->handle(['id' => 10]);
$fallbackView = is_array($fallbackResult['view'] ?? null) ? $fallbackResult['view'] : [];

assert_true(!empty($fallbackView['referralsTableReady']), 'Fallback scenario should keep the referrals table ready.', $errors);
assert_true(!empty($fallbackView['commissionsTableReady']), 'Fallback scenario should keep the commissions table ready.', $errors);
assert_true((int) ($fallbackView['referralCount'] ?? 0) === 1, 'Fallback scenario should derive the referral count from commissions.', $errors);
assert_true((int) ($fallbackView['activeAffiliateCount'] ?? 0) === 1, 'Fallback scenario should count the active affiliate.', $errors);
assert_true(count((array) ($fallbackView['referralRows'] ?? [])) === 1, 'Fallback scenario should display a referral row even without the referrals table data.', $errors);
assert_true((int) (($fallbackView['referralRows'][0]['buyer_id'] ?? 0)) === 2001, 'Fallback scenario should expose the buyer as the visible referral row.', $errors);

$viewHtml = (string) @file_get_contents($rootDir . '/app/Views/teacher/referrals.php');
assert_true(str_contains($viewHtml, 'name="app-global-assets" content="off"'), 'Referral page should opt out of global theme assets.', $errors);
assert_true(str_contains($viewHtml, 'assets/js/tailwind_theme_bootstrap.js'), 'Referral page should load the Tailwind bootstrap helper.', $errors);
assert_true(str_contains($viewHtml, 'assets/js/global_popups.js'), 'Referral page should load the shared popup toast script.', $errors);
assert_true(str_contains($viewHtml, 'assets/js/web_theme.js'), 'Referral page should load the public web theme helper.', $errors);
assert_true(str_contains($viewHtml, 'assets/js/referral_copy.js'), 'Referral page should load the referral copy helper.', $errors);
assert_true(str_contains($viewHtml, 'assets/js/sidebar_toggle.js'), 'Referral page should load the sidebar toggle helper.', $errors);
assert_true(str_contains($viewHtml, 'global-popup-data'), 'Referral page should emit a backend toast payload.', $errors);
assert_true(str_contains($viewHtml, 'data-view="teacher/referrals"'), 'Referral page should expose the referrals view id on the shell.', $errors);
assert_true(!str_contains($viewHtml, 'data-referral-copy-status'), 'Referral page should not render inline referral copy success messages.', $errors);
assert_true(!str_contains($viewHtml, 'global-theme.css'), 'Referral page should not include the global theme stylesheet.', $errors);
assert_true(!str_contains($viewHtml, 'assets/js/theme.js'), 'Referral page should not include the legacy theme script.', $errors);

$warningPdo = build_sqlite_pdo();
$warningPdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    referral_code TEXT
)');
register_sqlite_schema($warningPdo, 'users', ['id', 'first_name', 'last_name', 'referral_code']);

$warningPdo->exec('CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    status TEXT
)');
register_sqlite_schema($warningPdo, 'enrollments', ['id', 'user_id', 'status']);

$warningPdo->exec('CREATE TABLE referrals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    referrer_user_id INTEGER,
    referred_user_id INTEGER,
    created_at TEXT
)');
register_sqlite_schema($warningPdo, 'referrals', ['id', 'referrer_user_id', 'referred_user_id', 'created_at']);

$warningPdo->exec('CREATE TABLE platform_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT
)');
register_sqlite_schema($warningPdo, 'platform_settings', ['setting_key', 'setting_value']);

$warningPdo->exec("INSERT INTO users (id, first_name, last_name, referral_code) VALUES (10, 'Alice', 'Teacher', 'REFWARN001')");
$warningPdo->exec("INSERT INTO enrollments (user_id, status) VALUES (10, 'active')");
$warningPdo->exec("INSERT INTO referrals (referrer_user_id, referred_user_id, created_at) VALUES
    (10, 5001, '2026-03-01 10:00:00'),
    (10, 5002, '2026-03-02 10:00:00')
");
$warningPdo->exec("INSERT INTO platform_settings (setting_key, setting_value) VALUES
    ('referral_l1_rate', '5'),
    ('referral_l2_rate', '2')
");

$warningService = build_referrals_service($warningPdo);
$warningResult = $warningService->handle(['id' => 10]);
$warningView = is_array($warningResult['view'] ?? null) ? $warningResult['view'] : [];

assert_true(!empty($warningView['referralsTableReady']), 'Warning scenario should still keep the referrals table ready.', $errors);
assert_true(empty($warningView['commissionsTableReady']), 'Warning scenario should report the commissions table as unavailable.', $errors);
assert_true((int) ($warningView['referralCount'] ?? 0) === 2, 'Warning scenario should still count referrals when that table exists.', $errors);
assert_true((int) ($warningView['commissionCount'] ?? 0) === 0, 'Warning scenario should zero commissions when the table is unavailable.', $errors);
assert_true(count((array) ($warningView['referralRows'] ?? [])) === 2, 'Warning scenario should still render referral rows from the referrals table.', $errors);
assert_true(str_contains((string) ($warningView['warning'] ?? ''), 'indisponibles pour le moment'), 'Warning scenario should expose a clear unavailable message.', $errors);

if ($errors !== []) {
    echo "Teacher referrals functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Teacher referrals functional OK\n";
exit(0);
