<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CompanySsoModel extends Model
{
    private ?bool $hasAuthProviderColumn = null;

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateUserRoles(int $userId, string $rolesJson): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET roles_json = :roles,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            ':roles' => $rolesJson,
            ':id' => $userId,
        ]);
    }

    public function activateAndVerifyUser(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET status = :status,
                 email_verified_at = COALESCE(email_verified_at, CURRENT_TIMESTAMP),
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => 'active',
            ':id' => $userId,
        ]);
    }

    public function createActiveVerifiedUser(
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        string $rolesJson
    ): int {
        if ($this->hasAuthProviderColumn()) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, status, email_verified_at, auth_provider, roles_json, created_at)
                 VALUES (:first, :last, :email, :password, :status, CURRENT_TIMESTAMP, :provider, :roles, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                ':first' => $firstName,
                ':last' => $lastName,
                ':email' => $email,
                ':password' => $passwordHash,
                ':status' => 'active',
                ':provider' => 'sso',
                ':roles' => $rolesJson,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, status, email_verified_at, roles_json, created_at)
                 VALUES (:first, :last, :email, :password, :status, CURRENT_TIMESTAMP, :roles, CURRENT_TIMESTAMP)'
            );
            $stmt->execute([
                ':first' => $firstName,
                ':last' => $lastName,
                ':email' => $email,
                ':password' => $passwordHash,
                ':status' => 'active',
                ':roles' => $rolesJson,
            ]);
        }

        return (int) $this->pdo->lastInsertId();
    }

    private function hasAuthProviderColumn(): bool
    {
        if ($this->hasAuthProviderColumn !== null) {
            return $this->hasAuthProviderColumn;
        }
        $this->hasAuthProviderColumn = $this->columnExists('users', 'auth_provider');
        return $this->hasAuthProviderColumn;
    }
}
