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

$isMaintenanceExempt = static function (string $path): bool {
    if ($path === 'admin' || str_starts_with($path, 'admin/')) {
        return true;
    }

    if ($path === 'api' || str_starts_with($path, 'api/')) {
        return true;
    }

    return in_array($path, ['service_worker.js', 'robots.txt', 'sitemap.xml'], true);
};

if (function_exists('site_maintenance_enabled') && site_maintenance_enabled() && !$isMaintenanceExempt($normalizedPath)) {
    http_response_code(503);
    header('Retry-After: 3600');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    $siteName = trim(platform_setting_get('site_name', defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique'));
    if ($siteName === '') {
        $siteName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
    }

    \App\Core\View::render('web/maintenance', [
        'injectGlobalAssets' => false,
        'siteName' => $siteName,
        'siteLogoUrl' => function_exists('site_logo_url') ? (string) site_logo_url() : '',
        'maintenanceMessage' => function_exists('site_maintenance_message')
            ? (string) site_maintenance_message()
            : 'Maintenance temporaire en cours. Merci de revenir dans quelques minutes.',
        'supportEmail' => platform_setting_get('support_email', defined('APP_FROM_EMAIL') ? (string) APP_FROM_EMAIL : ''),
        'supportPhone' => platform_setting_get('support_phone', ''),
        'supportWhatsapp' => platform_setting_get('support_whatsapp', ''),
    ]);
    return;
}

$routes = require __DIR__ . '/app/routes.php';
if (\App\Core\RouteDispatcher::dispatch($normalizedPath, $routes)) {
    return;
}

http_response_code(404);
echo '<h1>404</h1><p>Page introuvable.</p>';
