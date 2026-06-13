<?php
$publicPage = 'catalogue';
$publicBrandSubtitle = 'Un catalogue clair, rapide et centré sur la découverte.';
$platformName = trim((string) platform_setting_get('site_name', defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique'));
if ($platformName === '') {
    $platformName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
}

/** @var null|callable(array):array $publicDecorateCourse */
$publicDecorateCourse = null;
/** @var null|callable(string):string $publicNormalizeKey */
$publicNormalizeKey = null;
/** @var null|callable(int):string $publicFormatMoney */
$publicFormatMoney = null;

require __DIR__ . '/_public_bootstrap.php';

/** @var array<int,array<string,mixed>> $ssrCourses */
$ssrCourses = is_array($ssrCourses ?? null) ? $ssrCourses : [];
$seoCanonicalUrl = trim((string) ($seoCanonicalUrl ?? ''));

$platformLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$platformLogoAlt = 'Logo ' . $platformName;
$headerNotificationCount = max(0, (int) ($totalUnread ?? 0));
$headerLoginLabel = 'Se connecter';
$headerLoginUrl = $loginUrl;
$headerPrimaryLabel = $isLoggedIn ? 'Tableau de bord' : 'Créer un compte';
$headerPrimaryUrl = $isLoggedIn ? $dashboardUrl : $registerUrl;
$headerLearningLabel = $isLoggedIn ? 'Mes parcours' : 'Parcours';
$headerLearningUrl = $isLoggedIn ? site_url('student/my_courses') : $homeUrl . '#carouselSection';
$coursePrimaryActionLabel = 'Voir le cours';
$coursePrimaryActionIcon = 'fa-arrow-right';
$publicNavPanelId = 'catalogue-public-nav-panel';

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
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1d4ed8"/>
      <stop offset="100%" stop-color="#06b6d4"/>
    </linearGradient>
  </defs>
  <rect width="800" height="480" fill="url(#g)"/>
  <circle cx="660" cy="120" r="110" fill="rgba(255,255,255,0.08)"/>
  <circle cx="120" cy="360" r="130" fill="rgba(255,255,255,0.10)"/>
  <text x="56" y="88" fill="#dbeafe" font-size="30" font-family="Arial, sans-serif">{$safeCategory}</text>
  <text x="56" y="182" fill="#ffffff" font-size="46" font-weight="700" font-family="Arial, sans-serif">{$safeTitle}</text>
</svg>
SVG;

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
};

$decorateCatalogCourse = static function (array $course) use ($publicDecorateCourse, $publicFormatMoney, $makeCoverPlaceholder): array {
    $decorated = $publicDecorateCourse($course);
    $title = trim((string) ($decorated['title'] ?? 'Cours numérique'));
    $category = trim((string) ($decorated['category'] ?? 'Formation'));
    $priceRaw = max(0, (int) ($decorated['priceRaw'] ?? 0));
    $basePriceRaw = max($priceRaw, (int) ($course['basePriceXof'] ?? $priceRaw));
    $promoActive = !empty($course['promoActive']) && $basePriceRaw > $priceRaw;
    $levelSource = strtolower(trim((string) ($decorated['levelKey'] ?? '')));
    $levelKey = match ($levelSource) {
        'beginner' => 'debutant',
        'intermediate' => 'intermediaire',
        'advanced' => 'avance',
        default => '',
    };
    $createdAt = trim((string) ($course['createdAt'] ?? ''));
    $createdTs = $createdAt !== '' ? (int) strtotime($createdAt) : 0;
    $rating = max(0.0, round((float) ($decorated['rating'] ?? 0), 1));
    $ratingCount = max(0, (int) ($decorated['ratingCount'] ?? 0));
    $enrolledCount = max(0, (int) ($decorated['enrolledCount'] ?? 0));
    $image = trim((string) ($decorated['cover'] ?? ''));
    if ($image === '') {
        $image = $makeCoverPlaceholder($title, $category);
    }

    return [
        'id' => max(0, (int) ($decorated['id'] ?? 0)),
        'url' => trim((string) ($decorated['url'] ?? site_url('catalogue'))),
        'buyUrl' => trim((string) ($decorated['buyUrl'] ?? site_url('checkout?course=' . max(0, (int) ($decorated['id'] ?? 0))))),
        'isInCart' => !empty($course['isInCart']) ? 1 : 0,
        'isFavorite' => !empty($course['isFavorite']) ? 1 : 0,
        'title' => $title,
        'description' => trim((string) ($decorated['description'] ?? '')),
        'category' => $category,
        'categoryKey' => trim((string) ($decorated['categoryKey'] ?? '')),
        'level' => $levelKey,
        'levelLabel' => trim((string) ($decorated['levelLabel'] ?? 'Tous niveaux')),
        'durationLabel' => trim((string) ($decorated['durationLabel'] ?? '')),
        'price' => $priceRaw,
        'priceLabel' => $priceRaw <= 0 ? 'Gratuit' : $publicFormatMoney($priceRaw),
        'basePriceLabel' => $promoActive ? $publicFormatMoney($basePriceRaw) : '',
        'priceHint' => trim((string) ($decorated['priceHint'] ?? 'Accès immédiat')),
        'promoActive' => $promoActive,
        'promoDiscountLabel' => trim((string) ($course['promoDiscountLabel'] ?? '')),
        'promoEndAt' => trim((string) ($course['promoEndAt'] ?? '')),
        'couponAvailable' => !empty($course['couponAvailable']),
        'rating' => $rating,
        'ratingCount' => $ratingCount,
        'enrolledCount' => $enrolledCount,
        'students' => $enrolledCount,
        'studentsLabel' => $enrolledCount > 0 ? view_pluralize($enrolledCount, 'inscrit', 'inscrits') : 'Nouveau',
        'objectives' => is_array($decorated['objectives'] ?? null) ? array_values($decorated['objectives']) : [],
        'prerequisites' => is_array($decorated['prerequisites'] ?? null) ? array_values($decorated['prerequisites']) : [],
        'equipment' => is_array($decorated['equipment'] ?? null) ? array_values($decorated['equipment']) : [],
        'deliveryMode' => trim((string) ($decorated['deliveryMode'] ?? '')),
        'courseLocation' => trim((string) ($decorated['courseLocation'] ?? '')),
        'formatLabel' => trim((string) ($decorated['formatLabel'] ?? '')),
        'formatIcon' => trim((string) ($decorated['formatIcon'] ?? 'fa-video')),
        'objectiveSnippet' => trim((string) ($decorated['objectiveSnippet'] ?? '')),
        'prerequisiteSnippet' => trim((string) ($decorated['prerequisiteSnippet'] ?? '')),
        'image' => $image,
        'date' => $createdTs > 0 ? date('Y-m-d', $createdTs) : '',
        'createdTs' => $createdTs,
        'searchBlob' => trim((string) ($decorated['searchText'] ?? '')),
        'teacher' => trim((string) ($decorated['teacher'] ?? 'Formateur')),
        'ctaLabel' => trim((string) ($decorated['ctaLabel'] ?? 'Voir les détails')),
        'tags' => [],
    ];
};

$catalogCourses = array_values(array_map($decorateCatalogCourse, $ssrCourses));
$courseCount = count($catalogCourses);
$courseCountLabel = number_format($courseCount, 0, ',', ' ');
$compactCatalogFilters = $courseCount < 12;
$suggestCourseMessage = 'Bonjour, je souhaite suggerer une formation a ajouter au catalogue.';
$supportWhatsappRaw = trim((string) platform_setting_get('support_whatsapp', ''));
$supportWhatsappDigits = (string) preg_replace('/\D+/', '', $supportWhatsappRaw);
$suggestCourseUrl = $supportWhatsappDigits !== ''
    ? 'https://wa.me/' . $supportWhatsappDigits . '?text=' . rawurlencode($suggestCourseMessage)
    : $supportUrl;
$suggestCourseTarget = $supportWhatsappDigits !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
$suggestCourseButtonLabel = $supportWhatsappDigits !== '' ? 'Suggérer une formation sur WhatsApp' : 'Suggérer une formation';

$categoryCounts = [];
$categoryLabels = [];
foreach ($catalogCourses as $courseCard) {
    $categoryKey = trim((string) ($courseCard['categoryKey'] ?? ''));
    if ($categoryKey === '') {
        continue;
    }
    $categoryCounts[$categoryKey] = ($categoryCounts[$categoryKey] ?? 0) + 1;
    $categoryLabels[$categoryKey] = (string) ($courseCard['category'] ?? 'Formation');
}
arsort($categoryCounts);

$categoryOptions = [];
foreach ($categoryCounts as $categoryKey => $count) {
    $categoryOptions[] = [
        'key' => $categoryKey,
        'label' => (string) ($categoryLabels[$categoryKey] ?? 'Formation'),
        'count' => (int) $count,
    ];
}

$levelDefinitions = [
    'debutant' => 'Débutant',
    'intermediaire' => 'Intermédiaire',
    'avance' => 'Avancé',
];
$levelCounts = array_fill_keys(array_keys($levelDefinitions), 0);
foreach ($catalogCourses as $courseCard) {
    $levelKey = trim((string) ($courseCard['level'] ?? ''));
    if ($levelKey !== '' && array_key_exists($levelKey, $levelCounts)) {
        $levelCounts[$levelKey] += 1;
    }
}

$levelOptions = [];
foreach ($levelDefinitions as $levelKey => $levelLabel) {
    $levelOptions[] = [
        'key' => $levelKey,
        'label' => $levelLabel,
        'count' => (int) ($levelCounts[$levelKey] ?? 0),
    ];
}

$priceValues = array_values(array_filter(array_map(static fn(array $course): int => max(0, (int) ($course['price'] ?? 0)), $catalogCourses), static fn(int $price): bool => $price > 0));
$catalogPriceMin = $priceValues !== [] ? min($priceValues) : 0;
$catalogPriceMax = $priceValues !== [] ? max($priceValues) : 0;

$totalReviewCount = array_sum(array_map(static fn(array $course): int => max(0, (int) ($course['ratingCount'] ?? 0)), $catalogCourses));
$ratedCourses = array_values(array_filter($catalogCourses, static fn(array $course): bool => (float) ($course['rating'] ?? 0) > 0));
$catalogAverageRating = $ratedCourses !== []
    ? array_sum(array_map(static fn(array $course): float => (float) ($course['rating'] ?? 0), $ratedCourses)) / count($ratedCourses)
    : 0.0;
$totalReviewCountLabel = number_format($totalReviewCount, 0, ',', ' ');

$bestSellerCandidates = $catalogCourses;
usort(
    $bestSellerCandidates,
    static fn(array $left, array $right): int => [(int) ($right['enrolledCount'] ?? 0), (float) ($right['rating'] ?? 0), (int) ($right['createdTs'] ?? 0)]
        <=>
        [(int) ($left['enrolledCount'] ?? 0), (float) ($left['rating'] ?? 0), (int) ($left['createdTs'] ?? 0)]
);
$bestSellerIds = array_slice(array_map(static fn(array $course): int => (int) ($course['id'] ?? 0), array_filter($bestSellerCandidates, static fn(array $course): bool => (int) ($course['enrolledCount'] ?? 0) > 0)), 0, 3);

$topRatedCandidates = $catalogCourses;
usort(
    $topRatedCandidates,
    static fn(array $left, array $right): int => [(float) ($right['rating'] ?? 0), (int) ($right['enrolledCount'] ?? 0), (int) ($right['createdTs'] ?? 0)]
        <=>
        [(float) ($left['rating'] ?? 0), (int) ($left['enrolledCount'] ?? 0), (int) ($left['createdTs'] ?? 0)]
);
$topRatedIds = array_slice(array_map(static fn(array $course): int => (int) ($course['id'] ?? 0), array_filter($topRatedCandidates, static fn(array $course): bool => (float) ($course['rating'] ?? 0) > 0)), 0, 3);

$recentCandidates = $catalogCourses;
usort(
    $recentCandidates,
    static fn(array $left, array $right): int => (int) ($right['createdTs'] ?? 0) <=> (int) ($left['createdTs'] ?? 0)
);
$newIds = array_slice(array_map(static fn(array $course): int => (int) ($course['id'] ?? 0), array_filter($recentCandidates, static fn(array $course): bool => (int) ($course['createdTs'] ?? 0) > 0)), 0, 3);

foreach ($catalogCourses as $index => $courseCard) {
    $tags = [];
    $courseId = (int) ($courseCard['id'] ?? 0);
    if ($courseId > 0 && in_array($courseId, $bestSellerIds, true)) {
        $tags[] = 'best-seller';
    }
    if ($courseId > 0 && in_array($courseId, $topRatedIds, true)) {
        $tags[] = 'top-rated';
    }
    if ($courseId > 0 && in_array($courseId, $newIds, true)) {
        $tags[] = 'nouveaute';
    }
    $catalogCourses[$index]['tags'] = $tags;
}

$catalogNormalize = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strtolower')) {
        $value = mb_strtolower($value, 'UTF-8');
    } else {
        $value = strtolower($value);
    }

    if (function_exists('iconv')) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && $ascii !== '') {
            $value = $ascii;
        }
    }

    $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? $value;
    return trim($value);
};

