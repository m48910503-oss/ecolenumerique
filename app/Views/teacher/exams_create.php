<?php
declare(strict_types=1);

$course = is_array($course ?? null) ? $course : [];
$errors = is_array($errors ?? null) ? $errors : [];
$success = trim((string) ($success ?? ''));
$warning = trim((string) ($warning ?? ''));
$pageTitle = trim((string) ($pageTitle ?? 'Examen'));
$isLocked = !empty($isLocked);
$isEdit = !empty($isEdit);
$driveReady = !empty($driveReady);
$driveNeedsConnect = !empty($driveNeedsConnect);
$driveConnectRecommended = !empty($driveConnectRecommended);
$driveUploadNote = trim((string) ($driveUploadNote ?? ''));
$driveMaxMb = max(1, (int) ($driveMaxMb ?? 25));
$title = trim((string) ($title ?? ''));
$type = trim((string) ($type ?? 'qcm'));
$description = trim((string) ($description ?? ''));
$duration = max(0, (int) ($duration ?? 0));
$maxAttempts = max(1, (int) ($maxAttempts ?? 1));
$minScore = max(0, (int) ($minScore ?? 0));
$pointsPerQuestion = max(1, (int) ($pointsPerQuestion ?? 1));
$meetingMode = in_array((string) ($meetingMode ?? 'online'), ['online', 'onsite'], true) ? (string) $meetingMode : 'online';
$meetingLink = trim((string) ($meetingLink ?? ''));
$meetingPlace = trim((string) ($meetingPlace ?? ''));
$driveSubjectLink = trim((string) ($driveSubjectLink ?? ''));
$driveSubjectName = trim((string) ($driveSubjectName ?? ''));
$attemptCount = max(0, (int) ($attemptCount ?? 0));
$initialQuestionsByVariant = is_array($initialQuestionsByVariant ?? null) ? $initialQuestionsByVariant : [1 => [], 2 => [], 3 => []];
$hasSettingsColumn = !empty($hasSettingsColumn);
$examSettings = is_array($examSettings ?? null) ? $examSettings : [];
$examTemplates = is_array($examTemplates ?? null) ? $examTemplates : [];
$returnUrl = trim((string) ($returnUrl ?? site_url('teacher/courses')));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));

$courseId = (int) ($course['id'] ?? ($_GET['course_id'] ?? 0));
$returnMode = trim((string) ($_GET['return'] ?? 'content'));
$formAction = site_url('teacher/exams_create?course_id=' . $courseId . ($returnMode !== '' ? '&return=' . urlencode($returnMode) : ''));
$csrfToken = view_csrf_token();

$courseTitle = trim((string) ($course['title'] ?? 'Cours'));
if ($courseTitle === '') {
    $courseTitle = 'Cours';
}

$courseStatus = strtolower(trim((string) ($course['status'] ?? 'draft')));
$courseStatusLabel = match ($courseStatus) {
    'approved' => 'Approuvé',
    'submitted' => 'Soumis',
    'rejected' => 'Rejeté',
    'archived' => 'Archivé',
    default => 'Brouillon',
};
$courseStatusTone = match ($courseStatus) {
    'approved' => 'success',
    'submitted' => 'warning',
    default => 'info',
};

$currentExam = is_array($examTemplates[0] ?? null) ? $examTemplates[0] : [];
$currentExamId = max(0, (int) ($currentExam['id'] ?? 0));
$currentExamUpdatedAt = trim((string) ($currentExam['updated_at'] ?? ''));

$fmtDateTime = static function (string $value): string {
    if ($value === '') {
        return 'Non défini';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return 'Non défini';
    }
    return date('d/m/Y H:i', $ts);
};

$coverPath = trim((string) ($course['cover_path'] ?? ''));
$courseCoverUrl = '';
if ($coverPath !== '') {
    $courseCoverUrl = preg_match('~^https?://~i', $coverPath) === 1
        ? $coverPath
        : site_url(ltrim($coverPath, '/'));
}

$typeLabel = match ($type) {
    'drive' => 'Soumission de document',
    'meeting' => 'Rendez-vous',
    default => 'QCM',
};
$typeShortLabel = match ($type) {
    'drive' => 'Drive',
    'meeting' => 'Rendez-vous',
    default => 'QCM',
};
$durationLabel = $duration > 0 ? format_duration_minutes_hm($duration) : 'Durée libre';
$meetingModeLabel = $meetingMode === 'onsite' ? 'Présentiel' : 'En ligne';

$questionsPayload = ['variants' => ['1' => [], '2' => [], '3' => []]];
$variantQuestionCounts = [1 => 0, 2 => 0, 3 => 0];
$readyVariants = 0;
$totalQuestions = 0;
foreach ([1, 2, 3] as $variant) {
    $questions = array_values(is_array($initialQuestionsByVariant[$variant] ?? null) ? $initialQuestionsByVariant[$variant] : []);
    $questionsPayload['variants'][(string) $variant] = $questions;
    $variantQuestionCounts[$variant] = count($questions);
    $totalQuestions += $variantQuestionCounts[$variant];
    if ($variantQuestionCounts[$variant] > 0) {
        $readyVariants++;
    }
}

$randomization = is_array($examSettings['randomization'] ?? null) ? $examSettings['randomization'] : [];
$scoring = is_array($examSettings['scoring'] ?? null) ? $examSettings['scoring'] : [];
$availability = is_array($examSettings['availability'] ?? null) ? $examSettings['availability'] : [];
$antiCheat = is_array($examSettings['anti_cheat'] ?? null) ? $examSettings['anti_cheat'] : [];

$availabilityEnabled = !empty($availability['enabled']);
$availabilityStartAt = trim((string) ($availability['start_at'] ?? ''));
$availabilityEndAt = trim((string) ($availability['end_at'] ?? ''));
$availabilityLabel = 'Toujours disponible';
if ($availabilityEnabled && ($availabilityStartAt !== '' || $availabilityEndAt !== '')) {
    $parts = [];
    if ($availabilityStartAt !== '') {
        $parts[] = 'Du ' . $fmtDateTime(str_replace('T', ' ', $availabilityStartAt));
    }
    if ($availabilityEndAt !== '') {
        $parts[] = 'Au ' . $fmtDateTime(str_replace('T', ' ', $availabilityEndAt));
    }
    $availabilityLabel = implode(' ', $parts);
}

$antiCheatCount = 0;
foreach (['fullscreen_required', 'copy_paste_block', 'tab_switch_warning', 'camera_required'] as $antiKey) {
    if (!empty($antiCheat[$antiKey])) {
        $antiCheatCount++;
    }
}

$qcmBuilderReadOnly = $isEdit && $attemptCount > 0 && $type === 'qcm';
$typeLocked = $isEdit && $attemptCount > 0;
$driveSubjectReady = $driveSubjectLink !== '';
$meetingReady = in_array($meetingMode, ['online', 'onsite'], true);
$structureReady = match ($type) {
    'drive' => $driveSubjectReady,
    'meeting' => $meetingReady,
    default => $readyVariants === 3,
};

