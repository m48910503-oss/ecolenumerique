<?php
$appFromName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
$platformName = trim((string) ($platformName ?? ($siteName ?? platform_setting_get('site_name', $appFromName))));
if ($platformName === '') {
    $platformName = $appFromName;
}
$platformSubtitle = trim((string) ($siteTagline ?? ($siteDescription ?? '')));
if ($platformSubtitle === '') {
    $platformSubtitle = 'Des formations claires, utiles et accessibles.';
}

$platformLogoUrl = trim((string) ($platformLogoUrl ?? ($siteLogoUrl ?? '')));
$platformLogoAlt = trim((string) ($platformLogoAlt ?? ('Logo ' . $platformName)));
$homeUrl = trim((string) ($homeUrl ?? site_url('')));
$catalogueUrl = trim((string) ($catalogueUrl ?? site_url('catalogue')));
$teacherApplyUrl = trim((string) ($teacherApplyUrl ?? site_url('become-teacher')));
$supportUrl = trim((string) ($supportUrl ?? site_url('support')));
$loginUrl = trim((string) ($loginUrl ?? site_url('login')));
$registerUrl = trim((string) ($registerUrl ?? site_url('register')));
$notificationsUrl = trim((string) ($notificationsUrl ?? site_url('notifications')));
$dashboardUrl = trim((string) ($dashboardUrl ?? site_url('student/dashboard')));
$cartCount = max(0, (int) ($cartCount ?? 0));
$headerNotificationCount = max(0, (int) ($headerNotificationCount ?? ($totalUnread ?? 0)));
$isLoggedIn = !empty($isLoggedIn) || !empty($currentUser);
$headerLoginLabel = 'Se connecter';
$headerLoginUrl = $loginUrl;
$headerPrimaryLabel = $isLoggedIn ? 'Tableau de bord' : 'Créer un compte';
$headerPrimaryUrl = $isLoggedIn ? $dashboardUrl : $registerUrl;
$publicNavPanelId = trim((string) ($publicNavPanelId ?? 'public-nav-panel'));
$publicHeaderPage = trim((string) ($publicPage ?? ''));

$publicHeaderHomeClass = $publicHeaderPage === 'home'
    ? 'border-b-2 border-blue-600 pb-1 font-semibold text-blue-600 dark:border-blue-400 dark:text-blue-300'
    : 'text-gray-600 transition hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-300';
$publicHeaderCatalogueClass = $publicHeaderPage === 'catalogue'
    ? 'border-b-2 border-blue-600 pb-1 font-semibold text-blue-600 dark:border-blue-400 dark:text-blue-300'
    : 'text-gray-600 transition hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-300';
$isTeacherPublicPage = in_array($publicHeaderPage, ['teacher/apply', 'teacher/become'], true);
$publicHeaderTeacherClass = $isTeacherPublicPage
    ? 'rounded-full border border-blue-300 bg-blue-50 px-4 py-2 text-blue-700 transition dark:border-blue-400/40 dark:bg-blue-500/10 dark:text-blue-200'
    : 'rounded-full border border-blue-200 px-4 py-2 text-blue-700 transition hover:bg-blue-50 dark:border-blue-500/30 dark:text-blue-300 dark:hover:bg-blue-500/10';
$publicHeaderMobileHomeClass = $publicHeaderPage === 'home'
    ? 'font-semibold text-blue-600 dark:text-blue-300'
    : 'text-gray-600 transition hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-300';
$publicHeaderMobileCatalogueClass = $publicHeaderPage === 'catalogue'
    ? 'font-semibold text-blue-600 dark:text-blue-300'
    : 'text-gray-600 transition hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-300';
$publicHeaderMobileTeacherClass = $isTeacherPublicPage
    ? 'font-semibold text-blue-600 dark:text-blue-300'
    : 'text-gray-600 transition hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-300';
