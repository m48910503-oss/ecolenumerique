<?php
$dashboardUrl = (string) ($dashboardUrl ?? site_url(''));
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
$dashboardSpace = trim((string) ($dashboardSpace ?? ''));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$useDashboardShell = dashboard_uses_shell_space($dashboardSpace);
$isTeacherNotifications = $dashboardSpace === 'teacher';

if (strtolower(trim((string) ($_GET['space'] ?? ''))) === 'student' || $dashboardSpace === 'student') {
    require __DIR__ . '/../student/notifications.php';
    return;
}
if (strtolower(trim((string) ($_GET['space'] ?? ''))) === 'teacher' || $dashboardSpace === 'teacher') {
    require __DIR__ . '/../teacher/notifications.php';
    return;
}
if (strtolower(trim((string) ($_GET['space'] ?? ''))) === 'company' || $dashboardSpace === 'company') {
    require __DIR__ . '/../company/notifications.php';
    return;
}

$scopeFilters = [
    'all' => 'Toutes',
    'unread' => 'Non lues',
    'important' => 'Importantes',
    'support' => 'Support',
    'learning' => 'Pédagogie',
    'business' => 'Commercial',
    'admin' => 'Admin',
    'other' => 'Autres',
];
$scopeMeta = [
    'support' => ['label' => 'Support', 'class' => 'bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300'],
    'learning' => ['label' => 'Pédagogie', 'class' => 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-300'],
    'business' => ['label' => 'Commercial', 'class' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300'],
    'admin' => ['label' => 'Admin', 'class' => 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300'],
    'other' => ['label' => 'Général', 'class' => 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300'],
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

$appendSpace = static function (string $url) use ($dashboardSpace): string {
    if ($dashboardSpace === '') {
        return $url;
    }

    return $url . (str_contains($url, '?') ? '&' : '?') . 'space=' . urlencode($dashboardSpace);
};

$pageContextLabel = 'Centre personnel';
if ($dashboardSpace === 'teacher') {
    $dashboardUrl = site_url('teacher/dashboard');
    $pageContextLabel = 'Espace enseignant';
} elseif ($dashboardSpace === 'student') {
    $dashboardUrl = site_url('student/dashboard');
    $pageContextLabel = 'Espace étudiant';
} elseif ($dashboardUrl === '') {
    $dashboardUrl = site_url('');
}

$supportUrl = $appendSpace(site_url('support'));
$dashboardUrl = $appendSpace($dashboardUrl);
if ($dashboardSpace !== '') {
    $dashboardUrl = dashboard_dashboard_url_for_user($user, $dashboardSpace);
    $pageContextLabel = dashboard_space_label($dashboardSpace);
}
$mainClass = 'notifications-page max-w-6xl mx-auto px-4 sm:px-6 py-6 space-y-5';
if ($dashboardSpace === 'teacher') {
    $mainClass = 'notifications-page w-full px-0 sm:px-0 py-6 space-y-5';
}
if ($useDashboardShell) {
    $mainClass = ($dashboardSpace === 'teacher' ? 'teacher-hub-main ' : 'student-content-main ') . $mainClass;
}

$baseQuery = $_GET;
unset($baseQuery['scope'], $baseQuery['action'], $baseQuery['notif_id'], $baseQuery['_csrf'], $baseQuery['open'], $baseQuery['page']);

$buildScopeUrl = static function (string $targetScope) use ($baseQuery, $dashboardSpace): string {
    $params = $baseQuery;
    if ($dashboardSpace !== '') {
        $params['space'] = $dashboardSpace;
    }
    if ($targetScope !== 'all') {
        $params['scope'] = $targetScope;
    }
    $query = http_build_query($params);
    return site_url('notifications') . ($query !== '' ? '?' . $query : '');
};

$buildOpenUrl = static function (int $notificationId) use ($scope, $dashboardSpace): string {
    $params = ['open' => $notificationId];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    if ($dashboardSpace !== '') {
        $params['space'] = $dashboardSpace;
    }
    $currentPage = max(1, (int) ($_GET['page'] ?? 1));
    if ($currentPage > 1) {
        $params['page'] = $currentPage;
    }
    return site_url('notifications?' . http_build_query($params));
};

$hasMeaningfulNotificationTarget = static function (array $notification): bool {
    return !empty($notification['has_open_action']);
};

$buildPageUrl = static function (int $targetPage) use ($scope, $dashboardSpace): string {
    $targetPage = max(1, $targetPage);
    $params = [];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    if ($dashboardSpace !== '') {
        $params['space'] = $dashboardSpace;
    }
    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }
    $query = http_build_query($params);
    return site_url('notifications') . ($query !== '' ? '?' . $query : '');
};

$fmtDt = static function (string $dbValue): string {
    if ($dbValue === '') {
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

$buildMarkAllReadAction = static function (string $buttonClass) use ($scope, $dashboardSpace, $page, $icon): string {
    ob_start();
    ?>
    <form method="post" action="<?php echo e(site_url('notifications')); ?>">
        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
        <input type="hidden" name="action" value="mark_all_read">
        <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
        <input type="hidden" name="space" value="<?php echo e($dashboardSpace); ?>">
        <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
        <button class="<?php echo e($buttonClass); ?>" type="submit">
            <span class="app-icon" aria-hidden="true"><?php echo $icon('check-circle'); ?></span>
            <span>Tout marquer comme lu</span>
        </button>
    </form>
    <?php
    return trim((string) ob_get_clean());
};

$pageHeroActionsHtml = '';
if (!$isTeacherNotifications) {
    ob_start();
    echo $buildMarkAllReadAction('inline-flex items-center gap-2 rounded-full border border-blue-600 bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-500/20 transition hover:border-blue-700 hover:bg-blue-700 dark:border-blue-400 dark:bg-blue-500 dark:hover:bg-blue-400');
    ?>
    <a class="app-segmented-link inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold" href="<?php echo e($supportUrl); ?>">
        <span class="app-icon" aria-hidden="true"><?php echo $icon('lifebuoy'); ?></span>
        <span>Assistance</span>
    </a>
    <a class="app-segmented-link inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold" href="<?php echo e($dashboardUrl); ?>">
        <span class="app-icon" aria-hidden="true"><?php echo $icon('layout'); ?></span>
        <span>Tableau de bord</span>
    </a>
    <?php
    $pageHeroActionsHtml = trim((string) ob_get_clean());
}

ob_start();
?>
<div class="notifications-meta">
    <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-600 dark:bg-sky-900/30 dark:text-sky-300"><?php echo e($pageContextLabel); ?></span>
    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/30 dark:text-rose-200"><?php echo e((string) $unreadCount); ?> non lues</span>
    <?php if ($supportUnreadCount > 0): ?>
        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"><?php echo e((string) $supportUnreadCount); ?> côté support</span>
    <?php endif; ?>
</div>
<?php
$pageHeroAsideHtml = trim((string) ob_get_clean());

ob_start();
?>
<div class="app-page-hero__stats">
    <div class="app-page-hero__stat">
        <p class="app-page-hero__stat-label">Total</p>
        <p class="app-page-hero__stat-value"><?php echo e((string) (int) ($scopeCounts['all'] ?? 0)); ?></p>
    </div>
    <div class="app-page-hero__stat">
        <p class="app-page-hero__stat-label">Prioritaires</p>
        <p class="app-page-hero__stat-value"><?php echo e((string) (int) ($scopeCounts['important'] ?? 0)); ?></p>
    </div>
    <div class="app-page-hero__stat">
        <p class="app-page-hero__stat-label">Support</p>
        <p class="app-page-hero__stat-value"><?php echo e((string) max($supportUnreadCount, (int) ($scopeCounts['support'] ?? 0))); ?></p>
    </div>
</div>
<?php
$pageHeroFooterHtml = trim((string) ob_get_clean());
if ($isTeacherNotifications) {
    $pageHeroFooterHtml = '';
}
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Notifications - École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/notifications_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/notifications_page.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<?php if ($useDashboardShell): ?>
    <?php $dashboardShellSpace = $dashboardSpace; ?>
    <?php $dashboardShellNavActive = 'notifications'; ?>
    <?php $dashboardShellTopTitle = 'Notifications'; ?>
    <?php $dashboardShellTopSubtitle = $pageContextLabel; ?>
    <?php $dashboardShellBodyClass = $dashboardSpace === 'teacher' ? 'app-view app-role-teacher min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 text-slate-900 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-slate-100 font-body' : 'app-view app-role-student min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body'; ?>
    <?php $dashboardShellDataView = $dashboardSpace . '/notifications'; ?>
    <?php require __DIR__ . '/../dashboard/_shell_start.php'; ?>
<?php else: ?>
<body data-view="web/notifications" class="standalone-dashboard-page app-view app-role-web min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body">
<?php endif; ?>
    <main class="<?php echo e($mainClass); ?>">
        <?php if (!$isTeacherNotifications): ?>
        <?php
            $pageHeroEyebrow = $pageContextLabel;
            $pageHeroTitle = $dashboardSpace === 'teacher' ? 'Notifications' : 'Centre de notifications';
            $pageHeroSummary = $dashboardSpace === 'teacher'
                ? 'Retrouvez vos alertes, filtrez rapidement ce qui demande une action et laissez les simples informations en lecture.'
                : 'Retrouvez vos alertes, hiérarchisez-les par priorité et traitez rapidement ce qui demande une action.';
            require __DIR__ . '/../shared/_page_hero.php';
        ?>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <section class="app-feedback app-feedback--success text-sm" role="status" aria-live="polite">
                <?php echo e($success); ?>
            </section>
        <?php endif; ?>

        <?php if (!$isTeacherNotifications): ?>
        <section class="app-summary-strip app-summary-strip--4 notifications-summary-strip">
            <article class="app-summary-card app-summary-card--info">
                <p class="app-summary-card__label">Total</p>
                <p class="app-summary-card__value"><?php echo e((string) (int) ($scopeCounts['all'] ?? 0)); ?></p>
                <p class="app-summary-card__meta">Historique disponible</p>
            </article>
            <article class="app-summary-card app-summary-card--danger">
                <p class="app-summary-card__label">Non lues</p>
                <p class="app-summary-card__value"><?php echo e((string) (int) ($scopeCounts['unread'] ?? $unreadCount)); ?></p>
                <p class="app-summary-card__meta">À traiter en priorité</p>
            </article>
            <article class="app-summary-card app-summary-card--warning">
                <p class="app-summary-card__label">Pédagogie</p>
                <p class="app-summary-card__value"><?php echo e((string) (int) ($scopeCounts['learning'] ?? 0)); ?></p>
                <p class="app-summary-card__meta">Cours, évaluations et suivi</p>
            </article>
            <article class="app-summary-card app-summary-card--success">
                <p class="app-summary-card__label">Support</p>
                <p class="app-summary-card__value"><?php echo e((string) max($supportUnreadCount, (int) ($scopeCounts['support'] ?? 0))); ?></p>
                <p class="app-summary-card__meta">Demandes ouvertes</p>
            </article>
        </section>
        <?php endif; ?>

        <section class="app-page-section-card notifications-scope-card">
            <?php
                $sectionHeadEyebrow = 'Filtres';
                $sectionHeadTitle = 'Affiner l’affichage';
                $sectionHeadDesc = 'Choisissez un prisme de lecture pour réduire le bruit et mettre en avant les notifications qui comptent.';
                ob_start();
            ?>
                <?php if (!$isTeacherNotifications): ?>
                    <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-600 dark:bg-sky-900/30 dark:text-sky-300"><?php echo e($scopeFilters[$scope] ?? 'Toutes'); ?></span>
                    <span class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?php echo e((string) $totalItems); ?> élément(s)</span>
                <?php endif; ?>
            <?php
                $sectionHeadMetaHtml = trim((string) ob_get_clean());
                $sectionHeadActionsHtml = '';
                require __DIR__ . '/../shared/_section_head.php';
            ?>
            <div class="notifications-scope-links">
                <?php foreach ($scopeFilters as $scopeKey => $scopeLabel): ?>
                    <?php $active = $scope === $scopeKey; ?>
                    <a href="<?php echo e($buildScopeUrl($scopeKey)); ?>" class="app-segmented-link inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold<?php echo $active ? ' app-segmented-link--active' : ''; ?>">
                        <span class="app-icon" aria-hidden="true"><?php echo $icon($scopeIcons[$scopeKey] ?? 'bell'); ?></span>
                        <span><?php echo e($scopeLabel); ?></span>
                        <span class="app-shell-chip__badge"><?php echo e((string) (int) ($scopeCounts[$scopeKey] ?? 0)); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="app-page-section-card">
            <?php
                $sectionHeadEyebrow = 'Boîte de réception';
                $sectionHeadTitle = 'Notifications';
                $sectionHeadDesc = $scope === 'all'
                    ? 'Consultez l’ensemble des événements récents.'
                    : 'Affichage filtré : ' . (string) ($scopeFilters[$scope] ?? 'Notifications');
                ob_start();
            ?>
                <span class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Page <?php echo e((string) $page); ?> / <?php echo e((string) $totalPages); ?></span>
            <?php
                $sectionHeadMetaHtml = trim((string) ob_get_clean());
                $sectionHeadActionsHtml = $isTeacherNotifications
                    ? $buildMarkAllReadAction('app-segmented-link inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold')
                    : '';
                require __DIR__ . '/../shared/_section_head.php';
            ?>

            <?php if ($notifications === []): ?>
                <?php
                    $emptyStateTone = 'done';
                    $emptyStateTitle = 'Aucune notification pour ce filtre';
                    $emptyStateMessage = 'Vous êtes à jour. Revenez plus tard ou poursuivez votre activité depuis le support ou le tableau de bord.';
                    $emptyStateIcon = 'bell';
                    $emptyStateActions = [
                        ['label' => 'Contacter le support', 'url' => $supportUrl, 'icon' => 'lifebuoy', 'variant' => 'primary'],
                        ['label' => 'Retour au tableau de bord', 'url' => $dashboardUrl, 'icon' => 'layout', 'variant' => 'secondary'],
                    ];
                    require __DIR__ . '/../shared/_empty_state.php';
                ?>
            <?php else: ?>
                <ul class="space-y-3 list-none p-0 m-0">
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                            $notificationId = (int) ($notification['id'] ?? 0);
                            $notifScope = (string) ($notification['scope_key'] ?? 'other');
                            $scopeInfo = $scopeMeta[$notifScope] ?? $scopeMeta['other'];
                            $notifCreated = $fmtDt((string) ($notification['created_at'] ?? ''));
                            $announcementId = (int) ($notification['announcement_id'] ?? 0);
                            $notifTitleRaw = trim((string) ($notification['title'] ?? ''));
                            $notifTitle = $notifTitleRaw !== '' ? $notifTitleRaw : 'Notification';
                            $notifTitleLower = strtolower($notifTitle);
                            $titlePrefix = '';
                            if ($announcementId > 0 && !str_contains($notifTitleLower, 'annonce')) {
                                $titlePrefix = 'Annonce';
                            } elseif ($notifTitleRaw === '' || $notifTitleLower === 'notification') {
                                $titlePrefix = (string) ($scopeInfo['label'] ?? 'Notification');
                            }
                            if ($titlePrefix !== '') {
                                if ($notifTitleRaw === '' || $notifTitleLower === 'notification') {
                                    $notifTitle = $titlePrefix;
                                } else {
                                    $notifTitle = $titlePrefix . ' : ' . $notifTitle;
                                }
                            }
                            $notifBody = (string) ($notification['body'] ?? '');
                            $notifSummary = trim((string) ($notification['summary'] ?? ''));
                            $notifImportance = strtolower((string) ($notification['importance'] ?? 'info'));
                            $importanceInfo = $importanceMeta[$notifImportance] ?? $importanceMeta['info'];
                            $notifIsRead = !empty($notification['is_read']);
                            $resolvedUrl = trim((string) ($notification['resolved_url'] ?? ''));
                            $hasOpenAction = $notificationId > 0 && $hasMeaningfulNotificationTarget($notification);
                            $openUrl = $hasOpenAction ? $buildOpenUrl($notificationId) : '';
                            $toggleAction = $notifIsRead ? 'mark_unread' : 'mark_read';
                            $toggleLabel = $notifIsRead ? 'Marquer comme non lue' : 'Marquer comme lue';
                            $stateLabel = $notifIsRead ? 'Lue' : 'Non lue';
                            $stateClass = $notifIsRead
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/35 dark:text-emerald-200'
                                : 'bg-rose-100 text-rose-700 dark:bg-rose-900/35 dark:text-rose-200';
                            $scopeLabel = (string) ($scopeInfo['label'] ?? 'Général');
                            $showScopeBadge = $scopeLabel !== '' && stripos($notifTitle, $scopeLabel) === false;
                            $notifIsPriority = in_array($notifImportance, ['important', 'urgent'], true);
                            $scopeIcon = $scopeIcons[$notifScope] ?? 'bell';
                            $toneInfo = $scopeToneMeta[$notifScope] ?? $scopeToneMeta['other'];
                            $cardToneClass = (string) ($toneInfo['card'] ?? $scopeToneMeta['other']['card']);
                            $iconToneClass = (string) ($toneInfo['icon'] ?? $scopeToneMeta['other']['icon']);
                            $openButtonClass = (string) ($toneInfo['open'] ?? $scopeToneMeta['other']['open']);
                            $toggleButtonClass = $notifIsRead
                                ? 'border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/15 dark:text-indigo-200 dark:hover:bg-indigo-500/25'
                                : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-200 dark:hover:bg-emerald-500/25';
                            $articleClass = 'web-notifications-card relative rounded-2xl border p-3 transition sm:p-3.5 ' . $cardToneClass . ' shadow-sm';
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
                                            <span class="app-icon" aria-hidden="true"><?php echo $icon('external-link'); ?></span>
                                            <span>Ouvrir</span>
                                        </a>
                                    <?php endif; ?>
                                    <form method="post" action="<?php echo e(site_url('notifications')); ?>" class="w-full sm:w-auto">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                                        <input type="hidden" name="action" value="<?php echo e($toggleAction); ?>">
                                        <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                                        <input type="hidden" name="space" value="<?php echo e($dashboardSpace); ?>">
                                        <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                                        <button class="<?php echo e($toggleButtonClass); ?> inline-flex w-full items-center justify-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition sm:w-auto sm:py-1.5" type="submit">
                                            <span class="app-icon" aria-hidden="true"><?php echo $icon($notifIsRead ? 'repeat' : 'check'); ?></span>
                                            <span><?php echo e($toggleLabel); ?></span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($totalPages > 1): ?>
                    <nav class="app-page-subcard flex flex-wrap items-center justify-between gap-3" aria-label="Pagination notifications">
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            Page <?php echo e((string) $page); ?> / <?php echo e((string) $totalPages); ?> - <?php echo e((string) $totalItems); ?> notification(s)
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo e($buildPageUrl($page - 1)); ?>" class="app-segmented-link rounded-xl px-3 py-1.5 text-xs font-semibold">Précédent</a>
                            <?php endif; ?>
                            <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                            ?>
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="app-segmented-link app-segmented-link--active rounded-xl px-3 py-1.5 text-xs font-semibold"><?php echo e((string) $i); ?></span>
                                <?php else: ?>
                                    <a href="<?php echo e($buildPageUrl($i)); ?>" class="app-segmented-link rounded-xl px-3 py-1.5 text-xs font-semibold"><?php echo e((string) $i); ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo e($buildPageUrl($page + 1)); ?>" class="app-segmented-link rounded-xl px-3 py-1.5 text-xs font-semibold">Suivant</a>
                            <?php endif; ?>
                        </div>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
<?php if ($useDashboardShell): ?>
    <?php require __DIR__ . '/../dashboard/_shell_end.php'; ?>
<?php else: ?>
</body>
</html>
<?php endif; ?>
