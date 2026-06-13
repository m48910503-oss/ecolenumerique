<?php
$dashboardUrl = trim((string) ($dashboardUrl ?? ''));
if ($dashboardUrl === '') {
    $dashboardUrl = site_url('company/dashboard');
}
$user = is_array($user ?? null) ? $user : [];
$scope = (string) ($scope ?? 'all');
$scopeCounts = is_array($scopeCounts ?? null) ? $scopeCounts : [];
$notifications = is_array($notifications ?? null) ? $notifications : [];
$success = (string) ($success ?? '');
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$totalItems = max(0, (int) ($totalItems ?? 0));
$perPage = max(1, (int) ($perPage ?? 10));
 
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$siteLogoUrl = (string) ($siteLogoUrl ?? '');

$supportUrl = site_url('support');
$companyHelpUrl = site_url('company/help');
$profileUrl = site_url('profile');
$catalogueUrl = site_url('catalogue');
$logoutUrl = site_url('logout');

$scopeFilters = [
    'all' => 'Toutes',
    'unread' => 'Non lues',
    'important' => 'Importantes',
    'support' => 'Support',
    'learning' => 'P&eacute;dagogie',
    'business' => 'Commercial',
    'admin' => 'Administration',
    'other' => 'Autres',
];
$scopeMeta = [
    'support' => ['label' => 'Support', 'class' => 'bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300'],
    'learning' => ['label' => 'P&eacute;dagogie', 'class' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-300'],
    'business' => ['label' => 'Commercial', 'class' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300'],
    'admin' => ['label' => 'Administration', 'class' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300'],
    'other' => ['label' => 'G&eacute;n&eacute;ral', 'class' => 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300'],
];
$importanceMeta = [
    'info' => ['label' => 'Info', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'],
    'important' => ['label' => 'Important', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
    'urgent' => ['label' => 'Urgent', 'class' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'],
];
$scopeIcons = [
    'all' => 'bell',
    'unread' => 'bell',
    'important' => 'bell',
    'support' => 'bell',
    'learning' => 'bell',
    'business' => 'bell',
    'admin' => 'bell',
    'other' => 'bell',
];

$icon = static function (string $name): string {
    return function_exists('dashboard_icon_svg') ? (string) dashboard_icon_svg($name) : '';
};

$baseQuery = $_GET;
unset($baseQuery['scope'], $baseQuery['action'], $baseQuery['notif_id'], $baseQuery['_csrf'], $baseQuery['open'], $baseQuery['page']);

$buildScopeUrl = static function (string $targetScope) use ($baseQuery): string {
    $params = $baseQuery;
    if ($targetScope !== 'all') {
        $params['scope'] = $targetScope;
    }
    $query = http_build_query($params);
    return site_url('notifications') . ($query !== '' ? '?' . $query : '');
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
    return site_url('notifications?' . http_build_query($params));
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
    return site_url('notifications') . ($query !== '' ? '?' . $query : '');
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
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Notifications entreprise - École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/notifications_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/notifications_page.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="standalone-dashboard-page app-view app-role-company min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body" data-view="company/notifications">
<?php

$companyTopEyebrow = 'Centre de notifications';
$companyTopTitle = 'Notifications entreprise';
$companyTopSummary = "Filtrez vos alertes, traitez l’important en premier et gardez une lecture plus nette des priorités.";
$companyTopStatusLabel = $unreadCount > 0 ? 'À traiter' : 'À jour';
$companyTopStatusTone = $unreadCount > 0 ? 'warning' : 'active';
$companyTopMeta = [
    ['label' => view_pluralize($totalItems, 'notification'), 'tone' => 'done'],
    ['label' => view_pluralize($unreadCount, 'non lue', 'non lues'), 'tone' => $unreadCount > 0 ? 'pending' : 'active'],
];
$companyTopActions = [
    ['label' => 'Notifications', 'url' => site_url('notifications'), 'icon' => 'bell', 'badge' => $unreadCount, 'current' => true],
    ['label' => 'Guide', 'url' => $companyHelpUrl, 'icon' => 'book-open'],
    ['label' => 'Catalogue', 'url' => $catalogueUrl, 'icon' => 'layout-grid'],
];
$companyTopPrimaryUrl = $dashboardUrl;
$companyTopPrimaryLabel = 'Tableau de bord';
$companyNotificationsUrl = site_url('notifications');

require __DIR__ . '/_topbar.php';
?>
    <main class="company-content-main max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-5">
        <section class="rounded-3xl border border-slate-200/70 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl border border-slate-200 bg-white flex items-center justify-center overflow-hidden dark:border-slate-700 dark:bg-slate-900">
                        <?php if ($siteLogoUrl !== ''): ?>
                            <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain">
                        <?php else: ?>
                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-200">ENT</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Espace entreprise</p>
                        <h1 class="text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Centre de notifications</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo e((string) $unreadCount); ?> non lues</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="post" action="<?php echo e(site_url('notifications')); ?>">
                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                        <input type="hidden" name="action" value="mark_all_read">
                        <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                        <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                        <button class="inline-flex items-center gap-2 rounded-full border border-blue-600 bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-500/20 transition hover:border-blue-700 hover:bg-blue-700 dark:border-blue-400 dark:bg-blue-500 dark:hover:bg-blue-400" type="submit">
                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                            <span>Tout marquer comme lu</span>
                        </button>
                    </form>
                    <a class="app-segmented-link inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold" href="<?php echo e($supportUrl); ?>">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('lifebuoy'); ?></span>
                        <span>Support</span>
                    </a>
                    <a class="app-segmented-link inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold" href="<?php echo e($dashboardUrl); ?>">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span>
                        <span>Tableau de bord</span>
                    </a>
                </div>
            </div>
        </section>

        <?php if ($success !== ''): ?>
            <section class="app-feedback app-feedback--success text-sm" role="status" aria-live="polite">
                <?php echo e($success); ?>
            </section>
        <?php endif; ?>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="app-kpi-card app-kpi-card--blue rounded-2xl border p-4">
                <p class="app-kpi-card__head">
                    <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('bell'); ?></span>
                    <span>Total</span>
                </p>
                <p class="app-kpi-card__value text-2xl"><?php echo e((string) (int) ($scopeCounts['all'] ?? 0)); ?></p>
                <p class="app-kpi-card__meta">Alertes entreprise</p>
            </article>
            <article class="app-kpi-card app-kpi-card--rose rounded-2xl border p-4">
                <p class="app-kpi-card__head">
                    <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('bell'); ?></span>
                    <span>Non lues</span>
                </p>
                <p class="app-kpi-card__value text-2xl"><?php echo e((string) (int) ($scopeCounts['unread'] ?? $unreadCount)); ?></p>
                <p class="app-kpi-card__meta">Prioritaires</p>
            </article>
            <article class="app-kpi-card app-kpi-card--amber rounded-2xl border p-4">
                <p class="app-kpi-card__head">
                    <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('bell'); ?></span>
                    <span>Commercial</span>
                </p>
                <p class="app-kpi-card__value text-2xl"><?php echo e((string) (int) ($scopeCounts['business'] ?? 0)); ?></p>
                <p class="app-kpi-card__meta">Achats &amp; facturation</p>
            </article>
            <article class="app-kpi-card app-kpi-card--sky rounded-2xl border p-4">
                <p class="app-kpi-card__head">
                    <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('bell'); ?></span>
                    <span>Support</span>
                </p>
                <p class="app-kpi-card__value text-2xl"><?php echo e((string) max($supportUnreadCount, (int) ($scopeCounts['support'] ?? 0))); ?></p>
                <p class="app-kpi-card__meta">En attente</p>
            </article>
        </section>

        <section class="sticky top-2 z-20 rounded-2xl border border-slate-200/80 bg-white/95 p-2 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
            <div class="flex flex-wrap items-center gap-2">
                <?php foreach ($scopeFilters as $scopeKey => $scopeLabel): ?>
                    <?php
                        $active = $scope === $scopeKey;
                        $count = (int) ($scopeCounts[$scopeKey] ?? 0);
                        $scopeIcon = $scopeIcons[$scopeKey] ?? 'bell';
                    ?>
                    <a href="<?php echo e($buildScopeUrl($scopeKey)); ?>" class="app-segmented-link inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold<?php echo $active ? ' app-segmented-link--active' : ''; ?>">
                        <span class="app-icon" aria-hidden="true"><?php echo $icon($scopeIcon); ?></span>
                        <span><?php echo e($scopeLabel); ?></span>
                        <span class="app-shell-chip__badge"><?php echo e((string) $count); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($notifications === []): ?>
            <section class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                <div class="max-w-md space-y-3">
                    <p class="text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Aucune notification pour ce filtre</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Vous &ecirc;tes &agrave; jour. Revenez plus tard ou contactez le support.</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <a class="app-btn-primary rounded-lg px-3 py-2 text-xs font-semibold" href="<?php echo e($supportUrl); ?>">Contacter le support</a>
                        <a class="app-segmented-link rounded-lg px-3 py-2 text-xs font-semibold" href="<?php echo e($dashboardUrl); ?>">Retour au tableau de bord</a>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <ul class="space-y-3 list-none p-0 m-0">
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
                        $openUrl = $notificationId > 0 ? $buildOpenUrl($notificationId) : site_url('notifications');
                        $hasOpenAction = $notificationId > 0 && $hasMeaningfulNotificationTarget($notification);
                        $toggleAction = $notifIsRead ? 'mark_unread' : 'mark_read';
                        $toggleLabel = $notifIsRead ? 'Marquer comme non lue' : 'Marquer comme lue';
                        $stateLabel = $notifIsRead ? 'Lue' : 'Non lue';
                        $scopeIcon = $scopeIcons[$notifScope] ?? 'bell';
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
                        $articleClass = 'company-notifications-card relative rounded-2xl border p-3 transition sm:p-3.5 ' . $cardToneClass . ' shadow-sm';
                        if (!$notifIsRead) {
                            $articleClass .= ' ring-1 ring-blue-300/60 dark:ring-blue-400/30';
                        } elseif ($notifIsPriority) {
                            $articleClass .= ' ring-1 ring-amber-300/60 dark:ring-amber-400/30';
                        }
                    ?>
                    <li class="<?php echo e($articleClass); ?>" data-status="<?php echo $notifIsRead ? 'read' : 'unread'; ?>">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl <?php echo e($iconToneClass); ?>">
                                    <span class="app-icon" aria-hidden="true"><?php echo $icon($scopeIcon); ?></span>
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
                                        <div class="notif-message-body mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo format_announcement_body($notifBody); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="min-w-0 pt-0 text-xs leading-tight text-gray-500 dark:text-gray-400 sm:pt-1 sm:text-right"><?php echo e($notifCreated !== '' ? $notifCreated : 'Date inconnue'); ?></p>
                        </div>

                        <div class="mt-3 pl-0 sm:mt-2.5 sm:pl-[3.25rem]">
                            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                                <?php if ($hasOpenAction): ?>
                                    <a class="<?php echo e($openButtonClass); ?> inline-flex w-full items-center justify-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition sm:w-auto sm:py-1.5" href="<?php echo e($openUrl); ?>">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                        <span>Ouvrir</span>
                                    </a>
                                <?php endif; ?>
                                <form method="post" action="<?php echo e(site_url('notifications')); ?>" class="w-full sm:w-auto">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                                    <input type="hidden" name="action" value="<?php echo e($toggleAction); ?>">
                                    <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                                    <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                                    <button class="<?php echo e($toggleButtonClass); ?> inline-flex w-full items-center justify-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition sm:w-auto sm:py-1.5" type="submit">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($notifIsRead ? 'repeat' : 'check'); ?></span>
                                        <span><?php echo e($toggleLabel); ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($totalPages > 1): ?>
                <nav class="notif-pagination flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/70 bg-white/90 p-3 dark:border-slate-800 dark:bg-slate-950/40" aria-label="Pagination notifications">
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                        Page <?php echo e((string) $page); ?> / <?php echo e((string) $totalPages); ?> - <?php echo e((string) $totalItems); ?> notification(s)
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo e($buildPageUrl($page - 1)); ?>" class="app-segmented-link rounded-lg px-3 py-1.5 text-xs font-semibold">Pr&eacute;c&eacute;dent</a>
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
                            <a href="<?php echo e($buildPageUrl($page + 1)); ?>" class="app-segmented-link rounded-lg px-3 py-1.5 text-xs font-semibold">Suivant</a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>




