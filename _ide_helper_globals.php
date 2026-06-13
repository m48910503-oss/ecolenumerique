<?php
declare(strict_types=1);

/**
 * IDE helper stubs for MVC global helpers/constants.
 *
 * This file is intended for static analysis and editor indexing only.
 * It must not be required by the application at runtime.
 */

if (!defined('APP_FROM_NAME')) {
    define('APP_FROM_NAME', '');
}

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', '');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', '');
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return (string) $value;
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        return $path;
    }
}

if (!function_exists('abs_url')) {
    function abs_url(string $path = ''): string
    {
        return $path;
    }
}

if (!function_exists('view_csrf_token')) {
    function view_csrf_token(): string
    {
        return '';
    }
}

if (!function_exists('platform_setting_get')) {
    function platform_setting_get(string $key, string $default): string
    {
        return $default;
    }
}

if (!function_exists('is_local_environment')) {
    function is_local_environment(): bool
    {
        return false;
    }
}

if (!function_exists('view_format_money_xof')) {
    function view_format_money_xof($amount, string $zeroLabel = 'Gratuit'): string
    {
        return $zeroLabel;
    }
}

if (!function_exists('view_format_date_label')) {
    function view_format_date_label($value, bool $withTime = false, string $fallback = 'Non defini'): string
    {
        return $fallback;
    }
}

if (!function_exists('view_pluralize')) {
    function view_pluralize(int $count, string $singular, ?string $plural = null): string
    {
        return (string) $count . ' ' . ($count === 1 ? $singular : ($plural ?? ($singular . 's')));
    }
}
