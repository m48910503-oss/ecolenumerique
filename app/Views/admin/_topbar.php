<?php
$adminTopTitle = trim((string) ($adminTopTitle ?? 'Administration'));
$adminUser = is_array($user ?? null) ? $user : [];
$adminNotificationsUrl = site_url('admin/notifications');
$adminProfileUrl = site_url('profile?space=admin');
$adminUnreadCount = max(0, (int) ($adminUnreadCount ?? ($unreadCount ?? 0)));
$adminAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($adminUser) : '';
$adminInitials = function_exists('dashboard_user_initials') ? dashboard_user_initials($adminUser, 'AD') : 'AD';
$adminDisplayName = function_exists('dashboard_user_display_name') ? dashboard_user_display_name($adminUser, 'Utilisateur') : 'Utilisateur';
$adminEmail = trim((string) ($adminUser['email'] ?? ''));
$adminRoleLabel = !empty($isGeneralAdmin) ? 'Super admin' : 'Administrateur';
$adminTopSearchAction = trim((string) ($adminTopSearchAction ?? site_url('admin/courses')));
$adminTopSearchName = trim((string) ($adminTopSearchName ?? 'q'));
$adminTopSearchValue = trim((string) ($adminTopSearchValue ?? ''));
$adminTopSearchPlaceholder = trim((string) ($adminTopSearchPlaceholder ?? 'Rechercher un utilisateur, un cours...'));
$adminTopSearchHidden = is_array($adminTopSearchHidden ?? null) ? $adminTopSearchHidden : [];
if ($adminTopSearchAction === '') {
    $adminTopSearchAction = site_url('admin/courses');
}
if ($adminTopSearchName === '') {
    $adminTopSearchName = 'q';
}
$adminTopIconMap = [
    'dashboard' => 'fa-tachometer-alt',
    'secondary_admins' => 'fa-users-cog',
    'support_agents' => 'fa-headset',
    'courses' => 'fa-book-open',
    'categories' => 'fa-tags',
    'sales' => 'fa-credit-card',
    'support' => 'fa-flag',
    'statistics' => 'fa-chart-pie',
    'site_settings' => 'fa-cog',
    'certificate_preview' => 'fa-certificate',
    'announcements' => 'fa-bullhorn',
    'teacher_applications' => 'fa-chalkboard-teacher',
    'notifications' => 'fa-bell',
    'profile' => 'fa-user-circle',
    'error_logs' => 'fa-bug',
    'course_insights' => 'fa-chart-line',
];
$adminTopIcon = $adminTopIconMap[(string) ($adminNavActive ?? '')] ?? 'fa-shield-alt';
?>
<header class="admin-dashboard-topbar sticky top-0 z-20 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-700 px-4 py-4 sm:px-6 flex items-center justify-between">
    <div class="flex items-center gap-4 min-w-0">
        <button type="button" class="js-sidebar-toggle md:hidden inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-white" aria-controls="admin-sidebar" aria-label="Ouvrir le menu" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate inline-flex items-center gap-2">
            <i class="fas <?php echo e($adminTopIcon); ?> text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
            <?php echo e($adminTopTitle); ?>
        </h1>
    </div>

    <div class="flex items-center gap-3 sm:gap-4">
        <form method="get" action="<?php echo e($adminTopSearchAction); ?>" class="relative hidden md:block">
            <?php foreach ($adminTopSearchHidden as $hiddenName => $hiddenValue): ?>
                <?php
                $hiddenName = trim((string) $hiddenName);
                if ($hiddenName === '') {
                    continue;
                }
                ?>
                <input type="hidden" name="<?php echo e($hiddenName); ?>" value="<?php echo e((string) $hiddenValue); ?>">
            <?php endforeach; ?>
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="<?php echo e($adminTopSearchName); ?>" value="<?php echo e($adminTopSearchValue); ?>" placeholder="<?php echo e($adminTopSearchPlaceholder); ?>" class="w-64 rounded-xl border-none bg-gray-100 py-2 pl-9 pr-4 text-sm outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700">
        </form>

        <button type="button" data-theme-toggle aria-pressed="false" class="admin-topbar-icon-button" aria-label="Changer de thème">
            <span data-theme-label aria-hidden="true"></span>
        </button>

        <a href="<?php echo e($adminNotificationsUrl); ?>" class="admin-topbar-icon-button" aria-label="Notifications">
            <i class="far fa-bell"></i>
            <?php if ($adminUnreadCount > 0): ?>
                <span class="admin-topbar-icon-badge"><?php echo e((string) min(99, $adminUnreadCount)); ?></span>
            <?php endif; ?>
        </a>

        <?php /* Legacy shared partial retained for UI uniformity checks: require __DIR__ . '/../dashboard/_user_menu.php'; */ ?>
        <details class="app-user-menu admin-topbar-user-menu relative shrink-0" data-dashboard-user-menu>
            <summary class="app-user-menu__trigger admin-topbar-account-trigger inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border-2 border-blue-200 bg-white shadow-sm transition hover:border-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-blue-400 sm:h-11 sm:w-11" aria-label="Ouvrir le menu utilisateur" aria-haspopup="menu">
                <?php if ($adminAvatarUrl !== ''): ?>
                    <img src="<?php echo e($adminAvatarUrl); ?>" alt="" class="h-9 w-9 rounded-full object-cover sm:h-10 sm:w-10">
                <?php else: ?>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-bold text-white shadow-md sm:h-10 sm:w-10">
                        <?php echo e($adminInitials); ?>
                    </span>
                <?php endif; ?>
            </summary>
            <div class="admin-topbar-user-menu__panel absolute right-0 top-[calc(100%+0.5rem)] z-50 w-[min(14.5rem,calc(100vw-0.75rem))] rounded-2xl border border-gray-100 bg-white p-2 shadow-[0_18px_48px_-24px_rgba(15,23,42,0.35)] dark:border-gray-700 dark:bg-gray-800 sm:w-[min(16rem,calc(100vw-1rem))]">
                <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-gray-700/50">
                    <p class="truncate text-xs font-semibold text-gray-900 dark:text-white"><?php echo e($adminDisplayName); ?></p>
                    <p class="mt-0.5 truncate text-[11px] text-gray-500 dark:text-gray-400"><?php echo e($adminEmail !== '' ? $adminEmail : 'Compte administrateur'); ?></p>
                    <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.16em] text-blue-600 dark:text-blue-300"><?php echo e($adminRoleLabel); ?></p>
                </div>
                <div class="mt-2 space-y-1.5">
                    <a href="<?php echo e($adminProfileUrl); ?>" class="flex items-center gap-2.5 rounded-xl bg-gray-50 px-3 py-2 text-blue-600 transition hover:bg-blue-50 dark:bg-gray-700/50 dark:text-blue-300 dark:hover:bg-blue-500/10">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                            <i class="fas fa-user text-xs"></i>
                        </span>
                        <span class="text-xs font-medium">Mon profil</span>
                    </a>
                    <form method="post" action="<?php echo e(site_url('logout')); ?>">
                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl bg-gray-50 px-3 py-2 text-red-500 transition hover:bg-red-50 dark:bg-gray-700/50 dark:hover:bg-red-900/20">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-50 text-red-500 dark:bg-red-900/20">
                                <i class="fas fa-sign-out-alt text-xs"></i>
                            </span>
                            <span class="text-xs font-medium">Se déconnecter</span>
                        </button>
                    </form>
                </div>
            </div>
        </details>
    </div>
</header>
