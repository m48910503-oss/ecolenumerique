<?php
$publicNavPanelId = 'public-nav-panel';
$platformName = trim((string) ($siteName ?? (defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique')));
if ($platformName === '') {
    $platformName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
}
$platformSubtitle = trim((string) ($siteTagline ?? ($siteDescription ?? $publicBrandSubtitle)));
if ($platformSubtitle === '') {
    $platformSubtitle = 'Des formations numériques claires et accessibles.';
}
?>
<header class="mk-header" data-public-header>
    <div class="mk-shell">
        <div class="mk-header__bar">
            <a class="mk-brand" href="<?php echo e($homeUrl); ?>" aria-label="<?php echo e($platformName); ?>, retour à l'accueil">
                <span class="mk-brand__mark" aria-hidden="true">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?php echo e($siteLogoUrl); ?>" alt="" width="52" height="52" loading="eager" decoding="async">
                    <?php else: ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                            <path d="M2 12l10 5 10-5"></path>
                            <path d="M2 17l10 5 10-5"></path>
                        </svg>
                    <?php endif; ?>
                </span>
                <span class="mk-brand__wording">
                    <span class="mk-brand__title"><?php echo site_brand_name_html($platformName); ?></span>
                    <span class="mk-brand__subtitle"><?php echo e($platformSubtitle); ?></span>
                </span>
            </a>

            <button
                class="mk-header__toggle"
                type="button"
                aria-expanded="false"
                aria-controls="<?php echo e($publicNavPanelId); ?>"
                data-public-nav-toggle
            >
                <span class="mk-header__toggle-box" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span class="mk-sr-only">Ouvrir la navigation</span>
            </button>

            <div class="mk-header__panel" id="<?php echo e($publicNavPanelId); ?>" data-public-nav-panel>
                <nav class="mk-header__nav" aria-label="Navigation principale">
                    <?php foreach ($publicNavLinks as $item): ?>
                        <?php $isCurrent = ($publicPage === (string) ($item['key'] ?? '')); ?>
                        <a
                            class="mk-header__link"
                            href="<?php echo e((string) ($item['href'] ?? '#')); ?>"
                            <?php if ($isCurrent): ?>aria-current="page"<?php endif; ?>
                        >
                            <?php echo e((string) ($item['label'] ?? 'Lien')); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="mk-header__actions">
                    <div class="mk-header__account">
                        <span class="mk-header__account-label"><?php echo e($publicHeaderMetaLabel); ?></span>
                        <div class="mk-header__account-links">
                            <?php if ($isLoggedIn): ?>
                                <a class="mk-header__account-link" href="<?php echo e($notificationsUrl); ?>">
                                    <span class="app-icon" aria-hidden="true"><?php echo function_exists('dashboard_icon_svg') ? dashboard_icon_svg('bell') : ''; ?></span>
                                    <span>Notifications</span>
                                    <?php if ($totalUnread > 0): ?>
                                        <span class="mk-count-badge"><?php echo e((string) $totalUnread); ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>
                            <a class="mk-header__account-link" href="<?php echo e($publicHeaderSecondaryUrl); ?>"><?php echo e($publicHeaderSecondaryLabel); ?></a>
                        </div>
                    </div>
                    <a class="mk-btn mk-btn--primary" href="<?php echo e($publicHeaderPrimaryUrl); ?>"><?php echo e($publicHeaderPrimaryLabel); ?></a>
                </div>
            </div>
        </div>
    </div>
</header>