$courseById = [];
foreach ($catalogCourses as $courseCard) {
    $courseId = (int) ($courseCard['id'] ?? 0);
    if ($courseId > 0) {
        $courseById[$courseId] = $courseCard;
    }
}

$buildGuideObjective = static function (array $categoryOption, int $index) use ($catalogNormalize): array {
    $label = trim((string) ($categoryOption['label'] ?? 'Formation'));
    $count = max(0, (int) ($categoryOption['count'] ?? 0));
    $categoryKey = trim((string) ($categoryOption['key'] ?? ''));
    $normalized = $catalogNormalize($label);

    $objective = [
        'key' => $categoryKey !== '' ? $categoryKey : 'objectif-' . ($index + 1),
        'categoryKey' => $categoryKey,
        'count' => $count,
        'search' => $label,
        'icon' => 'fa-compass',
        'iconClass' => 'bg-slate-700 text-white',
        'cardClass' => 'from-slate-50 to-white dark:from-slate-500/10 dark:to-gray-900',
        'linkClass' => 'text-slate-600 dark:text-slate-300',
        'headline' => 'Explorer ' . $label,
        'description' => $count > 0
            ? $count . ' cours pour démarrer vite.'
            : 'Une entrée claire pour démarrer.',
        'cta' => 'Voir la sélection',
        'featuredEyebrow' => 'Recommandé pour avancer en ' . $label,
        'featuredSupport' => 'Passez à l’action plus vite.',
    ];

    if (preg_match('/develop|code|web|php|javascript|frontend|backend/i', $normalized)) {
        $objective['icon'] = 'fa-code';
        $objective['iconClass'] = 'bg-blue-600 text-white';
        $objective['cardClass'] = 'from-blue-50 to-white dark:from-blue-500/10 dark:to-gray-900';
        $objective['linkClass'] = 'text-blue-600 dark:text-blue-300';
        $objective['headline'] = 'Devenir développeur';
        $objective['description'] = 'Les meilleurs cours pour lancer votre progression tech.';
        $objective['cta'] = 'Voir les cours';
        $objective['featuredEyebrow'] = 'Recommandé pour devenir développeur';
        $objective['featuredSupport'] = 'Des bases claires pour avancer vite.';
    } elseif (preg_match('/marketing|seo|contenu|social|communication|brand/i', $normalized)) {
        $objective['icon'] = 'fa-bullhorn';
        $objective['iconClass'] = 'bg-cyan-600 text-white';
        $objective['cardClass'] = 'from-cyan-50 to-white dark:from-cyan-500/10 dark:to-gray-900';
        $objective['linkClass'] = 'text-cyan-600 dark:text-cyan-300';
        $objective['headline'] = 'Gagner en visibilité';
        $objective['description'] = 'SEO et contenu pour gagner en impact rapidement.';
        $objective['cta'] = 'Explorer';
        $objective['featuredEyebrow'] = 'Recommandé pour gagner en visibilité';
        $objective['featuredSupport'] = 'Des actions utiles dès la première séance.';
    } elseif (preg_match('/data|ia|ai|analytics|analyse|bi/i', $normalized)) {
        $objective['icon'] = 'fa-chart-line';
        $objective['iconClass'] = 'bg-violet-600 text-white';
        $objective['cardClass'] = 'from-violet-50 to-white dark:from-violet-500/10 dark:to-gray-900';
        $objective['linkClass'] = 'text-violet-600 dark:text-violet-300';
        $objective['headline'] = 'Piloter avec la data';
        $objective['description'] = 'Data et IA pour monter en compétence rapidement.';
        $objective['cta'] = 'Découvrir';
        $objective['featuredEyebrow'] = 'Recommandé pour monter en compétence';
        $objective['featuredSupport'] = 'Une recommandation utile et directe.';
    } elseif (preg_match('/compta|finance|gestion|comptabilite/i', $normalized)) {
        $objective['icon'] = 'fa-wallet';
        $objective['iconClass'] = 'bg-emerald-600 text-white';
        $objective['cardClass'] = 'from-emerald-50 to-white dark:from-emerald-500/10 dark:to-gray-900';
        $objective['linkClass'] = 'text-emerald-600 dark:text-emerald-300';
        $objective['headline'] = 'Structurer mon activité';
        $objective['description'] = 'Les bons réflexes pour mieux piloter votre activité.';
        $objective['cta'] = 'Voir le parcours';
        $objective['featuredEyebrow'] = 'Recommandé pour structurer votre activité';
        $objective['featuredSupport'] = 'Une base claire pour bien progresser.';
    }

    return $objective;
};

