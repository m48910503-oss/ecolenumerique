<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected Session $session;
    protected Flash $flash;
    protected Csrf $csrf;
    protected ?Auth $auth = null;

    public function __construct()
    {
        $this->session = new Session();
        $this->flash = new Flash($this->session);
        $this->csrf = new Csrf($this->session);
    }

    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function disableCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    protected function csrfToken(): string
    {
        return $this->csrf->token();
    }

    protected function requireCsrf(): void
    {
        $this->csrf->requireFromPost();
    }

    protected function flashSet(string $key, mixed $value): void
    {
        $this->flash->set($key, $value);
    }

    protected function flashGet(string $key, mixed $default = null): mixed
    {
        return $this->flash->get($key, $default);
    }

    protected function currentUser(): ?array
    {
        return $this->auth()->currentUser();
    }

    protected function isAuthenticated(): bool
    {
        return $this->auth()->isAuthenticated();
    }

    protected function requireAuth(bool $mustBeVerified = true, ?string $returnPath = null): array
    {
        return $this->auth()->requireAuthenticated($mustBeVerified, $returnPath);
    }

    protected function login(array $user, bool $remember = false): void
    {
        $this->auth()->login($user, $remember);
    }

    protected function logout(): void
    {
        $this->auth()->logout();
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
        return $this->siteUrl('assets/img/logo-site.png');
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

    /**
     * @param array<string,mixed>|null $currentUser
     */
    protected function publicDashboardUrl(?array $currentUser = null): string
    {
        $currentUser = is_array($currentUser) ? $currentUser : $this->currentUser();
        if (!is_array($currentUser)) {
            return $this->siteUrl('student/dashboard');
        }

        return \App\Core\DashboardContextResolver::dashboardUrlForUser($currentUser) ?: $this->siteUrl('student/dashboard');
    }

    /**
     * @param array<int,array{label:string,url:string,icon?:string,variant?:string}> $actions
     * @return array<string,mixed>
     */
    protected function publicErrorViewData(string $title, string $message, array $actions = []): array
    {
        $currentUser = $this->currentUser();
        $isLoggedIn = is_array($currentUser);
        $dashboardUrl = $this->publicDashboardUrl($currentUser);

        if ($actions === []) {
            $actions = [
                [
                    'label' => 'Voir le catalogue',
                    'url' => $this->siteUrl('catalogue'),
                    'icon' => 'book-open',
                    'variant' => 'primary',
                ],
                [
                    'label' => "Retour a l'accueil",
                    'url' => $this->siteUrl(''),
                    'icon' => 'layout',
                    'variant' => 'secondary',
                ],
            ];

            if ($isLoggedIn) {
                $actions[] = [
                    'label' => 'Mon espace',
                    'url' => $dashboardUrl,
                    'icon' => 'external-link',
                    'variant' => 'secondary',
                ];
            }
        }

        return [
            'siteLogoUrl' => $this->siteLogoUrl(),
            'footerText' => str_replace('{year}', date('Y'), $this->siteFooterText()),
            'isLoggedIn' => $isLoggedIn,
            'dashboardUrl' => $dashboardUrl,
            'errorTitle' => $title,
            'errorMessage' => $message,
            'errorActions' => $actions,
        ];
    }

    protected function pdo(): \PDO
    {
        return PdoRegistry::get();
    }

    private function auth(): Auth
    {
        if ($this->auth instanceof Auth) {
            return $this->auth;
        }
        $this->auth = new Auth($this->pdo(), $this->session);
        return $this->auth;
    }
}
