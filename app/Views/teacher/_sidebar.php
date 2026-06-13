<?php
declare(strict_types=1);

$teacherNavActive = (string) ($teacherNavActive ?? 'dashboard');
$teacherUser = is_array($user ?? null) ? $user : [];
$teacherSidebarBadges = teacher_sidebar_badge_counts($teacherUser);
$teacherUserFirstName = trim((string) ($teacherUser['first_name'] ?? ''));
$teacherUnreadCount = max(0, (int) ($unreadCount ?? ($teacherSidebarBadges['notifications'] ?? 0)));
$teacherSupportUnreadCount = max(0, (int) ($supportUnreadCount ?? ($teacherSidebarBadges['support'] ?? 0)));
$teacherPendingMeetingsCount = max(0, (int) ($pendingMeetingsCount ?? ($teacherSidebarBadges['pending_meetings'] ?? 0)));
$teacherPendingWithdrawalsCount = max(0, (int) ($pendingWithdrawalsCount ?? ($teacherSidebarBadges['pending_withdrawals'] ?? 0)));
$teacherPendingExamsCount = max(0, (int) ($pendingExamCount ?? ($teacherSidebarBadges['pending_exams'] ?? 0)));
$teacherMenuSections = dashboard_menu_config('teacher');
$teacherRoles = dashboard_roles_from_user($teacherUser);
$canSwitchToStudent = in_array('student', $teacherRoles, true) || in_array('teacher', $teacherRoles, true);
$showSpaceSwitcher = $canSwitchToStudent;
$teacherDashboardUrl = site_url('teacher/dashboard');
$studentDashboardUrl = site_url('student/dashboard'); 
$teacherBadgeCounts = [
    'notifications' => $teacherUnreadCount,
    'support' => $teacherSupportUnreadCount,
    'pending_meetings' => $teacherPendingMeetingsCount,
    'pending_withdrawals' => $teacherPendingWithdrawalsCount,
    'pending_exams' => $teacherPendingExamsCount,
];

$teacherNavAliases = [
    'affiliation' => 'referrals',
    'messages' => 'support',
    'coupons' => 'promotions',
    'course_actions' => 'courses',
    'course_content' => 'course_create',
    'course_edit' => 'courses',
    'course_preview' => 'courses',
    'course_updates' => 'courses',
    'exam_preview' => 'exams_manage',
    'exams_create' => 'exams_manage',
];
$teacherCurrentNavKey = $teacherNavAliases[$teacherNavActive] ?? $teacherNavActive;
$isTeacherNavActive = static function (string $key) use ($teacherCurrentNavKey): bool {
    return $teacherCurrentNavKey === $key;
};

$menuItemClass = static function (string $key) use ($isTeacherNavActive): string {
    $base = 'app-nav-link app-nav-link--teacher flex items-center justify-between px-3 py-2 rounded-xl transition';
    return $base . ($isTeacherNavActive($key) ? ' is-active-nav' : '');
};

$teacherDashboardIconClass = static function (string $name): string {
    return dashboard_icon_fa_class($name);
};

