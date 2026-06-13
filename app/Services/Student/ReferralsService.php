<?php
declare(strict_types=1);

namespace App\Services\Student;

use App\Core\Service;
use App\Models\StudentDashboardModel;
use App\Models\TeacherWithdrawalsModel;
use App\Models\ReferralWalletModel;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Shared\ReferralService;

final class ReferralsService extends Service
{
    private const REFERRALS_PER_PAGE = 10;
    private const COMMISSIONS_PER_PAGE = 10;
    private const WITHDRAWALS_PER_PAGE = 8;

    private StudentDashboardModel $model;
    private TeacherWithdrawalsModel $withdrawals;
    private PlatformSettingsService $settings;
    private ReferralService $referrals;
    private ReferralWalletModel $wallet;

    public function __construct(
        StudentDashboardModel $model,
        TeacherWithdrawalsModel $withdrawals,
        PlatformSettingsService $settings,
        ReferralService $referrals,
        ReferralWalletModel $wallet
    )
    {
        $this->model = $model;
        $this->withdrawals = $withdrawals;
        $this->settings = $settings;
        $this->referrals = $referrals;
        $this->wallet = $wallet;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @param array<string,mixed> $query
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(array $user, string $method, array $post, array $query = [], string $pageMode = 'overview'): array
    {
        $errors = [];
        $success = (string) $this->flashGet('success', '');
        $viewData = $this->buildViewData($user, $query, $pageMode);

        if (strtoupper($method) === 'POST') {
            $action = trim((string) ($post['action'] ?? ''));
            if ($action === 'request_referral_withdrawal') {
                $amount = (int) ($post['amount_xof'] ?? 0);
                $withdrawMethod = trim((string) ($post['method'] ?? 'fedapay'));
                $destination = trim((string) ($post['destination'] ?? ''));

                $available = (int) ($viewData['referralWithdrawAvailable'] ?? 0);
                $minWithdrawal = (int) ($viewData['referralMinWithdrawal'] ?? 0);

                if ($amount <= 0) {
                    $errors[] = 'Montant invalide.';
                }
                if ($minWithdrawal > 0 && $amount < $minWithdrawal) {
                    $errors[] = 'Montant minimum parrainage: ' . (string) $minWithdrawal . ' F CFA.';
                }
                if ($amount > $available) {
                    $errors[] = 'Solde parrainage insuffisant.';
                }
                if (!in_array($withdrawMethod, ['fedapay'], true)) {
                    $errors[] = "M\u{00e9}thode invalide.";
                }
                if ($destination === '') {
                    $errors[] = 'Destination obligatoire.';
                }

                if ($errors === []) {
                    try {
                        $userId = (int) ($user['id'] ?? 0);
                        $this->withdrawals->insertWithdrawal(
                            $userId,
                            $amount,
                            $withdrawMethod,
                            $destination,
                            'requested',
                            '',
                            'referral'
                        );
                        $this->withdrawals->insertNotification(
                            $userId,
                            "Demande de retrait parrainage envoy\u{00e9}e",
                            'Votre demande de retrait parrainage de ' . (string) $amount . ' F CFA est en attente de validation.',
                            $this->siteUrl('student/affiliation')
                        );
                        $success = "Demande de retrait parrainage envoy\u{00e9}e.";
                    } catch (\Throwable $e) {
                        $errors[] = 'Erreur lors de la demande de retrait.';
                    }
                }
            }
        }

        if (strtoupper($method) === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            return [
                'redirectTo' => $this->siteUrl('student/affiliation'),
                'view' => [],
            ];
        }

        $viewData = $this->buildViewData($user, $query, $pageMode);
        $viewData['errors'] = $errors;
        $viewData['success'] = $success;
        $viewData['referralsPageMode'] = $pageMode;

        return [
            'redirectTo' => null,
            'view' => $viewData,
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildViewData(array $user, array $query = [], string $pageMode = 'overview'): array
    {
        $userId = (int) ($user['id'] ?? 0);
        $pageMode = in_array($pageMode, ['overview', 'referees', 'history'], true) ? $pageMode : 'overview';

        $unreadCount = 0;
        $supportUnreadCount = 0;
        $supportUnreadMessages = 0;
        $supportUnreadNotifications = 0;
        $activeEnrollments = 0;
        try {
            $unreadCount = $this->model->countUnreadNotifications($userId);
        } catch (\Throwable $e) {
            $unreadCount = 0;
        }
        try {
            $supportUnreadMessages = $this->model->countUnreadSupportReplies($userId);
        } catch (\Throwable $e) {
            $supportUnreadMessages = 0;
        }
        try {
            $supportUnreadNotifications = $this->model->countUnreadSupportNotifications($userId);
        } catch (\Throwable $e) {
            $supportUnreadNotifications = 0;
        }
        $supportUnreadCount = max($supportUnreadMessages, $supportUnreadNotifications);
        try {
            $activeEnrollments = $this->model->countActiveEnrollments($userId);
        } catch (\Throwable $e) {
            $activeEnrollments = 0;
        }

        $referralCode = null;
        $referralLink = '';
        $canShareReferral = $this->referrals->userHasActiveEnrollment($userId);
        $referralEarnings = 0;
        $referralMinWithdrawal = $this->settings->getInt('referral_min_withdrawal_xof', $this->settings->getInt('min_withdrawal_xof', 0));
        $referralCommissionRate = 0.0;
        $referralSecondLevelRate = 0.0;
        $paidReferralWithdrawals = 0;
        $pendingReferralWithdrawals = 0;
        $referralRedemptionsLocked = 0;
        $referralWithdrawAvailable = 0;
        $referralWithdrawalHistory = [];
        $referralWithdrawalHistoryCount = 0;
        $referralWithdrawalPagination = Pagination::build(0, 1, self::WITHDRAWALS_PER_PAGE);
        $earnedByLevel = ['1' => 0, '2' => 0];
        $referralByUser = [];
        $referralByUserCount = 0;
        $referralByUserPagination = Pagination::build(0, 1, self::REFERRALS_PER_PAGE);
        $referralCommissionHistory = [];
        $referralCommissionHistoryCount = 0;
        $referralCommissionHistoryPagination = Pagination::build(0, 1, self::COMMISSIONS_PER_PAGE);

        try {
            $platformReferral = $this->referrals->getPlatformReferralSettings();
            $referralCommissionRate = max(0.0, (float) ($platformReferral['l1_rate'] ?? 0.0));
            $referralSecondLevelRate = max(0.0, (float) ($platformReferral['l2_rate'] ?? 0.0));

            if ($canShareReferral) {
                $referralCode = $this->referrals->ensureUserReferralCode($userId);
            }
            if ($referralCode) {
                $referralLink = $this->absUrl('register?ref=' . urlencode((string) $referralCode));
            }

            $commissionsTableReady = $this->referrals->ensureReferralCommissionsTable();
            if ($commissionsTableReady) {
                $referralEarnings = $this->model->sumReferralEarnings($userId);

                foreach ($this->model->sumReferralEarningsByLevel($userId) as $row) {
                    $level = (int) ($row['level'] ?? 0);
                    $amount = (int) ($row['total_amount'] ?? 0);
                    if ($level === 1 || $level === 2) {
                        $earnedByLevel[(string) $level] = $amount;
                    }
                }

                $referralCommissionHistoryCount = $this->model->countReferralCommissionHistory($userId);
                $commissionPage = Pagination::pageFromQuery($query, $pageMode === 'history' ? 'page' : 'history_page');
                $referralCommissionHistoryPagination = Pagination::build(
                    $referralCommissionHistoryCount,
                    $commissionPage,
                    self::COMMISSIONS_PER_PAGE
                );
                $referralCommissionHistory = $this->model->fetchReferralCommissionHistoryPage(
                    $userId,
                    (int) $referralCommissionHistoryPagination['offset'],
                    (int) $referralCommissionHistoryPagination['per_page']
                );
            }

            if ($this->referrals->ensureReferralsTable()) {
                $referralByUserCount = $this->model->countReferralRows($userId);
                $refPage = Pagination::pageFromQuery($query, $pageMode === 'referees' ? 'page' : 'ref_page');
                $referralByUserPagination = Pagination::build($referralByUserCount, $refPage, self::REFERRALS_PER_PAGE);
                $referralByUser = $this->model->fetchReferralRowsPage(
                    $userId,
                    (int) $referralByUserPagination['offset'],
                    (int) $referralByUserPagination['per_page'],
                    $commissionsTableReady
                );
            } elseif ($commissionsTableReady) {
                $referralByUserCount = $this->model->countReferralByUser($userId);
                $refPage = Pagination::pageFromQuery($query, $pageMode === 'referees' ? 'page' : 'ref_page');
                $referralByUserPagination = Pagination::build($referralByUserCount, $refPage, self::REFERRALS_PER_PAGE);
                $referralByUser = $this->model->fetchReferralByUserPage(
                    $userId,
                    (int) $referralByUserPagination['offset'],
                    (int) $referralByUserPagination['per_page']
                );
            }

            $paidReferralWithdrawals = $this->withdrawals->sumPaidWithdrawals($userId, 'referral');
            $pendingReferralWithdrawals = $this->withdrawals->sumPendingWithdrawals($userId, 'referral');
            $referralRedemptionsLocked = $this->wallet->sumByUserAndStatuses($userId, ['reserved', 'used']);
            $lockedReferral = $paidReferralWithdrawals + $pendingReferralWithdrawals + $referralRedemptionsLocked;
            $referralWithdrawAvailable = $referralEarnings - $lockedReferral;
            if ($referralWithdrawAvailable < 0) {
                $referralWithdrawAvailable = 0;
            }
            $referralWithdrawalHistoryCount = $this->withdrawals->countWithdrawalsHistory($userId, null, null, 'referral');
            $withdrawPage = Pagination::pageFromQuery($query, 'withdraw_page');
            $referralWithdrawalPagination = Pagination::build(
                $referralWithdrawalHistoryCount,
                $withdrawPage,
                self::WITHDRAWALS_PER_PAGE
            );
            $referralWithdrawalHistory = $this->withdrawals->fetchWithdrawalsHistoryPage(
                $userId,
                (int) $referralWithdrawalPagination['offset'],
                (int) $referralWithdrawalPagination['per_page'],
                null,
                null,
                'referral'
            );
        } catch (\Throwable $e) {
            $referralEarnings = 0;
            $referralCommissionRate = 0.0;
            $referralSecondLevelRate = 0.0;
            $earnedByLevel = ['1' => 0, '2' => 0];
            $referralByUser = [];
            $referralByUserCount = 0;
            $referralByUserPagination = Pagination::build(0, 1, self::REFERRALS_PER_PAGE);
            $referralCommissionHistory = [];
            $referralCommissionHistoryCount = 0;
            $referralCommissionHistoryPagination = Pagination::build(0, 1, self::COMMISSIONS_PER_PAGE);
            $paidReferralWithdrawals = 0;
            $pendingReferralWithdrawals = 0;
            $referralRedemptionsLocked = 0;
            $referralWithdrawAvailable = 0;
            $referralWithdrawalHistory = [];
            $referralWithdrawalHistoryCount = 0;
            $referralWithdrawalPagination = Pagination::build(0, 1, self::WITHDRAWALS_PER_PAGE);
        }

        $fmtDtShort = static function (?string $dbValue): string {
            if (!$dbValue) {
                return '';
            }
            $ts = strtotime($dbValue);
            if (!$ts) {
                return (string) $dbValue;
            }
            return date('d/m/Y H:i', $ts);
        };

        return [
            'unreadCount' => $unreadCount,
            'supportUnreadCount' => $supportUnreadCount,
            'activeEnrollments' => $activeEnrollments,
            'referralCode' => $referralCode,
            'referralLink' => $referralLink,
            'canShareReferral' => $canShareReferral,
            'referralEarnings' => $referralEarnings,
            'referralCommissionRate' => $referralCommissionRate,
            'referralSecondLevelRate' => $referralSecondLevelRate,
            'referralMinWithdrawal' => $referralMinWithdrawal,
            'paidReferralWithdrawals' => $paidReferralWithdrawals,
            'pendingReferralWithdrawals' => $pendingReferralWithdrawals,
            'referralRedemptionsLocked' => $referralRedemptionsLocked,
            'referralWithdrawAvailable' => $referralWithdrawAvailable,
            'referralWithdrawalHistory' => $referralWithdrawalHistory,
            'referralWithdrawalHistoryCount' => $referralWithdrawalHistoryCount,
            'referralWithdrawalPagination' => $referralWithdrawalPagination,
            'earnedByLevel' => $earnedByLevel,
            'referralByUser' => $referralByUser,
            'referralByUserCount' => $referralByUserCount,
            'referralByUserPagination' => $referralByUserPagination,
            'referralCommissionHistory' => $referralCommissionHistory,
            'referralCommissionHistoryCount' => $referralCommissionHistoryCount,
            'referralCommissionHistoryPagination' => $referralCommissionHistoryPagination,
            'referralsPageMode' => $pageMode,
            'fmtDtShort' => $fmtDtShort,
        ];
    }
}
