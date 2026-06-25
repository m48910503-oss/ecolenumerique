<?php
declare(strict_types=1);

$teacherTopTitle = trim((string) ($teacherTopTitle ?? 'Tableau de bord'));
$teacherTopSubtitle = trim((string) ($teacherTopSubtitle ?? 'Espace formateur'));
$showCatalogue = isset($showCatalogue) ? (bool) $showCatalogue : false;
$showThemeToggle = !isset($showThemeToggle) || (bool) $showThemeToggle;
$showSupportFab = !isset($showSupportFab) || (bool) $showSupportFab;
$catalogueUrl = (string) ($catalogueUrl ?? site_url('catalogue'));
$teacherDashboardUrl = site_url('teacher/dashboard');
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = trim((string) ($teacherDisplayName ?? dashboard_user_display_name($teacherUser, 'Enseignant')));
if ($teacherDisplayName === '') {
    $teacherDisplayName = 'Enseignant';
}
$teacherEmail = trim((string) ($teacherEmail ?? ($teacherUser['email'] ?? '')));
$teacherAvatarUrl = trim((string) ($teacherAvatarUrl ?? dashboard_user_avatar_url($teacherUser)));
$teacherInitials = trim((string) ($teacherInitials ?? dashboard_user_initials($teacherUser, 'EN')));
$topUnreadCount = max(0, (int) ($unreadCount ?? 0)); 
$topSupportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$notificationsUrl = site_url('notifications?space=teacher');
$supportUrl = site_url('support?space=teacher');
$globalPopupsAsset = site_url('assets/js/global_popups.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/global_popups.js') ?: time()));
$feedbackVisibilityAsset = site_url('assets/js/feedback_visibility.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/feedback_visibility.js') ?: time()));
$teacherEnhancementsAsset = site_url('assets/js/teacher_hub_enhancements.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_hub_enhancements.js') ?: time()));
$supportModalJsUrl = site_url('assets/js/support_modal.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/support_modal.js') ?: time()));
$teacherContextSection = trim((string) ($teacherTopContextSection ?? ''));
$teacherContextLabel = trim((string) ($teacherTopContextLabel ?? ''));
$teacherContextTrail = trim($teacherContextSection . ($teacherContextSection !== '' && $teacherContextLabel !== '' ? ' · ' : '') . $teacherContextLabel);
$teacherDashboardShell = $teacherDashboardShell ?? true;
if ($teacherDashboardShell !== false) {
    $teacherProfileUrl = site_url('profile?space=teacher');
    ?>
    <script src="<?php echo e($supportModalJsUrl); ?>" defer></script>
    <header class="teacher-dashboard-topbar fixed left-0 top-0 z-50 flex h-16 w-full items-center justify-between border-b border-gray-200 bg-white/80 px-3 backdrop-blur-xl dark:border-gray-700 dark:bg-gray-800/80 sm:h-[4.5rem] sm:px-4 lg:px-6">
        <div class="flex items-center gap-3 min-w-0 sm:gap-4">
            <button type="button" class="md:hidden rounded-lg p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 js-sidebar-toggle" aria-controls="teacher-dashboard-sidebar" aria-expanded="false" aria-label="Ouvrir le menu" title="Ouvrir le menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Espace formateur</p>
                <h1 class="teacher-dashboard-title mt-0.5 text-lg font-semibold tracking-tight text-gray-900 dark:text-white sm:text-xl"><?php echo e($teacherTopTitle); ?></h1>
            </div>
        </div>
        <div class="teacher-dashboard-topbar__actions flex items-center gap-2 sm:gap-4 flex-nowrap">
            <div class="relative hidden md:block">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" placeholder="Rechercher..." class="pl-9 pr-4 py-2 bg-gray-100 dark:bg-gray-700 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary-500 outline-none w-64">
            </div>
                <button
                type="button"
                data-theme-toggle
                id="theme-toggle"
                aria-pressed="false"
                class="teacher-dashboard-theme-toggle inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300 sm:h-11 sm:w-11"
                aria-label="Basculer le thème"
            >
                <i class="fas fa-moon text-base dark:hidden" aria-hidden="true"></i>
                <i class="fas fa-sun hidden text-base dark:inline" aria-hidden="true"></i>
            </button>
            <a
                href="<?php echo e($notificationsUrl); ?>"
                class="teacher-dashboard-notification relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300 sm:h-11 sm:w-11"
                aria-label="Notifications"
            >
                <i class="far fa-bell"></i>
                <?php if ($topUnreadCount > 0): ?>
                    <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-5 text-white shadow-sm">
                        <?php echo e((string) min(99, $topUnreadCount)); ?>
                    </span>
                <?php endif; ?>
            </a>
            <details class="student-user-menu relative shrink-0" data-dashboard-user-menu>
                <summary class="student-dashboard-user-trigger inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border-2 border-blue-200 bg-white shadow-sm transition hover:border-blue-300 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-blue-400 sm:h-11 sm:w-11" aria-label="Ouvrir le menu utilisateur" aria-haspopup="menu">
                    <?php if ($teacherAvatarUrl !== ''): ?>
                        <img
                            src="<?php echo e($teacherAvatarUrl); ?>"
                            alt=""
                            class="hidden h-9 w-9 rounded-full object-cover sm:h-10 sm:w-10"
                            onload="this.classList.remove('hidden');this.nextElementSibling.classList.add('hidden');"
                            onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden');"
                        >
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-bold text-white shadow-md sm:h-10 sm:w-10">
                            <?php echo e($teacherInitials); ?>
                        </span>
                    <?php else: ?>
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-bold text-white shadow-md sm:h-10 sm:w-10">
                            <?php echo e($teacherInitials); ?>
                        </span>
                    <?php endif; ?>
                </summary>
                <div class="student-user-menu__panel absolute right-0 top-[calc(100%+0.75rem)] z-50 w-[min(16.5rem,calc(100vw-0.75rem))] rounded-[20px] border border-gray-100 bg-white p-2.5 shadow-[0_20px_60px_-24px_rgba(15,23,42,0.35)] dark:border-gray-700 dark:bg-gray-800 sm:w-[min(19rem,calc(100vw-1rem))] sm:rounded-[22px] sm:p-3">
                    <div class="rounded-[18px] bg-gray-50 px-4 py-3 dark:bg-gray-700/50">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo e($teacherDisplayName); ?></p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"><?php echo e($teacherEmail !== '' ? $teacherEmail : 'Compte enseignant'); ?></p>
                        <p class="mt-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Espace enseignant</p>
                    </div>
                    <div class="mt-3 space-y-2">
                        <a href="<?php echo e($teacherProfileUrl); ?>" class="flex items-center gap-3 rounded-[18px] bg-gray-50 px-3.5 py-2.5 text-blue-600 transition hover:bg-blue-50 dark:bg-gray-700/50 dark:text-blue-300 dark:hover:bg-blue-500/10">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                                <i class="fas fa-user"></i>
                            </span>
                            <span class="text-sm font-medium">Mon profil</span>
                        </a>
                        <form method="post" action="<?php echo e(site_url('logout')); ?>">
                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                            <button type="submit" class="flex w-full items-center gap-3 rounded-[18px] bg-gray-50 px-3.5 py-2.5 text-red-500 transition hover:bg-red-50 dark:bg-gray-700/50 dark:hover:bg-red-900/20">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-50 text-red-500 dark:bg-red-900/20">
                                    <i class="fas fa-sign-out-alt"></i>
                                </span>
                                <span class="text-sm font-medium">Se déconnecter</span>
                            </button>
                        </form>
                    </div>
                </div>
            </details>
        </div>
    </header>

    <?php if ($showSupportFab): ?>
        <a
            href="#support-chat"
            data-support-url="<?php echo e($supportUrl); ?>"
            class="teacher-dashboard-support-fab fixed bottom-4 right-4 z-40 inline-flex items-center gap-3 rounded-[1.75rem] bg-gradient-to-r from-primary-600 via-primary-600 to-accent-500 px-4 py-3 text-white shadow-[0_26px_56px_-28px_rgba(37,99,235,0.78)] transition hover:-translate-y-0.5 hover:from-primary-700 hover:to-accent-600 focus:outline-none focus:ring-2 focus:ring-primary-300 dark:from-primary-500 dark:via-primary-500 dark:to-accent-500 dark:hover:from-primary-600 dark:hover:to-accent-600 sm:bottom-5 sm:right-5"
            data-support-fab="1"
            aria-label="Assistance support"
            title="Assistance support"
        >
            <span class="relative flex h-11 w-11 items-center justify-center rounded-full bg-white/15 shadow-inner ring-1 ring-white/10" aria-hidden="true">
                <span class="app-icon" aria-hidden="true"><i class="fas fa-headset"></i></span>
                <?php if ($topSupportUnreadCount > 0): ?>
                    <span class="absolute -right-1 -top-1 inline-flex min-h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white shadow-sm" data-live-support-badge><?php echo e((string) min(99, $topSupportUnreadCount)); ?></span>
                <?php endif; ?>
            </span>
            <span class="teacher-dashboard-support-label hidden flex-col items-start leading-tight sm:flex">
                <span class="text-[0.95rem] font-semibold">Assistance support</span>
                <span class="text-[0.8rem] font-medium text-white/90">Réponse rapide</span>
            </span>
            <?php if ($topSupportUnreadCount > 0): ?>
                <span class="sr-only"><?php echo e((string) min(99, $topSupportUnreadCount)); ?> message(s) non lu(s)</span>
            <?php endif; ?>
        </a>
    <?php endif; ?>
    <?php return; ?>
<?php } ?>
?>
<script src="<?php echo e($globalPopupsAsset); ?>" defer></script>
<script src="<?php echo e($feedbackVisibilityAsset); ?>" defer></script>
<script src="<?php echo e($teacherEnhancementsAsset); ?>" defer></script>
<header class="app-shell-toolbar sticky top-0 z-30">
    <div class="app-shell-toolbar__inner">
        <div class="app-shell-toolbar__heading">
            <div class="app-shell-toolbar__brandline">
                <a href="<?php echo e($teacherDashboardUrl); ?>" class="app-shell-toolbar__mobile-logo md:hidden" aria-label="Retour au tableau de bord">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain rounded-lg">
                    <?php else: ?>
                        <span class="app-shell-toolbar__mobile-logo-text">EN</span>
                    <?php endif; ?>
                </a>
                <div class="app-shell-toolbar__titleblock">
                    <p class="app-shell-toolbar__eyebrow"><?php echo e($teacherTopSubtitle); ?></p>
                    <p class="app-shell-toolbar__title"><?php echo e($teacherTopTitle); ?></p>
                    <?php if ($teacherContextTrail !== ''): ?><p class="app-shell-toolbar__trail"><?php echo e($teacherContextTrail); ?></p><?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-2 md:hidden">
                <?php if ($showThemeToggle): ?><button type="button" data-theme-toggle aria-pressed="false" class="app-shell-theme" aria-label="Changer de thème"><span data-theme-label aria-hidden="true"></span></button><?php endif; ?>
                <button type="button" class="app-shell-burger js-sidebar-toggle md:hidden" aria-controls="teacher-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('menu'); ?></span>
                    <span class="sr-only">Menu</span>
                </button>
            </div>
        </div>

        <div class="app-shell-toolbar__actions md:justify-end">
            <?php if ($showCatalogue): ?><a class="app-shell-chip teacher-topbar-chip inline-flex shrink-0 items-center gap-2" data-topbar-catalogue="1" aria-label="Catalogue" title="Catalogue" href="<?php echo e($catalogueUrl); ?>"><span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout-grid'); ?></span><span>Catalogue</span></a><?php endif; ?>
            <?php if ($showThemeToggle): ?><button type="button" id="theme-toggle" data-theme-toggle aria-pressed="false" class="app-shell-theme app-shell-theme--desktop" aria-label="Changer de thème"><span data-theme-label aria-hidden="true"></span></button><?php endif; ?>
            <?php
            $userMenuUser = $teacherUser;
            $userMenuRoleLabel = 'Espace enseignant';
            $userMenuNotificationsUrl = $notificationsUrl;
            $userMenuSupportUrl = $supportUrl;
            $userMenuNotificationCount = $topUnreadCount;
            $userMenuSupportCount = $topSupportUnreadCount;
            $userMenuTriggerClass = 'app-shell-chip teacher-topbar-chip';
            require __DIR__ . '/../dashboard/_user_menu.php';
            ?>
        </div>
    </div>
</header>
