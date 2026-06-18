<?php
$dashboardUrl = site_url('student/dashboard');
$myCoursesUrl = site_url('student/my_courses');
$calendarUrl = site_url('student/meetings');
$certificatesUrl = site_url('student/certificates');
$favoritesUrl = site_url('student/favorites');
$ordersUrl = site_url('student/orders');
$notificationsUrl = site_url('notifications?space=student');
$referralsUrl = site_url('student/affiliation');
$supportUrl = site_url('support?space=student');
$profileUrl = site_url('profile?space=student');
$homeUrl = site_url('');
$messagesUrl = $supportUrl;
$settingsUrl = $profileUrl;
$catalogueUrl = site_url('catalogue');
$logoutUrl = site_url('logout');
$logoutCsrf = function_exists('view_csrf_token') ? view_csrf_token() : '';
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$studentDashboardCssUrl = site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()));
$studentDashboardJsUrl = site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time()));
$notificationsPageJsUrl = site_url('assets/js/notifications_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/notifications_page.js') ?: time()));

$dashboardUser = is_array($user ?? null) ? $user : [];
$dashboardDisplayName = dashboard_user_display_name($dashboardUser, 'Utilisateur');
$dashboardEmail = trim((string) ($dashboardUser['email'] ?? ''));
$dashboardFirstName = trim((string) ($dashboardUser['first_name'] ?? ''));
if ($dashboardFirstName === '') {
    $displayNameParts = preg_split('/\s+/', $dashboardDisplayName) ?: [];
    $candidateFirstName = trim((string) ($displayNameParts[0] ?? ''));
    if ($candidateFirstName === '' && $dashboardEmail !== '') {
        $emailPrefix = trim((string) explode('@', $dashboardEmail, 2)[0]);
        $emailPrefix = str_replace(['.', '_', '-'], ' ', $emailPrefix);
        $emailParts = preg_split('/\s+/', $emailPrefix) ?: [];
        $candidateFirstName = trim((string) ($emailParts[0] ?? ''));
    }
    $dashboardFirstName = $candidateFirstName !== '' ? $candidateFirstName : 'Étudiant';
}
$dashboardAvatarUrl = dashboard_user_avatar_url($dashboardUser);
$dashboardInitials = dashboard_user_initials($dashboardUser, 'ET');
$dashboardSidebarName = $dashboardDisplayName;
if ($dashboardEmail !== '' && strcasecmp($dashboardSidebarName, $dashboardEmail) === 0) {
    $dashboardSidebarName = 'Compte étudiant';
}
$activeEnrollments = max(0, (int) ($activeEnrollments ?? $courseCount ?? 0));
$profileCompletionPct = max(0, min(100, (int) ($profileCompletionPct ?? 0)));

$dashboardSpace = (string) ($dashboardSpace ?? 'student');
if (!in_array($dashboardSpace, ['student', 'teacher'], true)) {
    $dashboardSpace = 'student';
}

