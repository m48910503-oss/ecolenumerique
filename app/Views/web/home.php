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
$supportModalJsUrl = site_url('assets/js/support_modal.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/support_modal.js') ?: time()));
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$csrfToken = view_csrf_token();
$homeHeroTextSizeClass = 'text-[2rem] sm:text-[2.5rem] lg:text-[3rem]';

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
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));

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

$homeCollapseWhitespace = static function (string $value): string {
    $value = preg_replace('/\s+/u', ' ', trim($value));
    return trim((string) $value);
};

$homeFormatCourseTitle = static function (string $value) use ($homeCollapseWhitespace): string {
    $value = $homeCollapseWhitespace($value);
    if ($value === '') {
        return 'Cours numérique';
    }

    $wordReplacements = [
        'Php' => 'PHP',
        'Html' => 'HTML',
        'Css' => 'CSS',
        'Sql' => 'SQL',
        'Seo' => 'SEO',
        'Ui' => 'UI',
        'Ux' => 'UX',
    ];

    foreach ($wordReplacements as $search => $replace) {
        $value = preg_replace('/\b' . preg_quote($search, '/') . '\b/u', $replace, $value) ?? $value;
    }

    return $value;
};

$decorateHomeCourse = static function (array $course) use ($publicDecorateCourse, $deriveLevelLabel, $catalogueUrl, $makeCoverPlaceholder, $homeCollapseWhitespace, $homeFormatCourseTitle): array {
    $decorated = $publicDecorateCourse($course);
    $title = $homeFormatCourseTitle((string) ($decorated['title'] ?? $course['title'] ?? 'Cours numérique'));

    $category = $homeCollapseWhitespace((string) ($decorated['category'] ?? $course['category'] ?? 'Formation'));
    if ($category === '') {
        $category = 'Formation';
    }

    $currentPrice = max(0, (int) ($decorated['priceRaw'] ?? ($course['priceXof'] ?? 0)));
    $basePrice = max($currentPrice, (int) ($course['basePriceXof'] ?? ($course['priceXof'] ?? $currentPrice)));
    $createdAt = trim((string) ($course['createdAt'] ?? $decorated['createdAt'] ?? ''));
    $createdTs = $createdAt !== '' ? (int) strtotime($createdAt) : 0;
    $levelKey = strtolower(trim((string) ($decorated['levelKey'] ?? $course['level'] ?? '')));
    $teacher = $homeCollapseWhitespace((string) ($decorated['teacher'] ?? $course['teacher'] ?? 'Formateur'));
    if ($teacher === '') {
        $teacher = 'Formateur';
    }
    $enrolledCount = max(0, (int) ($decorated['enrolledCount'] ?? $course['enrolledCount'] ?? 0));
    $cover = trim((string) ($decorated['cover'] ?? $course['cover'] ?? ''));
    if ($cover === '') {
        $cover = $makeCoverPlaceholder($title, $category);
    }

    return [
        'id' => max(0, (int) ($decorated['id'] ?? $course['id'] ?? 0)),
        'title' => $title,
        'description' => $homeCollapseWhitespace((string) ($decorated['description'] ?? $course['description'] ?? '')),
        'teacher' => $teacher,
        'category' => $category,
        'categoryKey' => trim((string) ($decorated['categoryKey'] ?? '')),
        'enrolledCount' => $enrolledCount,
        'studentsLabel' => $enrolledCount > 0 ? view_pluralize($enrolledCount, 'inscrit', 'inscrits') : 'Nouveau',
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

$categoryThemeFor = static function (string $label) use ($publicNormalizeKey): string {
    $key = $publicNormalizeKey($label);

    $rules = [
        [
            'keywords' => ['bureautique', 'bureau', 'office', 'excel', 'word', 'powerpoint', 'tableur'],
            'icon' => 'fa-keyboard',
        ],
        [
            'keywords' => ['compta', 'comptabil', 'finance', 'facture', 'budget', 'fiscal', 'payroll'],
            'icon' => 'fa-file-invoice-dollar',
        ],
        [
            'keywords' => ['marketing', 'vente', 'copy', 'communication', 'publicite', 'pub', 'affiliation'],
            'icon' => 'fa-bullhorn',
        ],
        [
            'keywords' => ['dev', 'code', 'web', 'python', 'javascript', 'php', 'html', 'css', 'programmation'],
            'icon' => 'fa-laptop-code',
        ],
        [
            'keywords' => ['data', 'science', 'analyse', 'ia', 'machine', 'stat', 'bi'],
            'icon' => 'fa-chart-line',
        ],
        [
            'keywords' => ['design', 'graph', 'ui', 'ux', 'crea', 'illustr', 'branding'],
            'icon' => 'fa-pen-ruler',
        ],
        [
            'keywords' => ['langue', 'anglais', 'francais', 'espagnol', 'allemand', 'arabe', 'lingu'],
            'icon' => 'fa-globe',
        ],
        [
            'keywords' => ['gestion', 'business', 'entrepreneuriat', 'projet', 'leadership', 'strategy'],
            'icon' => 'fa-rocket',
        ],
        [
            'keywords' => ['secur', 'reseau', 'cyber', 'cloud', 'infra', 'systeme', 'admin'],
            'icon' => 'fa-user-shield',
        ],
        [
            'keywords' => ['photo', 'video', 'audio', 'montage', 'media', 'contenu', 'podcast'],
            'icon' => 'fa-clapperboard',
        ],
    ];

    foreach ($rules as $rule) {
        foreach ($rule['keywords'] as $keyword) {
            if ($key !== '' && str_contains($key, $keyword)) {
                return (string) $rule['icon'];
            }
        }
    }

    if ($key === '' || str_contains($key, 'general') || str_contains($key, 'formation')) {
        return 'fa-book-open-reader';
    }

    return 'fa-table-cells-large';
};

$categoryIconFor = static function (string $label) use ($categoryThemeFor): string {
    return (string) $categoryThemeFor($label);
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
        'name' => $label,
        'count' => view_pluralize((int) $count, 'cours', 'cours'),
    ];

    if (count($categoryCarouselItems) >= 6) {
        break;
    }
}

$homeCategoryCarouselGroups = [];
$categoryCarouselItemCount = count($categoryCarouselItems);
if ($categoryCarouselItemCount > 0) {
    $homeCategoryCarouselRepeatCount = max(2, min(5, (int) ceil(12 / $categoryCarouselItemCount)));
    for ($repeatIndex = 0; $repeatIndex < $homeCategoryCarouselRepeatCount; $repeatIndex++) {
        $homeCategoryCarouselGroups[] = [
            'items' => $categoryCarouselItems,
            'clone' => $repeatIndex > 0,
        ];
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

$defaultSiteDescription = 'Des cours clairs, pratiques et accessibles pour apprendre vite, pratiquer souvent et progresser sans perdre de temps.';
$siteDescription = $isMeaningfulMarketingCopy($siteDescription ?? '')
    ? trim((string) $siteDescription)
    : $defaultSiteDescription;
$siteTagline = $isMeaningfulMarketingCopy($siteTagline ?? '')
    ? trim((string) $siteTagline)
    : 'Des parcours concrets pour progresser à votre rythme.';
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
$homePrimaryCtaLabel = $isLoggedIn ? 'Reprendre mes cours' : 'Commencer maintenant';
$homeSecondaryCtaUrl = $catalogueUrl;
$homeSecondaryCtaLabel = 'Explorer le catalogue';
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

$homeAllcategoriesUrl = $catalogueUrl;
$homeResumeCourseActionUrl = trim((string) ($homeResumeCourse['url'] ?? $catalogueUrl));
$homeResumePrimaryUrl = $homeResumeCourseActionUrl;
$homeResumePrimaryLabel = $homeResumeProgress > 0 ? 'Reprendre le cours' : 'Commencer le cours';
$homeResumeSecondaryLabel = 'Voir mes cours';
$whyChoosePoints = [
    [
        'icon' => 'fa-layer-group',
        'title' => 'Des parcours clairs',
        'description' => 'Des formations organisées pour savoir quoi apprendre et dans quel ordre.',
        'cardClass' => 'why-card why-card--blue',
        'iconClass' => 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-200',
    ],
    [
        'icon' => 'fa-mobile-screen-button',
        'title' => 'Apprentissage mobile',
        'description' => 'Une expérience fluide sur téléphone, tablette et ordinateur.',
        'cardClass' => 'why-card why-card--emerald',
        'iconClass' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-200',
    ],
    [
        'icon' => 'fa-headset',
        'title' => 'Support réactif',
        'description' => 'Le chat support reste accessible dès que vous bloquez sur un point.',
        'cardClass' => 'why-card why-card--amber',
        'iconClass' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-200',
    ],
];
$whyChooseStats = [
    [
        'value' => number_format(max(0, (int) count($courseCards)), 0, ',', ' '),
        'label' => 'formations publiées',
    ],
    [
        'value' => number_format(max(0, (int) $activeEnrollmentCount), 0, ',', ' '),
        'label' => 'apprenants actifs',
    ],
    [
        'value' => number_format(max(0, (int) count($categoryCarouselItems)), 0, ',', ' '),
        'label' => 'domaines populaires',
    ],
];
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
        'answer' => "Oui. Le support reste accessible pour vous aider si vous avez une difficulté de navigation ou d'inscription.",
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
  <script src="<?php echo e($supportModalJsUrl); ?>" defer></script>
  <script>
    document.documentElement.classList.add("home-animate-js");
  </script>
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
    html.dark body[data-view="web/home"] {
      background: #020617;
      color: #e2e8f0;
    }
    .home-page-shell {
      background:
        radial-gradient(circle at top right, rgba(45, 99, 234, 0.08), transparent 28%),
        radial-gradient(circle at top left, rgba(255, 122, 26, 0.05), transparent 24%),
        #f7f8fc;
    }
    html.dark body[data-view="web/home"] .home-page-shell {
      background:
        radial-gradient(circle at top right, rgba(59, 130, 246, 0.18), transparent 28%),
        radial-gradient(circle at top left, rgba(34, 211, 238, 0.08), transparent 22%),
        #020617;
    }
    body[data-view="web/home"] .home-page-shell > main {
      padding-top: clamp(5.75rem, 6vw, 6.75rem);
    }
    body[data-view="web/home"] .mk-header {
      position: fixed;
      inset-inline: 0;
      top: 0;
      z-index: 60;
      box-shadow: 0 12px 34px -28px rgba(15, 23, 42, 0.36);
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
    .home-course-category-badge {
      position: absolute;
      top: 0.75rem;
      left: 0.75rem;
      z-index: 20;
      display: inline-flex;
      max-width: calc(100% - 5rem);
      align-items: center;
      overflow: hidden;
      padding: 0.42rem 0.8rem;
      border: 1px solid rgba(255, 255, 255, 0.28);
      border-radius: 9999px;
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.95) 0%, rgba(14, 165, 233, 0.95) 100%);
      color: #fff;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      line-height: 1;
      text-transform: uppercase;
      white-space: nowrap;
      box-shadow: 0 16px 30px -20px rgba(37, 99, 235, 0.8);
      backdrop-filter: blur(10px);
      pointer-events: none;
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
      transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.45s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.45s cubic-bezier(0.16, 1, 0.3, 1);
      transform: translate3d(0, 0, 0);
      will-change: transform, box-shadow, border-color;
    }
    .home-card-hover:hover {
      transform: translate3d(0, -8px, 0) scale(1.01);
      box-shadow: 0 32px 72px -42px rgba(37, 99, 235, 0.28);
      border-color: rgba(59, 130, 246, 0.35);
    }
    .home-animate-js [data-home-reveal] {
      opacity: 0;
      transform: translate3d(0, 18px, 0);
      filter: blur(3px);
      transition-property: opacity, transform, filter;
      transition-duration: var(--home-reveal-duration, 720ms);
      transition-timing-function: cubic-bezier(0.14, 1, 0.24, 1);
      transition-delay: var(--home-delay, 0ms);
      will-change: opacity, transform, filter;
    }
    .home-animate-js [data-home-reveal="hero"] {
      --home-reveal-duration: 900ms;
      transform: translate3d(0, 26px, 0) scale(0.982);
      filter: blur(4px);
    }
    .home-animate-js [data-home-reveal="hero-copy"] {
      --home-reveal-duration: 760ms;
      transform: translate3d(0, 22px, 0);
      filter: blur(4px);
    }
    .home-animate-js [data-home-reveal="hero-badge"] {
      --home-reveal-duration: 620ms;
      transform: translate3d(0, 14px, 0) scale(0.965);
      filter: blur(2px);
    }
    .home-animate-js [data-home-reveal="hero-action"] {
      --home-reveal-duration: 680ms;
      transform: translate3d(0, 16px, 0) scale(0.97);
      filter: blur(2px);
    }
    .home-animate-js [data-home-reveal="up"] {
      transform: translate3d(0, 18px, 0);
    }
    .home-animate-js [data-home-reveal="slide-left"] {
      transform: translate3d(-28px, 0, 0);
    }
    .home-animate-js [data-home-reveal="slide-right"] {
      transform: translate3d(28px, 0, 0);
    }
    .home-animate-js [data-home-reveal="scale"] {
      transform: scale(0.96);
    }
    .home-animate-js [data-home-reveal="title"] {
      --home-reveal-duration: 920ms;
      transform: translate3d(0, 22px, 0) scale(0.975);
      filter: blur(4px);
    }
    .home-animate-js [data-home-reveal="card"] {
      --home-reveal-duration: 980ms;
      transform-origin: center bottom;
      transform: translate3d(0, 34px, 0) scale(0.92) perspective(1200px) rotateX(6deg);
      filter: blur(6px);
    }
    @keyframes home-card-title-rise {
      0% {
        opacity: 0;
        transform: translate3d(0, 14px, 0);
        filter: blur(2px);
      }
      100% {
        opacity: 1;
        transform: none;
        filter: none;
      }
    }
    .home-animate-js [data-home-reveal="card"].is-visible .home-card-title {
      animation: home-card-title-rise 720ms cubic-bezier(0.16, 1, 0.3, 1) both;
      animation-delay: 150ms;
      will-change: opacity, transform, filter;
    }
    .home-animate-js [data-home-reveal].is-visible {
      opacity: 1;
      transform: none;
      filter: none;
    }
    @keyframes home-ambient-float {
      0%,
      100% {
        transform: translate3d(0, 0, 0);
      }
      50% {
        transform: translate3d(0, -10px, 0);
      }
    }
    @keyframes home-ambient-drift {
      0%,
      100% {
        transform: translate3d(0, 0, 0);
      }
      33% {
        transform: translate3d(8px, -8px, 0);
      }
      66% {
        transform: translate3d(-8px, -4px, 0);
      }
    }
    @keyframes home-ambient-pulse {
      0%,
      100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.02);
      }
    }
    .home-hero-orb {
      pointer-events: none;
      transform-origin: center;
      will-change: transform;
    }
    .home-hero-orb--slow {
      animation: home-ambient-float 15s ease-in-out infinite;
    }
    .home-hero-orb--fast {
      animation: home-ambient-drift 18s ease-in-out infinite;
    }
    .home-animate-js [data-home-ambient] {
      will-change: transform;
    }
    .home-animate-js [data-home-ambient].is-ambient-active {
      animation-duration: var(--home-ambient-duration, 12s);
      animation-delay: var(--home-ambient-delay, 0ms);
      animation-timing-function: ease-in-out;
      animation-iteration-count: infinite;
      animation-fill-mode: both;
    }
    .home-animate-js [data-home-ambient="float"].is-ambient-active {
      animation-name: home-ambient-float;
    }
    .home-animate-js [data-home-ambient="drift"].is-ambient-active {
      animation-name: home-ambient-drift;
    }
    .home-animate-js [data-home-ambient="pulse"].is-ambient-active {
      animation-name: home-ambient-pulse;
    }
    @media (prefers-reduced-motion: reduce) {
      .home-animate-js [data-home-reveal] {
        opacity: 1 !important;
        transform: none !important;
        filter: none !important;
        transition: none !important;
        will-change: auto;
      }
      .home-animate-js [data-home-ambient].is-ambient-active,
      .home-hero-orb {
        animation: none !important;
      }
      body[data-view="web/home"] [data-scroll-top],
      body[data-view="web/home"] [data-support-fab] {
        transition: none !important;
      }
    }
    .home-section-panel {
      border: 1px solid rgba(219, 228, 240, 0.92);
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.94) 0%, rgba(250, 251, 255, 0.98) 100%);
      backdrop-filter: blur(10px);
    }
    .why-card {
      position: relative;
      overflow: hidden;
    }
    .why-card::after {
      content: "";
      position: absolute;
      right: -1.75rem;
      top: -1.75rem;
      width: 8.5rem;
      height: 8.5rem;
      border-radius: 9999px;
      pointer-events: none;
      opacity: 0.42;
      filter: blur(2px);
    }
    .why-card--blue {
      border-color: rgba(191, 219, 254, 0.92);
      background:
        linear-gradient(180deg, rgba(239, 246, 255, 0.98) 0%, rgba(248, 250, 252, 0.98) 100%);
    }
    .why-card--blue::after {
      background: radial-gradient(circle, rgba(59, 130, 246, 0.18) 0%, rgba(59, 130, 246, 0) 70%);
    }
    .why-card--emerald {
      border-color: rgba(167, 243, 208, 0.9);
      background:
        linear-gradient(180deg, rgba(236, 253, 245, 0.98) 0%, rgba(250, 252, 251, 0.98) 100%);
    }
    .why-card--emerald::after {
      background: radial-gradient(circle, rgba(16, 185, 129, 0.16) 0%, rgba(16, 185, 129, 0) 70%);
    }
    .why-card--amber {
      border-color: rgba(253, 230, 138, 0.9);
      background:
        linear-gradient(180deg, rgba(255, 247, 237, 0.98) 0%, rgba(255, 251, 247, 0.98) 100%);
    }
    .why-card--amber::after {
      background: radial-gradient(circle, rgba(245, 158, 11, 0.16) 0%, rgba(245, 158, 11, 0) 70%);
    }
    .home-search-shell {
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(191, 219, 254, 0.68);
      border-radius: 34px;
      background:
        radial-gradient(circle at top left, rgba(45, 99, 234, 0.12), transparent 28%),
        radial-gradient(circle at right center, rgba(255, 122, 26, 0.10), transparent 24%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(247, 250, 255, 0.95) 100%);
      box-shadow: 0 30px 70px -42px rgba(15, 23, 42, 0.18);
    }
    .home-search-shell::before,
    .home-search-shell::after {
      content: "";
      position: absolute;
      pointer-events: none;
      border-radius: 999px;
    }
    .home-search-shell::before {
      top: 1rem;
      left: 1rem;
      width: 10rem;
      height: 10rem;
      background: radial-gradient(circle, rgba(45, 99, 234, 0.12), transparent 68%);
    }
    .home-search-shell::after {
      right: -3rem;
      bottom: -3.5rem;
      width: 13rem;
      height: 13rem;
      background: radial-gradient(circle, rgba(255, 122, 26, 0.10), transparent 70%);
    }
    .home-search-shell__content {
      position: relative;
      z-index: 1;
    }
    .home-search-kicker {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      padding: 0.55rem 0.85rem;
      border: 1px solid rgba(147, 197, 253, 0.55);
      border-radius: 999px;
      background: rgba(239, 246, 255, 0.92);
      color: var(--home-primary);
      font-size: 0.74rem;
      font-weight: 800;
      letter-spacing: 0.11em;
      text-transform: uppercase;
    }
    .home-search-title {
      max-width: 30rem;
      color: var(--home-ink);
      font-size: clamp(1.55rem, 2.15vw, 2.35rem);
      font-weight: 900;
      line-height: 1.06;
      letter-spacing: -0.05em;
    }
    .home-search-subtitle {
      margin-top: 0.65rem;
      max-width: 40rem;
      color: var(--home-muted);
      font-size: 1rem;
      line-height: 1.72;
    }
    .home-search-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 0.55rem;
    }
    .home-search-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      padding: 0.5rem 0.8rem;
      border: 1px solid rgba(219, 228, 240, 0.95);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.82);
      color: #475569;
      font-size: 0.84rem;
      font-weight: 700;
      box-shadow: 0 12px 24px -18px rgba(15, 23, 42, 0.18);
    }
    .home-search-dock {
      display: grid;
      gap: 0.65rem;
      padding: 0.65rem;
      border: 1px solid rgba(191, 219, 254, 0.7);
      border-radius: 30px;
      background: rgba(255, 255, 255, 0.88);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75), 0 18px 40px -28px rgba(15, 23, 42, 0.22);
      backdrop-filter: blur(14px);
    }
    .home-search-section {
      position: relative;
      z-index: 0;
      transition: padding-bottom 0.22s cubic-bezier(0.16, 1, 0.3, 1), z-index 0.22s cubic-bezier(0.16, 1, 0.3, 1);
      padding-bottom: 1.5rem;
    }
    .home-search-section.is-category-open {
      z-index: 25;
      padding-bottom: calc(1.5rem + var(--home-category-menu-space, 0px));
    }
    .home-search-field {
      position: relative;
      display: flex;
      align-items: center;
      gap: 0.8rem;
      min-width: 0;
      min-height: 4.75rem;
      padding: 1rem 1.1rem;
      border: 1px solid rgba(219, 228, 240, 0.96);
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.97);
      color: #334155;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease, background-color 0.2s ease;
    }
    .home-search-field:focus-within {
      border-color: rgba(96, 165, 250, 0.75);
      box-shadow: 0 0 0 4px rgba(191, 219, 254, 0.28);
    }
    .home-search-field select,
    .home-search-field input {
      width: 100%;
      height: 100%;
      min-width: 0;
      border: 0;
      outline: none;
      background: transparent;
      color: inherit;
      font: inherit;
      padding: 0;
      line-height: 1.2;
      -webkit-appearance: none;
      appearance: none;
    }
    .home-search-field select {
      cursor: pointer;
      appearance: none;
      font-weight: 700;
    }
    .home-search-field input {
      font-weight: 600;
    }
    .home-search-field__icon {
      flex-shrink: 0;
      align-self: center;
      color: #94a3b8;
    }
    .home-search-field__chevron {
      position: absolute;
      right: 1rem;
      pointer-events: none;
      align-self: center;
      color: #94a3b8;
    }
    .home-category-picker {
      position: relative;
      z-index: 0;
      overflow: visible;
    }
    .home-category-picker.is-open {
      z-index: 20;
    }
    .home-category-trigger {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      width: 100%;
      min-width: 0;
      min-height: 100%;
      padding: 0;
      border: 0;
      background: transparent;
      color: inherit;
      font: inherit;
      text-align: left;
      cursor: pointer;
    }
    .home-category-trigger:focus-visible {
      outline: none;
    }
    .home-category-value {
      min-width: 0;
      flex: 1 1 auto;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      font-weight: 700;
    }
    .home-category-menu {
      position: absolute;
      top: calc(100% + 0.7rem);
      left: 0;
      right: 0;
      z-index: 30;
      display: grid;
      gap: 0.35rem;
      padding: 0.55rem;
      border: 1px solid rgba(191, 219, 254, 0.9);
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.98);
      box-shadow: 0 26px 54px -34px rgba(15, 23, 42, 0.35);
      backdrop-filter: blur(12px);
    }
    .home-category-menu[hidden] {
      display: none;
    }
    .home-category-option {
      display: flex;
      align-items: center;
      gap: 0.7rem;
      width: 100%;
      min-height: 3.15rem;
      padding: 0.85rem 0.95rem;
      border: 0;
      border-radius: 16px;
      background: transparent;
      color: #334155;
      font: inherit;
      font-weight: 600;
      text-align: left;
      cursor: pointer;
      transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
    }
    .home-category-option:hover,
    .home-category-option:focus-visible {
      background: rgba(239, 246, 255, 0.95);
      color: var(--home-primary);
      outline: none;
    }
    .home-category-option.is-active {
      background: linear-gradient(135deg, var(--home-primary) 0%, #4f8cff 100%);
      color: #fff;
      box-shadow: 0 14px 28px -22px rgba(45, 99, 234, 0.55);
    }
    .home-category-option__icon {
      width: 1rem;
      flex-shrink: 0;
      opacity: 0;
    }
    .home-category-option.is-active .home-category-option__icon {
      opacity: 1;
    }
    .home-category-option__label {
      min-width: 0;
      flex: 1 1 auto;
    }
    .home-search-field input[type="search"]::-webkit-search-decoration,
    .home-search-field input[type="search"]::-webkit-search-cancel-button,
    .home-search-field input[type="search"]::-webkit-search-results-button,
    .home-search-field input[type="search"]::-webkit-search-results-decoration {
      display: none;
      appearance: none;
    }
    .home-search-field-title {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
    .home-search-field--select {
      padding-right: 2.15rem;
      background: linear-gradient(180deg, rgba(239, 246, 255, 0.95), rgba(255, 255, 255, 0.98));
    }
    .home-search-field--button {
      justify-content: center;
      border: none;
      background: linear-gradient(135deg, var(--home-primary) 0%, #4f8cff 100%);
      box-shadow: 0 18px 38px -22px rgba(45, 99, 234, 0.68);
      color: #fff;
      font-weight: 800;
      letter-spacing: -0.01em;
      white-space: nowrap;
    }
    .home-search-field--button:hover {
      background: linear-gradient(135deg, var(--home-primary-dark) 0%, #3f76ff 100%);
      transform: translateY(-1px);
    }
    .home-search-field--button i {
      font-size: 0.9rem;
    }
    @media (min-width: 1024px) {
      .home-search-shell__content {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
        align-items: end;
        gap: 1.4rem;
      }
      .home-search-dock {
        grid-template-columns: minmax(0, 240px) minmax(0, 1fr) minmax(0, 220px);
        gap: 0;
        padding: 0;
        overflow: hidden;
        align-self: end;
      }
      .home-search-field {
        border: none;
        border-radius: 0;
        min-height: 5.25rem;
        padding: 1.15rem 1.25rem;
        background: transparent;
      }
      .home-search-field + .home-search-field {
        border-left: 1px solid rgba(226, 232, 240, 0.95);
      }
      .home-search-field--select {
        padding-right: 2.35rem;
        background: rgba(248, 250, 255, 0.96);
      }
      .home-search-field--button {
        padding-inline: 1.35rem;
      }
    }
    @media (max-width: 1023px) {
      .home-search-shell__content {
        display: grid;
        gap: 1rem;
      }
      .home-search-dock {
        padding: 0.75rem;
      }
      .home-search-field {
        min-height: 4.25rem;
      }
      .home-search-field--button {
        min-height: 4.35rem;
      }
    }
    @media (max-width: 639px) {
      .home-search-field {
        min-height: 3.5rem;
        padding: 0.75rem 0.9rem;
        gap: 0.65rem;
        border-radius: 18px;
      }
      .home-search-field--select {
        padding-right: 1.8rem;
      }
      .home-search-field select,
      .home-search-field input {
        font-size: 0.95rem;
      }
      .home-search-field--button {
        min-height: 3.5rem;
        padding-inline: 1rem;
        border-radius: 18px;
        font-size: 0.95rem;
      }
      .home-search-field__icon {
        font-size: 0.95rem;
      }
      .home-search-field__chevron {
        right: 0.85rem;
      }
      .home-category-trigger {
        gap: 0.65rem;
      }
      .home-category-menu {
        top: calc(100% + 0.5rem);
        padding: 0.45rem;
      }
      .home-category-option {
        min-height: 2.75rem;
        padding: 0.75rem 0.85rem;
      }
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
    .faq-item {
      transition: border-color 0.24s ease, box-shadow 0.24s ease, background-color 0.24s ease;
    }
    .faq-item summary::-webkit-details-marker {
      display: none;
    }
    .faq-item[open] {
      border-color: rgba(96, 165, 250, 0.52);
      box-shadow: 0 24px 48px -36px rgba(37, 99, 235, 0.28);
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
    .home-carousel {
      -ms-overflow-style: none;
      scrollbar-width: none;
      position: relative;
      overflow: hidden;
    }
    .home-carousel::-webkit-scrollbar {
      display: none;
    }
    .home-carousel-track {
      display: flex;
      width: max-content;
      will-change: transform;
      animation: home-carousel-marquee var(--home-carousel-duration, 32s) linear infinite;
      transform: translate3d(0, 0, 0);
    }
    .home-carousel:hover .home-carousel-track,
    .home-carousel:focus-within .home-carousel-track {
      animation-play-state: paused;
    }
    .home-carousel-group {
      display: flex;
      gap: 0.75rem;
      padding-right: 0.75rem;
      flex: 0 0 auto;
    }
    @keyframes home-carousel-marquee {
      from {
        transform: translate3d(0, 0, 0);
      }
      to {
        transform: translate3d(calc(-1 * var(--home-carousel-shift, 0px)), 0, 0);
      }
    }
    html.dark body[data-view="web/home"] main > section:first-child {
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(8, 15, 27, 0.96)) !important;
      border-bottom-color: rgba(148, 163, 184, 0.16) !important;
    }
    html.dark body[data-view="web/home"] :is(.home-section-panel, .home-course-shadow, .home-panel-shadow, .home-soft-shadow, .home-card-shadow) {
      box-shadow: 0 30px 70px -42px rgba(0, 0, 0, 0.78) !important;
    }
    html.dark body[data-view="web/home"] :is(.home-section-panel, .home-course-shadow, .home-soft-shadow, .home-panel-shadow) {
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 0.86)) !important;
      border-color: rgba(148, 163, 184, 0.16) !important;
    }
    html.dark body[data-view="web/home"] :is([class*="bg-white"], [class*="bg-slate-50"], [class*="bg-slate-100"], [class*="bg-gray-50"]) {
      background-color: rgba(15, 23, 42, 0.82) !important;
    }
    html.dark body[data-view="web/home"] :is([class*="border-slate-200"], [class*="border-slate-100"], [class*="border-slate-300"], [class*="border-gray-200"]) {
      border-color: rgba(148, 163, 184, 0.16) !important;
    }
    html.dark body[data-view="web/home"] :is(.text-slate-950, .text-slate-900, .text-slate-800) {
      color: #f8fafc !important;
    }
    html.dark body[data-view="web/home"] :is(.text-slate-700, .text-slate-600, .text-gray-700) {
      color: #dbe4f0 !important;
    }
    html.dark body[data-view="web/home"] :is(.text-slate-500, .text-slate-400, .text-gray-500, .text-gray-400) {
      color: #94a3b8 !important;
    }
    html.dark body[data-view="web/home"] .home-search-shell {
      border-color: rgba(148, 163, 184, 0.18);
      background:
        radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 28%),
        radial-gradient(circle at right center, rgba(14, 165, 233, 0.08), transparent 24%),
        linear-gradient(180deg, rgba(15, 23, 42, 0.96) 0%, rgba(15, 23, 42, 0.90) 100%);
      box-shadow: 0 34px 76px -44px rgba(0, 0, 0, 0.82);
    }
    html.dark body[data-view="web/home"] .home-search-kicker {
      border-color: rgba(96, 165, 250, 0.25);
      background: rgba(30, 41, 59, 0.82);
      color: #93c5fd;
    }
    html.dark body[data-view="web/home"] .home-search-badge {
      border-color: rgba(148, 163, 184, 0.2);
      background: rgba(15, 23, 42, 0.78);
      color: #dbe4f0;
    }
    html.dark body[data-view="web/home"] .home-search-dock {
      border-color: rgba(148, 163, 184, 0.18);
      background: rgba(15, 23, 42, 0.78);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06), 0 18px 40px -28px rgba(0, 0, 0, 0.5);
    }
    html.dark body[data-view="web/home"] .home-search-field {
      border-color: rgba(148, 163, 184, 0.16);
      background: rgba(15, 23, 42, 0.80);
      color: #e2e8f0;
    }
    html.dark body[data-view="web/home"] .home-search-field--select {
      background: rgba(15, 23, 42, 0.88);
    }
    html.dark body[data-view="web/home"] .home-search-field__icon,
    html.dark body[data-view="web/home"] .home-search-field__chevron {
      color: #94a3b8;
    }
    html.dark body[data-view="web/home"] .home-category-menu {
      border-color: rgba(148, 163, 184, 0.2);
      background: rgba(15, 23, 42, 0.94);
      box-shadow: 0 30px 66px -38px rgba(0, 0, 0, 0.78);
    }
    html.dark body[data-view="web/home"] .home-category-option {
      color: #dbe4f0;
    }
    html.dark body[data-view="web/home"] .home-category-option:hover,
    html.dark body[data-view="web/home"] .home-category-option:focus-visible {
      background: rgba(30, 41, 59, 0.9);
      color: #bfdbfe;
    }
    html.dark body[data-view="web/home"] .home-search-field:focus-within {
      border-color: rgba(96, 165, 250, 0.55);
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.18);
    }
    .home-cta-button {
      color: #fff !important;
      background: #1d4ed8;
      box-shadow: 0 18px 34px -24px rgba(29, 78, 216, 0.55);
    }
    .home-cta-button:hover,
    .home-cta-button:focus-visible {
      color: #fff !important;
      background: #1e40af;
    }
    .home-cta-button--secondary {
      color: #fff !important;
      background: #f97316;
      box-shadow: 0 18px 34px -24px rgba(249, 115, 22, 0.35);
    }
    .home-cta-button--secondary:hover,
    .home-cta-button--secondary:focus-visible {
      color: #fff !important;
      background: #ea580c;
    }
    .home-section-eyebrow {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(251, 146, 60, 0.28);
      border-radius: 999px;
      padding: 0.7rem 1.5rem;
      background: #fff7ed;
      color: #ea580c;
      font-size: 0.875rem;
      line-height: 1.25rem;
      font-weight: 700;
      text-align: center;
    }
    .home-section-title {
      margin: 1.25rem auto 0;
      max-width: 64rem;
      text-align: center;
      font-size: clamp(2.15rem, 4vw, 3.1rem);
      line-height: 1.05;
      font-weight: 900;
      letter-spacing: -0.04em;
      color: #0f172a;
      text-wrap: balance;
    }
    .home-section-header {
      display: grid;
      gap: 1rem;
      align-items: center;
    }
    .home-section-header__spacer {
      display: none;
    }
    .home-section-header__content {
      text-align: center;
    }
    .home-section-header__action {
      justify-self: center;
    }
    @media (min-width: 1024px) {
      .home-section-header {
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
      }
      .home-section-header__spacer {
        display: block;
      }
      .home-section-header__content {
        grid-column: 2;
      }
      .home-section-header__action {
        grid-column: 3;
        justify-self: end;
      }
    }
    html.dark body[data-view="web/home"] .home-section-eyebrow {
      background: rgba(245, 158, 11, 0.12);
      border-color: rgba(245, 158, 11, 0.28);
      color: #fdba74;
    }
    html.dark body[data-view="web/home"] .why-card--blue,
    html.dark body[data-view="web/home"] .why-card--emerald,
    html.dark body[data-view="web/home"] .why-card--amber {
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.96) 0%, rgba(15, 23, 42, 0.9) 100%);
    }
    html.dark body[data-view="web/home"] .why-card--blue {
      border-color: rgba(59, 130, 246, 0.2);
    }
    html.dark body[data-view="web/home"] .why-card--emerald {
      border-color: rgba(16, 185, 129, 0.2);
    }
    html.dark body[data-view="web/home"] .why-card--amber {
      border-color: rgba(245, 158, 11, 0.2);
    }
    html.dark body[data-view="web/home"] .home-section-title {
      color: #f8fafc !important;
    }
  </style>
