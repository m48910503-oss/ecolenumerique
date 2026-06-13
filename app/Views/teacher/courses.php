
<?php
$errors = is_array($errors ?? null) ? $errors : [];
$success = trim((string) ($success ?? ''));
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [
    'draft' => 'Brouillon',
    'submitted' => 'Soumis',
    'approved' => 'Approuv&eacute;',
    'rejected' => 'Rejet&eacute;',
    'archived' => 'Archiv&eacute;',
]; 
$levelLabels = is_array($levelLabels ?? null) ? $levelLabels : [
    'all' => 'Tous niveaux',
    'beginner' => 'D&eacute;butant',
    'intermediate' => 'Interm&eacute;diaire',
    'advanced' => 'Avanc&eacute;',
];
$sortLabels = is_array($sortLabels ?? null) ? $sortLabels : [
    'updated_desc' => 'Plus r&eacute;cents',
    'updated_asc' => 'Mise &agrave; jour ancienne',
    'created_desc' => 'Cr&eacute;ation r&eacute;cente',
    'created_asc' => 'Cr&eacute;ation ancienne',
    'revenue_desc' => 'Revenus',
    'students_desc' => '&Eacute;tudiants',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix d&eacute;croissant',
    'status_asc' => 'Statut A-Z',
    'status_desc' => 'Statut Z-A',
    'title_asc' => 'Titre A-Z',
    'title_desc' => 'Titre Z-A',
];
$decodeLabel = static function (string $label): string {
    return html_entity_decode($label, ENT_QUOTES, 'UTF-8');
};
$levelLabels = array_map($decodeLabel, $levelLabels);
$sortLabels = array_map($decodeLabel, $sortLabels);
$statusLabels = array_map($decodeLabel, $statusLabels);
$perPageOptions = is_array($perPageOptions ?? null) ? $perPageOptions : [6, 12, 24, 48];
$filters = is_array($filters ?? null) ? $filters : [
    'q' => '',
    'status' => 'all',
    'level' => 'all',
    'price_min' => null,
    'price_max' => null,
    'sort' => 'updated_desc',
    'per_page' => 12,
    'page' => 1,
    'period' => 'all',
    'from' => '',
    'to' => '',
];
$periodState = is_array($periodState ?? null) ? $periodState : [
    'key' => 'all',
    'from_date' => '',
    'to_date' => '',
    'label' => 'Depuis le d&eacute;but',
    'warning' => '',
];
$statusCounters = is_array($statusCounters ?? null) ? $statusCounters : [
    'draft' => 0,
    'submitted' => 0,
    'approved' => 0,
    'rejected' => 0,
    'archived' => 0,
];
if (!isset($statusCounters['all'])) {
    $statusCounters['all'] = (int) array_sum($statusCounters);
}
$resumeCourses = is_array($resumeCourses ?? null) ? $resumeCourses : [];
$portfolioSummary = is_array($portfolioSummary ?? null) ? $portfolioSummary : [
    'total' => 0,
    'draft' => 0,
    'published' => 0,
    'students' => 0,
    'revenue_xof' => 0,
];
$listQuery = (string) ($listQuery ?? '');
$pagination = is_array($pagination ?? null) ? $pagination : [
    'page' => 1,
    'per_page' => 12,
    'total' => 0,
    'total_pages' => 1,
    'has_prev' => false,
    'has_next' => false,
    'start' => 0,
    'end' => 0,
];
$courses = is_array($courses ?? null) ? $courses : [];

$periodKey = (string) ($periodState['key'] ?? 'all');
$periodFrom = (string) ($periodState['from_date'] ?? '');
$periodTo = (string) ($periodState['to_date'] ?? '');
$periodLabel = html_entity_decode((string) ($periodState['label'] ?? 'Depuis le d&eacute;but'), ENT_QUOTES, 'UTF-8');
$periodWarning = (string) ($periodState['warning'] ?? '');

$filterQuery = trim((string) ($filters['q'] ?? ''));
$filterStatus = (string) ($filters['status'] ?? 'all');
$filterLevel = (string) ($filters['level'] ?? 'all');
$filterSort = (string) ($filters['sort'] ?? 'updated_desc');
$filterPerPage = (int) ($filters['per_page'] ?? 12);
$filterPage = (int) ($filters['page'] ?? 1);
$filterPriceMin = $filters['price_min'] ?? null;
$filterPriceMax = $filters['price_max'] ?? null;
$priceMinValue = $filterPriceMin !== null ? (string) $filterPriceMin : '';
$priceMaxValue = $filterPriceMax !== null ? (string) $filterPriceMax : '';

$hasActiveFilters = $filterQuery !== ''
    || $filterStatus !== 'all'
    || $filterLevel !== 'all'
    || $filterPriceMin !== null
    || $filterPriceMax !== null
    || $filterSort !== 'updated_desc'
    || $filterPerPage !== 12;
$isFilterPanelOpen = $hasActiveFilters || $periodKey === 'custom';

$periodOptions = [
    'all' => 'Tout',
    '7d' => '7j',
    '30d' => '30j',
    '90d' => '90j',
    'custom' => 'Personnalis&eacute;',
];

$doneLabel = $decodeLabel('Termin&eacute;');
$doneLabelFeminine = $decodeLabel('Termin&eacute;e');
$urgentLabel = 'Urgent';
$urgentLabelFeminine = 'Urgente';
$stateBadgeMap = [
    'Actif' => 'border border-emerald-200 bg-emerald-100 text-emerald-800 shadow-sm dark:border-emerald-300 dark:bg-emerald-500 dark:text-white',
    'Publié' => 'border border-emerald-200 bg-emerald-100 text-emerald-800 shadow-sm dark:border-emerald-300 dark:bg-emerald-500 dark:text-white',
    'En attente' => 'border border-amber-200 bg-amber-100 text-amber-800 shadow-sm dark:border-amber-300 dark:bg-amber-500 dark:text-white',
    $urgentLabel => 'border border-rose-200 bg-rose-100 text-rose-800 shadow-sm dark:border-rose-300 dark:bg-rose-500 dark:text-white',
    $urgentLabelFeminine => 'border border-rose-200 bg-rose-100 text-rose-800 shadow-sm dark:border-rose-300 dark:bg-rose-500 dark:text-white',
    $doneLabel => 'border border-slate-200 bg-slate-100 text-slate-700 shadow-sm dark:border-slate-300 dark:bg-slate-500 dark:text-white',
    $doneLabelFeminine => 'border border-slate-200 bg-slate-100 text-slate-700 shadow-sm dark:border-slate-300 dark:bg-slate-500 dark:text-white',
    'Brouillon' => 'border border-slate-200 bg-slate-100 text-slate-700 shadow-sm dark:border-slate-300 dark:bg-slate-500 dark:text-white',
    $decodeLabel('Rejet&eacute;') => 'border border-rose-200 bg-rose-100 text-rose-800 shadow-sm dark:border-rose-300 dark:bg-rose-500 dark:text-white',
    $decodeLabel('Archiv&eacute;') => 'border border-slate-200 bg-slate-100 text-slate-700 shadow-sm dark:border-slate-300 dark:bg-slate-500 dark:text-white',
];
$courseActionButtonClass = static function (string $label): string {
    $label = trim(html_entity_decode($label, ENT_QUOTES, 'UTF-8'));

    return match ($label) {
        'Continuer', 'Modifier' => 'flex-1 rounded-lg border border-blue-500 bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-[0_14px_24px_-18px_rgba(37,99,235,0.68)] transition hover:bg-blue-700 dark:border-blue-400 dark:bg-blue-500 dark:text-white dark:hover:bg-blue-400',
        'Aperçu' => 'flex-1 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-center text-sm font-medium text-sky-700 transition hover:bg-sky-100 dark:border-sky-400/40 dark:bg-sky-500/15 dark:text-sky-100 dark:hover:bg-sky-500/25',
        'Statistiques' => 'flex-1 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-center text-sm font-medium text-violet-700 transition hover:bg-violet-100 dark:border-violet-400/40 dark:bg-violet-500/15 dark:text-violet-100 dark:hover:bg-violet-500/25',
        'Détails' => 'flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-slate-400/40 dark:bg-slate-500/15 dark:text-slate-100 dark:hover:bg-slate-500/25',
        'Corriger' => 'flex-1 rounded-lg border border-rose-500 bg-rose-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-[0_14px_24px_-18px_rgba(225,29,72,0.68)] transition hover:bg-rose-700 dark:border-rose-400 dark:bg-rose-500 dark:text-white dark:hover:bg-rose-400',
        'Restaurer' => 'flex-1 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-sm font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-400/40 dark:bg-emerald-500/15 dark:text-emerald-100 dark:hover:bg-emerald-500/25',
        default => 'flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-100 dark:border-slate-400/40 dark:bg-slate-500/15 dark:text-slate-100 dark:hover:bg-slate-500/25',
    };
};
$courseArchiveButtonClass = static function (string $workflow): string {
    return $workflow === 'archived'
        ? 'inline-flex h-10 w-10 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-400/40 dark:bg-emerald-500/15 dark:text-emerald-100 dark:hover:bg-emerald-500/25'
        : 'inline-flex h-10 w-10 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 dark:border-rose-400/40 dark:bg-rose-500/15 dark:text-rose-100 dark:hover:bg-rose-500/25';
};
$workflowLabels = [
    'draft' => 'Brouillon',
    'submitted' => 'En attente',
    'approved' => 'Actif',
    'rejected' => $decodeLabel('Rejet&eacute;'),
    'archived' => $decodeLabel('Archiv&eacute;'),
];

