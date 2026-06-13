<?php
$errors = is_array($errors ?? null) ? $errors : [];
$success = trim((string) ($success ?? ''));
$commissionPercent = (float) ($commissionPercent ?? 0);
$minWithdrawal = (float) ($minWithdrawal ?? 0);
$teacherMinWithdrawal = (float) ($teacherMinWithdrawal ?? $minWithdrawal);
$referralMinWithdrawal = (float) ($referralMinWithdrawal ?? 0);
$referralL1Rate = (float) ($referralL1Rate ?? 0);
$referralL2Rate = (float) ($referralL2Rate ?? 0);
$sales = array_values(is_array($sales ?? null) ? $sales : []);
$pendingOrders = array_values(is_array($pendingOrders ?? null) ? $pendingOrders : []);
$referralByItem = is_array($referralByItem ?? null) ? $referralByItem : [];
$referralByTeacher = is_array($referralByTeacher ?? null) ? $referralByTeacher : [];
$referralByCourse = is_array($referralByCourse ?? null) ? $referralByCourse : [];
$totalReferral = (int) ($totalReferral ?? 0);
$totalGross = (int) ($totalGross ?? 0);
$totalPlatform = (int) ($totalPlatform ?? 0);
$totalTeacher = (int) ($totalTeacher ?? 0);
$teacherTotals = is_array($teacherTotals ?? null) ? $teacherTotals : [];
$paidTotals = is_array($paidTotals ?? null) ? $paidTotals : [];
$payouts = array_values(is_array($payouts ?? null) ? $payouts : []);
$withdrawals = array_values(is_array($withdrawals ?? null) ? $withdrawals : []);
$teacherCourses = is_array($teacherCourses ?? null) ? $teacherCourses : [];
$teacherWithdrawalMethodCatalog = is_array($teacherWithdrawalMethodCatalog ?? null) ? $teacherWithdrawalMethodCatalog : [
    'fedapay' => 'Mobile Money',
    'crypto' => 'Crypto',
];
$teacherWithdrawalMethodsEnabled = array_values(is_array($teacherWithdrawalMethodsEnabled ?? null) ? $teacherWithdrawalMethodsEnabled : array_keys($teacherWithdrawalMethodCatalog));

$formatMoney = static function (int $amount): string {
    return number_format(max(0, $amount), 0, ',', ' ') . ' F CFA';
};

$formatMoneyShort = static function (int $amount): string {
    $amount = max(0, $amount);
    if ($amount >= 1000000) {
        return number_format($amount / 1000000, $amount >= 10000000 ? 0 : 1, ',', ' ') . ' M F CFA';
    }
    if ($amount >= 1000) {
        return number_format($amount / 1000, $amount >= 10000 ? 0 : 1, ',', ' ') . ' k F CFA';
    }
    return number_format($amount, 0, ',', ' ') . ' F CFA';
};

$formatPercent = static function (float $value): string {
    return number_format($value, 2, ',', ' ') . ' %';
};

$formatDateTime = static function (string $value): string {
    $raw = trim($value);
    if ($raw === '') {
        return '-';
    }
    try {
        return (new DateTimeImmutable($raw))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $raw;
    }
};

$normalizeSearch = static function (string $value): string {
    $value = trim($value);
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
};

$query = trim((string) ($_GET['q'] ?? ''));
$normalizedQuery = $normalizeSearch($query);
$matchesSearch = static function (array $parts) use ($normalizedQuery, $normalizeSearch): bool {
    if ($normalizedQuery === '') {
        return true;
    }
    $haystack = $normalizeSearch(implode(' ', array_map(static fn ($part): string => (string) $part, $parts)));
    return str_contains($haystack, $normalizedQuery);
};

$statusLabel = static function (string $status): string {
    return match (strtolower(trim($status))) {
        'paid' => 'Payé',
        'approved' => 'Approuvé',
        'pending', 'requested' => 'En attente',
        'rejected' => 'Rejeté',
        'refunded' => 'Remboursé',
        'simulated' => 'Simulé',
        'failed' => 'Échec',
        default => trim($status) !== '' ? ucfirst($status) : '-',
    };
};

$statusClass = static function (string $status): string {
    return match (strtolower(trim($status))) {
        'paid', 'approved' => 'app-status-pill app-status-pill--active',
        'pending', 'requested', 'simulated' => 'app-status-pill app-status-pill--pending',
        'rejected', 'refunded', 'failed' => 'app-status-pill app-status-pill--urgent',
        default => 'app-status-pill app-status-pill--done',
    };
};

$withdrawalTypeLabel = static function (string $type): string {
    return match (strtolower(trim($type))) {
        'teacher_sales' => 'Ventes enseignants',
        'referral' => 'Parrainage',
        default => trim($type) !== '' ? ucfirst($type) : 'Retrait',
    };
};

$withdrawalActionLabels = [
    'approved' => ['label' => 'Approuver', 'icon' => 'fa-check', 'class' => 'admin-finance-action admin-finance-action--approve'],
    'rejected' => ['label' => 'Rejeter', 'icon' => 'fa-times', 'class' => 'admin-finance-action admin-finance-action--reject'],
    'paid' => ['label' => 'Marquer payé', 'icon' => 'fa-money-bill-wave', 'class' => 'admin-finance-action admin-finance-action--paid'],
];

$withdrawalActions = static function (string $status): array {
    return match (strtolower(trim($status))) {
        'requested' => ['approved', 'rejected', 'paid'],
        'approved' => ['paid', 'rejected'],
        default => [],
    };
};

$monthStart = (new DateTimeImmutable('first day of this month'))->modify('-5 months');
$monthlyRevenue = [];
$monthlyCommission = [];
for ($i = 0; $i < 6; $i++) {
    $date = $monthStart->modify('+' . $i . ' months');
    $key = $date->format('Y-m');
    $monthlyRevenue[$key] = [
        'label' => $date->format('M'),
        'full_label' => $date->format('m/Y'),
        'value' => 0,
    ];
    $monthlyCommission[$key] = $monthlyRevenue[$key];
}

