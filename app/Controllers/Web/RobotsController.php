<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;

final class RobotsController extends Controller
{
    public function __invoke(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');

        $basePath = '/' . trim((string) (defined('BASE_PATH') ? BASE_PATH : '/'), '/');
        if ($basePath === '//') {
            $basePath = '/';
        }

        $path = static function (string $route) use ($basePath): string {
            if ($basePath === '/' || $basePath === '') {
                return '/' . ltrim($route, '/');
            }
            return rtrim($basePath, '/') . '/' . ltrim($route, '/');
        };

        $lines = [
            'User-agent: *',
            'Allow: ' . ($basePath === '/' ? '/' : rtrim($basePath, '/') . '/'),
            'Disallow: ' . $path('admin/'),
            'Disallow: ' . $path('teacher/'),
            'Disallow: ' . $path('student/'),
            'Disallow: ' . $path('company/'),
            'Disallow: ' . $path('login'),
            'Disallow: ' . $path('register'),
            'Disallow: ' . $path('checkout'),
            'Disallow: ' . $path('support'),
            'Disallow: ' . $path('notifications'),
            'Disallow: ' . $path('profile'),
            'Sitemap: ' . $this->absUrl('sitemap.xml'),
        ];

        echo implode("\n", $lines) . "\n";
    }
}
