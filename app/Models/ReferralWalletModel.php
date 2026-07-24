<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ReferralWalletModel extends Model
{
    /**
     * @param array<int,string> $statuses
     */
    public function sumByUserAndStatuses(int $userId, array $statuses): int
    {
        if ($userId < 1 || $statuses === []) {
            return 0;
        }
        if (!$this->tableExists('referral_redemptions')) {
            return 0;
        }

        $clean = [];
        foreach ($statuses as $status) {
            $status = $this->sanitizeStatus((string) $status);
            if ($status !== '' && !in_array($status, $clean, true)) {
                $clean[] = $status;
            }
        }
        if ($clean === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(amount_xof), 0)
             FROM referral_redemptions
             WHERE user_id = ?
               AND status IN (' . $placeholders . ')'
        );
        $stmt->execute(array_merge([(int) $userId], $clean));
        return (int) $stmt->fetchColumn();
    }

    public function insertRedemption(int $userId, int $orderId, int $amountXof, string $status = 'reserved'): bool
    {
        if ($userId < 1 || $orderId < 1 || $amountXof <= 0) {
            return false;
        }
        if (!$this->tableExists('referral_redemptions')) {
            return false;
        }
        $status = $this->sanitizeStatus($status);
        if ($status === '') {
            $status = 'reserved';
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO referral_redemptions (user_id, order_id, amount_xof, status, created_at, updated_at)
             VALUES (:uid, :oid, :amt, :st, NOW(), NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':oid' => $orderId,
            ':amt' => $amountXof,
            ':st' => $status,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateRedemptionStatusByOrder(int $orderId, string $status): void
    {
        if ($orderId < 1) {
            return;
        }
        if (!$this->tableExists('referral_redemptions')) {
            return;
        }
        $status = $this->sanitizeStatus($status);
        if ($status === '') {
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE referral_redemptions
             SET status = :st, updated_at = NOW()
             WHERE order_id = :oid'
        );
        $stmt->execute([
            ':st' => $status,
            ':oid' => $orderId,
        ]);
    }

    private function sanitizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if ($status === 'canceled') {
            $status = 'cancelled';
        }
        return match ($status) {
            'reserved', 'used', 'cancelled' => $status,
            default => '',
        };
    }
}
