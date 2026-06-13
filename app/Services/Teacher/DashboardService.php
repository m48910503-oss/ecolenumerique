<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;
use App\Models\TeacherDashboardModel;
use App\Models\TeacherCoursesModel;
use App\Models\TeacherMeetingsModel;
use App\Models\TeacherReviewsModel;

final class DashboardService extends Service
{
    private TeacherDashboardModel $model;
    private \App\Models\TeacherWithdrawalsModel $withdrawals;
    private \App\Services\Shared\PlatformSettingsService $settings;
    private \App\Services\Shared\ReferralService $referrals;
    private TeacherCoursesModel $courses;
    private TeacherMeetingsModel $meetings;
    private TeacherReviewsModel $reviews;

    public function __construct(
        TeacherDashboardModel $model,
        \App\Models\TeacherWithdrawalsModel $withdrawals,
        \App\Services\Shared\PlatformSettingsService $settings,
        \App\Services\Shared\ReferralService $referrals,
        TeacherCoursesModel $courses,
        TeacherMeetingsModel $meetings,
        TeacherReviewsModel $reviews
    ) {
        $this->model = $model;
        $this->withdrawals = $withdrawals;
        $this->settings = $settings;
        $this->referrals = $referrals;
        $this->courses = $courses;
        $this->meetings = $meetings;
        $this->reviews = $reviews;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<int,string> $roles
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildViewData(array $user, array $roles, array $query = []): array
    {
        $isAdmin = in_array('admin', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);
        $teacherId = (int) ($user['id'] ?? 0);

        $periodState = $this->resolvePeriodState($query);
        $startAt = (string) $periodState['start_at'];
        $endAt = (string) $periodState['end_at'];
        $prevStartAt = (string) $periodState['prev_start_at'];
        $prevEndAt = (string) $periodState['prev_end_at'];

        $unreadCount = $this->model->countUnreadNotifications($teacherId);
        $supportUnreadMessages = 0;
        $supportUnreadNotifications = 0;
        try {
            $supportUnreadMessages = $this->model->countUnreadSupportReplies($teacherId);
        } catch (\Throwable $e) {
            $supportUnreadMessages = 0;
        }
        try {
            $supportUnreadNotifications = $this->model->countUnreadSupportNotifications($teacherId);
        } catch (\Throwable $e) {
            $supportUnreadNotifications = 0;
        }
        $supportUnreadCount = max($supportUnreadMessages, $supportUnreadNotifications);
        $notifications = $this->model->fetchNotifications($teacherId, 30);

        $inboxFilter = strtolower(trim((string) ($query['inbox'] ?? 'all')));
        if (!in_array($inboxFilter, ['all', 'admin', 'student', 'support'], true)) {
            $inboxFilter = 'all';
        }
        $inboxCounts = ['all' => 0, 'admin' => 0, 'student' => 0, 'support' => 0];
        $inboxRows = [];
        foreach ($notifications as $notification) {
            $text = strtolower((string) ($notification['title'] ?? '') . ' ' . (string) ($notification['body'] ?? ''));
            $type = 'admin';
            if (str_contains($text, 'support') || str_contains($text, 'ticket')) {
                $type = 'support';
            } elseif (
                str_contains($text, 'etudiant')
                || str_contains($text, 'cours')
                || str_contains($text, 'examen')
                || str_contains($text, 'commande')
                || str_contains($text, 'avis')
            ) {
                $type = 'student';
            }
            $inboxCounts[$type]++;
            $inboxCounts['all']++;
            $notification['inbox_type'] = $type;
            if ($inboxFilter === 'all' || $inboxFilter === $type) {
                $inboxRows[] = $notification;
            }
        }

        $pendingMeetingsCount = 0;
        try {
            $pendingMeetingsCount = $this->model->countPendingMeetings($isAdmin ? null : $teacherId);
        } catch (\Throwable $e) {
            $pendingMeetingsCount = 0;
        }

        $latestCourseUpdate = null;
        $latestCourseUpdateLabel = '';
        try {
            $latestCourseUpdate = $this->model->fetchLatestCourseUpdate($isAdmin ? null : $teacherId);
            if ($latestCourseUpdate) {
                $meta = json_decode((string) ($latestCourseUpdate['meta_json'] ?? ''), true) ?: [];
                $latestCourseUpdateLabel = (string) ($meta['label'] ?? 'Mise à jour');
            }
        } catch (\Throwable $e) {
            $latestCourseUpdate = null;
            $latestCourseUpdateLabel = '';
        }

        $courseStats = $this->model->fetchCourseStats($teacherId, 24);
        $completionSummary = $this->model->fetchCompletionSummary($teacherId);
        $pendingExamCount = $this->model->countPendingExamAttempts($teacherId);
        $pendingExamAttempts = $this->model->fetchPendingExamAttempts($teacherId, 12);
        $courseSales = $this->model->fetchPaidSalesByCourse($teacherId, 8, $startAt, $endAt);

        $salesNowRaw = $this->model->fetchCourseSalesWindowMap($teacherId, $startAt, $endAt);
        $salesPrevRaw = $this->model->fetchCourseSalesWindowMap($teacherId, $prevStartAt, $prevEndAt);
        $completionRaw = $this->model->fetchCourseCompletionByCourse($teacherId);
        $salesNowMap = [];
        $salesPrevMap = [];
        $completionMap = [];
        foreach ($salesNowRaw as $row) {
            $salesNowMap[(int) ($row['course_id'] ?? 0)] = (int) ($row['sales_count'] ?? 0);
        }
        foreach ($salesPrevRaw as $row) {
            $salesPrevMap[(int) ($row['course_id'] ?? 0)] = (int) ($row['sales_count'] ?? 0);
        }
        foreach ($completionRaw as $row) {
            $cid = (int) ($row['course_id'] ?? 0);
            $lessons = (int) ($row['lesson_count'] ?? 0);
            $students = (int) ($row['student_count'] ?? 0);
            $done = (int) ($row['done_count'] ?? 0);
            $possible = $lessons * $students;
            $completionMap[$cid] = $possible > 0 ? (int) round(($done / $possible) * 100) : 0;
        }

        $courseHealthRows = [];
        foreach ($courseStats as $course) {
            $cid = (int) ($course['id'] ?? 0);
            if ($cid < 1) {
                continue;
            }
            $signals = [];
            $score = 0;
            $views = (int) ($course['view_count'] ?? 0);
            $enroll = (int) ($course['enroll_count'] ?? 0);
            $rating = (float) ($course['avg_rating'] ?? 0);
            $ratingCount = (int) ($course['rating_count'] ?? 0);
            $salesNow = (int) ($salesNowMap[$cid] ?? 0);
            $salesPrev = (int) ($salesPrevMap[$cid] ?? 0);
            $completionRate = (int) ($completionMap[$cid] ?? 0);

            if ($salesPrev > 0 && $salesNow < $salesPrev) {
                $signals[] = 'Baisse des ventes';
                $score += 2;
            }
            if ($views >= 40 && $enroll > 0 && (($enroll / max(1, $views)) * 100) < 4.0) {
                $signals[] = 'Conversion faible';
                $score += 1;
            }
            if ($ratingCount >= 3 && $rating > 0 && $rating < 3.8) {
                $signals[] = 'Notes faibles';
                $score += 1;
            }
            if ($completionRate > 0 && $completionRate < 25) {
                $signals[] = 'Complétion faible';
                $score += 1;
            }
            $state = $score >= 3 ? 'Urgent' : ($score > 0 ? 'En attente' : 'Actif');
            $courseHealthRows[] = [
                'course_id' => $cid,
                'title' => (string) ($course['title'] ?? 'Cours'),
                'status' => (string) ($course['status'] ?? 'draft'),
                'state' => $state,
                'signals' => $signals,
                'views' => $views,
                'enroll_count' => $enroll,
                'rating' => $rating,
                'rating_count' => $ratingCount,
                'completion_rate' => $completionRate,
                'sales_now' => $salesNow,
                'sales_prev' => $salesPrev,
            ];
        }
        usort($courseHealthRows, static function (array $a, array $b): int {
            $stateRank = static function (string $state): int {
                if ($state === 'Urgent') {
                    return 3;
                }
                if ($state === 'En attente') {
                    return 2;
                }
                return 1;
            };
            $ra = $stateRank((string) ($a['state'] ?? 'Actif'));
            $rb = $stateRank((string) ($b['state'] ?? 'Actif'));
            if ($ra !== $rb) {
                return $rb <=> $ra;
            }
            return ((int) ($b['views'] ?? 0)) <=> ((int) ($a['views'] ?? 0));
        });
        $courseHealthRows = array_slice($courseHealthRows, 0, 10);

        $correctionQueue = [];
        $correctionUrgentCount = 0;
        foreach ($pendingExamAttempts as $attempt) {
            $submittedAt = (string) ($attempt['submitted_at'] ?? '');
            $submittedTs = $submittedAt !== '' ? strtotime($submittedAt) : false;
            $ageHours = $submittedTs !== false ? max(0, (int) floor((time() - $submittedTs) / 3600)) : 0;
            $state = $ageHours >= 72 ? 'Urgent' : 'En attente';
            if ($state === 'Urgent') {
                $correctionUrgentCount++;
            }
            $attempt['age_hours'] = $ageHours;
            $attempt['state'] = $state;
            $correctionQueue[] = $attempt;
        }
        usort($correctionQueue, static function (array $a, array $b): int {
            return ((int) ($b['age_hours'] ?? 0)) <=> ((int) ($a['age_hours'] ?? 0));
        });
        $courseTotals = ['total' => 0, 'draft' => 0, 'submitted' => 0, 'approved' => 0, 'rejected' => 0];
        $totalActiveEnrollments = 0;
        $totalCourseViews = 0;
        $ratingSummary = ['avg' => 0.0, 'count' => 0];
        try {
            foreach ($this->model->fetchCourseTotalsByStatus($teacherId) as $row) {
                $status = (string) ($row['status'] ?? '');
                $cnt = (int) ($row['cnt'] ?? 0);
                $courseTotals['total'] += $cnt;
                if (isset($courseTotals[$status])) {
                    $courseTotals[$status] = $cnt;
                }
            }
            $totalActiveEnrollments = $this->model->countActiveEnrollments($teacherId);
            $totalCourseViews = $this->model->countTotalCourseViews($teacherId);
            $ratingSummary = $this->model->fetchRatingSummary($teacherId);
        } catch (\Throwable $e) {
        }

        $lifetimePortfolioRows = [];
        try {
            $lifetimePortfolioRows = $this->model->fetchApprovedCoursePortfolioRows($teacherId);
        } catch (\Throwable $e) {
            $lifetimePortfolioRows = [];
        }
        $lifetimeSalesCount = 0;
        $lifetimePaidOrders = 0;
        $lifetimeGrossAmount = 0;
        foreach ($lifetimePortfolioRows as $row) {
            $lifetimeSalesCount += (int) ($row['paid_sales'] ?? 0);
            $lifetimePaidOrders += (int) ($row['paid_orders'] ?? 0);
            $lifetimeGrossAmount += (int) ($row['gross_amount'] ?? 0);
        }

        $commissionPercent = $this->settings->getFloat('commission_percent', 20.0);
        $monthStart = (new \DateTimeImmutable('first day of this month 00:00:00'))->format('Y-m-d H:i:s');
        $nextMonthStart = (new \DateTimeImmutable('first day of next month 00:00:00'))->format('Y-m-d H:i:s');

        $grossMonth = 0;
        $grossTotal = 0;
        $grossPeriod = 0;
        $grossPrev = 0;
        $referralMonth = 0;
        $referralTotal = 0;
        $referralPeriod = 0;
        $referralPrev = 0;
        $pendingWithdrawals = 0;
        $paidWithdrawals = 0;
        $paidPayouts = 0;
        $nextPayout = null;
        $monthlySales = [];
        try {
            $grossMonth = $this->withdrawals->sumGrossSales($teacherId, $monthStart, $nextMonthStart);
            $grossTotal = $this->withdrawals->sumGrossSales($teacherId);
            $grossPeriod = $this->withdrawals->sumGrossSales($teacherId, $startAt, $endAt);
            $grossPrev = $this->withdrawals->sumGrossSales($teacherId, $prevStartAt, $prevEndAt);
            $referralMonth = $this->withdrawals->sumReferralEarnings($teacherId, $monthStart, $nextMonthStart);
            $referralTotal = $this->withdrawals->sumReferralEarnings($teacherId);
            $referralPeriod = $this->withdrawals->sumReferralEarnings($teacherId, $startAt, $endAt);
            $referralPrev = $this->withdrawals->sumReferralEarnings($teacherId, $prevStartAt, $prevEndAt);
            $pendingWithdrawals = $this->withdrawals->sumPendingWithdrawals($teacherId);
            $paidWithdrawals = $this->withdrawals->sumPaidWithdrawals($teacherId);
            $paidPayouts = $this->withdrawals->sumPaidPayouts($teacherId);
            $nextPayout = $this->withdrawals->fetchNextPayout($teacherId);
            $trendStart = (new \DateTimeImmutable('first day of -5 months 00:00:00'))->format('Y-m-d H:i:s');
            $monthlySales = $this->withdrawals->fetchMonthlyGrossSales($teacherId, $trendStart, $nextMonthStart);
            foreach ($monthlySales as &$row) {
                $labelTs = strtotime((string) ($row['month'] ?? '') . '-01');
                $row['label'] = $labelTs ? date('M', $labelTs) : '';
            }
            unset($row);
        } catch (\Throwable $e) {
        }

        $netMonth = max(0, $grossMonth - (int) round($grossMonth * $commissionPercent / 100) - $referralMonth);
        $netTotal = max(0, $grossTotal - (int) round($grossTotal * $commissionPercent / 100) - $referralTotal);
        $netPeriod = max(0, $grossPeriod - (int) round($grossPeriod * $commissionPercent / 100) - $referralPeriod);
        $netPrev = max(0, $grossPrev - (int) round($grossPrev * $commissionPercent / 100) - $referralPrev);
        $available = max(0, $netTotal - $paidWithdrawals - $paidPayouts - $pendingWithdrawals);

        $netDeltaPct = $netPrev > 0 ? round((($netPeriod - $netPrev) / $netPrev) * 100, 1) : ($netPeriod > 0 ? 100.0 : 0.0);
        $periodSalesCount = 0;
        $prevSalesCount = 0;
        foreach ($salesNowMap as $count) {
            $periodSalesCount += (int) $count;
        }
        foreach ($salesPrevMap as $count) {
            $prevSalesCount += (int) $count;
        }
        $salesDeltaPct = $prevSalesCount > 0 ? round((($periodSalesCount - $prevSalesCount) / $prevSalesCount) * 100, 1) : ($periodSalesCount > 0 ? 100.0 : 0.0);

        $conversionRate = $totalCourseViews > 0 ? round(($totalActiveEnrollments / $totalCourseViews) * 100, 1) : 0.0;
        $todoItems = [];
        if ($pendingExamCount > 0) {
            $todoItems[] = [
                'label' => 'Corriger les examens',
                'count' => $pendingExamCount,
                'url' => $this->siteUrl('teacher/exams_manage'),
                'tone' => 'amber',
                'meta' => $correctionUrgentCount > 0 ? ($correctionUrgentCount . ' urgent(s)') : "À traiter aujourd'hui",
                'critical' => $correctionUrgentCount > 0,
            ];
        }
        if ($supportUnreadCount > 0) {
            $todoItems[] = [
                'label' => 'Répondre au support',
                'count' => $supportUnreadCount,
                'url' => $this->siteUrl('support'),
                'tone' => 'emerald',
                'meta' => $supportUnreadMessages > 0 ? $supportUnreadMessages . ' message(s) support' : 'Boîte de support',
                'critical' => $supportUnreadCount >= 5,
            ];
        }
        if ($courseTotals['submitted'] > 0) {
            $todoItems[] = [
                'label' => 'Publier les cours',
                'count' => $courseTotals['submitted'],
                'url' => $this->siteUrl('teacher/courses?status=submitted'),
                'tone' => 'sky',
                'meta' => 'Finaliser avant validation admin',
                'critical' => false,
            ];
        }
        if ($pendingMeetingsCount > 0) {
            $todoItems[] = [
                'label' => 'Confirmer les rendez-vous',
                'count' => $pendingMeetingsCount,
                'url' => $this->siteUrl('teacher/meetings?status=pending'),
                'tone' => 'purple',
                'meta' => 'Sessions en attente de validation',
                'critical' => false,
            ];
        }

        $minimalPriorityTasks = array_slice($todoItems, 0, 2);
        $topPriorityTask = $minimalPriorityTasks[0] ?? null;
        if ($lifetimeSalesCount > 0) {
            $salesLabel = $lifetimeSalesCount === 1 ? 'vente payée' : 'ventes payées';
            $teacherOverviewSummary = 'Depuis votre inscription, vous avez enregistré ' . number_format($lifetimeSalesCount, 0, ',', ' ') . ' ' . $salesLabel . ' pour ' . number_format($lifetimeGrossAmount, 0, ',', ' ') . ' F CFA de chiffre d\'affaires brut.';
        } else {
            $teacherOverviewSummary = "Depuis votre inscription, aucune vente payée n'a été enregistrée.";
        }
        $ratingCount = (int) ($ratingSummary['count'] ?? 0);
        $ratingAvg = (float) ($ratingSummary['avg'] ?? 0);
        if ($ratingCount > 0) {
            $teacherOverviewSummary .= ' Votre note moyenne est de ' . number_format($ratingAvg, 2, ',', ' ') . '/5 sur ' . number_format($ratingCount, 0, ',', ' ') . ' avis.';
        }
        if ($topPriorityTask !== null) {
            $teacherOverviewSummary .= ' Priorité du moment : ' . (string) ($topPriorityTask['label'] ?? 'Action') . '.';
        } elseif ($pendingExamCount > 0 || $supportUnreadCount > 0 || $pendingMeetingsCount > 0) {
            $teacherOverviewSummary .= ' Votre file de tâches reste active.';
        } else {
            $teacherOverviewSummary .= ' Votre activité reste régulière sur la période active.';
        }

        $courseFunnelRows = $this->model->fetchCourseFunnelRows($teacherId, $startAt, $endAt, 10);
        foreach ($courseFunnelRows as &$row) {
            $views = (int) ($row['views_count'] ?? 0);
            $sales = (int) ($row['paid_sales'] ?? 0);
            $row['view_to_sale_rate'] = $views > 0 ? round(($sales / $views) * 100, 1) : 0.0;
        }
        unset($row);

        $goalDefault = $netMonth > 0 ? max(100000, (int) ceil(($netMonth * 1.3) / 50000) * 50000) : 300000;
        $goalXof = $this->resolveGoalXof($query, $goalDefault);
        $now = new \DateTimeImmutable('today');
        $daysInMonth = (int) $now->format('t');
        $dayInMonth = (int) $now->format('j');
        $expectedPct = $daysInMonth > 0 ? round(($dayInMonth / $daysInMonth) * 100, 1) : 0.0;
        $goalPct = $goalXof > 0 ? max(0.0, round(($netMonth / $goalXof) * 100, 1)) : 0.0;
        $goalStatus = $goalPct >= 100.0 ? 'Terminé' : (($goalPct + 5 < $expectedPct) ? 'Urgent' : 'Actif');
        $goalExpectedXof = (int) round(($goalXof * $expectedPct) / 100);
        $goalProjectedXof = $dayInMonth > 0 ? (int) round(($netMonth / $dayInMonth) * $daysInMonth) : 0;

        $estimatedPayoutAt = !empty($nextPayout['created_at'])
            ? date('Y-m-d H:i:s', strtotime('+7 days', strtotime((string) $nextPayout['created_at']) ?: time()))
            : (new \DateTimeImmutable('first day of next month 09:00:00'))->format('Y-m-d H:i:s');
        $financialCalendar = [
            ['label' => 'Revenus attendus sur la période', 'amount_xof' => $netPeriod, 'meta' => (string) $periodState['label'], 'status' => 'Actif'],
            ['label' => 'Retraits en attente', 'amount_xof' => $pendingWithdrawals, 'meta' => $pendingWithdrawals > 0 ? 'Validation en cours' : 'Aucun retrait en attente', 'status' => $pendingWithdrawals > 0 ? 'En attente' : 'Terminé'],
            ['label' => 'Paiement estimé', 'amount_xof' => $nextPayout ? (int) ($nextPayout['teacher_amount_xof'] ?? 0) : $available, 'meta' => $estimatedPayoutAt, 'status' => $available > 0 ? 'Actif' : 'En attente'],
        ];

        $automationRules = [
            ['name' => 'Coupon auto panier abandonné', 'status' => ($periodSalesCount > 0 && $conversionRate < 4.0) ? 'Urgent' : 'En attente', 'description' => 'Déclenche un coupon 24h après abandon.', 'cta_label' => 'Configurer', 'cta_url' => $this->siteUrl('teacher/promotions#section-coupons')],
            ['name' => 'Revue hebdo des avis', 'status' => count($courseHealthRows) > 0 ? 'Actif' : 'En attente', 'description' => 'Traitez les avis sensibles et ajustez les cours.', 'cta_label' => 'Avis', 'cta_url' => $this->siteUrl('teacher/reviews')],
            ['name' => 'Upsell apprenants engagés', 'status' => ((int) ($completionSummary['rate'] ?? 0) >= 40) ? 'Actif' : 'En attente', 'description' => 'Offre avancée aux étudiants engagés.', 'cta_label' => 'Promotions', 'cta_url' => $this->siteUrl('teacher/promotions#section-promotions')],
        ];

        $completionRateGlobal = (int) ($completionSummary['rate'] ?? 0);
        $completionGap = round((float) $completionRateGlobal - 60.0, 1);
        $netTone = $netDeltaPct < 0 ? 'rose' : ($netDeltaPct > 0 ? 'emerald' : 'slate');
        $salesTone = $salesDeltaPct < 0 ? 'rose' : ($salesDeltaPct > 0 ? 'emerald' : 'slate');
        $completionTone = $completionRateGlobal >= 60 ? 'emerald' : ($completionRateGlobal >= 40 ? 'amber' : 'rose');
        $balanceTone = $available > 0 ? 'slate' : 'amber';

        $kpiCards = [
            ['label' => 'Revenus nets (' . (string) $periodState['label'] . ')', 'value' => $netPeriod, 'type' => 'money', 'delta' => $netDeltaPct, 'delta_label' => 'vs période précédente', 'tone' => $netTone],
            ['label' => 'Ventes sur période', 'value' => $periodSalesCount, 'type' => 'count', 'delta' => $salesDeltaPct, 'delta_label' => 'vs période précédente', 'tone' => $salesTone],
            ['label' => 'Taux de complétion global', 'value' => $completionRateGlobal, 'type' => 'percent', 'delta' => $completionGap, 'delta_label' => 'Écart vs. cible (60 %)', 'tone' => $completionTone],
            ['label' => 'Solde disponible', 'value' => $available, 'type' => 'money', 'delta' => null, 'delta_label' => 'Disponible au retrait', 'tone' => $balanceTone],
        ];

        $courseStatusLabels = ['draft' => 'Brouillon', 'submitted' => 'Soumis', 'approved' => 'Actif', 'rejected' => 'Rejeté'];
        $fmtDt = static function (?string $dbValue): string {
            if (!$dbValue) {
                return '';
            }
            $ts = strtotime($dbValue);
            return $ts ? date('d/m/Y H:i', $ts) : (string) $dbValue;
        };

        // Defensive cleanup: normalize occasional mojibake artifacts in UI labels.
        $latestCourseUpdateLabel = $this->normalizeUiText((string) $latestCourseUpdateLabel);
        $periodState['label'] = $this->normalizeUiText((string) ($periodState['label'] ?? ''));
        $periodState['warning'] = $this->normalizeUiText((string) ($periodState['warning'] ?? ''));
        foreach ($todoItems as &$todoItem) {
            $todoItem['label'] = $this->normalizeUiText((string) ($todoItem['label'] ?? ''));
        }
        unset($todoItem);
        foreach ($financialCalendar as &$calendarItem) {
            $calendarItem['label'] = $this->normalizeUiText((string) ($calendarItem['label'] ?? ''));
            $calendarItem['meta'] = $this->normalizeUiText((string) ($calendarItem['meta'] ?? ''));
            $calendarItem['status'] = $this->normalizeUiText((string) ($calendarItem['status'] ?? ''));
        }
        unset($calendarItem);
        foreach ($automationRules as &$automationRule) {
            $automationRule['name'] = $this->normalizeUiText((string) ($automationRule['name'] ?? ''));
            $automationRule['description'] = $this->normalizeUiText((string) ($automationRule['description'] ?? ''));
            $automationRule['cta_label'] = $this->normalizeUiText((string) ($automationRule['cta_label'] ?? ''));
        }
        unset($automationRule);
        foreach ($kpiCards as &$kpiCard) {
            $kpiCard['label'] = $this->normalizeUiText((string) ($kpiCard['label'] ?? ''));
            $kpiCard['delta_label'] = $this->normalizeUiText((string) ($kpiCard['delta_label'] ?? ''));
        }
        unset($kpiCard);
        foreach ($courseStatusLabels as $statusKey => $statusLabel) {
            $courseStatusLabels[$statusKey] = $this->normalizeUiText((string) $statusLabel);
        }

        $dashboardRange = $this->resolveDashboardRange($query);
        $completionDone = (int) ($completionSummary['done'] ?? 0);
        $completionTotal = (int) ($completionSummary['total'] ?? 0);
        $dashboardKpis = [
            [
                'label' => 'Total étudiants',
                'value' => $totalActiveEnrollments,
                'type' => 'count',
                'meta' => $completionDone > 0 && $completionTotal > 0
                    ? ($completionDone . '/' . $completionTotal . ' complétions')
                    : 'Vue globale des inscriptions',
                'tone' => 'sky',
                'icon' => 'user-graduate',
            ],
            [
                'label' => 'Formations publiées',
                'value' => (int) ($courseTotals['approved'] ?? 0),
                'type' => 'count',
                'meta' => ((int) ($courseTotals['submitted'] ?? 0)) > 0
                    ? ((int) ($courseTotals['submitted'] ?? 0) . ' en attente de validation')
                    : 'Catalogues actifs',
                'tone' => 'cyan',
                'icon' => 'layer-group',
            ],
            [
                'label' => 'Revenus du mois',
                'value' => $netMonth,
                'type' => 'money',
                'meta' => $pendingWithdrawals > 0
                    ? ($moneyFmt($pendingWithdrawals) . ' en attente')
                    : 'Paiement disponible',
                'tone' => 'emerald',
                'icon' => 'euro-sign',
            ],
            [
                'label' => 'Note moyenne',
                'value' => $ratingSummary['avg'] ?? 0,
                'type' => 'rating',
                'meta' => ((int) ($ratingSummary['count'] ?? 0)) > 0
                    ? (((int) ($ratingSummary['count'] ?? 0)) === 1
                        ? '1 avis reçu'
                        : ((int) ($ratingSummary['count'] ?? 0) . ' avis reçus'))
                    : 'Aucun avis pour le moment',
                'tone' => 'amber',
                'icon' => 'star',
            ],
        ];
        $dashboardCourses = $this->buildDashboardCourseCards($teacherId, $courseStats, $courseTotals);
        $dashboardMeetings = $this->buildDashboardMeetings($teacherId, $isAdmin);
        $dashboardReviews = $this->buildDashboardReviews($teacherId);
        $dashboardChart = $this->buildDashboardChart(
            $teacherId,
            $dashboardRange,
            (string) ($user['created_at'] ?? ''),
            $totalActiveEnrollments
        );
        $dashboardHeroSummary = $teacherOverviewSummary;
        if ($dashboardCourses !== []) {
            $topCourse = $dashboardCourses[0];
            $topCourseTitle = trim((string) ($topCourse['title'] ?? ''));
            $topCourseStudents = max(0, (int) ($topCourse['students'] ?? 0));
            if ($topCourseTitle !== '' && $topCourseStudents > 0) {
                $courseLabel = $topCourseStudents === 1 ? 'étudiant' : 'étudiants';
                $dashboardHeroSummary = 'Votre cours "' . $topCourseTitle . '" compte ' . number_format($topCourseStudents, 0, ',', ' ') . ' ' . $courseLabel . '.';
                if ($topPriorityTask !== null) {
                    $dashboardHeroSummary .= ' Priorité du moment : ' . (string) ($topPriorityTask['label'] ?? 'Action') . '.';
                } else {
                    $dashboardHeroSummary .= ' Votre activité reste régulière sur la période active.';
                }
            }
        }

        return [
            'user' => $user,
            'roles' => $roles,
            'isAdmin' => $isAdmin,
            'isTeacher' => $isTeacher,
            'periodState' => $periodState,
            'unreadCount' => $unreadCount,
            'supportUnreadCount' => $supportUnreadCount,
            'supportUnreadMessages' => $supportUnreadMessages,
            'supportUnreadNotifications' => $supportUnreadNotifications,
            'notifications' => $notifications,
            'inboxState' => ['filter' => $inboxFilter, 'counts' => $inboxCounts, 'rows' => $inboxRows],
            'pendingMeetingsCount' => $pendingMeetingsCount,
            'pendingExamCount' => $pendingExamCount,
            'pendingExamAttempts' => $pendingExamAttempts,
            'correctionQueue' => $correctionQueue,
            'correctionUrgentCount' => $correctionUrgentCount,
            'latestCourseUpdate' => $latestCourseUpdate,
            'latestCourseUpdateLabel' => $latestCourseUpdateLabel,
            'courseStats' => $courseStats,
            'courseSales' => $courseSales,
            'courseHealthRows' => $courseHealthRows,
            'courseTotals' => $courseTotals,
            'totalActiveEnrollments' => $totalActiveEnrollments,
            'totalCourseViews' => $totalCourseViews,
            'ratingSummary' => $ratingSummary,
            'completionSummary' => $completionSummary,
            'conversionRate' => $conversionRate,
            'courseFunnelRows' => $courseFunnelRows,
            'automationRules' => $automationRules,
            'monthlyGoal' => [
                'goal_xof' => $goalXof,
                'current_xof' => $netMonth,
                'progress_pct' => $goalPct,
                'expected_progress_pct' => $expectedPct,
                'expected_xof' => $goalExpectedXof,
                'projected_xof' => $goalProjectedXof,
                'status' => $goalStatus,
            ],
            'financialCalendar' => $financialCalendar,
            'kpiCards' => $kpiCards,
            'monthlySales' => $monthlySales,
            'commissionPercent' => $commissionPercent,
            'grossMonth' => $grossMonth,
            'grossTotal' => $grossTotal,
            'netMonth' => $netMonth,
            'netPeriod' => $netPeriod,
            'pendingWithdrawals' => $pendingWithdrawals,
            'availableBalance' => $available,
            'nextPayout' => $nextPayout,
            'netTotal' => $netTotal,
            'paidWithdrawals' => $paidWithdrawals,
            'paidPayouts' => $paidPayouts,
            'todoItems' => $todoItems,
            'courseStatusLabels' => $courseStatusLabels,
            'fmtDt' => $fmtDt,
            'dashboardRange' => $dashboardRange,
            'dashboardKpis' => $dashboardKpis,
            'dashboardCourses' => $dashboardCourses,
            'dashboardMeetings' => $dashboardMeetings,
            'dashboardReviews' => $dashboardReviews,
            'dashboardChart' => $dashboardChart,
            'dashboardHeroSummary' => $dashboardHeroSummary,
            'dashboardTasks' => $minimalPriorityTasks,
        ];
    }

    private function normalizeUiText(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $fixed = strtr($value, [
            'Ã©' => 'e',
            'Ã¨' => 'e',
            'Ãª' => 'e',
            'Ã«' => 'e',
            'Ã‰' => 'E',
            'Ãˆ' => 'E',
            'ÃŠ' => 'E',
            'Ã ' => 'a',
            'Ã¢' => 'a',
            'Ã¤' => 'a',
            'Ã¹' => 'u',
            'Ã»' => 'u',
            'Ã¼' => 'u',
            'Ã®' => 'i',
            'Ã¯' => 'i',
            'Ã´' => 'o',
            'Ã¶' => 'o',
            'â€™' => "'",
            'â€˜' => "'",
            'â€œ' => '"',
            'â€�' => '"',
            'â€“' => '-',
            'â€”' => '-',
        ]);

        return preg_replace('/\s+/', ' ', trim($fixed)) ?: '';
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
            '7j' => '7d',
            '30j' => '30d',
            '90j' => '90d',
            default => $key,
        };
        if (!in_array($key, ['all', '7d', '30d', '90d', 'custom'], true)) {
            $key = 'all';
        }

        $warning = '';
        $today = new \DateTimeImmutable('today');
        $from = null;
        $to = null;
        $label = "Depuis le d\u{00e9}but";

        if ($key === '7d') {
            $from = $today->modify('-6 days');
            $to = $today;
            $label = '7 derniers jours';
        } elseif ($key === '90d') {
            $from = $today->modify('-89 days');
            $to = $today;
            $label = '90 derniers jours';
        } elseif ($key === 'custom') {
            $rawFrom = trim((string) ($query['from'] ?? ''));
            $rawTo = trim((string) ($query['to'] ?? ''));
            $fromTs = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) === 1) ? strtotime($rawFrom . ' 00:00:00') : false;
            $toTs = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo) === 1) ? strtotime($rawTo . ' 00:00:00') : false;
            if ($fromTs === false || $toTs === false || $fromTs > $toTs) {
                $warning = '';
                $key = 'all';
            } else {
                $from = (new \DateTimeImmutable())->setTimestamp($fromTs);
                $to = (new \DateTimeImmutable())->setTimestamp($toTs);
                $label = 'Période personnalisée';
            }
        }
        if ($key === '30d') {
            $from = $today->modify('-29 days');
            $to = $today;
            $label = '30 derniers jours';
        }

        $days = 0;
        if ($from && $to) {
            $days = (int) $from->diff($to)->days + 1;
        }
        if ($days < 1) {
            $days = 1;
        }

        $startAt = $from ? $from->format('Y-m-d 00:00:00') : '';
        $endAt = $to ? $to->modify('+1 day')->format('Y-m-d 00:00:00') : '';
        $prevEnd = $from ? $from->format('Y-m-d 00:00:00') : '';
        $prevStart = $from ? $from->modify('-' . $days . ' days')->format('Y-m-d 00:00:00') : '';

        return [
            'key' => $key,
            'from_date' => $from ? $from->format('Y-m-d') : '',
            'to_date' => $to ? $to->format('Y-m-d') : '',
            'start_at' => $startAt,
            'end_at' => $endAt,
            'prev_start_at' => $prevStart,
            'prev_end_at' => $prevEnd,
            'label' => $label,
            'warning' => $warning,
        ];
    }

    /**
     * @param array<string,mixed> $query
     */
    private function resolveGoalXof(array $query, int $default): int
    {
        $raw = trim((string) ($query['goal_xof'] ?? ''));
        if ($raw === '' || !ctype_digit($raw)) {
            return $default;
        }
        $value = (int) $raw;
        if ($value < 50000) {
            return 50000;
        }
        return $value;
    }

    /**
     * @param array<string,mixed> $query
     */
    private function resolveDashboardRange(array $query): string
    {
        $range = strtolower(trim((string) ($query['dashboard_range'] ?? '30d')));
        $range = match ($range) {
            '0', 'tout', 'all-time', 'lifetime' => 'all',
            default => $range,
        };
        return in_array($range, ['30d', '3m', 'all'], true) ? $range : '30d';
    }

    /**
     * @return array{title:string,range_label:string,labels:array<int,string>,values:array<int,int>,metric_label:string}
     */
    private function buildDashboardChart(int $teacherId, string $range, string $accountCreatedAt = '', int $totalActiveEnrollments = 0): array
    {
        $range = in_array($range, ['30d', '3m', 'all'], true) ? $range : '30d';
        $granularity = $range === '3m' ? 'month' : 'week';
        $today = new \DateTimeImmutable('today');
        if ($range === 'all') {
            $startDates = [];
            $accountCreatedTs = strtotime(trim($accountCreatedAt));
            if ($accountCreatedTs !== false) {
                $startDates[] = (new \DateTimeImmutable())->setTimestamp($accountCreatedTs)->setTime(0, 0, 0);
            }

            try {
                $firstMetricAt = $this->model->fetchTeacherMetricFirstDate($teacherId, 'active_enrollments');
            } catch (\Throwable $e) {
                $firstMetricAt = '';
            }
            $firstMetricTs = strtotime(trim($firstMetricAt));
            if ($firstMetricTs !== false) {
                $startDates[] = (new \DateTimeImmutable())->setTimestamp($firstMetricTs)->setTime(0, 0, 0);
            }

            $from = $today;
            foreach ($startDates as $candidate) {
                if ($candidate < $from) {
                    $from = $candidate;
                }
            }
            if ($from > $today) {
                $from = $today;
            }

            $spanDays = (int) $from->diff($today)->days + 1;
            if ($spanDays > 730) {
                $granularity = 'year';
                $from = $from->setDate((int) $from->format('Y'), 1, 1)->setTime(0, 0, 0);
                $to = $today->setDate((int) $today->format('Y') + 1, 1, 1)->setTime(0, 0, 0);
            } else {
                $granularity = 'month';
                $from = $from->modify('first day of this month')->setTime(0, 0, 0);
                $to = $today->modify('first day of next month')->setTime(0, 0, 0);
            }
            $rangeLabel = 'Depuis la création du compte';
        } elseif ($range === '3m') {
            $from = $today->modify('first day of this month')->modify('-2 months')->setTime(0, 0, 0);
            $to = $today->modify('first day of next month')->setTime(0, 0, 0);
            $rangeLabel = '3 derniers mois';
        } else {
            $from = $today->modify('monday this week')->modify('-3 weeks')->setTime(0, 0, 0);
            $to = $today->modify('monday next week')->setTime(0, 0, 0);
            $rangeLabel = '30 derniers jours';
        }

        $rows = [];
        try {
            $rows = $this->model->fetchTeacherMetricSeriesForRange(
                $teacherId,
                'active_enrollments',
                $granularity,
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s')
            );
        } catch (\Throwable $e) {
            $rows = [];
        }

        $lookup = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['bucket_key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $lookup[$key] = (int) ($row['metric_value'] ?? 0);
        }

        $monthLabels = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Avr',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juil',
            8 => 'Aout',
            9 => 'Sept',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];

        $labels = [];
        $values = [];
        $cursor = $from;
        while ($cursor < $to) {
            if ($granularity === 'year') {
                $bucketKey = $cursor->format('Y');
                $label = $cursor->format('Y');
                $cursor = $cursor->modify('+1 year');
            } elseif ($granularity === 'month') {
                $bucketKey = $cursor->format('Y-m');
                $monthNo = (int) $cursor->format('n');
                $label = ($monthLabels[$monthNo] ?? $cursor->format('M')) . ' ' . $cursor->format('y');
                $cursor = $cursor->modify('+1 month');
            } else {
                $bucketKey = $cursor->format('Y-m-d');
                $label = 'S' . $cursor->format('W');
                $cursor = $cursor->modify('+1 week');
            }

            $labels[] = $label;
            $values[] = (int) ($lookup[$bucketKey] ?? 0);
        }

        if ($range === 'all' && array_sum($values) === 0 && $totalActiveEnrollments > 0) {
            $labels = ['Tout'];
            $values = [$totalActiveEnrollments];
        }

        if ($labels === []) {
            $labels = match ($range) {
                'all' => ['Tout'],
                '3m' => ['M-2', 'M-1', 'M0'],
                default => ['S1', 'S2', 'S3', 'S4'],
            };
            $values = array_fill(0, count($labels), 0);
        }

        return [
            'title' => 'Évolution des inscriptions',
            'range_label' => $rangeLabel,
            'labels' => $labels,
            'values' => $values,
            'metric_label' => 'Inscriptions',
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $courseStats
     * @param array<string,int> $courseTotals
     * @return array<int,array<string,mixed>>
     */
    private function buildDashboardCourseCards(int $teacherId, array $courseStats, array $courseTotals): array
    {
        $courses = [];
        try {
            $courses = $this->courses->listCoursesForTeacher($teacherId);
        } catch (\Throwable $e) {
            $courses = [];
        }

        $statsByCourseId = [];
        foreach ($courseStats as $row) {
            $courseId = (int) ($row['id'] ?? 0);
            if ($courseId < 1) {
                continue;
            }
            $statsByCourseId[$courseId] = [
                'students' => (int) ($row['enroll_count'] ?? 0),
                'views' => (int) ($row['view_count'] ?? 0),
                'rating' => (float) ($row['avg_rating'] ?? 0),
                'rating_count' => (int) ($row['rating_count'] ?? 0),
            ];
        }

        $statusRank = static function (string $status): int {
            return match ($status) {
                'approved' => 4,
                'submitted' => 3,
                'draft' => 2,
                'rejected' => 1,
                default => 0,
            };
        };

        usort($courses, static function (array $left, array $right) use ($statusRank): int {
            $leftRank = $statusRank((string) ($left['status'] ?? ''));
            $rightRank = $statusRank((string) ($right['status'] ?? ''));
            if ($leftRank !== $rightRank) {
                return $rightRank <=> $leftRank;
            }

            $leftTs = strtotime((string) ($left['updated_at'] ?? $left['created_at'] ?? '')) ?: 0;
            $rightTs = strtotime((string) ($right['updated_at'] ?? $right['created_at'] ?? '')) ?: 0;
            if ($leftTs !== $rightTs) {
                return $rightTs <=> $leftTs;
            }

            return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        $courses = array_slice($courses, 0, 4);
        $courseCards = [];
        foreach ($courses as $course) {
            $courseId = (int) ($course['id'] ?? 0);
            if ($courseId < 1) {
                continue;
            }

            $status = strtolower(trim((string) ($course['status'] ?? 'draft')));
            $stats = $statsByCourseId[$courseId] ?? ['students' => 0, 'views' => 0, 'rating' => 0.0, 'rating_count' => 0];
            $students = (int) ($stats['students'] ?? 0);
            $coverPath = trim((string) ($course['cover_path'] ?? ''));
            $courseCards[] = [
                'id' => $courseId,
                'title' => $this->normalizeUiText((string) ($course['title'] ?? 'Cours')),
                'status' => $status,
                'status_label' => match ($status) {
                    'approved' => 'Publié',
                    'submitted' => 'Soumis',
                    'rejected' => 'Rejeté',
                    default => 'Brouillon',
                },
                'status_tone' => match ($status) {
                    'approved' => 'emerald',
                    'submitted' => 'amber',
                    'rejected' => 'rose',
                    default => 'slate',
                },
                'students' => $students,
                'students_label' => number_format($students, 0, ',', ' ') . ' étudiants',
                'price' => (int) ($course['price_xof'] ?? 0),
                'price_label' => ($status === 'draft' || (int) ($course['price_xof'] ?? 0) <= 0)
                    ? '--'
                    : number_format((int) ($course['price_xof'] ?? 0), 0, ',', ' ') . ' F CFA',
                'cover_url' => $this->resolveMediaUrl($coverPath),
                'cover_alt' => $this->normalizeUiText((string) ($course['cover_alt'] ?? 'Miniature du cours')),
                'rating' => (float) ($stats['rating'] ?? 0),
                'rating_count' => (int) ($stats['rating_count'] ?? 0),
                'url' => $status === 'approved'
                    ? $this->siteUrl('teacher/course_preview?id=' . $courseId)
                    : $this->siteUrl('teacher/course_edit?id=' . $courseId),
            ];
        }

        return $courseCards;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildDashboardMeetings(int $teacherId, bool $isAdmin): array
    {
        $meetingsHasDuration = $this->schema()->ensureCourseMeetingsDurationColumn();
        try {
            $rows = $this->meetings->findMeetingRequests($isAdmin, $teacherId, 'pending', 0, '', $meetingsHasDuration);
        } catch (\Throwable $e) {
            $rows = [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $keyData = [
                'course_id' => (int) ($row['course_id'] ?? 0),
                'chapter_id' => $row['chapter_id'] !== null ? (int) $row['chapter_id'] : null,
                'title' => (string) ($row['title'] ?? ''),
                'mode' => (string) ($row['mode'] ?? ''),
                'student_id' => (int) ($row['student_id'] ?? 0),
                'status' => (string) ($row['status'] ?? ''),
            ];
            $key = md5((string) json_encode($keyData));
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['row' => $row, 'count' => 0];
            }
            $grouped[$key]['count']++;
        }

        $meetingCards = [];
        $palette = ['primary', 'purple', 'green'];
        $iconMap = [
            'online' => 'video',
            'onsite' => 'users',
        ];
        foreach (array_values($grouped) as $index => $group) {
            $row = is_array($group['row'] ?? null) ? $group['row'] : [];
            $studentName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            if ($studentName === '') {
                $studentName = 'Étudiant';
            }
            $chapterId = array_key_exists('chapter_id', $row) && $row['chapter_id'] !== null ? (int) $row['chapter_id'] : null;
            $meetingCards[] = [
                'title' => $this->normalizeUiText((string) ($row['title'] ?? 'Rendez-vous')),
                'course_title' => $this->normalizeUiText((string) ($row['course_title'] ?? 'Cours')),
                'chapter_title' => $this->normalizeUiText((string) ($row['chapter_title'] ?? '')),
                'student_name' => $this->normalizeUiText($studentName),
                'student_email' => (string) ($row['email'] ?? ''),
                'when_label' => $this->formatDateLabel((string) ($row['start_at'] ?? ''), true, 'À planifier'),
                'when_short' => $this->formatDateLabel((string) ($row['start_at'] ?? ''), false, 'À planifier'),
                'mode' => (string) ($row['mode'] ?? ''),
                'icon' => $iconMap[(string) ($row['mode'] ?? '')] ?? 'comment',
                'tone' => $palette[$index % count($palette)],
                'url' => $this->siteUrl('teacher/meetings?status=pending'),
                'chapter_id' => $chapterId,
            ];
            if (count($meetingCards) >= 3) {
                break;
            }
        }

        return $meetingCards;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildDashboardReviews(int $teacherId): array
    {
        try {
            $rows = $this->reviews->fetchReviewRows($teacherId, 0, 'visible', 0, 4);
        } catch (\Throwable $e) {
            $rows = [];
        }

        $makeInitials = static function (string $value): string {
            $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
            if ($value === '') {
                return 'ET';
            }

            $parts = preg_split('/\s+/', $value) ?: [];
            $first = trim((string) ($parts[0] ?? ''));
            $second = trim((string) ($parts[1] ?? ''));
            $initials = '';

            if ($first !== '') {
                $initials .= function_exists('mb_substr')
                    ? mb_substr($first, 0, 1, 'UTF-8')
                    : substr($first, 0, 1);
            }

            if ($second !== '') {
                $initials .= function_exists('mb_substr')
                    ? mb_substr($second, 0, 1, 'UTF-8')
                    : substr($second, 0, 1);
            }

            $initials = strtoupper(trim((string) $initials));
            return $initials !== '' ? $initials : 'ET';
        };

        $reviewCards = [];
        foreach ($rows as $row) {
            $reviewer = trim((string) ($row['reviewer_first_name'] ?? '') . ' ' . (string) ($row['reviewer_last_name'] ?? ''));
            if ($reviewer === '') {
                $reviewer = 'Utilisateur';
            }
            $rating = max(1, min(5, (int) ($row['rating'] ?? 0)));
            $reviewId = max(0, (int) ($row['id'] ?? 0));
            $reviewerAvatar = $this->resolveMediaUrl(trim((string) ($row['reviewer_avatar_url'] ?? '')));
            $reviewCards[] = [
                'id' => $reviewId,
                'reviewer_name' => $this->normalizeUiText($reviewer),
                'avatar_initials' => $makeInitials($reviewer),
                'course_title' => $this->normalizeUiText((string) ($row['course_title'] ?? 'Cours')),
                'comment' => $this->normalizeUiText((string) ($row['comment'] ?? 'Sans commentaire.')),
                'rating' => $rating,
                'created_label' => $this->formatDateLabel((string) ($row['created_at'] ?? ''), true, 'Récemment'),
                'avatar_url' => $reviewerAvatar,
                'tone' => $rating >= 4 ? 'emerald' : ($rating >= 3 ? 'amber' : 'rose'),
                'hidden' => (int) ($row['is_hidden_by_teacher'] ?? 0) === 1,
                'reply' => trim((string) ($row['teacher_reply_text'] ?? '')),
                'url' => $this->siteUrl('teacher/reviews?course_id=' . max(0, (int) ($row['course_id'] ?? 0)) . '&detail=1'),
            ];
        }

        return $reviewCards;
    }

    private function formatDateLabel(?string $value, bool $withTime = false, string $fallback = 'Non défini'): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return $fallback;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return $fallback;
        }

        return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts);
    }

    private function resolveMediaUrl(string $path): string
    {
        $value = trim($path);
        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '//') || str_starts_with($value, 'data:')) {
            return $value;
        }

        return $this->siteUrl(ltrim($value, '/'));
    }
}


