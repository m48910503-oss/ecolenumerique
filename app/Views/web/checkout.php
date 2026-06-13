<?php
$errors = is_array($errors ?? null) ? $errors : [];
$warnings = is_array($warnings ?? null) ? $warnings : [];
$success = (string) ($success ?? '');
$courses = is_array($courses ?? null) ? $courses : [];
$selectedMethod = (string) ($selectedMethod ?? '');
$availableMethods = is_array($availableMethods ?? null) ? $availableMethods : [];
$isFreeCheckout = !empty($isFreeCheckout);
$fedapayCheckout = is_array($fedapayCheckout ?? null) ? $fedapayCheckout : null;
$fedapayPublicKey = (string) ($fedapayPublicKey ?? '');
$siteLogoUrl = (string) ($siteLogoUrl ?? '');
$returnToCatalogUrl = (string) ($returnToCatalogUrl ?? site_url('catalogue'));
$myCoursesUrl = (string) ($myCoursesUrl ?? site_url('student/my_courses'));
$ordersUrl = (string) ($ordersUrl ?? site_url('student/orders'));
$removeBaseUrl = (string) ($removeBaseUrl ?? site_url('checkout'));
$couponCodeInput = (string) ($couponCodeInput ?? '');
$activeCouponCode = (string) ($activeCouponCode ?? '');
$enterpriseCheckout = !empty($enterpriseCheckout);
$hasCompanyContext = !empty($hasCompanyContext);
$companyContext = is_array($companyContext ?? null) ? $companyContext : null;
$companyName = trim((string) ($companyContext['company_name'] ?? ''));
$referralWalletAvailable = (int) ($referralWalletAvailable ?? 0);
$referralWalletUse = !empty($referralWalletUse);
$referralWalletAmountInput = (int) ($referralWalletAmountInput ?? 0);
$formatMoney = static fn (int $amount): string => number_format($amount, 0, ',', ' ') . ' F CFA';
$platformName = trim((string) platform_setting_get('site_name', defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique'));
if ($platformName === '') {
    $platformName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
}
$homeUrl = site_url('');
$catalogueUrl = site_url('catalogue');
$profileUrl = site_url('profile');
$teacherApplyUrl = site_url('teacher/apply');
$supportUrl = site_url('support');
$supportWhatsappRaw = trim((string) platform_setting_get('support_whatsapp', ''));
$supportWhatsappDigits = (string) preg_replace('/\D+/', '', $supportWhatsappRaw);
$checkoutSupportMessage = 'Bonjour, je souhaite être informé de la disponibilité du paiement pour finaliser mon inscription.';
$supportContactUrl = $supportWhatsappDigits !== ''
    ? 'https://wa.me/' . $supportWhatsappDigits . '?text=' . rawurlencode($checkoutSupportMessage)
    : $supportUrl;
$supportContactTarget = $supportWhatsappDigits !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
$privacyUrl = site_url('privacy');
$cguUrl = site_url('cgu');
$loginUrl = site_url('login');
$registerUrl = site_url('register');
$notificationsUrl = site_url('notifications');
$dashboardUrl = (string) ($dashboardUrl ?? site_url('student/dashboard'));
$isLoggedIn = !empty($isLoggedIn);
$publicPage = 'checkout';
$platformLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$platformLogoAlt = 'Logo ' . $platformName;
$publicNavPanelId = 'checkout-public-nav-panel';
$webMarketingJsUrl = site_url('assets/js/web-marketing.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web-marketing.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$cartCount = count($courses);
$headerNotificationCount = max(0, (int) ($headerNotificationCount ?? 0));
$headerLoginLabel = 'Se connecter';
$headerLoginUrl = $loginUrl;
$headerPrimaryLabel = 'Tableau de bord';
$headerPrimaryUrl = $dashboardUrl;
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

$itemCount = count($courses);
$total = 0;
$subtotalBaseTotal = 0;
$enterpriseSeatsTotal = 0;
$freeCoursesCount = 0;
$discountedCoursesCount = 0;
$enterprisePricingCount = 0;

$makeCheckoutCover = static function (string $title, string $level): string {
    $safeTitle = htmlspecialchars($title !== '' ? $title : 'Cours numerique', ENT_QUOTES, 'UTF-8');
    $safeLevel = htmlspecialchars($level !== '' ? $level : 'Formation en ligne', ENT_QUOTES, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 360">
  <defs>
    <linearGradient id="checkoutCoverGradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#2563eb"/>
      <stop offset="100%" stop-color="#06b6d4"/>
    </linearGradient>
  </defs>
  <rect width="640" height="360" rx="36" fill="url(#checkoutCoverGradient)"/>
  <circle cx="520" cy="84" r="76" fill="rgba(255,255,255,0.10)"/>
  <circle cx="104" cy="300" r="92" fill="rgba(255,255,255,0.12)"/>
  <text x="44" y="76" fill="#dbeafe" font-size="24" font-family="Arial, sans-serif">{$safeLevel}</text>
  <text x="44" y="170" fill="#ffffff" font-size="34" font-weight="700" font-family="Arial, sans-serif">{$safeTitle}</text>
</svg>
SVG;

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
};

foreach ($courses as $course) {
    $priceFinal = (int) ($course['price_final'] ?? $course['price_xof'] ?? 0);
    $priceBase = (int) ($course['price_base'] ?? $course['price_xof'] ?? 0);
    $quantity = max(1, (int) ($course['quantity'] ?? 1));

    $total += $priceFinal * $quantity;
    $subtotalBaseTotal += $priceBase * $quantity;

    if ($enterpriseCheckout) {
        $enterpriseSeatsTotal += $quantity;
    }
    if ($priceFinal <= 0) {
        $freeCoursesCount++;
    }
    if (!empty($course['promo_active']) && $priceBase > $priceFinal) {
        $discountedCoursesCount++;
    }
    if (!empty($course['price_enterprise_applied'])) {
        $enterprisePricingCount++;
    }
}

foreach ($courses as $index => $course) {
    $title = trim((string) ($course['title'] ?? 'Cours numerique'));
    $level = trim((string) ($course['level'] ?? ''));
    $levelKey = function_exists('mb_strtolower') ? mb_strtolower($level, 'UTF-8') : strtolower($level);
    $levelLabel = match ($levelKey) {
        'beginner', 'debutant' => 'Débutant',
        'intermediate', 'intermediaire' => 'Intermédiaire',
        'advanced', 'avance' => 'Avancé',
        default => $level !== '' ? (function_exists('mb_convert_case') ? mb_convert_case($level, MB_CASE_TITLE, 'UTF-8') : ucfirst($level)) : '',
    };
    $courseCover = trim((string) ($course['cover'] ?? ''));
    if ($courseCover === '') {
        $courseCover = trim((string) ($course['cover_path'] ?? ''));
        if ($courseCover !== '' && preg_match('#^(?:https?:)?//#i', $courseCover) !== 1 && !str_starts_with(strtolower($courseCover), 'data:')) {
            $courseCover = site_url(ltrim($courseCover, '/'));
        }
    }
    if ($courseCover === '') {
        $courseCover = $makeCheckoutCover($title, $level !== '' ? $level : 'Formation en ligne');
    }

    $courses[$index]['cover'] = $courseCover;
    $courseAlt = trim((string) ($course['coverAlt'] ?? ($course['cover_alt'] ?? '')));
    if ($courseAlt === '') {
        $courseAlt = 'Couverture du cours ' . $title;
    }
    $courses[$index]['coverAlt'] = $courseAlt;
    $courses[$index]['teacherLabel'] = $levelLabel !== '' ? ('Niveau ' . $levelLabel) : 'Formation en ligne';

    $totalMinutes = max(0, (int) ($course['total_min'] ?? 0));
    if ($totalMinutes <= 0) {
        $durationHours = is_numeric($course['duration_hours'] ?? null) ? (float) $course['duration_hours'] : 0.0;
        $totalMinutes = (int) round(max(0.0, $durationHours) * 60);
    }
    $durationLabel = $totalMinutes > 0 && function_exists('format_duration_minutes_hm')
        ? format_duration_minutes_hm($totalMinutes)
        : '';
    $lessonsCount = max(0, (int) ($course['lessons_count'] ?? 0));
    $examCount = max(0, (int) ($course['exam_count'] ?? 0));
    $courseMeta = [];
    if ($levelLabel !== '') {
        $courseMeta[] = $levelLabel;
    }
    if ($durationLabel !== '') {
        $courseMeta[] = $durationLabel;
    }
    if ($lessonsCount > 0) {
        $courseMeta[] = $lessonsCount . ' ' . ($lessonsCount === 1 ? 'leçon' : 'leçons');
    }
    $courseMeta[] = $examCount > 0 ? 'Certificat de réussite' : 'Ressources incluses';
    $courses[$index]['checkoutMetaLabel'] = implode(' · ', $courseMeta);
    $courses[$index]['checkoutAccessLabel'] = 'Accès sur téléphone et ordinateur après paiement';
}

$walletCanCover = (!$enterpriseCheckout && $referralWalletAvailable > 0);
$walletMax = (!$enterpriseCheckout && $referralWalletAvailable > 0 && $total > 0) ? min($referralWalletAvailable, $total) : 0;
$walletInputValue = $referralWalletAmountInput > 0 ? min($referralWalletAmountInput, $walletMax) : $walletMax;
$walletAppliedPreview = $referralWalletUse ? min($walletInputValue, $walletMax) : 0;
$walletRemainingPreview = max(0, $total - $walletAppliedPreview);
$previewPayableTotal = $referralWalletUse ? $walletRemainingPreview : $total;
$summaryDiscountTotal = max(0, $subtotalBaseTotal - $previewPayableTotal);
$paymentUnavailable = (!$isFreeCheckout && $availableMethods === [] && $previewPayableTotal > 0);
$payButtonLabel = $isFreeCheckout
    ? ($enterpriseCheckout ? 'Valider les places gratuites' : 'Valider mon inscription gratuite')
    : ($previewPayableTotal <= 0
        ? ($enterpriseCheckout ? 'Valider les places' : 'Valider mon inscription')
        : ($enterpriseCheckout
            ? 'Payer ' . $formatMoney($previewPayableTotal) . ' et réserver les places'
            : 'Payer ' . $formatMoney($previewPayableTotal) . ' et accéder ' . ($itemCount === 1 ? 'au cours' : 'aux formations')));

require __DIR__ . '/_checkout_page.php';