$guideObjectiveSource = array_slice($categoryOptions, 0, 3);
if ($guideObjectiveSource === []) {
    $guideObjectiveSource = [
        ['key' => 'developpement', 'label' => 'Développement', 'count' => 0],
        ['key' => 'marketing', 'label' => 'Marketing', 'count' => 0],
        ['key' => 'data', 'label' => 'Data', 'count' => 0],
    ];
}

$guideObjectives = [];
foreach ($guideObjectiveSource as $objectiveIndex => $categoryOption) {
    $guideObjectives[] = $buildGuideObjective($categoryOption, $objectiveIndex);
}

$pickRecommendedCourse = static function (array $objective, array $courses) use ($catalogNormalize): ?array {
    $bestCourse = null;
    $bestScore = -1;
    $objectiveSearch = $catalogNormalize((string) ($objective['search'] ?? ''));
    $objectiveCategoryKey = trim((string) ($objective['categoryKey'] ?? ''));

    foreach ($courses as $course) {
        $score = 0;
        $courseCategoryKey = trim((string) ($course['categoryKey'] ?? ''));
        $searchBlob = $catalogNormalize((string) ($course['searchBlob'] ?? ''));
        $titleBlob = $catalogNormalize((string) ($course['title'] ?? ''));

        if ($objectiveCategoryKey !== '' && $courseCategoryKey === $objectiveCategoryKey) {
            $score += 100;
        }
        if ($objectiveSearch !== '' && str_contains($searchBlob, $objectiveSearch)) {
            $score += 55;
        }
        if ($objectiveSearch !== '' && str_contains($titleBlob, $objectiveSearch)) {
            $score += 20;
        }
        if (in_array('best-seller', $course['tags'] ?? [], true)) {
            $score += 18;
        }
        if (in_array('top-rated', $course['tags'] ?? [], true)) {
            $score += 14;
        }
        $score += min(20, (int) ($course['enrolledCount'] ?? 0));
        $score += (int) round(((float) ($course['rating'] ?? 0)) * 4);
        if (!empty($course['promoActive'])) {
            $score += 4;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestCourse = $course;
        }
    }

    return $bestCourse;
};

$guideObjectivesPayload = [];
foreach ($guideObjectives as $objective) {
    $recommendedCourse = $pickRecommendedCourse($objective, $catalogCourses) ?? ($bestSellerCandidates[0] ?? ($catalogCourses[0] ?? null));
    if (is_array($recommendedCourse)) {
        $objective['featuredCourseId'] = (int) ($recommendedCourse['id'] ?? 0);
    }
    $guideObjectivesPayload[] = $objective;
}

$featuredObjective = $guideObjectivesPayload[0] ?? null;
$featuredCourse = null;
if (is_array($featuredObjective) && !empty($featuredObjective['featuredCourseId'])) {
    $featuredCourse = $courseById[(int) $featuredObjective['featuredCourseId']] ?? null;
}
if (!is_array($featuredCourse)) {
    $featuredCourse = $bestSellerCandidates[0] ?? ($catalogCourses[0] ?? null);
}

$trustHighlights = [];
if ($totalReviewCount >= 25 && $catalogAverageRating >= 4.0) {
    $trustHighlights[] = ['icon' => 'fa-star', 'label' => number_format($catalogAverageRating, 1, '.', '') . '/5 de moyenne'];
} else {
    $trustHighlights[] = ['icon' => 'fa-bolt', 'label' => 'Parcours concrets'];
}
$trustHighlights[] = ['icon' => 'fa-layer-group', 'label' => $courseCountLabel . ' formations actives'];
if ($totalReviewCount >= 25) {
    $trustHighlights[] = ['icon' => 'fa-comment-dots', 'label' => $totalReviewCountLabel . ' avis publies'];
} elseif (count($newIds) > 0) {
    $trustHighlights[] = ['icon' => 'fa-wand-magic-sparkles', 'label' => 'Nouveaux cours régulièrement'];
} else {
    $trustHighlights[] = ['icon' => 'fa-check-circle', 'label' => 'Sélection orientée résultat'];
}

$guideObjectivesJson = json_encode(
    $guideObjectivesPayload,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($guideObjectivesJson) || $guideObjectivesJson === '') {
    $guideObjectivesJson = '[]';
}

$catalogCoursesJson = json_encode(
    $catalogCourses,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($catalogCoursesJson) || $catalogCoursesJson === '') {
    $catalogCoursesJson = '[]';
}

