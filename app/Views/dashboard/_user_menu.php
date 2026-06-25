<?php

$userMenuUser = is_array($userMenuUser ?? null) ? $userMenuUser : [];
$userMenuRoleLabel = trim((string) ($userMenuRoleLabel ?? 'Compte'));
$userMenuProfileUrl = (string) ($userMenuProfileUrl ?? site_url('profile'));
$userMenuLogoutUrl = (string) ($userMenuLogoutUrl ?? site_url('logout'));
$userMenuClass = trim((string) ($userMenuClass ?? ''));
$userMenuTriggerClass = trim((string) ($userMenuTriggerClass ?? ''));
$userMenuPanelClass = trim((string) ($userMenuPanelClass ?? ''));
$userMenuNotificationCount = max(0, (int) ($userMenuNotificationCount ?? 0));
$userMenuSupportCount = max(0, (int) ($userMenuSupportCount ?? 0));
$userMenuTotalCount = max(0, (int) ($userMenuTotalCount ?? ($userMenuNotificationCount + $userMenuSupportCount)));
$userMenuTriggerBadgeCount = max(0, (int) ($userMenuTriggerBadgeCount ?? $userMenuTotalCount));
$userMenuShowTriggerBadge = !isset($userMenuShowTriggerBadge) || (bool) $userMenuShowTriggerBadge;
$userMenuShowHeaderPill = !isset($userMenuShowHeaderPill) || (bool) $userMenuShowHeaderPill;
$userMenuShowRoleLine = !empty($userMenuShowRoleLine);
$userMenuDisplayName = dashboard_user_display_name($userMenuUser, 'Utilisateur');
$userMenuInitials = dashboard_user_initials($userMenuUser, 'US');
$userMenuAvatarUrl = dashboard_user_avatar_url($userMenuUser);
$userMenuEmail = trim((string) ($userMenuUser['email'] ?? ''));
$userMenuTriggerLabel = trim($userMenuDisplayName . ($userMenuRoleLabel !== '' ? ' · ' . $userMenuRoleLabel : ''));
if ($userMenuTriggerLabel === '') {
    $userMenuTriggerLabel = 'Compte';
}

$userMenuLogoutItem = ['label' => 'Se déconnecter', 'url' => $userMenuLogoutUrl, 'icon' => 'log-out'];
?>
<details class="app-user-menu <?php echo e($userMenuClass); ?>">
    <summary class="app-user-menu__trigger app-user-menu__trigger--compact <?php echo e($userMenuTriggerClass); ?>" aria-label="Ouvrir le menu utilisateur" aria-haspopup="menu">
        <span class="app-user-menu__avatar" aria-hidden="true">
            <?php if ($userMenuAvatarUrl !== ''): ?>
                <img
                    src="<?php echo e($userMenuAvatarUrl); ?>"
                    alt=""
                    class="hidden app-user-menu__avatar-image"
                    onload="this.classList.remove('hidden');this.nextElementSibling.classList.add('hidden');"
                    onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden');"
                >
                <span class="app-user-menu__avatar-fallback"><?php echo e($userMenuInitials); ?></span>
            <?php else: ?>
                <span class="app-user-menu__avatar-fallback"><?php echo e($userMenuInitials); ?></span>
            <?php endif; ?>
            <?php if ($userMenuShowTriggerBadge): ?>
                <span class="app-user-menu__trigger-badge <?php echo $userMenuTriggerBadgeCount > 0 ? '' : 'hidden'; ?>" data-live-user-menu-total-badge><?php echo e((string) $userMenuTriggerBadgeCount); ?></span>
            <?php endif; ?>
        </span>
        <span class="sr-only"><?php echo e($userMenuTriggerLabel); ?></span>
        <span class="app-icon app-user-menu__caret" aria-hidden="true"><?php echo dashboard_icon_svg('chevron-down'); ?></span>
    </summary>

    <div class="app-user-menu__panel <?php echo e($userMenuPanelClass); ?>">
        <div class="app-user-menu__header">
            <span class="app-user-menu__header-avatar" aria-hidden="true">
                <?php if ($userMenuAvatarUrl !== ''): ?>
                    <img
                        src="<?php echo e($userMenuAvatarUrl); ?>"
                        alt=""
                        class="hidden app-user-menu__avatar-image"
                        onload="this.classList.remove('hidden');this.nextElementSibling.classList.add('hidden');"
                        onerror="this.classList.add('hidden');this.nextElementSibling.classList.remove('hidden');"
                    >
                    <span class="app-user-menu__avatar-fallback"><?php echo e($userMenuInitials); ?></span>
                <?php else: ?>
                    <span class="app-user-menu__avatar-fallback"><?php echo e($userMenuInitials); ?></span>
                <?php endif; ?>
            </span>
            <div class="app-user-menu__header-copy">
                <p class="app-user-menu__header-name"><?php echo e($userMenuDisplayName); ?></p>
                <?php if ($userMenuEmail !== ''): ?>
                    <p class="app-user-menu__header-email"><?php echo e($userMenuEmail); ?></p>
                <?php endif; ?>
                <?php if ($userMenuRoleLabel !== '' && ($userMenuShowRoleLine || $userMenuEmail === '')): ?>
                    <p class="app-user-menu__header-role"><?php echo e($userMenuRoleLabel); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($userMenuShowHeaderPill && $userMenuRoleLabel !== ''): ?>
                <span class="app-user-menu__header-pill"><?php echo e($userMenuRoleLabel); ?></span>
            <?php endif; ?>
        </div>

        <nav class="app-user-menu__list" aria-label="Navigation du compte">
            <a href="<?php echo e($userMenuProfileUrl); ?>" class="app-user-menu__item">
                <span class="app-user-menu__item-copy">
                    <span class="app-icon app-user-menu__item-icon" aria-hidden="true"><?php echo dashboard_icon_svg('user'); ?></span>
                    <span class="app-user-menu__item-label">Mon profil</span>
                </span>
            </a>
        </nav>

        <div class="app-user-menu__separator" aria-hidden="true"></div>

        <form method="post" action="<?php echo e((string) ($userMenuLogoutItem['url'] ?? '#')); ?>">
            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
            <button type="submit" class="app-user-menu__item app-user-menu__item--danger">
                <span class="app-user-menu__item-copy">
                    <span class="app-icon app-user-menu__item-icon" aria-hidden="true"><?php echo dashboard_icon_svg((string) ($userMenuLogoutItem['icon'] ?? 'log-out')); ?></span>
                    <span class="app-user-menu__item-label"><?php echo e((string) ($userMenuLogoutItem['label'] ?? 'Lien')); ?></span>
                </span>
            </button>
        </form>
    </div>
</details>
