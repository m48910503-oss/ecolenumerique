<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherReferralsModel extends Model
{
    public function countReferrals(int $referrerUserId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM referrals WHERE referrer_user_id = :uid');
        $stmt->execute([':uid' => $referrerUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function sumEarnedCommissions(int $referrerUserId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(amount_xof), 0) FROM referral_commissions WHERE referrer_id = :uid AND status = "earned"');
        $stmt->execute([':uid' => $referrerUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function countEarnedCommissions(int $referrerUserId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM referral_commissions WHERE referrer_id = :uid AND status = "earned"');
        $stmt->execute([':uid' => $referrerUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function countActiveAffiliates(int $referrerUserId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(DISTINCT buyer_id) FROM referral_commissions WHERE referrer_id = :uid AND status = "earned"');
        $stmt->execute([':uid' => $referrerUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function findLatestEarnedCommissionAt(int $referrerUserId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT MAX(created_at) FROM referral_commissions WHERE referrer_id = :uid AND status = "earned"');
        $stmt->execute([':uid' => $referrerUserId]);
        $value = $stmt->fetchColumn();
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    public function countReferralRows(int $referrerUserId): int
    {
        if (!$this->tableExists('referrals')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM referrals
             WHERE referrer_user_id = :uid'
        );
        $stmt->execute([':uid' => $referrerUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function countReferralByUser(int $referrerUserId): int
    {
        if (!$this->tableExists('referral_commissions')) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM (
                SELECT rc.buyer_id
                FROM referral_commissions rc
                WHERE rc.referrer_id = :uid
                  AND rc.status = "earned"
                GROUP BY rc.buyer_id
             ) ref_users'
        );
        $stmt->execute([':uid' => $referrerUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function sumEarnedByLevel(int $referrerUserId): array
    {
        $stmt = $this->pdo->prepare('SELECT level, COALESCE(SUM(amount_xof), 0) AS total_amount
            FROM referral_commissions
            WHERE referrer_id = :uid AND status = "earned"
            GROUP BY level');
        $stmt->execute([':uid' => $referrerUserId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findRecentCommissions(int $referrerUserId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare('SELECT rc.id, rc.level, rc.amount_xof, rc.rate_percent, rc.created_at,
                c.title AS course_title,
                t.first_name AS teacher_first, t.last_name AS teacher_last,
                b.first_name AS buyer_first, b.last_name AS buyer_last,
                o.reference AS order_ref
            FROM referral_commissions rc
            JOIN courses c ON c.id = rc.course_id
            JOIN users t ON t.id = rc.teacher_id
            JOIN users b ON b.id = rc.buyer_id
            LEFT JOIN orders o ON o.id = rc.order_id
            WHERE rc.referrer_id = :uid AND rc.status = "earned"
            ORDER BY rc.created_at DESC
            LIMIT ' . (int) $limit);
        $stmt->execute([':uid' => $referrerUserId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findTopCourses(int $referrerUserId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare('SELECT rc.course_id, c.title AS course_title, c.status,
                COALESCE(SUM(rc.amount_xof), 0) AS total_amount,
                COUNT(*) AS total_count
            FROM referral_commissions rc
            JOIN courses c ON c.id = rc.course_id
            WHERE rc.referrer_id = :uid AND rc.status = "earned"
            GROUP BY rc.course_id
            ORDER BY total_amount DESC
            LIMIT ' . (int) $limit);
        $stmt->execute([':uid' => $referrerUserId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findReferralRows(int $referrerUserId, int $limit = 12, bool $includeCommissionData = true): array
    {
        return $this->fetchReferralRowsPage($referrerUserId, 0, $limit, $includeCommissionData);
    }

    public function fetchReferralRowsPage(int $referrerUserId, int $offset, int $limit = 12, bool $includeCommissionData = true): array
    {
        if (!$this->tableExists('referrals')) {
            return [];
        }

        $limit = max(1, $limit);
        $offset = max(0, $offset);

        if ($includeCommissionData) {
            $sql = 'SELECT
                    r.referred_user_id,
                    r.created_at AS referral_created_at,
                    COALESCE(u.first_name, "") AS referred_first_name,
                    COALESCE(u.last_name, "") AS referred_last_name,
                    COUNT(DISTINCT rc.order_id) AS purchase_count,
                    COALESCE(SUM(rc.amount_xof), 0) AS earnings_xof,
                    MAX(rc.created_at) AS last_purchase_at
                FROM referrals r
                LEFT JOIN users u ON u.id = r.referred_user_id
                LEFT JOIN referral_commissions rc
                    ON rc.referrer_id = r.referrer_user_id
                   AND rc.buyer_id = r.referred_user_id
                   AND rc.status = "earned"
                WHERE r.referrer_user_id = :uid
                GROUP BY r.referred_user_id, r.created_at, u.first_name, u.last_name
                ORDER BY r.created_at DESC
                LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        } else {
            $sql = 'SELECT
                    r.referred_user_id,
                    r.created_at AS referral_created_at,
                    COALESCE(u.first_name, "") AS referred_first_name,
                    COALESCE(u.last_name, "") AS referred_last_name,
                    0 AS purchase_count,
                    0 AS earnings_xof,
                    NULL AS last_purchase_at
                FROM referrals r
                LEFT JOIN users u ON u.id = r.referred_user_id
                WHERE r.referrer_user_id = :uid
                ORDER BY r.created_at DESC
                LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $referrerUserId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findReferralsByUser(int $referrerUserId, int $limit = 50): array
    {
        return $this->fetchReferralByUserPage($referrerUserId, 0, $limit);
    }

    public function fetchReferralByUserPage(int $referrerUserId, int $offset, int $limit = 50): array
    {
        if (!$this->tableExists('referral_commissions')) {
            return [];
        }

        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare('SELECT rc.buyer_id,
                b.first_name AS buyer_first, b.last_name AS buyer_last,
                SUM(CASE WHEN rc.level = 1 THEN rc.amount_xof ELSE 0 END) AS amount_l1,
                SUM(CASE WHEN rc.level = 2 THEN rc.amount_xof ELSE 0 END) AS amount_l2,
                SUM(rc.amount_xof) AS total_amount,
                COUNT(*) AS total_count,
                MAX(rc.created_at) AS last_at
            FROM referral_commissions rc
            JOIN users b ON b.id = rc.buyer_id
            WHERE rc.referrer_id = :uid AND rc.status = "earned"
            GROUP BY rc.buyer_id
            ORDER BY total_amount DESC
            LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
        $stmt->execute([':uid' => $referrerUserId]);
        return $stmt->fetchAll() ?: [];
    }
}
