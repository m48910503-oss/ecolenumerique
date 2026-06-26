<?php
declare(strict_types=1);

$categories = array_values(is_array($categories ?? null) ? $categories : []);
$errors = array_values(is_array($errors ?? null) ? $errors : []);
$successMessage = trim((string) ($successMessage ?? ''));
$createdCourseId = max(0, (int) ($createdCourseId ?? 0));
$title = trim((string) ($title ?? ''));
$categoryId = trim((string) ($categoryId ?? ''));
$level = trim((string) ($level ?? 'beginner'));
$level = in_array($level, ['beginner', 'intermediate', 'advanced'], true) ? $level : 'beginner';
$description = trim((string) ($description ?? ''));
$objectives = array_values(is_array($objectives ?? null) ? $objectives : []);
$prerequisites = array_values(is_array($prerequisites ?? null) ? $prerequisites : []);
$equipment = array_values(is_array($equipment ?? null) ? $equipment : []);
$durationHours = trim((string) ($durationHours ?? ''));
$priceXof = trim((string) ($priceXof ?? ''));
$enterprisePriceXof = trim((string) ($enterprisePriceXof ?? ''));
$enterpriseDiscountPercent = trim((string) ($enterpriseDiscountPercent ?? ''));
$referralOverride = !empty($referralOverride);
$referralL2Enabled = !empty($referralL2Enabled);
$contentBuilderPayload = trim((string) ($contentBuilderPayload ?? ''));
$examBuilderPayload = trim((string) ($examBuilderPayload ?? ''));
$coverPathExisting = trim((string) ($coverPathExisting ?? ''));
$teacherId = max(0, (int) ($teacherId ?? 0));
$editMode = !empty($editMode);
$courseId = max(0, (int) ($courseId ?? 0));
$updatedCourseId = max(0, (int) ($updatedCourseId ?? 0));
$courseStatus = strtolower(trim((string) (($course['status'] ?? 'draft'))));
$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($teacherUser, 'Enseignant')
    : trim((string) (($teacherUser['first_name'] ?? '') . ' ' . ($teacherUser['last_name'] ?? '')));
$teacherDisplayName = $teacherDisplayName !== '' ? $teacherDisplayName : 'Enseignant';
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($teacherUser) : '';
$teacherInitials = function_exists('dashboard_user_initials')
    ? dashboard_user_initials($teacherUser, 'EN')
    : strtoupper(substr($teacherDisplayName, 0, 2));
$csrfToken = function_exists('view_csrf_token') ? view_csrf_token() : '';
$driveReady = !empty($driveReady);
$driveRemoteReady = !empty($driveRemoteReady);
$driveLocalReady = !empty($driveLocalReady);
$driveConnectRecommended = !empty($driveConnectRecommended);
$driveUploadNote = trim((string) ($driveUploadNote ?? ''));
$driveMaxMb = max(1, (int) ($driveMaxMb ?? 25));
$platformReferral = is_array($platformReferral ?? null) ? $platformReferral : ['l1_rate' => 0, 'l2_rate' => 0];
$teacherDraftCourses = array_values(is_array($teacherDraftCourses ?? null) ? $teacherDraftCourses : []);
$generalFileCacheName = trim((string) ($generalFileCacheName ?? ''));
$generalFileCacheSize = max(0, (int) ($generalFileCacheSize ?? 0));

$coverExistingUrl = $coverPathExisting !== '' ? site_url(ltrim($coverPathExisting, '/')) : '';
$createdCourseUrl = $createdCourseId > 0 ? site_url('teacher/course_edit?id=' . $createdCourseId) : '';
$formAction = $editMode && $courseId > 0 ? site_url('teacher/course_edit?id=' . $courseId) : site_url('teacher/course_create');
$autosaveKey = $editMode && $courseId > 0 ? 'course_edit_' . $courseId : 'course_create_' . $teacherId;
$autosaveClear = ($createdCourseId > 0 || $updatedCourseId > 0) ? '1' : '0';
$pageTitle = $editMode ? 'Modifier le cours complet' : 'Créer un cours complet';
$pageSubtitle = $editMode
    ? 'Modifiez les informations, le contenu et les évaluations depuis le même formulaire.'
    : 'Un seul brouillon pour les informations, le contenu et les évaluations.';
$draftButtonLabel = $editMode ? 'Enregistrer' : 'Brouillon';
$submitButtonLabel = $editMode
    ? ($courseStatus === 'approved' ? 'Enregistrer les modifications' : 'Soumettre les modifications')
    : 'Publier le cours';