$saleRows = [];
$categoryBreakdown = [];
$totalSalesCount = 0;
foreach ($sales as $row) {
    $qty = max(1, (int) ($row['quantity'] ?? 1));
    $unit = max(0, (int) ($row['unit_price_xof'] ?? 0));
    $gross = $qty * $unit;
    $platformFee = (int) round($gross * $commissionPercent / 100);
    $referralFee = (int) ($referralByItem[(int) ($row['item_id'] ?? 0)] ?? 0);
    $teacherAmount = max(0, $gross - $platformFee - $referralFee);
    $buyerName = trim((string) ($row['buyer_first'] ?? '') . ' ' . (string) ($row['buyer_last'] ?? ''));
    $teacherName = trim((string) ($row['teacher_first'] ?? '') . ' ' . (string) ($row['teacher_last'] ?? ''));
    $courseTitle = trim((string) ($row['course_title'] ?? ''));
    $categoryName = trim((string) ($row['category_name'] ?? ''));
    $categoryName = $categoryName !== '' ? $categoryName : 'Sans catégorie';
    $createdAt = (string) ($row['order_created_at'] ?? '');
    $monthKey = '';
    try {
        $monthKey = (new DateTimeImmutable($createdAt))->format('Y-m');
    } catch (Throwable $e) {
        $monthKey = '';
    }
    if (isset($monthlyRevenue[$monthKey])) {
        $monthlyRevenue[$monthKey]['value'] += $gross;
        $monthlyCommission[$monthKey]['value'] += $platformFee;
    }
    if (!isset($categoryBreakdown[$categoryName])) {
        $categoryBreakdown[$categoryName] = [
            'name' => $categoryName,
            'gross' => 0,
            'commission' => 0,
            'sales' => 0,
        ];
    }
    $categoryBreakdown[$categoryName]['gross'] += $gross;
    $categoryBreakdown[$categoryName]['commission'] += $platformFee;
    $categoryBreakdown[$categoryName]['sales'] += $qty;
    $totalSalesCount += $qty;

    $searchParts = [
        (string) ($row['reference'] ?? ''),
        $buyerName,
        $teacherName,
        $courseTitle,
        $categoryName,
        $formatMoney($gross),
    ];
    if (!$matchesSearch($searchParts)) {
        continue;
    }

    $saleRows[] = [
        'reference' => (string) ($row['reference'] ?? ('#' . (int) ($row['order_id'] ?? 0))),
        'date' => $formatDateTime($createdAt),
        'buyer' => $buyerName !== '' ? $buyerName : 'Client',
        'course' => $courseTitle !== '' ? $courseTitle : 'Cours',
        'category' => $categoryName,
        'teacher' => $teacherName !== '' ? $teacherName : 'Enseignant',
        'amount' => $gross,
        'commission' => $platformFee,
        'referral' => $referralFee,
        'teacher_amount' => $teacherAmount,
        'search' => implode(' ', $searchParts),
    ];
}

usort($categoryBreakdown, static fn (array $a, array $b): int => ($b['commission'] <=> $a['commission']) ?: strcasecmp((string) $a['name'], (string) $b['name']));
$categoryBreakdown = array_slice(array_values($categoryBreakdown), 0, 6);
$maxCategoryCommission = max(1, ...array_map(static fn (array $row): int => (int) ($row['commission'] ?? 0), $categoryBreakdown ?: [['commission' => 1]]));

$pendingOrderRows = [];
foreach ($pendingOrders as $order) {
    $buyerName = trim((string) ($order['buyer_first'] ?? '') . ' ' . (string) ($order['buyer_last'] ?? ''));
    $searchParts = [
        (string) ($order['reference'] ?? ''),
        $buyerName,
        (string) ($order['provider'] ?? ''),
        (string) ($order['payment_ref'] ?? ''),
        $formatMoney((int) ($order['total_xof'] ?? 0)),
    ];
    if (!$matchesSearch($searchParts)) {
        continue;
    }
    $pendingOrderRows[] = [
        'id' => (int) ($order['id'] ?? 0),
        'reference' => (string) ($order['reference'] ?? ('CMD-' . (int) ($order['id'] ?? 0))),
        'buyer' => $buyerName !== '' ? $buyerName : 'Client',
        'amount' => (int) ($order['total_xof'] ?? 0),
        'provider' => trim((string) ($order['provider'] ?? '')),
        'payment_ref' => trim((string) ($order['payment_ref'] ?? '')),
        'payment_status' => trim((string) ($order['payment_status'] ?? 'pending')),
        'created_at' => $formatDateTime((string) ($order['created_at'] ?? '')),
        'search' => implode(' ', $searchParts),
    ];
}

$withdrawalRows = [];
$pendingWithdrawalsCount = 0;
$pendingWithdrawalAmount = 0;
$withdrawalsByStatus = ['requested' => 0, 'approved' => 0, 'paid' => 0, 'rejected' => 0];
foreach ($withdrawals as $withdrawal) {
    $status = strtolower(trim((string) ($withdrawal['status'] ?? 'requested')));
    $amount = (int) ($withdrawal['amount_xof'] ?? 0);
    $withdrawalsByStatus[$status] = ($withdrawalsByStatus[$status] ?? 0) + 1;
    if ($status === 'requested') {
        $pendingWithdrawalsCount++;
        $pendingWithdrawalAmount += $amount;
    }
    $requesterName = trim((string) ($withdrawal['teacher_first'] ?? '') . ' ' . (string) ($withdrawal['teacher_last'] ?? ''));
    $requesterName = $requesterName !== '' ? $requesterName : 'Utilisateur';
    $type = (string) ($withdrawal['withdrawal_type'] ?? 'teacher_sales');
    $method = trim((string) ($withdrawal['method'] ?? ''));
    $destination = trim((string) ($withdrawal['destination'] ?? ''));
    $searchParts = [
        $requesterName,
        (string) ($withdrawal['requester_email'] ?? ''),
        $withdrawalTypeLabel($type),
        $method,
        $destination,
        $formatMoney($amount),
        $statusLabel($status),
    ];
    if (!$matchesSearch($searchParts)) {
        continue;
    }
    $withdrawalRows[] = [
        'id' => (int) ($withdrawal['id'] ?? 0),
        'name' => $requesterName,
        'email' => trim((string) ($withdrawal['requester_email'] ?? '')),
        'type' => $type,
        'type_label' => $withdrawalTypeLabel($type),
        'amount' => $amount,
        'method' => $method !== '' ? $method : 'Non renseigné',
        'destination' => $destination,
        'status' => $status,
        'created_at' => $formatDateTime((string) ($withdrawal['created_at'] ?? '')),
        'reference' => trim((string) ($withdrawal['payout_reference'] ?? '')),
        'actions' => $withdrawalActions($status),
        'search' => implode(' ', $searchParts),
    ];
}

