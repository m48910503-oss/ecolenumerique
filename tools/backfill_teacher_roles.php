<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__);
chdir($rootDir);

$config = require $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';
require_once $rootDir . '/app/bootstrap.php';

/**
 * @param array<int,string> $roles
 */
function format_roles(array $roles): string
{
    $json = json_encode($roles);
    return is_string($json) ? $json : '[]';
}

$args = array_slice($_SERVER['argv'] ?? [], 1);
$apply = in_array('--apply', $args, true);

try {
    /** @var \PDO $pdo */
    $pdo = db($config['db']);
} catch (Throwable $exception) {
    fwrite(STDERR, "teacher_role_backfill: FAILED\n");
    fwrite(STDERR, "Database connection error: " . $exception->getMessage() . "\n");
    exit(1);
}

$service = new \App\Services\Admin\TeacherRoleBackfillService($pdo);

try {
    $result = $service->run($apply);
} catch (Throwable $exception) {
    fwrite(STDERR, "teacher_role_backfill: FAILED\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

$summary = is_array($result['summary'] ?? null) ? $result['summary'] : [];
$changes = is_array($result['changes'] ?? null) ? $result['changes'] : [];
$warnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];

fwrite(STDOUT, 'teacher_role_backfill mode=' . ($apply ? 'apply' : 'dry-run') . PHP_EOL);
foreach ($summary as $key => $value) {
    fwrite(STDOUT, $key . '=' . (string) $value . PHP_EOL);
}

if ($changes !== []) {
    fwrite(STDOUT, "changes:\n");
    foreach ($changes as $change) {
        fwrite(
            STDOUT,
            '- user #' . (string) ($change['user_id'] ?? 0)
            . ' ' . implode(',', is_array($change['actions'] ?? null) ? $change['actions'] : [])
            . ' ' . format_roles(is_array($change['before_roles'] ?? null) ? $change['before_roles'] : [])
            . ' => ' . format_roles(is_array($change['after_roles'] ?? null) ? $change['after_roles'] : [])
            . PHP_EOL
        );
    }
}

if ($warnings !== []) {
    fwrite(STDOUT, "warnings:\n");
    foreach ($warnings as $warning) {
        fwrite(
            STDOUT,
            '- user #' . (string) ($warning['user_id'] ?? 0)
            . ' ' . implode(',', is_array($warning['warnings'] ?? null) ? $warning['warnings'] : [])
            . ' ' . format_roles(is_array($warning['roles'] ?? null) ? $warning['roles'] : [])
            . PHP_EOL
        );
    }
}

if (($summary['warning_count'] ?? 0) > 0 || (!$apply && ($summary['changed_user_count'] ?? 0) > 0)) {
    exit(1);
}

exit(0);
