<?php
declare(strict_types=1);

$course = is_array($course ?? null) ? $course : [];
$courseId = max(0, (int) ($courseId ?? ($course['id'] ?? 0)));
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [
    'draft' => 'Brouillon',
    'submitted' => 'Soumis',
    'approved' => 'Approuve',
    'rejected' => 'Rejete',
];
$errors = array_values(is_array($errors ?? null) ? $errors : []);
$success = trim((string) ($success ?? ''));
$isLocked = !empty($isLocked);
$driveConnectRecommended = !empty($driveConnectRecommended);
$driveUploadNote = trim((string) ($driveUploadNote ?? ''));
$driveMaxMb = max(1, (int) ($driveMaxMb ?? 25));
$chapters = array_values(is_array($chapters ?? null) ? $chapters : []);
$lessonsByChapter = is_array($lessonsByChapter ?? null) ? $lessonsByChapter : [];
$docsByChapter = is_array($docsByChapter ?? null) ? $docsByChapter : [];
$docsByLesson = is_array($docsByLesson ?? null) ? $docsByLesson : [];
$meetings = array_values(is_array($meetings ?? null) ? $meetings : []);
$exams = array_values(is_array($exams ?? null) ? $exams : []);
$lessonsList = array_values(is_array($lessonsList ?? null) ? $lessonsList : []);
$contentStats = is_array($contentStats ?? null) ? $contentStats : [];
$submissionSummary = is_array($submissionSummary ?? null) ? $submissionSummary : [];
$categories = array_values(is_array($categories ?? null) ? $categories : []);
$submissionChecklist = array_values(is_array($submissionSummary['checklist'] ?? null) ? $submissionSummary['checklist'] : []);
$submissionMissingRequired = array_values(is_array($submissionSummary['missing_required'] ?? null) ? $submissionSummary['missing_required'] : []);
$summaryPercent = max(0, min(100, (int) ($submissionSummary['progress_pct'] ?? 0)));
$summaryRequiredOk = !empty($submissionSummary['required_ok']);
$auditTimeline = array_values(is_array($auditTimeline ?? null) ? $auditTimeline : []);
$docLabel = isset($docLabel) && is_callable($docLabel) ? $docLabel : static function (?string $type, ?string $mime): string {
    if ($type === 'exercise') {
        return 'Exercice';
    }
    if ($type === 'exam') {
        return 'Examen';
    }
    if ($type === 'resource') {
        if (is_string($mime) && (stripos($mime, 'doc:file') !== false || stripos($mime, 'url_file') !== false)) {
            return 'Fichier';
        }
        if (is_string($mime) && (stripos($mime, 'doc:document') !== false || stripos($mime, 'url_document') !== false)) {
            return "Document d'accompagnement";
        }
    }
    return 'Ressource';
};

$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$teacherNavActive = 'course_create';
$teacherTopTitle = 'Parcours de creation';
$teacherTopSubtitle = 'Espace enseignant';

$courseTitle = trim((string) ($course['title'] ?? 'Cours'));
$courseStatus = trim((string) ($course['status'] ?? 'draft'));
$courseStatusLabel = trim((string) ($statusLabels[$courseStatus] ?? ucfirst($courseStatus)));
$courseStatusTone = match ($courseStatus) {
    'approved' => 'emerald',
    'submitted' => 'amber',
    'rejected' => 'rose',
    default => 'slate',
};
$courseStatusBadgeClass = match ($courseStatusTone) {
    'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
    'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
    default => 'border-slate-200 bg-slate-100 text-slate-700',
};
$coverPath = trim((string) ($course['cover_path'] ?? ''));
$coverUrl = $coverPath !== '' ? (preg_match('~^https?://~i', $coverPath) ? $coverPath : site_url(ltrim($coverPath, '/'))) : '';
$courseSlug = trim((string) ($course['slug'] ?? ''));
$previewUrl = $courseSlug !== '' ? site_url('course?slug=' . rawurlencode($courseSlug)) : '';
$teacherPreviewUrl = site_url('teacher/course_preview?id=' . $courseId);
$courseEditUrl = site_url('teacher/course_edit?id=' . $courseId);
$promotionsUrl = site_url('teacher/promotions?course_id=' . $courseId);
$reviewsUrl = site_url('teacher/reviews?course_id=' . $courseId . '&detail=1');
$updatesUrl = site_url('teacher/course_updates');
$courseStepDefault = trim((string) ($courseStepDefault ?? 'audience'));
$validSteps = ['audience', 'config', 'production', 'curriculum', 'quizzes', 'subtitles', 'dubbing', 'landing', 'pricing', 'promotions', 'messages', 'submit'];
if (!in_array($courseStepDefault, $validSteps, true)) {
    $courseStepDefault = 'audience';
}
$updateNoticeActive = $courseStatus === 'approved' && !empty($course['update_in_progress']);
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

$decodeCourseList = static function (mixed $value): array {
    if (is_array($value)) {
        $items = $value;
    } else {
        $decoded = json_decode((string) $value, true);
        $items = is_array($decoded) ? $decoded : [];
    }

    $clean = [];
    foreach ($items as $item) {
        $item = trim((string) $item);
        if ($item !== '') {
            $clean[] = $item;
        }
    }

    return array_values(array_slice(array_unique($clean), 0, 6));
};

$objectives = $decodeCourseList($course['objectives'] ?? []);
$prerequisites = $decodeCourseList($course['prerequisites'] ?? []);
$equipment = $decodeCourseList($course['equipment'] ?? []);

$formatMinutes = static function (int $minutes): string {
    $minutes = max(0, $minutes);
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = intdiv($minutes, 60);
    $remain = $minutes % 60;
    return $remain > 0 ? $hours . ' h ' . str_pad((string) $remain, 2, '0', STR_PAD_LEFT) . ' min' : $hours . ' h';
};

$formatDate = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return 'Non defini';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }
    return date('d/m/Y H:i', $timestamp);
};

$formatDurationHours = static function (float $hours) use ($formatMinutes): string {
    $hours = max(0, $hours);
    if ($hours <= 0) {
        return 'Non calculee';
    }
    return $formatMinutes((int) round($hours * 60));
};

$meetingsByChapter = [];
foreach ($meetings as $meeting) {
    $chapterId = max(0, (int) ($meeting['chapter_id'] ?? 0));
    if ($chapterId < 1) {
        continue;
    }
    if (!isset($meetingsByChapter[$chapterId])) {
        $meetingsByChapter[$chapterId] = [];
    }
    $meetingsByChapter[$chapterId][] = $meeting;
}

$contentChapters = [];
foreach ($chapters as $chapter) {
    $title = strtolower(trim((string) ($chapter['title'] ?? '')));
    if (in_array($title, ['examens', 'examen'], true)) {
        continue;
    }
    $contentChapters[] = $chapter;
}
$hasChapters = $contentChapters !== [];

$stats = [
    'chapters' => max(0, (int) ($contentStats['chapters_cnt'] ?? count($contentChapters))),
    'lessons' => max(0, (int) ($contentStats['lessons_cnt'] ?? count($lessonsList))),
    'resources' => max(0, (int) ($contentStats['resources_cnt'] ?? 0)) + max(0, (int) ($contentStats['exercises_cnt'] ?? 0)),
    'meetings' => max(0, (int) ($contentStats['meetings_cnt'] ?? count($meetings))),
    'exam_docs' => max(0, (int) ($contentStats['exam_docs_cnt'] ?? 0)),
    'adv_exams' => max(0, (int) ($contentStats['adv_exams_cnt'] ?? count($exams))),
];
$totalExamCount = $stats['exam_docs'] + $stats['adv_exams'];
$levelMap = [
    'beginner' => 'Debutant',
    'intermediate' => 'Intermediaire',
    'advanced' => 'Avance',
];
$courseLevelLabel = $levelMap[(string) ($course['level'] ?? 'beginner')] ?? 'Debutant';
$coursePriceValue = (int) ($course['price_xof'] ?? 0);
$coursePriceLabel = $coursePriceValue > 0 ? number_format($coursePriceValue, 0, ',', ' ') . ' F CFA' : 'Gratuit';
$enterprisePriceValue = isset($course['enterprise_price_xof']) ? (int) $course['enterprise_price_xof'] : 0;
$enterpriseDiscountValue = isset($course['enterprise_discount_percent']) ? (float) $course['enterprise_discount_percent'] : 0.0;
$courseCategoryId = (int) ($course['category_id'] ?? 0);
$courseCoverAlt = trim((string) ($course['cover_alt'] ?? ''));
$courseSeoTitle = trim((string) ($course['seo_title'] ?? ''));
$courseSeoDescription = trim((string) ($course['seo_description'] ?? ''));
$enterprisePricingMode = 'none';
if ($enterprisePriceValue > 0) {
    $enterprisePricingMode = 'price';
} elseif ($enterpriseDiscountValue > 0) {
    $enterprisePricingMode = 'discount';
}
$enterpriseOfferLabel = 'Aucune offre entreprise definie';
if ($enterprisePriceValue > 0) {
    $enterpriseOfferLabel = number_format($enterprisePriceValue, 0, ',', ' ') . ' F CFA par place';
} elseif ($enterpriseDiscountValue > 0) {
    $enterpriseOfferLabel = rtrim(rtrim(number_format($enterpriseDiscountValue, 2, ',', ' '), '0'), ',') . ' % de reduction';
}

