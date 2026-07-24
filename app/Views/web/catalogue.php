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
  <text x="56" y="88" fill="#dbeafe" font-size="30" font-family="Plus Jakarta Sans, Inter, Arial, sans-serif">{$safeCategory}</text>
  <text x="56" y="182" fill="#ffffff" font-size="46" font-weight="700" font-family="Plus Jakarta Sans, Inter, Arial, sans-serif">{$safeTitle}</text>
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
$supportModalJsUrl = site_url('assets/js/support_modal.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/support_modal.js') ?: time()));
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
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($webMarketingJsUrl); ?>" defer></script>
  <script src="<?php echo e($catalogJsUrl); ?>" defer></script>
  <script src="<?php echo e($supportModalJsUrl); ?>" defer></script>
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
    #coursesGrid {
      grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
      gap: 1.15rem;
    }
    .catalog-course-card {
      position: relative;
      display: flex;
      min-width: 0;
      overflow: hidden;
      border: 1px solid rgba(226, 232, 240, 0.9);
      border-radius: 1.5rem;
      background: rgba(255, 255, 255, 0.98);
      box-shadow: 0 18px 34px -24px rgba(15, 23, 42, 0.28);
      transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }
    .catalog-course-card:hover,
    .catalog-course-card:focus-within {
      transform: translateY(-4px);
      border-color: rgba(37, 99, 235, 0.18);
      box-shadow: 0 24px 42px -28px rgba(15, 23, 42, 0.34);
    }
    .catalog-course-card__media {
      position: relative;
      aspect-ratio: 16 / 9;
      overflow: hidden;
      background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.3), transparent 42%),
        linear-gradient(140deg, rgba(37, 99, 235, 0.12), rgba(15, 118, 110, 0.12), rgba(180, 83, 9, 0.12));
    }
    html.dark .catalog-course-card {
      border-color: rgba(55, 65, 81, 0.88);
      background: rgba(17, 24, 39, 0.96);
      box-shadow: 0 18px 34px -24px rgba(0, 0, 0, 0.42);
    }
    html.dark .catalog-course-card:hover,
    html.dark .catalog-course-card:focus-within {
      border-color: rgba(96, 165, 250, 0.28);
      box-shadow: 0 24px 42px -28px rgba(0, 0, 0, 0.52);
    }
    html.dark .catalog-course-card__media {
      background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.08), transparent 42%),
        linear-gradient(140deg, rgba(59, 130, 246, 0.12), rgba(6, 182, 212, 0.1), rgba(15, 23, 42, 0.16));
    }
    .catalog-course-card__media-link {
      display: block;
      width: 100%;
      height: 100%;
    }
    .catalog-course-card__media-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
      transition: transform 0.5s ease;
    }
    .catalog-course-card:hover .catalog-course-card__media-img,
    .catalog-course-card:focus-within .catalog-course-card__media-img {
      transform: scale(1.06);
      filter: brightness(0.42) saturate(0.85);
    }
    .catalog-course-card__favorite {
      display: inline-flex;
      flex: 0 0 auto;
      align-items: center;
      justify-content: center;
      width: 2.25rem;
      height: 2.25rem;
      border: 1px solid rgba(148, 163, 184, 0.22);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.94);
      color: #8b5cf6;
      box-shadow: 0 12px 26px -18px rgba(15, 23, 42, 0.4);
      backdrop-filter: blur(10px);
      cursor: pointer;
      transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, opacity 0.2s ease;
      opacity: 0.9;
    }
    .catalog-course-card__favorite:hover,
    .catalog-course-card__favorite:focus-visible {
      border-color: rgba(139, 92, 246, 0.34);
      color: #7c3aed;
      opacity: 1;
      transform: translateY(-1px);
    }
    .catalog-course-card__favorite.is-active {
      border-color: rgba(139, 92, 246, 0.3);
      background: rgba(243, 232, 255, 0.98);
      color: #7c3aed;
      opacity: 1;
    }
    .catalog-course-card__hover-overlay {
      position: absolute;
      inset: 0;
      z-index: 30;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.18) 0%, rgba(15, 23, 42, 0.88) 100%);
      backdrop-filter: blur(12px);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.22s ease;
    }
    .catalog-course-card:hover .catalog-course-card__hover-overlay,
    .catalog-course-card:focus-within .catalog-course-card__hover-overlay {
      opacity: 1;
      pointer-events: auto;
    }
    .catalog-course-card__hover-button {
      display: inline-flex;
      align-items: center;
      gap: 0.65rem;
      padding: 0.95rem 1.35rem;
      border-radius: 9999px;
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: #fff;
      font-size: 0.92rem;
      font-weight: 800;
      letter-spacing: 0.01em;
      text-decoration: none;
      box-shadow: 0 20px 42px -24px rgba(37, 99, 235, 0.78);
      transform: translateY(8px) scale(0.98);
      transition: transform 0.22s ease, box-shadow 0.22s ease, filter 0.22s ease;
      pointer-events: auto;
    }
    .catalog-course-card:hover .catalog-course-card__hover-button,
    .catalog-course-card:focus-within .catalog-course-card__hover-button {
      transform: translateY(0) scale(1);
    }
    .catalog-course-card__hover-button:hover,
    .catalog-course-card__hover-button:focus-visible {
      filter: brightness(1.04);
      outline: none;
      box-shadow: 0 24px 48px -26px rgba(37, 99, 235, 0.82);
    }
    html.dark .catalog-course-card__hover-overlay {
      background: linear-gradient(180deg, rgba(2, 6, 23, 0.22) 0%, rgba(2, 6, 23, 0.92) 100%);
    }
    .catalog-course-category-badge {
      position: absolute;
      top: 0.75rem;
      left: 0.75rem;
      z-index: 20;
      display: inline-flex;
      max-width: calc(100% - 5rem);
      align-items: center;
      overflow: hidden;
      padding: 0.42rem 0.8rem;
      border: 1px solid rgba(255, 255, 255, 0.26);
      border-radius: 9999px;
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.96) 0%, rgba(14, 165, 233, 0.96) 100%);
      color: #fff;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      line-height: 1;
      white-space: nowrap;
      text-overflow: ellipsis;
      box-shadow: 0 16px 30px -20px rgba(37, 99, 235, 0.82);
      backdrop-filter: blur(10px);
      pointer-events: none;
    }
    .catalog-course-duration-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      white-space: nowrap;
      padding: 0.42rem 0.8rem;
      border: 1px solid rgba(255, 255, 255, 0.22);
      border-radius: 9999px;
      background: rgba(255, 255, 255, 0.94);
      color: #c2410c;
      font-size: 0.78rem;
      font-weight: 800;
      line-height: 1;
      box-shadow: 0 14px 28px -20px rgba(15, 23, 42, 0.32);
      backdrop-filter: blur(10px);
    }
    html.dark .catalog-course-duration-badge {
      border-color: rgba(253, 186, 116, 0.28);
      background: rgba(15, 23, 42, 0.92);
      color: #fdba74;
      box-shadow: 0 16px 32px -20px rgba(0, 0, 0, 0.45);
    }
    .catalog-course-badge {
      position: absolute;
      top: 0.75rem;
      left: 0.75rem;
      z-index: 20;
      display: inline-flex;
      max-width: calc(100% - 5rem);
      flex-direction: column;
      align-items: flex-start;
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
    .catalog-course-badge__meta {
      margin-top: 0.2rem;
      font-size: 0.6rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      line-height: 1;
      text-transform: none;
      color: rgba(255, 255, 255, 0.86);
    }
    html.dark .catalog-course-card__favorite {
      border-color: rgba(71, 85, 105, 0.72);
      background: rgba(15, 23, 42, 0.86);
      color: #c4b5fd;
    }
    html.dark .catalog-course-card__favorite:hover,
    html.dark .catalog-course-card__favorite:focus-visible {
      border-color: rgba(167, 139, 250, 0.4);
      color: #ddd6fe;
    }
    html.dark .catalog-course-card__favorite.is-active {
      border-color: rgba(167, 139, 250, 0.45);
      background: rgba(49, 46, 129, 0.42);
      color: #ddd6fe;
    }
    .catalog-course-card__body {
      display: flex;
      flex-direction: column;
      gap: 0.85rem;
      min-width: 0;
      padding: 1rem 1rem 1.05rem;
    }
    .catalog-course-card__author {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      min-width: 0;
      color: #2563eb;
      font-size: 0.88rem;
      font-weight: 600;
    }
    html.dark .catalog-course-card__author {
      color: #e2e8f0;
    }
    .catalog-course-card__author-icon {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
      width: 2rem;
      height: 2rem;
      border-radius: 999px;
      background: rgba(239, 246, 255, 0.95);
      color: #2563eb;
      box-shadow: 0 10px 22px -18px rgba(37, 99, 235, 0.45);
    }
    html.dark .catalog-course-card__author-icon {
      background: rgba(37, 99, 235, 0.14);
      color: #93c5fd;
      box-shadow: 0 10px 22px -18px rgba(37, 99, 235, 0.35);
    }
    .catalog-course-card__author-icon i {
      color: inherit;
      line-height: 1;
    }
    .catalog-course-card__author-name {
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      color: #1d4ed8;
      font-weight: 700;
    }
    html.dark .catalog-course-card__author-name {
      color: #f8fafc;
    }
    .catalog-course-card__title {
      margin: 0;
      color: #0f172a;
      font-size: 1.08rem;
      font-weight: 800;
      line-height: 1.28;
      letter-spacing: -0.02em;
      overflow: hidden;
      display: -webkit-box;
      -webkit-box-orient: vertical;
      -webkit-line-clamp: 2;
    }
    html.dark .catalog-course-card__title {
      color: #f8fafc;
    }
    .catalog-course-card__title-link {
      color: inherit;
      text-decoration: none;
    }
    .catalog-course-card__rating {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.35rem 0.5rem;
      min-height: 1.25rem;
      color: #64748b;
      font-size: 0.84rem;
      font-weight: 600;
    }
    html.dark .catalog-course-card__rating {
      color: #cbd5e1;
    }
    .catalog-course-card__rating-stars {
      display: inline-flex;
      gap: 0.1rem;
      line-height: 1;
    }
    .catalog-course-card__rating-stars .fas {
      color: #f59e0b;
    }
    .catalog-course-card__rating-stars .far {
      color: rgba(245, 158, 11, 0.34);
    }
    html.dark .catalog-course-card__rating-stars .fas {
      color: #fbbf24;
    }
    html.dark .catalog-course-card__rating-stars .far {
      color: rgba(251, 191, 36, 0.34);
    }
    .catalog-course-card__rating-value {
      color: #0f172a;
      font-weight: 800;
    }
    html.dark .catalog-course-card__rating-value {
      color: #f8fafc;
    }
    .catalog-course-card__footer {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 0.9rem;
      margin-top: auto;
      padding-top: 0.9rem;
      border-top: 1px solid rgba(226, 232, 240, 0.8);
    }
    html.dark .catalog-course-card__footer {
      border-top-color: rgba(55, 65, 81, 0.92);
    }
    .catalog-course-card__pricing {
      display: grid;
      gap: 0.14rem;
      min-width: 0;
    }
    .catalog-course-card__price-label {
      color: #94a3b8;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }
    .catalog-course-card__price {
      color: #ea580c;
      font-size: 1.08rem;
      font-weight: 900;
      line-height: 1.1;
      letter-spacing: -0.02em;
      font-variant-numeric: tabular-nums;
    }
    html.dark .catalog-course-card__price,
    html[data-theme="dark"] .catalog-course-card__price {
      color: #fdba74;
    }
    .catalog-course-card__price.app-price--promo {
      color: #dc2626;
    }
    html.dark .catalog-course-card__price.app-price--promo,
    html[data-theme="dark"] .catalog-course-card__price.app-price--promo {
      color: #fca5a5;
    }
    .catalog-course-card__base-price {
      color: #94a3b8;
      font-size: 0.75rem;
      font-weight: 600;
      text-decoration: line-through;
    }
    .catalog-course-card__cta {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 8.2rem;
      min-height: 2.8rem;
      padding: 0.75rem 1rem;
      border-radius: 0.95rem;
      border: 0;
      appearance: none;
      cursor: pointer;
      gap: 0.5rem;
      color: #ffffff;
      font-size: 0.88rem;
      font-weight: 800;
      text-decoration: none;
      white-space: nowrap;
      transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
    }
    .catalog-course-card__cta i {
      color: inherit;
      flex: 0 0 auto;
      font-size: 0.96em;
      line-height: 1;
    }
    .catalog-course-card__cta:hover,
    .catalog-course-card__cta:focus-visible {
      transform: translateY(-1px);
      filter: brightness(1.02);
    }
    .catalog-course-card__cta--primary {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      box-shadow: 0 16px 30px -20px rgba(37, 99, 235, 0.7);
    }
    .catalog-course-card__cta--success {
      background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
      box-shadow: 0 16px 30px -20px rgba(22, 163, 74, 0.72);
    }
    @media (max-width: 640px) {
      #coursesGrid {
        grid-template-columns: 1fr;
      }
      .catalog-course-card__body {
        padding: 0.95rem 0.95rem 1rem;
      }
      .catalog-course-card__cta {
        min-width: 7.6rem;
        padding-inline: 0.85rem;
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
        position: static;
        top: auto;
        margin-bottom: 0.85rem;
        padding: 0.65rem;
        border-radius: 14px;
        box-shadow: 0 12px 28px -26px rgba(15, 23, 42, 0.35);
      }
      .catalog-results-toolbar > .flex {
        gap: 0.5rem;
      }
      .catalog-results-toolbar .filter-scroll {
        gap: 0.35rem;
        padding-bottom: 0;
      }
      .catalog-results-toolbar .filter-scroll::-webkit-scrollbar {
        height: 3px;
      }
      .catalog-results-toolbar [data-filter-toggle],
      .catalog-results-toolbar [data-quick-filter] {
        min-height: 2.25rem;
        padding: 0.45rem 0.75rem;
        font-size: 0.75rem;
        line-height: 1.1;
      }
      .catalog-results-toolbar #sortSelect {
        min-height: 2.25rem;
        padding: 0.4rem 0.65rem;
        font-size: 0.75rem;
      }
      .catalog-results-toolbar #resultCount {
        font-size: 0.75rem;
        line-height: 1.25rem;
      }
      .catalog-results-toolbar #catalogTotalCount {
        padding: 0.3rem 0.65rem;
        font-size: 0.72rem;
      }
      .catalog-results-toolbar #activeFiltersChips {
        margin-top: 0.5rem;
        gap: 0.35rem;
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
        align-items: stretch;
        padding: 0.75rem;
        pointer-events: auto;
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
        height: 100%;
        max-height: none;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        pointer-events: auto;
        border-radius: 1.25rem;
        box-shadow: 0 -24px 60px -32px rgba(15, 23, 42, 0.55);
      }
    }
    @media (max-width: 639px) {
      .catalog-filter-shell {
        padding: 0.5rem;
      }
      .catalog-filter-shell #filterContainer {
        border-radius: 1rem;
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
  data-checkout-url="<?php echo e(site_url('checkout')); ?>"
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
              <div class="mb-7 flex items-center justify-between gap-3">
                <h2 class="text-[1.15rem] font-bold text-gray-900 dark:text-white">
                  <i class="fas fa-sliders-h mr-2 text-blue-500"></i>Filtres
                </h2>
                <div class="flex items-center gap-2">
                  <button id="resetFiltersBtn" type="button" class="text-sm font-medium text-blue-600 transition hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">Réinitialiser</button>
                  <button
                    type="button"
                    data-filter-close
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 lg:hidden dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300"
                    aria-label="Fermer les filtres"
                  >
                    <i class="fas fa-times" aria-hidden="true"></i>
                  </button>
                </div>
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

            <div id="coursesGrid" class="grid gap-5">
              <?php foreach ($initialCatalogCourses as $course): ?><?php if (false): ?>
                <?php
                $courseBadge = $resolveCatalogBadge($course);
                $courseTrust = $resolveCatalogTrust($course);
                $levelBadgeClass = $resolveLevelBadgeClass((string) ($course['level'] ?? ''));
                $serverRating = max(0.0, min(5.0, (float) ($course['rating'] ?? 0)));
                $courseDurationLabel = trim((string) ($course['durationLabel'] ?? ''));
                $courseBasePriceLabel = trim((string) ($course['basePriceLabel'] ?? ''));
                $coursePromoDiscountLabel = trim((string) ($course['promoDiscountLabel'] ?? ''));
                $coursePriceHint = trim((string) ($course['priceHint'] ?? 'Accès immédiat'));
                $coursePromoBadgeLabel = $coursePromoDiscountLabel !== '' ? $coursePromoDiscountLabel : 'Promotion';
                $courseHasPromo = !empty($course['promoActive']) && (($courseBasePriceLabel !== '' && $courseBasePriceLabel !== $coursePriceLabel) || (string) ($course['promoEndAt'] ?? '') !== '' || $coursePromoDiscountLabel !== '');
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
                      <span class="catalog-course-badge">
                        <span><?php echo e((string) ($courseBadge['label'] ?? '')); ?></span>
                        <?php if ($courseBadgeMeta !== ''): ?>
                          <span class="catalog-course-badge__meta"><?php echo e($courseBadgeMeta); ?></span>
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
                            <i class="fas fa-chalkboard-teacher text-[10px]" aria-hidden="true"></i>
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
                            <div class="whitespace-nowrap text-base font-bold app-price"><?php echo e((string) ($course['priceLabel'] ?? 'Gratuit')); ?></div>
                            <?php if ($courseBasePriceLabel !== ''): ?>
                              <div class="whitespace-nowrap text-[11px] font-medium text-gray-400 line-through<?php echo $courseHasPromo ? ' text-rose-400 dark:text-rose-200/80' : ''; ?>"><?php echo e($courseBasePriceLabel); ?></div>
                            <?php endif; ?>
                          </div>
                          <p class="mt-0.5 text-[10px] font-medium text-gray-500 dark:text-gray-400"><?php echo e($coursePriceHint !== '' ? $coursePriceHint : 'Accès immédiat'); ?></p>
                        </div>
                        <?php if ($courseHasPromo || $courseHasCoupon): ?>
                          <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                            <?php if ($courseHasPromo): ?>
                              <span class="inline-flex items-center whitespace-nowrap rounded-full bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 dark:bg-rose-500/15 dark:text-rose-200">
                                <?php echo e($coursePromoDiscountLabel); ?>
                              </span>
                              <?php if ((string) ($course['promoEndAt'] ?? '') !== ''): ?>
                                <span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white shadow-sm dark:bg-red-500 dark:text-white" data-promo-countdown="<?php echo e((string) ($course['promoEndAt'] ?? '')); ?>">Fin dans --</span>
                              <?php endif; ?>
                            <?php elseif ($courseHasCoupon): ?>
                              <span class="inline-flex items-center whitespace-nowrap rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                                Code promo dispo
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
              <?php endif; ?>
              <?php
              $serverRating = max(0.0, min(5.0, (float) ($course['rating'] ?? 0)));
              $ratingFullStars = (int) floor($serverRating);
              $ratingFraction = $serverRating - $ratingFullStars;
              $ratingHasHalfStar = $ratingFraction >= 0.25 && $ratingFraction < 0.75;
              if ($ratingFraction >= 0.75) {
                  $ratingFullStars++;
                  $ratingHasHalfStar = false;
              }
              $ratingFullStars = min(5, $ratingFullStars);
              $ratingEmptyStars = max(0, 5 - $ratingFullStars - ($ratingHasHalfStar ? 1 : 0));
              $courseId = (int) ($course['id'] ?? 0);
              $courseTitle = trim((string) ($course['title'] ?? 'Cours'));
              $courseDetailsUrl = trim((string) ($course['url'] ?? $catalogueUrl));
              $coursePrice = max(0, (int) ($course['price'] ?? 0));
              $courseInCart = !empty($course['isInCart']);
              $courseCheckoutUrl = site_url('checkout');
              $courseCtaLabel = $coursePrice > 0 ? ($courseInCart ? 'Accéder au panier' : 'Ajouter au panier') : 'Commencer';
              $courseCtaTone = $coursePrice > 0 ? ($courseInCart ? 'success' : 'primary') : 'primary';
              $courseCtaIconClass = $coursePrice > 0 ? ($courseInCart ? 'fa-arrow-right' : 'fa-cart-shopping') : 'fa-circle-play';
              $courseTeacher = trim((string) ($course['teacher'] ?? 'Formateur'));
              $coursePriceLabel = trim((string) ($course['priceLabel'] ?? ($coursePrice > 0 ? $publicFormatMoney($coursePrice) : 'Gratuit')));
              $courseBasePriceLabel = trim((string) ($course['basePriceLabel'] ?? ''));
              $courseCategory = trim((string) ($course['category'] ?? 'Formation'));
              $courseDurationLabel = trim((string) ($course['durationLabel'] ?? ''));
              $courseEnrolledCount = max(0, (int) ($course['enrolledCount'] ?? 0));
              $coursePriceHint = trim((string) ($course['priceHint'] ?? 'Accès immédiat'));
              $courseFavoriteLabel = !empty($course['isFavorite']) ? 'Retirer des favoris' : 'Ajouter aux favoris';
              $coursePromoDiscountLabel = trim((string) ($course['promoDiscountLabel'] ?? ''));
              $coursePromoBadgeLabel = $coursePromoDiscountLabel !== '' ? $coursePromoDiscountLabel : 'Promotion';
              $courseHasPromo = !empty($course['promoActive']) && (($courseBasePriceLabel !== '' && $courseBasePriceLabel !== $coursePriceLabel) || (string) ($course['promoEndAt'] ?? '') !== '' || $coursePromoDiscountLabel !== '');
              $courseHasCoupon = !$courseHasPromo && !empty($course['couponAvailable']);
              $coursePriceClass = 'catalog-course-card__price app-price' . ($courseHasPromo ? ' app-price--promo' : '');
              ?>
                <article class="catalog-course-card hover-lift group flex h-full flex-col">
                  <div class="catalog-course-card__media">
                    <a href="<?php echo e($courseDetailsUrl); ?>" class="catalog-course-card__media-link">
                      <img
                        src="<?php echo e((string) ($course['image'] ?? '')); ?>"
                        alt="<?php echo e($courseTitle); ?>"
                        class="catalog-course-card__media-img"
                        loading="lazy"
                        decoding="async"
                      >
                    </a>
                    <?php if ($courseCategory !== ''): ?>
                      <span class="catalog-course-category-badge">
                        <?php echo e($courseCategory); ?>
                      </span>
                    <?php endif; ?>
                    <button
                      type="button"
                      class="catalog-course-card__favorite<?php echo !empty($course['isFavorite']) ? ' is-active' : ''; ?>"
                      data-course-action="favorite"
                      data-course-id="<?php echo e((string) $courseId); ?>"
                      aria-label="<?php echo e($courseFavoriteLabel); ?>"
                      aria-pressed="<?php echo !empty($course['isFavorite']) ? 'true' : 'false'; ?>"
                    >
                      <i class="<?php echo !empty($course['isFavorite']) ? 'fas' : 'far'; ?> fa-heart" aria-hidden="true"></i>
                    </button>
                    <?php if ($courseDurationLabel !== ''): ?>
                      <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/55 via-slate-950/5 to-transparent px-4 pb-4 pt-8">
                        <div class="flex justify-end">
                          <span class="catalog-course-duration-badge">
                            <i class="far fa-clock text-[10px]" aria-hidden="true"></i>
                            <?php echo e($courseDurationLabel); ?>
                          </span>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="catalog-course-card__body">
                    <div class="catalog-course-card__author">
                      <span class="catalog-course-card__author-icon" aria-hidden="true">
                        <i class="fas fa-chalkboard-teacher text-xs" aria-hidden="true"></i>
                      </span>
                      <span class="catalog-course-card__author-name" title="<?php echo e($courseTeacher); ?>"><?php echo e($courseTeacher); ?></span>
                    </div>

                    <h3 class="catalog-course-card__title">
                      <a href="<?php echo e($courseDetailsUrl); ?>" class="catalog-course-card__title-link"><?php echo e($courseTitle); ?></a>
                    </h3>

                    <div class="catalog-course-card__rating">
                      <?php if ((int) ($course['ratingCount'] ?? 0) > 0): ?>
                        <span class="catalog-course-card__rating-stars" aria-hidden="true">
                          <?php for ($starIndex = 0; $starIndex < $ratingFullStars; $starIndex++): ?>
                            <i class="fas fa-star"></i>
                          <?php endfor; ?>
                          <?php if ($ratingHasHalfStar): ?>
                            <i class="fas fa-star-half-alt"></i>
                          <?php endif; ?>
                          <?php for ($starIndex = 0; $starIndex < $ratingEmptyStars; $starIndex++): ?>
                            <i class="far fa-star"></i>
                          <?php endfor; ?>
                        </span>
                        <span class="catalog-course-card__rating-value"><?php echo e(number_format($serverRating, 1, '.', '')); ?></span>
                        <span><?php echo e(view_pluralize((int) ($course['ratingCount'] ?? 0), 'Avis', 'Avis')); ?></span>
                      <?php else: ?>
                        <span class="catalog-course-card__rating-stars" aria-hidden="true">
                          <?php for ($starIndex = 0; $starIndex < 5; $starIndex++): ?>
                            <i class="far fa-star"></i>
                          <?php endfor; ?>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-[0.72rem] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                          <i class="fas fa-sparkles" aria-hidden="true"></i>
                          <span>Nouveau</span>
                        </span>
                        <?php if ($courseEnrolledCount > 0): ?>
                          <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[0.72rem] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                            <i class="fas fa-users text-[10px]" aria-hidden="true"></i>
                            <span><?php echo e(view_pluralize($courseEnrolledCount, 'inscrit', 'inscrits')); ?></span>
                          </span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>

                    <div class="catalog-course-card__footer">
                      <div class="catalog-course-card__pricing">
                        <span class="catalog-course-card__price-label">Prix</span>
                        <strong class="<?php echo e($coursePriceClass); ?>"><?php echo e($coursePriceLabel); ?></strong>
                        <?php if ($courseBasePriceLabel !== '' && $courseBasePriceLabel !== $coursePriceLabel): ?>
                          <span class="catalog-course-card__base-price"><?php echo e($courseBasePriceLabel); ?></span>
                        <?php endif; ?>
                        <span class="text-[0.78rem] font-medium text-slate-500 dark:text-slate-400"><?php echo e($coursePriceHint !== '' ? $coursePriceHint : 'Accès immédiat'); ?></span>
                        <?php if ($courseHasPromo || $courseHasCoupon): ?>
                          <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <?php if ($courseHasPromo): ?>
                              <span class="inline-flex items-center whitespace-nowrap rounded-full bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 dark:bg-rose-500/15 dark:text-rose-200">
                                <?php echo e($coursePromoBadgeLabel); ?>
                              </span>
                              <?php if ((string) ($course['promoEndAt'] ?? '') !== ''): ?>
                                <span class="inline-flex items-center whitespace-nowrap rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white shadow-sm dark:bg-red-500 dark:text-white" data-promo-countdown="<?php echo e((string) ($course['promoEndAt'] ?? '')); ?>">Fin dans --</span>
                              <?php endif; ?>
                            <?php elseif ($courseHasCoupon): ?>
                              <span class="inline-flex items-center whitespace-nowrap rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                                Code promo dispo
                              </span>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                      <?php if ($coursePrice > 0 && !$courseInCart): ?>
                        <button
                          type="button"
                          class="catalog-course-card__cta catalog-course-card__cta--<?php echo e($courseCtaTone); ?>"
                          data-course-action="cart"
                          data-course-id="<?php echo e((string) $courseId); ?>"
                          aria-label="<?php echo e($courseCtaLabel); ?>"
                          aria-pressed="false"
                        >
                          <i class="fas <?php echo e($courseCtaIconClass); ?>" aria-hidden="true"></i>
                          <span><?php echo e($courseCtaLabel); ?></span>
                        </button>
                      <?php elseif ($coursePrice > 0): ?>
                        <a href="<?php echo e($courseCheckoutUrl); ?>" class="catalog-course-card__cta catalog-course-card__cta--<?php echo e($courseCtaTone); ?>">
                          <i class="fas <?php echo e($courseCtaIconClass); ?>" aria-hidden="true"></i>
                          <span><?php echo e($courseCtaLabel); ?></span>
                        </a>
                      <?php else: ?>
                        <a href="<?php echo e($courseDetailsUrl); ?>" class="catalog-course-card__cta catalog-course-card__cta--<?php echo e($courseCtaTone); ?>">
                          <i class="fas <?php echo e($courseCtaIconClass); ?>" aria-hidden="true"></i>
                          <span><?php echo e($courseCtaLabel); ?></span>
                        </a>
                      <?php endif; ?>
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
    <button
      type="button"
      class="pointer-events-none inline-flex h-11 w-11 items-center justify-center rounded-full bg-blue-600 text-white opacity-0 shadow-[0_18px_35px_-15px_rgba(37,99,235,0.9)] transition hover:bg-blue-700 sm:pointer-events-auto sm:h-12 sm:w-12 sm:opacity-100"
      data-support-fab
      data-support-url="<?php echo e($supportUrl); ?>"
      aria-label="Assistance"
      title="Assistance"
    >
      <i class="fas fa-headset" aria-hidden="true"></i>
    </button>
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
