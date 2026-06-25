<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;

use App\Models\ReferralWalletModel;
use App\Models\TeacherReferralsModel;
use App\Models\TeacherWithdrawalsModel;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Shared\ReferralService;

final class WithdrawalsService extends Service
{
    private TeacherWithdrawalsModel $model;
    private TeacherReferralsModel $referralGains;
    private ReferralWalletModel $wallet;
    private PlatformSettingsService $settings;
    private ReferralService $referrals;

    public function __construct(
        TeacherWithdrawalsModel $model,
        TeacherReferralsModel $referralGains,
        ReferralWalletModel $wallet,
        PlatformSettingsService $settings,
        ReferralService $referrals
    )
    {
        $this->model = $model;
        $this->referralGains = $referralGains;
        $this->wallet = $wallet;
        $this->settings = $settings;
        $this->referrals = $referrals;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @param array<string,mixed> $query
     * @return array{
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $user, string $method, array $post, array $query = []): array
    {
        $errors = [];
        $success = (string) $this->flashGet('success', '');

        $commissionPercent = $this->settings->getFloat('commission_percent', 20.0);
        $legacyMin = $this->settings->getInt('min_withdrawal_xof', 0);
        $salesMinWithdrawal = $this->settings->getInt('teacher_min_withdrawal_xof', $legacyMin);
        $referralMinWithdrawal = $this->settings->getInt('referral_min_withdrawal_xof', $legacyMin);
        $withdrawalMethodCatalog = $this->settings->teacherWithdrawalMethodCatalog();
        $withdrawalMethodOptions = $this->settings->teacherWithdrawalMethodOptions();
        $defaultWithdrawalMethod = array_key_first($withdrawalMethodOptions);
        if (!is_string($defaultWithdrawalMethod)) {
            $defaultWithdrawalMethod = '';
        }
        $teacherId = (int) ($user['id'] ?? 0);
        $withdrawalDestination = $this->resolveWithdrawalDestination($user);
        $withdrawalDestinationDisplay = $withdrawalDestination;
        $hasWithdrawalDestination = $withdrawalDestination !== '';
        $periodInput = $query;
        if (strtoupper($method) === 'POST') {
            foreach (['period', 'from', 'to'] as $periodField) {
                if (isset($post[$periodField]) && (string) $post[$periodField] !== '') {
                    $periodInput[$periodField] = (string) $post[$periodField];
                }
            }
        }
        $periodState = $this->resolvePeriodState($periodInput);
        $periodStartAt = (string) ($periodState['start_at'] ?? '');
        $periodEndAt = (string) ($periodState['end_at'] ?? '');

        $referralCostTotal = 0;
        $referralCostByCourse = [];
        $referralCostByLevel = [1 => 0, 2 => 0];
        $periodReferralCostTotal = 0;
        $periodReferralCostByCourse = [];
        try {
            $this->referrals->ensureReferralCommissionsTable();
            foreach ($this->model->fetchReferralCostsByCourse($teacherId) as $row) {
                $courseId = (int) ($row['course_id'] ?? 0);
                $amount = (int) ($row['total_amount'] ?? 0);
                if ($courseId > 0 && $amount > 0) {
                    $referralCostByCourse[$courseId] = $amount;
                    $referralCostTotal += $amount;
                }
            }

            $referralCostByLevel = $this->model->sumReferralCostsByLevel($teacherId);

            foreach ($this->model->fetchReferralCostsByCourse($teacherId, $periodStartAt, $periodEndAt) as $row) {
                $courseId = (int) ($row['course_id'] ?? 0);
                $amount = (int) ($row['total_amount'] ?? 0);
                if ($courseId > 0 && $amount > 0) {
                    $periodReferralCostByCourse[$courseId] = $amount;
                    $periodReferralCostTotal += $amount;
                }
            }
        } catch (\Throwable $e) {
            $referralCostTotal = 0;
            $referralCostByCourse = [];
            $referralCostByLevel = [1 => 0, 2 => 0];
            $periodReferralCostTotal = 0;
            $periodReferralCostByCourse = [];
        }

        $referralEarnings = 0;
        $referralEarningsByLevel = [1 => 0, 2 => 0];
        $paidReferralWithdrawals = 0;
        $pendingReferralWithdrawals = 0;
        $referralWalletLocked = 0;
        $referralWithdrawAvailable = 0;
        try {
            $this->referrals->ensureReferralCommissionsTable();
            $referralEarnings = $this->referralGains->sumEarnedCommissions($teacherId);
            foreach ($this->referralGains->sumEarnedByLevel($teacherId) as $row) {
                $level = (int) ($row['level'] ?? 0);
                $amount = (int) ($row['total_amount'] ?? 0);
                if ($level === 1 || $level === 2) {
                    $referralEarningsByLevel[$level] = $amount;
                }
            }
            $paidReferralWithdrawals = $this->model->sumPaidWithdrawals($teacherId, 'referral');
            $pendingReferralWithdrawals = $this->model->sumPendingWithdrawals($teacherId, 'referral');
            $referralWalletLocked = $this->wallet->sumByUserAndStatuses($teacherId, ['reserved', 'used']);
            $referralWithdrawAvailable = $referralEarnings - ($paidReferralWithdrawals + $pendingReferralWithdrawals + $referralWalletLocked);
            if ($referralWithdrawAvailable < 0) {
                $referralWithdrawAvailable = 0;
            }
        } catch (\Throwable $e) {
            $referralEarnings = 0;
            $referralEarningsByLevel = [1 => 0, 2 => 0];
            $paidReferralWithdrawals = 0;
            $pendingReferralWithdrawals = 0;
            $referralWalletLocked = 0;
            $referralWithdrawAvailable = 0;
        }

        $grossTotal = 0;
        $platformTotal = 0;
        $periodGrossTotal = 0;
        $periodPlatformTotal = 0;
        try {
            foreach ($this->model->fetchPaidSalesRows($teacherId) as $row) {
                $qty = (int) ($row['quantity'] ?? 1);
                $unit = (int) ($row['unit_price_xof'] ?? 0);
                $gross = $qty * $unit;
                $fee = (int) round($gross * $commissionPercent / 100);
                $grossTotal += $gross;
                $platformTotal += $fee;
            }

            foreach ($this->model->fetchPaidSalesRows($teacherId, $periodStartAt, $periodEndAt) as $row) {
                $qty = (int) ($row['quantity'] ?? 1);
                $unit = (int) ($row['unit_price_xof'] ?? 0);
                $gross = $qty * $unit;
                $fee = (int) round($gross * $commissionPercent / 100);
                $periodGrossTotal += $gross;
                $periodPlatformTotal += $fee;
            }
        } catch (\Throwable $e) {
            $grossTotal = 0;
            $platformTotal = 0;
            $periodGrossTotal = 0;
            $periodPlatformTotal = 0;
        }

        $teacherNet = $grossTotal - $platformTotal - $referralCostTotal;
        if ($teacherNet < 0) {
            $teacherNet = 0;
        }
        $periodTeacherNet = $periodGrossTotal - $periodPlatformTotal - $periodReferralCostTotal;
        if ($periodTeacherNet < 0) {
            $periodTeacherNet = 0;
        }

        $courseEarnings = [];
        try {
            foreach ($this->model->fetchCourseEarningsRows($teacherId, $periodStartAt, $periodEndAt) as $row) {
                $salesCount = (int) ($row['sales_count'] ?? 0);
                $gross = (int) ($row['gross_amount'] ?? 0);
                $fee = (int) round($gross * $commissionPercent / 100);
                $refFee = (int) ($periodReferralCostByCourse[(int) ($row['id'] ?? 0)] ?? 0);
                $net = $gross - $fee - $refFee;
                if ($net < 0) {
                    $net = 0;
                }
                $isPaid = $salesCount > 0;
                $courseEarnings[] = [
                    'course_id' => (int) ($row['id'] ?? 0),
                    'title' => (string) ($row['title'] ?? ''),
                    'sales_count' => $salesCount,
                    'gross' => $gross,
                    'platform_fee' => $fee,
                    'referral_fee' => $refFee,
                    'net' => $net,
                    'is_paid' => $isPaid,
                ];
            }
        } catch (\Throwable $e) {
            $courseEarnings = [];
        }

        $courseRevenueRows = [];
        try {
            $courseRevenueRows = $this->model->fetchCourseRevenueRows($teacherId, $periodStartAt, $periodEndAt);
        } catch (\Throwable $e) {
            $courseRevenueRows = [];
        }

        $recentTransactionsRows = [];
        try {
            $recentTransactionsRows = $this->model->fetchRecentTransactionRows($teacherId, 50, $periodStartAt, $periodEndAt);
        } catch (\Throwable $e) {
            $recentTransactionsRows = [];
        }

        $paidWithdrawals = 0;
        $pendingWithdrawals = 0;
        $paidPayouts = 0;
        try {
            $paidWithdrawals = $this->model->sumPaidWithdrawals((int) ($user['id'] ?? 0), 'teacher_sales');
        } catch (\Throwable $e) {
            $paidWithdrawals = 0;
        }
        try {
            $pendingWithdrawals = $this->model->sumPendingWithdrawals((int) ($user['id'] ?? 0), 'teacher_sales');
        } catch (\Throwable $e) {
            $pendingWithdrawals = 0;
        }
        try {
            $paidPayouts = $this->model->sumPaidPayouts($teacherId);
        } catch (\Throwable $e) {
            $paidPayouts = 0;
        }

        $alreadyPaid = $paidWithdrawals + $paidPayouts;
        $lockedAmount = $alreadyPaid + $pendingWithdrawals;
        $salesAvailable = $teacherNet - $lockedAmount;
        if ($salesAvailable < 0) {
            $salesAvailable = 0;
        }

        $withdrawAmountValue = strtoupper($method) === 'POST' ? max(0, (int) ($post['amount_xof'] ?? 0)) : 0;
        $withdrawalTypeValue = strtoupper($method) === 'POST'
            ? $this->normalizeWithdrawalType((string) ($post['withdrawal_type'] ?? 'teacher_sales'))
            : 'teacher_sales';
        $withdrawMethodValue = strtoupper($method) === 'POST'
            ? $this->normalizeWithdrawalMethod((string) ($post['method'] ?? $defaultWithdrawalMethod))
            : $defaultWithdrawalMethod;
        if ($withdrawMethodValue !== '' && !isset($withdrawalMethodCatalog[$withdrawMethodValue])) {
            $withdrawMethodValue = $defaultWithdrawalMethod;
        }

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            if ($action === 'cancel_withdrawal') {
                $withdrawalId = (int) ($post['withdrawal_id'] ?? 0);
                $cancelType = $this->normalizeWithdrawalType((string) ($post['withdrawal_type'] ?? 'teacher_sales'));
                if ($withdrawalId < 1) {
                    $errors[] = 'Demande invalide.';
                } else {
                    try {
                        $cancelled = $this->model->cancelWithdrawalRequest(
                            (int) ($user['id'] ?? 0),
                            $withdrawalId,
                            $cancelType
                        );
                        if ($cancelled) {
                            $success = 'Demande annulée.';
                        } else {
                            $errors[] = 'Impossible d\'annuler cette demande.';
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'Erreur lors de l\'annulation.';
                    }
                }

                if ($errors === [] && $success !== '') {
                    $this->flashSet('success', $success);
                    return [
                        'redirectTo' => $this->siteUrl('teacher/withdrawals' . $this->buildPeriodQuery($periodState)),
                        'view' => [],
                    ];
                }
            }
        }

        if ($method === 'POST') {
            $amount = (int) ($post['amount_xof'] ?? 0);
            $withdrawMethod = $this->normalizeWithdrawalMethod((string) ($post['method'] ?? $defaultWithdrawalMethod));
            $destination = $withdrawalDestination;
            $withdrawalType = $this->normalizeWithdrawalType((string) ($post['withdrawal_type'] ?? 'teacher_sales'));
            $currentMinWithdrawal = $withdrawalType === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal;
            $currentAvailable = $withdrawalType === 'referral' ? $referralWithdrawAvailable : $salesAvailable;

            if ($amount <= 0) {
                $errors[] = 'Montant invalide.';
            }
            if ($currentMinWithdrawal > 0 && $amount < $currentMinWithdrawal) {
                $errors[] = ($withdrawalType === 'referral' ? 'Montant minimum parrainage: ' : 'Montant minimum: ')
                    . (string) $currentMinWithdrawal
                    . ' F CFA.';
            }
            if ($amount > $currentAvailable) {
                $errors[] = $withdrawalType === 'referral' ? 'Solde parrainage insuffisant.' : 'Solde insuffisant.';
            }
            if ($withdrawalMethodOptions === []) {
                $errors[] = 'Aucune methode de retrait n\'est disponible actuellement.';
            } elseif ($withdrawMethod === '' || !isset($withdrawalMethodOptions[$withdrawMethod])) {
                $errors[] = 'Méthode invalide.';
            }
            if ($destination === '') {
                $errors[] = 'Renseignez votre numéro de retrait dans votre profil.';
            }

            if ($errors === []) {
                $reference = '';
                try {
                    $this->model->insertWithdrawal(
                        (int) ($user['id'] ?? 0),
                        $amount,
                        $withdrawMethod,
                        $destination,
                        'requested',
                        $reference,
                        $withdrawalType
                    );
                    $this->notifyAdminsWithdrawalRequested($user, $amount, $withdrawalType);
                    $success = $withdrawalType === 'referral'
                        ? 'Demande de retrait parrainage envoyée. Validation admin en attente.'
                        : 'Demande envoyée. Validation admin en attente.';
                } catch (\Throwable $e) {
                    $errors[] = 'Erreur lors du retrait.';
                }
            }
        }

        // PRG: prevent duplicate POST on refresh.
        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            return [
                'redirectTo' => $this->siteUrl('teacher/withdrawals' . $this->buildPeriodQuery($periodState)),
                'view' => [],
            ];
        }

        $withdrawals = [];
        try {
            $withdrawals = $this->model->fetchWithdrawalsHistory((int) ($user['id'] ?? 0), 50, $periodStartAt, $periodEndAt, 'all');
        } catch (\Throwable $e) {
            $withdrawals = [];
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'success' => $success,
                'commissionPercent' => $commissionPercent,
                'minWithdrawal' => $salesMinWithdrawal,
                'salesMinWithdrawal' => $salesMinWithdrawal,
                'referralMinWithdrawal' => $referralMinWithdrawal,
                'grossTotal' => $grossTotal,
                'platformTotal' => $platformTotal,
                'teacherNet' => $teacherNet,
                'periodState' => $periodState,
                'periodGrossTotal' => $periodGrossTotal,
                'periodPlatformTotal' => $periodPlatformTotal,
                'periodReferralTotal' => $periodReferralCostTotal,
                'periodTeacherNet' => $periodTeacherNet,
                'courseEarnings' => $courseEarnings,
                'courseRevenueRows' => $courseRevenueRows,
                'recentTransactionsRows' => $recentTransactionsRows,
                'referralTotal' => $referralCostTotal,
                'referralByLevel' => $referralCostByLevel,
                'referralCostTotal' => $referralCostTotal,
                'referralCostByLevel' => $referralCostByLevel,
                'referralEarnings' => $referralEarnings,
                'referralEarningsByLevel' => $referralEarningsByLevel,
                'paidReferralWithdrawals' => $paidReferralWithdrawals,
                'pendingReferralWithdrawals' => $pendingReferralWithdrawals,
                'referralWalletLocked' => $referralWalletLocked,
                'referralWithdrawAvailable' => $referralWithdrawAvailable,
                'alreadyPaid' => $alreadyPaid,
                'pendingWithdrawals' => $pendingWithdrawals,
                'available' => $salesAvailable,
                'salesAvailable' => $salesAvailable,
                'withdrawals' => $withdrawals,
                'withdrawalDestination' => $withdrawalDestination,
                'withdrawalDestinationDisplay' => $withdrawalDestinationDisplay,
                'hasWithdrawalDestination' => $hasWithdrawalDestination,
                'withdrawAmountValue' => $withdrawAmountValue,
                'withdrawalTypeValue' => $withdrawalTypeValue,
                'withdrawMethodValue' => $withdrawMethodValue,
                'withdrawalMethodCatalog' => $withdrawalMethodCatalog,
                'withdrawalMethodOptions' => $withdrawalMethodOptions,
                'profileUrl' => $this->siteUrl('profile') . '?space=teacher',
            ],
        ];
    }

    /**
     * @param array<string,mixed> $user
     */
    private function notifyAdminsWithdrawalRequested(array $user, int $amount, string $withdrawalType = 'teacher_sales'): void
    {
        try {
            $adminIds = $this->model->findAdminNotificationRecipientIds();
        } catch (\Throwable $e) {
            return;
        }

        if ($adminIds === []) {
            return;
        }

        $teacherId = (int) ($user['id'] ?? 0);
        $teacherName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
        if ($teacherName === '') {
            $teacherName = trim((string) ($user['email'] ?? ''));
        }
        if ($teacherName === '') {
            $teacherName = 'Enseignant #' . (string) $teacherId;
        }

        $isReferralWithdrawal = $withdrawalType === 'referral';
        $title = $isReferralWithdrawal
            ? 'Nouvelle demande de retrait parrainage enseignant'
            : 'Nouvelle demande de retrait enseignant';
        $body = $teacherName
            . ' a demandé un retrait de '
            . (string) $amount
            . ' F CFA'
            . ($isReferralWithdrawal ? ' sur ses gains de parrainage.' : '.');
        $url = $this->siteUrl('admin/sales');

        foreach ($adminIds as $adminId) {
            if ($adminId < 1 || $adminId === $teacherId) {
                continue;
            }
            try {
                $this->model->insertNotification($adminId, $title, $body, $url);
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * @param array<string,mixed> $user
     */
    private function resolveWithdrawalDestination(array $user): string
    {
        $phone = preg_replace('/[^\d+]+/', '', trim((string) ($user['phone'] ?? '')));
        if (is_string($phone)) {
            $phone = preg_replace('/(?!^)\+/', '', $phone);
        }
        return is_string($phone) ? $phone : '';
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function resolvePeriodState(array $query): array
    {
        $key = strtolower(trim((string) ($query['period'] ?? 'all')));
        $key = match ($key) {
            '0' => 'all',
            'all' => 'all',
            'tout' => 'all',
            '7d' => '7',
            '7j' => '7',
            '30d' => '30',
            '30j' => '30',
            '90d' => '90',
            '90j' => '90',
            '365d' => '365',
            '365j' => '365',
            'custom' => 'custom',
            default => $key,
        };
        if (!in_array($key, ['all', '7', '30', '90', '365', 'custom'], true)) {
            $key = 'all';
        }

        $warning = '';
        $today = new \DateTimeImmutable('today');
        $from = null;
        $to = null;
        $label = 'Depuis le début';

        if ($key === 'custom') {
            $fromCandidate = $this->parseDateInput((string) ($query['from'] ?? ''));
            $toCandidate = $this->parseDateInput((string) ($query['to'] ?? ''));
            $from = $fromCandidate;
            $to = $toCandidate;
            $label = 'Période personnalisée';
            if ($from && $to && $from > $to) {
                [$from, $to] = [$to, $from];
            }
        } elseif ($key === 'all') {
            $from = null;
            $to = null;
            $label = 'Depuis le début';
        } elseif ($key === '7') {
            $from = $today->modify('-6 days');
            $to = $today;
            $label = '7 derniers jours';
        } elseif ($key === '90') {
            $from = $today->modify('-89 days');
            $to = $today;
            $label = '3 derniers mois';
        } elseif ($key === '365') {
            $from = $today->modify('-364 days');
            $to = $today;
            $label = '12 derniers mois';
        } else {
            $from = $today->modify('-29 days');
            $to = $today;
            $label = '30 derniers jours';
        }

        return [
            'key' => $key,
            'from_date' => $from ? $from->format('Y-m-d') : '',
            'to_date' => $to ? $to->format('Y-m-d') : '',
            'start_at' => $from ? $from->format('Y-m-d 00:00:00') : '',
            'end_at' => $to ? $to->modify('+1 day')->format('Y-m-d 00:00:00') : '',
            'label' => $label,
            'warning' => $warning,
        ];
    }

    /**
     * @param array<string,mixed> $periodState
     */
    private function buildPeriodQuery(array $periodState): string
    {
        $key = (string) ($periodState['key'] ?? 'all');
        if ($key === '') {
            $key = 'all';
        }

        $params = ['period' => $key];
        if ($key === 'custom') {
            $from = trim((string) ($periodState['from_date'] ?? ''));
            $to = trim((string) ($periodState['to_date'] ?? ''));
            if ($from !== '') {
                $params['from'] = $from;
            }
            if ($to !== '') {
                $params['to'] = $to;
            }
        }
        $query = http_build_query($params);
        return $query !== '' ? ('?' . $query) : '';
    }

    private function normalizeWithdrawalMethod(string $method): string
    {
        $normalized = strtolower(trim($method));
        if (in_array($normalized, ['crypto'], true)) {
            return 'crypto';
        }
        return 'fedapay';
    }

    private function normalizeWithdrawalType(string $withdrawalType): string
    {
        return strtolower(trim($withdrawalType)) === 'referral' ? 'referral' : 'teacher_sales';
    }

    private function parseDateInput(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date instanceof \DateTimeImmutable) {
            return $date->setTime(0, 0, 0);
        }

        return null;
    }
}

