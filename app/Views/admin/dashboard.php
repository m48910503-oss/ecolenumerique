<?php
$adminPermissions = is_array($adminPermissions ?? null) ? $adminPermissions : [];
$isGeneralAdmin = !empty($isGeneralAdmin);
$hasAdminAccess = array_key_exists('hasAdminAccess', get_defined_vars()) ? (bool) $hasAdminAccess : ($isGeneralAdmin || $adminPermissions !== []);
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$user = is_array($user ?? null) ? $user : [];
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$summary = is_array($dashboardSummary ?? null) ? $dashboardSummary : [];
$dashboardChart = is_array($dashboardChart ?? null) ? $dashboardChart : [];
$pendingCourseCards = is_array($pendingCourseCards ?? null) ? $pendingCourseCards : [];
$latestUsers = is_array($latestUsers ?? null) ? $latestUsers : [];
$latestTransactions = is_array($latestTransactions ?? null) ? $latestTransactions : [];
$platformHealth = is_array($platformHealth ?? null) ? $platformHealth : [];

$assetUrl = static function (string $relativePath): string {
    $relativePath = ltrim($relativePath, '/');
    $absolutePath = dirname(__DIR__, 3) . '/' . $relativePath;
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : (string) time();

    return site_url($relativePath . '?v=' . $version);
};

$can = static function (string $permission) use ($adminPermissions, $isGeneralAdmin, $hasAdminAccess): bool {
    if (!$hasAdminAccess) {
        return false;
    }

    return $isGeneralAdmin || in_array($permission, $adminPermissions, true);
};

$formatNumber = static function ($value): string {
    return number_format((int) ($value ?? 0), 0, ',', ' ');
};

$formatMoney = static function ($value): string {
    return number_format((int) ($value ?? 0), 0, ',', ' ') . ' F CFA';
};

$formatPercent = static function ($value): string {
    $number = (float) ($value ?? 0);
    $formatted = number_format($number, $number === floor($number) ? 0 : 1, ',', ' ');

    return $formatted . '%';
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

$personName = static function (array $row, string $fallback): string {
    $name = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));

    return $name !== '' ? $name : $fallback;
};

$initialsFromName = static function (string $name, string $fallback = 'AD'): string {
    $name = trim($name);
    if ($name === '') {
        return $fallback;
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
        if (strlen($letters) >= 2) {
            break;
        }
    }
    $letters = function_exists('mb_strtoupper') ? mb_strtoupper($letters) : strtoupper($letters);

    return $letters !== '' ? $letters : $fallback;
};

$userRoleLabel = static function (array $row): string {
    $roles = json_decode((string) ($row['roles_json'] ?? '[]'), true);
    $roles = is_array($roles) ? $roles : [];
    if (in_array('admin', $roles, true)) {
        return 'Admin';
    }
    if (in_array('teacher', $roles, true)) {
        return 'Enseignant';
    }
    if (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
        return 'Entreprise';
    }

    return 'Étudiant';
};

$statusLabel = static function (string $status): string {
    return match ($status) {
        'paid' => 'Complété',
        'pending' => 'En attente',
        'failed' => 'Échoué',
        'canceled' => 'Annulé',
        'refunded' => 'Remboursé',
        default => ucfirst($status !== '' ? $status : 'inconnu'),
    };
};

$statusClass = static function (string $status): string {
    return match ($status) {
        'paid' => 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300',
        'pending' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300',
        'failed', 'canceled' => 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300',
        'refunded' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300',
        default => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    };
};

$displayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($user, 'Administrateur')
    : $personName($user, 'Administrateur');
$firstName = trim((string) ($user['first_name'] ?? ''));
if ($firstName === '') {
    $firstName = $displayName;
}
$email = trim((string) ($user['email'] ?? ''));
$adminRoleLabel = $isGeneralAdmin ? 'Super Admin' : 'Admin';
$adminInitials = function_exists('dashboard_user_initials')
    ? dashboard_user_initials($user, 'AD')
    : $initialsFromName($displayName, 'AD');
$adminAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($user) : '';

