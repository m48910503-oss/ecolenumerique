<?php
declare(strict_types=1);

$alreadyTeacher = !empty($alreadyTeacher);
$pendingApplication = !empty($pendingApplication);
$isCompanyAccount = !empty($isCompanyAccount);
$errors = is_array($errors ?? null) ? $errors : [];
$successMessage = trim((string) ($successMessage ?? ''));
$formLocked = !empty($formLocked);
$driveReady = !empty($driveReady);
$driveNeedsConnect = !empty($driveNeedsConnect);
$driveMaxMb = max(1, (int) ($driveMaxMb ?? 25));
$title = trim((string) ($title ?? ''));
$bio = trim((string) ($bio ?? ''));
$teachingExperience = trim((string) ($teachingExperience ?? ''));
$cvPath = trim((string) ($cvPath ?? ''));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$profileIncomplete = !empty($profileIncomplete);
$profileMissingFields = is_array($profileMissingFields ?? null) ? array_values($profileMissingFields) : [];
$dashboardUrl = trim((string) ($dashboardUrl ?? site_url('student/dashboard')));
$profileUrl = trim((string) ($profileUrl ?? site_url('profile')));
$notificationsUrl = trim((string) ($notificationsUrl ?? site_url('notifications')));
$contactEmail = trim((string) ($contactEmail ?? ''));
$contactPhone = trim((string) ($contactPhone ?? ''));
$publicPage = 'teacher/apply';
$isLoggedIn = true;

$lockedCardTitle = $isCompanyAccount ? 'Compte entreprise non éligible' : 'Dossier en cours';
$lockedCardBody = $isCompanyAccount
    ? 'Les comptes entreprise ne peuvent pas utiliser ce formulaire.'
    : 'Votre dossier est déjà validé ou en cours de traitement.';

$teachingExperienceOptions = [
    'regular' => 'Oui, régulièrement',
    'occasional' => 'Oui, occasionnellement',
    'beginner' => 'Non, mais je souhaite commencer',
];
$experienceCards = [
    'beginner' => [
        'title' => 'Je débute dans l’enseignement',
        'description' => 'Vous avez une expertise métier et souhaitez apprendre à la structurer pour des apprenants.',
        'icon' => 'fa-seedling',
    ],
    'occasional' => [
        'title' => 'J’ai déjà accompagné des apprenants',
        'description' => 'Vous avez animé des ateliers, du mentorat ou des sessions ponctuelles.',
        'icon' => 'fa-user-group',
    ],
    'regular' => [
        'title' => 'J’enseigne déjà régulièrement',
        'description' => 'Vous avez une pratique pédagogique installée et souhaitez publier dans un cadre plus structuré.',
        'icon' => 'fa-chalkboard-user',
    ],
];
$focusCards = [
    [
        'key' => 'digital',
        'title' => 'Compétences numériques',
        'description' => 'Développement, bureautique, outils web, design, data, productivité.',
        'suggestion' => 'Développement web et outils numériques',
        'icon' => 'fa-laptop-code',
    ],
    [
        'key' => 'business',
        'title' => 'Business & gestion',
        'description' => 'Comptabilité, marketing, vente, finance, gestion, entrepreneuriat.',
        'suggestion' => 'Business, gestion ou marketing digital',
        'icon' => 'fa-briefcase',
    ],
    [
        'key' => 'creative',
        'title' => 'Création & médias',
        'description' => 'Photo, vidéo, design, montage, communication visuelle et création de contenus.',
        'suggestion' => 'Création visuelle, vidéo ou design',
        'icon' => 'fa-photo-film',
    ],
    [
        'key' => 'languages',
        'title' => 'Langues & accompagnement',
        'description' => 'Langues, soutien scolaire, méthodologie, expression et accompagnement.',
        'suggestion' => 'Langues, pédagogie ou accompagnement',
        'icon' => 'fa-language',
    ],
];

$appFromName = defined('APP_FROM_NAME') ? (string) constant('APP_FROM_NAME') : 'École Numérique';
$platformName = trim((string) platform_setting_get('site_name', $appFromName));
if ($platformName === '') {
    $platformName = $appFromName;
}

