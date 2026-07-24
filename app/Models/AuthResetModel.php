<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AuthResetModel extends Model
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

    public function findResetWithUserByHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pr.*, u.status, u.email_verified_at
             FROM password_resets pr
             JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = :hash
             LIMIT 1'
        );
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateUserPasswordAndClearRemember(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET password_hash = :pwd, remember_selector = NULL, remember_validator_hash = NULL
             WHERE id = :id'
        );
        $stmt->execute([
            ':pwd' => $passwordHash,
            ':id' => $userId,
        ]);
    }

    public function markResetUsedByHash(string $tokenHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE token_hash = :hash');
        $stmt->execute([':hash' => $tokenHash]);
    }
}

