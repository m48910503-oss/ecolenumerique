<?php
declare(strict_types=1);

$studentUser = is_array($user ?? null) ? $user : [];
$studentDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($studentUser, 'Utilisateur')
    : 'Utilisateur';
$studentEmail = trim((string) ($studentUser['email'] ?? ''));
$studentAvatarUrl = function_exists('dashboard_user_avatar_url')
    ? dashboard_user_avatar_url($studentUser)
    : '';
$studentInitials = function_exists('dashboard_user_initials')
    ? dashboard_user_initials($studentUser, 'ET')
    : 'ET';
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$ordersList = is_array($orders ?? null) ? array_values($orders) : [];
$ordersCount = max(0, (int) ($ordersCount ?? count($ordersList)));
$ordersPagination = is_array($pagination ?? null) ? $pagination : ['page' => 1, 'total_pages' => 1, 'total' => count($ordersList)];
$titles = is_array($titles ?? null) ? $titles : [];

$dashboardUrl = site_url('student/dashboard');
$myCoursesUrl = site_url('student/my_courses');
$calendarUrl = site_url('student/meetings');
$certificatesUrl = site_url('student/certificates');
$favoritesUrl = site_url('student/favorites');
$ordersUrl = site_url('student/orders');
$notificationsUrl = site_url('notifications?space=student');
$referralsUrl = site_url('student/referrals');
$supportUrl = site_url('support?space=student');
$profileUrl = site_url('profile?space=student');
$homeUrl = site_url('');
$logoutUrl = site_url('logout');
$logoutCsrf = function_exists('view_csrf_token') ? view_csrf_token() : '';
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$studentDashboardCssUrl = site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()));
$studentDashboardJsUrl = site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time()));
$sidebarToggleJsUrl = site_url('assets/js/sidebar_toggle.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/sidebar_toggle.js') ?: time()));
$studentOrdersJsUrl = site_url('assets/js/student_orders.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_orders.js') ?: time()));

$fmtDtShort = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $ts = strtotime($value);
    return $ts ? date('d/m/Y H:i', $ts) : $value;
};

$normalizeSearchText = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (function_exists('mb_strtolower')) {
        $decoded = mb_strtolower($decoded, 'UTF-8');
    } else {
        $decoded = strtolower($decoded);
    }

    if (function_exists('iconv')) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $decoded);
        if (is_string($ascii) && $ascii !== '') {
            $decoded = $ascii;
        }
    }

    $decoded = preg_replace('/[^a-z0-9]+/i', ' ', $decoded) ?? $decoded;
    return trim((string) $decoded);
};

$formatMoney = static function (int $amount, string $status = ''): string {
    $amount = max(0, $amount);
    if ($amount <= 0 && strtolower(trim($status)) === 'free') {
        return 'Gratuit';
    }

    return number_format($amount, 0, ',', ' ') . ' F CFA';
};

$providerLabelFor = static function (string $provider): string {
    $provider = strtolower(trim($provider));
    return match ($provider) {
        'fedapay' => 'FedaPay',
        'crypto' => 'Crypto',
        'free' => 'Gratuit',
        'referral' => 'Solde de parrainage',
        default => $provider !== '' ? strtoupper($provider) : 'Non renseigné',
    };
};

$statusMetaFor = static function (string $status): array {
    $status = strtolower(trim($status));
    return match ($status) {
        'paid', 'completed', 'succeeded', 'success' => ['label' => 'Validée', 'tone' => 'success'],
        'pending' => ['label' => 'En attente', 'tone' => 'warning'],
        'failed' => ['label' => 'Échouée', 'tone' => 'danger'],
        'cancelled', 'canceled' => ['label' => 'Annulée', 'tone' => 'danger'],
        'refunded' => ['label' => 'Remboursée', 'tone' => 'neutral'],
        'free' => ['label' => 'Gratuite', 'tone' => 'info'],
        default => [
            'label' => $status !== '' ? ucwords(str_replace(['_', '-'], ' ', $status)) : 'Inconnu',
            'tone' => 'neutral',
        ],
    };
};

