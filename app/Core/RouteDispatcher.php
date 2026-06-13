<?php
declare(strict_types=1);

namespace App\Core;

final class RouteDispatcher
{
    public static function normalizePath(string $requestUri, string $basePath): string
    {
        $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '/');

        $normalizedBase = rtrim($basePath, '/');
        if ($normalizedBase !== '' && $normalizedBase !== '/' && str_starts_with($path, $normalizedBase)) {
            $path = substr($path, strlen($normalizedBase));
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '/index.php') {
            $path = '/';
        } elseif (str_starts_with($path, '/index.php/')) {
            $path = substr($path, strlen('/index.php'));
        }

        $path = trim($path, '/');
        if ($path !== '' && str_ends_with($path, '.php')) {
            $path = substr($path, 0, -4);
        }

        return trim($path, '/');
    }

    /**
     * @param array<string, callable|string> $routes
     */
    public static function dispatch(string $normalizedPath, array $routes): bool
    {
        if (!isset($routes[$normalizedPath])) {
            return false;
        }

        $target = $routes[$normalizedPath];
        if (is_string($target)) {
            if (!class_exists($target)) {
                return false;
            }
            $controller = new $target();
            if (!is_callable($controller)) {
                return false;
            }
            $controller();
            return true;
        }

        if (is_callable($target)) {
            $target();
            return true;
        }

        return false;
    }
}