$teacherDashboardShell = $teacherDashboardShell ?? true;
if ($teacherDashboardShell !== false) {
    $teacherDashboardPrimaryItems = [];
    $teacherDashboardMenuGroups = [];
    foreach ($teacherMenuSections as $section) {
        $sectionTitle = (string) ($section['section'] ?? '');
        $rawSectionItems = is_array($section['items'] ?? null) ? $section['items'] : [];
        $sectionItems = [];
        foreach ($rawSectionItems as $rawItem) {
            $itemKey = (string) ($rawItem['key'] ?? '');
            if ($itemKey === 'logout') {
                continue;
            }
            if ((string) ($rawItem['variant'] ?? '') === 'primary') {
                $teacherDashboardPrimaryItems[] = $rawItem;
                continue;
            }
            $sectionItems[] = $rawItem;
        }
        if ($sectionItems === []) {
            continue;
        }
        $teacherDashboardMenuGroups[] = [
            'section' => $sectionTitle,
            'items' => $sectionItems,
        ];
    }
    ?>
    <aside id="teacher-dashboard-sidebar" class="teacher-dashboard-sidebar js-sidebar fixed inset-y-0 -left-full z-40 flex h-screen w-72 flex-col overflow-y-auto border-r border-gray-200 bg-white shadow-soft transition-[left] duration-200 dark:border-gray-700 dark:bg-gray-800 md:left-0">
        <div class="p-6 flex items-center gap-3 border-b border-gray-100 dark:border-gray-700">
            <span class="w-9 h-9 bg-gradient-to-br from-primary-600 to-accent-500 rounded-xl flex items-center justify-center text-white shadow-md">
                <i class="fas fa-chalkboard-teacher"></i>
            </span>
            <span class="text-xl font-bold text-gray-900 dark:text-white">École <span class="text-primary-600 dark:text-primary-400">Numérique</span></span>
            <button type="button" class="md:hidden ml-auto rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700" data-close-sidebar aria-label="Fermer le menu">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="p-5 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <?php if ($teacherAvatarUrl !== ''): ?>
                    <img src="<?php echo e($teacherAvatarUrl); ?>" alt="Avatar" class="w-12 h-12 rounded-full object-cover border-2 border-primary-200 dark:border-primary-800">
                <?php else: ?>
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-600 to-accent-500 flex items-center justify-center text-white font-bold shadow-md">
                        <?php echo e($teacherInitials); ?>
                    </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white truncate"><?php echo e($teacherDisplayName); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo e($teacherEmail !== '' ? $teacherEmail : 'Compte enseignant'); ?></p>
                </div>
            </div>
            <div class="mt-3 flex w-full flex-col gap-2 rounded-2xl bg-gray-50 p-2 dark:bg-gray-700/50">
                <span class="w-full rounded-full bg-white px-3 py-1 text-left text-[11px] font-medium leading-5 whitespace-nowrap text-gray-600 dark:bg-gray-800 dark:text-gray-300">Compte enseignant</span>
                <span class="w-full rounded-full bg-green-100 px-3 py-1 text-left text-[11px] font-semibold leading-5 whitespace-nowrap text-green-700 dark:bg-green-900/40 dark:text-green-300">Enseignant vérifié</span>
            </div>
        </div>

        <nav class="flex-1 px-4 py-5 space-y-5 overflow-y-auto">
            <?php if ($teacherDashboardPrimaryItems !== []): ?>
                <div class="space-y-2">
                    <p class="px-3 text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Action principale</p>
                    <?php foreach ($teacherDashboardPrimaryItems as $item): ?>
                        <?php
                            $itemKey = (string) ($item['key'] ?? '');
                            $itemLabel = (string) ($item['label'] ?? 'Lien');
                            $itemUrl = (string) ($item['url'] ?? '#');
                            $itemIcon = (string) ($item['icon'] ?? 'circle');
                            $itemActive = $isTeacherNavActive($itemKey);
                        ?>
                        <a href="<?php echo e($itemUrl); ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo $itemActive ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> transition" aria-label="<?php echo e($itemLabel); ?>" title="<?php echo e($itemLabel); ?>" <?php echo $itemActive ? 'aria-current="page"' : ''; ?>>
                            <i class="<?php echo e($teacherDashboardIconClass($itemIcon)); ?> w-5" aria-hidden="true"></i>
                            <span class="truncate"><?php echo e($itemLabel); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php foreach ($teacherDashboardMenuGroups as $section): ?>
                <?php
                    $sectionTitle = (string) ($section['section'] ?? '');
                    $sectionItems = is_array($section['items'] ?? null) ? $section['items'] : [];
                    if ($sectionItems === []) {
                        continue;
                    }
                ?>
                <div class="space-y-2">
                    <?php if ($sectionTitle !== ''): ?>
                        <p class="px-3 text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500"><?php echo e($sectionTitle); ?></p>
                    <?php endif; ?>
                    <div class="space-y-1">
                        <?php foreach ($sectionItems as $item): ?>
                            <?php
                                $itemKey = (string) ($item['key'] ?? '');
                                $itemLabel = (string) ($item['label'] ?? 'Lien');
                                $itemUrl = (string) ($item['url'] ?? '#');
                                $itemIcon = (string) ($item['icon'] ?? 'circle');
                                $badgeKey = (string) ($item['badge'] ?? '');
                                $badgeCount = max(0, (int) ($teacherBadgeCounts[$badgeKey] ?? 0));
                                $badgeLabel = $badgeCount > 99 ? '99+' : (string) $badgeCount;
                                $itemActive = $isTeacherNavActive($itemKey);
                                $badgeClass = match ($badgeKey) {
                                    'support' => 'bg-red-500 text-white',
                                    'pending_meetings' => 'bg-blue-500 text-white',
                                    'pending_withdrawals', 'pending_exams' => 'bg-amber-500 text-white',
                                    default => 'bg-primary-600 text-white',
                                };
                            ?>
                            <a href="<?php echo e($itemUrl); ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo $itemActive ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> transition" aria-label="<?php echo e($itemLabel); ?>" title="<?php echo e($itemLabel); ?>" <?php echo $itemActive ? 'aria-current="page"' : ''; ?>>
                                <i class="<?php echo e($teacherDashboardIconClass($itemIcon)); ?> w-5" aria-hidden="true"></i>
                                <span class="min-w-0 flex-1 truncate"><?php echo e($itemLabel); ?></span>
                                <?php if ($badgeKey === 'notifications'): ?>
                                    <span data-live-notif-badge class="ml-auto <?php echo e($badgeClass); ?> <?php echo $badgeCount > 0 ? '' : 'hidden'; ?> text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo e($badgeLabel); ?></span>
                                <?php elseif ($badgeKey === 'support'): ?>
                                    <span data-live-support-badge class="ml-auto <?php echo e($badgeClass); ?> <?php echo $badgeCount > 0 ? '' : 'hidden'; ?> text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo e($badgeLabel); ?></span>
                                <?php elseif ($badgeKey !== '' && $badgeCount > 0): ?>
                                    <span class="ml-auto <?php echo e($badgeClass); ?> text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo e($badgeLabel); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-gray-100 dark:border-gray-700">
            <button id="theme-toggle-sidebar" type="button" data-theme-toggle="1" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="Basculer le thème" aria-pressed="false">
                <i class="fas fa-moon dark:hidden w-5"></i>
                <i class="fas fa-sun hidden dark:inline w-5"></i>
                <span>Mode sombre</span>
            </button>
            <a href="<?php echo e(site_url('logout')); ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition mt-1">
                <i class="fas fa-sign-out-alt w-5"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>
    <?php return; ?>
<?php
}

