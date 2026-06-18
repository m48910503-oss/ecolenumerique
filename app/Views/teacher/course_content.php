<?php
declare(strict_types=1);

$course = is_array($course ?? null) ? $course : [];
$courseId = max(0, (int) ($courseId ?? ($course['id'] ?? 0)));
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [
    'draft' => 'Brouillon',
    'submitted' => 'Soumis',
    'approved' => 'Approuvé',
    'rejected' => 'Rejeté',
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
$submissionSummary = array_values(is_array($submissionSummary ?? null) ? $submissionSummary : []);
$auditTimeline = array_values(is_array($auditTimeline ?? null) ? $auditTimeline : []);
$docLabel = isset($docLabel) && is_callable($docLabel) ? $docLabel : static function (?string $type, ?string $mime): string {
    if ($type === 'exercise') {
        return 'Exercice';
    }
    if ($type === 'exam') {
        return 'Examen';
    }
    if ($type === 'resource') {
        if (is_string($mime) && str_contains($mime, 'doc:file')) {
            return 'Fichier';
        }
        if (is_string($mime) && str_contains($mime, 'doc:document')) {
            return "Document d'accompagnement";
        }
    }
    return 'Ressource';
};

$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$teacherNavActive = 'course_create';
$teacherTopTitle = 'Créer un nouveau cours';
$teacherTopSubtitle = 'Espace enseignant';

$courseTitle = trim((string) ($course['title'] ?? 'Cours'));
$courseStatus = trim((string) ($course['status'] ?? 'draft'));
$courseStatusLabel = trim((string) ($statusLabels[$courseStatus] ?? ucfirst($courseStatus)));
$courseStatusTone = match ($courseStatus) {
    'approved' => 'emerald',
    'submitted' => 'amber',
    'rejected' => 'red',
    default => 'sky',
};
$coverPath = trim((string) ($course['cover_path'] ?? ''));
$coverUrl = $coverPath !== '' ? (preg_match('~^https?://~i', $coverPath) ? $coverPath : site_url(ltrim($coverPath, '/'))) : '';
$courseSlug = trim((string) ($course['slug'] ?? ''));
$previewUrl = $courseSlug !== '' ? site_url('course?slug=' . rawurlencode($courseSlug)) : '';
$courseEditUrl = site_url('teacher/course_edit?id=' . $courseId);
$updateNoticeActive = $courseStatus === 'approved' && !empty($course['update_in_progress']);
$courseStepDefault = trim((string) ($courseStepDefault ?? 'content'));
$courseStepDefault = in_array($courseStepDefault, ['pedagogy', 'content', 'settings', 'publish'], true) ? $courseStepDefault : 'content';
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
        return 'Non défini';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }
    return date('d/m/Y H:i', $timestamp);
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
    if (trim((string) ($chapter['title'] ?? '')) === 'Examens') {
        continue;
    }
    $contentChapters[] = $chapter;
}

$firstChapterId = max(0, (int) (($contentChapters[0]['id'] ?? 0)));
$hasChapters = $contentChapters !== [];

$stats = [
    'chapters' => max(0, (int) ($contentStats['chapters_cnt'] ?? count($contentChapters))),
    'lessons' => max(0, (int) ($contentStats['lessons_cnt'] ?? count($lessonsList))),
    'resources' => max(0, (int) ($contentStats['resources_cnt'] ?? 0)) + max(0, (int) ($contentStats['exercises_cnt'] ?? 0)),
    'meetings' => max(0, (int) ($contentStats['meetings_cnt'] ?? count($meetings))),
    'exam_docs' => max(0, (int) ($contentStats['exam_docs_cnt'] ?? 0)),
    'adv_exams' => max(0, (int) ($contentStats['adv_exams_cnt'] ?? count($exams))),
];

