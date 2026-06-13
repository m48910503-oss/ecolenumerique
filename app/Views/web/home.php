<?php
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../View/helpers.php';

/** @var array<int,array<string,mixed>> $ssrCourses */
$ssrCourses = is_array($ssrCourses ?? null) ? $ssrCourses : [];
$homeTestimonials = is_array($homeTestimonials ?? null) ? $homeTestimonials : [];
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

$headerNotificationCount = max(0, (int) ($totalUnread ?? 0));
$headerLoginLabel = 'Se connecter';
$headerLoginUrl = $loginUrl;
$headerPrimaryLabel = $isLoggedIn ? 'Tableau de bord' : 'Commencer';
$headerPrimaryUrl = $isLoggedIn ? $dashboardUrl : $registerUrl;
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

$brandParts = preg_split('/\s+/', trim($platformName), 2) ?: [$platformName];
$brandLead = trim((string) ($brandParts[0] ?? $platformName));
$brandAccent = trim((string) ($brandParts[1] ?? ''));

$makeCoverPlaceholder = static function (string $title, string $category): string {
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeCategory = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1d4ed8"/>
      <stop offset="100%" stop-color="#06b6d4"/>
    </linearGradient>
  </defs>
  <rect width="800" height="480" fill="url(#g)"/>
  <circle cx="640" cy="120" r="110" fill="rgba(255,255,255,0.08)"/>
  <circle cx="120" cy="400" r="130" fill="rgba(255,255,255,0.06)"/>
  <text x="56" y="92" fill="#dbeafe" font-size="28" font-family="Arial, sans-serif">{$safeCategory}</text>
  <text x="56" y="180" fill="#ffffff" font-size="46" font-weight="700" font-family="Arial, sans-serif">{$safeTitle}</text>
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
$fallbackCourseCards = array_values(array_map($decorateHomeCourse, [
    [
        'id' => 0,
        'url' => site_url('catalogue'),
        'buyUrl' => site_url('catalogue'),
        'title' => 'Développement Web',
        'description' => 'HTML, CSS, JavaScript, React et Node.js, du niveau débutant à avancé. Projets pratiques inclus.',
        'teacher' => 'Sophie Durand',
        'category' => 'Développement',
        'rating' => 0,
        'ratingCount' => 0,
        'priceXof' => 79000,
        'basePriceXof' => 149000,
        'promoActive' => true,
        'cover' => 'https://images.pexels.com/photos/546819/pexels-photo-546819.jpeg?auto=compress&cs=tinysrgb&w=600',
        'coverAlt' => 'Cours de développement web',
        'durationLabel' => '42 h de vidéo',
        'durationMin' => 2520,
        'level' => 'beginner',
        'createdAt' => '2026-04-06 10:00:00',
        'rating' => 0,
        'ratingCount' => 0,
        'enrolledCount' => 0,
    ],
    [
        'id' => 0,
        'url' => site_url('catalogue'),
        'buyUrl' => site_url('catalogue'),
        'title' => 'Intelligence artificielle',
        'description' => 'Machine Learning, Deep Learning et NLP avec Python et TensorFlow. Projets réels.',
        'teacher' => 'Dr. Karim Benali',
        'category' => 'Data Science',
        'rating' => 0,
        'ratingCount' => 0,
        'priceXof' => 99000,
        'basePriceXof' => 199000,
        'promoActive' => true,
        'cover' => 'https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg?auto=compress&cs=tinysrgb&w=600',
        'coverAlt' => 'Cours d’intelligence artificielle',
        'durationLabel' => '56 h de vidéo',
        'durationMin' => 3360,
        'level' => 'intermediate',
        'createdAt' => '2026-04-08 08:30:00',
        'rating' => 0,
        'ratingCount' => 0,
        'enrolledCount' => 0,
    ],
    [
        'id' => 0,
        'url' => site_url('catalogue'),
        'buyUrl' => site_url('catalogue'),
        'title' => 'Marketing digital',
        'description' => 'SEO, SEA, Social Media et Growth Hacking avec des stratégies concrètes.',
        'teacher' => 'Julie Leroy',
        'category' => 'Marketing',
        'rating' => 0,
        'ratingCount' => 0,
        'priceXof' => 89000,
        'basePriceXof' => 169000,
        'promoActive' => true,
        'cover' => 'https://images.pexels.com/photos/905163/pexels-photo-905163.jpeg?auto=compress&cs=tinysrgb&w=600',
        'coverAlt' => 'Cours de marketing digital',
        'durationLabel' => '38 h de vidéo',
        'durationMin' => 2280,
        'level' => 'beginner',
        'createdAt' => '2026-04-03 09:00:00',
        'rating' => 0,
        'ratingCount' => 0,
        'enrolledCount' => 0,
    ],
]));

if (count($courseCards) < 3) {
    foreach ($fallbackCourseCards as $fallbackCourseCard) {
        $courseCards[] = $fallbackCourseCard;
        if (count($courseCards) >= 3) {
            break;
        }
    }
}

$categoryIconFor = static function (string $label) use ($publicNormalizeKey): string {
    $key = $publicNormalizeKey($label);

    if ($key === '' || str_contains($key, 'dev') || str_contains($key, 'code') || str_contains($key, 'web')) {
        return 'fa-code';
    }
    if (str_contains($key, 'data') || str_contains($key, 'science') || str_contains($key, 'analyse')) {
        return 'fa-chart-pie';
    }
    if (str_contains($key, 'design') || str_contains($key, 'ux') || str_contains($key, 'ui')) {
        return 'fa-paint-brush';
    }
    if (str_contains($key, 'marketing') || str_contains($key, 'business') || str_contains($key, 'vente')) {
        return 'fa-bullhorn';
    }
    if (str_contains($key, 'cloud') || str_contains($key, 'devops')) {
        return 'fa-cloud';
    }
    if (str_contains($key, 'cyber') || str_contains($key, 'secur')) {
        return 'fa-shield-alt';
    }
    if (str_contains($key, 'ia') || str_contains($key, 'ai') || str_contains($key, 'machine')) {
        return 'fa-robot';
    }

    return 'fa-graduation-cap';
};

$categoryPaletteFor = static function (string $label) use ($publicNormalizeKey): array {
    $key = $publicNormalizeKey($label);

    if ($key === '' || str_contains($key, 'dev') || str_contains($key, 'code') || str_contains($key, 'web')) {
        return ['gradient' => 'from-blue-500 to-indigo-600'];
    }
    if (str_contains($key, 'data') || str_contains($key, 'science') || str_contains($key, 'analyse') || str_contains($key, 'ia') || str_contains($key, 'ai') || str_contains($key, 'machine')) {
        return ['gradient' => 'from-emerald-500 to-teal-600'];
    }
    if (str_contains($key, 'design') || str_contains($key, 'ux') || str_contains($key, 'ui')) {
        return ['gradient' => 'from-violet-500 to-indigo-500'];
    }
    if (str_contains($key, 'marketing') || str_contains($key, 'business') || str_contains($key, 'vente') || str_contains($key, 'communication')) {
        return ['gradient' => 'from-amber-500 to-orange-500'];
    }
    if (str_contains($key, 'cloud') || str_contains($key, 'devops')) {
        return ['gradient' => 'from-sky-500 to-cyan-500'];
    }
    if (str_contains($key, 'cyber') || str_contains($key, 'secur')) {
        return ['gradient' => 'from-gray-700 to-gray-900'];
    }

    return ['gradient' => 'from-blue-600 to-cyan-600'];
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
        $icons .= '<i class="fas fa-star text-yellow-400 text-xs"></i>';
        $rendered++;
    }

    if ($hasHalfStar && $rendered < 5) {
        $icons .= '<i class="fas fa-star-half-alt text-yellow-400 text-xs"></i>';
        $rendered++;
    }

    while ($rendered < 5) {
        $icons .= '<i class="far fa-star text-yellow-400 text-xs"></i>';
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
        'gradient' => $categoryPaletteFor($label)['gradient'],
        'name' => $label,
        'count' => view_pluralize((int) $count, 'formation', 'formations'),
    ];

    if (count($categoryCarouselItems) >= 7) {
        break;
    }
}