$platformLogoUrl = $siteLogoUrl;
$platformLogoAlt = 'Logo ' . $platformName;
$teacherApplyLandingUrl = site_url('become-teacher');
$catalogueUrl = site_url('catalogue');
$supportUrl = site_url('support');
$driveConnectUrl = site_url('drive/oauth_connect');
$selectedFocusKey = '';
foreach ($focusCards as $focusCard) {
    if (strcasecmp($title, (string) $focusCard['suggestion']) === 0) {
        $selectedFocusKey = (string) $focusCard['key'];
        break;
    }
}

if ($successMessage !== '') {
    $applicationNoticeState = 'success';
} elseif ($pendingApplication) {
    $applicationNoticeState = 'pending';
} elseif (!$alreadyTeacher && !$isCompanyAccount && $profileIncomplete) {
    $applicationNoticeState = 'profile';
} elseif (!$formLocked && $errors === []) {
    $applicationNoticeState = 'preflight';
} else {
    $applicationNoticeState = '';
}

$applicationNoticeAutoOpen = in_array($applicationNoticeState, ['success', 'pending', 'profile'], true);

if ($formLocked || $successMessage !== '') {
    $defaultWizardStep = 3;
} elseif ($errors !== []) {
    if ($teachingExperience === '') {
        $defaultWizardStep = 1;
    } elseif ($title === '') {
        $defaultWizardStep = 2;
    } else {
        $defaultWizardStep = 3;
    }
} else {
    $defaultWizardStep = 1;
}

