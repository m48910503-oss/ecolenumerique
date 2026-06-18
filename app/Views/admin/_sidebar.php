<?php
$adminPermissions = is_array($adminPermissions ?? null) ? $adminPermissions : [];
$isGeneralAdmin = !empty($isGeneralAdmin);
$hasAdminAccess = array_key_exists('hasAdminAccess', get_defined_vars()) ? (bool) $hasAdminAccess : ($isGeneralAdmin || $adminPermissions !== []);
$user = is_array($user ?? null) ? $user : [];
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$adminNavActive = (string) ($adminNavActive ?? '');
$adminSidebarBadges = admin_sidebar_badge_counts($user);
$adminUnreadInput = max(0, (int) ($adminUnreadCount ?? ($unreadCount ?? 0)));
$adminUnreadCount = $adminUnreadInput > 0 ? $adminUnreadInput : max(0, (int) ($adminSidebarBadges['notifications'] ?? 0));
$adminSidebarBadges['notifications'] = $adminUnreadCount;

$adminCan = static function (string $permission) use ($isGeneralAdmin, $adminPermissions, $hasAdminAccess): bool {
    if (!$hasAdminAccess) {
        return false;
    }

    return $isGeneralAdmin || in_array($permission, $adminPermissions, true);
};

$adminFormatNumber = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ');
};

$adminDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($user, 'Administrateur')
    : trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
if ($adminDisplayName === '') {
    $adminDisplayName = $hasAdminAccess ? 'Administrateur' : 'Support';
}
$adminEmail = trim((string) ($user['email'] ?? ''));
$adminAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($user) : '';
$adminInitials = function_exists('dashboard_user_initials') ? dashboard_user_initials($user, $hasAdminAccess ? 'AD' : 'SP') : ($hasAdminAccess ? 'AD' : 'SP');
$adminRoleLabel = $isGeneralAdmin ? 'Super admin' : ($hasAdminAccess ? 'Administrateur' : 'Support');
$adminProfileUrl = site_url('profile?space=admin');

$pendingCoursesBadge = max(0, (int) ($adminSidebarBadges['pending_courses'] ?? 0));
$pendingSalesBadge = max(0, (int) ($adminSidebarBadges['pending_withdrawals'] ?? 0))
    + max(0, (int) ($adminSidebarBadges['pending_payments'] ?? 0))
    + max(0, (int) ($adminSidebarBadges['failed_payments'] ?? 0));
$supportWaitingBadge = max(0, (int) ($adminSidebarBadges['support_waiting'] ?? 0));
$pendingApplicationsBadge = max(0, (int) ($adminSidebarBadges['pending_teacher_applications'] ?? 0));

$adminNavAliases = [
    'course_preview' => 'courses',
];
$adminCurrentNavKey = $adminNavAliases[$adminNavActive] ?? $adminNavActive;

