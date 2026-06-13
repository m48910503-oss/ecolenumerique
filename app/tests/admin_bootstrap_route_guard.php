<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

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

$_SERVER['HTTP_HOST'] = 'localhost';
putenv('APP_ENV=local');
putenv('ADMIN_BOOTSTRAP_REGISTER=1');
$localRoutes = require $rootDir . '/app/routes.php';
assert_true(isset($localRoutes['admin/register']), 'Admin bootstrap route should exist in local mode when explicitly enabled.', $errors);

$_SERVER['HTTP_HOST'] = 'prod.example.test';
putenv('APP_ENV=production');
putenv('ADMIN_BOOTSTRAP_REGISTER=1');
$prodRoutes = require $rootDir . '/app/routes.php';
assert_true(!isset($prodRoutes['admin/register']), 'Admin bootstrap route must be absent in production.', $errors);

if ($originalHost !== null) {
    $_SERVER['HTTP_HOST'] = (string) $originalHost;
} else {
    unset($_SERVER['HTTP_HOST']);
}
putenv($originalEnv === false ? 'APP_ENV' : 'APP_ENV=' . $originalEnv);
putenv($originalBootstrap === false ? 'ADMIN_BOOTSTRAP_REGISTER' : 'ADMIN_BOOTSTRAP_REGISTER=' . $originalBootstrap);

if ($errors !== []) {
    echo "Admin bootstrap route guard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Admin bootstrap route guard OK\n";
exit(0);
