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
    $pdo->sqliteCreateFunction('DATABASE', static fn (): string => 'main', 0);
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
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    password_hash TEXT,
    status TEXT,
    email_verified_at TEXT,
    auth_provider TEXT,
    phone TEXT,
    phone_country_code TEXT,
    phone_e164 TEXT,
    city TEXT,
    country TEXT,
    avatar_url TEXT,
    signature_path TEXT,
    roles_json TEXT,
    remember_selector TEXT,
    remember_validator_hash TEXT,
    notification_preferences_json TEXT,
    mfa_enabled INTEGER,
    mfa_enabled_at TEXT,
    updated_at TEXT
)');
register_sqlite_schema($pdo, 'users', [
    'id',
    'first_name',
    'last_name',
    'email',
    'password_hash',
    'status',
    'email_verified_at',
    'auth_provider',
    'phone',
    'phone_country_code',
    'phone_e164',
    'city',
    'country',
    'avatar_url',
    'signature_path',
    'roles_json',
    'remember_selector',
    'remember_validator_hash',
    'notification_preferences_json',
    'mfa_enabled',
    'mfa_enabled_at',
    'updated_at',
]);

$pdo->exec('CREATE TABLE email_verifications (id INTEGER PRIMARY KEY, user_id INTEGER, token_hash TEXT)');
register_sqlite_schema($pdo, 'email_verifications', ['id', 'user_id', 'token_hash']);

$pdo->exec('CREATE TABLE password_resets (id INTEGER PRIMARY KEY, user_id INTEGER, token_hash TEXT)');
register_sqlite_schema($pdo, 'password_resets', ['id', 'user_id', 'token_hash']);

$pdo->exec('CREATE TABLE notifications (id INTEGER PRIMARY KEY, user_id INTEGER, title TEXT)');
register_sqlite_schema($pdo, 'notifications', ['id', 'user_id', 'title']);

$pdo->exec('CREATE TABLE student_profiles (id INTEGER PRIMARY KEY, user_id INTEGER, certificate_display_name TEXT)');
register_sqlite_schema($pdo, 'student_profiles', ['id', 'user_id', 'certificate_display_name']);

$pdo->exec('CREATE TABLE teacher_profiles (id INTEGER PRIMARY KEY, user_id INTEGER, cv_path TEXT)');
register_sqlite_schema($pdo, 'teacher_profiles', ['id', 'user_id', 'cv_path']);

$pdo->exec('CREATE TABLE teacher_applications (id INTEGER PRIMARY KEY, user_id INTEGER, cv_path TEXT)');
register_sqlite_schema($pdo, 'teacher_applications', ['id', 'user_id', 'cv_path']);

$pdo->exec('CREATE TABLE support_conversations (id INTEGER PRIMARY KEY, created_by INTEGER, subject TEXT)');
register_sqlite_schema($pdo, 'support_conversations', ['id', 'created_by', 'subject']);

$pdo->exec('CREATE TABLE support_messages (id INTEGER PRIMARY KEY, conversation_id INTEGER, sender_id INTEGER, body TEXT)');
register_sqlite_schema($pdo, 'support_messages', ['id', 'conversation_id', 'sender_id', 'body']);

$pdo->exec('CREATE TABLE support_conversation_reads (conversation_id INTEGER, user_id INTEGER)');
register_sqlite_schema($pdo, 'support_conversation_reads', ['conversation_id', 'user_id']);

$pdo->exec('CREATE TABLE company_members (id INTEGER PRIMARY KEY, company_id INTEGER, user_id INTEGER, role TEXT, status TEXT)');
register_sqlite_schema($pdo, 'company_members', ['id', 'company_id', 'user_id', 'role', 'status']);

$pdo->exec('CREATE TABLE company_course_seat_assignments (id INTEGER PRIMARY KEY, member_user_id INTEGER, status TEXT)');
register_sqlite_schema($pdo, 'company_course_seat_assignments', ['id', 'member_user_id', 'status']);

$pdo->exec('CREATE TABLE companies (id INTEGER PRIMARY KEY, admin_user_id INTEGER, status TEXT)');
register_sqlite_schema($pdo, 'companies', ['id', 'admin_user_id', 'status']);