?>
<header class="mk-header sticky top-0 z-50 border-b border-gray-200/60 bg-white/80 backdrop-blur-xl dark:border-gray-800 dark:bg-gray-950/80">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex min-h-[4.5rem] items-center justify-between gap-4 py-3 md:min-h-[5rem]">
      <a href="<?php echo e($homeUrl); ?>" class="flex min-w-0 items-center gap-3">
        <?php if ($platformLogoUrl !== ''): ?>
          <img src="<?php echo e($platformLogoUrl); ?>" alt="<?php echo e($platformLogoAlt); ?>" class="h-9 w-9 rounded-xl object-cover shadow-sm sm:h-10 sm:w-10">
        <?php else: ?>
          <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-md sm:h-10 sm:w-10">
            <i class="fas fa-graduation-cap"></i>
          </span>
        <?php endif; ?>
        <span class="flex min-w-0 flex-col leading-tight">
          <span class="truncate text-base font-bold tracking-tight text-gray-900 dark:text-white sm:text-xl">
            <?php echo site_brand_name_html($platformName); ?>
          </span>
          <span class="truncate text-[11px] font-medium text-gray-500 dark:text-gray-400">
            <?php echo e($platformSubtitle); ?>
          </span>
        </span>
      </a>

      <nav class="public-desktop-nav hidden items-center gap-8 text-sm font-medium md:flex">
        <a href="<?php echo e($homeUrl); ?>" class="<?php echo e($publicHeaderHomeClass); ?>"<?php echo $publicHeaderPage === 'home' ? ' aria-current="page"' : ''; ?>>Accueil</a>
        <a href="<?php echo e($catalogueUrl); ?>" class="<?php echo e($publicHeaderCatalogueClass); ?>"<?php echo $publicHeaderPage === 'catalogue' ? ' aria-current="page"' : ''; ?>>Catalogue</a>
        <a href="<?php echo e($teacherApplyUrl); ?>" class="<?php echo e($publicHeaderTeacherClass); ?>"<?php echo $isTeacherPublicPage ? ' aria-current="page"' : ''; ?>>
          <i class="fas fa-chalkboard-user mr-1"></i> Devenir enseignant
        </a>
      </nav>

      <div class="flex shrink-0 items-center gap-1.5 sm:gap-3">
        <button
          type="button"
          data-web-theme-toggle
          class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300 sm:h-11 sm:w-11"
        >
          <span class="inline-flex h-5 w-5 items-center justify-center" data-theme-icon-dark aria-hidden="true"><?php echo dashboard_icon_svg('theme-moon', 'class="h-5 w-5"'); ?></span>
          <span class="inline-flex h-5 w-5 items-center justify-center" data-theme-icon-light aria-hidden="true" hidden><?php echo dashboard_icon_svg('theme-sun', 'class="h-5 w-5"'); ?></span>
          <span class="sr-only">Changer le thème</span>
        </button>
        <?php if ($isLoggedIn): ?>
          <a
            href="<?php echo e($notificationsUrl); ?>"
            class="public-mobile-header-notification relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 md:hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300"
            aria-label="Notifications"
          >
            <i class="fas fa-bell" aria-hidden="true"></i>
            <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-bold text-white<?php echo $headerNotificationCount > 0 ? '' : ' hidden'; ?>"><?php echo e((string) $headerNotificationCount); ?></span>
          </a>
        <?php endif; ?>
        <a
          href="<?php echo e(site_url('checkout')); ?>"
          class="relative hidden h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300 sm:inline-flex sm:h-11 sm:w-11"
          data-catalog-cart-link
          aria-label="Panier"
        >
          <i class="fas fa-cart-shopping" aria-hidden="true"></i>
          <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-blue-600 px-1 text-[11px] font-bold text-white<?php echo $cartCount > 0 ? '' : ' hidden'; ?>" data-catalog-cart-count><?php echo e((string) $cartCount); ?></span>
        </a>
        <div class="public-desktop-actions hidden items-center gap-2 md:flex">
          <?php if ($isLoggedIn): ?>
            <a
              href="<?php echo e($notificationsUrl); ?>"
              class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300"
              aria-label="Notifications"
            >
              <i class="fas fa-bell" aria-hidden="true"></i>
              <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-bold text-white<?php echo $headerNotificationCount > 0 ? '' : ' hidden'; ?>"><?php echo e((string) $headerNotificationCount); ?></span>
            </a>
          <?php else: ?>
            <a href="<?php echo e($headerLoginUrl); ?>" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300"><?php echo e($headerLoginLabel); ?></a>
          <?php endif; ?>
          <a href="<?php echo e($headerPrimaryUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700">
            <i class="fas <?php echo e($isLoggedIn ? 'fa-table-columns' : 'fa-user-plus'); ?>" aria-hidden="true"></i>
            <?php echo e($headerPrimaryLabel); ?>
          </a>
        </div>
        <button
          type="button"
          class="public-mobile-toggle inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 md:hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 sm:h-11 sm:w-11"
          aria-expanded="false"
          aria-controls="<?php echo e($publicNavPanelId); ?>"
          aria-haspopup="dialog"
          aria-label="Ouvrir la navigation"
          data-public-nav-toggle
        >
          <span class="public-mobile-toggle__box" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
          </span>
          <span class="sr-only" data-public-nav-label>Ouvrir la navigation</span>
        </button>
      </div>
    </div>

  </div>
</header>

