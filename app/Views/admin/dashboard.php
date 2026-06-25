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

$pendingCourses = max(0, (int) ($summary['pending_courses'] ?? 0));
$pendingTeacherApplications = max(0, (int) ($summary['pending_teacher_applications'] ?? 0));
$pendingPayments = max(0, (int) ($summary['pending_payments'] ?? 0));
$pendingWithdrawals = max(0, (int) ($summary['pending_withdrawals'] ?? 0));
$waitingSupportTickets = max(0, (int) ($summary['waiting_support_tickets'] ?? 0));
$failedPayments = max(0, (int) ($summary['failed_payments'] ?? 0));
$financeReviewCount = $pendingPayments + $pendingWithdrawals + $failedPayments;

$heroPrimaryActionLabel = 'Consulter les notifications récentes';
$heroPrimaryActionUrl = site_url('admin/notifications');

if ($pendingCourses > 0) {
    $heroPrimaryActionLabel = 'Valider ' . $formatNumber($pendingCourses) . ' cours en attente';
    $heroPrimaryActionUrl = site_url('admin/courses');
} elseif ($pendingTeacherApplications > 0) {
    $heroPrimaryActionLabel = 'Traiter ' . $formatNumber($pendingTeacherApplications) . ' candidature(s) enseignant';
    $heroPrimaryActionUrl = site_url('admin/teacher_applications');
} elseif ($waitingSupportTickets > 0) {
    $heroPrimaryActionLabel = 'Répondre à ' . $formatNumber($waitingSupportTickets) . ' ticket(s) support';
    $heroPrimaryActionUrl = site_url('admin/support?status=waiting_support');
} elseif ($financeReviewCount > 0) {
    $heroPrimaryActionLabel = 'Vérifier ' . $formatNumber($financeReviewCount) . ' opération(s) de paiement';
    $heroPrimaryActionUrl = site_url('admin/sales');
}

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
$adminUnreadCount = $unreadCount;
$adminTopTitle = 'Administration';
$adminTopSearchAction = site_url('admin/courses');
$adminTopSearchPlaceholder = 'Rechercher un utilisateur, un cours...';
$adminPageScripts = ['admin_dashboard_page.js'];

