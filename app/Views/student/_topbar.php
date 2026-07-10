<?php

$studentTopTitle = trim((string) ($studentTopTitle ?? 'Tableau de bord'));
$studentTopSubtitle = trim((string) ($studentTopSubtitle ?? 'Espace étudiant'));
$showThemeToggle = !isset($showThemeToggle) || (bool) $showThemeToggle;
$studentTopShowSearch = !isset($studentTopShowSearch) || (bool) $studentTopShowSearch;
$catalogueUrl = (string) ($catalogueUrl ?? site_url('catalogue'));
$studentDashboardUrl = site_url('student/dashboard');
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$studentUser = is_array($user ?? null) ? $user : [];
$topUnreadCount = max(0, (int) ($unreadCount ?? 0));
$topSupportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$notificationsUrl = site_url('notifications?space=student');
$supportUrl = site_url('support?space=student');
$studentTopHideSupportFab = !empty($studentTopHideSupportFab);
$studentNavMeta = dashboard_menu_item_meta('student', (string) ($studentNavActive ?? 'dashboard'));
$studentContextSection = trim((string) ($studentTopContextSection ?? ($studentNavMeta['section'] ?? '')));
$studentContextLabel = trim((string) ($studentTopContextLabel ?? ((string) (($studentNavMeta['item']['label'] ?? '')))));
$studentContextTrail = trim($studentContextSection . ($studentContextSection !== '' && $studentContextLabel !== '' ? ' · ' : '') . $studentContextLabel);
$studentTopSearchValue = trim((string) ($studentTopSearchValue ?? ($_GET['q'] ?? '')));
$studentTopSearchPlaceholder = trim((string) ($studentTopSearchPlaceholder ?? 'Rechercher dans le catalogue'));
$studentTopSearchAriaLabel = trim((string) ($studentTopSearchAriaLabel ?? $studentTopSearchPlaceholder));
$studentTopSearchUrl = trim((string) ($studentTopSearchUrl ?? $catalogueUrl));
$studentTopSearchInputAttributes = trim((string) ($studentTopSearchInputAttributes ?? ''));
$studentTopSearchHiddenFields = is_array($studentTopSearchHiddenFields ?? null) ? $studentTopSearchHiddenFields : [];
$supportModalJsUrl = site_url('assets/js/support_modal.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/support_modal.js') ?: time()));
?>
<script src="<?php echo e($supportModalJsUrl); ?>" defer></script>
<?php if (!empty($studentDashboardShell)): ?>
<?php
$dashboardUser = $studentUser;
$dashboardDisplayName = dashboard_user_display_name($dashboardUser, 'Utilisateur');
$dashboardEmail = trim((string) ($dashboardUser['email'] ?? ''));
$dashboardAvatarUrl = dashboard_user_avatar_url($dashboardUser);
$dashboardInitials = dashboard_user_initials($dashboardUser, 'ET');
$dashboardLogoutUrl = site_url('logout');
$dashboardLogoutCsrf = function_exists('view_csrf_token') ? view_csrf_token() : '';
$dashboardSearchUrl = trim((string) ($studentTopSearchUrl ?? $catalogueUrl));
if ($dashboardSearchUrl === '') {
    $dashboardSearchUrl = $catalogueUrl;
}
$dashboardSearchValue = trim((string) ($studentTopSearchValue ?? ''));
$dashboardSearchPlaceholder = trim((string) ($studentTopSearchPlaceholder ?? 'Rechercher dans le catalogue'));
if ($dashboardSearchPlaceholder === '') {
    $dashboardSearchPlaceholder = 'Rechercher dans le catalogue';
}
$dashboardSearchAriaLabel = trim((string) ($studentTopSearchAriaLabel ?? $dashboardSearchPlaceholder));
if ($dashboardSearchAriaLabel === '') {
    $dashboardSearchAriaLabel = $dashboardSearchPlaceholder;
}
?>
<header class="student-dashboard-topbar fixed left-0 top-0 z-50 flex h-16 w-full items-center justify-between border-b border-gray-200 bg-white/80 px-3 backdrop-blur-xl dark:border-gray-700 dark:bg-gray-800/80 sm:h-[4.5rem] sm:px-4 lg:px-6">
  <div class="flex items-center gap-3 min-w-0 sm:gap-4">
    <button type="button" class="js-sidebar-toggle lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" aria-controls="student-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
      <i class="fas fa-bars"></i>
    </button>
    <a href="<?php echo e($studentDashboardUrl); ?>" class="student-dashboard-mobile-logo flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-md md:hidden" aria-label="Retour au tableau de bord">
      <?php if ($siteLogoUrl !== ''): ?>
        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full rounded-xl object-contain">
      <?php else: ?>
        <span class="text-[0.8rem] font-bold">ET</span>
      <?php endif; ?>
    </a>
    <div class="min-w-0">
      <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400"><?php echo e($studentTopSubtitle); ?></p>
      <h1 class="student-dashboard-title mt-0.5 text-lg font-semibold tracking-tight text-gray-900 dark:text-white sm:text-xl"><?php echo e($studentTopTitle); ?></h1>
    </div>
  </div>
  <div class="flex items-center gap-2 sm:gap-4">
    <?php if ($studentTopShowSearch): ?>
      <form action="<?php echo e($dashboardSearchUrl); ?>" method="get" class="student-dashboard-topbar__search relative hidden md:block">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input
          type="text"
          name="q"
          <?php if ($dashboardSearchValue !== ''): ?>value="<?php echo e($dashboardSearchValue); ?>" <?php endif; ?>
          placeholder="<?php echo e($dashboardSearchPlaceholder); ?>"
          aria-label="<?php echo e($dashboardSearchAriaLabel); ?>"
          class="pl-9 pr-4 py-2 bg-gray-100 dark:bg-gray-700 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none w-64"
          <?php echo $studentTopSearchInputAttributes !== '' ? ' ' . $studentTopSearchInputAttributes : ''; ?>
        >
        <?php foreach ($studentTopSearchHiddenFields as $hiddenField): ?>
          <?php
          $hiddenName = trim((string) ($hiddenField['name'] ?? ''));
          if ($hiddenName === '') {
              continue;
          }
          $hiddenValue = (string) ($hiddenField['value'] ?? '');
          $hiddenAttributes = trim((string) ($hiddenField['attributes'] ?? ''));
          ?>
          <input type="hidden" name="<?php echo e($hiddenName); ?>" value="<?php echo e($hiddenValue); ?>"<?php echo $hiddenAttributes !== '' ? ' ' . $hiddenAttributes : ''; ?>>
        <?php endforeach; ?>
      </form>
    <?php endif; ?>

    <button
      type="button"
      data-web-theme-toggle
      data-theme-toggle
      class="student-dashboard-theme-toggle inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300 sm:h-11 sm:w-11"
      aria-label="Changer le thème"
      aria-pressed="false"
    >
      <span class="inline-flex h-5 w-5 items-center justify-center" data-theme-icon-dark aria-hidden="true"><?php echo dashboard_icon_svg('theme-moon'); ?></span>
      <span class="inline-flex h-5 w-5 items-center justify-center" data-theme-icon-light aria-hidden="true" hidden><?php echo dashboard_icon_svg('theme-sun'); ?></span>
      <span class="sr-only">Changer le thème</span>
    </button>

    <a href="<?php echo e($notificationsUrl); ?>" class="student-dashboard-notification relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300 sm:h-11 sm:w-11" data-live-notifications-link="1" aria-label="Notifications" title="Notifications">
      <i class="far fa-bell"></i>
      <?php if ($topUnreadCount > 0): ?>
        <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-5 text-white shadow-sm" data-live-notif-badge>
          <?php echo e((string) min(99, $topUnreadCount)); ?>
        </span>
      <?php endif; ?>
    </a>

    <details class="student-user-menu relative" data-dashboard-user-menu>
      <summary class="student-dashboard-user-trigger inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border-2 border-blue-200 bg-white shadow-sm transition hover:border-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-blue-400 sm:h-11 sm:w-11" aria-label="Ouvrir le menu utilisateur" aria-haspopup="menu">
        <?php if ($dashboardAvatarUrl !== ''): ?>
          <img src="<?php echo e($dashboardAvatarUrl); ?>" alt="" class="h-9 w-9 rounded-full object-cover sm:h-10 sm:w-10">
        <?php else: ?>
          <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-bold text-white shadow-md sm:h-10 sm:w-10">
            <?php echo e($dashboardInitials); ?>
          </span>
        <?php endif; ?>
      </summary>
      <div class="student-user-menu__panel absolute right-0 top-[calc(100%+0.5rem)] z-50 w-[min(14.5rem,calc(100vw-0.75rem))] rounded-2xl border border-gray-100 bg-white p-2 shadow-[0_18px_48px_-24px_rgba(15,23,42,0.35)] dark:border-gray-700 dark:bg-gray-800 sm:w-[min(16rem,calc(100vw-1rem))]">
        <div class="rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-gray-700/50">
          <p class="truncate text-xs font-semibold text-gray-900 dark:text-white"><?php echo e($dashboardDisplayName); ?></p>
          <p class="mt-0.5 truncate text-[11px] text-gray-500 dark:text-gray-400"><?php echo e($dashboardEmail !== '' ? $dashboardEmail : 'Compte étudiant'); ?></p>
          <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.16em] text-blue-600 dark:text-blue-300">Espace étudiant</p>
        </div>
        <div class="mt-2 space-y-1.5">
          <a href="<?php echo e(site_url('profile?space=student')); ?>" class="flex items-center gap-2.5 rounded-xl bg-gray-50 px-3 py-2 text-blue-600 transition hover:bg-blue-50 dark:bg-gray-700/50 dark:text-blue-300 dark:hover:bg-blue-500/10">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
              <i class="fas fa-user text-xs"></i>
            </span>
            <span class="text-xs font-medium">Mon profil</span>
          </a>
          <form method="post" action="<?php echo e($dashboardLogoutUrl); ?>">
            <input type="hidden" name="_csrf" value="<?php echo e($dashboardLogoutCsrf); ?>">
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