$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$globalPopupsJsUrl = site_url('assets/js/global_popups.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/global_popups.js') ?: time()));
$feedbackVisibilityJsUrl = site_url('assets/js/feedback_visibility.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/feedback_visibility.js') ?: time()));
$teacherApplyJsUrl = site_url('assets/js/teacher_apply.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_apply.js') ?: time()));
?>
<!doctype html>
<html lang="fr" class="light" data-view="teacher/apply">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
    <meta name="color-scheme" content="light dark">
    <title>Formulaire enseignant | <?php echo e($platformName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
    <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
    <script src="<?php echo e($globalPopupsJsUrl); ?>" defer></script>
    <script src="<?php echo e($feedbackVisibilityJsUrl); ?>" defer></script>
    <script src="<?php echo e($teacherApplyJsUrl); ?>" defer></script>
    <style>
        .teacher-apply-page [data-apply-step-panel][hidden] {
            display: none !important;
        }

        .teacher-apply-page .teacher-apply-step-indicator {
            transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .teacher-apply-page .teacher-apply-step-indicator[data-state="active"] {
            border-color: rgba(37, 99, 235, 0.35);
            background: rgba(239, 246, 255, 0.92);
            box-shadow: 0 18px 40px -30px rgba(37, 99, 235, 0.45);
            transform: translateY(-1px);
        }

        .teacher-apply-page .teacher-apply-step-indicator[data-state="active"] [data-apply-step-indicator-circle] {
            background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
            color: #ffffff;
            border-color: transparent;
        }

        .teacher-apply-page .teacher-apply-step-indicator[data-state="complete"] {
            border-color: rgba(148, 163, 184, 0.28);
            background: rgba(248, 250, 252, 0.9);
        }

        .teacher-apply-page .teacher-apply-step-indicator[data-state="complete"] [data-apply-step-indicator-circle] {
            border-color: rgba(37, 99, 235, 0.22);
            background: rgba(219, 234, 254, 0.9);
            color: #1d4ed8;
        }

        .teacher-apply-page .teacher-apply-step-indicator[data-state="upcoming"] {
            border-color: rgba(226, 232, 240, 0.9);
            background: rgba(255, 255, 255, 0.78);
        }

        html.dark .teacher-apply-page .teacher-apply-step-indicator[data-state="active"],
        html[data-theme="dark"] .teacher-apply-page .teacher-apply-step-indicator[data-state="active"] {
            border-color: rgba(96, 165, 250, 0.34);
            background: rgba(29, 78, 216, 0.14);
            box-shadow: none;
        }

        html.dark .teacher-apply-page .teacher-apply-step-indicator[data-state="complete"],
        html[data-theme="dark"] .teacher-apply-page .teacher-apply-step-indicator[data-state="complete"] {
            border-color: rgba(71, 85, 105, 0.72);
            background: rgba(15, 23, 42, 0.7);
        }

        html.dark .teacher-apply-page .teacher-apply-step-indicator[data-state="upcoming"],
        html[data-theme="dark"] .teacher-apply-page .teacher-apply-step-indicator[data-state="upcoming"] {
            border-color: rgba(51, 65, 85, 0.88);
            background: rgba(15, 23, 42, 0.54);
        }
    </style>
</head>
<body class="teacher-apply-page overflow-x-hidden bg-slate-50 font-sans antialiased text-slate-900 dark:bg-slate-950 dark:text-slate-100" data-view="teacher/apply">
    <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/90">
        <div class="mx-auto flex max-w-[1180px] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="<?php echo e($teacherApplyLandingUrl); ?>" class="flex min-w-0 items-center gap-3">
                <?php if ($platformLogoUrl !== ''): ?>
                    <img src="<?php echo e($platformLogoUrl); ?>" alt="<?php echo e($platformLogoAlt); ?>" class="h-10 w-10 rounded-2xl object-cover shadow-sm">
                <?php else: ?>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-md">
                        <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    </span>
                <?php endif; ?>
                <span class="min-w-0">
                    <span class="block truncate text-lg font-black tracking-tight text-slate-950 dark:text-white"><?php echo site_brand_name_html($platformName); ?></span>
                    <span class="block truncate text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Formulaire enseignant</span>
                </span>
            </a>

            <div class="hidden min-w-0 flex-1 justify-center px-8 md:flex">
                <div class="w-full max-w-md text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Étape <span data-apply-step-current><?php echo e((string) $defaultWizardStep); ?></span> sur 3</p>
                    <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-slate-300" data-apply-step-title-current>
                        <?php echo e($defaultWizardStep === 1 ? 'Présentation du parcours' : ($defaultWizardStep === 2 ? 'Choix de la spécialité' : 'Préparation du dossier')); ?>
                    </p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 transition-[width] duration-300" data-apply-progress-bar style="width: <?php echo e((string) (($defaultWizardStep / 3) * 100)); ?>%;"></div>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    data-web-theme-toggle
                    class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-300 dark:hover:border-blue-400 dark:hover:text-blue-200"
                    aria-label="Changer le thème"
                >
                    <i class="fas fa-moon text-base" data-theme-icon-dark aria-hidden="true"></i>
                    <i class="fas fa-sun hidden text-base" data-theme-icon-light aria-hidden="true"></i>
                </button>
                <a href="<?php echo e($teacherApplyLandingUrl); ?>" class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200 dark:hover:border-blue-400 dark:hover:text-blue-200">
                    Quitter
                </a>
            </div>
        </div>
        <div class="border-t border-slate-200/70 px-4 py-3 md:hidden dark:border-white/10">
            <div class="mx-auto max-w-[1180px]">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Étape <span data-apply-step-current><?php echo e((string) $defaultWizardStep); ?></span> sur 3</p>
                <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-slate-300" data-apply-step-title-current>
                    <?php echo e($defaultWizardStep === 1 ? 'Présentation du parcours' : ($defaultWizardStep === 2 ? 'Choix de la spécialité' : 'Préparation du dossier')); ?>
                </p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10">
                    <div class="h-full rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 transition-[width] duration-300" data-apply-progress-bar style="width: <?php echo e((string) (($defaultWizardStep / 3) * 100)); ?>%;"></div>
                </div>
            </div>
        </div>
    </header>

    <main class="overflow-x-hidden bg-[linear-gradient(180deg,#f8fbff_0%,#f8fafc_45%,#ffffff_100%)] dark:bg-[linear-gradient(180deg,#020617_0%,#07111f_50%,#020617_100%)]">
        <section class="py-8 sm:py-10">
            <div class="mx-auto max-w-[1180px] px-4 sm:px-6 lg:px-8">
                <div class="grid gap-3 sm:grid-cols-3">
                    <?php foreach ([1 => 'Expérience', 2 => 'Expertise', 3 => 'Dossier'] as $stepNumber => $stepLabel): ?>
                        <div class="teacher-apply-step-indicator rounded-[24px] border p-3" data-apply-step-indicator data-step="<?php echo e((string) $stepNumber); ?>" data-state="<?php echo $stepNumber === $defaultWizardStep ? 'active' : ($stepNumber < $defaultWizardStep ? 'complete' : 'upcoming'); ?>">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-black text-slate-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100" data-apply-step-indicator-circle>
                                    <?php echo e((string) $stepNumber); ?>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">Étape <?php echo e((string) $stepNumber); ?></p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo e($stepLabel); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="pb-14 sm:pb-20">
            <div class="mx-auto max-w-[1040px] px-4 sm:px-6 lg:px-8">
                <div class="rounded-[36px] border border-slate-200/80 bg-white/96 shadow-[0_40px_120px_-65px_rgba(15,23,42,0.52)] dark:border-white/10 dark:bg-slate-900/84">
                    <div class="border-b border-slate-200/70 px-5 py-5 dark:border-white/10 sm:px-7 sm:py-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-md">
                                    <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Dossier enseignant</p>
                                    <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">Votre dossier</h1>
                                </div>
                            </div>
                            <div class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-600 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-300">
                                Retour sous 3 à 5 jours
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-5 sm:px-7 sm:py-7">
                        <div class="space-y-3">
                            <?php if ($alreadyTeacher): ?>
                                <div data-feedback-inline="1" class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-200">
                                    Vous êtes déjà enseignant.
                                </div>
                            <?php endif; ?>

                            <?php if ($pendingApplication): ?>
                                <div data-feedback-inline="1" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-600 dark:bg-amber-900/40 dark:text-amber-100">
                                    Votre demande est déjà en cours d’examen.
                                </div>
                            <?php endif; ?>

                            <?php if ($errors !== []): ?>
                                <div data-feedback-inline="1" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800 dark:border-red-700 dark:bg-red-900/50 dark:text-red-100">
                                    <ul class="space-y-1 pl-4">
                                        <?php foreach ($errors as $err): ?>
                                            <li><?php echo e((string) $err); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if ($successMessage !== ''): ?>
                                <div data-feedback-inline="1" class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-200">
                                    <?php echo e($successMessage); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($formLocked): ?>
                            <div class="mt-6 rounded-[28px] border border-slate-200/80 bg-slate-50 p-6 dark:border-white/10 dark:bg-white/[0.04]">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                        <i class="fas fa-lock" aria-hidden="true"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xl font-black tracking-tight text-slate-950 dark:text-white"><?php echo e($lockedCardTitle); ?></p>
                                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300"><?php echo e($lockedCardBody); ?></p>
                                        <div class="mt-5 flex flex-wrap gap-3">
                                            <a href="<?php echo e($alreadyTeacher ? $dashboardUrl : $teacherApplyLandingUrl); ?>" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                                                <?php echo e($alreadyTeacher ? 'Ouvrir mon espace' : 'Retour à la page de présentation'); ?>
                                            </a>
                                            <a href="<?php echo e($supportUrl); ?>" class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200 dark:hover:border-blue-400 dark:hover:text-blue-200">
                                                Contacter le support
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="post" action="<?php echo e(site_url('teacher/apply')); ?>" class="mt-6" enctype="multipart/form-data" data-apply-wizard-form data-initial-step="<?php echo e((string) $defaultWizardStep); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                <section data-apply-step-panel data-step="1" <?php echo $defaultWizardStep === 1 ? '' : 'hidden'; ?>>
                                    <div class="mx-auto max-w-3xl">
                                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Étape 1</p>
                                        <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">Quelle est votre expérience dans l’enseignement ?</h2>
                                        <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                                            On s’en sert juste pour lire votre dossier avec le bon contexte.
                                        </p>

                                        <div class="mt-6 space-y-4">
                                            <?php foreach ($teachingExperienceOptions as $value => $label): ?>
                                                <?php $experienceCard = $experienceCards[$value] ?? ['title' => $label, 'description' => '', 'icon' => 'fa-user-graduate']; ?>
                                                <label class="block cursor-pointer">
                                                    <input type="radio" class="peer sr-only" name="teaching_experience" value="<?php echo e($value); ?>" <?php echo $teachingExperience === $value ? 'checked' : ''; ?>>
                                                    <span class="flex items-start gap-4 rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:bg-blue-50/70 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:shadow-[0_25px_60px_-45px_rgba(37,99,235,0.8)] dark:border-white/10 dark:bg-slate-900/70 dark:hover:border-blue-400 dark:hover:bg-blue-500/10 dark:peer-checked:border-blue-400 dark:peer-checked:bg-blue-500/10">
                                                        <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-3xl bg-slate-100 text-slate-500 transition peer-checked:bg-blue-600 peer-checked:text-white dark:bg-white/[0.05] dark:text-slate-300 dark:peer-checked:bg-blue-500">
                                                            <i class="fas <?php echo e((string) $experienceCard['icon']); ?> text-lg" aria-hidden="true"></i>
                                                        </span>
                                                        <span class="min-w-0 flex-1">
                                                            <span class="block text-xl font-black tracking-tight text-slate-950 dark:text-white"><?php echo e((string) $experienceCard['title']); ?></span>
                                                            <span class="mt-2 block text-sm leading-7 text-slate-600 dark:text-slate-300"><?php echo e((string) $experienceCard['description']); ?></span>
                                                        </span>
                                                        <span class="mt-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-transparent opacity-0 transition peer-checked:opacity-100 peer-checked:border-blue-500 peer-checked:bg-blue-600 peer-checked:text-white dark:border-white/10 dark:bg-slate-900 dark:peer-checked:border-blue-400 dark:peer-checked:bg-blue-500">
                                                            <i class="fas fa-check text-[11px]" aria-hidden="true"></i>
                                                        </span>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <p class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200" data-apply-step-error></p>
                                        <div class="mt-8 flex justify-end">
                                            <button type="button" class="inline-flex items-center justify-center gap-3 rounded-full bg-blue-600 px-6 py-3.5 text-sm font-bold text-white shadow-[0_20px_45px_-20px_rgba(37,99,235,0.72)] transition hover:bg-blue-500" data-apply-next>
                                                Continuer
                                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </section>

                                <section data-apply-step-panel data-step="2" <?php echo $defaultWizardStep === 2 ? '' : 'hidden'; ?>>
                                    <div class="mx-auto max-w-3xl">
                                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Étape 2</p>
                                        <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">Quel type de contenu souhaitez-vous enseigner ?</h2>
                                        <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                                            Choisissez la famille la plus proche de ce que vous enseignez.
                                        </p>

                                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                            <?php foreach ($focusCards as $focusCard): ?>
                                                <label class="block cursor-pointer">
                                                    <input type="radio" class="peer sr-only" name="focus_choice" value="<?php echo e((string) $focusCard['key']); ?>" data-apply-focus-option data-title-suggestion="<?php echo e((string) $focusCard['suggestion']); ?>" <?php echo $selectedFocusKey === (string) $focusCard['key'] ? 'checked' : ''; ?>>
                                                    <span class="flex h-full flex-col rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:bg-blue-50/70 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:border-white/10 dark:bg-slate-900/70 dark:hover:border-blue-400 dark:hover:bg-blue-500/10 dark:peer-checked:border-blue-400 dark:peer-checked:bg-blue-500/10">
                                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 transition peer-checked:bg-blue-600 peer-checked:text-white dark:bg-white/[0.05] dark:text-slate-300 dark:peer-checked:bg-blue-500">
                                                            <i class="fas <?php echo e((string) $focusCard['icon']); ?>" aria-hidden="true"></i>
                                                        </span>
                                                        <span class="mt-4 block text-lg font-black tracking-tight text-slate-950 dark:text-white"><?php echo e((string) $focusCard['title']); ?></span>
                                                        <span class="mt-2 block text-sm leading-7 text-slate-600 dark:text-slate-300"><?php echo e((string) $focusCard['description']); ?></span>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="mt-6">
                                            <label for="title" class="mb-2 block text-sm font-bold text-slate-900 dark:text-white">Compétence principale à enseigner</label>
                                            <div class="relative">
                                                <i class="fas fa-briefcase absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                                                <input type="text" id="title" name="title" value="<?php echo e($title); ?>" placeholder="Ex : Développement web, comptabilité, marketing digital" class="w-full rounded-[22px] border border-slate-200 bg-white py-4 pl-12 pr-4 text-base text-slate-800 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/10">
                                            </div>
                                        </div>

                                        <p class="mt-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200" data-apply-step-error></p>
                                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200 dark:hover:border-blue-400 dark:hover:text-blue-200" data-apply-prev>
                                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                                Précédent
                                            </button>
                                            <button type="button" class="inline-flex items-center justify-center gap-3 rounded-full bg-blue-600 px-6 py-3.5 text-sm font-bold text-white shadow-[0_20px_45px_-20px_rgba(37,99,235,0.72)] transition hover:bg-blue-500" data-apply-next>
                                                Continuer
                                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </section>

                                <section data-apply-step-panel data-step="3" <?php echo $defaultWizardStep === 3 ? '' : 'hidden'; ?>>
                                    <div class="mx-auto max-w-[920px]">
                                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Étape 3</p>
                                        <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">Présentez votre dossier</h2>
                                        <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                                            Quelques lignes et un CV lisible suffisent pour lancer la lecture.
                                        </p>

                                        <?php if ($profileIncomplete && $errors === []): ?>
                                            <div class="mt-6 rounded-[24px] border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-300">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-slate-900 dark:text-white">Profil à compléter avant l’envoi.</p>
                                                        <?php if ($profileMissingFields !== []): ?>
                                                            <p class="mt-1">Champs manquants : <?php echo e(implode(', ', $profileMissingFields)); ?>.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <a href="<?php echo e($profileUrl); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 dark:border-blue-400/30 dark:bg-white/5 dark:text-blue-200 dark:hover:bg-white/10">
                                                        <i class="fas fa-user-edit" aria-hidden="true"></i>
                                                        Compléter mon profil
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!$driveReady): ?>
                                            <div data-feedback-inline="1" class="mt-6 rounded-[24px] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:border-amber-600 dark:bg-amber-900/30 dark:text-amber-100">
                                                <?php if ($driveNeedsConnect): ?>
                                                    <div class="flex items-start gap-3">
                                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-amber-600 shadow-sm dark:bg-slate-900/40 dark:text-amber-300">
                                                            <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="font-bold">Drive non connecté</p>
                                                            <p class="mt-1 leading-6">Connectez Drive pour permettre le dépôt du CV.</p>
                                                            <a class="mt-3 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-white px-4 py-2.5 font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-500/40 dark:bg-slate-900/40 dark:text-amber-100 dark:hover:bg-slate-900/70" href="<?php echo e($driveConnectUrl); ?>">
                                                                <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                                                Connecter Drive
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    Dépôt de CV indisponible. Contactez le support.
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mt-6 grid gap-6 lg:grid-cols-[1.06fr_0.94fr]">
                                            <div class="rounded-[30px] border border-slate-200/80 bg-slate-50/80 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                                                <label for="bio" class="block text-sm font-bold text-slate-900 dark:text-white">À propos de vous</label>
                                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Dites ce que vous enseignez et pourquoi.</p>
                                                <textarea id="bio" name="bio" rows="8" required placeholder="Dites ce que vous enseignez et pourquoi." class="mt-4 w-full resize-none rounded-[22px] border border-slate-200 bg-white px-4 py-4 text-base text-slate-800 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-white/10 dark:bg-slate-950/70 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/10"><?php echo e($bio); ?></textarea>
                                            </div>

                                            <div class="space-y-5">
                                                <div class="rounded-[30px] border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-950/60">
                                                    <label class="block text-sm font-bold text-slate-900 dark:text-white">Fichier de candidature</label>
                                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajoutez un CV ou un justificatif.</p>
                                                    <div class="mt-4 flex flex-col gap-3">
                                                        <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-[22px] border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100 dark:hover:border-blue-400 dark:hover:bg-blue-500/10">
                                                            <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                                            Choisir un fichier
                                                            <input type="file" id="cv_file" name="cv_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" required class="hidden">
                                                        </label>
                                                        <span id="cv-file-name" class="text-sm text-slate-500 dark:text-slate-400">Aucun fichier choisi</span>
                                                    </div>
                                                    <p class="mt-3 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                                                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                                                        Max <?php echo e((string) $driveMaxMb); ?> Mo.
                                                    </p>
                                                    <?php if ($cvPath !== ''): ?>
                                                        <p class="mt-3 text-xs text-slate-600 dark:text-slate-300">
                                                            Dernier CV :
                                                            <a class="font-semibold text-blue-600 underline decoration-blue-400 decoration-2 underline-offset-2 dark:text-blue-300" href="<?php echo e($cvPath); ?>" target="_blank" rel="noopener">Ouvrir</a>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="rounded-[30px] border border-slate-200/80 bg-slate-950 p-5 text-white dark:border-white/10">
                                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">Résumé avant envoi</p>
                                                    <dl class="mt-4 space-y-3 text-sm text-slate-200">
                                                        <div class="flex items-center justify-between gap-4">
                                                            <dt class="text-slate-400">Contact</dt>
                                                            <dd class="text-right font-semibold"><?php echo e($contactEmail !== '' ? $contactEmail : 'email non renseigné'); ?></dd>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-4">
                                                            <dt class="text-slate-400">Spécialité</dt>
                                                            <dd class="text-right font-semibold" data-apply-summary-title><?php echo e($title !== '' ? $title : 'À définir'); ?></dd>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-4">
                                                            <dt class="text-slate-400">Expérience</dt>
                                                            <dd class="text-right font-semibold" data-apply-summary-experience><?php echo e($teachingExperience !== '' ? ($teachingExperienceOptions[$teachingExperience] ?? $teachingExperience) : 'À définir'); ?></dd>
                                                        </div>
                                                        <?php if ($contactPhone !== ''): ?>
                                                            <div class="flex items-center justify-between gap-4">
                                                                <dt class="text-slate-400">Téléphone</dt>
                                                                <dd class="text-right font-semibold"><?php echo e($contactPhone); ?></dd>
                                                            </div>
                                                        <?php endif; ?>
                                                    </dl>
                                                    <div class="mt-5 rounded-[22px] border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                                                        Vous recevrez la suite par notification.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200 dark:hover:border-blue-400 dark:hover:text-blue-200" data-apply-prev>
                                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                                Précédent
                                            </button>
                                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-blue-600 px-6 py-3.5 text-sm font-bold text-white shadow-[0_20px_45px_-20px_rgba(37,99,235,0.72)] transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" <?php echo !$driveReady ? 'disabled' : ''; ?>>
                                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                                Envoyer ma candidature
                                            </button>
                                        </div>
                                    </div>
                                </section>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php if ($applicationNoticeState !== ''): ?>
        <div
            class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm"
            data-application-notice-modal
            data-application-notice-state="<?php echo e($applicationNoticeState); ?>"
            data-application-notice-auto-open="<?php echo $applicationNoticeAutoOpen ? '1' : '0'; ?>"
            aria-hidden="true"
            inert
        >
            <section
                class="relative w-full max-w-md overflow-hidden rounded-[26px] border border-white/70 bg-white shadow-[0_30px_100px_rgba(15,23,42,0.35)] dark:border-white/10 dark:bg-slate-900"
                role="dialog"
                aria-modal="true"
                aria-labelledby="application-notice-title"
                aria-describedby="application-notice-description"
            >
                <div class="h-1.5 bg-gradient-to-r from-blue-600 via-sky-500 to-cyan-400"></div>
                <div class="p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                            <i class="fas <?php echo $applicationNoticeState === 'success' ? 'fa-circle-check' : ($applicationNoticeState === 'profile' ? 'fa-user-edit' : 'fa-hourglass-half'); ?>" aria-hidden="true"></i>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-blue-300 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                            data-application-notice-close
                            aria-label="Fermer le message"
                        >
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>

                    <?php if ($applicationNoticeState === 'success'): ?>
                        <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Demande envoyée</p>
                        <h2 id="application-notice-title" class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                            Votre demande a bien été envoyée
                        </h2>
                        <p id="application-notice-description" class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Elle sera analysée dans les heures ou les jours à suivre. Vous recevrez ensuite une notification pour la suite.
                        </p>

                        <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                            <p class="flex items-start gap-3 text-sm font-semibold leading-5 text-blue-800 dark:text-blue-200">
                                <i class="fas fa-bell mt-0.5 text-blue-600 dark:text-blue-300" aria-hidden="true"></i>
                                <span>Vous serez notifié dès qu’une suite sera donnée.</span>
                            </p>
                        </div>
                    <?php elseif ($applicationNoticeState === 'pending'): ?>
                        <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Demande déjà envoyée</p>
                        <h2 id="application-notice-title" class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                            Votre demande est déjà en cours d’examen
                        </h2>
                        <p id="application-notice-description" class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Vous n’avez rien à renvoyer pour le moment. Une notification vous sera envoyée dès qu’une suite sera donnée.
                        </p>

                        <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                            <p class="flex items-start gap-3 text-sm font-semibold leading-5 text-blue-800 dark:text-blue-200">
                                <i class="fas fa-bell mt-0.5 text-blue-600 dark:text-blue-300" aria-hidden="true"></i>
                                <span>La suite arrivera par notification.</span>
                            </p>
                        </div>
                    <?php elseif ($applicationNoticeState === 'profile'): ?>
                        <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Profil incomplet</p>
                        <h2 id="application-notice-title" class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                            Complétez votre profil avant de continuer
                        </h2>
                        <p id="application-notice-description" class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            <?php if ($profileMissingFields !== []): ?>
                                Champs manquants : <?php echo e(implode(', ', $profileMissingFields)); ?>.
                            <?php else: ?>
                                Certains champs essentiels sont encore manquants.
                            <?php endif; ?>
                        </p>

                        <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                            <p class="flex items-start gap-3 text-sm font-semibold leading-5 text-blue-800 dark:text-blue-200">
                                <i class="fas fa-user-edit mt-0.5 text-blue-600 dark:text-blue-300" aria-hidden="true"></i>
                                <span>Une fois le profil complété, vous pourrez déposer votre candidature.</span>
                            </p>
                        </div>
                    <?php else: ?>
                        <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">À savoir avant l’envoi</p>
                        <h2 id="application-notice-title" class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                            On relira votre dossier
                        </h2>
                        <p id="application-notice-description" class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            On relira votre dossier dans les heures ou les jours qui suivent. Vous recevrez ensuite une notification pour la suite.
                        </p>

                        <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                            <p class="flex items-start gap-3 text-sm font-semibold leading-5 text-blue-800 dark:text-blue-200">
                                <i class="fas fa-bell mt-0.5 text-blue-600 dark:text-blue-300" aria-hidden="true"></i>
                                <span>La suite vous arrivera par notification.</span>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($applicationNoticeState === 'profile'): ?>
                        <a
                            href="<?php echo e($profileUrl); ?>"
                            class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-slate-900 sm:text-base"
                            data-application-notice-confirm
                        >
                            <i class="fas fa-user-edit" aria-hidden="true"></i>
                            Compléter mon profil
                        </a>
                    <?php else: ?>
                        <button
                            type="button"
                            class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-slate-900 sm:text-base"
                            data-application-notice-confirm
                        >
                            <i class="fas fa-check" aria-hidden="true"></i>
                            <?php echo $applicationNoticeState === 'success' ? 'Fermer' : 'J’ai compris'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</body>
</html>
