<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../View/helpers.php';

$ssrCourses = is_array($ssrCourses ?? null) ? $ssrCourses : [];
$homeTestimonials = is_array($homeTestimonials ?? null) ? $homeTestimonials : [];
$teacherLandingStats = is_array($teacherLandingStats ?? null) ? $teacherLandingStats : [];
$teacherApplyFormUrl = trim((string) ($teacherApplyFormUrl ?? site_url('teacher/apply')));
$teacherLandingPrimaryUrl = trim((string) ($teacherLandingPrimaryUrl ?? $teacherApplyFormUrl));
$teacherLandingPrimaryLabel = trim((string) ($teacherLandingPrimaryLabel ?? 'Commencer à enseigner'));
$teacherLandingSecondaryUrl = trim((string) ($teacherLandingSecondaryUrl ?? site_url('catalogue')));
$publicPage = 'teacher/become';
$teacherApplyUrl = site_url('become-teacher');
$activeEnrollmentCount = max(0, (int) ($teacherLandingStats['activeEnrollments'] ?? 0));
$publishedCoursesCount = max(0, (int) ($teacherLandingStats['publishedCourses'] ?? 0));
$activeTeachersCount = max(0, (int) ($teacherLandingStats['activeTeachers'] ?? 0));
$publicCategoriesCount = max(0, (int) ($teacherLandingStats['categories'] ?? 0));

$platformName = trim((string) platform_setting_get('site_name', defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique'));
if ($platformName === '') {
    $platformName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
}

$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$webMarketingJsUrl = site_url('assets/js/web-marketing.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web-marketing.js') ?: time()));
$teacherHeroImageUrl = site_url('storage/uploads/site/teacher-hero.png?v=' . (@filemtime(__DIR__ . '/../../../storage/uploads/site/teacher-hero.png') ?: time()));

require __DIR__ . '/_public_bootstrap.php';

$landingMetrics = [
    [
        'value' => number_format($activeTeachersCount, 0, ',', ' '),
        'suffix' => $activeTeachersCount > 0 ? '+' : '',
        'label' => 'enseignants actifs',
        'icon' => 'fa-chalkboard-user',
    ],
    [
        'value' => number_format($publishedCoursesCount, 0, ',', ' '),
        'suffix' => $publishedCoursesCount > 0 ? '+' : '',
        'label' => 'cours publiés',
        'icon' => 'fa-book-open',
    ],
    [
        'value' => number_format($activeEnrollmentCount, 0, ',', ' '),
        'suffix' => $activeEnrollmentCount > 0 ? '+' : '',
        'label' => 'inscriptions actives',
        'icon' => 'fa-user-graduate',
    ],
    [
        'value' => number_format($publicCategoriesCount, 0, ',', ' '),
        'suffix' => $publicCategoriesCount > 0 ? '' : '',
        'label' => 'thématiques couvertes',
        'icon' => 'fa-layer-group',
    ],
];

$whyItems = [
    [
        'title' => 'Un dossier lisible',
        'description' => 'Vous remplissez l’essentiel, pas un formulaire interminable. L’équipe relit le dossier avant publication.',
        'icon' => 'fa-shield',
    ],
    [
        'title' => 'Un profil clair',
        'description' => 'Votre domaine, votre présentation et votre CV servent de base à votre profil enseignant.',
        'icon' => 'fa-id-card',
    ],
    [
        'title' => 'Un retour par notification',
        'description' => 'Après l’envoi, vous recevez un message pour la suite.',
        'icon' => 'fa-bell',
    ],
];

$toolCards = [
    [
        'title' => 'Dossier guidé',
        'description' => 'Trois étapes pour aller à l’essentiel.',
        'icon' => 'fa-route',
    ],
    [
        'title' => 'Relecture avant publication',
        'description' => 'Chaque dossier est vérifié avant mise en ligne.',
        'icon' => 'fa-circle-check',
    ],
    [
        'title' => 'Profil réutilisable',
        'description' => 'Votre bio et votre spécialité servent de base à votre profil enseignant.',
        'icon' => 'fa-address-card',
    ],
    [
        'title' => 'Notifications',
        'description' => 'Vous êtes averti quand le dossier avance.',
        'icon' => 'fa-envelope-open-text',
    ],
    [
        'title' => 'Accès à l’espace enseignant',
        'description' => 'Une fois validé, vous passez à votre espace enseignant.',
        'icon' => 'fa-table-columns',
    ],
    [
        'title' => 'Aide disponible',
        'description' => 'Si quelque chose bloque, vous avez un point de contact.',
        'icon' => 'fa-life-ring',
    ],
];