$summaryDone = 0;
foreach ($submissionSummary as $summaryItem) {
    if (!empty($summaryItem['done'])) {
        $summaryDone++;
    }
}
$summaryTotal = max(1, count($submissionSummary));
$summaryPercent = (int) round(($summaryDone / $summaryTotal) * 100);
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/course_content" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="app-global-assets" content="off">
    <title>Contenu du cours · Enseignant · École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-course-builder.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-course-builder.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_content.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_content.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body" data-view="teacher/course_content">
    <div class="teacher-dashboard-layout relative min-h-screen overflow-x-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="w-full min-h-screen" data-scroll-top-root>
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="mx-auto flex w-full max-w-[100rem] flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                <?php $teacherWelcomeTitle = $courseTitle !== '' ? $courseTitle : 'Contenu du cours'; ?>
                <?php $teacherWelcomeSummary = 'Organisez la pédagogie, les modules, les ressources, les rendez-vous et les examens.'; ?>
                <?php $teacherWelcomeActionUrl = $courseEditUrl; ?>
                <?php $teacherWelcomeActionLabel = 'Informations'; ?>
                <?php $teacherWelcomeActionIcon = 'settings'; ?>
                <?php require __DIR__ . '/_welcome_hero.php'; ?>

                <section class="rounded-[1.75rem] border border-slate-700 bg-slate-800/85 p-4 shadow-[0_24px_60px_-30px_rgba(15,23,42,0.85)] backdrop-blur">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-4 md:gap-6">
                            <a href="<?php echo e($courseEditUrl); ?>" class="flex items-center gap-3 opacity-80 transition hover:opacity-100">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-700 text-sm font-bold text-slate-300">1</span>
                                <span class="text-sm font-medium text-slate-300">Informations</span>
                            </a>
                            <span class="hidden text-slate-500 sm:inline-flex"><i class="fas fa-chevron-right"></i></span>
                            <button type="button" data-course-step-button="pedagogy" class="flex items-center gap-3 opacity-75 transition hover:opacity-100">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-700 text-sm font-bold text-slate-300">2</span>
                                <span class="text-sm font-medium text-slate-400">Pédagogie</span>
                            </button>
                            <span class="hidden text-slate-500 sm:inline-flex"><i class="fas fa-chevron-right"></i></span>
                            <button type="button" data-course-step-button="content" class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-lg shadow-blue-900/30">3</span>
                                <span class="text-sm font-semibold text-blue-300">Contenu</span>
                            </button>
                            <span class="hidden text-slate-500 sm:inline-flex"><i class="fas fa-chevron-right"></i></span>
                            <button type="button" data-course-step-button="settings" class="flex items-center gap-3 opacity-75 transition hover:opacity-100">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-700 text-sm font-bold text-slate-300">4</span>
                                <span class="text-sm font-medium text-slate-400">Paramètres</span>
                            </button>
                            <span class="hidden text-slate-500 sm:inline-flex"><i class="fas fa-chevron-right"></i></span>
                            <button type="button" data-course-step-button="publish" class="flex items-center gap-3 opacity-75 transition hover:opacity-100">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-700 text-sm font-bold text-slate-300">5</span>
                                <span class="text-sm font-medium text-slate-400">Publication</span>
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?php echo $courseStatusTone === 'emerald' ? 'bg-emerald-500/15 text-emerald-200' : ($courseStatusTone === 'amber' ? 'bg-amber-500/15 text-amber-200' : ($courseStatusTone === 'red' ? 'bg-red-500/15 text-red-200' : 'bg-blue-500/15 text-blue-200')); ?>">
                                <?php echo e($courseStatusLabel); ?>
                            </span>
                            <?php if ($previewUrl !== ''): ?>
                                <a href="<?php echo e($previewUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-900/40 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-900">
                                    <i class="fas fa-eye"></i>
                                    Voir la fiche
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <?php if ($updateNoticeActive): ?>
                    <section class="rounded-[1.5rem] border border-amber-500/30 bg-amber-500/10 px-5 py-4 text-sm text-amber-100">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle mt-0.5"></i>
                                <div>
                                    <p class="font-semibold">Le cours est signalé en mise à jour.</p>
                                    <p class="mt-1">Lorsque la révision est terminée, retirez ce signal pour informer les apprenants.</p>
                                </div>
                            </div>
                            <form method="post">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <button type="submit" name="action" value="clear_update_notice" class="rounded-xl border border-amber-300/40 bg-slate-950/40 px-4 py-3 text-sm font-semibold text-amber-100 transition hover:border-amber-200/60">Marquer la mise à jour comme terminée</button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($errors !== []): ?>
                    <section class="rounded-[1.5rem] border border-red-500/30 bg-red-500/10 px-5 py-4 text-sm text-red-100">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle mt-0.5"></i>
                            <div class="space-y-1">
                                <p class="font-semibold">L’action demandée a échoué.</p>
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo e((string) $error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <section class="rounded-[1.5rem] border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-100">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle mt-0.5"></i>
                            <div>
                                <p class="font-semibold"><?php echo e($success); ?></p>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($isLocked): ?>
                    <section class="rounded-[1.5rem] border border-slate-600 bg-slate-800/70 px-5 py-4 text-sm text-slate-200">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-lock mt-0.5"></i>
                            <div>
                                <p class="font-semibold">Le cours est actuellement verrouillé.</p>
                                <p class="mt-1">Tant que son statut reste <strong><?php echo e($courseStatusLabel); ?></strong>, les modifications de contenu sont bloquées.</p>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section data-course-content-root data-course-id="<?php echo e((string) $courseId); ?>" data-course-step-default="<?php echo e($courseStepDefault); ?>" class="space-y-6">
                    <div data-course-step-panel="pedagogy" class="hidden space-y-6">
                        <section class="rounded-[1.85rem] border border-slate-700 bg-slate-800/80 p-6 shadow-[0_22px_60px_-35px_rgba(15,23,42,0.9)] backdrop-blur md:p-8">
                            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Étape 2</p>
                                    <h1 class="mt-2 text-2xl font-bold text-white sm:text-3xl">Pédagogie du cours</h1>
                                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">Ajoutez les résultats attendus, la préparation et le matériel.</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Objectifs</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) count($objectives)); ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Prérequis</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) count($prerequisites)); ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Matériel</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) count($equipment)); ?></p>
                                    </div>
                                </div>
                            </div>

                            <form method="post" data-course-pedagogy-form>
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="update_pedagogy">

                                <section class="learning-outcomes-panel rounded-[1.35rem] border border-slate-700 bg-slate-950/25 p-4" aria-labelledby="content-learning-outcomes-title">
                                    <div class="learning-outcomes-head">
                                        <div>
                                            <p class="course-list-eyebrow">Pédagogie</p>
                                            <h3 id="content-learning-outcomes-title">Objectifs, prérequis et matériel</h3>
                                        </div>
                                        <span class="learning-outcomes-badge">1 champ requis</span>
                                    </div>

                                    <div class="learning-outcome-row is-required">
                                        <div class="learning-outcome-title">
                                            <span class="learning-outcome-icon"><i class="fas fa-bullseye"></i></span>
                                            <div class="learning-outcome-copy">
                                                <p>Résultats attendus <span class="text-red-400">*</span></p>
                                                <small>Ce que l’apprenant saura faire à la fin du cours.</small>
                                            </div>
                                            <span class="course-list-count" data-list-count="objectives-list" data-list-max="6">0/6</span>
                                        </div>
                                        <div class="learning-outcome-body">
                                            <div class="course-list-quick is-inline">
                                                <input type="text" data-list-quick-input data-list-target="objectives-list" placeholder="Ex : Créer une page HTML simple" class="course-list-quick-input w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <button type="button" data-add-target="objectives-list" aria-label="Ajouter un objectif" class="course-list-add-btn inline-flex items-center justify-center rounded-xl bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <div id="objectives-list" data-name="objectives[]" data-placeholder="Ex : Créer une page HTML simple" data-initial="<?php echo e((string) json_encode($objectives, $jsonFlags)); ?>" class="course-list"></div>
                                        </div>
                                    </div>

                                    <div class="learning-outcome-row">
                                        <div class="learning-outcome-title">
                                            <span class="learning-outcome-icon"><i class="fas fa-key"></i></span>
                                            <div class="learning-outcome-copy">
                                                <p>Préparation</p>
                                                <small>Ce qu’il doit déjà connaître.</small>
                                            </div>
                                            <span class="course-list-optional">Optionnel</span>
                                        </div>
                                        <div class="learning-outcome-body">
                                            <div class="course-list-quick is-inline">
                                                <input type="text" data-list-quick-input data-list-target="prerequisites-list" placeholder="Ex : Savoir utiliser un ordinateur" class="course-list-quick-input w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <button type="button" data-add-target="prerequisites-list" aria-label="Ajouter un prérequis" class="course-list-add-btn inline-flex items-center justify-center rounded-xl bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <div id="prerequisites-list" data-name="prerequisites[]" data-placeholder="Ex : Savoir utiliser un ordinateur" data-initial="<?php echo e((string) json_encode($prerequisites, $jsonFlags)); ?>" class="course-list"></div>
                                        </div>
                                    </div>

                                    <div class="learning-outcome-row">
                                        <div class="learning-outcome-title">
                                            <span class="learning-outcome-icon"><i class="fas fa-laptop"></i></span>
                                            <div class="learning-outcome-copy">
                                                <p>Matériel</p>
                                                <small>Ce dont il aura besoin pour suivre le cours.</small>
                                            </div>
                                            <span class="course-list-optional">Optionnel</span>
                                        </div>
                                        <div class="learning-outcome-body">
                                            <div class="course-list-quick is-inline">
                                                <input type="text" data-list-quick-input data-list-target="equipment-list" placeholder="Ex : Ordinateur + connexion internet" class="course-list-quick-input w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                <button type="button" data-add-target="equipment-list" aria-label="Ajouter un équipement" class="course-list-add-btn inline-flex items-center justify-center rounded-xl bg-blue-600 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <div id="equipment-list" data-name="equipment[]" data-placeholder="Ex : Ordinateur + connexion internet" data-initial="<?php echo e((string) json_encode($equipment, $jsonFlags)); ?>" class="course-list"></div>
                                        </div>
                                    </div>
                                </section>

                                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <a href="<?php echo e($courseEditUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-900/40 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-900">
                                        <i class="fas fa-arrow-left"></i>
                                        Informations
                                    </a>
                                    <div class="flex flex-col gap-3 sm:flex-row">
                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-900/40 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-900 disabled:cursor-not-allowed disabled:opacity-60">
                                            <i class="fas fa-save"></i>
                                            Enregistrer
                                        </button>
                                        <button type="button" data-course-step-next="content" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
                                            Contenu
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </section>
                    </div>

                    <div data-course-step-panel="content" class="space-y-6">
                        <section class="rounded-[1.85rem] border border-slate-700 bg-slate-800/80 p-6 shadow-[0_22px_60px_-35px_rgba(15,23,42,0.9)] backdrop-blur md:p-8">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Étape 2</p>
                                    <h1 class="mt-2 text-2xl font-bold text-white sm:text-3xl"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></h1>
                                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">Structurez modules, leçons, ressources et rendez-vous.</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-4">
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Chapitres</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) $stats['chapters']); ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Leçons</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) $stats['lessons']); ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Ressources</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) $stats['resources']); ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Rendez-vous</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) $stats['meetings']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <form id="form-add-chapter" method="post" class="rounded-[1.7rem] border border-slate-700 bg-slate-800/80 p-5 shadow-[0_18px_55px_-35px_rgba(15,23,42,0.9)] backdrop-blur">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="add_chapter">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Modules</p>
                                        <h2 class="mt-1 text-lg font-semibold text-white">Ajouter un module</h2>
                                    </div>
                                    <span class="rounded-full bg-blue-500/15 px-3 py-1 text-xs font-semibold text-blue-200">Étape clé</span>
                                </div>
                                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                                    <div>
                                        <label for="chapter_title" class="mb-2 block text-sm font-medium text-slate-200">Titre *</label>
                                        <input id="chapter_title" name="chapter_title" <?php echo $isLocked ? 'disabled' : ''; ?> required placeholder="Module 1 : Fondamentaux" class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    </div>
                                    <div>
                                        <label for="chapter_description" class="mb-2 block text-sm font-medium text-slate-200">Description</label>
                                        <input id="chapter_description" name="chapter_description" <?php echo $isLocked ? 'disabled' : ''; ?> placeholder="Objectif du module" class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    </div>
                                    <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                        <i class="fas fa-plus-circle"></i>
                                        Ajouter
                                    </button>
                                </div>
                            </form>

                            <form id="form-add-lesson" method="post" class="rounded-[1.7rem] border border-slate-700 bg-slate-800/80 p-5 shadow-[0_18px_55px_-35px_rgba(15,23,42,0.9)] backdrop-blur">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="add_lesson">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Leçons</p>
                                        <h2 class="mt-1 text-lg font-semibold text-white">Ajouter une leçon</h2>
                                    </div>
                                </div>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-200">Chapitre *</label>
                                        <select name="lesson_chapter_id" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> required class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                            <option value="">Choisir</option>
                                            <?php foreach ($contentChapters as $chapter): ?>
                                                <option value="<?php echo e((string) ($chapter['id'] ?? '')); ?>"><?php echo e((string) ($chapter['position'] ?? '')); ?>. <?php echo e((string) ($chapter['title'] ?? '')); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-200">Position (optionnel)</label>
                                        <input name="lesson_position" type="number" min="1" <?php echo $isLocked ? 'disabled' : ''; ?> placeholder="Auto" class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    </div>
                                </div>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-200">Titre *</label>
                                        <input name="lesson_title" <?php echo $isLocked ? 'disabled' : ''; ?> required placeholder="Introduction" class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-200">Durée (minutes) *</label>
                                        <input name="lesson_duration" type="number" min="1" <?php echo $isLocked ? 'disabled' : ''; ?> required placeholder="15" class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="mb-2 block text-sm font-medium text-slate-200">Description</label>
                                    <textarea name="lesson_description" rows="3" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60"></textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="mb-2 block text-sm font-medium text-slate-200">Lien YouTube *</label>
                                    <input name="youtube_url" <?php echo $isLocked ? 'disabled' : ''; ?> required placeholder="https://www.youtube.com/watch?v=..." class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                </div>
                                <button type="submit" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    <i class="fas fa-play-circle"></i>
                                    Ajouter la leçon
                                </button>
                            </form>
                        </div>

                        <section class="rounded-[1.85rem] border border-slate-700 bg-slate-800/80 p-6 shadow-[0_22px_60px_-35px_rgba(15,23,42,0.9)] backdrop-blur md:p-8">
                            <div class="mb-6 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Contenu du cours</p>
                                    <h2 class="mt-1 text-xl font-bold text-white">Modules et leçons</h2>
                                </div>
                                <button type="button" data-course-step-next="settings" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500">
                                    Ressources et paramètres
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>

                            <?php if ($contentChapters === []): ?>
                                <div class="rounded-[1.4rem] border border-dashed border-slate-600 bg-slate-900/30 px-5 py-10 text-center text-sm text-slate-400">Aucun module pour le moment. Commencez par ajouter un chapitre.</div>
                            <?php endif; ?>

                            <div class="space-y-5">
                                <?php foreach ($contentChapters as $chapter): ?>
                                    <?php
                                    $chapterId = max(0, (int) ($chapter['id'] ?? 0));
                                    $chapterLessons = array_values(is_array($lessonsByChapter[$chapterId] ?? null) ? $lessonsByChapter[$chapterId] : []);
                                    $chapterDocs = array_values(is_array($docsByChapter[$chapterId] ?? null) ? $docsByChapter[$chapterId] : []);
                                    $chapterMeetings = array_values(is_array($meetingsByChapter[$chapterId] ?? null) ? $meetingsByChapter[$chapterId] : []);
                                    ?>
                                    <article class="rounded-[1.55rem] border border-slate-700 bg-slate-900/35 p-5">
                                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Module <?php echo e((string) ($chapter['position'] ?? '')); ?></p>
                                                <h3 class="mt-1 text-xl font-semibold text-white"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></h3>
                                                <?php if (trim((string) ($chapter['description'] ?? '')) !== ''): ?>
                                                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300"><?php echo e((string) $chapter['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <form method="post">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="duplicate_chapter">
                                                    <input type="hidden" name="duplicate_chapter_id" value="<?php echo e((string) $chapterId); ?>">
                                                    <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-slate-500 disabled:cursor-not-allowed disabled:opacity-60">Dupliquer</button>
                                                </form>
                                                <form method="post">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="delete_chapter">
                                                    <input type="hidden" name="delete_chapter_id" value="<?php echo e((string) $chapterId); ?>">
                                                    <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-xl border border-red-400/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-200 transition hover:border-red-300/50 disabled:cursor-not-allowed disabled:opacity-60">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                            <div class="rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-3">
                                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Leçons</p>
                                                <p class="mt-1 font-semibold text-white"><?php echo e((string) count($chapterLessons)); ?></p>
                                            </div>
                                            <div class="rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-3">
                                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Ressources</p>
                                                <p class="mt-1 font-semibold text-white"><?php echo e((string) count($chapterDocs)); ?></p>
                                            </div>
                                            <div class="rounded-xl border border-slate-700 bg-slate-800/80 px-4 py-3">
                                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Rendez-vous</p>
                                                <p class="mt-1 font-semibold text-white"><?php echo e((string) count($chapterMeetings)); ?></p>
                                            </div>
                                        </div>

                                        <div class="mt-5 space-y-3">
                                            <?php if ($chapterLessons === []): ?>
                                                <div class="rounded-xl border border-dashed border-slate-600 bg-slate-800/50 px-4 py-4 text-sm text-slate-400">Aucune leçon pour ce module.</div>
                                            <?php endif; ?>
                                            <?php foreach ($chapterLessons as $lesson): ?>
                                                <?php $lessonId = max(0, (int) ($lesson['id'] ?? 0)); ?>
                                                <article class="rounded-[1.2rem] border border-slate-700 bg-slate-800/90 p-4">
                                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-2 text-sm font-semibold text-white">
                                                                <i class="fas fa-play-circle text-blue-400"></i>
                                                                <span>Leçon <?php echo e((string) ($lesson['position'] ?? '')); ?></span>
                                                            </div>
                                                            <h4 class="mt-2 text-lg font-semibold text-white"><?php echo e((string) ($lesson['title'] ?? '')); ?></h4>
                                                            <?php if (trim((string) ($lesson['description'] ?? '')) !== ''): ?>
                                                                <p class="mt-2 text-sm leading-7 text-slate-300"><?php echo e((string) $lesson['description']); ?></p>
                                                            <?php endif; ?>
                                                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-300">
                                                                <span class="rounded-full border border-slate-600 bg-slate-900/60 px-3 py-1"><?php echo e($formatMinutes((int) ($lesson['duration_estimate_min'] ?? 0))); ?></span>
                                                                <?php if (trim((string) ($lesson['youtube_url'] ?? '')) !== ''): ?>
                                                                    <a href="<?php echo e((string) $lesson['youtube_url']); ?>" target="_blank" rel="noopener" class="rounded-full border border-blue-400/30 bg-blue-500/10 px-3 py-1 font-semibold text-blue-200 transition hover:bg-blue-500/20">YouTube</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-wrap gap-2">
                                                            <form method="post">
                                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                <input type="hidden" name="action" value="duplicate_lesson">
                                                                <input type="hidden" name="duplicate_lesson_id" value="<?php echo e((string) $lessonId); ?>">
                                                                <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-xl border border-slate-600 bg-slate-900/60 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-slate-500 disabled:cursor-not-allowed disabled:opacity-60">Dupliquer</button>
                                                            </form>
                                                            <form method="post">
                                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                <input type="hidden" name="action" value="delete_lesson">
                                                                <input type="hidden" name="delete_lesson_id" value="<?php echo e((string) $lessonId); ?>">
                                                                <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-xl border border-red-400/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-200 transition hover:border-red-300/50 disabled:cursor-not-allowed disabled:opacity-60">Supprimer</button>
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
                            <a href="<?php echo e($courseEditUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-900/40 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-900">
                                Précédent
                            </a>
                            <button type="button" data-course-step-next="settings" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
                                Étape suivante
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div data-course-step-panel="settings" class="hidden space-y-6">
                        <section class="rounded-[1.85rem] border border-slate-700 bg-slate-800/80 p-6 shadow-[0_22px_60px_-35px_rgba(15,23,42,0.9)] backdrop-blur md:p-8">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Étape 3</p>
                                    <h2 class="mt-2 text-2xl font-bold text-white">Ressources, rendez-vous et examens</h2>
                                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">Ajoutez documents, rendez-vous et évaluation.</p>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Documents</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) ($stats['resources'] + $stats['exam_docs'])); ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Rendez-vous</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) $stats['meetings']); ?></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Examens</p>
                                        <p class="mt-1 text-2xl font-semibold text-white"><?php echo e((string) count($exams)); ?></p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[1.85rem] border border-slate-700 bg-slate-800/80 p-6 shadow-[0_22px_60px_-35px_rgba(15,23,42,0.9)] backdrop-blur md:p-8">
                            <div class="mb-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Ressources pédagogiques</p>
                                <h2 class="mt-1 text-xl font-bold text-white">Documents et fichiers</h2>
                            </div>

                            <form id="form-add-doc" method="post" enctype="multipart/form-data" class="rounded-[1.4rem] border border-slate-700 bg-slate-900/30 p-5">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="add_doc">
                                <div class="grid gap-3 md:grid-cols-3">
                                    <div>
                                        <label for="doc_type" class="mb-2 block text-sm font-medium text-slate-200">Type *</label>
                                        <select id="doc_type" name="doc_type" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                            <option value="document">Document d'accompagnement</option>
                                            <option value="file">Fichier</option>
                                            <option value="exercise">Exercice</option>
                                        </select>
                                    </div>
                                    <div id="doc_scope_wrapper">
                                        <label for="doc_scope" class="mb-2 block text-sm font-medium text-slate-200">Associer à *</label>
                                        <select id="doc_scope" name="doc_scope" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                            <option value="chapter">Chapitre</option>
                                            <option value="lesson">Leçon</option>
                                        </select>
                                    </div>
                                    <div id="doc_chapter_wrapper">
                                        <label for="doc_chapter_id" class="mb-2 block text-sm font-medium text-slate-200">Chapitre *</label>
                                        <select id="doc_chapter_id" name="doc_chapter_id" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                            <option value="">Choisir</option>
                                            <?php foreach ($contentChapters as $chapter): ?>
                                                <option value="<?php echo e((string) ($chapter['id'] ?? '')); ?>"><?php echo e((string) ($chapter['position'] ?? '')); ?>. <?php echo e((string) ($chapter['title'] ?? '')); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div id="doc_lesson_wrapper" class="mt-3 hidden">
                                    <label for="doc_lesson_id" class="mb-2 block text-sm font-medium text-slate-200">Leçon *</label>
                                    <select id="doc_lesson_id" name="doc_lesson_id" <?php echo ($lessonsList === [] || $isLocked) ? 'disabled' : ''; ?> class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                        <option value="">Choisir</option>
                                        <?php foreach ($lessonsList as $lesson): ?>
                                            <option value="<?php echo e((string) ($lesson['id'] ?? '')); ?>" data-chapter-id="<?php echo e((string) ($lesson['chapter_id'] ?? '')); ?>">
                                                <?php echo e((string) ($lesson['chap_pos'] ?? '')); ?>. <?php echo e((string) ($lesson['chapter_title'] ?? '')); ?> - <?php echo e((string) ($lesson['position'] ?? '')); ?>. <?php echo e((string) ($lesson['title'] ?? '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mt-3">
                                    <label class="mb-2 block text-sm font-medium text-slate-200">Titre *</label>
                                    <input name="doc_title" <?php echo $isLocked ? 'disabled' : ''; ?> required class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                </div>
                                <div class="mt-3">
                                    <label for="doc_file" class="mb-2 block text-sm font-medium text-slate-200">Fichier *</label>
                                    <input id="doc_file" name="doc_file" type="file" <?php echo $isLocked ? 'disabled' : ''; ?> required class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-sm text-white outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-200 focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    <p class="mt-2 text-xs text-slate-400">Maximum <?php echo e((string) $driveMaxMb); ?> Mo. <?php echo e($driveUploadNote !== '' ? $driveUploadNote : "Google Drive n'est pas configuré."); ?><?php if ($driveConnectRecommended): ?> <a href="<?php echo e(site_url('drive/oauth_connect')); ?>" class="font-semibold text-blue-300 underline underline-offset-4">Connecter Drive</a><?php endif; ?></p>
                                </div>
                                <div id="doc-upload-progress" class="mt-4 hidden rounded-xl border border-slate-700 bg-slate-800 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p id="doc-upload-progress-label" class="text-sm font-semibold text-white">Préparation de l'envoi...</p>
                                        <span id="doc-upload-progress-text" class="text-sm font-semibold text-slate-400">0%</span>
                                    </div>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-700">
                                        <div id="doc-upload-progress-bar" class="h-full w-0 rounded-full bg-emerald-500 transition-all duration-150"></div>
                                    </div>
                                    <p id="doc-upload-progress-hint" class="mt-3 text-xs text-slate-400">Le document peut prendre quelques instants à être validé après l'envoi.</p>
                                </div>
                                <button type="submit" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    <i class="fas fa-upload"></i>
                                    Ajouter la ressource
                                </button>
                            </form>

                            <div class="mt-6 space-y-4">
                                <?php if ($docsByChapter === [] && $docsByLesson === []): ?>
                                    <div class="rounded-[1.4rem] border border-dashed border-slate-600 bg-slate-900/30 px-5 py-8 text-center text-sm text-slate-400">Aucune ressource ajoutée pour le moment.</div>
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
                                    <section class="rounded-[1.35rem] border border-slate-700 bg-slate-900/30 p-5">
                                        <div class="mb-4">
                                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Chapitre <?php echo e((string) ($chapter['position'] ?? '')); ?></p>
                                            <h3 class="text-lg font-semibold text-white"><?php echo e((string) ($chapter['title'] ?? '')); ?></h3>
                                        </div>
                                        <div class="space-y-3">
                                            <?php foreach ($chapterDocs as $doc): ?>
                                                <?php $docId = max(0, (int) ($doc['id'] ?? 0)); ?>
                                                <article class="rounded-xl border border-slate-700 bg-slate-800/90 p-4">
                                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                        <div>
                                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400"><?php echo e($docLabel((string) ($doc['type'] ?? ''), (string) ($doc['mime'] ?? ''))); ?></p>
                                                            <h4 class="mt-1 font-semibold text-white"><?php echo e((string) ($doc['title'] ?? 'Ressource')); ?></h4>
                                                            <?php if (trim((string) ($doc['file_path'] ?? '')) !== ''): ?>
                                                                <a href="<?php echo e((string) $doc['file_path']); ?>" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-blue-200 transition hover:text-blue-100">
                                                                    <i class="fas fa-external-link-alt text-xs"></i>
                                                                    Ouvrir
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                        <form method="post">
                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                            <input type="hidden" name="action" value="delete_doc">
                                                            <input type="hidden" name="delete_doc_id" value="<?php echo e((string) $docId); ?>">
                                                            <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-xl border border-red-400/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-200 transition hover:border-red-300/50 disabled:cursor-not-allowed disabled:opacity-60">Supprimer</button>
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
                                                <div class="rounded-xl border border-slate-700 bg-slate-800/70 p-4">
                                                    <p class="mb-3 text-sm font-semibold text-white">Leçon <?php echo e((string) ($lesson['position'] ?? '')); ?>. <?php echo e((string) ($lesson['title'] ?? '')); ?></p>
                                                    <div class="space-y-3">
                                                        <?php foreach ($lessonDocs as $doc): ?>
                                                            <?php $docId = max(0, (int) ($doc['id'] ?? 0)); ?>
                                                            <article class="rounded-xl border border-slate-700 bg-slate-900/60 p-4">
                                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                                    <div>
                                                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400"><?php echo e($docLabel((string) ($doc['type'] ?? ''), (string) ($doc['mime'] ?? ''))); ?></p>
                                                                        <h4 class="mt-1 font-semibold text-white"><?php echo e((string) ($doc['title'] ?? 'Ressource')); ?></h4>
                                                                        <?php if (trim((string) ($doc['file_path'] ?? '')) !== ''): ?>
                                                                            <a href="<?php echo e((string) $doc['file_path']); ?>" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-blue-200 transition hover:text-blue-100">
                                                                                <i class="fas fa-external-link-alt text-xs"></i>
                                                                                Ouvrir
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <form method="post">
                                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                        <input type="hidden" name="action" value="delete_doc">
                                                                        <input type="hidden" name="delete_doc_id" value="<?php echo e((string) $docId); ?>">
                                                                        <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-xl border border-red-400/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-200 transition hover:border-red-300/50 disabled:cursor-not-allowed disabled:opacity-60">Supprimer</button>
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

                        <section class="rounded-[1.85rem] border border-slate-700 bg-slate-800/80 p-6 shadow-[0_22px_60px_-35px_rgba(15,23,42,0.9)] backdrop-blur md:p-8">
                            <div class="mb-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Rendez-vous</p>
                                <h2 class="mt-1 text-xl font-bold text-white">Sessions et accompagnement</h2>
                            </div>

                            <form id="form-add-meeting" method="post" class="rounded-[1.4rem] border border-slate-700 bg-slate-900/30 p-5">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="add_meeting">
                                <div class="grid gap-3 md:grid-cols-3">
                                    <div class="md:col-span-2">
                                        <label class="mb-2 block text-sm font-medium text-slate-200">Titre *</label>
                                        <input name="meeting_title" <?php echo $isLocked ? 'disabled' : ''; ?> required class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-200">Mode *</label>
                                        <select name="meeting_mode" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                            <option value="online">En ligne</option>
                                            <option value="onsite">Présentiel</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-200">Chapitre *</label>
                                        <select id="meeting_chapter_id" name="meeting_chapter_id" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                            <option value="">Choisir</option>
                                            <?php foreach ($contentChapters as $chapter): ?>
                                                <option value="<?php echo e((string) ($chapter['id'] ?? '')); ?>"><?php echo e((string) ($chapter['position'] ?? '')); ?>. <?php echo e((string) ($chapter['title'] ?? '')); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p id="meeting_chapter_label" class="mt-2 text-xs text-slate-400">Sélectionnez un chapitre depuis le plan.</p>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-200">Durée (minutes)</label>
                                        <input name="meeting_duration_min" type="number" min="0" value="60" <?php echo $isLocked ? 'disabled' : ''; ?> class="w-full rounded-xl border border-slate-600 bg-slate-700/90 px-4 py-3 text-white outline-none transition focus:border-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    </div>
                                </div>
                                <button type="submit" <?php echo (!$hasChapters || $isLocked) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                    <i class="fas fa-calendar-plus"></i>
                                    Ajouter le rendez-vous
                                </button>
                            </form>

                            <div class="mt-6 space-y-4">
                                <?php if ($meetings === []): ?>
                                    <div class="rounded-[1.4rem] border border-dashed border-slate-600 bg-slate-900/30 px-5 py-8 text-center text-sm text-slate-400">Aucun rendez-vous créé pour l’instant.</div>
                                <?php endif; ?>
                                <?php foreach ($meetings as $meeting): ?>
                                    <?php $meetingId = max(0, (int) ($meeting['id'] ?? 0)); ?>
                                    <article class="rounded-[1.35rem] border border-slate-700 bg-slate-900/30 p-5">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400"><?php echo e(((string) ($meeting['mode'] ?? 'online')) === 'onsite' ? 'Présentiel' : 'En ligne'); ?></p>
                                                <h3 class="mt-1 text-lg font-semibold text-white"><?php echo e((string) ($meeting['title'] ?? 'Rendez-vous')); ?></h3>
                                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-300">
                                                    <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1"><?php echo e((string) ($meeting['chapter_title'] ?? 'Chapitre')); ?></span>
                                                    <?php if ((int) ($meeting['duration_min'] ?? 0) > 0): ?>
                                                        <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1"><?php echo e($formatMinutes((int) ($meeting['duration_min'] ?? 0))); ?></span>
                                                    <?php endif; ?>
                                                    <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1"><?php echo e((string) ($meeting['status'] ?? 'pending')); ?></span>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <form method="post">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="duplicate_meeting">
                                                    <input type="hidden" name="duplicate_meeting_id" value="<?php echo e((string) $meetingId); ?>">
                                                    <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-xl border border-slate-600 bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-slate-500 disabled:cursor-not-allowed disabled:opacity-60">Dupliquer</button>
                                                </form>
                                                <form method="post">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="delete_meeting">
                                                    <input type="hidden" name="delete_meeting_id" value="<?php echo e((string) $meetingId); ?>">
                                                    <button type="submit" <?php echo $isLocked ? 'disabled' : ''; ?> class="rounded-xl border border-red-400/30 bg-red-500/10 px-3 py-2 text-xs font-semibold text-red-200 transition hover:border-red-300/50 disabled:cursor-not-allowed disabled:opacity-60">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="rounded-[1.85rem] border border-slate-700 bg-slate-800/80 p-6 shadow-[0_22px_60px_-35px_rgba(15,23,42,0.9)] backdrop-blur md:p-8">
                            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Examens & évaluations</p>
                                    <h2 class="mt-1 text-xl font-bold text-white">Examen final</h2>
                                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-300">La configuration détaillée se fait dans l’écran dédié.</p>
                                </div>
                                <a href="<?php echo e(site_url('teacher/exams_create?course_id=' . $courseId . '&return=content')); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
                                    <i class="fas fa-file-alt"></i>
                                    Ouvrir la configuration
                                </a>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-4">
                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Sessions avancées</p>
                                    <p class="mt-2 text-3xl font-semibold text-white"><?php echo e((string) count($exams)); ?></p>
                                </div>
                                <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-4">
                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Documents d’examen</p>
                                    <p class="mt-2 text-3xl font-semibold text-white"><?php echo e((string) $stats['exam_docs']); ?></p>
                                </div>
                            </div>

                            <div class="mt-6 space-y-4">
                                <?php if ($exams === []): ?>
                                    <div class="rounded-[1.4rem] border border-dashed border-slate-600 bg-slate-900/30 px-5 py-8 text-center text-sm text-slate-400">Aucun examen avancé n’est encore configuré.</div>
                                <?php endif; ?>
                                <?php foreach ($exams as $exam): ?>
                                    <article class="rounded-[1.35rem] border border-slate-700 bg-slate-900/30 p-5">
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400"><?php echo e((string) ($exam['type'] ?? 'exam')); ?></p>
                                        <h3 class="mt-1 text-lg font-semibold text-white"><?php echo e((string) ($exam['title'] ?? 'Examen')); ?></h3>
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-300">
                                            <?php if ((int) ($exam['duration_min'] ?? 0) > 0): ?>
                                                <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1"><?php echo e($formatMinutes((int) ($exam['duration_min'] ?? 0))); ?></span>
                                            <?php endif; ?>
                                            <span class="rounded-full border border-slate-600 bg-slate-800 px-3 py-1"><?php echo e($formatDate((string) ($exam['updated_at'] ?? ''))); ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <div class="flex justify-between gap-3">
                            <button type="button" data-course-step-prev="content" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-900/40 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-900">
                                Précédent
                            </button>
                            <button type="button" data-course-step-next="publish" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500">
                                Étape suivante
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div data-course-step-panel="publish" class="hidden space-y-6">
                        <section class="rounded-[1.95rem] border border-slate-700 bg-slate-800/80 p-6 shadow-[0_24px_65px_-35px_rgba(15,23,42,0.9)] backdrop-blur md:p-10">
                            <div class="mx-auto flex max-w-3xl flex-col items-center text-center">
                                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/15 text-3xl text-emerald-300">
                                    <i class="fas fa-check"></i>
                                </span>
                                <p class="mt-6 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Étape 4</p>
                                <h2 class="mt-3 text-3xl font-bold text-white">Prêt à publier ?</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">Vérifiez, enregistrez, puis soumettez pour validation.</p>
                                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                    <form method="post">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <button type="submit" name="action" value="save_draft" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-900/40 px-6 py-3 text-sm font-semibold text-slate-100 transition hover:border-slate-500 hover:bg-slate-900 disabled:cursor-not-allowed disabled:opacity-60">
                                            <i class="fas fa-save"></i>
                                            Enregistrer le brouillon
                                        </button>
                                    </form>
                                    <form method="post">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <button type="submit" name="action" value="submit_course" <?php echo $isLocked ? 'disabled' : ''; ?> class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60">
                                            <i class="fas fa-paper-plane"></i>
                                            Soumettre pour validation
                                        </button>
                                    </form>
                                </div>
                                <p class="mt-4 text-xs text-slate-400"><?php echo $isLocked ? 'Le cours est déjà soumis et reste en lecture tant qu’il n’est pas rouvert.' : 'Le cours sera examiné par notre équipe avant publication.'; ?></p>
                                <?php if ($previewUrl !== ''): ?>
                                    <a href="<?php echo e($previewUrl); ?>" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-200 transition hover:text-blue-100">
                                        <i class="fas fa-external-link-alt"></i>
                                        Ouvrir la fiche publique
                                    </a>
                                <?php endif; ?>
                            </div>
                        </section>

                        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.85fr)_minmax(0,1fr)_minmax(0,0.85fr)]">
                            <section class="overflow-hidden rounded-[1.7rem] border border-slate-700 bg-slate-800/80 shadow-[0_18px_55px_-35px_rgba(15,23,42,0.9)] backdrop-blur">
                                <div class="border-b border-slate-700 px-5 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Aperçu</p>
                                    <h3 class="mt-1 text-lg font-semibold text-white">Fiche apprenant</h3>
                                </div>
                                <?php if ($coverUrl !== ''): ?>
                                    <img src="<?php echo e($coverUrl); ?>" alt="Miniature du cours" class="h-56 w-full object-cover">
                                <?php else: ?>
                                    <div class="flex h-56 items-center justify-center bg-slate-900/30 text-slate-500">
                                        <i class="fas fa-image text-3xl"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="space-y-4 px-5 py-5">
                                    <div>
                                        <p class="text-xl font-semibold text-white"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></p>
                                        <p class="mt-2 text-sm leading-7 text-slate-300"><?php echo e(trim((string) ($course['description'] ?? '')) !== '' ? (string) $course['description'] : 'La description de ce cours apparaîtra ici une fois la fiche complétée.'); ?></p>
                                    </div>
                                    <div class="grid gap-3">
                                        <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Durée estimée</p>
                                            <p class="mt-1 font-semibold text-white"><?php echo e(((float) ($course['duration_hours'] ?? 0)) > 0 ? number_format((float) $course['duration_hours'], 2, ',', ' ') . ' h' : '0 h'); ?></p>
                                        </div>
                                        <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Tarif</p>
                                            <p class="mt-1 font-semibold text-white"><?php echo e(((int) ($course['price_xof'] ?? 0)) > 0 ? number_format((int) ($course['price_xof'] ?? 0), 0, ',', ' ') . ' F CFA' : 'Gratuit'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-[1.7rem] border border-slate-700 bg-slate-800/80 p-5 shadow-[0_18px_55px_-35px_rgba(15,23,42,0.9)] backdrop-blur">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Checklist</p>
                                        <h3 class="mt-1 text-lg font-semibold text-white">Prêt pour validation</h3>
                                    </div>
                                    <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-200"><?php echo e((string) $summaryPercent); ?>%</span>
                                </div>
                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-700">
                                    <div class="h-full rounded-full bg-emerald-500" style="width: <?php echo e((string) $summaryPercent); ?>%;"></div>
                                </div>
                                <div class="mt-4 space-y-3">
                                    <?php foreach ($submissionSummary as $summaryItem): ?>
                                        <?php $done = !empty($summaryItem['done']); ?>
                                        <div class="builder-summary-item<?php echo $done ? ' is-done' : ''; ?>">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo e((string) ($summaryItem['label'] ?? 'Vérification')); ?></p>
                                                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                                                        <?php if (array_key_exists('count', $summaryItem)): ?>
                                                            Compteur : <?php echo e((string) ($summaryItem['count'] ?? 0)); ?>
                                                        <?php else: ?>
                                                            <?php echo $done ? 'Condition remplie.' : 'Condition encore manquante.'; ?>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <span class="rounded-full px-2.5 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.16em] <?php echo $done ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'; ?>">
                                                    <?php echo $done ? 'OK' : 'À faire'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <section class="rounded-[1.7rem] border border-slate-700 bg-slate-800/80 p-5 shadow-[0_18px_55px_-35px_rgba(15,23,42,0.9)] backdrop-blur">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Historique</p>
                                <h3 class="mt-1 text-lg font-semibold text-white">Dernières actions</h3>
                                <div class="mt-4 space-y-3">
                                    <?php if ($auditTimeline === []): ?>
                                        <div class="rounded-xl border border-dashed border-slate-600 bg-slate-900/30 px-4 py-6 text-sm text-slate-400">Aucune action récente à afficher.</div>
                                    <?php endif; ?>
                                    <?php foreach ($auditTimeline as $event): ?>
                                        <div class="rounded-xl border border-slate-700 bg-slate-900/30 px-4 py-3">
                                            <p class="text-sm font-semibold text-white"><?php echo e((string) ($event['label'] ?? 'Action')); ?></p>
                                            <p class="mt-1 text-xs text-slate-400"><?php echo e((string) ($event['who'] ?? 'Système')); ?> · <?php echo e($formatDate((string) ($event['created_at'] ?? ''))); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>

                        <div class="flex justify-start">
                            <button type="button" data-course-step-prev="settings" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-600 bg-slate-900/40 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-900">
                                Précédent
                            </button>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
