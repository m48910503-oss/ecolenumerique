<?php
declare(strict_types=1);

$dashboardSpace = 'teacher';
$user = is_array($user ?? null) ? $user : [];
$notifications = array_values(is_array($notifications ?? null) ? $notifications : []);
$scopeCounts = is_array($scopeCounts ?? null) ? $scopeCounts : [];
$scope = (string) ($scope ?? 'all');
if (!in_array($scope, ['all', 'unread', 'read', 'todo', 'archived', 'important', 'support', 'learning', 'business', 'admin', 'other'], true)) {
    $scope = 'all';
}
$success = trim((string) ($success ?? ''));
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$totalItems = max(0, (int) ($totalItems ?? 0));
$perPage = max(1, (int) ($perPage ?? 10));
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$pendingMeetingsCount = max(0, (int) ($pendingMeetingsCount ?? 0));
$courseCount = max(0, (int) ($courseCount ?? 0));
$dashboardUrl = trim((string) ($dashboardUrl ?? site_url('teacher/dashboard')));
$teacherSupportUrl = site_url('support?space=teacher');
$teacherProfileUrl = site_url('profile?space=teacher');
$teacherDisplayName = dashboard_user_display_name($user, 'Enseignant');
$teacherEmail = trim((string) ($user['email'] ?? ''));
$teacherAvatarUrl = dashboard_user_avatar_url($user);
$teacherInitials = dashboard_user_initials($user, 'EN');

$normalizeTitleCase = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_convert_case')) {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    return ucwords(strtolower($value));
};

$teacherFirstName = trim((string) ($user['first_name'] ?? ''));
if ($teacherFirstName === '') {
    $displayParts = preg_split('/\s+/', trim($teacherDisplayName)) ?: [];
    $teacherFirstName = trim((string) ($displayParts[0] ?? ''));
}
if ($teacherFirstName === '' && $teacherEmail !== '') {
    $emailPrefix = trim((string) explode('@', $teacherEmail, 2)[0]);
    $emailPrefix = str_replace(['.', '_', '-'], ' ', $emailPrefix);
    $emailParts = preg_split('/\s+/', $emailPrefix) ?: [];
    $teacherFirstName = trim((string) ($emailParts[0] ?? ''));
}
$teacherFirstName = $normalizeTitleCase($teacherFirstName !== '' ? $teacherFirstName : 'Enseignant');

