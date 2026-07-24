<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminSupportAgentsModel;
use App\Models\AdminDashboardModel;

final class SupportAgentsService extends Service
{
    private AdminSupportAgentsModel $model;
    private AdminDashboardModel $auditModel;

    public function __construct(AdminSupportAgentsModel $model, AdminDashboardModel $auditModel)
    {
        $this->model = $model;
        $this->auditModel = $auditModel;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post, array $currentUser): array
    {
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');
        $currentPage = max(1, (int) ($post['page'] ?? 1));
        $searchQuery = trim((string) ($post['q'] ?? ''));

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');

            if ($action === 'create') {
                $first = trim((string) ($post['first_name'] ?? ''));
                $last = trim((string) ($post['last_name'] ?? ''));
                $email = strtolower(trim((string) ($post['email'] ?? '')));
                $password = (string) ($post['password'] ?? '');

                if ($first === '' || $last === '') {
                    $errors[] = 'Le prénom et le nom sont obligatoires.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Adresse email invalide.';
                }
                if (strlen($password) < 8) {
                    $errors[] = 'Mot de passe trop court (8 caractères minimum).';
                }
                if ($errors === [] && $this->model->findUserByEmail($email)) {
                    $errors[] = 'Un compte existe déjà avec cette adresse email.';
                }

                if ($errors === []) {
                    try {
                        $userId = $this->model->createSupportUser(
                            $first,
                            $last,
                            $email,
                            password_hash($password, PASSWORD_DEFAULT),
                            $this->encodeRoles(['support'])
                        );
                        $this->recordAudit((int) ($currentUser['id'] ?? 0), 'support_agent_create', $userId, [
                            'email' => $email,
                            'first_name' => $first,
                            'last_name' => $last,
                        ]);
                        $successMessage = 'Compte support créé.';
                    } catch (\Throwable $e) {
                        $errors[] = 'Erreur lors de la création du compte support.';
                    }
                }
            } elseif ($action === 'grant') {
                $email = strtolower(trim((string) ($post['email'] ?? '')));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Adresse email invalide.';
                } else {
                    $user = $this->model->findUserByEmail($email);
                    if (!$user) {
                        $errors[] = 'Utilisateur introuvable.';
                    } else {
                        $rolesList = $this->decodeRoles($user['roles_json'] ?? '[]');
                        if (!in_array('support', $rolesList, true)) {
                            $rolesList[] = 'support';
                        }
                        $this->model->updateRolesByUserId((int) ($user['id'] ?? 0), $this->encodeRoles($rolesList));
                        $this->recordAudit((int) ($currentUser['id'] ?? 0), 'support_agent_grant', (int) ($user['id'] ?? 0), [
                            'email' => $email,
                            'roles' => implode(',', $rolesList),
                        ]);
                        $successMessage = 'Rôle support ajouté.';
                    }
                }
            } elseif ($action === 'revoke') {
                $userId = (int) ($post['user_id'] ?? 0);
                if ($userId < 1) {
                    $errors[] = 'Utilisateur invalide.';
                } else {
                    $user = $this->model->findUserById($userId);
                    if (!$user) {
                        $errors[] = 'Utilisateur introuvable.';
                    } else {
                        $rolesList = $this->decodeRoles($user['roles_json'] ?? '[]');
                        $rolesList = array_values(array_filter(
                            $rolesList,
                            static fn ($role): bool => $role !== 'support'
                        ));
                        $this->model->updateRolesByUserId($userId, $this->encodeRoles($rolesList));
                        $this->recordAudit((int) ($currentUser['id'] ?? 0), 'support_agent_revoke', $userId, [
                            'email' => (string) ($user['email'] ?? ''),
                            'roles' => implode(',', $rolesList),
                        ]);
                        $successMessage = 'Rôle support retiré.';
                    }
                }
            } else {
                $errors[] = 'Action invalide.';
            }
        }

        if ($method === 'POST' && $errors === [] && $successMessage !== '') {
            $this->flashSet('success', $successMessage);
            $query = [];
            if ($searchQuery !== '') {
                $query['q'] = $searchQuery;
            }
            if ($currentPage > 1) {
                $query['page'] = $currentPage;
            }
            return [
                'redirectTo' => $this->siteUrl('admin/support_agents' . ($query !== [] ? ('?' . http_build_query($query)) : '')),
                'view' => [],
            ];
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'successMessage' => $successMessage,
                'agents' => $this->model->fetchSupportAgents(),
                'loginUrl' => $this->siteUrl('login'),
                'supportConsoleUrl' => $this->siteUrl('admin/support'),
            ],
        ];
    }

    /**
     * @return array<int,string>
     */
    private function decodeRoles(mixed $rolesJson): array
    {
        if (!is_string($rolesJson) || trim($rolesJson) === '') {
            return [];
        }
        $decoded = json_decode($rolesJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $roles = [];
        foreach ($decoded as $role) {
            if (is_string($role) && $role !== '') {
                $roles[] = $role;
            }
        }
        return array_values(array_unique($roles));
    }

    /**
     * @param array<int,string> $roles
     */
    private function encodeRoles(array $roles): string
    {
        $encoded = json_encode(array_values($roles));
        return is_string($encoded) ? $encoded : '[]';
    }

    /**
     * @param array<string,mixed> $meta
     */
    private function recordAudit(int $actorId, string $action, int $targetId, array $meta = []): void
    {
        $this->auditModel->insertAuditLog($actorId > 0 ? $actorId : null, $action, 'support_agent', $targetId, $meta);
    }
}