$pendingCourses = max(0, (int) ($summary['pending_courses'] ?? 0));
$pendingTeacherApplications = max(0, (int) ($summary['pending_teacher_applications'] ?? 0));
$pendingPayments = max(0, (int) ($summary['pending_payments'] ?? 0));
$pendingWithdrawals = max(0, (int) ($summary['pending_withdrawals'] ?? 0));
$highPriorityTickets = max(0, (int) ($summary['high_priority_tickets'] ?? 0));
$failedPayments = max(0, (int) ($summary['failed_payments'] ?? 0));

$chartLabels = [];
$chartValues = [];
foreach ($dashboardChart as $point) {
    if (!is_array($point)) {
        continue;
    }
    $chartLabels[] = (string) ($point['label'] ?? '');
    $chartValues[] = (int) ($point['value'] ?? 0);
}
if ($chartLabels === []) {
    $chartLabels = ['M-5', 'M-4', 'M-3', 'M-2', 'M-1', 'M'];
    $chartValues = [0, 0, 0, 0, 0, (int) ($summary['registered_users'] ?? 0)];
}
$chartLabelsJson = json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$chartValuesJson = json_encode($chartValues, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

$adminNavActive = 'dashboard';
$adminTopTitle = 'Administration';
$adminTopSearchAction = site_url('admin/courses');
$adminTopSearchPlaceholder = 'Rechercher un utilisateur, un cours...';

$navItems = [
    ['key' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'fa-tachometer-alt', 'url' => site_url('admin/dashboard'), 'active' => true, 'permission' => 'dashboard'],
    ['key' => 'statistics', 'label' => 'Statistiques', 'icon' => 'fa-chart-pie', 'url' => site_url('admin/statistics'), 'permission' => 'dashboard'],
    ['key' => 'courses', 'label' => 'Cours à valider', 'icon' => 'fa-book-open', 'url' => site_url('admin/courses'), 'permission' => 'courses', 'badge' => $pendingCourses, 'badgeClass' => 'bg-yellow-500'],
    ['key' => 'course_insights', 'label' => 'Cours approuvés', 'icon' => 'fa-chart-line', 'url' => site_url('admin/course_insights'), 'permission' => 'courses'],
    ['key' => 'categories', 'label' => 'Catégories', 'icon' => 'fa-tags', 'url' => site_url('admin/categories'), 'permission' => 'categories'],
    ['key' => 'teacher_applications', 'label' => 'Enseignants', 'icon' => 'fa-chalkboard-teacher', 'url' => site_url('admin/teacher_applications'), 'permission' => 'teacher_applications', 'badge' => $pendingTeacherApplications, 'badgeClass' => 'bg-orange-500'],
    ['key' => 'sales', 'label' => 'Finances', 'icon' => 'fa-credit-card', 'url' => site_url('admin/sales'), 'permission' => 'sales', 'badge' => $pendingPayments + $pendingWithdrawals + $failedPayments, 'badgeClass' => 'bg-cyan-500'],
    ['key' => 'support', 'label' => 'Tickets support', 'icon' => 'fa-headset', 'url' => site_url('admin/support'), 'permission' => 'support', 'badge' => $highPriorityTickets, 'badgeClass' => 'bg-red-500'],
    ['key' => 'support_agents', 'label' => 'Équipe support', 'icon' => 'fa-headset', 'url' => site_url('admin/support_agents'), 'permission' => 'support_agents'],
    ['key' => 'secondary_admins', 'label' => 'Admins secondaires', 'icon' => 'fa-users-cog', 'url' => site_url('admin/secondary_admins'), 'permission' => 'dashboard', 'generalOnly' => true],
    ['key' => 'announcements', 'label' => 'Annonces', 'icon' => 'fa-bullhorn', 'url' => site_url('admin/announcements'), 'permission' => 'announcements'],
    ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'fa-bell', 'url' => site_url('admin/notifications'), 'always' => true, 'badge' => $unreadCount, 'badgeClass' => 'bg-primary-600'],
    ['key' => 'site_settings', 'label' => 'Paramètres du site', 'icon' => 'fa-cog', 'url' => site_url('admin/site_settings'), 'permission' => 'site_settings'],
    ['key' => 'certificate_preview', 'label' => 'Certificat', 'icon' => 'fa-certificate', 'url' => site_url('admin/certificate_preview'), 'permission' => 'site_settings'],
    ['key' => 'error_logs', 'label' => 'Logs & erreurs', 'icon' => 'fa-bug', 'url' => site_url('admin/error_logs'), 'permission' => 'error_logs'],
    ['key' => 'profile', 'label' => 'Mon profil', 'icon' => 'fa-user-circle', 'url' => site_url('profile?space=admin'), 'always' => true],
    ['key' => 'home', 'label' => 'Retour au site', 'icon' => 'fa-home', 'url' => site_url(''), 'always' => true],
];