$heroStructureValue = '';
$heroStructureMeta = '';
$summaryStructureCount = '';
$summaryStructureMeta = '';
if ($type === 'qcm') {
    $heroStructureValue = $readyVariants . '/3 versions';
    $heroStructureMeta = $totalQuestions . ' question(s) répartie(s) sur les variantes.';
    $summaryStructureCount = (string) $readyVariants;
    $summaryStructureMeta = $totalQuestions . ' question(s) au total.';
} elseif ($type === 'drive') {
    $heroStructureValue = $driveSubjectReady ? 'Sujet prêt' : 'Sujet manquant';
    $heroStructureMeta = $driveSubjectReady ? 'Le sujet est déjà associé à cet examen.' : 'Ajoutez un sujet avant publication.';
    $summaryStructureCount = $driveSubjectReady ? 'OK' : 'À revoir';
    $summaryStructureMeta = $driveSubjectReady ? 'Sujet relié au flux Drive.' : 'Le sujet Drive reste obligatoire.';
} else {
    $heroStructureValue = $meetingModeLabel;
    $heroStructureMeta = $meetingReady ? 'Le lien ou le lieu sera défini lors de la confirmation d\'un créneau.' : 'Choisissez un mode de rendez-vous.';
    $summaryStructureCount = $meetingReady ? 'OK' : 'À revoir';
    $summaryStructureMeta = $meetingReady ? 'Mode de rendez-vous sélectionné.' : 'Le mode de rendez-vous reste à choisir.';
}

$summaryChecks = [
    $title !== '',
    in_array($type, ['qcm', 'drive', 'meeting'], true),
    $structureReady,
    $description !== '',
];
$summaryReadyCount = 0;
foreach ($summaryChecks as $summaryCheck) {
    if ($summaryCheck) {
        $summaryReadyCount++;
    }
}
$summaryProgressPct = (int) round(($summaryReadyCount / max(1, count($summaryChecks))) * 100);

$summaryNotes = [];
if ($title === '') {
    $summaryNotes[] = 'Ajoutez un titre clair pour identifier l\'examen.';
}
if ($description === '') {
    $summaryNotes[] = 'Ajoutez les consignes apprenant.';
}
if ($type === 'qcm' && !$structureReady) {
    $summaryNotes[] = 'Chaque version A, B et C doit contenir au moins une question valide.';
}
if ($type === 'drive' && !$driveSubjectReady) {
    $summaryNotes[] = 'Le sujet à remettre doit être ajouté avant l\'enregistrement final.';
}
if ($type === 'meeting' && !$meetingReady) {
    $summaryNotes[] = 'Choisissez le mode du rendez-vous.';
}
if (!$hasSettingsColumn) {
    $summaryNotes[] = 'Les réglages avancés sont visibles ici, mais la base ne sauvegarde pas encore settings_json.';
}
if ($qcmBuilderReadOnly) {
    $summaryNotes[] = 'Des tentatives existent déjà : les questions restent en lecture seule.';
}
if ($isLocked) {
    $summaryNotes[] = 'Le cours est actuellement soumis, la modification reste bloquée.';
}

$pageHeading = $isEdit ? 'Modifier l\'examen' : 'Créer un examen';
$pageLead = $isEdit
    ? 'Ajustez les règles, le type d\'évaluation et les réglages avancés sans sortir du même flux enseignant.'
    : 'Montez un examen final cohérent avec le cours en reprenant la même palette visuelle que le reste de l\'espace enseignant.';

