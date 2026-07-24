<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    private PDO $pdo;
    private Session $session;
    private ?array $cachedUser = null;
    private bool $resolved = false;

    public function __construct(PDO $pdo, Session $session)
    {
        $this->pdo = $pdo;
        $this->session = $session;
    }

    public function login(array $user, bool $remember = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $this->session->set('user_id', (int) ($user['id'] ?? 0));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true);
        $this->session->set('user_roles', is_array($roles) ? $roles : []);

        if ($remember) {
            $selector = bin2hex(random_bytes(12));
            $validator = bin2hex(random_bytes(32));
            $validatorHash = hash('sha256', $validator);

            $stmt = $this->pdo->prepare('UPDATE users SET remember_selector = :selector, remember_validator_hash = :validator WHERE id = :id');
            $stmt->execute([
                ':selector' => $selector,
                ':validator' => $validatorHash,
                ':id' => (int) ($user['id'] ?? 0),
            ]);

            $params = [
                'expires' => time() + (60 * 60 * 24 * 30),
                'path' => '/',
                'domain' => '',
                'secure' => $this->isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ];
            setcookie('remember_selector', $selector, $params);
            setcookie('remember_validator', $validator, $params);
        }

        $this->cachedUser = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        $userId = (int) $this->session->get('user_id', 0);
        if ($userId > 0) {
            $stmt = $this->pdo->prepare('UPDATE users SET remember_selector = NULL, remember_validator_hash = NULL WHERE id = :id');
            $stmt->execute([':id' => $userId]);
        }

        $this->clearRememberCookies();
        $this->session->clear();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $this->cachedUser = null;
        $this->resolved = true;
    }

    public function currentUser(): ?array
    {
        if ($this->resolved) {
            return $this->cachedUser;
        }
        $this->resolved = true;

        $userId = (int) $this->session->get('user_id', 0);
        if ($userId > 0) {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($user)) {
                $this->cachedUser = $user;
                return $this->cachedUser;
            }
        }

        $selector = (string) ($_COOKIE['remember_selector'] ?? '');
        $validator = (string) ($_COOKIE['remember_validator'] ?? '');
        if ($selector !== '' && $validator !== '') {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE remember_selector = :selector LIMIT 1');
            $stmt->execute([':selector' => $selector]);
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
            if (
                is_array($candidate)
                && hash_equals((string) ($candidate['remember_validator_hash'] ?? ''), hash('sha256', $validator))
                && (string) ($candidate['status'] ?? '') === 'active'
                && !empty($candidate['email_verified_at'])
            ) {
                $this->login($candidate, true);
                $this->cachedUser = $candidate;
                $this->resolved = true;
                return $this->cachedUser;
            }
            $this->clearRememberCookies();
        }

        $this->cachedUser = null;
        return null;
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }

    public function requireAuthenticated(bool $mustBeVerified = true, ?string $returnPath = null): array
    {
        $user = $this->currentUser();
        if (!is_array($user)) {
            $this->redirectToLogin($returnPath ?? $this->siteUrl(''));
        }

        if (
            $mustBeVerified
            && (((string) ($user['status'] ?? '')) !== 'active' || empty($user['email_verified_at']))
        ) {
            $this->logout();
            $this->redirectToLogin($this->siteUrl('login'));
        }

        return $user;
    }

    public function redirectToLogin(string $returnPath): never
    {
        header('Location: ' . $this->siteUrl('login') . '?return=' . urlencode($returnPath));
        exit;
    }

    public function clearRememberCookies(): void
    {
        $params = [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        setcookie('remember_selector', '', $params);
        setcookie('remember_validator', '', $params);
    }

    private function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off';
    }

    private function siteUrl(string $path = ''): string
    {
        $basePath = defined('BASE_PATH') ? (string) BASE_PATH : '/';
        $base = rtrim($basePath, '/');
        return $base . '/' . ltrim($path, '/');
    }
}
