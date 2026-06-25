<?php
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../View/helpers.php';

/** @var array<int,array<string,mixed>> $ssrCourses */
$ssrCourses = is_array($ssrCourses ?? null) ? $ssrCourses : [];
$homeTestimonials = is_array($homeTestimonials ?? null) ? $homeTestimonials : [];
$continueLearningItems = is_array($continueLearningItems ?? null) ? $continueLearningItems : [];
$activeEnrollmentCount = max(0, (int) ($activeEnrollmentCount ?? 0));
$seoCanonicalUrl = trim((string) ($seoCanonicalUrl ?? ''));

$publicPage = 'home';
$appFromName = defined('APP_FROM_NAME') ? (string) constant('APP_FROM_NAME') : 'École Numérique';
$platformName = trim((string) platform_setting_get('site_name', $appFromName));
if ($platformName === '') {
    $platformName = $appFromName;
}

$platformLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$platformLogoAlt = 'Logo ' . $platformName;
$publicNavPanelId = 'home-public-nav-panel';
$marketingJsUrl = site_url('assets/js/web-marketing.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web-marketing.js') ?: time()));
$homePageJsUrl = site_url('assets/js/home_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/home_page.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$csrfToken = view_csrf_token();

/** @var null|callable(array):array $publicDecorateCourse */
$publicDecorateCourse = null;
/** @var null|callable(string):string $publicNormalizeKey */
$publicNormalizeKey = null;
/** @var null|callable(int):string $publicFormatMoney */
$publicFormatMoney = null;

require __DIR__ . '/_public_bootstrap.php';

$displayUser = is_array($user ?? null) ? $user : null;
$isLoggedIn = !empty($isLoggedIn) || $displayUser !== null;
$headerNotificationCount = max(0, (int) ($totalUnread ?? 0));
$cartCount = max(0, (int) ($cartCount ?? 0));

if (!is_callable($publicDecorateCourse)) {
    $publicDecorateCourse = static function (array $course): array {
        return $course;
    };
}

if (!is_callable($publicNormalizeKey)) {
    $publicNormalizeKey = static function (string $value): string {
        $value = trim(strtolower($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim((string) $value, '-');
    };
}

if (!is_callable($publicFormatMoney)) {
    $publicFormatMoney = static function (int $amount): string {
        $amount = max(0, $amount);
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    };
}

$makeCoverPlaceholder = static function (string $title, string $category): string {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeCategory = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480" role="img" aria-labelledby="title desc">
  <title id="title">{$safeTitle}</title>
  <desc id="desc">{$safeCategory}</desc>
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1d4ed8"/>
      <stop offset="100%" stop-color="#06b6d4"/>
    </linearGradient>
    <linearGradient id="soft" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.24"/>
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0.08"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="20" stdDeviation="24" flood-color="#020617" flood-opacity="0.28"/>
    </filter>
  </defs>
  <rect width="800" height="480" fill="url(#g)"/>
  <circle cx="660" cy="92" r="120" fill="#ffffff" fill-opacity="0.10"/>
  <circle cx="120" cy="420" r="145" fill="#ffffff" fill-opacity="0.08"/>
  <path d="M560 72 l78 28 -38 40 -82 -28 z" fill="#ffffff" fill-opacity="0.18"/>
  <path d="M84 320 h190 a22 22 0 0 1 22 22 v58 a22 22 0 0 1 -22 22 h-190 a22 22 0 0 1 -22 -22 v-58 a22 22 0 0 1 22 -22 z" fill="url(#soft)" filter="url(#shadow)"/>
  <rect x="116" y="346" width="64" height="64" rx="18" fill="#ffffff"/>
  <path d="M126 370 h18 v18 h-18 z" fill="#2d63ea"/>
  <path d="M150 370 h18 v18 h-18 z" fill="#38bdf8"/>
  <rect x="200" y="354" width="84" height="12" rx="6" fill="#ffffff" opacity="0.8"/>
  <rect x="200" y="374" width="56" height="12" rx="6" fill="#ffffff" opacity="0.62"/>
  <rect x="200" y="394" width="68" height="12" rx="6" fill="#ffffff" opacity="0.42"/>
  <text x="200" y="334" fill="#ffffff" font-size="22" font-weight="700" font-family="Plus Jakarta Sans, Inter, Arial, sans-serif">{$safeCategory}</text>
</svg>
SVG;

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
};

$makeHeroVisual = static function (): string {
    $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 900" role="img" aria-labelledby="title desc">
  <title id="title">Illustration d'apprentissage</title>
  <desc id="desc">Étudiante avec livres, formes géométriques et tableau de bord éducatif stylisé.</desc>
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#ffffff"/>
      <stop offset="45%" stop-color="#f6faff"/>
      <stop offset="100%" stop-color="#e9f1ff"/>
    </linearGradient>
    <linearGradient id="blueBlock" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#2d63ea"/>
      <stop offset="100%" stop-color="#1f4fd6"/>
    </linearGradient>
    <linearGradient id="lightRibbon" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#d6e2ff"/>
      <stop offset="100%" stop-color="#b9cbf7"/>
    </linearGradient>
    <filter id="softShadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="22" stdDeviation="24" flood-color="#0f172a" flood-opacity="0.18"/>
    </filter>
  </defs>

  <rect width="1200" height="900" rx="44" fill="url(#bg)"/>

  <path d="M118 124 C 138 106, 170 106, 194 116 C 216 126, 238 130, 262 142 C 288 156, 292 184, 286 206 C 280 228, 272 250, 264 268"
        fill="none" stroke="#5d93f2" stroke-width="16" stroke-linecap="round"/>
  <path d="M944 164 h128 l-38 38 h-90 z" fill="#d8e6ff" opacity="0.8"/>
  <path d="M975 660 l-88 -48 36 -392 h170 l-12 372 z" fill="url(#lightRibbon)" opacity="0.95"/>

  <g filter="url(#softShadow)">
    <rect x="726" y="520" width="328" height="232" rx="54" fill="url(#blueBlock)"/>
    <path d="M780 560 h88 l-18 180 h-68 z" fill="#b7cdfa"/>
    <path d="M868 552 l50 22 -16 200 -60 -30 z" fill="#eff5ff" opacity="0.92"/>
  </g>

  <g transform="translate(610 248)" filter="url(#softShadow)">
    <ellipse cx="126" cy="384" rx="136" ry="24" fill="#94a3b8" opacity="0.18"/>
    <ellipse cx="124" cy="70" rx="58" ry="64" fill="#d7a07a"/>
    <path d="M71 70 C 68 24, 92 0, 126 0 C 170 0, 200 28, 205 74 C 205 116, 187 130, 154 136 C 128 140, 106 138, 84 128 C 70 118, 64 96, 71 70 Z"
          fill="#2a1a17"/>
    <path d="M74 74 C 76 37, 100 14, 128 14 C 165 14, 185 34, 192 72 C 186 84, 177 95, 162 98 C 136 103, 101 102, 74 74 Z"
          fill="#3a231d" opacity="0.9"/>
    <rect x="96" y="116" width="58" height="34" rx="14" fill="#c18a66"/>
    <path d="M54 170 C 82 146, 168 144, 202 170 C 220 184, 232 214, 236 248 L 236 360 C 236 376, 224 388, 208 388 L 42 388 C 26 388, 14 376, 14 360 L 14 248 C 18 214, 28 184, 54 170 Z"
          fill="#f4c44e"/>
    <path d="M16 250 C 36 224, 72 204, 124 204 C 170 204, 208 222, 236 246"
          fill="none" stroke="#f5d97b" stroke-width="14" stroke-linecap="round"/>
    <path d="M58 214 C 16 250, -2 292, 2 348" fill="none" stroke="#f4c44e" stroke-width="22" stroke-linecap="round"/>
    <path d="M198 214 C 240 250, 258 292, 254 348" fill="none" stroke="#f4c44e" stroke-width="22" stroke-linecap="round"/>
    <path d="M44 358 C 72 340, 98 334, 124 334 C 154 334, 180 340, 210 358" fill="none" stroke="#6b7280" stroke-width="22" stroke-linecap="round"/>
    <path d="M66 388 L 52 520 C 50 544, 72 560, 92 560 L 132 560 C 152 560, 170 544, 168 520 L 160 388 Z" fill="#9ca3af"/>
    <path d="M126 388 L 120 520 C 118 544, 140 560, 160 560 L 200 560 C 220 560, 238 544, 236 520 L 228 388 Z" fill="#8b95a8"/>
    <path d="M44 388 L 18 476 C 12 496, 20 514, 40 520 L 78 530 L 96 458 Z" fill="#f4c44e"/>
    <path d="M232 388 L 260 474 C 268 494, 260 512, 240 520 L 202 530 L 184 458 Z" fill="#f4c44e"/>
    <g transform="translate(90 182)">
      <circle cx="44" cy="34" r="12" fill="none" stroke="#1f2937" stroke-width="5"/>
      <circle cx="98" cy="34" r="12" fill="none" stroke="#1f2937" stroke-width="5"/>
      <path d="M58 34 H 84" fill="none" stroke="#1f2937" stroke-width="5" stroke-linecap="round"/>
      <path d="M66 48 C 76 56, 84 56, 94 48" fill="none" stroke="#1f2937" stroke-width="5" stroke-linecap="round"/>
    </g>
    <path d="M14 256 C 44 230, 78 218, 116 214" fill="none" stroke="#ef8a23" stroke-width="8" stroke-linecap="round" opacity="0.85"/>
    <path d="M236 256 C 208 230, 174 218, 136 214" fill="none" stroke="#ef8a23" stroke-width="8" stroke-linecap="round" opacity="0.85"/>
    <rect x="96" y="258" width="92" height="126" rx="18" fill="#de3030" transform="rotate(-8 142 321)"/>
    <rect x="82" y="268" width="92" height="126" rx="18" fill="#ef4444" transform="rotate(-8 128 331)" opacity="0.96"/>
    <rect x="76" y="274" width="72" height="8" rx="4" fill="#fde68a" transform="rotate(-8 112 278)"/>
    <rect x="80" y="294" width="72" height="8" rx="4" fill="#93c5fd" transform="rotate(-8 116 298)"/>
    <rect x="84" y="314" width="72" height="8" rx="4" fill="#fdba74" transform="rotate(-8 120 318)"/>
  </g>

  <g opacity="0.95">
    <rect x="146" y="560" width="160" height="100" rx="28" fill="#ffffff" filter="url(#softShadow)"/>
    <rect x="170" y="582" width="56" height="56" rx="14" fill="#2d63ea"/>
    <rect x="232" y="582" width="56" height="56" rx="14" fill="#dbeafe"/>
    <path d="M188 606 h22" stroke="#ffffff" stroke-width="6" stroke-linecap="round"/>
    <path d="M250 606 h22" stroke="#2d63ea" stroke-width="6" stroke-linecap="round"/>
  </g>

  <g opacity="0.88">
    <rect x="196" y="744" width="252" height="36" rx="18" fill="#dbeafe"/>
    <rect x="196" y="744" width="142" height="36" rx="18" fill="#2d63ea"/>
  </g>
</svg>
SVG;

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
};

$deriveLevelLabel = static function (string $levelKey): string {
    return match (strtolower(trim($levelKey))) {
        'beginner' => 'Débutant',
        'intermediate' => 'Intermédiaire',
        'advanced' => 'Avancé',
        default => 'Tous niveaux',
    };
};

$decorateHomeCourse = static function (array $course) use ($publicDecorateCourse, $deriveLevelLabel, $catalogueUrl, $makeCoverPlaceholder): array {
    $decorated = $publicDecorateCourse($course);
    $title = trim((string) ($decorated['title'] ?? $course['title'] ?? 'Cours numérique'));
    if ($title === '') {
        $title = 'Cours numérique';
    }

    $category = trim((string) ($decorated['category'] ?? $course['category'] ?? 'Formation'));
    if ($category === '') {
        $category = 'Formation';
    }

    $currentPrice = max(0, (int) ($decorated['priceRaw'] ?? ($course['priceXof'] ?? 0)));
    $basePrice = max($currentPrice, (int) ($course['basePriceXof'] ?? ($course['priceXof'] ?? $currentPrice)));
    $createdAt = trim((string) ($course['createdAt'] ?? $decorated['createdAt'] ?? ''));
    $createdTs = $createdAt !== '' ? (int) strtotime($createdAt) : 0;
    $levelKey = strtolower(trim((string) ($decorated['levelKey'] ?? $course['level'] ?? '')));
    $cover = trim((string) ($decorated['cover'] ?? $course['cover'] ?? ''));
    if ($cover === '') {
        $cover = $makeCoverPlaceholder($title, $category);
    }

    return [
        'id' => max(0, (int) ($decorated['id'] ?? $course['id'] ?? 0)),
        'title' => $title,
        'description' => trim((string) ($decorated['description'] ?? $course['description'] ?? '')),
        'teacher' => trim((string) ($decorated['teacher'] ?? $course['teacher'] ?? 'Formateur')),
        'category' => $category,
        'categoryKey' => trim((string) ($decorated['categoryKey'] ?? '')),
        'enrolledCount' => max(0, (int) ($decorated['enrolledCount'] ?? $course['enrolledCount'] ?? 0)),
        'cover' => $cover,
        'coverAlt' => trim((string) ($decorated['coverAlt'] ?? $course['coverAlt'] ?? ('Visuel du cours ' . $title))),
        'url' => trim((string) ($decorated['url'] ?? $course['url'] ?? $catalogueUrl)),
        'buyUrl' => trim((string) ($decorated['buyUrl'] ?? $course['buyUrl'] ?? $catalogueUrl)),
        'rating' => max(0.0, (float) ($decorated['rating'] ?? $course['rating'] ?? 0)),
        'ratingCount' => max(0, (int) ($decorated['ratingCount'] ?? $course['ratingCount'] ?? 0)),
        'durationLabel' => trim((string) ($decorated['durationLabel'] ?? $course['durationLabel'] ?? 'À votre rythme')),
        'durationMin' => max(0, (int) ($decorated['durationMin'] ?? $course['durationMin'] ?? 0)),
        'priceRaw' => $currentPrice,
        'basePriceXof' => $basePrice,
        'promoActive' => !empty($course['promoActive']) && $currentPrice < $basePrice,
        'levelKey' => $levelKey,
        'levelLabel' => trim((string) ($decorated['levelLabel'] ?? $deriveLevelLabel($levelKey))),
        'createdTs' => $createdTs,
        'isFavorite' => !empty($course['isFavorite']) || !empty($decorated['isFavorite']),
    ];
};

$courseCards = array_values(array_map($decorateHomeCourse, $ssrCourses));

$categoryIconFor = static function (string $label) use ($publicNormalizeKey): string {
    $key = $publicNormalizeKey($label);

    if ($key === '' || str_contains($key, 'dev') || str_contains($key, 'code') || str_contains($key, 'web') || str_contains($key, 'python')) {
        return 'fa-code';
    }
    if (str_contains($key, 'data') || str_contains($key, 'science') || str_contains($key, 'analyse') || str_contains($key, 'ia')) {
        return 'fa-chart-simple';
    }
    if (str_contains($key, 'excel') || str_contains($key, 'bureau') || str_contains($key, 'office')) {
        return 'fa-table-cells-large';
    }
    if (str_contains($key, 'marketing') || str_contains($key, 'copy') || str_contains($key, 'business') || str_contains($key, 'vente') || str_contains($key, 'affiliation')) {
        return 'fa-bullhorn';
    }

    return 'fa-graduation-cap';
};

$categoryGradientFor = static function (string $label) use ($publicNormalizeKey): string {
    $key = $publicNormalizeKey($label);

    if ($key === '' || str_contains($key, 'dev') || str_contains($key, 'code') || str_contains($key, 'web') || str_contains($key, 'python')) {
        return 'from-blue-600 to-cyan-500';
    }
    if (str_contains($key, 'excel') || str_contains($key, 'bureau') || str_contains($key, 'office')) {
        return 'from-emerald-500 to-green-600';
    }
    if (str_contains($key, 'marketing') || str_contains($key, 'copy') || str_contains($key, 'business') || str_contains($key, 'vente') || str_contains($key, 'affiliation')) {
        return 'from-amber-500 to-orange-500';
    }

    return 'from-indigo-500 to-blue-600';
};

$makeStarIcons = static function (float $rating): string {
    $rating = max(0.0, min(5.0, $rating));
    $fullStars = (int) floor($rating);
    $fraction = $rating - $fullStars;
    $hasHalfStar = $fraction >= 0.25 && $fraction < 0.75;

    if ($fraction >= 0.75) {
        $fullStars++;
        $hasHalfStar = false;
    }

    $fullStars = min(5, $fullStars);
    $icons = '';
    $rendered = 0;

    for ($i = 0; $i < $fullStars; $i++) {
        $icons .= '<i class="fas fa-star"></i>';
        $rendered++;
    }

    if ($hasHalfStar && $rendered < 5) {
        $icons .= '<i class="fas fa-star-half-alt"></i>';
        $rendered++;
    }

    while ($rendered < 5) {
        $icons .= '<i class="far fa-star"></i>';
        $rendered++;
    }

    return $icons;
};

$categoryCounts = [];
$categoryLabels = [];
foreach ($courseCards as $courseCard) {
    $categoryKey = trim((string) ($courseCard['categoryKey'] ?? ''));
    if ($categoryKey === '') {
        $categoryKey = $publicNormalizeKey((string) ($courseCard['category'] ?? ''));
    }
    if ($categoryKey === '') {
        continue;
    }

    $categoryCounts[$categoryKey] = ($categoryCounts[$categoryKey] ?? 0) + 1;
    $categoryLabels[$categoryKey] = trim((string) ($courseCard['category'] ?? 'Formation'));
}
arsort($categoryCounts);

$categoryCarouselItems = [];
foreach ($categoryCounts as $categoryKey => $count) {
    $label = trim((string) ($categoryLabels[$categoryKey] ?? 'Formation'));
    if ($label === '') {
        $label = 'Formation';
    }

    $categoryCarouselItems[] = [
        'key' => $categoryKey,
        'href' => $catalogueUrl,
        'icon' => $categoryIconFor($label),
        'gradient' => $categoryGradientFor($label),
        'name' => $label,
        'count' => view_pluralize((int) $count, 'cours', 'cours'),
    ];

    if (count($categoryCarouselItems) >= 6) {
        break;
    }
}

$isMeaningfulMarketingCopy = static function (string $text): bool {
    $text = trim($text);
    if ($text === '') {
        return false;
    }

    $compact = preg_replace('/\s+/u', '', $text) ?? $text;
    if ($compact === '') {
        return false;
    }

    if (!preg_match('/\s/u', $text)) {
        return false;
    }

    if (preg_match('/^(.)\1{10,}$/u', $compact) === 1) {
        return false;
    }

    return true;
};

$defaultSiteDescription = 'Chez ' . $platformName . ', chacun mérite d apprendre. Des formations accessibles et interactives pour progresser sans limite.';
$siteDescription = $isMeaningfulMarketingCopy($siteDescription ?? '')
    ? trim((string) $siteDescription)
    : $defaultSiteDescription;
$siteTagline = $isMeaningfulMarketingCopy($siteTagline ?? '')
    ? trim((string) $siteTagline)
    : 'Des formations claires, utiles et accessibles.';
$footerText = $isMeaningfulMarketingCopy($footerText ?? '')
    ? trim((string) $footerText)
    : ('© ' . date('Y') . ' ' . $platformName . '. Tous droits réservés.');

$homeDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($displayUser, 'Apprenant')
    : 'Apprenant';
$homeUserInitials = function_exists('dashboard_user_initials')
    ? dashboard_user_initials($displayUser, 'EN')
    : 'EN';
$homeUserAvatarLetter = $homeUserInitials !== ''
    ? (function_exists('mb_substr') ? mb_substr($homeUserInitials, 0, 1, 'UTF-8') : substr($homeUserInitials, 0, 1))
    : 'U';
$homeAvatarUrl = function_exists('dashboard_user_avatar_url')
    ? dashboard_user_avatar_url($displayUser)
    : '';
$homeFirstName = trim((string) ($displayUser['first_name'] ?? ''));
if ($homeFirstName === '') {
    $nameParts = preg_split('/\s+/', $homeDisplayName) ?: [];
    $homeFirstName = trim((string) ($nameParts[0] ?? $homeDisplayName));
}

$homeUserRoles = is_array($displayUser)
    ? (json_decode((string) ($displayUser['roles_json'] ?? '[]'), true) ?: [])
    : [];
$homeMyCoursesUrl = $dashboardUrl;
if ($isLoggedIn && !in_array('teacher', $homeUserRoles, true) && !in_array('admin', $homeUserRoles, true) && !in_array('admin_secondary', $homeUserRoles, true) && !in_array('company_admin', $homeUserRoles, true) && !in_array('company_member', $homeUserRoles, true)) {
    $homeMyCoursesUrl = site_url('student/my_courses');
}

$homeFavoritesUrl = $isLoggedIn ? site_url('student/favorites') : $loginUrl;
$homeProfileUrl = $isLoggedIn ? site_url('profile') : $loginUrl;
$homePrimaryCtaUrl = $isLoggedIn ? $homeMyCoursesUrl : $registerUrl;
$homePrimaryCtaLabel = $isLoggedIn ? 'Mon apprentissage' : 'Créer un compte';
$homeSecondaryCtaUrl = $catalogueUrl;
$homeSecondaryCtaLabel = 'Explorer les cours';
$homePreferenceUrl = $isLoggedIn ? $homeProfileUrl : $registerUrl;
$homeShowcaseCourses = array_slice($courseCards, 0, 5);
$homePopularCourses = $courseCards;
usort($homePopularCourses, static function (array $a, array $b): int {
    $enrolledCompare = ((int) ($b['enrolledCount'] ?? 0)) <=> ((int) ($a['enrolledCount'] ?? 0));
    if ($enrolledCompare !== 0) {
        return $enrolledCompare;
    }

    $ratingCountCompare = ((int) ($b['ratingCount'] ?? 0)) <=> ((int) ($a['ratingCount'] ?? 0));
    if ($ratingCountCompare !== 0) {
        return $ratingCountCompare;
    }

    $ratingCompare = ((float) ($b['rating'] ?? 0)) <=> ((float) ($a['rating'] ?? 0));
    if ($ratingCompare !== 0) {
        return $ratingCompare;
    }

    return ((int) ($b['createdTs'] ?? 0)) <=> ((int) ($a['createdTs'] ?? 0));
});
$homePopularCourses = array_slice($homePopularCourses, 0, 5);
if ($homePopularCourses === []) {
    $homePopularCourses = $homeShowcaseCourses;
}

$homeResumeCourse = is_array($continueLearningItems[0] ?? null) ? $continueLearningItems[0] : null;
$homeResumeProgress = max(0, min(100, (int) ($homeResumeCourse['progressPct'] ?? 0)));
$homeResumeSecondaryCourses = array_values(array_slice($continueLearningItems, 1, 2));

$homeAllCategoriesUrl = $catalogueUrl;
$homeResumeCourseActionUrl = trim((string) ($homeResumeCourse['url'] ?? $catalogueUrl));
$homeResumePrimaryUrl = $homeResumeCourseActionUrl;
$homeResumePrimaryLabel = $homeResumeProgress > 0 ? 'Reprendre le cours' : 'Commencer le cours';
$homeResumeSecondaryLabel = 'Voir mes cours';
$heroVisualUrl = site_url('storage/uploads/site/home-hero.png?v=' . (@filemtime(__DIR__ . '/../../../storage/uploads/site/home-hero.png') ?: time()));
$homeHeaderNav = [
    ['label' => 'Explorer', 'href' => $catalogueUrl],
    ['label' => 'Instructeurs', 'href' => $teacherApplyUrl],
    ['label' => 'Opportunités', 'href' => $supportUrl],
];

$faqItems = [
    [
        'question' => 'Les cours sont-ils accessibles sur téléphone ?',
        'answer' => 'Oui. La page et les formations restent pensées pour une utilisation simple sur mobile et ordinateur.',
    ],
    [
        'question' => 'Les formations sont-elles toutes payantes ?',
        'answer' => 'Non. Certains cours peuvent être gratuits, et les cours payants affichent clairement leur tarif.',
    ],
    [
        'question' => 'Puis-je reprendre un cours plus tard ?',
        'answer' => 'Oui. Votre espace vous permet de retrouver votre progression et de reprendre là où vous vous êtes arrêté.',
    ],
    [
        'question' => 'Y a-t-il un accompagnement si besoin ?',
        'answer' => 'Oui. Le support reste accessible pour vous aider si vous avez une difficulté de navigation ou d inscription.',
    ],
];
?>
<!DOCTYPE html>
<html lang="fr" data-view="web/home">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <meta name="csrf-token" content="<?php echo e($csrfToken); ?>">
  <title><?php echo e($platformName); ?> - Formation en ligne</title>
  <?php if ($seoCanonicalUrl !== ''): ?>
    <link rel="canonical" href="<?php echo e($seoCanonicalUrl); ?>">
  <?php endif; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($homePageJsUrl); ?>" defer></script>
  <script src="<?php echo e($marketingJsUrl); ?>" defer></script>
  <style>
    :root {
      --home-primary: #2d63ea;
      --home-primary-dark: #1f4fd6;
      --home-accent: #ff7a1a;
      --home-ink: #121826;
      --home-muted: #667085;
      --home-line: #dbe4f0;
      --home-soft: #f4f7fb;
      --home-soft-blue: #edf4ff;
      --home-surface: #ffffff;
    }
    body[data-view="web/home"] {
      background: #f7f8fc;
      color: var(--home-ink);
      font-family: "Plus Jakarta Sans", "Inter", sans-serif;
    }
    .home-page-shell {
      background:
        radial-gradient(circle at top right, rgba(45, 99, 234, 0.08), transparent 28%),
        radial-gradient(circle at top left, rgba(255, 122, 26, 0.05), transparent 24%),
        #f7f8fc;
    }
    .home-blue-text {
      color: var(--home-primary);
    }
    .home-card-shadow {
      box-shadow: 0 26px 60px -34px rgba(15, 23, 42, 0.20);
    }
    .home-soft-shadow {
      box-shadow: 0 18px 46px -32px rgba(15, 23, 42, 0.18);
    }
    .home-course-shadow {
      box-shadow: 0 20px 48px -34px rgba(15, 23, 42, 0.22);
    }
    .home-panel-shadow {
      box-shadow: 0 30px 70px -42px rgba(15, 23, 42, 0.16);
    }
    .home-outline-triangle {
      border-top: 10px solid var(--home-primary);
      border-right: 10px solid var(--home-primary);
      border-bottom: 10px solid transparent;
      border-left: 10px solid transparent;
      border-radius: 18px;
    }
    .home-card-hover {
      transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
    }
    .home-card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 28px 62px -38px rgba(37, 99, 235, 0.26);
      border-color: rgba(59, 130, 246, 0.35);
    }
    .home-section-panel {
      border: 1px solid rgba(219, 228, 240, 0.92);
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(250, 251, 255, 0.98) 100%);
      backdrop-filter: blur(10px);
    }
    .home-line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .home-line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .faq-item summary::-webkit-details-marker {
      display: none;
    }
    .faq-item[open] .faq-chevron {
      transform: rotate(180deg);
    }
    .home-section-anchor {
      scroll-margin-top: 7rem;
    }
    .home-scrollbar::-webkit-scrollbar {
      height: 8px;
    }
    .home-scrollbar::-webkit-scrollbar-thumb {
      background: rgba(148, 163, 184, 0.48);
      border-radius: 999px;
    }
    .home-scrollbar::-webkit-scrollbar-track {
      background: rgba(226, 232, 240, 0.55);
      border-radius: 999px;
    }
  </style>
</head>
<body class="overflow-x-hidden bg-[#f7f8fc] text-slate-900 antialiased" data-view="web/home">
<div class="home-page-shell min-h-screen">
  <?php require __DIR__ . '/_public_header_catalogue.php'; ?>

  <main>
    <section class="relative overflow-hidden border-b border-slate-200/80 bg-gradient-to-br from-white via-[#f8fbff] to-[#eef4ff] pt-8 sm:pt-10 lg:pt-12">
      <div class="absolute inset-x-0 top-0 h-full">
        <div class="absolute -left-24 top-28 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="absolute right-[-5rem] top-12 h-80 w-80 rounded-full bg-blue-300/15 blur-3xl"></div>
      </div>

      <div class="relative mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="grid items-center gap-10 pb-20 lg:grid-cols-[1.03fr_0.97fr] lg:pb-24">
          <div class="min-w-0">
            <div class="flex items-center gap-4">
              <?php if ($isLoggedIn): ?>
                <a href="<?php echo e($homeProfileUrl); ?>" class="inline-flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-[#118a8e] text-2xl font-bold text-white shadow-sm">
                  <?php if ($homeAvatarUrl !== ''): ?>
                    <img src="<?php echo e($homeAvatarUrl); ?>" alt="<?php echo e($homeDisplayName); ?>" class="h-full w-full object-cover">
                  <?php else: ?>
                    <?php echo e($homeUserAvatarLetter); ?>
                  <?php endif; ?>
                </a>
                <div class="min-w-0">
                  <p class="text-[1.65rem] font-semibold leading-tight text-slate-950 sm:text-[2rem]">
                    Bonjour
                    <span class="font-extrabold text-blue-600"><?php echo e($homeFirstName !== '' ? $homeFirstName : $homeDisplayName); ?></span>,
                    heureux de vous retrouver sur <?php echo e($platformName); ?> !
                  </p>
                  <a href="<?php echo e($homePreferenceUrl); ?>" class="mt-3 inline-flex text-[1.05rem] font-medium text-blue-600 transition hover:text-blue-700">
                    Ajouter vos préférences et intérêt
                  </a>
                </div>
              <?php else: ?>
                <div class="min-w-0">
                  <p class="text-[1.65rem] font-semibold leading-tight text-slate-950 sm:text-[2rem]">
                    Bienvenue sur
                    <span class="font-extrabold text-blue-600"><?php echo e($platformName); ?></span>
                  </p>
                  <a href="<?php echo e($registerUrl); ?>" class="mt-3 inline-flex text-[1.05rem] font-medium text-blue-600 transition hover:text-blue-700">
                    Créez votre compte pour personnaliser vos recommandations
                  </a>
                </div>
              <?php endif; ?>
            </div>

            <div class="mt-14 inline-flex rounded-full border border-orange-200 bg-orange-50 px-5 py-2 text-sm font-semibold text-orange-600">
              L'apprentissage ouvert à chacun
            </div>
            <h1 class="mt-5 max-w-3xl text-[2.8rem] font-black leading-[0.98] tracking-tight text-slate-950 sm:text-[3.6rem] lg:text-[4.25rem]">
              Des cours en ligne
              <span class="block text-blue-600">engageants</span>
              et accessibles à tous
            </h1>
            <p class="mt-8 max-w-2xl text-lg leading-9 text-slate-500 sm:text-[1.28rem]">
              <?php echo e($siteDescription); ?>
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
              <a href="<?php echo e($homePrimaryCtaUrl); ?>" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                <?php echo e($homePrimaryCtaLabel); ?>
              </a>
              <a href="<?php echo e($homeSecondaryCtaUrl); ?>" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-bold text-slate-900 transition hover:border-blue-300 hover:text-blue-600">
                <?php echo e($homeSecondaryCtaLabel); ?>
              </a>
            </div>
          </div>

          <div class="relative">
            <div class="home-card-shadow relative min-h-[420px] overflow-hidden rounded-[34px] border border-slate-200 bg-gradient-to-br from-white via-[#f7fbff] to-[#eef4ff] lg:min-h-[560px]">
              <img
                src="<?php echo e($heroVisualUrl); ?>"
                alt="Étudiante en ligne"
                class="absolute inset-0 h-full w-full object-cover"
                style="object-position: 56% center;"
                fetchpriority="high"
              >
              <div class="absolute inset-0 bg-gradient-to-l from-white/6 via-transparent to-white/0"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="relative -mt-10 pb-10 sm:-mt-12 lg:-mt-14">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="home-panel-shadow home-section-panel rounded-[34px] p-4 sm:p-5">
          <form action="<?php echo e($catalogueUrl); ?>" method="get" class="grid gap-3 lg:grid-cols-[240px_minmax(0,1fr)_190px]">
            <label class="flex items-center gap-3 rounded-[22px] bg-slate-50 px-4 py-4 text-slate-700">
              <i class="far fa-book-open text-blue-600" aria-hidden="true"></i>
              <select name="category" class="w-full bg-transparent text-sm font-semibold outline-none">
                <option value="">Tous les cours</option>
                <?php foreach ($categoryCarouselItems as $categoryItem): ?>
                  <option value="<?php echo e((string) ($categoryItem['key'] ?? '')); ?>"><?php echo e((string) ($categoryItem['name'] ?? 'Formation')); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label class="flex items-center gap-3 rounded-[22px] border border-slate-200 bg-white px-4 py-4 text-slate-700">
              <i class="fas fa-search text-slate-400" aria-hidden="true"></i>
              <input type="search" name="q" value="" placeholder="Recherchez un cours" class="w-full bg-transparent text-sm font-medium outline-none placeholder:text-slate-400">
            </label>
            <button type="submit" class="inline-flex items-center justify-center rounded-[22px] bg-blue-600 px-5 py-4 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
              Rechercher
            </button>
          </form>

          <div class="mt-5 flex flex-wrap gap-3">
            <?php foreach (array_slice($categoryCarouselItems, 0, 5) as $categoryItem): ?>
              <a href="<?php echo e((string) ($categoryItem['href'] ?? $catalogueUrl)); ?>" class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-blue-600">
                  <i class="fas <?php echo e((string) ($categoryItem['icon'] ?? 'fa-graduation-cap')); ?>" aria-hidden="true"></i>
                </span>
                <?php echo e((string) ($categoryItem['name'] ?? 'Formation')); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <?php if ($isLoggedIn && $homeResumeCourse !== null): ?>
      <section class="home-section-anchor pt-2 pb-10 lg:pb-12" id="why-choose">
        <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
          <?php $homeResumeCover = trim((string) ($homeResumeCourse['cover'] ?? '')); ?>
          <?php if ($homeResumeCover === '') { $homeResumeCover = $makeCoverPlaceholder((string) ($homeResumeCourse['title'] ?? 'Cours'), 'Mon cours'); } ?>
          <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <span class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-5 py-2 text-sm font-semibold text-orange-600">Reprenez là où vous vous êtes arrêté</span>
              <h2 class="mt-5 text-[2.25rem] font-black tracking-tight text-slate-950 sm:text-[3rem]">Continuez votre apprentissage maintenant</h2>
              <p class="mt-3 max-w-3xl text-lg leading-8 text-slate-500">Ne perdez pas votre élan. Continuez les cours que vous avez commencés et progressez vers vos objectifs.</p>
            </div>
            <a href="<?php echo e($homeMyCoursesUrl); ?>" class="inline-flex items-center gap-3 text-lg font-semibold text-blue-600 transition hover:text-blue-700">
              Mon apprentissage
              <i class="fas fa-chevron-right text-sm" aria-hidden="true"></i>
            </a>
          </div>

          <div class="grid gap-6 xl:grid-cols-[minmax(0,1.18fr)_340px]">
            <article class="home-panel-shadow home-section-panel overflow-hidden rounded-[30px]">
              <div class="grid gap-0 md:grid-cols-[280px_minmax(0,1fr)]">
                <div class="relative overflow-hidden bg-slate-100">
                  <img
                    src="<?php echo e($homeResumeCover); ?>"
                    alt="<?php echo e((string) ($homeResumeCourse['coverAlt'] ?? 'Cours')); ?>"
                    class="h-full min-h-[260px] w-full object-cover"
                    loading="lazy"
                  >
                  <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/70 via-slate-950/15 to-transparent px-5 pb-5 pt-12">
                    <div class="flex flex-wrap gap-2">
                      <span class="rounded-full bg-white/92 px-3 py-1.5 text-xs font-bold text-slate-800">
                        <?php echo e((string) ($homeResumeCourse['statusLabel'] ?? 'À reprendre')); ?>
                      </span>
                      <span class="rounded-full bg-white/88 px-3 py-1.5 text-xs font-bold text-slate-700">
                        <?php echo e((string) ($homeResumeCourse['levelLabel'] ?? 'Tous niveaux')); ?>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex flex-col justify-between p-6 sm:p-8">
                  <div>
                    <div class="flex items-center gap-3 text-sm text-slate-500">
                      <span class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400">
                        <i class="far fa-user" aria-hidden="true"></i>
                        <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                      </span>
                      <span class="font-medium"><?php echo e((string) ($homeResumeCourse['teacher'] ?? 'Formateur')); ?></span>
                    </div>
                    <h3 class="mt-5 max-w-2xl text-[1.55rem] font-bold leading-tight text-slate-900 sm:text-[2rem]">
                      <?php echo e((string) ($homeResumeCourse['title'] ?? 'Cours')); ?>
                    </h3>
                    <p class="home-line-clamp-3 mt-4 max-w-2xl text-sm leading-7 text-slate-500">
                      <?php echo e((string) ($homeResumeCourse['summary'] ?? 'Continuez le cours et gardez votre progression sous contrôle.')); ?>
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                      <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                        <i class="fas fa-signal text-blue-600" aria-hidden="true"></i>
                        Progression: <?php echo e((string) $homeResumeProgress); ?>%
                      </span>
                      <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                        <i class="fas fa-list-check text-orange-500" aria-hidden="true"></i>
                        <?php echo e((string) max(0, (int) ($homeResumeCourse['remainingLessons'] ?? 0))); ?> leçon<?php echo max(0, (int) ($homeResumeCourse['remainingLessons'] ?? 0)) > 1 ? 's' : ''; ?> restante<?php echo max(0, (int) ($homeResumeCourse['remainingLessons'] ?? 0)) > 1 ? 's' : ''; ?>
                      </span>
                    </div>
                  </div>

                  <div class="mt-8">
                    <div class="flex items-center justify-between text-base font-medium text-slate-500">
                      <span>Progression</span>
                      <span class="text-slate-900"><?php echo e((string) $homeResumeProgress); ?>%</span>
                    </div>
                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100">
                      <span class="block h-full rounded-full bg-blue-600 transition-all" style="width: <?php echo e((string) $homeResumeProgress); ?>%;"></span>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">
                      <?php echo e((string) ($homeResumeCourse['summary'] ?? '')); ?>
                    </p>
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                      <a href="<?php echo e($homeResumePrimaryUrl); ?>" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                        <?php echo e($homeResumePrimaryLabel); ?>
                      </a>
                      <a href="<?php echo e($homeMyCoursesUrl); ?>" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-900 transition hover:border-blue-300 hover:text-blue-600">
                        <?php echo e($homeResumeSecondaryLabel); ?>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </article>

            <aside class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
              <?php foreach (array_slice($homeResumeSecondaryCourses, 0, 2) as $sideCourse): ?>
                <?php $sideCourseCover = trim((string) ($sideCourse['cover'] ?? '')); ?>
                <?php if ($sideCourseCover === '') { $sideCourseCover = $makeCoverPlaceholder((string) ($sideCourse['title'] ?? 'Cours'), 'Mon cours'); } ?>
                <article class="home-card-hover home-panel-shadow home-section-panel overflow-hidden rounded-[24px]">
                  <div class="flex gap-4 p-4">
                    <img
                      src="<?php echo e($sideCourseCover); ?>"
                      alt="<?php echo e((string) ($sideCourse['coverAlt'] ?? 'Cours')); ?>"
                      class="h-24 w-24 shrink-0 rounded-[18px] object-cover"
                      loading="lazy"
                    >
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">À suivre</p>
                      <h3 class="home-line-clamp-2 mt-2 text-base font-bold leading-tight text-slate-900"><?php echo e((string) ($sideCourse['title'] ?? 'Cours')); ?></h3>
                      <p class="mt-2 truncate text-sm text-slate-500"><?php echo e((string) ($sideCourse['teacher'] ?? 'Formateur')); ?></p>
                      <p class="mt-2 text-xs font-semibold text-slate-400"><?php echo e((string) ($sideCourse['summary'] ?? '')); ?></p>
                      <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-sm font-black text-orange-500"><?php echo e((string) max(0, (int) ($sideCourse['progressPct'] ?? 0))); ?>%</span>
                        <a href="<?php echo e((string) ($sideCourse['url'] ?? $homeMyCoursesUrl)); ?>" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white transition hover:bg-blue-700" aria-label="Voir le cours">
                          <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>

              <div class="home-panel-shadow home-section-panel rounded-[24px] p-5">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Raccourci</p>
                <h3 class="mt-3 text-lg font-bold text-slate-900">Gardez votre rythme</h3>
                <p class="mt-2 text-sm leading-7 text-slate-500">Retrouvez vos cours actifs, votre progression réelle et vos prochains cours à reprendre depuis votre espace apprenant.</p>
                <a href="<?php echo e($homeMyCoursesUrl); ?>" class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-blue-600 transition hover:text-blue-700">
                  Accéder à mon espace
                  <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                </a>
              </div>
            </aside>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <section class="home-section-anchor py-10 lg:py-12" id="categories">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <span class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-5 py-2 text-sm font-semibold text-orange-600">Nouveautés du catalogue</span>
            <h2 class="mt-5 max-w-5xl text-[2.3rem] font-black tracking-tight text-slate-950 sm:text-[3rem]">Les derniers cours publiés sur la plateforme</h2>
            <p class="mt-3 max-w-4xl text-lg leading-8 text-slate-500">Voici les cours réellement disponibles en ce moment dans le catalogue public.</p>
          </div>
          <a href="<?php echo e($homeAllCategoriesUrl); ?>" class="inline-flex items-center gap-3 text-base font-semibold text-blue-600 transition hover:text-blue-700">
            Voir tout le catalogue
            <i class="fas fa-chevron-right text-sm" aria-hidden="true"></i>
          </a>
        </div>

        <div class="home-panel-shadow home-section-panel rounded-[32px] p-4 sm:p-5 lg:p-6">
          <?php if ($homeShowcaseCourses !== []): ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5">
            <?php foreach ($homeShowcaseCourses as $courseCard): ?>
              <?php
              $currentPrice = max(0, (int) ($courseCard['priceRaw'] ?? 0));
              $priceLabel = $currentPrice > 0 ? $publicFormatMoney($currentPrice) : 'Gratuit';
              $courseActionUrl = trim((string) ($currentPrice > 0 ? ($courseCard['buyUrl'] ?? $catalogueUrl) : ($courseCard['url'] ?? $catalogueUrl)));
              $courseActionIcon = $currentPrice > 0 ? 'fa-plus' : 'fa-arrow-right';
              $courseCategory = trim((string) ($courseCard['category'] ?? 'Formation'));
              $courseDuration = trim((string) ($courseCard['durationLabel'] ?? 'À votre rythme'));
              $ratingValue = max(0.0, (float) ($courseCard['rating'] ?? 0));
              $ratingCount = max(0, (int) ($courseCard['ratingCount'] ?? 0));
              ?>
              <article class="home-card-hover home-course-shadow flex h-full flex-col overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                <div class="relative">
                  <img
                    src="<?php echo e((string) ($courseCard['cover'] ?? '')); ?>"
                    alt="<?php echo e((string) ($courseCard['coverAlt'] ?? 'Cours')); ?>"
                    class="h-48 w-full object-cover"
                    loading="lazy"
                  >
                  <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/60 via-slate-950/5 to-transparent px-5 pb-4 pt-10">
                    <div class="flex items-center justify-between gap-3">
                      <span class="rounded-full bg-white/92 px-3 py-1.5 text-xs font-bold text-slate-800">
                        <?php echo e($courseCategory !== '' ? $courseCategory : 'Formation'); ?>
                      </span>
                      <?php if ($courseDuration !== ''): ?>
                        <span class="rounded-full bg-white/88 px-3 py-1.5 text-xs font-bold text-slate-700">
                          <?php echo e($courseDuration); ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <span class="absolute right-4 top-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/95 text-slate-500 shadow-sm" aria-hidden="true">
                    <i class="<?php echo e(!empty($courseCard['isFavorite']) ? 'fas' : 'far'); ?> fa-heart text-xl"></i>
                  </span>
                </div>

                <div class="flex flex-1 flex-col p-5">
                  <div class="flex items-center gap-3 text-sm text-slate-500">
                    <span class="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400">
                      <i class="far fa-user" aria-hidden="true"></i>
                      <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </span>
                    <span class="truncate"><?php echo e((string) ($courseCard['teacher'] ?? 'Formateur')); ?></span>
                  </div>

                  <h3 class="home-line-clamp-2 mt-5 text-[1.05rem] font-extrabold leading-[1.32] text-slate-900 sm:text-[1.15rem]">
                    <?php echo e((string) ($courseCard['title'] ?? 'Cours')); ?>
                  </h3>

                  <div class="mt-6 flex items-center gap-2 text-sm text-slate-500">
                    <?php if ($ratingCount > 0): ?>
                      <span class="flex items-center gap-1 text-amber-400" aria-hidden="true"><?php echo $makeStarIcons($ratingValue); ?></span>
                      <span class="font-semibold text-slate-900"><?php echo e(number_format($ratingValue, 1, ',', ' ')); ?></span>
                      <span>(<?php echo e(number_format($ratingCount, 0, ',', ' ')); ?> Avis)</span>
                    <?php else: ?>
                      <i class="far fa-star text-slate-300" aria-hidden="true"></i>
                      <span>Pas encore d'avis</span>
                    <?php endif; ?>
                  </div>

                  <div class="mt-auto flex items-end justify-between gap-4 border-t border-slate-100 pt-6">
                    <div class="min-w-0">
                      <p class="text-[1.15rem] font-black tracking-tight text-orange-500 sm:text-[1.35rem]"><?php echo e($priceLabel); ?></p>
                    </div>
                    <a href="<?php echo e($courseActionUrl); ?>" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white transition hover:bg-blue-700" aria-label="Voir le cours">
                      <i class="fas <?php echo e($courseActionIcon); ?>" aria-hidden="true"></i>
                    </a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
              <p class="text-lg font-semibold text-slate-800">Aucun cours public n’est encore disponible.</p>
              <p class="mt-2 text-sm text-slate-500">Les prochaines publications apparaîtront ici automatiquement.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="py-10 lg:py-12">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <span class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-5 py-2 text-sm font-semibold text-orange-600">Les plus suivis</span>
            <h2 class="mt-5 max-w-5xl text-[2.25rem] font-black tracking-tight text-slate-950 sm:text-[3rem]">Les cours populaires du moment</h2>
            <p class="mt-3 max-w-4xl text-lg leading-8 text-slate-500">Classement basé sur les inscriptions et les avis réellement présents sur la plateforme.</p>
          </div>
          <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex items-center gap-3 text-base font-semibold text-blue-600 transition hover:text-blue-700">
            Explorer plus de cours
            <i class="fas fa-chevron-right text-sm" aria-hidden="true"></i>
          </a>
        </div>

        <div class="home-panel-shadow home-section-panel rounded-[32px] p-4 sm:p-5 lg:p-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5">
          <?php foreach ($homePopularCourses as $courseCard): ?>
            <?php
            $currentPrice = max(0, (int) ($courseCard['priceRaw'] ?? 0));
            $basePrice = max($currentPrice, (int) ($courseCard['basePriceXof'] ?? $currentPrice));
            $priceLabel = $currentPrice > 0 ? $publicFormatMoney($currentPrice) : 'Gratuit';
            $courseActionUrl = trim((string) ($currentPrice > 0 ? ($courseCard['buyUrl'] ?? $catalogueUrl) : ($courseCard['url'] ?? $catalogueUrl)));
            $courseCategory = trim((string) ($courseCard['category'] ?? 'Formation'));
            $ratingValue = max(0.0, (float) ($courseCard['rating'] ?? 0));
            $ratingCount = max(0, (int) ($courseCard['ratingCount'] ?? 0));
            ?>
            <article class="home-card-hover home-course-shadow flex h-full flex-col overflow-hidden rounded-[24px] border border-slate-200 bg-white">
              <div class="relative">
                <img
                  src="<?php echo e((string) ($courseCard['cover'] ?? '')); ?>"
                  alt="<?php echo e((string) ($courseCard['coverAlt'] ?? 'Cours')); ?>"
                  class="h-48 w-full object-cover"
                  loading="lazy"
                >
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/60 via-slate-950/5 to-transparent px-5 pb-4 pt-10">
                  <span class="rounded-full bg-white/92 px-3 py-1.5 text-xs font-bold text-slate-800">
                    <?php echo e($courseCategory !== '' ? $courseCategory : 'Formation'); ?>
                  </span>
                </div>
                <span class="absolute right-4 top-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/95 text-slate-500 shadow-sm" aria-hidden="true">
                  <i class="<?php echo e(!empty($courseCard['isFavorite']) ? 'fas' : 'far'); ?> fa-heart text-xl"></i>
                </span>
              </div>

              <div class="flex flex-1 flex-col p-5">
                <div class="flex items-center gap-3 text-sm text-slate-500">
                  <span class="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400">
                    <i class="far fa-user" aria-hidden="true"></i>
                    <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                  </span>
                  <span class="truncate"><?php echo e((string) ($courseCard['teacher'] ?? 'Formateur')); ?></span>
                </div>

                <h3 class="home-line-clamp-2 mt-5 text-[1.05rem] font-extrabold leading-[1.32] text-slate-900 sm:text-[1.15rem]">
                  <?php echo e((string) ($courseCard['title'] ?? 'Cours')); ?>
                </h3>
                <p class="home-line-clamp-3 mt-4 text-sm leading-7 text-slate-500"><?php echo e((string) ($courseCard['description'] ?? '')); ?></p>

                <div class="mt-5 flex items-center gap-2 text-sm text-slate-500">
                  <?php if ($ratingCount > 0): ?>
                    <span class="flex items-center gap-1 text-amber-400" aria-hidden="true"><?php echo $makeStarIcons($ratingValue); ?></span>
                    <span class="font-semibold text-slate-900"><?php echo e(number_format($ratingValue, 1, ',', ' ')); ?></span>
                    <span>(<?php echo e(number_format($ratingCount, 0, ',', ' ')); ?> Avis)</span>
                  <?php else: ?>
                    <i class="far fa-star text-slate-300" aria-hidden="true"></i>
                    <span>Pas encore d'avis</span>
                  <?php endif; ?>
                </div>

                <div class="mt-auto flex items-end justify-between gap-4 border-t border-slate-100 pt-6">
                  <div class="min-w-0">
                    <p class="text-[1.15rem] font-black tracking-tight text-orange-500 sm:text-[1.35rem]"><?php echo e($priceLabel); ?></p>
                    <?php if ($basePrice > $currentPrice && $currentPrice > 0): ?>
                      <p class="mt-1 text-xs text-slate-400 line-through"><?php echo e($publicFormatMoney($basePrice)); ?></p>
                    <?php endif; ?>
                  </div>
                  <a href="<?php echo e($courseActionUrl); ?>" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white transition hover:bg-blue-700" aria-label="Voir le cours">
                    <i class="fas <?php echo e($currentPrice > 0 ? 'fa-plus' : 'fa-arrow-right'); ?>" aria-hidden="true"></i>
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="py-10 lg:py-12">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <span class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-5 py-2 text-sm font-semibold text-orange-600">Explorez par domaine</span>
            <h2 class="mt-5 text-[2.1rem] font-black tracking-tight text-slate-950 sm:text-[2.8rem]">Catégories populaires</h2>
            <p class="mt-3 max-w-3xl text-lg leading-8 text-slate-500">Des raccourcis visuels pour retrouver rapidement les thématiques les plus demandées.</p>
          </div>
          <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex items-center gap-3 text-base font-semibold text-blue-600 transition hover:text-blue-700">
            Parcourir les catégories
            <i class="fas fa-chevron-right text-sm" aria-hidden="true"></i>
          </a>
        </div>

        <div class="home-panel-shadow home-section-panel rounded-[32px] p-4 sm:p-5 lg:p-6">
          <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
          <?php foreach ($categoryCarouselItems as $categoryItem): ?>
            <a href="<?php echo e((string) ($categoryItem['href'] ?? $catalogueUrl)); ?>" class="home-card-hover home-soft-shadow flex items-center gap-4 rounded-[24px] border border-slate-200 bg-white px-5 py-5">
              <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br <?php echo e((string) ($categoryItem['gradient'] ?? 'from-blue-600 to-cyan-500')); ?> text-white">
                <i class="fas <?php echo e((string) ($categoryItem['icon'] ?? 'fa-graduation-cap')); ?> text-xl" aria-hidden="true"></i>
              </span>
              <span class="min-w-0">
                <span class="block truncate text-lg font-bold text-slate-900"><?php echo e((string) ($categoryItem['name'] ?? 'Formation')); ?></span>
                <span class="mt-1 block text-sm text-slate-500"><?php echo e((string) ($categoryItem['count'] ?? 'Cours')); ?></span>
              </span>
              <span class="ml-auto inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-50 text-blue-600">
                <i class="fas fa-arrow-right text-sm" aria-hidden="true"></i>
              </span>
            </a>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="home-section-anchor bg-slate-50/70 py-12 lg:py-16" id="faq">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="mb-8">
          <span class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-5 py-2 text-sm font-semibold text-orange-600">FAQ</span>
          <h2 class="mt-5 text-[2.1rem] font-black tracking-tight text-slate-950 sm:text-[2.8rem]">Questions fréquentes avant de commencer</h2>
          <p class="mt-3 max-w-3xl text-lg leading-8 text-slate-500">Les réponses essentielles pour démarrer sans friction.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
          <?php foreach ($faqItems as $faqIndex => $faqItem): ?>
            <details class="faq-item rounded-[24px] border border-slate-200 bg-white px-6 py-5 home-soft-shadow"<?php echo $faqIndex === 0 ? ' open' : ''; ?>>
              <summary class="flex cursor-pointer list-none items-start justify-between gap-4">
                <span class="text-left text-base font-bold text-slate-900"><?php echo e((string) ($faqItem['question'] ?? 'Question')); ?></span>
                <span class="faq-chevron inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-500 transition-transform">
                  <i class="fas fa-chevron-down text-xs" aria-hidden="true"></i>
                </span>
              </summary>
              <p class="mt-4 pr-10 text-sm leading-7 text-slate-500"><?php echo e((string) ($faqItem['answer'] ?? '')); ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="py-12 lg:py-16">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="home-card-shadow overflow-hidden rounded-[32px] bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 px-6 py-12 text-white sm:px-10 lg:px-14">
          <div class="max-w-3xl">
            <h2 class="text-[2rem] font-black leading-tight sm:text-[2.6rem]">Prêt à apprendre une compétence utile cette semaine ?</h2>
            <p class="mt-5 text-lg leading-8 text-blue-100">Commencez dès maintenant, explorez le catalogue et reprenez vos formations quand vous le souhaitez.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
              <a href="<?php echo e($homePrimaryCtaUrl); ?>" class="inline-flex items-center justify-center rounded-2xl bg-white px-6 py-3.5 text-sm font-bold text-blue-700 transition hover:bg-slate-100">
                <?php echo e($homePrimaryCtaLabel); ?>
              </a>
              <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-6 py-3.5 text-sm font-bold text-white transition hover:bg-white/15">
                Voir les formations
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <a href="<?php echo e($supportUrl); ?>" class="fixed bottom-6 right-6 z-40 inline-flex h-16 w-16 items-center justify-center rounded-full bg-blue-600 text-white shadow-[0_18px_40px_-18px_rgba(45,99,234,0.7)] transition hover:bg-blue-700" aria-label="Support">
    <i class="far fa-comments text-2xl" aria-hidden="true"></i>
  </a>

  <?php require __DIR__ . '/_public_footer_home.php'; ?>
</div>
</body>
</html>
