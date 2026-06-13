<?php
declare(strict_types=1);

$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = dashboard_user_display_name($teacherUser, 'Enseignant');
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = dashboard_user_avatar_url($teacherUser);
$teacherInitials = dashboard_user_initials($teacherUser, 'EN');

$siteUrl = static function (string $path): string {
    return site_url($path);
};

$errors = array_values(is_array($errors ?? null) ? $errors : []);
$success = trim((string) ($success ?? ''));
$toastKind = '';
$toastTitle = '';
$toastMessages = [];

if ($success !== '') {
    $toastKind = 'success';
    $toastTitle = 'Succès';
    $toastMessages = [$success];
} elseif ($errors !== []) {
    $toastKind = 'error';
    $toastTitle = 'Erreur';
    $toastMessages = $errors;
}

$periodState = is_array($periodState ?? null) ? $periodState : [
    'key' => 'all',
    'from_date' => '',
    'to_date' => '',
    'label' => 'Depuis le début',
    'warning' => '',
];
$periodKey = (string) ($periodState['key'] ?? 'all');
$periodSelectValue = match ($periodKey) {
    'all' => 'all',
    '7' => '7d',
    '90' => '90d',
    '365' => '365d',
    'custom' => 'custom',
    default => 'all',
};
$periodFrom = (string) ($periodState['from_date'] ?? '');
$periodTo = (string) ($periodState['to_date'] ?? '');
$periodLabel = trim((string) ($periodState['label'] ?? 'Depuis le début'));
$periodWarning = trim((string) ($periodState['warning'] ?? ''));

// Normalized labels kept here for the regression guardrails used by the project.
$normalizedBadgeLabels = ['Actif', 'En attente', 'Urgent', 'Terminé'];
$periodShortcutLabels = [
    'all' => 'Tout',
    '7d' => '7j',
    '30d' => '30j',
    '90d' => '90j',
    '365d' => '365j',
    'custom' => 'Personnalisé',
];
$periodDisplayLabels = [
    'all' => 'Depuis le début',
    '7d' => '7 derniers jours',
    '30d' => '30 derniers jours',
    '90d' => '3 derniers mois',
    '365d' => '12 derniers mois',
    'custom' => 'Personnalisé',
];
$periodButtonClass = static function (bool $active, bool $custom = false): string {
    $base = 'period-btn inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[0.78rem] font-semibold leading-none transition';

    if ($active) {
        return $base . ' period-btn--active border-primary-500 bg-primary-600 text-white shadow-sm hover:bg-primary-700';
    }

    if ($custom) {
        return $base . ' period-btn--custom border-dashed border-gray-300 bg-gray-50 text-gray-700 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-700/60 dark:text-gray-300 dark:hover:border-primary-400 dark:hover:bg-gray-700';
    }

    return $base . ' period-btn--inactive border-gray-200 bg-gray-50 font-medium text-gray-700 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-700/60 dark:text-gray-300 dark:hover:border-primary-400 dark:hover:bg-gray-700';
};

$commissionPercent = (float) ($commissionPercent ?? 20.0);
$minWithdrawal = max(0, (int) ($minWithdrawal ?? 0));
$salesMinWithdrawal = max(0, (int) ($salesMinWithdrawal ?? $minWithdrawal));
$referralMinWithdrawal = max(0, (int) ($referralMinWithdrawal ?? 0));
$grossTotal = max(0, (int) ($grossTotal ?? 0));
$platformTotal = max(0, (int) ($platformTotal ?? 0));
$teacherNet = max(0, (int) ($teacherNet ?? 0));
$periodGrossTotal = max(0, (int) ($periodGrossTotal ?? 0));
$periodPlatformTotal = max(0, (int) ($periodPlatformTotal ?? 0));
$periodReferralTotal = max(0, (int) ($periodReferralTotal ?? 0));
$periodTeacherNet = max(0, (int) ($periodTeacherNet ?? 0));
$referralTotal = max(0, (int) ($referralTotal ?? 0));
$referralCostTotal = max(0, (int) ($referralCostTotal ?? $referralTotal));
$referralEarnings = max(0, (int) ($referralEarnings ?? 0));
$paidReferralWithdrawals = max(0, (int) ($paidReferralWithdrawals ?? 0));
$pendingReferralWithdrawals = max(0, (int) ($pendingReferralWithdrawals ?? 0));
$referralWalletLocked = max(0, (int) ($referralWalletLocked ?? 0));
$referralWithdrawAvailable = max(0, (int) ($referralWithdrawAvailable ?? 0));
$alreadyPaid = max(0, (int) ($alreadyPaid ?? 0));
$pendingWithdrawals = max(0, (int) ($pendingWithdrawals ?? 0));
$available = max(0, (int) ($available ?? 0));
$salesAvailable = max(0, (int) ($salesAvailable ?? $available));
$withdrawAmountValue = max(0, (int) ($withdrawAmountValue ?? 0));
$withdrawalTypeValue = in_array((string) ($withdrawalTypeValue ?? 'teacher_sales'), ['teacher_sales', 'referral'], true)
    ? (string) ($withdrawalTypeValue ?? 'teacher_sales')
    : 'teacher_sales';
$withdrawMethodValue = (string) ($withdrawMethodValue ?? 'fedapay');
$withdrawalMethodCatalog = is_array($withdrawalMethodCatalog ?? null) ? $withdrawalMethodCatalog : [
    'fedapay' => 'Mobile Money',
    'crypto' => 'Crypto',
];
$withdrawalMethodOptions = is_array($withdrawalMethodOptions ?? null) ? $withdrawalMethodOptions : $withdrawalMethodCatalog;
$hasWithdrawalMethods = $withdrawalMethodOptions !== [];
$withdrawalMethodsSummary = $hasWithdrawalMethods ? implode(' · ', array_values($withdrawalMethodOptions)) : 'Aucune méthode activée';
if ($withdrawMethodValue !== '' && !isset($withdrawalMethodCatalog[$withdrawMethodValue])) {
    $withdrawMethodValue = '';
}
if ($withdrawMethodValue === '' && $hasWithdrawalMethods) {
    $firstWithdrawalMethod = array_key_first($withdrawalMethodOptions);
    $withdrawMethodValue = is_string($firstWithdrawalMethod) ? $firstWithdrawalMethod : '';
}
$withdrawalDestinationDisplay = trim((string) ($withdrawalDestinationDisplay ?? ''));
$hasWithdrawalDestination = (bool) ($hasWithdrawalDestination ?? false);
$profileUrl = trim((string) ($profileUrl ?? $siteUrl('profile?space=teacher')));
$supportUrl = $siteUrl('support?space=teacher');
$withdrawalsPageMode = (string) ($withdrawalsPageMode ?? 'overview');
if (!in_array($withdrawalsPageMode, ['overview', 'revenues', 'transactions', 'requests'], true)) {
    $withdrawalsPageMode = 'overview';
}
$withdrawalsPageRoute = trim((string) ($withdrawalsPageRoute ?? 'teacher/withdrawals'));
$withdrawalsPageTitles = [
    'overview' => 'Revenus & Retraits',
    'revenues' => 'Revenus par cours',
    'transactions' => 'Dernières transactions',
    'requests' => 'Demandes de retrait',
];
$pageTitle = (string) ($withdrawalsPageTitles[$withdrawalsPageMode] ?? 'Revenus & Retraits');
$isOverviewPage = $withdrawalsPageMode === 'overview';
$periodQueryParams = ['period' => $periodSelectValue];
if ($periodSelectValue === 'custom') {
    if ($periodFrom !== '') {
        $periodQueryParams['from'] = $periodFrom;
    }
    if ($periodTo !== '') {
        $periodQueryParams['to'] = $periodTo;
    }
}
$periodQueryString = http_build_query($periodQueryParams);
$periodQuerySuffix = $periodQueryString !== '' ? ('?' . $periodQueryString) : '';
$overviewPageUrl = $siteUrl('teacher/withdrawals') . $periodQuerySuffix;
$revenuesPageUrl = $siteUrl('teacher/withdrawals/revenus') . $periodQuerySuffix;
$transactionsPageUrl = $siteUrl('teacher/withdrawals/transactions') . $periodQuerySuffix;
$requestsPageUrl = $siteUrl('teacher/withdrawals/retraits') . $periodQuerySuffix;
$currentRouteUrl = $siteUrl($withdrawalsPageRoute);
$currentPageUrl = $siteUrl($withdrawalsPageRoute) . $periodQuerySuffix;

