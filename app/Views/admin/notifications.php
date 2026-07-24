<?php
$dashboardUrl = (string) ($dashboardUrl ?? site_url('admin/dashboard'));
$user = is_array($user ?? null) ? $user : [];
$scope = (string) ($scope ?? ($defaultScope ?? 'all'));
$scopeCounts = is_array($scopeCounts ?? null) ? $scopeCounts : [];
$notifications = is_array($notifications ?? null) ? $notifications : [];
$success = (string) ($success ?? '');
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$totalItems = max(0, (int) ($totalItems ?? 0));
$perPage = max(1, (int) ($perPage ?? 10));
 
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$allowedScopes = is_array($allowedScopes ?? null) ? $allowedScopes : ['all', 'unread', 'important', 'support', 'admin'];
$defaultScope = (string) ($defaultScope ?? 'all');
$hasAdminAccess = !empty($hasAdminAccess);

$supportUrl = site_url('admin/support');

$scopeFilters = [
    'all' => 'Toutes',
    'unread' => 'Non lues',
    'important' => 'Importantes',
    'support' => 'Support',
    'admin' => 'Admin',
    'other' => 'Autres',
];
$scopeMeta = [
    'support' => ['label' => 'Support', 'class' => 'bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300'],
    'learning' => ['label' => 'P&eacute;dagogie', 'class' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-300'],
    'business' => ['label' => 'Commercial', 'class' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300'],
    'admin' => ['label' => 'Admin', 'class' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300'],
    'other' => ['label' => 'G&eacute;n&eacute;ral', 'class' => 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300'],
];
$importanceMeta = [
    'info' => ['label' => 'Info', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'],
    'important' => ['label' => 'Important', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
    'urgent' => ['label' => 'Urgent', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'],
];

$baseQuery = $_GET;
unset($baseQuery['scope'], $baseQuery['action'], $baseQuery['notif_id'], $baseQuery['_csrf'], $baseQuery['open'], $baseQuery['page']);

$buildScopeUrl = static function (string $targetScope) use ($baseQuery): string {
    $params = $baseQuery;
    if ($targetScope !== 'all') {
        $params['scope'] = $targetScope;
    }
    $query = http_build_query($params);
    return site_url('admin/notifications') . ($query !== '' ? '?' . $query : '');
};

$buildOpenUrl = static function (int $notificationId) use ($scope): string {
    $params = ['open' => $notificationId];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    $currentPage = max(1, (int) ($_GET['page'] ?? 1));
    if ($currentPage > 1) {
        $params['page'] = $currentPage;
    }
    return site_url('admin/notifications?' . http_build_query($params));
};

$buildPageUrl = static function (int $targetPage) use ($scope): string {
    $targetPage = max(1, $targetPage);
    $params = [];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }
    $query = http_build_query($params);
    return site_url('admin/notifications') . ($query !== '' ? '?' . $query : '');
};

$hasMeaningfulNotificationTarget = static function (array $notification): bool {
    return !empty($notification['has_open_action']);
};

$fmtDt = static function (string $dbValue): string {
    if (!$dbValue) {
        return '';
    }
    $ts = strtotime($dbValue);
    if (!$ts) {
        return (string) $dbValue;
    }
    return date('d/m/Y H:i', $ts);
};

$visibleFilters = [];
foreach ($scopeFilters as $scopeKey => $scopeLabel) {
    if (in_array($scopeKey, $allowedScopes, true)) {
        $visibleFilters[$scopeKey] = $scopeLabel;
    }
}
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
$unreadLabel = $unreadCount === 1 ? '1 non lue' : ($unreadCount . ' non lues');
$notificationFilterButtonClass = static fn (bool $active): string => $active
    ? 'inline-flex items-center gap-2 rounded-full border border-blue-600 bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm shadow-blue-500/20 transition hover:border-blue-700 hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-blue-400 dark:bg-blue-500 dark:hover:border-blue-300 dark:hover:bg-blue-400 dark:focus-visible:ring-offset-slate-950'
    : 'inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:border-blue-500/40 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-offset-slate-950';
$notificationFilterBadgeClass = static fn (bool $active): string => $active
    ? 'inline-flex min-w-[1.45rem] items-center justify-center rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-bold leading-none text-white'
    : 'inline-flex min-w-[1.45rem] items-center justify-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold leading-none text-slate-600 dark:bg-slate-700 dark:text-slate-100';
$notificationFilterIconClass = static fn (string $scopeKey, bool $active): string => $active
    ? 'text-white'
    : ($scopeKey === 'support'
        ? 'text-sky-500 dark:text-sky-300'
        : ($scopeKey === 'important'
            ? 'text-amber-500 dark:text-amber-300'
            : ($scopeKey === 'admin'
                ? 'text-rose-500 dark:text-rose-300'
                : 'text-primary-500 dark:text-primary-300')));
$adminPageScripts = ['notifications_page.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications administrateur - &Eacute;cole Num&eacute;rique</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/notifications" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php $adminNavActive = 'notifications'; ?>
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php
$adminTopTitle = 'Notifications';
$adminTopSubtitle = $hasAdminAccess ? 'Administration' : 'Support';
$adminTopBadge = $unreadCount > 0 ? $unreadLabel : 'Aucune alerte';
$adminTopBadgeTone = $unreadCount > 0 ? 'urgent' : 'done';
            ?>
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense admin-notifications admin-notifications-inspired px-4 py-5 pb-32 sm:px-6 sm:pb-24 space-y-5">
                <?php if ($success !== ''): ?>
                    <section class="admin-shell-alert admin-shell-alert--success">
                        <i class="fas fa-check-circle mr-1" aria-hidden="true"></i><?php echo e($success); ?>
                    </section>
                <?php endif; ?>

                <section class="admin-notifications-filter sticky top-2 z-20 overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-r from-white via-slate-50 to-sky-50/70 p-3 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
                    <div class="flex flex-wrap items-center gap-2">
                        <?php foreach ($visibleFilters as $scopeKey => $scopeLabel): ?>
                            <?php
                                $active = $scope === $scopeKey;
                                $count = (int) ($scopeCounts[$scopeKey] ?? 0);
                                $buttonClass = $notificationFilterButtonClass($active);
                                $badgeClass = $notificationFilterBadgeClass($active);
                                $iconClass = $notificationFilterIconClass($scopeKey, $active);
                            ?>
                            <a href="<?php echo e($buildScopeUrl($scopeKey)); ?>" class="<?php echo e($buttonClass); ?>" aria-current="<?php echo $active ? 'page' : 'false'; ?>">
                                <i class="fas <?php echo e($scopeKey === 'support' ? 'fa-headset' : ($scopeKey === 'important' ? 'fa-exclamation-triangle' : ($scopeKey === 'admin' ? 'fa-user-shield' : ($scopeKey === 'unread' ? 'fa-envelope' : 'fa-bell')))); ?> <?php echo e($iconClass); ?>" aria-hidden="true"></i>
                                <span><?php echo e($scopeLabel); ?></span>
                                <span class="<?php echo e($badgeClass); ?>"><?php echo e((string) $count); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if ($notifications === []): ?>
                    <section class="admin-notifications-empty rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                        <div class="max-w-md space-y-3">
                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                <i class="fas fa-bell text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                <p class="text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Aucune notification pour ce filtre</p>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Vous &ecirc;tes &agrave; jour. Revenez plus tard ou contactez le support.</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <a class="app-btn-primary inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold" href="<?php echo e($supportUrl); ?>">
                                    <i class="fas fa-life-ring" aria-hidden="true"></i>
                                    <span>Contacter le support</span>
                                </a>
                                <a class="app-segmented-link inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold" href="<?php echo e($dashboardUrl); ?>">
                                    <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                                    <span>Retour au tableau de bord</span>
                                </a>
                            </div>
                        </div>
                    </section>
                <?php else: ?>
                    <div class="admin-notifications-list mt-5 grid gap-3 list-none p-0 m-0">
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                                $notificationId = (int) ($notification['id'] ?? 0);
                                $notifScope = (string) ($notification['scope_key'] ?? 'other');
                                $scopeInfo = $scopeMeta[$notifScope] ?? $scopeMeta['other'];
                                $notifCreated = $fmtDt((string) ($notification['created_at'] ?? ''));
                                $notifTitle = (string) ($notification['title'] ?? 'Notification');
                                $notifBody = (string) ($notification['body'] ?? '');
                                $notifSummary = trim((string) ($notification['summary'] ?? ''));
                                $notifImportance = strtolower((string) ($notification['importance'] ?? 'info'));
                                $importanceInfo = $importanceMeta[$notifImportance] ?? $importanceMeta['info'];
                                $notifIsRead = !empty($notification['is_read']);
                                $openUrl = $notificationId > 0 ? $buildOpenUrl($notificationId) : site_url('admin/notifications');
                                $hasOpenAction = $notificationId > 0 && $hasMeaningfulNotificationTarget($notification);
                                $toggleAction = $notifIsRead ? 'mark_unread' : 'mark_read';
                                $toggleLabel = $notifIsRead ? 'Marquer comme non lue' : 'Marquer comme lue';
                                $stateLabel = $notifIsRead ? 'Lue' : 'Non lue';
                                $stateClass = $notifIsRead
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-200'
                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-900/35 dark:text-rose-200';
                                $scopeLabel = (string) ($scopeInfo['label'] ?? 'Général');
                                $showScopeBadge = $scopeLabel !== '' && stripos($notifTitle, $scopeLabel) === false;
                                $notifIsPriority = in_array($notifImportance, ['important', 'urgent'], true);
                                $toneInfo = $scopeToneMeta[$notifScope] ?? $scopeToneMeta['other'];
                                $cardToneClass = (string) ($toneInfo['card'] ?? $scopeToneMeta['other']['card']);
                                $iconToneClass = (string) ($toneInfo['icon'] ?? $scopeToneMeta['other']['icon']);
                                $openButtonClass = (string) ($toneInfo['open'] ?? $scopeToneMeta['other']['open']);
                                $toggleButtonClass = $notifIsRead
                                    ? 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/15 dark:text-indigo-200 dark:hover:bg-indigo-500/25'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-200 dark:hover:bg-emerald-500/25';
                                $articleClass = 'admin-notifications-card relative rounded-2xl border p-3 transition sm:p-3.5 ' . $cardToneClass . ' shadow-sm';
                                if (!$notifIsRead) {
                                    $articleClass .= ' ring-1 ring-blue-300/60 dark:ring-blue-400/30';
                                } elseif ($notifIsPriority) {
                                    $articleClass .= ' ring-1 ring-amber-300/60 dark:ring-amber-400/30';
                                }
                            ?>
                            <article class="<?php echo e($articleClass); ?>" data-status="<?php echo $notifIsRead ? 'read' : 'unread'; ?>">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex min-w-0 flex-1 items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl <?php echo e($iconToneClass); ?>">
                                            <i class="fas <?php echo e($notifScope === 'support' ? 'fa-headset' : ($notifScope === 'admin' ? 'fa-user-shield' : ($notifScope === 'business' ? 'fa-briefcase' : ($notifScope === 'learning' ? 'fa-graduation-cap' : 'fa-bell')))); ?>" aria-hidden="true"></i>
                                        </div>
                                        <div class="min-w-0 space-y-1.5">
                                            <div class="flex flex-wrap items-start gap-x-2 gap-y-1">
                                                <p class="text-sm font-semibold leading-snug text-gray-900 dark:text-gray-100"><?php echo e($notifTitle); ?></p>
                                                <?php if ($showScopeBadge): ?>
                                                    <span class="<?php echo e((string) ($scopeInfo['class'] ?? 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300')); ?> inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"><?php echo e($scopeLabel); ?></span>
                                                <?php endif; ?>
                                                <?php if ($notifIsPriority): ?>
                                                    <span class="<?php echo e((string) $importanceInfo['class']); ?> inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"><?php echo e((string) $importanceInfo['label']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!$notifIsRead): ?>
                                                    <span class="<?php echo e($stateClass); ?> inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"><?php echo e($stateLabel); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($notifSummary !== ''): ?>
                                                <p class="notif-message-body text-sm text-gray-600 dark:text-gray-300" data-linkify="true"><?php echo e($notifSummary); ?></p>
                                            <?php endif; ?>
                                            <?php if ($notifBody !== '' && $notifBody !== $notifSummary): ?>
                                                <div class="notif-message-body mt-1 text-xs text-gray-500 dark:text-gray-400" data-linkify="true"><?php echo format_announcement_body($notifBody); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="min-w-0 pt-0 text-xs leading-tight text-gray-500 dark:text-gray-400 sm:pt-1 sm:text-right"><?php echo e($notifCreated !== '' ? $notifCreated : 'Date inconnue'); ?></p>
                                </div>

                                <div class="mt-3 pl-0 sm:mt-2.5 sm:pl-[3.25rem]">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                                        <?php if ($hasOpenAction): ?>
                                            <a class="<?php echo e($openButtonClass); ?> inline-flex w-full items-center justify-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition sm:w-auto sm:py-1.5" href="<?php echo e($openUrl); ?>">
                                                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                                <span>Ouvrir</span>
                                            </a>
                                        <?php endif; ?>
                                        <form method="post" action="<?php echo e(site_url('admin/notifications')); ?>" class="w-full sm:w-auto">
                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                            <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                                            <input type="hidden" name="action" value="<?php echo e($toggleAction); ?>">
                                            <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                                            <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                                            <button class="<?php echo e($toggleButtonClass); ?> inline-flex w-full items-center justify-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition sm:w-auto sm:py-1.5" type="submit">
                                                <i class="fas <?php echo $notifIsRead ? 'fa-redo-alt' : 'fa-check-circle'; ?>" aria-hidden="true"></i>
                                                <span><?php echo e($toggleLabel); ?></span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav class="notif-pagination admin-notifications-pagination flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/70 bg-white/90 p-3 dark:border-slate-800 dark:bg-slate-950/40" aria-label="Pagination notifications">
                            <p class="text-xs text-slate-600 dark:text-slate-300">
                                <i class="fas fa-list-ol mr-1" aria-hidden="true"></i>Page <?php echo e((string) $page); ?> / <?php echo e((string) $totalPages); ?> - <?php echo e($totalLabel); ?>
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo e($buildPageUrl($page - 1)); ?>" class="app-segmented-link rounded-lg px-3 py-1.5 text-xs font-semibold"><i class="fas fa-chevron-left mr-1" aria-hidden="true"></i>Pr&eacute;c&eacute;dent</a>
                                <?php endif; ?>
                                <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                ?>
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="app-segmented-link app-segmented-link--active rounded-lg px-3 py-1.5 text-xs font-semibold"><?php echo e((string) $i); ?></span>
                                    <?php else: ?>
                                        <a href="<?php echo e($buildPageUrl($i)); ?>" class="app-segmented-link rounded-lg px-3 py-1.5 text-xs font-semibold"><?php echo e((string) $i); ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="<?php echo e($buildPageUrl($page + 1)); ?>" class="app-segmented-link rounded-lg px-3 py-1.5 text-xs font-semibold">Suivant<i class="fas fa-chevron-right ml-1" aria-hidden="true"></i></a>
                                <?php endif; ?>
                            </div>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

</body>
</html>

