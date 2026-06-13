<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminSecondaryAdminsModel;
use App\Services\Student\Pagination;

final class SecondaryAdminsService extends Service
{
    private const PER_PAGE = 6;

    private AdminSecondaryAdminsModel $model;

    public function __construct(AdminSecondaryAdminsModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    /**
     * @param array<string,mixed> $query
     */
    public function handle(string $method, array $post, array $currentUser, array $query = []): array
    {
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            if ($action === 'create') {
                $errors = $this->handleCreate($post, $currentUser, $successMessage);
            } elseif ($action === 'grant') {
                $errors = $this->handleGrant($post, $currentUser, $successMessage);
            } elseif ($action === 'update_permissions') {
                $errors = $this->handleUpdatePermissions($post, $currentUser, $successMessage);
            } elseif ($action === 'toggle_permissions_active') {
                $errors = $this->handleTogglePermissionsActive($post, $successMessage);
            } elseif ($action === 'revoke') {
                $errors = $this->handleRevoke($post, $successMessage);
            } else {
                $errors[] = 'Action invalide.';
            }
        }

        $page = Pagination::pageFromQuery($query, 'page');
        if ($method === 'POST') {
            $page = Pagination::pageFromQuery($post, 'page', $page);
        }

        if ($method === 'POST' && $errors === [] && $successMessage !== '') {
            $this->flashSet('success', $successMessage);
            $redirectPath = 'admin/secondary_admins' . ($page > 1 ? ('?page=' . $page) : '');
            return [
                'redirectTo' => $this->siteUrl($redirectPath),
                'view' => [],
            ];
        }

        $permissionsCatalog = AdminAccessService::permissionsCatalog();
        $total = $this->model->countSecondaryAdmins();
        $pagination = Pagination::build($total, $page, self::PER_PAGE);

        $rows = $this->model->fetchSecondaryAdminsPage(
            (int) ($pagination['offset'] ?? 0),
            (int) ($pagination['per_page'] ?? self::PER_PAGE)
        );
        $secondaryAdmins = [];
        foreach ($rows as $row) {
            $permissions = AdminAccessService::normalizePermissions(
                json_decode((string) ($row['permissions_json'] ?? '[]'), true)
            );
            $permissionLabels = [];
            foreach ($permissions as $permission) {
                if (isset($permissionsCatalog[$permission])) {
                    $permissionLabels[] = (string) $permissionsCatalog[$permission];
                }
            }
            $creatorName = trim((string) ($row['creator_first'] ?? '') . ' ' . (string) ($row['creator_last'] ?? ''));

            $secondaryAdmins[] = [
                'id' => (int) ($row['id'] ?? 0),
                'first_name' => (string) ($row['first_name'] ?? ''),
                'last_name' => (string) ($row['last_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'is_active' => (int) ($row['is_active'] ?? 0) === 1,
                'permissions' => $permissions,
                'permission_labels' => $permissionLabels,
                'created_by_name' => $creatorName,
            ];
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'successMessage' => $successMessage,
                'permissionsCatalog' => $permissionsCatalog,
                'secondaryAdmins' => $secondaryAdmins,
                'pagination' => $pagination,
                'adminDashboardUrl' => $this->siteUrl('admin/dashboard'),
                'adminSupportAgentsUrl' => $this->siteUrl('admin/support_agents'),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleCreate(array $post, array $currentUser, string &$successMessage): array
    {
        $errors = [];
        $first = trim((string) ($post['first_name'] ?? ''));
        $last = trim((string) ($post['last_name'] ?? ''));
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $password = (string) ($post['password'] ?? '');
        $permissions = AdminAccessService::normalizePermissions($post['permissions'] ?? []);

        if ($first === '' || $last === '') {
            $errors[] = 'Prénom et nom sont obligatoires.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        $this->validateStrongPassword($password, $errors);
        if ($permissions === []) {
            $errors[] = 'Sélectionnez au moins une fonctionnalité.';
        }

        if ($errors === [] && $this->model->findUserByEmail($email)) {
            $errors[] = 'Un compte existe déjà avec cet e-mail.';
        }
        if ($errors !== []) {
            return $errors;
        }

        try {
            $this->model->beginTransaction();

            $userId = $this->model->createSecondaryAdminUser(
                $first,
                $last,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $this->encodeRoles([AdminAccessService::ROLE_SECONDARY])
            );
            $this->model->upsertPermissions(
                $userId,
                (int) ($currentUser['id'] ?? 0),
                $this->encodePermissions($permissions),
                true
            );

            $this->model->commit();
            $successMessage = 'Administrateur secondaire créé avec succès.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = $this->friendlyError($e);
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleGrant(array $post, array $currentUser, string &$successMessage): array
    {
        $errors = [];
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $permissions = AdminAccessService::normalizePermissions($post['permissions'] ?? []);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        if ($permissions === []) {
            $errors[] = 'Sélectionnez au moins une fonctionnalité.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $user = $this->model->findUserByEmail($email);
        if (!$user) {
            return ['Utilisateur introuvable.'];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (in_array(AdminAccessService::ROLE_PRIMARY, $roles, true)) {
            return ['Ce compte est déjà administrateur général.'];
        }

        if (!in_array(AdminAccessService::ROLE_SECONDARY, $roles, true)) {
            $roles[] = AdminAccessService::ROLE_SECONDARY;
        }

        try {
            $this->model->beginTransaction();
            $this->model->updateRolesByUserId(
                (int) ($user['id'] ?? 0),
                $this->encodeRoles($roles)
            );
            $this->model->upsertPermissions(
                (int) ($user['id'] ?? 0),
                (int) ($currentUser['id'] ?? 0),
                $this->encodePermissions($permissions),
                true
            );
            $this->model->commit();
            $successMessage = 'Rôle d’administrateur secondaire ajouté ou mis à jour.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = $this->friendlyError($e);
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleUpdatePermissions(array $post, array $currentUser, string &$successMessage): array
    {
        $errors = [];
        $userId = (int) ($post['user_id'] ?? 0);
        $permissions = AdminAccessService::normalizePermissions($post['permissions'] ?? []);

        if ($userId < 1) {
            $errors[] = 'Utilisateur invalide.';
        }
        if ($permissions === []) {
            $errors[] = 'Sélectionnez au moins une fonctionnalité.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $user = $this->model->findUserById($userId);
        if (!$user) {
            return ['Utilisateur introuvable.'];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (!in_array(AdminAccessService::ROLE_SECONDARY, $roles, true)) {
            return ['Ce compte n\'est pas un administrateur secondaire.'];
        }

        try {
            $existing = $this->model->fetchPermissionsByUserId($userId);
            $isActive = (int) ($existing['is_active'] ?? 1) === 1;
            $this->model->upsertPermissions(
                $userId,
                (int) ($currentUser['id'] ?? 0),
                $this->encodePermissions($permissions),
                $isActive
            );
            $successMessage = 'Permissions mises à jour.';
        } catch (\Throwable $e) {
            $errors[] = $this->friendlyError($e);
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $post
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleTogglePermissionsActive(array $post, string &$successMessage): array
    {
        $userId = (int) ($post['user_id'] ?? 0);
        $setActive = (int) ($post['set_active'] ?? 0) === 1;
        if ($userId < 1) {
            return ['Utilisateur invalide.'];
        }

        $user = $this->model->findUserById($userId);
        if (!$user) {
            return ['Utilisateur introuvable.'];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (!in_array(AdminAccessService::ROLE_SECONDARY, $roles, true)) {
            return ['Ce compte n\'est pas un administrateur secondaire.'];
        }

        $permRow = $this->model->fetchPermissionsByUserId($userId);
        if (!$permRow) {
            return ['Permissions introuvables pour ce compte.'];
        }

        try {
            $this->model->setPermissionsActive($userId, $setActive);
            $successMessage = $setActive
                ? 'Permissions réactivées.'
                : 'Permissions désactivées (compte conservé).';
        } catch (\Throwable $e) {
            return [$this->friendlyError($e)];
        }

        return [];
    }

    /**
     * @param array<string,mixed> $post
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleRevoke(array $post, string &$successMessage): array
    {
        $errors = [];
        $userId = (int) ($post['user_id'] ?? 0);
        if ($userId < 1) {
            return ['Utilisateur invalide.'];
        }

        $user = $this->model->findUserById($userId);
        if (!$user) {
            return ['Utilisateur introuvable.'];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (in_array(AdminAccessService::ROLE_PRIMARY, $roles, true)) {
            return ['Impossible de retirer les droits d\'un administrateur général ici.'];
        }

        $roles = array_values(array_filter(
            $roles,
            static fn(string $role): bool => $role !== AdminAccessService::ROLE_SECONDARY
        ));

        try {
            $this->model->beginTransaction();
            $this->model->updateRolesByUserId($userId, $this->encodeRoles($roles));
            $this->model->removePermissions($userId);
            $this->model->commit();
            $successMessage = 'Administrateur secondaire retiré.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $errors[] = $this->friendlyError($e);
        }

        return $errors;
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
        $encoded = json_encode(array_values(array_unique($roles)));
        return is_string($encoded) ? $encoded : '[]';
    }

    /**
     * @param array<int,string> $permissions
     */
    private function encodePermissions(array $permissions): string
    {
        $encoded = json_encode(array_values($permissions));
        return is_string($encoded) ? $encoded : '[]';
    }

    private function friendlyError(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());
        if (str_contains($message, 'admin_secondary_permissions')) {
            return 'Migration manquante: appliquez database/schema_v33.sql puis réessayez.';
        }
        return 'Opération impossible pour le moment.';
    }

    /**
     * @param array<int,string> $errors
     */
    private function validateStrongPassword(string $password, array &$errors): void
    {
        if (strlen($password) < 8) {
            $errors[] = 'Mot de passe : 8 caractères minimum.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Mot de passe : au moins une lettre majuscule.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Mot de passe : au moins un chiffre.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Mot de passe : au moins un caractère spécial.';
        }
    }
}
