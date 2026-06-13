<?php
declare(strict_types=1);

$course = is_array($course ?? null) ? $course : [];
$courseId = (int) ($courseId ?? 0);
$isLocked = !empty($isLocked);
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$levelLabels = is_array($levelLabels ?? null) ? $levelLabels : [];
$chapters = is_array($chapters ?? null) ? $chapters : [];
$lessonsByChapter = is_array($lessonsByChapter ?? null) ? $lessonsByChapter : [];
$docsByChapter = is_array($docsByChapter ?? null) ? $docsByChapter : [];
$docsByLesson = is_array($docsByLesson ?? null) ? $docsByLesson : [];
$durationLabel = $durationLabel ?? null;
$videoPlaylist = is_array($videoPlaylist ?? null) ? $videoPlaylist : [];
$meetings = is_array($meetings ?? null) ? $meetings : [];
$docTotals = is_array($docTotals ?? null) ? $docTotals : ['exercise' => 0, 'resource' => 0, 'exam' => 0, 'all' => 0];
$exams = is_array($exams ?? null) ? $exams : [];
$ratingsModerationReady = !empty($ratingsModerationReady);
$ratingsForModeration = is_array($ratingsForModeration ?? null) ? $ratingsForModeration : [];
$visibleReviewCount = max(0, (int) ($visibleReviewCount ?? 0));
$hiddenReviewCount = max(0, (int) ($hiddenReviewCount ?? 0));
$moderationErrors = is_array($moderationErrors ?? null) ? $moderationErrors : [];
$moderationSuccess = trim((string) ($moderationSuccess ?? ''));

$extractYoutubeId = isset($extractYoutubeId) && is_callable($extractYoutubeId) ? $extractYoutubeId : static function (string $url): ?string {
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return null;
    }
    $host = strtolower((string) $parts['host']);
    if ($host === 'youtu.be') {
        return ltrim((string) ($parts['path'] ?? ''), '/');
    }
    if (str_contains($host, 'youtube.com') && !empty($parts['query'])) {
        parse_str((string) $parts['query'], $query);
        return is_string($query['v'] ?? null) ? $query['v'] : null;
    }
    return null;
};

$docLabel = isset($docLabel) && is_callable($docLabel) ? $docLabel : static function (?string $type, ?string $mime): string {
    if ($type === 'exercise') {
        return 'Exercice';
    }
    if ($type === 'exam') {
        return 'Examen';
    }
    if ($type === 'resource' && is_string($mime) && stripos($mime, 'url_document') !== false) {
        return "Document d'accompagnement";
    }
    return 'Ressource';
};

$formatCourseList = isset($formatCourseList) && is_callable($formatCourseList) ? $formatCourseList : static function ($value): string {
    return nl2br(e((string) $value));
};

$previewMode = 'teacher';
require __DIR__ . '/../shared/_course_preview_bootstrap.php';

$teacherAvatarUrl = (string) ($teacherAvatarUrl ?? '');
$teacherInitials = trim((string) ($teacherInitials ?? 'EN'));
$teacherDisplayName = trim((string) ($teacherDisplayName ?? $teacherName));
$teacherEmail = trim((string) ($teacherEmail ?? ''));

$chapterById = [];
foreach ($chapters as $chapterRow) {
    $chapterById[(int) ($chapterRow['id'] ?? 0)] = $chapterRow;
}

$activeVideo = $videoPlaylist[0] ?? null;
$firstLesson = null;
$activeLesson = null;
foreach ($lessonsByChapter as $lessonRows) {
    foreach ((array) $lessonRows as $lessonRow) {
        if ($firstLesson === null) {
            $firstLesson = $lessonRow;
        }
        if (is_array($activeVideo) && (int) ($lessonRow['id'] ?? 0) === (int) ($activeVideo['id'] ?? 0)) {
            $activeLesson = $lessonRow;
            break 2;
        }
    }
}
$activeLesson = $activeLesson ?: $firstLesson;