$scope = (string) ($scope ?? 'all');
$scopeCounts = is_array($scopeCounts ?? null) ? $scopeCounts : [];
$notifications = is_array($notifications ?? null) ? $notifications : [];
$success = trim((string) ($success ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$totalItems = max(0, (int) ($totalItems ?? 0));
$perPage = max(1, (int) ($perPage ?? 10));
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$pendingMeetingsCount = max(0, (int) ($pendingMeetingsCount ?? 0));
$readCount = max(0, (int) ($scopeCounts['read'] ?? max(0, (int) ($scopeCounts['all'] ?? 0) - $unreadCount)));
$importantCount = max(0, (int) ($scopeCounts['important'] ?? 0));
$notificationTotalCount = max(0, (int) ($scopeCounts['all'] ?? count($notifications)));
$supportScopeCount = max($supportUnreadCount, (int) ($scopeCounts['support'] ?? 0));
$latestNotification = is_array($latestNotification ?? null) ? $latestNotification : (is_array($notifications[0] ?? null) ? $notifications[0] : []);
$latestNotificationTitle = trim((string) ($latestNotification['title'] ?? ''));
if ($latestNotificationTitle === '') {
    $latestNotificationTitle = 'Dernière notification';
}
$latestNotificationWhen = '';
if (!empty($latestNotification['created_at'])) {
    $ts = strtotime((string) $latestNotification['created_at']);
    if ($ts) {
        $latestNotificationWhen = date('d/m/Y H:i', $ts);
    }
}
$latestNotificationBody = trim((string) ($latestNotification['summary'] ?? ($latestNotification['body'] ?? '')));
$latestNotificationScope = trim((string) ($latestNotification['scope_key'] ?? 'other'));
$latestNotificationScopeLabel = 'Général';

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
    'support' => 'fa-headset',
    'learning' => 'fa-book-open',
    'business' => 'fa-chart-line',
    'admin' => 'fa-cog',
    'other' => 'fa-bell',
];
$scopeToneMeta = [
    'support' => [
        'card' => 'border-sky-200 bg-sky-50/80 dark:border-sky-500/35 dark:bg-sky-950/25',
        'icon' => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-400/20',
        'open' => 'border-sky-200 bg-sky-100 text-sky-800 hover:bg-sky-200 dark:border-sky-500/30 dark:bg-sky-500/15 dark:text-sky-200 dark:hover:bg-sky-500/25',
        'latest' => 'border-sky-100 bg-sky-50/80 dark:border-sky-500/25 dark:bg-sky-950/30',
    ],
    'learning' => [
        'card' => 'border-violet-200 bg-violet-50/80 dark:border-violet-500/35 dark:bg-violet-950/25',
        'icon' => 'bg-violet-100 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-500/15 dark:text-violet-300 dark:ring-violet-400/20',
        'open' => 'border-violet-200 bg-violet-100 text-violet-800 hover:bg-violet-200 dark:border-violet-500/30 dark:bg-violet-500/15 dark:text-violet-200 dark:hover:bg-violet-500/25',
        'latest' => 'border-violet-100 bg-violet-50/80 dark:border-violet-500/25 dark:bg-violet-950/30',
    ],
    'business' => [
        'card' => 'border-amber-200 bg-amber-50/80 dark:border-amber-500/35 dark:bg-amber-950/25',
        'icon' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-400/20',
        'open' => 'border-amber-200 bg-amber-100 text-amber-800 hover:bg-amber-200 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-200 dark:hover:bg-amber-500/25',
        'latest' => 'border-amber-100 bg-amber-50/80 dark:border-amber-500/25 dark:bg-amber-950/30',
    ],
    'admin' => [
        'card' => 'border-rose-200 bg-rose-50/80 dark:border-rose-500/35 dark:bg-rose-950/25',
        'icon' => 'bg-rose-100 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-400/20',
        'open' => 'border-rose-200 bg-rose-100 text-rose-800 hover:bg-rose-200 dark:border-rose-500/30 dark:bg-rose-500/15 dark:text-rose-200 dark:hover:bg-rose-500/25',
        'latest' => 'border-rose-100 bg-rose-50/80 dark:border-rose-500/25 dark:bg-rose-950/30',
    ],
    'other' => [
        'card' => 'border-slate-200 bg-slate-50/90 dark:border-slate-600/60 dark:bg-slate-800/70',
        'icon' => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-500/30',
        'open' => 'border-slate-200 bg-slate-100 text-slate-800 hover:bg-slate-200 dark:border-slate-500/30 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600',
        'latest' => 'border-slate-200 bg-slate-50/90 dark:border-slate-600/60 dark:bg-slate-900/70',
    ],
];
if (isset($scopeMeta[$latestNotificationScope])) {
    $latestNotificationScopeLabel = (string) $scopeMeta[$latestNotificationScope]['label'];
}
$latestToneInfo = $scopeToneMeta[$latestNotificationScope] ?? $scopeToneMeta['other'];

$studentTopSearchValue = trim((string) ($_GET['q'] ?? ''));
$studentTopSearchPlaceholder = 'Rechercher...';
$studentTopSearchAriaLabel = 'Rechercher';
$studentTopSearchUrl = site_url('notifications?space=student');
$studentTopTitle = 'Notifications';
$studentTopSubtitle = 'Espace étudiant';
$studentNavActive = 'notifications';

$formatCount = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ');
};

