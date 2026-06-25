<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Service
{
    private ?Session $session = null;
    private ?Flash $flash = null;
    private ?Auth $auth = null;
    private ?SchemaGuard $schemaGuard = null;

    protected function flashSet(string $key, mixed $value): void
    {
        $this->flash()->set($key, $value);
    }

    protected function flashGet(string $key, mixed $default = null): mixed
    {
        return $this->flash()->get($key, $default);
    }

    protected function currentUser(): ?array
    {
        return $this->auth()->currentUser();
    }

    protected function isAuthenticated(): bool
    {
        return $this->auth()->isAuthenticated();
    }

    protected function loginUser(array $user, bool $remember = false): void
    {
        $this->auth()->login($user, $remember);
    }

    protected function logoutUser(): void
    {
        $this->auth()->logout();
    }

    protected function clearRememberCookies(): void
    {
        $this->auth()->clearRememberCookies();
    }

    protected function siteUrl(string $path = ''): string
    {
        if (function_exists('site_url')) {
            return \site_url($path);
        }
        $basePath = defined('BASE_PATH') ? (string) BASE_PATH : '/';
        $base = rtrim($basePath, '/');
        return $base . '/' . ltrim($path, '/');
    }

    protected function absUrl(string $path = ''): string
    {
        if (function_exists('abs_url')) {
            return \abs_url($path);
        }
        $base = defined('APP_BASE_URL') ? (string) APP_BASE_URL : '';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    protected function siteLogoUrl(): string
    {
        if (function_exists('site_logo_url')) {
            return \site_logo_url();
        }
        return '';
    }

    protected function siteFooterText(): string
    {
        if (function_exists('site_footer_text')) {
            return \site_footer_text();
        }
        return 'Plateforme e-learning.';
    }

    protected function currentUrl(): string
    {
        if (function_exists('current_url')) {
            return \current_url();
        }
        $scheme = (!empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        return $scheme . '://' . $host . $uri;
    }

    protected function sanitizeReturnPath(?string $input, string $fallback = '/'): string
    {
        if (function_exists('sanitize_return_path')) {
            return \sanitize_return_path($input, $fallback);
        }
        $path = trim((string) ($input ?? ''));
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $fallback;
        }
        return '/' . ltrim($path, '/');
    }

    protected function redirectSelf(string $fallback = '/'): never
    {
        if (function_exists('redirect_self')) {
            \redirect_self($fallback);
        }
        $target = $this->sanitizeReturnPath($_SERVER['REQUEST_URI'] ?? null, $fallback);
        header('Location: ' . $target);
        exit;
    }

    protected function sendEmail(string $to, string $subject, string $body): bool
    {
        if (function_exists('send_email')) {
            return \send_email($to, $subject, $body);
        }
        return false;
    }

    protected function pdo(): PDO
    {
        return $this->resolvePdo();
    }

    protected function schema(): SchemaGuard
    {
        if ($this->schemaGuard instanceof SchemaGuard) {
            return $this->schemaGuard;
        }
        $this->schemaGuard = new SchemaGuard();
        return $this->schemaGuard;
    }

    private function flash(): Flash
    {
        if ($this->flash instanceof Flash) {
            return $this->flash;
        }
        $this->flash = new Flash($this->session());
        return $this->flash;
    }

    private function auth(): Auth
    {
        if ($this->auth instanceof Auth) {
            return $this->auth;
        }
        $this->auth = new Auth($this->resolvePdo(), $this->session());
        return $this->auth;
    }

    private function session(): Session
    {
        if ($this->session instanceof Session) {
            return $this->session;
        }
        $this->session = new Session();
        return $this->session;
    }

    private function resolvePdo(): PDO
    {
        return PdoRegistry::get();
    }
}