$activeChapterId = (int) (($activeLesson['chapter_id'] ?? null) ?: ($activeVideo['chapter'] ?? 0));
$activeChapterTitle = trim((string) ($chapterById[$activeChapterId]['title'] ?? 'Module principal'));
$activeLessonTitle = trim((string) (($activeLesson['title'] ?? null) ?: ($activeVideo['title'] ?? 'Aucune leçon sélectionnée')));
$activeLessonTitle = $activeLessonTitle !== '' ? $activeLessonTitle : 'Aucune leçon sélectionnée';
$activeLessonDescription = trim((string) ($activeLesson['description'] ?? ''));
$activeLessonType = trim((string) (($activeLesson['lesson_type'] ?? null) ?: ($activeVideo ? 'video' : '')));
$activeLessonDuration = $formatShortDuration($activeLesson['duration_estimate_min'] ?? ($activeVideo['duration'] ?? null));
$activeYoutubeId = is_array($activeVideo) ? trim((string) ($activeVideo['yt_id'] ?? '')) : '';
if ($activeYoutubeId === '' && $activeLesson !== null) {
    $activeYoutubeId = (string) ($extractYoutubeId((string) ($activeLesson['youtube_url'] ?? '')) ?? '');
}

$objectivesHtml = trim((string) $formatCourseList($course['objectives'] ?? ''));
$prerequisitesHtml = trim((string) $formatCourseList($course['prerequisites'] ?? ''));
$equipmentHtml = trim((string) $formatCourseList($course['equipment'] ?? ''));

$statusClass = match ($courseStatus) {
    'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
    'submitted' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200',
    'rejected' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200',
    default => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
};

$lessonTypeLabels = [
    'video' => 'Vidéo',
    'document' => 'Document',
    'exercise' => 'Exercice',
    'meeting' => 'Rendez-vous',
];

$lessonTypeIcon = static function (string $type): string {
    return match ($type) {
        'document' => 'file-text',
        'exercise' => 'clipboard-list',
        'meeting' => 'calendar',
        default => 'play-circle',
    };
};

$sumLessonMinutes = static function (array $lessonRows): int {
    $total = 0;
    foreach ($lessonRows as $lessonRow) {
        $total += max(0, (int) ($lessonRow['duration_estimate_min'] ?? 0));
    }
    return $total;
};

$formatExamType = static function (?string $type): string {
    return match ((string) $type) {
        'drive' => 'Document à soumettre',
        'meeting' => 'Rendez-vous',
        default => 'QCM',
    };
};

