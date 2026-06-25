<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminSalesModel extends Model
{
    public function ensureTeacherPayoutsTable(): bool
    {
        return $this->tableExists('teacher_payouts');
    }

    public function insertTeacherPayout(
        int $teacherId,
        int $totalSalesXof,
        int $platformFeeXof,
        int $teacherAmountXof,
        float $commissionPercent,
        int $createdBy
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO teacher_payouts
            (teacher_id, total_sales_xof, platform_fee_xof, teacher_amount_xof, commission_percent, status, created_by)
            VALUES (:tid, :ts, :pf, :ta, :pc, :st, :cb)'
        );
        $stmt->execute([
            ':tid' => $teacherId,
            ':ts' => $totalSalesXof,
            ':pf' => $platformFeeXof,
            ':ta' => $teacherAmountXof,
            ':pc' => $commissionPercent,
            ':st' => 'simulated',
            ':cb' => $createdBy,
        ]);

        $payoutId = (int) $this->pdo->lastInsertId();
        if ($payoutId > 0) {
            return $payoutId;
        }

        return 0;
    }

    public function markTeacherPayoutPaid(int $payoutId): void
    {
        $stmt = $this->pdo->prepare("UPDATE teacher_payouts SET status = 'paid', updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $payoutId]);
    }

    public function markPaymentsPaidByOrderId(int $orderId): void
    {
        $stmt = $this->pdo->prepare('UPDATE payments SET status = "paid", updated_at = NOW() WHERE order_id = :oid');
        $stmt->execute([':oid' => $orderId]);
    }

    public function fetchSalesPaidApproved(): array
    {
        $sql = 'SELECT oi.id AS item_id, oi.quantity, oi.unit_price_xof,
                o.id AS order_id, o.reference, o.created_at AS order_created_at, o.status AS order_status,
                buyer.first_name AS buyer_first, buyer.last_name AS buyer_last,
                c.id AS course_id, c.title AS course_title, cat.name AS category_name,
                t.id AS teacher_id, t.first_name AS teacher_first, t.last_name AS teacher_last
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN users buyer ON buyer.id = o.user_id
            JOIN courses c ON c.id = oi.course_id
            LEFT JOIN categories cat ON cat.id = c.category_id
            JOIN users t ON t.id = c.teacher_id
            WHERE o.status = \'paid\' AND c.status = \'approved\'
            ORDER BY o.created_at DESC, oi.id DESC
            LIMIT 200';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchPendingOrders(): array
    {
        $sql = 'SELECT o.id, o.reference, o.total_xof, o.provider, o.status, o.created_at,
                u.first_name AS buyer_first, u.last_name AS buyer_last,
                p.reference AS payment_ref, p.status AS payment_status, p.raw_payload_json
            FROM orders o
            JOIN users u ON u.id = o.user_id
            LEFT JOIN payments p ON p.id = (
                SELECT p2.id FROM payments p2 WHERE p2.order_id = o.id ORDER BY p2.id DESC LIMIT 1
            )
            WHERE o.status = \'pending\'
            ORDER BY o.created_at DESC
            LIMIT 50';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchReferralCommissionsByItemTeacherCourse(): array
    {
        $stmt = $this->pdo->query(
            'SELECT order_item_id, teacher_id, course_id, COALESCE(SUM(amount_xof), 0) AS total_amount
             FROM referral_commissions
             WHERE status = "earned"
             GROUP BY order_item_id, teacher_id, course_id'
        );
        return $stmt->fetchAll() ?: [];
    }

    public function fetchPaidWithdrawalTotalsByTeacher(): array
    {
        $sql = 'SELECT user_id AS teacher_id, SUM(amount_xof) AS total_paid FROM withdrawals WHERE status = "paid"';
        if ($this->supportsWithdrawalType()) {
            $sql .= ' AND withdrawal_type = "teacher_sales"';
        }
        $sql .= ' GROUP BY user_id';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchPaidTeacherPayoutTotalsByTeacher(): array
    {
        $stmt = $this->pdo->query('SELECT teacher_id, SUM(teacher_amount_xof) AS total_paid FROM teacher_payouts WHERE status = "paid" GROUP BY teacher_id');
        return $stmt->fetchAll() ?: [];
    }

    public function fetchRecentPayouts(): array
    {
        $stmt = $this->pdo->query(
            'SELECT p.*, t.first_name AS teacher_first, t.last_name AS teacher_last,
                a.first_name AS admin_first, a.last_name AS admin_last
             FROM teacher_payouts p
             JOIN users t ON t.id = p.teacher_id
             LEFT JOIN users a ON a.id = p.created_by
             ORDER BY p.created_at DESC
             LIMIT 50'
        );
        return $stmt->fetchAll() ?: [];
    }

    public function fetchRecentWithdrawals(): array
    {
        $typeSelect = $this->supportsWithdrawalType()
            ? 'w.withdrawal_type'
            : '"teacher_sales" AS withdrawal_type';
        $stmt = $this->pdo->query(
            'SELECT w.*, ' . $typeSelect . ', u.first_name AS teacher_first, u.last_name AS teacher_last, u.email AS requester_email
             FROM withdrawals w
             JOIN users u ON u.id = w.user_id
             ORDER BY w.created_at DESC
             LIMIT 50'
        );
        return $stmt->fetchAll() ?: [];
    }

    public function findWithdrawalById(int $withdrawalId): ?array
    {
        if ($withdrawalId < 1) {
            return null;
        }
        $typeSelect = $this->supportsWithdrawalType()
            ? 'w.withdrawal_type'
            : '"teacher_sales" AS withdrawal_type';
        $stmt = $this->pdo->prepare(
            'SELECT w.*, ' . $typeSelect . ', u.first_name AS teacher_first, u.last_name AS teacher_last, u.email AS requester_email
             FROM withdrawals w
             JOIN users u ON u.id = w.user_id
             WHERE w.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $withdrawalId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateWithdrawalStatus(
        int $withdrawalId,
        string $status,
        int $reviewedBy,
        ?string $payoutReference = null,
        ?string $reason = null
    ): bool {
        $status = strtolower(trim($status));
        if (!in_array($status, ['approved', 'rejected', 'paid'], true)) {
            return false;
        }

        $updateReason = $status === 'rejected' ? trim((string) $reason) : null;
        $sql = 'UPDATE withdrawals
             SET status = :status,
                 reviewed_by = :reviewed_by,
                 reviewed_at = NOW(),
                 payout_reference = :ref';
        $params = [
            ':status' => $status,
            ':reviewed_by' => $reviewedBy > 0 ? $reviewedBy : null,
            ':ref' => ($status === 'paid' && trim((string) $payoutReference) !== '')
                ? trim((string) $payoutReference)
                : null,
            ':id' => $withdrawalId,
        ];
        if ($this->supportsWithdrawalReason()) {
            $sql .= ', reason = :reason';
            $params[':reason'] = $updateReason !== '' ? $updateReason : null;
        }
        $sql .= ', updated_at = NOW()
             WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function insertNotification(int $userId, string $title, string $body, string $url): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, title, body, url, created_at)
             VALUES (:uid, :title, :body, :url, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    public function fetchTeacherCourseSalesRows(): array
    {
        $sql = 'SELECT t.id AS teacher_id, t.first_name, t.last_name,
                c.id AS course_id, c.title AS course_title,
                COALESCE(SUM(CASE WHEN o.status = "paid" THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS gross_amount,
                COALESCE(SUM(CASE WHEN o.status = "paid" THEN oi.quantity ELSE 0 END), 0) AS sales_count
            FROM courses c
            JOIN users t ON t.id = c.teacher_id
            LEFT JOIN order_items oi ON oi.course_id = c.id
            LEFT JOIN orders o ON o.id = oi.order_id
            WHERE c.status = "approved"
            GROUP BY t.id, c.id
            ORDER BY t.last_name, t.first_name, c.title';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    private function supportsWithdrawalType(): bool
    {
        static $supported = null;
        if ($supported !== null) {
            return $supported;
        }

        try {
            $supported = $this->columnExists('withdrawals', 'withdrawal_type');
        } catch (\Throwable $e) {
            $supported = false;
        }

        return $supported;
    }

    private function supportsWithdrawalReason(): bool
    {
        static $supported = null;
        if ($supported !== null) {
            return $supported;
        }

        try {
            $supported = $this->columnExists('withdrawals', 'reason');
        } catch (\Throwable $e) {
            $supported = false;
        }

        return $supported;
    }
}
