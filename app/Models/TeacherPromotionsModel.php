<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherPromotionsModel extends Model
{
    private function hasPromoModeColumn(): bool
    {
        return $this->columnExists('course_promotions', 'promo_mode');
    }

    private function hasCouponCodeColumn(): bool
    {
        return $this->columnExists('course_promotions', 'coupon_code');
    }

    private function hasCouponSchema(): bool
    {
        return $this->hasPromoModeColumn() && $this->hasCouponCodeColumn();
    }

    public function findCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, teacher_id, price_xof, status, title, slug
             FROM courses
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findPromotionForUpdate(int $promoId, ?string $promoMode = null): ?array
    {
        $modeFilter = '';
        $params = [':id' => $promoId];
        $hasPromoMode = $this->hasPromoModeColumn();
        $hasCouponCode = $this->hasCouponCodeColumn();
        if ($promoMode !== null && $hasPromoMode) {
            $modeFilter = ' AND cp.promo_mode = :pmode';
            $params[':pmode'] = $promoMode;
        }

        $extraColumns = '';
        if ($hasPromoMode) {
            $extraColumns .= ', cp.promo_mode';
        }
        if ($hasCouponCode) {
            $extraColumns .= ', cp.coupon_code';
        }

        $stmt = $this->pdo->prepare(
            'SELECT cp.id, cp.course_id, cp.promo_start_at, cp.promo_end_at, c.teacher_id, c.price_xof, c.status, c.title, c.slug,
                    cp.promo_type, cp.promo_value'
                    . $extraColumns . '
             FROM course_promotions cp
             JOIN courses c ON c.id = cp.course_id
             WHERE cp.id = :id AND cp.deleted_at IS NULL'
             . $modeFilter . '
             LIMIT 1'
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findCouponByCode(string $couponCode, ?int $excludePromoId = null): ?array
    {
        if (!$this->hasCouponSchema() || $couponCode === '') {
            return null;
        }

        $params = [':code' => $couponCode];
        $excludeSql = '';
        if ($excludePromoId !== null && $excludePromoId > 0) {
            $excludeSql = ' AND cp.id <> :id';
            $params[':id'] = $excludePromoId;
        }

        $stmt = $this->pdo->prepare(
            'SELECT cp.id, cp.course_id, cp.promo_type, cp.promo_value, cp.promo_start_at, cp.promo_end_at, cp.coupon_code, cp.deleted_at
             FROM course_promotions cp
             WHERE cp.promo_mode = "coupon"
               AND cp.coupon_code = :code'
             . $excludeSql . '
             LIMIT 1'
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findPromotionForDelete(int $promoId, ?string $promoMode = null): ?array
    {
        $modeFilter = '';
        $params = [':id' => $promoId];
        $hasPromoMode = $this->hasPromoModeColumn();
        if ($promoMode !== null && $hasPromoMode) {
            $modeFilter = ' AND cp.promo_mode = :pmode';
            $params[':pmode'] = $promoMode;
        }

        $extraColumns = $hasPromoMode ? ', cp.promo_mode' : '';

        $stmt = $this->pdo->prepare(
            'SELECT cp.course_id, c.teacher_id'
             . $extraColumns . '
             FROM course_promotions cp
             JOIN courses c ON c.id = cp.course_id
             WHERE cp.id = :id AND cp.deleted_at IS NULL'
             . $modeFilter . '
             LIMIT 1'
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @param array<int,int> $targetUserIds
     */
    public function hasActiveIndividualConflict(
        int $courseId,
        array $targetUserIds,
        ?int $excludePromoId = null,
        ?string $promoMode = null
    ): bool
    {
        if ($targetUserIds === []) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($targetUserIds), '?'));
        $sql = "SELECT cpt.user_id
                FROM course_promotions cp
                JOIN course_promotion_targets cpt ON cpt.promotion_id = cp.id
                WHERE cp.course_id = ?
                  AND cp.deleted_at IS NULL
                  AND cp.promo_start_at <= NOW()
                  AND cp.promo_end_at > NOW()";
        $params = [$courseId];
        if ($promoMode !== null && $this->hasPromoModeColumn()) {
            $sql .= ' AND cp.promo_mode = ?';
            $params[] = $promoMode;
        }
        if ($excludePromoId !== null) {
            $sql .= ' AND cp.id <> ?';
            $params[] = $excludePromoId;
        }
        $sql .= " AND cpt.user_id IN ({$placeholders}) LIMIT 1";
        foreach ($targetUserIds as $userId) {
            $params[] = (int) $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function hasActiveCompanyConflict(
        int $courseId,
        int $companyId,
        ?int $excludePromoId = null,
        ?string $promoMode = null
    ): bool
    {
        $sql = 'SELECT cp.id
                FROM course_promotions cp
                JOIN course_promotion_company_targets cpc ON cpc.promotion_id = cp.id
                WHERE cp.course_id = ?
                  AND cp.deleted_at IS NULL
                  AND cp.promo_start_at <= NOW()
                  AND cp.promo_end_at > NOW()';
        $params = [$courseId];
        if ($promoMode !== null && $this->hasPromoModeColumn()) {
            $sql .= ' AND cp.promo_mode = ?';
            $params[] = $promoMode;
        }
        if ($excludePromoId !== null) {
            $sql .= ' AND cp.id <> ?';
            $params[] = $excludePromoId;
        }
        $sql .= ' AND cpc.company_id = ? LIMIT 1';
        $params[] = $companyId;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function hasActivePublicConflict(
        int $courseId,
        bool $targetsTableAvailable,
        bool $companyTargetsTableAvailable,
        ?int $excludePromoId = null,
        ?string $promoMode = null
    ): bool {
        $filters = [];
        if ($targetsTableAvailable) {
            $filters[] = 'NOT EXISTS (SELECT 1 FROM course_promotion_targets cpt WHERE cpt.promotion_id = cp.id)';
        }
        if ($companyTargetsTableAvailable) {
            $filters[] = 'NOT EXISTS (SELECT 1 FROM course_promotion_company_targets cpc WHERE cpc.promotion_id = cp.id)';
        }
        if ($promoMode !== null && $this->hasPromoModeColumn()) {
            $filters[] = 'cp.promo_mode = :pmode';
        }

        $sql = 'SELECT cp.id
                FROM course_promotions cp
                WHERE cp.course_id = :cid
                  AND cp.deleted_at IS NULL
                  AND cp.promo_start_at <= NOW()
                  AND cp.promo_end_at > NOW()';
        if ($excludePromoId !== null) {
            $sql .= ' AND cp.id <> :pid';
        }
        if ($filters !== []) {
            $sql .= ' AND ' . implode(' AND ', $filters);
        }
        $sql .= ' LIMIT 1';

        $params = [':cid' => $courseId];
        if ($promoMode !== null && $this->hasPromoModeColumn()) {
            $params[':pmode'] = $promoMode;
        }
        if ($excludePromoId !== null) {
            $params[':pid'] = $excludePromoId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function createPromotion(
        int $courseId,
        int $teacherId,
        string $promoType,
        float $promoValue,
        string $promoStartAt,
        string $promoEndAt,
        string $promoMode = 'promotion',
        ?string $couponCode = null
    ): int {
        if ($this->hasCouponSchema()) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO course_promotions (course_id, teacher_id, promo_mode, promo_type, promo_value, coupon_code, promo_start_at, promo_end_at, created_at, updated_at)
                 VALUES (:cid, :tid, :pmode, :ptype, :pvalue, :ccode, :pstart, :pend, NOW(), NOW())'
            );
            $stmt->execute([
                ':cid' => $courseId,
                ':tid' => $teacherId,
                ':pmode' => $promoMode,
                ':ptype' => $promoType,
                ':pvalue' => $promoValue,
                ':ccode' => $couponCode,
                ':pstart' => $promoStartAt,
                ':pend' => $promoEndAt,
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO course_promotions (course_id, teacher_id, promo_type, promo_value, promo_start_at, promo_end_at, created_at, updated_at)
                 VALUES (:cid, :tid, :ptype, :pvalue, :pstart, :pend, NOW(), NOW())'
            );
            $stmt->execute([
                ':cid' => $courseId,
                ':tid' => $teacherId,
                ':ptype' => $promoType,
                ':pvalue' => $promoValue,
                ':pstart' => $promoStartAt,
                ':pend' => $promoEndAt,
            ]);
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function updatePromotion(
        int $promoId,
        string $promoType,
        float $promoValue,
        string $promoStartAt,
        string $promoEndAt,
        ?string $couponCode = null
    ): void {
        if ($this->hasCouponSchema()) {
            $stmt = $this->pdo->prepare(
                'UPDATE course_promotions
                 SET promo_type = :ptype,
                     promo_value = :pvalue,
                     coupon_code = :ccode,
                     promo_start_at = :pstart,
                     promo_end_at = :pend,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':ptype' => $promoType,
                ':pvalue' => $promoValue,
                ':ccode' => $couponCode,
                ':pstart' => $promoStartAt,
                ':pend' => $promoEndAt,
                ':id' => $promoId,
            ]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE course_promotions
             SET promo_type = :ptype,
                 promo_value = :pvalue,
                 promo_start_at = :pstart,
                 promo_end_at = :pend,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':ptype' => $promoType,
            ':pvalue' => $promoValue,
            ':pstart' => $promoStartAt,
            ':pend' => $promoEndAt,
            ':id' => $promoId,
        ]);
    }

    public function softDeletePromotion(int $promoId): void
    {
        $stmt = $this->pdo->prepare('UPDATE course_promotions SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $promoId]);
    }

    public function addPromotionTarget(int $promoId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_promotion_targets (promotion_id, user_id, created_at)
             VALUES (:pid, :uid, NOW())'
        );
        $stmt->execute([
            ':pid' => $promoId,
            ':uid' => $userId,
        ]);
    }

    public function clearPromotionTargets(int $promoId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_promotion_targets WHERE promotion_id = :pid');
        $stmt->execute([':pid' => $promoId]);
    }

    public function addPromotionCompanyTarget(int $promoId, int $companyId, int $seatLimit): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_promotion_company_targets (promotion_id, company_id, seat_limit, created_at, updated_at)
             VALUES (:pid, :cid, :seats, NOW(), NOW())'
        );
        $stmt->execute([
            ':pid' => $promoId,
            ':cid' => $companyId,
            ':seats' => $seatLimit,
        ]);
    }

    public function clearPromotionCompanyTargets(int $promoId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_promotion_company_targets WHERE promotion_id = :pid');
        $stmt->execute([':pid' => $promoId]);
    }

    public function findPromotionCompanyId(int $promoId): int
    {
        $stmt = $this->pdo->prepare('SELECT company_id FROM course_promotion_company_targets WHERE promotion_id = :pid LIMIT 1');
        $stmt->execute([':pid' => $promoId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findCoursesForTeacher(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, price_xof, status
             FROM courses
             WHERE teacher_id = :tid AND deleted_at IS NULL AND status = "approved"
             ORDER BY created_at DESC'
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findPromotionsForTeacher(
        int $teacherId,
        string $promoMode = 'promotion',
        ?string $periodStartAt = null,
        ?string $periodEndAt = null
    ): array
    {
        $modeFilter = '';
        $params = [':tid' => $teacherId];
        if ($this->hasPromoModeColumn()) {
            $modeFilter = ' AND cp.promo_mode = :pmode';
            $params[':pmode'] = $promoMode;
        }
        $periodFilter = '';
        if ($periodStartAt !== null && $periodStartAt !== '') {
            $periodFilter .= ' AND cp.promo_end_at >= :period_start_at';
            $params[':period_start_at'] = $periodStartAt;
        }
        if ($periodEndAt !== null && $periodEndAt !== '') {
            $periodFilter .= ' AND cp.promo_start_at < :period_end_at';
            $params[':period_end_at'] = $periodEndAt;
        }
        $stmt = $this->pdo->prepare(
            'SELECT cp.*, c.title, c.price_xof, c.status
             FROM course_promotions cp
             JOIN courses c ON c.id = cp.course_id
             WHERE cp.deleted_at IS NULL AND c.teacher_id = :tid'
             . $modeFilter
             . $periodFilter . '
             ORDER BY cp.created_at DESC'
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{
     *   rows:array<int,array<string,mixed>>,
     *   total:int,
     *   page:int,
     *   per_page:int,
     *   total_pages:int
     * }
     */
    public function fetchPromotionsForTeacherPaged(
        int $teacherId,
        string $promoMode = 'promotion',
        ?string $periodStartAt = null,
        ?string $periodEndAt = null,
        array $filters = [],
        int $page = 1,
        int $perPage = 10
    ): array {
        if ($teacherId < 1) {
            return [
                'rows' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => max(1, $perPage),
                'total_pages' => 1,
            ];
        }

        $params = [':tid' => $teacherId];
        $whereParts = ['cp.deleted_at IS NULL', 'c.teacher_id = :tid'];

        if ($this->hasPromoModeColumn()) {
            $whereParts[] = 'cp.promo_mode = :pmode';
            $params[':pmode'] = $promoMode;
        }

        if ($periodStartAt !== null && $periodStartAt !== '') {
            $whereParts[] = 'cp.promo_end_at >= :period_start_at';
            $params[':period_start_at'] = $periodStartAt;
        }
        if ($periodEndAt !== null && $periodEndAt !== '') {
            $whereParts[] = 'cp.promo_start_at < :period_end_at';
            $params[':period_end_at'] = $periodEndAt;
        }

        $queryText = trim((string) ($filters['q'] ?? ''));
        if ($queryText !== '') {
            $params[':q'] = '%' . $queryText . '%';
            if ($this->hasCouponCodeColumn()) {
                $whereParts[] = '(c.title LIKE :q OR cp.coupon_code LIKE :q)';
            } else {
                $whereParts[] = 'c.title LIKE :q';
            }
        }

        $courseId = (int) ($filters['course_id'] ?? 0);
        if ($courseId > 0) {
            $whereParts[] = 'c.id = :course_id';
            $params[':course_id'] = $courseId;
        }

        $state = strtolower(trim((string) ($filters['state'] ?? 'all')));
        if ($state === 'active') {
            $whereParts[] = 'cp.promo_start_at <= NOW() AND cp.promo_end_at > NOW()';
        } elseif ($state === 'scheduled') {
            $whereParts[] = 'cp.promo_start_at > NOW()';
        } elseif ($state === 'ended') {
            $whereParts[] = 'cp.promo_end_at <= NOW()';
        }

        $whereSql = implode(' AND ', $whereParts);

        $countStmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM course_promotions cp
             JOIN courses c ON c.id = cp.course_id
             WHERE ' . $whereSql
        );
        foreach ($params as $key => $value) {
            if (in_array($key, [':tid', ':course_id'], true)) {
                $countStmt->bindValue($key, (int) $value, \PDO::PARAM_INT);
            } else {
                $countStmt->bindValue($key, (string) $value, \PDO::PARAM_STR);
            }
        }
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $safePerPage = max(1, min(100, $perPage));
        $totalPages = max(1, (int) ceil(($total > 0 ? $total : 1) / $safePerPage));
        $safePage = max(1, min($totalPages, $page));
        $offset = ($safePage - 1) * $safePerPage;

        $dataStmt = $this->pdo->prepare(
            'SELECT cp.*, c.title, c.price_xof, c.status
             FROM course_promotions cp
             JOIN courses c ON c.id = cp.course_id
             WHERE ' . $whereSql . '
             ORDER BY cp.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            if (in_array($key, [':tid', ':course_id'], true)) {
                $dataStmt->bindValue($key, (int) $value, \PDO::PARAM_INT);
            } else {
                $dataStmt->bindValue($key, (string) $value, \PDO::PARAM_STR);
            }
        }
        $dataStmt->bindValue(':limit', $safePerPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();

        return [
            'rows' => $dataStmt->fetchAll() ?: [],
            'total' => $total,
            'page' => $safePage,
            'per_page' => $safePerPage,
            'total_pages' => $totalPages,
        ];
    }

    public function hasCouponModeSupport(): bool
    {
        return $this->hasCouponSchema();
    }

    private function hasOrderItemCouponColumns(): bool
    {
        return $this->columnExists('order_items', 'promo_scope')
            && $this->columnExists('order_items', 'coupon_code');
    }

    private function hasOrderItemPromoColumns(): bool
    {
        return $this->columnExists('order_items', 'promo_scope')
            && $this->columnExists('order_items', 'promotion_id')
            && $this->columnExists('order_items', 'coupon_code');
    }

    private function hasCouponTrackingSchema(): bool
    {
        return $this->hasCouponSchema() && $this->hasOrderItemPromoColumns();
    }

    /**
     * @param array<string,mixed> $params
     */
    private function appendPaidDateFilters(string $orderAlias, ?string $paidFrom, ?string $paidTo, array &$params): string
    {
        $sql = '';
        if ($paidFrom !== null && $paidFrom !== '') {
            $sql .= " AND {$orderAlias}.updated_at >= :paid_from";
            $params[':paid_from'] = $paidFrom;
        }
        if ($paidTo !== null && $paidTo !== '') {
            $sql .= " AND {$orderAlias}.updated_at <= :paid_to";
            $params[':paid_to'] = $paidTo;
        }
        return $sql;
    }

    /**
     * @return array{uses_count:int,codes_count:int,revenue_xof:int,last_paid_at:?string}
     */
    public function fetchCouponUsageSummaryForTeacher(int $teacherId, ?string $paidFrom = null, ?string $paidTo = null): array
    {
        if ($teacherId < 1 || !$this->hasOrderItemCouponColumns()) {
            return [
                'uses_count' => 0,
                'codes_count' => 0,
                'revenue_xof' => 0,
                'last_paid_at' => null,
            ];
        }

        $params = [':tid' => $teacherId];
        $dateFilterSql = $this->appendPaidDateFilters('o', $paidFrom, $paidTo, $params);
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS uses_count,
                COUNT(DISTINCT oi.coupon_code) AS codes_count,
                COALESCE(SUM(oi.unit_price_xof), 0) AS revenue_xof,
                MAX(o.updated_at) AS last_paid_at
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN courses c ON c.id = oi.course_id
             WHERE c.teacher_id = :tid
               AND o.status = "paid"
               AND oi.promo_scope = "coupon"
               AND oi.coupon_code IS NOT NULL
               AND oi.coupon_code <> ""'
               . $dateFilterSql
        );
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'uses_count' => (int) ($row['uses_count'] ?? 0),
            'codes_count' => (int) ($row['codes_count'] ?? 0),
            'revenue_xof' => (int) ($row['revenue_xof'] ?? 0),
            'last_paid_at' => isset($row['last_paid_at']) ? (string) $row['last_paid_at'] : null,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCouponUsageRowsForTeacher(int $teacherId, int $limit = 30, ?string $paidFrom = null, ?string $paidTo = null): array
    {
        if ($teacherId < 1 || !$this->hasOrderItemCouponColumns()) {
            return [];
        }

        $safeLimit = max(1, min(200, $limit));
        $params = [':tid' => $teacherId];
        $dateFilterSql = $this->appendPaidDateFilters('o', $paidFrom, $paidTo, $params);
        $stmt = $this->pdo->prepare(
            'SELECT
                oi.coupon_code,
                oi.unit_price_xof,
                o.reference AS order_ref,
                o.updated_at AS paid_at,
                c.id AS course_id,
                c.title AS course_title,
                u.id AS buyer_id,
                u.first_name AS buyer_first_name,
                u.last_name AS buyer_last_name,
                u.email AS buyer_email
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN courses c ON c.id = oi.course_id
             JOIN users u ON u.id = o.user_id
             WHERE c.teacher_id = :tid
               AND o.status = "paid"
               AND oi.promo_scope = "coupon"
               AND oi.coupon_code IS NOT NULL
               AND oi.coupon_code <> ""'
               . $dateFilterSql . '
             ORDER BY o.updated_at DESC, oi.id DESC
             LIMIT :lim'
        );
        foreach ($params as $key => $value) {
            if ($key === ':tid') {
                $stmt->bindValue($key, (int) $value, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, (string) $value, \PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':lim', $safeLimit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchExpiringOffersForTeacher(int $teacherId, int $days = 7, ?string $promoMode = null): array
    {
        if ($teacherId < 1) {
            return [];
        }

        $safeDays = max(1, min(90, $days));
        $params = [':tid' => $teacherId];
        $modeFilter = '';
        $extraColumns = '';
        if ($this->hasPromoModeColumn()) {
            $extraColumns .= ', cp.promo_mode';
            $normalizedMode = strtolower(trim((string) $promoMode));
            if (in_array($normalizedMode, ['coupon', 'promotion'], true)) {
                $modeFilter = ' AND cp.promo_mode = :pmode';
                $params[':pmode'] = $normalizedMode;
            }
        }
        if ($this->hasCouponCodeColumn()) {
            $extraColumns .= ', cp.coupon_code';
        }

        $stmt = $this->pdo->prepare(
            'SELECT cp.id, cp.promo_end_at, c.id AS course_id, c.title AS course_title'
             . $extraColumns . '
             FROM course_promotions cp
             JOIN courses c ON c.id = cp.course_id
             WHERE c.teacher_id = :tid
               AND cp.deleted_at IS NULL
               AND cp.promo_end_at > NOW()
               AND cp.promo_end_at <= DATE_ADD(NOW(), INTERVAL :days DAY)'
             . $modeFilter . '
             ORDER BY cp.promo_end_at ASC
             LIMIT 20'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->bindValue(':days', $safeDays, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchExpiringCouponsForTeacher(int $teacherId, int $days = 7): array
    {
        if (!$this->hasCouponSchema()) {
            return [];
        }

        return $this->fetchExpiringOffersForTeacher($teacherId, $days, 'coupon');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCouponPerformanceForTeacher(int $teacherId, int $limit = 50, ?string $paidFrom = null, ?string $paidTo = null): array
    {
        if ($teacherId < 1 || !$this->hasCouponTrackingSchema()) {
            return [];
        }

        $safeLimit = max(1, min(200, $limit));
        $params = [':tid' => $teacherId];
        $usageDateFilterSql = $this->appendPaidDateFilters('o', $paidFrom, $paidTo, $params);

        $sql = 'SELECT
                cp.id,
                cp.coupon_code,
                cp.promo_end_at,
                c.id AS course_id,
                c.title AS course_title,
                COALESCE(coupon_usage.uses_count, 0) AS uses_count,
                COALESCE(coupon_usage.revenue_xof, 0) AS revenue_xof,
                coupon_usage.last_used_at,
                COALESCE(user_targets.target_count, 0) AS user_target_count,
                COALESCE(company_targets.seat_count, 0) AS company_target_count
            FROM course_promotions cp
            JOIN courses c ON c.id = cp.course_id
            LEFT JOIN (
                SELECT
                    oi.promotion_id,
                    COUNT(*) AS uses_count,
                    COALESCE(SUM(oi.unit_price_xof), 0) AS revenue_xof,
                    MAX(o.updated_at) AS last_used_at
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.status = "paid"
                  AND oi.promo_scope = "coupon"
                  AND oi.promotion_id IS NOT NULL'
                  . $usageDateFilterSql . '
                GROUP BY oi.promotion_id
            ) AS coupon_usage ON coupon_usage.promotion_id = cp.id
            LEFT JOIN (
                SELECT promotion_id, COUNT(*) AS target_count
                FROM course_promotion_targets
                GROUP BY promotion_id
            ) AS user_targets ON user_targets.promotion_id = cp.id
            LEFT JOIN (
                SELECT promotion_id, COALESCE(SUM(seat_limit), 0) AS seat_count
                FROM course_promotion_company_targets
                GROUP BY promotion_id
            ) AS company_targets ON company_targets.promotion_id = cp.id
            WHERE c.teacher_id = :tid
              AND cp.deleted_at IS NULL
              AND cp.promo_mode = "coupon"
            ORDER BY COALESCE(coupon_usage.uses_count, 0) DESC, cp.promo_end_at ASC
            LIMIT :lim';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':tid') {
                $stmt->bindValue($key, (int) $value, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, (string) $value, \PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':lim', $safeLimit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array{
     *   coupon:array<string,int>,
     *   promo:array<string,int>
     * }
     */
    public function fetchPromoComparisonForTeacher(int $teacherId, ?string $paidFrom = null, ?string $paidTo = null): array
    {
        $empty = [
            'coupon' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0],
            'promo' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0],
        ];
        if ($teacherId < 1 || !$this->hasOrderItemPromoColumns()) {
            return $empty;
        }

        $params = [':tid' => $teacherId];
        $dateFilterSql = $this->appendPaidDateFilters('o', $paidFrom, $paidTo, $params);

        $stmt = $this->pdo->prepare(
            'SELECT
                CASE
                    WHEN oi.promo_scope = "coupon" THEN "coupon"
                    WHEN oi.promo_scope IN ("promotion","targeted","company") THEN "promo"
                    ELSE "other"
                END AS scope_group,
                COUNT(*) AS inscriptions,
                COALESCE(SUM(oi.unit_price_xof), 0) AS revenue_xof,
                COUNT(DISTINCT oi.order_id) AS orders_count
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN courses c ON c.id = oi.course_id
             WHERE c.teacher_id = :tid
               AND o.status = "paid"
               AND oi.promo_scope IN ("coupon","promotion","targeted","company")'
               . $dateFilterSql . '
             GROUP BY scope_group'
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $result = $empty;
        foreach ($rows as $row) {
            $group = (string) ($row['scope_group'] ?? '');
            if (!isset($result[$group])) {
                continue;
            }
            $result[$group] = [
                'revenue_xof' => (int) ($row['revenue_xof'] ?? 0),
                'inscriptions' => (int) ($row['inscriptions'] ?? 0),
                'orders_count' => (int) ($row['orders_count'] ?? 0),
            ];
        }
        return $result;
    }

    /**
     * @param array<int,int> $promotionIds
     * @return array<int,array<string,mixed>>
     */
    public function findPromotionTargetEmails(array $promotionIds): array
    {
        if ($promotionIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($promotionIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT cpt.promotion_id, u.email
             FROM course_promotion_targets cpt
             JOIN users u ON u.id = cpt.user_id
             WHERE cpt.promotion_id IN ({$placeholders})
             ORDER BY u.email"
        );
        $stmt->execute($promotionIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $promotionIds
     * @return array<int,array<string,mixed>>
     */
    public function findPromotionCompanyTargets(array $promotionIds): array
    {
        if ($promotionIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($promotionIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT cpc.promotion_id, cpc.company_id, cpc.seat_limit, c.name AS company_name
             FROM course_promotion_company_targets cpc
             JOIN companies c ON c.id = cpc.company_id
             WHERE cpc.promotion_id IN ({$placeholders})"
        );
        $stmt->execute($promotionIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchTeacherPromoTargets(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email,
                    COUNT(DISTINCT e.course_id) AS course_count,
                    MAX(e.created_at) AS last_at
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             JOIN users u ON u.id = e.user_id
             WHERE c.teacher_id = :tid
               AND e.status = "active"
             GROUP BY u.id, u.first_name, u.last_name, u.email
             ORDER BY last_at DESC'
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchTeacherPromoCompanies(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.name,
                    COUNT(DISTINCT cm.user_id) AS member_count,
                    MAX(e.created_at) AS last_at
             FROM companies c
             JOIN company_members cm ON cm.company_id = c.id AND cm.status = "active"
             JOIN enrollments e ON e.user_id = cm.user_id AND e.status = "active"
             JOIN courses co ON co.id = e.course_id
             WHERE co.teacher_id = :tid
               AND c.status = "active"
             GROUP BY c.id, c.name
             ORDER BY last_at DESC'
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,int>
     */
    public function findCompanyMemberIds(int $companyId): array
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM company_members WHERE company_id = :cid AND status = "active"');
        $stmt->execute([':cid' => $companyId]);
        $rows = $stmt->fetchAll() ?: [];
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['user_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    public function insertNotification(int $userId, string $title, string $body, ?string $url): void
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
}