$adminNavSections = $hasAdminAccess ? [
    [
        'section' => 'Pilotage',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'fa-tachometer-alt', 'url' => site_url('admin/dashboard'), 'permission' => 'dashboard'],
            ['key' => 'statistics', 'label' => 'Statistiques', 'icon' => 'fa-chart-pie', 'url' => site_url('admin/statistics'), 'permission' => 'dashboard'],
        ],
    ],
    [
        'section' => 'Contenu',
        'items' => [
            ['key' => 'courses', 'label' => 'Cours à valider', 'icon' => 'fa-book-open', 'url' => site_url('admin/courses'), 'permission' => 'courses', 'badge' => $pendingCoursesBadge, 'badgeClass' => 'bg-yellow-500'],
            ['key' => 'course_insights', 'label' => 'Cours approuvés', 'icon' => 'fa-chart-line', 'url' => site_url('admin/course_insights'), 'permission' => 'courses'],
            ['key' => 'categories', 'label' => 'Catégories', 'icon' => 'fa-tags', 'url' => site_url('admin/categories'), 'permission' => 'categories'],
            ['key' => 'teacher_applications', 'label' => 'Enseignants', 'icon' => 'fa-chalkboard-teacher', 'url' => site_url('admin/teacher_applications'), 'permission' => 'teacher_applications', 'badge' => $pendingApplicationsBadge, 'badgeClass' => 'bg-orange-500'],
        ],
    ],
    [
        'section' => 'Opérations',
        'items' => [
            ['key' => 'sales', 'label' => 'Finances', 'icon' => 'fa-credit-card', 'url' => site_url('admin/sales'), 'permission' => 'sales', 'badge' => $pendingSalesBadge, 'badgeClass' => 'bg-cyan-500'],
            ['key' => 'support', 'label' => 'Tickets support', 'icon' => 'fa-headset', 'url' => site_url('admin/support'), 'permission' => 'support', 'badge' => $supportWaitingBadge, 'badgeClass' => 'bg-red-500'],
            ['key' => 'support_agents', 'label' => 'Équipe support', 'icon' => 'fa-headset', 'url' => site_url('admin/support_agents'), 'permission' => 'support_agents'],
            ['key' => 'secondary_admins', 'label' => 'Admins secondaires', 'icon' => 'fa-users-cog', 'url' => site_url('admin/secondary_admins'), 'permission' => 'dashboard', 'generalOnly' => true],
        ],
    ],
    [
        'section' => 'Communication',
        'items' => [
            ['key' => 'announcements', 'label' => 'Annonces', 'icon' => 'fa-bullhorn', 'url' => site_url('admin/announcements'), 'permission' => 'announcements'],
            ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'fa-bell', 'url' => site_url('admin/notifications'), 'always' => true, 'badge' => $adminUnreadCount, 'badgeClass' => 'bg-primary-600'],
        ],
    ],
    [
        'section' => 'Système',
        'items' => [
            ['key' => 'site_settings', 'label' => 'Paramètres du site', 'icon' => 'fa-cog', 'url' => site_url('admin/site_settings'), 'permission' => 'site_settings'],
            ['key' => 'certificate_preview', 'label' => 'Certificat', 'icon' => 'fa-certificate', 'url' => site_url('admin/certificate_preview'), 'permission' => 'site_settings'],
            ['key' => 'error_logs', 'label' => 'Logs & erreurs', 'icon' => 'fa-bug', 'url' => site_url('admin/error_logs'), 'permission' => 'error_logs'],
        ],
    ],
    [
        'section' => 'Compte',
        'items' => [
            ['key' => 'profile', 'label' => 'Mon profil', 'icon' => 'fa-user-circle', 'url' => $adminProfileUrl, 'always' => true],
            ['key' => 'home', 'label' => 'Retour au site', 'icon' => 'fa-home', 'url' => site_url(''), 'always' => true],
        ],
    ],
] : [
    [
        'section' => 'Support',
        'items' => [
            ['key' => 'support', 'label' => 'Tickets support', 'icon' => 'fa-headset', 'url' => site_url('admin/support'), 'always' => true],
            ['key' => 'notifications', 'label' => 'Notifications', 'icon' => 'fa-bell', 'url' => site_url('admin/notifications'), 'always' => true, 'badge' => $adminUnreadCount, 'badgeClass' => 'bg-primary-600'],
        ],
    ],
    [
        'section' => 'Compte',
        'items' => [
            ['key' => 'profile', 'label' => 'Mon profil', 'icon' => 'fa-user-circle', 'url' => $adminProfileUrl, 'always' => true],
            ['key' => 'home', 'label' => 'Retour au site', 'icon' => 'fa-home', 'url' => site_url(''), 'always' => true],
        ],
    ],
];
?>
<aside id="admin-sidebar" class="app-shell-sidebar js-sidebar fixed inset-y-0 -left-full z-40 flex h-screen flex-col overflow-y-auto overscroll-contain transition-all duration-200 md:left-0 md:sticky md:top-0 md:h-screen md:overflow-hidden">
    <div class="app-shell-sidebar__brand">
        <div class="app-shell-sidebar__logo font-bold">
            <?php if ($siteLogoUrl !== ''): ?>
                <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain rounded-lg">
            <?php else: ?>
                <span class="app-shell-sidebar__logo-icon">ADM</span>
            <?php endif; ?>
        </div>
        <div class="app-shell-sidebar__brand-copy">
            <p class="app-shell-brand-title">École <span>Numérique</span></p>
            <p class="app-shell-brand-meta">Espace admin</p>
        </div>
        <button
            type="button"
            class="app-shell-burger app-shell-burger--desktop js-sidebar-toggle ml-auto hidden md:inline-flex"
            aria-controls="admin-sidebar"
            aria-expanded="true"
            aria-label="Réduire la barre latérale"
            title="Réduire la barre latérale"
            data-sidebar-label-collapse="Réduire la barre latérale"
            data-sidebar-label-expand="Déployer la barre latérale"
            data-sidebar-label-open="Ouvrir le menu"
        >
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
        <button type="button" class="app-shell-burger ml-auto md:hidden" data-close-sidebar aria-label="Fermer le menu">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>

    <div class="admin-sidebar-profile">
        <div class="admin-sidebar-profile__row">
            <?php if ($adminAvatarUrl !== ''): ?>
                <img src="<?php echo e($adminAvatarUrl); ?>" alt="Avatar" class="admin-sidebar-profile__avatar">
            <?php else: ?>
                <span class="admin-sidebar-profile__avatar admin-sidebar-profile__avatar--fallback admin-avatar-fallback"><?php echo e($adminInitials); ?></span>
            <?php endif; ?>
            <div class="admin-sidebar-profile__copy">
                <p class="admin-sidebar-profile__name truncate"><?php echo e($adminDisplayName); ?></p>
                <p class="admin-sidebar-profile__email truncate"><?php echo e($adminEmail !== '' ? $adminEmail : 'Compte administrateur'); ?></p>
            </div>
        </div>
        <div class="admin-sidebar-profile__role">
            <span><i class="fas fa-crown mr-1" aria-hidden="true"></i><?php echo e($adminRoleLabel); ?></span>
        </div>
    </div>

    <nav class="app-shell-sidebar__nav admin-dashboard-scrollbar flex-1 min-h-0 overflow-y-auto overscroll-contain space-y-3">
        <?php foreach ($adminNavSections as $section): ?>
            <?php
            $sectionTitle = (string) ($section['section'] ?? '');
            $sectionItems = is_array($section['items'] ?? null) ? $section['items'] : [];
            $visibleItems = [];
            foreach ($sectionItems as $item) {
                $always = !empty($item['always']);
                $permission = (string) ($item['permission'] ?? '');
                if (!empty($item['generalOnly']) && !$isGeneralAdmin) {
                    continue;
                }
                if (!$always && $permission !== '' && !$adminCan($permission)) {
                    continue;
                }
                $visibleItems[] = $item;
            }
            if ($visibleItems === []) {
                continue;
            }
            ?>
            <div class="admin-sidebar-section">
                <?php if ($sectionTitle !== ''): ?>
                    <p class="admin-sidebar-section__title"><?php echo e($sectionTitle); ?></p>
                <?php endif; ?>
                <div class="space-y-1">
                    <?php foreach ($visibleItems as $item): ?>
                        <?php
                        $itemKey = (string) ($item['key'] ?? '');
                        $isActive = $adminCurrentNavKey === $itemKey;
                        $badge = max(0, (int) ($item['badge'] ?? 0));
                        $badgeClass = (string) ($item['badgeClass'] ?? 'bg-primary-600');
                        ?>
                        <a href="<?php echo e((string) ($item['url'] ?? '#')); ?>" class="admin-shell-nav-link app-nav-link <?php echo $isActive ? 'admin-shell-nav-link--active is-active-nav' : ''; ?>" aria-label="<?php echo e((string) ($item['label'] ?? 'Lien')); ?>" title="<?php echo e((string) ($item['label'] ?? 'Lien')); ?>" <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                            <span class="admin-shell-nav-link__content app-nav-link__content">
                                <i class="admin-shell-nav-link__icon fas <?php echo e((string) ($item['icon'] ?? 'fa-circle')); ?>" aria-hidden="true"></i>
                                <span class="app-nav-link__label"><?php echo e((string) ($item['label'] ?? 'Lien')); ?></span>
                            </span>
                            <?php if ($badge > 0): ?>
                                <span class="admin-shell-nav-link__badge app-nav-link__badge <?php echo e($badgeClass); ?> ml-auto text-white"><?php echo e($adminFormatNumber($badge)); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-footer">
        <button type="button" data-theme-toggle class="admin-sidebar-theme" aria-label="Changer de thème">
            <span class="admin-sidebar-theme__icon inline-flex w-5 items-center justify-center" data-theme-label aria-hidden="true"></span>
            <span class="app-nav-link__label">Changer le thème</span>
        </button>
        <form method="post" action="<?php echo e(site_url('logout')); ?>" class="mt-1">
            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
            <button type="submit" class="admin-shell-nav-link admin-shell-nav-link--danger mt-1">
                <span class="admin-shell-nav-link__content app-nav-link__content">
                    <i class="admin-shell-nav-link__icon fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span class="app-nav-link__label">Déconnexion</span>
                </span>
            </button>
        </form>
    </div>
</aside>
