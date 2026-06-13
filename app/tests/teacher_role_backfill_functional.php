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

function build_sqlite_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("ATTACH DATABASE ':memory:' AS INFORMATION_SCHEMA");
    $pdo->sqliteCreateFunction('DATABASE', static fn (): string => 'main', 0);
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.TABLES (TABLE_SCHEMA TEXT, TABLE_NAME TEXT)');
    return $pdo;
}

/**
 * @return array<int,string>
 */
function read_roles(PDO $pdo, int $userId): array
{
    $stmt = $pdo->query('SELECT roles_json FROM users WHERE id = ' . $userId . ' LIMIT 1');
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    $roles = is_array($row) ? json_decode((string) ($row['roles_json'] ?? '[]'), true) : [];
    return is_array($roles) ? $roles : [];
}

$errors = [];
$pdo = build_sqlite_pdo();

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT,
    roles_json TEXT
)');
$pdo->exec('CREATE TABLE teacher_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    status TEXT
)');

$pdo->exec("INSERT INTO users (id, email, roles_json) VALUES
    (1, 'legacy@example.test', '[\"teacher\"]'),
    (2, 'approved@example.test', '[\"student\",\"teacher_candidate\"]'),
    (3, 'pending@example.test', '[\"student\"]'),
    (4, 'rejected@example.test', '[\"student\",\"teacher_candidate\"]'),
    (5, 'conflict@example.test', '[\"student\",\"teacher\"]'),
    (6, 'duplicate@example.test', '[\"student\",\"teacher\",\"teacher\"]')
");

$pdo->exec("INSERT INTO teacher_applications (user_id, status) VALUES
    (2, 'approved'),
    (3, 'pending'),
    (4, 'rejected'),
    (5, 'pending')
");

$service = new \App\Services\Admin\TeacherRoleBackfillService($pdo);

$dryRun = $service->run(false);
$drySummary = is_array($dryRun['summary'] ?? null) ? $dryRun['summary'] : [];

assert_true((int) ($drySummary['changed_user_count'] ?? 0) === 4, 'Dry-run should detect four safe role changes.', $errors);
assert_true((int) ($drySummary['warning_count'] ?? 0) === 3, 'Dry-run should surface three warning cases requiring manual review.', $errors);
assert_true(read_roles($pdo, 2) === ['student', 'teacher_candidate'], 'Dry-run must not persist approved user changes.', $errors);
assert_true(read_roles($pdo, 3) === ['student'], 'Dry-run must not persist pending candidate restoration.', $errors);

$applyRun = $service->run(true);
$applySummary = is_array($applyRun['summary'] ?? null) ? $applyRun['summary'] : [];

assert_true((int) ($applySummary['changed_user_count'] ?? 0) === 4, 'Apply mode should persist the same four safe changes.', $errors);
assert_true(read_roles($pdo, 1) === ['teacher'], 'Legacy teacher without application should remain unchanged for manual review.', $errors);
assert_true(read_roles($pdo, 2) === ['student', 'teacher'], 'Approved application should promote teacher and clear candidate role.', $errors);
assert_true(read_roles($pdo, 3) === ['student', 'teacher_candidate'], 'Pending application should restore teacher_candidate.', $errors);
assert_true(read_roles($pdo, 4) === ['student'], 'Rejected application should clear stale candidate role.', $errors);
assert_true(read_roles($pdo, 5) === ['student', 'teacher'], 'Teacher with pending application and no approval should stay unchanged until manual review.', $errors);
assert_true(read_roles($pdo, 6) === ['student', 'teacher'], 'Duplicate roles should be normalized during apply mode.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "teacher_role_backfill_functional: OK\n");