$scopeFilters = [
    'all' => 'Toutes',
    'unread' => 'Non lues',
    'read' => 'Lues',
    'important' => 'Prioritaires',
    'support' => 'Support',
    'learning' => 'Pédagogie',
    'business' => 'Commercial',
    'admin' => 'Admin',
    'other' => 'Autres',
];
$primaryScopeFilters = ['all', 'unread', 'important'];
$secondaryScopeFilters = array_values(array_filter(
    array_keys($scopeFilters),
    static fn (string $filterKey): bool => !in_array($filterKey, $primaryScopeFilters, true)
));
$activeSecondaryScope = in_array($scope, $secondaryScopeFilters, true) ? $scope : '';

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

$buildTabUrl = static function (string $targetScope) use ($dashboardSpace, $q): string {
    $targetScope = in_array($targetScope, ['all', 'unread', 'read', 'todo', 'archived', 'important', 'support', 'learning', 'business', 'admin', 'other'], true) ? $targetScope : 'all';
    $params = ['space' => $dashboardSpace];
    if ($targetScope !== 'all') {
        $params['scope'] = $targetScope;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }
    return site_url('notifications?' . http_build_query($params));
};

$buildPostActionUrl = static function () use ($scope, $page, $dashboardSpace, $q): string {
    $params = ['space' => $dashboardSpace];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }

    return site_url('notifications') . ($params !== [] ? '?' . http_build_query($params) : '');
};

$buildOpenUrl = static function (int $notificationId) use ($scope, $dashboardSpace, $page, $q): string {
    $params = ['open' => $notificationId, 'space' => $dashboardSpace];
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

$buildPageUrl = static function (int $targetPage) use ($scope, $dashboardSpace, $q): string {
    $targetPage = max(1, $targetPage);
    $params = ['space' => $dashboardSpace];
    if ($scope !== 'all') {
        $params['scope'] = $scope;
    }
    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }
    return site_url('notifications?' . http_build_query($params));
};

$notificationActionUrl = $buildPostActionUrl();