$moneyFmt = static function ($amount): string {
    return number_format((int) ($amount ?? 0), 0, ',', ' ') . ' F CFA';
};
$fmtDate = static function (string $value, bool $withTime = false): string {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '') {
        return 'Non d&eacute;fini';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return 'Non d&eacute;fini';
    }
    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts);
};
$resolveMediaUrl = static function ($path): string {
    $value = trim((string) $path);
    if ($value === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $value) === 1) {
        return $value;
    }
    return site_url(ltrim($value, '/'));
};
$buildListQuery = static function (array $state): string {
    $params = [];
    $q = trim((string) ($state['q'] ?? ''));
    if ($q !== '') {
        $params['q'] = $q;
    }
$status = (string) ($state['status'] ?? 'all');
    if ($status !== 'all') {
        $params['status'] = $status;
    }
$level = (string) ($state['level'] ?? 'all');
    if ($level !== 'all') {
        $params['level'] = $level;
    }
if (array_key_exists('price_min', $state) && $state['price_min'] !== null) {
        $params['price_min'] = (string) $state['price_min'];
    }
if (array_key_exists('price_max', $state) && $state['price_max'] !== null) {
        $params['price_max'] = (string) $state['price_max'];
    }
$sort = (string) ($state['sort'] ?? 'updated_desc');
    if ($sort !== 'updated_desc') {
        $params['sort'] = $sort;
    }
$perPage = (int) ($state['per_page'] ?? 12);
    if ($perPage !== 12) {
        $params['per_page'] = (string) $perPage;
    }
$page = (int) ($state['page'] ?? 1);
    if ($page > 1) {
        $params['page'] = (string) $page;
    }
$period = (string) ($state['period'] ?? 'all');
    if ($period !== 'all') {
        $params['period'] = $period;
        if ($period === 'custom') {
            $from = trim((string) ($state['from'] ?? ''));
            $to = trim((string) ($state['to'] ?? ''));
            if ($from !== '') {
                $params['from'] = $from;
            }
if ($to !== '') {
                $params['to'] = $to;
            }
        }
    }
return http_build_query($params);
};
$buildUrl = static function (array $overrides = []) use ($filters, $buildListQuery): string {
    $state = $filters;
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($state[$key]);
            continue;
        }
$state[$key] = $value;
    }
if (($state['period'] ?? 'all') !== 'custom') {
        $state['from'] = '';
        $state['to'] = '';
    }
$query = $buildListQuery($state);
    return site_url('teacher/courses' . ($query !== '' ? ('?' . $query) : ''));
};