$stepDone = [
    'audience' => count($objectives) > 0,
    'config' => false,
    'production' => $stats['resources'] > 0 || $stats['meetings'] > 0,
    'curriculum' => $stats['chapters'] > 0 && $stats['lessons'] > 0,
    'quizzes' => $totalExamCount > 0,
    'subtitles' => false,
    'dubbing' => false,
    'landing' => $coverUrl !== '' && trim((string) ($course['description'] ?? '')) !== '',
    'pricing' => true,
    'promotions' => false,
    'messages' => true,
    'submit' => in_array($courseStatus, ['submitted', 'approved'], true),
];

$stepGroups = [
    [
        'title' => 'Planifiez votre cours',
        'steps' => [
            ['key' => 'audience', 'label' => 'Participants cibles', 'index' => 1],
            ['key' => 'config', 'label' => 'Configuration et video test', 'index' => 2],
        ],
    ],
    [
        'title' => 'Creez votre contenu',
        'steps' => [
            ['key' => 'production', 'label' => 'Filmer et monter', 'index' => 3],
            ['key' => 'curriculum', 'label' => 'Programme', 'index' => 4],
            ['key' => 'quizzes', 'label' => 'Quiz des modules', 'index' => 5],
            ['key' => 'subtitles', 'label' => 'Sous-titres (en option)', 'index' => 6],
            ['key' => 'dubbing', 'label' => 'Doublage audio (en option)', 'index' => 7],
        ],
    ],
    [
        'title' => 'Publiez votre cours',
        'steps' => [
            ['key' => 'landing', 'label' => 'Page d accueil du cours', 'index' => 8],
            ['key' => 'pricing', 'label' => 'Tarification', 'index' => 9, 'meta' => $coursePriceValue > 0 ? '' : 'Gratuit'],
            ['key' => 'promotions', 'label' => 'Promotions', 'index' => 10],
            ['key' => 'messages', 'label' => 'Messages du cours', 'index' => 11],
        ],
    ],
    [
        'title' => 'Soumettre votre cours',
        'steps' => [
            ['key' => 'submit', 'label' => 'Soumettre pour evaluation', 'index' => 12],
        ],
    ],
];
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/course_content" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="app-global-assets" content="off">
    <title>Parcours du cours · Enseignant · Ecole Numerique</title>
    <?php if ($siteLogoUrl !== ''): ?>
        <link rel="icon" href="<?php echo e($siteLogoUrl); ?>">
        <link rel="apple-touch-icon" href="<?php echo e($siteLogoUrl); ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-course-builder.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-course-builder.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_content.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_content.js') ?: time()))); ?>" defer></script>