$teacherPrimaryItems = [];
$teacherMenuGroups = [];
foreach ($teacherMenuSections as $section) {
    $sectionTitle = (string) ($section['section'] ?? '');
    $rawSectionItems = is_array($section['items'] ?? null) ? $section['items'] : [];
    $sectionItems = [];
    foreach ($rawSectionItems as $rawItem) {
        if ((string) ($rawItem['variant'] ?? '') === 'primary') {
            $teacherPrimaryItems[] = $rawItem;
            continue;
        }
$sectionItems[] = $rawItem;
    }
if ($sectionItems === []) {
        continue;
    }
$teacherMenuGroups[] = [
        'section' => $sectionTitle,
        'items' => $sectionItems,
    ];
}
?>
<aside id="teacher-sidebar" class="app-shell-sidebar js-sidebar fixed inset-y-0 -left-full z-40 flex h-screen flex-col overflow-y-auto overscroll-contain md:left-0 md:sticky md:top-0 md:overflow-hidden md:h-screen transition-all duration-200">
    <div class="app-shell-sidebar__brand">
        <div class="app-shell-sidebar__logo font-bold">
            <?php if (!empty($siteLogoUrl)): ?>
                <img src="<?php echo e((string) $siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain rounded-lg">
            <?php else: ?>
                TCH
            <?php endif; ?>
        </div>
        <div class="app-shell-sidebar__brand-copy">
            <p class="app-shell-brand-title">Espace enseignant</p>
            <p class="app-shell-brand-meta"><?php echo e($teacherUserFirstName); ?></p>
        </div>
        <button type="button"
            class="app-shell-burger app-shell-burger--desktop js-sidebar-toggle ml-auto hidden md:inline-flex"
            aria-controls="teacher-sidebar"
            aria-expanded="true"
            aria-label="R&eacute;duire la barre lat&eacute;rale"
            title="R&eacute;duire la barre lat&eacute;rale"
            data-sidebar-label-collapse="R&eacute;duire la barre lat&eacute;rale"
            data-sidebar-label-expand="D&eacute;ployer la barre lat&eacute;rale"
            data-sidebar-label-open="Ouvrir le menu"
        >
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('panel-left-close'); ?></span>
            <span class="sr-only">R&eacute;duire la barre lat&eacute;rale</span>
        </button>
        <button type="button" class="app-shell-burger ml-auto md:hidden" data-close-sidebar aria-label="Fermer le menu">
            &times;
        </button>
    </div>

    <?php if ($teacherPrimaryItems !== []): ?>
        <div class="app-shell-sidebar__primary-block px-4 pb-3 space-y-2">
            <p class="app-shell-sidebar__aux-label text-[10px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Action principale</p>
            <?php foreach ($teacherPrimaryItems as $item): ?>
                <?php
                    $itemKey = (string) ($item['key'] ?? '');
                    $itemLabel = (string) ($item['label'] ?? '');
                    $itemUrl = (string) ($item['url'] ?? '#');
                    $itemIcon = (string) ($item['icon'] ?? '');
                ?>
                <?php $itemActive = $isTeacherNavActive($itemKey); ?>
                <a class="app-shell-sidebar__primary app-nav-link app-nav-link--teacher flex items-center justify-between px-3 py-2 rounded-xl transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/70 <?php echo $itemActive ? 'is-active-nav' : ''; ?>" href="<?php echo e($itemUrl); ?>" aria-label="<?php echo e($itemLabel); ?>" title="<?php echo e($itemLabel); ?>" <?php echo $itemActive ? 'aria-current="page"' : ''; ?>>
                    <span class="app-nav-link__content">
                        <?php if ($itemIcon !== ''): ?>
                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($itemIcon); ?></span>
                        <?php endif; ?>
                        <span class="app-nav-link__label"><?php echo e($itemLabel); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <nav class="app-shell-sidebar__nav flex-1 min-h-0 overflow-y-auto overscroll-contain space-y-3">
        <?php foreach ($teacherMenuGroups as $section): ?>
            <?php
                $sectionTitle = (string) ($section['section'] ?? '');
                $sectionItems = is_array($section['items'] ?? null) ? $section['items'] : [];
                $sectionHasActive = false;
                foreach ($sectionItems as $rawItem) {
                    $rawKey = (string) ($rawItem['key'] ?? '');
                    if ($isTeacherNavActive($rawKey)) {
                        $sectionHasActive = true;
                    }
                }