$courseRevenueRows = array_values(is_array($courseRevenueRows ?? null) ? $courseRevenueRows : []);
$recentTransactionsRows = array_values(is_array($recentTransactionsRows ?? null) ? $recentTransactionsRows : []);
$withdrawals = array_values(is_array($withdrawals ?? null) ? $withdrawals : []);
$withdrawalsDetailPage = max(1, (int) ($withdrawalsDetailPage ?? ($_GET['page'] ?? 1)));
$detailPageSize = 10;
$paginateRows = static function (array $rows, int $page, int $pageSize): array {
    $total = count($rows);
    $lastPage = max(1, (int) ceil($total / max(1, $pageSize)));
    $currentPage = min(max(1, $page), $lastPage);
    $offset = ($currentPage - 1) * $pageSize;

    return [
        'rows' => array_slice($rows, $offset, $pageSize),
        'total' => $total,
        'page' => $currentPage,
        'lastPage' => $lastPage,
        'from' => $total > 0 ? ($offset + 1) : 0,
        'to' => min($total, $offset + $pageSize),
    ];
};
$courseRevenuePagination = $paginateRows($courseRevenueRows, $withdrawalsDetailPage, $detailPageSize);
$transactionsPagination = $paginateRows($recentTransactionsRows, $withdrawalsDetailPage, $detailPageSize);
$requestsPagination = $paginateRows($withdrawals, $withdrawalsDetailPage, $detailPageSize);
$courseRevenueRows = array_values($courseRevenuePagination['rows']);
$recentTransactionsRows = array_values($transactionsPagination['rows']);
$withdrawals = array_values($requestsPagination['rows']);
$detailPageUrl = static function (string $route, int $page) use ($siteUrl, $periodQueryParams): string {
    $params = $periodQueryParams;
    if ($page > 1) {
        $params['page'] = $page;
    }
    $query = http_build_query($params);
    return $siteUrl($route) . ($query !== '' ? ('?' . $query) : '');
};
$renderPagination = static function (array $pagination, string $route) use ($detailPageUrl): void {
    $page = max(1, (int) ($pagination['page'] ?? 1));
    $lastPage = max(1, (int) ($pagination['lastPage'] ?? 1));
    $total = max(0, (int) ($pagination['total'] ?? 0));
    if ($lastPage <= 1) {
        return;
    }

    $from = max(0, (int) ($pagination['from'] ?? 0));
    $to = max(0, (int) ($pagination['to'] ?? 0));
    $previousUrl = $detailPageUrl($route, max(1, $page - 1));
    $nextUrl = $detailPageUrl($route, min($lastPage, $page + 1));
    ?>
    <nav class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800/70 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
      <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400"><?php echo e((string) $from); ?>-<?php echo e((string) $to); ?> sur <?php echo e((string) $total); ?></p>
      <div class="flex items-center gap-2">
        <?php if ($page > 1): ?>
          <a href="<?php echo e($previousUrl); ?>" class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-400 dark:hover:bg-gray-700">
            <i class="fas fa-arrow-left"></i>
            Précédent
          </a>
        <?php else: ?>
          <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
            <i class="fas fa-arrow-left"></i>
            Précédent
          </span>
        <?php endif; ?>
        <span class="rounded-full bg-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-200"><?php echo e((string) $page); ?> / <?php echo e((string) $lastPage); ?></span>
        <?php if ($page < $lastPage): ?>
          <a href="<?php echo e($nextUrl); ?>" class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-400 dark:hover:bg-gray-700">
            Suivant
            <i class="fas fa-arrow-right"></i>
          </a>
        <?php else: ?>
          <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
            Suivant
            <i class="fas fa-arrow-right"></i>
          </span>
        <?php endif; ?>
      </div>
    </nav>
    <?php
};

$courseCount = max(0, (int) ($activeCourseCount ?? ($courseCount ?? 0)));
$unreadCount = max(0, (int) ($unreadCount ?? 0));

$frMonths = [
    1 => 'janvier',
    2 => 'février',
    3 => 'mars',
    4 => 'avril',
    5 => 'mai',
    6 => 'juin',
    7 => 'juillet',
    8 => 'août',
    9 => 'septembre',
    10 => 'octobre',
    11 => 'novembre',
    12 => 'décembre',
];

$formatMoney = static function (int|float $value): string {
    return number_format((float) $value, 0, ',', ' ') . ' F CFA';
};

$formatDate = static function (string $value) use ($frMonths): string {
    $raw = trim($value);
    if ($raw === '') {
        return '—';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return '—';
    }

    $day = date('j', $ts);
    $monthIndex = (int) date('n', $ts);
    $year = date('Y', $ts);
    $month = (string) ($frMonths[$monthIndex] ?? date('m', $ts));
    return $day . ' ' . $month . ' ' . $year;
};

$formatDateTime = static function (string $value) use ($frMonths): string {
    $raw = trim($value);
    if ($raw === '') {
        return '—';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return '—';
    }

    $day = date('j', $ts);
    $monthIndex = (int) date('n', $ts);
    $year = date('Y', $ts);
    $hours = date('H:i', $ts);
    $month = (string) ($frMonths[$monthIndex] ?? date('m', $ts));
    return $day . ' ' . $month . ' ' . $year . ' ' . $hours;
};

$courseRevenueLabel = static function (string $title): string {
    $title = trim($title);
    return $title !== '' ? $title : 'Cours sans titre';
};