$paginationPage = max(1, (int) ($pagination['page'] ?? 1));
$paginationTotalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$paginationTotal = max(0, (int) ($pagination['total'] ?? 0));
$paginationStart = max(0, (int) ($pagination['start'] ?? 0));
$paginationEnd = max(0, (int) ($pagination['end'] ?? 0));
$pageWindowStart = max(1, $paginationPage - 2);
$pageWindowEnd = min($paginationTotalPages, $paginationPage + 2);
$formatCount = static function (int $value): string {
    return number_format($value, 0, ',', ' ');
};
$excerptText = static function (string $value, int $limit = 180): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit, 'UTF-8') . '…' : $value;
    }

    return strlen($value) > $limit ? substr($value, 0, $limit) . '...' : $value;
};
$teacherUser = is_array($teacherUser ?? null) ? $teacherUser : (is_array($user ?? null) ? $user : []);
$teacherDisplayName = dashboard_user_display_name($teacherUser, 'Enseignant');
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherInitials = dashboard_user_initials($teacherUser, 'EN');
$projectRoot = dirname(__DIR__, 3);
$resolveAvatarUrl = static function ($path) use ($projectRoot): string {
    $value = trim((string) $path);
    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '//') || str_starts_with($value, 'data:')) {
        return $value;
    }

    $relativePath = ltrim($value, '/');
    $absolutePath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (is_file($absolutePath)) {
        return site_url($relativePath);
    }

    return '';
};
$teacherAvatarUrl = $resolveAvatarUrl((string) ($teacherUser['avatar_url'] ?? $teacherUser['avatar'] ?? ''));
$teacherSidebarBadges = teacher_sidebar_badge_counts($teacherUser);
$teacherUnreadCount = max(0, (int) ($teacherUnreadCount ?? ($teacherSidebarBadges['notifications'] ?? 0)));
$teacherSupportUnreadCount = max(0, (int) ($teacherSupportUnreadCount ?? ($teacherSidebarBadges['support'] ?? 0)));
$teacherPendingMeetingsCount = max(0, (int) ($teacherPendingMeetingsCount ?? ($teacherSidebarBadges['pending_meetings'] ?? 0)));
$teacherPendingWithdrawalsCount = max(0, (int) ($teacherPendingWithdrawalsCount ?? ($teacherSidebarBadges['pending_withdrawals'] ?? 0)));
$teacherDashboardUrl = trim((string) ($teacherDashboardUrl ?? ''));
if ($teacherDashboardUrl === '') {
    $teacherDashboardUrl = site_url('teacher/dashboard');
}
$teacherSupportUrl = trim((string) ($teacherSupportUrl ?? ''));
if ($teacherSupportUrl === '') {
    $teacherSupportUrl = site_url('support?space=teacher');
}
$teacherProfileUrl = trim((string) ($teacherProfileUrl ?? ''));
if ($teacherProfileUrl === '') {
    $teacherProfileUrl = site_url('profile?space=teacher');
}
$coursesBadgeCount = max(0, (int) ($statusCounters['all'] ?? array_sum($statusCounters)));
$coursesBadge = $coursesBadgeCount > 0 ? ($coursesBadgeCount > 99 ? '99+' : (string) $coursesBadgeCount) : '';
$iconClass = static function (string $name): string {
    static $map = [
        'home' => 'fas fa-home',
        'book' => 'fas fa-book',
        'book-open' => 'fas fa-book-open',
        'plus-circle' => 'fas fa-plus-circle',
        'users' => 'fas fa-users',
        'chart-bar' => 'fas fa-chart-bar',
        'hand-holding-usd' => 'fas fa-hand-holding-usd',
        'handshake' => 'fas fa-handshake',
        'gift' => 'fas fa-gift',
        'ticket' => 'fas fa-ticket',
        'comments' => 'fas fa-comments',
        'headset' => 'fas fa-headset',
        'user-circle' => 'fas fa-user-circle',
        'cog' => 'fas fa-cog',
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
    ];

    $key = strtolower(trim($name));
    return $map[$key] ?? 'fas fa-circle';
};
$teacherSidebarLinks = [
    [
        'label' => 'Tableau de bord',
        'url' => $teacherDashboardUrl,
        'icon' => 'home',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Mes formations',
        'url' => site_url('teacher/courses'),
        'icon' => 'book-open',
        'active' => true,
        'badge' => $coursesBadge,
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Créer un cours',
        'url' => site_url('teacher/course_create'),
        'icon' => 'plus-circle',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Revenus',
        'url' => site_url('teacher/withdrawals'),
        'icon' => 'hand-holding-usd',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Promotions et coupons',
        'url' => site_url('teacher/promotions'),
        'icon' => 'ticket',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Rendez-vous',
        'url' => site_url('teacher/meetings?status=pending'),
        'icon' => 'handshake',
        'active' => false,
        'badge' => $teacherPendingMeetingsCount > 0 ? ($teacherPendingMeetingsCount > 99 ? '99+' : (string) $teacherPendingMeetingsCount) : '',
        'badge_tone' => 'blue',
    ],
    [
        'label' => 'Parrainage',
        'url' => site_url('teacher/affiliation'),
        'icon' => 'gift',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Messages',
        'url' => $teacherSupportUrl,
        'icon' => 'headset',
        'active' => false,
        'badge' => $teacherSupportUnreadCount > 0 ? ($teacherSupportUnreadCount > 99 ? '99+' : (string) $teacherSupportUnreadCount) : '',
        'badge_tone' => 'red',
    ],
    [
        'label' => 'Mon profil',
        'url' => $teacherProfileUrl,
        'icon' => 'user-circle',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
];
$draftCount = (int) ($statusCounters['draft'] ?? 0);
$pendingCount = (int) ($statusCounters['submitted'] ?? 0);
$activeCount = (int) ($statusCounters['approved'] ?? 0);
$portfolioTotal = max(0, (int) ($portfolioSummary['total'] ?? ($statusCounters['all'] ?? 0)));
$portfolioDrafts = max(0, (int) ($portfolioSummary['draft'] ?? $draftCount));
$portfolioPublished = max(0, (int) ($portfolioSummary['published'] ?? $activeCount));
$portfolioStudents = max(0, (int) ($portfolioSummary['students'] ?? 0));
$portfolioRevenue = max(0, (int) ($portfolioSummary['revenue_xof'] ?? 0));
$activeFilterCount = 0;
foreach ([
    $filterQuery !== '',
    $filterStatus !== 'all',
    $filterLevel !== 'all',
    $filterPriceMin !== null,
    $filterPriceMax !== null,
    $filterSort !== 'updated_desc',
    $filterPerPage !== 12,
] as $hasFilterFlag) {
    if ($hasFilterFlag) {
        $activeFilterCount++;
    }
}
$formatRating = static function (float $value): string {
    return number_format($value, 1, ',', ' ');
};
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/courses" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="app-global-assets" content="off">
    <title>Mes cours · Enseignant · École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-courses.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-courses.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_period_toggle.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_period_toggle.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_courses.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_courses.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/courses" class="app-role-teacher teacher-courses-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">
<div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
  <?php $teacherNavActive = 'courses'; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
    <?php $teacherTopTitle = 'Mes cours'; ?>
    <?php $teacherTopSubtitle = 'Espace formateur'; ?>
    <?php $showCatalogue = false; ?>
    <?php require __DIR__ . '/_topbar.php'; ?>
            <div class="p-4 sm:p-5 lg:p-6 space-y-5 animate-fade-up">
                <section class="teacher-courses-page-head flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Mes formations</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Créez, gérez et améliorez vos cours.</p>
                    </div>
                </section>

                <section class="teacher-courses-summary grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Résumé des formations">
                    <article class="teacher-courses-summary-card">
                        <span class="teacher-courses-summary-card__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                        <span class="teacher-courses-summary-card__label">Total formations</span>
                        <strong class="teacher-courses-summary-card__value"><?php echo e($formatCount($portfolioTotal)); ?></strong>
                    </article>
                    <article class="teacher-courses-summary-card">
                        <span class="teacher-courses-summary-card__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                        <span class="teacher-courses-summary-card__label">Publiées</span>
                        <strong class="teacher-courses-summary-card__value"><?php echo e($formatCount($portfolioPublished)); ?></strong>
                    </article>
                    <article class="teacher-courses-summary-card">
                        <span class="teacher-courses-summary-card__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                        <span class="teacher-courses-summary-card__label">Brouillons</span>
                        <strong class="teacher-courses-summary-card__value"><?php echo e($formatCount($portfolioDrafts)); ?></strong>
                    </article>
                    <article class="teacher-courses-summary-card">
                        <span class="teacher-courses-summary-card__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('wallet'); ?></span>
                        <span class="teacher-courses-summary-card__label">Revenus</span>
                        <strong class="teacher-courses-summary-card__value"><?php echo e($moneyFmt($portfolioRevenue)); ?></strong>
                    </article>
                    <article class="teacher-courses-summary-card">
                        <span class="teacher-courses-summary-card__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('users'); ?></span>
                        <span class="teacher-courses-summary-card__label">Étudiants</span>
                        <strong class="teacher-courses-summary-card__value"><?php echo e($formatCount($portfolioStudents)); ?></strong>
                    </article>
                </section>

                <section class="teacher-courses-filter-shell rounded-xl border border-gray-200 bg-white p-3 shadow-card dark:border-gray-700 dark:bg-gray-800" data-course-filters-section>
                    <form id="courses-filter-form" method="get" action="<?php echo e(site_url('teacher/courses')); ?>" class="space-y-3" data-course-filter-form>
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="level" value="<?php echo e($filterLevel); ?>">
                        <input type="hidden" name="per_page" value="<?php echo e((string) $filterPerPage); ?>">
                        <input type="hidden" name="period" value="<?php echo e($periodKey); ?>">
                        <input type="hidden" name="from" value="<?php echo e($periodFrom); ?>">
                        <input type="hidden" name="to" value="<?php echo e($periodTo); ?>">
                        <div class="teacher-courses-filter-row">
                            <label class="teacher-courses-filter-field teacher-courses-filter-field--search" for="searchCourseInput">
                                <span class="teacher-courses-filter-label">Recherche</span>
                                <span class="teacher-courses-filter-input-wrap">
                                    <i class="fas fa-search pointer-events-none text-gray-400" aria-hidden="true"></i>
                                    <input id="searchCourseInput" type="text" name="q" value="<?php echo e($filterQuery); ?>" placeholder="Titre, mot-clé..." data-autofilter="text" class="teacher-courses-filter-control teacher-courses-filter-control--search">
                                </span>
                            </label>
                            <label class="teacher-courses-filter-field">
                                <span class="teacher-courses-filter-label">Statut</span>
                                <select name="status" aria-label="Filtrer par statut" data-autofilter="select" class="teacher-courses-filter-control">
                                    <?php foreach ([
                                        'all' => 'Tous les statuts',
                                        'approved' => 'Publié',
                                        'draft' => 'Brouillon',
                                        'submitted' => 'En attente',
                                        'rejected' => 'Rejeté',
                                        'archived' => 'Archivé',
                                    ] as $key => $label): ?>
                                        <option value="<?php echo e($key); ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="teacher-courses-filter-field">
                                <span class="teacher-courses-filter-label">Trier par</span>
                                <select name="sort" aria-label="Trier les cours" data-autofilter="select" class="teacher-courses-filter-control">
                                    <?php foreach ([
                                        'updated_desc' => 'Plus récents',
                                        'revenue_desc' => 'Revenus',
                                        'students_desc' => 'Étudiants',
                                    ] as $key => $label): ?>
                                        <option value="<?php echo e($key); ?>" <?php echo $filterSort === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <a href="<?php echo e(site_url('teacher/course_create')); ?>" class="teacher-courses-create-button">
                                <i class="fas fa-plus-circle"></i>
                                <span>Créer un cours</span>
                            </a>
                        </div>
                        <span id="coursesCountStat" class="sr-only"><?php echo e((string) $paginationTotal); ?></span>
                    </form>
                </section>

                <section class="teacher-courses-hero rounded-2xl border border-slate-200/80 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40" hidden>
                    <div class="teacher-courses-hero__header flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="teacher-courses-hero__eyebrow text-xs uppercase tracking-wide text-white/80">
                                Pilotage des cours
                            </p>
                            <h1 class="teacher-courses-hero__title text-xl font-display font-semibold text-white">Pilotez vos cours</h1>
                            <p class="teacher-courses-hero__subtitle mt-1 text-sm text-white/90">Suivez vos cours et publiez quand tout est pr&ecirc;t.</p>
                        </div>
                        <div class="teacher-courses-hero__actions teacher-courses-action-group">
                            <a href="<?php echo e(site_url('teacher/course_updates')); ?>" class="teacher-courses-btn teacher-courses-btn--secondary">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                                <span>Historique</span>
                            </a>
                            <a href="<?php echo e(site_url('teacher/course_create')); ?>" class="teacher-courses-btn teacher-courses-btn--primary">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('plus-square'); ?></span>
                                <span>Nouveau cours</span>
                            </a>
                        </div>
                    </div>
                    <div class="teacher-courses-hero__stats">
                        <article class="teacher-courses-hero-stat" data-stat-tone="draft">
                            <p class="teacher-courses-hero-stat__label">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                                <span>Brouillons</span>
                            </p>
                            <p class="teacher-courses-hero-stat__value"><?php echo e($formatCount($draftCount)); ?></p>
                            <p class="teacher-courses-hero-stat__meta"><?php echo e($resumeCourses !== [] ? 'À reprendre rapidement' : 'Aucun brouillon prioritaire'); ?></p>
                        </article>
                        <article class="teacher-courses-hero-stat" data-stat-tone="pending">
                            <p class="teacher-courses-hero-stat__label">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>
                                <span>En attente</span>
                            </p>
                            <p class="teacher-courses-hero-stat__value"><?php echo e($formatCount($pendingCount)); ?></p>
                            <p class="teacher-courses-hero-stat__meta">Soumis pour validation</p>
                        </article>
                        <article class="teacher-courses-hero-stat" data-stat-tone="active">
                            <p class="teacher-courses-hero-stat__label">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                                <span>Actifs</span>
                            </p>
                            <p class="teacher-courses-hero-stat__value"><?php echo e($formatCount($activeCount)); ?></p>
                            <p class="teacher-courses-hero-stat__meta">Publi&eacute;s dans le catalogue</p>
                        </article>
                        <article class="teacher-courses-hero-stat" data-stat-tone="view">
                            <p class="teacher-courses-hero-stat__label">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span>
                                <span>Dans la vue</span>
                            </p>
                            <p class="teacher-courses-hero-stat__value"><?php echo e($formatCount($paginationTotal)); ?></p>
                            <p class="teacher-courses-hero-stat__meta"><?php echo e($activeFilterCount > 0 ? (string) $activeFilterCount . ' filtre(s) actif(s)' : $periodLabel); ?></p>
                        </article>
                    </div>
                </section>
                <?php if ($errors !== []): ?>
                    <section class="teacher-courses-alert teacher-courses-alert--danger rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200">
                        <span class="teacher-courses-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-triangle'); ?></span>
                        <div class="teacher-courses-alert__body">
                            <p class="teacher-courses-alert__title">Corrections requises</p>
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo e((string) $error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <section class="teacher-courses-alert teacher-courses-alert--success rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200">
                        <span class="teacher-courses-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                        <div class="teacher-courses-alert__body">
                            <p class="teacher-courses-alert__title">Action enregistr&eacute;e</p>
                            <p><?php echo e($success); ?></p>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($courses === []): ?>
                    <section class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center shadow-card dark:border-gray-700 dark:bg-gray-800">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-200">
                            <i class="fas fa-book-open text-xl"></i>
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Aucun cours trouvé</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Créez votre première formation ou modifiez vos filtres de recherche.</p>
                        <div class="mt-5 flex justify-center">
                            <a href="<?php echo e(site_url('teacher/course_create')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                                <i class="fas fa-plus-circle"></i>
                                <span>Créer un nouveau cours</span>
                            </a>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="teacher-courses-grid grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($courses as $course): ?>
                            <?php
                                $courseId = (int) ($course['id'] ?? 0);
                                $courseTitle = trim((string) ($course['title'] ?? 'Cours sans titre'));
                                $courseDescription = trim((string) ($course['description'] ?? ''));
                                $coursePrice = (int) ($course['price_xof'] ?? 0);
                                $coursePriceLabel = $coursePrice > 0 ? $moneyFmt($coursePrice) : 'Gratuit';
                                $courseUpdated = $fmtDate($course['updated_at'] ?? ($course['created_at'] ?? null));
                                $coverUrl = $resolveMediaUrl($course['cover_path'] ?? '');
                                $coverAlt = trim((string) ($course['cover_alt'] ?? $courseTitle));
                                $workflow = strtolower(trim((string) ($course['workflow_status'] ?? ($course['status'] ?? 'draft'))));
                                if (!in_array($workflow, ['draft', 'submitted', 'approved', 'rejected', 'archived'], true)) {
                                    $workflow = 'draft';
                                }
                                $submission = is_array($course['submission'] ?? null) ? $course['submission'] : [];
                                $chaptersCnt = max(0, (int) ($submission['chapters_cnt'] ?? 0));
                                $lessonsCnt = max(0, (int) ($submission['lessons_cnt'] ?? 0));
                                $meetingsCnt = max(0, (int) ($submission['meetings_cnt'] ?? 0));
                                $enrollments = max(0, (int) (($course['stats']['enrollments'] ?? 0) ?: 0));
                                $views = max(0, (int) (($course['stats']['views'] ?? 0) ?: 0));
                                $paidSales = max(0, (int) (($course['stats']['paid_sales'] ?? 0) ?: 0));
                                $paidAmountXof = max(0, (int) (($course['stats']['paid_amount_xof'] ?? 0) ?: 0));
                                $rating = (float) (($course['stats']['rating'] ?? 0) ?: 0);
                                $ratingCount = max(0, (int) (($course['stats']['rating_count'] ?? 0) ?: 0));
                                $courseRatingLabel = $ratingCount > 0 ? $formatRating($rating) : 'Aucun avis';
                                $courseRatingMeta = $ratingCount > 0 ? ((string) $ratingCount . ' avis') : '0 avis';
                                $coursePaidSalesMeta = (string) $paidSales . ' commande(s) payée(s)';
                                $statusConfig = match ($workflow) {
                                    'approved' => ['label' => 'Publié', 'tone' => 'emerald', 'icon' => 'check-circle'],
                                    'submitted' => ['label' => 'En attente', 'tone' => 'amber', 'icon' => 'clock'],
                                    'rejected' => ['label' => 'Rejeté', 'tone' => 'rose', 'icon' => 'triangle-exclamation'],
                                    'archived' => ['label' => 'Archivé', 'tone' => 'slate', 'icon' => 'folder'],
                                    default => ['label' => 'Brouillon', 'tone' => 'slate', 'icon' => 'pen-to-square'],
                                };
                                $statusBadgeClass = match ($statusConfig['tone']) {
                                    'emerald' => 'border border-emerald-200 bg-emerald-100 text-emerald-800 shadow-sm dark:border-emerald-300 dark:bg-emerald-500 dark:text-white',
                                    'amber' => 'border border-amber-200 bg-amber-100 text-amber-800 shadow-sm dark:border-amber-300 dark:bg-amber-500 dark:text-white',
                                    'rose' => 'border border-rose-200 bg-rose-100 text-rose-800 shadow-sm dark:border-rose-300 dark:bg-rose-500 dark:text-white',
                                    default => 'border border-slate-200 bg-slate-100 text-slate-700 shadow-sm dark:border-slate-300 dark:bg-slate-500 dark:text-white',
                                };
                                $progressPct = match ($workflow) {
                                    'submitted', 'approved', 'rejected', 'archived' => 100,
                                    default => $submission !== [] ? 55 : 18,
                                };
                                if ($workflow === 'draft' && $chaptersCnt > 0 && $lessonsCnt > 0) {
                                    $progressPct = 55;
                                }
                                if ($workflow === 'approved' && $progressPct < 100) {
                                    $progressPct = 100;
                                }
                                $progressLabel = $workflow === 'approved' ? 'Complétion moyenne' : 'Progression de création';
                                $primaryLabel = match ($workflow) {
                                    'draft' => 'Continuer',
                                    'submitted' => 'Détails',
                                    'rejected' => 'Corriger',
                                    'archived' => 'Détails',
                                    default => 'Modifier',
                                };
                                $primaryUrl = match ($workflow) {
                                    'submitted', 'archived' => site_url('teacher/course_actions?id=' . $courseId . ($listQuery !== '' ? ('&list=' . rawurlencode($listQuery)) : '')),
                                    default => site_url('teacher/course_edit?id=' . $courseId),
                                };
                                $secondaryLabel = match ($workflow) {
                                    'draft' => 'Aperçu',
                                    'submitted' => 'Modifier',
                                    'rejected' => 'Aperçu',
                                    'archived' => 'Détails',
                                    default => 'Statistiques',
                                };
                                $secondaryUrl = match ($workflow) {
                                    'draft', 'rejected' => site_url('teacher/course_preview?id=' . $courseId),
                                    'submitted' => site_url('teacher/course_edit?id=' . $courseId),
                                    'archived' => site_url('teacher/course_actions?id=' . $courseId . ($listQuery !== '' ? ('&list=' . rawurlencode($listQuery)) : '')),
                                    default => '',
                                };
                                $archiveAction = $workflow === 'archived' ? 'restore' : 'archive';
                                $archiveLabel = $workflow === 'archived' ? 'Restaurer' : 'Archiver';
                                $archiveTitle = $workflow === 'archived' ? 'Restaurer ce cours' : 'Archiver ce cours';
                                $archiveMessage = $workflow === 'archived'
                                    ? 'Le cours redeviendra visible dans votre liste.'
                                    : 'Le cours sera retiré du catalogue.';
                                $archiveVariant = $workflow === 'archived' ? 'success' : 'danger';
                                $cardSubline = match ($workflow) {
                                    'approved' => $lessonsCnt . ' leçons · ' . $chaptersCnt . ' modules · ' . $enrollments . ' étudiants',
                                    'submitted' => 'Soumis le ' . $courseUpdated . ' · En cours de validation',
                                    'rejected' => 'Soumis le ' . $courseUpdated . ' · À corriger',
                                    'archived' => 'Archivé le ' . $courseUpdated . ' · Hors catalogue',
                                    default => 'En cours de création · ' . $lessonsCnt . ' leçons',
                                };
                            ?>
                            <article class="teacher-course-card flex flex-col transition duration-200 hover:-translate-y-0.5" data-course-state="<?php echo e($workflow); ?>">
                                <div class="teacher-course-card__media relative overflow-hidden">
                                    <?php if ($coverUrl !== ''): ?>
                                        <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt !== '' ? $coverAlt : $courseTitle); ?>" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-600 via-primary-600 to-accent-500 text-white">
                                            <i class="fas fa-book-open text-3xl opacity-90"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="teacher-course-card__status absolute left-3 top-3 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?php echo e($statusBadgeClass); ?>">
                                        <i class="fas fa-<?php echo e((string) $statusConfig['icon']); ?> mr-1"></i>
                                        <?php echo e((string) $statusConfig['label']); ?>
                                    </span>
                                </div>
                                <div class="teacher-course-card__body flex flex-1 flex-col">
                                    <div class="teacher-course-card__header">
                                        <h3 class="teacher-course-card__title text-lg font-bold text-gray-900 dark:text-white" title="<?php echo e($courseTitle); ?>"><?php echo e($courseTitle); ?></h3>
                                        <p class="teacher-course-card__subline text-xs text-gray-500 dark:text-gray-400"><?php echo e($cardSubline); ?></p>
                                    </div>

                                    <div class="teacher-course-card__progress">
                                        <div class="teacher-course-card__progress-head">
                                            <span class="teacher-course-card__progress-label"><?php echo e($progressLabel); ?></span>
                                            <span class="teacher-course-card__progress-value"><?php echo e((string) $progressPct); ?>%</span>
                                        </div>
                                        <div
                                            class="teacher-course-card__progress-track"
                                            role="progressbar"
                                            aria-label="<?php echo e($progressLabel . ' : ' . (string) $progressPct . '%'); ?>"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-valuenow="<?php echo e((string) $progressPct); ?>"
                                        >
                                            <div class="teacher-course-card__progress-fill" style="width: <?php echo e((string) $progressPct); ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="teacher-course-card__meta">
                                        <?php if ($workflow === 'approved' && $ratingCount > 0): ?>
                                            <span class="teacher-course-card__meta-item" title="Note moyenne">
                                                <i class="fas fa-star text-yellow-400" aria-hidden="true"></i>
                                                <span><?php echo e($formatRating($rating)); ?></span>
                                            </span>
                                            <span class="teacher-course-card__meta-item" title="Prix">
                                                <i class="fas fa-tag" aria-hidden="true"></i>
                                                <span>Prix : <?php echo e($coursePriceLabel); ?></span>
                                            </span>
                                            <span class="teacher-course-card__meta-item" title="Avis">
                                                <i class="fas fa-comment" aria-hidden="true"></i>
                                                <span><?php echo e((string) $ratingCount); ?> avis</span>
                                            </span>
                                        <?php elseif ($workflow === 'draft'): ?>
                                            <span class="teacher-course-card__meta-item" title="Prix">
                                                <i class="fas fa-tag" aria-hidden="true"></i>
                                                <span>Prix : <?php echo e($coursePriceLabel); ?></span>
                                            </span>
                                            <span class="teacher-course-card__meta-item" title="Dernière modification">
                                                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                                <span><?php echo e($courseUpdated); ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="teacher-course-card__meta-item" title="Prix">
                                                <i class="fas fa-tag" aria-hidden="true"></i>
                                                <span>Prix : <?php echo e($coursePriceLabel); ?></span>
                                            </span>
                                            <span class="teacher-course-card__meta-item" title="Suivi">
                                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                                <span><?php echo e($views > 0 ? (string) $views . ' vues' : 'Suivi en cours'); ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="teacher-course-card__actions flex items-center gap-2">
                                        <a href="<?php echo e($primaryUrl); ?>" class="teacher-course-card__primary-action <?php echo e($courseActionButtonClass($primaryLabel)); ?>">
                                            <?php echo e($primaryLabel); ?>
                                        </a>
                                        <?php if ($workflow === 'approved'): ?>
                                            <button
                                                type="button"
                                                class="teacher-course-card__secondary-action"
                                                data-course-stats-open
                                                data-course-title="<?php echo e($courseTitle); ?>"
                                                data-course-teacher="<?php echo e($teacherDisplayName); ?>"
                                                data-course-students="<?php echo e((string) $enrollments); ?>"
                                                data-course-revenue="<?php echo e($moneyFmt($paidAmountXof)); ?>"
                                                data-course-sales="<?php echo e($coursePaidSalesMeta); ?>"
                                                data-course-rating="<?php echo e($courseRatingLabel); ?>"
                                                data-course-reviews="<?php echo e($courseRatingMeta); ?>"
                                                data-course-completion="<?php echo e((string) $progressPct); ?>%"
                                                data-course-lessons="<?php echo e((string) $lessonsCnt); ?> leçons"
                                                data-course-modules="<?php echo e((string) $chaptersCnt); ?> modules"
                                                data-course-views="<?php echo e((string) $views); ?> vues"
                                                data-course-price="<?php echo e($coursePriceLabel); ?>"
                                            >
                                                <i class="fas fa-chart-line" aria-hidden="true"></i>
                                                <span>Statistiques</span>
                                            </button>
                                        <?php endif; ?>
                                        <details class="teacher-course-card-menu relative">
                                            <summary class="teacher-course-card-menu__trigger" aria-label="Autres actions" title="Autres actions">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('more-vertical'); ?></span>
                                            </summary>
                                            <div class="teacher-course-card-menu__panel" role="menu">
                                                <?php if ($workflow !== 'approved'): ?>
                                                    <a href="<?php echo e($secondaryUrl); ?>" class="teacher-course-card-menu__item" role="menuitem">
                                                        <i class="fas <?php echo $secondaryLabel === 'Statistiques' ? 'fa-chart-line' : 'fa-eye'; ?>" aria-hidden="true"></i>
                                                        <span><?php echo e($secondaryLabel); ?></span>
                                                    </a>
                                                <?php endif; ?>
                                                <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="teacher-course-card-menu__form">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="<?php echo e($archiveAction); ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                                    <input type="hidden" name="list_query" value="<?php echo e($listQuery); ?>">
                                                    <button
                                                        type="submit"
                                                        class="teacher-course-card-menu__item teacher-course-card-menu__item--danger"
                                                        data-confirm-action="form"
                                                        data-confirm-title="<?php echo e($archiveTitle); ?>"
                                                        data-confirm-message="<?php echo e($archiveMessage); ?>"
                                                        data-confirm-label="<?php echo e($archiveLabel); ?>"
                                                        data-confirm-variant="<?php echo e($archiveVariant); ?>"
                                                    >
                                                        <i class="fas <?php echo e($workflow === 'archived' ? 'fa-undo-alt' : 'fa-trash-alt'); ?>" aria-hidden="true"></i>
                                                        <span><?php echo e($archiveLabel); ?></span>
                                                    </button>
                                                </form>
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>

                    <div class="flex justify-center mt-8">
                        <nav class="flex items-center gap-1">
                            <a href="<?php echo e($buildUrl(['page' => max(1, $paginationPage - 1)])); ?>" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 <?php echo $paginationPage > 1 ? '' : 'pointer-events-none opacity-50'; ?>">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </a>
                            <?php for ($p = max(1, $paginationPage - 1); $p <= min($paginationTotalPages, $paginationPage + 1); $p++): ?>
                                <a href="<?php echo e($buildUrl(['page' => $p])); ?>" class="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-semibold transition <?php echo $p === $paginationPage ? 'bg-primary-600 text-white shadow-sm' : 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'; ?>">
                                    <?php echo e((string) $p); ?>
                                </a>
                            <?php endfor; ?>
                            <a href="<?php echo e($buildUrl(['page' => min($paginationTotalPages, $paginationPage + 1)])); ?>" class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 <?php echo $paginationPage < $paginationTotalPages ? '' : 'pointer-events-none opacity-50'; ?>">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </a>
                        </nav>
                    </div>
                <?php endif; ?>

                <?php if ($resumeCourses !== []): ?>
                    <section class="teacher-courses-panel rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40" data-course-resume-section hidden>
                        <div class="teacher-courses-section-head flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="teacher-courses-section-head__eyebrow text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                                    <span>&Agrave; reprendre</span>
                                </p>
                                <h2 class="teacher-courses-section-head__title text-lg font-semibold text-slate-900 dark:text-slate-100">Vos derniers brouillons</h2>
                                <p class="teacher-courses-section-head__meta">Finalisez les brouillons les plus r&eacute;cents avant la soumission.</p>
                            </div>
                            <a href="<?php echo e($buildUrl(['status' => 'draft', 'page' => 1])); ?>" class="teacher-courses-link-chip dash-btn dash-btn--ghost inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                                <span>Voir tout</span>
                            </a>
                        </div>
                        <div class="mt-3 grid gap-3 xl:grid-cols-2">
                            <?php foreach ($resumeCourses as $draftCourse): ?>
                                <?php
                                    $draftId = (int) ($draftCourse['id'] ?? 0);
                                    $draftTitle = trim((string) ($draftCourse['title'] ?? 'Cours sans titre'));
                                    $draftUpdated = $fmtDate($draftCourse['updated_at'] ?? ($draftCourse['created_at'] ?? null));
                                    $draftStatus = (string) ($draftCourse['workflow_status'] ?? ($draftCourse['status'] ?? 'draft'));
                                    if (!isset($workflowLabels[$draftStatus])) {
                                        $draftStatus = 'draft';
                                    }
$draftStatusLabel = $workflowLabels[$draftStatus] ?? 'Brouillon';
                                    $draftBadgeClass = $stateBadgeMap[$draftStatusLabel] ?? $stateBadgeMap['Brouillon'];
                                    $draftSubmission = is_array($draftCourse['submission'] ?? null) ? $draftCourse['submission'] : ['chapters_cnt' => 0, 'lessons_cnt' => 0, 'meetings_cnt' => 0];
                                    $draftReady = !empty($draftCourse['submission_ready']);
                                    $draftReadyLabel = $draftReady ? $doneLabelFeminine : $urgentLabelFeminine;
                                    $draftReadyIcon = $draftReady ? 'check-circle' : 'alert-circle';
                                ?>
                                <article class="teacher-courses-draft-card rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="teacher-courses-draft-card__title text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                                                <span><?php echo e($draftTitle); ?></span>
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-300">Mise &agrave; jour : <?php echo e($draftUpdated); ?></p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-semibold <?php echo e($draftBadgeClass); ?>"><?php echo e($draftStatusLabel); ?></span>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-300">
                                        Plan : <?php echo e((string) ($draftSubmission['chapters_cnt'] ?? 0)); ?> chap.
                                        &middot; <?php echo e((string) ($draftSubmission['lessons_cnt'] ?? 0)); ?> le&ccedil;ons
                                        &middot; <?php echo e((string) ($draftSubmission['meetings_cnt'] ?? 0)); ?> rendez-vous
                                    </p>
                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-xs font-semibold <?php echo e($stateBadgeMap[$draftReadyLabel] ?? $stateBadgeMap['Urgent']); ?>">
                                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($draftReadyIcon); ?></span>
                                            <span>Soumission : <?php echo e($draftReadyLabel); ?></span>
                                        </span>
                                        <a href="<?php echo e(site_url('teacher/course_edit?id=' . $draftId)); ?>" class="teacher-courses-btn teacher-courses-btn--secondary teacher-courses-btn--compact teacher-courses-action-strong">
                                            <span>Continuer</span>
                                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('chevron-right'); ?></span>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                <section class="teacher-courses-panel filter-bar sticky top-2 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40" data-course-filters-section hidden>
                    <form id="courses-filter-form-legacy" method="get" action="<?php echo e(site_url('teacher/courses')); ?>" class="teacher-courses-filter-form space-y-3" data-course-filter-form data-period-toggle data-period-custom="<?php echo $periodKey === 'custom' ? 'true' : 'false'; ?>" data-filter-open="<?php echo $isFilterPanelOpen ? 'true' : 'false'; ?>">
                        <input type="hidden" name="page" value="1">
                        <input type="hidden" name="period" value="<?php echo e($periodKey); ?>">
                        <div class="teacher-courses-filter-top flex flex-wrap items-center gap-2" data-course-period-panel>
                            <div class="teacher-courses-period-cluster">
                                <p class="dash-section-label">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
                                    <span>P&eacute;riode</span>
                                </p>
                                <div class="teacher-period-tabs">
                                    <?php foreach ($periodOptions as $key => $label): ?>
                                        <?php $active = $periodKey === $key; ?>
                                        <button
                                            type="submit"
                                            name="period"
                                            value="<?php echo e($key); ?>"
                                            class="period-btn <?php echo $active ? 'period-btn--active' : 'period-btn--inactive'; ?>"
                                        >
                                            <?php echo e(html_entity_decode($label, ENT_QUOTES, 'UTF-8')); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="teacher-courses-filter-main">
                                <label class="teacher-courses-search-compact" for="course-filter-q">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('search'); ?></span>
                                    <input id="course-filter-q" type="text" name="q" value="<?php echo e($filterQuery); ?>" placeholder="Rechercher un cours..." data-autofilter="text" class="teacher-courses-control">
                                </label>
                                <span class="teacher-courses-filter-summary teacher-period-label text-xs text-slate-500 dark:text-slate-300"><?php echo e($periodLabel); ?></span>
                                <button type="button" class="teacher-courses-filter-toggle dash-btn dash-btn--ghost inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold" data-filter-toggle aria-expanded="<?php echo $isFilterPanelOpen ? 'true' : 'false'; ?>">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('filter'); ?></span>
                                    <span><?php echo $isFilterPanelOpen ? 'Masquer les filtres' : 'Filtres avanc&eacute;s'; ?></span>
                                </button>
                            </div>
                        </div>

                        <div class="period-custom-fields teacher-courses-custom-range grid gap-3 md:grid-cols-3">
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label">Du</span>
                                <input type="date" name="from" value="<?php echo e($periodFrom); ?>" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                            </label>
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label">Au</span>
                                <input type="date" name="to" value="<?php echo e($periodTo); ?>" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                            </label>
                            <button type="submit" name="period" value="custom" class="teacher-courses-btn teacher-courses-btn--primary">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('filter'); ?></span>
                                <span>Appliquer</span>
                            </button>
                        </div>

                        <div class="teacher-courses-form-grid grid gap-3 md:grid-cols-3" data-filter-panel<?php echo $isFilterPanelOpen ? '' : ' hidden'; ?>>
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label">Statut</span>
                                <select name="status" data-autofilter="select" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                    <?php foreach (['all' => 'Tous', 'draft' => 'Brouillon', 'submitted' => 'En attente', 'approved' => 'Actif', 'rejected' => 'Rejet&eacute;', 'archived' => 'Archiv&eacute;'] as $key => $label): ?>
                                        <option value="<?php echo e($key); ?>" <?php echo $filterStatus === $key ? 'selected' : ''; ?>><?php echo e($decodeLabel($label)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label">Niveau</span>
                                <select name="level" data-autofilter="select" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                    <?php foreach ($levelLabels as $levelKey => $levelLabel): ?>
                                        <option value="<?php echo e($levelKey); ?>" <?php echo $filterLevel === $levelKey ? 'selected' : ''; ?>>
                                            <?php echo e($levelLabel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label">Prix min (F CFA)</span>
                                <input type="number" name="price_min" min="0" step="1" value="<?php echo e($priceMinValue); ?>" data-autofilter="text" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                            </label>
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label">Prix max (F CFA)</span>
                                <input type="number" name="price_max" min="0" step="1" value="<?php echo e($priceMaxValue); ?>" data-autofilter="text" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                            </label>
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label">Trier par</span>
                                <select name="sort" data-autofilter="select" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                    <?php foreach ($sortLabels as $sortKey => $sortLabel): ?>
                                        <option value="<?php echo e($sortKey); ?>" <?php echo $filterSort === $sortKey ? 'selected' : ''; ?>>
                                            <?php echo e($sortLabel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label">Taille</span>
                                <select name="per_page" data-autofilter="select" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                    <?php foreach ($perPageOptions as $option): ?>
                                        <option value="<?php echo e((string) $option); ?>" <?php echo $filterPerPage === (int) $option ? 'selected' : ''; ?>>
                                            <?php echo e((string) $option); ?> / page
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <div class="teacher-courses-field teacher-courses-field--actions">
                                <span class="teacher-courses-field__label">Actions</span>
                                <div class="teacher-courses-field__actions">
                                    <button type="submit" class="teacher-courses-btn teacher-courses-btn--primary">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('filter'); ?></span>
                                        <span>Appliquer filtres</span>
                                    </button>
                                    <a href="<?php echo e(site_url('teacher/courses')); ?>" class="teacher-courses-btn teacher-courses-btn--secondary">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('repeat'); ?></span>
                                        <span>R&eacute;initialiser</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="teacher-courses-status-row flex flex-wrap items-center gap-2" data-course-status-pills hidden>
                            <?php
                                $statusPills = [
                                    'all' => 'Tous',
                                    'draft' => 'Brouillons',
                                    'submitted' => 'En attente',
                                    'approved' => 'Actifs',
                                    'rejected' => 'Rejet&eacute;s',
                                    'archived' => 'Archiv&eacute;s',
                                ];
                            ?>
                            <?php foreach ($statusPills as $pillKey => $pillLabel): ?>
                                <?php
                                    $pillCount = (int) ($statusCounters[$pillKey] ?? 0);
                                    $pillActive = $filterStatus === $pillKey;
                                    $statusIconMap = [
                                        'draft' => 'file-text',
                                        'submitted' => 'alert-circle',
                                        'approved' => 'check-circle',
                                        'rejected' => 'alert-triangle',
                                        'archived' => 'folder',
                                    ];
                                    $pillIcon = $statusIconMap[$pillKey] ?? 'layout';
                                ?>
                                <a href="<?php echo e($buildUrl(['status' => $pillKey, 'page' => 1])); ?>" class="teacher-courses-status-pill inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold <?php echo $pillActive ? 'is-active border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400/60 dark:bg-indigo-500/10 dark:text-indigo-200' : 'border-slate-300 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200'; ?>">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($pillIcon); ?></span>
                                    <span><?php echo e($decodeLabel($pillLabel)); ?></span>
                                    <span class="teacher-courses-status-pill__count rounded-full border px-2 py-0.5 text-[0.68rem] <?php echo $pillActive ? 'border-indigo-400/60' : 'border-slate-200 dark:border-slate-700'; ?>"><?php echo e((string) $pillCount); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </section>
                <?php if ($periodWarning !== ''): ?>
                    <section class="teacher-courses-alert teacher-courses-alert--warning rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                        <span class="teacher-courses-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>
                        <div class="teacher-courses-alert__body">
                            <p class="teacher-courses-alert__title">Point sur la p&eacute;riode</p>
                            <p><?php echo e($periodWarning); ?></p>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="teacher-courses-panel rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40" data-course-list-section hidden>
                    <div class="teacher-courses-section-head flex flex-wrap items-center justify-between gap-3" data-course-list-toolbar>
                        <div>
                            <p class="teacher-courses-section-head__eyebrow text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span>
                                <span>Liste</span>
                            </p>
                            <h2 class="teacher-courses-section-head__title text-lg font-semibold text-slate-900 dark:text-slate-100">Catalogue affich&eacute;</h2>
                            <p class="teacher-courses-section-head__meta text-sm text-slate-600 dark:text-slate-300">
                                <?php echo e((string) $paginationTotal); ?> cours &middot; affichage <?php echo e((string) $paginationStart); ?>-<?php echo e((string) $paginationEnd); ?>
                            </p>
                        </div>
                        <div class="teacher-courses-toolbar-actions teacher-courses-action-group">
                            <a href="<?php echo e(site_url('teacher/course_updates')); ?>" class="teacher-courses-btn teacher-courses-btn--secondary">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                                <span>Historique</span>
                            </a>
                            <a href="<?php echo e(site_url('teacher/course_create')); ?>" class="teacher-courses-btn teacher-courses-btn--primary">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('plus-square'); ?></span>
                                <span>Nouveau cours</span>
                            </a>
                        </div>
                    </div>

                    <form id="courses-bulk-form" method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="teacher-courses-bulk-row mt-4" data-course-bulk-form>
                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                        <input type="hidden" name="action" value="bulk">
                        <input type="hidden" name="list_query" value="<?php echo e($listQuery); ?>">
                        <div class="teacher-courses-bulk-row__controls">
                            <label class="teacher-courses-check inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                                <input id="bulk-select-all" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                Tout s&eacute;lectionner
                            </label>
                            <label class="teacher-courses-field text-xs text-slate-600 dark:text-slate-300">
                                <span class="teacher-courses-field__label inline-flex items-center gap-1">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('settings'); ?></span>
                                    <span>Action group&eacute;e</span>
                                </span>
                                <select name="bulk_action" class="teacher-courses-control mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                    <option value="">-- S&eacute;lectionner --</option>
                                    <option value="submit">Soumettre</option>
                                    <option value="archive">Archiver</option>
                                    <option value="restore">Restaurer</option>
                                    <option value="duplicate">Dupliquer</option>
                                </select>
                            </label>
                        </div>
                        <div class="teacher-courses-bulk-row__actions">
                            <button type="submit" class="teacher-courses-btn teacher-courses-btn--primary">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('filter'); ?></span>
                                <span>Appliquer</span>
                            </button>
                        </div>
                    </form>

                    <?php if ($courses === []): ?>
                        <div id="emptyCoursesMsg" class="teacher-courses-empty mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900/30 dark:text-slate-300">
                            <span class="teacher-courses-empty__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                            <p class="teacher-courses-empty__title">Aucun cours ne correspond à vos filtres.</p>
                            <p class="teacher-courses-empty__meta">Essayez un autre statut ou réinitialisez la recherche.</p>
                        </div>
                    <?php else: ?>
                        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-card dark:border-gray-700 dark:bg-gray-800" hidden>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cours</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Étudiants</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix</th>
                                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="coursesTableBody" class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <?php foreach ($courses as $course): ?>
                                            <?php
                                                $courseId = (int) ($course['id'] ?? 0);
                                                $courseTitle = trim((string) ($course['title'] ?? 'Cours sans titre'));
                                                $courseDescription = trim((string) ($course['description'] ?? ''));
                                                $courseLevel = (string) ($course['level'] ?? '');
                                                $courseLevelLabel = $levelLabels[$courseLevel] ?? ($courseLevel !== '' ? $courseLevel : $decodeLabel('Niveau non défini'));
                                                $coursePrice = (int) ($course['price_xof'] ?? 0);
                                                $coursePriceLabel = $coursePrice > 0 ? $moneyFmt($coursePrice) : 'Gratuit';
                                                $courseUpdated = $fmtDate($course['updated_at'] ?? ($course['created_at'] ?? null));
                                                $coverUrl = $resolveMediaUrl($course['cover_path'] ?? '');
                                                $coverAlt = trim((string) ($course['cover_alt'] ?? 'Miniature du cours'));
                                                $workflow = (string) ($course['workflow_status'] ?? ($course['status'] ?? 'draft'));
                                                if (!in_array($workflow, ['draft', 'submitted', 'approved', 'rejected', 'archived'], true)) {
                                                    $workflow = 'draft';
                                                }
                                                $tableStatusLabel = match ($workflow) {
                                                    'submitted' => 'En attente',
                                                    'approved' => 'Publié',
                                                    'rejected' => 'Rejeté',
                                                    'archived' => 'Archivé',
                                                    default => 'Brouillon',
                                                };
                                                $tableBadgeClass = $stateBadgeMap[$tableStatusLabel] ?? $stateBadgeMap['Brouillon'];
                                                $enrollments = (int) (($course['stats']['enrollments'] ?? 0) ?: 0);
                                                $manageUrl = site_url('teacher/course_actions?id=' . $courseId . ($listQuery !== '' ? ('&list=' . rawurlencode($listQuery)) : ''));
                                                $archiveAction = $workflow === 'archived' ? 'restore' : 'archive';
                                                $archiveLabel = $workflow === 'archived' ? 'Restaurer' : 'Archiver';
                                                $archiveTitle = $workflow === 'archived' ? 'Restaurer ce cours' : 'Archiver ce cours';
                                                $archiveMessage = $workflow === 'archived'
                                                    ? 'Le cours redeviendra visible dans votre liste.'
                                                    : 'Le cours sera retiré du catalogue.';
                                                $archiveVariant = $workflow === 'archived' ? 'success' : 'danger';
                                            ?>
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden shrink-0">
                                                            <?php if ($coverUrl !== ''): ?>
                                                                <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" loading="lazy" decoding="async" class="w-full h-full object-cover">
                                                            <?php else: ?>
                                                                <i class="fas fa-book-open text-gray-400"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <div class="flex items-center gap-2">
                                                                <label class="inline-flex items-center">
                                                                    <input type="checkbox" name="selected_ids[]" value="<?php echo e((string) $courseId); ?>" form="courses-bulk-form" class="course-selector h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                                </label>
                                                                <span class="font-medium text-gray-900 dark:text-white truncate"><?php echo e($courseTitle); ?></span>
                                                            </div>
                                                            <?php if ($courseDescription !== ''): ?>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo e($excerptText($courseDescription, 70)); ?></p>
                                                            <?php else: ?>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Niveau : <?php echo e($courseLevelLabel); ?> · Mise à jour : <?php echo e($courseUpdated); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold <?php echo e($tableBadgeClass); ?>">
                                                        <?php echo e($tableStatusLabel); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                                    <?php echo e($formatCount($enrollments)); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900 dark:text-white">
                                                    <?php echo e($coursePriceLabel); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                                    <div class="inline-flex items-center justify-end gap-2">
                                                        <a href="<?php echo e($manageUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900/70 dark:text-gray-100 dark:hover:border-gray-500">
                                                            <i class="fas fa-edit"></i>
                                                            <span>Gérer</span>
                                                        </a>
                                                        <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex">
                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                            <input type="hidden" name="action" value="<?php echo e($archiveAction); ?>">
                                                            <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                                            <input type="hidden" name="list_query" value="<?php echo e($listQuery); ?>">
                                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition <?php echo $workflow === 'archived' ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:border-emerald-400 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200' : 'border-rose-300 bg-rose-50 text-rose-700 hover:border-rose-400 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200'; ?>" data-confirm-action="form" data-confirm-title="<?php echo e($archiveTitle); ?>" data-confirm-message="<?php echo e($archiveMessage); ?>" data-confirm-label="<?php echo e($archiveLabel); ?>" data-confirm-variant="<?php echo e($archiveVariant); ?>">
                                                                <i class="fas <?php echo $workflow === 'archived' ? 'fa-undo-alt' : 'fa-trash-alt'; ?>"></i>
                                                                <span><?php echo e($archiveLabel); ?></span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
                <section class="teacher-courses-panel rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40" data-course-pagination hidden>
                    <div class="teacher-courses-pagination flex flex-wrap items-center justify-between gap-3">
                        <p class="teacher-courses-pagination__meta text-xs text-slate-500 dark:text-slate-300">Page <?php echo e((string) $paginationPage); ?> / <?php echo e((string) $paginationTotalPages); ?></p>
                        <nav class="teacher-courses-pagination__nav flex flex-wrap items-center gap-1" data-course-pagination-nav>
                            <a href="<?php echo e($buildUrl(['page' => max(1, $paginationPage - 1)])); ?>" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:text-slate-100 <?php echo $paginationPage > 1 ? '' : 'opacity-50 pointer-events-none is-disabled'; ?>">&laquo;</a>
                            <?php if ($pageWindowStart > 1): ?>
                                <a href="<?php echo e($buildUrl(['page' => 1])); ?>" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:text-slate-100">1</a>
                                <?php if ($pageWindowStart > 2): ?>
                                    <span class="teacher-courses-pagination__ellipsis px-2 text-xs text-slate-400">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($p = $pageWindowStart; $p <= $pageWindowEnd; $p++): ?>
                                <a href="<?php echo e($buildUrl(['page' => $p])); ?>" class="inline-flex items-center justify-center rounded-lg border px-2.5 py-1 text-xs font-semibold transition <?php echo $p === $paginationPage ? 'is-active border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400/60 dark:bg-indigo-500/10 dark:text-indigo-200' : 'border-slate-300 text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:text-slate-100'; ?>"
                                    <?php echo e((string) $p); ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($pageWindowEnd < $paginationTotalPages): ?>
                                <?php if ($pageWindowEnd < $paginationTotalPages - 1): ?>
                                    <span class="teacher-courses-pagination__ellipsis px-2 text-xs text-slate-400">...</span>
                                <?php endif; ?>
                                <a href="<?php echo e($buildUrl(['page' => $paginationTotalPages])); ?>" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:text-slate-100"><?php echo e((string) $paginationTotalPages); ?></a>
                            <?php endif; ?>
                            <a href="<?php echo e($buildUrl(['page' => min($paginationTotalPages, $paginationPage + 1)])); ?>" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:text-slate-100 <?php echo $paginationPage < $paginationTotalPages ? '' : 'opacity-50 pointer-events-none is-disabled'; ?>">&raquo;</a>
                        </nav>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div id="course-action-modal" class="teacher-courses-modal fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/60 p-4" aria-hidden="true">
        <div class="teacher-courses-modal__panel w-full max-w-md rounded-2xl border p-5 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="course-action-modal-title">
            <h2 id="course-action-modal-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Confirmation</h2>
            <p id="course-action-modal-message" class="mt-2 text-sm text-slate-600 dark:text-slate-300">Confirmez-vous cette action ?</p>
            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <button type="button" id="course-action-modal-cancel" class="teacher-courses-btn teacher-courses-btn--secondary" data-modal-close>Annuler</button>
                <button type="button" id="course-action-modal-confirm" class="teacher-courses-btn teacher-courses-btn--primary">Confirmer</button>
            </div>
        </div>
    </div>

    <div id="course-stats-modal" class="teacher-course-stats-modal hidden" data-course-stats-modal aria-hidden="true">
        <div class="teacher-course-stats-modal__backdrop" data-course-stats-close></div>
        <section class="teacher-course-stats-modal__panel" role="dialog" aria-modal="true" aria-labelledby="course-stats-modal-title">
            <header class="teacher-course-stats-modal__header">
                <div>
                    <h2 id="course-stats-modal-title" class="teacher-course-stats-modal__title">
                        <span class="teacher-course-stats-modal__title-icon" aria-hidden="true">
                            <i class="fas fa-chart-pie"></i>
                        </span>
                        <span>Statistiques du cours</span>
                    </h2>
                    <p class="teacher-course-stats-modal__subtitle">
                        <span data-course-stats-title>Cours</span>
                        <span aria-hidden="true">&middot;</span>
                        <span data-course-stats-teacher>Enseignant</span>
                    </p>
                </div>
                <button type="button" class="teacher-course-stats-modal__close" data-course-stats-close aria-label="Fermer les statistiques">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </header>

            <div class="teacher-course-stats-modal__metrics" aria-label="Résumé des statistiques du cours">
                <article class="teacher-course-stats-modal__metric teacher-course-stats-modal__metric--blue">
                    <span class="teacher-course-stats-modal__metric-icon" aria-hidden="true"><i class="fas fa-user-graduate"></i></span>
                    <span class="teacher-course-stats-modal__metric-label">Élèves inscrits</span>
                    <strong data-course-stats-students>0</strong>
                    <small>Inscriptions actives</small>
                </article>
                <article class="teacher-course-stats-modal__metric teacher-course-stats-modal__metric--green">
                    <span class="teacher-course-stats-modal__metric-icon" aria-hidden="true"><i class="fas fa-wallet"></i></span>
                    <span class="teacher-course-stats-modal__metric-label">Revenus générés</span>
                    <strong data-course-stats-revenue>0 F CFA</strong>
                    <small data-course-stats-sales>0 commande(s) payée(s)</small>
                </article>
                <article class="teacher-course-stats-modal__metric teacher-course-stats-modal__metric--amber">
                    <span class="teacher-course-stats-modal__metric-icon" aria-hidden="true"><i class="fas fa-star"></i></span>
                    <span class="teacher-course-stats-modal__metric-label">Note moyenne</span>
                    <strong data-course-stats-rating>Aucun avis</strong>
                    <small data-course-stats-reviews>0 avis</small>
                </article>
                <article class="teacher-course-stats-modal__metric teacher-course-stats-modal__metric--violet">
                    <span class="teacher-course-stats-modal__metric-icon" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                    <span class="teacher-course-stats-modal__metric-label">Taux de complétion</span>
                    <strong data-course-stats-completion>0%</strong>
                    <small>Leçons terminées</small>
                </article>
            </div>

            <div class="teacher-course-stats-modal__details" aria-label="Détails du cours">
                <span><i class="fas fa-book-open" aria-hidden="true"></i><strong data-course-stats-lessons>0 leçons</strong></span>
                <span><i class="fas fa-layer-group" aria-hidden="true"></i><strong data-course-stats-modules>0 modules</strong></span>
                <span><i class="fas fa-eye" aria-hidden="true"></i><strong data-course-stats-views>0 vues</strong></span>
                <span><i class="fas fa-tag" aria-hidden="true"></i><strong data-course-stats-price>Gratuit</strong></span>
            </div>
        </section>
    </div>
</body>
</html>