$sectionOpen = $sectionHasActive || in_array($sectionTitle, ['Tableau de bord', 'Contenu'], true);
            ?>
            <details class="app-shell-sidebar__section group/section rounded-2xl" <?php echo $sectionOpen ? 'open' : ''; ?>>
                <summary class="app-shell-sidebar__summary list-none cursor-pointer flex items-center justify-between gap-2">
                    <span class="app-shell-sidebar__summary-label"><?php echo e($sectionTitle); ?></span>
                    <span class="app-shell-sidebar__summary-meta inline-flex items-center gap-2">
                        <span class="app-shell-sidebar__summary-caret">&#9662;</span>
                    </span>
                </summary>
                <div class="app-shell-sidebar__section-body space-y-2">
                    <?php foreach ($sectionItems as $item): ?>
                        <?php
                            $itemKey = (string) ($item['key'] ?? '');
                            $itemLabel = (string) ($item['label'] ?? '');
                            $itemUrl = (string) ($item['url'] ?? '#');
                            $itemVariant = (string) ($item['variant'] ?? '');
                            $itemIcon = (string) ($item['icon'] ?? '');
                            $badgeKey = (string) ($item['badge'] ?? '');
                            $badgeCount = max(0, (int) ($teacherBadgeCounts[$badgeKey] ?? 0));
                            $showStaticBadge = in_array($badgeKey, ['pending_meetings', 'pending_withdrawals', 'pending_exams'], true);
                        ?>
                        <?php if ($itemVariant !== 'primary'): ?>
                            <a class="<?php echo e($menuItemClass($itemKey)); ?> focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/70" href="<?php echo e($itemUrl); ?>" aria-label="<?php echo e($itemLabel); ?>" title="<?php echo e($itemLabel); ?>" <?php echo $isTeacherNavActive($itemKey) ? 'aria-current="page"' : ''; ?>>
                                <span class="app-nav-link__content">
                                    <?php if ($itemIcon !== ''): ?>
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($itemIcon); ?></span>
                                    <?php endif; ?>
                                    <span class="app-nav-link__label"><?php echo e($itemLabel); ?></span>
                                </span>
                                <?php if ($badgeKey === 'notifications'): ?>
                                    <span data-live-notif-badge class="app-nav-link__badge <?php echo $badgeCount > 0 ? '' : 'hidden'; ?>"><?php echo e((string) $badgeCount); ?></span>
                                <?php elseif ($badgeKey === 'support'): ?>
                                    <span data-live-support-badge class="app-nav-link__badge <?php echo $badgeCount > 0 ? '' : 'hidden'; ?>"><?php echo e((string) $badgeCount); ?></span>
                                <?php elseif ($showStaticBadge): ?>
                                    <span class="app-nav-link__badge <?php echo $badgeCount > 0 ? '' : 'is-empty'; ?>"><?php echo e((string) $badgeCount); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </nav>
</aside>