$buyerLabel = static function (array $row): string {
    $first = trim((string) ($row['buyer_first_name'] ?? ''));
    $last = trim((string) ($row['buyer_last_name'] ?? ''));
    $email = trim((string) ($row['buyer_email'] ?? ''));
    $name = trim($first . ' ' . $last);
    if ($name !== '') {
        return $name;
    }
    if ($email !== '') {
        $prefix = trim((string) explode('@', $email, 2)[0]);
        $prefix = str_replace(['.', '_', '-'], ' ', $prefix);
        return trim($prefix) !== '' ? ucwords(trim($prefix)) : $email;
    }
    return 'Client';
};

$paymentProviderLabel = static function (string $provider): string {
    return match (strtolower(trim($provider))) {
        'fedapay' => 'FedaPay',
        'crypto' => 'Crypto',
        default => 'Paiement',
    };
};

$courseStatusMeta = static function (string $status): array {
    return match (strtolower(trim($status))) {
        'approved' => ['label' => 'Publié', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'],
        'submitted' => ['label' => 'Soumis', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
        'draft' => ['label' => 'Brouillon', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'],
        'rejected' => ['label' => 'Rejeté', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'],
        default => ['label' => 'Publié', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'],
    };
};

$resolveMediaUrl = static function (string $path): string {
    $value = trim($path);
    if ($value === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $value) === 1) {
        return $value;
    }
    return site_url(ltrim($value, '/'));
};

$withdrawalMethodLabel = static function (string $method) use ($withdrawalMethodCatalog): string {
    $method = strtolower(trim($method));
    return (string) ($withdrawalMethodCatalog[$method] ?? 'Mobile Money');
};

$originMeta = static function (string $origin): array {
    return match (strtolower(trim($origin))) {
        'direct' => ['label' => 'Direct', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'],
        'promotion' => ['label' => 'Promotion', 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300'],
        'coupon' => ['label' => 'Coupon', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
        'entreprise' => ['label' => 'Entreprise', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'],
        default => ['label' => 'Direct', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'],
    };
};

$withdrawalStatusMeta = static function (string $status): array {
    return match (strtolower(trim($status))) {
        'requested' => ['label' => 'En attente', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
        'approved' => ['label' => 'Confirmé', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'],
        'paid' => ['label' => 'Payé', 'class' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300'],
        'rejected' => ['label' => 'Annulé', 'class' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300'],
        default => ['label' => 'En attente', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'],
    };
};

$withdrawalTypeMeta = static function (string $withdrawalType): array {
    return strtolower(trim($withdrawalType)) === 'referral'
        ? ['label' => 'Parrainage', 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300']
        : ['label' => 'Ventes de cours', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'];
};

$sidebarLinks = [
    ['label' => 'Tableau de bord', 'url' => $siteUrl('teacher/dashboard'), 'icon' => 'home', 'active' => false, 'badge' => ''],
    ['label' => 'Mes formations', 'url' => $siteUrl('teacher/courses'), 'icon' => 'book', 'active' => false, 'badge' => $courseCount > 0 ? (string) $courseCount : ''],
    ['label' => 'Créer un cours', 'url' => $siteUrl('teacher/course_create'), 'icon' => 'plus-circle', 'active' => false, 'badge' => ''],
    ['label' => 'Revenus', 'url' => $siteUrl('teacher/withdrawals'), 'icon' => 'hand-holding-usd', 'active' => true, 'badge' => ''],
    ['label' => 'Promotions et coupons', 'url' => $siteUrl('teacher/promotions'), 'icon' => 'ticket', 'active' => false, 'badge' => ''],
    ['label' => 'Paramètres', 'url' => $profileUrl, 'icon' => 'cog', 'active' => false, 'badge' => ''],
];

$periodQueryValue = [
    '7d' => '7d',
    '30d' => '30d',
    '90d' => '90d',
    '365d' => '365d',
    'custom' => 'custom',
][$periodSelectValue] ?? '30d';

$withdrawModalShouldOpen = $errors !== [];
$salesCanRequestWithdrawal = $salesAvailable > 0 && ($salesMinWithdrawal <= 0 || $salesAvailable >= $salesMinWithdrawal);
$referralCanRequestWithdrawal = $referralWithdrawAvailable > 0 && ($referralMinWithdrawal <= 0 || $referralWithdrawAvailable >= $referralMinWithdrawal);
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/withdrawals">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title><?php echo e($pageTitle); ?> · Enseignant · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_withdrawals.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_withdrawals.js') ?: time()))); ?>" defer></script>
  <style>
    html[data-view="teacher/withdrawals"] {
      font-size: 14px;
    }
  </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/withdrawals" class="app-role-teacher teacher-withdrawals-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">
<div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
  <?php $teacherNavActive = 'withdrawals'; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
    <?php $teacherTopTitle = $pageTitle; ?>
    <?php $teacherTopSubtitle = 'Espace formateur'; ?>
    <?php $showCatalogue = false; ?>
    <?php require __DIR__ . '/_topbar.php'; ?>
    <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-8">
      <?php if ($toastKind !== ''): ?>
        <div
          data-withdrawal-toast="1"
          role="<?php echo $toastKind === 'error' ? 'alert' : 'status'; ?>"
          aria-live="<?php echo $toastKind === 'error' ? 'assertive' : 'polite'; ?>"
          class="teacher-withdrawal-toast teacher-withdrawal-toast--<?php echo e($toastKind); ?>"
        >
          <div class="teacher-withdrawal-toast__copy">
            <span class="teacher-withdrawal-toast__icon" aria-hidden="true">
              <i class="<?php echo $toastKind === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'; ?>"></i>
            </span>
            <div class="min-w-0">
              <p class="teacher-withdrawal-toast__title"><?php echo e($toastTitle); ?></p>
              <?php if (count($toastMessages) === 1): ?>
                <p class="teacher-withdrawal-toast__message"><?php echo e((string) $toastMessages[0]); ?></p>
              <?php else: ?>
                <ul class="teacher-withdrawal-toast__list">
                  <?php foreach ($toastMessages as $toastMessage): ?>
                    <li><?php echo e((string) $toastMessage); ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
          <span class="teacher-withdrawal-toast__bar" aria-hidden="true"></span>
        </div>
      <?php endif; ?>

      <!-- sticky top-2 removed: this filter now scrolls with the page -->
      <section class="rounded-[1.35rem] border border-gray-100 bg-white p-3 shadow-soft dark:border-gray-700 dark:bg-gray-800">
        <form method="get" action="<?php echo e($currentRouteUrl); ?>" class="grid gap-3" data-period-form data-period-custom="<?php echo $periodSelectValue === 'custom' ? 'true' : 'false'; ?>">
          <input type="hidden" name="page" value="1">
          <input type="hidden" name="period" value="<?php echo e($periodSelectValue); ?>" data-period-value>

          <div class="flex items-center justify-between gap-3">
            <div>
              <label class="text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Période</label>
            </div>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[0.7rem] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?php echo e($periodLabel); ?></span>
          </div>

          <div class="teacher-period-tabs flex flex-wrap gap-1.5">
            <button type="button" data-period-preset="all" class="<?php echo e($periodButtonClass($periodSelectValue === 'all')); ?>"><?php echo e($periodShortcutLabels['all']); ?></button>
            <button type="button" data-period-preset="7d" class="<?php echo e($periodButtonClass($periodSelectValue === '7d')); ?>"><?php echo e($periodShortcutLabels['7d']); ?></button>
            <button type="button" data-period-preset="30d" class="<?php echo e($periodButtonClass($periodSelectValue === '30d')); ?>"><?php echo e($periodShortcutLabels['30d']); ?></button>
            <button type="button" data-period-preset="90d" class="<?php echo e($periodButtonClass($periodSelectValue === '90d')); ?>"><?php echo e($periodShortcutLabels['90d']); ?></button>
            <button type="button" data-period-preset="365d" class="<?php echo e($periodButtonClass($periodSelectValue === '365d')); ?>"><?php echo e($periodShortcutLabels['365d']); ?></button>
            <button type="button" value="custom" data-period-preset="custom" class="<?php echo e($periodButtonClass($periodSelectValue === 'custom', true)); ?>">
              <i class="fas fa-sliders-h text-xs"></i>
              <?php echo e($periodDisplayLabels['custom']); ?>
            </button>
          </div>

          <div class="period-custom-fields <?php echo $periodSelectValue === 'custom' ? 'grid' : 'hidden'; ?> gap-2.5 sm:grid-cols-[1fr_1fr_auto] sm:items-end" data-period-custom-fields aria-hidden="<?php echo $periodSelectValue === 'custom' ? 'false' : 'true'; ?>">
            <div class="grid gap-1">
              <label class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Du</label>
              <input type="date" name="from" value="<?php echo e($periodFrom); ?>" class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
            </div>
            <div class="grid gap-1">
              <label class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Au</label>
              <input type="date" name="to" value="<?php echo e($periodTo); ?>" class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
            </div>
            <button type="button" data-period-custom-apply class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
              Appliquer
            </button>
          </div>
        </form>
        <?php if ($periodWarning !== ''): ?>
          <p class="mt-2 text-xs text-amber-600 dark:text-amber-300"><?php echo e($periodWarning); ?></p>
        <?php endif; ?>
      </section>

      <?php if ($isOverviewPage): ?>
        <section class="space-y-4">
          <div class="flex flex-col gap-4 xl:flex-row xl:items-start">
            <div class="min-w-0 flex-1 space-y-4">
              <article class="rounded-[1.35rem] border border-gray-200 bg-white p-5 shadow-soft dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Solde ventes disponible</p>
                      <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-[0.72rem] font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-200"><?php echo e($periodLabel); ?></span>
                    </div>
                    <p class="mt-2 text-4xl font-extrabold tracking-tight text-primary-600 dark:text-primary-300"><?php echo e($formatMoney($salesAvailable)); ?></p>
                  </div>
                  <button
                    type="button"
                    data-open-withdraw-modal
                    data-withdrawal-clear="1"
                    data-withdrawal-type="teacher_sales"
                    data-withdrawal-kicker="Retrait ventes"
                    data-withdrawal-title="Demander un retrait sur vos ventes"
                    data-withdrawal-min="<?php echo e((string) $salesMinWithdrawal); ?>"
                    data-withdrawal-available="<?php echo e((string) $salesAvailable); ?>"
                    data-withdrawal-min-label="<?php echo e($salesMinWithdrawal > 0 ? $formatMoney($salesMinWithdrawal) : 'Aucun minimum'); ?>"
                    data-withdrawal-available-label="<?php echo e($formatMoney($salesAvailable)); ?>"
                    <?php echo ($hasWithdrawalMethods && $salesCanRequestWithdrawal) ? '' : 'disabled'; ?>
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white transition lg:w-auto <?php echo ($hasWithdrawalMethods && $salesCanRequestWithdrawal) ? 'bg-primary-600 shadow-[0_16px_28px_-20px_rgba(37,99,235,0.82)] hover:bg-primary-700' : 'cursor-not-allowed bg-gray-300 shadow-none dark:bg-gray-700'; ?>"
                  >
                    <i class="fas fa-wallet"></i>
                    Retirer mes ventes
                  </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                  <div class="rounded-2xl bg-emerald-50 px-4 py-3 ring-1 ring-emerald-100 dark:bg-emerald-900/20 dark:ring-emerald-900/40">
                    <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Net période</p>
                    <p class="mt-1 text-lg font-extrabold text-emerald-700 dark:text-emerald-200"><?php echo e($formatMoney($periodTeacherNet)); ?></p>
                  </div>
                  <div class="rounded-2xl bg-rose-50 px-4 py-3 ring-1 ring-rose-100 dark:bg-rose-900/20 dark:ring-rose-900/40">
                    <p class="text-xs font-semibold text-rose-700 dark:text-rose-300">Commission plateforme</p>
                    <p class="mt-1 text-lg font-extrabold text-rose-700 dark:text-rose-200"><?php echo $periodPlatformTotal > 0 ? '-' : ''; ?><?php echo e($formatMoney($periodPlatformTotal)); ?></p>
                  </div>
                  <div class="rounded-2xl bg-amber-50 px-4 py-3 ring-1 ring-amber-100 dark:bg-amber-900/20 dark:ring-amber-900/40">
                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-300">Coût de parrainage</p>
                    <p class="mt-1 text-lg font-extrabold text-amber-700 dark:text-amber-200"><?php echo $periodReferralTotal > 0 ? '-' : ''; ?><?php echo e($formatMoney($periodReferralTotal)); ?></p>
                  </div>
                </div>
              </article>

              <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-soft dark:border-gray-700 dark:bg-gray-800">
                  <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Revenus bruts</dt>
                  <dd class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white"><?php echo e($formatMoney($periodGrossTotal)); ?></dd>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-soft dark:border-gray-700 dark:bg-gray-800">
                  <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Commission plateforme</dt>
                  <dd class="mt-2 text-2xl font-extrabold text-rose-600 dark:text-rose-300"><?php echo e($formatMoney($periodPlatformTotal)); ?></dd>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-soft dark:border-gray-700 dark:bg-gray-800">
                  <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Coût de parrainage</dt>
                  <dd class="mt-2 text-2xl font-extrabold text-amber-600 dark:text-amber-300"><?php echo e($formatMoney($periodReferralTotal)); ?></dd>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-soft dark:border-gray-700 dark:bg-gray-800">
                  <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Total retiré ventes</dt>
                  <dd class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white"><?php echo e($formatMoney($alreadyPaid)); ?></dd>
                </div>
              </dl>

              <section class="space-y-4">
                <div class="flex items-end justify-between gap-4">
                  <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white sm:text-lg">Pages détaillées</h2>
                  </div>
                </div>
                <div class="grid gap-3 lg:grid-cols-3">
                  <a href="<?php echo e($revenuesPageUrl); ?>" class="flex h-full flex-col justify-between gap-4 rounded-[1.25rem] border border-gray-200 bg-white p-4 text-left transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-400 dark:hover:bg-gray-700">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Revenus par cours</h3>
                      </div>
                      <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                        <i class="fas fa-chart-pie"></i>
                      </span>
                    </div>
                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 dark:text-primary-300"><i class="fas fa-arrow-right"></i> Ouvrir la page</span>
                  </a>
                  <a href="<?php echo e($transactionsPageUrl); ?>" class="flex h-full flex-col justify-between gap-4 rounded-[1.25rem] border border-gray-200 bg-white p-4 text-left transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-400 dark:hover:bg-gray-700">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Dernières transactions</h3>
                      </div>
                      <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                        <i class="fas fa-receipt"></i>
                      </span>
                    </div>
                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 dark:text-primary-300"><i class="fas fa-arrow-right"></i> Ouvrir la page</span>
                  </a>
                  <a href="<?php echo e($requestsPageUrl); ?>" class="flex h-full flex-col justify-between gap-4 rounded-[1.25rem] border border-gray-200 bg-white p-4 text-left transition hover:border-primary-300 hover:bg-primary-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-400 dark:hover:bg-gray-700">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Demandes de retrait</h3>
                      </div>
                      <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                        <i class="fas fa-hand-holding-usd"></i>
                      </span>
                    </div>
                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 dark:text-primary-300"><i class="fas fa-arrow-right"></i> Ouvrir la page</span>
                  </a>
                </div>
              </section>
            </div>

            <aside class="space-y-4 xl:w-[22rem] xl:flex-none">
              <section class="rounded-[1.35rem] border border-gray-200 bg-white p-5 shadow-soft dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Retrait ventes</h2>
                  </div>
                  <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                    <i class="fas fa-wallet"></i>
                  </span>
                </div>
                <p class="mt-4 text-3xl font-extrabold tracking-tight text-primary-600 dark:text-primary-300"><?php echo e($formatMoney($salesAvailable)); ?></p>
                <dl class="mt-4 space-y-3">
                  <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Minimum</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?php echo e($salesMinWithdrawal > 0 ? $formatMoney($salesMinWithdrawal) : 'Aucun minimum'); ?></dd>
                  </div>
                  <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Demandes en attente</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($pendingWithdrawals)); ?></dd>
                  </div>
                  <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Déjà retiré</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($alreadyPaid)); ?></dd>
                  </div>
                </dl>
                <button
                  type="button"
                  data-open-withdraw-modal
                  data-withdrawal-clear="1"
                  data-withdrawal-type="teacher_sales"
                  data-withdrawal-kicker="Retrait ventes"
                  data-withdrawal-title="Demander un retrait sur vos ventes"
                  data-withdrawal-min="<?php echo e((string) $salesMinWithdrawal); ?>"
                  data-withdrawal-available="<?php echo e((string) $salesAvailable); ?>"
                  data-withdrawal-min-label="<?php echo e($salesMinWithdrawal > 0 ? $formatMoney($salesMinWithdrawal) : 'Aucun minimum'); ?>"
                  data-withdrawal-available-label="<?php echo e($formatMoney($salesAvailable)); ?>"
                  <?php echo ($hasWithdrawalMethods && $salesCanRequestWithdrawal) ? '' : 'disabled'; ?>
                  class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white transition <?php echo ($hasWithdrawalMethods && $salesCanRequestWithdrawal) ? 'bg-primary-600 shadow-[0_16px_28px_-20px_rgba(37,99,235,0.82)] hover:bg-primary-700' : 'cursor-not-allowed bg-gray-300 shadow-none dark:bg-gray-700'; ?>"
                >
                  <i class="fas fa-wallet"></i>
                  Retirer mes ventes
                </button>
              </section>

              <section class="rounded-[1.35rem] border border-violet-200 bg-violet-50/60 p-5 shadow-soft dark:border-violet-500/20 dark:bg-violet-900/10">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Gains de parrainage</h2>
                  </div>
                  <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                    <i class="fas fa-gift"></i>
                  </span>
                </div>
                <p class="mt-4 text-3xl font-extrabold tracking-tight text-violet-700 dark:text-violet-300"><?php echo e($formatMoney($referralWithdrawAvailable)); ?></p>
                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-violet-600 dark:text-violet-300">Disponible au retrait</p>
                <dl class="mt-4 space-y-3">
                  <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Total gagné</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($referralEarnings)); ?></dd>
                  </div>
                  <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Minimum</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?php echo e($referralMinWithdrawal > 0 ? $formatMoney($referralMinWithdrawal) : 'Aucun minimum'); ?></dd>
                  </div>
                  <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Demandes en attente</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($pendingReferralWithdrawals)); ?></dd>
                  </div>
                  <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Retraits/payés</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($paidReferralWithdrawals)); ?></dd>
                  </div>
                  <div class="flex items-center justify-between gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Montants bloqués</dt>
                    <dd class="font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($referralWalletLocked)); ?></dd>
                  </div>
                </dl>
                <button
                  type="button"
                  data-open-withdraw-modal
                  data-withdrawal-clear="1"
                  data-withdrawal-type="referral"
                  data-withdrawal-kicker="Retrait parrainage"
                  data-withdrawal-title="Demander un retrait sur vos gains de parrainage"
                  data-withdrawal-min="<?php echo e((string) $referralMinWithdrawal); ?>"
                  data-withdrawal-available="<?php echo e((string) $referralWithdrawAvailable); ?>"
                  data-withdrawal-min-label="<?php echo e($referralMinWithdrawal > 0 ? $formatMoney($referralMinWithdrawal) : 'Aucun minimum'); ?>"
                  data-withdrawal-available-label="<?php echo e($formatMoney($referralWithdrawAvailable)); ?>"
                  <?php echo ($hasWithdrawalMethods && $referralCanRequestWithdrawal) ? '' : 'disabled'; ?>
                  class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white transition <?php echo ($hasWithdrawalMethods && $referralCanRequestWithdrawal) ? 'bg-violet-600 shadow-[0_16px_28px_-20px_rgba(109,40,217,0.75)] hover:bg-violet-700' : 'cursor-not-allowed bg-gray-300 shadow-none dark:bg-gray-700'; ?>"
                >
                  <i class="fas fa-gift"></i>
                  Retirer mes gains de parrainage
                </button>
              </section>

              <section class="rounded-[1.35rem] border border-gray-200 bg-white p-5 shadow-soft dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Configuration retrait</h2>
                <dl class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                  <div class="flex items-center justify-between gap-4 py-3 first:pt-0">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Délai</dt>
                    <dd class="text-right font-bold text-gray-900 dark:text-white">Validation administrative</dd>
                  </div>
                  <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Méthodes</dt>
                    <dd class="text-right font-bold <?php echo $hasWithdrawalMethods ? 'text-gray-900 dark:text-white' : 'text-rose-600 dark:text-rose-300'; ?>"><?php echo e($withdrawalMethodsSummary); ?></dd>
                  </div>
                  <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-sm font-semibold text-gray-500 dark:text-gray-400">Destination</dt>
                    <dd class="text-right font-bold <?php echo $hasWithdrawalDestination ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300'; ?>"><?php echo e($hasWithdrawalDestination ? 'Vérifiée' : 'À configurer'); ?></dd>
                  </div>
                </dl>
                <div class="mt-4 grid gap-2">
                  <a href="<?php echo e($profileUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:bg-gray-700">
                    <i class="fas fa-credit-card"></i>
                    Méthode de paiement
                  </a>
                  <a href="<?php echo e($supportUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:bg-gray-700">
                    <i class="fas fa-headset"></i>
                    Support paiement
                  </a>
                </div>
              </section>
            </aside>
          </div>
        </section>
      <?php else: ?>
        <section class="flex flex-col gap-4 rounded-[1.35rem] border border-gray-100 bg-white p-5 shadow-soft dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-start sm:justify-between">
          <div class="max-w-3xl">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white sm:text-xl"><?php echo e($pageTitle); ?></h2>
          </div>
          <a href="<?php echo e($overviewPageUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:bg-gray-700">
            <i class="fas fa-arrow-left"></i>
            Retour au résumé
          </a>
        </section>
      <?php endif; ?>

      <?php if ($withdrawalsPageMode === 'revenues'): ?>
      <section class="space-y-4">
        <div class="overflow-hidden rounded-[1.35rem] border border-gray-100 bg-white shadow-soft dark:border-gray-700 dark:bg-gray-800">
          <div class="overflow-x-auto">
            <table class="min-w-[68rem] w-full table-fixed text-[0.88rem]">
              <colgroup>
                <col class="w-[32%]">
                <col class="w-[16%]">
                <col class="w-[16%]">
                <col class="w-[13%]">
                <col class="w-[13%]">
                <col class="w-[10%]">
              </colgroup>
              <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                <tr>
                  <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-[0.18em]">Cours</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Revenus totaux</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Ventes directes</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Promotions</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Coupons</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Compte entreprise</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if ($courseRevenueRows !== []): ?>
                  <?php foreach ($courseRevenueRows as $row): ?>
                    <?php
                      $courseTitle = $courseRevenueLabel((string) ($row['title'] ?? ''));
                      $courseStatus = $courseStatusMeta((string) ($row['status'] ?? ''));
                      $courseId = (int) ($row['course_id'] ?? 0);
                      $lessonCount = max(0, (int) ($row['lessons_count'] ?? 0));
                      $coverUrl = $resolveMediaUrl((string) ($row['cover_path'] ?? ''));
                      $totalXof = (int) ($row['total_xof'] ?? 0);
                      $directXof = (int) ($row['direct_xof'] ?? 0);
                      $promoXof = (int) ($row['promo_xof'] ?? 0);
                      $couponXof = (int) ($row['coupon_xof'] ?? 0);
                      $enterpriseXof = (int) ($row['enterprise_xof'] ?? 0);
                    ?>
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/25">
                      <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                          <div class="flex h-11 w-11 flex-none items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-700">
                            <?php if ($coverUrl !== ''): ?>
                              <img src="<?php echo e($coverUrl); ?>" alt="" class="h-full w-full object-cover">
                            <?php else: ?>
                              <i class="fas fa-graduation-cap"></i>
                            <?php endif; ?>
                          </div>
                          <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                              <p class="truncate font-medium text-gray-900 dark:text-white"><?php echo e($courseTitle); ?></p>
                              <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-gray-600 dark:bg-gray-700 dark:text-gray-300">#<?php echo e(str_pad((string) max(1, $courseId), 3, '0', STR_PAD_LEFT)); ?></span>
                              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[0.68rem] font-semibold <?php echo e((string) $courseStatus['class']); ?>"><?php echo e((string) $courseStatus['label']); ?></span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e(number_format($lessonCount, 0, ',', ' ')); ?> leçon(s) publiées</p>
                          </div>
                        </div>
                      </td>
                      <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white"><?php echo e($formatMoney($totalXof)); ?></td>
                      <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($formatMoney($directXof)); ?></td>
                      <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($formatMoney($promoXof)); ?></td>
                      <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($formatMoney($couponXof)); ?></td>
                      <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200"><?php echo e($formatMoney($enterpriseXof)); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Aucun revenu publié sur cette période.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php $renderPagination($courseRevenuePagination, 'teacher/withdrawals/revenus'); ?>
      </section>
      <?php endif; ?>

      <?php if ($withdrawalsPageMode === 'transactions'): ?>
      <section class="space-y-4">
        <div class="overflow-hidden rounded-[1.35rem] border border-gray-100 bg-white shadow-soft dark:border-gray-700 dark:bg-gray-800">
          <div class="overflow-x-auto">
            <table class="min-w-[60rem] w-full table-fixed text-[0.88rem]">
              <colgroup>
                <col class="w-[18%]">
                <col class="w-[34%]">
                <col class="w-[12%]">
                <col class="w-[18%]">
                <col class="w-[18%]">
              </colgroup>
              <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                <tr>
                  <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-[0.18em]">Étudiant</th>
                  <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-[0.18em]">Cours</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Montant</th>
                  <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-[0.18em]">Origine</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if ($recentTransactionsRows !== []): ?>
                  <?php foreach ($recentTransactionsRows as $row): ?>
                    <?php
                      $studentLabel = $buyerLabel($row);
                      $courseTitle = $courseRevenueLabel((string) ($row['course_title'] ?? ''));
                      $orderReference = trim((string) ($row['order_reference'] ?? ''));
                      $paymentProvider = $paymentProviderLabel((string) ($row['payment_provider'] ?? ''));
                      $amountXof = (int) ($row['amount_xof'] ?? 0);
                      $origin = $originMeta((string) ($row['origin_label'] ?? 'Direct'));
                      $paidAt = $formatDateTime((string) ($row['paid_at'] ?? ''));
                    ?>
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/25">
                      <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?php echo e($studentLabel); ?></td>
                      <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                        <div class="min-w-0">
                          <p class="font-medium text-gray-900 dark:text-white"><?php echo e($courseTitle); ?></p>
                          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <?php if ($orderReference !== ''): ?>
                              Réf. <span class="font-mono"><?php echo e($orderReference); ?></span>
                            <?php else: ?>
                              Référence indisponible
                            <?php endif; ?>
                            · Paiement : <?php echo e($paymentProvider); ?>
                          </p>
                        </div>
                      </td>
                      <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white"><?php echo e($formatMoney($amountXof)); ?></td>
                      <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?php echo e((string) $origin['class']); ?>">
                          <?php echo e((string) $origin['label']); ?>
                        </span>
                      </td>
                      <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400"><?php echo e($paidAt); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Aucune transaction sur cette période.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php $renderPagination($transactionsPagination, 'teacher/withdrawals/transactions'); ?>
      </section>
      <?php endif; ?>

      <?php if ($withdrawalsPageMode === 'requests'): ?>
      <section class="space-y-4">
        <div class="overflow-hidden rounded-[1.35rem] border border-gray-100 bg-white shadow-soft dark:border-gray-700 dark:bg-gray-800">
          <div class="overflow-x-auto">
            <table class="min-w-[68rem] w-full table-fixed text-[0.88rem]">
              <colgroup>
                <col class="w-[16%]">
                <col class="w-[14%]">
                <col class="w-[18%]">
                <col class="w-[14%]">
                <col class="w-[14%]">
                <col class="w-[12%]">
                <col class="w-[12%]">
              </colgroup>
              <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                <tr>
                  <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-[0.18em]">Référence</th>
                  <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-[0.18em]">Source</th>
                  <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-[0.18em]">M&eacute;thode</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Montant</th>
                  <th class="px-4 py-2.5 text-left font-semibold uppercase tracking-[0.18em]">Statut</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Date</th>
                  <th class="px-4 py-2.5 text-right font-semibold uppercase tracking-[0.18em]">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if ($withdrawals !== []): ?>
                  <?php foreach ($withdrawals as $row): ?>
                    <?php
                      $withdrawalId = (int) ($row['id'] ?? 0);
                      $reference = trim((string) ($row['payout_reference'] ?? ''));
                      if ($reference === '') {
                          $reference = 'RET-' . str_pad((string) max(1, $withdrawalId), 3, '0', STR_PAD_LEFT);
                      }
                      $methodLabel = $withdrawalMethodLabel((string) ($row['method'] ?? 'fedapay'));
                      $amountXof = (int) ($row['amount_xof'] ?? 0);
                      $withdrawalType = (string) ($row['withdrawal_type'] ?? 'teacher_sales');
                      $withdrawalType = in_array($withdrawalType, ['teacher_sales', 'referral'], true) ? $withdrawalType : 'teacher_sales';
                      $withdrawalTypeBadge = $withdrawalTypeMeta($withdrawalType);
                      $statusValue = strtolower(trim((string) ($row['status'] ?? 'requested')));
                      $status = $withdrawalStatusMeta((string) ($row['status'] ?? 'requested'));
                      $dateLabel = $formatDate((string) ($row['created_at'] ?? ''));
                      $reviewedAtLabel = $formatDateTime((string) ($row['reviewed_at'] ?? ''));
                      $withdrawalReason = trim((string) ($row['reason'] ?? ''));
                      $withdrawalDestination = trim((string) ($row['destination'] ?? ''));
                      $isCancelable = $statusValue === 'requested';
                      $isRejected = $statusValue === 'rejected';
                      $isPaid = $statusValue === 'paid';
                      $isApproved = $statusValue === 'approved';
                    ?>
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/25">
                      <td class="px-4 py-3 font-mono text-[0.72rem] uppercase tracking-[0.14em] text-gray-700 dark:text-gray-300"><?php echo e($reference); ?></td>
                      <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?php echo e((string) $withdrawalTypeBadge['class']); ?>">
                          <?php echo e((string) $withdrawalTypeBadge['label']); ?>
                        </span>
                      </td>
                      <td class="px-4 py-3 text-gray-700 dark:text-gray-200"><?php echo e($methodLabel); ?></td>
                      <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white"><?php echo e($formatMoney($amountXof)); ?></td>
                      <td class="px-4 py-3">
                        <div class="space-y-1">
                          <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?php echo e((string) $status['class']); ?>">
                            <?php echo e((string) $status['label']); ?>
                          </span>
                        </div>
                      </td>
                      <td class="px-4 py-3 text-right text-sm text-gray-500 dark:text-gray-400"><?php echo e($dateLabel); ?></td>
                      <td class="px-4 py-3 text-right">
                        <div class="flex flex-col items-end gap-2">
                          <details class="w-full max-w-[18rem] text-left">
                            <summary class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-700/60 dark:text-gray-300 dark:hover:border-primary-400 dark:hover:bg-gray-700">
                              <i class="fas fa-info-circle text-[0.72rem]"></i>
                              Voir détail
                            </summary>
                            <div class="mt-2 rounded-2xl border border-gray-200 bg-white p-3 text-left text-xs leading-5 text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                              <p><span class="font-semibold text-gray-900 dark:text-white">Source :</span> <?php echo e((string) $withdrawalTypeBadge['label']); ?></p>
                              <p><span class="font-semibold text-gray-900 dark:text-white">Destination :</span> <?php echo e($withdrawalDestination !== '' ? $withdrawalDestination : '—'); ?></p>
                              <p><span class="font-semibold text-gray-900 dark:text-white">Référence :</span> <?php echo e($reference !== '' ? $reference : '—'); ?></p>
                              <p><span class="font-semibold text-gray-900 dark:text-white">Dernière action :</span> <?php echo e($reviewedAtLabel !== '' ? $reviewedAtLabel : '—'); ?></p>
                              <p><span class="font-semibold text-gray-900 dark:text-white">Motif :</span> <?php echo e($withdrawalReason !== '' ? $withdrawalReason : '—'); ?></p>
                              <a href="<?php echo e($supportUrl); ?>" class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 transition hover:text-primary-700 dark:text-primary-300">
                                <i class="fas fa-headset text-[0.72rem]"></i>
                                Contacter le support
                              </a>
                            </div>
                          </details>

                          <?php if ($isCancelable): ?>
                            <form method="post" class="inline-flex" data-confirm-withdraw-cancel="1">
                              <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                              <input type="hidden" name="period" value="<?php echo e($periodSelectValue); ?>">
                              <input type="hidden" name="from" value="<?php echo e($periodFrom); ?>">
                              <input type="hidden" name="to" value="<?php echo e($periodTo); ?>">
                              <input type="hidden" name="action" value="cancel_withdrawal">
                              <input type="hidden" name="withdrawal_id" value="<?php echo e((string) $withdrawalId); ?>">
                              <input type="hidden" name="withdrawal_type" value="<?php echo e($withdrawalType); ?>">
                              <button type="submit" class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-semibold text-rose-500 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20">
                                <i class="fas fa-ban"></i>
                                Annuler
                              </button>
                            </form>
                          <?php elseif ($isRejected): ?>
                            <?php if ($hasWithdrawalMethods): ?>
                              <button
                                type="button"
                                data-open-withdraw-modal
                                data-withdrawal-clear="1"
                                data-withdrawal-amount="<?php echo e((string) $amountXof); ?>"
                                data-withdrawal-method="<?php echo e((string) ($row['method'] ?? 'fedapay')); ?>"
                                data-withdrawal-type="<?php echo e($withdrawalType); ?>"
                                data-withdrawal-kicker="<?php echo e($withdrawalType === 'referral' ? 'Retrait parrainage' : 'Retrait ventes'); ?>"
                                data-withdrawal-title="<?php echo e($withdrawalType === 'referral' ? 'Demander un retrait sur vos gains de parrainage' : 'Demander un retrait sur vos ventes'); ?>"
                                data-withdrawal-min="<?php echo e((string) ($withdrawalType === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal)); ?>"
                                data-withdrawal-available="<?php echo e((string) ($withdrawalType === 'referral' ? $referralWithdrawAvailable : $salesAvailable)); ?>"
                                data-withdrawal-min-label="<?php echo e(($withdrawalType === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal) > 0 ? $formatMoney($withdrawalType === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal) : 'Aucun minimum'); ?>"
                                data-withdrawal-available-label="<?php echo e($formatMoney($withdrawalType === 'referral' ? $referralWithdrawAvailable : $salesAvailable)); ?>"
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-semibold text-primary-600 transition hover:bg-primary-50 hover:text-primary-700 dark:hover:bg-primary-900/20 dark:text-primary-300"
                              >
                                <i class="fas fa-redo-alt"></i>
                                Réessayer
                              </button>
                            <?php else: ?>
                              <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">Indisponible</span>
                            <?php endif; ?>
                          <?php else: ?>
                            <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Aucune demande de retrait pour cette période.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php $renderPagination($requestsPagination, 'teacher/withdrawals/retraits'); ?>
      </section>
      <?php endif; ?>
    </div>
  </main>
</div>

<div
  id="withdraw-modal"
  data-withdraw-modal
  data-withdraw-modal-open="<?php echo $withdrawModalShouldOpen ? '1' : '0'; ?>"
  data-withdraw-default-type="<?php echo e($withdrawalTypeValue); ?>"
  data-withdraw-default-kicker="<?php echo e($withdrawalTypeValue === 'referral' ? 'Retrait parrainage' : 'Retrait ventes'); ?>"
  data-withdraw-default-title="<?php echo e($withdrawalTypeValue === 'referral' ? 'Demander un retrait sur vos gains de parrainage' : 'Demander un retrait sur vos ventes'); ?>"
  data-withdraw-default-min="<?php echo e((string) ($withdrawalTypeValue === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal)); ?>"
  data-withdraw-default-available="<?php echo e((string) ($withdrawalTypeValue === 'referral' ? $referralWithdrawAvailable : $salesAvailable)); ?>"
  data-withdraw-default-min-label="<?php echo e(($withdrawalTypeValue === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal) > 0 ? $formatMoney($withdrawalTypeValue === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal) : 'Aucun minimum'); ?>"
  data-withdraw-default-available-label="<?php echo e($formatMoney($withdrawalTypeValue === 'referral' ? $referralWithdrawAvailable : $salesAvailable)); ?>"
  class="<?php echo $withdrawModalShouldOpen ? 'flex' : 'hidden'; ?> fixed inset-0 z-50 items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
>
  <div class="w-full max-w-sm rounded-[1.5rem] bg-white p-5 shadow-2xl ring-1 ring-gray-100 dark:bg-gray-800 dark:ring-gray-700">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-gray-400" data-withdraw-modal-kicker><?php echo e($withdrawalTypeValue === 'referral' ? 'Retrait parrainage' : 'Retrait ventes'); ?></p>
        <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white" data-withdraw-modal-title><?php echo e($withdrawalTypeValue === 'referral' ? 'Demander un retrait sur vos gains de parrainage' : 'Demander un retrait sur vos ventes'); ?></h3>
      </div>
      <button type="button" data-close-withdraw-modal class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200" aria-label="Fermer la fenêtre">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <form method="post" action="<?php echo e($currentPageUrl); ?>" class="mt-5 space-y-5" data-withdraw-form>
      <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
      <input type="hidden" name="period" value="<?php echo e($periodSelectValue); ?>">
      <input type="hidden" name="from" value="<?php echo e($periodFrom); ?>">
      <input type="hidden" name="to" value="<?php echo e($periodTo); ?>">
      <input type="hidden" name="withdrawal_type" value="<?php echo e($withdrawalTypeValue); ?>">
      <div class="grid gap-1.5">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Montant (F CFA) <span class="text-rose-500">*</span></label>
        <input
          type="number"
          name="amount_xof"
          value="<?php echo e($withdrawAmountValue > 0 ? (string) $withdrawAmountValue : ''); ?>"
          placeholder="500"
          min="<?php echo e((string) max(1, $withdrawalTypeValue === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal)); ?>"
          max="<?php echo e((string) max($withdrawalTypeValue === 'referral' ? $referralWithdrawAvailable : $salesAvailable, $withdrawalTypeValue === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal)); ?>"
          required
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
        >
        <p class="text-xs text-gray-500 dark:text-gray-400" data-withdraw-modal-balance>
          Minimum <span data-withdraw-modal-min-label><?php echo e(($withdrawalTypeValue === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal) > 0 ? $formatMoney($withdrawalTypeValue === 'referral' ? $referralMinWithdrawal : $salesMinWithdrawal) : 'Aucun minimum'); ?></span> · Solde disponible : <span data-withdraw-modal-available-label><?php echo e($formatMoney($withdrawalTypeValue === 'referral' ? $referralWithdrawAvailable : $salesAvailable)); ?></span>
        </p>
      </div>

      <div class="grid gap-1.5">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">M&eacute;thode de retrait</label>
        <?php if ($hasWithdrawalMethods): ?>
          <select
            name="method"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
          >
            <?php foreach ($withdrawalMethodOptions as $methodValue => $methodLabel): ?>
              <option value="<?php echo e($methodValue); ?>" <?php echo $withdrawMethodValue === $methodValue ? 'selected' : ''; ?>><?php echo e($methodLabel); ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <p class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-300">
            Aucune méthode de retrait n'est activée pour le moment.
          </p>
        <?php endif; ?>
      </div>

      <?php if ($withdrawalDestinationDisplay !== ''): ?>
        <p class="rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-700/50 dark:text-gray-300">
          Destination vérifiée : <span class="font-semibold text-gray-900 dark:text-white"><?php echo e($withdrawalDestinationDisplay); ?></span>
        </p>
      <?php endif; ?>

      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" data-close-withdraw-modal class="rounded-xl border border-gray-300 px-4 py-2 font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Annuler</button>
        <button type="submit" <?php echo $hasWithdrawalMethods ? '' : 'disabled'; ?> class="inline-flex items-center gap-2 rounded-xl px-4 py-2 font-semibold text-white transition <?php echo $hasWithdrawalMethods ? 'bg-primary-600 shadow-sm hover:bg-primary-700' : 'cursor-not-allowed bg-gray-300 shadow-none dark:bg-gray-700'; ?>">
          <i class="fas fa-paper-plane"></i>
          Soumettre
        </button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