$teacherTotalsList = array_values($teacherTotals);
usort($teacherTotalsList, static fn (array $a, array $b): int => ((int) ($b['teacher_amount'] ?? 0) <=> (int) ($a['teacher_amount'] ?? 0)) ?: strcasecmp((string) ($a['teacher_name'] ?? ''), (string) ($b['teacher_name'] ?? '')));
$teacherRows = [];
$totalPaidToTeachers = 0;
foreach ($paidTotals as $paidAmount) {
    $totalPaidToTeachers += (int) $paidAmount;
}
foreach ($teacherTotalsList as $teacher) {
    $teacherId = (int) ($teacher['teacher_id'] ?? 0);
    $teacherName = trim((string) ($teacher['teacher_name'] ?? ''));
    $teacherName = $teacherName !== '' ? $teacherName : ('Enseignant #' . $teacherId);
    $gross = (int) ($teacher['total_sales'] ?? 0);
    $platformFee = (int) ($teacher['platform_fee'] ?? 0);
    $referralFee = (int) ($referralByTeacher[$teacherId] ?? 0);
    $teacherAmount = (int) ($teacher['teacher_amount'] ?? 0);
    $alreadyPaid = (int) ($paidTotals[$teacherId] ?? 0);
    $remaining = max(0, $teacherAmount - $alreadyPaid);
    $searchParts = [$teacherName, $formatMoney($gross), $formatMoney($teacherAmount), $formatMoney($remaining)];
    if (!$matchesSearch($searchParts)) {
        continue;
    }
    $teacherRows[] = [
        'id' => $teacherId,
        'name' => $teacherName,
        'gross' => $gross,
        'platform_fee' => $platformFee,
        'referral_fee' => $referralFee,
        'teacher_amount' => $teacherAmount,
        'already_paid' => $alreadyPaid,
        'remaining' => $remaining,
        'sales_count' => (int) ($teacher['sales_count'] ?? 0),
        'eligible' => $remaining >= $teacherMinWithdrawal && $remaining > 0,
        'search' => implode(' ', $searchParts),
    ];
}
$maxTeacherRemaining = max(1, ...array_map(static fn (array $row): int => (int) ($row['remaining'] ?? 0), $teacherRows ?: [['remaining' => 1]]));

$courseDetailsList = array_values($teacherCourses);
usort($courseDetailsList, static fn (array $a, array $b): int => strcasecmp((string) ($a['teacher_name'] ?? ''), (string) ($b['teacher_name'] ?? '')));

$payoutRows = [];
foreach ($payouts as $payout) {
    $teacherName = trim((string) ($payout['teacher_first'] ?? '') . ' ' . (string) ($payout['teacher_last'] ?? ''));
    $teacherName = $teacherName !== '' ? $teacherName : ('Enseignant #' . (int) ($payout['teacher_id'] ?? 0));
    $searchParts = [
        $teacherName,
        $formatMoney((int) ($payout['teacher_amount_xof'] ?? 0)),
        $statusLabel((string) ($payout['status'] ?? 'simulated')),
    ];
    if (!$matchesSearch($searchParts)) {
        continue;
    }
    $payoutRows[] = [
        'id' => (int) ($payout['id'] ?? 0),
        'teacher' => $teacherName,
        'total_sales' => (int) ($payout['total_sales_xof'] ?? 0),
        'platform_fee' => (int) ($payout['platform_fee_xof'] ?? 0),
        'teacher_amount' => (int) ($payout['teacher_amount_xof'] ?? 0),
        'commission_percent' => (float) ($payout['commission_percent'] ?? 0),
        'status' => (string) ($payout['status'] ?? 'simulated'),
        'created_at' => $formatDateTime((string) ($payout['created_at'] ?? '')),
        'search' => implode(' ', $searchParts),
    ];
}

$remainingToPay = max(0, $totalTeacher - $totalPaidToTeachers);
$pendingOrdersCount = count($pendingOrders);
$payoutsPendingCount = 0;
foreach ($payouts as $payout) {
    if (strtolower((string) ($payout['status'] ?? '')) !== 'paid') {
        $payoutsPendingCount++;
    }
}

$buildLineChart = static function (array $points, string $color, callable $valueFormatter): array {
    $width = 760;
    $height = 260;
    $left = 36;
    $right = 18;
    $top = 24;
    $bottom = 38;
    $plotWidth = $width - $left - $right;
    $plotHeight = $height - $top - $bottom;
    $values = array_map(static fn (array $point): int => (int) ($point['value'] ?? 0), $points);
    $max = max(1, ...($values !== [] ? $values : [1]));
    $series = [];
    $count = max(1, count($points));
    foreach ($points as $index => $point) {
        $x = $left + ($count === 1 ? 0 : ($plotWidth * $index / ($count - 1)));
        $y = $top + ($plotHeight - (((int) ($point['value'] ?? 0) / $max) * $plotHeight));
        $series[] = [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'value' => (int) ($point['value'] ?? 0),
            'value_label' => $valueFormatter((int) ($point['value'] ?? 0)),
            'label' => (string) ($point['label'] ?? ''),
            'full_label' => (string) ($point['full_label'] ?? ''),
        ];
    }
    $linePath = '';
    foreach ($series as $index => $point) {
        $linePath .= ($index === 0 ? 'M ' : ' L ') . $point['x'] . ' ' . $point['y'];
    }
    $areaPath = $linePath !== ''
        ? $linePath . ' L ' . ($series[count($series) - 1]['x'] ?? $left) . ' ' . ($height - $bottom) . ' L ' . ($series[0]['x'] ?? $left) . ' ' . ($height - $bottom) . ' Z'
        : '';
    $gridLines = [];
    for ($i = 0; $i <= 4; $i++) {
        $value = (int) round($max * (4 - $i) / 4);
        $gridLines[] = [
            'y' => round($top + ($plotHeight * $i / 4), 2),
            'label' => $valueFormatter($value),
        ];
    }
    return compact('width', 'height', 'left', 'right', 'top', 'bottom', 'plotWidth', 'plotHeight', 'series', 'linePath', 'areaPath', 'gridLines', 'color');
};

$revenueChart = $buildLineChart(array_values($monthlyRevenue), '#2563eb', $formatMoneyShort);
$commissionChart = $buildLineChart(array_values($monthlyCommission), '#0891b2', $formatMoneyShort);

