<?php
require_once __DIR__ . '/../../../includes/helpers.php';

$isLoggedIn = !empty($isLoggedIn);

$siteIdentity = function_exists('site_public_identity') ? site_public_identity() : [];
$siteName = trim((string) ($siteName ?? ($siteIdentity['siteName'] ?? '')));
if ($siteName === '') {
    $siteName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
}
$siteTagline = trim((string) ($siteTagline ?? ($siteIdentity['siteTagline'] ?? '')));
$siteDescription = trim((string) ($siteDescription ?? ($siteIdentity['siteDescription'] ?? '')));
$supportEmail = trim((string) ($supportEmail ?? ($siteIdentity['supportEmail'] ?? '')));
$supportPhone = trim((string) ($supportPhone ?? ($siteIdentity['supportPhone'] ?? '')));
$supportWhatsapp = trim((string) ($supportWhatsapp ?? ($siteIdentity['supportWhatsapp'] ?? '')));
$siteAddress = trim((string) ($siteAddress ?? ($siteIdentity['siteAddress'] ?? '')));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ($siteIdentity['siteLogoUrl'] ?? '')));
$footerText = trim((string) ($footerText ?? ($siteIdentity['footerText'] ?? '')));
$dashboardUrl = trim((string) ($dashboardUrl ?? site_url('student/dashboard')));
$publicPage = trim((string) ($publicPage ?? 'home'));
$publicBrandSubtitle = trim((string) ($publicBrandSubtitle ?? ($siteTagline !== '' ? $siteTagline : ($siteDescription !== '' ? $siteDescription : 'Des formations numériques conçues pour devenir utiles.'))));
$publicHeaderMetaLabel = trim((string) ($publicHeaderMetaLabel ?? ($isLoggedIn ? 'Compte connecté' : 'Déjà membre ?')));
$publicHeaderSecondaryLabel = trim((string) ($publicHeaderSecondaryLabel ?? ($isLoggedIn ? 'Mon profil' : 'Se connecter')));
$publicHeaderSecondaryUrl = trim((string) ($publicHeaderSecondaryUrl ?? ($isLoggedIn ? site_url('profile') : site_url('login'))));
$publicHeaderPrimaryLabel = trim((string) ($publicHeaderPrimaryLabel ?? ($isLoggedIn ? 'Accéder à mon espace' : 'Créer un compte')));
$publicHeaderPrimaryUrl = trim((string) ($publicHeaderPrimaryUrl ?? ($isLoggedIn ? $dashboardUrl : site_url('register'))));
$publicFooterNote = trim((string) ($publicFooterNote ?? ($siteDescription !== '' ? $siteDescription : "L'espace public est pensé pour découvrir les formations. Les espaces personnels restent dédiés au suivi et à l'apprentissage.")));

$publicAnnouncement = is_array($publicAnnouncement ?? null) ? $publicAnnouncement : [];

$homeUrl = site_url('');
$catalogueUrl = site_url('catalogue');
$teacherApplyUrl = site_url('become-teacher');
$supportUrl = site_url('support');
$privacyUrl = site_url('privacy');
$cguUrl = site_url('cgu');
$loginUrl = site_url('login');
$registerUrl = site_url('register');
$notificationsUrl = site_url('notifications');
$profileUrl = site_url('profile');
$logoutUrl = site_url('logout');
$totalUnread = max(0, (int) ($notifCount ?? 0) + (int) ($supportUnreadCount ?? 0));

$publicNavLinks = [
    ['key' => 'catalogue', 'label' => 'Catalogue', 'href' => $catalogueUrl],
    ['key' => 'parcours', 'label' => 'Parcours', 'href' => $homeUrl . '#parcours'],
    ['key' => 'entreprises', 'label' => 'Entreprises', 'href' => $homeUrl . '#entreprises'],
    ['key' => 'support', 'label' => 'Support', 'href' => $supportUrl],
];