$courseLabelsForOrder = static function (array $order) use ($titles): array {
    $rawIds = trim((string) ($order['course_ids'] ?? ''));
    if ($rawIds === '') {
        return [];
    }

    $courseIds = [];
    foreach (preg_split('/\s*,\s*/', $rawIds) ?: [] as $rawId) {
        $courseId = (int) $rawId;
        if ($courseId > 0) {
            $courseIds[] = $courseId;
        }
    }
    $courseIds = array_values(array_unique($courseIds));

    $labels = [];
    foreach ($courseIds as $courseId) {
        $labels[] = trim((string) ($titles[$courseId] ?? ('Cours #' . $courseId)));
    }

    return array_values(array_filter($labels, static fn (string $label): bool => $label !== ''));
};

$courseIdsForOrder = static function (array $order): array {
    $rawIds = trim((string) ($order['course_ids'] ?? ''));
    if ($rawIds === '') {
        return [];
    }

    $courseIds = [];
    foreach (preg_split('/\s*,\s*/', $rawIds) ?: [] as $rawId) {
        $courseId = (int) $rawId;
        if ($courseId > 0) {
            $courseIds[] = $courseId;
        }
    }

    return array_values(array_unique($courseIds));
};

$ordersSummary = is_array($ordersSummary ?? null) ? $ordersSummary : [];
$paidCount = 0;
$pendingCount = 0;
$failedCount = 0;
$canceledCount = 0;
$freeCount = 0;
$refundedCount = 0;
$totalSpentXof = 0;
$latestOrder = null;

if ($ordersSummary !== []) {
    $paidCount = max(0, (int) ($ordersSummary['paid_count'] ?? 0));
    $pendingCount = max(0, (int) ($ordersSummary['pending_count'] ?? 0));
    $failedCount = max(0, (int) ($ordersSummary['failed_count'] ?? 0));
    $canceledCount = max(0, (int) ($ordersSummary['canceled_count'] ?? 0));
    $freeCount = max(0, (int) ($ordersSummary['free_count'] ?? 0));
    $refundedCount = max(0, (int) ($ordersSummary['refunded_count'] ?? 0));
    $totalSpentXof = max(0, (int) ($ordersSummary['total_spent_xof'] ?? 0));
    $latestOrder = is_array($ordersSummary['latest_order'] ?? null) ? $ordersSummary['latest_order'] : null;
} else {
    foreach ($ordersList as $orderRow) {
        $statusRow = strtolower((string) ($orderRow['status'] ?? ''));
        $amountRow = max(0, (int) ($orderRow['total_xof'] ?? 0));
        if (in_array($statusRow, ['paid', 'completed', 'succeeded', 'success'], true)) {
            $paidCount++;
            $totalSpentXof += $amountRow;
        } elseif ($statusRow === 'pending') {
            $pendingCount++;
        } elseif ($statusRow === 'failed') {
            $failedCount++;
        } elseif ($statusRow === 'canceled' || $statusRow === 'cancelled') {
            $canceledCount++;
        } elseif ($statusRow === 'free') {
            $freeCount++;
            $totalSpentXof += $amountRow;
        } elseif ($statusRow === 'refunded') {
            $refundedCount++;
        }
    }

    $latestOrder = is_array($ordersList[0] ?? null) ? $ordersList[0] : null;
}

$validatedCount = $paidCount + $freeCount;
$failedOrCanceledCount = $failedCount + $canceledCount;
$latestOrderReference = trim((string) ($latestOrder['reference'] ?? ''));
$latestOrderCourseLabels = is_array($latestOrder) ? $courseLabelsForOrder($latestOrder) : [];
$latestOrderTitle = $latestOrderReference !== ''
    ? 'Commande ' . $latestOrderReference
    : 'Aucune commande';
