<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/ecolenumerique');
}
if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', 'http://localhost/ecolenumerique');
}

require_once $rootDir . '/app/bootstrap.php';
if (!function_exists('platform_setting_get')) {
    function platform_setting_get(string $key, string $default): string
    {
        return $default;
    }
}
if (!function_exists('site_logo_url')) {
    function site_logo_url(): string
    {
        return '';
    }
}
if (!function_exists('site_footer_text')) {
    function site_footer_text(): string
    {
        return '';
    }
}
require_once $rootDir . '/includes/helpers.php';
require_once $rootDir . '/app/View/helpers.php';

/**
 * @param array<int,string> $errors
 */
function assert_true(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

$errors = [];
$cartCount = 0;
$recentCreatedAt = date('Y-m-d H:i:s', strtotime('-2 days'));

$ssrCourses = [
    [
        'id' => 101,
        'title' => 'Cours A',
        'url' => '/ecolenumerique/course?slug=cours-a',
        'buyUrl' => '/ecolenumerique/checkout?course=101',
        'isInCart' => 0,
        'isFavorite' => 0,
        'description' => 'Premier cours',
        'category' => 'Développement',
        'categoryKey' => 'developpement',
        'level' => 'beginner',
        'levelLabel' => 'Débutant',
        'durationLabel' => '3 h',
        'priceXof' => 1000,
        'promoActive' => false,
        'promoDiscountLabel' => '',
        'promoEndAt' => '',
        'couponAvailable' => false,
        'objectives' => ['Créer des tableaux Excel professionnels'],
        'prerequisites' => ['Savoir utiliser les bases d’Excel'],
        'equipment' => ['Un ordinateur avec Excel'],
        'deliveryMode' => 'online',
        'courseLocation' => '',
        'rating' => 2.0,
        'ratingCount' => 1,
        'enrolledCount' => 25,
        'createdAt' => '2026-06-01 10:00:00',
        'teacher' => 'Formateur A',
    ],
    [
        'id' => 102,
        'title' => 'Cours B',
        'url' => '/ecolenumerique/course?slug=cours-b',
        'buyUrl' => '/ecolenumerique/checkout?course=102',
        'isInCart' => 0,
        'isFavorite' => 0,
        'description' => 'Deuxième cours',
        'category' => 'Développement',
        'categoryKey' => 'developpement',
        'level' => 'intermediate',
        'levelLabel' => 'Intermédiaire',
        'durationLabel' => '4 h',
        'priceXof' => 2000,
        'promoActive' => false,
        'promoDiscountLabel' => '',
        'promoEndAt' => '',
        'couponAvailable' => false,
        'rating' => 4.9,
        'ratingCount' => 60,
        'enrolledCount' => 20,
        'createdAt' => '2026-06-02 10:00:00',
        'teacher' => 'Formateur B',
    ],
    [
        'id' => 103,
        'title' => 'Cours C',
        'url' => '/ecolenumerique/course?slug=cours-c',
        'buyUrl' => '/ecolenumerique/checkout?course=103',
        'isInCart' => 0,
        'isFavorite' => 0,
        'description' => 'Troisième cours',
        'category' => 'Développement',
        'categoryKey' => 'developpement',
        'level' => 'advanced',
        'levelLabel' => 'Avancé',
        'durationLabel' => '5 h',
        'priceXof' => 3000,
        'promoActive' => false,
        'promoDiscountLabel' => '',
        'promoEndAt' => '',
        'couponAvailable' => false,
        'rating' => 4.7,
        'ratingCount' => 45,
        'enrolledCount' => 15,
        'createdAt' => '2026-06-03 10:00:00',
        'teacher' => 'Formateur C',
    ],
    [
        'id' => 104,
        'title' => 'Cours D',
        'url' => '/ecolenumerique/course?slug=cours-d',
        'buyUrl' => '/ecolenumerique/checkout?course=104',
        'isInCart' => 0,
        'isFavorite' => 0,
        'description' => 'Quatrième cours',
        'category' => 'Développement',
        'categoryKey' => 'developpement',
        'level' => 'beginner',
        'levelLabel' => 'Débutant',
        'durationLabel' => '2 h',
        'priceXof' => 4000,
        'promoActive' => false,
        'promoDiscountLabel' => '',
        'promoEndAt' => '',
        'couponAvailable' => false,
        'rating' => 5.0,
        'ratingCount' => 90,
        'enrolledCount' => 5,
        'createdAt' => '2026-06-04 10:00:00',
        'teacher' => 'Formateur D',
        'objectives' => [],
        'prerequisites' => [],
        'equipment' => [],
        'deliveryMode' => 'presential',
        'courseLocation' => 'Cotonou',
    ],
    [
        'id' => 105,
        'title' => 'Cours E',
        'url' => '/ecolenumerique/course?slug=cours-e',
        'buyUrl' => '/ecolenumerique/checkout?course=105',
        'isInCart' => 0,
        'isFavorite' => 0,
        'description' => 'Cinquième cours',
        'category' => 'Développement',
        'categoryKey' => 'developpement',
        'level' => 'beginner',
        'levelLabel' => 'Débutant',
        'durationLabel' => '6 h',
        'priceXof' => 4500,
        'promoActive' => false,
        'promoDiscountLabel' => '',
        'promoEndAt' => '',
        'couponAvailable' => false,
        'rating' => 0,
        'ratingCount' => 0,
        'enrolledCount' => 12,
        'createdAt' => $recentCreatedAt,
        'teacher' => 'Formateur E',
    ],
];

ob_start();
include $rootDir . '/app/Views/web/catalogue.php';
$html = ob_get_clean();

if (!is_string($html) || $html === '') {
    fwrite(STDERR, "Catalogue view did not render.\n");
    exit(1);
}

if (!preg_match('/<script id="catalog-courses-data" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
    fwrite(STDERR, "Could not locate catalog course payload.\n");
    exit(1);
}

$catalogCourses = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
if (!is_array($catalogCourses)) {
    fwrite(STDERR, "Catalog course payload is not valid JSON.\n");
    exit(1);
}

$coursesById = [];
foreach ($catalogCourses as $course) {
    if (is_array($course) && isset($course['id'])) {
        $coursesById[(int) $course['id']] = $course;
    }
}

assert_true(isset($coursesById[101]), 'Course 101 must exist in the rendered payload.', $errors);
assert_true(isset($coursesById[104]), 'Course 104 must exist in the rendered payload.', $errors);
assert_true((int) ($coursesById[101]['enrolledCount'] ?? -1) === 25, 'Course 101 should preserve enrolledCount.', $errors);
assert_true((int) ($coursesById[101]['students'] ?? -1) === 25, 'Course 101 should expose enrolledCount through students.', $errors);
assert_true(in_array('best-seller', $coursesById[101]['tags'] ?? [], true), 'Course 101 should be tagged best-seller from enrollments.', $errors);
assert_true(!in_array('best-seller', $coursesById[104]['tags'] ?? [], true), 'Course 104 should not outrank the higher-enrollment courses.', $errors);
assert_true(str_contains($html, '25 inscrits'), 'Best-seller badge should show the enrollment count.', $errors);
assert_true(str_contains($html, 'Formation récemment ajoutée'), 'Recent unrated courses should show a positive freshness label.', $errors);
assert_true(str_contains($html, '12 apprenants'), 'Recent unrated courses should show the enrolled count instead of a missing-review message.', $errors);
assert_true((string) ($coursesById[101]['objectiveSnippet'] ?? '') !== '', 'Course 101 should expose a real objective snippet in the payload.', $errors);
assert_true((string) ($coursesById[101]['formatLabel'] ?? '') === 'En ligne', 'Course 101 should expose the delivery format in the payload.', $errors);
assert_true(str_contains((string) ($coursesById[104]['formatLabel'] ?? ''), 'Présentiel'), 'Course 104 should expose the location-aware format in the payload.', $errors);
assert_true(str_contains((string) ($coursesById[104]['formatLabel'] ?? ''), 'Cotonou'), 'Course 104 should expose the location in the payload.', $errors);
assert_true((string) ($coursesById[101]['priceHint'] ?? '') === 'Accès immédiat', 'Course 101 should expose the pricing hint in the payload.', $errors);
assert_true(!str_contains($html, 'Aucun avis pour l’instant'), 'Catalog cards should not show the missing-review message anymore.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "catalogue_popularity_functional: OK\n");
