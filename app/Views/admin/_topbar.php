<?php
$adminTopTitle = trim((string) ($adminTopTitle ?? 'Administration'));
$adminUser = is_array($user ?? null) ? $user : [];
$adminNotificationsUrl = site_url('admin/notifications');
$adminProfileUrl = site_url('profile?space=admin');
$adminUnreadCount = max(0, (int) ($adminUnreadCount ?? ($unreadCount ?? 0)));
$adminAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($adminUser) : '';
$adminInitials = function_exists('dashboard_user_initials') ? dashboard_user_initials($adminUser, 'AD') : 'AD';
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
        <button type="button" class="js-sidebar-toggle md:hidden p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" aria-controls="admin-sidebar" aria-label="Ouvrir le menu" aria-expanded="false">
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

        <a href="<?php echo e($adminNotificationsUrl); ?>" class="relative p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Notifications">
            <i class="far fa-bell"></i>
            <?php if ($adminUnreadCount > 0): ?>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo e($adminProfileUrl); ?>" class="inline-flex items-center justify-center rounded-full transition hover:opacity-90" aria-label="Ouvrir le profil administrateur">
            <?php if ($adminAvatarUrl !== ''): ?>
                <img src="<?php echo e($adminAvatarUrl); ?>" class="w-8 h-8 rounded-full object-cover" alt="Avatar">
            <?php else: ?>
                <span class="admin-avatar-fallback w-8 h-8 rounded-full bg-gradient-to-br from-primary-600 to-accent-500 text-[11px] font-bold text-white"><?php echo e($adminInitials); ?></span>
            <?php endif; ?>
        </a>
    </div>
</header>
