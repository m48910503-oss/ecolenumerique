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
$parsedPath = parse_url((string) ($config['app']['base_url'] ?? ''), PHP_URL_PATH);
$basePath = $parsedPath !== null ? rtrim((string) $parsedPath, '/') : '';
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $basePath === '' ? '/' : $basePath);
}

require_once $rootDir . '/app/bootstrap.php';

$routes = require $rootDir . '/app/routes.php';
$errors = [];

$requiredRoutes = [
    '',
    'become-teacher',
    'course',
    'course_preview_open',
    'checkout',
    'service_worker.js',
    'api/courses',
    'api/cart',
    'api/course_favorites',
    'api/notifications_feed',
    'api/push_subscription',
    'api/support_conversations',
    'api/support_messages',
    'api/fedapay_webhook',
    'student/dashboard',
    'teacher/dashboard',
    'teacher/coupons',
    'teacher/withdrawals',
    'teacher/withdrawals/revenus',
    'teacher/withdrawals/transactions',
    'teacher/withdrawals/retraits',
    'company/dashboard',
    'company/analytics',
    'company/courses',
    'company/billing',
    'company/invites',
    'company/members',
    'company/onboarding',
    'company/sso',
    'company/ops',
    'company/reviews',
    'company/settings',
    'company/seats',
    'company/teams',
    'admin/dashboard',
    'admin/register',
    'admin/statistics',
    'admin/secondary_login',
    'admin/secondary_admins',
    'admin/secondary_admins/create',
    'admin/secondary_admins/grant',
    'admin/secondary_admins/edit',
    'admin/secondary_admins/activity',
    'admin/announcements',
    'admin/announcements/create',
    'admin/exam_preview',
];

foreach ($requiredRoutes as $path) {
    if (!isset($routes[$path])) {
        $errors[] = "Missing route: {$path}";
        continue;
    }
    $target = $routes[$path];
    if (is_string($target)) {
        if (!class_exists($target)) {
            $errors[] = "Route {$path} points to missing class {$target}";
            continue;
        }
        $controller = new $target();
        if (!is_callable($controller)) {
            $errors[] = "Route {$path} class {$target} is not invokable";
        }
        continue;
    }
    if (!is_callable($target)) {
        $errors[] = "Route {$path} target is not callable";
    }
}

$normalizeCases = [
    '/' => '',
    '/index.php' => '',
    '/course' => 'course',
    '/course.php' => 'course',
    '/api/courses' => 'api/courses',
    '/api/courses.php' => 'api/courses',
    '/index.php/api/cart' => 'api/cart',
];

foreach ($normalizeCases as $input => $expected) {
    $actual = \App\Core\RouteDispatcher::normalizePath($input, BASE_PATH);
    if ($actual !== $expected) {
        $errors[] = "normalizePath({$input}) expected {$expected}, got {$actual}";
    }
}

$probeRoutes = [
    '' => static function (): void {
    },
    'course' => static function (): void {
    },
    'api/courses' => static function (): void {
    },
];

$probePaths = ['/', '/course', '/api/courses'];
foreach ($probePaths as $probeUri) {
    $normalized = \App\Core\RouteDispatcher::normalizePath($probeUri, BASE_PATH);
    $ok = \App\Core\RouteDispatcher::dispatch($normalized, $probeRoutes);
    if (!$ok) {
        $errors[] = "dispatch failed for {$probeUri} ({$normalized})";
    }
}

// MVC guardrail: controllers/services must not call legacy global helpers directly.
$forbiddenHelperCalls = [
    'site_url(',
    'abs_url(',
    'send_email(',
    'site_logo_url(',
    'site_footer_text(',
    'redirect_self(',
    'current_url(',
];
$forbiddenSchemaRegex = '/\b(?:ensure_[a-z0-9_]+|[a-z0-9_]+_has_[a-z0-9_]+)\s*\(/i';
$guardFolders = [
    $rootDir . '/app/Controllers',
    $rootDir . '/app/Services',
];
foreach ($guardFolders as $folder) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
    foreach ($it as $entry) {
        if (!$entry->isFile() || strtolower($entry->getExtension()) !== 'php') {
            continue;
        }
        $path = (string) $entry->getPathname();
        $content = (string) file_get_contents($path);
        foreach ($forbiddenHelperCalls as $needle) {
            if (strpos($content, $needle) !== false) {
                $errors[] = "MVC guard failed: {$needle} found in {$path}";
            }
        }
        if (preg_match($forbiddenSchemaRegex, $content) === 1) {
            $errors[] = "MVC guard failed: legacy schema helper call found in {$path}";
        }
    }
}

// Schema guardrail: no runtime DDL in app code paths (use SQL migrations instead).
$ddlGuardPaths = [
    $rootDir . '/includes/helpers.php',
    $rootDir . '/app/Models',
];
$ddlRegex = '/\b(?:ALTER\s+TABLE|CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS)\b/i';
foreach ($ddlGuardPaths as $ddlPath) {
    if (is_file($ddlPath)) {
        $content = (string) file_get_contents($ddlPath);
        if (preg_match($ddlRegex, $content) === 1) {
            $errors[] = "Schema guard failed: runtime DDL found in {$ddlPath}";
        }
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ddlPath));
    foreach ($it as $entry) {
        if (!$entry->isFile() || strtolower($entry->getExtension()) !== 'php') {
            continue;
        }
        $path = (string) $entry->getPathname();
        $content = (string) file_get_contents($path);
        if (preg_match($ddlRegex, $content) === 1) {
            $errors[] = "Schema guard failed: runtime DDL found in {$path}";
        }
    }
}

if ($errors !== []) {
    echo "Routing smoke FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Routing smoke OK\n";
exit(0);