$editUrl = site_url('teacher/course_edit?id=' . $courseId);
$backUrl = site_url('teacher/courses');
$teacherNavActive = 'courses';
$teacherTopTitle = 'Aperçu du cours';
$teacherTopSubtitle = 'Lecture simulée côté apprenant';
$showSupportFab = true;
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Aperçu du cours - <?php echo e($courseTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/course_preview_teacher.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/course_preview_teacher.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-slate-100 text-slate-900 antialiased transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100 font-sans" data-view="teacher/course_preview">
    <div class="teacher-dashboard-layout relative min-h-screen pt-16 sm:pt-[4.5rem] md:pl-72">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="min-w-0">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="course-preview-reader relative mx-auto w-full max-w-[104rem] px-4 py-5 sm:px-6 lg:px-7">
                <section class="course-preview-shell rounded-[1.75rem] border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 sm:p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <a href="<?php echo e($backUrl); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-300">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('chevron-left'); ?></span>
                                <span>Mes cours</span>
                            </a>
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 shadow-sm ring-1 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/20">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-primary-600 dark:text-primary-300">Aperçu enseignant</p>
                                    <h1 class="mt-1 truncate text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                                        <?php echo e($courseTitle); ?>
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Visualisation proche de l'expérience apprenant, avec vos données de création: informations, modules, ressources, rendez-vous et examens.
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap lg:justify-end">
                            <span class="inline-flex items-center justify-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-bold <?php echo $statusClass; ?>">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($isLocked ? 'lock' : 'check-circle'); ?></span>
                                <?php echo e($courseStatusLabel); ?>
                            </span>
                            <a href="<?php echo e($editUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-200 hover:text-primary-700 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 dark:hover:border-primary-500/40 dark:hover:text-primary-200">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('settings'); ?></span>
                                Modifier le cours
                            </a>
                            <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="save_draft">
                                <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 <?php echo $isLocked ? 'cursor-not-allowed opacity-60' : ''; ?>" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('save'); ?></span>
                                    Brouillon
                                </button>
                            </form>
                            <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="submit">
                                <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white shadow-[0_18px_34px_-18px_rgba(37,99,235,0.75)] transition hover:-translate-y-0.5 hover:bg-primary-700 <?php echo $isLocked ? 'cursor-not-allowed opacity-60' : ''; ?>" <?php echo $isLocked ? 'disabled' : ''; ?>>
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                                    Soumettre
                                </button>
                            </form>
                        </div>
                    </div>
                </section>

                <div class="mt-4 rounded-[1.5rem] border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 shadow-sm dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 app-icon shrink-0" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>
                        <p>Simulation apprenant. Les actions enseignant restent disponibles.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
                    <div class="min-w-0 overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <section id="preview-video" class="bg-slate-950">
                            <div class="course-preview-video-frame relative mx-auto max-w-6xl overflow-hidden bg-slate-950">
                                <?php if ($activeYoutubeId !== ''): ?>
                                    <iframe
                                        class="h-full w-full"
                                        src="https://www.youtube.com/embed/<?php echo e(rawurlencode($activeYoutubeId)); ?>"
                                        title="<?php echo e($activeLessonTitle); ?>"
                                        loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                    ></iframe>
                                <?php elseif ($coverUrl !== ''): ?>
                                    <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" class="h-full w-full object-cover opacity-85">
                                    <div class="absolute inset-0 flex items-center justify-center bg-slate-950/35">
                                        <div class="rounded-3xl bg-white/95 px-5 py-4 text-center shadow-2xl dark:bg-slate-900/95">
                                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-600 text-white">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>
                                            </span>
                                            <p class="mt-3 text-sm font-bold text-slate-900 dark:text-white">Aucune vidéo YouTube détectée</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ajoutez une leçon vidéo pour obtenir un lecteur ici.</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="flex h-full min-h-[18rem] items-center justify-center bg-gradient-to-br from-slate-900 via-slate-950 to-primary-950 px-6 text-center">
                                        <div class="max-w-sm">
                                            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white/10 text-white ring-1 ring-white/15">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('video'); ?></span>
                                            </span>
                                            <p class="mt-4 text-lg font-black text-white">Aucun média principal</p>
                                            <p class="mt-2 text-sm text-slate-300">Ajoutez une vidéo ou une miniature pour améliorer l'aperçu apprenant.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="pointer-events-none absolute bottom-4 left-4 rounded-full bg-slate-950/70 px-3 py-1.5 text-xs font-bold text-white ring-1 ring-white/10">
                                    <?php echo e($activeLessonType !== '' ? ($lessonTypeLabels[$activeLessonType] ?? ucfirst($activeLessonType)) : 'Aperçu'); ?>
                                </div>
                            </div>
                        </section>

                        <nav class="course-preview-tabs flex gap-1 overflow-x-auto border-b border-slate-200 bg-white px-4 dark:border-slate-800 dark:bg-slate-900 sm:px-6" aria-label="Sections de l'aperçu">
                            <a href="#preview-overview" class="inline-flex shrink-0 items-center gap-2 border-b-2 border-primary-600 px-2 py-4 text-sm font-bold text-primary-600 dark:border-primary-300 dark:text-primary-300">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                                Aperçu
                            </a>
                            <a href="#preview-resources" class="inline-flex shrink-0 items-center gap-2 border-b-2 border-transparent px-2 py-4 text-sm font-bold text-slate-500 transition hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-300">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('folder'); ?></span>
                                Ressources
                            </a>
                            <a href="#preview-notes" class="inline-flex shrink-0 items-center gap-2 border-b-2 border-transparent px-2 py-4 text-sm font-bold text-slate-500 transition hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-300">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clipboard-list'); ?></span>
                                Examens
                            </a>
                            <a href="#reviews-moderation" class="inline-flex shrink-0 items-center gap-2 border-b-2 border-transparent px-2 py-4 text-sm font-bold text-slate-500 transition hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-300">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('star'); ?></span>
                                Avis
                            </a>
                        </nav>

                        <div class="space-y-8 p-4 sm:p-6 lg:p-8">
                            <section id="preview-overview" class="course-preview-anchor space-y-6">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-primary-600 dark:text-primary-300"><?php echo e($activeChapterTitle); ?></p>
                                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 dark:text-white"><?php echo e($activeLessonTitle); ?></h2>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($lessonTypeIcon($activeLessonType)); ?></span>
                                            <?php echo e($activeLessonType !== '' ? ($lessonTypeLabels[$activeLessonType] ?? ucfirst($activeLessonType)) : 'Contenu'); ?>
                                        </span>
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                                            <?php echo e($activeLessonDuration); ?>
                                        </span>
                                    </div>
                                    <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">
                                        <?php echo e($activeLessonDescription !== '' ? $activeLessonDescription : ($courseDescription !== '' ? $courseDescription : "Aucune description n'est encore disponible pour cette leçon.")); ?>
                                    </p>
                                </div>

                                <div class="rounded-[1.5rem] border border-primary-100 bg-primary-50 p-5 dark:border-primary-500/20 dark:bg-primary-500/10">
                                    <h3 class="flex items-center gap-2 text-base font-black text-primary-900 dark:text-primary-100">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                                        Objectifs du cours
                                    </h3>
                                    <div class="mt-4 text-sm leading-7 text-slate-700 dark:text-slate-200">
                                        <?php if ($objectivesHtml !== ''): ?>
                                            <?php echo $objectivesHtml; ?>
                                        <?php else: ?>
                                            <p>Ajoutez les objectifs de fin de cours.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <article class="rounded-[1.35rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/45">
                                        <h3 class="flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white">
                                            <span class="app-icon text-amber-500" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>
                                            Prérequis
                                        </h3>
                                        <div class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                            <?php echo $prerequisitesHtml !== '' ? $prerequisitesHtml : '<p>Aucun prérequis indiqué.</p>'; ?>
                                        </div>
                                    </article>
                                    <article class="rounded-[1.35rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/45">
                                        <h3 class="flex items-center gap-2 text-sm font-black text-slate-900 dark:text-white">
                                            <span class="app-icon text-teal-500" aria-hidden="true"><?php echo dashboard_icon_svg('settings'); ?></span>
                                            Équipement
                                        </h3>
                                        <div class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                            <?php echo $equipmentHtml !== '' ? $equipmentHtml : '<p>Aucun équipement spécifique indiqué.</p>'; ?>
                                        </div>
                                    </article>
                                </div>

                                <?php if ($generalResourceUrl !== ''): ?>
                                    <a href="<?php echo e($generalResourceUrl); ?>" target="_blank" rel="noopener noreferrer" class="course-preview-lift flex items-center justify-between gap-4 rounded-[1.35rem] border border-slate-200 bg-white p-4 text-sm font-bold text-slate-700 shadow-sm transition dark:border-slate-800 dark:bg-slate-950/45 dark:text-slate-200">
                                        <span class="inline-flex items-center gap-3">
                                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-200">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('download'); ?></span>
                                            </span>
                                            Ressource générale du cours
                                        </span>
                                        <span class="app-icon text-slate-400" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                    </a>
                                <?php endif; ?>
                            </section>

                            <section id="preview-resources" class="course-preview-anchor space-y-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Ressources</p>
                                        <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Documents et supports</h2>
                                    </div>
                                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        <?php echo e((string) $resourceCount); ?> fichier(s)
                                    </span>
                                </div>

                                <?php if ($resourceCount <= 0 && $generalResourceUrl === ''): ?>
                                    <div class="rounded-[1.35rem] border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/45 dark:text-slate-400">
                                        Aucun document n'est encore attaché au cours.
                                    </div>
                                <?php else: ?>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <?php foreach ($docsByChapter as $chapterId => $chapterDocs): ?>
                                            <?php foreach ((array) $chapterDocs as $doc): ?>
                                                <?php
                                                $docUrl = $resolveUrl((string) ($doc['file_path'] ?? ''));
                                                $docType = (string) ($doc['type'] ?? 'resource');
                                                ?>
                                                <a href="<?php echo e($docUrl); ?>" target="_blank" rel="noopener noreferrer" class="course-preview-lift rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm transition dark:border-slate-800 dark:bg-slate-950/45">
                                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400"><?php echo e($chapterById[(int) $chapterId]['title'] ?? 'Module'); ?></p>
                                                    <p class="mt-2 line-clamp-2 text-sm font-black text-slate-900 dark:text-white"><?php echo e((string) ($doc['title'] ?? 'Document')); ?></p>
                                                    <p class="mt-2 flex items-center gap-2 text-xs font-bold text-primary-600 dark:text-primary-300">
                                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($docType === 'exercise' ? 'clipboard-list' : 'file-text'); ?></span>
                                                        <?php echo e($docLabel($docType, (string) ($doc['mime'] ?? ''))); ?>
                                                    </p>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>

                                        <?php foreach ($docsByLesson as $lessonId => $lessonDocs): ?>
                                            <?php foreach ((array) $lessonDocs as $doc): ?>
                                                <?php
                                                $docUrl = $resolveUrl((string) ($doc['file_path'] ?? ''));
                                                $docType = (string) ($doc['type'] ?? 'resource');
                                                ?>
                                                <a href="<?php echo e($docUrl); ?>" target="_blank" rel="noopener noreferrer" class="course-preview-lift rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm transition dark:border-slate-800 dark:bg-slate-950/45">
                                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Leçon #<?php echo e((string) ((int) $lessonId)); ?></p>
                                                    <p class="mt-2 line-clamp-2 text-sm font-black text-slate-900 dark:text-white"><?php echo e((string) ($doc['title'] ?? 'Document')); ?></p>
                                                    <p class="mt-2 flex items-center gap-2 text-xs font-bold text-primary-600 dark:text-primary-300">
                                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($docType === 'exercise' ? 'clipboard-list' : 'file-text'); ?></span>
                                                        <?php echo e($docLabel($docType, (string) ($doc['mime'] ?? ''))); ?>
                                                    </p>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>

                            <section id="preview-notes" class="course-preview-anchor space-y-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Évaluations</p>
                                        <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Examens et rendez-vous</h2>
                                    </div>
                                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        <?php echo e((string) $examCount); ?> examen(s)
                                    </span>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-3">
                                        <?php if ($exams === []): ?>
                                            <div class="rounded-[1.25rem] border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/45 dark:text-slate-400">
                                                Aucun examen n'est encore configuré.
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($exams as $exam): ?>
                                                <article class="rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/45">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-600 dark:text-emerald-300"><?php echo e($formatExamType($exam['type'] ?? null)); ?></p>
                                                            <h3 class="mt-1 text-sm font-black text-slate-900 dark:text-white"><?php echo e((string) ($exam['title'] ?? 'Examen')); ?></h3>
                                                        </div>
                                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">
                                                            <?php echo e($formatShortDuration($exam['duration_min'] ?? null)); ?>
                                                        </span>
                                                    </div>
                                                    <?php if (!empty($exam['description'])): ?>
                                                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e((string) $exam['description']); ?></p>
                                                    <?php endif; ?>
                                                </article>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="space-y-3">
                                        <?php if ($meetings === []): ?>
                                            <div class="rounded-[1.25rem] border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/45 dark:text-slate-400">
                                                Aucun rendez-vous de cours n'est encore prévu.
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($meetings as $meeting): ?>
                                                <?php $meetingMode = (string) ($meeting['mode'] ?? 'online'); ?>
                                                <article class="rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/45">
                                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600 dark:text-blue-300">
                                                        <?php echo $meetingMode === 'onsite' ? 'Présentiel' : 'En ligne'; ?>
                                                    </p>
                                                    <h3 class="mt-1 text-sm font-black text-slate-900 dark:text-white"><?php echo e((string) ($meeting['title'] ?? 'Rendez-vous')); ?></h3>
                                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400"><?php echo e($formatDate((string) ($meeting['start_at'] ?? '')) ?: 'Date à préciser'); ?></p>
                                                </article>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>

                            <?php require __DIR__ . '/_course_preview_reviews.php'; ?>
                        </div>
                    </div>

                    <aside class="course-preview-plan-scroll space-y-4 xl:sticky xl:top-24 xl:self-start">
                        <article class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <?php if ($coverUrl !== ''): ?>
                                <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" class="h-40 w-full object-cover">
                            <?php else: ?>
                                <div class="flex h-40 items-center justify-center bg-gradient-to-br from-primary-600 to-cyan-500 text-white">
                                    <span class="app-icon h-12 w-12" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="p-5">
                                <h2 class="text-lg font-black leading-tight text-slate-950 dark:text-white"><?php echo e($courseTitle); ?></h2>
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?php echo e($teacherName); ?><?php echo $categoryName !== '' ? ' · ' . e($categoryName) : ''; ?></p>
                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/55">
                                        <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400">Durée</p>
                                        <p class="mt-1 text-sm font-black text-slate-900 dark:text-white"><?php echo e($durationLabel ?: 'Non précisée'); ?></p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/55">
                                        <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400">Prix</p>
                                        <p class="mt-1 text-sm font-black text-slate-900 dark:text-white"><?php echo e($formatMoney($coursePrice)); ?></p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="mb-1 flex justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
                                        <span>Progression simulée</span>
                                        <span>0%</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                        <div class="h-full w-0 rounded-full bg-primary-600"></div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Plan</p>
                                    <h2 class="mt-1 text-lg font-black text-slate-950 dark:text-white">Modules du cours</h2>
                                </div>
                                <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-700 dark:bg-primary-500/10 dark:text-primary-200">
                                    <?php echo e((string) $chapterCount); ?>
                                </span>
                            </div>

                            <?php if ($chapters === []): ?>
                                <div class="mt-4 rounded-[1.25rem] border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/45 dark:text-slate-400">
                                    Aucun module n'est encore disponible.
                                </div>
                            <?php else: ?>
                                <div class="mt-4 space-y-3">
                                    <?php foreach ($chapters as $chapterIndex => $chapter): ?>
                                        <?php
                                        $chapterId = (int) ($chapter['id'] ?? 0);
                                        $chapterLessons = is_array($lessonsByChapter[$chapterId] ?? null) ? $lessonsByChapter[$chapterId] : [];
                                        $chapterDocs = is_array($docsByChapter[$chapterId] ?? null) ? $docsByChapter[$chapterId] : [];
                                        $chapterMeetings = is_array($meetingsByChapter[$chapterId] ?? null) ? $meetingsByChapter[$chapterId] : [];
                                        $chapterMinutes = $sumLessonMinutes($chapterLessons);
                                        ?>
                                        <details class="course-preview-module overflow-hidden rounded-[1.25rem] border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/45" <?php echo $chapterIndex === 0 ? 'open' : ''; ?>>
                                            <summary class="flex cursor-pointer list-none items-center gap-3 p-3">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-primary-100 text-sm font-black text-primary-700 dark:bg-primary-500/15 dark:text-primary-200"><?php echo e((string) ($chapterIndex + 1)); ?></span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-black text-slate-900 dark:text-white"><?php echo e((string) ($chapter['title'] ?? 'Module')); ?></span>
                                                    <span class="mt-0.5 block text-xs font-semibold text-slate-500 dark:text-slate-400">
                                                        <?php echo e((string) count($chapterLessons)); ?> leçon(s)
                                                        <?php if ($chapterMinutes > 0): ?> · <?php echo e($formatShortDuration($chapterMinutes)); ?><?php endif; ?>
                                                    </span>
                                                </span>
                                                <span class="course-preview-chevron app-icon text-slate-400" aria-hidden="true"><?php echo dashboard_icon_svg('chevron-down'); ?></span>
                                            </summary>
                                            <div class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/70">
                                                <?php if ($chapterLessons === [] && $chapterDocs === [] && $chapterMeetings === []): ?>
                                                    <p class="p-3 text-sm text-slate-500 dark:text-slate-400">Module vide.</p>
                                                <?php endif; ?>

                                                <?php foreach ($chapterLessons as $lesson): ?>
                                                    <?php
                                                    $lessonType = (string) ($lesson['lesson_type'] ?? 'video');
                                                    $lessonId = (int) ($lesson['id'] ?? 0);
                                                    $isActiveLesson = $activeLesson !== null && (int) ($activeLesson['id'] ?? 0) === $lessonId;
                                                    ?>
                                                    <div class="flex items-center gap-3 border-b border-slate-100 p-3 last:border-b-0 dark:border-slate-800 <?php echo $isActiveLesson ? 'bg-primary-50/80 dark:bg-primary-500/10' : ''; ?>">
                                                        <span class="app-icon shrink-0 <?php echo $isActiveLesson ? 'text-primary-600 dark:text-primary-300' : 'text-slate-400'; ?>" aria-hidden="true"><?php echo dashboard_icon_svg($lessonTypeIcon($lessonType)); ?></span>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="truncate text-sm font-bold <?php echo $isActiveLesson ? 'text-primary-700 dark:text-primary-200' : 'text-slate-700 dark:text-slate-200'; ?>"><?php echo e((string) ($lesson['title'] ?? 'Leçon')); ?></p>
                                                            <p class="mt-0.5 text-xs text-slate-400"><?php echo e($lessonTypeLabels[$lessonType] ?? ucfirst($lessonType)); ?></p>
                                                        </div>
                                                        <span class="text-xs font-bold text-slate-400"><?php echo e($formatShortDuration($lesson['duration_estimate_min'] ?? null)); ?></span>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php foreach ($chapterDocs as $doc): ?>
                                                    <div class="flex items-center gap-3 border-b border-slate-100 p-3 last:border-b-0 dark:border-slate-800">
                                                        <span class="app-icon shrink-0 text-emerald-500" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="truncate text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo e((string) ($doc['title'] ?? 'Document')); ?></p>
                                                            <p class="mt-0.5 text-xs text-slate-400"><?php echo e($docLabel((string) ($doc['type'] ?? 'resource'), (string) ($doc['mime'] ?? ''))); ?></p>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>

                                                <?php foreach ($chapterMeetings as $meeting): ?>
                                                    <div class="flex items-center gap-3 border-b border-slate-100 p-3 last:border-b-0 dark:border-slate-800">
                                                        <span class="app-icon shrink-0 text-blue-500" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="truncate text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo e((string) ($meeting['title'] ?? 'Rendez-vous')); ?></p>
                                                            <p class="mt-0.5 text-xs text-slate-400"><?php echo e($formatDate((string) ($meeting['start_at'] ?? '')) ?: 'Date à préciser'); ?></p>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>

                        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Synthèse</p>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/55">
                                    <p class="text-2xl font-black text-slate-950 dark:text-white"><?php echo e((string) $lessonCount); ?></p>
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Leçons</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/55">
                                    <p class="text-2xl font-black text-slate-950 dark:text-white"><?php echo e((string) $videoCount); ?></p>
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Vidéos</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/55">
                                    <p class="text-2xl font-black text-slate-950 dark:text-white"><?php echo e((string) $resourceCount); ?></p>
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Ressources</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/55">
                                    <p class="text-2xl font-black text-slate-950 dark:text-white"><?php echo e((string) $examCount); ?></p>
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Examens</p>
                                </div>
                            </div>
                        </article>
                    </aside>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