$initialCatalogCourses = array_slice($catalogCourses, 0, 12);
$catalogJsUrl = site_url('assets/js/web_catalogue.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_catalogue.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$webMarketingJsUrl = site_url('assets/js/web-marketing.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web-marketing.js') ?: time()));
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$recentCatalogThreshold = time() - (45 * 86400);

$resolveCatalogBadge = static function (array $course) use ($recentCatalogThreshold): array {
    $tags = is_array($course['tags'] ?? null) ? $course['tags'] : [];
    $enrolledCount = max(0, (int) ($course['enrolledCount'] ?? 0));
    if (in_array('best-seller', $tags, true)) {
        return [
            'label' => 'Populaire',
            'meta' => $enrolledCount > 0 ? view_pluralize($enrolledCount, 'inscrit', 'inscrits') : 'Preuve sociale',
            'class' => 'bg-black/70 text-white',
        ];
    }
    if (in_array('top-rated', $tags, true)) {
        return ['label' => 'Tendance', 'meta' => '', 'class' => 'bg-black/65 text-white'];
    }
    if (in_array('nouveaute', $tags, true)) {
        return ['label' => 'Nouveau', 'meta' => '', 'class' => 'bg-black/65 text-white'];
    }

    return [
        'label' => '',
        'meta' => '',
        'class' => 'bg-black/65 text-white',
    ];
};

$resolveCatalogTrust = static function (array $course) use ($recentCatalogThreshold): array {
    $ratingCount = max(0, (int) ($course['ratingCount'] ?? 0));
    $rating = max(0.0, round((float) ($course['rating'] ?? 0), 1));
    $enrolledCount = max(0, (int) ($course['enrolledCount'] ?? 0));
    $createdTs = max(0, (int) ($course['createdTs'] ?? 0));

    if ($ratingCount > 0) {
        return [
            'mode' => 'rated',
            'label' => number_format($rating, 1, '.', ''),
            'meta' => view_pluralize($ratingCount, 'avis', 'avis'),
            'icon' => 'fa-star',
            'iconClass' => 'text-yellow-400',
        ];
    }

    if ($createdTs > 0 && $createdTs >= $recentCatalogThreshold && $enrolledCount === 0) {
        return [
            'mode' => 'recent',
            'label' => 'Nouveau cours',
            'meta' => 'Disponible maintenant',
            'icon' => 'fa-magic',
            'iconClass' => 'text-emerald-500',
        ];
    }

    if ($createdTs > 0 && $createdTs >= $recentCatalogThreshold && $enrolledCount > 0) {
        return [
            'mode' => 'recent-social',
            'label' => 'Formation récemment ajoutée',
            'meta' => view_pluralize($enrolledCount, 'apprenant', 'apprenants'),
            'icon' => 'fa-wand-magic-sparkles',
            'iconClass' => 'text-emerald-500',
        ];
    }

    if ($enrolledCount > 0) {
        return [
            'mode' => 'available',
            'label' => 'Disponible maintenant',
            'meta' => view_pluralize($enrolledCount, 'apprenant', 'apprenants'),
            'icon' => 'fa-users',
            'iconClass' => 'text-blue-500',
        ];
    }

    return [
        'mode' => 'available',
        'label' => 'Disponible maintenant',
        'meta' => 'Inscription ouverte',
        'icon' => 'fa-bolt',
        'iconClass' => 'text-blue-500',
    ];
};

$resolveLevelBadgeClass = static function (string $levelKey): string {
    return match ($levelKey) {
        'debutant' => 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200',
        'intermediaire' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-500/15 dark:text-cyan-200',
        'avance' => 'bg-violet-100 text-violet-800 dark:bg-violet-500/15 dark:text-violet-200',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
    };
};
?>
<!DOCTYPE html>
<html lang="fr" data-view="web/catalogue">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Catalogue | <?php echo e($platformName); ?></title>
  <meta name="description" content="Explorez le catalogue de <?php echo e($platformName); ?> avec recherche, filtres et tri pour trouver la formation qui vous correspond.">
  <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
  <meta name="color-scheme" content="light dark">
   <?php if ($seoCanonicalUrl !== ''): ?>
    <link rel="canonical" href="<?php echo e($seoCanonicalUrl); ?>">
  <?php endif; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($webMarketingJsUrl); ?>" defer></script>
  <script src="<?php echo e($catalogJsUrl); ?>" defer></script>
  <style>
    * { transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease; }
    body[data-view="web/catalogue"] {
      overflow-x: clip;
    }
    .hover-lift {
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .hover-lift:hover {
      transform: translateY(-6px);
      box-shadow: 0 25px 30px -12px rgba(15, 23, 42, 0.18);
    }
    .catalog-results-toolbar {
      position: sticky;
      top: 5.15rem;
      z-index: 30;
      margin-bottom: 1.5rem;
      border: 1px solid rgba(226, 232, 240, 0.86);
      border-radius: 18px;
      background: rgba(248, 250, 252, 0.92);
      box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.42);
      backdrop-filter: blur(14px) saturate(1.05);
    }
    html.dark .catalog-results-toolbar {
      border-color: rgba(51, 65, 85, 0.84);
      background: rgba(2, 6, 23, 0.84);
      box-shadow: 0 18px 40px -34px rgba(0, 0, 0, 0.8);
    }
    .catalog-course-card {
      min-height: 34rem;
    }
    .catalog-card__cover {
      aspect-ratio: 16 / 9;
      background: #e2e8f0;
    }
    html.dark .catalog-card__cover {
      background: #020617;
    }
    .catalog-card__cover::after {
      content: "";
      position: absolute;
      inset: 0;
      z-index: 1;
      pointer-events: none;
      background:
        linear-gradient(180deg, rgba(15, 23, 42, 0.18) 0%, rgba(15, 23, 42, 0) 42%, rgba(15, 23, 42, 0.16) 100%);
    }
    .catalog-card__cover-img {
      height: 100%;
      width: 100%;
      object-fit: cover;
      object-position: center;
    }
    .catalog-card__hover-preview {
      position: absolute;
      inset: 0;
      z-index: 4;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      gap: 0.55rem;
      padding: 0.9rem;
      color: #ffffff;
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.24), rgba(15, 23, 42, 0.92));
      opacity: 0;
      transform: translateY(10px);
      pointer-events: none;
      transition: opacity 0.22s ease, transform 0.22s ease;
    }
    @media (hover: hover) and (pointer: fine) {
      .catalog-course-card:hover .catalog-card__hover-preview,
      .catalog-course-card:focus-within .catalog-card__hover-preview {
        opacity: 1;
        transform: translateY(0);
      }
    }
    @media (hover: none) {
      .catalog-card__hover-preview {
        display: none;
      }
    }
    .gradient-text {
      background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .dark .gradient-text {
      background: linear-gradient(135deg, #60a5fa 0%, #22d3ee 100%);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .filter-scroll::-webkit-scrollbar { height: 4px; }
    .filter-scroll::-webkit-scrollbar-track { background: #e5e7eb; border-radius: 999px; }
    .filter-scroll::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 999px; }
    .catalog-quick-filter[aria-pressed="true"] {
      border-color: rgba(37, 99, 235, 0.32);
      background: #dbeafe;
      color: #1d4ed8;
      box-shadow: 0 12px 28px -22px rgba(37, 99, 235, 0.75);
    }
    html.dark .catalog-quick-filter[aria-pressed="true"] {
      border-color: rgba(96, 165, 250, 0.42);
      background: rgba(37, 99, 235, 0.22);
      color: #bfdbfe;
    }
    .catalog-card__favorite,
    .catalog-card__cart {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 9999px;
      border: 1px solid rgba(148, 163, 184, 0.26);
      background: rgba(255, 255, 255, 0.92);
      color: #1f2937;
      box-shadow: 0 10px 30px -18px rgba(15, 23, 42, 0.4);
      transition: transform 0.2s ease, opacity 0.2s ease, box-shadow 0.25s ease, background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }
    .catalog-card__favorite {
      width: 2.5rem;
      height: 2.5rem;
      backdrop-filter: blur(10px);
      opacity: 0;
      transform: translateY(-4px);
    }
    .catalog-card__cart {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 0.85rem;
      opacity: 0.92;
    }
    .catalog-course-card:hover .catalog-card__favorite,
    .catalog-course-card:focus-within .catalog-card__favorite,
    .catalog-card__favorite.is-active {
      opacity: 1;
      transform: translateY(0);
    }
    .catalog-card__favorite.is-active,
    .catalog-card__cart.is-active {
      border-color: rgba(59, 130, 246, 0.28);
      background: rgba(219, 234, 254, 0.95);
      color: #1d4ed8;
      opacity: 1;
    }
    html.dark .catalog-card__favorite,
    html.dark .catalog-card__cart {
      border-color: rgba(71, 85, 105, 0.7);
      background: rgba(15, 23, 42, 0.84);
      color: #e2e8f0;
    }
    html.dark .catalog-card__favorite {
      opacity: 0;
    }
    html.dark .catalog-course-card:hover .catalog-card__favorite,
    html.dark .catalog-course-card:focus-within .catalog-card__favorite,
    html.dark .catalog-card__favorite.is-active {
      opacity: 1;
    }
    html.dark .catalog-card__favorite.is-active,
    html.dark .catalog-card__cart.is-active {
      border-color: rgba(96, 165, 250, 0.45);
      background: rgba(30, 41, 59, 0.96);
      color: #93c5fd;
    }
    .catalog-card__favorite.is-busy,
    .catalog-card__cart.is-busy {
      opacity: 0.65;
      pointer-events: none;
    }
    @media (hover: none) {
      .catalog-card__favorite {
        opacity: 1;
        transform: translateY(0);
      }
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
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
      overflow: hidden;
    }
    .line-clamp-1 {
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 1;
      overflow: hidden;
    }
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 3;
      overflow: hidden;
    }
    @media (max-width: 640px) {
      .catalog-results-toolbar {
        top: 4.55rem;
        border-radius: 14px;
      }
      .catalog-course-card {
        min-height: auto;
      }
    }
    @media (max-width: 1023px) {
      body.catalog-filter-open {
        overflow: hidden;
      }
      .catalog-filter-shell {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: none !important;
        align-items: flex-end;
        padding: 1rem;
        pointer-events: none;
      }
      body.catalog-filter-open .catalog-filter-shell {
        display: flex !important;
      }
      .catalog-filter-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.48);
        backdrop-filter: blur(2px);
      }
      .catalog-filter-shell #filterContainer {
        position: relative;
        width: 100%;
        max-height: min(78vh, 42rem);
        overflow-y: auto;
        pointer-events: auto;
        border-radius: 1.25rem 1.25rem 0 0;
        box-shadow: 0 -24px 60px -32px rgba(15, 23, 42, 0.55);
      }
    }
    @media (max-width: 639px) {
      body[data-view="web/catalogue"] .container {
        max-width: 100vw;
      }
      body[data-view="web/catalogue"] header .container {
        position: relative;
        overflow: visible;
      }
      body[data-view="web/catalogue"] main .container {
        overflow: hidden;
      }
      body[data-view="web/catalogue"] #searchInput {
        min-width: 0;
        text-overflow: ellipsis;
      }
    }
  </style>
