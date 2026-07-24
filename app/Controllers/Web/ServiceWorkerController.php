<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;

final class ServiceWorkerController extends Controller
{
    public function __invoke(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            http_response_code(405);
            echo 'Method not allowed';
            return;
        }

        $file = dirname(__DIR__, 3) . '/assets/js/service_worker.js';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'Service worker not found';
            return;
        }

        $basePath = defined('BASE_PATH') ? rtrim((string) BASE_PATH, '/') : '';
        $scope = $basePath === '' ? '/' : ($basePath . '/');

        header('Content-Type: application/javascript; charset=UTF-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Service-Worker-Allowed: ' . $scope);

        readfile($file);
    }
}