$generalFileRemoveLabel = $editMode ? 'Retirer cette ressource' : 'Retirer cette ressource temporaire';
$teacherNavActive = $editMode ? 'courses' : 'course_create';
$teacherTopTitle = $pageTitle;
$teacherTopSubtitle = 'Cours, contenu et examens';
$showCatalogue = false;
$showSupportFab = true;
$driveStatusLabel = $driveRemoteReady ? 'Drive prêt' : ($driveLocalReady ? 'Stockage local' : 'À vérifier');
$driveStatusClass = $driveRemoteReady
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
    : ($driveLocalReady
        ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'
        : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300');
$enterprisePricingMode = 'none';
if ($enterprisePriceXof !== '') {
    $enterprisePricingMode = 'price';
} elseif ($enterpriseDiscountPercent !== '') {
    $enterprisePricingMode = 'discount';
}

$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '0 Ko';
    }
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 2, ',', ' ') . ' Mo';
    }
    return number_format($bytes / 1024, 0, ',', ' ') . ' Ko';
};

$listValues = static function (array $items): array {
    $clean = [];
    foreach ($items as $item) {
        $value = trim((string) $item);
        if ($value !== '') {
            $clean[] = $value;
        }
    }
    return $clean !== [] ? $clean : [''];
};

$countFilledList = static function (array $items): int {
    $count = 0;
    foreach ($items as $item) {
        if (trim((string) $item) !== '') {
            $count++;
        }
    }
    return $count;
};

$requiredChecks = [
    $title !== '',
    $level !== '',
    $description !== '',
    $priceXof !== '',
    $categoryId !== '',
    $coverPathExisting !== '',
];
$requiredDone = count(array_filter($requiredChecks));
$requiredTotal = count($requiredChecks);
$requiredRemaining = max(0, $requiredTotal - $requiredDone);
$draftProgressPct = $requiredTotal > 0 ? (int) round(($requiredDone / $requiredTotal) * 100) : 0;
$pedagogyCount = $countFilledList($objectives) + $countFilledList($prerequisites) + $countFilledList($equipment);
$pedagogyOpen = $pedagogyCount > 0;
$commercialReady = $priceXof !== '' && $categoryId !== '' && $coverPathExisting !== '';

$decodeBuilderCollection = static function (string $payload, string $rootKey): array {
    $decoded = json_decode($payload, true);
    if (!is_array($decoded)) {
        return [];
    }
    $items = $decoded[$rootKey] ?? [];
    return array_values(is_array($items) ? $items : []);
};

$draftModules = $decodeBuilderCollection($contentBuilderPayload, 'modules');
$draftModuleCount = count($draftModules);
$draftLessonCount = 0;
$draftMeetingCount = 0;
foreach ($draftModules as $moduleDraft) {
    $items = array_values(is_array($moduleDraft['items'] ?? null) ? $moduleDraft['items'] : []);
    $draftLessonCount += count($items);
    foreach ($items as $itemDraft) {
        if (trim((string) ($itemDraft['type'] ?? '')) === 'meeting') {
            $draftMeetingCount++;
        }
    }
}

$draftExams = $decodeBuilderCollection($examBuilderPayload, 'exams');
$draftExamCount = count($draftExams);
$journeyCards = [
    [
        'step' => 1,
        'title' => 'Présentation',
        'hint' => '',
        'state' => $requiredRemaining === 0 ? 'Prêt' : 'À faire',
    ],
    [
        'step' => 2,
        'title' => 'Offre & médias',
        'hint' => '',
        'state' => $commercialReady ? 'Prêt' : 'À faire',
    ],
    [
        'step' => 3,
        'title' => 'Contenu',
        'hint' => '',
        'state' => $draftModuleCount . ' module(s)',
    ],
    [
        'step' => 4,
        'title' => 'Évaluation',
        'hint' => '',
        'state' => $draftExamCount . ' examen(s)',
    ],
];

