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
$portfolioUrl = trim((string) ($portfolioUrl ?? ''));
$linkedinUrl = trim((string) ($linkedinUrl ?? ''));
$cvPath = trim((string) ($cvPath ?? ''));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$profileIncomplete = !empty($profileIncomplete);
$profileMissingFields = is_array($profileMissingFields ?? null) ? array_values($profileMissingFields) : [];
$dashboardUrl = trim((string) ($dashboardUrl ?? site_url('student/dashboard')));
$profileUrl = trim((string) ($profileUrl ?? site_url('profile')));
$notificationsUrl = trim((string) ($notificationsUrl ?? site_url('notifications')));
$publicPage = 'teacher/apply';
$isLoggedIn = true;
$contactEmail = trim((string) ($contactEmail ?? ''));
$contactPhone = trim((string) ($contactPhone ?? ''));
$linksOpen = $portfolioUrl !== '' || $linkedinUrl !== '';
$lockedCardTitle = $isCompanyAccount ? 'Compte entreprise non éligible' : 'Dossier en cours';
$lockedCardBody = $isCompanyAccount
    ? 'Les comptes entreprise ne peuvent pas candidater.'
    : 'Votre dossier est déjà validé ou en cours.';
$teachingExperienceOptions = [
    'regular' => 'Oui, régulièrement',
    'occasional' => 'Oui, occasionnellement',
    'beginner' => 'Non, mais je souhaite commencer',
];
$teacherInfoTopics = [
    [
        'key' => 'revenus',
        'icon' => 'fa-money-bill-wave',
        'title' => 'Revenus, prix et paiement',
        'summary' => 'Prix cadré.',
        'details' => [
            'Part et retrait validés.',
            'Prix selon le cours.',
            'Rien avant validation.',
        ],
    ],
    [
        'key' => 'creation',
        'icon' => 'fa-film',
        'title' => 'Création et publication du cours',
        'summary' => 'Vérification d’abord.',
        'details' => [
            'Compétence vérifiée.',
            'Plan après acceptation.',
            'Publication ensuite.',
        ],
    ],
    [
        'key' => 'droits',
        'icon' => 'fa-file-signature',
        'title' => 'Droits, image et profil public',
        'summary' => 'Profil finalisé après.',
        'details' => [
            'Profil public plus tard.',
            'Image préparée ensuite.',
            'Droits avant mise en ligne.',
        ],
    ],
    [
        'key' => 'validation',
        'icon' => 'fa-headset',
        'title' => 'Validation et accompagnement',
        'summary' => 'Réponse sous 3 à 5 jours.',
        'details' => [
            'Dossier examiné vite.',
            'Bénin ou ailleurs.',
            'Suite guidée après acceptation.',
        ],
    ],
];

$teacherHeroSignals = [
    [
        'icon' => 'fa-film',
        'title' => 'Pas de vidéos',
    ],
    [
        'icon' => 'fa-hourglass-half',
        'title' => 'Réponse rapide',
    ],
    [
        'icon' => 'fa-shield-alt',
        'title' => 'Cadre clair',
    ],
];

$appFromName = defined('APP_FROM_NAME') ? (string) constant('APP_FROM_NAME') : 'École Numérique';
$platformName = trim((string) platform_setting_get('site_name', $appFromName));
if ($platformName === '') {
    $platformName = $appFromName;
}

$platformLogoUrl = $siteLogoUrl;
$platformLogoAlt = 'Logo ' . $platformName;

$homeUrl = site_url('');
$catalogueUrl = site_url('catalogue');
$teacherApplyUrl = site_url('teacher/apply');
$supportUrl = site_url('support');
$privacyUrl = site_url('privacy');
$cguUrl = site_url('cgu');
$loginUrl = site_url('login');
$registerUrl = site_url('register');
$driveConnectUrl = site_url('drive/oauth_connect');
$publicNavPanelId = 'teacher-apply-public-nav-panel';

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
    <title>Devenir enseignant | <?php echo e($platformName); ?></title>
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

        @media (min-width: 981px) {
            .teacher-apply-page .public-mobile-toggle,
            .teacher-apply-page .public-mobile-panel {
                display: none !important;
            }
        }

        @media (max-width: 980px) {
            .teacher-apply-page .public-desktop-nav {
                display: none !important;
            }
        }
    </style>