$localPasswordHash = password_hash('Sup3rPass!', PASSWORD_DEFAULT);
$blockedPasswordHash = password_hash('AnotherPass!', PASSWORD_DEFAULT);

$avatarDir = $rootDir . '/storage/uploads/avatars';
$signatureDir = $rootDir . '/storage/uploads/signatures';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}
if (!is_dir($signatureDir)) {
    mkdir($signatureDir, 0755, true);
}

$avatarPath = 'storage/uploads/avatars/test_profile_delete_avatar.jpg';
$signaturePath = 'storage/uploads/signatures/test_profile_delete_signature.png';
file_put_contents($rootDir . '/' . $avatarPath, 'avatar');
file_put_contents($rootDir . '/' . $signaturePath, 'signature');

$pdo->prepare(
    'INSERT INTO users (
        id, first_name, last_name, email, password_hash, status, email_verified_at, auth_provider,
        phone, phone_country_code, phone_e164, city, country, avatar_url, signature_path, roles_json,
        remember_selector, remember_validator_hash, notification_preferences_json, mfa_enabled, mfa_enabled_at, updated_at
     ) VALUES (
        :id, :first_name, :last_name, :email, :password_hash, :status, :email_verified_at, :auth_provider,
        :phone, :phone_country_code, :phone_e164, :city, :country, :avatar_url, :signature_path, :roles_json,
        :remember_selector, :remember_validator_hash, :notification_preferences_json, :mfa_enabled, :mfa_enabled_at, :updated_at
     )'
)->execute([
    ':id' => 1,
    ':first_name' => 'Alice',
    ':last_name' => 'Martin',
    ':email' => 'alice@example.test',
    ':password_hash' => $localPasswordHash,
    ':status' => 'active',
    ':email_verified_at' => '2026-04-10 10:00:00',
    ':auth_provider' => 'local',
    ':phone' => '90000000',
    ':phone_country_code' => '+229',
    ':phone_e164' => '+22990000000',
    ':city' => 'Cotonou',
    ':country' => 'BJ',
    ':avatar_url' => $avatarPath,
    ':signature_path' => $signaturePath,
    ':roles_json' => '["student"]',
    ':remember_selector' => 'remember-a',
    ':remember_validator_hash' => 'validator-a',
    ':notification_preferences_json' => '{"email_support":true}',
    ':mfa_enabled' => 1,
    ':mfa_enabled_at' => '2026-04-12 09:00:00',
    ':updated_at' => '2026-04-12 09:00:00',
]);

$pdo->prepare(
    'INSERT INTO users (
        id, first_name, last_name, email, password_hash, status, email_verified_at, auth_provider,
        roles_json, remember_selector, remember_validator_hash, notification_preferences_json, mfa_enabled, updated_at
     ) VALUES (
        :id, :first_name, :last_name, :email, :password_hash, :status, :email_verified_at, :auth_provider,
        :roles_json, :remember_selector, :remember_validator_hash, :notification_preferences_json, :mfa_enabled, :updated_at
     )'
)->execute([
    ':id' => 2,
    ':first_name' => 'Nora',
    ':last_name' => 'Owner',
    ':email' => 'owner@example.test',
    ':password_hash' => $blockedPasswordHash,
    ':status' => 'active',
    ':email_verified_at' => '2026-04-10 10:00:00',
    ':auth_provider' => 'local',
    ':roles_json' => '["company_admin"]',
    ':remember_selector' => 'remember-b',
    ':remember_validator_hash' => 'validator-b',
    ':notification_preferences_json' => null,
    ':mfa_enabled' => 0,
    ':updated_at' => '2026-04-12 09:00:00',
]);

