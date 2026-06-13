<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;

use App\Models\TeacherPromotionsModel;
use App\Services\Shared\PromotionsService as SharedPromotionsService;

final class PromotionsService extends Service
{
    private TeacherPromotionsModel $model;
    private SharedPromotionsService $sharedPromotions;

    public function __construct(TeacherPromotionsModel $model, SharedPromotionsService $sharedPromotions)
    {
        $this->model = $model;
        $this->sharedPromotions = $sharedPromotions;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @return array{
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(
        array $user,
        bool $canTargetPromos,
        string $method,
        array $post,
        string $baseRoute = 'teacher/promotions',
        string $pageMode = 'promotions',
        array $query = []
    ): array
    {
        $schema = $this->sharedPromotions->ensurePromotionSchema();
        $isCombinedPage = $pageMode === 'all';
        $isCouponsPage = $pageMode === 'coupons';

        $teacherId = (int) ($user['id'] ?? 0);
        $errors = [];
        $success = (string) $this->flashGet('success', '');
        $promoInputs = [];
        $listingInput = $query;
        if (strtoupper($method) === 'POST') {
            foreach (['q', 'course_id', 'state', 'offer', 'size', 'pp', 'cp'] as $listField) {
                if (isset($post[$listField]) && (string) $post[$listField] !== '') {
                    $listingInput[$listField] = (string) $post[$listField];
                }
            }
        }
        $listingState = $this->resolveListingState($listingInput);

        if ($isCouponsPage && !$schema['couponColumns']) {
            $errors[] = 'Module coupon indisponible: appliquez database/schema_v44.sql puis rechargez la page.';
        }

        $promoTargetCandidates = [];
        $promoTargetEmailMap = [];
        $promoCompanyCandidates = [];
        $promoCompanyMap = [];

        if ($canTargetPromos) {
            try {
                $promoTargetCandidates = $this->model->fetchTeacherPromoTargets($teacherId);
                foreach ($promoTargetCandidates as $candidate) {
                    $email = strtolower(trim((string) ($candidate['email'] ?? '')));
                    if ($email !== '') {
                        $promoTargetEmailMap[$email] = (int) ($candidate['id'] ?? 0);
                    }
                }

                $promoCompanyCandidates = $this->model->fetchTeacherPromoCompanies($teacherId);
                foreach ($promoCompanyCandidates as $company) {
                    $companyId = (int) ($company['id'] ?? 0);
                    if ($companyId > 0) {
                        $promoCompanyMap[$companyId] = $company;
                    }
                }
            } catch (\Throwable $e) {
                // Keep the page usable if optional promo target datasets are not deploy-ready.
                $promoTargetCandidates = [];
                $promoTargetEmailMap = [];
                $promoCompanyCandidates = [];
                $promoCompanyMap = [];
            }
        }

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            $postPromoMode = strtolower(trim((string) ($post['promo_mode'] ?? '')));
            $actionIsCoupons = $isCouponsPage;
            if ($isCombinedPage) {
                if ($postPromoMode === 'coupon') {
                    $actionIsCoupons = true;
                } elseif ($postPromoMode === 'promotion') {
                    $actionIsCoupons = false;
                }
            }
            if ($action === 'create_promo') {
                $result = $this->handleCreatePromo(
                    $teacherId,
                    $canTargetPromos,
                    $post,
                    $promoTargetEmailMap,
                    $promoCompanyMap,
                    $schema,
                    $actionIsCoupons,
                    $actionIsCoupons ? 'create_coupon' : 'create_promotion'
                );
                $errors = $result['errors'];
                $success = $result['success'];
                $promoInputs = $result['promoInputs'];
            } elseif ($action === 'update_promo') {
                $result = $this->handleUpdatePromo(
                    $teacherId,
                    $canTargetPromos,
                    $post,
                    $promoTargetEmailMap,
                    $promoCompanyMap,
                    $schema,
                    $actionIsCoupons
                );
                $errors = $result['errors'];
                $success = $result['success'];
                $promoInputs = $result['promoInputs'];
            } elseif ($action === 'delete_promo') {
                $result = $this->handleDeletePromo($teacherId, $post, $actionIsCoupons);
                $errors = $result['errors'];
                $success = $result['success'];
            }
        }

        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            return [
                'redirectTo' => $this->siteUrl($baseRoute . $this->buildListingQuery($listingState)),
                'view' => [],
            ];
        }

        $courses = $this->model->findCoursesForTeacher($teacherId);
        $periodStartAt = null;
        $periodEndAt = null;
        $promotionPaged = $this->model->fetchPromotionsForTeacherPaged(
            $teacherId,
            'promotion',
            $periodStartAt,
            $periodEndAt,
            $listingState['filters'],
            $listingState['promotionPage'],
            $listingState['perPage']
        );
        $promotionRows = is_array($promotionPaged['rows'] ?? null) ? $promotionPaged['rows'] : [];
        $couponPaged = $schema['couponColumns']
            ? $this->model->fetchPromotionsForTeacherPaged(
                $teacherId,
                'coupon',
                $periodStartAt,
                $periodEndAt,
                $listingState['filters'],
                $listingState['couponPage'],
                $listingState['perPage']
            )
            : ['rows' => [], 'total' => 0, 'page' => 1, 'per_page' => $listingState['perPage'], 'total_pages' => 1];
        $couponRows = is_array($couponPaged['rows'] ?? null) ? $couponPaged['rows'] : [];
        $promotions = $isCouponsPage ? $couponRows : $promotionRows;
        $promotionTotal = (int) ($promotionPaged['total'] ?? count($promotionRows));
        $couponTotal = (int) ($couponPaged['total'] ?? count($couponRows));
        $promotionActiveCount = (int) (($this->model->fetchPromotionsForTeacherPaged(
            $teacherId,
            'promotion',
            $periodStartAt,
            $periodEndAt,
            array_merge($listingState['filters'], ['state' => 'active']),
            1,
            1
        ))['total'] ?? 0);
        $couponActiveCount = (int) (($schema['couponColumns']
            ? $this->model->fetchPromotionsForTeacherPaged(
                $teacherId,
                'coupon',
                $periodStartAt,
                $periodEndAt,
                array_merge($listingState['filters'], ['state' => 'active']),
                1,
                1
            )
            : ['total' => 0]
        )['total'] ?? 0);
        $couponUsageSummary = [
            'uses_count' => 0,
            'codes_count' => 0,
            'revenue_xof' => 0,
            'last_paid_at' => null,
        ];
        $expiringOfferRows = [];
        $couponPerformanceRows = [];
        $promoComparison = [
            'coupon' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0, 'avg_basket_xof' => 0],
            'promo' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0, 'avg_basket_xof' => 0],
        ];
        if (($isCouponsPage || $isCombinedPage) && $schema['couponColumns']) {
            $expiringOfferMode = null;
            if ($isCouponsPage) {
                $expiringOfferMode = 'coupon';
            } elseif (!$isCombinedPage) {
                $expiringOfferMode = 'promotion';
            } else {
                $selectedOffer = strtolower(trim((string) ($listingState['offer'] ?? 'all')));
                if (in_array($selectedOffer, ['coupon', 'promotion'], true)) {
                    $expiringOfferMode = $selectedOffer;
                }
            }

            try {
                $couponUsageSummary = $this->model->fetchCouponUsageSummaryForTeacher(
                    $teacherId,
                    $periodStartAt,
                    $periodEndAt
                );
                $expiringOfferRows = $this->model->fetchExpiringOffersForTeacher($teacherId, 7, $expiringOfferMode);
            } catch (\Throwable $e) {
                $couponUsageSummary = [
                    'uses_count' => 0,
                    'codes_count' => 0,
                    'revenue_xof' => 0,
                    'last_paid_at' => null,
                ];
                $expiringOfferRows = [];
                $couponPerformanceRows = [];
                $promoComparison = [
                    'coupon' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0, 'avg_basket_xof' => 0],
                    'promo' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0, 'avg_basket_xof' => 0],
                ];
            }

            try {
                $rawPerformance = $this->model->fetchCouponPerformanceForTeacher(
                    $teacherId,
                    60,
                    $periodStartAt,
                    $periodEndAt
                );
                foreach ($rawPerformance as $row) {
                    $userTargets = (int) ($row['user_target_count'] ?? 0);
                    $companyTargets = (int) ($row['company_target_count'] ?? 0);
                    $diffused = $userTargets > 0 ? $userTargets : ($companyTargets > 0 ? $companyTargets : 0);
                    $uses = (int) ($row['uses_count'] ?? 0);
                    $conversionRate = $diffused > 0
                        ? round(($uses / $diffused) * 100, 2)
                        : null;
                    $row['diffused_count'] = $diffused;
                    $row['conversion_rate'] = $conversionRate;
                    $couponPerformanceRows[] = $row;
                }
            } catch (\Throwable $e) {
                $couponPerformanceRows = [];
            }

            try {
                $promoComparison = $this->model->fetchPromoComparisonForTeacher(
                    $teacherId,
                    $periodStartAt,
                    $periodEndAt
                );
                foreach (['coupon', 'promo'] as $scopeKey) {
                    $ordersCount = (int) ($promoComparison[$scopeKey]['orders_count'] ?? 0);
                    $revenueXof = (int) ($promoComparison[$scopeKey]['revenue_xof'] ?? 0);
                    $promoComparison[$scopeKey]['avg_basket_xof'] = $ordersCount > 0
                        ? (int) round($revenueXof / $ordersCount)
                        : 0;
                }
            } catch (\Throwable $e) {
                $promoComparison = [
                    'coupon' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0, 'avg_basket_xof' => 0],
                    'promo' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0, 'avg_basket_xof' => 0],
                ];
            }
        }

        $promoTargetEmails = [];
        $promoCompanyTargets = [];
        $targetSourceRows = $promotions;
        if ($isCombinedPage) {
            $targetSourceRows = array_merge($promotionRows, $couponRows);
        }
        if ($canTargetPromos && $targetSourceRows !== []) {
            $promoIds = $this->extractPromotionIds($targetSourceRows);

            if ($schema['targetsTable'] && $promoIds !== []) {
                foreach ($this->model->findPromotionTargetEmails($promoIds) as $row) {
                    $promoId = (int) ($row['promotion_id'] ?? 0);
                    $email = (string) ($row['email'] ?? '');
                    if ($promoId > 0 && $email !== '') {
                        $promoTargetEmails[$promoId][] = $email;
                    }
                }
            }

            if ($schema['companyTargetsTable'] && $promoIds !== []) {
                foreach ($this->model->findPromotionCompanyTargets($promoIds) as $row) {
                    $promoId = (int) ($row['promotion_id'] ?? 0);
                    if ($promoId > 0) {
                        $promoCompanyTargets[$promoId] = [
                            'company_id' => (int) ($row['company_id'] ?? 0),
                            'company_name' => (string) ($row['company_name'] ?? ''),
                            'seat_limit' => (int) ($row['seat_limit'] ?? 0),
                        ];
                    }
                }
            }
        }

        $courseStatusLabels = [
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'approved' => 'Publie',
            'rejected' => 'Rejete',
        ];

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'success' => $success,
                'courses' => $courses,
                'promotions' => $promotions,
                'promotionRows' => $promotionRows,
                'couponRows' => $couponRows,
                'promotionTotal' => $promotionTotal,
                'couponTotal' => $couponTotal,
                'promotionActiveCount' => $promotionActiveCount,
                'couponActiveCount' => $couponActiveCount,
                'promoInputs' => $promoInputs,
                'canTargetPromos' => $canTargetPromos,
                'promoTargetCandidates' => $promoTargetCandidates,
                'promoTargetCount' => count($promoTargetCandidates),
                'promoTargetEmails' => $promoTargetEmails,
                'promoCompanyCandidates' => $promoCompanyCandidates,
                'promoCompanyCount' => count($promoCompanyCandidates),
                'promoCompanyMap' => $promoCompanyMap,
                'promoCompanyTargets' => $promoCompanyTargets,
                'courseStatusLabels' => $courseStatusLabels,
                'pageMode' => $pageMode,
                'couponCodeSupported' => $schema['couponColumns'],
                'couponUsageSummary' => $couponUsageSummary,
                'expiringOfferRows' => $expiringOfferRows,
                'couponPerformanceRows' => $couponPerformanceRows,
                'promoComparison' => $promoComparison,
                'listingState' => $listingState,
                'promotionPagination' => [
                    'page' => (int) ($promotionPaged['page'] ?? 1),
                    'per_page' => (int) ($promotionPaged['per_page'] ?? $listingState['perPage']),
                    'total' => $promotionTotal,
                    'total_pages' => (int) ($promotionPaged['total_pages'] ?? 1),
                ],
                'couponPagination' => [
                    'page' => (int) ($couponPaged['page'] ?? 1),
                    'per_page' => (int) ($couponPaged['per_page'] ?? $listingState['perPage']),
                    'total' => $couponTotal,
                    'total_pages' => (int) ($couponPaged['total_pages'] ?? 1),
                ],
                'refreshedAtIso' => gmdate('c'),
                'fmtDt' => static function (?string $value): string {
                    return self::fmtDt($value);
                },
            ],
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,int> $promoTargetEmailMap
     * @param array<int,array<string,mixed>> $promoCompanyMap
     * @param array<string,bool> $schema
     * @return array{errors:array<int,string>,success:string,promoInputs:array<string,mixed>}
     */
    private function handleCreatePromo(
        int $teacherId,
        bool $canTargetPromos,
        array $post,
        array $promoTargetEmailMap,
        array $promoCompanyMap,
        array $schema,
        bool $isCouponsPage,
        string $createInputKey = 'create'
    ): array {
        $errors = [];
        $success = '';
        $promoInputs = [];
        $entityLabel = $isCouponsPage ? 'coupon' : 'promotion';

        $courseId = (int) ($post['course_id'] ?? 0);
        $couponCodeModeRaw = strtolower(trim((string) ($post['coupon_code_mode'] ?? 'auto')));
        $couponCodeMode = in_array($couponCodeModeRaw, ['auto', 'manual'], true) ? $couponCodeModeRaw : 'auto';
        $couponCodeRaw = trim((string) ($post['coupon_code'] ?? ''));
        $promoInputs[$createInputKey] = [
            'course_id' => (string) $courseId,
            'promo_type' => (string) ($post['promo_type'] ?? ''),
            'promo_amount' => (string) ($post['promo_amount'] ?? ''),
            'promo_percent' => (string) ($post['promo_percent'] ?? ''),
            'promo_duration_days' => (string) ($post['promo_duration_days'] ?? ''),
            'promo_schedule_mode' => (string) ($post['promo_schedule_mode'] ?? 'duration'),
            'promo_start_at' => (string) ($post['promo_start_at'] ?? ''),
            'promo_end_at' => (string) ($post['promo_end_at'] ?? ''),
            'coupon_code_mode' => $couponCodeMode,
            'coupon_code' => $couponCodeRaw,
            'promo_target_type' => (string) ($post['promo_target_type'] ?? ''),
            'promo_targets' => (string) ($post['promo_targets'] ?? ''),
            'promo_company_id' => (string) ($post['promo_company_id'] ?? ''),
            'promo_company_seats' => (string) ($post['promo_company_seats'] ?? ''),
        ];

        if ($isCouponsPage && !$schema['couponColumns']) {
            return [
                'errors' => ['Module coupon indisponible: appliquez database/schema_v44.sql puis rechargez la page.'],
                'success' => '',
                'promoInputs' => $promoInputs,
            ];
        }

        $courseRow = null;
        if ($courseId < 1) {
            $errors[] = 'Cours invalide.';
        } else {
            $courseRow = $this->model->findCourseById($courseId);
            if (!$courseRow) {
                $errors[] = 'Cours introuvable.';
            } elseif ((int) ($courseRow['teacher_id'] ?? 0) !== $teacherId) {
                $errors[] = 'Accès refusé.';
            } elseif ((string) ($courseRow['status'] ?? '') !== 'approved') {
                $errors[] = 'Le cours doit être publié pour créer un(e) ' . $entityLabel . '.';
            }
        }

        $manualCouponCode = '';
        if ($isCouponsPage && $couponCodeMode === 'manual') {
            if ($couponCodeRaw === '') {
                $errors[] = 'Code coupon obligatoire en mode manuel.';
            } elseif (strlen($couponCodeRaw) < 3 || strlen($couponCodeRaw) > 32) {
                $errors[] = 'Le code coupon doit contenir entre 3 et 32 caractères.';
            } elseif (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{1,30}[A-Za-z0-9])?$/', $couponCodeRaw)) {
                $errors[] = 'Le code coupon doit contenir uniquement des lettres, chiffres et tirets, sans tiret au début ni à la fin.';
            } else {
                $manualCouponCode = SharedPromotionsService::normalizeCouponCode($couponCodeRaw);
                if ($manualCouponCode === '') {
                    $errors[] = 'Code coupon invalide.';
                } elseif ($this->model->findCouponByCode($manualCouponCode)) {
                    $errors[] = 'Ce code coupon existe déjà.';
                }
            }
        }

        $targetResult = $this->resolveTargets(
            $canTargetPromos,
            $post,
            $promoTargetEmailMap,
            $promoCompanyMap,
            null,
            !$isCouponsPage
        );
        $errors = array_merge($errors, $targetResult['errors']);
        if ($targetResult['isTargeted'] && !$schema['targetsTable']) {
            $errors[] = 'Le ciblage utilisateur requiert la migration database/schema_v31.sql.';
        }
        if ($targetResult['isCompanyTargeted'] && !$schema['companyTargetsTable']) {
            $errors[] = 'Le ciblage entreprise requiert la migration database/schema_v32.sql.';
        }

        if ($courseRow && $errors === []) {
            if ($targetResult['isTargeted']) {
                if ($this->model->hasActiveIndividualConflict($courseId, $targetResult['targetUserIds'], null, $isCouponsPage ? 'coupon' : 'promotion')) {
                    $errors[] = 'Une offre ciblée est déjà active pour ce cours.';
                }
            } elseif ($targetResult['isCompanyTargeted']) {
                if ($this->model->hasActiveCompanyConflict($courseId, $targetResult['targetCompanyId'], null, $isCouponsPage ? 'coupon' : 'promotion')) {
                    $errors[] = 'Une offre entreprise est déjà active pour ce cours.';
                }
            } else {
                if ($this->model->hasActivePublicConflict(
                    $courseId,
                    $schema['targetsTable'],
                    $schema['companyTargetsTable'],
                    null,
                    $isCouponsPage ? 'coupon' : 'promotion'
                )) {
                    $errors[] = 'Une offre publique est déjà active pour ce cours.';
                }
            }
        }

        $pricing = $this->resolvePricingForCreate($post, $courseRow, $errors);
        $errors = $pricing['errors'];

        if ($errors === [] && $courseRow) {
            try {
                $promoId = 0;
                $createdCouponCode = null;
                if ($isCouponsPage) {
                    $promoId = $this->createCouponWithCode(
                        $courseId,
                        (int) ($courseRow['teacher_id'] ?? 0),
                        $pricing['promoType'],
                        $pricing['promoValue'],
                        $pricing['promoStartAt'],
                        $pricing['promoEndAt'],
                        $couponCodeMode === 'manual' ? $manualCouponCode : null,
                        $createdCouponCode
                    );
                } else {
                    $promoId = $this->model->createPromotion(
                        $courseId,
                        (int) ($courseRow['teacher_id'] ?? 0),
                        $pricing['promoType'],
                        $pricing['promoValue'],
                        $pricing['promoStartAt'],
                        $pricing['promoEndAt'],
                        'promotion',
                        null
                    );
                }

                if ($targetResult['isTargeted'] && $promoId > 0) {
                    foreach ($targetResult['targetUserIds'] as $targetUserId) {
                        $this->model->addPromotionTarget($promoId, (int) $targetUserId);
                    }
                    $this->notifyPromoTargets(
                        $targetResult['targetUserIds'],
                        $courseRow,
                        $pricing['promoType'],
                        $pricing['promoValue'],
                        $pricing['promoEndAt']
                    );
                }

                if ($targetResult['isCompanyTargeted'] && $promoId > 0) {
                    $this->model->addPromotionCompanyTarget(
                        $promoId,
                        $targetResult['targetCompanyId'],
                        $targetResult['targetCompanySeats']
                    );

                    $companyMembers = $this->model->findCompanyMemberIds($targetResult['targetCompanyId']);
                    if ($companyMembers !== []) {
                        $this->notifyPromoTargets(
                            $companyMembers,
                            $courseRow,
                            $pricing['promoType'],
                            $pricing['promoValue'],
                            $pricing['promoEndAt']
                        );
                    }
                }

                $this->sharedPromotions->syncCourseActivePromo($courseId);
                if ($isCouponsPage) {
                    $success = 'Coupon créé. Code : ' . (string) $createdCouponCode . '.';
                } else {
                    $success = 'Promotion créée.';
                }
            } catch (\Throwable $e) {
                $errors[] = $isCouponsPage
                    ? 'Erreur lors de la creation du coupon.'
                    : 'Erreur lors de la creation de la promotion.';
            }
        }

        return [
            'errors' => $errors,
            'success' => $success,
            'promoInputs' => $promoInputs,
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,int> $promoTargetEmailMap
     * @param array<int,array<string,mixed>> $promoCompanyMap
     * @param array<string,bool> $schema
     * @return array{errors:array<int,string>,success:string,promoInputs:array<string,mixed>}
     */
    private function handleUpdatePromo(
        int $teacherId,
        bool $canTargetPromos,
        array $post,
        array $promoTargetEmailMap,
        array $promoCompanyMap,
        array $schema,
        bool $isCouponsPage
    ): array {
        $errors = [];
        $success = '';
        $promoInputs = [];
        $entityLabel = $isCouponsPage ? 'coupon' : 'promotion';

        $promoId = (int) ($post['promo_id'] ?? 0);
        $couponCodeModeRaw = strtolower(trim((string) ($post['coupon_code_mode'] ?? 'auto')));
        $couponCodeMode = in_array($couponCodeModeRaw, ['auto', 'manual'], true) ? $couponCodeModeRaw : 'auto';
        $couponCodeRaw = trim((string) ($post['coupon_code'] ?? ''));
        $promoInputs[$promoId] = [
            'promo_type' => (string) ($post['promo_type'] ?? ''),
            'promo_amount' => (string) ($post['promo_amount'] ?? ''),
            'promo_percent' => (string) ($post['promo_percent'] ?? ''),
            'promo_duration_days' => (string) ($post['promo_duration_days'] ?? ''),
            'promo_schedule_mode' => (string) ($post['promo_schedule_mode'] ?? 'duration'),
            'promo_start_at' => (string) ($post['promo_start_at'] ?? ''),
            'promo_end_at' => (string) ($post['promo_end_at'] ?? ''),
            'coupon_code_mode' => $couponCodeMode,
            'coupon_code' => $couponCodeRaw,
            'promo_target_type' => (string) ($post['promo_target_type'] ?? ''),
            'promo_targets' => (string) ($post['promo_targets'] ?? ''),
            'promo_company_id' => (string) ($post['promo_company_id'] ?? ''),
            'promo_company_seats' => (string) ($post['promo_company_seats'] ?? ''),
        ];

        if ($promoId < 1) {
            return [
                'errors' => ['Promotion invalide.'],
                'success' => '',
                'promoInputs' => $promoInputs,
            ];
        }

        if ($isCouponsPage && !$schema['couponColumns']) {
            return [
                'errors' => ['Module coupon indisponible: appliquez database/schema_v44.sql puis rechargez la page.'],
                'success' => '',
                'promoInputs' => $promoInputs,
            ];
        }

        $promoMode = $isCouponsPage ? 'coupon' : 'promotion';
        $promoRow = $this->model->findPromotionForUpdate($promoId, $promoMode);
        if (!$promoRow) {
            return [
                'errors' => [$isCouponsPage ? 'Coupon introuvable.' : 'Promotion introuvable.'],
                'success' => '',
                'promoInputs' => $promoInputs,
            ];
        }
        if ((int) ($promoRow['teacher_id'] ?? 0) !== $teacherId) {
            return [
                'errors' => ['Accès refusé.'],
                'success' => '',
                'promoInputs' => $promoInputs,
            ];
        }
        if ((string) ($promoRow['status'] ?? '') !== 'approved') {
            return [
                'errors' => ['Le cours doit être publié pour modifier un(e) ' . $entityLabel . '.'],
                'success' => '',
                'promoInputs' => $promoInputs,
            ];
        }

        $couponCodeToSave = null;
        if (array_key_exists('coupon_code', $promoRow)) {
            $existingCouponCode = trim((string) ($promoRow['coupon_code'] ?? ''));
            $couponCodeToSave = $existingCouponCode !== '' ? $existingCouponCode : null;
        }
        if ($isCouponsPage) {
            if ($couponCodeMode === 'manual') {
                if ($couponCodeRaw === '') {
                    $errors[] = 'Code coupon obligatoire en mode manuel.';
                } elseif (strlen($couponCodeRaw) < 3 || strlen($couponCodeRaw) > 32) {
                    $errors[] = 'Le code coupon doit contenir entre 3 et 32 caractères.';
                } elseif (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{1,30}[A-Za-z0-9])?$/', $couponCodeRaw)) {
                    $errors[] = 'Le code coupon doit contenir uniquement des lettres, chiffres et tirets, sans tiret au début ni à la fin.';
                } else {
                    $couponCodeToSave = SharedPromotionsService::normalizeCouponCode($couponCodeRaw);
                    if ($couponCodeToSave === '') {
                        $errors[] = 'Code coupon invalide.';
                    } elseif ($this->model->findCouponByCode($couponCodeToSave, $promoId)) {
                        $errors[] = 'Ce code coupon existe déjà.';
                    }
                }
            } else {
                $couponCodeToSave = '';
                for ($attempt = 0; $attempt < 8; $attempt++) {
                    $candidate = self::generateCouponCode((int) ($promoRow['course_id'] ?? 0));
                    if ($this->model->findCouponByCode($candidate, $promoId)) {
                        continue;
                    }
                    $couponCodeToSave = $candidate;
                    break;
                }
                if ($couponCodeToSave === '') {
                    $errors[] = 'Impossible de generer un code coupon unique.';
                }
            }
        }

        $existingCompanyId = 0;
        if ($schema['companyTargetsTable']) {
            $existingCompanyId = $this->model->findPromotionCompanyId($promoId);
        }

        $targetResult = $this->resolveTargets(
            $canTargetPromos,
            $post,
            $promoTargetEmailMap,
            $promoCompanyMap,
            $existingCompanyId > 0 ? $existingCompanyId : null,
            !$isCouponsPage
        );
        $errors = array_merge($errors, $targetResult['errors']);
        if ($targetResult['isTargeted'] && !$schema['targetsTable']) {
            $errors[] = 'Le ciblage utilisateur requiert la migration database/schema_v31.sql.';
        }
        if ($targetResult['isCompanyTargeted'] && !$schema['companyTargetsTable']) {
            $errors[] = 'Le ciblage entreprise requiert la migration database/schema_v32.sql.';
        }

        $courseId = (int) ($promoRow['course_id'] ?? 0);
        if ($errors === []) {
            if ($targetResult['isTargeted']) {
                if ($this->model->hasActiveIndividualConflict($courseId, $targetResult['targetUserIds'], $promoId, $isCouponsPage ? 'coupon' : 'promotion')) {
                    $errors[] = 'Une offre ciblée est déjà active pour ce cours.';
                }
            } elseif ($targetResult['isCompanyTargeted']) {
                if ($this->model->hasActiveCompanyConflict($courseId, $targetResult['targetCompanyId'], $promoId, $isCouponsPage ? 'coupon' : 'promotion')) {
                    $errors[] = 'Une offre entreprise est déjà active pour ce cours.';
                }
            } else {
                if ($this->model->hasActivePublicConflict(
                    $courseId,
                    $schema['targetsTable'],
                    $schema['companyTargetsTable'],
                    $promoId,
                    $isCouponsPage ? 'coupon' : 'promotion'
                )) {
                    $errors[] = 'Une offre publique est déjà active pour ce cours.';
                }
            }
        }

        $pricing = $this->resolvePricingForUpdate($post, $promoRow, $errors);
        $errors = $pricing['errors'];

        if ($errors === []) {
            try {
                $this->model->updatePromotion(
                    $promoId,
                    $pricing['promoType'],
                    $pricing['promoValue'],
                    $pricing['promoStartAt'],
                    $pricing['promoEndAt'],
                    $couponCodeToSave
                );

                if ($canTargetPromos) {
                    if ($schema['targetsTable']) {
                        $this->model->clearPromotionTargets($promoId);
                    }
                    if ($schema['companyTargetsTable']) {
                        $this->model->clearPromotionCompanyTargets($promoId);
                    }

                    if ($schema['targetsTable'] && $targetResult['targetUserIds'] !== []) {
                        foreach ($targetResult['targetUserIds'] as $targetUserId) {
                            $this->model->addPromotionTarget($promoId, (int) $targetUserId);
                        }
                        $this->notifyPromoTargets(
                            $targetResult['targetUserIds'],
                            $promoRow,
                            $pricing['promoType'],
                            $pricing['promoValue'],
                            $pricing['promoEndAt']
                        );
                    }

                    if ($schema['companyTargetsTable'] && $targetResult['isCompanyTargeted']) {
                        $this->model->addPromotionCompanyTarget(
                            $promoId,
                            $targetResult['targetCompanyId'],
                            $targetResult['targetCompanySeats']
                        );

                        $companyMembers = $this->model->findCompanyMemberIds($targetResult['targetCompanyId']);
                        if ($companyMembers !== []) {
                            $this->notifyPromoTargets(
                                $companyMembers,
                                $promoRow,
                                $pricing['promoType'],
                                $pricing['promoValue'],
                                $pricing['promoEndAt']
                            );
                        }
                    }
                }

                $this->sharedPromotions->syncCourseActivePromo($courseId);
                $success = $isCouponsPage
                    ? 'Coupon mis à jour.' . ($couponCodeToSave !== null && $couponCodeToSave !== '' ? ' Code : ' . $couponCodeToSave . '.' : '')
                    : 'Promotion mise à jour.';
            } catch (\Throwable $e) {
                $errors[] = $isCouponsPage
                    ? 'Erreur lors de la mise à jour du coupon.'
                    : 'Erreur lors de la mise à jour de la promotion.';
            }
        }

        return [
            'errors' => $errors,
            'success' => $success,
            'promoInputs' => $promoInputs,
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @return array{errors:array<int,string>,success:string}
     */
    private function handleDeletePromo(int $teacherId, array $post, bool $isCouponsPage): array
    {
        $promoId = (int) ($post['promo_id'] ?? 0);
        if ($promoId < 1) {
            return ['errors' => [$isCouponsPage ? 'Coupon invalide.' : 'Promotion invalide.'], 'success' => ''];
        }

        $promoMode = $isCouponsPage ? 'coupon' : 'promotion';
        $promoRow = $this->model->findPromotionForDelete($promoId, $promoMode);
        if (!$promoRow) {
            return ['errors' => [$isCouponsPage ? 'Coupon introuvable.' : 'Promotion introuvable.'], 'success' => ''];
        }
        if ((int) ($promoRow['teacher_id'] ?? 0) !== $teacherId) {
            return ['errors' => ['Accès refusé.'], 'success' => ''];
        }

        try {
            $this->model->softDeletePromotion($promoId);
            $this->sharedPromotions->syncCourseActivePromo((int) ($promoRow['course_id'] ?? 0));
            return ['errors' => [], 'success' => $isCouponsPage ? 'Coupon supprimé.' : 'Promotion supprimée.'];
        } catch (\Throwable $e) {
            return ['errors' => ['Erreur lors de la suppression.'], 'success' => ''];
        }
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed>|null $courseRow
     * @param array<int,string> $errors
     * @return array{
     *   errors:array<int,string>,
     *   promoType:string,
     *   promoValue:float,
     *   promoStartAt:string,
     *   promoEndAt:string
     * }
     */
    private function resolvePricingForCreate(array $post, ?array $courseRow, array $errors): array
    {
        $promoTypeRaw = trim((string) ($post['promo_type'] ?? ''));
        $promoAmountRaw = trim((string) ($post['promo_amount'] ?? ''));
        $promoPercentRaw = str_replace(',', '.', trim((string) ($post['promo_percent'] ?? '')));
        $promoScheduleModeRaw = strtolower(trim((string) ($post['promo_schedule_mode'] ?? 'duration')));
        $promoScheduleMode = in_array($promoScheduleModeRaw, ['duration', 'custom'], true) ? $promoScheduleModeRaw : 'duration';
        $promoDaysRaw = trim((string) ($post['promo_duration_days'] ?? ''));
        $promoStartDateRaw = trim((string) ($post['promo_start_at'] ?? ''));
        $promoEndDateRaw = trim((string) ($post['promo_end_at'] ?? ''));

        $promoTypeValue = '';
        $promoValueValue = 0.0;
        $promoStartValue = '';
        $promoEndValue = '';
        $nowTs = time();
        $basePrice = isset($courseRow['price_xof']) ? (int) $courseRow['price_xof'] : 0;

        if ($promoTypeRaw === 'amount') {
            if ($promoAmountRaw === '') {
                $errors[] = 'Montant promo obligatoire.';
            } elseif (!is_numeric($promoAmountRaw)) {
                $errors[] = 'Montant promo invalide.';
            } else {
                $amount = (int) $promoAmountRaw;
                if ($amount < 0) {
                    $errors[] = 'Montant promo invalide.';
                } elseif ($basePrice <= 0) {
                    $errors[] = 'Prix du cours invalide pour une promo.';
                } elseif ($amount >= $basePrice) {
                    $errors[] = 'Le nouveau montant doit être inférieur au prix actuel.';
                } else {
                    $promoTypeValue = 'amount';
                    $promoValueValue = (float) $amount;
                }
            }
        } elseif ($promoTypeRaw === 'percent') {
            if ($promoPercentRaw === '') {
                $errors[] = 'Réduction promo obligatoire.';
            } elseif (!is_numeric($promoPercentRaw)) {
                $errors[] = 'Réduction promo invalide.';
            } else {
                $percent = (float) $promoPercentRaw;
                if ($percent <= 0 || $percent > 100) {
                    $errors[] = 'Réduction promo invalide (1-100).';
                } elseif ($basePrice <= 0) {
                    $errors[] = 'Prix du cours invalide pour une promo.';
                } else {
                    $discounted = (int) round($basePrice * (1 - ($percent / 100)));
                    if ($discounted >= $basePrice) {
                        $errors[] = 'Réduction trop faible pour modifier le prix.';
                    } else {
                        $promoTypeValue = 'percent';
                        $promoValueValue = $percent;
                    }
                }
            }
        } else {
            $errors[] = 'Type de promotion invalide.';
        }

        if ($promoTypeValue !== '') {
            if ($promoScheduleMode === 'custom') {
                if ($promoStartDateRaw === '' || $promoEndDateRaw === '') {
                    $errors[] = 'Dates de début et de fin obligatoires.';
                } else {
                    $normalizedStart = self::normalizePromoDateTimeInput($promoStartDateRaw, false);
                    $normalizedEnd = self::normalizePromoDateTimeInput($promoEndDateRaw, true);
                    $startTs = $normalizedStart !== null ? strtotime($normalizedStart) : false;
                    $endTs = $normalizedEnd !== null ? strtotime($normalizedEnd) : false;
                    if ($normalizedStart === null || $normalizedEnd === null || $startTs === false || $endTs === false) {
                        $errors[] = 'Dates de période invalides.';
                    } elseif ($endTs < $startTs) {
                        $errors[] = 'La date de fin doit être postérieure à la date de début.';
                    } else {
                        $promoStartValue = $normalizedStart;
                        $promoEndValue = $normalizedEnd;
                    }
                }
            } else {
                if ($promoDaysRaw === '') {
                    $errors[] = 'Durée promo obligatoire.';
                } else {
                    $days = (int) $promoDaysRaw;
                    if ($days < 1) {
                        $errors[] = 'Durée promo invalide.';
                    } else {
                        $promoStartValue = date('Y-m-d H:i:s', $nowTs);
                        $promoEndValue = date('Y-m-d H:i:s', $nowTs + ($days * 86400));
                    }
                }
            }
        }

        return [
            'errors' => $errors,
            'promoType' => $promoTypeValue,
            'promoValue' => $promoValueValue,
            'promoStartAt' => $promoStartValue,
            'promoEndAt' => $promoEndValue,
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $promoRow
     * @param array<int,string> $errors
     * @return array{
     *   errors:array<int,string>,
     *   promoType:string,
     *   promoValue:float,
     *   promoStartAt:string,
     *   promoEndAt:string
     * }
     */
    private function resolvePricingForUpdate(array $post, array $promoRow, array $errors): array
    {
        $promoTypeRaw = trim((string) ($post['promo_type'] ?? ''));
        $promoAmountRaw = trim((string) ($post['promo_amount'] ?? ''));
        $promoPercentRaw = str_replace(',', '.', trim((string) ($post['promo_percent'] ?? '')));
        $promoScheduleModeRaw = strtolower(trim((string) ($post['promo_schedule_mode'] ?? 'duration')));
        $promoScheduleMode = in_array($promoScheduleModeRaw, ['duration', 'custom'], true) ? $promoScheduleModeRaw : 'duration';
        $promoDaysRaw = trim((string) ($post['promo_duration_days'] ?? ''));
        $promoStartDateRaw = trim((string) ($post['promo_start_at'] ?? ''));
        $promoEndDateRaw = trim((string) ($post['promo_end_at'] ?? ''));

        $promoTypeValue = '';
        $promoValueValue = 0.0;
        $promoStartValue = '';
        $promoEndValue = '';

        $nowTs = time();
        $basePrice = (int) ($promoRow['price_xof'] ?? 0);
        if ($promoTypeRaw === 'amount') {
            if ($promoAmountRaw === '') {
                $errors[] = 'Montant promo obligatoire.';
            } elseif (!is_numeric($promoAmountRaw)) {
                $errors[] = 'Montant promo invalide.';
            } else {
                $amount = (int) $promoAmountRaw;
                if ($amount < 0) {
                    $errors[] = 'Montant promo invalide.';
                } elseif ($basePrice <= 0) {
                    $errors[] = 'Prix du cours invalide pour une promo.';
                } elseif ($amount >= $basePrice) {
                    $errors[] = 'Le nouveau montant doit être inférieur au prix actuel.';
                } else {
                    $promoTypeValue = 'amount';
                    $promoValueValue = (float) $amount;
                }
            }
        } elseif ($promoTypeRaw === 'percent') {
            if ($promoPercentRaw === '') {
                $errors[] = 'Réduction promo obligatoire.';
            } elseif (!is_numeric($promoPercentRaw)) {
                $errors[] = 'Réduction promo invalide.';
            } else {
                $percent = (float) $promoPercentRaw;
                if ($percent <= 0 || $percent > 100) {
                    $errors[] = 'Réduction promo invalide (1-100).';
                } elseif ($basePrice <= 0) {
                    $errors[] = 'Prix du cours invalide pour une promo.';
                } else {
                    $discounted = (int) round($basePrice * (1 - ($percent / 100)));
                    if ($discounted >= $basePrice) {
                        $errors[] = 'Réduction trop faible pour modifier le prix.';
                    } else {
                        $promoTypeValue = 'percent';
                        $promoValueValue = $percent;
                    }
                }
            }
        } else {
            $errors[] = 'Type de promotion invalide.';
        }

        if ($promoTypeValue !== '') {
            if ($promoScheduleMode === 'custom') {
                if ($promoStartDateRaw === '' || $promoEndDateRaw === '') {
                    $errors[] = 'Dates de début et de fin obligatoires.';
                } else {
                    $normalizedStart = self::normalizePromoDateTimeInput($promoStartDateRaw, false);
                    $normalizedEnd = self::normalizePromoDateTimeInput($promoEndDateRaw, true);
                    $startTs = $normalizedStart !== null ? strtotime($normalizedStart) : false;
                    $endTs = $normalizedEnd !== null ? strtotime($normalizedEnd) : false;
                    if ($normalizedStart === null || $normalizedEnd === null || $startTs === false || $endTs === false) {
                        $errors[] = 'Dates de période invalides.';
                    } elseif ($endTs < $startTs) {
                        $errors[] = 'La date de fin doit être postérieure à la date de début.';
                    } else {
                        $promoStartValue = $normalizedStart;
                        $promoEndValue = $normalizedEnd;
                    }
                }
            } else {
                if ($promoDaysRaw === '') {
                    $errors[] = 'Durée promo obligatoire.';
                } else {
                    $days = (int) $promoDaysRaw;
                    if ($days < 1) {
                        $errors[] = 'Durée promo invalide.';
                    } else {
                        $promoStartValue = date('Y-m-d H:i:s', $nowTs);
                        $promoEndValue = date('Y-m-d H:i:s', $nowTs + ($days * 86400));
                    }
                }
            }
        }

        return [
            'errors' => $errors,
            'promoType' => $promoTypeValue,
            'promoValue' => $promoValueValue,
            'promoStartAt' => $promoStartValue,
            'promoEndAt' => $promoEndValue,
        ];
    }

    private static function normalizePromoDateTimeInput(?string $rawValue, bool $isEnd): ?string
    {
        $raw = trim((string) $rawValue);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            $raw .= $isEnd ? ' 23:59:59' : ' 00:00:00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $raw) === 1) {
            $raw = str_replace('T', ' ', $raw) . ':00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $raw) === 1) {
            $raw = str_replace('T', ' ', $raw);
        } else {
            return null;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,int> $promoTargetEmailMap
     * @param array<int,array<string,mixed>> $promoCompanyMap
     * @return array{
     *   errors:array<int,string>,
     *   isTargeted:bool,
     *   isCompanyTargeted:bool,
     *   targetUserIds:array<int,int>,
     *   targetCompanyId:int,
     *   targetCompanySeats:int
     * }
     */
    private function resolveTargets(
        bool $canTargetPromos,
        array $post,
        array $promoTargetEmailMap,
        array $promoCompanyMap,
        ?int $existingCompanyId,
        bool $allowCompanyTargets = true
    ): array {
        $errors = [];

        $targetType = trim((string) ($post['promo_target_type'] ?? 'public'));
        if ($targetType === '') {
            $targetType = 'public';
        }
        if (!in_array($targetType, ['public', 'individual', 'company'], true)) {
            $errors[] = 'Cible invalide.';
            $targetType = 'public';
        }
        if (!$canTargetPromos) {
            $targetType = 'public';
        }
        if (!$allowCompanyTargets && $targetType === 'company') {
            $errors[] = 'Les coupons entreprise ne sont pas disponibles pour le moment.';
            $targetType = 'public';
        }

        $targetUserIds = [];
        $targetCompanyId = (int) ($post['promo_company_id'] ?? 0);
        $targetCompanySeatsRaw = trim((string) ($post['promo_company_seats'] ?? ''));
        $targetCompanySeats = 0;

        if ($targetType === 'individual') {
            $promoTargetsRaw = trim((string) ($post['promo_targets'] ?? ''));
            if ($promoTargetsRaw === '') {
                $errors[] = 'Selectionnez au moins un utilisateur.';
            } else {
                $targetEmails = self::parseTargetEmails($promoTargetsRaw);
                $invalidEmails = [];
                foreach ($targetEmails as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $invalidEmails[] = $email;
                    }
                }
                if ($invalidEmails !== []) {
                    $errors[] = 'Emails invalides: ' . implode(', ', $invalidEmails);
                } else {
                    $missing = array_diff($targetEmails, array_keys($promoTargetEmailMap));
                    if ($missing !== []) {
                        $errors[] = 'Utilisateurs non eligibles: ' . implode(', ', $missing);
                    } else {
                        foreach ($targetEmails as $email) {
                            $targetUserIds[] = (int) ($promoTargetEmailMap[$email] ?? 0);
                        }
                    }
                }
            }
        } elseif ($targetType === 'company') {
            $companyIsKnown = isset($promoCompanyMap[$targetCompanyId]);
            if ($targetCompanyId < 1 || (!$companyIsKnown && $targetCompanyId !== (int) $existingCompanyId)) {
                $errors[] = 'Entreprise invalide.';
            }

            if ($targetCompanySeatsRaw === '') {
                $errors[] = 'Nombre de places obligatoire.';
            } elseif (!is_numeric($targetCompanySeatsRaw)) {
                $errors[] = 'Nombre de places invalide.';
            } else {
                $targetCompanySeats = (int) $targetCompanySeatsRaw;
                if ($targetCompanySeats < 1) {
                    $errors[] = 'Nombre de places invalide.';
                }
            }
        }

        return [
            'errors' => $errors,
            'isTargeted' => $canTargetPromos && $targetType === 'individual' && $targetUserIds !== [],
            'isCompanyTargeted' => $canTargetPromos && $targetType === 'company' && $targetCompanyId > 0 && $targetCompanySeats > 0,
            'targetUserIds' => $targetUserIds,
            'targetCompanyId' => $targetCompanyId,
            'targetCompanySeats' => $targetCompanySeats,
        ];
    }

    private function createCouponWithCode(
        int $courseId,
        int $teacherId,
        string $promoType,
        float $promoValue,
        string $promoStartAt,
        string $promoEndAt,
        ?string $couponCode,
        ?string &$resolvedCouponCode
    ): int {
        $resolvedCouponCode = null;
        if ($couponCode !== null && $couponCode !== '') {
            $resolvedCouponCode = $couponCode;
            return $this->model->createPromotion(
                $courseId,
                $teacherId,
                $promoType,
                $promoValue,
                $promoStartAt,
                $promoEndAt,
                'coupon',
                $couponCode
            );
        }

        $lastError = null;
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $candidate = self::generateCouponCode($courseId);
            try {
                $promotionId = $this->model->createPromotion(
                    $courseId,
                    $teacherId,
                    $promoType,
                    $promoValue,
                    $promoStartAt,
                    $promoEndAt,
                    'coupon',
                    $candidate
                );
                if ($promotionId > 0) {
                    $resolvedCouponCode = $candidate;
                    return $promotionId;
                }
            } catch (\Throwable $e) {
                $lastError = $e;
            }
        }

        if ($lastError instanceof \Throwable) {
            throw $lastError;
        }
        throw new \RuntimeException('Impossible de generer un code coupon unique.');
    }

    private static function generateCouponCode(int $courseId): string
    {
        try {
            $random = strtoupper(bin2hex(random_bytes(3)));
        } catch (\Throwable $e) {
            $random = strtoupper(substr(sha1((string) microtime(true) . '|' . (string) mt_rand()), 0, 6));
        }
        $coursePart = strtoupper(base_convert((string) max(1, $courseId), 10, 36));
        return 'CPN-' . $coursePart . '-' . $random;
    }

    /**
     * @param array<int,int> $userIds
     * @param array<string,mixed> $courseRow
     */
    private function notifyPromoTargets(array $userIds, array $courseRow, string $promoType, float $promoValue, ?string $promoEnd): void
    {
        if ($userIds === []) {
            return;
        }

        $courseTitle = (string) ($courseRow['title'] ?? 'Cours');
        if ($promoType === 'percent') {
            $labelValue = rtrim(rtrim(number_format($promoValue, 2, '.', ''), '0'), '.');
            $promoLabel = 'Réduction : ' . $labelValue . ' %';
        } else {
            $promoLabel = 'Nouveau montant : ' . number_format($promoValue, 0, ',', ' ') . ' F CFA';
        }

        $body = 'Une promotion spéciale est disponible sur le cours "' . $courseTitle . '". ' . $promoLabel . '.';
        if ($promoEnd) {
            $endTs = strtotime($promoEnd);
            if ($endTs) {
                $body .= ' Valable jusqu’au ' . date('d/m/Y H:i', $endTs) . '.';
            }
        }

        $url = '';
        if (!empty($courseRow['slug'])) {
            $url = $this->siteUrl('course?slug=' . (string) $courseRow['slug']);
        } elseif (!empty($courseRow['id'])) {
            $url = $this->siteUrl('course?id=' . (int) $courseRow['id']);
        }
        $urlValue = $url !== '' ? $url : null;

        foreach ($userIds as $userId) {
            $this->model->insertNotification((int) $userId, 'Promotion spéciale', $body, $urlValue);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $promotions
     * @return array<int,int>
     */
    private function extractPromotionIds(array $promotions): array
    {
        $ids = [];
        foreach ($promotions as $promotion) {
            $id = (int) ($promotion['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * @return array<int,string>
     */
    private static function parseTargetEmails(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return [];
        }

        $emails = [];
        foreach ($parts as $part) {
            $email = strtolower(trim((string) $part));
            if ($email !== '') {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    /**
     * @param array<string,mixed> $query
     * @return array{
     *   q:string,
     *   courseId:int,
     *   state:string,
     *   offer:string,
     *   perPage:int,
     *   promotionPage:int,
     *   couponPage:int,
     *   filters:array<string,mixed>
     * }
     */
    private function resolveListingState(array $query): array
    {
        $q = trim((string) ($query['q'] ?? ''));
        $courseId = max(0, (int) ($query['course_id'] ?? 0));
        $state = strtolower(trim((string) ($query['state'] ?? 'all')));
        if (!in_array($state, ['all', 'active', 'scheduled', 'ended'], true)) {
            $state = 'all';
        }
        $offer = strtolower(trim((string) ($query['offer'] ?? 'all')));
        if (!in_array($offer, ['all', 'promotion', 'coupon'], true)) {
            $offer = 'all';
        }
        $perPage = (int) ($query['size'] ?? 10);
        if (!in_array($perPage, [6, 10, 20, 30, 50], true)) {
            $perPage = 10;
        }
        $promotionPage = max(1, (int) ($query['pp'] ?? 1));
        $couponPage = max(1, (int) ($query['cp'] ?? 1));

        $filters = [];
        if ($q !== '') {
            $filters['q'] = $q;
        }
        if ($courseId > 0) {
            $filters['course_id'] = $courseId;
        }
        if ($state !== 'all') {
            $filters['state'] = $state;
        }

        return [
            'q' => $q,
            'courseId' => $courseId,
            'state' => $state,
            'offer' => $offer,
            'perPage' => $perPage,
            'promotionPage' => $promotionPage,
            'couponPage' => $couponPage,
            'filters' => $filters,
        ];
    }

    /**
     * @param array<string,mixed> $listingState
     */
    private function buildListingQuery(array $listingState): string
    {
        $params = [];
        $q = trim((string) ($listingState['q'] ?? ''));
        if ($q !== '') {
            $params['q'] = $q;
        }
        $courseId = (int) ($listingState['courseId'] ?? 0);
        if ($courseId > 0) {
            $params['course_id'] = $courseId;
        }
        $state = (string) ($listingState['state'] ?? 'all');
        if ($state !== 'all') {
            $params['state'] = $state;
        }
        $offer = (string) ($listingState['offer'] ?? 'all');
        if ($offer !== 'all') {
            $params['offer'] = $offer;
        }
        $perPage = (int) ($listingState['perPage'] ?? 10);
        if ($perPage !== 10) {
            $params['size'] = $perPage;
        }

        $promotionPage = (int) ($listingState['promotionPage'] ?? 1);
        if ($promotionPage > 1) {
            $params['pp'] = $promotionPage;
        }
        $couponPage = (int) ($listingState['couponPage'] ?? 1);
        if ($couponPage > 1) {
            $params['cp'] = $couponPage;
        }

        $query = http_build_query($params);
        return $query !== '' ? ('?' . $query) : '';
    }

    private static function fmtDt(?string $value): string
    {
        if (!$value) {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('d/m/Y H:i', $ts) : $value;
    }
}