<?php if (!$studentTopHideSupportFab): ?>
<a
    href="#support-chat"
    data-support-url="<?php echo e($supportUrl); ?>"
    data-support-fab="1"
    class="student-dashboard-support-fab fixed bottom-3 right-3 z-40 inline-flex items-center gap-3 rounded-full bg-blue-600 px-3 py-2.5 text-white shadow-glow transition hover:-translate-y-0.5 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 sm:bottom-4 sm:right-4 sm:px-4 sm:py-3"
    aria-label="Assistance support"
    title="Assistance support"
>
    <span class="relative flex h-10 w-10 items-center justify-center rounded-full bg-white/15 sm:h-11 sm:w-11">
        <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('headset')); ?>"></i></span>
        <?php if ($topSupportUnreadCount > 0): ?>
            <span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white text-center" data-live-support-badge>
                <?php echo e((string) min(99, $topSupportUnreadCount)); ?>
            </span>
        <?php endif; ?>
    </span>
    <span class="student-dashboard-support-label hidden pr-1 sm:block">
        <span class="block text-sm font-semibold leading-tight">Assistance support</span>
        <span class="block text-xs text-blue-100">Réponse rapide</span>
    </span>
</a>
<?php endif; ?>
<?php else: ?>
<header class="app-shell-toolbar student-shell-toolbar student-dashboard-topbar sticky top-0 z-30">
    <div class="app-shell-toolbar__inner">
        <div class="app-shell-toolbar__heading">
            <div class="app-shell-toolbar__brandline">
                <button type="button" class="app-shell-burger js-sidebar-toggle md:hidden" aria-controls="student-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('menu')); ?>"></i></span>
                    <span class="sr-only">Menu</span>
                </button>
                <a href="<?php echo e($studentDashboardUrl); ?>" class="app-shell-toolbar__mobile-logo md:hidden" aria-label="Retour au tableau de bord">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full rounded-lg object-contain">
                    <?php else: ?>
                        <span class="app-shell-toolbar__mobile-logo-text">ET</span>
                    <?php endif; ?>
                </a>
                <div class="app-shell-toolbar__titleblock">
                    <p class="app-shell-toolbar__eyebrow"><?php echo e($studentTopSubtitle); ?></p>
                    <p class="app-shell-toolbar__title student-dashboard-title"><?php echo e($studentTopTitle); ?></p>
                    <?php if ($studentContextTrail !== ''): ?><p class="app-shell-toolbar__trail"><?php echo e($studentContextTrail); ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="app-shell-toolbar__actions">
            <?php if ($studentTopShowSearch): ?>
                <form action="<?php echo e($studentTopSearchUrl !== '' ? $studentTopSearchUrl : $catalogueUrl); ?>" method="get" role="search" class="student-topbar-search hidden md:flex">
                    <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('search')); ?>"></i></span>
                    <input
                        type="search"
                        name="q"
                        value="<?php echo e($studentTopSearchValue); ?>"
                        placeholder="<?php echo e($studentTopSearchPlaceholder); ?>"
                        aria-label="<?php echo e($studentTopSearchAriaLabel !== '' ? $studentTopSearchAriaLabel : 'Rechercher dans le catalogue'); ?>"
                    >
                </form>
            <?php endif; ?>

            <?php if ($showThemeToggle): ?>
                <button type="button" data-theme-toggle data-web-theme-toggle aria-pressed="false" class="app-shell-theme app-shell-theme--desktop student-dashboard-theme-toggle inline-flex" aria-label="Changer de thème">
                    <span data-theme-label aria-hidden="true"></span>
                </button>
            <?php endif; ?>

            <a
                href="<?php echo e($notificationsUrl); ?>"
                class="app-shell-chip app-shell-chip--notification student-topbar-notification student-dashboard-notification inline-flex shrink-0 items-center gap-2"
                data-live-notifications-link="1"
                aria-label="Notifications"
                title="Notifications"
            >
                <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('bell')); ?>"></i></span>
                <?php if ($topUnreadCount > 0): ?>
                    <span class="app-shell-chip__badge" data-live-notif-badge><?php echo e((string) min(99, $topUnreadCount)); ?></span>
                <?php endif; ?>
            </a>

            <?php
            $userMenuUser = $studentUser;
            $userMenuRoleLabel = 'Espace étudiant';
            $userMenuProfileUrl = site_url('profile?space=student');
            $userMenuLogoutUrl = site_url('logout');
            $userMenuTriggerClass = 'app-shell-chip student-topbar-chip student-dashboard-user-trigger';
            require __DIR__ . '/../dashboard/_user_menu.php';
            ?>
        </div>
    </div>
</header>

<a
    href="#support-chat"
    data-support-url="<?php echo e($supportUrl); ?>"
    class="student-support-fab student-dashboard-support-fab fixed bottom-3 right-3 z-40 inline-flex items-center gap-3 rounded-full bg-blue-600 px-3 py-2.5 text-white shadow-glow transition hover:-translate-y-0.5 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 sm:bottom-4 sm:right-4 sm:px-4 sm:py-3"
    data-support-fab="1"
    aria-label="Assistance support"
    title="Assistance support"
>
    <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('headset')); ?>"></i></span>
    <span class="student-support-label student-dashboard-support-label hidden pr-1 sm:block">Assistance support</span>
    <?php if ($topSupportUnreadCount > 0): ?>
        <span class="app-shell-chip__badge" data-live-support-badge><?php echo e((string) min(99, $topSupportUnreadCount)); ?></span>
    <?php endif; ?>
</a>
<?php endif; ?>