$pdo->exec("INSERT INTO email_verifications (id, user_id, token_hash) VALUES (1, 1, 'email-token')");
$pdo->exec("INSERT INTO password_resets (id, user_id, token_hash) VALUES (1, 1, 'reset-token')");
$pdo->exec("INSERT INTO notifications (id, user_id, title) VALUES (1, 1, 'Notif')");
$pdo->exec("INSERT INTO student_profiles (id, user_id, certificate_display_name) VALUES (1, 1, 'Alice Martin')");
$pdo->exec("INSERT INTO teacher_profiles (id, user_id, cv_path) VALUES (1, 1, 'https://drive.example.test/cv')");
$pdo->exec("INSERT INTO teacher_applications (id, user_id, cv_path) VALUES (1, 1, 'https://drive.example.test/app')");
$pdo->exec("INSERT INTO support_conversations (id, created_by, subject) VALUES (1, 1, 'Besoin aide')");
$pdo->exec("INSERT INTO support_messages (id, conversation_id, sender_id, body) VALUES (1, 1, 1, 'Bonjour')");
$pdo->exec("INSERT INTO support_conversation_reads (conversation_id, user_id) VALUES (1, 1)");
$pdo->exec("INSERT INTO company_members (id, company_id, user_id, role, status) VALUES (1, 8, 1, 'member', 'active')");
$pdo->exec("INSERT INTO company_course_seat_assignments (id, member_user_id, status) VALUES (1, 1, 'active')");
$pdo->exec("INSERT INTO companies (id, admin_user_id, status) VALUES (1, 2, 'active')");
$pdo->exec("INSERT INTO company_members (id, company_id, user_id, role, status) VALUES (2, 1, 2, 'owner', 'active')");

$_SESSION['user_id'] = 1;
$_SESSION['user_roles'] = ['student'];

$service = new \App\Services\Web\ProfileService(
    new \App\Models\WebProfileModel($pdo),
    new \App\Services\Shared\CertificateOpsService(new \App\Models\SharedCertificateModel($pdo))
);

$lowercaseDeleteResult = $service->handle([
    'id' => 1,
    'roles_json' => '["student"]',
], 'POST', [
    'action' => 'delete_account',
    'delete_confirmation' => 'supprimer',
    'delete_acknowledge' => '1',
    'delete_account_password' => 'Sup3rPass!',
], []);

$lowercaseDeleteErrors = is_array($lowercaseDeleteResult['profileErrors'] ?? null) ? $lowercaseDeleteResult['profileErrors'] : [];
assert_true(($lowercaseDeleteResult['redirect'] ?? null) === null, 'Lowercase delete confirmation must not delete the account.', $errors);
assert_true(
    in_array('Tapez exactement SUPPRIMER pour confirmer la suppression definitive.', $lowercaseDeleteErrors, true),
    'Delete confirmation must require the exact SUPPRIMER keyword.',
    $errors
);
assert_true((string) ($pdo->query('SELECT email FROM users WHERE id = 1')->fetchColumn() ?: '') === 'alice@example.test', 'Failed delete confirmation must leave the account email unchanged.', $errors);

$deleteResult = $service->handle([
    'id' => 1,
    'roles_json' => '["student"]',
], 'POST', [
    'action' => 'delete_account',
    'delete_confirmation' => 'SUPPRIMER',
    'delete_acknowledge' => '1',
    'delete_account_password' => 'Sup3rPass!',
], []);

$redirect = (string) ($deleteResult['redirect'] ?? '');
$updatedUser = $pdo->query('SELECT * FROM users WHERE id = 1')->fetch(PDO::FETCH_ASSOC) ?: [];

