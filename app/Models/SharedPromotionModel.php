<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class SharedPromotionModel extends Model
{
    private function hasPromoModeColumn(): bool
    {
        return $this->columnExists('course_promotions', 'promo_mode');
    }

    private function hasCouponCodeColumn(): bool
    {
        return $this->columnExists('course_promotions', 'coupon_code');
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->columnExists($table, $column);
    }

    public function hasCoursesShareTokenColumn(): bool
    {
        return $this->hasColumn('courses', 'share_token');
    }

    public function addCoursesShareTokenColumn(): bool
    {
        return $this->hasCoursesShareTokenColumn();
    }

    public function findCourseShareToken(int $courseId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT share_token FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $courseId]);
        $value = $stmt->fetchColumn();
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    public function updateCourseShareTokenIfMissing(int $courseId, string $token): bool
    {
        $stmt = $this->pdo->prepare('UPDATE courses
            SET share_token = :tok
            WHERE id = :id AND (share_token IS NULL OR share_token = "")');
        $stmt->execute([
            ':tok' => $token,
            ':id' => $courseId,
        ]);
        return $stmt->rowCount() === 1;
    }

    public function ensureCoursesPromoColumns(): bool
    {
        $columns = [
            'promo_type' => "ENUM('amount','percent') NULL",
            'promo_value' => 'DECIMAL(10,2) NULL',
            'promo_start_at' => 'DATETIME NULL',
            'promo_end_at' => 'DATETIME NULL',
        ];
        $allPresent = true;
        foreach ($columns as $name => $definition) {
            if (!$this->hasColumn('courses', $name)) {
                $allPresent = false;
            }
        }
        return $allPresent;
    }

    public function hasCoursesPromoColumn(): bool
    {
        return $this->hasColumn('courses', 'promo_type');
    }

    public function ensureCoursesReferralSettingsColumns(): bool
    {
        $columns = [
            'referral_override' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'referral_l1_rate' => 'DECIMAL(5,2) NULL',
            'referral_l2_rate' => 'DECIMAL(5,2) NULL',
            'referral_l2_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
        ];
        $allPresent = true;
        foreach ($columns as $name => $definition) {
            if (!$this->hasColumn('courses', $name)) {
                $allPresent = false;
            }
        }
        return $allPresent;
    }

    public function hasCoursesReferralOverrideColumn(): bool
    {
        return $this->hasColumn('courses', 'referral_override');
    }

    public function ensureCoursePromotionsTable(): bool
    {
        if (!$this->tableExists('course_promotions')) {
            return false;
        }

        return $this->ensureCoursePromotionsAutoIncrementId();
    }

    public function hasCoursePromotionsCouponColumns(): bool
    {
        if (!$this->ensureCoursePromotionsTable()) {
            return false;
        }
        return $this->hasPromoModeColumn() && $this->hasCouponCodeColumn();
    }

    public function ensureCoursePromotionTargetsTable(): bool
    {
        return $this->tableExists('course_promotion_targets');
    }

    public function ensureCoursePromotionCompanyTargetsTable(): bool
    {
        return $this->tableExists('course_promotion_company_targets');
    }

    public function ensureCoursePromotionCompanyUsesTable(): bool
    {
        return $this->tableExists('course_promotion_company_uses');
    }

    private function ensureCoursePromotionsAutoIncrementId(): bool
    {
        if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return true;
        }

        $stmt = $this->pdo->prepare(
            'SELECT EXTRA
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column
             LIMIT 1'
        );
        $stmt->execute([
            ':table' => 'course_promotions',
            ':column' => 'id',
        ]);
        $extra = strtolower(trim((string) $stmt->fetchColumn()));
        if (strpos($extra, 'auto_increment') !== false) {
            return true;
        }

        try {
            $this->repairLegacyCoursePromotionZeroId();
            $this->pdo->exec(
                'ALTER TABLE course_promotions
                 MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        } catch (\Throwable $e) {
            return false;
        }

        $stmt->execute([
            ':table' => 'course_promotions',
            ':column' => 'id',
        ]);
        $extra = strtolower(trim((string) $stmt->fetchColumn()));
        return strpos($extra, 'auto_increment') !== false;
    }

    private function repairLegacyCoursePromotionZeroId(): void
    {
        $legacyRow = $this->pdo->query('SELECT id FROM course_promotions WHERE id = 0 LIMIT 1')->fetchColumn();
        if ($legacyRow === false) {
            return;
        }

        $nextId = (int) $this->pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM course_promotions')->fetchColumn();
        if ($nextId < 1) {
            $nextId = 1;
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $linkTables = [
                ['table' => 'course_promotion_targets', 'column' => 'promotion_id'],
                ['table' => 'course_promotion_company_targets', 'column' => 'promotion_id'],
                ['table' => 'course_promotion_company_uses', 'column' => 'promotion_id'],
                ['table' => 'order_items', 'column' => 'promotion_id'],
            ];

            foreach ($linkTables as $link) {
                $table = (string) ($link['table'] ?? '');
                $column = (string) ($link['column'] ?? '');
                if ($table === '' || $column === '') {
                    continue;
                }
                if (!$this->tableExists($table) || !$this->columnExists($table, $column)) {
                    continue;
                }
                $stmt = $this->pdo->prepare("UPDATE {$table} SET {$column} = :new_id WHERE {$column} = 0");
                $stmt->execute([':new_id' => $nextId]);
            }

            $stmt = $this->pdo->prepare('UPDATE course_promotions SET id = :new_id WHERE id = 0');
            $stmt->execute([':new_id' => $nextId]);
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function findActivePromotionForUser(int $courseId, int $userId, bool $hasCouponColumns): ?array
    {
        $modeFilter = $hasCouponColumns ? ' AND cp.promo_mode = "promotion"' : '';
        $stmt = $this->pdo->prepare('SELECT cp.id, cp.promo_type, cp.promo_value, cp.promo_start_at, cp.promo_end_at
            FROM course_promotions cp
            JOIN course_promotion_targets cpt ON cpt.promotion_id = cp.id
            WHERE cp.course_id = :cid
              AND cp.deleted_at IS NULL
              AND cpt.user_id = :uid
              AND cp.promo_start_at <= NOW()
              AND cp.promo_end_at > NOW()'
            . $modeFilter . '
            ORDER BY cp.promo_start_at DESC, cp.id DESC
            LIMIT 1');
        $stmt->execute([
            ':cid' => $courseId,
            ':uid' => $userId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActivePromotionForCompany(int $courseId, int $companyId, bool $hasCouponColumns): ?array
    {
        $modeFilter = $hasCouponColumns ? ' AND cp.promo_mode = "promotion"' : '';
        $stmt = $this->pdo->prepare('SELECT cp.id, cp.promo_type, cp.promo_value, cp.promo_start_at, cp.promo_end_at, pct.seat_limit
            FROM course_promotions cp
            JOIN course_promotion_company_targets pct ON pct.promotion_id = cp.id
            WHERE cp.course_id = :cid
              AND cp.deleted_at IS NULL
              AND pct.company_id = :coid
              AND cp.promo_start_at <= NOW()
              AND cp.promo_end_at > NOW()'
            . $modeFilter . '
            ORDER BY cp.promo_start_at DESC, cp.id DESC
            LIMIT 1');
        $stmt->execute([
            ':cid' => $courseId,
            ':coid' => $companyId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countCompanyPromotionUses(int $promotionId, int $companyId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*)
            FROM course_promotion_company_uses
            WHERE promotion_id = :pid AND company_id = :cid');
        $stmt->execute([
            ':pid' => $promotionId,
            ':cid' => $companyId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function hasCompanyPromotionUseByUser(int $promotionId, int $companyId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1
            FROM course_promotion_company_uses
            WHERE promotion_id = :pid AND company_id = :cid AND user_id = :uid
            LIMIT 1');
        $stmt->execute([
            ':pid' => $promotionId,
            ':cid' => $companyId,
            ':uid' => $userId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function findActivePublicPromotion(
        int $courseId,
        bool $hasTargetsTable,
        bool $hasCompanyTargetsTable,
        bool $hasCouponColumns
    ): ?array {
        $filters = [];
        if ($hasCouponColumns) {
            $filters[] = 'promo_mode = "promotion"';
        }
        if ($hasTargetsTable) {
            $filters[] = 'NOT EXISTS (SELECT 1 FROM course_promotion_targets cpt WHERE cpt.promotion_id = course_promotions.id)';
        }
        if ($hasCompanyTargetsTable) {
            $filters[] = 'NOT EXISTS (SELECT 1 FROM course_promotion_company_targets cpc WHERE cpc.promotion_id = course_promotions.id)';
        }
        $targetFilter = $filters !== [] ? (' AND ' . implode(' AND ', $filters)) : '';
        $stmt = $this->pdo->prepare('SELECT promo_type, promo_value, promo_start_at, promo_end_at
            FROM course_promotions
            WHERE course_id = :cid
              AND deleted_at IS NULL
              AND promo_start_at <= NOW()
              AND promo_end_at > NOW()'
            . $targetFilter . '
            ORDER BY promo_start_at DESC, id DESC
            LIMIT 1');
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @param array<int,int> $courseIds
     */
    public function findActiveCouponByCode(string $couponCode, array $courseIds = []): ?array
    {
        if (!$this->hasCoursePromotionsCouponColumns() || $couponCode === '') {
            return null;
        }

        $sql = 'SELECT cp.id, cp.course_id, cp.promo_type, cp.promo_value, cp.promo_start_at, cp.promo_end_at, cp.coupon_code
            FROM course_promotions cp
            WHERE cp.deleted_at IS NULL
              AND cp.promo_mode = "coupon"
              AND cp.coupon_code = :code
              AND cp.promo_start_at <= NOW()
              AND cp.promo_end_at > NOW()';
        $params = [':code' => $couponCode];

        if ($courseIds !== []) {
            $placeholders = [];
            foreach ($courseIds as $index => $courseId) {
                $key = ':cid' . $index;
                $placeholders[] = $key;
                $params[$key] = (int) $courseId;
            }
            $sql .= ' AND cp.course_id IN (' . implode(',', $placeholders) . ')';
        }

        $sql .= ' ORDER BY cp.promo_start_at DESC, cp.id DESC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findCouponByCode(string $couponCode): ?array
    {
        if (!$this->hasCoursePromotionsCouponColumns() || $couponCode === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT cp.id, cp.course_id, cp.promo_type, cp.promo_value, cp.promo_start_at, cp.promo_end_at, cp.coupon_code, cp.deleted_at
             FROM course_promotions cp
             WHERE cp.promo_mode = "coupon"
               AND cp.coupon_code = :code
             LIMIT 1'
        );
        $stmt->execute([':code' => $couponCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function hasPromotionTargetRows(int $promotionId): bool
    {
        if ($promotionId < 1 || !$this->ensureCoursePromotionTargetsTable()) {
            return false;
        }
        $stmt = $this->pdo->prepare('SELECT 1 FROM course_promotion_targets WHERE promotion_id = :pid LIMIT 1');
        $stmt->execute([':pid' => $promotionId]);
        return (bool) $stmt->fetchColumn();
    }

    public function hasPromotionTargetUser(int $promotionId, int $userId): bool
    {
        if ($promotionId < 1 || $userId < 1 || !$this->ensureCoursePromotionTargetsTable()) {
            return false;
        }
        $stmt = $this->pdo->prepare('SELECT 1 FROM course_promotion_targets WHERE promotion_id = :pid AND user_id = :uid LIMIT 1');
        $stmt->execute([
            ':pid' => $promotionId,
            ':uid' => $userId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function findPromotionCompanyTarget(int $promotionId): ?array
    {
        if ($promotionId < 1 || !$this->ensureCoursePromotionCompanyTargetsTable()) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT company_id, seat_limit FROM course_promotion_company_targets WHERE promotion_id = :pid LIMIT 1');
        $stmt->execute([':pid' => $promotionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function clearCoursePromoFields(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses
            SET promo_type = NULL,
                promo_value = NULL,
                promo_start_at = NULL,
                promo_end_at = NULL,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([':id' => $courseId]);
    }

    public function updateCoursePromoFields(int $courseId, array $promo): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses
            SET promo_type = :ptype,
                promo_value = :pvalue,
                promo_start_at = :pstart,
                promo_end_at = :pend,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            ':ptype' => (string) ($promo['promo_type'] ?? ''),
            ':pvalue' => (float) ($promo['promo_value'] ?? 0),
            ':pstart' => (string) ($promo['promo_start_at'] ?? ''),
            ':pend' => (string) ($promo['promo_end_at'] ?? ''),
            ':id' => $courseId,
        ]);
    }
}
