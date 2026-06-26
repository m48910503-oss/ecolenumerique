<?php
$appFromName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
$platformName = trim((string) ($platformName ?? ($siteName ?? platform_setting_get('site_name', $appFromName))));
if ($platformName === '') {
    $platformName = $appFromName;
}
$siteTagline = trim((string) ($siteTagline ?? ''));
$siteDescription = trim((string) ($siteDescription ?? ''));
$supportEmail = trim((string) ($supportEmail ?? ''));
$supportPhone = trim((string) ($supportPhone ?? ''));
$supportWhatsapp = trim((string) ($supportWhatsapp ?? ''));
$siteAddress = trim((string) ($siteAddress ?? ''));
$footerText = trim((string) ($footerText ?? ''));
$supportPhoneDigits = preg_replace('/\D+/', '', $supportPhone) ?: '';
$supportWhatsappDigits = preg_replace('/\D+/', '', $supportWhatsapp) ?: '';
$supportUrl = trim((string) ($supportUrl ?? site_url('support')));
$supportPhoneUrl = $supportPhoneDigits !== '' ? 'tel:' . $supportPhoneDigits : '#';
$supportWhatsappUrl = $supportWhatsappDigits !== '' ? 'https://wa.me/' . $supportWhatsappDigits : $supportUrl;
$supportWhatsappTarget = $supportWhatsappDigits !== '' ? '_blank' : '_self';
$supportWhatsappRel = $supportWhatsappDigits !== '' ? 'noopener noreferrer' : '';

$footerCopyIsMeaningful = static function (string $text, bool $allowShort = false): bool {
    $text = trim($text);
    if ($text === '') {
        return false;
    }

    $compact = preg_replace('/\s+/u', '', $text) ?? $text;
    if ($compact === '') {
        return false;
    }

    if (preg_match('/^(.)\1{6,}$/u', $compact) === 1) {
        return false;
    }

    if (preg_match('/^[\p{P}\p{S}]+$/u', $compact) === 1) {
        return false;
    }

    if (!$allowShort && !preg_match('/\s/u', $text)) {
        return false;
    }

    return true;
};

$footerTagline = $footerCopyIsMeaningful($siteTagline, true)
    ? $siteTagline
    : 'Formations tech par des experts.';
$footerDescription = $footerCopyIsMeaningful($siteDescription)
    ? $siteDescription
    : 'Des parcours concrets, des supports simples et un accompagnement rapide pour progresser à votre rythme.';
$footerCopyright = $footerCopyIsMeaningful($footerText)
    ? $footerText
    : ('© ' . date('Y') . ' ' . $platformName . '. Tous droits réservés.');

$platformLogoUrl = trim((string) ($platformLogoUrl ?? ($siteLogoUrl ?? '')));
$platformLogoAlt = trim((string) ($platformLogoAlt ?? ('Logo ' . $platformName)));
$homeUrl = trim((string) ($homeUrl ?? site_url('')));
$catalogueUrl = trim((string) ($catalogueUrl ?? site_url('catalogue')));
$teacherApplyUrl = trim((string) ($teacherApplyUrl ?? site_url('become-teacher')));
$privacyUrl = trim((string) ($privacyUrl ?? site_url('privacy')));
$cguUrl = trim((string) ($cguUrl ?? site_url('cgu')));
$registerUrl = trim((string) ($registerUrl ?? site_url('register')));

$footerExploreLinks = [
    ['label' => 'Accueil', 'href' => $homeUrl, 'icon' => 'home'],
    ['label' => 'Catalogue', 'href' => $catalogueUrl, 'icon' => 'book-open'],
    ['label' => 'Catégories', 'href' => $homeUrl . '#categories', 'icon' => 'layers'],
    ['label' => 'Pourquoi nous choisir', 'href' => $homeUrl . '#why-choose', 'icon' => 'star'],
    ['label' => 'FAQ', 'href' => $homeUrl . '#faq', 'icon' => 'help-circle'],
];