$dashboardStats = [
    [
        'label' => 'Notifications',
        'value' => (string) $notificationTotalCount,
        'note' => 'Total reçues',
        'note_class' => 'text-gray-500 dark:text-gray-400',
        'icon' => 'bell',
        'icon_class' => 'bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-300',
    ],
    [
        'label' => 'Non lues',
        'value' => (string) $unreadCount,
        'note' => 'À traiter en priorité',
        'note_class' => 'text-red-500 dark:text-red-300',
        'icon' => 'envelope-open-text',
        'icon_class' => 'bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-300',
    ],
    [
        'label' => 'Support',
        'value' => (string) $supportScopeCount,
        'note' => 'Messages de suivi',
        'note_class' => 'text-gray-500 dark:text-gray-400',
        'icon' => 'comments',
        'icon_class' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300',
    ],
    [
        'label' => 'Prioritaires',
        'value' => (string) $importantCount,
        'note' => $pendingMeetingsCount > 0 ? $pendingMeetingsCount . ' rendez-vous à surveiller' : 'Aucune demande prioritaire',
        'note_class' => 'text-gray-500 dark:text-gray-400',
        'icon' => 'star',
        'icon_class' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-300',
    ],
];
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Notifications · École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e($studentDashboardCssUrl); ?>">
    <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e($studentDashboardJsUrl); ?>" defer></script>
    <script src="<?php echo e($notificationsPageJsUrl); ?>" defer></script>
    <style>
        body[data-view="student/notifications"] {
            --student-notification-toast-top: var(--student-topbar-height);
            --student-notification-toast-bg: rgba(255, 255, 255, 0.98);
            --student-notification-toast-border: rgba(148, 163, 184, 0.2);
            --student-notification-toast-color: #0f172a;
            --student-notification-toast-shadow: 0 28px 60px -30px rgba(15, 23, 42, 0.24);
            --student-notification-toast-success-bg: linear-gradient(135deg, rgba(240, 253, 250, 0.98), rgba(236, 254, 255, 0.96));
            --student-notification-toast-success-color: #064e3b;
            --student-notification-toast-icon-bg: rgba(16, 185, 129, 0.12);
            --student-notification-toast-icon-color: #059669;
            --student-notification-toast-bar-bg: rgba(203, 213, 225, 0.58);
            --student-notification-toast-bar-gradient: linear-gradient(90deg, #34d399 0%, #10b981 45%, #059669 100%);
        }

        html.dark body[data-view="student/notifications"],
        html[data-theme="dark"] body[data-view="student/notifications"] {
            --student-notification-toast-bg: rgba(15, 23, 42, 0.96);
            --student-notification-toast-border: rgba(148, 163, 184, 0.2);
            --student-notification-toast-color: #dbeafe;
            --student-notification-toast-shadow: 0 28px 60px -30px rgba(15, 23, 42, 0.72);
            --student-notification-toast-success-bg: linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(8, 47, 73, 0.96));
            --student-notification-toast-success-color: #d1fae5;
            --student-notification-toast-icon-bg: rgba(16, 185, 129, 0.16);
            --student-notification-toast-icon-color: #34d399;
            --student-notification-toast-bar-bg: rgba(148, 163, 184, 0.18);
        }

        body[data-view="student/notifications"] .student-notification-toast {
            position: fixed;
            top: calc(var(--student-notification-toast-top, 4.5rem) + 0.75rem);
            left: 50%;
            z-index: 9999;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            width: min(42rem, calc(100vw - 1rem));
            border: 1px solid var(--student-notification-toast-border);
            border-radius: 1.1rem;
            padding: 0.95rem 1rem;
            background: var(--student-notification-toast-bg);
            color: var(--student-notification-toast-color);
            box-shadow: var(--student-notification-toast-shadow);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            transform: translateX(-50%);
            animation:
                student-notification-toast-in 180ms ease-out both,
                student-notification-toast-out 220ms ease-in 3200ms forwards;
        }

        body[data-view="student/notifications"] .student-notification-toast--success {
            border-color: rgba(52, 211, 153, 0.28);
            background: var(--student-notification-toast-success-bg);
            color: var(--student-notification-toast-success-color);
        }

        body[data-view="student/notifications"] .student-notification-toast__copy {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        body[data-view="student/notifications"] .student-notification-toast__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 auto;
            border-radius: 9999px;
            background: var(--student-notification-toast-icon-bg);
            color: var(--student-notification-toast-icon-color);
        }

        body[data-view="student/notifications"] .student-notification-toast__message {
            min-width: 0;
            flex: 1 1 auto;
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.35;
            word-break: break-word;
        }

        body[data-view="student/notifications"] .student-notification-toast__bar {
            display: block;
            width: 100%;
            height: 3px;
            border-radius: 9999px;
            background: var(--student-notification-toast-bar-bg);
            overflow: hidden;
        }

        body[data-view="student/notifications"] .student-notification-toast__bar::before {
            content: '';
            display: block;
            width: 100%;
            height: 100%;
            border-radius: inherit;
            transform-origin: left center;
            background: var(--student-notification-toast-bar-gradient);
            animation: student-notification-toast-bar 3200ms linear forwards;
        }

        body[data-view="student/notifications"] .notification-filter-more > summary,
        body[data-view="student/notifications"] .notification-card-menu > summary {
            list-style: none;
        }

        body[data-view="student/notifications"] .notification-filter-more > summary::-webkit-details-marker,
        body[data-view="student/notifications"] .notification-card-menu > summary::-webkit-details-marker {
            display: none;
        }

        body[data-view="student/notifications"] .notification-filter-more[open] > summary,
        body[data-view="student/notifications"] .notification-card-menu[open] > summary {
            border-color: rgba(96, 165, 250, 0.45);
        }

        body[data-view="student/notifications"] .notif-message-body {
            overflow-wrap: anywhere;
        }

        body[data-view="student/notifications"] .notif-inline-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            max-width: 100%;
            margin: 0.25rem 0.25rem 0 0;
            padding: 0.25rem;
            border: 1px solid rgba(125, 211, 252, 0.38);
            border-radius: 999px;
            background: rgba(14, 165, 233, 0.1);
            vertical-align: middle;
        }

        body[data-view="student/notifications"] .notif-body-link,
        body[data-view="student/notifications"] .notif-copy-link-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            min-height: 1.85rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
            white-space: nowrap;
        }

        body[data-view="student/notifications"] .notif-body-link {
            padding: 0.42rem 0.7rem;
            color: #075985;
        }

        body[data-view="student/notifications"] .notif-copy-link-btn {
            padding: 0.42rem 0.7rem;
            border: 1px solid rgba(14, 165, 233, 0.28);
            background: #ffffff;
            color: #0369a1;
            cursor: pointer;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease;
        }

        body[data-view="student/notifications"] .notif-copy-link-btn:hover {
            border-color: rgba(14, 165, 233, 0.45);
            background: #e0f2fe;
            color: #075985;
        }

        html.dark body[data-view="student/notifications"] .notif-inline-link,
        html[data-theme="dark"] body[data-view="student/notifications"] .notif-inline-link {
            border-color: rgba(56, 189, 248, 0.28);
            background: rgba(14, 165, 233, 0.12);
        }

        html.dark body[data-view="student/notifications"] .notif-body-link,
        html[data-theme="dark"] body[data-view="student/notifications"] .notif-body-link {
            color: #bae6fd;
        }

        html.dark body[data-view="student/notifications"] .notif-copy-link-btn,
        html[data-theme="dark"] body[data-view="student/notifications"] .notif-copy-link-btn {
            border-color: rgba(56, 189, 248, 0.24);
            background: rgba(15, 23, 42, 0.88);
            color: #bae6fd;
        }

        html.dark body[data-view="student/notifications"] .notif-copy-link-btn:hover,
        html[data-theme="dark"] body[data-view="student/notifications"] .notif-copy-link-btn:hover {
            background: rgba(14, 165, 233, 0.22);
            color: #e0f2fe;
        }

        @keyframes student-notification-toast-in {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-0.5rem) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0) scale(1);
            }
        }

        @keyframes student-notification-toast-out {
            to {
                opacity: 0;
                transform: translateX(-50%) translateY(-0.25rem) scale(0.98);
                pointer-events: none;
            }
        }

        @keyframes student-notification-toast-bar {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }
    </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="student/notifications" data-sidebar-desktop-toggle="0" class="app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">
