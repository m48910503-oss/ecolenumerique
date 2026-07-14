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
    define('APP_BASE_URL', 'https://www.slidecenter.bj/ecolenumerique');
}

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

$errors = [];
$originalHost = $_SERVER['HTTP_HOST'] ?? null;
$originalEnv = getenv('APP_ENV');
$originalBootstrap = getenv('ADMIN_BOOTSTRAP_REGISTER');
$originalSecret = getenv('ADMIN_REGISTER_SECRET');

$_SERVER['HTTP_HOST'] = 'prod.example.test';
putenv('APP_ENV=production');
putenv('ADMIN_BOOTSTRAP_REGISTER=0');
putenv('ADMIN_REGISTER_SECRET=super-secret-link');

$routes = require $rootDir . '/app/routes.php';
assert_true(isset($routes['admin/register']), 'Admin register route should always be declared.', $errors);
assert_true(admin_register_access_allowed('') === false, 'Secret access should be denied without a token in production.', $errors);
assert_true(admin_register_access_allowed('wrong-token') === false, 'Secret access should reject an invalid token.', $errors);
assert_true(admin_register_access_allowed('super-secret-link') === true, 'Secret access should accept the configured token.', $errors);
assert_true(
    admin_register_secret_link() === 'https://www.slidecenter.bj/ecolenumerique/admin/register?token=super-secret-link',
    'Secret link helper should build the expected absolute URL.',
    $errors
);

putenv('APP_ENV=local');
putenv('ADMIN_BOOTSTRAP_REGISTER=1');
putenv('ADMIN_REGISTER_SECRET=');
assert_true(admin_register_access_allowed('') === true, 'Local bootstrap mode should still allow the register page without a token.', $errors);

if ($originalHost !== null) {
    $_SERVER['HTTP_HOST'] = (string) $originalHost;
} else {
    unset($_SERVER['HTTP_HOST']);
}
putenv($originalEnv === false ? 'APP_ENV' : 'APP_ENV=' . $originalEnv);
putenv($originalBootstrap === false ? 'ADMIN_BOOTSTRAP_REGISTER' : 'ADMIN_BOOTSTRAP_REGISTER=' . $originalBootstrap);
putenv($originalSecret === false ? 'ADMIN_REGISTER_SECRET' : 'ADMIN_REGISTER_SECRET=' . $originalSecret);

if ($errors !== []) {
    echo "Admin bootstrap route guard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Admin bootstrap route guard OK\n";
exit(0);