if ($latestOrderCourseLabels !== []) {
    $latestOrderTitle = $latestOrderCourseLabels[0];
}
$latestOrderDate = is_array($latestOrder) ? $fmtDtShort((string) ($latestOrder['created_at'] ?? '')) : '';
$latestOrderStatusMeta = is_array($latestOrder)
    ? $statusMetaFor((string) ($latestOrder['status'] ?? ''))
    : ['label' => 'Aucune commande', 'tone' => 'neutral'];
$latestOrderMetaParts = [];
if ($latestOrderDate !== '') {
    $latestOrderMetaParts[] = $latestOrderDate;
}
if ($latestOrderReference !== '') {
    $latestOrderMetaParts[] = '#' . $latestOrderReference;
}
$latestOrderMeta = $latestOrderMetaParts !== [] ? implode(' · ', $latestOrderMetaParts) : 'Aucune commande enregistrée';

$paginationData = is_array($ordersPagination) ? $ordersPagination : [];
$paginationPage = max(1, (int) ($paginationData['page'] ?? 1));
$paginationTotal = max(0, (int) ($paginationData['total'] ?? $ordersCount));
$paginationTotalPages = max(1, (int) ($paginationData['total_pages'] ?? 1));
$paginationHasPrev = !empty($paginationData['has_prev']);
$paginationHasNext = !empty($paginationData['has_next']);
$paginationPrevPage = max(1, (int) ($paginationData['prev_page'] ?? max(1, $paginationPage - 1)));
$paginationNextPage = max(1, (int) ($paginationData['next_page'] ?? min($paginationTotalPages, $paginationPage + 1)));
$paginationStart = max(0, (int) ($paginationData['start'] ?? 0));
$paginationEnd = max(0, (int) ($paginationData['end'] ?? 0));
$paginationWindowStart = max(1, $paginationPage - 2);
$paginationWindowEnd = min($paginationTotalPages, $paginationPage + 2);
$buildPageUrl = static function (int $page) use ($ordersUrl): string {
    return $ordersUrl . '?page=' . max(1, $page);
};

$sidebarItems = [
    [
        'key' => 'dashboard',
        'label' => 'Tableau de bord',
        'url' => $dashboardUrl,
        'icon' => 'fa-home',
        'badge' => null,
    ],
    [
        'key' => 'my_courses',
        'label' => 'Mes cours',
        'url' => $myCoursesUrl,
        'icon' => 'fa-book-open',
        'badge' => null,
    ],
    [
        'key' => 'meetings',
        'label' => 'Mes rendez-vous',
        'url' => $calendarUrl,
        'icon' => 'fa-calendar-alt',
        'badge' => null,
    ],
    [
        'key' => 'certificates',
        'label' => 'Mes certificats',
        'url' => $certificatesUrl,
        'icon' => 'fa-award',
        'badge' => null,
    ],
    [
        'key' => 'favorites',
        'label' => 'Mes favoris',
        'url' => $favoritesUrl,
        'icon' => 'fa-heart',
        'badge' => null,
    ],
    [
        'key' => 'orders',
        'label' => 'Mes achats',
        'url' => $ordersUrl,
        'icon' => 'fa-receipt',
        'badge' => null,
    ],
    [
        'key' => 'referrals',
        'label' => 'Parrainage',
        'url' => $referralsUrl,
        'icon' => 'fa-users',
        'badge' => null,
    ],
    [
        'key' => 'notifications',
        'label' => 'Notifications',
        'url' => $notificationsUrl,
        'icon' => 'fa-bell',
        'badge' => $unreadCount,
    ],
    [
        'key' => 'support',
        'label' => 'Assistance',
        'url' => $supportUrl,
        'icon' => 'fa-headset',
        'badge' => $supportUnreadCount,
    ],
    [
        'key' => 'profile',
        'label' => 'Mon profil',
        'url' => $profileUrl,
        'icon' => 'fa-user',
        'badge' => null,
    ],
    [
        'key' => 'home',
        'label' => 'Retour au site',
        'url' => $homeUrl,
        'icon' => 'fa-sign-out-alt',
        'badge' => null,
    ],
];
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Mes achats · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e($studentDashboardCssUrl); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($sidebarToggleJsUrl); ?>" defer></script>
  <script src="<?php echo e($studentDashboardJsUrl); ?>" defer></script>
  <script src="<?php echo e($studentOrdersJsUrl); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="student/orders" data-sidebar-desktop-toggle="0" class="app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">

