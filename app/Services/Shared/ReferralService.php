<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;

use App\Models\SharedReferralModel;
use Throwable;

final class ReferralService extends Service
{
    private SharedReferralModel $model;

    public function __construct(SharedReferralModel $model)
    {
        $this->model = $model;
    }

    public function normalizeCode(string $raw): string
    {
        $raw = strtoupper(trim($raw));
        $raw = preg_replace('/[^A-Z0-9]/', '', $raw);
        if (!is_string($raw)) {
            return '';
        }
        return substr($raw, 0, 32);
    }

    public function generateCode(): string
    {
        return 'REF' . strtoupper(bin2hex(random_bytes(6)));
    }

    public function ensureUsersReferralCodeColumn(): bool
    {
        try {
            return $this->model->hasUserReferralCodeColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureReferralsTable(): bool
    {
        try {
            return $this->model->ensureReferralsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureReferralCommissionsTable(): bool
    {
        try {
            return $this->model->ensureReferralCommissionsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensurePlatformSettingsTable(): bool
    {
        try {
            return $this->model->ensurePlatformSettingsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureUserReferralCode(int $userId): ?string
    {
        if ($userId < 1 || !$this->ensureUsersReferralCodeColumn()) {
            return null;
        }
        try {
            $existing = $this->model->findUserReferralCode($userId);
            if ($existing !== null) {
                return $existing;
            }
        } catch (Throwable $e) {
            return null;
        }

        for ($i = 0; $i < 6; $i++) {
            $code = $this->generateCode();
            try {
                if ($this->model->updateUserReferralCodeIfMissing($userId, $code)) {
                    return $code;
                }
            } catch (Throwable $e) {
                // Retry on duplicate collision.
            }
        }
        return null;
    }

    public function getUserIdByReferralCode(string $code): ?int
    {
        $code = $this->normalizeCode($code);
        if ($code === '' || !$this->ensureUsersReferralCodeColumn()) {
            return null;
        }
        try {
            return $this->model->findUserIdByReferralCode($code);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function setUserReferrer(int $referredUserId, int $referrerUserId): bool
    {
        if ($referredUserId < 1 || $referrerUserId < 1 || $referredUserId === $referrerUserId) {
            return false;
        }
        if (!$this->ensureReferralsTable()) {
            return false;
        }
        try {
            $existing = $this->model->findReferrerForUser($referredUserId);
            if ($existing !== null) {
                return false;
            }
            $this->model->insertReferrer($referredUserId, $referrerUserId);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getReferrerChain(int $userId): array
    {
        $chain = ['l1' => 0, 'l2' => 0];
        if ($userId < 1 || !$this->ensureReferralsTable()) {
            return $chain;
        }

        try {
            $l1 = (int) ($this->model->findReferrerForUser($userId) ?? 0);
            if ($l1 < 1) {
                return $chain;
            }
            $chain['l1'] = $l1;

            $l2 = (int) ($this->model->findReferrerForUser($l1) ?? 0);
            if ($l2 > 0 && $l2 !== $l1 && $l2 !== $userId) {
                $chain['l2'] = $l2;
            }
            return $chain;
        } catch (Throwable $e) {
            return $chain;
        }
    }

    public function userHasActiveEnrollment(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }
        try {
            return $this->model->hasAnyActiveEnrollment($userId);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getPlatformReferralSettings(): array
    {
        $defaults = ['l1_rate' => 5.0, 'l2_rate' => 2.0];
        if (!$this->ensurePlatformSettingsTable()) {
            return $defaults;
        }

        $l1 = $this->platformSettingGetOrInit('referral_l1_rate', '5');
        $l2 = $this->platformSettingGetOrInit('referral_l2_rate', '2');
        $l1Rate = max(0.0, (float) $l1);
        $l2Rate = max(0.0, (float) $l2);

        return [
            'l1_rate' => $l1Rate,
            'l2_rate' => $l2Rate,
        ];
    }

    private function platformSettingGetOrInit(string $key, string $default): string
    {
        try {
            $value = $this->model->getPlatformSetting($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
            $this->model->insertPlatformSettingIfMissing($key, $default);
            return $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