$processSteps = [
    [
        'number' => '01',
        'title' => 'Décrivez votre expérience',
        'description' => 'Indiquez simplement votre niveau d’expérience pédagogique.',
        'icon' => 'fa-user-graduate',
    ],
    [
        'number' => '02',
        'title' => 'Choisissez votre domaine',
        'description' => 'Sélectionnez la catégorie la plus proche de ce que vous enseignez.',
        'icon' => 'fa-pen-ruler',
    ],
    [
        'number' => '03',
        'title' => 'Ajoutez votre dossier',
        'description' => 'Écrivez quelques lignes et joignez votre CV.',
        'icon' => 'fa-paper-plane',
    ],
];

$faqItems = [
    [
        'question' => 'Qui peut devenir enseignant sur ' . $platformName . ' ?',
        'answer' => 'Toute personne avec une compétence claire peut candidater.',
    ],
    [
        'question' => 'Combien de temps prend la réponse ?',
        'answer' => 'En général, le dossier est relu sous 3 à 5 jours. Vous recevez ensuite une notification.',
    ],
    [
        'question' => 'Que faut-il fournir ?',
        'answer' => 'Votre niveau d’expérience, votre domaine, une courte présentation et un CV lisible.',
    ],
    [
        'question' => 'Que se passe-t-il après validation ?',
        'answer' => 'Une fois validé, vous passez à votre espace enseignant pour préparer et publier vos contenus.',
    ],
];