assert_true($redirect === site_url('login?account_deleted=1'), 'Successful account deletion must redirect to login with confirmation.', $errors);
assert_true((string) ($updatedUser['first_name'] ?? '') === 'Compte', 'Deleted account first name must be anonymized.', $errors);
assert_true((string) ($updatedUser['last_name'] ?? '') === 'supprime', 'Deleted account last name must be anonymized.', $errors);
assert_true((string) ($updatedUser['status'] ?? '') === 'suspended', 'Deleted account must be suspended.', $errors);
assert_true(($updatedUser['email_verified_at'] ?? null) === null, 'Deleted account email verification must be cleared.', $errors);
assert_true((string) ($updatedUser['email'] ?? '') !== 'alice@example.test', 'Deleted account email must be replaced.', $errors);
assert_true(str_contains((string) ($updatedUser['email'] ?? ''), 'deleted+1+'), 'Deleted account email must use a deleted alias.', $errors);
assert_true(($updatedUser['avatar_url'] ?? null) === null, 'Deleted account avatar must be cleared.', $errors);
assert_true(($updatedUser['signature_path'] ?? null) === null, 'Deleted account signature must be cleared.', $errors);
assert_true(($updatedUser['remember_selector'] ?? null) === null, 'Deleted account remember selector must be cleared.', $errors);
assert_true(($updatedUser['remember_validator_hash'] ?? null) === null, 'Deleted account remember validator must be cleared.', $errors);
assert_true(($updatedUser['notification_preferences_json'] ?? null) === null, 'Deleted account notification preferences must be cleared.', $errors);
assert_true((int) ($updatedUser['mfa_enabled'] ?? 1) === 0, 'Deleted account MFA flag must be reset.', $errors);
assert_true(($updatedUser['mfa_enabled_at'] ?? null) === null, 'Deleted account MFA timestamp must be cleared.', $errors);
assert_true((string) ($updatedUser['password_hash'] ?? '') !== $localPasswordHash, 'Deleted account password hash must be replaced.', $errors);
assert_true(!isset($_SESSION['user_id']), 'Deleting the account must clear the authenticated session.', $errors);

foreach ([
    'email_verifications' => 'SELECT COUNT(*) FROM email_verifications WHERE user_id = 1',
    'password_resets' => 'SELECT COUNT(*) FROM password_resets WHERE user_id = 1',
    'notifications' => 'SELECT COUNT(*) FROM notifications WHERE user_id = 1',
    'student_profiles' => 'SELECT COUNT(*) FROM student_profiles WHERE user_id = 1',
    'teacher_profiles' => 'SELECT COUNT(*) FROM teacher_profiles WHERE user_id = 1',
    'teacher_applications' => 'SELECT COUNT(*) FROM teacher_applications WHERE user_id = 1',
    'support_conversations' => 'SELECT COUNT(*) FROM support_conversations WHERE created_by = 1',
    'support_messages' => 'SELECT COUNT(*) FROM support_messages WHERE sender_id = 1',
    'support_conversation_reads' => 'SELECT COUNT(*) FROM support_conversation_reads WHERE user_id = 1',
    'company_members' => 'SELECT COUNT(*) FROM company_members WHERE user_id = 1',
    'company_course_seat_assignments' => 'SELECT COUNT(*) FROM company_course_seat_assignments WHERE member_user_id = 1',
] as $label => $sql) {
    $remaining = (int) $pdo->query($sql)->fetchColumn();
    assert_true($remaining === 0, $label . ' rows linked to the deleted account must be purged.', $errors);
}

assert_true(!is_file($rootDir . '/' . $avatarPath), 'Avatar file must be deleted from storage.', $errors);
assert_true(!is_file($rootDir . '/' . $signaturePath), 'Signature file must be deleted from storage.', $errors);

$_SESSION = [
    'user_id' => 2,
    'user_roles' => ['company_admin'],
];

$blockedResult = $service->handle([
    'id' => 2,
    'roles_json' => '["company_admin"]',
], 'POST', [
    'action' => 'delete_account',
    'delete_confirmation' => 'SUPPRIMER',
    'delete_acknowledge' => '1',
    'delete_account_password' => 'AnotherPass!',
], []);

$blockedErrors = is_array($blockedResult['profileErrors'] ?? null) ? $blockedResult['profileErrors'] : [];
$blockedUser = $pdo->query('SELECT * FROM users WHERE id = 2')->fetch(PDO::FETCH_ASSOC) ?: [];

assert_true(($blockedResult['redirect'] ?? null) === null, 'Company owner account must not be deleted from profile page.', $errors);
assert_true(
    in_array('Ce compte gere encore une organisation entreprise. Transferez d abord l administration avant de supprimer le compte.', $blockedErrors, true),
    'Company owner deletion must explain that ownership has to be transferred first.',
    $errors
);
assert_true((string) ($blockedUser['status'] ?? '') === 'active', 'Blocked deletion must leave the account unchanged.', $errors);
assert_true(isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === 2, 'Blocked deletion must keep the current session active.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "profile_account_delete_functional: OK\n");
