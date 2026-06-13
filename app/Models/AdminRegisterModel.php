<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminRegisterModel extends Model
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

    public function hasAnyAdmin(): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM users WHERE status = :status AND roles_json LIKE :role LIMIT 1'
        );
        $stmt->execute([
            ':status' => 'active',
            ':role' => '%"admin"%',
        ]);
        return (bool) $stmt->fetch();
    }

    /**
     * @param array<int,string> $roles
     */
    public function createAdminUser(
        string $first,
        string $last,
        string $email,
        string $passwordHash,
        array $roles
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (
                first_name, last_name, email, password_hash, status, email_verified_at, roles_json, created_at
             ) VALUES (
                :first, :last, :email, :password, :status, NOW(), :roles, NOW()
             )'
        );
        $stmt->execute([
            ':first' => $first,
            ':last' => $last,
            ':email' => $email,
            ':password' => $passwordHash,
            ':status' => 'active',
            ':roles' => json_encode($roles),
        ]);
    }
}
