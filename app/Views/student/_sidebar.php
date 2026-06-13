<?php

$studentNavActive = (string) ($studentNavActive ?? 'dashboard');
$studentUser = is_array($user ?? null) ? $user : [];
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$studentUserFirstName = trim((string) ($studentUser['first_name'] ?? ''));
$studentUserDisplayName = dashboard_user_display_name($studentUser, 'Utilisateur');
$studentUserEmail = trim((string) ($studentUser['email'] ?? ''));
$studentUserAvatarUrl = dashboard_user_avatar_url($studentUser);
$studentUserInitials = dashboard_user_initials($studentUser, 'ET');
$studentUnreadCount = max(0, (int) ($unreadCount ?? 0));
$studentSupportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$studentCourseBadgeCount = max(0, (int) ($studentCourseBadgeCount ?? $activeEnrollments ?? $enrollmentsCount ?? $activeCoursesCount ?? $courseCount ?? 0));
$studentMenuSections = dashboard_menu_config('student');
$studentRoles = dashboard_roles_from_user($studentUser);
$canSwitchToTeacher = in_array('teacher', $studentRoles, true);
$showSpaceSwitcher = $canSwitchToTeacher;
$teacherDashboardUrl = site_url('teacher/dashboard');
$studentDashboardUrl = site_url('student/dashboard');
$studentBadgeCounts = [
    'courses' => $studentCourseBadgeCount,
    'notifications' => $studentUnreadCount,
    'support' => $studentSupportUnreadCount,
];

$menuItemClass = static function (string $key) use ($studentNavActive): string {
    $base = 'app-nav-link app-nav-link--student flex items-center justify-between rounded-xl transition';
    return $base . ($studentNavActive === $key ? ' is-active-nav' : '');
};

$studentShellNavItemClass = static function (string $key) use ($studentNavActive): string {
    $base = 'flex items-center gap-3 px-3 py-2.5 rounded-xl transition sidebar-icon sm:px-4 sm:py-3';
    $active = 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 font-medium';
    $idle = 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700';

    return $base . ' ' . ($studentNavActive === $key ? $active : $idle);
};
?>
<?php if (!empty($studentDashboardShell)): ?>
<?php
$dashboardUser = $studentUser;
$dashboardDisplayName = dashboard_user_display_name($dashboardUser, 'Utilisateur');
$dashboardEmail = trim((string) ($dashboardUser['email'] ?? ''));
$dashboardAvatarUrl = dashboard_user_avatar_url($dashboardUser);
$dashboardInitials = dashboard_user_initials($dashboardUser, 'ET');
$dashboardSidebarName = $dashboardDisplayName;
if ($dashboardEmail !== '' && strcasecmp($dashboardSidebarName, $dashboardEmail) === 0) {
    $dashboardSidebarName = 'Compte étudiant';
}
$dashboardUrl = site_url('student/dashboard');
$myCoursesUrl = site_url('student/my_courses');
$calendarUrl = site_url('student/meetings');
$certificatesUrl = site_url('student/certificates');
$favoritesUrl = site_url('student/favorites');
$ordersUrl = site_url('student/orders');
$notificationsUrl = site_url('notifications?space=student');
$referralsUrl = site_url('student/referrals');
$supportUrl = site_url('support?space=student');
$profileUrl = site_url('profile?space=student');
$homeUrl = site_url('');
$logoutUrl = site_url('logout');
$logoutCsrf = function_exists('view_csrf_token') ? view_csrf_token() : '';
$activeEnrollments = $studentCourseBadgeCount;
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$profileFields = ['first_name', 'last_name', 'email'];
$filledProfileFields = 0;
foreach ($profileFields as $profileField) {
    if (trim((string) ($dashboardUser[$profileField] ?? '')) !== '') {
        $filledProfileFields++;
    }
}
$profileCompletionPct = count($profileFields) > 0
    ? (int) round(($filledProfileFields / count($profileFields)) * 100)
    : 0;
