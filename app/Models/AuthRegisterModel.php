<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AuthRegisterModel extends Model
{
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

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return (bool) $stmt->fetch();
    }

    /**
     * @param array<int,string> $roles
     */
    public function createUser(
        string $first,
        string $last,
        string $email,
        string $passwordHash,
        string $status,
        array $roles
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (
                first_name, last_name, email, password_hash, status, email_verified_at, roles_json
            ) VALUES (
                :first, :last, :email, :password, :status, NULL, :roles
            )'
        );
        $stmt->execute([
            ':first' => $first,
            ':last' => $last,
            ':email' => $email,
            ':password' => $passwordHash,
            ':status' => $status,
            ':roles' => json_encode($roles),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function createCompany(string $name, int $adminUserId, string $status): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO companies (name, admin_user_id, status, created_at, updated_at)
             VALUES (:name, :admin, :status, NOW(), NOW())'
        );
        $stmt->execute([
            ':name' => $name,
            ':admin' => $adminUserId,
            ':status' => $status,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function createCompanyMember(int $companyId, int $userId, string $role, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO company_members (company_id, user_id, role, status, created_at, updated_at)
             VALUES (:cid, :uid, :role, :status, NOW(), NOW())'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':uid' => $userId,
            ':role' => $role,
            ':status' => $status,
        ]);
    }

    public function createEmailVerification(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO email_verifications (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    /**
     * @param array<int,string> $roles
     */
    public function updateUserRoles(int $userId, array $roles): void
    {
        $roles = array_values(array_unique(array_filter($roles, static fn ($r): bool => is_string($r) && $r !== '')));
        $stmt = $this->pdo->prepare('UPDATE users SET roles_json = :roles, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':roles' => json_encode($roles),
            ':id' => $userId,
        ]);
    }
}