$phaseIntroductions = [
    1 => [
        'eyebrow' => 'Étape 1',
        'title' => 'Présentation du cours',
        'copy' => 'Complétez les champs de base du cours.',
        'fields' => [
            ['label' => 'Titre', 'help' => 'Nom visible du cours. Il doit être court, clair et facile à retenir.'],
            ['label' => 'Niveau', 'help' => 'Indique si le cours s’adresse à un débutant, à un niveau intermédiaire ou avancé.'],
            ['label' => 'Description', 'help' => 'Texte qui résume ce que l’apprenant va apprendre dans le cours.'],
            ['label' => 'Objectifs', 'help' => 'Liste des résultats attendus à la fin du cours.'],
            ['label' => 'Prérequis', 'help' => 'Ce que l’apprenant doit déjà savoir ou avoir avant de commencer.'],
            ['label' => 'Équipement', 'help' => 'Matériel ou outils nécessaires pour suivre le cours.'],
        ],
        'cta' => 'Commencer',
    ],
    2 => [
        'eyebrow' => 'Étape 2',
        'title' => 'Médias & tarification',
        'copy' => 'Renseignez l’offre et les médias du cours.',
        'fields' => [
            ['label' => 'Miniature', 'help' => 'Image de couverture du cours affichée dans les listes et sur la page du cours.'],
            ['label' => 'Prix public', 'help' => 'Montant payé par un apprenant individuel pour accéder au cours.'],
            ['label' => 'Offre entreprise', 'help' => 'Tarif spécial ou remise appliqué quand une entreprise achète le cours.'],
            ['label' => 'Catégorie', 'help' => 'Domaine dans lequel le cours sera classé et retrouvé par les utilisateurs.'],
            ['label' => 'Parrainage', 'help' => 'Active le partage de revenus lié aux recommandations, si vous souhaitez l’utiliser.'],
            ['label' => 'Ressource générale', 'help' => 'Document ou fichier partagé à tous les apprenants du cours.'],
        ],
        'cta' => 'Continuer',
    ],
    3 => [
        'eyebrow' => 'Étape 3',
        'title' => 'Contenu du cours',
        'copy' => 'Structurez votre cours en modules et leçons.',
        'fields' => [
            ['label' => 'Modules', 'help' => 'Grands chapitres du cours qui organisent l’apprentissage.'],
            ['label' => 'Leçons', 'help' => 'Contenus à l’intérieur de chaque module: vidéo, document ou rendez-vous.'],
            ['label' => 'Type de leçon', 'help' => 'Format de chaque leçon: vidéo, document à télécharger ou session planifiée.'],
            ['label' => 'Durée', 'help' => 'Temps estimé pour suivre une leçon ou la terminer.'],
            ['label' => 'Liens / fichiers', 'help' => 'Ressource à fournir pour la leçon, comme une vidéo ou un document.'],
        ],
        'cta' => 'Continuer',
    ],
    4 => [
        'eyebrow' => 'Étape 4',
        'title' => 'Évaluation finale',
        'copy' => 'Préparez l’évaluation finale du cours.',
        'fields' => [
            ['label' => 'Titre', 'help' => 'Nom de l’évaluation finale affiché aux apprenants.'],
            ['label' => 'Type', 'help' => 'Format de l’évaluation: QCM, document ou rendez-vous selon votre besoin.'],
            ['label' => 'Durée', 'help' => 'Temps accordé pour compléter l’évaluation.'],
            ['label' => 'Questions', 'help' => 'Énoncés proposés aux apprenants dans le test.'],
            ['label' => 'Réponses', 'help' => 'Choix ou réponses correctes attendues pour valider le test.'],
            ['label' => 'Sujet', 'help' => 'Fichier ou document utilisé comme base de l’évaluation, si nécessaire.'],
        ],
        'cta' => 'Continuer',
    ],
];

$renderIntroFields = static function (array $fields): void {
    if ($fields === []) {
        return;
    }
    ?>
    <div class="course-step-intro__fields">
        <?php foreach ($fields as $field): ?>
            <?php
            $label = is_array($field) ? trim((string) ($field['label'] ?? '')) : trim((string) $field);
            $help = is_array($field) ? trim((string) ($field['help'] ?? '')) : '';
            ?>
            <article class="course-step-intro__field-card">
                <span class="course-step-intro__field-label">
                    <i class="fas fa-circle-info"></i>
                    <span><?php echo e($label !== '' ? $label : 'Champ'); ?></span>
                </span>
                <?php if ($help !== ''): ?>
                    <p class="course-step-intro__field-help"><?php echo e($help); ?></p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
};