<div class="student-dashboard-shell relative min-h-screen overflow-hidden">
  <?php $studentNavActive = 'notifications'; ?>
  <?php $studentDashboardShell = true; ?>
  <?php $studentTopTitle = 'Notifications'; ?>
  <?php $studentTopSubtitle = 'Espace étudiant'; ?>
  <?php $studentTopHideSupportFab = true; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
    <main class="student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
      <?php require __DIR__ . '/_topbar.php'; ?>

      <div class="student-dashboard-content p-4 pb-32 space-y-5 animate-fade-up sm:p-6 sm:pb-24">
        <section class="student-dashboard-stats-grid hidden" aria-hidden="true">
          <?php foreach ($dashboardStats as $stat): ?>
            <article class="student-dashboard-stat-card rounded-2xl border border-gray-100 bg-white p-3.5 shadow-sm transition dark:border-gray-700 dark:bg-gray-800">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400"><?php echo e((string) ($stat['label'] ?? '')); ?></p>
                  <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?php echo e($formatCount((int) ($stat['value'] ?? 0))); ?></p>
                  <p class="mt-1 text-[11px] <?php echo e((string) ($stat['note_class'] ?? 'text-gray-500 dark:text-gray-400')); ?>"><?php echo e((string) ($stat['note'] ?? '')); ?></p>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl <?php echo e((string) ($stat['icon_class'] ?? 'bg-gray-100 text-gray-600')); ?>">
                  <i class="fas fa-<?php echo e((string) ($stat['icon'] ?? 'chart-line')); ?>"></i>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </section>

        <section class="student-dashboard-panels-grid grid grid-cols-1 lg:grid-cols-3 gap-5">
          <div class="student-dashboard-panel lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 sm:p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div class="min-w-0">
                <h3 class="font-bold text-gray-900 dark:text-white">Boîte de réception</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Suivez les alertes qui demandent une action.</p>
              </div>
              <form method="post" action="<?php echo e($notificationActionUrl); ?>" class="shrink-0">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                <input type="hidden" name="space" value="<?php echo e($dashboardSpace); ?>">
                <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                <button type="submit" class="inline-flex items-center gap-2 rounded-full border border-blue-600 bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm shadow-blue-500/20 transition hover:border-blue-700 hover:bg-blue-700 dark:border-blue-400 dark:bg-blue-500 dark:hover:bg-blue-400">
                  <i class="fas fa-check-circle" aria-hidden="true"></i>
                  <span>Tout marquer comme lu</span>
                </button>
              </form>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-1.5 border-b border-gray-200 pb-3 dark:border-gray-700">
              <?php foreach ($primaryScopeFilters as $filterKey): ?>
                <?php
                  $filterLabel = (string) ($scopeFilters[$filterKey] ?? $filterKey);
                  $badgeValue = (int) ($scopeCounts[$filterKey] ?? 0);
                  if ($filterKey === 'all') {
                      $badgeValue = $notificationTotalCount;
                  } elseif ($filterKey === 'unread') {
                      $badgeValue = $unreadCount;
                  } elseif ($filterKey === 'read') {
                      $badgeValue = $readCount;
                  } elseif ($filterKey === 'important') {
                      $badgeValue = $importantCount;
                  } elseif ($filterKey === 'support') {
                      $badgeValue = $supportScopeCount;
                  }
                  $isActive = $scope === $filterKey;
                  $buttonClass = $isActive
                      ? 'relative inline-flex items-center rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1.5 text-sm font-semibold text-primary-700 transition dark:border-primary-400/30 dark:bg-primary-900/20 dark:text-primary-300'
                      : 'relative inline-flex items-center rounded-full border border-transparent px-2.5 py-1.5 text-sm font-medium text-gray-500 transition hover:border-gray-200 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:bg-gray-700/60 dark:hover:text-white';
                  $badgeClass = $filterKey === 'unread'
                      ? 'ml-1.5 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900/40 dark:text-red-300'
                      : ($filterKey === 'read'
                          ? 'ml-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                          : 'ml-1.5 rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300');
                ?>
                <a href="<?php echo e($buildTabUrl($filterKey)); ?>" class="<?php echo e($buttonClass); ?>" aria-current="<?php echo $isActive ? 'page' : 'false'; ?>">
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
                      if ($filterKey === 'read') {
                          $badgeValue = $readCount;
                      }
                      $isActive = $scope === $filterKey;
                      $menuBadgeClass = $filterKey === 'read'
                          ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                          : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300';
                    ?>
                    <a href="<?php echo e($buildTabUrl($filterKey)); ?>" class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-medium transition <?php echo $isActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700/70 dark:hover:text-white'; ?>" aria-current="<?php echo $isActive ? 'page' : 'false'; ?>">
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

            <div class="mt-5 space-y-3">
              <?php if ($notifications === []): ?>
                <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center dark:border-gray-700 dark:bg-gray-700/40">
                  <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                    <?php echo dashboard_icon_svg('bell', 'class="h-7 w-7 shrink-0"'); ?>
                  </div>
                  <p class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Aucune notification pour ce filtre</p>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <?php if ($studentTopSearchValue !== ''): ?>
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
                    $notifImportance = strtolower((string) ($notification['importance'] ?? 'info'));
                    $importanceInfo = $importanceMeta[$notifImportance] ?? $importanceMeta['info'];
                    $hasOpenAction = $notificationId > 0 && !empty($notification['has_open_action']);
                    $openUrl = $hasOpenAction ? $buildOpenUrl($notificationId) : '';
                    $toggleAction = $notifIsRead ? 'mark_unread' : 'mark_read';
                    $toggleLabel = $notifIsRead ? 'Marquer comme non lue' : 'Marquer comme lue';
                    $stateLabel = $notifIsRead ? 'Lue' : 'Non lue';
                    $iconName = (string) ($scopeIcons[$notifScope] ?? 'fa-bell');
                    $bodyText = $notifSummary !== '' ? $notifSummary : $notifBody;
                    $notifIsPriority = in_array($notifImportance, ['important', 'urgent'], true);
                    $scopeLabel = (string) ($scopeInfo['label'] ?? 'Général');
                    $showScopeBadge = $scopeLabel !== '' && stripos($notifTitle, $scopeLabel) === false;
                    $toneInfo = $scopeToneMeta[$notifScope] ?? $scopeToneMeta['other'];
                    $cardToneClass = $toneInfo['card'] . ' shadow-sm';
                    if (!$notifIsRead) {
                        $cardToneClass .= ' ring-1 ring-blue-300/60 dark:ring-blue-400/30';
                    } elseif ($notifIsPriority) {
                        $cardToneClass .= ' ring-1 ring-amber-300/60 dark:ring-amber-400/30';
                    }
                    $iconToneClass = (string) ($toneInfo['icon'] ?? $scopeToneMeta['other']['icon']);
                    $openButtonClass = (string) ($toneInfo['open'] ?? $scopeToneMeta['other']['open']);
                    $stateClass = $notifIsRead ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
                    $toggleButtonClass = $notifIsRead
                        ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-200 dark:hover:bg-indigo-500/25'
                        : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-200 dark:hover:bg-emerald-500/25';
                  ?>
                  <article class="student-notification-card relative rounded-2xl border p-3 transition sm:p-3.5 <?php echo e($cardToneClass); ?>">
                    <div class="student-notification-card__body flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                      <div class="student-notification-card__main flex min-w-0 flex-1 items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm sm:h-10 sm:w-10 <?php echo e($iconToneClass); ?>">
                          <i class="fas <?php echo e($iconName); ?>" aria-hidden="true"></i>
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
                            <p class="notif-message-body text-xs text-gray-500 dark:text-gray-400" data-linkify="true"><?php echo nl2br(e($notifBody)); ?></p>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div class="student-notification-card__meta flex w-full shrink-0 items-center justify-between gap-2 sm:w-auto sm:flex-col sm:items-end sm:justify-start sm:gap-1.5">
                        <p class="min-w-0 pt-0 text-xs leading-tight text-gray-500 dark:text-gray-400 sm:pt-1 sm:text-right"><?php echo e($notifCreated !== '' ? $notifCreated : 'Date inconnue'); ?></p>
                        <details class="notification-card-menu student-notification-card__menu relative">
                          <summary class="inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-700" aria-label="Actions secondaires">
                            <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                          </summary>
                          <div class="absolute right-0 top-full z-50 mt-2 w-60 rounded-2xl border border-gray-200 bg-white p-2 shadow-2xl ring-1 ring-black/5 dark:border-gray-700 dark:bg-slate-900 dark:ring-white/10">
                            <form method="post" action="<?php echo e($notificationActionUrl); ?>">
                              <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                              <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                              <input type="hidden" name="action" value="<?php echo e($toggleAction); ?>">
                              <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                              <input type="hidden" name="space" value="<?php echo e($dashboardSpace); ?>">
                              <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                              <button type="submit" class="<?php echo e($toggleButtonClass); ?> flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-semibold transition">
                                <i class="fas <?php echo e($notifIsRead ? 'fa-redo-alt' : 'fa-check-circle'); ?> w-4 text-center" aria-hidden="true"></i>
                                <span><?php echo e($toggleLabel); ?></span>
                              </button>
                            </form>
                            <form method="post" action="<?php echo e($notificationActionUrl); ?>" data-confirm-delete="Supprimer cette notification ?">
                              <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                              <input type="hidden" name="notif_id" value="<?php echo e((string) $notificationId); ?>">
                              <input type="hidden" name="action" value="delete">
                              <input type="hidden" name="scope" value="<?php echo e($scope); ?>">
                              <input type="hidden" name="space" value="<?php echo e($dashboardSpace); ?>">
                              <input type="hidden" name="page" value="<?php echo e((string) $page); ?>">
                              <button type="submit" class="flex w-full items-center gap-2 rounded-xl bg-rose-50 px-3 py-2 text-left text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-200 dark:hover:bg-rose-500/25">
                                <i class="fas fa-trash w-4 text-center" aria-hidden="true"></i>
                                <span>Supprimer</span>
                              </button>
                            </form>
                          </div>
                        </details>
                      </div>
                    </div>

                    <?php if ($hasOpenAction): ?>
                      <div class="student-notification-card__actions mt-3 pl-0 sm:mt-2.5 sm:pl-[3.25rem]">
                        <a class="<?php echo e($openButtonClass); ?> inline-flex w-full items-center justify-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition sm:w-auto sm:py-1.5" href="<?php echo e($openUrl); ?>">
                          <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                          <span>Ouvrir</span>
                        </a>
                      </div>
                    <?php endif; ?>
                  </article>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <aside class="student-dashboard-panel space-y-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-5">
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
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
              <h3 class="font-bold text-gray-900 dark:text-white">Actions rapides</h3>
              <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                <a href="<?php echo e($dashboardUrl); ?>" class="inline-flex items-center justify-between gap-3 rounded-xl bg-blue-50 px-3.5 py-3 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:hover:bg-blue-900/30">
                  <span>Tableau de bord</span>
                  <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="<?php echo e($supportUrl); ?>" class="inline-flex items-center justify-between gap-3 rounded-xl bg-cyan-50 px-3.5 py-3 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100 dark:bg-cyan-900/20 dark:text-cyan-300 dark:hover:bg-cyan-900/30">
                  <span>Assistance support</span>
                  <i class="fas fa-headset" aria-hidden="true"></i>
                </a>
                <a href="<?php echo e($myCoursesUrl); ?>" class="inline-flex items-center justify-between gap-3 rounded-xl bg-emerald-50 px-3.5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/30">
                  <span>Mes cours</span>
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
              <a href="<?php echo e($calendarUrl); ?>" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:underline dark:text-blue-300">
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
                Affichage de <?php echo e($formatCount($start)); ?> à <?php echo e($formatCount($end)); ?> sur <?php echo e($formatCount($totalItems)); ?> notifications
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

  <a
    href="#support-chat"
    data-support-url="<?php echo e($messagesUrl); ?>"
    data-support-fab="1"
    class="student-dashboard-support-fab fixed bottom-3 right-3 z-40 inline-flex items-center gap-3 rounded-full bg-blue-600 px-3 py-2.5 text-white shadow-glow transition hover:-translate-y-0.5 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 sm:bottom-4 sm:right-4 sm:px-4 sm:py-3"
    aria-label="Assistance support"
    title="Assistance support"
  >
    <span class="relative flex h-10 w-10 items-center justify-center rounded-full bg-white/15 shadow-inner ring-1 ring-white/10 sm:h-11 sm:w-11" aria-hidden="true">
      <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('headset')); ?>"></i></span>
      <?php if ($supportUnreadCount > 0): ?>
        <span class="absolute -right-1 -top-1 inline-flex min-w-4 rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white text-center shadow-sm" data-live-support-badge><?php echo e((string) min(99, $supportUnreadCount)); ?></span>
      <?php endif; ?>
    </span>
    <span class="student-dashboard-support-label hidden sm:block pr-1">
      <span class="block text-sm font-semibold leading-tight">Assistance support</span>
      <span class="block text-xs text-blue-100">Réponse rapide</span>
    </span>
    <?php if ($supportUnreadCount > 0): ?>
      <span class="sr-only"><?php echo e((string) min(99, $supportUnreadCount)); ?> message(s) non lu(s)</span>
    <?php endif; ?>
  </a>

</body>
</html>