$quickActions = [
    ['label' => 'Gérer les enseignants (' . $formatNumber($pendingTeacherApplications) . ' en attente)', 'icon' => 'fa-chalkboard-teacher text-cyan-600', 'url' => site_url('admin/teacher_applications'), 'class' => 'bg-cyan-50 dark:bg-cyan-900/20 hover:bg-cyan-100 dark:hover:bg-cyan-900/40'],
    ['label' => 'Valider les cours en attente (' . $formatNumber($pendingCourses) . ')', 'icon' => 'fa-check-circle text-green-600', 'url' => site_url('admin/courses'), 'class' => 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40'],
    ['label' => 'Tickets prioritaires (' . $formatNumber($highPriorityTickets) . ')', 'icon' => 'fa-exclamation-triangle text-yellow-600', 'url' => site_url('admin/support?status=open&priority=high'), 'class' => 'bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/40'],
    ['label' => 'Paiements à vérifier (' . $formatNumber($pendingPayments + $failedPayments) . ')', 'icon' => 'fa-credit-card text-purple-600', 'url' => site_url('admin/sales'), 'class' => 'bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/40'],
    ['label' => 'Publier une annonce', 'icon' => 'fa-plus-circle text-primary-600', 'url' => site_url('admin/announcements'), 'class' => 'bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700'],
];
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-theme-storage-key="theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Dashboard Administrateur · École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e($assetUrl('assets/js/tailwind_theme_bootstrap.js')); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e($assetUrl('assets/css/admin_dashboard_page.css')); ?>">
    <script src="<?php echo e($assetUrl('assets/js/admin_dashboard_page.js')); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="admin/dashboard" class="admin-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
<div class="flex min-h-screen lg:h-screen lg:overflow-hidden">
    <div class="admin-dashboard-backdrop fixed inset-0 z-30 bg-gray-950/50 lg:hidden" data-admin-menu-close></div>

    <aside id="admin-dashboard-sidebar" class="admin-dashboard-sidebar fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-white shadow-soft dark:bg-gray-800 lg:static lg:translate-x-0 border-r border-gray-200 dark:border-gray-700">
        <div class="p-6 flex items-center gap-3 border-b border-gray-100 dark:border-gray-700">
            <span class="w-9 h-9 bg-gradient-to-br from-primary-600 to-accent-500 rounded-xl flex items-center justify-center text-white shadow-md overflow-hidden">
                <?php if ($siteLogoUrl !== ''): ?>
                    <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain">
                <?php else: ?>
                    <i class="fas fa-shield-alt"></i>
                <?php endif; ?>
            </span>
            <span class="text-xl font-bold text-gray-900 dark:text-white">École <span class="text-primary-600 dark:text-primary-400">Numérique</span></span>
            <button type="button" class="ml-auto rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 lg:hidden" data-admin-menu-close aria-label="Fermer le menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <?php if ($adminAvatarUrl !== ''): ?>
                    <img src="<?php echo e($adminAvatarUrl); ?>" alt="Avatar" class="w-12 h-12 rounded-full object-cover border-2 border-primary-200 dark:border-primary-800">
                <?php else: ?>
                    <span class="admin-avatar-fallback w-12 h-12 rounded-full border-2 border-primary-200 bg-gradient-to-br from-primary-600 to-accent-500 text-sm font-bold text-white dark:border-primary-800"><?php echo e($adminInitials); ?></span>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white truncate"><?php echo e($displayName); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo e($email !== '' ? $email : 'Compte administrateur'); ?></p>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between bg-red-50 dark:bg-red-900/20 rounded-xl p-2">
                <span class="text-xs text-red-700 dark:text-red-300"><i class="fas fa-crown mr-1"></i><?php echo e($adminRoleLabel); ?></span>
            </div>
        </div>

        <nav class="admin-dashboard-scrollbar flex-1 space-y-1 overflow-y-auto px-4 py-6">
            <?php foreach ($navItems as $item): ?>
                <?php
                $always = !empty($item['always']);
                $permission = (string) ($item['permission'] ?? '');
                if (!empty($item['generalOnly']) && !$isGeneralAdmin) {
                    continue;
                }
                if (!$always && $permission !== '' && !$can($permission)) {
                    continue;
                }
                $active = !empty($item['active']);
                $badge = max(0, (int) ($item['badge'] ?? 0));
                $linkClass = $active
                    ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700';
                ?>
                <a href="<?php echo e((string) $item['url']); ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl transition <?php echo e($linkClass); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>>
                    <i class="fas <?php echo e((string) $item['icon']); ?> w-5"></i>
                    <span class="truncate"><?php echo e((string) $item['label']); ?></span>
                    <?php if ($badge > 0): ?>
                        <span class="ml-auto <?php echo e((string) ($item['badgeClass'] ?? 'bg-primary-600')); ?> text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo e($formatNumber($badge)); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            <button type="button" data-admin-theme-toggle class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <i class="fas fa-moon dark:hidden w-5"></i>
                <i class="fas fa-sun hidden dark:inline w-5"></i>
                <span data-theme-label>Mode sombre</span>
            </button>
            <form method="post" action="<?php echo e(site_url('logout')); ?>" class="mt-1">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span>Déconnexion</span>
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900">
        <header class="sticky top-0 z-20 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-700 px-4 py-4 sm:px-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <button type="button" class="lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" data-admin-menu-toggle aria-controls="admin-dashboard-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Administration</h1>
            </div>
            <div class="flex items-center gap-3 sm:gap-4">
                <form method="get" action="<?php echo e(site_url('admin/courses')); ?>" class="relative hidden md:block">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="q" placeholder="Rechercher un utilisateur, un cours..." class="w-64 rounded-xl border-none bg-gray-100 py-2 pl-9 pr-4 text-sm outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700">
                </form>
                <a href="<?php echo e(site_url('admin/notifications')); ?>" class="relative p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Notifications">
                    <i class="far fa-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    <?php endif; ?>
                </a>
                <?php if ($adminAvatarUrl !== ''): ?>
                    <img src="<?php echo e($adminAvatarUrl); ?>" class="w-8 h-8 rounded-full object-cover lg:hidden" alt="Avatar">
                <?php else: ?>
                    <span class="admin-avatar-fallback w-8 h-8 rounded-full bg-gradient-to-br from-primary-600 to-accent-500 text-[11px] font-bold text-white lg:hidden"><?php echo e($adminInitials); ?></span>
                <?php endif; ?>
            </div>
        </header>

        <div class="p-4 sm:p-6 space-y-8 animate-fade-up">
            <section class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-2xl p-6 text-white shadow-glow">
                <h2 class="text-2xl font-bold">Bonjour <?php echo e($firstName); ?></h2>
                <p class="text-primary-100 mt-1"><?php echo e($formatNumber($pendingCourses)); ?> cours <?php echo $pendingCourses > 1 ? 'sont' : 'est'; ?> en attente de validation.</p>
            </section>

            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5">
                <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="text-sm text-gray-500 dark:text-gray-400">Étudiants</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e($formatNumber($summary['students'] ?? 0)); ?></p></div>
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-primary-600"><i class="fas fa-user-graduate"></i></div>
                    </div>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-2 flex items-center"><i class="fas fa-arrow-up mr-1"></i><?php echo e($formatNumber($summary['new_users_this_month'] ?? 0)); ?> inscrits ce mois</p>
                </article>

                <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="text-sm text-gray-500 dark:text-gray-400">Enseignants</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e($formatNumber($summary['teachers'] ?? 0)); ?></p></div>
                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-600"><i class="fas fa-chalkboard-teacher"></i></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Profils enseignants actifs</p>
                </article>

                <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="text-sm text-gray-500 dark:text-gray-400">Cours publiés</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e($formatNumber($summary['published_courses'] ?? 0)); ?></p></div>
                        <div class="w-10 h-10 bg-cyan-100 dark:bg-cyan-900/30 rounded-xl flex items-center justify-center text-accent-600"><i class="fas fa-layer-group"></i></div>
                    </div>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-2 flex items-center"><i class="fas fa-clock mr-1"></i><?php echo e($formatNumber($pendingCourses)); ?> en attente</p>
                </article>

                <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="text-sm text-gray-500 dark:text-gray-400">Revenus</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e($formatMoney($summary['revenue_xof'] ?? 0)); ?></p></div>
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center text-green-600"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Commission: <?php echo e($formatMoney($summary['admin_revenue_xof'] ?? 0)); ?></p>
                </article>

                <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="text-sm text-gray-500 dark:text-gray-400">Conversion ventes</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e($formatPercent($summary['conversion_rate'] ?? 0)); ?></p></div>
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center text-purple-600"><i class="fas fa-percent"></i></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2"><?php echo e($formatNumber($summary['paid_orders'] ?? 0)); ?> commandes payées</p>
                </article>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <article class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900 dark:text-white">Croissance des inscriptions</h3>
                        <span class="text-xs bg-gray-100 dark:bg-gray-700 rounded-lg px-2 py-1.5 text-gray-600 dark:text-gray-300">6 derniers mois</span>
                    </div>
                    <div class="h-64">
                        <canvas id="adminChart" class="admin-chart-canvas" data-admin-chart data-chart-labels="<?php echo e($chartLabelsJson ?: '[]'); ?>" data-chart-values="<?php echo e($chartValuesJson ?: '[]'); ?>"></canvas>
                    </div>
                </article>

                <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4">Actions rapides</h3>
                    <div class="space-y-3">
                        <?php foreach ($quickActions as $action): ?>
                            <a href="<?php echo e((string) $action['url']); ?>" class="flex items-center gap-3 p-3 rounded-xl transition <?php echo e((string) $action['class']); ?>">
                                <i class="fas <?php echo e((string) $action['icon']); ?>"></i>
                                <span class="text-sm"><?php echo e((string) $action['label']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-sm font-medium mb-2">Système</p>
                        <div class="space-y-2 text-xs text-gray-500 dark:text-gray-400">
                            <?php if ($platformHealth === []): ?>
                                <div class="flex items-center justify-between">
                                    <span><i class="fas fa-circle text-green-500 text-[8px] mr-1"></i>Services accessibles</span>
                                    <span>OK</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($platformHealth as $health): ?>
                                    <?php
                                    $healthTone = (string) ($health['status'] ?? 'success');
                                    $healthDot = $healthTone === 'danger' ? 'text-red-500' : ($healthTone === 'warning' ? 'text-yellow-500' : 'text-green-500');
                                    ?>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="truncate"><i class="fas fa-circle <?php echo e($healthDot); ?> text-[8px] mr-1"></i><?php echo e((string) ($health['service'] ?? 'Service')); ?></span>
                                        <span class="truncate"><?php echo e((string) ($health['value'] ?? 'OK')); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900 dark:text-white">Cours en attente de validation</h3>
                        <a href="<?php echo e(site_url('admin/courses')); ?>" class="text-xs text-primary-600 hover:underline">Tout voir</a>
                    </div>
                    <div class="space-y-3">
                        <?php if ($pendingCourseCards === []): ?>
                            <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-700/30 dark:text-gray-400">Aucun cours en attente.</div>
                        <?php else: ?>
                            <?php foreach ($pendingCourseCards as $course): ?>
                                <?php
                                if (!is_array($course)) {
                                    continue;
                                }
                                $courseId = (int) ($course['id'] ?? 0);
                                $courseTitle = trim((string) ($course['title'] ?? 'Cours sans titre'));
                                $teacherName = $personName($course, 'Enseignant');
                                $coverUrl = $resolveMediaUrl($course['cover_path'] ?? '');
                                ?>
                                <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-xl">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <?php if ($coverUrl !== ''): ?>
                                            <img src="<?php echo e($coverUrl); ?>" alt="" class="w-10 h-10 rounded-lg object-cover">
                                        <?php else: ?>
                                            <span class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-600 to-accent-500 flex items-center justify-center text-white"><i class="fas fa-book"></i></span>
                                        <?php endif; ?>
                                        <div class="min-w-0">
                                            <p class="font-medium truncate"><?php echo e($courseTitle); ?></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo e($teacherName); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 gap-2">
                                        <a href="<?php echo e(site_url('admin/course_preview?id=' . $courseId)); ?>" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-700 p-1" aria-label="Prévisualiser">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="post" action="<?php echo e(site_url('admin/courses')); ?>">
                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                            <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                            <button type="submit" name="action" value="approve" class="text-green-600 hover:text-green-700 p-1" aria-label="Approuver">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-gray-900 dark:text-white">Derniers inscrits</h3>
                        <a href="<?php echo e(site_url($isGeneralAdmin ? 'admin/secondary_admins' : 'admin/support_agents')); ?>" class="text-xs text-primary-600 hover:underline">Voir tous</a>
                    </div>
                    <div class="space-y-3">
                        <?php if ($latestUsers === []): ?>
                            <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-700/30 dark:text-gray-400">Aucun utilisateur récent.</div>
                        <?php else: ?>
                            <?php foreach ($latestUsers as $recentUser): ?>
                                <?php
                                if (!is_array($recentUser)) {
                                    continue;
                                }
                                $recentName = $personName($recentUser, 'Utilisateur');
                                $recentInitials = $initialsFromName($recentName, 'UT');
                                $recentRole = $userRoleLabel($recentUser);
                                $rolePill = $recentRole === 'Enseignant'
                                    ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300'
                                    : 'text-gray-400';
                                ?>
                                <div class="flex items-center gap-3 p-2">
                                    <span class="admin-avatar-fallback w-9 h-9 rounded-full bg-gradient-to-br from-primary-600 to-accent-500 text-[11px] font-bold text-white"><?php echo e($recentInitials); ?></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate"><?php echo e($recentName); ?></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo e((string) ($recentUser['email'] ?? '')); ?></p>
                                    </div>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?php echo e($rolePill); ?>"><?php echo e($recentRole); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($formatNumber($summary['new_users_this_month'] ?? 0)); ?> nouveaux utilisateurs ce mois</p>
                    </div>
                </article>
            </section>

            <section class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">Dernières transactions</h3>
                <div class="overflow-x-auto admin-dashboard-scrollbar">
                    <table class="w-full text-sm">
                        <thead class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="pb-2 text-left">ID</th>
                                <th class="pb-2 text-left">Client</th>
                                <th class="pb-2 text-left">Cours</th>
                                <th class="pb-2 text-left">Montant</th>
                                <th class="pb-2 text-left">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php if ($latestTransactions === []): ?>
                                <tr><td colspan="5" class="py-4 text-gray-500 dark:text-gray-400">Aucune transaction récente.</td></tr>
                            <?php else: ?>
                                <?php foreach ($latestTransactions as $transaction): ?>
                                    <?php
                                    if (!is_array($transaction)) {
                                        continue;
                                    }
                                    $transactionStatus = strtolower((string) ($transaction['status'] ?? ''));
                                    $reference = trim((string) ($transaction['reference'] ?? ''));
                                    if ($reference === '') {
                                        $reference = '#' . (int) ($transaction['id'] ?? 0);
                                    }
                                    $client = trim((string) (($transaction['buyer_first'] ?? '') . ' ' . ($transaction['buyer_last'] ?? '')));
                                    $courseNames = trim((string) ($transaction['course_titles'] ?? ''));
                                    ?>
                                    <tr>
                                        <td class="py-3 pr-4 whitespace-nowrap"><?php echo e($reference); ?></td>
                                        <td class="py-3 pr-4 whitespace-nowrap"><?php echo e($client !== '' ? $client : 'Client'); ?></td>
                                        <td class="py-3 pr-4 min-w-[12rem]"><?php echo e($courseNames !== '' ? $courseNames : 'Commande plateforme'); ?></td>
                                        <td class="py-3 pr-4 whitespace-nowrap"><?php echo e($formatMoney($transaction['total_xof'] ?? 0)); ?></td>
                                        <td class="py-3 whitespace-nowrap"><span class="<?php echo e($statusClass($transactionStatus)); ?> text-xs px-2 py-0.5 rounded-full"><?php echo e($statusLabel($transactionStatus)); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