$questionsJson = json_encode($questionsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($questionsJson === false) {
    $questionsJson = '{"variants":{"1":[],"2":[],"3":[]}}';
}
$settingsJson = json_encode($examSettings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($settingsJson === false) {
    $settingsJson = '{}';
}
$templatesJson = json_encode(array_values($examTemplates), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($templatesJson === false) {
    $templatesJson = '[]';
}

$typeCards = [
    [
        'key' => 'qcm',
        'title' => 'QCM à 3 versions',
        'subtitle' => 'Versions A, B et C avec barème unique.',
        'fact' => '3 tentatives automatiques',
        'icon' => 'clipboard-list',
    ],
    [
        'key' => 'drive',
        'title' => 'Soumission Drive',
        'subtitle' => 'Exposez un sujet puis récupérez le document remis.',
        'fact' => '1 tentative',
        'icon' => 'upload',
    ],
    [
        'key' => 'meeting',
        'title' => 'Rendez-vous oral',
        'subtitle' => 'Fixez un oral en ligne ou en présentiel.',
        'fact' => '1 tentative',
        'icon' => 'calendar',
    ],
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title><?php echo e($pageHeading); ?> - École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/vendor/xlsx.full.min.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/exam_builder.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/exam_builder.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 text-slate-900 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-slate-100 font-body" data-view="teacher/exams_create">
    <div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php $teacherNavActive = 'courses'; ?>
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="flex-1 min-w-0">
            <?php $teacherTopTitle = $pageHeading; ?>
            <?php $teacherTopSubtitle = 'Espace enseignant'; ?>
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="teacher-workflow-main mx-auto max-w-7xl px-4 py-6 md:px-6 xl:px-8 space-y-6">
                <?php if ($errors !== []): ?>
                    <section class="exam-create-alert exam-create-alert--danger" role="alert" aria-live="assertive">
                        <span class="exam-create-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>
                        <div class="exam-create-alert__body">
                            <p class="exam-create-alert__title">Des corrections sont requises</p>
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo e((string) $error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <section class="exam-create-alert exam-create-alert--success" role="status" aria-live="polite">
                        <span class="exam-create-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                        <div class="exam-create-alert__body">
                            <p class="exam-create-alert__title">Enregistrement effectué</p>
                            <p><?php echo e($success); ?></p>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($warning !== ''): ?>
                    <section class="exam-create-alert exam-create-alert--warning" role="status" aria-live="polite">
                        <span class="exam-create-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('help-circle'); ?></span>
                        <div class="exam-create-alert__body">
                            <p class="exam-create-alert__title">Point d'attention</p>
                            <p><?php echo e($warning); ?></p>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($isLocked): ?>
                    <section class="exam-create-alert exam-create-alert--warning" role="status" aria-live="polite">
                        <span class="exam-create-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>
                        <div class="exam-create-alert__body">
                            <p class="exam-create-alert__title">Cours verrouillé</p>
                            <p>Cours soumis à validation. Enregistrement bloqué.</p>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if (!$hasSettingsColumn): ?>
                    <section class="exam-create-alert exam-create-alert--warning" role="status" aria-live="polite">
                        <span class="exam-create-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('help-circle'); ?></span>
                        <div class="exam-create-alert__body">
                            <p class="exam-create-alert__title">Migration incomplète</p>
                            <p>Les réglages avancés sont visibles dans cette page, mais la colonne <code>settings_json</code> manque encore en base.</p>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="exam-create-hero">
                    <div class="exam-create-hero__header flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="flex min-w-0 items-start gap-4">
                            <div class="exam-create-card__icon flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border">
                                <?php if ($courseCoverUrl !== ''): ?>
                                    <img src="<?php echo e($courseCoverUrl); ?>" alt="Couverture du cours" class="h-full w-full rounded-2xl object-cover">
                                <?php elseif ($siteLogoUrl !== ''): ?>
                                    <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full rounded-2xl object-contain">
                                <?php else: ?>
                                    <?php echo dashboard_icon_svg('clipboard-list'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <p class="exam-create-hero__eyebrow">Évaluation finale du cours</p>
                                <h1 class="exam-create-hero__title text-3xl font-display font-semibold"><?php echo e($pageHeading); ?></h1>
                                <p class="exam-create-hero__subtitle"><?php echo e($pageLead); ?></p>

                                <div class="exam-create-stepper" aria-label="Étapes de configuration">
                                    <span class="exam-create-stepper__item is-done">
                                        <span class="exam-create-stepper__index">1</span>
                                        <span>Cadre</span>
                                    </span>
                                    <span class="exam-create-stepper__item is-active">
                                        <span class="exam-create-stepper__index">2</span>
                                        <span>Structure</span>
                                    </span>
                                    <span class="exam-create-stepper__item <?php echo $summaryProgressPct >= 100 ? 'is-done' : ''; ?>">
                                        <span class="exam-create-stepper__index">3</span>
                                        <span>Validation</span>
                                    </span>
                                </div>

                                <div class="exam-create-hero__meta">
                                    <article class="exam-create-hero__meta-item">
                                        <span class="exam-create-hero__meta-label">Cours</span>
                                        <strong class="exam-create-hero__meta-value"><?php echo e($courseTitle); ?></strong>
                                        <span class="exam-create-hero__meta-sub">ID cours <?php echo e((string) $courseId); ?></span>
                                    </article>
                                    <article class="exam-create-hero__meta-item">
                                        <span class="exam-create-hero__meta-label">Statut</span>
                                        <strong class="exam-create-hero__meta-value"><?php echo e($courseStatusLabel); ?></strong>
                                        <span class="exam-create-hero__meta-sub">Le flux respecte le statut du cours.</span>
                                    </article>
                                    <article class="exam-create-hero__meta-item">
                                        <span class="exam-create-hero__meta-label">Dernière mise à jour</span>
                                        <strong class="exam-create-hero__meta-value"><?php echo e($currentExamUpdatedAt !== '' ? $fmtDateTime($currentExamUpdatedAt) : 'Nouvel examen'); ?></strong>
                                        <span class="exam-create-hero__meta-sub"><?php echo e($isEdit ? 'Configuration existante chargée.' : 'Aucun examen final n\'existe.'); ?></span>
                                    </article>
                                </div>
                            </div>
                        </div>

                        <div class="exam-create-hero__actions">
                            <a href="<?php echo e($returnUrl); ?>" class="exam-create-hero__back inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                                Retour au cours
                            </a>
                            <?php if ($currentExamId > 0): ?>
                                <a href="<?php echo e(site_url('teacher/exam_preview?exam_id=' . $currentExamId)); ?>" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                                    Aperçu
                                </a>
                                <a href="<?php echo e(site_url('teacher/exams_manage?exam_id=' . $currentExamId)); ?>" class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layers'); ?></span>
                                    Gérer
                                </a>
                            <?php endif; ?>
                            <span id="exam-type-hero-pill" class="exam-create-status-pill exam-create-status-pill--info"><?php echo e($typeLabel); ?></span>
                            <span class="exam-create-status-pill exam-create-status-pill--<?php echo e($courseStatusTone); ?>"><?php echo e($courseStatusLabel); ?></span>
                        </div>
                    </div>

                </section>

                <form id="exam-form" method="post" enctype="multipart/form-data" action="<?php echo e($formAction); ?>" data-course-id="<?php echo e((string) $courseId); ?>">
                    <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="questions_json" id="questions_json" value="<?php echo e($questionsJson); ?>">
                    <input type="hidden" name="settings_json" id="settings_json" value="<?php echo e($settingsJson); ?>">
                    <input id="score_partial_credit" type="checkbox" class="hidden" hidden <?php echo !empty($scoring['multiple_partial_credit']) ? 'checked' : ''; ?>>
                    <input id="score_wrong_penalty" type="hidden" value="<?php echo e((string) (float) ($scoring['wrong_penalty'] ?? 0)); ?>">
                    <input id="availability_enabled" type="checkbox" class="hidden" hidden <?php echo $availabilityEnabled ? 'checked' : ''; ?>>
                    <input id="availability_start_at" type="hidden" value="<?php echo e($availabilityStartAt); ?>">
                    <input id="availability_end_at" type="hidden" value="<?php echo e($availabilityEndAt); ?>">

                    <div class="exam-create-layout">
                        <div class="exam-create-main">
                            <section id="exam-live-validation" class="exam-create-banner exam-create-banner--error hidden" hidden aria-live="polite"></section>

                            <section id="exam-frame" class="exam-create-form-card">
                                <div class="exam-create-card__header">
                                    <div class="exam-create-card__heading">
                                        <span class="exam-create-card__kicker">Cadre général</span>
                                        <h2 class="exam-create-card__title">Informations de l'examen</h2>
                                        <p class="exam-create-card__subtitle">Titre, format et consignes de l’examen.</p>
                                    </div>
                                    <?php if ($typeLocked): ?>
                                        <span class="exam-create-status-pill exam-create-status-pill--warning"><?php echo e((string) $attemptCount); ?> tentative(s) déjà en base</span>
                                    <?php elseif ($isEdit): ?>
                                        <span class="exam-create-status-pill exam-create-status-pill--success">Configuration existante</span>
                                    <?php else: ?>
                                        <span class="exam-create-status-pill exam-create-status-pill--info">Nouvel examen</span>
                                    <?php endif; ?>
                                </div>

                                <div class="exam-create-type-grid" role="group" aria-label="Choisissez le type d'examen">
                                    <?php foreach ($typeCards as $typeCard): ?>
                                        <?php
                                        $cardKey = (string) ($typeCard['key'] ?? 'qcm');
                                        $isActiveCard = $type === $cardKey;
                                        $isDisabledCard = $isLocked || ($typeLocked && !$isActiveCard);
                                        ?>
                                        <button
                                            type="button"
                                            class="exam-create-type-option<?php echo $isActiveCard ? ' is-active' : ''; ?>"
                                            data-exam-type-choice="<?php echo e($cardKey); ?>"
                                            <?php echo $isDisabledCard ? 'disabled aria-disabled="true"' : ''; ?>
                                        >
                                            <span class="exam-create-type-option__top">
                                                <span class="exam-create-type-option__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg((string) ($typeCard['icon'] ?? 'clipboard-list')); ?></span>
                                                <span>
                                                    <span class="exam-create-type-option__title"><?php echo e((string) ($typeCard['title'] ?? 'Examen')); ?></span>
                                                    <span class="exam-create-type-option__subtitle"><?php echo e((string) ($typeCard['subtitle'] ?? '')); ?></span>
                                                </span>
                                            </span>
                                            <span class="exam-create-type-option__meta">
                                                <span class="exam-create-type-option__fact"><?php echo e((string) ($typeCard['fact'] ?? '')); ?></span>
                                                <?php if ($typeLocked && !$isActiveCard): ?>
                                                    <span class="exam-create-type-option__lock">Type verrouillé par des tentatives existantes.</span>
                                                <?php elseif ($isLocked): ?>
                                                    <span class="exam-create-type-option__lock">Cours soumis : mode en lecture seule.</span>
                                                <?php endif; ?>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="exam-create-tool-grid">
                                    <div class="exam-create-field">
                                        <label class="exam-create-field__label" for="exam-title">Titre de l'examen *</label>
                                        <input id="exam-title" name="title" type="text" value="<?php echo e($title); ?>" placeholder="Ex : Évaluation finale du module 1" <?php echo $isLocked ? 'readonly' : ''; ?>>
                                    </div>
                                    <div class="exam-create-field">
                                        <label class="exam-create-field__label" for="exam-type">Type d'examen *</label>
                                        <select
                                            id="exam-type"
                                            name="type"
                                            data-locked="<?php echo $typeLocked ? '1' : '0'; ?>"
                                            data-fixed-value="<?php echo e($type); ?>"
                                            <?php echo $isLocked ? 'disabled' : ''; ?>
                                        >
                                            <option value="qcm" <?php echo $type === 'qcm' ? 'selected' : ''; ?>>QCM</option>
                                            <option value="drive" <?php echo $type === 'drive' ? 'selected' : ''; ?>>Soumission de document</option>
                                            <option value="meeting" <?php echo $type === 'meeting' ? 'selected' : ''; ?>>Rendez-vous</option>
                                        </select>
                                    </div>
                                    <div class="exam-create-field">
                                        <label class="exam-create-field__label" for="duration_min">Durée (minutes)</label>
                                        <input id="duration_min" name="duration_min" type="number" min="0" value="<?php echo e($duration > 0 ? (string) $duration : ''); ?>" placeholder="Ex: 45" <?php echo $isLocked ? 'readonly' : ''; ?>>
                                    </div>
                                    <div class="exam-create-field">
                                        <label class="exam-create-field__label" for="max_attempts">Tentatives prévues</label>
                                        <input id="max_attempts" name="max_attempts" type="number" min="1" value="<?php echo e((string) $maxAttempts); ?>" readonly>
                                    </div>
                                    <div class="exam-create-field">
                                        <label class="exam-create-field__label" for="min_score">Note minimale</label>
                                        <input id="min_score" name="min_score" type="number" min="0" value="<?php echo e((string) $minScore); ?>" <?php echo $isLocked ? 'readonly' : ''; ?>>
                                        <p class="exam-create-subpanel__note">C'est la note à partir de laquelle l'étudiant est admis.</p>
                                    </div>
                                </div>

                                <div class="exam-create-field">
                                    <label class="exam-create-field__label" for="exam-description">Description et consignes</label>
                                    <textarea id="exam-description" name="description" rows="4" placeholder="Expliquez les attentes, la logique de notation et les points de vigilance." <?php echo $isLocked ? 'readonly' : ''; ?>><?php echo e($description); ?></textarea>
                                </div>

                                <?php if ($examTemplates !== []): ?>
                                    <div class="exam-create-subpanel">
                                        <div>
                                            <h3 class="exam-create-subpanel__title">Repartir d'un modèle interne</h3>
                                            <p class="exam-create-subpanel__subtitle">Rechargez un ancien examen du même cours pour gagner du temps.</p>
                                        </div>
                                        <div class="exam-create-tool-card__inline">
                                            <select id="template-exam-id" <?php echo ($isLocked || $typeLocked) ? 'disabled' : ''; ?>>
                                                <option value="">Choisir un modèle</option>
                                                <?php foreach ($examTemplates as $examTemplate): ?>
                                                    <option value="<?php echo e((string) (int) ($examTemplate['id'] ?? 0)); ?>">
                                                        <?php echo e((string) ($examTemplate['title'] ?? 'Examen')); ?>
                                                        <?php if (!empty($examTemplate['updated_at'])): ?>
                                                            - mise à jour <?php echo e($fmtDateTime((string) $examTemplate['updated_at'])); ?>
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" id="btn-apply-template" class="exam-create-inline-btn app-btn-soft" <?php echo ($isLocked || $typeLocked) ? 'disabled' : ''; ?>>
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                                                Appliquer
                                            </button>
                                        </div>
                                        <p class="exam-create-subpanel__note">Le modèle remplace le formulaire courant.</p>
                                    </div>
                                <?php endif; ?>

                                <div id="exam-template-data" class="hidden" data-templates="<?php echo e($templatesJson); ?>"></div>
                            </section>

                            <section id="qcm-builder" class="exam-create-form-card exam-create-builder" data-readonly="<?php echo ($isLocked || $qcmBuilderReadOnly) ? '1' : '0'; ?>" data-exam-type-panel="qcm">
                                <div class="exam-create-card__header">
                                    <div class="exam-create-card__heading">
                                        <span class="exam-create-card__kicker">QCM</span>
                                        <h2 class="exam-create-card__title">Constructeur de questions</h2>
                                        <p class="exam-create-card__subtitle">Choisissez la variante à publier.</p>
                                    </div>
                                    <span class="exam-create-status-pill exam-create-status-pill--info"><?php echo e((string) $readyVariants); ?>/3 variantes renseignées</span>
                                </div>

                                <div class="exam-create-tool-grid">
                                    <article class="exam-create-tool-card">
                                        <div class="exam-create-tool-card__header">
                                            <div>
                                                <h3 class="exam-create-tool-card__title">Importer des questions</h3>
                                                <p class="exam-create-tool-card__subtitle">CSV ou Excel avec colonnes <code>question</code>, <code>variant</code>, <code>type</code>, <code>option_a</code>...</p>
                                            </div>
                                            <span class="exam-create-tool-card__pill">Import en lot</span>
                                        </div>
                                        <div class="exam-create-tool-card__body">
                                            <input id="question-import-csv" type="file" accept=".csv,.xlsx,.xls" <?php echo ($isLocked || $qcmBuilderReadOnly) ? 'disabled' : ''; ?>>
                                            <div class="exam-create-tool-card__inline">
                                                <button type="button" id="btn-import-csv" class="exam-create-inline-btn app-btn-success" <?php echo ($isLocked || $qcmBuilderReadOnly) ? 'disabled' : ''; ?>>
                                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('upload'); ?></span>
                                                    Importer
                                                </button>
                                                <a href="<?php echo e(site_url('assets/samples/qcm_import_example.csv')); ?>" class="exam-create-inline-btn app-btn-soft" download>
                                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('download'); ?></span>
                                                    Exemple CSV
                                                </a>
                                                <button type="button" class="exam-create-inline-btn app-btn-soft" data-help-trigger data-help-type="qcm" aria-controls="exam-help-modal" aria-expanded="false">
                                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('help-circle'); ?></span>
                                                    Aide
                                                </button>
                                            </div>
                                        </div>
                                    </article>

                                    <article class="exam-create-tool-card">
                                        <div class="exam-create-tool-card__header">
                                            <div>
                                                <h3 class="exam-create-tool-card__title">Barème du QCM</h3>
                                                <p class="exam-create-tool-card__subtitle">Un score unique est appliqué à toutes les versions.</p>
                                            </div>
                                            <span class="exam-create-tool-card__pill"><?php echo e((string) $pointsPerQuestion); ?> pt</span>
                                        </div>
                                        <div class="exam-create-tool-card__body">
                                            <div class="exam-create-builder__controls">
                                                <label class="exam-create-field__label mb-0" for="points_per_question">Points / question</label>
                                                <input id="points_per_question" name="points_per_question" type="number" min="1" value="<?php echo e((string) $pointsPerQuestion); ?>" <?php echo $qcmBuilderReadOnly ? 'readonly' : ''; ?>>
                                            </div>
                                            <p class="exam-create-tool-card__hint"><?php echo e($qcmBuilderReadOnly ? 'Les questions restent figées car des copies existent déjà.' : 'Le barème est repris sur chaque question créée ou importée.'); ?></p>
                                        </div>
                                    </article>
                                </div>

                                <div class="exam-create-builder__controls">
                                    <div>
                                        <p class="exam-create-builder__panel-label">Ajoutez les questions par version.</p>
                                    </div>
                                </div>

                                <p id="qcm-errors" class="exam-create-inline-error hidden"></p>

                                <div class="exam-create-variant-tabs" role="tablist" aria-label="Versions du QCM">
                                    <?php foreach ([1 => 'Version A', 2 => 'Version B', 3 => 'Version C'] as $variantKey => $variantLabel): ?>
                                        <button type="button" class="variant-tab border border-slate-700 px-4 py-2 text-sm font-semibold" data-variant-tab="<?php echo e((string) $variantKey); ?>" role="tab">
                                            <?php echo e($variantLabel); ?>
                                            <?php if ($variantQuestionCounts[$variantKey] > 0): ?>
                                                (<?php echo e((string) $variantQuestionCounts[$variantKey]); ?>)
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>

                                <?php foreach ([1 => 'Version A', 2 => 'Version B', 3 => 'Version C'] as $variantKey => $variantLabel): ?>
                                    <?php
                                    $variantJson = json_encode(array_values(is_array($initialQuestionsByVariant[$variantKey] ?? null) ? $initialQuestionsByVariant[$variantKey] : []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                                    if ($variantJson === false) {
                                        $variantJson = '[]';
                                    }
                                    ?>
                                    <section class="exam-create-subpanel" data-variant-panel="<?php echo e((string) $variantKey); ?>">
                                        <div class="exam-create-variant-panel-head">
                                            <h3 class="exam-create-subpanel__title"><?php echo e($variantLabel); ?></h3>
                                            <p class="exam-create-subpanel__subtitle"><?php echo e($variantQuestionCounts[$variantKey] > 0 ? $variantQuestionCounts[$variantKey] . ' question(s) chargée(s).' : 'Ajoutez au moins une question pour cette variante.'); ?></p>
                                        </div>
                                        <div class="exam-create-variant-panel-actions">
                                            <button type="button" class="exam-create-inline-btn app-btn-success" data-variant-add-question="<?php echo e((string) $variantKey); ?>" <?php echo ($isLocked || $qcmBuilderReadOnly) ? 'disabled' : ''; ?>>
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                                                Ajouter une question
                                            </button>
                                        </div>
                                        <div class="questions-container space-y-3" data-variant="<?php echo e((string) $variantKey); ?>" data-initial="<?php echo e($variantJson); ?>"></div>
                                    </section>
                                <?php endforeach; ?>
                            </section>

                            <section class="exam-create-form-card" data-exam-type-panel="drive">
                                <div class="exam-create-actions-row">
                                    <button type="button" class="exam-create-inline-btn app-btn-soft" data-help-trigger data-help-type="drive" aria-controls="exam-help-modal" aria-expanded="false">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('help-circle'); ?></span>
                                        Aide
                                    </button>
                                </div>
                                <div class="exam-create-card__header">
                                    <div class="exam-create-card__heading">
                                        <span class="exam-create-card__kicker">Soumission</span>
                                        <h2 class="exam-create-card__title">Sujet à partager via Drive</h2>
                                        <p class="exam-create-card__subtitle">L'apprenant voit le sujet puis remet directement son document à corriger.</p>
                                    </div>
                                    <span class="exam-create-status-pill <?php echo $driveSubjectReady ? 'exam-create-status-pill--success' : 'exam-create-status-pill--warning'; ?>">
                                        <?php echo e($driveSubjectReady ? 'Sujet déjà en place' : 'Sujet requis'); ?>
                                    </span>
                                </div>

                                <div class="exam-create-subpanel">
                                    <div>
                                        <h3 class="exam-create-subpanel__title">Document du sujet</h3>
                                        <p class="exam-create-subpanel__subtitle">Formats autorisés : PDF, Office, images, archives et CSV.</p>
                                    </div>
                                    <div class="exam-create-field">
                                        <label class="exam-create-field__label" for="drive_subject_file">Fichier sujet</label>
                                        <input
                                            id="drive_subject_file"
                                            name="drive_subject_file"
                                            type="file"
                                            data-required-on-drive="<?php echo $driveSubjectReady ? '0' : '1'; ?>"
                                            <?php echo $isLocked ? 'disabled' : ''; ?>
                                        >
                                    </div>
                                    <p class="exam-create-subpanel__note">
                                        Taille maximale : <?php echo e((string) $driveMaxMb); ?> Mo.
                                        <?php echo e($driveUploadNote !== '' ? $driveUploadNote : "Google Drive n'est pas configure."); ?>
                                        <?php if ($driveConnectRecommended): ?>
                                            <a href="<?php echo e(site_url('drive/oauth_connect')); ?>">Connecter Drive</a>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <?php if ($driveSubjectReady): ?>
                                    <div class="exam-create-reference">
                                        <span class="exam-create-reference__label">Sujet actuellement associé</span>
                                        <p class="exam-create-reference__value">
                                            <a class="exam-create-subpanel__link" href="<?php echo e($driveSubjectLink); ?>" target="_blank" rel="noopener">
                                                <?php echo e($driveSubjectName !== '' ? $driveSubjectName : $driveSubjectLink); ?>
                                            </a>
                                        </p>
                                        <p class="exam-create-reference__meta">Le document sera conservé si vous n'en importez pas un nouveau.</p>
                                    </div>
                                <?php endif; ?>

                            </section>

                            <section class="exam-create-form-card" data-exam-type-panel="meeting">
                                <div class="exam-create-actions-row">
                                    <button type="button" class="exam-create-inline-btn app-btn-soft" data-help-trigger data-help-type="meeting" aria-controls="exam-help-modal" aria-expanded="false">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('help-circle'); ?></span>
                                        Aide
                                    </button>
                                </div>
                                <div class="exam-create-card__header">
                                    <div class="exam-create-card__heading">
                                        <span class="exam-create-card__kicker">Oral / Entretien</span>
                                        <h2 class="exam-create-card__title">Configuration du rendez-vous</h2>
                                        <p class="exam-create-card__subtitle">Choisissez le mode et confirmez le créneau.</p>
                                    </div>
                                    <span class="exam-create-status-pill <?php echo $meetingReady ? 'exam-create-status-pill--success' : 'exam-create-status-pill--warning'; ?>">
                                        <?php echo e($meetingReady ? 'Mode choisi' : 'Mode requis'); ?>
                                    </span>
                                </div>

                                <div class="exam-create-tool-grid">
                                    <div class="exam-create-field">
                                        <label class="exam-create-field__label" for="meeting-mode">Mode du rendez-vous</label>
                                        <select id="meeting-mode" name="meeting_mode" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                            <option value="online" <?php echo $meetingMode === 'online' ? 'selected' : ''; ?>>En ligne</option>
                                            <option value="onsite" <?php echo $meetingMode === 'onsite' ? 'selected' : ''; ?>>Présentiel</option>
                                        </select>
                                    </div>
                                    <article class="exam-create-tool-card">
                                        <div class="exam-create-tool-card__header">
                                            <div>
                                                <h3 class="exam-create-tool-card__title">Coordonnées du rendez-vous</h3>
                                                <p class="exam-create-tool-card__subtitle">Non renseignées à cette étape.</p>
                                            </div>
                                            <span class="exam-create-tool-card__pill">Plus tard</span>
                                        </div>
                                        <div class="exam-create-tool-card__body">
                                            <p class="exam-create-tool-card__hint">Lien ou lieu ajouté lors de la validation du créneau.</p>
                                        </div>
                                    </article>
                                </div>

                                <div class="exam-create-subpanel">
                                    <div>
                                        <h3 class="exam-create-subpanel__title">Logique côté apprenant</h3>
                                        <p class="exam-create-subpanel__subtitle">L'apprenant choisira ensuite un créneau dans la gestion des tentatives.</p>
                                    </div>
                                    <p class="exam-create-subpanel__note">Le mode actuel est <strong><?php echo e($meetingModeLabel); ?></strong>. Vous confirmerez ensuite le créneau sélectionné avec le lien ou le lieu définitif.</p>
                                </div>
                            </section>

                            <section id="exam-advanced-settings" class="exam-create-form-card<?php echo $type === 'qcm' ? '' : ' hidden'; ?>" data-exam-type-panel="qcm" <?php echo $type === 'qcm' ? '' : 'hidden'; ?>>
                                <div class="exam-create-card__header">
                                    <div class="exam-create-card__heading">
                                        <span class="exam-create-card__kicker">Réglages avancés</span>
                                        <h2 class="exam-create-card__title">Contrôle et configuration</h2>
                                        <p class="exam-create-card__subtitle">Ces réglages s'appliquent uniquement au QCM.</p>
                                    </div>
                                    <span class="exam-create-status-pill exam-create-status-pill--info"><?php echo e((string) $antiCheatCount); ?> consigne(s) de passage active(s)</span>
                                </div>

                                <div class="exam-create-settings-grid">
                                    <article class="exam-create-setting-card qcm-advanced-only">
                                        <h3 class="exam-create-setting-card__title">Aléatoire du QCM</h3>
                                        <div class="exam-create-check-grid">
                                            <label class="exam-create-check">
                                                <input id="rand_question_shuffle" type="checkbox" <?php echo !empty($randomization['question_shuffle']) ? 'checked' : ''; ?> <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                Mélanger l'ordre des questions
                                            </label>
                                            <label class="exam-create-check">
                                                <input id="rand_option_shuffle" type="checkbox" <?php echo !empty($randomization['option_shuffle']) ? 'checked' : ''; ?> <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                Mélanger les options
                                            </label>
                                        </div>
                                        <div class="exam-create-field">
                                            <label class="exam-create-field__label" for="rand_variant_strategy">Stratégie des variantes</label>
                                            <select id="rand_variant_strategy" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <option value="sequential" <?php echo (($randomization['variant_strategy'] ?? 'sequential') === 'sequential') ? 'selected' : ''; ?>>Séquence A/B/C</option>
                                                <option value="seeded_random" <?php echo (($randomization['variant_strategy'] ?? '') === 'seeded_random') ? 'selected' : ''; ?>>Aléatoire stable</option>
                                                <option value="fixed_a" <?php echo (($randomization['variant_strategy'] ?? '') === 'fixed_a') ? 'selected' : ''; ?>>Toujours A</option>
                                                <option value="fixed_b" <?php echo (($randomization['variant_strategy'] ?? '') === 'fixed_b') ? 'selected' : ''; ?>>Toujours B</option>
                                                <option value="fixed_c" <?php echo (($randomization['variant_strategy'] ?? '') === 'fixed_c') ? 'selected' : ''; ?>>Toujours C</option>
                                            </select>
                                        </div>
                                    </article>

                                    <article class="exam-create-setting-card">
                                        <h3 class="exam-create-setting-card__title">Consignes d'examen</h3>
                                        <p class="exam-create-setting-card__subtitle">Ces options sont affichées à l'apprenant comme cadre de passage et rappels de consignes.</p>
                                        <div class="exam-create-check-grid">
                                            <label class="exam-create-check">
                                                <input id="anti_fullscreen" type="checkbox" <?php echo !empty($antiCheat['fullscreen_required']) ? 'checked' : ''; ?> <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                Plein écran requis
                                            </label>
                                            <label class="exam-create-check">
                                                <input id="anti_copy_paste" type="checkbox" <?php echo !empty($antiCheat['copy_paste_block']) ? 'checked' : ''; ?> <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                Bloquer copier-coller
                                            </label>
                                            <label class="exam-create-check">
                                                <input id="anti_tab_switch" type="checkbox" <?php echo !empty($antiCheat['tab_switch_warning']) ? 'checked' : ''; ?> <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                Alerte changement d'onglet
                                            </label>
                                            <label class="exam-create-check">
                                                <input id="anti_camera" type="checkbox" <?php echo !empty($antiCheat['camera_required']) ? 'checked' : ''; ?> <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                Caméra requise
                                            </label>
                                        </div>
                                    </article>
                                </div>
                            </section>

                            <section id="exam-reference-context" class="exam-create-card">
                                <div class="exam-create-card__header exam-create-card__header--tight">
                                    <div class="exam-create-card__heading">
                                        <span class="exam-create-card__kicker">Références</span>
                                        <h2 class="exam-create-card__title">Contexte du formulaire</h2>
                                        <p class="exam-create-card__subtitle">Quelques repères fixes pour garder une configuration cohérente.</p>
                                    </div>
                                </div>

                                <div class="exam-create-reference-list exam-create-reference-list--compact">
                                    <article class="exam-create-reference">
                                        <span class="exam-create-reference__label">Type courant</span>
                                        <p id="exam-reference-type" class="exam-create-reference__value"><?php echo e($typeLabel); ?></p>
                                        <p class="exam-create-reference__meta">Le type pilote les panneaux visibles et le nombre de tentatives.</p>
                                    </article>
                                    <article class="exam-create-reference">
                                        <span class="exam-create-reference__label">Tentatives</span>
                                        <p id="exam-reference-attempts" class="exam-create-reference__value">Tentatives prévues : <?php echo e((string) $maxAttempts); ?></p>
                                        <p class="exam-create-reference__meta"><?php echo e($type === 'qcm' ? 'Le QCM reste fixé à 3 tentatives.' : 'Les autres formats restent limités à une tentative.'); ?></p>
                                    </article>
                                </div>
                            </section>

                            <section id="exam-form-help" class="exam-create-help-card hidden" hidden>
                                <div class="exam-create-help-card__summary">
                                    <span class="exam-create-help-card__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('help-circle'); ?></span>
                                    <div class="exam-create-help-card__content">
                                        <strong class="exam-create-help-card__title">Guide rapide d'import et de structure</strong>
                                        <span class="exam-create-help-card__meta">Format CSV / Excel, variantes et bonnes pratiques de correction.</span>
                                    </div>
                                </div>
                                <div class="exam-create-help-card__body">
                                    <ol class="exam-create-help-card__body--list">
                                        <li>Utilisez les colonnes <code>question</code>, <code>variant</code>, <code>type</code>, puis <code>option_a</code> à <code>option_f</code>.</li>
                                        <li>Pour du choix unique, indiquez une seule bonne option dans la colonne <code>correct</code> avec A, B, C... ou 1, 2, 3.</li>
                                        <li>Pour une question texte, laissez les options vides et posez simplement l'intitulé.</li>
                                        <li>Les variantes peuvent être A, B, C, 1, 2, 3 ou <code>all</code> pour dupliquer la même question partout.</li>
                                    </ol>
                                </div>
                            </section>

                            <section id="drive-upload-progress" class="exam-create-banner exam-create-banner--info hidden" hidden aria-live="polite">
                                <div class="exam-create-banner__copy">
                                    <span class="exam-create-banner__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('upload'); ?></span>
                                    <div>
                                        <p class="exam-create-banner__title" id="drive-upload-label">Téléversement du sujet en cours...</p>
                                        <div class="exam-create-upload__meta">
                                            <span>Progression</span>
                                            <strong id="drive-upload-percent">0%</strong>
                                        </div>
                                        <div class="exam-create-upload__track">
                                            <div id="drive-upload-bar" class="exam-create-upload__bar" style="width:0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <aside class="exam-create-side">
                            <section class="exam-create-summary-card">
                                <div class="exam-create-card__header">
                                    <div class="exam-create-card__heading">
                                        <span class="exam-create-card__kicker">Synthèse</span>
                                        <h2 class="exam-create-card__title">État du formulaire</h2>
                                        <p class="exam-create-card__subtitle">Les cartes se mettent à jour à mesure que vous avancez dans la configuration.</p>
                                    </div>
                                    <span class="exam-create-status-pill <?php echo $summaryProgressPct >= 100 ? 'exam-create-status-pill--success' : 'exam-create-status-pill--info'; ?>"><?php echo e((string) $summaryProgressPct); ?>%</span>
                                </div>

                                <div class="exam-create-summary-card__progress">
                                    <div class="dash-progress">
                                        <div class="dash-progress__fill" style="width: <?php echo e((string) $summaryProgressPct); ?>%;"></div>
                                    </div>
                                    <p class="exam-create-summary-card__progress-meta"><?php echo e((string) $summaryReadyCount); ?>/<?php echo e((string) count($summaryChecks)); ?> vérifications initiales valides.</p>
                                </div>

                                <div class="exam-create-summary-grid">
                                    <article class="exam-create-summary-item <?php echo $title !== '' ? 'exam-create-summary-item--done' : ''; ?>">
                                        <div class="exam-create-summary-item__top">
                                            <span class="exam-create-summary-item__label">Titre</span>
                                            <span class="exam-create-summary-item__count"><?php echo e($title !== '' ? 'OK' : '...'); ?></span>
                                        </div>
                                        <p class="exam-create-summary-item__meta"><?php echo e($title !== '' ? $title : 'Titre encore vide.'); ?></p>
                                    </article>

                                    <article class="exam-create-summary-item <?php echo $structureReady ? 'exam-create-summary-item--done' : ''; ?>">
                                        <div class="exam-create-summary-item__top">
                                            <span class="exam-create-summary-item__label">Structure</span>
                                            <span class="exam-create-summary-item__count" id="summary-structure-count"><?php echo e($summaryStructureCount); ?></span>
                                        </div>
                                        <p class="exam-create-summary-item__meta" id="summary-structure-meta"><?php echo e($summaryStructureMeta); ?></p>
                                    </article>

                                    <article class="exam-create-summary-item exam-create-summary-item--done">
                                        <div class="exam-create-summary-item__top">
                                            <span class="exam-create-summary-item__label">Tentatives</span>
                                            <span class="exam-create-summary-item__count"><?php echo e((string) $maxAttempts); ?></span>
                                        </div>
                                        <p class="exam-create-summary-item__meta" id="summary-attempts-stat">Tentatives : <?php echo e((string) $maxAttempts); ?></p>
                                    </article>

                                    <article class="exam-create-summary-item <?php echo $antiCheatCount > 0 ? 'exam-create-summary-item--done' : ''; ?>">
                                        <div class="exam-create-summary-item__top">
                                            <span class="exam-create-summary-item__label">Consignes</span>
                                            <span class="exam-create-summary-item__count"><?php echo e((string) $antiCheatCount); ?></span>
                                        </div>
                                        <p class="exam-create-summary-item__meta"><?php echo e($antiCheatCount > 0 ? 'Consignes de passage visibles côté apprenant.' : 'Aucune consigne de passage supplémentaire.'); ?></p>
                                    </article>
                                </div>

                                <?php if ($summaryNotes !== []): ?>
                                    <div class="exam-create-summary-note">
                                        <p class="exam-create-summary-note__title">Points à finaliser</p>
                                        <ul class="exam-create-summary-note__list">
                                            <?php foreach ($summaryNotes as $summaryNote): ?>
                                                <li><?php echo e($summaryNote); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <ul id="exam-compliance-list" class="exam-create-compliance-list"></ul>

                                <div class="exam-create-summary-footer">
                                    <span class="exam-create-mini-stat" id="exam-blocker-meta"><?php echo e($summaryNotes === [] ? 'Tout est bon' : count($summaryNotes) . ' à corriger'); ?></span>
                                    <span class="exam-create-mini-stat"><?php echo e($durationLabel); ?></span>
                                    <span class="exam-create-mini-stat"><?php echo e($typeShortLabel); ?></span>
                                </div>
                            </section>

                            <section class="exam-create-card">
                                <div class="exam-create-card__header">
                                    <div class="exam-create-card__heading">
                                        <span class="exam-create-card__kicker">Aperçu apprenant</span>
                                        <h2 class="exam-create-card__title">Lecture côté étudiant</h2>
                                        <p class="exam-create-card__subtitle">Le résumé ci-dessous suit en direct vos champs principaux.</p>
                                    </div>
                                </div>

                                <div class="exam-create-preview-card__cover relative overflow-hidden">
                                    <?php if ($courseCoverUrl !== ''): ?>
                                        <img src="<?php echo e($courseCoverUrl); ?>" alt="Couverture" class="absolute inset-0 h-full w-full object-cover opacity-60">
                                    <?php endif; ?>
                                    <div class="relative z-10 flex h-full min-h-[8rem] items-end p-4">
                                        <span class="exam-create-status-pill exam-create-status-pill--info"><?php echo e($courseTitle); ?></span>
                                    </div>
                                </div>

                                <div class="exam-create-preview-card__body">
                                    <h3 id="preview-exam-title" class="exam-create-preview-card__title"><?php echo e($title !== '' ? $title : 'Titre de l\'examen'); ?></h3>
                                    <p id="preview-exam-meta" class="exam-create-preview-card__meta"><?php echo e($typeLabel); ?> | <?php echo e($durationLabel); ?> | Note minimale <?php echo e((string) $minScore); ?></p>
                                    <p id="preview-exam-description" class="exam-create-preview-card__description"><?php echo e($description !== '' ? $description : 'La description apparaîtra ici dès qu\'elle sera renseignée.'); ?></p>
                                    <div id="preview-exam-sample" class="exam-create-preview-card__sample">
                                        <?php if ($type === 'qcm'): ?>
                                            <?php if ($totalQuestions > 0): ?>
                                                Variante A : <?php echo e((string) $variantQuestionCounts[1]); ?> question(s), total <?php echo e((string) $totalQuestions); ?>.
                                            <?php else: ?>
                                                Ajoutez des questions pour voir un extrait ici.
                                            <?php endif; ?>
                                        <?php elseif ($type === 'drive'): ?>
                                            L'apprenant verra le sujet et remettra ensuite son document.
                                        <?php else: ?>
                                            L'apprenant demandera un rendez-vous selon le mode défini.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>

                            <section id="exam-autosave-box" class="exam-create-banner exam-create-banner--info" aria-live="polite">
                                <div class="exam-create-banner__copy">
                                    <span class="exam-create-banner__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                                    <div>
                                        <p class="exam-create-banner__title">Sauvegarde locale</p>
                                        <p id="exam-autosave-status">Initialisation du brouillon local...</p>
                                    </div>
                                </div>
                                <div class="exam-create-banner__actions">
                                    <button type="button" id="btn-restore-autosave" class="exam-create-inline-btn app-btn-success">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                                        Restaurer
                                    </button>
                                    <button type="button" id="btn-clear-autosave" class="exam-create-inline-btn app-btn-soft">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>
                                        Effacer
                                    </button>
                                </div>
                            </section>
                        </aside>
                    </div>

                    <section class="exam-create-actions-card exam-create-actions-card--sticky exam-create-actions-card--compact">
                        <div class="exam-create-actions-head exam-create-actions-head--inline">
                            <p class="exam-create-actions-label">Validation finale</p>
                            <p class="exam-create-actions-meta"><?php echo e($isLocked ? 'Enregistrement désactivé tant que le cours reste soumis.' : 'Le JSON est généré automatiquement avant l\'envoi.'); ?></p>
                        </div>
                        <div class="exam-create-action-grid">
                            <a href="<?php echo e($returnUrl); ?>" class="exam-create-action-btn exam-create-action-btn--secondary app-btn-soft">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                                Revenir au cours
                            </a>
                            <button type="submit" class="exam-create-action-btn exam-create-action-btn--primary app-btn-success exam-create-submit-btn" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                                <?php echo $isEdit ? 'Enregistrer les changements' : 'Créer l\'examen'; ?>
                            </button>
                        </div>
                    </section>
                    <div id="exam-help-modal" class="exam-create-help-modal hidden fixed inset-0 z-50 flex items-center justify-center p-4" hidden aria-hidden="true">
                        <button type="button" class="exam-create-help-modal__backdrop absolute inset-0" data-help-modal-close aria-label="Fermer la fenetre d'aide"></button>
                        <section class="exam-create-help-modal__panel relative w-full max-w-2xl rounded-2xl border" role="dialog" aria-modal="true" aria-labelledby="exam-help-modal-title">
                            <header class="exam-create-help-modal__head">
                                <div class="exam-create-help-modal__title-wrap">
                                    <span class="exam-create-help-modal__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('help-circle'); ?></span>
                                    <div>
                                        <h3 id="exam-help-modal-title" class="exam-create-help-modal__title">Guide rapide d'import et de structure</h3>
                                        <p id="exam-help-modal-subtitle" class="exam-create-help-modal__subtitle">Format CSV / Excel, variantes et bonnes pratiques de correction.</p>
                                    </div>
                                </div>
                                <button type="button" class="exam-create-inline-btn app-btn-soft" data-help-modal-close aria-label="Fermer l'aide">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('x'); ?></span>
                                    Fermer
                                </button>
                            </header>
                            <div class="exam-create-help-modal__body">
                                <ol id="exam-help-modal-list" class="exam-create-help-card__body--list">
                                    <li>Utilisez les colonnes <code>question</code>, <code>variant</code>, <code>type</code>, puis <code>option_a</code> à <code>option_f</code>.</li>
                                    <li>Pour du choix unique, indiquez une seule bonne option dans la colonne <code>correct</code> avec A, B, C... ou 1, 2, 3.</li>
                                    <li>Pour une question texte, laissez les options vides et posez simplement l'intitulé.</li>
                                    <li>Les variantes peuvent être A, B, C, 1, 2, 3 ou <code>all</code> pour dupliquer la même question partout.</li>
                                </ol>
                            </div>
                        </section>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>


