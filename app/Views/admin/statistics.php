<?php
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$period = is_array($period ?? null) ? $period : [];
$chartFilter = is_array($chartFilter ?? null) ? $chartFilter : [];
$chart = is_array($chart ?? null) ? $chart : [];
$performanceCharts = is_array($performanceCharts ?? null) ? $performanceCharts : [];
$mainStats = is_array($mainStats ?? null) ? $mainStats : [];
$categoryBreakdown = is_array($categoryBreakdown ?? null) ? $categoryBreakdown : [];
$topTeachers = is_array($topTeachers ?? null) ? $topTeachers : [];
$conversionFunnel = is_array($conversionFunnel ?? null) ? $conversionFunnel : [];
$completionByCategory = is_array($completionByCategory ?? null) ? $completionByCategory : [];
$recentTransactions = is_array($recentTransactions ?? null) ? $recentTransactions : [];
$healthSummary = is_array($healthSummary ?? null) ? $healthSummary : ['score' => 0, 'label' => 'Stable', 'message' => '', 'tone' => 'done'];
$updatedAt = trim((string) ($updatedAt ?? ''));
$commissionPercent = (float) ($commissionPercent ?? 0.0);

$monthChoices = [
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

$number = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ');
};

$pillClass = static function (string $tone): string {
    return match (strtolower(trim($tone))) {
        'active', 'done', 'emerald' => 'admin-shell-badge admin-shell-badge--info',
        'pending', 'amber' => 'admin-shell-badge admin-shell-badge--warning',
        'urgent', 'rose', 'danger' => 'admin-shell-badge admin-shell-badge--danger',
        'blue', 'indigo' => 'admin-shell-badge admin-shell-badge--info',
        default => 'admin-shell-badge',
    };
};

$toneClass = static function (string $tone): string {
    return match (strtolower(trim($tone))) {
        'blue' => 'admin-stats-v2-tone--blue',
        'emerald' => 'admin-stats-v2-tone--emerald',
        'amber' => 'admin-stats-v2-tone--amber',
        'indigo' => 'admin-stats-v2-tone--indigo',
        'rose' => 'admin-stats-v2-tone--rose',
        default => 'admin-stats-v2-tone--slate',
    };
};

$progressWidth = static function (int $value): int {
    return max(0, min(100, $value));
};

$selectedScope = (string) ($period['scope'] ?? 'months');
$selectedMonths = max(1, (int) ($period['months'] ?? 6));
$selectedMonth = max(1, (int) ($period['month'] ?? (int) date('n')));
$selectedYear = max(2000, (int) ($period['year'] ?? (int) date('Y')));
$availableYears = array_values(array_unique(array_map('intval', is_array($period['available_years'] ?? null) ? $period['available_years'] : [$selectedYear])));
rsort($availableYears);

$selectedChartMetric = (string) ($chartFilter['metric'] ?? 'revenue_xof');
$selectedChartGranularity = (string) ($chartFilter['granularity'] ?? 'month');
$selectedChartPoints = max(2, (int) ($chartFilter['points'] ?? 12));
$chartMetricOptions = is_array($chartFilter['metric_options'] ?? null) ? $chartFilter['metric_options'] : [];
$chartGranularityOptions = is_array($chartFilter['granularity_options'] ?? null) ? $chartFilter['granularity_options'] : [];
$chartGranularityMap = [];
foreach ($chartGranularityOptions as $granularityKey => $granularityOption) {
    if (!is_array($granularityOption)) {
        continue;
    }
    $chartGranularityMap[(string) $granularityKey] = [
        'min' => (int) ($granularityOption['min_points'] ?? 2),
        'max' => (int) ($granularityOption['max_points'] ?? 90),
        'default' => (int) ($granularityOption['default_points'] ?? 12),
    ];
}

