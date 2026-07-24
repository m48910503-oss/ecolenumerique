<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherWithdrawalsModel extends Model
{
    private ?bool $hasOrdersTable = null;
    private ?bool $hasOrderItemsTable = null;
    private ?bool $hasPromoScopeColumn = null;
    private ?bool $hasCompanyIdColumn = null;
    private ?bool $hasWithdrawalReasonColumn = null;

    public function fetchReferralCostsByCourse(int $teacherId, ?string $startAt = null, ?string $endAt = null): array
    {
        $sql = 'SELECT course_id, COALESCE(SUM(amount_xof), 0) AS total_amount
            FROM referral_commissions
            WHERE teacher_id = :tid AND status = "earned"';
        $params = [':tid' => $teacherId];
        if ($startAt !== null && $startAt !== '') {
            $sql .= ' AND created_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $sql .= ' AND created_at < :end_at';
            $params[':end_at'] = $endAt;
        }
        $sql .= ' GROUP BY course_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchReferralByCourse(int $teacherId, ?string $startAt = null, ?string $endAt = null): array
    {
        return $this->fetchReferralCostsByCourse($teacherId, $startAt, $endAt);
    }

    public function fetchPaidSalesRows(int $teacherId, ?string $startAt = null, ?string $endAt = null): array
    {
        $sql = 'SELECT oi.quantity, oi.unit_price_xof
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN courses c ON c.id = oi.course_id
            WHERE o.status = "paid" AND c.status = "approved" AND c.teacher_id = :tid';
        $params = [':tid' => $teacherId];
        if ($startAt !== null && $startAt !== '') {
            $sql .= ' AND o.updated_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $sql .= ' AND o.updated_at < :end_at';
            $params[':end_at'] = $endAt;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchCourseEarningsRows(int $teacherId, ?string $startAt = null, ?string $endAt = null): array
    {
        $ordersJoin = 'LEFT JOIN orders o ON o.id = oi.order_id';
        $params = [':tid' => $teacherId];
        if ($startAt !== null && $startAt !== '') {
            $ordersJoin .= ' AND o.updated_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $ordersJoin .= ' AND o.updated_at < :end_at';
            $params[':end_at'] = $endAt;
        }

        $stmt = $this->pdo->prepare('SELECT c.id, c.title,
                COALESCE(SUM(CASE WHEN o.status = "paid" THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS gross_amount,
                COALESCE(SUM(CASE WHEN o.status = "paid" THEN oi.quantity ELSE 0 END), 0) AS sales_count
            FROM courses c
            LEFT JOIN order_items oi ON oi.course_id = c.id
            ' . $ordersJoin . '
            WHERE c.status = "approved" AND c.teacher_id = :tid
            GROUP BY c.id
            ORDER BY c.created_at DESC');
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function sumGrossSales(int $teacherId, ?string $startAt = null, ?string $endAt = null): int
    {
        $sql = 'SELECT COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0)
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN courses c ON c.id = oi.course_id
            WHERE o.status = "paid"
              AND c.teacher_id = :tid';
        $params = [':tid' => $teacherId];

        if ($startAt !== null && $startAt !== '') {
            $sql .= ' AND o.updated_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $sql .= ' AND o.updated_at < :end_at';
            $params[':end_at'] = $endAt;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function sumReferralCosts(int $teacherId, ?string $startAt = null, ?string $endAt = null): int
    {
        if (!$this->tableExists('referral_commissions')) {
            return 0;
        }
        $sql = 'SELECT COALESCE(SUM(amount_xof), 0)
            FROM referral_commissions
            WHERE teacher_id = :tid
              AND status = "earned"';
        $params = [':tid' => $teacherId];
        if ($startAt !== null && $startAt !== '') {
            $sql .= ' AND created_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $sql .= ' AND created_at < :end_at';
            $params[':end_at'] = $endAt;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function sumReferralEarnings(int $teacherId, ?string $startAt = null, ?string $endAt = null): int
    {
        return $this->sumReferralCosts($teacherId, $startAt, $endAt);
    }

    /**
     * @return array{1:int,2:int}
     */
    public function sumReferralCostsByLevel(int $teacherId, ?string $startAt = null, ?string $endAt = null): array
    {
        if (!$this->tableExists('referral_commissions')) {
            return [1 => 0, 2 => 0];
        }
        $sql = 'SELECT level, COALESCE(SUM(amount_xof), 0) AS total_amount
            FROM referral_commissions
            WHERE teacher_id = :tid
              AND status = "earned"';
        $params = [':tid' => $teacherId];
        if ($startAt !== null && $startAt !== '') {
            $sql .= ' AND created_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $sql .= ' AND created_at < :end_at';
            $params[':end_at'] = $endAt;
        }
        $sql .= ' GROUP BY level';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $levels = [1 => 0, 2 => 0];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $level = (int) ($row['level'] ?? 0);
            if ($level === 1 || $level === 2) {
                $levels[$level] = (int) ($row['total_amount'] ?? 0);
            }
        }
        return $levels;
    }

    /**
     * @return array{1:int,2:int}
     */
    public function sumReferralByLevel(int $teacherId, ?string $startAt = null, ?string $endAt = null): array
    {
        return $this->sumReferralCostsByLevel($teacherId, $startAt, $endAt);
    }

    /**
     * @return array<int,array{month:string,gross:int}>
     */
    public function fetchMonthlyGrossSales(int $teacherId, string $startAt, string $endAt): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DATE_FORMAT(o.updated_at, "%Y-%m") AS ym,
                    COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0) AS gross
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN courses c ON c.id = oi.course_id
             WHERE o.status = "paid"
               AND c.teacher_id = :tid
               AND o.updated_at >= :start_at
               AND o.updated_at < :end_at
             GROUP BY ym
             ORDER BY ym ASC'
        );
        $stmt->execute([
            ':tid' => $teacherId,
            ':start_at' => $startAt,
            ':end_at' => $endAt,
        ]);
        $rows = $stmt->fetchAll() ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'month' => (string) ($row['ym'] ?? ''),
                'gross' => (int) ($row['gross'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCourseRevenueRows(int $teacherId, ?string $startAt = null, ?string $endAt = null): array
    {
        if ($teacherId < 1 || !$this->salesSchemaReady()) {
            return [];
        }

        $params = [':tid' => $teacherId];
        $dateJoin = $this->appendPaidDateFilters('o', $startAt, $endAt, $params);
        $scopeExpr = $this->hasPromoScopeColumn()
            ? 'LOWER(COALESCE(oi.promo_scope, "none"))'
            : '"none"';
        $isEnterprise = match (true) {
            $this->hasCompanyIdColumn() && $this->hasPromoScopeColumn() => '(COALESCE(oi.company_id, 0) > 0 OR ' . $scopeExpr . ' = "enterprise")',
            $this->hasCompanyIdColumn() => '(COALESCE(oi.company_id, 0) > 0)',
            $this->hasPromoScopeColumn() => '(' . $scopeExpr . ' = "enterprise")',
            default => '0',
        };
        $isCoupon = $this->hasPromoScopeColumn()
            ? '(' . $scopeExpr . ' = "coupon")'
            : '0';
        $isPromo = $this->hasPromoScopeColumn()
            ? '(NOT (' . $isEnterprise . ') AND ' . $scopeExpr . ' IN ("promotion", "targeted", "company"))'
            : '0';
        $isDirect = $this->hasPromoScopeColumn()
            ? '(NOT (' . $isEnterprise . ') AND ' . $scopeExpr . ' NOT IN ("coupon", "promotion", "targeted", "company", "enterprise"))'
            : '1';
        $isPaid = 'LOWER(COALESCE(o.status, "")) IN ("paid", "completed", "succeeded", "success")';

        $sql = 'SELECT
                c.id,
                c.title,
                c.status,
                c.cover_path,
                COALESCE((
                    SELECT COUNT(*)
                    FROM course_chapters ch
                    JOIN course_lessons cl ON cl.chapter_id = ch.id
                    WHERE ch.course_id = c.id
                ), 0) AS lessons_count,
                COALESCE(SUM(CASE WHEN ' . $isPaid . ' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS total_xof,
                COALESCE(SUM(CASE WHEN ' . $isPaid . ' AND ' . $isDirect . ' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS direct_xof,
                COALESCE(SUM(CASE WHEN ' . $isPaid . ' AND ' . $isPromo . ' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS promo_xof,
                COALESCE(SUM(CASE WHEN ' . $isPaid . ' AND ' . $isCoupon . ' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS coupon_xof,
                COALESCE(SUM(CASE WHEN ' . $isPaid . ' AND ' . $isEnterprise . ' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS enterprise_xof,
                COALESCE(SUM(CASE WHEN ' . $isPaid . ' THEN oi.quantity ELSE 0 END), 0) AS sales_count
            FROM courses c
            LEFT JOIN order_items oi ON oi.course_id = c.id
            LEFT JOIN orders o ON o.id = oi.order_id' . $dateJoin . '
            WHERE c.teacher_id = :tid
              AND c.status = "approved"
            GROUP BY c.id
            ORDER BY total_xof DESC, c.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $direct = (int) ($row['direct_xof'] ?? 0);
            $promo = (int) ($row['promo_xof'] ?? 0);
            $coupon = (int) ($row['coupon_xof'] ?? 0);
            $enterprise = (int) ($row['enterprise_xof'] ?? 0);
            $total = (int) ($row['total_xof'] ?? 0);
            $salesCount = (int) ($row['sales_count'] ?? 0);
            $lessonsCount = (int) ($row['lessons_count'] ?? 0);
            $computedTotal = $direct + $promo + $coupon + $enterprise;
            if ($computedTotal > 0 && $total <= 0) {
                $total = $computedTotal;
            }
            $out[] = [
                'course_id' => (int) ($row['id'] ?? 0),
                'title' => (string) ($row['title'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'cover_path' => (string) ($row['cover_path'] ?? ''),
                'lessons_count' => $lessonsCount,
                'total_xof' => $total,
                'direct_xof' => $direct,
                'promo_xof' => $promo,
                'coupon_xof' => $coupon,
                'enterprise_xof' => $enterprise,
                'sales_count' => $salesCount,
            ];
        }

        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchRecentTransactionRows(int $teacherId, int $limit = 8, ?string $startAt = null, ?string $endAt = null): array
    {
        if ($teacherId < 1 || !$this->salesSchemaReady()) {
            return [];
        }

        $safeLimit = max(1, min(50, $limit));
        $params = [':tid' => $teacherId];
        $dateJoin = $this->appendPaidDateFilters('o', $startAt, $endAt, $params);
        $scopeExpr = $this->hasPromoScopeColumn()
            ? 'LOWER(COALESCE(oi.promo_scope, "none"))'
            : '"none"';
        $isEnterprise = match (true) {
            $this->hasCompanyIdColumn() && $this->hasPromoScopeColumn() => '(COALESCE(oi.company_id, 0) > 0 OR ' . $scopeExpr . ' = "enterprise")',
            $this->hasCompanyIdColumn() => '(COALESCE(oi.company_id, 0) > 0)',
            $this->hasPromoScopeColumn() => '(' . $scopeExpr . ' = "enterprise")',
            default => '0',
        };
        $isCoupon = $this->hasPromoScopeColumn()
            ? '(' . $scopeExpr . ' = "coupon")'
            : '0';
        $isPromo = $this->hasPromoScopeColumn()
            ? '(NOT (' . $isEnterprise . ') AND ' . $scopeExpr . ' IN ("promotion", "targeted", "company"))'
            : '0';
        $isDirect = $this->hasPromoScopeColumn()
            ? '(NOT (' . $isEnterprise . ') AND ' . $scopeExpr . ' NOT IN ("coupon", "promotion", "targeted", "company", "enterprise"))'
            : '1';
        $originExpr = 'CASE
                WHEN ' . $isEnterprise . ' THEN "Entreprise"
                WHEN ' . $isCoupon . ' THEN "Coupon"
                WHEN ' . $isPromo . ' THEN "Promotion"
                WHEN ' . $isDirect . ' THEN "Direct"
                ELSE "Direct"
            END';

        $sql = 'SELECT
                o.id AS order_id,
                o.reference AS order_reference,
                o.provider AS payment_provider,
                o.updated_at AS paid_at,
                oi.quantity,
                oi.unit_price_xof,
                c.id AS course_id,
                c.title AS course_title,
                c.status AS course_status,
                u.first_name AS buyer_first_name,
                u.last_name AS buyer_last_name,
                u.email AS buyer_email,
                ' . $originExpr . ' AS origin_label
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN courses c ON c.id = oi.course_id
            JOIN users u ON u.id = o.user_id
            WHERE c.teacher_id = :tid
              AND c.status = "approved"
              AND LOWER(COALESCE(o.status, "")) IN ("paid", "completed", "succeeded", "success")'
            . $dateJoin . '
            ORDER BY o.updated_at DESC, oi.id DESC
            LIMIT ' . (int) $safeLimit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $unit = max(0, (int) ($row['unit_price_xof'] ?? 0));
            $out[] = [
                'order_id' => (int) ($row['order_id'] ?? 0),
                'order_reference' => (string) ($row['order_reference'] ?? ''),
                'paid_at' => (string) ($row['paid_at'] ?? ''),
                'quantity' => $qty,
                'unit_price_xof' => $unit,
                'amount_xof' => $qty * $unit,
                'course_id' => (int) ($row['course_id'] ?? 0),
                'course_title' => (string) ($row['course_title'] ?? ''),
                'course_status' => (string) ($row['course_status'] ?? ''),
                'buyer_first_name' => (string) ($row['buyer_first_name'] ?? ''),
                'buyer_last_name' => (string) ($row['buyer_last_name'] ?? ''),
                'buyer_email' => (string) ($row['buyer_email'] ?? ''),
                'payment_provider' => (string) ($row['payment_provider'] ?? ''),
                'origin_label' => (string) ($row['origin_label'] ?? 'Direct'),
            ];
        }

        return $out;
    }

    public function sumPaidWithdrawals(int $userId, ?string $withdrawalType = 'teacher_sales'): int
    {
        $sql = 'SELECT COALESCE(SUM(amount_xof), 0) FROM withdrawals WHERE user_id = :uid AND status IN ("paid","approved")';
        $params = [':uid' => $userId];
        if ($this->supportsWithdrawalType()) {
            $type = $this->sanitizeWithdrawalType($withdrawalType);
            if ($type === 'teacher_sales') {
                $sql .= ' AND (withdrawal_type = :wtype OR withdrawal_type IS NULL)';
            } else {
                $sql .= ' AND withdrawal_type = :wtype';
            }
            $params[':wtype'] = $type;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function sumPendingWithdrawals(int $userId, ?string $withdrawalType = 'teacher_sales'): int
    {
        if (!$this->tableExists('withdrawals')) {
            return 0;
        }
        $sql = 'SELECT COALESCE(SUM(amount_xof), 0) FROM withdrawals WHERE user_id = :uid AND status IN ("requested","approved")';
        $params = [':uid' => $userId];
        if ($this->supportsWithdrawalType()) {
            $sql .= ' AND withdrawal_type = :wtype';
            $params[':wtype'] = $this->sanitizeWithdrawalType($withdrawalType);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function countPendingWithdrawalRequests(int $userId, ?string $withdrawalType = 'teacher_sales'): int
    {
        if (!$this->tableExists('withdrawals')) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) FROM withdrawals WHERE user_id = :uid AND status IN ("requested","approved")';
        $params = [':uid' => $userId];
        if ($this->supportsWithdrawalType()) {
            $sql .= ' AND withdrawal_type = :wtype';
            $params[':wtype'] = $this->sanitizeWithdrawalType($withdrawalType);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function sumPaidPayouts(int $teacherId): int
    {
        if (!$this->tableExists('teacher_payouts')) {
            return 0;
        }
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(teacher_amount_xof), 0) FROM teacher_payouts WHERE teacher_id = :tid AND status = "paid"');
        $stmt->execute([':tid' => $teacherId]);
        return (int) $stmt->fetchColumn();
    }

    public function fetchNextPayout(int $teacherId): ?array
    {
        if (!$this->tableExists('teacher_payouts')) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT teacher_amount_xof, total_sales_xof, status, created_at
             FROM teacher_payouts
             WHERE teacher_id = :tid
               AND status = "simulated"
             ORDER BY created_at ASC
             LIMIT 1'
        );
        $stmt->execute([':tid' => $teacherId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertWithdrawal(
        int $userId,
        int $amountXof,
        string $method,
        string $destination,
        string $status,
        string $reference,
        string $withdrawalType = 'teacher_sales',
        ?string $reason = null
    ): void {
        $reviewedAt = in_array($status, ['approved', 'rejected', 'paid'], true) ? date('Y-m-d H:i:s') : null;
        $ref = trim($reference);
        $reasonText = trim((string) $reason);
        $columns = ['user_id'];
        $values = [':uid'];
        $params = [
            ':uid' => $userId,
            ':amt' => $amountXof,
            ':m' => $method,
            ':dest' => $destination,
            ':st' => $status,
            ':reviewed_at' => $reviewedAt,
            ':ref' => $ref !== '' ? $ref : null,
        ];

        if ($this->supportsWithdrawalType()) {
            $columns[] = 'withdrawal_type';
            $values[] = ':wtype';
            $params[':wtype'] = $this->sanitizeWithdrawalType($withdrawalType);
        }

        $columns[] = 'amount_xof';
        $values[] = ':amt';
        $columns[] = 'method';
        $values[] = ':m';
        $columns[] = 'destination';
        $values[] = ':dest';
        $columns[] = 'status';
        $values[] = ':st';
        $columns[] = 'reviewed_at';
        $values[] = ':reviewed_at';
        $columns[] = 'payout_reference';
        $values[] = ':ref';
        if ($this->supportsWithdrawalReason()) {
            $columns[] = 'reason';
            $values[] = ':reason';
            $params[':reason'] = $reasonText !== '' ? $reasonText : null;
        }
        $columns[] = 'created_at';
        $values[] = 'NOW()';

        $sql = 'INSERT INTO withdrawals (' . implode(', ', $columns) . ')
            VALUES (' . implode(', ', $values) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function insertNotification(int $userId, string $title, string $body, string $url): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at)
            VALUES (:uid, :title, :body, :url, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    /**
     * @return array<int,int>
     */
    public function findAdminNotificationRecipientIds(): array
    {
        $rows = $this->pdo
            ->query('SELECT id FROM users WHERE roles_json LIKE \'%"admin"%\' OR roles_json LIKE \'%"admin_secondary"%\'')
            ->fetchAll() ?: [];

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    public function fetchWithdrawalsHistory(
        int $userId,
        int $limit = 50,
        ?string $startAt = null,
        ?string $endAt = null,
        ?string $withdrawalType = 'teacher_sales'
    ): array {
        return $this->fetchWithdrawalsHistoryPage($userId, 0, $limit, $startAt, $endAt, $withdrawalType);
    }

    public function countWithdrawalsHistory(
        int $userId,
        ?string $startAt = null,
        ?string $endAt = null,
        ?string $withdrawalType = 'teacher_sales'
    ): int {
        $sql = 'SELECT COUNT(*) FROM withdrawals WHERE user_id = :uid';
        $params = [':uid' => $userId];
        if ($this->supportsWithdrawalType() && !$this->isAllWithdrawalType($withdrawalType)) {
            $sql .= ' AND withdrawal_type = :wtype';
            $params[':wtype'] = $this->sanitizeWithdrawalType($withdrawalType);
        }
        if ($startAt !== null && $startAt !== '') {
            $sql .= ' AND created_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $sql .= ' AND created_at < :end_at';
            $params[':end_at'] = $endAt;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function fetchWithdrawalsHistoryPage(
        int $userId,
        int $offset = 0,
        int $limit = 50,
        ?string $startAt = null,
        ?string $endAt = null,
        ?string $withdrawalType = 'teacher_sales'
    ): array {
        $offset = max(0, $offset);
        $limit = max(1, min(200, $limit));

        $sql = 'SELECT * FROM withdrawals WHERE user_id = :uid';
        $params = [':uid' => $userId];
        if ($this->supportsWithdrawalType() && !$this->isAllWithdrawalType($withdrawalType)) {
            $sql .= ' AND withdrawal_type = :wtype';
            $params[':wtype'] = $this->sanitizeWithdrawalType($withdrawalType);
        }
        if ($startAt !== null && $startAt !== '') {
            $sql .= ' AND created_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $sql .= ' AND created_at < :end_at';
            $params[':end_at'] = $endAt;
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function cancelWithdrawalRequest(int $userId, int $withdrawalId, ?string $withdrawalType = 'teacher_sales'): bool
    {
        $sql = 'UPDATE withdrawals
            SET status = "rejected", reviewed_at = NOW(), reviewed_by = NULL, payout_reference = NULL';
        $params = [
            ':id' => $withdrawalId,
            ':uid' => $userId,
        ];
        if ($this->supportsWithdrawalReason()) {
            $sql .= ', reason = :reason';
            $params[':reason'] = 'Annulé par l\'enseignant';
        }
        $sql .= ' WHERE id = :id AND user_id = :uid AND status = "requested"';
        if ($this->supportsWithdrawalType()) {
            $sql .= ' AND withdrawal_type = :wtype';
            $params[':wtype'] = $this->sanitizeWithdrawalType($withdrawalType);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
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
        if ($this->hasWithdrawalReasonColumn === null) {
            try {
                $this->hasWithdrawalReasonColumn = $this->columnExists('withdrawals', 'reason');
            } catch (\Throwable $e) {
                $this->hasWithdrawalReasonColumn = false;
            }
        }

        return $this->hasWithdrawalReasonColumn;
    }

    private function salesSchemaReady(): bool
    {
        return $this->hasOrdersTable() && $this->hasOrderItemsTable();
    }

    private function hasOrdersTable(): bool
    {
        if ($this->hasOrdersTable === null) {
            $this->hasOrdersTable = $this->tableExists('orders');
        }
        return $this->hasOrdersTable;
    }

    private function hasOrderItemsTable(): bool
    {
        if ($this->hasOrderItemsTable === null) {
            $this->hasOrderItemsTable = $this->tableExists('order_items');
        }
        return $this->hasOrderItemsTable;
    }

    private function hasPromoScopeColumn(): bool
    {
        if ($this->hasPromoScopeColumn === null) {
            $this->hasPromoScopeColumn = $this->salesSchemaReady() && $this->columnExists('order_items', 'promo_scope');
        }
        return $this->hasPromoScopeColumn;
    }

    private function hasCompanyIdColumn(): bool
    {
        if ($this->hasCompanyIdColumn === null) {
            $this->hasCompanyIdColumn = $this->salesSchemaReady() && $this->columnExists('order_items', 'company_id');
        }
        return $this->hasCompanyIdColumn;
    }

    /**
     * @param array<string,mixed> $params
     */
    private function appendPaidDateFilters(string $alias, ?string $startAt, ?string $endAt, array &$params): string
    {
        $sql = '';
        if ($startAt !== null && $startAt !== '') {
            $sql .= ' AND ' . $alias . '.updated_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $sql .= ' AND ' . $alias . '.updated_at < :end_at';
            $params[':end_at'] = $endAt;
        }
        return $sql;
    }

    private function sanitizeWithdrawalType(?string $withdrawalType): string
    {
        $type = strtolower(trim((string) $withdrawalType));
        return $type === 'referral' ? 'referral' : 'teacher_sales';
    }

    private function isAllWithdrawalType(?string $withdrawalType): bool
    {
        if ($withdrawalType === null) {
            return true;
        }

        return strtolower(trim((string) $withdrawalType)) === 'all';
    }
}