$publicFooterColumns = [
    [
        'title' => 'Explorer',
        'links' => [
            ['label' => 'Accueil', 'href' => $homeUrl],
            ['label' => 'Catalogue', 'href' => $catalogueUrl],
            ['label' => 'Parcours', 'href' => $homeUrl . '#parcours'],
            ['label' => 'Devenir enseignant', 'href' => $teacherApplyUrl],
        ],
    ],
    [
        'title' => 'Accompagnement',
        'links' => [
            ['label' => 'Support', 'href' => $supportUrl],
            ['label' => 'Entreprises', 'href' => $homeUrl . '#entreprises'],
            ['label' => 'Confidentialité', 'href' => $privacyUrl],
            ['label' => 'CGU', 'href' => $cguUrl],
        ],
    ],
    [
        'title' => $isLoggedIn ? 'Mon espace' : 'Compte',
        'links' => $isLoggedIn
            ? [
                ['label' => 'Accéder à mon espace', 'href' => $dashboardUrl],
                ['label' => 'Mon profil', 'href' => $profileUrl],
                ['label' => 'Notifications', 'href' => $notificationsUrl, 'icon' => 'bell'],
                ['label' => 'Se déconnecter', 'href' => $logoutUrl, 'post' => true],
            ]
            : [
                ['label' => 'Se connecter', 'href' => $loginUrl],
                ['label' => 'Créer un compte', 'href' => $registerUrl],
                ['label' => 'Découvrir le catalogue', 'href' => $catalogueUrl],
                ['label' => 'Contacter un conseiller', 'href' => $supportUrl],
            ],
    ],
];

$publicLevelLabels = [
    'beginner' => 'Débutant',
    'intermediate' => 'Intermédiaire',
    'advanced' => 'Avancé',
];

$publicFormatMoney = static function (int $amount): string {
    return view_format_money_xof($amount);
};

$publicNormalizeKey = static function (string $value): string {
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

    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;
    return trim($value, '-');
};

$publicNormalizeSearch = static function (string $value) use ($publicNormalizeKey): string {
    $key = $publicNormalizeKey($value);
    return str_replace('-', ' ', $key);
};

$publicCourseFieldAsList = static function (mixed $value): array {
    if ($value === null) {
        return [];
    }

    if (is_array($value)) {
        $items = $value;
    } else {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        $items = is_array($decoded) ? $decoded : preg_split('/\r\n|\r|\n/', $raw);
        if (!is_array($items)) {
            return [];
        }
    }

    $items = array_map(static fn($item): string => trim((string) $item), $items);
    return array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
};

