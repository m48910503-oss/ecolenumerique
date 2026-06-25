<?php
$isLoggedIn = !empty($isLoggedIn);
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$siteName = trim((string) ($siteName ?? (defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique')));
if ($siteName === '') {
    $siteName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
}
$siteTagline = trim((string) ($siteTagline ?? ''));
$siteDescription = trim((string) ($siteDescription ?? ''));
$supportEmail = trim((string) ($supportEmail ?? ''));
$supportPhone = trim((string) ($supportPhone ?? ''));
$supportWhatsapp = trim((string) ($supportWhatsapp ?? ''));
$siteAddress = trim((string) ($siteAddress ?? ''));
$supportPhoneDigits = preg_replace('/\D+/', '', $supportPhone) ?: '';
$supportWhatsappDigits = preg_replace('/\D+/', '', $supportWhatsapp) ?: '';
$footerText = trim((string) ($footerText ?? ''));
$dashboardUrl = trim((string) ($dashboardUrl ?? site_url('student/dashboard')));
$publicPage = trim((string) ($publicPage ?? 'home'));
$publicBrandSubtitle = trim((string) ($publicBrandSubtitle ?? 'Des formations numériques conçues pour devenir utiles.'));
$publicHeaderMetaLabel = trim((string) ($publicHeaderMetaLabel ?? ($isLoggedIn ? 'Compte connecté' : 'Déjà membre ?')));
$publicHeaderSecondaryLabel = trim((string) ($publicHeaderSecondaryLabel ?? ($isLoggedIn ? 'Mon profil' : 'Se connecter')));
$publicHeaderSecondaryUrl = trim((string) ($publicHeaderSecondaryUrl ?? ($isLoggedIn ? site_url('profile') : site_url('login'))));
$publicHeaderPrimaryLabel = trim((string) ($publicHeaderPrimaryLabel ?? ($isLoggedIn ? 'Accéder à mon espace' : 'Créer un compte')));
$publicHeaderPrimaryUrl = trim((string) ($publicHeaderPrimaryUrl ?? ($isLoggedIn ? $dashboardUrl : site_url('register'))));
$publicFooterNote = trim((string) ($publicFooterNote ?? ($siteDescription !== '' ? $siteDescription : "L'espace public est pensé pour découvrir les formations. Les espaces personnels restent dédiés au suivi et à l'apprentissage.")));

$publicAnnouncement = is_array($publicAnnouncement ?? null) ? $publicAnnouncement : [];

$homeUrl = site_url('');
$catalogueUrl = site_url('catalogue');
$teacherApplyUrl = site_url('become-teacher');
$supportUrl = site_url('support');
$privacyUrl = site_url('privacy');
$cguUrl = site_url('cgu');
$loginUrl = site_url('login');
$registerUrl = site_url('register');
$notificationsUrl = site_url('notifications');
$profileUrl = site_url('profile');
$logoutUrl = site_url('logout');
$totalUnread = max(0, (int) ($notifCount ?? 0) + (int) ($supportUnreadCount ?? 0));

$publicNavLinks = [
    ['key' => 'catalogue', 'label' => 'Catalogue', 'href' => $catalogueUrl],
    ['key' => 'parcours', 'label' => 'Parcours', 'href' => $homeUrl . '#parcours'],
    ['key' => 'entreprises', 'label' => 'Entreprises', 'href' => $homeUrl . '#entreprises'],
    ['key' => 'support', 'label' => 'Support', 'href' => $supportUrl],
];

$publicFooterColumns = [
    [
        'title' => 'Explorer',
        'links' => [
            ['label' => 'Accueil', 'href' => $homeUrl],
            ['label' => 'Catalogue', 'href' => $catalogueUrl],
            ['label' => 'Parcours', 'href' => $homeUrl . '#parcours'],
            ['label' => 'Devenir enseignant', 'href' => $teacherApplyUrl],
        ],
    ],
    [
        'title' => 'Accompagnement',
        'links' => [
            ['label' => 'Support', 'href' => $supportUrl],
            ['label' => 'Entreprises', 'href' => $homeUrl . '#entreprises'],
            ['label' => 'Confidentialité', 'href' => $privacyUrl],
            ['label' => 'CGU', 'href' => $cguUrl],
        ],
    ],
    [
        'title' => $isLoggedIn ? 'Mon espace' : 'Compte',
        'links' => $isLoggedIn
            ? [
                ['label' => 'Accéder à mon espace', 'href' => $dashboardUrl],
                ['label' => 'Mon profil', 'href' => $profileUrl],
                ['label' => 'Notifications', 'href' => $notificationsUrl, 'icon' => 'bell'],
                ['label' => 'Se déconnecter', 'href' => $logoutUrl, 'post' => true],
            ]
            : [
                ['label' => 'Se connecter', 'href' => $loginUrl],
                ['label' => 'Créer un compte', 'href' => $registerUrl],
                ['label' => 'Découvrir le catalogue', 'href' => $catalogueUrl],
                ['label' => 'Contacter un conseiller', 'href' => $supportUrl],
            ],
    ],
];
?>
<footer class="mk-footer">
    <div class="mk-shell">
        <div class="mk-footer__grid">
            <div class="mk-footer__brand">
                <div class="mk-brand__title"><?php echo e($siteName); ?></div>
                <p class="mk-footer__lead"><?php echo e($siteTagline !== '' ? $siteTagline : 'Une plateforme éducative claire et structurée.'); ?></p>
                <p class="mk-footer__note"><?php echo e($siteDescription !== '' ? $siteDescription : $publicFooterNote); ?></p>
                <?php if ($supportEmail !== '' || $supportPhone !== '' || $supportWhatsapp !== '' || $siteAddress !== ''): ?>
                    <div class="mt-5 space-y-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4 text-sm text-slate-300">
                        <?php if ($supportEmail !== ''): ?>
                            <p class="flex items-center gap-2">
                                <i class="fas fa-envelope text-cyan-300" aria-hidden="true"></i>
                                <a href="<?php echo e('mailto:' . $supportEmail); ?>" class="transition hover:text-white"><?php echo e($supportEmail); ?></a>
                            </p>
                        <?php endif; ?>
                        <?php if ($supportPhone !== ''): ?>
                            <p class="flex items-center gap-2">
                                <i class="fas fa-phone text-emerald-300" aria-hidden="true"></i>
                                <a href="<?php echo e($supportPhoneDigits !== '' ? 'tel:' . $supportPhoneDigits : '#'); ?>" class="transition hover:text-white"><?php echo e($supportPhone); ?></a>
                            </p>
                        <?php endif; ?>
                        <?php if ($supportWhatsapp !== ''): ?>
                            <p class="flex items-center gap-2">
                                <i class="fab fa-whatsapp text-green-300" aria-hidden="true"></i>
                                <a href="<?php echo e($supportWhatsappDigits !== '' ? 'https://wa.me/' . $supportWhatsappDigits : $supportUrl); ?>" class="transition hover:text-white" <?php echo $supportWhatsappDigits !== '' ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo e($supportWhatsapp); ?></a>
                            </p>
                        <?php endif; ?>
                        <?php if ($siteAddress !== ''): ?>
                            <p class="flex items-start gap-2">
                                <i class="fas fa-location-dot mt-0.5 text-orange-300" aria-hidden="true"></i>
                                <span><?php echo e($siteAddress); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php foreach ($publicFooterColumns as $column): ?>
                <nav class="mk-footer__column" aria-label="<?php echo e((string) ($column['title'] ?? 'Navigation')); ?>">
                    <h2 class="mk-footer__title"><?php echo e((string) ($column['title'] ?? 'Navigation')); ?></h2>
                    <ul class="mk-footer__links">
                        <?php foreach ((array) ($column['links'] ?? []) as $link): ?>
                            <?php $isPost = !empty($link['post']); ?>
                            <li>
                                <?php if ($isPost): ?>
                                    <form method="post" action="<?php echo e((string) ($link['href'] ?? '#')); ?>">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <button type="submit" class="mk-footer__link-button">
                                            <?php $linkIcon = trim((string) ($link['icon'] ?? '')); ?>
                                            <?php if ($linkIcon !== ''): ?><span class="app-icon" aria-hidden="true"><?php echo function_exists('dashboard_icon_svg') ? dashboard_icon_svg($linkIcon) : ''; ?></span><?php endif; ?>
                                            <span><?php echo e((string) ($link['label'] ?? 'Lien')); ?></span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?php echo e((string) ($link['href'] ?? '#')); ?>">
                                        <?php $linkIcon = trim((string) ($link['icon'] ?? '')); ?>
                                        <?php if ($linkIcon !== ''): ?><span class="app-icon" aria-hidden="true"><?php echo function_exists('dashboard_icon_svg') ? dashboard_icon_svg($linkIcon) : ''; ?></span><?php endif; ?>
                                        <span><?php echo e((string) ($link['label'] ?? 'Lien')); ?></span>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endforeach; ?>
        </div>

        <div class="mk-footer__bottom">
            <p><?php echo e($footerText !== '' ? $footerText : ('© ' . date('Y') . ' ' . $siteName . '.')); ?></p>
            <div class="mk-footer__bottom-links">
                <a href="<?php echo e($privacyUrl); ?>">Confidentialité</a>
                <a href="<?php echo e($cguUrl); ?>">CGU</a>
                <a href="<?php echo e($supportUrl); ?>">Support</a>
            </div>
        </div>
    </div>
</footer>
