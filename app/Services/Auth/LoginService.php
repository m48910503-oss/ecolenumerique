<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Service;
use App\Models\AuthLoginModel;
use App\Security\LoginThrottle;

final class LoginService extends Service
{
    private AuthLoginModel $model;

    public function __construct(AuthLoginModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $query
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post, array $query = []): array
    {
        $errors = [];
        $oldEmail = '';
        $returnInput = (string) ($query['return'] ?? ($post['return'] ?? ''));
        $return = $this->sanitizeReturnPath($returnInput, $this->siteUrl(''));
        $throttle = new LoginThrottle();

        if ($method === 'POST') {
            $email = trim((string) ($post['email'] ?? ''));
            $password = (string) ($post['password'] ?? '');
            $remember = !empty($post['remember']);
            $oldEmail = $email;
            $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e-mail invalide.';
            }

            if ($errors === [] && $throttle->isBlocked('web', $email, $ip)) {
                $errors[] = 'Trop de tentatives de connexion. Réessayez plus tard.';
            }

            if ($errors === []) {
                $user = $this->model->findUserByEmail($email);

                if (!$user || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
                    $throttle->recordFailure('web', $email, $ip);
                    $errors[] = 'Identifiants incorrects.';
                } elseif ((string) ($user['status'] ?? '') !== 'active' || empty($user['email_verified_at'])) {
                    $throttle->recordFailure('web', $email, $ip);
                    $errors[] = 'Adresse e-mail non verifiee ou compte inactif.';
                } else {
                    $throttle->clear('web', $email, $ip);
                    $this->loginUser($user, $remember);
                    return [
                        'redirectTo' => $return,
                        'view' => [],
                    ];
                }
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'oldEmail' => $oldEmail,
                'return' => $return,
            ],
        ];
    }
}
