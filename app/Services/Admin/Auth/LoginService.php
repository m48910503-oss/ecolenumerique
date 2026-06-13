<?php
declare(strict_types=1);

namespace App\Services\Admin\Auth;

use App\Core\Service;
use App\Models\AdminLoginModel;
use App\Security\LoginThrottle;

final class LoginService extends Service
{
    private AdminLoginModel $model;

    public function __construct(AdminLoginModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post): array
    {
        $errors = [];
        $oldEmail = '';
        $throttle = new LoginThrottle();

        if ($method === 'POST') {
            $email = strtolower(trim((string) ($post['email'] ?? '')));
            $password = (string) ($post['password'] ?? '');
            $oldEmail = $email;
            $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e-mail invalide.';
            } elseif ($throttle->isBlocked('admin', $email, $ip)) {
                $errors[] = 'Trop de tentatives de connexion. Réessayez plus tard.';
            } else {
                $user = $this->model->findUserByEmail($email);
                $roles = [];

                if (is_array($user) && !empty($user['roles_json'])) {
                    $decoded = json_decode((string) $user['roles_json'], true);
                    if (is_array($decoded)) {
                        $roles = $decoded;
                    }
                }

                $isAdmin = in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true);

                if (!$user || !password_verify($password, (string) ($user['password_hash'] ?? '')) || !$isAdmin) {
                    $throttle->recordFailure('admin', $email, $ip);
                    $errors[] = 'Identifiants invalides ou droits insuffisants.';
                } elseif ((string) ($user['status'] ?? '') !== 'active' || empty($user['email_verified_at'])) {
                    $throttle->recordFailure('admin', $email, $ip);
                    $errors[] = 'Compte non actif ou adresse e-mail non vérifiée.';
                } else {
                    $throttle->clear('admin', $email, $ip);
                    $this->loginUser($user, false);

                    return [
                        'redirectTo' => $this->siteUrl('admin/dashboard'),
                        'view' => [],
                    ];
                }
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'return' => $this->siteUrl('admin/dashboard'),
                'oldEmail' => $oldEmail,
            ],
        ];
    }
}