$footerAccountLinks = [
    ['label' => 'Se connecter', 'href' => site_url('login'), 'icon' => 'key'],
    ['label' => 'Créer un compte', 'href' => $registerUrl, 'icon' => 'user-plus'],
    ['label' => 'Mes cours', 'href' => site_url('student/my_courses'), 'icon' => 'graduation-cap'],
    ['label' => 'Devenir enseignant', 'href' => $teacherApplyUrl, 'icon' => 'chalkboard-user'],
    ['label' => 'Support', 'href' => $supportUrl, 'icon' => 'headset'],
];
?>
<footer class="border-t border-white/5 text-white" style="background-color: #10172b;">
  <div class="mx-auto max-w-[1440px] px-4 py-16 sm:px-6 lg:px-8">
    <div class="grid gap-12 lg:grid-cols-[1.35fr_0.72fr_0.78fr] lg:gap-16">
      <section class="max-w-2xl space-y-6">
        <a href="<?php echo e($homeUrl); ?>" class="inline-flex max-w-full items-center gap-3 text-white">
          <?php if ($platformLogoUrl !== ''): ?>
            <img src="<?php echo e($platformLogoUrl); ?>" alt="<?php echo e($platformLogoAlt); ?>" class="h-11 w-11 rounded-2xl object-cover">
          <?php else: ?>
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-white">
              <?php echo dashboard_icon_svg('graduation-cap', 'class="h-5 w-5"'); ?>
            </span>
          <?php endif; ?>
          <span class="min-w-0">
            <span class="block text-[1.8rem] font-semibold tracking-tight sm:text-[2.15rem]"><?php echo e($platformName); ?></span>
            <span class="mt-1 block text-[0.72rem] font-semibold uppercase tracking-[0.28em] text-white/65"><?php echo e($footerTagline); ?></span>
          </span>
        </a>

        <p class="max-w-xl text-[1rem] leading-8 text-white/80 sm:text-[1.03rem]">
          <?php echo e($footerDescription); ?>
        </p>
      </section>

      <nav class="space-y-7" aria-label="Explorer">
        <h3 class="text-[1.15rem] font-semibold text-white">Explorer</h3>
        <div class="space-y-5 text-[1rem] leading-7 text-white/82">
          <?php foreach ($footerExploreLinks as $footerExploreLink): ?>
            <a href="<?php echo e((string) ($footerExploreLink['href'] ?? '#')); ?>" class="group flex items-center gap-3 transition hover:text-white">
              <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-white/90 transition group-hover:bg-white/15">
                <?php echo dashboard_icon_svg((string) ($footerExploreLink['icon'] ?? 'help-circle'), 'class="h-4 w-4"'); ?>
              </span>
              <?php echo e((string) ($footerExploreLink['label'] ?? 'Lien')); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </nav>

      <nav class="space-y-7" aria-label="Mon espace">
        <h3 class="text-[1.15rem] font-semibold text-white">Mon espace</h3>
        <div class="space-y-5 text-[1rem] leading-7 text-white/82">
          <?php foreach ($footerAccountLinks as $footerAccountLink): ?>
            <a href="<?php echo e((string) ($footerAccountLink['href'] ?? '#')); ?>" class="group flex items-center gap-3 transition hover:text-white">
              <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 text-white/90 transition group-hover:bg-white/15">
                <?php echo dashboard_icon_svg((string) ($footerAccountLink['icon'] ?? 'help-circle'), 'class="h-4 w-4"'); ?>
              </span>
              <?php echo e((string) ($footerAccountLink['label'] ?? 'Lien')); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </nav>
    </div>

    <div class="mt-14 border-t border-white/10 pt-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <p class="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-white/65">
          <span><?php echo e($footerCopyright); ?></span>
          <span class="hidden text-white/30 sm:inline">|</span>
          <a href="<?php echo e($cguUrl); ?>" class="transition hover:text-white">Conditions et politiques</a>
          <span class="hidden text-white/30 sm:inline">|</span>
          <a href="<?php echo e($privacyUrl); ?>" class="transition hover:text-white">Confidentialité</a>
        </p>

        <div class="flex items-center gap-3">
          <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold text-white/85">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/10 text-white">
              <?php echo dashboard_icon_svg('globe', 'class="h-5 w-5"'); ?>
            </span>
            FR
          </span>
          <a href="<?php echo e($supportUrl); ?>" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/12 text-white shadow-[0_10px_24px_-14px_rgba(255,255,255,0.25)] transition hover:bg-white/18" aria-label="Support">
            <?php echo dashboard_icon_svg('headset', 'class="h-5 w-5"'); ?>
          </a>
          <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/12 text-white shadow-[0_10px_24px_-14px_rgba(255,255,255,0.25)] transition hover:bg-white/18" aria-label="Catalogue">
            <?php echo dashboard_icon_svg('book-open', 'class="h-5 w-5"'); ?>
          </a>
          <a href="<?php echo e($registerUrl); ?>" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/12 text-white shadow-[0_10px_24px_-14px_rgba(255,255,255,0.25)] transition hover:bg-white/18" aria-label="Créer un compte">
            <?php echo dashboard_icon_svg('user-plus', 'class="h-5 w-5"'); ?>
          </a>
        </div>
      </div>
    </div>
  </div>
</footer>
