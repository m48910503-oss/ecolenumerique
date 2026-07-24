<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;

use App\Models\SharedPromotionModel;
use Throwable;

final class PromotionsService extends Service
{
    private SharedPromotionModel $model;
    private CompanySchemaService $companies;

    public function __construct(SharedPromotionModel $model, CompanySchemaService $companies)
    {
        $this->model = $model;
        $this->companies = $companies;
    }

    public function ensureCoursesShareTokenColumn(): bool
    {
        try {
            return $this->model->hasCoursesShareTokenColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCourseShareToken(int $courseId): ?string
    {
        if ($courseId < 1 || !$this->ensureCoursesShareTokenColumn()) {
            return null;
        }
        try {
            $existing = $this->model->findCourseShareToken($courseId);
            if ($existing !== null) {
                return $existing;
            }
        } catch (Throwable $e) {
            return null;
        }

        $prefix = base_convert((string) $courseId, 10, 36);
        for ($i = 0; $i < 8; $i++) {
            $token = $prefix . '@' . $this->generateShareToken(30);
            try {
                if ($this->model->updateCourseShareTokenIfMissing($courseId, $token)) {
                    return $token;
                }
                $existing = $this->model->findCourseShareToken($courseId);
                if ($existing !== null) {
                    return $existing;
                }
            } catch (Throwable $e) {
                // Retry on duplicate or transient SQL issues.
            }
        }
        return null;
    }

    public function ensureCoursesPromoColumns(): bool
    {
        try {
            return $this->model->ensureCoursesPromoColumns() && $this->model->hasCoursesPromoColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCoursesReferralSettingsColumns(): bool
    {
        try {
            return $this->model->ensureCoursesReferralSettingsColumns() && $this->model->hasCoursesReferralOverrideColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCoursePromotionsTable(): bool
    {
        try {
            return $this->model->ensureCoursePromotionsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCoursePromotionsCouponColumns(): bool
    {
        try {
            return $this->model->hasCoursePromotionsCouponColumns();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCoursePromotionTargetsTable(): bool
    {
        try {
            return $this->model->ensureCoursePromotionTargetsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCoursePromotionCompanyTargetsTable(): bool
    {
        if (!$this->companies->ensureCompaniesTable()) {
            return false;
        }
        try {
            return $this->model->ensureCoursePromotionCompanyTargetsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCoursePromotionCompanyUsesTable(): bool
    {
        if (!$this->companies->ensureCompaniesTable()) {
            return false;
        }
        try {
            return $this->model->ensureCoursePromotionCompanyUsesTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{targetsTable:bool,companyTargetsTable:bool,couponColumns:bool}
     */
    public function ensurePromotionSchema(): array
    {
        $this->ensureCoursesPromoColumns();
        $this->ensureCoursePromotionsTable();
        $couponColumns = $this->ensureCoursePromotionsCouponColumns();
        $targetsTable = $this->ensureCoursePromotionTargetsTable();
        $companyTargetsTable = $this->ensureCoursePromotionCompanyTargetsTable();
        $this->ensureCoursePromotionCompanyUsesTable();
        $this->companies->ensureCoreSchema();
        return [
            'targetsTable' => $targetsTable,
            'companyTargetsTable' => $companyTargetsTable,
            'couponColumns' => $couponColumns,
        ];
    }

    public function getCourseActivePromotionForUser(int $courseId, int $userId): ?array
    {
        if ($courseId < 1 || $userId < 1) {
            return null;
        }
        if (!$this->ensureCoursePromotionsTable() || !$this->ensureCoursePromotionTargetsTable()) {
            return null;
        }
        $hasCouponColumns = $this->ensureCoursePromotionsCouponColumns();
        try {
            return $this->model->findActivePromotionForUser($courseId, $userId, $hasCouponColumns);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getCourseActivePromotionForCompany(int $courseId, int $companyId, int $userId): ?array
    {
        if ($courseId < 1 || $companyId < 1) {
            return null;
        }
        if (
            !$this->ensureCoursePromotionsTable()
            || !$this->ensureCoursePromotionCompanyTargetsTable()
            || !$this->ensureCoursePromotionCompanyUsesTable()
        ) {
            return null;
        }
        $hasCouponColumns = $this->ensureCoursePromotionsCouponColumns();
        try {
            $row = $this->model->findActivePromotionForCompany($courseId, $companyId, $hasCouponColumns);
            if (!$row) {
                return null;
            }
            $promoId = (int) ($row['id'] ?? 0);
            $seatUsed = 0;
            $userUsed = 0;
            if ($promoId > 0) {
                $seatUsed = $this->model->countCompanyPromotionUses($promoId, $companyId);
                if ($userId > 0) {
                    $userUsed = $this->model->hasCompanyPromotionUseByUser($promoId, $companyId, $userId) ? 1 : 0;
                }
            }
            $row['seat_used'] = $seatUsed;
            $row['user_used'] = $userUsed;
            return $row;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function getCourseActivePromotion(int $courseId): ?array
    {
        if ($courseId < 1 || !$this->ensureCoursePromotionsTable()) {
            return null;
        }
        $hasTargets = $this->ensureCoursePromotionTargetsTable();
        $hasCompanyTargets = $this->ensureCoursePromotionCompanyTargetsTable();
        $hasCouponColumns = $this->ensureCoursePromotionsCouponColumns();
        try {
            return $this->model->findActivePublicPromotion($courseId, $hasTargets, $hasCompanyTargets, $hasCouponColumns);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function syncCourseActivePromo(int $courseId): bool
    {
        if ($courseId < 1 || !$this->ensureCoursesPromoColumns()) {
            return false;
        }
        try {
            $promo = $this->getCourseActivePromotion($courseId);
            if (!$promo) {
                $this->model->clearCoursePromoFields($courseId);
                return true;
            }
            $this->model->updateCoursePromoFields($courseId, $promo);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<string,mixed> $course
     * @return array{
     *   active:bool,
     *   price_xof:int,
     *   base_price_xof:int,
     *   promo_type:string,
     *   promo_value:float,
     *   start_at:mixed,
     *   end_at:mixed,
     *   end_ts:int,
     *   promo_scope:string,
     *   promo_targeted:bool,
     *   promo_id:mixed,
     *   promo_company_id:mixed,
     *   promo_seat_limit:mixed,
     *   promo_seat_used:mixed,
     *   promo_user_used:mixed
     * }
     */
    public function coursePromoData(array $course, ?int $nowTs = null, ?int $userId = null): array
    {
        $now = $nowTs ?? time();
        $basePrice = (int) ($course['price_xof'] ?? 0);
        $promoScope = 'public';
        $promoId = null;
        $promoCompanyId = null;
        $promoSeatLimit = null;
        $promoSeatUsed = null;
        $promoUserUsed = null;
        $type = (string) ($course['promo_type'] ?? '');
        $rawValue = $course['promo_value'] ?? null;
        $startAt = $course['promo_start_at'] ?? null;
        $endAt = $course['promo_end_at'] ?? null;
        $courseId = (int) ($course['id'] ?? 0);

        if ($userId && $courseId > 0) {
            $targetPromo = $this->getCourseActivePromotionForUser($courseId, $userId);
            if ($targetPromo) {
                $promoScope = 'targeted';
                $promoId = $targetPromo['id'] ?? null;
                $type = (string) ($targetPromo['promo_type'] ?? '');
                $rawValue = $targetPromo['promo_value'] ?? null;
                $startAt = $targetPromo['promo_start_at'] ?? null;
                $endAt = $targetPromo['promo_end_at'] ?? null;
            }
        }

        if ($promoScope === 'public' && $userId && $courseId > 0) {
            $companyId = $this->companies->getUserCompanyId($userId);
            if ($companyId) {
                $companyPromo = $this->getCourseActivePromotionForCompany($courseId, $companyId, $userId);
                if ($companyPromo) {
                    $seatLimit = (int) ($companyPromo['seat_limit'] ?? 0);
                    $seatUsed = (int) ($companyPromo['seat_used'] ?? 0);
                    $userUsed = !empty($companyPromo['user_used']);
                    if ($seatLimit > 0 && ($seatUsed < $seatLimit || $userUsed)) {
                        $promoScope = 'company';
                        $promoId = $companyPromo['id'] ?? null;
                        $promoCompanyId = (int) $companyId;
                        $promoSeatLimit = $seatLimit;
                        $promoSeatUsed = $seatUsed;
                        $promoUserUsed = $userUsed ? 1 : 0;
                        $type = (string) ($companyPromo['promo_type'] ?? '');
                        $rawValue = $companyPromo['promo_value'] ?? null;
                        $startAt = $companyPromo['promo_start_at'] ?? null;
                        $endAt = $companyPromo['promo_end_at'] ?? null;
                    }
                }
            }
        }

        $value = is_numeric($rawValue) ? (float) $rawValue : 0.0;
        $startTs = $startAt ? (int) strtotime((string) $startAt) : 0;
        $endTs = $endAt ? (int) strtotime((string) $endAt) : 0;
        $hasValue = false;
        if ($type === 'amount') {
            $hasValue = is_numeric($rawValue) && $value >= 0;
        } elseif ($type === 'percent') {
            $hasValue = is_numeric($rawValue) && $value > 0;
        }

        $active = false;
        if ($type !== '' && $hasValue && $endTs > $now) {
            if ($startTs === 0 || $startTs <= $now) {
                $active = true;
            }
        }

        $promoPrice = $basePrice;
        if ($active && $basePrice > 0) {
            if ($type === 'amount') {
                $promoPrice = (int) round($value);
            } elseif ($type === 'percent') {
                $promoPrice = (int) round($basePrice * (1 - ($value / 100)));
            }
            if ($promoPrice < 0) {
                $promoPrice = 0;
            }
        }
        if (!$active || $basePrice <= 0 || $promoPrice >= $basePrice) {
            $promoPrice = $basePrice;
            $active = false;
        }

        return [
            'active' => $active,
            'price_xof' => $promoPrice,
            'base_price_xof' => $basePrice,
            'promo_type' => $type,
            'promo_value' => $value,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'end_ts' => $endTs,
            'promo_scope' => $promoScope,
            'promo_targeted' => $promoScope === 'targeted',
            'promo_id' => $promoId,
            'promo_company_id' => $promoCompanyId,
            'promo_seat_limit' => $promoSeatLimit,
            'promo_seat_used' => $promoSeatUsed,
            'promo_user_used' => $promoUserUsed,
        ];
    }

    /**
     * @param array<int,int> $courseIds
     */
    public function resolveCouponForUser(string $couponCode, int $userId, array $courseIds): ?array
    {
        $inspection = $this->inspectCouponForUser($couponCode, $userId, $courseIds);
        if (($inspection['status'] ?? '') !== 'valid') {
            return null;
        }
        return is_array($inspection['coupon'] ?? null) ? $inspection['coupon'] : null;
    }

    /**
     * @param array<int,int> $courseIds
     * @return array{
     *   status:string,
     *   coupon:?array<string,mixed>
     * }
     */
    public function inspectCouponForUser(string $couponCode, int $userId, array $courseIds): array
    {
        $normalizedCode = self::normalizeCouponCode($couponCode);
        if ($normalizedCode === '') {
            return ['status' => 'empty_code', 'coupon' => null];
        }
        if ($userId < 1) {
            return ['status' => 'unauthenticated', 'coupon' => null];
        }
        if ($courseIds === []) {
            return ['status' => 'empty_cart', 'coupon' => null];
        }
        if (!$this->ensureCoursePromotionsCouponColumns() || !$this->ensureCoursePromotionsTable()) {
            return ['status' => 'schema_unavailable', 'coupon' => null];
        }

        $row = $this->model->findCouponByCode($normalizedCode);
        if (!$row) {
            return ['status' => 'not_found', 'coupon' => null];
        }

        if (!empty($row['deleted_at'])) {
            return ['status' => 'inactive', 'coupon' => null];
        }

        $couponCourseId = (int) ($row['course_id'] ?? 0);
        if ($couponCourseId < 1 || !in_array($couponCourseId, $courseIds, true)) {
            return ['status' => 'course_mismatch', 'coupon' => null];
        }

        $now = time();
        $startTs = !empty($row['promo_start_at']) ? (int) strtotime((string) $row['promo_start_at']) : 0;
        $endTs = !empty($row['promo_end_at']) ? (int) strtotime((string) $row['promo_end_at']) : 0;
        if ($startTs > 0 && $startTs > $now) {
            return ['status' => 'not_started', 'coupon' => null];
        }
        if ($endTs > 0 && $endTs <= $now) {
            return ['status' => 'expired', 'coupon' => null];
        }

        $promotionId = (int) ($row['id'] ?? 0);
        if ($promotionId < 1) {
            return ['status' => 'invalid_coupon', 'coupon' => null];
        }

        $hasTargetsTable = $this->ensureCoursePromotionTargetsTable();
        $hasCompanyTargetsTable = $this->ensureCoursePromotionCompanyTargetsTable();

        if ($hasCompanyTargetsTable) {
            $companyTarget = $this->model->findPromotionCompanyTarget($promotionId);
            if (is_array($companyTarget)) {
                return ['status' => 'company_only', 'coupon' => null];
            }
        }

        $targeted = false;
        if ($hasTargetsTable && $this->model->hasPromotionTargetRows($promotionId)) {
            $targeted = true;
            if (!$this->model->hasPromotionTargetUser($promotionId, $userId)) {
                return ['status' => 'not_eligible', 'coupon' => null];
            }
        }

        $row['promo_scope'] = $targeted ? 'targeted' : 'public';
        $row['coupon_code'] = $normalizedCode;
        return ['status' => 'valid', 'coupon' => $row];
    }

    public static function normalizeCouponCode(string $raw): string
    {
        $value = strtoupper(trim($raw));
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/[^A-Z0-9-]/', '', $value);
        $value = trim((string) $value, '-');
        if ($value === '') {
            return '';
        }
        if (strlen($value) > 32) {
            $value = substr($value, 0, 32);
        }
        return $value;
    }

    private function generateShareToken(int $bytes = 32): string
    {
        $raw = random_bytes(max(8, $bytes));
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