</head>
<body class="overflow-x-hidden bg-[#f7f8fc] text-slate-900 antialiased" data-view="web/home" data-base-path="<?php echo e((string) ($basePath ?? '')); ?>">
<div class="home-page-shell min-h-screen">
  <?php require __DIR__ . '/_public_header_catalogue.php'; ?>

  <main>
    <section class="relative overflow-hidden border-b border-slate-200/80 bg-gradient-to-br from-white via-[#f8fbff] to-[#eef4ff] pt-8 sm:pt-10 lg:pt-12">
      <div class="absolute inset-x-0 top-0 h-full">
        <div class="home-hero-orb home-hero-orb--slow absolute -left-24 top-28 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="home-hero-orb home-hero-orb--fast absolute right-[-5rem] top-12 h-80 w-80 rounded-full bg-blue-300/15 blur-3xl"></div>
      </div>

      <div class="relative mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="grid items-center gap-8 pb-16 lg:grid-cols-[1.03fr_0.97fr] lg:gap-10 lg:pb-20">
          <div class="min-w-0" data-home-reveal="slide-left" data-home-delay="40">
            <div class="min-w-0">
              <?php if ($isLoggedIn): ?>
                <p class="<?php echo e($homeHeroTextSizeClass); ?> font-semibold leading-tight text-slate-950" data-home-reveal="hero-copy" data-home-delay="90">
                  Bonjour
                  <span class="font-extrabold text-blue-600"><?php echo e($homeFirstName !== '' ? $homeFirstName : $homeDisplayName); ?></span>,
                  prêt à reprendre votre progression ?
                </p>
                <a href="<?php echo e($homePreferenceUrl); ?>" class="mt-3 inline-flex text-[1.05rem] font-medium text-blue-600 transition hover:text-blue-700" data-home-reveal="hero-copy" data-home-delay="150">
                  Dites-nous ce que vous voulez apprendre
                </a>
              <?php else: ?>
                <p class="<?php echo e($homeHeroTextSizeClass); ?> font-semibold leading-tight text-slate-950" data-home-reveal="hero-copy" data-home-delay="90">
                  Bienvenue sur
                  <span class="font-extrabold text-slate-900 dark:text-white"><?php echo site_brand_name_html($platformName); ?></span>
                </p>
                <a href="<?php echo e($registerUrl); ?>" class="mt-3 inline-flex text-[1.05rem] font-medium text-blue-600 transition hover:text-blue-700" data-home-reveal="hero-copy" data-home-delay="150">
                  Créez votre compte pour recevoir des recommandations adaptées
                </a>
              <?php endif; ?>
            </div>

            <div class="mt-10 inline-flex rounded-full border border-orange-200 bg-orange-50 px-5 py-2 text-sm font-semibold text-orange-600" data-home-reveal="hero-badge" data-home-delay="220">
              Apprenez des compétences utiles
            </div>
            <h1 class="mt-5 max-w-3xl <?php echo e($homeHeroTextSizeClass); ?> font-black leading-[0.98] tracking-tight text-slate-950" data-home-reveal="title" data-home-delay="300">
              Apprenez des compétences
              <span class="block text-blue-600">qui ouvrent des portes</span>
              à votre rythme
            </h1>
            <p class="mt-8 max-w-2xl text-lg leading-9 text-slate-500 sm:text-[1.28rem]" data-home-reveal="hero-copy" data-home-delay="380">
              Des formations claires, concrètes et accessibles pour avancer sans perdre de temps.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
              <a href="<?php echo e($homePrimaryCtaUrl); ?>" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700" data-home-reveal="hero-action" data-home-delay="460">
                <?php echo e($homePrimaryCtaLabel); ?>
              </a>
              <a href="<?php echo e($homeSecondaryCtaUrl); ?>" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-bold text-slate-900 transition hover:border-blue-300 hover:text-blue-600" data-home-reveal="hero-action" data-home-delay="540">
                <?php echo e($homeSecondaryCtaLabel); ?>
              </a>
            </div>
          </div>

          <div class="relative" data-home-reveal="slide-right" data-home-delay="180" data-home-ambient="float" data-home-ambient-duration="14">
            <div class="home-card-shadow relative min-h-[420px] overflow-hidden rounded-[34px] border border-slate-200 bg-gradient-to-br from-blue-100 via-[#dff0ff] to-[#c9e7ff] lg:min-h-[560px]">
              <img
                src="<?php echo e($heroVisualUrl); ?>"
                alt="Étudiante en ligne"
                class="absolute inset-0 h-full w-full object-cover"
                style="object-position: 56% center;"
                fetchpriority="high"
              >
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="home-search-section relative -mt-10 pb-6 sm:-mt-12 lg:-mt-14">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="home-panel-shadow home-section-panel rounded-[28px] p-3 sm:p-5" data-home-reveal="hero" data-home-delay="140" data-home-ambient="drift" data-home-ambient-duration="16">
          <div class="mb-3 sm:mb-4" data-home-reveal="hero-copy" data-home-delay="220">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-600">Recherche</p>
            <h2 class="mt-1.5 text-[1.1rem] font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:mt-2 sm:text-[1.25rem]">Trouver un cours</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Choisissez une catégorie puis lancez la recherche.</p>
          </div>
          <form action="<?php echo e($catalogueUrl); ?>" method="get" class="grid gap-3 sm:gap-4 lg:grid-cols-[minmax(280px,340px)_minmax(0,1fr)_minmax(170px,190px)]" data-home-reveal="up" data-home-delay="320">
            <div class="home-search-field home-search-field--select home-category-picker flex min-h-[3.5rem] w-full items-center gap-2.5 rounded-[18px] border border-slate-200 bg-white px-4 py-3 text-slate-700 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-200/60 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:focus-within:border-blue-500/70 dark:focus-within:ring-blue-500/20 sm:min-h-[4.5rem] sm:gap-3 sm:rounded-[22px] sm:px-5 sm:py-4" data-home-category-picker>
              <span class="home-search-field-title">Catégorie</span>
              <input type="hidden" name="category" value="" data-home-category-input>
              <button type="button" class="home-category-trigger" data-home-category-trigger aria-haspopup="listbox" aria-expanded="false" aria-controls="homeCategoryMenu">
                <i class="home-search-field__icon far fa-book-open text-base text-blue-600 sm:text-lg" aria-hidden="true"></i>
                <span class="home-category-value" data-home-category-label>Tous les cours</span>
              </button>
              <i class="home-search-field__chevron fas fa-chevron-down text-xs text-slate-400" aria-hidden="true"></i>
              <div id="homeCategoryMenu" class="home-category-menu" data-home-category-menu hidden role="listbox" aria-label="Catégorie">
                <button type="button" class="home-category-option is-active" data-home-category-option data-category-value="" data-category-label="Tous les cours" aria-selected="true">
                  <i class="home-category-option__icon fas fa-check" aria-hidden="true"></i>
                  <span class="home-category-option__label">Tous les cours</span>
                </button>
                <?php foreach ($categoryCarouselItems as $categoryItem): ?>
                  <?php $homeCategoryOptionValue = (string) ($categoryItem['key'] ?? ''); ?>
                  <?php $homeCategoryOptionLabel = (string) ($categoryItem['name'] ?? 'Formation'); ?>
                  <button type="button" class="home-category-option" data-home-category-option data-category-value="<?php echo e($homeCategoryOptionValue); ?>" data-category-label="<?php echo e($homeCategoryOptionLabel); ?>" aria-selected="false">
                    <i class="home-category-option__icon fas fa-check" aria-hidden="true"></i>
                    <span class="home-category-option__label"><?php echo e($homeCategoryOptionLabel); ?></span>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
            <label class="home-search-field flex min-h-[3.5rem] w-full items-center gap-2.5 rounded-[18px] border border-slate-200 bg-white px-4 py-3 text-slate-700 shadow-sm transition focus-within:border-blue-300 focus-within:ring-2 focus-within:ring-blue-200/60 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:focus-within:border-blue-500/70 dark:focus-within:ring-blue-500/20 sm:min-h-[4.5rem] sm:gap-3 sm:rounded-[22px] sm:px-5 sm:py-4">
              <span class="home-search-field-title">Recherche</span>
              <i class="home-search-field__icon fas fa-search text-base text-slate-400 sm:text-lg" aria-hidden="true"></i>
              <input
                type="text"
                name="q"
                value=""
                aria-label="Rechercher un cours ou un sujet"
                placeholder="Rechercher un cours ou un sujet"
                class="min-w-0 flex-1 appearance-none bg-transparent text-sm outline-none placeholder:text-slate-400 dark:text-slate-100 dark:placeholder:text-slate-400 sm:text-base"
              >
            </label>
            <button type="submit" class="home-search-field home-search-field--button inline-flex min-h-[3.5rem] w-full items-center justify-center rounded-[18px] bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:min-h-[4.5rem] sm:rounded-[22px] sm:px-6 sm:text-base lg:w-auto lg:min-w-[180px]">
              Trouver
            </button>
          </form>
        </div>
      </div>
    </section>

    <section class="home-section-anchor pt-2 pb-10 lg:pb-12" id="why-choose">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="home-section-header mb-6" data-home-reveal="up" data-home-delay="40">
          <div class="home-section-header__spacer" aria-hidden="true"></div>
          <div class="home-section-header__content">
            <span class="home-section-eyebrow">Pourquoi nous choisir</span>
            <h2 class="home-section-title" data-home-reveal="title" data-home-delay="80">Une plateforme pensée pour apprendre sans perdre de temps</h2>
            <p class="mx-auto mt-3 max-w-3xl text-lg leading-8 text-slate-500 sm:text-[1.15rem]">Des cours concrets, une navigation simple et un accompagnement rapide pour progresser avec confiance.</p>
          </div>
          <div class="home-section-header__spacer" aria-hidden="true"></div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <?php foreach ($whyChoosePoints as $whyChooseIndex => $whyChoosePoint): ?>
            <article class="home-card-hover home-panel-shadow home-section-panel <?php echo e((string) ($whyChoosePoint['cardClass'] ?? '')); ?> rounded-[24px] p-5 sm:p-6" data-home-reveal="scale" data-home-delay="<?php echo e((string) ($whyChooseIndex * 90)); ?>">
              <div class="flex items-start gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl <?php echo e((string) ($whyChoosePoint['iconClass'] ?? 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-200')); ?>">
                  <i class="fas <?php echo e((string) ($whyChoosePoint['icon'] ?? 'fa-star')); ?>" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                  <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100"><?php echo e((string) ($whyChoosePoint['title'] ?? 'Avantage')); ?></h3>
                  <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400"><?php echo e((string) ($whyChoosePoint['description'] ?? '')); ?></p>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <?php foreach ($whyChooseStats as $whyChooseStatIndex => $whyChooseStat): ?>
            <div class="home-panel-shadow home-section-panel rounded-[22px] px-5 py-4" data-home-reveal="up" data-home-delay="<?php echo e((string) (120 + ($whyChooseStatIndex * 90))); ?>">
              <p class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100"><?php echo e((string) ($whyChooseStat['value'] ?? '0')); ?></p>
              <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400"><?php echo e((string) ($whyChooseStat['label'] ?? '')); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php if ($isLoggedIn && $homeResumeCourse !== null): ?>
      <section class="home-section-anchor pt-2 pb-10 lg:pb-12" id="continue-learning" data-home-reveal="up" data-home-delay="60">
        <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
          <?php $homeResumeCover = trim((string) ($homeResumeCourse['cover'] ?? '')); ?>
          <?php if ($homeResumeCover === '') { $homeResumeCover = $makeCoverPlaceholder((string) ($homeResumeCourse['title'] ?? 'Cours'), 'Mon cours'); } ?>
          <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <span class="inline-flex rounded-full border border-orange-200 bg-orange-50 px-5 py-2 text-sm font-semibold text-orange-600">Reprenez là où vous vous êtes arrêté</span>
              <h2 class="mt-5 text-[2.25rem] font-black tracking-tight text-slate-950 sm:text-[3rem]" data-home-reveal="title" data-home-delay="110">Continuez votre apprentissage maintenant</h2>
              <p class="mt-3 max-w-3xl text-lg leading-8 text-slate-500">Ne perdez pas votre élan. Continuez les cours que vous avez commencés et progressez vers vos objectifs.</p>
            </div>
            <a href="<?php echo e($homeMyCoursesUrl); ?>" class="inline-flex items-center gap-3 text-lg font-semibold text-blue-600 transition hover:text-blue-700">
              Mon apprentissage
              <i class="fas fa-chevron-right text-sm" aria-hidden="true"></i>
            </a>
          </div>

          <div class="grid gap-6 xl:grid-cols-[minmax(0,1.18fr)_340px]">
            <article class="home-panel-shadow home-section-panel overflow-hidden rounded-[30px]" data-home-reveal="card" data-home-delay="180" data-home-ambient="float" data-home-ambient-duration="18" data-home-ambient-delay="120">
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
                    <h3 class="home-card-title mt-5 max-w-2xl text-[1.55rem] font-bold leading-tight text-slate-900 sm:text-[2rem]">
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
              <?php foreach (array_slice($homeResumeSecondaryCourses, 0, 2) as $sideCourseIndex => $sideCourse): ?>
                <?php $sideCourseCover = trim((string) ($sideCourse['cover'] ?? '')); ?>
                <?php if ($sideCourseCover === '') { $sideCourseCover = $makeCoverPlaceholder((string) ($sideCourse['title'] ?? 'Cours'), 'Mon cours'); } ?>
                <article class="home-card-hover home-panel-shadow home-section-panel overflow-hidden rounded-[24px]" data-home-reveal="card" data-home-delay="<?php echo e((string) (180 + ($sideCourseIndex * 90))); ?>">
                  <div class="flex gap-4 p-4">
                    <img
                      src="<?php echo e($sideCourseCover); ?>"
                      alt="<?php echo e((string) ($sideCourse['coverAlt'] ?? 'Cours')); ?>"
                      class="h-24 w-24 shrink-0 rounded-[18px] object-cover"
                      loading="lazy"
                    >
                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">À suivre</p>
                      <h3 class="home-card-title home-line-clamp-2 mt-2 text-base font-bold leading-tight text-slate-900"><?php echo e((string) ($sideCourse['title'] ?? 'Cours')); ?></h3>
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

    <section class="home-section-anchor py-8 lg:py-10" id="categories" data-home-reveal="up" data-home-delay="80">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="home-section-header mb-6" data-home-reveal="up" data-home-delay="0">
          <div class="home-section-header__spacer" aria-hidden="true"></div>
          <div class="home-section-header__content">
            <span class="home-section-eyebrow">Catégories</span>
            <h2 class="home-section-title" data-home-reveal="title" data-home-delay="80">Parcourez les domaines enseignés</h2>
            <p class="mx-auto mt-3 max-w-3xl text-lg leading-8 text-slate-500 sm:text-[1.15rem]">Faites défiler les thématiques pour trouver plus vite le bon point de départ.</p>
          </div>
          <a href="<?php echo e($homeAllcategoriesUrl); ?>" class="home-section-header__action inline-flex items-center gap-3 text-base font-semibold text-blue-600 transition hover:text-blue-700 lg:shrink-0">
            Voir tout le catalogue
            <i class="fas fa-chevron-right text-sm" aria-hidden="true"></i>
          </a>
        </div>

        <div class="home-carousel w-full overflow-hidden py-1" data-category-carousel data-home-reveal="scale" data-home-delay="120">
          <div class="home-carousel-track" data-category-carousel-track>
            <?php foreach ($homeCategoryCarouselGroups as $carouselGroup): ?>
              <div class="home-carousel-group"<?php echo !empty($carouselGroup['clone']) ? ' aria-hidden="true" inert' : ' data-category-carousel-group'; ?>>
                <?php foreach (($carouselGroup['items'] ?? []) as $categoryItem): ?>
                  <a href="<?php echo e((string) ($categoryItem['href'] ?? $catalogueUrl)); ?>" class="home-card-hover home-soft-shadow flex w-[220px] shrink-0 flex-col items-center gap-3 rounded-[24px] border border-slate-200 bg-white px-5 py-5 text-center sm:w-[240px] dark:border-slate-700 dark:bg-slate-900/70">
                    <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-200">
                      <i class="fas <?php echo e((string) ($categoryItem['icon'] ?? 'fa-book-open-reader')); ?> text-xl" aria-hidden="true"></i>
                    </span>
                    <span class="min-w-0">
                      <span class="block truncate text-lg font-bold text-slate-900 dark:text-slate-100"><?php echo e((string) ($categoryItem['name'] ?? 'Formation')); ?></span>
                      <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400"><?php echo e((string) ($categoryItem['count'] ?? 'Cours')); ?></span>
                    </span>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="home-section-anchor py-8 lg:py-10" id="featured-courses" data-home-reveal="up" data-home-delay="80">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="home-section-header mb-6" data-home-reveal="up" data-home-delay="0">
          <div class="home-section-header__spacer" aria-hidden="true"></div>
          <div class="home-section-header__content">
            <span class="home-section-eyebrow">Nouveautés du catalogue</span>
            <h2 class="home-section-title" data-home-reveal="title" data-home-delay="80">Les derniers cours publiés sur la plateforme</h2>
            <p class="mx-auto mt-3 max-w-4xl text-lg leading-8 text-slate-500 sm:text-[1.15rem]">Voici les cours réellement disponibles en ce moment dans le catalogue public.</p>
          </div>
          <a href="<?php echo e($homeAllcategoriesUrl); ?>" class="home-section-header__action inline-flex items-center gap-3 text-base font-semibold text-blue-600 transition hover:text-blue-700 lg:shrink-0">
            Voir tout le catalogue
            <i class="fas fa-chevron-right text-sm" aria-hidden="true"></i>
          </a>
        </div>

        <div class="home-panel-shadow home-section-panel rounded-[32px] p-4 sm:p-5 lg:p-6" data-home-reveal="scale" data-home-delay="120" data-home-ambient="float" data-home-ambient-duration="20" data-home-ambient-delay="180">
          <?php if ($homeShowcaseCourses !== []): ?>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-5">
            <?php foreach ($homeShowcaseCourses as $courseIndex => $courseCard): ?>
              <?php
              $currentPrice = max(0, (int) ($courseCard['priceRaw'] ?? 0));
              $basePrice = max($currentPrice, (int) ($courseCard['basePriceXof'] ?? $currentPrice));
              $priceLabel = $currentPrice > 0 ? $publicFormatMoney($currentPrice) : 'Gratuit';
              $courseDetailUrl = trim((string) ($courseCard['url'] ?? $catalogueUrl));
              $courseCatalogUrl = $catalogueUrl;
              $courseCategory = trim((string) ($courseCard['category'] ?? 'Formation'));
              $courseDuration = trim((string) ($courseCard['durationLabel'] ?? 'À votre rythme'));
              $courseDurationMin = max(0, (int) ($courseCard['durationMin'] ?? 0));
              $courseLevel = trim((string) ($courseCard['levelLabel'] ?? 'Tous niveaux'));
              $courseHasPromo = $basePrice > $currentPrice && $currentPrice > 0;
              $coursePriceClass = 'app-price text-[1.05rem] font-black tracking-tight sm:text-[1.2rem]' . ($courseHasPromo ? ' app-price--promo' : '');
              ?>
              <article class="home-card-hover home-course-shadow relative isolate flex h-full flex-col overflow-hidden rounded-[26px] border border-slate-200 bg-white" data-home-reveal="card" data-home-delay="<?php echo e((string) ($courseIndex * 95)); ?>">
                <a
                  href="<?php echo e($courseDetailUrl); ?>"
                  class="absolute inset-0 z-10 block rounded-[26px] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                  aria-label="Ouvrir la fiche du cours <?php echo e((string) ($courseCard['title'] ?? 'Cours')); ?>"
                ></a>
                <div class="relative">
                  <img
                    src="<?php echo e((string) ($courseCard['cover'] ?? '')); ?>"
                    alt="<?php echo e((string) ($courseCard['coverAlt'] ?? 'Cours')); ?>"
                    class="h-40 w-full object-cover"
                    loading="lazy"
                  >
                  <div class="home-course-category-badge">
                    <?php echo e($courseCategory !== '' ? $courseCategory : 'Formation'); ?>
                  </div>
                  <span class="absolute right-3 top-3 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-slate-500 shadow-sm dark:bg-slate-900/85 dark:text-slate-100" aria-hidden="true">
                    <i class="<?php echo e(!empty($courseCard['isFavorite']) ? 'fas' : 'far'); ?> fa-heart"></i>
                  </span>
                </div>

                <div class="relative z-0 flex flex-1 flex-col p-4">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-200">
                      <i class="fas fa-chalkboard-teacher text-xs" aria-hidden="true"></i>
                    </span>
                    <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-100"><?php echo e((string) ($courseCard['teacher'] ?? 'Formateur')); ?></p>
                  </div>

                  <h3 class="home-card-title home-line-clamp-2 mt-3 text-[1rem] font-extrabold leading-[1.28] text-slate-900 sm:text-[1.08rem]">
                    <?php echo e((string) ($courseCard['title'] ?? 'Cours')); ?>
                  </h3>

                  <?php if ($courseLevel !== '' || ($courseDurationMin > 0 && $courseDuration !== '')): ?>
                    <div class="mt-2 flex flex-wrap gap-2">
                      <?php if ($courseLevel !== ''): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                          <i class="fas fa-signal text-[10px]" aria-hidden="true"></i>
                          <?php echo e($courseLevel); ?>
                        </span>
                      <?php endif; ?>
                      <?php if ($courseDurationMin > 0 && $courseDuration !== ''): ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700">
                          <i class="far fa-clock text-[10px]" aria-hidden="true"></i>
                          <?php echo e($courseDuration); ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="mt-auto flex items-end justify-between gap-4 border-t border-slate-100 pt-4">
                    <div class="min-w-0">
                      <p class="<?php echo e($coursePriceClass); ?>"><?php echo e($priceLabel); ?></p>
                    </div>
                    <a href="<?php echo e($courseCatalogUrl); ?>" class="relative z-20 inline-flex h-11 min-w-[7.25rem] shrink-0 items-center justify-center rounded-full bg-blue-600 px-4 text-sm font-bold text-white transition hover:bg-blue-700" aria-label="Aller au catalogue" title="Catalogue">
                      Catalogue
                    </a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="rounded-[24px] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
              <p class="text-lg font-semibold text-slate-800">Aucune nouveauté n’est encore publiée.</p>
              <p class="mt-2 text-sm text-slate-500">Les prochains cours apparaîtront ici dès qu’ils seront mis en ligne.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <?php if ($homePopularCourses !== []): ?>
    <section class="py-8 lg:py-10" data-home-reveal="up" data-home-delay="80">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="home-section-header mb-6" data-home-reveal="up" data-home-delay="0">
          <div class="home-section-header__spacer" aria-hidden="true"></div>
          <div class="home-section-header__content">
            <span class="home-section-eyebrow">Les favoris du moment</span>
            <h2 class="home-section-title" data-home-reveal="title" data-home-delay="80">Les cours les plus choisis</h2>
            <p class="mt-3 max-w-4xl text-lg leading-8 text-slate-500">Les formations qui attirent le plus d’apprenants et qui donnent envie de commencer tout de suite.</p>
          </div>
          <a href="<?php echo e($catalogueUrl); ?>" class="home-section-header__action inline-flex items-center gap-3 text-base font-semibold text-blue-600 transition hover:text-blue-700 lg:shrink-0">
            Voir tout le catalogue
            <i class="fas fa-chevron-right text-sm" aria-hidden="true"></i>
          </a>
        </div>

        <div class="home-panel-shadow home-section-panel rounded-[32px] p-4 sm:p-5 lg:p-6" data-home-reveal="scale" data-home-delay="120" data-home-ambient="drift" data-home-ambient-duration="19" data-home-ambient-delay="160">
          <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
          <?php foreach ($homePopularCourses as $courseIndex => $courseCard): ?>
            <?php
            $currentPrice = max(0, (int) ($courseCard['priceRaw'] ?? 0));
            $basePrice = max($currentPrice, (int) ($courseCard['basePriceXof'] ?? $currentPrice));
            $priceLabel = $currentPrice > 0 ? $publicFormatMoney($currentPrice) : 'Gratuit';
            $courseDetailUrl = trim((string) ($courseCard['url'] ?? $catalogueUrl));
            $courseCatalogUrl = $catalogueUrl;
            $courseCategory = trim((string) ($courseCard['category'] ?? 'Formation'));
            $courseDuration = trim((string) ($courseCard['durationLabel'] ?? 'À votre rythme'));
            $courseDurationMin = max(0, (int) ($courseCard['durationMin'] ?? 0));
            $courseLevel = trim((string) ($courseCard['levelLabel'] ?? 'Tous niveaux'));
            $courseHasPromo = $basePrice > $currentPrice && $currentPrice > 0;
            $coursePriceClass = 'app-price text-[1.05rem] font-black tracking-tight sm:text-[1.2rem]' . ($courseHasPromo ? ' app-price--promo' : '');
            ?>
            <article class="home-card-hover home-course-shadow relative isolate flex h-full flex-col overflow-hidden rounded-[26px] border border-slate-200 bg-white" data-home-reveal="card" data-home-delay="<?php echo e((string) ($courseIndex * 95)); ?>">
              <a
                href="<?php echo e($courseDetailUrl); ?>"
                class="absolute inset-0 z-10 block rounded-[26px] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white"
                aria-label="Ouvrir la fiche du cours <?php echo e((string) ($courseCard['title'] ?? 'Cours')); ?>"
              ></a>
              <div class="relative">
                <img
                  src="<?php echo e((string) ($courseCard['cover'] ?? '')); ?>"
                  alt="<?php echo e((string) ($courseCard['coverAlt'] ?? 'Cours')); ?>"
                  class="h-40 w-full object-cover"
                  loading="lazy"
                >
                <div class="home-course-category-badge">
                  <?php echo e($courseCategory !== '' ? $courseCategory : 'Formation'); ?>
                </div>
                <span class="absolute right-3 top-3 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-slate-500 shadow-sm dark:bg-slate-900/85 dark:text-slate-100" aria-hidden="true">
                  <i class="<?php echo e(!empty($courseCard['isFavorite']) ? 'fas' : 'far'); ?> fa-heart"></i>
                </span>
              </div>

              <div class="relative z-0 flex flex-1 flex-col p-4">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-200">
                    <i class="fas fa-chalkboard-teacher text-xs" aria-hidden="true"></i>
                  </span>
                  <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-100"><?php echo e((string) ($courseCard['teacher'] ?? 'Formateur')); ?></p>
                </div>

                  <h3 class="home-card-title home-line-clamp-2 mt-3 text-[1rem] font-extrabold leading-[1.28] text-slate-900 sm:text-[1.08rem]">
                  <?php echo e((string) ($courseCard['title'] ?? 'Cours')); ?>
                </h3>

                <?php if ($courseLevel !== '' || ($courseDurationMin > 0 && $courseDuration !== '')): ?>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <?php if ($courseLevel !== ''): ?>
                      <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <i class="fas fa-signal text-[10px]" aria-hidden="true"></i>
                        <?php echo e($courseLevel); ?>
                      </span>
                    <?php endif; ?>
                    <?php if ($courseDurationMin > 0 && $courseDuration !== ''): ?>
                      <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700">
                        <i class="far fa-clock text-[10px]" aria-hidden="true"></i>
                        <?php echo e($courseDuration); ?>
                      </span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <div class="mt-auto flex items-end justify-between gap-4 border-t border-slate-100 pt-4">
                  <div class="min-w-0">
                    <p class="<?php echo e($coursePriceClass); ?>"><?php echo e($priceLabel); ?></p>
                    <?php if ($basePrice > $currentPrice && $currentPrice > 0): ?>
                      <p class="mt-1 text-xs text-slate-400 line-through"><?php echo e($publicFormatMoney($basePrice)); ?></p>
                    <?php endif; ?>
                  </div>
                  <a href="<?php echo e($courseCatalogUrl); ?>" class="relative z-20 inline-flex h-11 min-w-[7.25rem] shrink-0 items-center justify-center rounded-full bg-blue-600 px-4 text-sm font-bold text-white transition hover:bg-blue-700" aria-label="Aller au catalogue" title="Catalogue">
                    Catalogue
                  </a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    </section>
    <?php endif; ?>

    <section class="home-section-anchor bg-slate-50/70 py-10 lg:py-12" id="faq" data-home-reveal="up" data-home-delay="80">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="mb-6 text-center" data-home-reveal="up" data-home-delay="0">
          <span class="home-section-eyebrow">FAQ</span>
          <h2 class="home-section-title" data-home-reveal="title" data-home-delay="80">Tout savoir avant de commencer</h2>
          <p class="mx-auto mt-3 max-w-3xl text-lg leading-8 text-slate-500 sm:text-[1.15rem]">Des réponses simples pour démarrer sereinement et sans perdre de temps.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
          <?php foreach ($faqItems as $faqIndex => $faqItem): ?>
            <details class="faq-item rounded-[24px] border border-slate-200 bg-white px-5 py-4 home-soft-shadow" data-home-reveal="up" data-home-delay="<?php echo e((string) ($faqIndex * 90)); ?>"<?php echo $faqIndex === 0 ? ' open' : ''; ?>>
              <summary class="flex cursor-pointer list-none items-start justify-between gap-4">
                <span class="text-left text-[0.98rem] font-bold text-slate-900"><?php echo e((string) ($faqItem['question'] ?? 'Question')); ?></span>
                <span class="faq-chevron inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-500 transition-transform">
                  <i class="fas fa-chevron-down text-xs" aria-hidden="true"></i>
                </span>
              </summary>
              <p class="mt-3 pr-8 text-sm leading-6 text-slate-600"><?php echo e((string) ($faqItem['answer'] ?? '')); ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="py-10 lg:py-12" data-home-reveal="scale" data-home-delay="100">
      <div class="mx-auto max-w-[1400px] px-4 sm:px-6 xl:px-8">
        <div class="home-card-shadow relative isolate overflow-hidden rounded-[32px] bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 px-6 py-10 text-white sm:px-10 lg:px-14 lg:py-12" data-home-ambient="pulse" data-home-ambient-duration="10" data-home-ambient-delay="140">
          <div class="max-w-[42rem]">
            <h2 class="text-[2rem] font-black leading-tight sm:text-[2.6rem]" data-home-reveal="title" data-home-delay="100">Prêt à apprendre quelque chose d’utile dès aujourd’hui&nbsp;?</h2>
            <p class="mt-5 text-lg leading-8 text-blue-100">Choisissez un cours, lancez-vous et progressez à votre rythme avec des contenus concrets.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
              <a href="<?php echo e($homePrimaryCtaUrl); ?>" class="home-cta-button inline-flex items-center justify-center rounded-2xl px-6 py-3.5 text-sm font-bold transition">
                <?php echo e($homePrimaryCtaLabel); ?>
              </a>
              <a href="<?php echo e($catalogueUrl); ?>" class="home-cta-button home-cta-button--secondary inline-flex items-center justify-center rounded-2xl px-6 py-3.5 text-sm font-bold transition">
                Explorer le catalogue
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <div class="fixed bottom-4 right-4 z-40 flex flex-col items-end gap-2 sm:bottom-5 sm:right-5 sm:gap-3">
    <a
      href="<?php echo e($supportUrl); ?>"
      data-support-url="<?php echo e($supportUrl); ?>"
      data-support-fab="1"
      class="pointer-events-none relative inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-600 text-white opacity-0 shadow-[0_18px_35px_-15px_rgba(37,99,235,0.9)] transform-gpu translate-y-3 scale-95 transition-all duration-300 ease-out hover:bg-blue-700 sm:pointer-events-auto sm:h-12 sm:w-12 sm:translate-y-0 sm:scale-100 sm:opacity-100"
      aria-label="Assistance"
      title="Assistance"
    >
      <i class="fas fa-headset" aria-hidden="true"></i>
    </a>
    <button
      type="button"
      class="pointer-events-none inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-600 text-white opacity-0 shadow-[0_18px_35px_-15px_rgba(37,99,235,0.9)] transform-gpu translate-y-3 scale-95 transition-all duration-300 ease-out hover:bg-blue-700 dark:bg-blue-500 dark:text-white dark:hover:bg-blue-600 sm:h-12 sm:w-12"
      data-scroll-top
      aria-label="Remonter en haut"
      title="Remonter en haut"
    >
      <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </button>
  </div>

  <?php require __DIR__ . '/_public_footer_home.php'; ?>
</div>
</body>
</html>
