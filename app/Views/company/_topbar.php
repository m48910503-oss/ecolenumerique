<?php
declare(strict_types=1);

$companyTopTitle = trim((string) ($companyTopTitle ?? 'Pilotage de l’entreprise'));
$companyTopEyebrow = trim((string) ($companyTopEyebrow ?? 'Compte entreprise'));
$companyTopSummary = trim((string) ($companyTopSummary ?? ''));
$companyTopStatusLabel = trim((string) ($companyTopStatusLabel ?? ''));
$companyTopStatusTone = trim((string) ($companyTopStatusTone ?? 'info'));
$companyTopMeta = is_array($companyTopMeta ?? null) ? $companyTopMeta : [];
$companyTopActions = is_array($companyTopActions ?? null) ? $companyTopActions : [];
$companyTopPrimaryUrl = (string) ($companyTopPrimaryUrl ?? site_url('company/dashboard'));
$companyTopPrimaryLabel = trim((string) ($companyTopPrimaryLabel ?? 'Tableau de bord'));
$companyUser = is_array($user ?? null) ? $user : [];
$companyRecord = is_array($company ?? null) ? $company : [];
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$companyLogoPath = trim((string) ($companyTopLogoPath ?? ($companyRecord['logo_path'] ?? '')));
$companyLogoUrl = '';
if ($companyLogoPath !== '') {
    $companyLogoUrl = preg_match('#^https?://#i', $companyLogoPath) === 1 ? $companyLogoPath : site_url(ltrim($companyLogoPath, '/'));
}
if ($companyLogoUrl === '') {
    $companyLogoUrl = $siteLogoUrl;
}
$companyUnreadCount = max(0, (int) ($unreadCount ?? 0));
$companyNotificationsUrl = (string) ($companyNotificationsUrl ?? site_url('notifications'));
$companyHelpUrl = (string) ($companyHelpUrl ?? site_url('company/help'));
$companyUserMenuLabel = trim((string) ($companyUserMenuLabel ?? 'Compte entreprise'));
$actionClass = static function (array $action): string {
    $isCurrent = !empty($action['current']);
    return 'app-segmented-link company-shell-action inline-flex items-center gap-2' . ($isCurrent ? ' app-segmented-link--active' : '');
};
?>
<header class="app-shell-toolbar company-shell-toolbar sticky top-0 z-30">
    <div class="company-shell-toolbar__inner">
        <div class="company-shell-toolbar__brand">
            <a href="<?php echo e($companyTopPrimaryUrl); ?>" class="company-shell-toolbar__logo" aria-label="<?php echo e($companyTopPrimaryLabel); ?>">
                <?php if ($companyLogoUrl !== ''): ?><img src="<?php echo e($companyLogoUrl); ?>" alt="Logo entreprise" class="company-shell-toolbar__logo-image"><?php else: ?><span class="company-shell-toolbar__logo-text">ENT</span><?php endif; ?>
            </a>
            <div class="company-shell-toolbar__content">
                <p class="app-shell-toolbar__eyebrow"><?php echo e($companyTopEyebrow); ?></p>
                <div class="company-shell-toolbar__headline">
                    <h1 class="app-shell-toolbar__title"><?php echo e($companyTopTitle); ?></h1>
                    <?php if ($companyTopStatusLabel !== ''): ?><span class="<?php echo e(app_status_pill_class($companyTopStatusTone)); ?>"><?php echo e($companyTopStatusLabel); ?></span><?php endif; ?>
                </div>
                <?php if ($companyTopSummary !== ''): ?><p class="company-shell-toolbar__summary"><?php echo e($companyTopSummary); ?></p><?php endif; ?>
                <?php if ($companyTopMeta !== []): ?>
                    <div class="company-shell-toolbar__meta">
                        <?php foreach ($companyTopMeta as $meta): ?>
                            <?php $metaLabel = trim((string) ($meta['label'] ?? '')); if ($metaLabel === '') { continue; } $metaTone = trim((string) ($meta['tone'] ?? 'done')); ?>
                            <span class="<?php echo e(app_status_pill_class($metaTone)); ?>"><?php echo e($metaLabel); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="app-shell-toolbar__actions company-shell-toolbar__actions">
            <?php foreach ($companyTopActions as $action): ?>
                <?php $actionLabel = trim((string) ($action['label'] ?? '')); $actionUrl = (string) ($action['url'] ?? '#'); $actionIcon = trim((string) ($action['icon'] ?? '')); $actionBadge = max(0, (int) ($action['badge'] ?? 0)); if ($actionLabel === '') { continue; } ?>
                <a href="<?php echo e($actionUrl); ?>" class="<?php echo e($actionClass($action)); ?>">
                    <?php if ($actionIcon !== ''): ?><span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($actionIcon); ?></span><?php endif; ?>
                    <span><?php echo e($actionLabel); ?></span>
                    <?php if ($actionBadge > 0): ?><span class="app-shell-chip__badge"><?php echo e((string) $actionBadge); ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            <button type="button" data-theme-toggle aria-pressed="false" class="app-shell-theme" aria-label="Changer de thème"><span data-theme-label aria-hidden="true"></span></button>
            <?php
            $userMenuUser = $companyUser;
            $userMenuRoleLabel = $companyUserMenuLabel;
            $userMenuNotificationsUrl = $companyNotificationsUrl;
            $userMenuSupportUrl = $companyHelpUrl;
            $userMenuNotificationCount = $companyUnreadCount;
            $userMenuSupportCount = 0;
            $userMenuTriggerClass = 'app-shell-chip company-shell-account-trigger';
            require __DIR__ . '/../dashboard/_user_menu.php';
            ?>
        </div>
    </div>
</header>
<script>
(() => {
    const body = document.body;
    if (!body || !body.matches('.app-role-company')) return;
    const toolbar = document.querySelector('.app-shell-toolbar.company-shell-toolbar');
    if (!toolbar) return;
    const syncToolbarOffset = () => {
        const height = Math.ceil(toolbar.getBoundingClientRect().height);
        if (height > 0) body.style.setProperty('--app-toolbar-offset-current', `${height}px`);
    };
    syncToolbarOffset();
    window.addEventListener('load', syncToolbarOffset, { once: true });
    window.addEventListener('resize', syncToolbarOffset, { passive: true });
    window.addEventListener('orientationchange', syncToolbarOffset, { passive: true });
    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(syncToolbarOffset);
        observer.observe(toolbar);
    }
})();
</script>