</head>
<body class="app-view app-role-teacher teacher-apply-page overflow-x-hidden bg-gray-50 font-sans antialiased text-slate-900 dark:bg-gray-950 dark:text-slate-100" data-view="teacher/apply">
    <?php require __DIR__ . '/../web/_public_header_catalogue.php'; ?>

    <main class="overflow-x-hidden bg-gray-50 dark:bg-gray-950">
        <section class="relative overflow-hidden bg-gradient-to-br from-white via-gray-50 to-gray-100 pb-10 pt-10 text-slate-900 dark:from-gray-950 dark:via-gray-950 dark:to-gray-900 dark:text-white lg:pb-12 lg:pt-14">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute left-[-7rem] top-10 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl dark:bg-cyan-400/10"></div>
                <div class="absolute right-[-7rem] top-16 h-[26rem] w-[26rem] rounded-full bg-blue-500/10 blur-3xl dark:bg-blue-400/10"></div>
                <div class="absolute inset-x-0 top-[16rem] h-px bg-gradient-to-r from-transparent via-blue-200/60 to-transparent dark:via-blue-400/20"></div>
            </div>
            <div class="relative z-10 mx-auto max-w-[82rem] px-4 sm:px-6 lg:px-8">
                <div class="grid items-start gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:gap-10">
                    <div class="min-w-0 pt-1 lg:pt-6">
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/80 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm shadow-blue-100/80 backdrop-blur dark:border-blue-400/20 dark:bg-blue-500/10 dark:text-blue-200 dark:shadow-none">
                            <i class="fas fa-chalkboard-teacher text-cyan-500 dark:text-cyan-300" aria-hidden="true"></i>
                            Devenir enseignant
                        </span>

                        <h1 class="mt-5 max-w-2xl text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl lg:text-[2.95rem] lg:leading-[1]">
                            Transmettez votre savoir et
                            <span class="gradient-text">aidez</span>
                            d’autres à progresser.
                        </h1>

                        <p class="mt-4 max-w-xl text-sm leading-6 text-slate-600 dark:text-slate-300 sm:text-base">
                            Candidature courte. Validation avant publication.
                        </p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <a href="#teacher-apply-form" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-500 sm:text-base">
                                <i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>
                                Déposer ma candidature
                            </a>
                            <a href="#teacher-how-it-works" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3.5 text-sm font-bold text-slate-900 shadow-sm transition hover:border-blue-300 hover:bg-gray-50 sm:text-base dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:border-blue-400/40 dark:hover:bg-white/10">
                                <i class="fas fa-question-circle mr-2" aria-hidden="true"></i>
                                Comment ça marche
                            </a>
                        </div>

                        <div class="mt-7 grid gap-3 sm:grid-cols-3">
                            <?php foreach ($teacherHeroSignals as $signal): ?>
                                <div class="rounded-[22px] border border-slate-200/80 bg-white/80 p-4 shadow-[0_12px_30px_rgba(15,23,42,0.06)] backdrop-blur dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-200">
                                        <i class="fas <?php echo e((string) $signal['icon']); ?>" aria-hidden="true"></i>
                                    </div>
                                    <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                        <?php echo e((string) $signal['title']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-[960px] lg:justify-self-end">
                        <div class="rounded-[28px] border border-slate-200/80 bg-white/90 p-4 shadow-[0_28px_90px_rgba(15,23,42,0.14)] backdrop-blur dark:border-white/10 dark:bg-slate-950/50 dark:shadow-black/35 sm:p-5">
                            <section id="teacher-apply-form" class="scroll-mt-28">
                                <div class="rounded-[22px] border border-slate-200/70 bg-gradient-to-br from-white via-white to-slate-50 px-4 py-4 dark:border-white/10 dark:from-white/[0.04] dark:via-white/[0.04] dark:to-white/[0.02] sm:px-5 sm:py-5">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-md">
                                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <h2 class="text-lg font-black tracking-tight text-slate-950 dark:text-white sm:text-xl">Votre demande</h2>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2 sm:justify-end">
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-[11px] font-semibold text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200">
                                                <i class="fas fa-clock" aria-hidden="true"></i>
                                                3 à 5 jours
                                            </span>
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                                <i class="fas fa-user-shield" aria-hidden="true"></i>
                                                Profil vérifié
                                            </span>
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                                Envoi simple
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-4 space-y-2.5">
                                        <?php if ($alreadyTeacher): ?>
                                            <div data-feedback-inline="1" class="rounded-2xl border border-blue-200 bg-blue-50 px-3.5 py-2.5 text-sm text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-200">
                                                Vous êtes déjà enseignant.
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($pendingApplication): ?>
                                            <div data-feedback-inline="1" class="rounded-2xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800 dark:border-amber-600 dark:bg-amber-900/40 dark:text-amber-100">
                                                Demande déjà en cours.
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($errors !== []): ?>
                                            <div data-feedback-inline="1" class="rounded-2xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm leading-6 text-red-800 dark:border-red-700 dark:bg-red-900/50 dark:text-red-100">
                                                <ul class="space-y-1 pl-4">
                                                    <?php foreach ($errors as $err): ?>
                                                        <li><?php echo e((string) $err); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($successMessage !== ''): ?>
                                            <div data-feedback-inline="1" class="rounded-2xl border border-blue-200 bg-blue-50 px-3.5 py-2.5 text-sm text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-200">
                                                <?php echo e($successMessage); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($profileIncomplete && $errors === [] && !$formLocked): ?>
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-700 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-300">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-slate-900 dark:text-white">Profil à compléter.</p>
                                                        <?php if ($profileMissingFields !== []): ?>
                                                            <p class="mt-1 text-xs">Champs manquants : <?php echo e(implode(', ', $profileMissingFields)); ?>.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <a href="<?php echo e($profileUrl); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white px-3.5 py-2 text-xs font-bold text-blue-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 dark:border-blue-400/30 dark:bg-white/5 dark:text-blue-200 dark:hover:bg-white/10">
                                                        <i class="fas fa-user-edit" aria-hidden="true"></i>
                                                        Compléter mon profil
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($formLocked): ?>
                                        <div class="mt-4 rounded-2xl border border-slate-200/70 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                            <div class="flex items-start gap-3">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                                    <i class="fas fa-lock" aria-hidden="true"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($lockedCardTitle); ?></p>
                                                    <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">
                                                        <?php echo e($lockedCardBody); ?>
                                                    </p>
                                                    <div class="mt-3 flex flex-wrap gap-2.5">
                                                        <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Catalogue</a>
                                                        <a href="<?php echo e($supportUrl); ?>" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300">Support</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                    <?php if (!$driveReady): ?>
                                        <div data-feedback-inline="1" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-sm text-amber-800 dark:border-amber-600 dark:bg-amber-900/30 dark:text-amber-100">
                                            <?php if ($driveNeedsConnect): ?>
                                                <div class="flex items-start gap-3">
                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm dark:bg-slate-900/40 dark:text-amber-300">
                                                        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-semibold">Drive non connecté</p>
                                                        <p class="mt-1 leading-5">Connectez Drive pour le CV.</p>
                                                        <a class="mt-3 inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-white px-3.5 py-2 font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-500/40 dark:bg-slate-900/40 dark:text-amber-100 dark:hover:bg-slate-900/70" href="<?php echo e($driveConnectUrl); ?>">
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

                                    <form method="post" action="<?php echo e(site_url('teacher/apply')); ?>" class="mt-4" enctype="multipart/form-data">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <div class="grid gap-5 lg:grid-cols-[1.08fr_0.92fr]">
                                            <section class="space-y-3">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Votre profil</p>
                                                    </div>
                                                </div>
                                                <div class="space-y-3">
                                                    <div>
                                                        <label for="title" class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-200">Compétence principale à enseigner</label>
                                                        <div class="relative">
                                                            <i class="fas fa-briefcase absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                                                            <input type="text" id="title" name="title" required value="<?php echo e($title); ?>" placeholder="Ex: Développement web, comptabilité, marketing digital" class="w-full rounded-2xl border border-gray-200 bg-white py-2.5 pl-9 pr-4 text-sm text-gray-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200 sm:text-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/20">
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label for="teaching_experience" class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-200">Avez-vous déjà enseigné ?</label>
                                                        <div class="relative">
                                                            <i class="fas fa-user-graduate absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                                                            <select id="teaching_experience" name="teaching_experience" required class="w-full appearance-none rounded-2xl border border-gray-200 bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200 sm:text-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/20">
                                                                <option value="">Sélectionner</option>
                                                                <?php foreach ($teachingExperienceOptions as $value => $label): ?>
                                                                    <option value="<?php echo e($value); ?>" <?php echo $teachingExperience === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <i class="fas fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[11px] text-gray-400" aria-hidden="true"></i>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label for="bio" class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-200">À propos de vous</label>
                                                        <textarea id="bio" name="bio" rows="3" required placeholder="Présentez votre expérience et votre motivation." class="w-full resize-none rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200 sm:text-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/20"><?php echo e($bio); ?></textarea>
                                                    </div>
                                                </div>
                                            </section>

                                            <div class="space-y-3">
                                                <details<?php echo $linksOpen ? ' open' : ''; ?> class="rounded-[22px] border border-slate-200/80 bg-white/70 px-3 py-2.5 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/[0.03]">
                                                    <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-1 py-1.5 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                                        <span class="min-w-0">
                                                            <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 dark:text-slate-300">Liens professionnels</span>
                                                        </span>
                                                        <span class="ml-auto inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">Afficher</span>
                                                        <i class="fas fa-chevron-down shrink-0 text-[11px] text-slate-400" aria-hidden="true"></i>
                                                    </summary>
                                                    <div class="mt-3 grid gap-3">
                                                        <div>
                                                            <label for="portfolio_url" class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-200">Portfolio</label>
                                                            <div class="relative">
                                                                <i class="fas fa-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                                                                <input type="url" id="portfolio_url" name="portfolio_url" value="<?php echo e($portfolioUrl); ?>" placeholder="https://..." class="w-full rounded-2xl border border-gray-200 bg-white py-2.5 pl-9 pr-4 text-sm text-gray-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200 sm:text-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/20">
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <label for="linkedin_url" class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-200">LinkedIn</label>
                                                            <div class="relative">
                                                                <i class="fab fa-linkedin absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                                                                <input type="url" id="linkedin_url" name="linkedin_url" value="<?php echo e($linkedinUrl); ?>" placeholder="https://www.linkedin.com/in/..." class="w-full rounded-2xl border border-gray-200 bg-white py-2.5 pl-9 pr-4 text-sm text-gray-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-200 sm:text-base dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:ring-blue-500/20">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </details>

                                                <section class="space-y-3">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Votre CV</p>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-3">
                                                        <div class="rounded-[22px] border border-slate-200/70 bg-gradient-to-br from-white via-white to-slate-50 p-3 shadow-sm dark:border-white/10 dark:from-white/[0.04] dark:via-white/[0.04] dark:to-white/[0.02]">
                                                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-200">Fichier de candidature</label>
                                                            <div class="flex flex-col gap-2.5">
                                                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:bg-gray-800">
                                                                    <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                                                    Choisir un fichier
                                                                    <input type="file" id="cv_file" name="cv_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" required class="hidden">
                                                                </label>
                                                                <span id="cv-file-name" class="text-sm text-slate-500 dark:text-slate-400">Aucun fichier choisi</span>
                                                            </div>
                                                            <p class="mt-2 flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-300">
                                                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                                                Max <?php echo e((string) $driveMaxMb); ?> Mo.
                                                            </p>
                                                            <?php if ($cvPath !== ''): ?>
                                                                <p class="mt-2 text-[11px] text-slate-600 dark:text-slate-300">
                                                                    Dernier CV :
                                                                    <a class="text-blue-600 underline decoration-blue-400 decoration-2 underline-offset-2 dark:text-blue-300" href="<?php echo e($cvPath); ?>" target="_blank" rel="noopener">Ouvrir</a>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div>
                                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60 sm:text-base" <?php echo !$driveReady ? 'disabled' : ''; ?>>
                                                                <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                                                Envoyer ma candidature
                                                            </button>
                                                            <div class="mt-3 flex flex-col items-center gap-2 text-[11px] leading-5 text-slate-600 dark:text-slate-300">
                                                                <span class="inline-flex flex-wrap items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
                                                                    <i class="fas fa-envelope text-blue-500" aria-hidden="true"></i>
                                                                    <?php echo e($contactEmail !== '' ? $contactEmail : 'email non renseigné'); ?>
                                                                </span>
                                                                <span class="inline-flex items-center gap-2">
                                                                    Réponse sous 3 à 5 jours.
                                                                    <a href="<?php echo e($profileUrl); ?>" class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">Modifier</a>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </section>
                                            </div>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="teacher-how-it-works" class="border-t border-slate-200 bg-white py-10 dark:border-white/10 dark:bg-gray-950 sm:py-12">
            <div class="mx-auto max-w-[82rem] px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Repères rapides</p>
                        <h2 class="mt-3 text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">Avant de candidater</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Ouvrez un sujet pour le détail.
                        </p>
                    </div>
                    <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">
                        Voir les formations publiées
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-[1.15fr_0.95fr_0.95fr_0.95fr]">
                    <?php foreach ($teacherInfoTopics as $topicIndex => $topic): ?>
                        <article class="flex min-h-[15rem] flex-col rounded-[22px] border bg-slate-50/80 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_18px_45px_rgba(15,23,42,0.08)] dark:bg-white/[0.03] <?php echo $topicIndex === 0 ? 'border-blue-300/50 bg-gradient-to-br from-blue-500/10 via-white to-slate-50 dark:from-blue-500/15 dark:via-white/[0.04] dark:to-white/[0.02]' : 'border-slate-200 dark:border-white/10'; ?>">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl <?php echo $topicIndex === 0 ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25' : 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-200'; ?>">
                                <i class="fas <?php echo e((string) $topic['icon']); ?>" aria-hidden="true"></i>
                            </div>
                            <?php if ($topicIndex === 0): ?>
                                <span class="mt-4 inline-flex w-fit items-center gap-1.5 rounded-full border border-blue-200 bg-white px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-blue-700 dark:border-blue-400/30 dark:bg-white/[0.04] dark:text-blue-200">
                                    <i class="fas fa-star" aria-hidden="true"></i>
                                    À savoir
                                </span>
                            <?php endif; ?>
                            <h3 class="mt-4 text-base font-black leading-6 text-slate-950 dark:text-white"><?php echo e((string) $topic['title']); ?></h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e((string) $topic['summary']); ?></p>
                            <button
                                type="button"
                                class="mt-auto inline-flex items-center gap-2 pt-4 text-left text-sm font-bold text-blue-600 transition hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200"
                                data-teacher-info-open="<?php echo e((string) $topic['key']); ?>"
                                aria-controls="teacher-info-drawer"
                            >
                                Voir les détails
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div
            id="teacher-info-drawer"
            class="fixed inset-0 z-[80] hidden"
            data-teacher-info-drawer
            aria-hidden="true"
        >
            <button type="button" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" data-teacher-info-close aria-label="Fermer le panneau"></button>
            <aside class="relative ml-auto flex h-full w-full max-w-2xl flex-col overflow-hidden bg-white shadow-2xl dark:bg-slate-950">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Détail formateur</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Détails</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:text-blue-200"
                        data-teacher-info-close
                    >
                        <i class="fas fa-times" aria-hidden="true"></i>
                        <span class="sr-only">Fermer</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-6">
                    <?php foreach ($teacherInfoTopics as $topic): ?>
                        <article class="hidden" data-teacher-info-panel="<?php echo e((string) $topic['key']); ?>">
                            <div class="flex items-start gap-4">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-200">
                                    <i class="fas <?php echo e((string) $topic['icon']); ?>" aria-hidden="true"></i>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-xl font-black tracking-tight text-slate-950 dark:text-white"><?php echo e((string) $topic['title']); ?></h3>
                                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e((string) $topic['summary']); ?></p>
                                </div>
                            </div>

                            <div class="mt-7 space-y-4">
                                <?php foreach ((array) $topic['details'] as $detail): ?>
                                    <p class="flex gap-3 text-sm leading-6 text-slate-700 dark:text-slate-300">
                                        <i class="fas fa-check mt-1 text-blue-600 dark:text-blue-300" aria-hidden="true"></i>
                                        <span><?php echo e((string) $detail); ?></span>
                                    </p>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                                <a href="#teacher-apply-form" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500" data-teacher-info-close>
                                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                    Déposer ma candidature
                                </a>
                                <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-800 shadow-sm transition hover:border-blue-300 hover:text-blue-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-100 dark:hover:text-blue-200">
                                    <i class="fas fa-book-open" aria-hidden="true"></i>
                                    Voir les formations
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../web/_public_footer_home.php'; ?>
</body>
</html>
