<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/app/bootstrap.php';

register_shutdown_function(static function (): void {
    try {
        if (!defined('WEBPUSH_ENABLED') || !WEBPUSH_ENABLED) {
            return;
        }
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
        $pdo = \App\Core\PdoRegistry::get();
        $service = new \App\Services\Shared\WebPushService(
            new \App\Models\SharedWebPushModel($pdo)
        );
        $service->processQueue(15);
    } catch (\Throwable $e) {
        // Keep request flow resilient if push dispatch fails.
    }
});

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$normalizedPath = \App\Core\RouteDispatcher::normalizePath($requestUri, BASE_PATH);

// Keep share links in the short form /share/{token}/.
$sharePrefix = 'share/';
if (str_starts_with($normalizedPath, $sharePrefix)) {
    $shareToken = trim(substr($normalizedPath, strlen($sharePrefix)));
    if ($shareToken !== '' && !isset($_GET['token'])) {
        $_GET['token'] = $shareToken;
    }
    $normalizedPath = 'share';
}

$routes = require __DIR__ . '/app/routes.php';
if (\App\Core\RouteDispatcher::dispatch($normalizedPath, $routes)) {
    return;
}

http_response_code(404);
echo '<h1>404</h1><p>Page introuvable.</p>';