$categoryCounts = [];
foreach ($ssrCourses as $course) {
    $label = trim((string) ($course['category'] ?? ''));
    if ($label === '') {
        continue;
    }
    $categoryCounts[$label] = ($categoryCounts[$label] ?? 0) + 1;
}
arsort($categoryCounts);
$categorySpotlights = [];
foreach ($categoryCounts as $label => $count) {
    $categorySpotlights[] = [
        'title' => $label,
        'countLabel' => view_pluralize((int) $count, 'cours visible', 'cours visibles'),
    ];
    if (count($categorySpotlights) >= 4) {
        break;
    }
}
if ($categorySpotlights === []) {
    $categorySpotlights = [
        ['title' => 'Compétences numériques', 'countLabel' => 'Parcours structurés'],
        ['title' => 'Business & gestion', 'countLabel' => 'Contenus applicables'],
        ['title' => 'Langues & accompagnement', 'countLabel' => 'Formats pédagogiques'],
        ['title' => 'Outils professionnels', 'countLabel' => 'Expertises métier'],
    ];
}
?>
<!doctype html>
<html lang="fr" class="light" data-view="web/become_teacher">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
    <meta name="app-global-assets" content="off">
    <title>Devenir enseignant | <?php echo e($platformName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
    <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
    <script src="<?php echo e($webMarketingJsUrl); ?>" defer></script>
    <style>
        .teacher-landing-faq summary::-webkit-details-marker {
            display: none;
        }
    </style>
</head>
<body class="overflow-x-hidden bg-white font-sans antialiased text-slate-900 dark:bg-slate-950 dark:text-slate-100" data-view="web/become_teacher">
    <?php require __DIR__ . '/_public_header_catalogue.php'; ?>

    <main class="overflow-x-hidden bg-white dark:bg-slate-950">
        <section class="relative isolate overflow-hidden border-b border-slate-200/70 bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_54%,#ffffff_100%)] dark:border-white/10 dark:bg-[linear-gradient(180deg,#020617_0%,#07111f_50%,#020617_100%)]">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-[34rem] bg-[radial-gradient(circle_at_top_left,rgba(59,130,246,0.16),transparent_50%),radial-gradient(circle_at_80%_20%,rgba(14,165,233,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(249,115,22,0.12),transparent_30%)] dark:bg-[radial-gradient(circle_at_top_left,rgba(59,130,246,0.26),transparent_50%),radial-gradient(circle_at_80%_20%,rgba(14,165,233,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(249,115,22,0.16),transparent_30%)]"></div>
            <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-6 sm:py-18 lg:px-8 lg:py-24">
                <div class="grid gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                    <div class="relative z-[1]">
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/90 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-blue-700 shadow-sm dark:border-blue-400/30 dark:bg-white/5 dark:text-blue-200">
                            <i class="fas fa-chalkboard-user" aria-hidden="true"></i>
                            Devenir enseignant
                        </span>
                        <h1 class="mt-6 max-w-3xl text-4xl font-black tracking-tight text-slate-950 dark:text-white sm:text-5xl lg:text-[4.3rem] lg:leading-[0.96]">
                            Partagez votre savoir,
                            <span class="bg-gradient-to-r from-blue-600 via-sky-500 to-cyan-400 bg-clip-text text-transparent">publiez vos cours</span>
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-slate-600 dark:text-slate-300 sm:text-lg">
                            Vous remplissez un formulaire court, vous joignez votre dossier, puis on le relit avant publication.
                        </p>

                        <ul class="mt-8 grid gap-3 text-sm text-slate-700 dark:text-slate-200">
                            <li class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/90 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"><i class="fas fa-check text-[11px]" aria-hidden="true"></i></span>
                                <span>Un formulaire en 3 étapes.</span>
                            </li>
                            <li class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/90 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"><i class="fas fa-check text-[11px]" aria-hidden="true"></i></span>
                                <span>Un dossier relu avant mise en ligne.</span>
                            </li>
                            <li class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/90 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300"><i class="fas fa-check text-[11px]" aria-hidden="true"></i></span>
                                <span>Un retour envoyé par notification.</span>
                            </li>
                        </ul>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <a href="<?php echo e($teacherLandingPrimaryUrl); ?>" class="inline-flex items-center justify-center gap-3 rounded-full bg-blue-600 px-6 py-3.5 text-base font-bold text-white shadow-[0_20px_45px_-20px_rgba(37,99,235,0.72)] transition hover:-translate-y-0.5 hover:bg-blue-500">
                                <?php echo e($teacherLandingPrimaryLabel); ?>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                            <a href="#teacher-process" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200 dark:hover:border-blue-400 dark:hover:text-blue-200">
                                Voir le processus
                            </a>
                        </div>
                    </div>

                    <div class="relative z-[1]">
                        <div class="relative overflow-hidden rounded-[38px] border border-slate-200/80 bg-white p-5 shadow-[0_40px_120px_-65px_rgba(15,23,42,0.62)] dark:border-white/10 dark:bg-slate-900/85 sm:p-6">
                            <div class="absolute -right-10 top-10 h-40 w-40 rounded-full bg-blue-100 blur-3xl dark:bg-blue-500/20"></div>
                            <div class="absolute bottom-0 left-0 h-40 w-40 rounded-tr-[56px] bg-orange-400/90"></div>
                            <div class="absolute right-6 top-6 h-16 w-16 rounded-[24px] border border-slate-200 bg-slate-50/90 dark:border-white/10 dark:bg-white/[0.04]"></div>
                            <figure class="relative aspect-[4/5] overflow-hidden rounded-[30px] border border-slate-200/80 bg-slate-100 shadow-[0_24px_80px_-55px_rgba(15,23,42,0.5)] dark:border-white/10 dark:bg-slate-950">
                                <img
                                    src="<?php echo e($teacherHeroImageUrl); ?>"
                                    alt="Formateur devant un tableau dans une salle de cours"
                                    class="absolute inset-0 h-full w-full object-cover object-center"
                                >
                                <figcaption class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/70 via-slate-950/30 to-transparent px-5 pb-5 pt-16 text-white">
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">Espace enseignant</p>
                                    <p class="mt-1 text-lg font-black tracking-tight">Expliquez votre savoir, puis envoyez votre dossier.</p>
                                </figcaption>
                            </figure>

                            <div class="relative mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <?php foreach ($landingMetrics as $metric): ?>
                                    <article class="rounded-[24px] border border-slate-200/80 bg-slate-50/90 p-3.5 dark:border-white/10 dark:bg-white/[0.04]">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                                            <i class="fas <?php echo e((string) $metric['icon']); ?>" aria-hidden="true"></i>
                                        </span>
                                        <p class="mt-3 text-xl font-black text-slate-950 dark:text-white"><?php echo e((string) $metric['value']); ?><?php echo e((string) $metric['suffix']); ?></p>
                                        <p class="mt-1 text-[11px] font-medium uppercase tracking-[0.12em] text-slate-600 dark:text-slate-300"><?php echo e((string) $metric['label']); ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-10 sm:py-14">
            <div class="mx-auto max-w-[1180px] px-4 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-[0.92fr_1.08fr] lg:items-center">
                    <div class="relative overflow-hidden rounded-[36px] border border-blue-200 bg-blue-100 p-7 text-slate-950 shadow-[0_30px_90px_-45px_rgba(59,130,246,0.18)] dark:border-[#496191] dark:bg-[linear-gradient(135deg,#24375f_0%,#2d4475_58%,#36548c_100%)] dark:text-white">
                        <div class="relative">
                            <span class="inline-flex items-center gap-2 rounded-full border border-blue-200/80 bg-white/90 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-blue-700 shadow-sm dark:border-white/20 dark:bg-white/10 dark:text-blue-50">
                                <i class="fas fa-compass" aria-hidden="true"></i>
                                Candidature simple
                            </span>
                            <h2 class="mt-5 max-w-md text-3xl font-black tracking-tight sm:text-4xl">Pourquoi enseigner sur <?php echo e($platformName); ?> ?</h2>
                            <p class="mt-5 max-w-lg text-sm leading-8 text-slate-600 dark:text-blue-50/90 sm:text-base">
                                Si vous avez une compétence à transmettre, le plus important est d’aller droit au but. On vous laisse décrire l’essentiel, puis on vérifie le dossier avant publication.
                            </p>

                            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                                <?php foreach ($categorySpotlights as $spotlight): ?>
                                    <div class="rounded-[24px] border border-blue-100 bg-white p-4 shadow-[0_18px_40px_-30px_rgba(15,23,42,0.14)] dark:border-white/15 dark:bg-white/10 dark:shadow-[0_18px_40px_-30px_rgba(15,23,42,0.28)]">
                                        <p class="text-sm font-bold text-slate-950 dark:text-white"><?php echo e((string) $spotlight['title']); ?></p>
                                        <p class="mt-1 text-xs font-semibold uppercase tracking-[0.16em] text-blue-700 dark:text-blue-100/85"><?php echo e((string) $spotlight['countLabel']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200">
                            Ce qu’on regarde
                        </span>
                        <div class="mt-6 space-y-4">
                            <?php foreach ($whyItems as $item): ?>
                                <article class="flex items-start gap-4 rounded-[28px] border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900/70">
                                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                                        <i class="fas <?php echo e((string) $item['icon']); ?>" aria-hidden="true"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="text-xl font-black tracking-tight text-slate-950 dark:text-white"><?php echo e((string) $item['title']); ?></h3>
                                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300"><?php echo e((string) $item['description']); ?></p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="teacher-process" class="py-10 sm:py-14">
            <div class="mx-auto max-w-[1180px] px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:border-orange-400/30 dark:bg-orange-500/10 dark:text-orange-200">
                        Le parcours
                    </span>
                    <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">Comment ça marche</h2>
                    <p class="mt-4 text-base leading-8 text-slate-600 dark:text-slate-300">
                        Trois étapes. Vous remplissez l’essentiel et vous passez à la suite.
                    </p>
                </div>

                <div class="mt-10 grid gap-5 lg:grid-cols-3">
                    <?php foreach ($processSteps as $step): ?>
                        <article class="rounded-[32px] border border-slate-200/80 bg-white p-7 shadow-sm dark:border-white/10 dark:bg-slate-900/70">
                            <div class="flex items-start justify-between gap-4">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-3xl bg-slate-100 text-slate-500 dark:bg-white/[0.05] dark:text-slate-300">
                                    <i class="fas <?php echo e((string) $step['icon']); ?> text-lg" aria-hidden="true"></i>
                                </span>
                                <span class="text-5xl font-black tracking-tight text-slate-200 dark:text-slate-700"><?php echo e((string) $step['number']); ?></span>
                            </div>
                            <h3 class="mt-8 text-2xl font-black tracking-tight text-slate-950 dark:text-white"><?php echo e((string) $step['title']); ?></h3>
                            <p class="mt-4 text-sm leading-8 text-slate-600 dark:text-slate-300"><?php echo e((string) $step['description']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="py-10 sm:py-14">
            <div class="mx-auto max-w-[1180px] px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                        Ce que vous trouverez
                    </span>
                    <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">Des repères utiles, pas du remplissage</h2>
                    <p class="mt-4 text-base leading-8 text-slate-600 dark:text-slate-300">
                        Chaque bloc correspond à ce qui compte vraiment pour déposer un dossier propre.
                    </p>
                </div>

                <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($toolCards as $toolCard): ?>
                        <article class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-[0_30px_70px_-45px_rgba(15,23,42,0.46)] dark:border-white/10 dark:bg-slate-900/70">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-3xl bg-blue-50 text-blue-600 dark:bg-blue-500/12 dark:text-blue-300">
                                <i class="fas <?php echo e((string) $toolCard['icon']); ?> text-lg" aria-hidden="true"></i>
                            </span>
                            <h3 class="mt-5 text-xl font-black tracking-tight text-slate-950 dark:text-white"><?php echo e((string) $toolCard['title']); ?></h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300"><?php echo e((string) $toolCard['description']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($homeTestimonials !== []): ?>
            <section class="py-10 sm:py-14">
                <div class="mx-auto max-w-[1180px] px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200">
                            Retours d’apprenants
                        </span>
                        <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">Quelques retours publiés après les cours</h2>
                        <p class="mt-4 text-base leading-8 text-slate-600 dark:text-slate-300">
                            Ces avis viennent des formations déjà en ligne.
                        </p>
                    </div>

                    <div class="mt-10 grid gap-5 lg:grid-cols-3">
                        <?php foreach (array_slice($homeTestimonials, 0, 3) as $testimonial): ?>
                            <article class="rounded-[30px] border border-slate-200/80 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-900/70">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <span class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-lg font-black text-blue-700 dark:border-blue-400/25 dark:bg-blue-500/10 dark:text-blue-200">
                                            <?php echo e((string) ($testimonial['initials'] ?? 'EN')); ?>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-xl font-black tracking-tight text-slate-950 dark:text-white"><?php echo e((string) ($testimonial['name'] ?? 'Utilisateur')); ?></p>
                                            <p class="text-sm text-blue-600 dark:text-blue-300"><?php echo e((string) ($testimonial['role'] ?? 'Avis vérifié')); ?></p>
                                        </div>
                                    </div>
                                    <i class="fas fa-quote-right text-4xl text-blue-100 dark:text-blue-500/20" aria-hidden="true"></i>
                                </div>
                                <p class="mt-6 text-base leading-8 text-slate-600 dark:text-slate-300">"<?php echo e((string) ($testimonial['quote'] ?? '')); ?>"</p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section id="teacher-faq" class="py-10 sm:py-14">
            <div class="mx-auto max-w-[1040px] px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                        Questions fréquentes
                    </span>
                    <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">Ce qu’il faut savoir avant de déposer votre dossier</h2>
                </div>

                <div class="mt-10 space-y-4">
                    <?php foreach ($faqItems as $faqItem): ?>
                        <details class="teacher-landing-faq group rounded-[28px] border border-slate-200/80 bg-white p-6 shadow-sm transition open:border-blue-300 open:bg-blue-50/40 dark:border-white/10 dark:bg-slate-900/70 dark:open:border-blue-400/30 dark:open:bg-blue-500/10">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left">
                                <span class="text-lg font-black tracking-tight text-slate-950 dark:text-white"><?php echo e((string) $faqItem['question']); ?></span>
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 transition group-open:rotate-180 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-300">
                                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                </span>
                            </summary>
                            <p class="mt-4 max-w-3xl pr-2 text-sm leading-8 text-slate-600 dark:text-slate-300"><?php echo e((string) $faqItem['answer']); ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="pb-14 pt-6 sm:pb-20">
            <div class="mx-auto max-w-[1180px] px-4 sm:px-6 lg:px-8">
                <div class="relative overflow-hidden rounded-[36px] border border-blue-200 bg-blue-100 px-6 py-8 text-slate-950 shadow-[0_34px_90px_-45px_rgba(59,130,246,0.18)] dark:border-[#496191] dark:bg-[linear-gradient(135deg,#24375f_0%,#2d4475_58%,#36548c_100%)] dark:text-white sm:px-8 sm:py-10">
                    <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-100">Prêt à commencer ?</p>
                            <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Commencez votre candidature</h2>
                            <p class="mt-4 text-sm leading-8 text-slate-600 dark:text-blue-50/90 sm:text-base">
                                Vous arrivez sur le formulaire en 3 étapes. Si vous n’êtes pas connecté, la plateforme vous demandera d’abord de vous identifier.
                            </p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <a href="<?php echo e($teacherLandingPrimaryUrl); ?>" class="inline-flex items-center justify-center gap-3 rounded-full border border-blue-300 bg-white px-6 py-3.5 text-base font-bold text-blue-700 shadow-[0_20px_45px_-24px_rgba(59,130,246,0.22)] transition hover:-translate-y-0.5 hover:bg-blue-50 dark:border-white/35 dark:bg-blue-600 dark:text-white dark:hover:bg-blue-500">
                                <?php echo e($teacherLandingPrimaryLabel); ?>
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                            <a href="<?php echo e($teacherLandingSecondaryUrl); ?>" class="inline-flex items-center justify-center rounded-full border border-white/65 bg-white/78 px-6 py-3.5 text-sm font-semibold text-slate-900 shadow-[0_18px_40px_-28px_rgba(15,23,42,0.12)] transition hover:bg-white dark:border-white/20 dark:bg-white/10 dark:text-white dark:shadow-[0_18px_40px_-28px_rgba(15,23,42,0.22)] dark:hover:bg-white/16">
                                Découvrir le catalogue
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/_public_footer_home.php'; ?>
</body>
</html>