$profileCompletionPct = max(0, min(100, (int) ($completionPct ?? $profileCompletionPct)));
$isProfileComplete = $profileCompletionPct >= 100;
?>
<aside id="student-sidebar" class="student-dashboard-sidebar js-sidebar fixed -left-full top-0 z-40 flex h-screen w-64 flex-col overflow-y-auto border-r border-gray-200 bg-white shadow-soft transition-[left] duration-200 dark:border-gray-700 dark:bg-gray-800 sm:w-72 md:left-0 md:shadow-none">
  <div class="p-3 sm:p-4 flex items-center gap-2 border-b border-gray-100 dark:border-gray-700">
    <span class="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center text-white shadow-md">
      <i class="fas fa-graduation-cap"></i>
    </span>
    <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white sm:text-xl">École <span class="text-blue-600 dark:text-blue-400">Numérique</span></span>
  </div>

  <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-700">
    <div class="flex items-center gap-3">
      <?php if ($dashboardAvatarUrl !== ''): ?>
        <img src="<?php echo e($dashboardAvatarUrl); ?>" alt="Photo de profil" class="w-12 h-12 rounded-full object-cover border-2 border-blue-200 dark:border-blue-500/20">
      <?php else: ?>
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center text-white font-bold shadow-md">
          <?php echo e($dashboardInitials); ?>
        </div>
      <?php endif; ?>
      <div class="flex-1 min-w-0">
        <p class="font-semibold text-gray-900 dark:text-white truncate"><?php echo e($dashboardSidebarName); ?></p>
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo e($dashboardEmail !== '' ? $dashboardEmail : 'Compte étudiant'); ?></p>
      </div>
    </div>
    <div class="mt-3 flex items-center justify-between gap-2 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-2">
      <span class="min-w-0 truncate text-xs text-gray-600 dark:text-gray-300" title="<?php echo e($isProfileComplete ? 'Profil complet' : ('Profil complété à ' . (string) $profileCompletionPct . '%')); ?>"><?php echo $isProfileComplete ? 'Profil complet' : 'Profil ' . e((string) $profileCompletionPct) . '%'; ?></span>
      <?php if (!$isProfileComplete): ?>
        <a href="<?php echo e($profileUrl); ?>" class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700 transition hover:bg-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/20">Compléter</a>
      <?php else: ?>
        <span class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700 dark:bg-green-500/10 dark:text-green-300">Complet</span>
      <?php endif; ?>
    </div>
  </div>

  <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto sm:px-4 sm:py-6">
    <?php foreach ($studentMenuSections as $sectionIndex => $section): ?>
      <?php
        $sectionTitle = (string) ($section['section'] ?? '');
        $sectionItems = is_array($section['items'] ?? null) ? $section['items'] : [];
        if ($sectionItems === []) {
            continue;
        }
      ?>
      <?php if ($sectionIndex > 0 && $sectionTitle !== ''): ?>
        <p class="mt-3 px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500 sm:px-4"><?php echo e($sectionTitle); ?></p>
      <?php endif; ?>
      <?php foreach ($sectionItems as $item): ?>
        <?php
          $itemKey = (string) ($item['key'] ?? '');
          if ($itemKey === 'logout') {
              continue;
          }
          $itemLabel = (string) ($item['label'] ?? '');
          $itemUrl = (string) ($item['url'] ?? '#');
          $itemIcon = (string) ($item['icon'] ?? '');
          $badgeKey = (string) ($item['badge'] ?? '');
          $badgeCount = max(0, (int) ($studentBadgeCounts[$badgeKey] ?? 0));
        ?>
        <a href="<?php echo e($itemUrl); ?>" class="<?php echo e($studentShellNavItemClass($itemKey)); ?>" aria-label="<?php echo e($itemLabel); ?>" title="<?php echo e($itemLabel); ?>" <?php echo $studentNavActive === $itemKey ? 'aria-current="page"' : ''; ?>>
          <?php if ($itemIcon !== ''): ?>
            <i class="<?php echo e(dashboard_icon_fa_class($itemIcon)); ?> w-5" aria-hidden="true"></i>
          <?php endif; ?>
          <span><?php echo e($itemLabel); ?></span>
          <?php if ($badgeKey === 'notifications'): ?>
            <span data-live-notif-badge class="ml-auto rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-bold text-white <?php echo $badgeCount > 0 ? '' : 'hidden'; ?>"><?php echo e((string) min(99, $badgeCount)); ?></span>
          <?php elseif ($badgeKey === 'support'): ?>
            <span data-live-support-badge class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white <?php echo $badgeCount > 0 ? '' : 'hidden'; ?>"><?php echo e((string) min(99, $badgeCount)); ?></span>
          <?php elseif ($badgeCount > 0): ?>
            <span class="ml-auto rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-bold text-white"><?php echo e((string) min(99, $badgeCount)); ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="p-3 sm:p-4 border-t border-gray-100 dark:border-gray-700">
    <?php if ($showSpaceSwitcher): ?>
      <a href="<?php echo e($teacherDashboardUrl); ?>" class="mb-2 flex w-full items-center gap-3 rounded-xl bg-violet-50 px-3 py-2.5 text-sm font-semibold text-violet-700 transition hover:bg-violet-100 dark:bg-violet-500/10 dark:text-violet-300 dark:hover:bg-violet-500/20 sm:px-4 sm:py-3" aria-label="Basculer vers l'espace enseignant" title="Basculer vers l'espace enseignant">
        <i class="<?php echo e(dashboard_icon_fa_class('chalkboard-user')); ?> w-5" aria-hidden="true"></i>
        <span>Espace enseignant</span>
      </a>
    <?php endif; ?>
    <button id="theme-toggle-sidebar" data-web-theme-toggle data-theme-toggle type="button" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition sm:px-4 sm:py-3" aria-pressed="false">
      <i class="fas fa-moon text-base w-5" data-theme-icon-dark aria-hidden="true"></i>
      <i class="fas fa-sun hidden text-base w-5" data-theme-icon-light aria-hidden="true"></i>
      <span class="dark:hidden">Mode sombre</span>
      <span class="hidden dark:inline">Mode clair</span>
    </button>
    <form method="post" action="<?php echo e($logoutUrl); ?>" class="mt-1">
      <input type="hidden" name="_csrf" value="<?php echo e($logoutCsrf); ?>">
      <button type="submit" class="flex w-full items-center gap-3 px-3 py-2.5 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition sm:px-4 sm:py-3">
        <i class="fas fa-sign-out-alt w-5"></i>
        <span>Déconnexion</span>
      </button>
    </form>
  </div>
