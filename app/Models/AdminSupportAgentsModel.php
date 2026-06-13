<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminSupportAgentsModel extends Model
{
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

    public function createSupportUser(string $firstName, string $lastName, string $email, string $passwordHash, string $rolesJson): void
    {
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
    }

    public function updateRolesByUserId(int $userId, string $rolesJson): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET roles_json = :roles WHERE id = :id');
        $stmt->execute([
            ':roles' => $rolesJson,
            ':id' => $userId,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchSupportAgents(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, first_name, last_name, email, status, created_at
             FROM users
             WHERE roles_json LIKE \'%"support"%\'
             ORDER BY created_at DESC'
        );
        return $stmt->fetchAll() ?: [];
    }
}