$popularCourses = $courseCards;
usort(
    $popularCourses,
    static function (array $left, array $right): int {
        return [
            (int) ($right['ratingCount'] ?? 0),
            (float) ($right['rating'] ?? 0),
            (int) ($right['createdTs'] ?? 0),
        ] <=> [
            (int) ($left['ratingCount'] ?? 0),
            (float) ($left['rating'] ?? 0),
            (int) ($left['createdTs'] ?? 0),
        ];
    }
);
$popularCourses = array_slice($popularCourses, 0, 3);

$resolveCourseBadge = static function (array $course): array {
    if (!empty($course['promoActive'])) {
        return [
            'label' => 'Tendance',
            'class' => 'text-gray-800 dark:text-gray-200',
            'icon' => 'fa-chart-line text-emerald-500',
        ];
    }

    if ((int) ($course['ratingCount'] ?? 0) >= 20) {
        return [
            'label' => 'Populaire',
            'class' => 'text-gray-800 dark:text-gray-200',
            'icon' => 'fa-fire text-blue-500',
        ];
    }

    return [
        'label' => 'Nouveau',
        'class' => 'text-gray-800 dark:text-gray-200',
        'icon' => 'fa-bolt text-cyan-500',
    ];
};

$resolveLevelClass = static function (string $levelKey): string {
    return match (strtolower(trim($levelKey))) {
        'beginner' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300',
        'intermediate' => 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-800 dark:text-cyan-300',
        'advanced' => 'bg-violet-100 dark:bg-violet-900/40 text-violet-800 dark:text-violet-300',
        default => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200',
    };
};