$renderSimpleRows = static function (array $items, string $name, string $placeholder) use ($listValues): void {
    foreach ($listValues($items) as $value) {
        ?>
        <div class="flex items-center gap-2">
            <input type="text" name="<?php echo e($name); ?>[]" value="<?php echo e($value); ?>" placeholder="<?php echo e($placeholder); ?>" class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
            <button type="button" data-remove-simple-row class="text-slate-400 hover:text-red-500 transition" aria-label="Supprimer">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
        <?php
    }
};
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light" data-view="<?php echo $editMode ? 'teacher/course_edit' : 'teacher/course_create'; ?>" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="app-global-assets" content="off">
    <title><?php echo e($pageTitle); ?> · Enseignant · École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/course_create_builder.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/course_create_builder.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/vendor/xlsx.full.min.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_complete_builder.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_complete_builder.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-gray-50 text-gray-800 antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-gray-200 font-sans" data-view="<?php echo $editMode ? 'teacher/course_edit' : 'teacher/course_create'; ?>">
    <div class="teacher-dashboard-layout relative min-h-screen overflow-x-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <main class="teacher-dashboard-main min-h-screen bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <div class="course-builder-page p-4 sm:p-5 lg:p-6 animate-fade-up">
                <div class="course-builder-shell grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem] 2xl:items-start">
                    <form method="post" action="<?php echo e($formAction); ?>" enctype="multipart/form-data" novalidate data-course-create-form data-autosave-key="<?php echo e($autosaveKey); ?>" data-autosave-clear="<?php echo e($autosaveClear); ?>" class="course-complete-builder-form min-w-0">
        <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
        <input type="hidden" id="content_payload" name="content_payload" value="<?php echo e($contentBuilderPayload); ?>">
        <input type="hidden" id="exams_payload" name="exams_payload" value="<?php echo e($examBuilderPayload); ?>">
        <input type="hidden" id="cover_path_existing" name="cover_path_existing" value="<?php echo e($coverPathExisting); ?>">
        <input type="hidden" id="referral_override" name="referral_override" value="<?php echo $referralOverride ? '1' : '0'; ?>">
        <input type="hidden" id="referral_l2_enabled" name="referral_l2_enabled" value="<?php echo $referralL2Enabled ? '1' : '0'; ?>">
        <input type="hidden" id="duration_hours" name="duration_hours" value="<?php echo e($durationHours !== '' ? $durationHours : '1'); ?>">

                        <section class="mb-4 flex flex-col gap-3 rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400"><?php echo $editMode ? 'Édition' : 'Création'; ?></p>
                                <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white"><?php echo e($pageTitle); ?></h1>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-preview-course class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="far fa-check-circle"></i> Vérifier
                                </button>
                                <button type="submit" name="submit_action" value="draft" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <i class="far fa-save"></i> <?php echo e($draftButtonLabel); ?>
                                </button>
                            </div>
                        </section>

            <?php if ($errors !== []): ?>
                <section class="course-builder-error-banner mb-6 rounded-2xl px-5 py-4 text-sm shadow-card">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <div class="space-y-1">
                            <p class="font-semibold">Le cours n’a pas été enregistré.</p>
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo e((string) $error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <section class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700 shadow-card">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle mt-0.5"></i>
                        <div class="space-y-1">
                            <p class="font-semibold"><?php echo e($successMessage); ?></p>
                            <?php if ($createdCourseUrl !== ''): ?>
                                <a href="<?php echo e($createdCourseUrl); ?>" class="inline-flex items-center gap-2 font-semibold text-emerald-700 hover:text-emerald-900">
                                    Reprendre le formulaire <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="course-builder-workflow rounded-[1.8rem] border border-slate-200 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                <div class="course-builder-workflow__head">
                    <div>
                        <p class="course-builder-workflow__eyebrow">Parcours</p>
                        <h2 class="course-builder-workflow__title">4 parties</h2>
                    </div>
                </div>
                <div class="course-builder-workflow__grid">
                    <?php foreach ($journeyCards as $journeyCard): ?>
                        <?php $stepNumber = (int) ($journeyCard['step'] ?? 1); ?>
                        <button type="button" data-step-target="<?php echo e((string) (($stepNumber - 1) * 2 + 1)); ?>" id="stepBtn<?php echo e((string) $stepNumber); ?>" class="course-builder-workflow__step flex items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold transition-all duration-300 <?php echo $stepNumber === 1 ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/25' : 'bg-white text-slate-600 border border-slate-200'; ?>">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold <?php echo $stepNumber === 1 ? 'bg-white text-primary-600' : 'bg-slate-100 text-slate-500'; ?>"><?php echo e((string) $stepNumber); ?></span>
                            <span class="course-builder-workflow__step-copy">
                                <strong><?php echo e((string) ($journeyCard['title'] ?? 'Étape')); ?></strong>
                                <?php if (trim((string) ($journeyCard['hint'] ?? '')) !== ''): ?>
                                    <small><?php echo e((string) ($journeyCard['hint'] ?? '')); ?></small>
                                <?php endif; ?>
                            </span>
                            <em class="course-builder-workflow__step-state"><?php echo e((string) ($journeyCard['state'] ?? '')); ?></em>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="course-builder-workflow__progress">
                    <div class="course-builder-workflow__progress-track">
                        <div id="progressBar" class="course-create-progress-fill course-builder-workflow__progress-fill"></div>
                    </div>
                    <div class="course-builder-workflow__progress-track">
                        <div id="progressBar2" class="course-create-progress-fill course-builder-workflow__progress-fill"></div>
                    </div>
                    <div class="course-builder-workflow__progress-track">
                        <div id="progressBar3" class="course-create-progress-fill course-builder-workflow__progress-fill"></div>
                    </div>
                </div>
            </section>

            <section id="step1Intro" class="course-step-intro rounded-[1.8rem] border border-slate-200 bg-white p-6 text-center shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                <p class="course-step-intro__eyebrow"><?php echo e($phaseIntroductions[1]['eyebrow']); ?></p>
                <h2 class="course-step-intro__title"><?php echo e($phaseIntroductions[1]['title']); ?></h2>
                <p class="course-step-intro__copy"><?php echo e($phaseIntroductions[1]['copy']); ?></p>
                <?php $renderIntroFields($phaseIntroductions[1]['fields'] ?? []); ?>
                <div class="course-step-intro__actions">
                    <button type="button" data-step-target="2" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <?php echo e($phaseIntroductions[1]['cta']); ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div id="step1Content" class="hidden course-step-content space-y-5">
                <div class="course-form-card bg-white rounded-2xl shadow-card p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-info-circle text-primary-600"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold">Informations générales</h2>
                        </div>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div>
                            <label for="title" class="block text-sm font-semibold mb-1.5">Titre <span class="text-red-500">*</span></label>
                            <input id="title" name="title" type="text" value="<?php echo e($title); ?>" placeholder="Ex: Maîtriser React JS" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                        </div>
                        <div>
                            <label for="level" class="block text-sm font-semibold mb-1.5">Niveau <span class="text-red-500">*</span></label>
                            <select id="level" name="level" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                <option value="beginner" <?php echo $level === 'beginner' ? 'selected' : ''; ?>>Débutant</option>
                                <option value="intermediate" <?php echo $level === 'intermediate' ? 'selected' : ''; ?>>Intermédiaire</option>
                                <option value="advanced" <?php echo $level === 'advanced' ? 'selected' : ''; ?>>Avancé</option>
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label for="description" class="block text-sm font-semibold mb-1.5">Description <span class="text-red-500">*</span></label>
                            <textarea id="description" name="description" rows="4" placeholder="Décrivez le cours..." required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"><?php echo e($description); ?></textarea>
                        </div>
                    </div>

                    <details class="course-builder-details mt-5 rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50" <?php echo $pedagogyOpen ? 'open' : ''; ?>>
                        <summary class="course-builder-details-summary flex cursor-pointer items-center justify-between gap-3 px-4 py-3">
                            <span class="font-semibold text-slate-900 dark:text-white">Pédagogie</span>
                            <span class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-300">
                                <span data-pedagogy-count><?php echo e((string) ($countFilledList($objectives) + $countFilledList($prerequisites) + $countFilledList($equipment))); ?> élément(s)</span>
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </summary>
                        <div class="course-builder-details-body space-y-5 border-t border-slate-200 p-4 dark:border-slate-700">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5">Objectifs pédagogiques</label>
                            <div id="objectives-container" class="space-y-2">
                                <?php $renderSimpleRows($objectives, 'objectives', 'Objectif'); ?>
                            </div>
                            <button type="button" data-add-simple-row data-container="objectives-container" data-name="objectives" data-placeholder="Objectif" class="mt-2 text-primary-600 text-sm font-medium flex items-center gap-1">
                                <i class="fas fa-plus text-xs"></i> Ajouter
                            </button>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1.5">Prérequis</label>
                            <div id="prerequisites-container" class="space-y-2">
                                <?php $renderSimpleRows($prerequisites, 'prerequisites', 'Prérequis'); ?>
                            </div>
                            <button type="button" data-add-simple-row data-container="prerequisites-container" data-name="prerequisites" data-placeholder="Prérequis" class="mt-2 text-amber-600 text-sm font-medium flex items-center gap-1">
                                <i class="fas fa-plus text-xs"></i> Ajouter
                            </button>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1.5">Équipement nécessaire</label>
                            <div id="equipment-container" class="space-y-2">
                                <?php $renderSimpleRows($equipment, 'equipment', 'Équipement'); ?>
                            </div>
                            <button type="button" data-add-simple-row data-container="equipment-container" data-name="equipment" data-placeholder="Équipement" class="mt-2 text-teal-600 text-sm font-medium flex items-center gap-1">
                                <i class="fas fa-plus text-xs"></i> Ajouter
                            </button>
                        </div>
                        </div>
                    </details>
                </div>

                <div class="course-step-actions flex justify-between">
                    <button type="button" data-step-target="1" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="3" class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl text-sm font-semibold shadow-lg hover:bg-primary-700 transition">
                        Continuer <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <section id="step2Intro" class="hidden course-step-intro rounded-[1.8rem] border border-slate-200 bg-white p-6 text-center shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                <p class="course-step-intro__eyebrow"><?php echo e($phaseIntroductions[2]['eyebrow']); ?></p>
                <h2 class="course-step-intro__title"><?php echo e($phaseIntroductions[2]['title']); ?></h2>
                <p class="course-step-intro__copy"><?php echo e($phaseIntroductions[2]['copy']); ?></p>
                <?php $renderIntroFields($phaseIntroductions[2]['fields'] ?? []); ?>
                <div class="course-step-intro__actions">
                    <button type="button" data-step-target="2" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="4" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <?php echo e($phaseIntroductions[2]['cta']); ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div id="step2Content" class="hidden space-y-6">
                <div class="course-form-card bg-white rounded-2xl shadow-card p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 bg-accent-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-image text-accent-600"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold">Médias & tarification</h2>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Miniature <span class="text-red-500">*</span></label>
                            <div id="thumbnail-zone" data-thumbnail-zone class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center cursor-pointer hover:border-primary-400 transition">
                                <input type="file" id="cover_file" name="cover_file" accept=".jpg,.jpeg,.png,.webp,image/*" <?php echo $coverPathExisting === '' ? 'required' : ''; ?> class="hidden">
                                <div id="thumb-placeholder" class="<?php echo $coverExistingUrl !== '' ? 'hidden' : ''; ?>">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-400 mb-2"></i>
                                    <p class="text-sm text-slate-700">Choisir une image</p>
                                    <p class="text-xs text-slate-400 mt-1">PNG, JPG, WebP</p>
                                </div>
                                <div id="thumb-preview" class="<?php echo $coverExistingUrl !== '' ? '' : 'hidden'; ?> relative">
                                    <img id="thumb-img" src="<?php echo e($coverExistingUrl); ?>" alt="Miniature du cours" class="w-full h-32 object-cover rounded-lg">
                                    <button type="button" data-remove-thumb class="absolute top-2 right-2 bg-white p-1 rounded-full shadow text-slate-700 hover:text-red-500" aria-label="Retirer la miniature">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="price_xof" class="block text-sm font-semibold">Prix public (F CFA) <span class="text-red-500">*</span></label>
                                <input id="price_xof" name="price_xof" type="number" min="0" step="1" value="<?php echo e($priceXof); ?>" placeholder="49000" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            </div>
                            <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div>
                                    <label for="enterprise_pricing_mode" class="block text-sm font-semibold">Offre entreprise</label>
                                    <select id="enterprise_pricing_mode" name="enterprise_pricing_mode" class="mt-2 w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                        <option value="none" <?php echo $enterprisePricingMode === 'none' ? 'selected' : ''; ?>>Aucune offre entreprise</option>
                                        <option value="price" <?php echo $enterprisePricingMode === 'price' ? 'selected' : ''; ?>>Prix fixe entreprise</option>
                                        <option value="discount" <?php echo $enterprisePricingMode === 'discount' ? 'selected' : ''; ?>>Réduction entreprise</option>
                                    </select>
                                </div>
                                <div data-enterprise-panel="price" class="<?php echo $enterprisePricingMode === 'price' ? '' : 'hidden'; ?>">
                                    <label for="enterprise_price_xof" class="block text-sm font-semibold">Prix entreprise (F CFA)</label>
                                    <input id="enterprise_price_xof" name="enterprise_price_xof" type="number" min="1" step="1" value="<?php echo e($enterprisePriceXof); ?>" placeholder="25000" class="mt-2 w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                </div>
                                <div data-enterprise-panel="discount" class="<?php echo $enterprisePricingMode === 'discount' ? '' : 'hidden'; ?>">
                                    <label for="enterprise_discount_percent" class="block text-sm font-semibold">Réduction entreprise (%)</label>
                                    <input id="enterprise_discount_percent" name="enterprise_discount_percent" type="number" min="0.01" max="100" step="0.01" value="<?php echo e($enterpriseDiscountPercent); ?>" placeholder="15" class="mt-2 w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                </div>
                                <p class="hidden text-xs font-semibold text-amber-600" data-enterprise-warning>Choisissez soit un prix entreprise, soit une réduction, pas les deux.</p>
                            </div>
                            <div>
                                <label for="category_id" class="block text-sm font-semibold">Catégorie <span class="text-red-500">*</span></label>
                                <select id="category_id" name="category_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                    <option value="">Sélectionnez une catégorie</option>
                                    <?php foreach ($categories as $category): ?>
                                        <?php
                                        $catId = (string) ($category['id'] ?? '');
                                        $catName = trim((string) ($category['name'] ?? 'Catégorie'));
                                        ?>
                                        <option value="<?php echo e($catId); ?>" <?php echo $categoryId === $catId ? 'selected' : ''; ?>><?php echo e($catName); ?></option>
                                    <?php endforeach; ?>
                                    <option value="other" <?php echo $categoryId === 'other' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Parrainage</label>
                                <button type="button" id="referral-toggle" data-referral-toggle class="relative inline-flex h-7 w-12 items-center rounded-full <?php echo $referralOverride ? 'bg-primary-600' : 'bg-slate-300'; ?> transition">
                                    <span id="ref-dot" class="inline-block h-5 w-5 rounded-full bg-white shadow transition <?php echo $referralOverride ? 'translate-x-6' : 'translate-x-1'; ?>"></span>
                                </button>
                                <span id="ref-status" class="ml-2 text-sm"><?php echo $referralOverride ? 'Activé' : 'Désactivé'; ?></span>
                                <div id="referral-options" class="<?php echo $referralOverride ? '' : 'hidden'; ?> mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <label class="inline-flex items-start gap-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" id="referral-l2-toggle" data-referral-l2-toggle <?php echo $referralL2Enabled ? 'checked' : ''; ?> class="mt-1 h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                        <span>
                                            Appliquer aussi au niveau 2
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <details class="course-builder-details mt-6 rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50" <?php echo $generalFileCacheName !== '' ? 'open' : ''; ?>>
                        <summary class="course-builder-details-summary flex cursor-pointer items-center justify-between gap-3 px-4 py-3">
                            <span class="font-semibold text-slate-900 dark:text-white"><i class="fas fa-cloud-upload-alt mr-1 text-emerald-500"></i>Ressource générale</span>
                            <span class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-300"><i class="fas fa-chevron-down"></i></span>
                        </summary>
                        <div class="course-builder-details-body border-t border-slate-200 p-4 dark:border-slate-700">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <p class="text-xs text-slate-500 dark:text-slate-300">Max <?php echo e((string) $driveMaxMb); ?> Mo</p>
                            <?php if ($driveConnectRecommended): ?>
                                <a href="<?php echo e(site_url('drive/oauth_connect')); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700">
                                    Connecter Drive <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                            </div>
                            <input id="general_file" name="general_file" type="file" class="mt-4 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                        <?php if ($generalFileCacheName !== ''): ?>
                            <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700">
                                <p class="font-semibold"><?php echo e($generalFileCacheName); ?></p>
                                <p class="text-xs"><?php echo e($formatBytes($generalFileCacheSize)); ?></p>
                                <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-blue-700">
                                    <input type="checkbox" name="general_file_clear" value="1" class="rounded border-blue-200">
                                    <?php echo e($generalFileRemoveLabel); ?>
                                </label>
                            </div>
                        <?php endif; ?>
                        </div>
                    </details>
                </div>
                <div class="course-step-actions flex justify-between">
                    <button type="button" data-step-target="3" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="5" class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl text-sm font-semibold shadow-lg hover:bg-primary-700 transition">
                        Continuer <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <section id="step3Intro" class="hidden course-step-intro rounded-[1.8rem] border border-slate-200 bg-white p-6 text-center shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                <p class="course-step-intro__eyebrow"><?php echo e($phaseIntroductions[3]['eyebrow']); ?></p>
                <h2 class="course-step-intro__title"><?php echo e($phaseIntroductions[3]['title']); ?></h2>
                <p class="course-step-intro__copy"><?php echo e($phaseIntroductions[3]['copy']); ?></p>
                <?php $renderIntroFields($phaseIntroductions[3]['fields'] ?? []); ?>
                <div class="course-step-intro__actions">
                    <button type="button" data-step-target="4" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="6" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <?php echo e($phaseIntroductions[3]['cta']); ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div id="step3Content" class="hidden space-y-6">
                <div class="bg-white rounded-2xl shadow-card p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-layer-group text-indigo-600"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold">Contenu du cours</h2>
                            </div>
                        </div>
                        <button type="button" data-add-module class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold shadow">
                            <i class="fas fa-plus text-xs"></i> Nouveau module
                        </button>
                    </div>
                    <div id="modules-container" class="space-y-4"></div>
                </div>
                <div class="course-step-actions flex justify-between">
                    <button type="button" data-step-target="5" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="7" class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl text-sm font-semibold shadow-lg hover:bg-primary-700 transition">
                        Continuer <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <section id="step4Intro" class="hidden course-step-intro rounded-[1.8rem] border border-slate-200 bg-white p-6 text-center shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                <p class="course-step-intro__eyebrow"><?php echo e($phaseIntroductions[4]['eyebrow']); ?></p>
                <h2 class="course-step-intro__title"><?php echo e($phaseIntroductions[4]['title']); ?></h2>
                <p class="course-step-intro__copy"><?php echo e($phaseIntroductions[4]['copy']); ?></p>
                <?php $renderIntroFields($phaseIntroductions[4]['fields'] ?? []); ?>
                <div class="course-step-intro__actions">
                    <button type="button" data-step-target="6" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="8" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <?php echo e($phaseIntroductions[4]['cta']); ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div id="step4Content" class="hidden space-y-6">
                <div class="bg-white rounded-2xl shadow-card p-6 sm:p-8">
                    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clipboard-check text-emerald-600"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold">Examens & Évaluations</h2>
                            </div>
                        </div>
                    </div>
                    <div id="exams-container" class="space-y-4"></div>
                </div>
                <div class="course-step-actions flex justify-between">
                    <button type="button" data-step-target="7" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="submit" name="submit_action" value="submit" class="flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl text-sm font-bold shadow-lg hover:from-primary-700 transition">
                        <i class="fas fa-rocket"></i> <?php echo e($submitButtonLabel); ?>
                    </button>
                </div>
            </div>
                    </form>

                    <aside class="course-builder-aside space-y-3 xl:sticky xl:top-24">
                        <section class="course-side-card course-side-roadmap rounded-2xl border border-slate-200 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Parcours</p>
                            <h2 class="mt-2 text-base font-bold text-slate-900 dark:text-white">Étapes</h2>
                            <div class="course-side-roadmap__list">
                                <?php foreach ($journeyCards as $journeyCard): ?>
                                    <button type="button" data-step-target="<?php echo e((string) ((((int) ($journeyCard['step'] ?? 1)) - 1) * 2 + 1)); ?>" class="course-side-roadmap__item">
                                        <span class="course-side-roadmap__index"><?php echo e((string) ($journeyCard['step'] ?? 1)); ?></span>
                                        <span class="course-side-roadmap__copy">
                                            <strong><?php echo e((string) ($journeyCard['title'] ?? 'Étape')); ?></strong>
                                            <small><?php echo e((string) ($journeyCard['state'] ?? '')); ?></small>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <?php if ($teacherDraftCourses !== []): ?>
                            <details class="course-side-disclosure rounded-2xl border border-slate-200 bg-white shadow-card dark:border-gray-700 dark:bg-gray-800">
                                <summary class="flex cursor-pointer items-center justify-between gap-3 p-4">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">Derniers brouillons</span>
                                    <span class="course-side-summary-meta">
                                        <a href="<?php echo e(site_url('teacher/courses?status=draft')); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-300">Voir tout</a>
                                        <i class="fas fa-chevron-down course-side-chevron" aria-hidden="true"></i>
                                    </span>
                                </summary>
                                <div class="space-y-2 border-t border-slate-100 p-4 dark:border-gray-700">
                                    <?php foreach (array_slice($teacherDraftCourses, 0, 3) as $draftCourse): ?>
                                        <?php
                                        $draftId = max(0, (int) ($draftCourse['id'] ?? 0));
                                        $draftTitle = trim((string) ($draftCourse['title'] ?? 'Cours sans titre'));
                                        ?>
                                        <a href="<?php echo e(site_url('teacher/course_edit?id=' . $draftId)); ?>" class="block rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 transition hover:border-blue-200 hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-900/60 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/10">
                                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-white"><?php echo e($draftTitle); ?></p>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </main>
    </div>

    <div id="toast" class="fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-elevated text-sm font-medium transform translate-y-20 opacity-0 transition-all duration-300 z-50 pointer-events-none flex items-center gap-2">
        <i id="toast-icon" class="fas fa-check-circle text-emerald-400"></i>
        <span id="toast-msg"></span>
    </div>
</body>
</html>
