<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

$config = require $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';

/** @var PDO $pdo */
try {
    $pdo = db($config['db']);
} catch (Throwable $e) {
    fwrite(STDERR, "Company student role check FAILED\n");
    fwrite(STDERR, "Database connection error: " . $e->getMessage() . "\n");
    exit(1);
}

$stmt = $pdo->query(
    "SELECT DISTINCT u.id, u.email, u.roles_json
     FROM users u
     JOIN company_members cm ON cm.user_id = u.id
     WHERE cm.status = 'active'"
);

$total = 0;
$missing = 0;
$examples = [];
foreach (($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
    $total++;
    $roles = json_decode((string) ($row['roles_json'] ?? ''), true);
    if (!is_array($roles) || !in_array('student', $roles, true)) {
        $missing++;
        if (count($examples) < 5) {
            $examples[] = (string) ($row['email'] ?? $row['id'] ?? '');
        }
    }
}

echo "company_members_total={$total}\n";
echo "missing_student_role={$missing}\n";
if ($missing > 0) {
    echo 'examples=' . implode(', ', $examples) . "\n";
}

if ($missing > 0) {
    exit(1);
}

exit(0);
