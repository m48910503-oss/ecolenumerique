<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Service;
use App\Models\AuthResetModel;
use DateTime;

final class ResetService extends Service
{
    private AuthResetModel $model;

    public function __construct(AuthResetModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $get, array $post): array
    {
        $errors = [];
        $token = (string) ($get['token'] ?? ($post['token'] ?? ''));
        $tokenValid = false;
        $userId = 0;

        if ($token !== '') {
            $resetRow = $this->findValidResetToken($token);
            if ($resetRow) {
                $tokenValid = true;
                $userId = (int) ($resetRow['user_id'] ?? 0);
            } else {
                $errors[] = 'Lien de réinitialisation invalide ou expiré.';
            }
        } else {
            $errors[] = 'Aucun jeton de réinitialisation n’a été fourni.';
        }

        if ($method === 'POST' && $tokenValid) {
            $password = (string) ($post['password'] ?? '');
            $confirm = (string) ($post['confirm'] ?? '');

            if (strlen($password) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
            }
            if (!preg_match('/\d/', $password)) {
                $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
            }
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
            }
            if ($password !== $confirm) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            if ($errors === []) {
                try {
                    $this->model->beginTransaction();
                    $this->model->updateUserPasswordAndClearRemember($userId, password_hash($password, PASSWORD_DEFAULT));
                    $this->model->markResetUsedByHash(hash('sha256', $token));
                    $this->model->commit();

                    $this->clearRememberCookies();
                    $this->flashSet('success', 'Mot de passe mis à jour. Vous pouvez maintenant vous connecter.');

                    return [
                        'redirectTo' => $this->siteUrl('login'),
                        'view' => [],
                    ];
                } catch (\Throwable $e) {
                    if ($this->model->inTransaction()) {
                        $this->model->rollBack();
                    }
                    $errors[] = 'Une erreur est survenue lors de la réinitialisation.';
                }
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'successMessage' => '',
                'token' => $token,
                'tokenValid' => $tokenValid,
            ],
        ];
    }

    private function findValidResetToken(string $token): ?array
    {
        $row = $this->model->findResetWithUserByHash(hash('sha256', $token));
        if (!$row) {
            return null;
        }
        if (!empty($row['used_at'])) {
            return null;
        }
        if (new DateTime((string) $row['expires_at']) < new DateTime()) {
            return null;
        }
        if ((string) ($row['status'] ?? '') !== 'active' || empty($row['email_verified_at'])) {
            return null;
        }
        return $row;
    }
}