</aside>
<?php return; ?>
<?php endif; ?>
<aside id="student-sidebar" class="app-shell-sidebar student-shell-sidebar student-dashboard-sidebar js-sidebar fixed inset-y-0 -left-full z-40 flex h-screen flex-col overflow-y-auto overscroll-contain md:left-0 md:sticky md:top-0 md:h-screen md:overflow-hidden transition-all duration-200">
    <div class="app-shell-sidebar__brand">
        <div class="flex min-w-0 items-center gap-3">
            <div class="app-shell-sidebar__logo overflow-hidden font-bold">
                <?php if (!empty($siteLogoUrl)): ?>
                    <img src="<?php echo e((string) $siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain rounded-lg">
                <?php else: ?>
                    STD
                <?php endif; ?>
            </div>
            <div class="app-shell-sidebar__brand-copy">
                <p class="app-shell-brand-title">École <span>Numérique</span></p>
                <p class="app-shell-brand-meta">Espace étudiant</p>
            </div>
        </div>
        <button
            type="button"
            class="app-shell-burger app-shell-burger--desktop js-sidebar-toggle ml-auto hidden md:inline-flex"
            aria-controls="student-sidebar"
            aria-expanded="true"
            aria-label="Réduire la barre latérale"
            title="Réduire la barre latérale"
            data-sidebar-label-collapse="Réduire la barre latérale"
            data-sidebar-label-expand="Déployer la barre latérale"
            data-sidebar-label-open="Ouvrir le menu"
        >
            <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('panel-left-close')); ?>"></i></span>
            <span class="sr-only">Réduire la barre latérale</span>
        </button>
        <button type="button" class="app-shell-burger ml-auto md:hidden" data-close-sidebar aria-label="Fermer le menu">
            &times;
        </button>
    </div>

    <div class="app-shell-sidebar__profile">
        <div class="app-shell-sidebar__profile-avatar" aria-hidden="true">
            <?php if ($studentUserAvatarUrl !== ''): ?>
                <img src="<?php echo e($studentUserAvatarUrl); ?>" alt="" class="h-full w-full object-cover">
            <?php else: ?>
                <span><?php echo e($studentUserInitials); ?></span>
            <?php endif; ?>
        </div>
        <div class="app-shell-sidebar__profile-copy min-w-0">
            <p class="app-shell-sidebar__profile-name truncate"><?php echo e($studentUserDisplayName); ?></p>
            <p class="app-shell-sidebar__profile-email truncate"><?php echo e($studentUserEmail !== '' ? $studentUserEmail : 'Compte étudiant'); ?></p>
        </div>
        <span class="app-shell-sidebar__profile-pill">Étudiant</span>
    </div>

    <nav class="app-shell-sidebar__nav flex-1 min-h-0 overflow-y-auto overscroll-contain space-y-3">
        <?php foreach ($studentMenuSections as $section): ?>
            <?php
                $sectionTitle = (string) ($section['section'] ?? '');
                $sectionItems = is_array($section['items'] ?? null) ? $section['items'] : [];
                if ($sectionItems === []) {
                    continue;
                }
                $sectionHasActive = false;
                foreach ($sectionItems as $rawItem) {
                    $rawKey = (string) ($rawItem['key'] ?? '');
                    if ($rawKey === $studentNavActive) {
                        $sectionHasActive = true;
                    }
                }
                $sectionOpen = true;
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
                            if ($itemKey === 'logout') {
                                continue;
                            }
                            $itemLabel = (string) ($item['label'] ?? '');
                            $itemUrl = (string) ($item['url'] ?? '#');
                            $itemIcon = (string) ($item['icon'] ?? '');
                            $badgeKey = (string) ($item['badge'] ?? '');
                            $badgeCount = max(0, (int) ($studentBadgeCounts[$badgeKey] ?? 0));
                        ?>
                        <a class="<?php echo e($menuItemClass($itemKey)); ?> focus-visible:outline-none" href="<?php echo e($itemUrl); ?>" aria-label="<?php echo e($itemLabel); ?>" title="<?php echo e($itemLabel); ?>" <?php echo $studentNavActive === $itemKey ? 'aria-current="page"' : ''; ?>>
                            <span class="app-nav-link__content">
                                <?php if ($itemIcon !== ''): ?>
                                    <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class($itemIcon)); ?>"></i></span>
                                <?php endif; ?>
                                <span class="app-nav-link__label"><?php echo e($itemLabel); ?></span>
                            </span>
                            <?php if ($badgeKey === 'notifications'): ?>
                                <span data-live-notif-badge class="app-nav-link__badge <?php echo $badgeCount > 0 ? '' : 'hidden'; ?>"><?php echo e((string) $badgeCount); ?></span>
                            <?php elseif ($badgeKey === 'support'): ?>
                                <span data-live-support-badge class="app-nav-link__badge <?php echo $badgeCount > 0 ? '' : 'hidden'; ?>"><?php echo e((string) min(99, $badgeCount)); ?></span>
                            <?php elseif ($badgeCount > 0): ?>
                                <span class="app-nav-link__badge"><?php echo e((string) min(99, $badgeCount)); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </nav>

    <div class="app-shell-sidebar__footer">
        <?php if ($showSpaceSwitcher): ?>
            <a
                href="<?php echo e($teacherDashboardUrl); ?>"
                class="student-sidebar-teacher-switch app-nav-link app-nav-link--student mb-2 flex items-center justify-between rounded-xl px-3 py-2.5 transition focus-visible:outline-none"
                aria-label="Basculer vers l'espace enseignant"
                title="Basculer vers l'espace enseignant"
            >
                <span class="app-nav-link__content">
                    <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('chalkboard-user')); ?>"></i></span>
                    <span class="app-nav-link__label">Espace enseignant</span>
                </span>
            </a>
        <?php endif; ?>
        <button
            type="button"
            data-theme-toggle
            data-web-theme-toggle
            class="student-sidebar-theme-toggle inline-flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
            aria-label="Changer de thème"
        >
            <span data-theme-label aria-hidden="true"></span>
             <span class="dark:hidden">Mode sombre</span>
            <span class="hidden dark:inline">Mode clair</span>
        </button>

        <form method="post" action="<?php echo e(site_url('logout')); ?>" class="mt-2">
            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
            <button type="submit" class="student-sidebar-logout inline-flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition">
                <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('log-out')); ?>"></i></span>
                <span>Déconnexion</span>
            </button>
        </form>
    </div>
</aside>
