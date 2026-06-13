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

$user = [
    'roles_json' => '["student","teacher","company_admin","admin_secondary","teacher"]',
];

$roles = \App\Core\DashboardContextResolver::rolesFromUser($user);
$spaces = \App\Core\DashboardContextResolver::availableSpacesForUser($user);

assert_true($roles === ['student', 'teacher', 'company_admin', 'admin_secondary'], 'Roles should be normalized and deduplicated.', $errors);
assert_true($spaces === ['admin', 'teacher', 'company', 'student'], 'Spaces should follow unified admin > teacher > company > student priority.', $errors);
assert_true(\App\Core\DashboardContextResolver::resolveSpaceForUser($user, null) === 'admin', 'Default resolved space should honor unified priority.', $errors);
assert_true(\App\Core\DashboardContextResolver::resolveSpaceForUser($user, 'company') === 'company', 'Requested available space should be preserved.', $errors);
assert_true(\App\Core\DashboardContextResolver::dashboardUrlForUser($user) === '/ecolenumerique/admin/dashboard', 'Default dashboard URL should honor admin priority.', $errors);
assert_true(\App\Core\DashboardContextResolver::dashboardUrlForUser($user, 'teacher') === '/ecolenumerique/teacher/dashboard', 'Preferred space should drive dashboard URL when available.', $errors);
assert_true(\App\Core\DashboardContextResolver::spaceLabel('company') === 'Espace entreprise', 'Company space label should be explicit.', $errors);
assert_true(\App\Core\DashboardContextResolver::usesDashboardShell('teacher') === true, 'Teacher space should use dashboard shell.', $errors);
assert_true(\App\Core\DashboardContextResolver::usesDashboardShell('company') === false, 'Company space should not be forced into teacher/student shell.', $errors);

$teacherOnlyUser = [
    'roles_json' => '["teacher"]',
];
$teacherOnlySpaces = \App\Core\DashboardContextResolver::availableSpacesForUser($teacherOnlyUser);
assert_true($teacherOnlySpaces === ['teacher', 'student'], 'Teacher-only accounts should also expose the student learning space.', $errors);
assert_true(\App\Core\DashboardContextResolver::resolveSpaceForUser($teacherOnlyUser, 'student') === 'student', 'Teacher-only accounts should be able to select the student space.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "dashboard_context_resolver_functional: OK\n");
