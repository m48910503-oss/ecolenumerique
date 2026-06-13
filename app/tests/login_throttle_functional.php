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
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'login-throttle-' . bin2hex(random_bytes(8));
$throttle = new \App\Security\LoginThrottle($tempDir);
$email = 'user@example.test';
$ip = '127.0.0.1';

assert_true($throttle->isBlocked('web', $email, $ip) === false, 'Throttle should start unlocked.', $errors);

for ($i = 1; $i <= 4; $i++) {
    $throttle->recordFailure('web', $email, $ip);
}
assert_true($throttle->isBlocked('web', $email, $ip) === false, 'Throttle should allow fewer than five failures.', $errors);

$throttle->recordFailure('web', $email, $ip);
assert_true($throttle->isBlocked('web', $email, $ip) === true, 'Throttle should block after five failures.', $errors);

$throttle->clear('web', $email, $ip);
assert_true($throttle->isBlocked('web', $email, $ip) === false, 'Throttle clear should unlock the identity.', $errors);

cleanup_tree($tempDir);

if ($errors !== []) {
    echo "Login throttle functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Login throttle functional OK\n";
exit(0);

function cleanup_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            cleanup_tree($path);
        } elseif (is_file($path)) {
            @unlink($path);
        }
    }

    @rmdir($dir);
}