$renderLineChart = static function (array $chartData, string $emptyLabel = 'Aucune donnée disponible') {
    $series = is_array($chartData['series'] ?? null) ? $chartData['series'] : [];
    if ($series === []) {
        ?>
        <div class="admin-stats-v2-empty-chart">
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <span><?php echo e($emptyLabel); ?></span>
        </div>
        <?php
        return;
    }
    $width = max(120, (int) ($chartData['width'] ?? 760));
    $height = max(120, (int) ($chartData['height'] ?? 260));
    ?>
    <svg viewBox="0 0 <?php echo e((string) $width); ?> <?php echo e((string) $height); ?>" class="admin-stats-v2-svg" aria-label="<?php echo e((string) ($chartData['title'] ?? 'Graphique')); ?>">
        <?php foreach ((array) ($chartData['grid_lines'] ?? []) as $line): ?>
            <line x1="<?php echo e((string) ($chartData['plot_start_x'] ?? 14)); ?>" y1="<?php echo e((string) ($line['y'] ?? 0)); ?>" x2="<?php echo e((string) ($chartData['plot_end_x'] ?? ($width - 14))); ?>" y2="<?php echo e((string) ($line['y'] ?? 0)); ?>" class="admin-stats-v2-svg__grid" />
            <text x="<?php echo e((string) ($chartData['plot_end_x'] ?? ($width - 14))); ?>" y="<?php echo e((string) (((float) ($line['y'] ?? 0)) - 6)); ?>" text-anchor="end" class="admin-stats-v2-svg__label"><?php echo e((string) ($line['value_label'] ?? '0')); ?></text>
        <?php endforeach; ?>
        <?php if ((string) ($chartData['area_path'] ?? '') !== ''): ?>
            <path d="<?php echo e((string) ($chartData['area_path'] ?? '')); ?>" fill="<?php echo e((string) ($chartData['fill_color'] ?? 'rgba(59, 130, 246, 0.13)')); ?>" />
        <?php endif; ?>
        <?php if ((string) ($chartData['line_path'] ?? '') !== ''): ?>
            <path d="<?php echo e((string) ($chartData['line_path'] ?? '')); ?>" fill="none" stroke="<?php echo e((string) ($chartData['line_color'] ?? '#2563eb')); ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <?php endif; ?>
        <?php foreach ($series as $point): ?>
            <g>
                <title><?php echo e((string) (($point['full_label'] ?? '') . ' - ' . ($point['value_label'] ?? ''))); ?></title>
                <circle cx="<?php echo e((string) ($point['x'] ?? 0)); ?>" cy="<?php echo e((string) ($point['y'] ?? 0)); ?>" r="4.5" fill="<?php echo e((string) ($chartData['line_color'] ?? '#2563eb')); ?>" />
                <circle cx="<?php echo e((string) ($point['x'] ?? 0)); ?>" cy="<?php echo e((string) ($point['y'] ?? 0)); ?>" r="2.2" fill="#ffffff" />
            </g>
        <?php endforeach; ?>
    </svg>
    <div class="admin-stats-v2-axis">
        <?php foreach ((array) ($chartData['axis_labels'] ?? []) as $label): ?>
            <span style="left: <?php echo e((string) ($label['x_pct'] ?? 0)); ?>%;"><?php echo e((string) ($label['label'] ?? '')); ?></span>
        <?php endforeach; ?>
    </div>
    <?php
};

