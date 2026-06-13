<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AuthVerifyModel extends Model
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

    public function findVerificationWithUserByHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ev.*, u.status, u.email_verified_at
             FROM email_verifications ev
             JOIN users u ON u.id = ev.user_id
             WHERE ev.token_hash = :hash
             LIMIT 1'
        );
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function activateUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET status = :status, email_verified_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => 'active',
            ':id' => $userId,
        ]);
    }

    public function markVerificationUsed(int $verificationId): void
    {
        $stmt = $this->pdo->prepare('UPDATE email_verifications SET used_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $verificationId]);
    }
}