<div class="public-mobile-panel" id="<?php echo e($publicNavPanelId); ?>" data-public-nav-panel aria-hidden="true" role="dialog" aria-modal="true" aria-label="Navigation mobile">
  <button type="button" class="public-mobile-panel__backdrop" data-public-nav-backdrop data-public-nav-close tabindex="-1" aria-label="Fermer le menu"></button>

  <div class="public-mobile-panel__sheet" role="document" tabindex="-1">
    <div class="public-mobile-panel__head">
      <div class="min-w-0">
        <p class="public-mobile-panel__eyebrow">Menu</p>
        <div class="public-mobile-panel__brand">
          <?php if ($platformLogoUrl !== ''): ?>
            <img src="<?php echo e($platformLogoUrl); ?>" alt="<?php echo e($platformLogoAlt); ?>" class="public-mobile-panel__brand-logo">
          <?php else: ?>
            <span class="public-mobile-panel__brand-logo public-mobile-panel__brand-logo--fallback" aria-hidden="true">
              <i class="fas fa-graduation-cap"></i>
            </span>
          <?php endif; ?>
          <h2 class="public-mobile-panel__title"><?php echo site_brand_name_html($platformName); ?></h2>
        </div>
      </div>
      <button type="button" class="public-mobile-panel__close" data-public-nav-close aria-label="Fermer le menu">
        <i class="fas fa-xmark" aria-hidden="true"></i>
      </button>
    </div>

    <div class="public-mobile-panel__section">
      <p class="public-mobile-panel__section-label">Explorer</p>
      <nav class="public-mobile-panel__nav" aria-label="Navigation principale">
        <a href="<?php echo e($homeUrl); ?>" class="public-mobile-panel__link <?php echo e($publicHeaderMobileHomeClass); ?>"<?php echo $publicHeaderPage === 'home' ? ' aria-current="page"' : ''; ?>>
          <span class="public-mobile-panel__link-icon" aria-hidden="true"><i class="fas fa-house"></i></span>
          <span class="public-mobile-panel__link-text">Accueil</span>
          <i class="fas fa-chevron-right public-mobile-panel__link-chevron" aria-hidden="true"></i>
        </a>
        <a href="<?php echo e($catalogueUrl); ?>" class="public-mobile-panel__link <?php echo e($publicHeaderMobileCatalogueClass); ?>"<?php echo $publicHeaderPage === 'catalogue' ? ' aria-current="page"' : ''; ?>>
          <span class="public-mobile-panel__link-icon" aria-hidden="true"><i class="fas fa-book-open"></i></span>
          <span class="public-mobile-panel__link-text">Catalogue</span>
          <i class="fas fa-chevron-right public-mobile-panel__link-chevron" aria-hidden="true"></i>
        </a>
        <a href="<?php echo e($teacherApplyUrl); ?>" class="public-mobile-panel__link <?php echo e($publicHeaderMobileTeacherClass); ?>"<?php echo $isTeacherPublicPage ? ' aria-current="page"' : ''; ?>>
          <span class="public-mobile-panel__link-icon" aria-hidden="true"><i class="fas fa-chalkboard-user"></i></span>
          <span class="public-mobile-panel__link-text">Devenir enseignant</span>
          <i class="fas fa-chevron-right public-mobile-panel__link-chevron" aria-hidden="true"></i>
        </a>
      </nav>
    </div>

    <div class="public-mobile-panel__section">
      <p class="public-mobile-panel__section-label">Accès rapides</p>
      <div class="public-mobile-panel__actions">
        <a href="<?php echo e(site_url('checkout')); ?>" class="public-mobile-panel__action public-mobile-panel__action--soft" data-catalog-cart-link>
          <span class="public-mobile-panel__action-icon" aria-hidden="true">
            <i class="fas fa-cart-shopping"></i>
          </span>
          <span class="public-mobile-panel__action-text">Panier</span>
          <?php if ($cartCount > 0): ?>
            <span class="public-mobile-panel__badge public-mobile-panel__badge--primary" data-catalog-cart-count><?php echo e((string) $cartCount); ?></span>
          <?php endif; ?>
        </a>

        <?php if (!$isLoggedIn): ?>
          <a href="<?php echo e($headerLoginUrl); ?>" class="public-mobile-panel__action public-mobile-panel__action--soft">
            <span class="public-mobile-panel__action-icon" aria-hidden="true">
              <i class="fas fa-right-to-bracket"></i>
            </span>
            <span class="public-mobile-panel__action-text"><?php echo e($headerLoginLabel); ?></span>
          </a>
        <?php endif; ?>

        <a href="<?php echo e($headerPrimaryUrl); ?>" class="public-mobile-panel__action public-mobile-panel__action--primary">
          <span class="public-mobile-panel__action-icon" aria-hidden="true">
            <i class="fas <?php echo e($isLoggedIn ? 'fa-table-columns' : 'fa-user-plus'); ?>"></i>
          </span>
          <span class="public-mobile-panel__action-text"><?php echo e($headerPrimaryLabel); ?></span>
        </a>
      </div>
    </div>
  </div>
</div>