$publicCourseSnippet = static function (array $items, int $maxLength = 120): string {
    foreach ($items as $item) {
        $snippet = trim((string) $item);
        if ($snippet === '') {
            continue;
        }

        $decoded = html_entity_decode($snippet, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $snippet = trim((string) preg_replace('/\s+/u', ' ', strip_tags($decoded)));
        if ($snippet === '') {
            continue;
        }

        if (\function_exists('mb_strlen') && \function_exists('mb_substr')) {
            if (mb_strlen($snippet) > $maxLength) {
                return rtrim(mb_substr($snippet, 0, max(1, $maxLength - 3))) . '...';
            }
            return $snippet;
        }

        if (strlen($snippet) > $maxLength) {
            return rtrim(substr($snippet, 0, max(1, $maxLength - 3))) . '...';
        }

        return $snippet;
    }

    return '';
};

$publicCourseFormatLabel = static function (string $deliveryMode, string $courseLocation): array {
    $mode = strtolower(trim($deliveryMode));
    $location = trim($courseLocation);

    if ($mode === 'online') {
        return [
            'label' => 'En ligne',
            'icon' => 'fa-video',
        ];
    }

    if (in_array($mode, ['presential', 'onsite', 'presentiel'], true)) {
        return [
            'label' => $location !== '' ? 'Présentiel · ' . $location : 'Présentiel',
            'icon' => 'fa-location-dot',
        ];
    }

    return [
        'label' => '',
        'icon' => 'fa-video',
    ];
};

$publicDecorateCourse = static function (array $course) use ($publicCourseFieldAsList, $publicCourseFormatLabel, $publicCourseSnippet, $publicFormatMoney, $publicLevelLabels, $publicNormalizeKey, $publicNormalizeSearch): array {
    $title = trim((string) ($course['title'] ?? 'Cours numérique'));
    if ($title === '') {
        $title = 'Cours numérique';
    }

    $category = trim((string) ($course['category'] ?? 'Formation'));
    if ($category === '') {
        $category = 'Formation';
    }
    $categoryKey = array_key_exists('categoryKey', $course)
        ? trim((string) ($course['categoryKey'] ?? ''))
        : $publicNormalizeKey($category);

    $teacher = trim((string) ($course['teacher'] ?? 'Formateur'));
    if ($teacher === '') {
        $teacher = 'Formateur';
    }

    $description = trim((string) ($course['description'] ?? ''));
    if ($description === '') {
        $description = 'Progressez avec un contenu structuré, une méthode claire et un résultat concret à la clé.';
    }

    $durationLabel = trim((string) ($course['durationLabel'] ?? ''));
    $durationMin = max(0, (int) ($course['durationMin'] ?? 0));
    $priceRaw = max(0, (int) ($course['priceXof'] ?? 0));
    $rating = max(0, (float) ($course['rating'] ?? 0));
    $ratingCount = max(0, (int) ($course['ratingCount'] ?? 0));
    $enrolledCount = max(0, (int) ($course['enrolledCount'] ?? 0));
    $levelKey = strtolower(trim((string) ($course['level'] ?? '')));
    $levelLabel = $publicLevelLabels[$levelKey] ?? ($levelKey !== '' ? ucfirst($levelKey) : 'Tous niveaux');
    $createdAt = trim((string) ($course['createdAt'] ?? ''));
    $createdTs = $createdAt !== '' ? (int) strtotime($createdAt) : 0;
    $ratingLabel = $ratingCount > 0 ? number_format($rating, 1, ',', ' ') . '/5' : 'Nouveau';
    $ratingMeta = $ratingCount > 0
        ? $ratingLabel . ' · ' . view_pluralize($ratingCount, 'avis')
        : ($enrolledCount > 0 ? 'Nouveau · ' . view_pluralize($enrolledCount, 'inscrit', 'inscrits') : 'Nouveau');
    $priceLabel = $publicFormatMoney($priceRaw);
    $objectives = $publicCourseFieldAsList($course['objectives'] ?? null);
    $prerequisites = $publicCourseFieldAsList($course['prerequisites'] ?? null);
    $equipment = $publicCourseFieldAsList($course['equipment'] ?? null);
    $deliveryMode = strtolower(trim((string) ($course['deliveryMode'] ?? '')));
    if (!in_array($deliveryMode, ['online', 'presential', 'onsite'], true)) {
        $deliveryMode = '';
    }
    $courseLocation = trim((string) ($course['courseLocation'] ?? ''));
    $formatInfo = $publicCourseFormatLabel($deliveryMode, $courseLocation);
    $objectiveSnippet = $publicCourseSnippet($objectives, 120);
    $prerequisiteSnippet = $publicCourseSnippet($prerequisites, 120);

    return [
        'id' => max(0, (int) ($course['id'] ?? 0)),
        'url' => trim((string) ($course['url'] ?? site_url('catalogue'))),
        'title' => $title,
        'description' => $description,
        'cover' => trim((string) ($course['cover'] ?? '')),
        'coverAlt' => trim((string) ($course['coverAlt'] ?? ('Visuel du cours ' . $title))),
        'teacher' => $teacher,
        'category' => $category,
        'categoryKey' => $categoryKey,
        'rating' => $rating,
        'ratingCount' => $ratingCount,
        'enrolledCount' => $enrolledCount,
        'ratingLabel' => $ratingLabel,
        'ratingMeta' => $ratingMeta,
        'priceRaw' => $priceRaw,
        'priceLabel' => $priceLabel,
        'priceHint' => 'Accès immédiat',
        'durationLabel' => $durationLabel !== '' ? $durationLabel : 'À votre rythme',
        'durationMin' => $durationMin,
        'levelKey' => $levelKey,
        'levelLabel' => $levelLabel,
        'createdTs' => $createdTs,
        'objectives' => $objectives,
        'prerequisites' => $prerequisites,
        'equipment' => $equipment,
        'deliveryMode' => $deliveryMode,
        'courseLocation' => $courseLocation,
        'formatLabel' => (string) ($formatInfo['label'] ?? ''),
        'formatIcon' => (string) ($formatInfo['icon'] ?? 'fa-video'),
        'objectiveSnippet' => $objectiveSnippet,
        'prerequisiteSnippet' => $prerequisiteSnippet,
        'searchText' => $publicNormalizeSearch(implode(' ', [
            $title,
            $category,
            $teacher,
            $description,
            $levelLabel,
            (string) ($formatInfo['label'] ?? ''),
            $objectiveSnippet,
            implode(' ', $objectives),
            implode(' ', $prerequisites),
            implode(' ', $equipment),
        ])),
        'statusLabel' => $priceRaw <= 0 ? 'Accès gratuit' : 'Accès premium',
        'ctaLabel' => $priceRaw <= 0 ? 'Commencer' : 'Découvrir le cours',
    ];
};