$q = trim((string) ($_GET['q'] ?? ''));
$notificationTotalCount = max(0, (int) ($scopeCounts['all'] ?? count($notifications)));
$readCount = max(0, (int) ($scopeCounts['read'] ?? max(0, $notificationTotalCount - $unreadCount)));
$importantCount = max(0, (int) ($scopeCounts['important'] ?? 0));
$todoCount = max(0, (int) ($scopeCounts['todo'] ?? 0));
$archivedCount = max(0, (int) ($scopeCounts['archived'] ?? 0));
$supportScopeCount = max($supportUnreadCount, (int) ($scopeCounts['support'] ?? 0));
$scopeMeta = [
    'support' => ['label' => 'Support', 'class' => 'bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300', 'icon' => 'headset'],
    'learning' => ['label' => 'Pédagogie', 'class' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-300', 'icon' => 'book-open'],
    'business' => ['label' => 'Commercial', 'class' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300', 'icon' => 'chart-line'],
    'admin' => ['label' => 'Admin', 'class' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300', 'icon' => 'gear'],
    'other' => ['label' => 'Général', 'class' => 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300', 'icon' => 'bell'],
];

$importanceMeta = [
    'info' => ['label' => 'Info', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'],
    'important' => ['label' => 'Important', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
    'urgent' => ['label' => 'Urgent', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'],
];

$statusMeta = [
    'todo' => ['label' => 'À traiter', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/35 dark:text-amber-200'],
    'unread' => ['label' => 'Non lue', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/35 dark:text-rose-200'],
    'read' => ['label' => 'Lue', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-200'],
    'archived' => ['label' => 'Archivée', 'class' => 'bg-slate-200 text-slate-700 dark:bg-slate-700/80 dark:text-slate-200'],
];

$scopeFilters = [
    'all' => 'Toutes',
    'unread' => 'Non lues',
    'todo' => 'À traiter',
    'support' => 'Support',
    'learning' => 'Pédagogie',
    'business' => 'Commercial',
    'admin' => 'Admin',
    'important' => 'Prioritaires',
    'read' => 'Lues',
    'archived' => 'Archivées',
    'other' => 'Autres',
];
$primaryScopeFilters = ['all', 'unread', 'important'];
$secondaryScopeFilters = array_values(array_filter(
    array_keys($scopeFilters),
    static fn (string $filterKey): bool => !in_array($filterKey, $primaryScopeFilters, true)
));
$activeSecondaryScope = in_array($scope, $secondaryScopeFilters, true) ? $scope : '';

$scopeToneMeta = [
    'support' => [
        'card' => 'border-sky-200 bg-sky-50/80 dark:border-sky-500/35 dark:bg-sky-950/25',
        'icon' => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-400/20',
        'open' => 'border-sky-200 bg-sky-100 text-sky-800 hover:bg-sky-200 dark:border-sky-500/30 dark:bg-sky-500/15 dark:text-sky-200 dark:hover:bg-sky-500/25',
    ],
    'learning' => [
        'card' => 'border-violet-200 bg-violet-50/80 dark:border-violet-500/35 dark:bg-violet-950/25',
        'icon' => 'bg-violet-100 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-500/15 dark:text-violet-300 dark:ring-violet-400/20',
        'open' => 'border-violet-200 bg-violet-100 text-violet-800 hover:bg-violet-200 dark:border-violet-500/30 dark:bg-violet-500/15 dark:text-violet-200 dark:hover:bg-violet-500/25',
    ],
    'business' => [
        'card' => 'border-amber-200 bg-amber-50/80 dark:border-amber-500/35 dark:bg-amber-950/25',
        'icon' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-400/20',
        'open' => 'border-amber-200 bg-amber-100 text-amber-800 hover:bg-amber-200 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-200 dark:hover:bg-amber-500/25',
    ],
    'admin' => [
        'card' => 'border-rose-200 bg-rose-50/80 dark:border-rose-500/35 dark:bg-rose-950/25',
        'icon' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-400/20',
        'open' => 'border-rose-200 bg-rose-100 text-rose-800 hover:bg-rose-200 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-200 dark:hover:bg-rose-500/25',
    ],
    'other' => [
        'card' => 'border-slate-200 bg-slate-50/90 dark:border-slate-600/60 dark:bg-slate-800/70',
        'icon' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-500/30',
        'open' => 'border-slate-200 bg-slate-100 text-slate-800 hover:bg-slate-200 dark:border-slate-500/30 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600',
    ],
];

$iconClass = static function (string $name): string {
    static $map = [
        'home' => 'fas fa-home',
        'book' => 'fas fa-book',
        'book-open' => 'fas fa-book-open',
        'plus-circle' => 'fas fa-plus-circle',
        'users' => 'fas fa-users',
        'chart-bar' => 'fas fa-chart-bar',
        'chart-line' => 'fas fa-chart-line',
        'hand-holding-usd' => 'fas fa-hand-holding-usd',
        'handshake' => 'fas fa-handshake',
        'gift' => 'fas fa-gift',
        'ticket' => 'fas fa-ticket',
        'comments' => 'fas fa-comments',
        'user-circle' => 'fas fa-user-circle',
        'cog' => 'fas fa-cog',
        'gear' => 'fas fa-cog',
        'bell' => 'far fa-bell',
        'moon' => 'fas fa-moon',
        'sun' => 'fas fa-sun',
        'bars' => 'fas fa-bars',
        'times' => 'fas fa-times',
        'sign-out-alt' => 'fas fa-sign-out-alt',
        'search' => 'fas fa-search',
        'user-graduate' => 'fas fa-user-graduate',
        'layer-group' => 'fas fa-layer-group',
        'euro-sign' => 'fas fa-euro-sign',
        'star' => 'fas fa-star',
        'video' => 'fas fa-video',
        'calendar' => 'fas fa-calendar-alt',
        'wallet' => 'fas fa-wallet',
        'file-text' => 'fas fa-file-alt',
        'chevron-down' => 'fas fa-chevron-down',
        'check' => 'fas fa-check',
        'check-circle' => 'fas fa-check-circle',
        'award' => 'fas fa-award',
        'play-circle' => 'fas fa-play-circle',
        'video-slash' => 'fas fa-video-slash',
        'filter' => 'fas fa-filter',
        'credit-card' => 'fas fa-credit-card',
        'headset' => 'fas fa-headset',
        'envelope-open-text' => 'fas fa-envelope-open-text',
        'arrow-up-right-from-square' => 'fas fa-external-link-alt',
        'circle-check' => 'fas fa-check-circle',
        'circle-info' => 'fas fa-info-circle',
        'circle-exclamation' => 'fas fa-exclamation-circle',
        'clock' => 'fas fa-clock',
        'rotate-right' => 'fas fa-redo-alt',
    ];

    $key = strtolower(trim($name));
    return $map[$key] ?? 'fas fa-circle';
};

$toneClasses = static function (string $tone): array {
    $tone = strtolower(trim($tone));
    return match ($tone) {
        'sky' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-primary-600 dark:text-primary-300'],
        'cyan' => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30', 'text' => 'text-accent-600 dark:text-cyan-300'],
        'emerald', 'green' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-600 dark:text-green-300'],
        'amber', 'yellow' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-600 dark:text-yellow-300'],
        'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-600 dark:text-purple-300'],
        'rose', 'red' => ['bg' => 'bg-rose-100 dark:bg-rose-900/30', 'text' => 'text-rose-600 dark:text-rose-300'],
        default => ['bg' => 'bg-gray-100 dark:bg-gray-700/50', 'text' => 'text-gray-500 dark:text-gray-300'],
    };
};

$formatCount = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ');
};

$formatDateTime = static function (string $dbValue): string {
    $dbValue = trim($dbValue);
    if ($dbValue === '') {
        return '';
    }
    $ts = strtotime($dbValue);
    if (!$ts) {
        return $dbValue;
    }
    return date('d/m/Y H:i', $ts);
};

$buildScopeUrl = static function (string $targetScope) use ($q): string {
    $params = ['space' => 'teacher'];
    if ($targetScope !== 'all') {
        $params['scope'] = $targetScope;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }
    $query = http_build_query($params);
    return site_url('notifications') . ($query !== '' ? '?' . $query : '');
};

$buildPageUrl = static function (int $targetPage) use ($scope, $q): string {
    $targetPage = max(1, $targetPage);
    $params = ['space' => 'teacher'];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }
    $query = http_build_query($params);
    return site_url('notifications') . ($query !== '' ? '?' . $query : '');
};

$buildOpenUrl = static function (int $notificationId) use ($scope, $page, $q): string {
    $params = ['open' => $notificationId, 'space' => 'teacher'];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }
    return site_url('notifications?' . http_build_query($params));
};

$buildPostActionUrl = static function () use ($scope, $page, $q): string {
    $params = [];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    $params['space'] = 'teacher';
    if ($page > 1) {
        $params['page'] = $page;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }
    $query = http_build_query($params);
    return site_url('notifications') . ($query !== '' ? '?' . $query : '');
};

$notificationActionUrl = $buildPostActionUrl();

?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/notifications">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Notifications Enseignant · École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher_notifications.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher_notifications.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/notifications_page.js')); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/notifications" class="app-role-teacher teacher-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">
<div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
  <?php $teacherNavActive = 'notifications'; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
    <?php $teacherTopTitle = 'Notifications'; ?>
    <?php $teacherTopSubtitle = 'Espace formateur'; ?>
    <?php $showCatalogue = false; ?>
    <?php require __DIR__ . '/_topbar.php'; ?>
        <div class="notifications-page teacher-notifications-page p-4 pb-32 space-y-5 animate-fade-up sm:p-6 sm:pb-24">
            <?php $teacherWelcomeTitle = 'Notifications'; ?>
            <?php $teacherWelcomeSummary = $formatCount($unreadCount) . ' non lues · ' . $formatCount($notificationTotalCount) . ' reçues.'; ?>
            <?php $teacherWelcomeActionLabel = ''; ?>
            <?php require __DIR__ . '/_welcome_hero.php'; ?>

            <section class="teacher-notifications-stats-grid hidden" aria-hidden="true">
                <article class="teacher-notifications-stat-card rounded-2xl border border-blue-100 bg-white p-3.5 shadow-sm transition dark:border-blue-900/30 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Notifications</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?php echo e($formatCount($notificationTotalCount)); ?></p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Total reçues</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>
                </article>
                <article class="teacher-notifications-stat-card rounded-2xl border border-rose-100 bg-white p-3.5 shadow-sm transition dark:border-rose-900/30 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Non lues</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?php echo e($formatCount($unreadCount)); ?></p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">À traiter en priorité</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                    </div>
                </article>
                <article class="teacher-notifications-stat-card rounded-2xl border border-emerald-100 bg-white p-3.5 shadow-sm transition dark:border-emerald-900/30 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Support</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?php echo e($formatCount($supportScopeCount)); ?></p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Messages de suivi</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300">
                                <i class="fas fa-headset"></i>
                        </div>
                    </div>
                </article>
                <article class="teacher-notifications-stat-card rounded-2xl border border-amber-100 bg-white p-3.5 shadow-sm transition dark:border-amber-900/30 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Prioritaires</p>
                            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?php echo e($formatCount($importantCount)); ?></p>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">À consulter en premier</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </article>
            </section>

            <section class="teacher-notifications-panels-grid grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="teacher-notifications-panel bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 sm:p-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900 dark:text-white">Boîte de réception</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Suivez les alertes qui demandent une action.</p>
                        </div>
                        <form method="post" action="<?php echo e($notificationActionUrl); ?>" class="shrink-0">
                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                            <input type="hidden" name="action" value="mark_all_read">
                            <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                            <input type="hidden" name="space" value="teacher">
                            <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                            <?php if ($q !== ''): ?>
                                <input type="hidden" name="q" value="<?php echo e($q); ?>">
                            <?php endif; ?>
                            <button type="submit" class="teacher-notifications-mark-all inline-flex items-center gap-2 rounded-full border border-blue-600 bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-500/20 transition hover:border-blue-700 hover:bg-blue-700 dark:border-blue-400 dark:bg-blue-500 dark:hover:bg-blue-400">
                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                <span>Tout marquer comme lu</span>
                            </button>
                        </form>
                    </div>

                    <div class="teacher-notifications-filter-bar mt-4 flex flex-wrap items-center gap-1.5 border-b border-gray-200 pb-3 dark:border-gray-700">
                        <?php foreach ($primaryScopeFilters as $filterKey): ?>
                            <?php
                                $filterLabel = (string) ($scopeFilters[$filterKey] ?? $filterKey);
                                $badgeValue = (int) ($scopeCounts[$filterKey] ?? 0);
                                if ($filterKey === 'all') {
                                    $badgeValue = $notificationTotalCount;
                                } elseif ($filterKey === 'unread') {
                                    $badgeValue = $unreadCount;
                                } elseif ($filterKey === 'important') {
                                    $badgeValue = $importantCount;
                                }
                                $isActive = $scope === $filterKey;
                                $buttonClass = $isActive
                                    ? 'relative inline-flex items-center rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1.5 text-sm font-semibold text-primary-700 transition dark:border-primary-400/30 dark:bg-primary-900/20 dark:text-primary-300'
                                    : 'relative inline-flex items-center rounded-full border border-transparent px-2.5 py-1.5 text-sm font-medium text-gray-500 transition hover:border-gray-200 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:bg-gray-700/60 dark:hover:text-white';
                                $badgeClass = $filterKey === 'unread'
                                    ? 'ml-1.5 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900/40 dark:text-red-300'
                                    : 'ml-1.5 rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300';
                            ?>
                            <a href="<?php echo e($buildScopeUrl($filterKey)); ?>" class="<?php echo e($buttonClass); ?>" aria-current="<?php echo $isActive ? 'page' : 'false'; ?>">
                                <?php echo e($filterLabel); ?>
                                <span class="<?php echo e($badgeClass); ?>"><?php echo e($formatCount($badgeValue)); ?></span>
                            </a>
                        <?php endforeach; ?>

                        <details class="notification-filter-more relative">
                            <?php
                                $moreIsActive = $activeSecondaryScope !== '';
                                $moreLabel = $moreIsActive ? (string) ($scopeFilters[$activeSecondaryScope] ?? 'Plus') : 'Plus';
                                $moreSummaryClass = $moreIsActive
                                    ? 'inline-flex cursor-pointer items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1.5 text-sm font-semibold text-primary-700 transition dark:border-primary-400/30 dark:bg-primary-900/20 dark:text-primary-300'
                                    : 'inline-flex cursor-pointer items-center gap-2 rounded-full border border-gray-200 bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-700';
                            ?>
                            <summary class="<?php echo e($moreSummaryClass); ?>" aria-label="Afficher plus de filtres">
                                <i class="fas fa-sliders-h" aria-hidden="true"></i>
                                <span><?php echo e($moreLabel); ?></span>
                                <i class="fas fa-chevron-down text-[10px]" aria-hidden="true"></i>
                            </summary>
                            <div class="absolute left-0 top-full z-50 mt-2 w-60 rounded-2xl border border-gray-200 bg-white p-2 shadow-2xl ring-1 ring-black/5 dark:border-gray-700 dark:bg-slate-900 dark:ring-white/10">
                                <?php foreach ($secondaryScopeFilters as $filterKey): ?>
                                    <?php
                                        $filterLabel = (string) ($scopeFilters[$filterKey] ?? $filterKey);
                                        $badgeValue = (int) ($scopeCounts[$filterKey] ?? 0);
                                        if ($filterKey === 'todo') {
                                            $badgeValue = $todoCount;
                                        } elseif ($filterKey === 'support') {
                                            $badgeValue = $supportScopeCount;
                                        } elseif ($filterKey === 'read') {
                                            $badgeValue = $readCount;
                                        } elseif ($filterKey === 'archived') {
                                            $badgeValue = $archivedCount;
                                        }
                                        $isActive = $scope === $filterKey;
                                        $menuBadgeClass = $filterKey === 'important'
                                            ? 'rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                            : ($filterKey === 'read'
                                                ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                : ($filterKey === 'archived'
                                                    ? 'rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-700 dark:bg-slate-700 dark:text-slate-200'
                                                    : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300'));
                                    ?>
                                    <a href="<?php echo e($buildScopeUrl($filterKey)); ?>" class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium transition <?php echo $isActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700/70 dark:hover:text-white'; ?>" aria-current="<?php echo $isActive ? 'page' : 'false'; ?>">
                                        <span><?php echo e($filterLabel); ?></span>
                                        <span class="<?php echo e($menuBadgeClass); ?>"><?php echo e($formatCount($badgeValue)); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    </div>

    <?php if ($success !== ''): ?>
        <div
            class="teacher-withdrawal-toast teacher-withdrawal-toast--success"
            role="status"
            aria-live="polite"
            data-notification-toast="1"
            data-notification-toast-type="success"
            data-notification-toast-message="<?php echo e($success); ?>"
        >
            <div class="teacher-withdrawal-toast__copy">
                <span class="teacher-withdrawal-toast__icon" aria-hidden="true">
                    <i class="fas fa-check-circle"></i>
                </span>
                <div class="min-w-0">
                    <p class="teacher-withdrawal-toast__title">Succès</p>
                    <p class="teacher-withdrawal-toast__message"><?php echo e($success); ?></p>
                </div>
            </div>
            <span class="teacher-withdrawal-toast__bar" aria-hidden="true"></span>
        </div>
    <?php endif; ?>

                    <div class="teacher-notifications-list mt-5 grid gap-3">
                        <?php if ($notifications === []): ?>
                            <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center dark:border-gray-700 dark:bg-gray-700/40">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <p class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Aucune notification pour ce filtre</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    <?php if ($q !== ''): ?>
                                        Aucune notification ne correspond à votre recherche.
                                    <?php else: ?>
                                        Vous êtes à jour sur cette sélection.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <?php
                                    $notificationId = (int) ($notification['id'] ?? 0);
                                    $notifScope = (string) ($notification['scope_key'] ?? 'other');
                                    $scopeInfo = $scopeMeta[$notifScope] ?? $scopeMeta['other'];
                                    $notifTitleRaw = trim((string) ($notification['title'] ?? ''));
                                    $notifTitle = $notifTitleRaw !== '' ? $notifTitleRaw : 'Notification';
                                    $notifSummary = trim((string) ($notification['summary'] ?? ''));
                                    $notifBody = trim((string) ($notification['body'] ?? ''));
                                    $notifCreated = $formatDateTime((string) ($notification['created_at'] ?? ''));
                                    $notifIsRead = !empty($notification['is_read']);
                                    $notifIsArchived = !empty($notification['is_archived']) || !empty($notification['archived_at']);
                                    $notifImportance = strtolower((string) ($notification['importance'] ?? 'info'));
                                    $notifIsImportant = in_array($notifImportance, ['important', 'urgent'], true);
                                    $notifNeedsAction = !empty($notification['needs_action']);
                                    $hasOpenAction = $notificationId > 0 && !empty($notification['has_open_action']);
                                    $openUrl = $hasOpenAction ? $buildOpenUrl($notificationId) : '';
                                    $notifStatusKey = (string) ($notification['status_key'] ?? '');
                                    if ($notifStatusKey === '') {
                                        if ($notifIsArchived) {
                                            $notifStatusKey = 'archived';
                                        } elseif ($notifNeedsAction) {
                                            $notifStatusKey = 'todo';
                                        } elseif (!$notifIsRead) {
                                            $notifStatusKey = 'unread';
                                        } else {
                                            $notifStatusKey = 'read';
                                        }
                                    }
                                    $statusInfo = $statusMeta[$notifStatusKey] ?? $statusMeta['read'];
                                    $toggleAction = $notifIsArchived ? 'restore' : ($notifIsRead ? 'mark_unread' : 'mark_read');
                                    $toggleLabel = $notifIsArchived ? 'Restaurer' : ($notifIsRead ? 'Marquer comme non lue' : 'Marquer comme lue');
                                    $toggleIcon = $notifIsArchived ? 'fa-undo-alt' : ($notifIsRead ? 'fa-redo-alt' : 'fa-check-circle');
                                    $importanceAction = in_array($notifImportance, ['important', 'urgent'], true) ? 'clear_importance' : 'mark_important';
                                    $importanceLabel = in_array($notifImportance, ['important', 'urgent'], true) ? 'Retirer l\'importance' : 'Marquer comme importante';
                                    $importanceIcon = in_array($notifImportance, ['important', 'urgent'], true) ? 'far fa-star' : 'fas fa-star';
                                    $archiveAction = 'archive';
                                    $archiveLabel = 'Archiver';
                                    $archiveIcon = 'fa-archive';
                                    $stateLabel = (string) ($statusInfo['label'] ?? 'Lue');
                                    $stateClass = (string) ($statusInfo['class'] ?? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300');
                                    $showStateBadge = $notifStatusKey !== 'read';
                                    $showImportanceBadge = $notifIsImportant && !$notifIsArchived;
                                    $importanceInfo = $importanceMeta[$notifImportance] ?? $importanceMeta['info'];
                                    $scopeLabel = (string) ($scopeInfo['label'] ?? 'Général');
                                    $showScopeBadge = $scopeLabel !== '' && stripos($notifTitle, $scopeLabel) === false;
                                    $toneInfo = $scopeToneMeta[$notifScope] ?? $scopeToneMeta['other'];
                                    $iconToneClass = (string) ($toneInfo['icon'] ?? $scopeToneMeta['other']['icon']);
                                    $openButtonClass = (string) ($toneInfo['open'] ?? $scopeToneMeta['other']['open']);
                                    $toggleButtonClass = $notifIsArchived
                                        ? 'bg-slate-50 text-slate-700 hover:bg-slate-100 dark:bg-slate-500/15 dark:text-slate-200 dark:hover:bg-slate-500/25'
                                        : ($notifIsRead
                                            ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-200 dark:hover:bg-indigo-500/25'
                                            : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-200 dark:hover:bg-emerald-500/25');
                                    $articleClass = 'teacher-notifications-card relative rounded-2xl border p-3 transition sm:p-3.5 ' . (string) ($toneInfo['card'] ?? $scopeToneMeta['other']['card']) . ' shadow-sm';
                                    if ($notifIsArchived) {
                                        $articleClass = 'teacher-notifications-card relative rounded-2xl border border-slate-200 bg-slate-50/80 p-3 opacity-80 transition sm:p-3.5 dark:border-slate-700 dark:bg-slate-800/55';
                                        $iconToneClass = 'bg-slate-100 text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600/40';
                                    } elseif ($notifStatusKey === 'todo') {
                                        $articleClass .= ' ring-1 ring-amber-300/60 dark:ring-amber-400/30';
                                    } elseif ($notifStatusKey === 'unread') {
                                        $articleClass .= ' ring-1 ring-blue-300/60 dark:ring-blue-400/30';
                                    } elseif ($notifIsImportant) {
                                        $articleClass .= ' ring-1 ring-amber-300/60 dark:ring-amber-400/30';
                                    }
                                    $iconName = (string) ($scopeInfo['icon'] ?? 'bell');
                                ?>
                                <article
                                    class="<?php echo e($articleClass); ?>"
                                    data-notification-status="<?php echo e($notifStatusKey); ?>"
                                    data-notification-priority="<?php echo ($notifIsImportant || $notifNeedsAction) ? '1' : '0'; ?>"
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex min-w-0 flex-1 items-start gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl <?php echo e($iconToneClass); ?>">
                                                <i class="<?php echo e($iconClass($iconName)); ?>" aria-hidden="true"></i>
                                            </div>
                                                <div class="min-w-0 space-y-1.5">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p class="text-sm font-semibold leading-snug text-gray-900 dark:text-gray-100"><?php echo e($notifTitle); ?></p>
                                                        <?php if ($showScopeBadge): ?>
                                                            <span class="<?php echo e((string) ($scopeInfo['class'] ?? 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300')); ?> inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"><?php echo e($scopeLabel); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($showImportanceBadge): ?>
                                                            <span class="<?php echo e((string) ($importanceInfo['class'] ?? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300')); ?> inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"><?php echo e((string) ($importanceInfo['label'] ?? 'Prioritaire')); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($showStateBadge): ?>
                                                            <span class="<?php echo e($stateClass); ?> inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"><?php echo e($stateLabel); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php if ($notifSummary !== ''): ?>
                                                    <p class="notif-message-body text-sm text-gray-600 dark:text-gray-300<?php echo $notifIsArchived ? ' italic opacity-90' : ''; ?>" data-linkify="true"><?php echo e($notifSummary); ?></p>
                                                <?php endif; ?>
                                                <?php if ($notifBody !== '' && $notifBody !== $notifSummary): ?>
                                                    <p class="notif-message-body text-xs text-gray-500 dark:text-gray-400<?php echo $notifIsArchived ? ' opacity-80' : ''; ?>" data-linkify="true"><?php echo nl2br(e($notifBody)); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <div class="flex w-full shrink-0 items-center justify-between gap-2 sm:w-auto sm:flex-col sm:items-end sm:justify-start sm:gap-1.5">
                                        <p class="min-w-0 pt-0 text-xs leading-tight text-gray-500 dark:text-gray-400 sm:pt-1 sm:text-right"><?php echo e($notifCreated !== '' ? $notifCreated : 'Date inconnue'); ?></p>
                                        <details class="notification-card-menu relative">
                                                <summary class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-700" aria-label="Actions secondaires">
                                                    <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                                                </summary>
                                                <div class="absolute right-0 top-full z-50 mt-2 w-60 rounded-2xl border border-gray-200 bg-white p-2 shadow-2xl ring-1 ring-black/5 dark:border-gray-700 dark:bg-slate-900 dark:ring-white/10">
                                                    <form method="post" action="<?php echo e($notificationActionUrl); ?>">
                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                        <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                                                        <input type="hidden" name="action" value="<?php echo e($toggleAction); ?>">
                                                        <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                                                        <input type="hidden" name="space" value="teacher">
                                                        <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                                                        <?php if ($q !== ''): ?>
                                                            <input type="hidden" name="q" value="<?php echo e($q); ?>">
                                                        <?php endif; ?>
                                                        <button type="submit" class="<?php echo e($toggleButtonClass); ?> flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-semibold transition">
                                                            <i class="fas <?php echo e($toggleIcon); ?> w-4 text-center" aria-hidden="true"></i>
                                                            <span><?php echo e($toggleLabel); ?></span>
                                                        </button>
                                                    </form>
                                                    <?php if (!$notifIsArchived): ?>
                                                        <form method="post" action="<?php echo e($notificationActionUrl); ?>">
                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                            <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                                                            <input type="hidden" name="action" value="<?php echo e($importanceAction); ?>">
                                                            <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                                                            <input type="hidden" name="space" value="teacher">
                                                            <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                                                            <?php if ($q !== ''): ?>
                                                                <input type="hidden" name="q" value="<?php echo e($q); ?>">
                                                            <?php endif; ?>
                                                            <button type="submit" class="mt-1 flex w-full items-center gap-2 rounded-xl bg-amber-50 px-3 py-2 text-left text-sm font-semibold text-amber-700 transition hover:bg-amber-100 dark:bg-amber-500/15 dark:text-amber-200 dark:hover:bg-amber-500/25">
                                                                <i class="<?php echo e($importanceIcon); ?> w-4 text-center" aria-hidden="true"></i>
                                                                <span><?php echo e($importanceLabel); ?></span>
                                                            </button>
                                                        </form>
                                                        <form method="post" action="<?php echo e($notificationActionUrl); ?>">
                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                            <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                                                            <input type="hidden" name="action" value="<?php echo e($archiveAction); ?>">
                                                            <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                                                            <input type="hidden" name="space" value="teacher">
                                                            <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                                                            <?php if ($q !== ''): ?>
                                                                <input type="hidden" name="q" value="<?php echo e($q); ?>">
                                                            <?php endif; ?>
                                                            <button type="submit" class="mt-1 flex w-full items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:bg-slate-500/15 dark:text-slate-200 dark:hover:bg-slate-500/25">
                                                                <i class="fas <?php echo e($archiveIcon); ?> w-4 text-center" aria-hidden="true"></i>
                                                                <span>Archiver</span>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="post" action="<?php echo e($notificationActionUrl); ?>" class="mt-2 border-t border-gray-200 pt-2 dark:border-gray-700" data-confirm-delete="Supprimer cette notification ?">
                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                        <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                                                        <input type="hidden" name="space" value="teacher">
                                                        <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                                                        <?php if ($q !== ''): ?>
                                                            <input type="hidden" name="q" value="<?php echo e($q); ?>">
                                                        <?php endif; ?>
                                                        <button type="submit" class="flex w-full items-center gap-2 rounded-xl bg-rose-50 px-3 py-2 text-left text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-200 dark:hover:bg-rose-500/25">
                                                            <i class="fas fa-trash w-4 text-center" aria-hidden="true"></i>
                                                            <span>Supprimer</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </details>
                                        </div>
                                    </div>

                                    <div class="mt-3 pl-0 sm:mt-2.5 sm:pl-[3.25rem]">
                                        <?php if ($hasOpenAction): ?>
                                            <a class="<?php echo e($openButtonClass); ?> inline-flex w-full items-center justify-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition sm:w-auto sm:py-1.5" href="<?php echo e($openUrl); ?>">
                                                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                                <span>Ouvrir</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="teacher-notifications-sidebar space-y-4 bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 sm:p-5">
                    <section class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/45">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-bold text-gray-900 dark:text-white">Aperçu</h3>
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"><?php echo e($formatCount($notificationTotalCount)); ?> total</span>
                        </div>
                        <div class="mt-4 grid gap-2">
                            <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-sm shadow-sm dark:bg-slate-800">
                                <span class="text-gray-500 dark:text-gray-400">Non lues</span>
                                <span class="font-semibold text-red-500"><?php echo e($formatCount($unreadCount)); ?></span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-sm shadow-sm dark:bg-slate-800">
                                <span class="text-gray-500 dark:text-gray-400">Lues</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-300"><?php echo e($formatCount($readCount)); ?></span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-sm shadow-sm dark:bg-slate-800">
                                <span class="text-gray-500 dark:text-gray-400">Support</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-300"><?php echo e($formatCount($supportScopeCount)); ?></span>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2 text-sm shadow-sm dark:bg-slate-800">
                                <span class="text-gray-500 dark:text-gray-400">Prioritaires</span>
                                <span class="font-semibold text-amber-600 dark:text-amber-300"><?php echo e($formatCount($importantCount)); ?></span>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            <?php if ($todoCount > 0): ?>
                                <?php echo e((string) $todoCount); ?> élément<?php echo $todoCount > 1 ? 's' : ''; ?> à traiter maintenant.
                            <?php else: ?>
                                Aucun élément à traiter pour le moment.
                            <?php endif; ?>
                        </p>
                    </section>

                    <section class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="font-bold text-gray-900 dark:text-white">Actions rapides</h3>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                            <a href="<?php echo e($dashboardUrl); ?>" class="inline-flex items-center justify-between gap-3 rounded-xl bg-blue-50 px-3.5 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-900/30">
                                <span>Tableau de bord</span>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                            <a href="<?php echo e($teacherSupportUrl); ?>" class="inline-flex items-center justify-between gap-3 rounded-xl bg-cyan-50 px-3.5 py-3 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100 dark:bg-cyan-900/20 dark:text-cyan-300 dark:hover:bg-cyan-900/30">
                                <span>Assistance support</span>
                                <i class="fas fa-headset" aria-hidden="true"></i>
                            </a>
                            <a href="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex items-center justify-between gap-3 rounded-xl bg-emerald-50 px-3.5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/30">
                                <span>Mes formations</span>
                                <i class="fas fa-book-open" aria-hidden="true"></i>
                            </a>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="font-bold text-gray-900 dark:text-white">À surveiller</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <?php if ($pendingMeetingsCount > 0): ?>
                                <?php echo e((string) $pendingMeetingsCount); ?> rendez-vous nécessitent encore votre attention.
                            <?php else: ?>
                                Aucun rendez-vous prioritaire à suivre.
                            <?php endif; ?>
                        </p>
                        <a href="<?php echo e(site_url('teacher/meetings?status=pending')); ?>" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:underline dark:text-blue-300">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                            <span>Voir les rendez-vous</span>
                        </a>
                    </section>
                </aside>
            </section>

            <?php if ($totalPages > 1): ?>
                <?php
                    $start = $totalItems > 0 ? (($page - 1) * $perPage) + 1 : 0;
                    $end = $totalItems > 0 ? min($totalItems, $page * $perPage) : 0;
                ?>
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Affichage de <?php echo e((string) $start); ?> à <?php echo e((string) $end); ?> sur <?php echo e((string) $totalItems); ?> notifications
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="<?php echo e($buildPageUrl(max(1, $page - 1))); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:border-blue-500/30 dark:hover:bg-blue-900/20 dark:hover:text-blue-300<?php echo $page <= 1 ? ' pointer-events-none opacity-50' : ''; ?>">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                <span>Précédent</span>
                            </a>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?php echo e($buildPageUrl($i)); ?>" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-semibold transition <?php echo $i === $page ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:border-blue-500/30 dark:hover:bg-blue-900/20 dark:hover:text-blue-300'; ?>">
                                    <?php echo e((string) $i); ?>
                                </a>
                            <?php endfor; ?>
                            <a href="<?php echo e($buildPageUrl(min($totalPages, $page + 1))); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:border-blue-500/30 dark:hover:bg-blue-900/20 dark:hover:text-blue-300<?php echo $page >= $totalPages ? ' pointer-events-none opacity-50' : ''; ?>">
                                <span>Suivant</span>
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>

