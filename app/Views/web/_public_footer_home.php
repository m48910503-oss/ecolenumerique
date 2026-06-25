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
$supportPhoneDigits = preg_replace('/\D+/', '', $supportPhone) ?: '';
$supportWhatsappDigits = preg_replace('/\D+/', '', $supportWhatsapp) ?: '';

$platformLogoUrl = trim((string) ($platformLogoUrl ?? ($siteLogoUrl ?? '')));
$platformLogoAlt = trim((string) ($platformLogoAlt ?? ('Logo ' . $platformName)));
$homeUrl = trim((string) ($homeUrl ?? site_url('')));
$catalogueUrl = trim((string) ($catalogueUrl ?? site_url('catalogue')));
$teacherApplyUrl = trim((string) ($teacherApplyUrl ?? site_url('become-teacher')));
$supportUrl = trim((string) ($supportUrl ?? site_url('support')));
$privacyUrl = trim((string) ($privacyUrl ?? site_url('privacy')));
$cguUrl = trim((string) ($cguUrl ?? site_url('cgu')));
$registerUrl = trim((string) ($registerUrl ?? site_url('register')));

$footerPlatformLinks = [
    ['label' => 'Formations', 'href' => $catalogueUrl],
    ['label' => 'Catégories', 'href' => $homeUrl . '#categories'],
    ['label' => 'Pourquoi nous choisir', 'href' => $homeUrl . '#why-choose'],
    ['label' => 'FAQ', 'href' => $homeUrl . '#faq'],
];
$footerSupportLinks = [
    ['label' => 'Centre d’aide', 'href' => $supportUrl],
    ['label' => 'Contact WhatsApp', 'href' => $supportUrl],
    ['label' => 'Politique de confidentialité', 'href' => $privacyUrl],
    ['label' => "Conditions d'utilisation", 'href' => $cguUrl],
    ['label' => 'Moyens de paiement', 'href' => site_url('checkout')],
];
?>
<footer class="border-t border-white/10 bg-gray-900 py-16 text-slate-300">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr_0.8fr_1fr]">
      <div>
        <a href="<?php echo e($homeUrl); ?>" class="flex items-center gap-3 text-xl font-bold text-white sm:text-2xl">
          <?php if ($platformLogoUrl !== ''): ?>
            <img src="<?php echo e($platformLogoUrl); ?>" alt="<?php echo e($platformLogoAlt); ?>" class="h-10 w-10 rounded-xl object-cover shadow-sm">
          <?php else: ?>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500">
              <i class="fas fa-graduation-cap" aria-hidden="true"></i>
            </span>
          <?php endif; ?>
          <span class="min-w-0">
            <span class="block"><?php echo e($platformName); ?></span>
            <?php if ($siteTagline !== ''): ?>
              <span class="mt-1 block text-xs font-medium text-slate-400"><?php echo e($siteTagline); ?></span>
            <?php endif; ?>
          </span>
        </a>
        <p class="mt-4 max-w-md text-sm leading-7 text-slate-400">
          <?php echo e($siteDescription !== '' ? $siteDescription : 'Formation claire, mobile et adaptée à l’Afrique francophone.'); ?>
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
          <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm text-slate-200"><i class="fas fa-wallet text-blue-300" aria-hidden="true"></i> Mobile Money</span>
          <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm text-slate-200"><i class="fas fa-comments text-emerald-300" aria-hidden="true"></i> WhatsApp</span>
          <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm text-slate-200"><i class="fas fa-certificate text-orange-300" aria-hidden="true"></i> Certificat</span>
        </div>
        <div class="mt-6 flex items-center gap-3">
          <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] text-slate-200 transition hover:bg-white/[0.08]" aria-label="Catalogue"><i class="fas fa-book-open" aria-hidden="true"></i></a>
          <a href="<?php echo e($supportUrl); ?>" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] text-slate-200 transition hover:bg-white/[0.08]" aria-label="Support"><i class="fas fa-life-ring" aria-hidden="true"></i></a>
          <a href="<?php echo e($teacherApplyUrl); ?>" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] text-slate-200 transition hover:bg-white/[0.08]" aria-label="Devenir enseignant"><i class="fas fa-chalkboard-user" aria-hidden="true"></i></a>
        </div>
      </div>

      <div>
        <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Plateforme</h3>
        <ul class="mt-5 space-y-3 text-sm">
          <?php foreach ($footerPlatformLinks as $footerPlatformLink): ?>
            <li><a href="<?php echo e((string) ($footerPlatformLink['href'] ?? '#')); ?>" class="transition hover:text-white"><?php echo e((string) ($footerPlatformLink['label'] ?? 'Lien')); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Aide</h3>
        <ul class="mt-5 space-y-3 text-sm">
          <?php foreach ($footerSupportLinks as $footerSupportLink): ?>
            <li><a href="<?php echo e((string) ($footerSupportLink['href'] ?? '#')); ?>" class="transition hover:text-white"><?php echo e((string) ($footerSupportLink['label'] ?? 'Lien')); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div>
        <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Contact</h3>
        <ul class="mt-5 space-y-3 text-sm">
          <?php if ($supportEmail !== ''): ?>
            <li>
              <a href="<?php echo e('mailto:' . $supportEmail); ?>" class="inline-flex items-center gap-2 transition hover:text-white">
                <i class="fas fa-envelope text-cyan-300" aria-hidden="true"></i>
                <span><?php echo e($supportEmail); ?></span>
              </a>
            </li>
          <?php endif; ?>
          <?php if ($supportPhone !== ''): ?>
            <li>
              <a href="<?php echo e($supportPhoneDigits !== '' ? 'tel:' . $supportPhoneDigits : '#'); ?>" class="inline-flex items-center gap-2 transition hover:text-white">
                <i class="fas fa-phone text-emerald-300" aria-hidden="true"></i>
                <span><?php echo e($supportPhone); ?></span>
              </a>
            </li>
          <?php endif; ?>
          <?php if ($supportWhatsapp !== ''): ?>
            <li>
              <a href="<?php echo e($supportWhatsappDigits !== '' ? 'https://wa.me/' . $supportWhatsappDigits : $supportUrl); ?>" class="inline-flex items-center gap-2 transition hover:text-white" target="<?php echo e($supportWhatsappDigits !== '' ? '_blank' : '_self'); ?>" rel="<?php echo e($supportWhatsappDigits !== '' ? 'noopener noreferrer' : ''); ?>">
                <i class="fab fa-whatsapp text-green-300" aria-hidden="true"></i>
                <span><?php echo e($supportWhatsapp); ?></span>
              </a>
            </li>
          <?php endif; ?>
          <?php if ($siteAddress !== ''): ?>
            <li class="inline-flex items-start gap-2 text-slate-400">
              <i class="fas fa-location-dot mt-0.5 text-orange-300" aria-hidden="true"></i>
              <span><?php echo e($siteAddress); ?></span>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="mt-12 flex flex-col gap-4 border-t border-white/10 pt-6 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
      <p><?php echo e($footerText !== '' ? $footerText : ('© ' . date('Y') . ' ' . $platformName . '. Tous droits réservés.')); ?></p>
      <div class="flex flex-wrap gap-4">
        <a href="<?php echo e($privacyUrl); ?>" class="transition hover:text-slate-300">Politique de confidentialité</a>
        <a href="<?php echo e($cguUrl); ?>" class="transition hover:text-slate-300">Conditions d'utilisation</a>
        <a href="<?php echo e(site_url('checkout')); ?>" class="transition hover:text-slate-300">Moyens de paiement</a>
      </div>
    </div>
  </div>
</footer>