$quickActions = [
    ['label' => 'Gérer les enseignants (' . $formatNumber($pendingTeacherApplications) . ' en attente)', 'icon' => 'fa-chalkboard-teacher text-cyan-600', 'url' => site_url('admin/teacher_applications'), 'class' => 'bg-cyan-50 dark:bg-cyan-900/20 hover:bg-cyan-100 dark:hover:bg-cyan-900/40'],
    ['label' => 'Valider les cours en attente (' . $formatNumber($pendingCourses) . ')', 'icon' => 'fa-check-circle text-green-600', 'url' => site_url('admin/courses'), 'class' => 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40'],
    ['label' => 'Tickets à traiter (' . $formatNumber($waitingSupportTickets) . ')', 'icon' => 'fa-headset text-yellow-600', 'url' => site_url('admin/support?status=waiting_support'), 'class' => 'bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/40'],
    ['label' => 'Paiements à vérifier (' . $formatNumber($pendingPayments + $failedPayments) . ')', 'icon' => 'fa-credit-card text-purple-600', 'url' => site_url('admin/sales'), 'class' => 'bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/40'],
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrateur · École Numérique</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/admin_dashboard_page.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/admin_dashboard_page.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin admin-dashboard-page min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/dashboard" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop" aria-hidden="true">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense px-4 py-5 sm:px-6 sm:py-6 space-y-8 animate-fade-up">
                <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 to-accent-600 p-6 text-white shadow-glow">
                    <div class="pointer-events-none absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_52%)]"></div>
                    <div class="pointer-events-none absolute -left-10 top-0 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>

                    <div class="relative">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/70">Tableau de bord</p>
                        <h2 class="mt-3 text-2xl font-bold sm:text-3xl">👋 Bienvenue <?php echo e($firstName); ?></h2>
                        <p class="mt-5 text-sm text-primary-50/95">
                            Action principale :
                            <a href="<?php echo e($heroPrimaryActionUrl); ?>" class="ml-1 inline-flex items-center gap-2 font-semibold text-white underline decoration-white/35 underline-offset-4 transition hover:decoration-white">
                                <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                                <span><?php echo e($heroPrimaryActionLabel); ?></span>
                            </a>
                        </p>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-5">
                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="text-sm text-gray-500 dark:text-gray-400">Étudiants</p><p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($formatNumber($summary['students'] ?? 0)); ?></p></div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-primary-600 dark:bg-blue-900/30"><i class="fas fa-user-graduate"></i></div>
                        </div>
                        <p class="mt-2 flex items-center text-xs text-green-600 dark:text-green-400"><i class="fas fa-arrow-up mr-1"></i><?php echo e($formatNumber($summary['new_users_this_month'] ?? 0)); ?> inscrits ce mois</p>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="text-sm text-gray-500 dark:text-gray-400">Enseignants</p><p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($formatNumber($summary['teachers'] ?? 0)); ?></p></div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30"><i class="fas fa-chalkboard-teacher"></i></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Profils enseignants actifs</p>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="text-sm text-gray-500 dark:text-gray-400">Cours publiés</p><p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($formatNumber($summary['published_courses'] ?? 0)); ?></p></div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-100 text-accent-600 dark:bg-cyan-900/30"><i class="fas fa-layer-group"></i></div>
                        </div>
                        <p class="mt-2 flex items-center text-xs text-yellow-600 dark:text-yellow-400"><i class="fas fa-clock mr-1"></i><?php echo e($formatNumber($pendingCourses)); ?> en attente</p>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="text-sm text-gray-500 dark:text-gray-400">Revenus</p><p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($summary['revenue_xof'] ?? 0)); ?></p></div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/30"><i class="fas fa-money-bill-wave"></i></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Commission: <?php echo e($formatMoney($summary['admin_revenue_xof'] ?? 0)); ?></p>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="text-sm text-gray-500 dark:text-gray-400">Conversion ventes</p><p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($formatPercent($summary['conversion_rate'] ?? 0)); ?></p></div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30"><i class="fas fa-percent"></i></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e($formatNumber($summary['paid_orders'] ?? 0)); ?> commandes payées</p>
                    </article>
                </section>

                <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700 lg:col-span-2">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('trending-up'); ?><span>Croissance des inscriptions</span></h3>
                            <span class="rounded-lg bg-gray-100 px-2 py-1.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">6 derniers mois</span>
                        </div>
                        <div class="h-64">
                            <canvas id="adminChart" class="admin-chart-canvas" data-admin-chart data-chart-labels="<?php echo e($chartLabelsJson ?: '[]'); ?>" data-chart-values="<?php echo e($chartValuesJson ?: '[]'); ?>"></canvas>
                        </div>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700">
                        <h3 class="app-inline-title mb-4 font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('settings'); ?><span>Actions rapides</span></h3>
                        <div class="space-y-3">
                            <?php foreach ($quickActions as $action): ?>
                                <a href="<?php echo e((string) $action['url']); ?>" class="flex items-center gap-3 rounded-xl p-3 transition <?php echo e((string) $action['class']); ?>">
                                    <i class="fas <?php echo e((string) $action['icon']); ?>"></i>
                                    <span class="text-sm"><?php echo e((string) $action['label']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700">
                            <p class="mb-2 text-sm font-medium">Système</p>
                            <div class="space-y-2 text-xs text-gray-500 dark:text-gray-400">
                                <?php if ($platformHealth === []): ?>
                                    <div class="flex items-center justify-between">
                                        <span><i class="fas fa-circle mr-1 text-[8px] text-green-500"></i>Services accessibles</span>
                                        <span>OK</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($platformHealth as $health): ?>
                                        <?php
                                        $healthTone = (string) ($health['status'] ?? 'success');
                                        $healthDot = $healthTone === 'danger' ? 'text-red-500' : ($healthTone === 'warning' ? 'text-yellow-500' : 'text-green-500');
                                        ?>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="truncate"><i class="fas fa-circle <?php echo e($healthDot); ?> mr-1 text-[8px]"></i><?php echo e((string) ($health['service'] ?? 'Service')); ?></span>
                                            <span class="truncate"><?php echo e((string) ($health['value'] ?? 'OK')); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('book-open'); ?><span>Cours en attente de validation</span></h3>
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
                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 p-3 dark:bg-gray-700/30">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <?php if ($coverUrl !== ''): ?>
                                                <img src="<?php echo e($coverUrl); ?>" alt="" class="h-10 w-10 rounded-lg object-cover">
                                            <?php else: ?>
                                                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-primary-600 to-accent-500 text-white"><i class="fas fa-book"></i></span>
                                            <?php endif; ?>
                                            <div class="min-w-0">
                                                <p class="truncate font-medium"><?php echo e($courseTitle); ?></p>
                                                <p class="truncate text-xs text-gray-500 dark:text-gray-400"><?php echo e($teacherName); ?></p>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 gap-2">
                                            <a href="<?php echo e(site_url('admin/course_preview?id=' . $courseId)); ?>" target="_blank" rel="noopener noreferrer" class="p-1 text-primary-600 hover:text-primary-700" aria-label="Prévisualiser">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="post" action="<?php echo e(site_url('admin/courses')); ?>">
                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                                <button type="submit" name="action" value="approve" class="p-1 text-green-600 hover:text-green-700" aria-label="Approuver">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('users'); ?><span>Derniers inscrits</span></h3>
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
                                        <span class="admin-avatar-fallback h-9 w-9 rounded-full bg-gradient-to-br from-primary-600 to-accent-500 text-[11px] font-bold text-white"><?php echo e($recentInitials); ?></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium"><?php echo e($recentName); ?></p>
                                            <p class="truncate text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) ($recentUser['email'] ?? '')); ?></p>
                                        </div>
                                        <span class="rounded-full px-2 py-0.5 text-xs <?php echo e($rolePill); ?>"><?php echo e($recentRole); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 border-t border-gray-100 pt-2 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($formatNumber($summary['new_users_this_month'] ?? 0)); ?> nouveaux utilisateurs ce mois</p>
                        </div>
                    </article>
                </section>

                <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700">
                    <h3 class="app-inline-title mb-4 font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('receipt'); ?><span>Dernières transactions</span></h3>
                    <div class="overflow-x-auto admin-dashboard-scrollbar">
                        <table class="w-full text-sm">
                            <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
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
                                            <td class="whitespace-nowrap py-3 pr-4"><?php echo e($reference); ?></td>
                                            <td class="whitespace-nowrap py-3 pr-4"><?php echo e($client !== '' ? $client : 'Client'); ?></td>
                                            <td class="min-w-[12rem] py-3 pr-4"><?php echo e($courseNames !== '' ? $courseNames : 'Commande plateforme'); ?></td>
                                            <td class="whitespace-nowrap py-3 pr-4"><?php echo e($formatMoney($transaction['total_xof'] ?? 0)); ?></td>
                                            <td class="whitespace-nowrap py-3"><span class="<?php echo e($statusClass($transactionStatus)); ?> rounded-full px-2 py-0.5 text-xs"><?php echo e($statusLabel($transactionStatus)); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