$renderLineChart = static function (array $chart, string $emptyLabel): void {
    $series = is_array($chart['series'] ?? null) ? $chart['series'] : [];
    if ($series === [] || array_sum(array_map(static fn (array $point): int => (int) ($point['value'] ?? 0), $series)) <= 0) {
        ?>
        <div class="admin-finance-empty-chart">
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <span><?php echo e($emptyLabel); ?></span>
        </div>
        <?php
        return;
    }
    $width = (int) ($chart['width'] ?? 760);
    $height = (int) ($chart['height'] ?? 260);
    $bottom = (int) ($chart['bottom'] ?? 38);
    $color = (string) ($chart['color'] ?? '#2563eb');
    ?>
    <svg viewBox="0 0 <?php echo e((string) $width); ?> <?php echo e((string) $height); ?>" class="admin-finance-svg" aria-label="Graphique financier">
        <?php foreach ((array) ($chart['gridLines'] ?? []) as $line): ?>
            <line x1="<?php echo e((string) ($chart['left'] ?? 36)); ?>" y1="<?php echo e((string) ($line['y'] ?? 0)); ?>" x2="<?php echo e((string) ($width - (int) ($chart['right'] ?? 18))); ?>" y2="<?php echo e((string) ($line['y'] ?? 0)); ?>" class="admin-finance-svg__grid" />
            <text x="<?php echo e((string) (($chart['left'] ?? 36) - 8)); ?>" y="<?php echo e((string) (((float) ($line['y'] ?? 0)) + 4)); ?>" text-anchor="end" class="admin-finance-svg__label"><?php echo e((string) ($line['label'] ?? '0')); ?></text>
        <?php endforeach; ?>
        <path d="<?php echo e((string) ($chart['areaPath'] ?? '')); ?>" class="admin-finance-svg__area" />
        <path d="<?php echo e((string) ($chart['linePath'] ?? '')); ?>" fill="none" stroke="<?php echo e($color); ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <?php foreach ($series as $point): ?>
            <g>
                <title><?php echo e((string) (($point['full_label'] ?? '') . ' · ' . ($point['value_label'] ?? ''))); ?></title>
                <circle cx="<?php echo e((string) ($point['x'] ?? 0)); ?>" cy="<?php echo e((string) ($point['y'] ?? 0)); ?>" r="4.5" fill="<?php echo e($color); ?>" />
                <circle cx="<?php echo e((string) ($point['x'] ?? 0)); ?>" cy="<?php echo e((string) ($point['y'] ?? 0)); ?>" r="2" fill="#fff" />
                <text x="<?php echo e((string) ($point['x'] ?? 0)); ?>" y="<?php echo e((string) ($height - $bottom + 24)); ?>" text-anchor="middle" class="admin-finance-svg__label"><?php echo e((string) ($point['label'] ?? '')); ?></text>
            </g>
        <?php endforeach; ?>
    </svg>
    <?php
};

$selectedTab = strtolower(trim((string) ($_GET['tab'] ?? 'transactions')));
if (!in_array($selectedTab, ['transactions', 'withdrawals', 'teachers', 'settings'], true)) {
    $selectedTab = 'transactions';
}

$tabClass = static function (string $tab) use ($selectedTab): string {
    return 'admin-finance-tab' . ($selectedTab === $tab ? ' admin-finance-tab--active' : '');
};

$panelClass = static function (string $tab) use ($selectedTab): string {
    return $selectedTab === $tab ? 'space-y-5' : 'hidden space-y-5';
};