<div class="student-dashboard-shell relative min-h-screen overflow-hidden">
  <?php $studentNavActive = 'orders'; ?>
  <?php $studentDashboardShell = true; ?>
  <?php $studentTopTitle = 'Mes achats'; ?>
  <?php $studentTopSubtitle = 'Espace étudiant'; ?>
  <?php $studentTopShowSearch = true; ?>
  <?php $studentTopSearchUrl = $ordersUrl; ?>
  <?php $studentTopSearchPlaceholder = 'Rechercher une commande'; ?>
  <?php $studentTopSearchAriaLabel = 'Rechercher une commande'; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
    <main class="student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
      <?php require __DIR__ . '/_topbar.php'; ?>

      <div class="student-dashboard-content mx-auto max-w-6xl p-4 pb-24 space-y-5 animate-fade-up sm:p-6 sm:pb-24 sm:space-y-6 lg:space-y-8">
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div class="hover-lift rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total réglé</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($totalSpentXof)); ?></p>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                <i class="fas fa-euro-sign text-xl"></i>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Sur l’ensemble de vos achats.</p>
          </div>

          <div class="hover-lift rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nombre de commandes</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e(number_format($ordersCount, 0, ',', ' ')); ?></p>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-primary-600 dark:bg-blue-900/30 dark:text-primary-300">
                <i class="fas fa-shopping-bag text-xl"></i>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e(function_exists('view_pluralize') ? view_pluralize($ordersCount, 'commande enregistrée', 'commandes enregistrées') : ($ordersCount . ' commande(s) enregistrée(s)')); ?></p>
          </div>

          <div class="hover-lift rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-4">
              <div class="min-w-0">
                <p class="text-sm text-gray-500 dark:text-gray-400">Dernière commande</p>
                <p class="mt-1 truncate text-lg font-bold text-gray-900 dark:text-white"><?php echo e($latestOrderTitle); ?></p>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl <?php echo $latestOrderStatusMeta['tone'] === 'success' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300' : ($latestOrderStatusMeta['tone'] === 'warning' ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300' : ($latestOrderStatusMeta['tone'] === 'danger' ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300')); ?>">
                <i class="fas fa-clock text-xl"></i>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e($latestOrderMeta); ?></p>
          </div>
        </section>

        <section class="student-orders-panel rounded-[1.5rem] border border-gray-100 bg-white shadow-card dark:border-gray-700 dark:bg-gray-800">
          <div class="flex flex-col gap-4 border-b border-gray-100 p-5 dark:border-gray-700 md:flex-row md:items-center md:justify-between">
            <div>
              <h3 class="font-bold text-gray-900 dark:text-white">Historique des commandes</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                <?php echo e(function_exists('view_pluralize') ? view_pluralize(count($ordersList), 'commande listée sur cette page', 'commandes listées sur cette page') : (count($ordersList) . ' commande(s) listée(s) sur cette page')); ?>
                · <?php echo e(number_format($ordersCount, 0, ',', ' ')); ?> au total
              </p>
              <?php if ($ordersCount > 0): ?>
                <p class="student-order-status-summary mt-2 text-xs font-semibold text-slate-500 dark:text-slate-300">
                  Historique complet : <?php echo e((string) $validatedCount); ?> validée<?php echo $validatedCount === 1 ? '' : 's'; ?>
                  · <?php echo e((string) $pendingCount); ?> en attente
                  <?php if ($failedOrCanceledCount > 0): ?>
                    · <?php echo e((string) $failedOrCanceledCount); ?> à reprendre
                  <?php endif; ?>
                </p>
              <?php endif; ?>
            </div>
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
              <div class="relative md:hidden">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input
                  type="search"
                  id="search-orders-mobile"
                  data-orders-search
                  placeholder="Rechercher une commande..."
                  class="w-full rounded-xl border-none bg-gray-100 py-2.5 pl-9 pr-4 text-sm outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700"
                >
              </div>
              <select id="status-filter" class="rounded-xl border-none bg-gray-100 px-3 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300">
                <option value="all">Tous les statuts</option>
                <option value="paid">Validées</option>
                <option value="pending">En attente</option>
                <option value="failed">Échouées</option>
                <option value="refunded">Remboursées</option>
                <option value="free">Gratuites</option>
                <option value="cancelled">Annulées</option>
              </select>
            </div>
          </div>

          <?php if ($ordersList === []): ?>
            <div class="px-6 py-16 text-center">
              <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-blue-50 text-4xl text-primary-600 dark:bg-blue-500/10 dark:text-primary-300">
                <i class="fas fa-receipt"></i>
              </div>
              <h3 class="mt-5 text-lg font-bold text-gray-900 dark:text-white">Aucun achat pour le moment</h3>
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Vos commandes, statuts et accès apparaîtront ici.</p>
              <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="<?php echo e(site_url('catalogue')); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                  <i class="fas fa-search"></i>
                  <span>Explorer le catalogue</span>
                </a>
                <a href="<?php echo e($favoritesUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300">
                  <i class="fas fa-heart"></i>
                  <span>Voir mes favoris</span>
                </a>
              </div>
            </div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="student-orders-table w-full min-w-[1080px] text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                  <tr class="text-xs uppercase tracking-[0.16em]">
                    <th class="px-5 py-3 text-left">Commande</th>
                    <th class="px-5 py-3 text-left">Date</th>
                    <th class="px-5 py-3 text-left">Cours</th>
                    <th class="px-5 py-3 text-left">Montant</th>
                    <th class="px-5 py-3 text-left">Statut</th>
                    <th class="px-5 py-3 text-left">Reçu</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody id="orders-table-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                  <?php foreach ($ordersList as $index => $order): ?>
                    <?php
                      $orderReference = trim((string) ($order['reference'] ?? ''));
                      $statusRaw = strtolower((string) ($order['status'] ?? ''));
                      $statusMeta = $statusMetaFor($statusRaw);
                      $statusTone = (string) ($statusMeta['tone'] ?? 'neutral');
                      $statusLabel = (string) ($statusMeta['label'] ?? 'Inconnu');
                      $statusClass = match ($statusTone) {
                          'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                          'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                          'danger' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                          'info' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                          default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                      };
                      $providerLabel = $providerLabelFor((string) ($order['provider'] ?? ''));
                      $orderDate = $fmtDtShort((string) ($order['created_at'] ?? ''));
                      $amountLabel = $formatMoney((int) ($order['total_xof'] ?? 0), $statusRaw);
                      $courseLabels = $courseLabelsForOrder($order);
                      $searchParts = array_filter([
                          $orderReference,
                          $providerLabel,
                          $statusLabel,
                          $orderDate,
                          $amountLabel,
                          implode(' ', $courseLabels),
                      ], static fn (string $value): bool => trim($value) !== '');
                      $searchBlob = $normalizeSearchText(implode(' ', $searchParts));
                      $courseIds = $courseIdsForOrder($order);
                      $isValidatedOrder = in_array($statusRaw, ['paid', 'completed', 'succeeded', 'success', 'free'], true);
                      $isPendingOrder = $statusRaw === 'pending';
                      $isFailedOrder = in_array($statusRaw, ['failed', 'cancelled', 'canceled'], true);
                      $canRetryCheckout = $courseIds !== [];
                      $checkoutUrl = $canRetryCheckout ? site_url('checkout?course=' . (int) $courseIds[0]) : site_url('checkout');
                      $orderSupportUrl = $supportUrl . '&topic=paiement' . ($orderReference !== '' ? '&order=' . rawurlencode($orderReference) : '');
                      $receiptPdfUrl = site_url('student/orders?receipt=1&order_id=' . (int) ($order['id'] ?? 0));
                      $primaryCourseLabel = count($courseLabels) > 1 ? 'Voir les cours' : 'Voir le cours';
                      $detailMessage = ($orderReference !== '' ? ('Commande #' . $orderReference) : 'Commande')
                          . ' · ' . $statusLabel
                          . ' · ' . $amountLabel
                          . ($courseLabels !== [] ? (' · ' . implode(', ', $courseLabels)) : '');
                      $courseVisibleLabels = array_slice($courseLabels, 0, 3);
                      $courseOverflow = max(0, count($courseLabels) - count($courseVisibleLabels));
                    ?>
                    <tr class="order-row order-row-enter hover:bg-gray-50 transition dark:hover:bg-gray-700/30" data-search="<?php echo e($searchBlob); ?>" data-status="<?php echo e($statusRaw !== '' ? $statusRaw : 'unknown'); ?>">
                      <td class="px-5 py-4 align-top">
                        <div class="flex items-center gap-3">
                          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-primary-600 dark:bg-blue-500/10 dark:text-primary-300">
                            <i class="fas fa-receipt"></i>
                          </div>
                          <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white"><?php echo e($orderReference !== '' ? ('#' . $orderReference) : 'Commande sans référence'); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Paiement via <?php echo e($providerLabel); ?></p>
                            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500 sm:hidden"><?php echo e($orderDate !== '' ? $orderDate : 'Date non renseignée'); ?></p>
                          </div>
                        </div>
                      </td>
                      <td class="px-5 py-4 align-top text-gray-500 dark:text-gray-400 hidden sm:table-cell"><?php echo e($orderDate !== '' ? $orderDate : 'Non définie'); ?></td>
                      <td class="px-5 py-4 align-top">
                        <?php if ($courseVisibleLabels !== []): ?>
                          <div class="flex flex-wrap gap-2">
                            <?php foreach ($courseVisibleLabels as $label): ?>
                              <span class="student-order-course-chip rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-900/70 dark:text-gray-200">
                                <?php echo e($label); ?>
                              </span>
                            <?php endforeach; ?>
                            <?php if ($courseOverflow > 0): ?>
                              <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-primary-300">
                                +<?php echo e((string) $courseOverflow); ?>
                              </span>
                            <?php endif; ?>
                          </div>
                        <?php else: ?>
                          <p class="text-sm text-gray-500 dark:text-gray-400">Aucun cours lié n’a été trouvé.</p>
                        <?php endif; ?>
                      </td>
                      <td class="px-5 py-4 align-top font-semibold whitespace-nowrap text-gray-900 dark:text-white"><?php echo e($amountLabel); ?></td>
                      <td class="px-5 py-4 align-top">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                      </td>
                      <td class="px-5 py-4 align-top">
                        <?php if ($isValidatedOrder): ?>
                          <a
                            href="<?php echo e($receiptPdfUrl); ?>"
                            class="student-order-receipt inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-300"
                          >
                            <i class="fas fa-file-download"></i>
                            <span>Reçu</span>
                          </a>
                        <?php else: ?>
                          <span class="student-order-receipt-muted inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
                            <i class="fas fa-lock text-[11px]"></i>
                            <span>Après validation</span>
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="px-5 py-4 align-top text-right">
                        <div class="student-order-actions">
                          <?php if ($isValidatedOrder && $courseLabels !== []): ?>
                            <a href="<?php echo e($myCoursesUrl); ?>" class="student-order-action student-order-action--primary">
                              <i class="fas fa-book-open"></i>
                              <span><?php echo e($primaryCourseLabel); ?></span>
                            </a>
                          <?php elseif ($isPendingOrder && $canRetryCheckout): ?>
                            <a href="<?php echo e($checkoutUrl); ?>" class="student-order-action student-order-action--warning">
                              <i class="fas fa-credit-card"></i>
                              <span>Finaliser paiement</span>
                            </a>
                          <?php elseif ($isPendingOrder): ?>
                            <a href="<?php echo e($orderSupportUrl); ?>" class="student-order-action student-order-action--warning">
                              <i class="fas fa-history"></i>
                              <span>Vérifier paiement</span>
                            </a>
                          <?php elseif ($isFailedOrder && $canRetryCheckout): ?>
                            <a href="<?php echo e($checkoutUrl); ?>" class="student-order-action student-order-action--danger">
                              <i class="fas fa-redo-alt"></i>
                              <span>Réessayer paiement</span>
                            </a>
                          <?php else: ?>
                            <a href="<?php echo e($orderSupportUrl); ?>" class="student-order-action student-order-action--secondary">
                              <i class="fas fa-headset"></i>
                              <span>Support</span>
                            </a>
                          <?php endif; ?>
                          <button type="button" class="student-order-action student-order-action--secondary" data-order-detail data-order-detail-message="<?php echo e($detailMessage); ?>">
                            <i class="fas fa-info-circle"></i>
                            <span>Détails</span>
                          </button>
                          <?php if (!$isValidatedOrder && ($isPendingOrder || $isFailedOrder) && $canRetryCheckout): ?>
                            <a href="<?php echo e($orderSupportUrl); ?>" class="student-order-action student-order-action--ghost">
                              <i class="fas fa-headset"></i>
                              <span>Support</span>
                            </a>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div id="orders-no-search-results" class="hidden border-t border-gray-100 px-6 py-14 text-center dark:border-gray-700">
              <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-2xl text-gray-400 dark:bg-gray-700 dark:text-gray-300">
                <i class="fas fa-search"></i>
              </div>
              <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Aucun résultat</h3>
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Essayez un autre mot-clé ou changez le filtre de statut.</p>
            </div>

            <?php if ($paginationTotalPages > 1): ?>
              <div class="border-t border-gray-100 px-5 py-5 dark:border-gray-700">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Affichage <?php echo e((string) $paginationStart); ?> à <?php echo e((string) $paginationEnd); ?> sur <?php echo e((string) $paginationTotal); ?> élément(s)
                  </p>
                  <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                    <a href="<?php echo e($buildPageUrl($paginationPrevPage)); ?>" class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition <?php echo $paginationHasPrev ? 'border-gray-200 bg-white text-gray-700 hover:border-blue-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-primary-300' : 'pointer-events-none border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'; ?>">
                      <span>Précédent</span>
                    </a>
                    <?php if ($paginationWindowStart > 1): ?>
                      <a href="<?php echo e($buildPageUrl(1)); ?>" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-primary-300">1</a>
                      <?php if ($paginationWindowStart > 2): ?>
                        <span class="px-1 text-gray-400">…</span>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?php for ($pageNo = $paginationWindowStart; $pageNo <= $paginationWindowEnd; $pageNo++): ?>
                      <?php if ($pageNo === $paginationPage): ?>
                        <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl bg-primary-600 px-3 text-sm font-semibold text-white shadow-sm" aria-current="page"><?php echo e((string) $pageNo); ?></span>
                      <?php else: ?>
                        <a href="<?php echo e($buildPageUrl($pageNo)); ?>" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-primary-300"><?php echo e((string) $pageNo); ?></a>
                      <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($paginationWindowEnd < $paginationTotalPages): ?>
                      <?php if ($paginationWindowEnd < $paginationTotalPages - 1): ?>
                        <span class="px-1 text-gray-400">…</span>
                      <?php endif; ?>
                      <a href="<?php echo e($buildPageUrl($paginationTotalPages)); ?>" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-primary-300"><?php echo e((string) $paginationTotalPages); ?></a>
                    <?php endif; ?>
                    <a href="<?php echo e($buildPageUrl($paginationNextPage)); ?>" class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition <?php echo $paginationHasNext ? 'border-gray-200 bg-white text-gray-700 hover:border-blue-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-primary-300' : 'pointer-events-none border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'; ?>">
                      <span>Suivant</span>
                    </a>
                  </nav>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </section>
      </div>
    </main>
  </div>
</div>
</body>
</html>