</head>
<body
  class="min-h-screen overflow-x-hidden bg-gray-50 font-sans antialiased text-gray-800 dark:bg-gray-950 dark:text-gray-100"
  data-view="web/catalogue"
  data-compact-filters="<?php echo $compactCatalogFilters ? '1' : '0'; ?>"
  data-cart-api="<?php echo e(site_url('api/cart')); ?>"
  data-favorites-api="<?php echo e(site_url('api/course_favorites')); ?>"
  data-login-url="<?php echo e($loginUrl); ?>"
  data-is-authenticated="<?php echo $isLoggedIn ? '1' : '0'; ?>"
>
  <?php require __DIR__ . '/_public_header_catalogue.php'; ?>

  <main class="pb-0">
    <section class="bg-gradient-to-b from-white to-gray-50 pb-6 pt-8 dark:from-gray-950 dark:to-gray-900/70">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
          <span class="mb-3 inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-200">
            <i class="fas fa-layer-group"></i> Plus de <?php echo e($courseCountLabel); ?> formations
          </span>
          <h1 class="mb-3 text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-3xl md:text-4xl">
            Explorez notre <span class="gradient-text block sm:inline">catalogue</span>
          </h1>
          <p class="mx-auto max-w-2xl text-base leading-7 text-gray-600 dark:text-gray-300 md:text-lg">
            Des formations conçues par des experts pour accélérer votre progression. Filtrez, comparez et trouvez le bon parcours.
          </p>

          <div class="relative mx-auto mt-6 max-w-2xl">
            <i class="fas fa-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input
              type="text"
              id="searchInput"
              placeholder="Rechercher un cours, une compétence, un formateur..."
              class="w-full rounded-2xl border border-gray-200 bg-white py-3.5 pl-12 pr-4 text-gray-800 shadow-sm outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/20 sm:pr-40"
            >
            <button
              id="searchClearButton"
              type="button"
              class="absolute right-2 top-1/2 hidden h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200 sm:right-32"
              aria-label="Effacer la recherche"
            >
              <i class="fas fa-times" aria-hidden="true"></i>
            </button>
            <button id="searchSubmitButton" type="button" class="absolute right-2 top-1/2 hidden h-10 -translate-y-1/2 items-center justify-center rounded-xl bg-blue-600 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 sm:inline-flex sm:w-auto sm:px-4">
              <span class="hidden sm:inline">Rechercher</span>
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="border-y border-slate-200/70 bg-slate-50/90 py-6 dark:border-gray-800 dark:bg-gray-950">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-start">
          <aside
            id="catalogFilterPanel"
            class="catalog-filter-shell w-full flex-shrink-0 lg:sticky lg:top-24 lg:w-72 lg:self-start"
            <?php if ($compactCatalogFilters): ?>style="display: none;"<?php endif; ?>
          >
            <div class="mb-4 hidden">
              <button
                id="mobileFilterToggle"
                type="button"
                data-filter-toggle
                aria-controls="filterContainer"
                aria-expanded="false"
                class="w-full items-center justify-between rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
              >
                <span><i class="fas fa-filter mr-2 text-blue-600 dark:text-blue-300"></i> Afficher les filtres</span>
                <i class="fas fa-chevron-down text-gray-400"></i>
              </button>
            </div>

            <div id="filterContainer" class="hidden rounded-[20px] border border-gray-200 bg-white p-5 shadow-[0_14px_40px_-18px_rgba(15,23,42,0.2)] dark:border-gray-800 dark:bg-gray-900<?php echo $compactCatalogFilters ? '' : ' lg:block'; ?>">
              <div class="mb-7 flex items-center justify-between">
                <h2 class="text-[1.15rem] font-bold text-gray-900 dark:text-white">
                  <i class="fas fa-sliders-h mr-2 text-blue-500"></i>Filtres
                </h2>
                <button id="resetFiltersBtn" type="button" class="text-sm font-medium text-blue-600 transition hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">Réinitialiser</button>
              </div>

              <div class="mb-7">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-[0.14em] text-gray-700 dark:text-gray-200">Catégories</h3>
                <div class="space-y-3">
                  <?php if ($categoryOptions !== []): ?>
                    <?php foreach ($categoryOptions as $categoryOption): ?>
                      <label class="group flex cursor-pointer items-center gap-3 text-[1rem]">
                        <input
                          type="checkbox"
                          class="category-filter h-4 w-4 rounded-[4px] border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                          value="<?php echo e($categoryOption['key']); ?>"
                          data-label="<?php echo e($categoryOption['label']); ?>"
                        >
                        <span class="min-w-0 flex-1 truncate text-gray-700 transition group-hover:text-blue-600 dark:text-gray-300 dark:group-hover:text-blue-300" title="<?php echo e($categoryOption['label']); ?>"><?php echo e($categoryOption['label']); ?></span>
                        <span class="ml-auto inline-flex min-w-8 items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400"><?php echo e((string) $categoryOption['count']); ?></span>
                      </label>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune categorie publique disponible.</p>
                  <?php endif; ?>
                </div>
              </div>

              <div class="mb-7">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-[0.14em] text-gray-700 dark:text-gray-200">Niveau</h3>
                <div class="space-y-3">
                  <?php foreach ($levelOptions as $levelOption): ?>
                    <label class="group flex cursor-pointer items-center gap-3 text-[1rem]">
                      <input
                        type="checkbox"
                        class="level-filter h-4 w-4 rounded-[4px] border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800"
                        data-level="<?php echo e($levelOption['key']); ?>"
                        value="<?php echo e($levelOption['key']); ?>"
                      >
                      <span class="text-gray-700 transition group-hover:text-blue-600 dark:text-gray-300 dark:group-hover:text-blue-300"><?php echo e($levelOption['label']); ?></span>
                      <span class="ml-auto inline-flex min-w-8 items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400"><?php echo e((string) $levelOption['count']); ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="mb-7">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-[0.14em] text-gray-700 dark:text-gray-200">Prix</h3>
                <div class="space-y-3 text-[1rem] text-gray-700 dark:text-gray-300">
                  <label class="hidden items-center gap-3">
                    <input type="radio" name="price" class="price-radio h-4 w-4 text-blue-600 focus:ring-blue-500" value="all" checked>
                    <span>Tous</span>
                  </label>
                  <label class="flex items-center gap-3">
                    <input type="radio" name="price" class="price-radio h-4 w-4 text-blue-600 focus:ring-blue-500" value="free">
                    <span>Gratuit</span>
                  </label>
                  <label class="flex items-center gap-3">
                    <input type="radio" name="price" class="price-radio h-4 w-4 text-blue-600 focus:ring-blue-500" value="paid">
                    <span>Payant</span>
                  </label>
                </div>
                <div class="mt-4 flex items-center gap-2">
                  <input
                    type="number"
                    id="minPriceInput"
                    min="0"
                    inputmode="numeric"
                    placeholder="Min"
                    class="w-full rounded-[10px] border border-gray-200 px-3 py-2 text-sm text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:focus:border-blue-400 dark:focus:ring-blue-500/20"
                  >
                  <span class="text-gray-400">-</span>
                  <input
                    type="number"
                    id="maxPriceInput"
                    min="0"
                    inputmode="numeric"
                    placeholder="Max"
                    class="w-full rounded-[10px] border border-gray-200 px-3 py-2 text-sm text-gray-700 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:focus:border-blue-400 dark:focus:ring-blue-500/20"
                  >
                </div>
                <?php if ($catalogPriceMax > 0): ?>
                  <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Entre <?php echo e($publicFormatMoney($catalogPriceMin)); ?> et <?php echo e($publicFormatMoney($catalogPriceMax)); ?></p>
                <?php endif; ?>
              </div>

              <div class="mb-8">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-[0.14em] text-gray-700 dark:text-gray-200">Note minimale</h3>
                <div class="flex items-center gap-1">
                  <?php for ($ratingIndex = 1; $ratingIndex <= 5; $ratingIndex++): ?>
                    <button
                      type="button"
                      class="rating-star inline-flex h-6 w-6 items-center justify-center text-lg text-yellow-400 transition hover:scale-105"
                      data-rating-star
                      data-rating="<?php echo e((string) $ratingIndex); ?>"
                      aria-label="Filtrer à partir de <?php echo e((string) $ratingIndex); ?> étoiles"
                    >
                      <i class="far fa-star" aria-hidden="true"></i>
                    </button>
                  <?php endfor; ?>
                  <span class="ml-1 text-sm text-gray-500 dark:text-gray-400"></span>
                </div>
                <span id="ratingValue" class="sr-only">0.0</span>
                <input type="range" id="ratingSlider" min="0" max="5" step="1" value="0" class="hidden">
              </div>

            </div>
          </aside>

          <section class="flex-1">
            <div class="catalog-results-toolbar p-3 sm:p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="filter-scroll flex min-w-0 max-w-full flex-1 items-center gap-2 overflow-x-auto pb-1">
                <button
                  id="mobileFilterToggleClone"
                  type="button"
                  data-filter-toggle
                  aria-controls="filterContainer"
                  aria-expanded="<?php echo $compactCatalogFilters ? 'false' : 'true'; ?>"
                  class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                >
                  <i class="fas fa-filter mr-1"></i> <?php echo $compactCatalogFilters ? 'Filtrer les formations' : 'Masquer les filtres'; ?>
                </button>
                <button
                  type="button"
                  data-quick-filter="all"
                  aria-pressed="true"
                  class="catalog-quick-filter whitespace-nowrap rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300"
                >
                  Tous
                </button>
                <button
                  type="button"
                  data-quick-filter="price"
                  data-filter-value="free"
                  aria-pressed="false"
                  class="catalog-quick-filter whitespace-nowrap rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300"
                >
                  Gratuit
                </button>
                <?php foreach (array_slice($categoryOptions, 0, 4) as $categoryOption): ?>
                  <button
                    type="button"
                    data-quick-filter="category"
                    data-filter-value="<?php echo e($categoryOption['key']); ?>"
                    aria-pressed="false"
                    class="catalog-quick-filter whitespace-nowrap rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300"
                  >
                    <?php echo e($categoryOption['label']); ?>
                  </button>
                <?php endforeach; ?>
                <?php foreach ($levelOptions as $levelOption): ?>
                  <?php if (($levelOption['key'] ?? '') === 'debutant' && (int) ($levelOption['count'] ?? 0) > 0): ?>
                    <button
                      type="button"
                      data-quick-filter="level"
                      data-filter-value="<?php echo e($levelOption['key']); ?>"
                      aria-pressed="false"
                      class="catalog-quick-filter whitespace-nowrap rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300"
                    >
                      <?php echo e($levelOption['label']); ?>
                    </button>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>

              <div class="ml-0 flex w-full items-center justify-end gap-2 sm:ml-auto sm:w-auto">
                <span class="text-sm text-gray-500 dark:text-gray-400">Trier par :</span>
                <select id="sortSelect" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/20">
                  <option value="popular">Popularité</option>
                  <option value="new">Date d'ajout</option>
                  <option value="rating">Note</option>
                  <option value="priceAsc">Prix croissant</option>
                </select>
              </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
              <p id="resultCount" class="text-sm text-gray-500 dark:text-gray-400">
                Affichage de <span class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) count($initialCatalogCourses)); ?></span> cours sur <?php echo e($courseCountLabel); ?>
              </p>
              <span id="catalogTotalCount" class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-200">
                <?php echo e($courseCountLabel); ?> cours
              </span>
            </div>

            <div id="activeFiltersChips" class="mt-3 hidden flex flex-wrap gap-2"></div>
            </div>

            <div id="coursesGrid" class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
              <?php foreach ($initialCatalogCourses as $course): ?>
                <?php
                $courseBadge = $resolveCatalogBadge($course);
                $courseTrust = $resolveCatalogTrust($course);
                $levelBadgeClass = $resolveLevelBadgeClass((string) ($course['level'] ?? ''));
                $serverRating = max(0.0, min(5.0, (float) ($course['rating'] ?? 0)));
                $courseDurationLabel = trim((string) ($course['durationLabel'] ?? ''));
                $courseBasePriceLabel = trim((string) ($course['basePriceLabel'] ?? ''));
                $coursePromoDiscountLabel = trim((string) ($course['promoDiscountLabel'] ?? ''));
                $coursePriceHint = trim((string) ($course['priceHint'] ?? 'Accès immédiat'));
                $courseHasPromo = !empty($course['promoActive']) && $coursePromoDiscountLabel !== '';
                $courseHasCoupon = !$courseHasPromo && !empty($course['couponAvailable']);
                $courseFormatLabel = trim((string) ($course['formatLabel'] ?? ''));
                $courseFormatIcon = trim((string) ($course['formatIcon'] ?? 'fa-video'));
                $courseObjectiveSnippet = trim((string) ($course['objectiveSnippet'] ?? ''));
                $coursePrerequisiteSnippet = trim((string) ($course['prerequisiteSnippet'] ?? ''));
                $courseDescriptionPreview = trim((string) ($course['description'] ?? ''));
                $coursePreviewPrimary = $courseObjectiveSnippet !== '' ? $courseObjectiveSnippet : $courseDescriptionPreview;
                $coursePreviewSecondary = $coursePrerequisiteSnippet;
                ?>
    <article class="catalog-course-card hover-lift group flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_18px_34px_-24px_rgba(15,23,42,0.28)] dark:border-gray-800 dark:bg-gray-900">
                  <div class="catalog-card__cover relative overflow-hidden">
                    <a href="<?php echo e((string) ($course['url'] ?? $catalogueUrl)); ?>" class="block h-full w-full">
                      <img
                        src="<?php echo e((string) ($course['image'] ?? '')); ?>"
                        alt="<?php echo e((string) ($course['title'] ?? 'Cours')); ?>"
                        class="catalog-card__cover-img transition duration-500 group-hover:scale-105"
                        loading="lazy"
                        decoding="async"
                      >
                    </a>
                    <?php if ((string) ($courseBadge['label'] ?? '') !== ''): ?>
                      <?php $courseBadgeMeta = trim((string) ($courseBadge['meta'] ?? '')); ?>
                      <span class="absolute left-3 top-3 z-[5] inline-flex max-w-[calc(100%-4rem)] flex-col items-start rounded-full px-2.5 py-1.5 backdrop-blur-sm <?php echo e((string) ($courseBadge['class'] ?? 'bg-black/65 text-white')); ?>">
                        <span class="text-[10px] font-semibold uppercase tracking-[0.18em] leading-none"><?php echo e((string) ($courseBadge['label'] ?? '')); ?></span>
                        <?php if ($courseBadgeMeta !== ''): ?>
                          <span class="mt-0.5 text-[9px] font-medium normal-case tracking-[0.04em] leading-none text-white/80"><?php echo e($courseBadgeMeta); ?></span>
                        <?php endif; ?>
                      </span>
                    <?php endif; ?>
                    <div class="catalog-card__hover-preview">
                      <div class="flex flex-wrap gap-1.5">
                        <?php if ($courseFormatLabel !== ''): ?>
                          <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold backdrop-blur-sm">
                            <i class="fas <?php echo e($courseFormatIcon !== '' ? $courseFormatIcon : 'fa-video'); ?>" aria-hidden="true"></i>
                            <?php echo e($courseFormatLabel); ?>
                          </span>
                        <?php endif; ?>
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold backdrop-blur-sm">
                          <i class="fas fa-signal" aria-hidden="true"></i>
                          <?php echo e((string) ($course['levelLabel'] ?? 'Tous niveaux')); ?>
                        </span>
                      </div>
                      <?php if ($coursePreviewPrimary !== ''): ?>
                        <p class="line-clamp-2 text-sm font-semibold leading-5"><?php echo e($coursePreviewPrimary); ?></p>
                      <?php endif; ?>
                      <?php if ($coursePreviewSecondary !== ''): ?>
                        <p class="line-clamp-1 text-xs leading-5 text-white/80">
                          <i class="fas fa-check mr-1 text-emerald-300" aria-hidden="true"></i><?php echo e($coursePreviewSecondary); ?>
                        </p>
                      <?php endif; ?>
                      <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-100">
                        Voir le détail <i class="fas fa-arrow-right" aria-hidden="true"></i>
                      </span>
                    </div>
                    <button
                      type="button"
                      class="catalog-card__favorite absolute right-3 top-3 z-10<?php echo !empty($course['isFavorite']) ? ' is-active' : ''; ?>"
                      data-course-action="favorite"
                      data-course-id="<?php echo e((string) ((int) ($course['id'] ?? 0))); ?>"
                      aria-label="<?php echo !empty($course['isFavorite']) ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>"
                      aria-pressed="<?php echo !empty($course['isFavorite']) ? 'true' : 'false'; ?>"
                    >
                      <i class="<?php echo !empty($course['isFavorite']) ? 'fas' : 'far'; ?> fa-heart" aria-hidden="true"></i>
                    </button>
                  </div>

                  <div class="flex flex-1 flex-col p-4">
                    <div class="mb-3 flex min-h-[5.45rem] items-start justify-between gap-3">
                      <div class="min-w-0 flex-1">
                        <h3 class="line-clamp-2 min-h-[2.5rem] text-base font-bold leading-tight text-gray-900 dark:text-white">
                          <a href="<?php echo e((string) ($course['url'] ?? $catalogueUrl)); ?>" class="transition hover:text-blue-600 dark:hover:text-blue-300">
                            <?php echo e((string) ($course['title'] ?? 'Cours')); ?>
                          </a>
                        </h3>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                          <div class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-gradient-to-r from-blue-50 to-cyan-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:from-blue-500/10 dark:to-cyan-500/10 dark:text-blue-200">
                            <i class="fas fa-chalkboard-user text-[10px]" aria-hidden="true"></i>
                            <span class="truncate" title="<?php echo e((string) ($course['teacher'] ?? 'Formateur')); ?>"><?php echo e((string) ($course['teacher'] ?? 'Formateur')); ?></span>
                          </div>
                        </div>
                      </div>
                      <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo e($levelBadgeClass); ?>">
                        <?php echo e((string) ($course['levelLabel'] ?? 'Tous niveaux')); ?>
                      </span>
                    </div>

                    <div class="mb-3 flex min-h-[2.35rem] flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                      <?php if ((int) ($course['ratingCount'] ?? 0) > 0): ?>
                        <span class="inline-flex items-center gap-1 text-yellow-500">
                          <i class="fas fa-star" aria-hidden="true"></i>
                          <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo e(number_format((float) ($course['rating'] ?? 0), 1, '.', '')); ?></span>
                        </span>
                        <span class="text-gray-400">·</span>
                        <span><?php echo e(view_pluralize((int) ($course['ratingCount'] ?? 0), 'avis', 'avis')); ?></span>
                      <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-1 font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                          <i class="fas <?php echo e((string) ($courseTrust['icon'] ?? 'fa-magic')); ?>" aria-hidden="true"></i>
                          <span><?php echo e((string) ($courseTrust['label'] ?? 'Nouveau cours')); ?></span>
                        </span>
                        <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-300">
                          <i class="fas fa-bolt text-[10px]" aria-hidden="true"></i>
                          <span><?php echo e((string) ($courseTrust['meta'] ?? 'Disponible maintenant')); ?></span>
                        </span>
                      <?php endif; ?>
                      <?php if ($courseDurationLabel !== ''): ?>
                        <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-300">
                          <i class="fas fa-clock text-[10px]" aria-hidden="true"></i>
                          <span><?php echo e($courseDurationLabel); ?></span>
                        </span>
                      <?php endif; ?>
                    </div>

                    <div class="mb-3 min-h-[4.3rem] rounded-lg border border-gray-200 bg-slate-50/90 px-3 py-2 dark:border-gray-800 dark:bg-gray-950/70">
                      <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                          <p class="text-[9px] font-semibold uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">Prix</p>
                          <div class="mt-0.5 flex flex-wrap items-baseline gap-1.5">
                            <div class="whitespace-nowrap text-base font-bold text-gray-900 dark:text-white"><?php echo e((string) ($course['priceLabel'] ?? 'Gratuit')); ?></div>
                            <?php if ($courseBasePriceLabel !== ''): ?>
                              <div class="whitespace-nowrap text-[11px] font-medium text-gray-400 line-through<?php echo $courseHasPromo ? ' text-rose-400 dark:text-rose-200/80' : ''; ?>"><?php echo e($courseBasePriceLabel); ?></div>
                            <?php endif; ?>
                          </div>
                          <p class="mt-0.5 text-[10px] font-medium text-gray-500 dark:text-gray-400"><?php echo e($coursePriceHint !== '' ? $coursePriceHint : 'Accès immédiat'); ?></p>
                        </div>
                        <?php if ($courseHasPromo || $courseHasCoupon): ?>
                          <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                            <?php if ($courseHasPromo): ?>
                              <span class="inline-flex items-center rounded-full bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 dark:bg-rose-500/15 dark:text-rose-200">
                                <?php echo e($coursePromoDiscountLabel); ?>
                              </span>
                              <?php if ((string) ($course['promoEndAt'] ?? '') !== ''): ?>
                                <span class="inline-flex items-center rounded-full bg-white px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 shadow-sm dark:bg-white/10 dark:text-rose-100" data-promo-countdown="<?php echo e((string) ($course['promoEndAt'] ?? '')); ?>">Fin dans --</span>
                              <?php endif; ?>
                            <?php elseif ($courseHasCoupon): ?>
                              <span class="inline-flex items-center rounded-full bg-cyan-100 px-1.5 py-0.5 text-[10px] font-semibold text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-200">
                                Coupon disponible
                              </span>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="mt-auto flex min-h-[2.75rem] items-center justify-end gap-2 pt-1">
                      <div class="flex items-center gap-2">
                        <button
                          type="button"
                          class="catalog-card__cart<?php echo !empty($course['isInCart']) ? ' is-active' : ''; ?>"
                          data-course-action="cart"
                          data-course-id="<?php echo e((string) ((int) ($course['id'] ?? 0))); ?>"
                          aria-label="<?php echo !empty($course['isInCart']) ? 'Retirer du panier' : 'Ajouter au panier'; ?>"
                          aria-pressed="<?php echo !empty($course['isInCart']) ? 'true' : 'false'; ?>"
                        >
                          <i class="fas fa-cart-shopping" aria-hidden="true"></i>
                        </button>
                        <a href="<?php echo e((string) ($course['url'] ?? $catalogueUrl)); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" title="<?php echo e($coursePrimaryActionLabel); ?>" aria-label="<?php echo e($coursePrimaryActionLabel); ?>">
                          <span><?php echo e($coursePrimaryActionLabel); ?></span>
                          <i class="fas <?php echo e($coursePrimaryActionIcon); ?>" aria-hidden="true"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <div class="mt-6 flex justify-center">
              <nav id="paginationNav" class="flex items-center gap-1"></nav>
            </div>

            <?php if ($compactCatalogFilters): ?>
              <div id="catalogSuggestionBlock" class="mt-8 overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-[0_18px_34px_-26px_rgba(37,99,235,0.35)] dark:border-blue-500/20 dark:bg-gray-900">
                <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between">
                  <div class="flex min-w-0 gap-4">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200">
                      <i class="fas fa-lightbulb" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                      <h2 class="text-lg font-bold text-gray-900 dark:text-white">Vous ne trouvez pas la formation recherchée ?</h2>
                      <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Dites-nous la compétence que vous souhaitez apprendre. Nous préparerons de nouveaux parcours selon les besoins des apprenants.
                      </p>
                    </div>
                  </div>
                  <a href="<?php echo e($suggestCourseUrl); ?>"<?php echo $suggestCourseTarget; ?> class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    <i class="<?php echo e($supportWhatsappDigits !== '' ? 'fab fa-whatsapp' : 'fas fa-headset'); ?>" aria-hidden="true"></i>
                    <?php echo e($suggestCourseButtonLabel); ?>
                  </a>
                </div>
              </div>
            <?php endif; ?>
          </section>
        </div>
      </div>
    </section>
  </main>

  <?php require __DIR__ . '/_public_footer_home.php'; ?>

  <div class="fixed bottom-4 right-4 z-40 flex flex-col items-end gap-2 sm:bottom-5 sm:right-5 sm:gap-3">
    <a
      href="<?php echo e($supportUrl); ?>"
      class="pointer-events-none inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-600 text-white opacity-0 shadow-[0_18px_35px_-15px_rgba(37,99,235,0.9)] transition hover:bg-blue-700 sm:pointer-events-auto sm:h-12 sm:w-12 sm:opacity-100"
      data-support-fab
      aria-label="Assistance"
      title="Assistance"
    >
      <i class="fas fa-headset" aria-hidden="true"></i>
    </a>
    <button
      type="button"
      class="pointer-events-none inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-600 text-white opacity-0 shadow-[0_18px_35px_-15px_rgba(37,99,235,0.9)] transition duration-200 hover:bg-blue-700 dark:bg-blue-500 dark:text-white dark:hover:bg-blue-600 sm:h-12 sm:w-12"
      data-scroll-top
      aria-label="Remonter en haut"
      title="Remonter en haut"
    >
      <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </button>
  </div>

  <script id="catalog-courses-data" type="application/json"><?php echo $catalogCoursesJson; ?></script>
</body>
</html>
