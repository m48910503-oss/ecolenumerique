<?php
$publicUserMenuUser = is_array($publicUserMenuUser ?? null) ? $publicUserMenuUser : [];
$publicUserMenuDisplayName = dashboard_user_display_name($publicUserMenuUser, 'Utilisateur');
$publicUserMenuEmail = trim((string) ($publicUserMenuUser['email'] ?? ''));
$publicUserMenuInitials = dashboard_user_initials($publicUserMenuUser, 'US');
$publicUserMenuAvatarUrl = dashboard_user_avatar_url($publicUserMenuUser);
$publicUserMenuRoleLabel = trim((string) ($publicUserMenuRoleLabel ?? 'Compte connecté'));
$publicUserMenuShowRoleLine = !empty($publicUserMenuShowRoleLine);
$publicUserMenuShowHeaderPill = !isset($publicUserMenuShowHeaderPill) || (bool) $publicUserMenuShowHeaderPill;
$publicUserMenuPrimaryLabel = trim((string) ($publicUserMenuPrimaryLabel ?? 'Tableau de bord'));
$publicUserMenuPrimaryUrl = trim((string) ($publicUserMenuPrimaryUrl ?? site_url('student/dashboard')));
$publicUserMenuPrimaryIcon = trim((string) ($publicUserMenuPrimaryIcon ?? 'layout'));
$publicUserMenuLogoutUrl = trim((string) ($publicUserMenuLogoutUrl ?? site_url('logout')));
$publicUserMenuClass = trim((string) ($publicUserMenuClass ?? ''));
$publicUserMenuPanelClass = trim((string) ($publicUserMenuPanelClass ?? ''));
$publicUserMenuTriggerClass = trim((string) ($publicUserMenuTriggerClass ?? ''));
?>
<details class="public-user-menu relative shrink-0 <?php echo e($publicUserMenuClass); ?>" data-public-user-menu>
    <summary class="public-user-menu__trigger inline-flex cursor-pointer list-none appearance-none items-center justify-center gap-2 rounded-full border border-slate-200 bg-white/90 p-1.5 text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:hover:border-blue-400 dark:hover:bg-slate-800 <?php echo e($publicUserMenuTriggerClass); ?>" aria-label="Ouvrir le menu utilisateur" aria-haspopup="menu">
        <span class="relative inline-flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-bold text-white shadow-md" aria-hidden="true">
            <?php if ($publicUserMenuAvatarUrl !== ''): ?>
                <img
                    src="<?php echo e($publicUserMenuAvatarUrl); ?>"
                    alt=""
                    class="hidden h-full w-full object-cover"
                    onload="this.classList.remove('hidden');this.nextElementSibling.classList.add('hidden');"
                    onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden');"
                >
                <span class="h-full w-full flex items-center justify-center"><?php echo e($publicUserMenuInitials); ?></span>
            <?php else: ?>
                <span class="h-full w-full flex items-center justify-center"><?php echo e($publicUserMenuInitials); ?></span>
            <?php endif; ?>
        </span>
        <span class="sr-only"><?php echo e($publicUserMenuDisplayName); ?></span>
    </summary>

    <div class="public-user-menu__panel absolute right-0 top-[calc(100%+0.75rem)] z-50 w-[min(18rem,calc(100vw-1rem))] overflow-hidden rounded-3xl border border-slate-200 bg-white p-2 shadow-[0_24px_60px_-30px_rgba(15,23,42,0.45)] dark:border-slate-700 dark:bg-slate-900 <?php echo e($publicUserMenuPanelClass); ?>">
        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($publicUserMenuDisplayName); ?></p>
            <?php if ($publicUserMenuEmail !== ''): ?>
                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400"><?php echo e($publicUserMenuEmail); ?></p>
            <?php endif; ?>
            <?php if ($publicUserMenuRoleLabel !== '' && ($publicUserMenuShowRoleLine || $publicUserMenuEmail === '')): ?>
                <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300"><?php echo e($publicUserMenuRoleLabel); ?></p>
            <?php endif; ?>
            <?php if ($publicUserMenuShowHeaderPill && $publicUserMenuRoleLabel !== ''): ?>
                <span class="mt-2 inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200"><?php echo e($publicUserMenuRoleLabel); ?></span>
            <?php endif; ?>
        </div>

        <div class="mt-2 space-y-1.5">
            <a href="<?php echo e($publicUserMenuPrimaryUrl); ?>" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-blue-600 transition hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-500/10">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300" aria-hidden="true">
                    <span class="app-icon"><?php echo dashboard_icon_svg($publicUserMenuPrimaryIcon, 'class="h-4 w-4 shrink-0"'); ?></span>
                </span>
                <span class="text-sm font-semibold"><?php echo e($publicUserMenuPrimaryLabel); ?></span>
            </a>

            <form method="post" action="<?php echo e($publicUserMenuLogoutUrl); ?>">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-rose-500 transition hover:bg-rose-50 dark:hover:bg-rose-900/20">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-500 dark:bg-rose-900/20" aria-hidden="true">
                        <span class="app-icon"><?php echo dashboard_icon_svg('log-out', 'class="h-4 w-4 shrink-0"'); ?></span>
                    </span>
                    <span class="text-sm font-semibold">Se d&eacute;connecter</span>
                </button>
            </form>
        </div>
    </div>
</details>
