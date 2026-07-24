<?php
declare(strict_types=1);

namespace App\Services\Admin\Auth;

use App\Core\Service;
use App\Models\AdminRegisterModel;

final class RegisterService extends Service
{
    private AdminRegisterModel $model;

    public function __construct(AdminRegisterModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post, string $registerToken = ''): array
    {
        $errors = [];
        $old = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
        ];
        if (!admin_register_access_allowed($registerToken)) {
            $errors[] = "L'inscription administrateur est desactivee.";
        }

        if ($method === 'POST') {
            $first = trim((string) ($post['first_name'] ?? ''));
            $last = trim((string) ($post['last_name'] ?? ''));
            $email = trim((string) ($post['email'] ?? ''));
            $password = (string) ($post['password'] ?? '');

            $old = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
            ];

            if ($first === '' || $last === '') {
                $errors[] = 'Le prénom et le nom sont obligatoires.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e-mail invalide.';
            }
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
            if ($errors === [] && $this->model->hasAnyAdmin()) {
                $errors[] = "L'inscription administrateur n'est plus disponible.";
            }
            if ($errors === [] && $this->model->emailExists($email)) {
                $errors[] = 'Un compte existe déjà avec cette adresse e-mail.';
            }

            if ($errors === []) {
                try {
                    $this->model->beginTransaction();
                    $this->model->createAdminUser(
                        $first,
                        $last,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        ['admin']
                    );
                    $this->model->commit();
                    $this->flashSet('success', 'Compte administrateur créé. Vous pouvez maintenant vous connecter.');
                } catch (\Throwable $e) {
                    if ($this->model->inTransaction()) {
                        $this->model->rollBack();
                    }
                    $errors[] = 'Une erreur est survenue lors de la création du compte.';
                }
            }

            if ($errors === []) {
                return [
                    'redirectTo' => $this->siteUrl('admin/login'),
                    'view' => [],
                ];
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'old' => $old,
            ],
        ];
    }
}
