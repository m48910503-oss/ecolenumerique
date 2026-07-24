<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AuthForgotModel extends Model
{
    public function findActiveVerifiedUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, status, email_verified_at FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function invalidateOpenResetsForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL');
        $stmt->execute([':uid' => $userId]);
    }

    public function createPasswordReset(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:uid, :hash, :expires)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':hash' => $tokenHash,
            ':expires' => $expiresAt,
        ]);
    }
}