$adminNavActive = 'statistics';
$adminUnreadCount = $unreadCount;
$adminTopTitle = 'Tableau de bord statistiques';
$adminTopSubtitle = 'Administration';
$adminTopBadge = (string) ($healthSummary['label'] ?? 'Stable') . ' · ' . $number((int) ($healthSummary['score'] ?? 0)) . '/100';
$adminTopBadgeTone = (string) ($healthSummary['tone'] ?? 'done');
$adminTopActions = [
    ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'],
    ['label' => 'Paiements', 'url' => site_url('admin/sales'), 'tone' => 'neutral'],
];
$adminTopSearchAction = site_url('admin/statistics');
$adminTopSearchPlaceholder = 'Rechercher dans les statistiques...';
$adminPageScripts = ['admin_statistics_filters.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques · Administrateur</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/statistics" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop" aria-hidden="true">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense admin-stats-v2-page px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <section class="admin-stats-v2-hero rounded-3xl p-6 text-white">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.24em] text-white/75">
                                <i class="fas fa-chart-pie" aria-hidden="true"></i>
                                Analyse des performances
                            </p>
                            <h2 class="mt-2 text-2xl font-black sm:text-3xl">Statistiques de la plateforme</h2>
                            <p class="mt-2 max-w-3xl text-sm font-medium text-white/80">
                                Suivez l’évolution des inscriptions, des revenus, des catégories, de la complétion et des dernières transactions.
                            </p>
                        </div>
                        <div class="admin-stats-v2-hero__meta">
                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                            <span><?php echo e($updatedAt !== '' ? ('Mise à jour : ' . $updatedAt) : 'Données temps réel'); ?></span>
                        </div>
                    </div>
                </section>

                <section class="admin-shell-card rounded-3xl p-5">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                        <div>
                            <p class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-calendar-alt admin-inline-accent admin-inline-accent--info" aria-hidden="true"></i>
                                Période d’analyse
                            </p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                <?php echo e((string) ($period['display_label'] ?? 'Période')); ?> · <?php echo e((string) ($period['range_label'] ?? '')); ?>
                            </p>
                        </div>
                        <form method="get" action="<?php echo e(site_url('admin/statistics')); ?>" class="admin-stats-v2-filter" data-form-kind="period" data-admin-period-form>
                            <input type="hidden" name="chart_metric" value="<?php echo e($selectedChartMetric); ?>">
                            <input type="hidden" name="chart_granularity" value="<?php echo e($selectedChartGranularity); ?>">
                            <input type="hidden" name="chart_points" value="<?php echo e((string) $selectedChartPoints); ?>">
                            <label>
                                <span>Mode</span>
                                <select name="scope" class="admin-shell-input" data-period-scope>
                                    <option value="months" <?php echo $selectedScope === 'months' ? 'selected' : ''; ?>>Nombre de mois</option>
                                    <option value="month" <?php echo $selectedScope === 'month' ? 'selected' : ''; ?>>Mois précis</option>
                                    <option value="year" <?php echo $selectedScope === 'year' ? 'selected' : ''; ?>>Année complète</option>
                                </select>
                            </label>
                            <label data-scope-only="months">
                                <span>Mois</span>
                                <input type="number" name="months" min="1" max="24" value="<?php echo e((string) $selectedMonths); ?>" class="admin-shell-input" inputmode="numeric">
                            </label>
                            <label data-scope-only="month">
                                <span>Mois</span>
                                <select name="month" class="admin-shell-input">
                                    <?php foreach ($monthChoices as $monthValue => $monthLabel): ?>
                                        <option value="<?php echo e((string) $monthValue); ?>" <?php echo $selectedMonth === $monthValue ? 'selected' : ''; ?>><?php echo e($monthLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label data-scope-only="month year">
                                <span>Année</span>
                                <select name="year" class="admin-shell-input">
                                    <?php foreach ($availableYears as $yearOption): ?>
                                        <option value="<?php echo e((string) $yearOption); ?>" <?php echo $selectedYear === (int) $yearOption ? 'selected' : ''; ?>><?php echo e((string) $yearOption); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <a href="<?php echo e(site_url('admin/statistics')); ?>" class="admin-shell-button admin-shell-button--secondary">
                                <i class="fas fa-undo" aria-hidden="true"></i>
                                Réinitialiser
                            </a>
                        </form>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <?php foreach ($mainStats as $stat): ?>
                        <?php $tone = $toneClass((string) ($stat['tone'] ?? 'slate')); ?>
                        <article class="admin-stats-v2-kpi <?php echo e($tone); ?> rounded-2xl p-5 hover-lift">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400"><?php echo e((string) ($stat['label'] ?? 'Indicateur')); ?></p>
                                    <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white"><?php echo e((string) ($stat['value'] ?? '0')); ?></p>
                                </div>
                                <span class="admin-stats-v2-kpi__icon">
                                    <i class="fas fa-<?php echo e((string) ($stat['icon'] ?? 'chart-line')); ?>" aria-hidden="true"></i>
                                </span>
                            </div>
                            <p class="mt-3 text-xs font-bold">
                                <i class="fas fa-arrow-up mr-1" aria-hidden="true"></i>
                                <?php echo e((string) ($stat['meta'] ?? 'Stable')); ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-chart-line text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Évolution des inscriptions</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e((string) ($period['display_label'] ?? '')); ?></p>
                            </div>
                            <span class="admin-dashboard-count admin-dashboard-count--info"><?php echo e((string) (($performanceCharts['students']['total_label'] ?? '0'))); ?></span>
                        </div>
                        <div class="admin-stats-v2-chart-box">
                            <?php $renderLineChart(is_array($performanceCharts['students'] ?? null) ? $performanceCharts['students'] : [], 'Aucune inscription sur cette période'); ?>
                        </div>
                    </article>

                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-money-bill-wave text-emerald-600 dark:text-emerald-300" aria-hidden="true"></i>Chiffre d’affaires</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Commission plateforme : <?php echo e(number_format($commissionPercent, 2, ',', ' ')); ?> %</p>
                            </div>
                            <span class="admin-dashboard-count admin-dashboard-count--success"><?php echo e((string) (($performanceCharts['revenue']['total_label'] ?? '0 F CFA'))); ?></span>
                        </div>
                        <div class="admin-stats-v2-chart-box">
                            <?php $renderLineChart(is_array($performanceCharts['revenue'] ?? null) ? $performanceCharts['revenue'] : [], 'Aucun revenu sur cette période'); ?>
                        </div>
                    </article>
                </section>

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <article class="admin-shell-card rounded-3xl p-5">
                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-layer-group text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Répartition des cours par catégorie</h3>
                        <div class="mt-5 space-y-3">
                            <?php if ($categoryBreakdown === []): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Aucune catégorie disponible.</p>
                            <?php endif; ?>
                            <?php foreach ($categoryBreakdown as $category): ?>
                                <?php $tone = $toneClass((string) ($category['tone'] ?? 'slate')); ?>
                                <div class="admin-stats-v2-bar-row <?php echo e($tone); ?>">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="font-black text-slate-800 dark:text-slate-100"><?php echo e((string) ($category['label'] ?? 'Catégorie')); ?></span>
                                        <span class="font-bold text-slate-500 dark:text-slate-400"><?php echo e($number((int) ($category['value'] ?? 0))); ?> cours</span>
                                    </div>
                                    <div class="admin-stats-v2-progress">
                                        <span style="width: <?php echo e((string) $progressWidth((int) ($category['pct'] ?? 0))); ?>%;"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>

                    <article class="admin-shell-card rounded-3xl p-5">
                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-trophy text-amber-600 dark:text-amber-300" aria-hidden="true"></i>Top enseignants</h3>
                        <div class="mt-5 space-y-3">
                            <?php if ($topTeachers === []): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Aucun revenu enseignant disponible.</p>
                            <?php endif; ?>
                            <?php foreach ($topTeachers as $teacher): ?>
                                <div class="admin-stats-v2-top-teacher">
                                    <span class="admin-stats-v2-rank"><?php echo e((string) ($teacher['rank'] ?? 0)); ?></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-black text-slate-900 dark:text-white"><?php echo e((string) ($teacher['name'] ?? 'Enseignant')); ?></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e($number((int) ($teacher['courses'] ?? 0))); ?> cours · <?php echo e($number((int) ($teacher['orders'] ?? 0))); ?> commande(s)</p>
                                        <div class="admin-stats-v2-progress mt-2">
                                            <span style="width: <?php echo e((string) $progressWidth((int) ($teacher['pct'] ?? 0))); ?>%;"></span>
                                        </div>
                                    </div>
                                    <strong><?php echo e((string) ($teacher['revenue_label'] ?? '0 F CFA')); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </section>

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <article class="admin-shell-card rounded-3xl p-5">
                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-filter text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Entonnoir de conversion</h3>
                        <div class="mt-5 space-y-4">
                            <?php foreach ($conversionFunnel as $step): ?>
                                <?php $tone = $toneClass((string) ($step['tone'] ?? 'slate')); ?>
                                <div class="admin-stats-v2-bar-row <?php echo e($tone); ?>">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="font-black text-slate-800 dark:text-slate-100"><?php echo e((string) ($step['label'] ?? 'Étape')); ?></span>
                                        <span class="font-bold text-slate-500 dark:text-slate-400"><?php echo e((string) ($step['value_label'] ?? '0')); ?> · <?php echo e((string) ($step['pct'] ?? 0)); ?>%</span>
                                    </div>
                                    <div class="admin-stats-v2-progress">
                                        <span style="width: <?php echo e((string) $progressWidth((int) ($step['bar_pct'] ?? 0))); ?>%;"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>

                    <article class="admin-shell-card rounded-3xl p-5">
                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-check-double text-emerald-600 dark:text-emerald-300" aria-hidden="true"></i>Taux de complétion par catégorie</h3>
                        <div class="mt-5 space-y-3">
                            <?php if ($completionByCategory === []): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Aucune donnée de complétion disponible.</p>
                            <?php endif; ?>
                            <?php foreach ($completionByCategory as $row): ?>
                                <div class="admin-stats-v2-completion">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-black"><?php echo e((string) ($row['label'] ?? 'Catégorie')); ?></span>
                                        <strong><?php echo e((string) ($row['rate'] ?? 0)); ?>%</strong>
                                    </div>
                                    <div class="admin-stats-v2-progress">
                                        <span style="width: <?php echo e((string) $progressWidth((int) ($row['rate'] ?? 0))); ?>%;"></span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e($number((int) ($row['done'] ?? 0))); ?> leçons terminées · <?php echo e($number((int) ($row['courses'] ?? 0))); ?> cours</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </section>

                <section class="admin-shell-card rounded-3xl p-5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-receipt text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Dernières transactions</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Montants réels des commandes récentes, avec commission estimée.</p>
                        </div>
                        <a href="<?php echo e(site_url('admin/sales')); ?>" class="admin-shell-button admin-shell-button--secondary">
                            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                            Voir les paiements
                        </a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="admin-shell-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Cours</th>
                                    <th>Montant</th>
                                    <th>Commission</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentTransactions === []): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-sm text-slate-500 dark:text-slate-400">Aucune transaction récente.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td class="font-black"><?php echo e((string) ($transaction['reference'] ?? '#')); ?></td>
                                        <td><?php echo e((string) ($transaction['customer'] ?? 'Client')); ?></td>
                                        <td class="max-w-md truncate"><?php echo e((string) ($transaction['courses'] ?? 'Cours')); ?></td>
                                        <td class="font-black"><?php echo e((string) ($transaction['amount_label'] ?? '0 F CFA')); ?></td>
                                        <td><?php echo e((string) ($transaction['commission_label'] ?? '0 F CFA')); ?></td>
                                        <td><span class="<?php echo e($pillClass((string) ($transaction['status_tone'] ?? 'slate'))); ?>"><?php echo e((string) ($transaction['status_label'] ?? 'Inconnu')); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-shell-card rounded-3xl p-5">
                    <details class="admin-stats-collapse">
                        <summary><i class="fas fa-sliders-h mr-1" aria-hidden="true"></i>Graphe avancé personnalisable</summary>
                        <form method="get" action="<?php echo e(site_url('admin/statistics')); ?>" class="admin-stats-v2-filter mt-4" data-form-kind="chart" data-admin-chart-form data-chart-granularity-map="<?php echo e((string) json_encode($chartGranularityMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)); ?>">
                            <input type="hidden" name="scope" value="<?php echo e($selectedScope); ?>">
                            <input type="hidden" name="months" value="<?php echo e((string) $selectedMonths); ?>">
                            <input type="hidden" name="month" value="<?php echo e((string) $selectedMonth); ?>">
                            <input type="hidden" name="year" value="<?php echo e((string) $selectedYear); ?>">

                            <label>
                                <span>Critère</span>
                                <select name="chart_metric" class="admin-shell-input" data-chart-metric>
                                    <?php foreach ($chartMetricOptions as $metricKey => $metricOption): ?>
                                        <option value="<?php echo e((string) $metricKey); ?>" <?php echo $selectedChartMetric === (string) $metricKey ? 'selected' : ''; ?>>
                                            <?php echo e((string) ($metricOption['label'] ?? $metricKey)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Regroupement</span>
                                <select name="chart_granularity" class="admin-shell-input" data-chart-granularity>
                                    <?php foreach ($chartGranularityOptions as $granularityKey => $granularityOption): ?>
                                        <option value="<?php echo e((string) $granularityKey); ?>" <?php echo $selectedChartGranularity === (string) $granularityKey ? 'selected' : ''; ?>>
                                            <?php echo e((string) ($granularityOption['label'] ?? $granularityKey)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Points</span>
                                <input type="number" name="chart_points" min="2" max="90" value="<?php echo e((string) $selectedChartPoints); ?>" class="admin-shell-input" inputmode="numeric" data-chart-points>
                            </label>
                        </form>
                        <div class="admin-stats-v2-chart-box mt-4">
                            <?php $renderLineChart($chart, 'Aucune donnée pour ce graphe'); ?>
                        </div>
                    </details>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