$adminNavActive = 'sales';
$adminUnreadCount = max(0, (int) ($unreadCount ?? 0));
$adminTopTitle = 'Gestion financière';
$adminTopSubtitle = 'Administration';
$pendingActions = $pendingOrdersCount + $pendingWithdrawalsCount;
$adminTopBadge = $pendingActions > 0 ? (string) $pendingActions . ' action(s) en attente' : 'Tout est à jour';
$adminTopBadgeTone = $pendingActions > 0 ? 'pending' : 'done';
$adminTopActions = [
    ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'],
    ['label' => 'Statistiques', 'url' => site_url('admin/statistics'), 'tone' => 'neutral'],
];
$adminTopSearchAction = site_url('admin/sales');
$adminTopSearchValue = $query;
$adminTopSearchPlaceholder = 'Rechercher une transaction, un retrait...';
$adminTopSearchHidden = ['tab' => $selectedTab];
$adminPageScripts = ['admin_sales_page.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion financière · Administrateur</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/sales" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop" aria-hidden="true">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense admin-finance-page px-4 py-5 sm:px-6 sm:py-6 space-y-6" data-admin-finance-page data-default-tab="<?php echo e($selectedTab); ?>">
                <?php if ($errors !== []): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $err): ?>
                            <p class="inline-flex items-center gap-2"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><?php echo e((string) $err); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="admin-shell-alert admin-shell-alert--success">
                        <p class="inline-flex items-center gap-2"><i class="fas fa-check-circle" aria-hidden="true"></i><?php echo e($success); ?></p>
                    </div>
                <?php endif; ?>

                <section class="admin-finance-hero rounded-3xl p-6 text-white">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.24em] text-white/75">
                                <i class="fas fa-wallet" aria-hidden="true"></i>
                                Finances & commissions
                            </p>
                            <h2 class="mt-2 text-2xl font-black sm:text-3xl">Gestion financière</h2>
                            <p class="mt-2 max-w-3xl text-sm font-medium text-white/82">
                                Suivez les revenus, traitez les demandes de retrait et ajustez les paramètres de commission depuis une seule interface.
                            </p>
                        </div>
                        <div class="admin-finance-hero__status">
                            <i class="fas fa-bolt" aria-hidden="true"></i>
                            <span><?php echo e((string) $pendingActions); ?> action(s) à vérifier</span>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="admin-finance-kpi admin-finance-tone--emerald rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Chiffre d’affaires total</p>
                                <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white"><?php echo e($formatMoney($totalGross)); ?></p>
                            </div>
                            <span class="admin-finance-kpi__icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-emerald-700 dark:text-emerald-300"><?php echo e((string) count($sales)); ?> commande(s) payée(s) · <?php echo e(number_format($totalSalesCount, 0, ',', ' ')); ?> vente(s)</p>
                    </article>

                    <article class="admin-finance-kpi admin-finance-tone--blue rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Commission plateforme</p>
                                <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white"><?php echo e($formatMoney($totalPlatform)); ?></p>
                            </div>
                            <span class="admin-finance-kpi__icon"><i class="fas fa-percent" aria-hidden="true"></i></span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-blue-700 dark:text-blue-300">Taux actuel : <?php echo e($formatPercent($commissionPercent)); ?></p>
                    </article>

                    <article class="admin-finance-kpi admin-finance-tone--indigo rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">À reverser aux enseignants</p>
                                <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white"><?php echo e($formatMoney($remainingToPay)); ?></p>
                            </div>
                            <span class="admin-finance-kpi__icon"><i class="fas fa-hand-holding-usd" aria-hidden="true"></i></span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-indigo-700 dark:text-indigo-300"><?php echo e($formatMoney($totalPaidToTeachers)); ?> déjà payé(s)</p>
                    </article>

                    <article class="admin-finance-kpi admin-finance-tone--amber rounded-2xl p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Retraits à traiter</p>
                                <p class="mt-2 text-2xl font-black text-slate-950 dark:text-white"><?php echo e($formatMoney($pendingWithdrawalAmount)); ?></p>
                            </div>
                            <span class="admin-finance-kpi__icon"><i class="fas fa-money-bill-wave" aria-hidden="true"></i></span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-amber-700 dark:text-amber-300"><?php echo e((string) $pendingWithdrawalsCount); ?> demande(s) en attente</p>
                    </article>
                </section>

                <section class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-chart-line text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                Évolution des revenus
                            </h3>
                            <span class="admin-dashboard-count admin-dashboard-count--success"><?php echo e($formatMoney($totalGross)); ?></span>
                        </div>
                        <div class="admin-finance-chart mt-4">
                            <?php $renderLineChart($revenueChart, 'Aucun revenu sur les six derniers mois'); ?>
                        </div>
                    </article>

                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-layer-group text-cyan-600 dark:text-cyan-300" aria-hidden="true"></i>
                                Commissions par catégorie
                            </h3>
                            <span class="admin-dashboard-count admin-dashboard-count--info"><?php echo e((string) count($categoryBreakdown)); ?> catégorie(s)</span>
                        </div>
                        <div class="mt-5 space-y-4">
                            <?php if ($categoryBreakdown === []): ?>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Aucune commission disponible.</p>
                            <?php else: ?>
                                <?php foreach ($categoryBreakdown as $category): ?>
                                    <?php $percent = (int) round(((int) ($category['commission'] ?? 0) / $maxCategoryCommission) * 100); ?>
                                    <div class="admin-finance-bar-row">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-bold text-slate-900 dark:text-white"><?php echo e((string) ($category['name'] ?? 'Catégorie')); ?></span>
                                            <span class="text-xs font-black text-slate-500 dark:text-slate-400"><?php echo e($formatMoney((int) ($category['commission'] ?? 0))); ?></span>
                                        </div>
                                        <div class="admin-finance-progress"><span style="width: <?php echo e((string) max(4, $percent)); ?>%;"></span></div>
                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e((string) ($category['sales'] ?? 0)); ?> vente(s) · Brut : <?php echo e($formatMoney((int) ($category['gross'] ?? 0))); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                </section>

                <section class="admin-shell-card rounded-3xl p-4 sm:p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-sliders-h admin-inline-accent admin-inline-accent--info" aria-hidden="true"></i>
                                Espace financier
                            </p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Transactions, retraits, enseignants et paramètres financiers.</p>
                        </div>
                        <label class="admin-finance-search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" value="<?php echo e($query); ?>" placeholder="Filtrer la page..." data-admin-finance-search>
                        </label>
                    </div>

                    <div class="admin-finance-tabs mt-5" role="tablist" aria-label="Sections financières">
                        <button type="button" class="<?php echo e($tabClass('transactions')); ?>" data-finance-tab="transactions">
                            <i class="fas fa-list" aria-hidden="true"></i>
                            Transactions
                        </button>
                        <button type="button" class="<?php echo e($tabClass('withdrawals')); ?>" data-finance-tab="withdrawals">
                            <i class="fas fa-hand-holding-usd" aria-hidden="true"></i>
                            Retraits
                            <?php if ($pendingWithdrawalsCount > 0): ?><span><?php echo e((string) $pendingWithdrawalsCount); ?></span><?php endif; ?>
                        </button>
                        <button type="button" class="<?php echo e($tabClass('teachers')); ?>" data-finance-tab="teachers">
                            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                            Enseignants
                        </button>
                        <button type="button" class="<?php echo e($tabClass('settings')); ?>" data-finance-tab="settings">
                            <i class="fas fa-cog" aria-hidden="true"></i>
                            Configuration
                        </button>
                    </div>
                </section>

                <section class="<?php echo e($panelClass('transactions')); ?>" data-finance-panel="transactions">
                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-receipt text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                Transactions payées
                            </h3>
                            <span class="admin-dashboard-count admin-dashboard-count--info"><?php echo e((string) count($saleRows)); ?> ligne(s)</span>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="admin-shell-table min-w-[1060px] text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3">ID</th>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">Client</th>
                                        <th class="px-4 py-3">Cours</th>
                                        <th class="px-4 py-3 text-right">Montant</th>
                                        <th class="px-4 py-3 text-right">Commission</th>
                                        <th class="px-4 py-3">Enseignant</th>
                                        <th class="px-4 py-3">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($saleRows === []): ?>
                                        <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Aucune transaction trouvée.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($saleRows as $sale): ?>
                                            <tr data-finance-search-row data-finance-search="<?php echo e($normalizeSearch((string) ($sale['search'] ?? ''))); ?>">
                                                <td class="px-4 py-3 font-black"><?php echo e((string) ($sale['reference'] ?? '#')); ?></td>
                                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400"><?php echo e((string) ($sale['date'] ?? '-')); ?></td>
                                                <td class="px-4 py-3"><?php echo e((string) ($sale['buyer'] ?? 'Client')); ?></td>
                                                <td class="px-4 py-3">
                                                    <p class="font-bold text-slate-900 dark:text-white"><?php echo e((string) ($sale['course'] ?? 'Cours')); ?></p>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e((string) ($sale['category'] ?? 'Sans catégorie')); ?></p>
                                                </td>
                                                <td class="px-4 py-3 text-right font-black"><?php echo e($formatMoney((int) ($sale['amount'] ?? 0))); ?></td>
                                                <td class="px-4 py-3 text-right text-primary-700 dark:text-primary-300 font-bold"><?php echo e($formatMoney((int) ($sale['commission'] ?? 0))); ?></td>
                                                <td class="px-4 py-3"><?php echo e((string) ($sale['teacher'] ?? 'Enseignant')); ?></td>
                                                <td class="px-4 py-3"><span class="app-status-pill app-status-pill--active">Payé</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-hourglass-half text-amber-600 dark:text-amber-300" aria-hidden="true"></i>
                                Commandes en attente
                            </h3>
                            <span class="admin-dashboard-count admin-dashboard-count--warning"><?php echo e((string) count($pendingOrderRows)); ?> commande(s)</span>
                        </div>
                        <?php if ($pendingOrderRows === []): ?>
                            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Aucune commande en attente.</p>
                        <?php else: ?>
                            <div class="mt-4 grid gap-3">
                                <?php foreach ($pendingOrderRows as $order): ?>
                                    <article class="admin-finance-pending-order rounded-2xl p-4" data-finance-search-row data-finance-search="<?php echo e($normalizeSearch((string) ($order['search'] ?? ''))); ?>">
                                        <div>
                                            <p class="font-black text-slate-900 dark:text-white"><?php echo e((string) ($order['reference'] ?? '#')); ?></p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo e((string) ($order['buyer'] ?? 'Client')); ?> · <?php echo e((string) ($order['created_at'] ?? '-')); ?></p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Fournisseur : <?php echo e((string) ($order['provider'] ?: 'Non renseigné')); ?> · Paiement : <?php echo e((string) ($order['payment_ref'] ?: 'Sans référence')); ?></p>
                                        </div>
                                        <div class="admin-finance-pending-order__side">
                                            <strong><?php echo e($formatMoney((int) ($order['amount'] ?? 0))); ?></strong>
                                            <form method="post" action="<?php echo e(site_url('admin/sales')); ?>" class="inline-flex flex-wrap items-center gap-2">
                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="mark_order_paid">
                                                <input type="hidden" name="order_id" value="<?php echo e((string) ($order['id'] ?? 0)); ?>">
                                                <input type="hidden" name="payment_provider" value="<?php echo e((string) ($order['provider'] ?? 'manual')); ?>">
                                                <input type="text" name="payment_ref" value="<?php echo e((string) ($order['payment_ref'] ?? '')); ?>" placeholder="Référence paiement" class="admin-shell-input admin-finance-ref-input">
                                                <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2">
                                                    <i class="fas fa-check" aria-hidden="true"></i>
                                                    Marquer payé
                                                </button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </section>

                <section class="<?php echo e($panelClass('withdrawals')); ?>" data-finance-panel="withdrawals">
                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-hand-holding-usd text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                Demandes de retrait
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <span class="admin-dashboard-count admin-dashboard-count--warning">En attente : <?php echo e((string) ($withdrawalsByStatus['requested'] ?? 0)); ?></span>
                                <span class="admin-dashboard-count admin-dashboard-count--success">Payés : <?php echo e((string) ($withdrawalsByStatus['paid'] ?? 0)); ?></span>
                            </div>
                        </div>
                        <?php if ($withdrawalRows === []): ?>
                            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Aucune demande de retrait trouvée.</p>
                        <?php else: ?>
                            <div class="mt-4 grid gap-4">
                                <?php foreach ($withdrawalRows as $withdrawal): ?>
                                    <article class="admin-finance-withdrawal rounded-2xl p-4" data-finance-search-row data-finance-search="<?php echo e($normalizeSearch((string) ($withdrawal['search'] ?? ''))); ?>">
                                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="admin-finance-avatar"><?php echo e(strtoupper(substr((string) ($withdrawal['name'] ?? 'U'), 0, 1))); ?></span>
                                                    <div>
                                                        <p class="font-black text-slate-900 dark:text-white"><?php echo e((string) ($withdrawal['name'] ?? 'Utilisateur')); ?></p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e((string) ($withdrawal['email'] ?: 'Email non renseigné')); ?></p>
                                                    </div>
                                                    <span class="<?php echo e($statusClass((string) ($withdrawal['status'] ?? 'requested'))); ?>"><?php echo e($statusLabel((string) ($withdrawal['status'] ?? 'requested'))); ?></span>
                                                </div>
                                                <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                                    <span class="admin-finance-info-pill"><i class="fas fa-tag" aria-hidden="true"></i><?php echo e((string) ($withdrawal['type_label'] ?? 'Retrait')); ?></span>
                                                    <span class="admin-finance-info-pill"><i class="fas fa-wallet" aria-hidden="true"></i><?php echo e($formatMoney((int) ($withdrawal['amount'] ?? 0))); ?></span>
                                                    <span class="admin-finance-info-pill"><i class="fas fa-credit-card" aria-hidden="true"></i><?php echo e((string) ($withdrawal['method'] ?? 'Non renseigné')); ?></span>
                                                    <span class="admin-finance-info-pill"><i class="far fa-calendar-alt" aria-hidden="true"></i><?php echo e((string) ($withdrawal['created_at'] ?? '-')); ?></span>
                                                </div>
                                                <?php if ((string) ($withdrawal['destination'] ?? '') !== '' || (string) ($withdrawal['reference'] ?? '') !== ''): ?>
                                                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                                                        <?php if ((string) ($withdrawal['destination'] ?? '') !== ''): ?>Destination : <?php echo e((string) ($withdrawal['destination'] ?? '')); ?><?php endif; ?>
                                                        <?php if ((string) ($withdrawal['reference'] ?? '') !== ''): ?> · Référence : <?php echo e((string) ($withdrawal['reference'] ?? '')); ?><?php endif; ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (trim((string) ($withdrawal['reason'] ?? '')) !== ''): ?>
                                                    <p class="mt-2 text-xs text-rose-600 dark:text-rose-300">
                                                        Motif : <?php echo e(trim((string) ($withdrawal['reason'] ?? ''))); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="admin-finance-withdrawal__actions">
                                                <?php if (($withdrawal['actions'] ?? []) !== []): ?>
                                                    <form method="post" action="<?php echo e(site_url('admin/sales')); ?>" class="grid gap-2">
                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                        <input type="hidden" name="action" value="update_withdrawal_status">
                                                        <input type="hidden" name="withdrawal_id" value="<?php echo e((string) ($withdrawal['id'] ?? 0)); ?>">
                                                        <input type="text" name="payout_reference" value="" placeholder="Référence si payé" class="admin-shell-input">
                                                        <textarea name="reason" rows="2" placeholder="Motif requis pour un rejet" class="admin-shell-input min-h-[5rem] resize-y"></textarea>
                                                        <p class="text-[0.72rem] text-slate-500 dark:text-slate-400">Le motif de rejet sera visible dans la notification.</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            <?php foreach ((array) ($withdrawal['actions'] ?? []) as $action): ?>
                                                                <?php $actionMeta = $withdrawalActionLabels[(string) $action] ?? ['label' => $action, 'icon' => 'fa-play', 'class' => 'admin-finance-action']; ?>
                                                                <button type="submit" name="target_status" value="<?php echo e((string) $action); ?>" class="<?php echo e((string) ($actionMeta['class'] ?? 'admin-finance-action')); ?>">
                                                                    <i class="fas <?php echo e((string) ($actionMeta['icon'] ?? 'fa-play')); ?>" aria-hidden="true"></i>
                                                                    <?php echo e((string) ($actionMeta['label'] ?? $action)); ?>
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400"><i class="fas fa-lock mr-1" aria-hidden="true"></i>Aucune action disponible</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-check-circle text-emerald-600 dark:text-emerald-300" aria-hidden="true"></i>
                                Paiements enseignants récents
                            </h3>
                            <span class="admin-dashboard-count admin-dashboard-count--info"><?php echo e((string) count($payoutRows)); ?> paiement(s)</span>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="admin-shell-table min-w-[980px] text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3">Enseignant</th>
                                        <th class="px-4 py-3 text-right">Total ventes</th>
                                        <th class="px-4 py-3 text-right">Commission</th>
                                        <th class="px-4 py-3 text-right">Net</th>
                                        <th class="px-4 py-3">Statut</th>
                                        <th class="px-4 py-3">Créé le</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($payoutRows === []): ?>
                                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Aucun paiement enregistré.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($payoutRows as $payout): ?>
                                            <tr data-finance-search-row data-finance-search="<?php echo e($normalizeSearch((string) ($payout['search'] ?? ''))); ?>">
                                                <td class="px-4 py-3 font-bold"><?php echo e((string) ($payout['teacher'] ?? 'Enseignant')); ?></td>
                                                <td class="px-4 py-3 text-right"><?php echo e($formatMoney((int) ($payout['total_sales'] ?? 0))); ?></td>
                                                <td class="px-4 py-3 text-right"><?php echo e($formatMoney((int) ($payout['platform_fee'] ?? 0))); ?></td>
                                                <td class="px-4 py-3 text-right font-black"><?php echo e($formatMoney((int) ($payout['teacher_amount'] ?? 0))); ?></td>
                                                <td class="px-4 py-3"><span class="<?php echo e($statusClass((string) ($payout['status'] ?? 'simulated'))); ?>"><?php echo e($statusLabel((string) ($payout['status'] ?? 'simulated'))); ?></span></td>
                                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400"><?php echo e((string) ($payout['created_at'] ?? '-')); ?></td>
                                                <td class="px-4 py-3 text-right">
                                                    <?php if (strtolower((string) ($payout['status'] ?? '')) !== 'paid'): ?>
                                                        <form method="post" action="<?php echo e(site_url('admin/sales')); ?>">
                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                            <input type="hidden" name="action" value="mark_paid">
                                                            <input type="hidden" name="payout_id" value="<?php echo e((string) ($payout['id'] ?? 0)); ?>">
                                                            <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2"><i class="fas fa-check" aria-hidden="true"></i>Marquer payé</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Payé</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>

                <section class="<?php echo e($panelClass('teachers')); ?>" data-finance-panel="teachers">
                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-user-tie text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                Soldes enseignants
                            </h3>
                            <span class="admin-dashboard-count admin-dashboard-count--info"><?php echo e((string) count($teacherRows)); ?> enseignant(s)</span>
                        </div>
                        <?php if ($teacherRows === []): ?>
                            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Aucune vente enseignant disponible.</p>
                        <?php else: ?>
                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <?php foreach ($teacherRows as $teacher): ?>
                                    <?php $remainingPercent = (int) round(((int) ($teacher['remaining'] ?? 0) / $maxTeacherRemaining) * 100); ?>
                                    <article class="admin-finance-teacher rounded-2xl p-4" data-finance-search-row data-finance-search="<?php echo e($normalizeSearch((string) ($teacher['search'] ?? ''))); ?>">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-black text-slate-900 dark:text-white"><?php echo e((string) ($teacher['name'] ?? 'Enseignant')); ?></p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e((string) ($teacher['sales_count'] ?? 0)); ?> vente(s)</p>
                                            </div>
                                            <span class="app-status-pill <?php echo !empty($teacher['eligible']) ? 'app-status-pill--active' : 'app-status-pill--pending'; ?>"><?php echo !empty($teacher['eligible']) ? 'Éligible' : 'Sous seuil'; ?></span>
                                        </div>
                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <span class="admin-finance-info-pill"><i class="fas fa-receipt" aria-hidden="true"></i>Brut : <?php echo e($formatMoney((int) ($teacher['gross'] ?? 0))); ?></span>
                                            <span class="admin-finance-info-pill"><i class="fas fa-percent" aria-hidden="true"></i>Commission : <?php echo e($formatMoney((int) ($teacher['platform_fee'] ?? 0))); ?></span>
                                            <span class="admin-finance-info-pill"><i class="fas fa-users" aria-hidden="true"></i>Parrainage : <?php echo e($formatMoney((int) ($teacher['referral_fee'] ?? 0))); ?></span>
                                            <span class="admin-finance-info-pill"><i class="fas fa-money-bill-wave" aria-hidden="true"></i>Payé : <?php echo e($formatMoney((int) ($teacher['already_paid'] ?? 0))); ?></span>
                                        </div>
                                        <div class="mt-4">
                                            <div class="flex items-center justify-between gap-3 text-sm">
                                                <span class="font-bold text-slate-500 dark:text-slate-400">Reste à payer</span>
                                                <strong class="text-slate-950 dark:text-white"><?php echo e($formatMoney((int) ($teacher['remaining'] ?? 0))); ?></strong>
                                            </div>
                                            <div class="admin-finance-progress mt-2"><span style="width: <?php echo e((string) max(4, $remainingPercent)); ?>%;"></span></div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="admin-shell-card rounded-3xl p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h3 class="text-lg font-black inline-flex items-center gap-2">
                                <i class="fas fa-book-open text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                Détail par cours
                            </h3>
                            <span class="admin-dashboard-count admin-dashboard-count--info"><?php echo e((string) count($courseDetailsList)); ?> groupe(s)</span>
                        </div>
                        <?php if ($courseDetailsList === []): ?>
                            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Aucun détail de cours disponible.</p>
                        <?php else: ?>
                            <div class="mt-4 space-y-3">
                                <?php foreach ($courseDetailsList as $teacher): ?>
                                    <?php
                                    $teacherName = trim((string) ($teacher['teacher_name'] ?? ''));
                                    $courses = array_values(is_array($teacher['courses'] ?? null) ? $teacher['courses'] : []);
                                    ?>
                                    <details class="admin-finance-details rounded-2xl p-4">
                                        <summary>
                                            <span>
                                                <strong><?php echo e($teacherName !== '' ? $teacherName : 'Enseignant'); ?></strong>
                                                <small>Brut : <?php echo e($formatMoney((int) ($teacher['total_gross'] ?? 0))); ?> · Net : <?php echo e($formatMoney((int) ($teacher['total_teacher'] ?? 0))); ?></small>
                                            </span>
                                            <span class="admin-dashboard-count admin-dashboard-count--info"><?php echo e((string) count($courses)); ?> cours</span>
                                        </summary>
                                        <?php if ($courses !== []): ?>
                                            <div class="mt-4 overflow-x-auto">
                                                <table class="admin-shell-table min-w-[860px] text-sm">
                                                    <thead>
                                                        <tr>
                                                            <th class="px-4 py-3">Cours</th>
                                                            <th class="px-4 py-3 text-right">Ventes</th>
                                                            <th class="px-4 py-3 text-right">Brut</th>
                                                            <th class="px-4 py-3 text-right">Commission</th>
                                                            <th class="px-4 py-3 text-right">Parrainage</th>
                                                            <th class="px-4 py-3 text-right">Net</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($courses as $course): ?>
                                                            <tr>
                                                                <td class="px-4 py-3 font-bold"><?php echo e((string) ($course['course_title'] ?: 'Cours')); ?></td>
                                                                <td class="px-4 py-3 text-right"><?php echo e((string) ($course['sales_count'] ?? 0)); ?></td>
                                                                <td class="px-4 py-3 text-right"><?php echo e($formatMoney((int) ($course['gross'] ?? 0))); ?></td>
                                                                <td class="px-4 py-3 text-right"><?php echo e($formatMoney((int) ($course['platform_fee'] ?? 0))); ?></td>
                                                                <td class="px-4 py-3 text-right"><?php echo e($formatMoney((int) ($course['referral_fee'] ?? 0))); ?></td>
                                                                <td class="px-4 py-3 text-right font-black"><?php echo e($formatMoney((int) ($course['teacher_amount'] ?? 0))); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </section>

                <section class="<?php echo e($panelClass('settings')); ?>" data-finance-panel="settings">
                    <div class="grid gap-5 xl:grid-cols-2 2xl:grid-cols-4">
                        <article class="admin-shell-card rounded-3xl p-5">
                            <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-percent text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Commission plateforme</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pourcentage prélevé sur chaque vente payée.</p>
                            <form class="mt-5 space-y-4" method="post" action="<?php echo e(site_url('admin/sales')); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="save_commission">
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Taux de commission (%)</span>
                                    <input type="number" step="0.1" min="0" max="100" name="commission_percent" value="<?php echo e(number_format($commissionPercent, 2, '.', '')); ?>" class="admin-shell-input">
                                </label>
                                <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2"><i class="fas fa-save" aria-hidden="true"></i>Enregistrer</button>
                            </form>
                        </article>

                        <article class="admin-shell-card rounded-3xl p-5">
                            <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-wallet text-emerald-600 dark:text-emerald-300" aria-hidden="true"></i>Seuils de retrait</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Montant minimal pour demander un retrait.</p>
                            <form class="mt-5 space-y-4" method="post" action="<?php echo e(site_url('admin/sales')); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="save_withdrawal_minimums">
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Enseignants (F CFA)</span>
                                    <input type="number" min="0" step="1" name="teacher_min_withdrawal_xof" value="<?php echo e((string) (int) $teacherMinWithdrawal); ?>" class="admin-shell-input">
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Parrainage (F CFA)</span>
                                    <input type="number" min="0" step="1" name="referral_min_withdrawal_xof" value="<?php echo e((string) (int) $referralMinWithdrawal); ?>" class="admin-shell-input">
                                </label>
                                <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2"><i class="fas fa-save" aria-hidden="true"></i>Enregistrer</button>
                            </form>
                        </article>

                        <article class="admin-shell-card rounded-3xl p-5">
                            <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-mobile-alt text-sky-600 dark:text-sky-300" aria-hidden="true"></i>Méthodes de retrait</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choisissez ce que l’enseignant peut sélectionner dans sa demande de retrait.</p>
                            <form class="mt-5 space-y-4" method="post" action="<?php echo e(site_url('admin/sales')); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="save_teacher_withdrawal_methods">
                                <div class="space-y-3">
                                    <?php foreach ($teacherWithdrawalMethodCatalog as $methodKey => $methodLabel): ?>
                                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">
                                            <input type="checkbox" name="teacher_withdrawal_methods[]" value="<?php echo e($methodKey); ?>" class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500" <?php echo in_array($methodKey, $teacherWithdrawalMethodsEnabled, true) ? 'checked' : ''; ?>>
                                            <span><?php echo e($methodLabel); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2"><i class="fas fa-save" aria-hidden="true"></i>Enregistrer</button>
                            </form>
                        </article>

                        <article class="admin-shell-card rounded-3xl p-5">
                            <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-user-plus text-indigo-600 dark:text-indigo-300" aria-hidden="true"></i>Parrainage</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Taux de commission par niveau.</p>
                            <form class="mt-5 space-y-4" method="post" action="<?php echo e(site_url('admin/sales')); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="save_referral_rates">
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Niveau 1 (%)</span>
                                    <input type="number" step="0.1" min="0" max="100" name="referral_l1_rate" value="<?php echo e(number_format($referralL1Rate, 2, '.', '')); ?>" class="admin-shell-input">
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Niveau 2 (%)</span>
                                    <input type="number" step="0.1" min="0" max="100" name="referral_l2_rate" value="<?php echo e(number_format($referralL2Rate, 2, '.', '')); ?>" class="admin-shell-input">
                                </label>
                                <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2"><i class="fas fa-save" aria-hidden="true"></i>Enregistrer</button>
                            </form>
                        </article>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
