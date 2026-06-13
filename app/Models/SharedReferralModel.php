<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class SharedReferralModel extends Model
{
    public function hasUserReferralCodeColumn(): bool
    {
        return $this->columnExists('users', 'referral_code');
    }

    public function addUserReferralCodeColumn(): bool
    {
        return $this->hasUserReferralCodeColumn();
    }

    public function ensureReferralsTable(): bool
    {
        return $this->tableExists('referrals');
    }

    public function ensureReferralCommissionsTable(): bool
    {
        return $this->tableExists('referral_commissions');
    }

    public function ensurePlatformSettingsTable(): bool
    {
        return $this->tableExists('platform_settings');
    }

    public function findUserReferralCode(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT referral_code FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $value = $stmt->fetchColumn();
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    public function updateUserReferralCodeIfMissing(int $userId, string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET referral_code = :code WHERE id = :id AND (referral_code IS NULL OR referral_code = "")'
        );
        $stmt->execute([
            ':code' => $code,
            ':id' => $userId,
        ]);
        return $stmt->rowCount() === 1;
    }

    public function findUserIdByReferralCode(string $code): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE referral_code = :code LIMIT 1');
        $stmt->execute([':code' => $code]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    public function findReferrerForUser(int $userId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT referrer_user_id FROM referrals WHERE referred_user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    public function insertReferrer(int $referredUserId, int $referrerUserId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO referrals (referrer_user_id, referred_user_id, created_at) VALUES (:ref, :rid, NOW())'
        );
        $stmt->execute([
            ':ref' => $referrerUserId,
            ':rid' => $referredUserId,
        ]);
    }

    public function hasAnyActiveEnrollment(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM enrollments WHERE user_id = :uid AND status = "active" LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function getPlatformSetting(string $key): ?string
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM platform_settings WHERE setting_key = :k ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return (string) ($row['setting_value'] ?? '');
    }

    public function insertPlatformSettingIfMissing(string $key, string $value): void
    {
        $exists = $this->pdo->prepare('SELECT COUNT(*) FROM platform_settings WHERE setting_key = :k');
        $exists->execute([':k' => $key]);
        if ((int) $exists->fetchColumn() > 0) {
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (:k, :v)');
        $stmt->execute([':k' => $key, ':v' => $value]);
    }
}