</head>
<body class="app-view app-role-teacher min-h-screen bg-slate-50 text-slate-900 font-body" data-view="teacher/course_content">
    <div class="teacher-dashboard-layout relative min-h-screen overflow-x-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="w-full min-h-screen" data-scroll-top-root>
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="mx-auto flex w-full max-w-[104rem] flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                <?php $teacherWelcomeTitle = $courseTitle !== '' ? $courseTitle : 'Parcours du cours'; ?>
                <?php $teacherWelcomeSummary = 'Retrouvez les memes grandes etapes de creation, adaptees aux champs de votre projet.'; ?>
                <?php $teacherWelcomeActionUrl = $courseEditUrl; ?>
                <?php $teacherWelcomeActionLabel = 'Modifier la fiche'; ?>
                <?php $teacherWelcomeActionIcon = 'settings'; ?>
                <?php require __DIR__ . '/_welcome_hero.php'; ?>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></h1>
                                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold <?php echo e($courseStatusBadgeClass); ?>">
                                    <?php echo e($courseStatusLabel); ?>
                                </span>
                            </div>
                            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                Avancez etape par etape : participants cibles, configuration, contenu, quiz, page d'accueil, tarification et soumission.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <?php if ($previewUrl !== ''): ?>
                                <a href="<?php echo e($previewUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    <i class="fas fa-eye"></i>
                                    Voir la fiche
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo e($courseEditUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                <i class="fas fa-pen"></i>
                                Ouvrir l edition complete
                            </a>
                        </div>
                    </div>
                </section>

                <?php if ($updateNoticeActive): ?>
                    <section class="rounded-[1.75rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle mt-0.5"></i>
                                <div>
                                    <p class="font-semibold">Ce cours est signale en mise a jour.</p>
                                    <p class="mt-1">Quand la revision est terminee, vous pouvez retirer ce signal.</p>
                                </div>
                            </div>
                            <form method="post">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <button type="submit" name="action" value="clear_update_notice" class="rounded-2xl border border-amber-300 bg-white px-4 py-3 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                                    Marquer la mise a jour comme terminee
                                </button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($errors !== []): ?>
                    <section class="rounded-[1.75rem] border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                        <p class="font-semibold">L'action demandee a echoue.</p>
                        <div class="mt-2 space-y-1">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo e((string) $error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <section class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700">
                        <p class="font-semibold"><?php echo e($success); ?></p>
                    </section>
                <?php endif; ?>

                <?php if ($isLocked): ?>
                    <section class="rounded-[1.75rem] border border-slate-200 bg-slate-100 px-5 py-4 text-sm text-slate-700">
                        <p class="font-semibold">Le cours est verrouille.</p>
                        <p class="mt-2">Tant que le statut reste <strong><?php echo e($courseStatusLabel); ?></strong>, les modifications de contenu sont bloquees.</p>
                    </section>
                <?php endif; ?>

                <section data-course-content-root data-course-id="<?php echo e((string) $courseId); ?>" data-course-step-default="<?php echo e($courseStepDefault); ?>" class="grid gap-6 xl:grid-cols-[19rem_minmax(0,1fr)]">
                    <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">
                        <?php foreach ($stepGroups as $group): ?>
                            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500"><?php echo e((string) ($group['title'] ?? 'Etapes')); ?></p>
                                <div class="mt-4 space-y-2">
                                    <?php foreach ((array) ($group['steps'] ?? []) as $step): ?>
                                        <?php
                                        $stepKey = (string) ($step['key'] ?? '');
                                        $stepLabel = (string) ($step['label'] ?? '');
                                        $stepIndex = (int) ($step['index'] ?? 0);
                                        $stepMeta = trim((string) ($step['meta'] ?? ''));
                                        $stepIsDone = !empty($stepDone[$stepKey]);
                                        ?>
                                        <button
                                            type="button"
                                            data-course-step-button="<?php echo e($stepKey); ?>"
                                            data-course-step-button-light
                                            class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:border-blue-200 hover:bg-blue-50/60"
                                        >
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                                <?php if ($stepIsDone): ?>
                                                    <i class="fas fa-check"></i>
                                                <?php else: ?>
                                                    <?php echo e((string) $stepIndex); ?>
                                                <?php endif; ?>
                                            </span>
                                            <span class="min-w-0 text-sm font-medium text-slate-600">
                                                <span class="block truncate"><?php echo e($stepLabel); ?></span>
                                                <?php if ($stepMeta !== ''): ?>
                                                    <span class="mt-0.5 block text-xs font-medium text-slate-400"><?php echo e($stepMeta); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </aside>

                    <div class="space-y-6">
                        <div data-course-step-panel="audience" class="space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Etape 1</p>
                                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Participants cibles et prerequis</h2>
                                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                            Adaptez cette etape a vos champs actuels : resultats attendus, prerequis et materiel.
                                        </p>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Objectifs</p>
                                            <p class="mt-1 text-2xl font-bold text-slate-950"><?php echo e((string) count($objectives)); ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Prerequis</p>
                                            <p class="mt-1 text-2xl font-bold text-slate-950"><?php echo e((string) count($prerequisites)); ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Materiel</p>
                                            <p class="mt-1 text-2xl font-bold text-slate-950"><?php echo e((string) count($equipment)); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <form method="post" class="space-y-6">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="update_pedagogy">

                                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                    <div class="space-y-6">
                                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-lg font-bold text-slate-950">Resultats attendus <span class="text-red-500">*</span></p>
                                                    <p class="mt-2 text-sm leading-7 text-slate-600">Ce que l'apprenant saura faire a la fin du cours.</p>
                                                </div>
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500" data-list-count="objectives-list" data-list-max="6">0/6</span>
                                            </div>
                                            <div class="mt-4 flex gap-3">
                                                <input type="text" data-list-quick-input data-list-target="objectives-list" placeholder="Ex : Construire une interface React simple" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <button type="button" data-add-target="objectives-list" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                    Ajouter
                                                </button>
                                            </div>
                                            <div id="objectives-list" data-name="objectives[]" data-placeholder="Ex : Construire une interface React simple" data-initial="<?php echo e((string) json_encode($objectives, $jsonFlags)); ?>" class="course-list mt-4"></div>
                                        </div>

                                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-lg font-bold text-slate-950">Prerequis</p>
                                                    <p class="mt-2 text-sm leading-7 text-slate-600">Ce qu'il faut deja savoir avant de commencer.</p>
                                                </div>
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500" data-list-count="prerequisites-list" data-list-max="6">0/6</span>
                                            </div>
                                            <div class="mt-4 flex gap-3">
                                                <input type="text" data-list-quick-input data-list-target="prerequisites-list" placeholder="Ex : Connaissances HTML et CSS" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <button type="button" data-add-target="prerequisites-list" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                    Ajouter
                                                </button>
                                            </div>
                                            <div id="prerequisites-list" data-name="prerequisites[]" data-placeholder="Ex : Connaissances HTML et CSS" data-initial="<?php echo e((string) json_encode($prerequisites, $jsonFlags)); ?>" class="course-list mt-4"></div>
                                        </div>

                                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-lg font-bold text-slate-950">Materiel</p>
                                                    <p class="mt-2 text-sm leading-7 text-slate-600">Ce dont l'apprenant aura besoin pour suivre la formation.</p>
                                                </div>
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500" data-list-count="equipment-list" data-list-max="6">0/6</span>
                                            </div>
                                            <div class="mt-4 flex gap-3">
                                                <input type="text" data-list-quick-input data-list-target="equipment-list" placeholder="Ex : Ordinateur et connexion internet" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-blue-500" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <button type="button" data-add-target="equipment-list" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                    Ajouter
                                                </button>
                                            </div>
                                            <div id="equipment-list" data-name="equipment[]" data-placeholder="Ex : Ordinateur et connexion internet" data-initial="<?php echo e((string) json_encode($equipment, $jsonFlags)); ?>" class="course-list mt-4"></div>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <a href="<?php echo e($courseEditUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                            <i class="fas fa-arrow-left"></i>
                                            Revenir a la fiche
                                        </a>
                                        <div class="flex flex-col gap-3 sm:flex-row">
                                            <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 disabled:opacity-60">
                                                <i class="fas fa-save"></i>
                                                Enregistrer
                                            </button>
                                            <button type="button" data-course-step-next="config" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                                Continuer
                                                <i class="fas fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </section>
                            </form>
                        </div>

                        <div data-course-step-panel="config" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Etape 2</p>
                                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Configuration et video test</h2>
                                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                    Cette etape reprend l'esprit de Lambdy : verifiez votre installation, votre son et votre methode avant de produire.
                                </p>
                            </section>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <?php
                                $configCards = [
                                    ['icon' => 'fa-camera', 'tone' => 'bg-blue-50 text-blue-600', 'title' => 'Materiel simple et accessible', 'body' => "Un smartphone, une webcam ou un ordinateur suffisent. L'essentiel est de garantir une image stable et lisible."],
                                    ['icon' => 'fa-microphone', 'tone' => 'bg-rose-50 text-rose-600', 'title' => 'Audio de qualite prioritaire', 'body' => "Le son reste l'element le plus critique. Testez le micro et gardez une distance stable pendant l'enregistrement."],
                                    ['icon' => 'fa-lightbulb', 'tone' => 'bg-amber-50 text-amber-600', 'title' => 'Creez votre studio', 'body' => "Travaillez dans un espace calme, avec le meme eclairage et le meme cadrage pour garder un rendu propre."],
                                    ['icon' => 'fa-video', 'tone' => 'bg-emerald-50 text-emerald-600', 'title' => 'Humanisez votre formation', 'body' => "Ajoutez votre voix, votre visage ou une introduction contextuelle pour renforcer la confiance des apprenants."],
                                    ['icon' => 'fa-bolt', 'tone' => 'bg-fuchsia-50 text-fuchsia-600', 'title' => 'La pratique rend parfait', 'body' => "Faites quelques prises test avant de produire toute la serie. Corriger tres tot coute moins cher."],
                                    ['icon' => 'fa-display', 'tone' => 'bg-indigo-50 text-indigo-600', 'title' => "Captures d'ecran optimisees", 'body' => "Avant un screencast, agrandissez le texte, nettoyez le bureau et ouvrez seulement les fenetres utiles."],
                                ];
                                foreach ($configCards as $card):
                                ?>
                                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                                        <div class="flex items-start gap-4">
                                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl text-xl <?php echo e((string) $card['tone']); ?>">
                                                <i class="fas <?php echo e((string) $card['icon']); ?>"></i>
                                            </span>
                                            <div>
                                                <h3 class="text-xl font-bold text-slate-950"><?php echo e((string) $card['title']); ?></h3>
                                                <p class="mt-3 text-sm leading-7 text-slate-600"><?php echo e((string) $card['body']); ?></p>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <div class="flex justify-between gap-3">
                                <button type="button" data-course-step-prev="audience" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                                <button type="button" data-course-step-next="production" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Continuer
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="production" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Etape 3</p>
                                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Filmer et monter</h2>
                                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                            Ajoutez les documents utiles, les fichiers a telecharger et l'accompagnement que vous prevoyez pour les apprenants.
                                        </p>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Ressources</p>
                                            <p class="mt-1 text-2xl font-bold text-slate-950"><?php echo e((string) $stats['resources']); ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Rendez-vous</p>
                                            <p class="mt-1 text-2xl font-bold text-slate-950"><?php echo e((string) $stats['meetings']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="mb-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Ressources pedagogiques</p>
                                    <h3 class="mt-1 text-2xl font-bold text-slate-950">Documents et fichiers du cours</h3>
                                </div>

                                <form id="form-add-doc" method="post" enctype="multipart/form-data" class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="add_doc">
                                    <div class="grid gap-3 md:grid-cols-3">
                                        <div>
                                            <label for="doc_type" class="mb-2 block text-sm font-medium text-slate-700">Type *</label>
                                            <select id="doc_type" name="doc_type" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                                <option value="document">Document d'accompagnement</option>
                                                <option value="file">Fichier</option>
                                                <option value="exercise">Exercice</option>
                                            </select>
                                        </div>
                                        <div id="doc_scope_wrapper">
                                            <label for="doc_scope" class="mb-2 block text-sm font-medium text-slate-700">Associer a *</label>
                                            <select id="doc_scope" name="doc_scope" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                                <option value="chapter">Chapitre</option>
                                                <option value="lesson">Lecon</option>
                                            </select>
                                        </div>
                                        <div id="doc_chapter_wrapper">
                                            <label for="doc_chapter_id" class="mb-2 block text-sm font-medium text-slate-700">Chapitre *</label>
                                            <select id="doc_chapter_id" name="doc_chapter_id" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                                <option value="">Choisir</option>
                                                <?php foreach ($contentChapters as $chapter): ?>
                                                    <option value="<?php echo e((string) ($chapter['id'] ?? '')); ?>"><?php echo e((string) ($chapter['position'] ?? '')); ?>. <?php echo e((string) ($chapter['title'] ?? '')); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="doc_lesson_wrapper" class="mt-3 hidden">
                                        <label for="doc_lesson_id" class="mb-2 block text-sm font-medium text-slate-700">Lecon *</label>
                                        <select id="doc_lesson_id" name="doc_lesson_id" <?php echo ($lessonsList === [] || $isLocked) ? 'disabled' : ''; ?> class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                            <option value="">Choisir</option>
                                            <?php foreach ($lessonsList as $lesson): ?>
                                                <option value="<?php echo e((string) ($lesson['id'] ?? '')); ?>" data-chapter-id="<?php echo e((string) ($lesson['chapter_id'] ?? '')); ?>">
                                                    <?php echo e((string) ($lesson['chap_pos'] ?? '')); ?>. <?php echo e((string) ($lesson['chapter_title'] ?? '')); ?> - <?php echo e((string) ($lesson['position'] ?? '')); ?>. <?php echo e((string) ($lesson['title'] ?? '')); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mt-3">
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Titre *</label>
                                        <input name="doc_title" <?php echo $isLocked ? 'disabled' : ''; ?> required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                    </div>
                                    <div class="mt-3">
                                        <label for="doc_file" class="mb-2 block text-sm font-medium text-slate-700">Fichier *</label>
                                        <input id="doc_file" name="doc_file" type="file" <?php echo $isLocked ? 'disabled' : ''; ?> required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 focus:border-blue-500 disabled:opacity-60">
                                        <p class="mt-2 text-xs text-slate-500">
                                            Maximum <?php echo e((string) $driveMaxMb); ?> Mo. <?php echo e($driveUploadNote !== '' ? $driveUploadNote : "Google Drive n'est pas configure."); ?>
                                            <?php if ($driveConnectRecommended): ?>
                                                <a href="<?php echo e(site_url('drive/oauth_connect')); ?>" class="font-semibold text-blue-600 underline underline-offset-4">Connecter Drive</a>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div id="doc-upload-progress" class="mt-4 hidden rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <p id="doc-upload-progress-label" class="text-sm font-semibold text-slate-900">Preparation de l'envoi...</p>
                                            <span id="doc-upload-progress-text" class="text-sm font-semibold text-slate-500">0%</span>
                                        </div>
                                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                                            <div id="doc-upload-progress-bar" class="h-full w-0 rounded-full bg-emerald-500 transition-all duration-150"></div>
                                        </div>
                                        <p id="doc-upload-progress-hint" class="mt-3 text-xs text-slate-500">Le document peut prendre quelques instants a etre valide apres l'envoi.</p>
                                    </div>
                                    <button type="submit" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">
                                        <i class="fas fa-upload"></i>
                                        Ajouter la ressource
                                    </button>
                                </form>

                                <div class="mt-6 space-y-4">
                                    <?php if ($docsByChapter === [] && $docsByLesson === []): ?>
                                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">Aucune ressource ajoutee pour le moment.</div>
                                    <?php endif; ?>
                                    <?php foreach ($contentChapters as $chapter): ?>
                                        <?php
                                        $chapterId = max(0, (int) ($chapter['id'] ?? 0));
                                        $chapterDocs = array_values(is_array($docsByChapter[$chapterId] ?? null) ? $docsByChapter[$chapterId] : []);
                                        $chapterLessons = array_values(is_array($lessonsByChapter[$chapterId] ?? null) ? $lessonsByChapter[$chapterId] : []);
                                        ?>
                                        <?php if ($chapterDocs === [] && $chapterLessons === []): ?>
                                            <?php continue; ?>
                                        <?php endif; ?>
                                        <section class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                            <div class="mb-4">
                                                <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Chapitre <?php echo e((string) ($chapter['position'] ?? '')); ?></p>
                                                <h4 class="text-lg font-bold text-slate-950"><?php echo e((string) ($chapter['title'] ?? '')); ?></h4>
                                            </div>
                                            <div class="space-y-3">
                                                <?php foreach ($chapterDocs as $doc): ?>
                                                    <?php $docId = max(0, (int) ($doc['id'] ?? 0)); ?>
                                                    <article class="rounded-2xl border border-slate-200 bg-white p-4">
                                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                            <div>
                                                                <p class="text-xs uppercase tracking-[0.16em] text-slate-500"><?php echo e($docLabel((string) ($doc['type'] ?? ''), (string) ($doc['mime'] ?? ''))); ?></p>
                                                                <h5 class="mt-1 font-semibold text-slate-950"><?php echo e((string) ($doc['title'] ?? 'Ressource')); ?></h5>
                                                                <?php if (trim((string) ($doc['file_path'] ?? '')) !== ''): ?>
                                                                    <a href="<?php echo e((string) $doc['file_path']); ?>" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700">
                                                                        <i class="fas fa-external-link-alt text-xs"></i>
                                                                        Ouvrir
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                            <form method="post">
                                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                <input type="hidden" name="action" value="delete_doc">
                                                                <input type="hidden" name="delete_doc_id" value="<?php echo e((string) $docId); ?>">
                                                                <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-60">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </article>
                                                <?php endforeach; ?>

                                                <?php foreach ($chapterLessons as $lesson): ?>
                                                    <?php $lessonId = max(0, (int) ($lesson['id'] ?? 0)); ?>
                                                    <?php $lessonDocs = array_values(is_array($docsByLesson[$lessonId] ?? null) ? $docsByLesson[$lessonId] : []); ?>
                                                    <?php if ($lessonDocs === []): ?>
                                                        <?php continue; ?>
                                                    <?php endif; ?>
                                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                                        <p class="mb-3 text-sm font-semibold text-slate-950">Lecon <?php echo e((string) ($lesson['position'] ?? '')); ?>. <?php echo e((string) ($lesson['title'] ?? '')); ?></p>
                                                        <div class="space-y-3">
                                                            <?php foreach ($lessonDocs as $doc): ?>
                                                                <?php $docId = max(0, (int) ($doc['id'] ?? 0)); ?>
                                                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                                        <div>
                                                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500"><?php echo e($docLabel((string) ($doc['type'] ?? ''), (string) ($doc['mime'] ?? ''))); ?></p>
                                                                            <h5 class="mt-1 font-semibold text-slate-950"><?php echo e((string) ($doc['title'] ?? 'Ressource')); ?></h5>
                                                                            <?php if (trim((string) ($doc['file_path'] ?? '')) !== ''): ?>
                                                                                <a href="<?php echo e((string) $doc['file_path']); ?>" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700">
                                                                                    <i class="fas fa-external-link-alt text-xs"></i>
                                                                                    Ouvrir
                                                                                </a>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <form method="post">
                                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                            <input type="hidden" name="action" value="delete_doc">
                                                                            <input type="hidden" name="delete_doc_id" value="<?php echo e((string) $docId); ?>">
                                                                            <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-60">Supprimer</button>
                                                                        </form>
                                                                    </div>
                                                                </article>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="mb-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Accompagnement</p>
                                    <h3 class="mt-1 text-2xl font-bold text-slate-950">Sessions et rendez-vous</h3>
                                </div>

                                <form id="form-add-meeting" method="post" class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="add_meeting">
                                    <div class="grid gap-3 md:grid-cols-3">
                                        <div class="md:col-span-2">
                                            <label class="mb-2 block text-sm font-medium text-slate-700">Titre *</label>
                                            <input name="meeting_title" <?php echo $isLocked ? 'disabled' : ''; ?> required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700">Mode *</label>
                                            <select name="meeting_mode" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                                <option value="online">En ligne</option>
                                                <option value="onsite">Presentiel</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700">Chapitre *</label>
                                            <select id="meeting_chapter_id" name="meeting_chapter_id" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                                <option value="">Choisir</option>
                                                <?php foreach ($contentChapters as $chapter): ?>
                                                    <option value="<?php echo e((string) ($chapter['id'] ?? '')); ?>"><?php echo e((string) ($chapter['position'] ?? '')); ?>. <?php echo e((string) ($chapter['title'] ?? '')); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p id="meeting_chapter_label" class="mt-2 text-xs text-slate-500">Selectionnez un chapitre depuis le programme.</p>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700">Duree (minutes)</label>
                                            <input name="meeting_duration_min" type="number" min="0" value="60" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                        </div>
                                    </div>
                                    <button type="submit" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">
                                        <i class="fas fa-calendar-plus"></i>
                                        Ajouter le rendez-vous
                                    </button>
                                </form>

                                <div class="mt-6 space-y-4">
                                    <?php if ($meetings === []): ?>
                                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">Aucun rendez-vous cree pour l'instant.</div>
                                    <?php endif; ?>
                                    <?php foreach ($meetings as $meeting): ?>
                                        <?php $meetingId = max(0, (int) ($meeting['id'] ?? 0)); ?>
                                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500"><?php echo e(((string) ($meeting['mode'] ?? 'online')) === 'onsite' ? 'Presentiel' : 'En ligne'); ?></p>
                                                    <h4 class="mt-1 text-lg font-bold text-slate-950"><?php echo e((string) ($meeting['title'] ?? 'Rendez-vous')); ?></h4>
                                                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
                                                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1"><?php echo e((string) ($meeting['chapter_title'] ?? 'Chapitre')); ?></span>
                                                        <?php if ((int) ($meeting['duration_min'] ?? 0) > 0): ?>
                                                            <span class="rounded-full border border-slate-200 bg-white px-3 py-1"><?php echo e($formatMinutes((int) ($meeting['duration_min'] ?? 0))); ?></span>
                                                        <?php endif; ?>
                                                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1"><?php echo e((string) ($meeting['status'] ?? 'pending')); ?></span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <form method="post">
                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                        <input type="hidden" name="action" value="duplicate_meeting">
                                                        <input type="hidden" name="duplicate_meeting_id" value="<?php echo e((string) $meetingId); ?>">
                                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 disabled:opacity-60">Dupliquer</button>
                                                    </form>
                                                    <form method="post">
                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                        <input type="hidden" name="action" value="delete_meeting">
                                                        <input type="hidden" name="delete_meeting_id" value="<?php echo e((string) $meetingId); ?>">
                                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-60">Supprimer</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <div class="flex justify-between gap-3">
                                <button type="button" data-course-step-prev="config" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                                <button type="button" data-course-step-next="curriculum" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Programme
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="curriculum" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Etape 4</p>
                                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Programme</h2>
                                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                            Structurez votre cours en modules et lecons. Chaque module represente une grande partie du parcours.
                                        </p>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Modules</p>
                                            <p class="mt-1 text-2xl font-bold text-slate-950"><?php echo e((string) $stats['chapters']); ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Lecons</p>
                                            <p class="mt-1 text-2xl font-bold text-slate-950"><?php echo e((string) $stats['lessons']); ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Duree</p>
                                            <p class="mt-1 text-2xl font-bold text-slate-950"><?php echo e($formatDurationHours((float) ($course['duration_hours'] ?? 0))); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <div class="grid gap-6 xl:grid-cols-2">
                                <form id="form-add-chapter" method="post" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="add_chapter">
                                    <div class="mb-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Modules</p>
                                        <h3 class="mt-1 text-xl font-bold text-slate-950">Ajouter un module</h3>
                                    </div>
                                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                                        <div>
                                            <label for="chapter_title" class="mb-2 block text-sm font-medium text-slate-700">Titre *</label>
                                            <input id="chapter_title" name="chapter_title" <?php echo $isLocked ? 'disabled' : ''; ?> required placeholder="Module 1 : Fondamentaux" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                        </div>
                                        <div>
                                            <label for="chapter_description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                                            <input id="chapter_description" name="chapter_description" <?php echo $isLocked ? 'disabled' : ''; ?> placeholder="Objectif du module" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                        </div>
                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">
                                            <i class="fas fa-plus-circle"></i>
                                            Ajouter
                                        </button>
                                    </div>
                                </form>

                                <form id="form-add-lesson" method="post" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="add_lesson">
                                    <div class="mb-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Lecons</p>
                                        <h3 class="mt-1 text-xl font-bold text-slate-950">Ajouter une lecon</h3>
                                    </div>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700">Chapitre *</label>
                                            <select name="lesson_chapter_id" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                                <option value="">Choisir</option>
                                                <?php foreach ($contentChapters as $chapter): ?>
                                                    <option value="<?php echo e((string) ($chapter['id'] ?? '')); ?>"><?php echo e((string) ($chapter['position'] ?? '')); ?>. <?php echo e((string) ($chapter['title'] ?? '')); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700">Position (optionnel)</label>
                                            <input name="lesson_position" type="number" min="1" <?php echo $isLocked ? 'disabled' : ''; ?> placeholder="Auto" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                        </div>
                                    </div>
                                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700">Titre *</label>
                                            <input name="lesson_title" <?php echo $isLocked ? 'disabled' : ''; ?> required placeholder="Introduction" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-medium text-slate-700">Duree (minutes) *</label>
                                            <input name="lesson_duration" type="number" min="1" <?php echo $isLocked ? 'disabled' : ''; ?> required placeholder="15" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                                        <textarea name="lesson_description" rows="3" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60"></textarea>
                                    </div>
                                    <div class="mt-3">
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Lien YouTube *</label>
                                        <input name="youtube_url" <?php echo $isLocked ? 'disabled' : ''; ?> required placeholder="https://www.youtube.com/watch?v=..." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500 disabled:opacity-60">
                                    </div>
                                    <button type="submit" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">
                                        <i class="fas fa-play-circle"></i>
                                        Ajouter la lecon
                                    </button>
                                </form>
                            </div>

                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="mb-6 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Structure actuelle</p>
                                        <h3 class="mt-1 text-2xl font-bold text-slate-950">Modules et lecons</h3>
                                    </div>
                                    <button type="button" data-course-step-next="quizzes" class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                        Quiz des modules
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>

                                <?php if ($contentChapters === []): ?>
                                    <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">Aucun module pour le moment. Commencez par creer votre premier module.</div>
                                <?php endif; ?>

                                <div class="space-y-5">
                                    <?php foreach ($contentChapters as $chapter): ?>
                                        <?php
                                        $chapterId = max(0, (int) ($chapter['id'] ?? 0));
                                        $chapterLessons = array_values(is_array($lessonsByChapter[$chapterId] ?? null) ? $lessonsByChapter[$chapterId] : []);
                                        $chapterDocs = array_values(is_array($docsByChapter[$chapterId] ?? null) ? $docsByChapter[$chapterId] : []);
                                        $chapterMeetings = array_values(is_array($meetingsByChapter[$chapterId] ?? null) ? $meetingsByChapter[$chapterId] : []);
                                        ?>
                                        <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
                                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Module <?php echo e((string) ($chapter['position'] ?? '')); ?></p>
                                                    <h4 class="mt-1 text-xl font-bold text-slate-950"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></h4>
                                                    <?php if (trim((string) ($chapter['description'] ?? '')) !== ''): ?>
                                                        <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600"><?php echo e((string) $chapter['description']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <form method="post">
                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                        <input type="hidden" name="action" value="duplicate_chapter">
                                                        <input type="hidden" name="duplicate_chapter_id" value="<?php echo e((string) $chapterId); ?>">
                                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 disabled:opacity-60">Dupliquer</button>
                                                    </form>
                                                    <form method="post">
                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                        <input type="hidden" name="action" value="delete_chapter">
                                                        <input type="hidden" name="delete_chapter_id" value="<?php echo e((string) $chapterId); ?>">
                                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-60">Supprimer</button>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Lecons</p>
                                                    <p class="mt-1 font-bold text-slate-950"><?php echo e((string) count($chapterLessons)); ?></p>
                                                </div>
                                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Ressources</p>
                                                    <p class="mt-1 font-bold text-slate-950"><?php echo e((string) count($chapterDocs)); ?></p>
                                                </div>
                                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Rendez-vous</p>
                                                    <p class="mt-1 font-bold text-slate-950"><?php echo e((string) count($chapterMeetings)); ?></p>
                                                </div>
                                            </div>

                                            <div class="mt-5 space-y-3">
                                                <?php if ($chapterLessons === []): ?>
                                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-500">Aucune lecon pour ce module.</div>
                                                <?php endif; ?>
                                                <?php foreach ($chapterLessons as $lesson): ?>
                                                    <?php $lessonId = max(0, (int) ($lesson['id'] ?? 0)); ?>
                                                    <article class="rounded-[1.35rem] border border-slate-200 bg-white p-4">
                                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-center gap-2 text-sm font-semibold text-slate-950">
                                                                    <i class="fas fa-play-circle text-blue-600"></i>
                                                                    <span>Lecon <?php echo e((string) ($lesson['position'] ?? '')); ?></span>
                                                                </div>
                                                                <h5 class="mt-2 text-lg font-bold text-slate-950"><?php echo e((string) ($lesson['title'] ?? '')); ?></h5>
                                                                <?php if (trim((string) ($lesson['description'] ?? '')) !== ''): ?>
                                                                    <p class="mt-2 text-sm leading-7 text-slate-600"><?php echo e((string) $lesson['description']); ?></p>
                                                                <?php endif; ?>
                                                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
                                                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1"><?php echo e($formatMinutes((int) ($lesson['duration_estimate_min'] ?? 0))); ?></span>
                                                                    <?php if (trim((string) ($lesson['youtube_url'] ?? '')) !== ''): ?>
                                                                        <a href="<?php echo e((string) ($lesson['youtube_url'] ?? '')); ?>" target="_blank" rel="noopener" class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 font-semibold text-blue-700 transition hover:bg-blue-100">
                                                                            Ouvrir la video
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="flex flex-wrap gap-2">
                                                                <form method="post">
                                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                    <input type="hidden" name="action" value="duplicate_lesson">
                                                                    <input type="hidden" name="duplicate_lesson_id" value="<?php echo e((string) $lessonId); ?>">
                                                                    <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white disabled:opacity-60">Dupliquer</button>
                                                                </form>
                                                                <form method="post">
                                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                    <input type="hidden" name="action" value="delete_lesson">
                                                                    <input type="hidden" name="delete_lesson_id" value="<?php echo e((string) $lessonId); ?>">
                                                                    <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-60">Supprimer</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </article>
                                                <?php endforeach; ?>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <div class="flex justify-between gap-3">
                                <button type="button" data-course-step-prev="production" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                                <button type="button" data-course-step-next="quizzes" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Quiz des modules
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="quizzes" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Etape 5</p>
                                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Quiz du cours</h2>
                                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                            Creez une evaluation finale ou un quiz par module selon la logique actuelle de votre projet.
                                        </p>
                                    </div>
                                    <a href="<?php echo e(site_url('teacher/exams_create?course_id=' . $courseId . '&return=content&back_step=quizzes')); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                        <i class="fas fa-file-alt"></i>
                                        Ouvrir la configuration
                                    </a>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Sessions avancees</p>
                                        <p class="mt-2 text-3xl font-bold text-slate-950"><?php echo e((string) count($exams)); ?></p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Documents d'examen</p>
                                        <p class="mt-2 text-3xl font-bold text-slate-950"><?php echo e((string) $stats['exam_docs']); ?></p>
                                    </div>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <?php if ($exams === []): ?>
                                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">Aucun quiz avance n'est encore configure.</div>
                                    <?php endif; ?>
                                    <?php foreach ($exams as $exam): ?>
                                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500"><?php echo e((string) ($exam['type'] ?? 'exam')); ?></p>
                                            <h3 class="mt-1 text-lg font-bold text-slate-950"><?php echo e((string) ($exam['title'] ?? 'Examen')); ?></h3>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-600">
                                                <?php if ((int) ($exam['duration_min'] ?? 0) > 0): ?>
                                                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1"><?php echo e($formatMinutes((int) ($exam['duration_min'] ?? 0))); ?></span>
                                                <?php endif; ?>
                                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1"><?php echo e($formatDate((string) ($exam['updated_at'] ?? ''))); ?></span>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <div class="flex justify-between gap-3">
                                <button type="button" data-course-step-prev="curriculum" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                                <button type="button" data-course-step-next="subtitles" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Continuer
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="subtitles" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Option</p>
                                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Sous-titres</h2>
                                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                    Votre projet ne stocke pas encore de piste de sous-titres dediee ici, mais vous pouvez deja preparer le terrain.
                                </p>
                                <div class="mt-6 grid gap-4 md:grid-cols-2">
                                    <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                        <h3 class="text-lg font-bold text-slate-950">Bonnes pratiques</h3>
                                        <ul class="mt-4 space-y-2 text-sm leading-7 text-slate-600">
                                            <li>Ecrivez des phrases courtes et prononcez distinctement.</li>
                                            <li>Gardez un vocabulaire stable tout au long du cours.</li>
                                            <li>Preparez un script ou un plan pour accelerer la transcription plus tard.</li>
                                        </ul>
                                    </article>
                                    <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                        <h3 class="text-lg font-bold text-slate-950">Conseil pratique</h3>
                                        <p class="mt-4 text-sm leading-7 text-slate-600">
                                            Si vous publiez sur YouTube, activez une premiere transcription auto, puis corrigez-la avant d'importer ou de reutiliser le texte ailleurs.
                                        </p>
                                    </article>
                                </div>
                            </section>

                            <div class="flex justify-between gap-3">
                                <button type="button" data-course-step-prev="quizzes" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                                <button type="button" data-course-step-next="dubbing" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Continuer
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="dubbing" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Option</p>
                                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Doublage audio</h2>
                                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                    Le doublage n'est pas encore un champ natif du parcours, mais vous pouvez deja preparer une version secondaire de votre narration.
                                </p>
                                <div class="mt-6 grid gap-4 md:grid-cols-2">
                                    <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                        <h3 class="text-lg font-bold text-slate-950">Avant de doubler</h3>
                                        <ul class="mt-4 space-y-2 text-sm leading-7 text-slate-600">
                                            <li>Conservez le meme rythme et les memes points d'appui visuels.</li>
                                            <li>Separer l'audio de la video vous donnera plus de flexibilite.</li>
                                            <li>Documentez les termes techniques qui ne doivent pas changer.</li>
                                        </ul>
                                    </article>
                                    <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                        <h3 class="text-lg font-bold text-slate-950">Adaptation projet</h3>
                                        <p class="mt-4 text-sm leading-7 text-slate-600">
                                            Si vous ajoutez plus tard une gestion multilingue, cette etape pourra recevoir ses propres pistes audio sans modifier le reste du parcours.
                                        </p>
                                    </article>
                                </div>
                            </section>

                            <div class="flex justify-between gap-3">
                                <button type="button" data-course-step-prev="subtitles" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                                <button type="button" data-course-step-next="landing" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Continuer
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="landing" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Etape 8</p>
                                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Page d'accueil du cours</h2>
                                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                            Vous pouvez maintenant mettre a jour la fiche publique du cours directement dans ce tunnel sans sortir du parcours.
                                        </p>
                                    </div>
                                    <a href="<?php echo e($courseEditUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                        <i class="fas fa-up-right-from-square"></i>
                                        Ouvrir l'editeur complet
                                    </a>
                                </div>
                            </section>

                            <form method="post" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1fr)]">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="update_course_landing">
                                <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                                    <?php if ($coverUrl !== ''): ?>
                                        <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($courseCoverAlt !== '' ? $courseCoverAlt : 'Miniature du cours'); ?>" class="h-64 w-full object-cover">
                                    <?php else: ?>
                                        <div class="flex h-64 items-center justify-center bg-slate-100 text-slate-400">
                                            <i class="fas fa-image text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="space-y-4 p-6">
                                        <div>
                                            <label for="course_title" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Titre</label>
                                            <input
                                                id="course_title"
                                                name="course_title"
                                                type="text"
                                                value="<?php echo e($courseTitle !== '' ? $courseTitle : ''); ?>"
                                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-lg font-bold text-slate-950 outline-none transition focus:border-blue-500"
                                                <?php echo $isLocked ? 'disabled' : ''; ?>
                                            >
                                        </div>
                                        <div>
                                            <label for="course_description" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Description</label>
                                            <textarea
                                                id="course_description"
                                                name="course_description"
                                                rows="6"
                                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm leading-7 text-slate-700 outline-none transition focus:border-blue-500"
                                                <?php echo $isLocked ? 'disabled' : ''; ?>
                                            ><?php echo e((string) ($course['description'] ?? '')); ?></textarea>
                                        </div>
                                        <div>
                                            <label for="course_cover_file" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Miniature</label>
                                            <input
                                                id="course_cover_file"
                                                name="course_cover_file"
                                                type="file"
                                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                class="mt-2 block w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:font-semibold file:text-white hover:border-blue-300"
                                                <?php echo $isLocked ? 'disabled' : ''; ?>
                                            >
                                            <p class="mt-2 text-xs text-slate-500">Format recommande 16:9, maximum 5 Mo.</p>
                                        </div>
                                        <div>
                                            <label for="course_cover_alt" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Texte alternatif</label>
                                            <input
                                                id="course_cover_alt"
                                                name="course_cover_alt"
                                                type="text"
                                                value="<?php echo e($courseCoverAlt); ?>"
                                                class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500"
                                                placeholder="Ex.: Apercu du tableau de bord React du cours"
                                                <?php echo $isLocked ? 'disabled' : ''; ?>
                                            >
                                            <p class="mt-2 text-xs text-slate-500">Utile pour l'accessibilite et les apercus partages.</p>
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                                    <h3 class="text-xl font-bold text-slate-950">Informations visibles pour l'apprenant</h3>
                                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="course_category_id" class="mb-2 block text-sm font-medium text-slate-700">Categorie</label>
                                            <select id="course_category_id" name="course_category_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <option value="">Selectionnez une categorie</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <?php $categoryId = (int) ($category['id'] ?? 0); ?>
                                                    <option value="<?php echo e((string) $categoryId); ?>" <?php echo $courseCategoryId === $categoryId ? 'selected' : ''; ?>>
                                                        <?php echo e((string) ($category['name'] ?? ('Categorie #' . $categoryId))); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="course_level" class="mb-2 block text-sm font-medium text-slate-700">Niveau</label>
                                            <select id="course_level" name="course_level" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <option value="beginner" <?php echo (string) ($course['level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Debutant</option>
                                                <option value="intermediate" <?php echo (string) ($course['level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediaire</option>
                                                <option value="advanced" <?php echo (string) ($course['level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Avance</option>
                                            </select>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Duree estimee</p>
                                            <p class="mt-2 text-lg font-bold text-slate-950"><?php echo e($formatDurationHours((float) ($course['duration_hours'] ?? 0))); ?></p>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Fiche publique</p>
                                            <p class="mt-2 text-lg font-bold text-slate-950"><?php echo e($previewUrl !== '' ? 'Disponible' : 'A ouvrir apres sauvegarde'); ?></p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="course_seo_title" class="mb-2 block text-sm font-medium text-slate-700">Titre SEO</label>
                                            <input
                                                id="course_seo_title"
                                                name="course_seo_title"
                                                type="text"
                                                value="<?php echo e($courseSeoTitle); ?>"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500"
                                                placeholder="Titre optimise pour les moteurs de recherche"
                                                <?php echo $isLocked ? 'disabled' : ''; ?>
                                            >
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="course_seo_description" class="mb-2 block text-sm font-medium text-slate-700">Meta description</label>
                                            <textarea
                                                id="course_seo_description"
                                                name="course_seo_description"
                                                rows="4"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm leading-7 text-slate-700 outline-none transition focus:border-blue-500"
                                                placeholder="Resume court du cours pour les apercus et le referencement"
                                                <?php echo $isLocked ? 'disabled' : ''; ?>
                                            ><?php echo e($courseSeoDescription); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="mt-5 flex flex-wrap gap-3">
                                        <a href="<?php echo e($teacherPreviewUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 transition hover:text-slate-950">
                                            <i class="fas fa-eye"></i>
                                            Previsualiser cote enseignant
                                        </a>
                                        <?php if ($previewUrl !== ''): ?>
                                            <a href="<?php echo e($previewUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700">
                                                <i class="fas fa-external-link-alt"></i>
                                                Ouvrir la fiche publique
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 disabled:opacity-60">
                                            <i class="fas fa-save"></i>
                                            Enregistrer la fiche
                                        </button>
                                        <button type="button" data-course-step-next="pricing" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                            Continuer
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </section>
                            </form>

                            <div class="flex justify-start gap-3">
                                <button type="button" data-course-step-prev="dubbing" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="pricing" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Etape 9</p>
                                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Tarification</h2>
                                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                            Ajustez le prix public et, si votre schema le permet deja, une offre entreprise dediee.
                                        </p>
                                    </div>
                                    <a href="<?php echo e($promotionsUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                        <i class="fas fa-bullhorn"></i>
                                        Gerer les promotions
                                    </a>
                                </div>
                            </section>

                            <form method="post" class="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1fr)]">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="update_course_pricing">

                                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                    <div class="grid gap-5">
                                        <div>
                                            <label for="price_xof" class="mb-2 block text-sm font-medium text-slate-700">Prix public (F CFA)</label>
                                            <input
                                                id="price_xof"
                                                name="price_xof"
                                                type="number"
                                                min="0"
                                                step="1"
                                                value="<?php echo e((string) $coursePriceValue); ?>"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500"
                                                <?php echo $isLocked ? 'disabled' : ''; ?>
                                            >
                                            <p class="mt-2 text-xs text-slate-500">Mettez `0` pour un cours gratuit.</p>
                                        </div>

                                        <div>
                                            <label for="enterprise_pricing_mode" class="mb-2 block text-sm font-medium text-slate-700">Offre entreprise</label>
                                            <select
                                                id="enterprise_pricing_mode"
                                                name="enterprise_pricing_mode"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500"
                                                <?php echo $isLocked ? 'disabled' : ''; ?>
                                            >
                                                <option value="none" <?php echo $enterprisePricingMode === 'none' ? 'selected' : ''; ?>>Aucune</option>
                                                <option value="price" <?php echo $enterprisePricingMode === 'price' ? 'selected' : ''; ?>>Prix entreprise fixe</option>
                                                <option value="discount" <?php echo $enterprisePricingMode === 'discount' ? 'selected' : ''; ?>>Reduction entreprise</option>
                                            </select>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="enterprise_price_xof" class="mb-2 block text-sm font-medium text-slate-700">Prix entreprise (F CFA)</label>
                                                <input
                                                    id="enterprise_price_xof"
                                                    name="enterprise_price_xof"
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    value="<?php echo $enterprisePriceValue > 0 ? e((string) $enterprisePriceValue) : ''; ?>"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500"
                                                    <?php echo $isLocked ? 'disabled' : ''; ?>
                                                >
                                            </div>
                                            <div>
                                                <label for="enterprise_discount_percent" class="mb-2 block text-sm font-medium text-slate-700">Reduction entreprise (%)</label>
                                                <input
                                                    id="enterprise_discount_percent"
                                                    name="enterprise_discount_percent"
                                                    type="number"
                                                    min="0.01"
                                                    max="100"
                                                    step="0.01"
                                                    value="<?php echo $enterpriseDiscountValue > 0 ? e(rtrim(rtrim(number_format($enterpriseDiscountValue, 2, '.', ''), '0'), '.')) : ''; ?>"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-500"
                                                    <?php echo $isLocked ? 'disabled' : ''; ?>
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="space-y-4">
                                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Prix public actuel</p>
                                        <p class="mt-3 text-2xl font-bold text-slate-950"><?php echo e($coursePriceLabel); ?></p>
                                        <p class="mt-2 text-sm leading-7 text-slate-600">Le tarif visible sur la fiche du cours.</p>
                                    </article>
                                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Offre entreprise actuelle</p>
                                        <p class="mt-3 text-2xl font-bold text-slate-950"><?php echo e($enterpriseOfferLabel); ?></p>
                                        <p class="mt-2 text-sm leading-7 text-slate-600">Prix dedie ou pourcentage de reduction selon votre choix.</p>
                                    </article>
                                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Etat actuel</p>
                                        <p class="mt-3 text-2xl font-bold text-slate-950"><?php echo e($coursePriceValue > 0 ? 'Payant' : 'Gratuit'); ?></p>
                                        <p class="mt-2 text-sm leading-7 text-slate-600">Vous pourrez ensuite combiner ce prix avec vos promotions.</p>
                                    </article>
                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 disabled:opacity-60">
                                            <i class="fas fa-save"></i>
                                            Enregistrer le prix
                                        </button>
                                        <button type="button" data-course-step-next="promotions" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                            Continuer
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </section>
                            </form>

                            <div class="flex justify-start gap-3">
                                <button type="button" data-course-step-prev="landing" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="promotions" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Etape 10</p>
                                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Promotions</h2>
                                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                    Les promotions restent gerees dans votre ecran dedie. Vous pouvez y revenir a tout moment apres avoir fixe le prix public.
                                </p>
                                <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6">
                                    <h3 class="text-xl font-bold text-slate-950">Ce que vous pouvez faire</h3>
                                    <ul class="mt-4 space-y-2 text-sm leading-7 text-slate-600">
                                        <li>Creer des codes promo sur un cours precis.</li>
                                        <li>Definir une periode de validite.</li>
                                        <li>Activer des campagnes marketing sans toucher au contenu.</li>
                                    </ul>
                                    <a href="<?php echo e($promotionsUrl); ?>" class="mt-5 inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                        <i class="fas fa-bullhorn"></i>
                                        Ouvrir les promotions
                                    </a>
                                </div>
                            </section>

                            <div class="flex justify-between gap-3">
                                <button type="button" data-course-step-prev="pricing" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                                <button type="button" data-course-step-next="messages" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Continuer
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="messages" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Etape 11</p>
                                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Suivi et communication</h2>
                                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                                    Cette etape sert de hub avant soumission. Vous pouvez controler le rendu du cours, surveiller les avis et preparer vos futures mises a jour sans sortir du parcours.
                                </p>
                                <div class="mt-6 grid gap-4 md:grid-cols-2">
                                    <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                        <h3 class="text-lg font-bold text-slate-950">Avant publication</h3>
                                        <p class="mt-3 text-sm leading-7 text-slate-600">Preparez un message de bienvenue, les consignes d'installation et les attentes pour le premier module.</p>
                                    </article>
                                    <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                        <h3 class="text-lg font-bold text-slate-950">Apres publication</h3>
                                        <p class="mt-3 text-sm leading-7 text-slate-600">Utilisez les avis et le journal des mises a jour pour piloter les ajustements du contenu apres validation.</p>
                                    </article>
                                </div>
                                <div class="mt-6 grid gap-4 xl:grid-cols-3">
                                    <a href="<?php echo e($teacherPreviewUrl); ?>" target="_blank" rel="noopener" class="group rounded-[1.5rem] border border-slate-200 bg-white p-5 transition hover:border-blue-300 hover:shadow-sm">
                                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                        <h3 class="mt-4 text-lg font-bold text-slate-950">Previsualisation enseignant</h3>
                                        <p class="mt-2 text-sm leading-7 text-slate-600">Verifiez le plan, les ressources, les rendez-vous et l'experience apprenant avant soumission.</p>
                                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition group-hover:text-blue-700">
                                            Ouvrir
                                            <i class="fas fa-arrow-right"></i>
                                        </span>
                                    </a>
                                    <a href="<?php echo e($reviewsUrl); ?>" class="group rounded-[1.5rem] border border-slate-200 bg-white p-5 transition hover:border-blue-300 hover:shadow-sm">
                                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                                            <i class="fas fa-star-half-alt"></i>
                                        </span>
                                        <h3 class="mt-4 text-lg font-bold text-slate-950">Avis et retours</h3>
                                        <p class="mt-2 text-sm leading-7 text-slate-600">Accedez directement a la vue detaillee des avis du cours pour preparer votre boucle d'amelioration.</p>
                                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition group-hover:text-blue-700">
                                            Consulter
                                            <i class="fas fa-arrow-right"></i>
                                        </span>
                                    </a>
                                    <a href="<?php echo e($updatesUrl); ?>" class="group rounded-[1.5rem] border border-slate-200 bg-white p-5 transition hover:border-blue-300 hover:shadow-sm">
                                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                            <i class="fas fa-bullhorn"></i>
                                        </span>
                                        <h3 class="mt-4 text-lg font-bold text-slate-950">Journal des mises a jour</h3>
                                        <p class="mt-2 text-sm leading-7 text-slate-600">Retrouvez l'historique des demandes, validations et reprises de cours depuis l'espace enseignant.</p>
                                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition group-hover:text-blue-700">
                                            Explorer
                                            <i class="fas fa-arrow-right"></i>
                                        </span>
                                    </a>
                                </div>
                            </section>

                            <div class="flex justify-between gap-3">
                                <button type="button" data-course-step-prev="promotions" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                                <button type="button" data-course-step-next="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Soumettre le cours
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <div data-course-step-panel="submit" class="hidden space-y-6">
                            <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                                <div class="mx-auto max-w-3xl text-center">
                                    <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50 text-3xl text-emerald-600">
                                        <i class="fas fa-paper-plane"></i>
                                    </span>
                                    <p class="mt-6 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Etape finale</p>
                                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950">Soumettre pour evaluation</h2>
                                    <p class="mt-3 text-sm leading-7 text-slate-600">Verifiez votre progression, sauvegardez le brouillon si necessaire, puis soumettez le cours pour validation.</p>
                                    <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                                        <form method="post">
                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                            <button type="submit" name="action" value="save_draft" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 disabled:opacity-60">
                                                <i class="fas fa-save"></i>
                                                Enregistrer le brouillon
                                            </button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                            <button type="submit" name="action" value="submit_course" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">
                                                <i class="fas fa-paper-plane"></i>
                                                Soumettre pour validation
                                            </button>
                                        </form>
                                    </div>
                                    <p class="mt-4 text-xs text-slate-500"><?php echo $isLocked ? 'Le cours est deja soumis. Il reste en lecture tant qu il n est pas rouvert.' : 'Le cours sera examine par l equipe avant publication.'; ?></p>
                                </div>
                            </section>

                            <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1fr)_minmax(0,0.9fr)]">
                                <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                                    <div class="border-b border-slate-200 px-5 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Apercu</p>
                                        <h3 class="mt-1 text-lg font-bold text-slate-950">Fiche apprenant</h3>
                                    </div>
                                    <?php if ($coverUrl !== ''): ?>
                                        <img src="<?php echo e($coverUrl); ?>" alt="Miniature du cours" class="h-56 w-full object-cover">
                                    <?php else: ?>
                                        <div class="flex h-56 items-center justify-center bg-slate-100 text-slate-400">
                                            <i class="fas fa-image text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="space-y-4 px-5 py-5">
                                        <div>
                                            <p class="text-xl font-bold text-slate-950"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></p>
                                            <p class="mt-2 text-sm leading-7 text-slate-600"><?php echo e(trim((string) ($course['description'] ?? '')) !== '' ? (string) $course['description'] : 'La description de ce cours apparaitra ici une fois completee.'); ?></p>
                                        </div>
                                        <div class="grid gap-3">
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Duree estimee</p>
                                                <p class="mt-1 font-bold text-slate-950"><?php echo e($formatDurationHours((float) ($course['duration_hours'] ?? 0))); ?></p>
                                            </div>
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                <p class="text-xs uppercase tracking-[0.16em] text-slate-500">Tarif</p>
                                                <p class="mt-1 font-bold text-slate-950"><?php echo e($coursePriceLabel); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Checklist</p>
                                            <h3 class="mt-1 text-lg font-bold text-slate-950">Pret pour validation</h3>
                                        </div>
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"><?php echo e((string) $summaryPercent); ?>%</span>
                                    </div>
                                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: <?php echo e((string) $summaryPercent); ?>%;"></div>
                                    </div>
                                    <?php if ($submissionMissingRequired !== []): ?>
                                        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                            <p class="font-semibold">Elements requis manquants</p>
                                            <div class="mt-2 space-y-1">
                                                <?php foreach ($submissionMissingRequired as $missing): ?>
                                                    <p><?php echo e((string) $missing); ?></p>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-4 space-y-3">
                                        <?php foreach ($submissionChecklist as $summaryItem): ?>
                                            <?php $done = !empty($summaryItem['done']); ?>
                                            <article class="rounded-2xl border px-4 py-4 <?php echo $done ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50'; ?>">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-950"><?php echo e((string) ($summaryItem['label'] ?? 'Verification')); ?></p>
                                                        <p class="mt-1 text-xs text-slate-600">Compteur : <?php echo e((string) ($summaryItem['count'] ?? 0)); ?></p>
                                                    </div>
                                                    <span class="rounded-full px-2.5 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.16em] <?php echo $done ? 'bg-white text-emerald-700' : 'bg-white text-slate-600'; ?>">
                                                        <?php echo $done ? 'OK' : 'A faire'; ?>
                                                    </span>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if ($summaryRequiredOk): ?>
                                        <p class="mt-4 text-sm font-semibold text-emerald-700">Les points obligatoires sont remplis.</p>
                                    <?php endif; ?>
                                </section>

                                <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Historique</p>
                                    <h3 class="mt-1 text-lg font-bold text-slate-950">Dernieres actions</h3>
                                    <div class="mt-4 space-y-3">
                                        <?php if ($auditTimeline === []): ?>
                                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">Aucune action recente a afficher.</div>
                                        <?php endif; ?>
                                        <?php foreach ($auditTimeline as $event): ?>
                                            <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                <p class="text-sm font-semibold text-slate-950"><?php echo e((string) ($event['label'] ?? 'Action')); ?></p>
                                                <p class="mt-1 text-xs text-slate-500"><?php echo e((string) ($event['who'] ?? 'Systeme')); ?> · <?php echo e($formatDate((string) ($event['created_at'] ?? ''))); ?></p>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            </div>

                            <div class="flex justify-start">
                                <button type="button" data-course-step-prev="messages" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                                    Precedent
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
