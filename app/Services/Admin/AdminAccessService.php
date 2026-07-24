<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;

final class AdminAccessService extends Service
{
    public const ROLE_PRIMARY = 'admin';
    public const ROLE_SECONDARY = 'admin_secondary';

    public const PERMISSION_DASHBOARD = 'dashboard';
    public const PERMISSION_SUPPORT = 'support';
    public const PERMISSION_SUPPORT_AGENTS = 'support_agents';
    public const PERMISSION_COURSES = 'courses';
    public const PERMISSION_TEACHER_APPLICATIONS = 'teacher_applications';
    public const PERMISSION_SALES = 'sales';
    public const PERMISSION_CATEGORIES = 'categories';
    public const PERMISSION_ANNOUNCEMENTS = 'announcements';
    public const PERMISSION_SITE_SETTINGS = 'site_settings';
    public const PERMISSION_ERROR_LOGS = 'error_logs';

    /** @var array<int,array<int,string>> */
    private array $permissionsCache = [];

    /**
     * @return array<string,string>
     */
    public static function permissionsCatalog(): array
    {
        return [
            self::PERMISSION_DASHBOARD => 'Tableau de bord',
            self::PERMISSION_SUPPORT => 'Support',
            self::PERMISSION_SUPPORT_AGENTS => 'Agents de support',
            self::PERMISSION_COURSES => 'Cours à valider',
            self::PERMISSION_TEACHER_APPLICATIONS => 'Gestion des enseignants',
            self::PERMISSION_SALES => 'Ventes',
            self::PERMISSION_CATEGORIES => 'Catégories',
            self::PERMISSION_ANNOUNCEMENTS => 'Annonces',
            self::PERMISSION_SITE_SETTINGS => 'Paramètres du site',
            self::PERMISSION_ERROR_LOGS => 'Journal des erreurs',
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function permissionKeys(): array
    {
        return array_keys(self::permissionsCatalog());
    }

    /**
     * @return array<int,string>
     */
    public static function normalizePermissions(mixed $rawPermissions): array
    {
        if (!is_array($rawPermissions)) {
            return [];
        }

        $allowed = array_fill_keys(self::permissionKeys(), true);
        $normalized = [];
        foreach ($rawPermissions as $permission) {
            if (!is_string($permission)) {
                continue;
            }
            $permission = trim($permission);
            if ($permission === '' || !isset($allowed[$permission])) {
                continue;
            }
            $normalized[] = $permission;
        }

        return array_values(array_unique($normalized));
    }

    public function isGeneralAdmin(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }
        return in_array(self::ROLE_PRIMARY, $this->rolesFromUser($user), true);
    }

    public function isSecondaryAdmin(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }
        return in_array(self::ROLE_SECONDARY, $this->rolesFromUser($user), true);
    }

    public function hasAdminAccess(?array $user): bool
    {
        return $this->isGeneralAdmin($user) || $this->isSecondaryAdmin($user);
    }

    public function canAccess(?array $user, string $permission): bool
    {
        if (!is_array($user)) {
            return false;
        }

        $knownPermissions = array_fill_keys(self::permissionKeys(), true);
        if (!isset($knownPermissions[$permission])) {
            return false;
        }

        if ($this->isGeneralAdmin($user)) {
            return true;
        }
        if (!$this->isSecondaryAdmin($user)) {
            return false;
        }

        return in_array($permission, $this->allowedPermissions($user), true);
    }

    /**
     * @param array<string,mixed>|null $user
     * @return array<int,string>
     */
    public function allowedPermissions(?array $user): array
    {
        if (!is_array($user)) {
            return [];
        }

        if ($this->isGeneralAdmin($user)) {
            return self::permissionKeys();
        }

        if (!$this->isSecondaryAdmin($user)) {
            return [];
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            return [];
        }

        if (isset($this->permissionsCache[$userId])) {
            return $this->permissionsCache[$userId];
        }

        try {
            $stmt = $this->pdo()->prepare(
                'SELECT permissions_json, is_active
                 FROM admin_secondary_permissions
                 WHERE user_id = :uid
                 LIMIT 1'
            );
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {
            $this->permissionsCache[$userId] = [];
            return [];
        }

        if (!is_array($row) || (int) ($row['is_active'] ?? 0) !== 1) {
            $this->permissionsCache[$userId] = [];
            return [];
        }

        $decoded = json_decode((string) ($row['permissions_json'] ?? '[]'), true);
        $permissions = self::normalizePermissions($decoded);
        $this->permissionsCache[$userId] = $permissions;

        return $permissions;
    }

    /**
     * @param array<string,mixed> $user
     * @return array<int,string>
     */
    public function rolesFromUser(array $user): array
    {
        $decoded = json_decode((string) ($user['roles_json'] ?? '[]'), true);
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
}
