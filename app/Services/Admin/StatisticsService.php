<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminDashboardModel;
use App\Models\AdminStatisticsModel;

final class StatisticsService extends Service
{
    private AdminDashboardModel $dashboard;
    private AdminStatisticsModel $statistics;

    public function __construct(AdminDashboardModel $dashboard, AdminStatisticsModel $statistics)
    {
        $this->dashboard = $dashboard;
        $this->statistics = $statistics;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildViewData(array $user, array $query = []): array
    {
        $availableYears = $this->statistics->fetchAvailableYears(8);
        $period = $this->resolvePeriod($query, $availableYears);
        $chartFilter = $this->resolveChartFilter($query);

        $unreadCount = $this->dashboard->countUnreadNotifications((int) ($user['id'] ?? 0));
        $waitingSupportTickets = $this->dashboard->countWaitingSupportTickets();
        $pendingCourses = $this->dashboard->countPendingCourses();
        $pendingWithdrawals = $this->dashboard->countPendingWithdrawals();
        $failedPayments = $this->dashboard->countFailedPayments();
        $pendingPayments = $this->dashboard->countPendingPayments();
        $supportResponseMinutes = $this->dashboard->averageFirstResponseMinutes(14);
        $commissionPercent = $this->dashboard->commissionPercent(20.0);

        $revenueSnapshot = $this->statistics->fetchPaidRevenueSnapshotForRange(
            (string) $period['from_sql'],
            (string) $period['to_sql'],
            (string) $period['previous_from_sql'],
            (string) $period['previous_to_sql']
        );
        $userSnapshot = $this->statistics->fetchUserGrowthSnapshotForRange(
            (string) $period['from_sql'],
            (string) $period['to_sql'],
            (string) $period['previous_from_sql'],
            (string) $period['previous_to_sql']
        );

        $totalStudents = $this->dashboard->countActiveUsersByRole('student');
        $totalTeachers = $this->dashboard->countActiveUsersByRole('teacher');
        $totalCompanies = $this->dashboard->countActiveCompanies();
        $activeEnrollments = $this->statistics->countActiveEnrollments();

        $chart = $this->buildMetricChart(
            $chartFilter,
            $this->statistics->fetchMetricSeriesForRange(
                (string) $chartFilter['metric'],
                (string) $chartFilter['granularity'],
                (string) $chartFilter['from_sql'],
                (string) $chartFilter['to_sql']
            )
        );

        $periodGranularity = (string) ($period['chart_granularity'] ?? 'month');
        $granularityCatalog = $this->chartGranularityCatalog();
        $enrollmentChartFilter = [
            'metric' => 'new_students',
            'metric_label' => 'Nouveaux étudiants',
            'metric_type' => 'count',
            'metric_tone' => 'blue',
            'granularity' => $periodGranularity,
            'granularity_label' => (string) ($granularityCatalog[$periodGranularity]['label'] ?? 'Par mois'),
            'from_sql' => (string) $period['from_sql'],
            'to_sql' => (string) $period['to_sql'],
        ];
        $revenueChartFilter = [
            'metric' => 'revenue_xof',
            'metric_label' => 'Chiffre d’affaires',
            'metric_type' => 'money',
            'metric_tone' => 'emerald',
            'granularity' => $periodGranularity,
            'granularity_label' => (string) ($granularityCatalog[$periodGranularity]['label'] ?? 'Par mois'),
            'from_sql' => (string) $period['from_sql'],
            'to_sql' => (string) $period['to_sql'],
        ];
        $performanceCharts = [
            'students' => $this->buildMetricChart(
                $enrollmentChartFilter,
                $this->statistics->fetchMetricSeriesForRange(
                    'new_students',
                    $periodGranularity,
                    (string) $period['from_sql'],
                    (string) $period['to_sql']
                )
            ),
            'revenue' => $this->buildMetricChart(
                $revenueChartFilter,
                $this->statistics->fetchMetricSeriesForRange(
                    'revenue_xof',
                    $periodGranularity,
                    (string) $period['from_sql'],
                    (string) $period['to_sql']
                )
            ),
        ];

        $healthSummary = $this->buildHealthSummary(
            $waitingSupportTickets,
            $pendingCourses,
            $pendingWithdrawals,
            $failedPayments,
            $supportResponseMinutes
        );

        $backlogTotal = $waitingSupportTickets + $pendingCourses + $pendingWithdrawals + $failedPayments;
        $estimatedCommission = (int) round(((int) $revenueSnapshot['current_total_xof']) * ($commissionPercent / 100));

        $userDelta = $this->deltaBadge((int) $userSnapshot['current_users'], (int) $userSnapshot['previous_users']);
        if (($userDelta['label'] ?? '') === 'nouveau pic') {
            $userDelta['label'] = '1re période active';
        }

        $kpiCards = [
            [
                'label' => 'Revenus',
                'value' => $this->formatMoney((int) $revenueSnapshot['current_total_xof']),
                'meta' => $period['display_label'] . ' | Commission estimée : ' . $this->formatMoney($estimatedCommission),
                'delta' => $this->deltaBadge((int) $revenueSnapshot['current_total_xof'], (int) $revenueSnapshot['previous_total_xof']),
                'icon' => 'wallet',
                'tone' => 'blue',
            ],
            [
                'label' => 'Commandes payées',
                'value' => $this->formatNumber((int) $revenueSnapshot['current_orders']),
                'meta' => 'Période : ' . $period['display_label'],
                'delta' => $this->deltaBadge((int) $revenueSnapshot['current_orders'], (int) $revenueSnapshot['previous_orders']),
                'icon' => 'receipt',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Comptes créés',
                'value' => $this->formatNumber((int) $userSnapshot['current_users']),
                'meta' => 'Rôles détectés : ' . $this->formatNumber((int) $userSnapshot['current_teachers']) . ' ens. | ' . $this->formatNumber((int) $userSnapshot['current_students']) . ' étu. (non exclusifs)',
                'delta' => $userDelta,
                'icon' => 'users',
                'tone' => 'indigo',
            ],
            [
                'label' => 'Retraits à traiter',
                'value' => $this->formatNumber($pendingWithdrawals),
                'meta' => $failedPayments > 0
                    ? $this->formatNumber($failedPayments) . ' paiement(s) en échec'
                    : $this->formatNumber($pendingPayments) . ' paiement(s) en attente',
                'delta' => [
                    'label' => $pendingWithdrawals > 0 ? 'suivi requis' : 'flux stable',
                    'tone' => $pendingWithdrawals > 0 ? 'amber' : 'emerald',
                ],
                'icon' => 'wallet',
                'tone' => ($pendingWithdrawals > 0 || $failedPayments > 0) ? 'amber' : 'slate',
            ],
            [
                'label' => 'Actions en attente',
                'value' => $this->formatNumber($backlogTotal),
                'meta' => 'Support, cours, retraits et paiements en échec',
                'delta' => [
                    'label' => $backlogTotal > 0 ? $this->formatNumber($waitingSupportTickets) . ' ticket(s) support en attente' : 'rien de bloquant',
                    'tone' => $backlogTotal > 0 ? 'rose' : 'emerald',
                ],
                'icon' => 'alert-triangle',
                'tone' => $backlogTotal > 0 ? 'rose' : 'amber',
            ],
        ];

        $heroHighlights = [];

        $audienceRows = $this->buildAudienceRows([
            ['label' => 'Étudiants actifs', 'value' => $totalStudents, 'tone' => 'blue', 'icon' => 'book-open'],
            ['label' => 'Enseignants actifs', 'value' => $totalTeachers, 'tone' => 'emerald', 'icon' => 'user'],
            ['label' => 'Entreprises actives', 'value' => $totalCompanies, 'tone' => 'amber', 'icon' => 'layout'],
            ['label' => 'Inscriptions actives', 'value' => $activeEnrollments, 'tone' => 'indigo', 'icon' => 'users'],
        ]);

        $categoryBreakdown = $this->buildCategoryBreakdown($this->statistics->fetchCourseCategoryBreakdown(6));
        $topTeachers = $this->buildTopTeachers($this->statistics->fetchTopTeachersByRevenue(5));
        $conversionFunnel = $this->buildConversionFunnel($this->statistics->fetchConversionFunnel());
        $completionByCategory = $this->buildCompletionByCategory($this->statistics->fetchCompletionRowsByCategory(), 5);
        $recentTransactions = $this->buildRecentTransactions($this->statistics->fetchRecentTransactions(6), $commissionPercent);

        $mainStats = [
            'students' => [
                'label' => 'Étudiants inscrits',
                'value' => $this->formatNumber($totalStudents),
                'meta' => (string) ($userDelta['label'] ?? 'stable') . ' sur la période',
                'tone' => (string) ($userDelta['tone'] ?? 'blue'),
                'icon' => 'user-graduate',
            ],
            'teachers' => [
                'label' => 'Enseignants',
                'value' => $this->formatNumber($totalTeachers),
                'meta' => '+' . $this->formatNumber((int) $userSnapshot['current_teachers']) . ' sur la période',
                'tone' => 'indigo',
                'icon' => 'chalkboard-user',
            ],
            'courses' => [
                'label' => 'Cours actifs',
                'value' => $this->formatNumber($this->dashboard->countPublishedCourses()),
                'meta' => $this->formatNumber($pendingCourses) . ' en attente',
                'tone' => $pendingCourses > 0 ? 'amber' : 'emerald',
                'icon' => 'layer-group',
            ],
            'revenue' => [
                'label' => 'Revenus totaux',
                'value' => $this->formatMoney((int) $revenueSnapshot['current_total_xof']),
                'meta' => (string) (($this->deltaBadge((int) $revenueSnapshot['current_total_xof'], (int) $revenueSnapshot['previous_total_xof']))['label'] ?? 'stable'),
                'tone' => 'emerald',
                'icon' => 'money-bill-wave',
            ],
        ];

        $workloadBoards = [
            [
                'title' => 'Catalogue',
                'icon' => 'book-open',
                'meta' => $pendingCourses > 0
                    ? $this->formatNumber($pendingCourses) . ' cours à valider'
                    : 'Aucun cours en attente',
                'items' => $this->buildDistribution(
                    $this->statistics->fetchCourseStatusBreakdown(),
                    [
                        'submitted' => 'À valider',
                        'approved' => 'Publiés',
                        'draft' => 'Brouillons',
                        'rejected' => 'Rejetés',
                    ],
                    [
                        'submitted' => 'amber',
                        'approved' => 'emerald',
                        'draft' => 'indigo',
                        'rejected' => 'rose',
                    ]
                ),
            ],
            [
                'title' => 'Paiements',
                'icon' => 'credit-card',
                'meta' => ($pendingPayments > 0 || $failedPayments > 0)
                    ? $this->formatNumber($pendingPayments) . ' en attente | ' . $this->formatNumber($failedPayments) . ' en échec'
                    : 'Flux de paiements stable',
                'items' => $this->buildDistribution(
                    $this->statistics->fetchPaymentStatusBreakdown(),
                    [
                        'paid' => 'Payés',
                        'pending' => 'En attente',
                        'initiated' => 'Initiées',
                        'failed' => 'Échec',
                        'refunded' => 'Remboursées',
                    ],
                    [
                        'paid' => 'emerald',
                        'pending' => 'amber',
                        'initiated' => 'indigo',
                        'failed' => 'rose',
                        'refunded' => 'slate',
                    ]
                ),
            ],
        ];

        $quickLinks = [
            [
                'label' => 'Support',
                'url' => $this->siteUrl('admin/support'),
                'icon' => 'lifebuoy',
                'tone' => 'primary',
            ],
            [
                'label' => 'Cours à valider',
                'url' => $this->siteUrl('admin/courses'),
                'icon' => 'book-open',
                'tone' => 'secondary',
            ],
            [
                'label' => 'Ventes',
                'url' => $this->siteUrl('admin/sales'),
                'icon' => 'wallet',
                'tone' => 'secondary',
            ],
        ];

        return [
            'unreadCount' => $unreadCount,
            'period' => $period,
            'chartFilter' => $chartFilter,
            'kpiCards' => $kpiCards,
            'heroHighlights' => $heroHighlights,
            'healthSummary' => $healthSummary,
            'chart' => $chart,
            'performanceCharts' => $performanceCharts,
            'mainStats' => $mainStats,
            'categoryBreakdown' => $categoryBreakdown,
            'topTeachers' => $topTeachers,
            'conversionFunnel' => $conversionFunnel,
            'completionByCategory' => $completionByCategory,
            'recentTransactions' => $recentTransactions,
            'audienceRows' => $audienceRows,
            'workloadBoards' => $workloadBoards,
            'quickLinks' => $quickLinks,
            'commissionPercent' => $commissionPercent,
            'updatedAt' => date('H:i'),
        ];
    }

    /**
     * @param array<string,mixed> $query
     * @param array<int,int> $availableYears
     * @return array<string,mixed>
     */
    private function resolvePeriod(array $query, array $availableYears): array
    {
        $today = new \DateTimeImmutable('today');
        $currentYear = (int) $today->format('Y');
        $currentMonth = (int) $today->format('n');

        if ($availableYears === []) {
            $availableYears = [$currentYear];
        }
        if (!in_array($currentYear, $availableYears, true)) {
            $availableYears[] = $currentYear;
            rsort($availableYears);
        }

        $scope = strtolower(trim((string) ($query['scope'] ?? 'months')));
        if (!in_array($scope, ['months', 'month', 'year'], true)) {
            $scope = 'months';
        }

        $months = max(1, min(24, (int) ($query['months'] ?? 6)));
        $selectedMonth = max(1, min(12, (int) ($query['month'] ?? $currentMonth)));
        $selectedYear = (int) ($query['year'] ?? ($availableYears[0] ?? $currentYear));
        if ($selectedYear < 2000 || $selectedYear > ($currentYear + 1)) {
            $selectedYear = $currentYear;
        }
        if (!in_array($selectedYear, $availableYears, true)) {
            $availableYears[] = $selectedYear;
            rsort($availableYears);
        }

        $monthNames = $this->monthNamesLong();
        $from = $today;
        $to = $today;
        $previousFrom = $today;
        $previousTo = $today;
        $displayLabel = '';
        $rangeLabel = '';
        $chartGranularity = 'month';

        if ($scope === 'month') {
            $from = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $selectedYear, $selectedMonth));
            $to = $from->modify('+1 month');
            $previousFrom = $from->modify('-1 month');
            $previousTo = $from;
            $displayLabel = ($monthNames[$selectedMonth] ?? 'Mois') . ' ' . $selectedYear;
            $rangeLabel = 'Du ' . $from->format('d/m/Y') . ' au ' . $to->modify('-1 day')->format('d/m/Y');
            $chartGranularity = 'day';
        } elseif ($scope === 'year') {
            $from = new \DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $selectedYear));
            $to = $from->modify('+1 year');
            $previousFrom = $from->modify('-1 year');
            $previousTo = $from;
            $displayLabel = 'Année ' . $selectedYear;
            $rangeLabel = 'Du ' . $from->format('d/m/Y') . ' au ' . $to->modify('-1 day')->format('d/m/Y');
            $chartGranularity = 'month';
        } else {
            $from = new \DateTimeImmutable(date('Y-m-01 00:00:00'));
            $from = $from->modify('-' . ($months - 1) . ' months');
            $to = new \DateTimeImmutable(date('Y-m-01 00:00:00'));
            $to = $to->modify('+1 month');
            $previousFrom = $from->modify('-' . $months . ' months');
            $previousTo = $from;
            $displayLabel = $months . ' dernier(s) mois';
            $rangeLabel = 'Du ' . $from->format('d/m/Y') . ' au ' . $to->modify('-1 day')->format('d/m/Y');
            $chartGranularity = 'month';
        }

        return [
            'scope' => $scope,
            'months' => $months,
            'month' => $selectedMonth,
            'year' => $selectedYear,
            'available_years' => array_values(array_unique($availableYears)),
            'display_label' => $displayLabel,
            'range_label' => $rangeLabel,
            'from_sql' => $from->format('Y-m-d H:i:s'),
            'to_sql' => $to->format('Y-m-d H:i:s'),
            'previous_from_sql' => $previousFrom->format('Y-m-d H:i:s'),
            'previous_to_sql' => $previousTo->format('Y-m-d H:i:s'),
            'chart_granularity' => $chartGranularity,
        ];
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function resolveChartFilter(array $query): array
    {
        $metricCatalog = $this->chartMetricCatalog();
        $granularityCatalog = $this->chartGranularityCatalog();

        $metric = strtolower(trim((string) ($query['chart_metric'] ?? 'revenue_xof')));
        if (!array_key_exists($metric, $metricCatalog)) {
            $metric = 'revenue_xof';
        }

        $granularity = strtolower(trim((string) ($query['chart_granularity'] ?? 'month')));
        if (!array_key_exists($granularity, $granularityCatalog)) {
            $granularity = 'month';
        }

        $points = (int) ($query['chart_points'] ?? $granularityCatalog[$granularity]['default_points']);
        $points = max(
            (int) $granularityCatalog[$granularity]['min_points'],
            min((int) $granularityCatalog[$granularity]['max_points'], $points)
        );

        $today = new \DateTimeImmutable('today');
        $from = $today;
        $to = $today;

        if ($granularity === 'day') {
            $from = $today->modify('-' . ($points - 1) . ' days')->setTime(0, 0, 0);
            $to = $today->modify('+1 day')->setTime(0, 0, 0);
        } elseif ($granularity === 'week') {
            $weekStart = $today->modify('monday this week')->setTime(0, 0, 0);
            $from = $weekStart->modify('-' . ($points - 1) . ' weeks');
            $to = $weekStart->modify('+1 week');
        } elseif ($granularity === 'year') {
            $yearStart = new \DateTimeImmutable(date('Y-01-01 00:00:00'));
            $from = $yearStart->modify('-' . ($points - 1) . ' years');
            $to = $yearStart->modify('+1 year');
        } else {
            $monthStart = new \DateTimeImmutable(date('Y-m-01 00:00:00'));
            $from = $monthStart->modify('-' . ($points - 1) . ' months');
            $to = $monthStart->modify('+1 month');
        }

        return [
            'metric' => $metric,
            'metric_label' => (string) $metricCatalog[$metric]['label'],
            'metric_type' => (string) $metricCatalog[$metric]['type'],
            'metric_tone' => (string) $metricCatalog[$metric]['tone'],
            'metric_options' => $metricCatalog,
            'granularity' => $granularity,
            'granularity_label' => (string) $granularityCatalog[$granularity]['label'],
            'granularity_options' => $granularityCatalog,
            'points' => $points,
            'from_sql' => $from->format('Y-m-d H:i:s'),
            'to_sql' => $to->format('Y-m-d H:i:s'),
            'range_label' => 'Du ' . $from->format('d/m/Y') . ' au ' . $to->modify('-1 day')->format('d/m/Y'),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildHealthSummary(
        int $waitingSupportTickets,
        int $pendingCourses,
        int $pendingWithdrawals,
        int $failedPayments,
        int $supportResponseMinutes
    ): array {
        $score = 100;
        $score -= min(28, $waitingSupportTickets * 8);
        $score -= min(18, $pendingCourses * 2);
        $score -= min(18, $pendingWithdrawals * 4);
        $score -= min(22, $failedPayments * 7);
        if ($supportResponseMinutes > 45) {
            $score -= min(16, (int) floor(($supportResponseMinutes - 45) / 15) * 2);
        }
        $score = max(24, min(100, $score));

        if ($score >= 85) {
            return [
                'score' => $score,
                'label' => 'Opération fluide',
                'message' => 'Les files critiques restent sous contrôle et le support répond vite.',
                'tone' => 'active',
                'badge_tone' => 'emerald',
            ];
        }

        if ($score >= 68) {
            return [
                'score' => $score,
                'label' => 'Sous surveillance',
                'message' => 'Quelques points demandent un arbitrage dans la journée.',
                'tone' => 'pending',
                'badge_tone' => 'amber',
            ];
        }

        return [
            'score' => $score,
            'label' => 'Charge critique',
            'message' => 'Le support ou les paiements demandent une action rapide.',
            'tone' => 'urgent',
            'badge_tone' => 'rose',
        ];
    }

    /**
     * @param array<string,mixed> $filter
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function buildMetricChart(array $filter, array $rows): array
    {
        $lookup = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['bucket_key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $lookup[$key] = (int) ($row['metric_value'] ?? 0);
        }

        $monthShort = $this->monthNamesShort();
        $granularity = (string) ($filter['granularity'] ?? 'month');
        $metricType = (string) ($filter['metric_type'] ?? 'count');
        $metricLabel = (string) ($filter['metric_label'] ?? 'Valeur');
        $from = new \DateTimeImmutable((string) $filter['from_sql']);
        $to = new \DateTimeImmutable((string) $filter['to_sql']);

        $series = [];
        $cursor = $from;
        while ($cursor < $to) {
            if ($granularity === 'day') {
                $bucketKey = $cursor->format('Y-m-d');
                $label = $cursor->format('d/m');
                $fullLabel = $cursor->format('d/m/Y');
                $cursor = $cursor->modify('+1 day');
            } elseif ($granularity === 'week') {
                $bucketKey = $cursor->format('Y-m-d');
                $label = 'S' . $cursor->format('W');
                $fullLabel = 'Semaine du ' . $cursor->format('d/m/Y');
                $cursor = $cursor->modify('+1 week');
            } elseif ($granularity === 'year') {
                $bucketKey = $cursor->format('Y');
                $label = $cursor->format('Y');
                $fullLabel = 'Année ' . $label;
                $cursor = $cursor->modify('+1 year');
            } else {
                $bucketKey = $cursor->format('Y-m');
                $label = ($monthShort[(int) $cursor->format('n')] ?? $cursor->format('M')) . ' ' . $cursor->format('y');
                $fullLabel = ($monthShort[(int) $cursor->format('n')] ?? $cursor->format('M')) . ' ' . $cursor->format('Y');
                $cursor = $cursor->modify('+1 month');
            }

            $value = (int) ($lookup[$bucketKey] ?? 0);
            $series[] = [
                'bucket_key' => $bucketKey,
                'label' => $label,
                'full_label' => $fullLabel,
                'value' => $value,
            ];
        }

        $maxValue = 1;
        $totalValue = 0;
        foreach ($series as $point) {
            $value = (int) ($point['value'] ?? 0);
            $maxValue = max($maxValue, $value);
            $totalValue += $value;
        }

        $width = 760;
        $height = 260;
        $paddingX = 14.0;
        $paddingTop = 18.0;
        $paddingBottom = 42.0;
        $baselineY = $height - $paddingBottom;
        $plotHeight = $baselineY - $paddingTop;
        $plotWidth = $width - ($paddingX * 2);
        $pointCount = count($series);
        $stepX = $pointCount > 1 ? ($plotWidth / ($pointCount - 1)) : 0.0;

        $lineParts = [];
        $areaParts = [];
        foreach ($series as $index => $point) {
            $value = (int) ($point['value'] ?? 0);
            $ratio = $maxValue > 0 ? ($value / $maxValue) : 0.0;
            $x = $paddingX + ($stepX * $index);
            $y = $baselineY - ($ratio * $plotHeight);
            $series[$index]['x'] = round($x, 2);
            $series[$index]['y'] = round($y, 2);
            $series[$index]['value_label'] = $this->formatMetricValue($value, $metricType);
            $lineParts[] = ($index === 0 ? 'M ' : 'L ') . round($x, 2) . ' ' . round($y, 2);
            $areaParts[] = round($x, 2) . ' ' . round($y, 2);
        }

        $linePath = implode(' ', $lineParts);
        $areaPath = '';
        if ($series !== []) {
            $first = $series[0];
            $last = $series[$pointCount - 1];
            $areaPath = 'M ' . $first['x'] . ' ' . $baselineY . ' L '
                . implode(' L ', $areaParts)
                . ' L ' . $last['x'] . ' ' . $baselineY . ' Z';
        }

        $gridLines = [];
        foreach ([0, 25, 50, 75, 100] as $pct) {
            $ratio = $pct / 100;
            $gridLines[] = [
                'y' => round($baselineY - ($plotHeight * $ratio), 2),
                'value_label' => $this->formatMetricValue((int) round($maxValue * $ratio), $metricType),
            ];
        }

        $axisLabels = [];
        $step = $pointCount <= 6 ? 1 : (int) ceil(($pointCount - 1) / 5);
        foreach ($series as $index => $point) {
            if ($index !== 0 && $index !== ($pointCount - 1) && ($index % $step) !== 0) {
                continue;
            }
            $axisLabels[] = [
                'x_pct' => $width > 0 ? round((((float) $point['x']) / $width) * 100, 2) : 0.0,
                'label' => (string) ($point['label'] ?? ''),
            ];
        }

        $colors = $this->chartMetricColors((string) ($filter['metric_tone'] ?? 'blue'));

        return [
            'title' => $metricLabel,
            'subtitle' => (string) ($filter['granularity_label'] ?? ''),
            'series' => $series,
            'line_path' => $linePath,
            'area_path' => $areaPath,
            'grid_lines' => $gridLines,
            'axis_labels' => $axisLabels,
            'width' => $width,
            'height' => $height,
            'baseline_y' => round($baselineY, 2),
            'plot_start_x' => $paddingX,
            'plot_end_x' => round($width - $paddingX, 2),
            'max_label' => $this->formatMetricValue($maxValue, $metricType),
            'total_label' => $this->formatMetricValue($totalValue, $metricType),
            'points_label' => $this->formatNumber($pointCount) . ' point(s)',
            'has_values' => $totalValue > 0,
            'line_color' => $colors['line'],
            'fill_color' => $colors['fill'],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildAudienceRows(array $rows): array
    {
        $maxValue = 1;
        foreach ($rows as $row) {
            $maxValue = max($maxValue, (int) ($row['value'] ?? 0));
        }

        foreach ($rows as $index => $row) {
            $value = (int) ($row['value'] ?? 0);
            $rows[$index]['fill'] = $value > 0
                ? (int) max(12, round(($value / $maxValue) * 100))
                : 0;
        }

        return $rows;
    }

    /**
     * @param array<string,int> $counts
     * @param array<string,string> $labels
     * @param array<string,string> $tones
     * @return array<int,array<string,mixed>>
     */
    private function buildDistribution(array $counts, array $labels, array $tones): array
    {
        $total = array_sum($counts);
        $total = $total > 0 ? $total : 1;
        $items = [];

        foreach ($labels as $key => $label) {
            $value = (int) ($counts[$key] ?? 0);
            $items[] = [
                'label' => $label,
                'value' => $value,
                'pct' => $value > 0 ? (int) round(($value / $total) * 100) : 0,
                'tone' => $tones[$key] ?? 'slate',
            ];
        }

        usort($items, static function (array $left, array $right): int {
            $valueCompare = ((int) ($right['value'] ?? 0)) <=> ((int) ($left['value'] ?? 0));
            if ($valueCompare !== 0) {
                return $valueCompare;
            }
            return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });

        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildCategoryBreakdown(array $rows): array
    {
        $total = 0;
        foreach ($rows as $row) {
            $total += max(0, (int) ($row['total_courses'] ?? 0));
        }
        $total = max(1, $total);

        $tones = ['blue', 'emerald', 'indigo', 'amber', 'rose', 'slate'];
        $items = [];
        foreach ($rows as $index => $row) {
            $count = max(0, (int) ($row['total_courses'] ?? 0));
            $items[] = [
                'label' => trim((string) ($row['category_name'] ?? 'Sans catégorie')) ?: 'Sans catégorie',
                'value' => $count,
                'approved' => max(0, (int) ($row['approved_courses'] ?? 0)),
                'pct' => $count > 0 ? max(5, (int) round(($count / $total) * 100)) : 0,
                'tone' => $tones[$index % count($tones)],
            ];
        }

        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildTopTeachers(array $rows): array
    {
        $maxRevenue = 1;
        foreach ($rows as $row) {
            $maxRevenue = max($maxRevenue, (int) ($row['revenue_xof'] ?? 0));
        }

        $items = [];
        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['teacher_name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($row['teacher_email'] ?? 'Enseignant'));
            }
            $revenue = max(0, (int) ($row['revenue_xof'] ?? 0));
            $items[] = [
                'rank' => $index + 1,
                'name' => $name !== '' ? $name : 'Enseignant',
                'courses' => max(0, (int) ($row['courses_count'] ?? 0)),
                'orders' => max(0, (int) ($row['paid_orders'] ?? 0)),
                'revenue_xof' => $revenue,
                'revenue_label' => $this->formatMoney($revenue),
                'pct' => $revenue > 0 ? max(8, (int) round(($revenue / $maxRevenue) * 100)) : 0,
            ];
        }

        return $items;
    }

    /**
     * @param array<string,int> $raw
     * @return array<int,array<string,mixed>>
     */
    private function buildConversionFunnel(array $raw): array
    {
        $visitors = max(1, (int) ($raw['visitors'] ?? 0));
        $rows = [
            ['label' => 'Visiteurs uniques', 'key' => 'visitors', 'tone' => 'blue'],
            ['label' => 'Inscriptions', 'key' => 'registrations', 'tone' => 'indigo'],
            ['label' => 'Commandes payées', 'key' => 'paid_orders', 'tone' => 'emerald'],
            ['label' => 'Inscriptions aux cours', 'key' => 'active_enrollments', 'tone' => 'amber'],
        ];

        foreach ($rows as $index => $row) {
            $value = max(0, (int) ($raw[$row['key']] ?? 0));
            $pct = $value > 0 ? (int) round(($value / $visitors) * 100, 0) : 0;
            $rows[$index]['value'] = $value;
            $rows[$index]['value_label'] = $this->formatNumber($value);
            $rows[$index]['pct'] = max(0, min(100, $pct));
            $rows[$index]['bar_pct'] = $value > 0 ? max(5, min(100, $pct)) : 0;
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildCompletionByCategory(array $rows, int $limit = 5): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $category = trim((string) ($row['category_name'] ?? 'Sans catégorie')) ?: 'Sans catégorie';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'label' => $category,
                    'lesson_student_total' => 0,
                    'done_count' => 0,
                    'courses' => 0,
                ];
            }
            $lessonCount = max(0, (int) ($row['lesson_count'] ?? 0));
            $studentCount = max(0, (int) ($row['student_count'] ?? 0));
            $grouped[$category]['lesson_student_total'] += $lessonCount * $studentCount;
            $grouped[$category]['done_count'] += max(0, (int) ($row['done_count'] ?? 0));
            $grouped[$category]['courses']++;
        }

        $items = [];
        foreach ($grouped as $row) {
            $total = max(0, (int) ($row['lesson_student_total'] ?? 0));
            $done = max(0, (int) ($row['done_count'] ?? 0));
            $rate = $total > 0 ? (int) round(($done / $total) * 100) : 0;
            $items[] = [
                'label' => (string) $row['label'],
                'rate' => max(0, min(100, $rate)),
                'done' => $done,
                'total' => $total,
                'courses' => max(0, (int) ($row['courses'] ?? 0)),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            $rateCompare = ((int) ($right['rate'] ?? 0)) <=> ((int) ($left['rate'] ?? 0));
            if ($rateCompare !== 0) {
                return $rateCompare;
            }
            return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });

        return array_slice($items, 0, max(1, min(8, $limit)));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildRecentTransactions(array $rows, float $commissionPercent): array
    {
        $items = [];
        foreach ($rows as $row) {
            $amount = max(0, (int) ($row['total_xof'] ?? 0));
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            $customer = trim((string) ($row['customer_name'] ?? ''));
            if ($customer === '') {
                $customer = trim((string) ($row['customer_email'] ?? 'Client'));
            }
            $courseTitles = trim((string) ($row['course_titles'] ?? ''));
            $courseCount = max(0, (int) ($row['course_count'] ?? 0));
            if ($courseTitles === '') {
                $courseTitles = $courseCount > 1 ? $this->formatNumber($courseCount) . ' cours' : 'Cours non renseigné';
            }

            $items[] = [
                'reference' => trim((string) ($row['reference'] ?? '')) ?: ('#' . (int) ($row['id'] ?? 0)),
                'customer' => $customer !== '' ? $customer : 'Client',
                'courses' => $courseTitles,
                'amount_label' => $this->formatMoney($amount),
                'commission_label' => $this->formatMoney((int) round($amount * ($commissionPercent / 100))),
                'status' => $status,
                'status_label' => $this->orderStatusLabel($status),
                'status_tone' => in_array($status, ['paid', 'completed', 'succeeded', 'success', 'free'], true) ? 'emerald' : ($status === 'pending' ? 'amber' : 'rose'),
            ];
        }

        return $items;
    }

    private function orderStatusLabel(string $status): string
    {
        return match (strtolower(trim($status))) {
            'paid', 'completed', 'succeeded', 'success', 'free' => 'Complété',
            'pending', 'initiated' => 'En attente',
            'failed' => 'Échec',
            'canceled', 'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            default => $status !== '' ? ucfirst($status) : 'Inconnu',
        };
    }

    /**
     * @return array<string,string>
     */
    private function deltaBadge(int $current, int $previous): array
    {
        if ($current === 0 && $previous === 0) {
            return ['label' => 'stable', 'tone' => 'slate'];
        }

        if ($previous <= 0) {
            return ['label' => 'nouveau pic', 'tone' => 'indigo'];
        }

        $delta = (($current - $previous) / $previous) * 100;
        if (abs($delta) < 0.05) {
            return ['label' => 'stable', 'tone' => 'slate'];
        }

        return [
            'label' => ($delta > 0 ? '+' : '') . number_format($delta, 1, ',', ' ') . '%',
            'tone' => $delta > 0 ? 'emerald' : 'rose',
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function chartMetricCatalog(): array
    {
        return [
            'revenue_xof' => ['label' => 'Revenus', 'type' => 'money', 'tone' => 'blue'],
            'paid_orders' => ['label' => 'Commandes payées', 'type' => 'count', 'tone' => 'emerald'],
            'new_accounts' => ['label' => 'Comptes créés', 'type' => 'count', 'tone' => 'indigo'],
            'new_teachers' => ['label' => 'Enseignants créés', 'type' => 'count', 'tone' => 'amber'],
            'new_students' => ['label' => 'Étudiants créés', 'type' => 'count', 'tone' => 'blue'],
            'new_companies' => ['label' => 'Entreprises créées', 'type' => 'count', 'tone' => 'rose'],
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function chartGranularityCatalog(): array
    {
        return [
            'day' => ['label' => 'Par jour', 'default_points' => 14, 'min_points' => 2, 'max_points' => 90],
            'week' => ['label' => 'Par semaine', 'default_points' => 12, 'min_points' => 2, 'max_points' => 52],
            'month' => ['label' => 'Par mois', 'default_points' => 12, 'min_points' => 2, 'max_points' => 36],
            'year' => ['label' => 'Par année', 'default_points' => 5, 'min_points' => 2, 'max_points' => 10],
        ];
    }

    /**
     * @return array<string,string>
     */
    private function chartMetricColors(string $tone): array
    {
        $key = strtolower(trim($tone));
        $palette = [
            'blue' => ['line' => '#2563eb', 'fill' => 'rgba(59, 130, 246, 0.15)'],
            'emerald' => ['line' => '#059669', 'fill' => 'rgba(16, 185, 129, 0.16)'],
            'amber' => ['line' => '#d97706', 'fill' => 'rgba(245, 158, 11, 0.18)'],
            'indigo' => ['line' => '#4f46e5', 'fill' => 'rgba(99, 102, 241, 0.16)'],
            'rose' => ['line' => '#e11d48', 'fill' => 'rgba(244, 63, 94, 0.16)'],
            'slate' => ['line' => '#475569', 'fill' => 'rgba(100, 116, 139, 0.16)'],
        ];

        return $palette[$key] ?? $palette['blue'];
    }

    private function formatMetricValue(int $value, string $type): string
    {
        if ($type === 'money') {
            return $this->formatMoney($value);
        }

        return $this->formatNumber($value);
    }

    /**
     * @return array<int,string>
     */
    private function monthNamesShort(): array
    {
        return [
            1 => 'Jan',
            2 => 'Fév',
            3 => 'Mar',
            4 => 'Avr',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juil',
            8 => 'Août',
            9 => 'Sept',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc',
        ];
    }

    /**
     * @return array<int,string>
     */
    private function monthNamesLong(): array
    {
        return [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ];
    }

    private function formatMoney(int $amount): string
    {
        return number_format(max(0, $amount), 0, ',', ' ') . ' F CFA';
    }

    private function formatNumber(int $value): string
    {
        return number_format(max(0, $value), 0, ',', ' ');
    }

    private function formatDurationMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        if ($minutes >= 1440) {
            return $this->formatNumber((int) floor($minutes / 1440)) . ' j';
        }
        if ($minutes >= 60) {
            return $this->formatNumber((int) floor($minutes / 60)) . ' h';
        }
        return $this->formatNumber($minutes) . ' min';
    }
}
