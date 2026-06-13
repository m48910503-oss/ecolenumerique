<?php
$formatMoney = static fn (int $amount): string => number_format($amount, 0, ',', ' ') . ' F CFA';
$supportsFedapay = in_array('fedapay', $availableMethods, true);
$paymentMethodValue = $selectedMethod !== '' ? $selectedMethod : ($supportsFedapay ? 'fedapay' : '');
$summaryQuantityCount = $enterpriseCheckout ? $enterpriseSeatsTotal : $itemCount;
$summaryQuantityLabel = $enterpriseCheckout ? ($summaryQuantityCount === 1 ? 'place' : 'places') : ($summaryQuantityCount === 1 ? 'formation' : 'formations');
$formationSectionTitle = $itemCount === 1 ? 'Votre formation sélectionnée' : 'Vos formations sélectionnées';
$showWalletBlock = !$enterpriseCheckout && $referralWalletAvailable > 0 && $total > 0;
$paymentUnavailable = !empty($paymentUnavailable);
$checkoutProgressStep = (int) ($checkoutProgressStep ?? 0);
if ($checkoutProgressStep < 1 || $checkoutProgressStep > 3) {
    $checkoutProgressStep = $itemCount === 0 ? 1 : 2;
}
$checkoutStepClass = static function (int $step) use ($checkoutProgressStep): string {
    if ($checkoutProgressStep === $step) {
        return 'inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-2 text-primary-700 dark:bg-primary-900/25 dark:text-primary-300';
    }

    if ($checkoutProgressStep > $step) {
        return 'inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-emerald-700 dark:bg-emerald-900/25 dark:text-emerald-300';
    }

    return 'inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 text-gray-600 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700';
};
$checkoutStepBadgeClass = static function (int $step) use ($checkoutProgressStep): string {
    if ($checkoutProgressStep === $step) {
        return 'bg-primary-600 text-white';
    }

    if ($checkoutProgressStep > $step) {
        return 'bg-emerald-600 text-white';
    }

    return 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
};
$removeBaseUrl = trim((string) ($removeBaseUrl ?? site_url('checkout')));
$removeCourseUrl = static function (int $courseId) use ($removeBaseUrl): string {
    return $removeBaseUrl . (str_contains($removeBaseUrl, '?') ? '&' : '?') . 'remove=' . $courseId;
};
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-view="web/checkout">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
  <meta name="color-scheme" content="light dark">
  <title>Panier & Paiement · <?php echo e($platformName); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($webMarketingJsUrl); ?>" defer></script>
  <style>
    .hover-lift {
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .hover-lift:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08);
    }

    .gradient-text {
      background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .dark .gradient-text {
      background: linear-gradient(135deg, #60a5fa 0%, #22d3ee 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .status-alert,
    .checkout-status-card {
      border-radius: 1rem;
      border: 1px solid #e5e7eb;
      background: #ffffff;
      padding: 1rem 1.1rem;
      box-shadow: 0 8px 24px -18px rgba(15, 23, 42, 0.2);
    }

    .status-alert p,
    .checkout-status-card p {
      margin: 0;
    }

    .status-alert p + p,
    .checkout-status-card p + p {
      margin-top: 0.35rem;
    }

    .status-alert--danger,
    .checkout-status-card--danger {
      border-color: rgba(248, 113, 113, 0.34);
      background: #fff1f2;
      color: #9f1239;
    }

    .status-alert--warning,
    .checkout-status-card--warning {
      border-color: rgba(251, 191, 36, 0.34);
      background: #fffbeb;
      color: #92400e;
    }

    .status-alert--success,
    .checkout-status-card--success {
      border-color: rgba(74, 222, 128, 0.34);
      background: #f0fdf4;
      color: #166534;
    }

    .dark .status-alert,
    .dark .checkout-status-card {
      border-color: #374151;
      background: #111827;
      color: #e5e7eb;
    }

    .dark .status-alert--danger,
    .dark .checkout-status-card--danger {
      border-color: rgba(248, 113, 113, 0.3);
      background: rgba(127, 29, 29, 0.24);
      color: #fecaca;
    }

    .dark .status-alert--warning,
    .dark .checkout-status-card--warning {
      border-color: rgba(251, 191, 36, 0.3);
      background: rgba(120, 53, 15, 0.24);
      color: #fde68a;
    }

    .dark .status-alert--success,
    .dark .checkout-status-card--success {
      border-color: rgba(74, 222, 128, 0.3);
      background: rgba(20, 83, 45, 0.24);
      color: #bbf7d0;
    }

    .checkout-input::-webkit-outer-spin-button,
    .checkout-input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    .checkout-input[type="number"] {
      -moz-appearance: textfield;
    }

    @media (min-width: 981px) {
      .public-mobile-toggle,
      .public-mobile-panel {
        display: none !important;
      }
    }

    @media (max-width: 980px) {
      .public-desktop-nav,
      .public-desktop-actions {
        display: none !important;
      }
    }
  </style>
</head>
<body class="overflow-x-hidden bg-gray-50 font-sans antialiased text-slate-900 transition-colors duration-200 dark:bg-gray-950 dark:text-slate-100" data-view="web/checkout">

<?php require __DIR__ . '/_public_header_catalogue.php'; ?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
  <?php if ($hasCompanyContext && $companyName !== ''): ?>
    <div class="mb-4 status-alert">
      <p>Compte entreprise détecté : <strong><?php echo e($companyName); ?></strong>. Activez le mode entreprise si vous devez réserver plusieurs places.</p>
    </div>
  <?php endif; ?>

  <?php if ($errors !== []): ?>
    <div class="mb-4 space-y-3">
      <?php foreach ($errors as $error): ?>
        <div class="status-alert status-alert--danger">
          <p><?php echo e((string) $error); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($warnings !== []): ?>
    <div class="mb-4 space-y-3">
      <?php foreach ($warnings as $warning): ?>
        <div class="status-alert status-alert--warning">
          <p><?php echo e((string) $warning); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($success !== ''): ?>
    <div class="mb-6 status-alert status-alert--success">
      <p><?php echo e($success); ?></p>
      <div class="mt-4 flex flex-wrap gap-3">
        <a href="<?php echo e($myCoursesUrl); ?>" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md transition">Aller à mes cours</a>
        <a href="<?php echo e($ordersUrl); ?>" class="border border-gray-300 dark:border-gray-600 text-sm font-medium px-5 py-2.5 rounded-xl hover:border-primary-500 hover:text-primary-600 transition">Voir mes commandes</a>
      </div>
    </div>
  <?php endif; ?>

  <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
    <div>
      <p class="text-sm font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-400">Inscription</p>
      <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Finaliser votre inscription</h1>
      <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-300">Vérifiez votre formation et choisissez votre moyen de paiement.</p>
    </div>
    <ol class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
      <li class="<?php echo e($checkoutStepClass(1)); ?>"<?php echo $checkoutProgressStep === 1 ? ' aria-current="step"' : ''; ?>>
        <span class="flex h-7 w-7 items-center justify-center rounded-full <?php echo e($checkoutStepBadgeClass(1)); ?>">1</span>
        Formation sélectionnée
      </li>
      <li class="text-gray-300 dark:text-gray-600"><i class="fas fa-chevron-right" aria-hidden="true"></i></li>
      <li class="<?php echo e($checkoutStepClass(2)); ?>"<?php echo $checkoutProgressStep === 2 ? ' aria-current="step"' : ''; ?>>
        <span class="flex h-7 w-7 items-center justify-center rounded-full <?php echo e($checkoutStepBadgeClass(2)); ?>">2</span>
        Paiement
      </li>
      <li class="text-gray-300 dark:text-gray-600"><i class="fas fa-chevron-right" aria-hidden="true"></i></li>
      <li class="<?php echo e($checkoutStepClass(3)); ?>"<?php echo $checkoutProgressStep === 3 ? ' aria-current="step"' : ''; ?>>
        <span class="flex h-7 w-7 items-center justify-center rounded-full <?php echo e($checkoutStepBadgeClass(3)); ?>">3</span>
        Accès au cours
      </li>
    </ol>
  </div>

  <?php if ($courses !== []): ?>
    <form id="checkout-form" method="post">
      <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
      <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
          <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6 gap-3">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-book-open text-primary-500"></i> <?php echo e($formationSectionTitle); ?>
              </h2>
              <a href="<?php echo e($returnToCatalogUrl); ?>" class="text-sm text-gray-500 hover:text-primary-600 transition flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Retour au catalogue
              </a>
            </div>

            <div class="space-y-4">
              <?php foreach ($courses as $course): ?>
                <?php
                $priceFinal = (int) ($course['price_final'] ?? $course['price_xof'] ?? 0);
                $priceBase = (int) ($course['price_base'] ?? $course['price_xof'] ?? 0);
                $quantity = max(1, (int) ($course['quantity'] ?? 1));
                $lineTotal = $priceFinal * $quantity;
                $promoActive = !empty($course['promo_active']) && $priceBase > $priceFinal;
                $couponCode = trim((string) ($course['coupon_code'] ?? ''));
                $courseLevel = trim((string) ($course['teacherLabel'] ?? 'Formation en ligne'));
                $courseMetaLabel = trim((string) ($course['checkoutMetaLabel'] ?? $courseLevel));
                $courseAccessLabel = trim((string) ($course['checkoutAccessLabel'] ?? 'Accès après paiement'));
                $courseId = (int) ($course['id'] ?? 0);
                $courseCoverAlt = trim((string) ($course['coverAlt'] ?? ($course['cover_alt'] ?? ($course['title'] ?? 'Cours'))));
                ?>
                <article class="flex flex-col sm:flex-row items-start gap-4 p-4 border border-gray-100 dark:border-gray-700 rounded-xl hover:border-gray-200 dark:hover:border-gray-600 transition hover-lift">
                  <img src="<?php echo e((string) ($course['cover'] ?? '')); ?>" alt="<?php echo e($courseCoverAlt); ?>" loading="lazy" decoding="async" class="w-full sm:w-28 h-20 flex-none object-cover rounded-lg">
                  <div class="flex-1 w-full min-w-0">
                    <div class="flex justify-between items-start gap-3">
                      <div class="min-w-0">
                        <h3 class="font-bold text-gray-900 dark:text-white"><?php echo e((string) ($course['title'] ?? '')); ?></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo e($courseMetaLabel); ?></p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($courseAccessLabel); ?></p>
                      </div>
                      <a href="<?php echo e($removeCourseUrl($courseId)); ?>" class="text-gray-400 hover:text-red-500 transition shrink-0" title="Supprimer" aria-label="Supprimer le cours du panier">
                        <i class="fas fa-times"></i>
                      </a>
                    </div>

                    <div class="flex items-center justify-between mt-3 gap-3">
                      <div class="flex flex-wrap items-center gap-2">
                        <span class="text-lg font-bold text-gray-900 dark:text-white"><?php echo e($formatMoney($lineTotal)); ?></span>
                        <?php if ($promoActive): ?>
                          <span class="text-sm text-gray-400 line-through"><?php echo e($formatMoney($priceBase * $quantity)); ?></span>
                        <?php endif; ?>
                      </div>
                      <?php if ($priceFinal <= 0): ?>
                        <span class="text-xs text-green-600 bg-green-50 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded-full">Gratuit</span>
                      <?php elseif ($couponCode !== ''): ?>
                        <span class="text-xs text-violet-600 bg-violet-50 dark:bg-violet-900/30 dark:text-violet-300 px-2 py-1 rounded-full">Coupon <?php echo e($couponCode); ?></span>
                      <?php elseif ($promoActive): ?>
                        <span class="text-xs text-green-600 bg-green-50 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded-full">En promotion</span>
                      <?php elseif (!empty($course['price_enterprise_applied'])): ?>
                        <span class="text-xs text-sky-600 bg-sky-50 dark:bg-sky-900/30 dark:text-sky-300 px-2 py-1 rounded-full">Tarif entreprise</span>
                      <?php endif; ?>
                    </div>

                    <?php if ($enterpriseCheckout): ?>
                      <div class="mt-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Places à réserver</label>
                        <input
                          type="number"
                          min="1"
                          max="5000"
                          name="seat_qty[<?php echo e((string) $courseId); ?>]"
                          value="<?php echo e((string) $quantity); ?>"
                          class="checkout-input w-full sm:w-36 px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                        >
                      </div>
                    <?php elseif ($quantity > 1): ?>
                      <p class="mt-3 text-xs text-gray-500"><?php echo e($formatMoney($priceFinal)); ?> par place × <?php echo e((string) $quantity); ?></p>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <details class="mt-6 rounded-xl bg-gray-50 dark:bg-gray-700/30 p-4" <?php echo ($couponCodeInput !== '' || $activeCouponCode !== '') ? 'open' : ''; ?>>
              <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-medium text-gray-600 dark:text-gray-300 [&::-webkit-details-marker]:hidden">
                <span class="inline-flex items-center gap-2">
                  <i class="fas fa-tag text-gray-400" aria-hidden="true"></i>
                  Vous avez un code promotionnel ?
                </span>
                <span class="text-primary-600 dark:text-primary-400"><?php echo $activeCouponCode !== '' ? 'Code appliqué' : 'Ajouter un code'; ?></span>
              </summary>
              <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                <div class="flex-1 relative">
                  <i class="fas fa-tag absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                  <input
                    type="text"
                    name="coupon_code"
                    value="<?php echo e($couponCodeInput); ?>"
                    maxlength="32"
                    placeholder="Code promo"
                    class="checkout-input w-full pl-9 pr-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                  >
                </div>
                <button type="submit" name="checkout_action" value="apply_coupon" formnovalidate class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm transition">Appliquer</button>
                <?php if ($activeCouponCode !== ''): ?>
                  <button type="submit" name="checkout_action" value="clear_coupon" formnovalidate class="border border-gray-300 dark:border-gray-600 text-sm font-medium px-5 py-2.5 rounded-lg hover:border-primary-500 hover:text-primary-600 transition">Retirer</button>
                <?php endif; ?>
              </div>
            </details>
          </section>

          <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between gap-4 mb-6">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-credit-card text-primary-500"></i> Moyen de paiement
              </h2>
              <button type="submit" name="checkout_action" value="refresh" formnovalidate class="hidden" aria-hidden="true" tabindex="-1">Recalculer</button>
            </div>

            <div data-checkout-feedback hidden class="mb-5 space-y-3"></div>

            <div class="space-y-5">
              <div>
                <p class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Choisissez votre moyen de paiement</p>

                <?php if ($isFreeCheckout): ?>
                  <div class="status-alert status-alert--success">
                    <p>Total à 0 F CFA. Confirmez directement l’inscription.</p>
                  </div>
                <?php elseif (!$supportsFedapay && !$walletCanCover): ?>
                  <div class="status-alert status-alert--danger">
                    <p class="font-semibold">Paiement indisponible actuellement.</p>
                    <p>Paiement en cours d’activation. Contactez le support.</p>
                    <a href="<?php echo e($supportContactUrl); ?>"<?php echo $supportContactTarget; ?> class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-50 dark:border-rose-500/30 dark:bg-rose-950/20 dark:text-rose-200 dark:hover:bg-rose-900/30">
                      <i class="fab fa-whatsapp" aria-hidden="true"></i> Contacter le support WhatsApp
                    </a>
                  </div>
                <?php elseif (!$supportsFedapay && $walletCanCover): ?>
                  <div class="status-alert status-alert--warning">
                    <p class="font-semibold">Paiement en ligne indisponible actuellement.</p>
                    <p>Vous pouvez payer avec votre solde de parrainage.</p>
                  </div>
                <?php else: ?>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:has-[:checked]:bg-primary-900/20 transition hover-lift">
                      <input type="radio" name="payment_method" value="fedapay" class="mt-1 text-primary-600" <?php echo $paymentMethodValue === 'fedapay' ? 'checked' : ''; ?>>
                      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                        <i class="fas fa-mobile-alt text-lg" aria-hidden="true"></i>
                      </span>
                      <span class="min-w-0">
                        <span class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                          Mobile Money
                          <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Recommandé</span>
                        </span>
                        <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">Payez depuis votre téléphone via FedaPay.</span>
                      </span>
                    </label>
                    <div class="flex items-start gap-3 p-4 border border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-700/20 text-gray-500 dark:text-gray-400" aria-disabled="true">
                      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                        <i class="fas fa-credit-card text-lg" aria-hidden="true"></i>
                      </span>
                      <span class="min-w-0">
                        <span class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                          Carte bancaire
                          <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">Indisponible</span>
                        </span>
                        <span class="mt-1 block text-xs leading-5">Visa / Mastercard selon disponibilité.</span>
                      </span>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <?php if ($hasCompanyContext): ?>
                <input type="hidden" name="enterprise_mode" value="0">
                <label class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-700/30 border border-gray-200 dark:border-gray-700 rounded-xl">
                  <input type="checkbox" name="enterprise_mode" value="1" <?php echo $enterpriseCheckout ? 'checked' : ''; ?> class="mt-1 w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                  <span class="text-sm text-gray-600 dark:text-gray-300">
                    Utiliser le compte entreprise<?php echo $companyName !== '' ? ' : ' . e($companyName) : ''; ?>.
                  </span>
                </label>
              <?php endif; ?>

              <?php if ($showWalletBlock): ?>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/90 dark:bg-gray-700/20 p-4">
                  <div class="flex items-start gap-3">
                    <input type="checkbox" name="use_referral_wallet" value="1" <?php echo $referralWalletUse ? 'checked' : ''; ?> class="mt-1 w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">Solde de parrainage</p>
                      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Disponible : <?php echo e($formatMoney($referralWalletAvailable)); ?>.</p>
                      <div class="mt-3">
                        <input type="number" name="referral_wallet_amount" min="0" max="<?php echo e((string) $walletMax); ?>" value="<?php echo e((string) $walletInputValue); ?>" class="checkout-input w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                      </div>
                      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Utilisez vos gains pour réduire le total de cette commande.</p>
                      <?php if ($referralWalletUse): ?>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Reste à payer après déduction : <?php echo e($formatMoney($walletRemainingPreview)); ?>.</p>
                      <?php endif; ?>
                      <?php if ($referralWalletUse && $walletRemainingPreview > 0 && $walletRemainingPreview < 100): ?>
                        <div class="mt-3 status-alert status-alert--warning">
                          <p>Le reste à payer doit être supérieur ou égal à 100 F CFA pour FedaPay.</p>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

              <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/90 dark:bg-gray-700/20 p-4">
                <label class="flex items-start gap-2">
                  <input type="checkbox" id="terms" name="terms" value="1" required class="mt-1 w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                  <span class="text-sm text-gray-600 dark:text-gray-400">
                    J'accepte les <a href="<?php echo e($cguUrl); ?>" class="font-medium text-primary-600 hover:underline dark:text-primary-300">conditions d'achat</a>, les règles d'accès à la formation et la <a href="<?php echo e($cguUrl); ?>" class="font-medium text-primary-600 hover:underline dark:text-primary-300">politique de remboursement</a>.
                  </span>
                </label>
              </div>
            </div>
          </section>
        </div>

        <aside class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 p-6 sticky top-24">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Résumé de votre inscription</h3>

            <div class="space-y-3 text-sm">
              <div class="flex justify-between gap-3">
                <span class="text-gray-600 dark:text-gray-400">Sous-total (<?php echo e((string) $summaryQuantityCount); ?> <?php echo e($summaryQuantityLabel); ?>)</span>
                <span class="font-medium"><?php echo e($formatMoney($subtotalBaseTotal)); ?></span>
              </div>
              <div class="flex justify-between gap-3 <?php echo $summaryDiscountTotal > 0 ? 'text-green-600' : 'text-gray-500'; ?>">
                <span>Réduction</span>
                <span><?php echo $summaryDiscountTotal > 0 ? '- ' . e($formatMoney($summaryDiscountTotal)) : '0 F CFA'; ?></span>
              </div>
              <div class="flex justify-between font-bold text-base border-t border-gray-100 dark:border-gray-700 pt-3 gap-3">
                <span>Total</span>
                <span class="text-gray-900 dark:text-white"><?php echo e($formatMoney($previewPayableTotal)); ?></span>
              </div>
            </div>

            <?php if ($fedapayCheckout === null && $paymentUnavailable): ?>
              <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                <p class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white">
                  <i class="fas fa-lock text-gray-500" aria-hidden="true"></i>
                  Paiement indisponible pour le moment
                </p>
                <p class="mt-2 leading-6 text-gray-600 dark:text-gray-300">Paiement en cours d’activation. Contactez le support.</p>
                <a href="<?php echo e($supportContactUrl); ?>"<?php echo $supportContactTarget; ?> class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-800 transition hover:border-primary-300 hover:text-primary-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-primary-500 dark:hover:text-primary-300">
                  <i class="fab fa-whatsapp" aria-hidden="true"></i>
                  Contacter le support WhatsApp
                </a>
              </div>
            <?php elseif ($fedapayCheckout === null): ?>
              <button
                class="w-full mt-6 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3.5 rounded-xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
                type="submit"
                name="checkout_action"
                value="pay"
              >
                <i class="fas fa-lock"></i>
                <span data-checkout-pay-label><?php echo e($payButtonLabel); ?></span>
              </button>
              <?php if (!$isFreeCheckout && $previewPayableTotal > 0): ?>
                <p class="mt-3 text-center text-xs leading-5 text-gray-500 dark:text-gray-400">Accès au cours après confirmation du paiement.</p>
              <?php endif; ?>
            <?php else: ?>
              <?php
              $fedapayCustomer = is_array($fedapayCheckout['customer'] ?? null) ? $fedapayCheckout['customer'] : [];
              $fedapayPhoneData = is_array($fedapayCustomer['phone_number'] ?? null) ? $fedapayCustomer['phone_number'] : [];
              $fedapayFirst = (string) ($fedapayCustomer['firstname'] ?? '');
              $fedapayLast = (string) ($fedapayCustomer['lastname'] ?? '');
              $fedapayEmail = (string) ($fedapayCustomer['email'] ?? '');
              $fedapayPhone = (string) ($fedapayPhoneData['number'] ?? '');
              $fedapayPhoneCountry = (string) ($fedapayPhoneData['country'] ?? '');
              ?>
              <button
                id="fedapay-button"
                class="w-full mt-6 bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3.5 rounded-xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
                type="button"
                data-auto-open="1"
                data-public-key="<?php echo e($fedapayPublicKey); ?>"
                data-transaction-id="<?php echo (int) ($fedapayCheckout['transaction_id'] ?? 0); ?>"
                data-transaction-amount="<?php echo (int) ($fedapayCheckout['amount'] ?? 0); ?>"
                data-transaction-description="<?php echo e('Commande ' . (string) ($fedapayCheckout['order_ref'] ?? '')); ?>"
                data-currency-iso="XOF"
                data-customer-firstname="<?php echo e($fedapayFirst); ?>"
                data-customer-lastname="<?php echo e($fedapayLast); ?>"
                data-customer-email="<?php echo e($fedapayEmail); ?>"
                data-customer-phone-number-number="<?php echo e($fedapayPhone); ?>"
                data-customer-phone-number-country="<?php echo e($fedapayPhoneCountry); ?>"
                data-return-url="<?php echo e(site_url('payment_return')); ?>"
              >
                <i class="fas fa-lock"></i> Payer avec FedaPay
              </button>
            <?php endif; ?>

            <?php if ($supportsFedapay || $fedapayCheckout !== null): ?>
              <div class="mt-4 text-center text-xs text-gray-600 dark:text-gray-300 space-y-1">
                <p><i class="fas fa-shield-alt mr-1"></i> Transaction protégée et paiement traité via notre partenaire sécurisé.</p>
                <p class="flex items-center justify-center gap-1">
                  <i class="fas fa-mobile-alt text-xl text-primary-600"></i>
                  <i class="fas fa-wallet text-xl text-cyan-600"></i>
                  <i class="fas fa-lock text-gray-400 text-lg"></i>
                </p>
              </div>
            <?php endif; ?>

            <p class="mt-4 text-center text-xs leading-5 text-gray-500 dark:text-gray-400">
              Besoin d'aide ? <a href="<?php echo e($supportContactUrl); ?>"<?php echo $supportContactTarget; ?> class="font-semibold text-primary-600 hover:underline dark:text-primary-300">Contactez le support</a>.
            </p>
          </div>
        </aside>
      </div>
    </form>
  <?php else: ?>
    <div class="grid lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-8">
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-6 gap-3">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <i class="fas fa-book-open text-primary-500"></i> Votre formation sélectionnée
            </h2>
            <a href="<?php echo e($returnToCatalogUrl); ?>" class="text-sm text-gray-500 hover:text-primary-600 transition flex items-center gap-1">
              <i class="fas fa-arrow-left"></i> Retour au catalogue
            </a>
          </div>

          <div class="border border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-primary-50 dark:bg-primary-900/20 rounded-2xl flex items-center justify-center text-primary-600 text-2xl">
              <i class="fas fa-shopping-bag"></i>
            </div>
        <h3 class="mt-5 text-xl font-bold text-gray-900 dark:text-white">Aucune formation sélectionnée</h3>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Ajoutez une formation depuis le catalogue pour finaliser votre inscription.</p>
            <a href="<?php echo e($returnToCatalogUrl); ?>" class="inline-flex mt-6 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md transition">Explorer le catalogue</a>
          </div>
        </section>
      </div>

      <aside class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 p-6 sticky top-24">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Résumé de votre inscription</h3>

          <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-3">
              <span class="text-gray-600 dark:text-gray-400">Sous-total</span>
              <span class="font-medium">0 F CFA</span>
            </div>
            <div class="flex justify-between gap-3 text-gray-500">
              <span>Réduction</span>
              <span>0 F CFA</span>
            </div>
            <div class="flex justify-between font-bold text-base border-t border-gray-100 dark:border-gray-700 pt-3 gap-3">
              <span>Total</span>
              <span class="text-gray-900 dark:text-white">0 F CFA</span>
            </div>
          </div>

          <button class="w-full mt-6 bg-gray-300 dark:bg-gray-700 text-white font-semibold py-3.5 rounded-xl shadow-md cursor-not-allowed flex items-center justify-center gap-2" type="button" disabled>
            <i class="fas fa-lock"></i> Panier vide
          </button>

          <p class="mt-4 text-center text-xs leading-5 text-gray-500 dark:text-gray-400">Sélectionnez une formation pour continuer.</p>
        </div>
      </aside>
    </div>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/_public_footer_home.php'; ?>
<div class="fixed bottom-5 right-5 z-40 hidden sm:block">
  <a
    href="<?php echo e($supportUrl); ?>"
    class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-[0_18px_35px_-15px_rgba(37,99,235,0.9)] transition hover:bg-blue-700"
    aria-label="Assistance"
    title="Assistance"
  >
    <i class="fas fa-headset" aria-hidden="true"></i>
  </a>
</div>
<script src="<?php echo e(site_url('assets/js/checkout_fedapay.js')); ?>" defer></script>
<script src="<?php echo e(site_url('assets/js/checkout_async.js')); ?>" defer></script>
</body>
</html>
