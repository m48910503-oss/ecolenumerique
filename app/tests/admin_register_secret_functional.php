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

require_once $rootDir . '/includes/helpers.php';
require_once $rootDir . '/app/Core/Auth.php';
require_once $rootDir . '/app/Core/Flash.php';
require_once $rootDir . '/app/Core/Model.php';
require_once $rootDir . '/app/Core/PdoRegistry.php';
require_once $rootDir . '/app/Core/SchemaGuard.php';
require_once $rootDir . '/app/Core/Service.php';
require_once $rootDir . '/app/Core/Session.php';
require_once $rootDir . '/app/Models/AdminRegisterModel.php';
require_once $rootDir . '/app/Services/Admin/Auth/RegisterService.php';

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
$originalEnv = getenv('APP_ENV');
$originalBootstrap = getenv('ADMIN_BOOTSTRAP_REGISTER');
$originalSecret = getenv('ADMIN_REGISTER_SECRET');
$originalHost = $_SERVER['HTTP_HOST'] ?? null;

$_SERVER['HTTP_HOST'] = 'prod.example.test';
putenv('APP_ENV=production');
putenv('ADMIN_BOOTSTRAP_REGISTER=0');
putenv('ADMIN_REGISTER_SECRET=super-secret-link');
$_SESSION = [];

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->sqliteCreateFunction('NOW', static fn (): string => '2026-07-14 10:00:00', 0);
$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    password_hash TEXT,
    status TEXT,
    email_verified_at TEXT,
    roles_json TEXT,
    created_at TEXT
)');

$service = new \App\Services\Admin\Auth\RegisterService(
    new \App\Models\AdminRegisterModel($pdo)
);

$denied = $service->handle('POST', [
    'first_name' => 'Awa',
    'last_name' => 'Ndiaye',
    'email' => 'awa@example.test',
    'password' => 'Azerty1!',
], 'wrong-token');

assert_true(($denied['redirectTo'] ?? null) === null, 'Register service should not redirect with an invalid token.', $errors);
assert_true(in_array("L'inscription administrateur est desactivee.", (array) ($denied['view']['errors'] ?? []), true), 'Invalid token should be rejected.', $errors);
assert_true($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() == 0, 'Invalid token must not create a user.', $errors);

$created = $service->handle('POST', [
    'first_name' => 'Awa',
    'last_name' => 'Ndiaye',
    'email' => 'awa@example.test',
    'password' => 'Azerty1!',
], 'super-secret-link');

assert_true((string) ($created['redirectTo'] ?? '') === '/ecolenumerique/admin/login', 'Successful registration should redirect to admin login.', $errors);

$userStmt = $pdo->query("SELECT first_name, last_name, email, roles_json, status, email_verified_at FROM users WHERE email = 'awa@example.test' LIMIT 1");
$user = $userStmt ? $userStmt->fetch(PDO::FETCH_ASSOC) : false;
assert_true(is_array($user), 'Successful registration should persist the admin user.', $errors);
if (is_array($user)) {
    $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true);
    assert_true(in_array('admin', is_array($roles) ? $roles : [], true), 'Super admin registration should create a primary admin role.', $errors);
    assert_true((string) ($user['status'] ?? '') === 'active', 'Super admin should be activated immediately.', $errors);
    assert_true(trim((string) ($user['email_verified_at'] ?? '')) !== '', 'Super admin email should be marked verified.', $errors);
}

assert_true(isset($_SESSION['_flash']['success']), 'Success flash should be set for the login page.', $errors);

if ($originalHost !== null) {
    $_SERVER['HTTP_HOST'] = (string) $originalHost;
} else {
    unset($_SERVER['HTTP_HOST']);
}
putenv($originalEnv === false ? 'APP_ENV' : 'APP_ENV=' . $originalEnv);
putenv($originalBootstrap === false ? 'ADMIN_BOOTSTRAP_REGISTER' : 'ADMIN_BOOTSTRAP_REGISTER=' . $originalBootstrap);
putenv($originalSecret === false ? 'ADMIN_REGISTER_SECRET' : 'ADMIN_REGISTER_SECRET=' . $originalSecret);

if ($errors !== []) {
    echo "Admin register secret functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Admin register secret functional OK\n";
exit(0);