$homeCoursePrimaryActionLabel = $isLoggedIn ? 'Acheter' : 'Commencer';
$homeCoursePrimaryActionIcon = $isLoggedIn ? 'fa-cart-shopping' : 'fa-circle-play';
$heroPrimaryUrl = $isLoggedIn ? $dashboardUrl : $registerUrl;
$heroPrimaryLabel = $isLoggedIn ? 'Accéder au tableau de bord' : 'Commencer gratuitement';
$heroSecondaryUrl = $catalogueUrl;
$heroSecondaryLabel = 'Explorer les formations';
$heroHasEnrollmentStat = $activeEnrollmentCount > 0;
$heroEnrollmentLabel = number_format($activeEnrollmentCount, 0, ',', ' ');
$heroBadgeLabel = $heroHasEnrollmentStat
    ? '+' . $heroEnrollmentLabel . ' inscriptions actives'
    : '+0 inscriptions actives';
$heroQuickStats = [
    ['icon' => 'fa-language', 'label' => 'Formations en français'],
    ['icon' => 'fa-mobile-screen-button', 'label' => 'Accès mobile 24 h/24'],
    ['icon' => 'fa-wallet', 'label' => 'Paiement Mobile Money'],
];

$trustBandItems = [
    [
        'icon' => 'fa-users',
        'title' => 'Formations en français',
        'text' => 'Claires, pratiques et accessibles',
    ],
    [
        'icon' => 'fa-book-open',
        'title' => 'Accès mobile',
        'text' => 'Depuis votre téléphone ou votre ordinateur',
    ],
    [
        'icon' => 'fa-certificate',
        'title' => 'Certificat disponible',
        'text' => 'Pour valoriser vos acquis',
    ],
    [
        'icon' => 'fa-wallet',
        'title' => 'Paiement Mobile Money',
        'text' => 'Simple selon les cours payants',
    ],
    [
        'icon' => 'fa-comments',
        'title' => 'Support WhatsApp',
        'text' => 'Accompagnement rapide si besoin',
    ],
];

$whyItems = [
    [
        'icon' => 'fa-briefcase',
        'iconWrap' => 'bg-blue-100 dark:bg-blue-500/10',
        'iconClass' => 'text-blue-700 dark:text-blue-300',
        'title' => 'Cours pratiques',
        'text' => 'Des contenus orientés action, avec des exercices et des cas concrets.',
    ],
    [
        'icon' => 'fa-mobile-screen-button',
        'iconWrap' => 'bg-cyan-100 dark:bg-cyan-500/10',
        'iconClass' => 'text-cyan-700 dark:text-cyan-300',
        'title' => 'Pensé pour le mobile',
        'text' => 'Apprenez depuis votre téléphone ou votre ordinateur, selon votre disponibilité.',
    ],
    [
        'icon' => 'fa-wallet',
        'iconWrap' => 'bg-blue-100 dark:bg-blue-500/10',
        'iconClass' => 'text-blue-700 dark:text-blue-300',
        'title' => 'Certificat valorisable',
        'text' => 'Obtenez une preuve de compétence à partager sur votre CV ou votre profil.',
    ],
    [
        'icon' => 'fa-comments',
        'iconWrap' => 'bg-orange-100 dark:bg-orange-500/10',
        'iconClass' => 'text-orange-600 dark:text-orange-300',
        'title' => 'Support accessible',
        'text' => 'Bénéficiez d’un accompagnement simple, avec la possibilité d’un support via WhatsApp.',
    ],
];

$howItWorksItems = [
    [
        'step' => '1',
        'title' => 'Choisissez une formation',
        'text' => 'Parcourez le catalogue et trouvez la compétence qui correspond à votre objectif.',
    ],
    [
        'step' => '2',
        'title' => 'Créez votre compte',
        'text' => 'En quelques clics, votre espace est prêt pour suivre vos cours et garder vos progrès.',
    ],
    [
        'step' => '3',
        'title' => 'Apprenez à votre rythme',
        'text' => 'Suivez les vidéos, réalisez les exercices et progressez étape par étape.',
    ],
];

$testimonialItems = array_values(array_filter(
    array_map(
        static function (array $testimonial): array {
            return [
                'name' => trim((string) ($testimonial['name'] ?? '')),
                'role' => trim((string) ($testimonial['role'] ?? '')),
                'quote' => trim((string) ($testimonial['quote'] ?? '')),
                'rating' => max(0.0, min(5.0, (float) ($testimonial['rating'] ?? 0))),
                'initials' => trim((string) ($testimonial['initials'] ?? 'EN')),
            ];
        },
        $homeTestimonials
    ),
    static fn(array $testimonial): bool => $testimonial['name'] !== '' && $testimonial['quote'] !== '' && $testimonial['rating'] > 0
));
$showTestimonials = count($testimonialItems) >= 3;

