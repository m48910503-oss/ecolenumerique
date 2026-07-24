<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;

use App\Services\Student\Pagination;
use App\Services\Shared\PlatformSettingsService;
use App\Models\TeacherReferralsModel;
use App\Services\Shared\ReferralService;

final class ReferralsService extends Service
{
    private const REFERRALS_PER_PAGE = 10;

    private TeacherReferralsModel $model;
    private ReferralService $referrals;
    private PlatformSettingsService $settings;

    public function __construct(
        TeacherReferralsModel $model,
        ReferralService $referrals,
        PlatformSettingsService $settings
    )
    {
        $this->model = $model;
        $this->referrals = $referrals;
        $this->settings = $settings;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $query
     * @return array{view:array<string,mixed>}
     */
    public function handle(array $user, array $query = [], string $pageMode = 'overview'): array
    {
        return [
            'view' => $this->buildViewData($user, $query, $pageMode),
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function buildViewData(array $user, array $query = [], string $pageMode = 'overview'): array
    {
        $errors = [];
        $success = (string) $this->flashGet('success', '');
        $warning = '';
        $pageMode = in_array($pageMode, ['overview', 'referees'], true) ? $pageMode : 'overview';

        $referralCode = null;
        $userId = (int) ($user['id'] ?? 0);
        $canShareReferral = $userId > 0;
        if ($canShareReferral) {
            $referralCode = $this->referrals->ensureUserReferralCode($userId);
        }

        $platformReferral = $this->referrals->getPlatformReferralSettings();
        $legacyMinWithdrawal = $this->settings->getInt('min_withdrawal_xof', 0);
        $referralMinWithdrawal = $this->settings->getInt('referral_min_withdrawal_xof', $legacyMinWithdrawal);

        $referralsTableReady = false;
        $commissionsTableReady = false;
        $referralCount = 0;
        $referralEarnings = 0;
        $earnedByLevel = ['1' => 0, '2' => 0];
        $commissionCount = 0;
        $activeAffiliateCount = 0;
        $latestCommissionAt = null;
        $referralCommissions = [];
        $referralByCourse = [];
        $referralByUser = [];
        $referralRows = [];
        $referralRowsPagination = Pagination::build(0, 1, self::REFERRALS_PER_PAGE);

        $referralsTableReady = $this->referrals->ensureReferralsTable();
        if (!$referralsTableReady) {
            $warning = "Le resume d'affiliation est partiellement indisponible sur cette base.";
        }

        $commissionsTableReady = $this->referrals->ensureReferralCommissionsTable();
        if (!$commissionsTableReady) {
            $warning = "Les statistiques d'affiliation sont indisponibles pour le moment sur cette base.";
        }

        try {
            if ($referralsTableReady) {
                $referralCount = $this->model->countReferrals($userId);
                if ($pageMode === 'referees') {
                    $referralPage = Pagination::pageFromQuery($query, 'page');
                    $referralRowsPagination = Pagination::build($referralCount, $referralPage, self::REFERRALS_PER_PAGE);
                    $referralRows = $this->model->fetchReferralRowsPage(
                        $userId,
                        (int) $referralRowsPagination['offset'],
                        (int) $referralRowsPagination['per_page'],
                        $commissionsTableReady
                    );
                } else {
                    $referralRows = $this->model->findReferralRows($userId, 8, $commissionsTableReady);
                }
            }

            if ($commissionsTableReady) {
                $referralEarnings = $this->model->sumEarnedCommissions($userId);
                $commissionCount = $this->model->countEarnedCommissions($userId);
                $activeAffiliateCount = $this->model->countActiveAffiliates($userId);
                $latestCommissionAt = $this->model->findLatestEarnedCommissionAt($userId);

                foreach ($this->model->sumEarnedByLevel($userId) as $row) {
                    $level = (int) ($row['level'] ?? 0);
                    $amount = (int) ($row['total_amount'] ?? 0);
                    if ($level === 1 || $level === 2) {
                        $earnedByLevel[(string) $level] = $amount;
                    }
                }

                $referralCommissions = $this->model->findRecentCommissions($userId, 50);
                $referralByCourse = $this->model->findTopCourses($userId, 10);
                $referralByUser = $this->model->findReferralsByUser($userId, 50);

                if (!$referralsTableReady) {
                    $referralCount = $this->model->countReferralByUser($userId);
                    if ($pageMode === 'referees') {
                        $referralPage = Pagination::pageFromQuery($query, 'page');
                        $referralRowsPagination = Pagination::build($referralCount, $referralPage, self::REFERRALS_PER_PAGE);
                        $referralRows = $this->model->fetchReferralByUserPage(
                            $userId,
                            (int) $referralRowsPagination['offset'],
                            (int) $referralRowsPagination['per_page']
                        );
                    }
                }
            }

            if ($pageMode === 'referees' && $referralRows === [] && $commissionsTableReady) {
                $referralCount = $this->model->countReferralByUser($userId);
                $referralPage = Pagination::pageFromQuery($query, 'page');
                $referralRowsPagination = Pagination::build($referralCount, $referralPage, self::REFERRALS_PER_PAGE);
                $referralRows = $this->model->fetchReferralByUserPage(
                    $userId,
                    (int) $referralRowsPagination['offset'],
                    (int) $referralRowsPagination['per_page']
                );
            }

            if ($referralRows === [] && $commissionsTableReady && $pageMode !== 'referees') {
                $fallbackRows = $this->model->findReferralsByUser($userId, 8);
                if ($fallbackRows !== []) {
                    $referralRows = $fallbackRows;
                    if ($referralCount === 0) {
                        $referralCount = $this->model->countActiveAffiliates($userId);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[teacher/referrals] unable to load referral data for user ' . $userId . ': ' . $e->getMessage());
            $warning = "Les statistiques d'affiliation n'ont pas pu etre chargees completement.";

            if (!$referralsTableReady) {
                $referralCount = 0;
            }
            $referralEarnings = 0;
            $earnedByLevel = ['1' => 0, '2' => 0];
            $commissionCount = 0;
            $activeAffiliateCount = 0;
            $latestCommissionAt = null;
            $referralCommissions = [];
            $referralByCourse = [];
            $referralByUser = [];
            $referralRows = [];
            $commissionsTableReady = false;
        }

        $referralLink = $referralCode ? $this->absUrl('register?ref=' . urlencode((string) $referralCode)) : '';

        return [
            'errors' => $errors,
            'success' => $success,
            'warning' => $warning,
            'referralsPageMode' => $pageMode,
            'referralCode' => $referralCode,
            'canShareReferral' => $canShareReferral,
            'platformReferral' => $platformReferral,
            'referralMinWithdrawal' => $referralMinWithdrawal,
            'referralsTableReady' => $referralsTableReady,
            'commissionsTableReady' => $commissionsTableReady,
            'referralCount' => $referralCount,
            'referralEarnings' => $referralEarnings,
            'earnedByLevel' => $earnedByLevel,
            'commissionCount' => $commissionCount,
            'activeAffiliateCount' => $activeAffiliateCount,
            'latestCommissionAt' => $latestCommissionAt,
            'referralCommissions' => $referralCommissions,
            'referralByCourse' => $referralByCourse,
            'referralByUser' => $referralByUser,
            'referralRows' => $referralRows,
            'referralRowsPagination' => $referralRowsPagination,
            'referralLink' => $referralLink,
            'fmtDt' => static function (?string $value): string {
                if (!$value) {
                    return '';
                }
                $ts = strtotime($value);
                return $ts ? date('d/m/Y H:i', $ts) : $value;
            },
        ];
    }
}

