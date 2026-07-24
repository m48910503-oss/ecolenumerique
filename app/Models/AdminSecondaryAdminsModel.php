<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminSecondaryAdminsModel extends Model
{
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, roles_json, status, created_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findUserById(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, roles_json, status, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createSecondaryAdminUser(
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        string $rolesJson
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password_hash, status, email_verified_at, roles_json, created_at)
             VALUES (:first, :last, :email, :pass, :status, NOW(), :roles, NOW())'
        );
        $stmt->execute([
            ':first' => $firstName,
            ':last' => $lastName,
            ':email' => $email,
            ':pass' => $passwordHash,
            ':status' => 'active',
            ':roles' => $rolesJson,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateRolesByUserId(int $userId, string $rolesJson): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET roles_json = :roles WHERE id = :id');
        $stmt->execute([
            ':roles' => $rolesJson,
            ':id' => $userId,
        ]);
    }

    public function upsertPermissions(int $userId, int $createdBy, string $permissionsJson, bool $isActive = true): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_secondary_permissions (user_id, created_by, permissions_json, is_active, created_at, updated_at)
             VALUES (:uid, :creator, :permissions, :active, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 permissions_json = VALUES(permissions_json),
                 is_active = VALUES(is_active),
                 updated_at = NOW()'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':creator' => $createdBy > 0 ? $createdBy : null,
            ':permissions' => $permissionsJson,
            ':active' => $isActive ? 1 : 0,
        ]);
    }

    public function removePermissions(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin_secondary_permissions WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
    }

    public function fetchPermissionsByUserId(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT user_id, permissions_json, is_active, created_at, updated_at
             FROM admin_secondary_permissions
             WHERE user_id = :uid
             LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setPermissionsActive(int $userId, bool $isActive): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_secondary_permissions
             SET is_active = :active, updated_at = NOW()
             WHERE user_id = :uid'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':active' => $isActive ? 1 : 0,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchSecondaryAdmins(): array
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.status,
                    u.created_at,
                    p.permissions_json,
                    p.is_active,
                    p.created_by,
                    creator.first_name AS creator_first,
                    creator.last_name AS creator_last
                 FROM users u
                 LEFT JOIN admin_secondary_permissions p ON p.user_id = u.id
                 LEFT JOIN users creator ON creator.id = p.created_by
                 WHERE u.roles_json LIKE \'%"admin_secondary"%\'
                 ORDER BY u.created_at DESC'
            );
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function countSecondaryAdmins(): int
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT COUNT(*) AS total
                 FROM users u
                 WHERE u.roles_json LIKE \'%"admin_secondary"%\''
            );
            $count = $stmt->fetchColumn();
            return is_numeric($count) ? (int) $count : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchSecondaryAdminsPage(int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);

        try {
            $stmt = $this->pdo->prepare(
                'SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.status,
                    u.created_at,
                    p.permissions_json,
                    p.is_active,
                    p.created_by,
                    creator.first_name AS creator_first,
                    creator.last_name AS creator_last
                 FROM users u
                 LEFT JOIN admin_secondary_permissions p ON p.user_id = u.id
                 LEFT JOIN users creator ON creator.id = p.created_by
                 WHERE u.roles_json LIKE \'%"admin_secondary"%\'
                 ORDER BY u.created_at DESC
                 LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