$faqItems = [
    [
        'question' => 'Les cours sont-ils accessibles sur téléphone ?',
        'answer' => 'Oui. La plateforme est pensée pour le mobile afin de suivre les cours facilement depuis un smartphone, même avec une connexion moyenne.',
    ],
    [
        'question' => 'Est-ce que je reçois un certificat ?',
        'answer' => 'Oui, selon la formation. Lorsque le parcours prévoit une validation, vous pouvez obtenir un certificat pour valoriser vos acquis.',
    ],
    [
        'question' => 'Comment payer une formation ?',
        'answer' => 'Les formations payantes peuvent être réglées simplement en ligne, avec une expérience adaptée au Mobile Money via FedaPay.',
    ],
    [
        'question' => 'Puis-je suivre les cours à mon rythme ?',
        'answer' => 'Oui. Vous avancez à votre rythme, selon votre emploi du temps, depuis votre téléphone ou votre ordinateur.',
    ],
    [
        'question' => 'Que faire si j’ai un problème ?',
        'answer' => 'Vous pouvez contacter le support pour être aidé rapidement. L’accompagnement reste pensé pour être simple et accessible, y compris via WhatsApp.',
    ],
    [
        'question' => 'Les cours sont-ils en français ?',
        'answer' => 'Oui. Les parcours sont présentés en français avec une approche claire, pratique et adaptée aux besoins du marché francophone.',
    ],
    [
        'question' => 'Y a-t-il des formations gratuites ?',
        'answer' => 'Oui, certains contenus ou parcours d’introduction peuvent être accessibles gratuitement avant de passer à une formation payante.',
    ],
];

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
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($homePageJsUrl); ?>" defer></script>
  <script src="<?php echo e($marketingJsUrl); ?>" defer></script>
  <style>
    .hover-lift {
      transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }
    .hover-lift:hover {
      transform: translateY(-6px);
      box-shadow: 0 24px 50px -24px rgba(59, 130, 246, 0.28);
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
    .home-anchor {
      scroll-margin-top: 7rem;
    }
    .surface-card {
      border: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(15, 23, 42, 0.72);
      backdrop-filter: blur(18px);
      box-shadow: 0 24px 60px -30px rgba(2, 6, 23, 0.7);
    }
    .surface-card-light {
      border: 1px solid rgba(148, 163, 184, 0.18);
      background: rgba(255, 255, 255, 0.88);
      box-shadow: 0 24px 50px -34px rgba(15, 23, 42, 0.28);
    }
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .line-clamp-3 {
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
<body class="overflow-x-hidden bg-gray-50 font-sans antialiased text-slate-900 transition-colors duration-200 dark:bg-gray-950 dark:text-slate-100" data-view="web/home">

<?php require __DIR__ . '/_public_header_catalogue.php'; ?>

<main class="overflow-x-hidden bg-gray-50 dark:bg-gray-950">
  <section class="relative overflow-hidden bg-gradient-to-br from-white via-gray-50 to-gray-100 pb-20 pt-12 text-slate-900 sm:pb-24 sm:pt-16 dark:from-gray-950 dark:via-gray-950 dark:to-gray-900 dark:text-white lg:pb-28 lg:pt-20">
    <div class="absolute inset-0 opacity-90 dark:opacity-80">
      <div class="absolute -left-24 top-24 h-72 w-72 rounded-full bg-blue-500/10 blur-3xl dark:bg-blue-600/20"></div>
      <div class="absolute right-[-5rem] top-10 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl dark:bg-cyan-500/20"></div>
      <div class="absolute bottom-[-8rem] left-1/3 h-80 w-80 rounded-full bg-blue-200/40 blur-3xl dark:bg-blue-900/30"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="min-w-0">
          <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/90 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm shadow-blue-100/80 backdrop-blur dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:shadow-none">
            <i class="fas fa-users text-cyan-500 dark:text-cyan-300" aria-hidden="true"></i>
            <?php echo e($heroBadgeLabel); ?>
          </span>
          <h1 class="mt-6 max-w-3xl text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl lg:text-4xl">
            Apprenez une
            <span class="gradient-text">compétence utile</span>,
            à votre rythme.
          </h1>
          <p class="mt-5 max-w-2xl text-base text-slate-600 dark:text-slate-300 sm:text-lg">
            Des formations pratiques en français pour progresser rapidement, obtenir un certificat et avancer dans votre carrière.
          </p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="<?php echo e($heroPrimaryUrl); ?>" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:-translate-y-0.5 hover:bg-blue-500">
              <i class="fas fa-circle-play mr-2" aria-hidden="true"></i>
              <?php echo e($heroPrimaryLabel); ?>
            </a>
            <a href="<?php echo e($heroSecondaryUrl); ?>" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-900 shadow-sm transition hover:border-blue-300 hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-blue-400/40 dark:hover:bg-white/10">
              <i class="fas fa-book-open mr-2" aria-hidden="true"></i>
              <?php echo e($heroSecondaryLabel); ?>
            </a>
          </div>
          <div class="mt-8 flex flex-wrap gap-3">
            <?php foreach ($heroQuickStats as $heroQuickStat): ?>
              <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:shadow-none">
                <i class="fas <?php echo e((string) ($heroQuickStat['icon'] ?? 'fa-circle')); ?> text-cyan-500 dark:text-cyan-300" aria-hidden="true"></i>
                <?php echo e((string) ($heroQuickStat['label'] ?? '')); ?>
              </span>
            <?php endforeach; ?>
          </div>
          <div class="mt-6 flex flex-wrap gap-3">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-700 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:shadow-none">
              <i class="fas fa-certificate text-orange-500 dark:text-orange-300" aria-hidden="true"></i>
              Certificat disponible
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-700 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5 dark:text-slate-200 dark:shadow-none">
              <i class="fas fa-comments text-emerald-500 dark:text-emerald-300" aria-hidden="true"></i>
              Support simple si besoin
            </span>
          </div>
        </div>

        <div class="relative mx-auto w-full max-w-[560px]">
          <div class="absolute -left-4 top-6 z-20 hidden rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-sm text-slate-700 shadow-lg shadow-slate-200/60 backdrop-blur md:flex md:items-center md:gap-2 dark:border-white/10 dark:bg-gray-900/90 dark:text-slate-100 dark:shadow-black/30">
            <i class="fas fa-language text-cyan-500 dark:text-cyan-300" aria-hidden="true"></i>
            Formations en français
          </div>
          <div class="absolute -right-4 top-16 z-20 hidden rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-sm text-slate-700 shadow-lg shadow-slate-200/60 backdrop-blur md:flex md:items-center md:gap-2 dark:border-white/10 dark:bg-gray-900/90 dark:text-slate-100 dark:shadow-black/30">
            <i class="fas fa-wallet text-blue-500 dark:text-blue-300" aria-hidden="true"></i>
            Paiement Mobile Money
          </div>
          <div class="rounded-[30px] border border-slate-200 bg-white/80 p-3 shadow-2xl shadow-slate-200/70 backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/35">
            <div class="relative overflow-hidden rounded-[24px] border border-slate-200/80 dark:border-white/10">
              <img
                src="https://images.pexels.com/photos/4145190/pexels-photo-4145190.jpeg?auto=compress&cs=tinysrgb&w=1200"
                alt="Apprentissage en ligne"
                class="aspect-[4/3] w-full object-cover"
                fetchpriority="high"
              >
              <div class="absolute inset-0 bg-gradient-to-tr from-gray-900/15 via-transparent to-transparent dark:from-gray-950/40 dark:via-gray-950/10"></div>
            </div>
          </div>
          <div class="absolute -bottom-5 left-5 z-20 hidden rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-sm text-slate-700 shadow-lg shadow-slate-200/60 backdrop-blur md:flex md:items-center md:gap-2 dark:border-white/10 dark:bg-gray-900/90 dark:text-slate-100 dark:shadow-black/30">
            <i class="fas fa-certificate text-orange-500 dark:text-orange-300" aria-hidden="true"></i>
            Certificat disponible
          </div>
          <div class="absolute bottom-8 right-6 z-20 hidden rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-sm text-slate-700 shadow-lg shadow-slate-200/60 backdrop-blur lg:flex lg:items-center lg:gap-2 dark:border-white/10 dark:bg-gray-900/90 dark:text-slate-100 dark:shadow-black/30">
            <i class="fas fa-comments text-emerald-500 dark:text-emerald-300" aria-hidden="true"></i>
            Support WhatsApp
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="relative -mt-8 pb-6 sm:-mt-10 sm:pb-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="rounded-3xl border border-slate-200/70 bg-white/90 p-5 shadow-xl shadow-slate-200/40 backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20 sm:p-6">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
          <?php foreach ($trustBandItems as $trustItem): ?>
            <div class="rounded-2xl border border-slate-200/70 bg-gray-50/90 px-4 py-4 dark:border-white/10 dark:bg-gray-900/70">
              <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 shadow-sm shadow-blue-100/70 dark:bg-blue-500/10 dark:text-blue-300 dark:shadow-none">
                  <i class="fas <?php echo e((string) ($trustItem['icon'] ?? 'fa-check')); ?>" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo e((string) ($trustItem['title'] ?? 'Atout')); ?></p>
                  <p class="mt-1 text-sm text-slate-600 dark:text-slate-300"><?php echo e((string) ($trustItem['text'] ?? '')); ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="home-anchor relative py-20 sm:py-24 lg:py-28 bg-gray-100/80 dark:bg-gray-900/60" id="popular-courses">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-3xl text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm shadow-blue-100/70 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:shadow-none">Catalogue premium</span>
        <h2 class="mt-5 text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl lg:text-3xl">Formations populaires</h2>
        <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300">Retrouvez les parcours les plus consultés pour passer plus vite à l’action.</p>
      </div>

      <div class="mt-14 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($popularCourses as $popularCourse): ?>
          <?php
          $badge = $resolveCourseBadge($popularCourse);
          $currentPrice = max(0, (int) ($popularCourse['priceRaw'] ?? 0));
          $basePrice = max($currentPrice, (int) ($popularCourse['basePriceXof'] ?? $currentPrice));
          $teacherLabel = trim((string) ($popularCourse['teacher'] ?? 'Formateur'));
          $ratingValue = max(0, (float) ($popularCourse['rating'] ?? 0));
          $ratingCount = max(0, (int) ($popularCourse['ratingCount'] ?? 0));
          $courseUrl = trim((string) ($popularCourse['url'] ?? $catalogueUrl));
          $buyUrl = trim((string) ($popularCourse['buyUrl'] ?? $courseUrl));
          $levelLabel = trim((string) ($popularCourse['levelLabel'] ?? 'Tous niveaux'));
          $levelKey = trim((string) ($popularCourse['levelKey'] ?? ''));
          $enrolledCount = max(0, (int) ($popularCourse['enrolledCount'] ?? 0));
          ?>
          <article class="group flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 shadow-xl shadow-slate-200/30 transition-all duration-300 hover:-translate-y-1 hover:border-blue-300/60 hover:shadow-2xl hover:shadow-blue-500/10 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20 dark:hover:border-blue-400/40">
            <div class="relative overflow-hidden">
              <img
                src="<?php echo e((string) ($popularCourse['cover'] ?? '')); ?>"
                alt="<?php echo e((string) ($popularCourse['coverAlt'] ?? 'Cours')); ?>"
                class="aspect-[16/9] w-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
              >
              <div class="absolute inset-0 bg-gradient-to-t from-gray-950/70 via-gray-950/10 to-transparent"></div>
              <span class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-gray-950/80 px-3 py-1.5 text-xs font-bold text-white backdrop-blur">
                <i class="fas <?php echo e((string) ($badge['icon'] ?? 'fa-fire')); ?>" aria-hidden="true"></i>
                <?php echo e((string) ($badge['label'] ?? 'Populaire')); ?>
              </span>
              <a href="<?php echo e($courseUrl); ?>" class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-gray-950/75 text-white backdrop-blur transition hover:bg-blue-600" title="Voir les détails" aria-label="Voir les détails">
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>

            <div class="flex flex-1 flex-col p-6">
              <div class="flex items-start justify-between gap-4">
                <h3 class="line-clamp-2 min-w-0 text-lg font-bold text-slate-900 dark:text-white sm:text-xl"><?php echo e((string) ($popularCourse['title'] ?? 'Cours')); ?></h3>
                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold <?php echo e($resolveLevelClass($levelKey)); ?>"><?php echo e($levelLabel); ?></span>
              </div>

              <div class="mt-4 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gray-200/80 text-slate-700 dark:bg-gray-800 dark:text-slate-200">
                  <i class="fas fa-chalkboard-user" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                  <p class="truncate font-medium text-slate-800 dark:text-slate-200"><?php echo e($teacherLabel); ?></p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">Formateur</p>
                </div>
              </div>

              <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-600 dark:text-slate-300">
                <?php if ($ratingCount > 0): ?>
                  <span class="inline-flex items-center gap-1.5">
                    <?php echo $makeStarIcons($ratingValue); ?>
                    <span class="font-medium"><?php echo e(number_format($ratingValue, 1, ',', ' ')); ?></span>
                  </span>
                  <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                  <span><?php echo e(number_format($ratingCount, 0, ',', ' ') . ' avis'); ?></span>
                  <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200/80 bg-gray-100/80 px-3 py-1.5 text-xs font-medium text-slate-600 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-300">
                    <i class="fas fa-magic text-cyan-400" aria-hidden="true"></i>
                    Nouveau cours
                  </span>
                  <span class="inline-flex items-center gap-1.5">
                    <i class="fas fa-bolt text-cyan-400" aria-hidden="true"></i>
                    <?php echo e($enrolledCount > 0 ? (number_format($enrolledCount, 0, ',', ' ') . ' apprenants') : 'Disponible maintenant'); ?>
                  </span>
                <?php endif; ?>
                <span class="inline-flex items-center gap-1.5"><i class="fas fa-clock text-cyan-400" aria-hidden="true"></i><?php echo e((string) ($popularCourse['durationLabel'] ?? 'À votre rythme')); ?></span>
              </div>

              <p class="line-clamp-2 mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e((string) ($popularCourse['description'] ?? '')); ?></p>

              <div class="mt-auto pt-6">
                <div class="flex items-end justify-between gap-4 border-t border-slate-200/80 pt-5 dark:border-white/10">
                  <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prix</p>
                    <div class="mt-2 flex flex-wrap items-end gap-2">
                      <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl"><?php echo e($currentPrice > 0 ? $publicFormatMoney($currentPrice) : 'Gratuit'); ?></span>
                      <?php if ($basePrice > $currentPrice): ?>
                        <span class="text-sm text-slate-400 line-through"><?php echo e($publicFormatMoney($basePrice)); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="flex shrink-0 items-center gap-2">
                    <a href="<?php echo e($courseUrl); ?>" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-gray-100 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-white/10 dark:bg-white/[0.05] dark:text-slate-200 dark:hover:border-blue-400/40 dark:hover:text-white" title="Voir les détails" aria-label="Voir les détails">
                      Voir les détails
                    </a>
                    <a href="<?php echo e($buyUrl); ?>" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:bg-blue-500" title="<?php echo e($homeCoursePrimaryActionLabel); ?>" aria-label="<?php echo e($homeCoursePrimaryActionLabel); ?>">
                      <i class="fas <?php echo e($homeCoursePrimaryActionIcon); ?>" aria-hidden="true"></i>
                      <span class="sr-only"><?php echo e($homeCoursePrimaryActionLabel); ?></span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="mt-12 text-center">
        <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-900 transition hover:border-blue-400/40 hover:bg-gray-50 dark:border-white/10 dark:bg-white/[0.05] dark:text-white dark:hover:bg-white/[0.08]">
          Voir toutes les formations
          <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
        </a>
      </div>
    </div>
  </section>

  <section class="home-anchor relative py-20 sm:py-24 lg:py-28" id="categories">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-3xl text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm shadow-blue-100/70 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:shadow-none">Explorez par domaine</span>
        <h2 class="mt-5 text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl lg:text-3xl">Catégories populaires</h2>
        <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300">Parcourez nos thématiques et trouvez la formation qui correspond à vos ambitions.</p>
      </div>

      <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <?php foreach ($categoryCarouselItems as $categoryItem): ?>
          <a href="<?php echo e((string) ($categoryItem['href'] ?? $catalogueUrl)); ?>" class="group rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-xl shadow-slate-200/30 transition-all duration-300 hover:-translate-y-1 hover:border-blue-300/60 hover:shadow-2xl hover:shadow-blue-500/10 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20 dark:hover:border-blue-400/40">
            <div class="flex h-full flex-col">
              <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br <?php echo e((string) ($categoryItem['gradient'] ?? 'from-blue-500 to-indigo-600')); ?> text-white shadow-lg shadow-blue-900/20">
                <i class="fas <?php echo e((string) ($categoryItem['icon'] ?? 'fa-graduation-cap')); ?> text-2xl" aria-hidden="true"></i>
              </div>
              <h3 class="mt-6 text-lg font-bold text-slate-900 dark:text-white sm:text-xl"><?php echo e((string) ($categoryItem['name'] ?? 'Formation')); ?></h3>
              <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?php echo e((string) ($categoryItem['count'] ?? 'Catalogue actif')); ?></p>
              <span class="mt-6 inline-flex h-10 w-10 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-600 transition group-hover:border-blue-300 group-hover:bg-blue-100 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-300 dark:group-hover:bg-blue-500/15">
                <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                <span class="sr-only">Explorer</span>
              </span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="home-anchor relative py-20 sm:py-24 lg:py-28 bg-gray-100/80 dark:bg-gray-900/60" id="how-it-works">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-3xl text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm shadow-blue-100/70 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:shadow-none">Comment ça marche</span>
        <h2 class="mt-5 text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl lg:text-3xl">Trois étapes pour passer à l’action</h2>
        <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300">Un parcours clair, rassurant et rapide à comprendre, même depuis un smartphone.</p>
      </div>

      <div class="relative mt-14">
        <div class="absolute left-1/2 top-10 hidden h-px w-[68%] -translate-x-1/2 bg-gradient-to-r from-transparent via-blue-500/30 to-transparent lg:block"></div>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <?php foreach ($howItWorksItems as $howItWorksItem): ?>
          <article class="relative rounded-3xl border border-slate-200/70 bg-white/90 p-7 text-left shadow-xl shadow-slate-200/30 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20">
              <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-xl font-black text-white shadow-lg shadow-blue-600/30">
                <?php echo e((string) ($howItWorksItem['step'] ?? '1')); ?>
              </div>
              <h3 class="mt-6 text-lg font-bold text-slate-900 dark:text-white sm:text-xl"><?php echo e((string) ($howItWorksItem['title'] ?? 'Etape')); ?></h3>
              <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e((string) ($howItWorksItem['text'] ?? '')); ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="home-anchor relative py-14 sm:py-16 lg:py-20" id="why-choose">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-3xl text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm shadow-blue-100/70 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:shadow-none">Pourquoi choisir École Numérique ?</span>
        <h2 class="mt-5 text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl lg:text-3xl">Une plateforme plus simple, plus crédible, plus utile</h2>
        <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300">Apprenez simplement et progressez à votre rythme.</p>
      </div>

      <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($whyItems as $whyItem): ?>
          <article class="hover-lift rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-xl shadow-slate-200/30 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl <?php echo e((string) ($whyItem['iconWrap'] ?? 'bg-blue-500/10')); ?>">
              <i class="fas <?php echo e((string) ($whyItem['icon'] ?? 'fa-star')); ?> <?php echo e((string) ($whyItem['iconClass'] ?? 'text-blue-300')); ?> text-xl" aria-hidden="true"></i>
            </div>
            <h3 class="mt-6 text-lg font-bold text-slate-900 dark:text-white sm:text-xl"><?php echo e((string) ($whyItem['title'] ?? 'Atout')); ?></h3>
            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e((string) ($whyItem['text'] ?? '')); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ($showTestimonials): ?>
    <section class="relative py-14 sm:py-16 lg:py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm shadow-blue-100/70 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:shadow-none">Témoignages vérifiés</span>
          <h2 class="mt-5 text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl lg:text-3xl">Retours d’apprenants vérifiés</h2>
          <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300">Avis réels, affichés quand ils sont disponibles.</p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-3">
          <?php foreach (array_slice($testimonialItems, 0, 6) as $testimonialItem): ?>
            <article class="hover-lift relative overflow-hidden rounded-3xl border border-slate-200/70 bg-white/90 p-7 shadow-xl shadow-slate-200/30 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20">
              <div class="absolute -right-10 top-0 text-6xl font-black text-blue-500/10 dark:text-white/5">"</div>
              <div class="relative flex items-center gap-4">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full border border-slate-200 bg-gray-100 text-base font-bold text-slate-700 shadow-md dark:border-white/10 dark:bg-white/[0.05] dark:text-slate-100">
                  <?php echo e((string) ($testimonialItem['initials'] ?? 'EN')); ?>
                </span>
                <div class="min-w-0">
                  <h3 class="text-base font-bold text-slate-900 dark:text-white"><?php echo e((string) ($testimonialItem['name'] ?? '')); ?></h3>
                  <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo e((string) ($testimonialItem['role'] ?? 'Avis vérifié')); ?></p>
                </div>
              </div>
              <p class="mt-6 text-sm leading-7 text-slate-700 dark:text-slate-300">"<?php echo e((string) ($testimonialItem['quote'] ?? '')); ?>"</p>
              <div class="mt-5 flex items-center gap-1 text-yellow-400" aria-hidden="true">
                <?php echo $makeStarIcons((float) ($testimonialItem['rating'] ?? 0)); ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <section class="home-anchor relative py-20 sm:py-24 lg:py-28 bg-gray-100/80 dark:bg-gray-900/60" id="faq">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mx-auto max-w-3xl text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-100 px-4 py-2 text-sm font-semibold text-blue-800 shadow-sm shadow-blue-100/70 dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:shadow-none">FAQ</span>
        <h2 class="mt-5 text-xl font-black tracking-tight text-slate-900 dark:text-white sm:text-2xl lg:text-3xl">Questions fréquentes avant de commencer</h2>
        <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300">Des réponses simples pour lever les freins avant l’inscription ou le paiement.</p>
      </div>

      <div class="mt-10 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <?php foreach ($faqItems as $faqIndex => $faqItem): ?>
          <details class="faq-item rounded-3xl border border-slate-200/70 bg-white/90 px-6 py-5 shadow-xl shadow-slate-200/20 transition-all duration-300 open:border-blue-300/60 open:shadow-blue-500/10 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20"<?php echo $faqIndex === 0 ? ' open' : ''; ?>>
            <summary class="flex cursor-pointer list-none items-start justify-between gap-4">
              <span class="text-left text-base font-bold text-slate-900 dark:text-white"><?php echo e((string) ($faqItem['question'] ?? 'Question')); ?></span>
              <span class="faq-chevron mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-gray-100 text-slate-600 transition-transform dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                <i class="fas fa-chevron-down text-xs" aria-hidden="true"></i>
              </span>
            </summary>
            <p class="mt-4 pr-10 text-sm leading-7 text-slate-600 dark:text-slate-300"><?php echo e((string) ($faqItem['answer'] ?? '')); ?></p>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="home-anchor relative py-20 sm:py-24 lg:py-28" id="final-cta">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="relative overflow-hidden rounded-[32px] border border-blue-400/20 bg-gradient-to-br from-blue-700 via-blue-600 to-cyan-500 px-6 py-12 text-white shadow-2xl shadow-blue-900/25 sm:px-10 lg:px-14 lg:py-16">
        <div class="absolute -left-16 top-0 h-52 w-52 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute bottom-[-3rem] right-[-2rem] h-64 w-64 rounded-full bg-cyan-200/20 blur-3xl"></div>
        <div class="relative mx-auto max-w-3xl text-center">
          <h2 class="text-xl font-black tracking-tight sm:text-2xl lg:text-3xl">Prêt à apprendre une compétence utile cette semaine ?</h2>
          <p class="mt-5 text-base sm:text-lg text-blue-100">Commencez gratuitement depuis mobile ou ordinateur.</p>
          <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="<?php echo e($registerUrl); ?>" class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-bold text-blue-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-gray-100 sm:w-auto">
              <i class="fas fa-circle-play mr-2" aria-hidden="true"></i>
              Commencer gratuitement
            </a>
            <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex w-full items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/15 sm:w-auto">
              <i class="fas fa-book-open mr-2" aria-hidden="true"></i>
              Voir les formations
            </a>
          </div>
          <p class="mt-5 text-sm text-blue-100/90">Certains cours sont gratuits. Paiement simple pour les formations payantes.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/_public_footer_home.php'; ?>

</body>
</html>
