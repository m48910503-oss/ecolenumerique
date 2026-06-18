<?php
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$csp = trim((string) ($csp ?? ''));
$course = is_array($course ?? null) ? $course : [];
$chapters = is_array($chapters ?? null) ? $chapters : [];
$lessonsByChapter = is_array($lessonsByChapter ?? null) ? $lessonsByChapter : [];
$docsByChapter = is_array($docsByChapter ?? null) ? $docsByChapter : [];
$docsByLesson = is_array($docsByLesson ?? null) ? $docsByLesson : [];
$meetingsByChapter = is_array($meetingsByChapter ?? null) ? $meetingsByChapter : [];
$myMeetingsByKey = is_array($myMeetingsByKey ?? null) ? $myMeetingsByKey : [];
$meetingErrors = is_array($meetingErrors ?? null) ? $meetingErrors : [];
$meetingSuccess = trim((string) ($meetingSuccess ?? ''));
$currentUser = is_array($currentUser ?? null) ? $currentUser : [];
$isEnrolled = !empty($isEnrolled);
$userRating = is_array($userRating ?? null) ? $userRating : null;
$doneByLesson = is_array($doneByLesson ?? null) ? $doneByLesson : [];
$progressPercent = max(0, (int) ($progressPercent ?? 0));
$totalLessons = max(0, (int) ($totalLessons ?? 0));
$firstVideoId = isset($firstVideoId) && $firstVideoId !== null ? (int) $firstVideoId : null;
$firstEmbedUrl = trim((string) ($firstEmbedUrl ?? ''));
$firstEmbedTitle = trim((string) ($firstEmbedTitle ?? ''));
$videoPlaylist = is_array($videoPlaylist ?? null) ? $videoPlaylist : [];
$doneCount = max(0, (int) ($doneCount ?? 0));
$totalLessonsAll = max(0, (int) ($totalLessonsAll ?? 0));
if ($totalLessons > 0) {
    $doneCount = min($doneCount, $totalLessons);
} else {
    $doneCount = 0;
}
$progressPercent = max(0, min(100, $progressPercent));
$objectivesList = is_array($objectivesList ?? null) ? $objectivesList : [];
$prerequisitesList = is_array($prerequisitesList ?? null) ? $prerequisitesList : [];
$equipmentList = is_array($equipmentList ?? null) ? $equipmentList : [];
$durationLabel = isset($durationLabel) && trim((string) $durationLabel) !== '' ? (string) $durationLabel : 'Non définie';
$courseTitle = trim((string) ($course['title'] ?? 'Cours'));
$courseDescription = trim((string) ($course['description'] ?? ''));
$teacherFirstName = trim((string) ($course['first_name'] ?? ''));
$teacherLastName = trim((string) ($course['last_name'] ?? ''));
$teacherFullName = trim($teacherFirstName . ' ' . $teacherLastName);
if ($teacherFullName === '') {
    $teacherFullName = 'Formateur';
}
$teacherEmail = trim((string) ($course['email'] ?? ''));
$courseLevel = trim((string) ($course['level'] ?? ''));
$levelLabels = [
    'beginner' => 'Débutant',
    'intermediate' => 'Intermédiaire',
    'advanced' => 'Avancé',
];
$levelLabel = $courseLevel !== '' && isset($levelLabels[$courseLevel])
    ? $levelLabels[$courseLevel]
    : ($courseLevel !== '' ? ucfirst(str_replace(['-', '_'], ' ', $courseLevel)) : 'Niveau libre');
$coverUrl = static function ($path) : string {
    $value = trim((string) $path);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '//') || str_starts_with($value, 'data:')) {
        return $value;
    }
    return site_url(ltrim($value, '/'));
};
$courseCoverUrl = $coverUrl($course['cover_path'] ?? '');
$courseCoverAlt = trim((string) ($course['cover_alt'] ?? $courseTitle));
if ($courseCoverAlt === '') {
    $courseCoverAlt = $courseTitle;
}

$watchUser = $currentUser;
$watchDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($watchUser, 'Utilisateur')
    : trim(((string) ($watchUser['first_name'] ?? '')) . ' ' . ((string) ($watchUser['last_name'] ?? '')));
if ($watchDisplayName === '') {
    $watchDisplayName = 'Utilisateur';
}
$watchInitials = function_exists('dashboard_user_initials')
    ? dashboard_user_initials($watchUser, 'EN')
    : strtoupper(substr((string) ($watchUser['first_name'] ?? 'E'), 0, 1) . substr((string) ($watchUser['last_name'] ?? 'N'), 0, 1));
$watchAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($watchUser) : '';

$slug = trim((string) ($course['slug'] ?? ''));
$courseDetailsUrl = $slug !== ''
    ? site_url('course?slug=' . urlencode($slug))
    : site_url('course?id=' . (int) ($course['id'] ?? 0));
$dashboardUrl = site_url('student/dashboard');
$myCoursesUrl = site_url('student/my_courses');

$buildLessonLabel = static function (?array $item): string {
    $item = is_array($item) ? $item : [];
    if ($item === []) {
        return 'Aucune leçon chargée';
    }

    $parts = [];
    if (!empty($item['chapter_pos'])) {
        $parts[] = 'Chapitre ' . $item['chapter_pos'];
    }
    if (!empty($item['lesson_pos'])) {
        $parts[] = 'Leçon ' . $item['lesson_pos'];
    }
    $prefix = $parts === [] ? '' : implode(' • ', $parts) . ' • ';
    return $prefix . trim((string) ($item['title'] ?? 'Leçon'));
};

$resolveDocumentUrl = static function (array $document) : string {
    $value = trim((string) ($document['url'] ?? $document['path'] ?? $document['file_path'] ?? $document['link'] ?? ''));
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '//') || str_starts_with($value, 'data:')) {
        return $value;
    }
    if (preg_match('#^(?:drive|docs)\.google\.com/#i', $value) === 1 || preg_match('#^googleusercontent\.com/#i', $value) === 1) {
        return 'https://' . ltrim($value, '/');
    }
    return site_url(ltrim($value, '/'));
};

$isGenericResourceLabel = static function (string $label): bool {
    $normalized = strtolower(trim($label));
    if ($normalized === '') {
        return true;
    }

    return in_array($normalized, ['ressource', 'document', 'fichier', 'support', 'google drive', 'rien', 'aucun', 'aucune', 'none', 'null', 'n/a', 'sans titre'], true);
};

$normalizeDocuments = static function (array $documents, string $contextLabel = '') use ($resolveDocumentUrl, $isGenericResourceLabel): array {
    $items = [];
    $seen = [];
    foreach ($documents as $document) {
        if (!is_array($document)) {
            continue;
        }
        $url = $resolveDocumentUrl($document);
        if ($url === '' || isset($seen[$url])) {
            continue;
        }
        $seen[$url] = true;
        $isGoogleDrive = preg_match('#(?:drive|docs)\.google\.com/#i', $url) === 1;
        $label = trim((string) ($document['label'] ?? ''));
        $title = trim((string) ($document['title'] ?? ''));
        if ($label === '' || $isGenericResourceLabel($label)) {
            if ($title !== '' && !$isGenericResourceLabel($title)) {
                $label = $title;
            } elseif ($contextLabel !== '') {
                $position = trim((string) ($document['position'] ?? ''));
                $label = $position !== '' ? $contextLabel . ' (' . $position . ')' : $contextLabel;
            } elseif ($title !== '') {
                $label = $title;
            } else {
                $label = 'Ressource';
            }
        }
        $items[] = [
            'label' => $label,
            'kind' => $isGoogleDrive ? 'Google Drive' : trim((string) ($document['type'] ?? 'Document')),
            'url' => $url,
            'icon' => preg_match('#\\.pdf($|[?#])#i', $url) === 1
                ? 'file-pdf'
                : (
                    stripos((string) ($document['label'] ?? ''), 'github') !== false
                    || stripos((string) ($document['kind'] ?? ''), 'code') !== false
                    || stripos((string) ($document['type'] ?? ''), 'code') !== false
                    ? 'code'
                    : 'link'
                ),
        ];
    }
    return $items;
};

$currentQueueItem = is_array($videoPlaylist[0] ?? null) ? $videoPlaylist[0] : null;
$currentLessonId = $firstVideoId ?: (int) ($currentQueueItem['id'] ?? 0);
$currentLessonDone = $currentLessonId > 0 && in_array($currentLessonId, $doneByLesson, true);
$currentLessonTitle = trim((string) ($currentQueueItem['title'] ?? $firstEmbedTitle));
$currentLessonLabel = $buildLessonLabel($currentQueueItem);
$chapterCount = count($chapters);
$videoCount = count($videoPlaylist);
$playlistByChapter = [];
foreach ($videoPlaylist as $video) {
    if (!is_array($video)) {
        continue;
    }
    $chapterId = (int) ($video['chapter_id'] ?? 0);
    $playlistByChapter[$chapterId][] = $video;
}
$progressSummary = $totalLessons > 0 ? $doneCount . ' / ' . $totalLessons : '0 / 0';
$isCourseCompleted = $totalLessons > 0 && $doneCount >= $totalLessons;
$courseProgressStateLabel = $isCourseCompleted ? 'Cours terminé' : 'En cours';
$courseProgressStateClass = $isCourseCompleted
    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/25 dark:text-emerald-200'
    : 'border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-900/50 dark:bg-primary-900/25 dark:text-primary-200';
$courseStatusLabel = $isEnrolled ? 'Accès complet' : 'Accès limité';
$watchRatingValue = $userRating ? max(0, min(5, (int) ($userRating['rating'] ?? 0))) : 0;
$watchRatingComment = $userRating ? trim((string) ($userRating['comment'] ?? '')) : '';
$watchRatingLabel = $watchRatingValue > 0 ? "Modifier l'avis" : 'Laisser une note';
$courseUpdatedLabel = function_exists('view_format_date_label')
    ? view_format_date_label($course['updated_at'] ?? ($course['created_at'] ?? null))
    : '';
$courseUpdatedDisplay = $courseUpdatedLabel !== '' ? $courseUpdatedLabel : 'Non défini';

$chapterResourcesMap = [];
$lessonResourcesMap = [];
foreach ($chapters as $chapter) {
    if (!is_array($chapter)) {
        continue;
    }
    $chapterId = (int) ($chapter['id'] ?? 0);
    if ($chapterId < 1) {
        continue;
    }
    $chapterDocs = is_array($docsByChapter[$chapterId] ?? null) ? $docsByChapter[$chapterId] : [];
    $chapterTitle = trim((string) ($chapter['title'] ?? 'Chapitre'));
    $chapterResourceLabel = $chapterTitle !== '' ? 'Support du chapitre ' . $chapterTitle : 'Support du chapitre';
    $chapterResources = $normalizeDocuments($chapterDocs, $chapterResourceLabel);
    $chapterResourcesMap[(string) $chapterId] = $chapterResources;
    foreach (($lessonsByChapter[$chapterId] ?? []) as $lesson) {
        if (!is_array($lesson)) {
            continue;
        }
        $lessonId = (int) ($lesson['id'] ?? 0);
        if ($lessonId < 1) {
            continue;
        }
        $lessonDocs = is_array($docsByLesson[$lessonId] ?? null) ? $docsByLesson[$lessonId] : [];
        $lessonTitle = trim((string) ($lesson['title'] ?? 'Leçon'));
        $lessonResourceLabel = $lessonTitle !== '' ? 'Support - ' . $lessonTitle : 'Support de la leçon';
        $lessonPath = trim((string) ($lesson['document_path'] ?? ''));
        $lessonResources = $normalizeDocuments($lessonDocs, $lessonResourceLabel);
        if ($lessonPath !== '') {
            $lessonResources[] = [
                'label' => $lessonResourceLabel,
                'type' => 'Document',
                'path' => $lessonPath,
            ];
        }
        $lessonResourcesMap[(string) $lessonId] = $normalizeDocuments(array_merge($chapterResources, $lessonResources));
    }
}
$activeLessonResources = is_array($lessonResourcesMap[(string) $currentLessonId] ?? null) ? $lessonResourcesMap[(string) $currentLessonId] : [];
$activeChapterId = 0;
foreach ($videoPlaylist as $video) {
    if (!is_array($video)) {
        continue;
    }
    if ((int) ($video['id'] ?? 0) === $currentLessonId) {
        $activeChapterId = (int) ($video['chapter_id'] ?? 0);
        break;
    }
}
if ($activeChapterId < 1 && $chapters !== []) {
    foreach ($chapters as $chapter) {
        if (!is_array($chapter)) {
            continue;
        }
        $chapterId = (int) ($chapter['id'] ?? 0);
        if ($chapterId > 0 && !empty($playlistByChapter[$chapterId])) {
            $activeChapterId = $chapterId;
            break;
        }
    }
}
$lessonResourcesJson = json_encode(
    $lessonResourcesMap,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($lessonResourcesJson) || $lessonResourcesJson === '') {
    $lessonResourcesJson = '{}';
}

if ($csp !== '' && !headers_sent()) {
    header('Content-Security-Policy: ' . $csp);
}

$courseDescriptionDisplay = $courseDescription !== ''
    ? $courseDescription
    : "Cette page de lecture rassemble vos vidéos, vos notes et les ressources liées à votre inscription.";
$overviewPoints = $objectivesList !== []
    ? $objectivesList
    : [
        'Suivre les vidéos dans l’ordre du parcours.',
        'Marquer chaque leçon vue pour faire avancer votre progression.',
        'Prendre des notes locales et exporter les points clés.',
        'Retrouver rapidement les documents associés à la leçon active.',
    ];

$courseSummaryItems = [
    [
        'label' => 'Progression',
        'value' => (string) $progressPercent . '%',
        'note' => $progressSummary . ' leçons vues',
    ],
    [
        'label' => 'Vidéos',
        'value' => (string) $videoCount,
        'note' => $courseStatusLabel,
    ],
    [
        'label' => 'Chapitres',
        'value' => (string) $chapterCount,
        'note' => $courseUpdatedDisplay,
    ],
    [
        'label' => 'Durée',
        'value' => $durationLabel,
        'note' => $teacherFullName,
    ],
];

$meetingCount = 0;
foreach ($meetingsByChapter as $meetingGroup) {
    if (is_array($meetingGroup)) {
        $meetingCount += count($meetingGroup);
    }
}

$activeChapterResources = [];
if ($activeChapterId > 0 && isset($chapterResourcesMap[(string) $activeChapterId])) {
    $activeChapterResources = $chapterResourcesMap[(string) $activeChapterId];
}
if ($activeChapterResources === [] && $chapterResourcesMap !== []) {
    foreach ($chapterResourcesMap as $resourcesForChapter) {
        if ($resourcesForChapter !== []) {
            $activeChapterResources = $resourcesForChapter;
            break;
        }
    }
}
$sidebarResources = $activeLessonResources !== [] ? $activeLessonResources : $activeChapterResources;
$activeLessonResources = $sidebarResources;
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
  <?php if ($csp !== ''): ?><meta http-equiv="Content-Security-Policy" content="<?php echo e($csp); ?>"><?php endif; ?>
  <title><?php echo e($courseTitle); ?> · Lecture · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
  <script src="https://www.youtube.com/iframe_api" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_progress.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_notes.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_player.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_player.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/fullscreen_guard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/fullscreen_guard.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_player_guard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_player_guard.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_watch.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_watch.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_rating.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_rating.js') ?: time()))); ?>" defer></script>
  <style>
    .video-wrapper {
      aspect-ratio: 16 / 9;
    }

    .watch-tab-scroll::-webkit-scrollbar {
      height: 6px;
    }

    .watch-tab-scroll::-webkit-scrollbar-thumb {
      background: rgba(148, 163, 184, 0.65);
      border-radius: 999px;
    }

    .dark .watch-tab-scroll::-webkit-scrollbar-thumb {
      background: rgba(75, 85, 99, 0.95);
    }

    .course-watch-details[open] .course-watch-chevron {
      transform: rotate(180deg);
    }

    .notes-widget[open] .notes-widget-chevron {
      transform: rotate(180deg);
    }

    .notes-widget > summary {
      list-style: none;
    }

    .notes-widget > summary::-webkit-details-marker {
      display: none;
    }

    .course-watch-resource-list::-webkit-scrollbar {
      width: 6px;
    }

    .course-watch-resource-list::-webkit-scrollbar-thumb {
      background: rgba(148, 163, 184, 0.65);
      border-radius: 999px;
    }

    .dark .course-watch-resource-list::-webkit-scrollbar-thumb {
      background: rgba(75, 85, 99, 0.95);
    }

    .queue-item {
      transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .queue-item.active {
      background: rgba(239, 246, 255, 0.55);
      border-left: 4px solid #2563eb;
      color: #1d4ed8;
    }

    .dark .queue-item.active {
      background: rgba(30, 58, 138, 0.14);
      border-left-color: #60a5fa;
      color: #bfdbfe;
    }
  </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body
  data-view="web/course_watch"
  data-base-path="<?php echo e(defined('BASE_PATH') ? rtrim((string) BASE_PATH, '/') : ''); ?>"
  data-course-id="<?php echo e((string) ($course['id'] ?? '')); ?>"
  data-total-lessons="<?php echo e((string) $totalLessons); ?>"
  data-done-lessons="<?php echo e((string) $doneCount); ?>"
  data-base-progress="<?php echo e((string) $progressPercent); ?>"
  data-current-lesson-id="<?php echo e((string) $currentLessonId); ?>"
  data-course-title="<?php echo e($courseTitle); ?>"
  data-course-completed="<?php echo $isCourseCompleted ? '1' : '0'; ?>"
  data-rating-api-url="<?php echo e(site_url('api/rating')); ?>"
  data-rating-auto-prompts="1"
  data-rating-value="<?php echo e((string) $watchRatingValue); ?>"
  data-rating-comment="<?php echo e($watchRatingComment); ?>"
  class="course-watch-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 min-h-screen overflow-hidden overflow-x-hidden"
>
  <div class="flex h-screen min-h-0 flex-col overflow-hidden">
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur-xl shadow-xs dark:border-gray-700 dark:bg-gray-800/90">
      <div class="flex min-w-0 items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-4">
          <a href="<?php echo e($courseDetailsUrl); ?>" class="flex items-center gap-2 text-gray-700 transition hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-300">
            <i class="fas fa-arrow-left"></i>
            <span class="hidden sm:inline text-sm font-medium">Retour au cours</span>
          </a>
          <div class="min-w-0">
            <h1 class="truncate text-base font-semibold text-gray-900 dark:text-white max-w-md"><?php echo e($courseTitle); ?></h1>
            <p class="truncate text-xs text-gray-500 dark:text-gray-400"><?php echo e($currentLessonLabel !== '' ? $currentLessonLabel : 'Leçon active'); ?></p>
          </div>
        </div>

        <div class="flex min-w-0 shrink-0 items-center justify-end gap-1.5 sm:gap-2">
          <div class="hidden min-w-0 items-center gap-2 lg:flex">
            <span class="hidden text-xs text-gray-500 dark:text-gray-400 2xl:inline">Progression du cours</span>
            <div class="h-1.5 w-16 shrink-0 rounded-full bg-gray-200 dark:bg-gray-700 xl:w-24">
              <div id="progress-bar" class="h-1.5 rounded-full bg-primary-600 transition-all duration-500" style="width: <?php echo e((string) $progressPercent); ?>%"></div>
            </div>
            <span id="progress-percent" class="whitespace-nowrap text-xs font-medium text-gray-700 dark:text-gray-200"><?php echo e((string) $progressPercent); ?>% · <?php echo e($progressSummary); ?></span>
            <span id="course-completion-badge" class="<?php echo $isCourseCompleted ? '' : 'hidden '; ?>inline-flex shrink-0 items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/25 dark:text-emerald-200 xl:px-2.5">
              <i class="fas fa-check" aria-hidden="true"></i>
              <span class="hidden xl:inline">Cours terminé</span>
              <span class="xl:hidden">Terminé</span>
            </span>
          </div>
          <button type="button" data-watch-tab-target="notes" class="shrink-0 p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="Prendre des notes" title="Prendre des notes">
            <i class="far fa-sticky-note"></i>
          </button>
          <button type="button" data-course-rating-open data-rating-source="course_watch_player" data-course-id="<?php echo e((string) ($course['id'] ?? '')); ?>" data-title="<?php echo e($courseTitle); ?>" data-rating-value="<?php echo e((string) $watchRatingValue); ?>" data-rating-comment="<?php echo e($watchRatingComment); ?>" class="inline-flex shrink-0 items-center gap-2 rounded-lg p-2 <?php echo $watchRatingValue > 0 ? 'text-amber-500' : 'text-gray-600 dark:text-gray-400'; ?> transition hover:bg-gray-100 hover:text-amber-500 dark:hover:bg-gray-700" aria-label="<?php echo e($watchRatingLabel); ?>" title="<?php echo e($watchRatingLabel); ?>">
            <i class="fas fa-star"></i>
            <span class="hidden text-xs font-semibold 2xl:inline">Avis</span>
            <span class="sr-only" data-rating-trigger-label><?php echo e($watchRatingLabel); ?></span>
          </button>
          <button id="btn-mark" type="button" data-lesson-id="<?php echo e($currentLessonId > 0 ? (string) $currentLessonId : ''); ?>" data-done="<?php echo $currentLessonDone ? '1' : '0'; ?>" class="shrink-0 p-2 rounded-lg <?php echo $currentLessonDone ? 'text-emerald-600 dark:text-emerald-300' : 'text-gray-600 dark:text-gray-400'; ?> hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="<?php echo $currentLessonDone ? 'Marquée comme vue' : 'Marquer comme vue'; ?>" title="<?php echo $currentLessonDone ? 'Marquée comme vue' : 'Marquer comme vue'; ?>">
            <i class="far fa-check-circle"></i>
            <span class="sr-only" data-mark-label><?php echo $currentLessonDone ? 'Marquée comme vue' : 'Marquer comme vue'; ?></span>
          </button>
          <button type="button" data-web-theme-toggle class="shrink-0 p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="Changer le thème">
            <i class="fas fa-moon" data-theme-icon-dark aria-hidden="true"></i>
            <i class="fas fa-sun hidden" data-theme-icon-light aria-hidden="true"></i>
            <span class="sr-only">Changer le thème</span>
          </button>
          <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-white text-xs font-bold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
            <?php if ($watchAvatarUrl !== ''): ?>
              <img src="<?php echo e($watchAvatarUrl); ?>" alt="<?php echo e($watchDisplayName); ?>" class="h-full w-full object-cover">
            <?php else: ?>
              <?php echo e($watchInitials); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </header>

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden lg:flex-row">
      <main class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto bg-gray-100 dark:bg-gray-900" data-scroll-top-root="1">
        <section class="bg-black">
          <div class="relative mx-auto w-full">
            <?php if ($isEnrolled && $firstEmbedUrl !== ''): ?>
              <div id="player-wrapper" class="video-wrapper group/player relative overflow-hidden bg-black" data-player-wrapper oncontextmenu="return false">
                <div id="main-player-card" data-current-lesson-id="<?php echo e((string) $currentLessonId); ?>" class="sr-only">
                  <p id="current-lesson-meta"><?php echo e($currentLessonLabel !== '' ? $currentLessonLabel : 'Leçon active'); ?></p>
                  <p id="player-title"><?php echo e($currentLessonTitle !== '' ? $currentLessonTitle : $courseTitle); ?></p>
                  <span id="progress-counter"><?php echo e($progressSummary); ?></span>
                  <span id="player-speed-status" data-player-speed-status>Vitesse active : 1.00x</span>
                  <span id="progress-label"><?php echo e((string) $progressPercent); ?>% complété</span>
                  <span id="remaining-label">Temps restant : --</span>
                </div>
                <iframe id="main-player" src="<?php echo e($firstEmbedUrl); ?>" allow="autoplay; encrypted-media; picture-in-picture" title="Lecteur vidéo principal" class="pointer-events-none h-full w-full" oncontextmenu="return false"></iframe>
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/25 via-transparent to-transparent" aria-hidden="true"></div>
                <button id="player-click-layer" type="button" class="absolute inset-0 z-10 cursor-pointer bg-transparent" aria-label="Lecture ou pause" oncontextmenu="return false"></button>
                <div id="play-overlay" class="absolute inset-0 z-20 flex items-center justify-center transition-opacity duration-200">
                  <button id="btn-play" type="button" data-player-toggle class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary-600/95 text-white shadow-[0_18px_40px_-12px_rgba(37,99,235,0.8)] transition hover:scale-105 hover:bg-primary-700" aria-label="Lecture">
                    <i class="fas fa-play ml-1 text-2xl" data-play-icon aria-hidden="true"></i>
                    <span class="sr-only" data-control-label>Lecture</span>
                  </button>
                </div>
                <div id="player-controls" class="absolute inset-x-0 bottom-0 z-30 bg-gradient-to-t from-black/85 via-black/45 to-transparent px-3 pb-3 pt-16 text-white transition duration-200 sm:px-4">
                  <input id="progress-range" type="range" min="0" max="1000" value="0" class="mb-3 h-1.5 w-full cursor-pointer appearance-none bg-transparent accent-primary-500">
                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-1 sm:gap-2">
                      <button id="btn-inline-play" type="button" data-player-toggle class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/12 text-white transition hover:bg-white/20" aria-label="Lecture" title="Lecture">
                        <i class="fas fa-play ml-0.5 text-sm" data-play-icon aria-hidden="true"></i>
                        <span class="sr-only" data-control-label>Lecture</span>
                      </button>
                      <button type="button" data-action="back10" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/12 text-white transition hover:bg-white/20" aria-label="Reculer de 10 secondes" title="Reculer de 10 secondes">
                        <i class="fas fa-rotate-left text-sm"></i>
                      </button>
                      <button type="button" data-action="fwd10" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/12 text-white transition hover:bg-white/20" aria-label="Avancer de 10 secondes" title="Avancer de 10 secondes">
                        <i class="fas fa-rotate-right text-sm"></i>
                      </button>
                      <div id="time-label" class="ml-1 min-w-[7.5rem] text-xs font-semibold text-white/90 sm:min-w-[8rem]">00:00 / 00:00</div>
                    </div>
                    <div class="flex items-center gap-1 sm:gap-2">
                      <button id="btn-mute" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/12 text-white transition hover:bg-white/20" aria-label="Couper le son" title="Couper le son">
                        <i class="fas fa-volume-high text-sm" data-mute-icon aria-hidden="true"></i>
                        <span class="sr-only" data-control-label>Couper le son</span>
                      </button>
                      <input id="volume-range" type="range" min="0" max="100" value="80" class="hidden w-20 accent-primary-500 sm:block" aria-label="Volume">
                      <button id="btn-pip" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/12 text-white transition hover:bg-white/20" aria-label="Paramètres" title="Paramètres">
                        <i class="fas fa-cog text-sm"></i>
                      </button>
                      <button id="btn-fullscreen" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/12 text-white transition hover:bg-white/20" aria-label="Plein écran" title="Plein écran">
                        <i class="fas fa-expand text-sm"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <div id="player-settings-panel" class="absolute bottom-24 right-4 z-40 hidden w-80 max-w-[calc(100vw-2rem)] rounded-xl border border-white/10 bg-slate-950/95 p-4 text-white shadow-2xl backdrop-blur" aria-hidden="true">
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-white/50">Paramètres</p>
                      <h3 class="mt-1 text-sm font-semibold text-white">Réglages du lecteur</h3>
                    </div>
                    <button id="btn-settings-close" type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Fermer les paramètres" title="Fermer">
                      <i class="fas fa-times text-sm"></i>
                    </button>
                  </div>
                  <div class="mt-4 space-y-4">
                    <div>
                      <div class="flex items-center justify-between gap-3 text-xs text-white/60">
                        <span>Vitesse de lecture</span>
                        <span class="font-semibold text-white/90" data-player-speed-status>Vitesse active : 1.00x</span>
                      </div>
                      <div class="mt-3 flex flex-wrap gap-2">
                        <?php foreach (['0.75', '1', '1.25', '1.5', '2'] as $speed): ?>
                          <button type="button" class="speed-chip inline-flex min-w-14 items-center justify-center rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-semibold text-white/90 transition hover:border-primary-300 hover:bg-white/10 <?php echo $speed === '1' ? 'border-primary-400 bg-primary-500/20 text-primary-100' : ''; ?>" data-speed="<?php echo e($speed); ?>"><?php echo e($speed); ?>x</button>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <div>
                      <div class="flex items-center justify-between gap-3 text-xs text-white/60">
                        <span>Volume</span>
                        <span class="font-semibold text-white/90">Ajustable</span>
                      </div>
                      <input id="volume-range-panel" type="range" min="0" max="100" value="80" class="mt-3 w-full accent-primary-500">
                    </div>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <div class="flex min-h-[32rem] items-center justify-center bg-gray-900 px-6 py-16 text-center text-white">
                <div class="max-w-xl space-y-4">
                  <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg">
                    <i class="fas fa-graduation-cap text-2xl"></i>
                  </div>
                  <h2 class="text-2xl font-bold"><?php echo e($courseTitle); ?></h2>
                  <p class="text-sm text-white/75">Aucune vidéo exploitable n’est disponible pour cette leçon.</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </section>

        <div class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
          <div class="px-4 sm:px-6 lg:px-8">
            <nav class="watch-tab-scroll flex gap-6 overflow-x-auto text-sm font-medium">
              <button type="button" data-watch-tab="overview" aria-selected="true" class="border-b-2 border-primary-600 py-4 text-primary-600 transition whitespace-nowrap dark:text-primary-400">Aperçu</button>
              <button type="button" data-watch-tab="resources" aria-selected="false" class="border-b-2 border-transparent py-4 text-gray-600 transition hover:text-primary-600 whitespace-nowrap dark:text-gray-400 dark:hover:text-primary-400">Ressources <span class="ml-1 rounded bg-gray-200 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200" data-lesson-resources-count><?php echo e((string) count($activeLessonResources)); ?></span></button>
              <button type="button" data-watch-tab="meetings" aria-selected="false" class="border-b-2 border-transparent py-4 text-gray-600 transition hover:text-primary-600 whitespace-nowrap dark:text-gray-400 dark:hover:text-primary-400">Rendez-vous <span class="ml-1 rounded bg-gray-200 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"><?php echo e((string) $meetingCount); ?></span></button>
              <button type="button" data-watch-tab="notes" aria-selected="false" class="border-b-2 border-transparent py-4 text-gray-600 transition hover:text-primary-600 whitespace-nowrap dark:text-gray-400 dark:hover:text-primary-400">Notes</button>
              <button type="button" data-watch-tab="qna" aria-selected="false" class="border-b-2 border-transparent py-4 text-gray-600 transition hover:text-primary-600 whitespace-nowrap dark:text-gray-400 dark:hover:text-primary-400">Questions &amp; Réponses</button>
            </nav>
          </div>
        </div>

        <?php if ($meetingErrors !== [] || $meetingSuccess !== ''): ?>
          <div class="px-4 pt-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl space-y-3">
              <?php if ($meetingErrors !== []): ?>
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-200">
                  <?php foreach ($meetingErrors as $error): ?>
                    <p>- <?php echo e($error); ?></p>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php if ($meetingSuccess !== ''): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200">
                  <?php echo e($meetingSuccess); ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="px-4 py-6 pb-24 sm:px-6 lg:px-8 lg:py-8">
          <div class="mx-auto max-w-3xl space-y-6">
            <div data-watch-pane="overview" class="space-y-6">
              <div class="space-y-1">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white"><?php echo e($courseTitle); ?></h2>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($currentLessonLabel !== '' ? $currentLessonLabel : 'Leçon active'); ?></p>
              </div>

              <section id="course-completion-panel" class="<?php echo $isCourseCompleted ? '' : 'hidden '; ?>rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900/50 dark:bg-emerald-900/20">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                  <div class="flex min-w-0 items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                      <i class="fas fa-check"></i>
                    </span>
                    <div class="min-w-0">
                      <h3 class="font-semibold text-emerald-900 dark:text-emerald-100">Cours terminé</h3>
                      <p class="mt-1 text-sm text-emerald-800/80 dark:text-emerald-100/75">Votre progression est complète. Vous pouvez revoir une leçon ou laisser votre avis.</p>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button type="button" data-scroll-top class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-emerald-800 dark:bg-gray-950 dark:text-emerald-200 dark:hover:bg-emerald-950/40">
                      <i class="fas fa-rotate-left"></i>
                      Revoir
                    </button>
                    <button type="button" data-course-rating-open data-rating-source="course_watch_completed" data-course-id="<?php echo e((string) ($course['id'] ?? '')); ?>" data-title="<?php echo e($courseTitle); ?>" data-rating-value="<?php echo e((string) $watchRatingValue); ?>" data-rating-comment="<?php echo e($watchRatingComment); ?>" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                      <i class="fas fa-star"></i>
                      <span data-rating-trigger-label><?php echo e($watchRatingLabel); ?></span>
                    </button>
                  </div>
                </div>
              </section>

              <p class="text-gray-600 dark:text-gray-400"><?php echo e($courseDescriptionDisplay); ?></p>

              <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <?php foreach ($courseSummaryItems as $summaryItem): ?>
                  <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400"><?php echo e((string) ($summaryItem['label'] ?? 'Résumé')); ?></p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) ($summaryItem['value'] ?? '')); ?></p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo e((string) ($summaryItem['note'] ?? '')); ?></p>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="rounded-xl border border-primary-100 bg-primary-50 p-5 dark:border-primary-800 dark:bg-primary-900/20">
                <h3 class="mb-3 flex items-center gap-2 font-semibold text-primary-800 dark:text-primary-300">
                  <i class="fas fa-lightbulb"></i>
                  Ce que vous allez apprendre
                </h3>
                <ul class="grid gap-2 text-sm text-gray-700 dark:text-gray-300 sm:grid-cols-2">
                  <?php foreach ($overviewPoints as $point): ?>
                    <li class="flex items-start gap-2">
                      <i class="fas fa-check-circle mt-1 text-green-500"></i>
                      <span><?php echo e((string) $point); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <div class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-950/40">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                      <i class="fas fa-clipboard-list"></i>
                    </span>
                    <div>
                      <h3 class="font-semibold text-gray-900 dark:text-white">Prérequis</h3>
                      <p class="text-xs text-gray-500 dark:text-gray-400">Ce qu’il faut connaître avant de commencer</p>
                    </div>
                  </div>
                  <?php if ($prerequisitesList !== []): ?>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                      <?php foreach ($prerequisitesList as $prerequisite): ?>
                        <li class="flex items-start gap-2">
                          <i class="fas fa-circle-check mt-1 text-emerald-500"></i>
                          <span><?php echo e((string) $prerequisite); ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php else: ?>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Aucun prérequis spécifique n’est demandé pour cette leçon.</p>
                  <?php endif; ?>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-950/40">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300">
                      <i class="fas fa-toolbox"></i>
                    </span>
                    <div>
                      <h3 class="font-semibold text-gray-900 dark:text-white">Matériel recommandé</h3>
                      <p class="text-xs text-gray-500 dark:text-gray-400">Les outils utiles pour suivre le cours</p>
                    </div>
                  </div>
                  <?php if ($equipmentList !== []): ?>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                      <?php foreach ($equipmentList as $equipment): ?>
                        <li class="flex items-start gap-2">
                          <i class="fas fa-circle-check mt-1 text-emerald-500"></i>
                          <span><?php echo e((string) $equipment); ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php else: ?>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Un navigateur et un espace de travail suffisent pour commencer.</p>
                  <?php endif; ?>
                </div>
              </div>

              <p class="text-sm leading-7 text-gray-600 dark:text-gray-400">Votre progression se met à jour pendant le parcours.</p>

              <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button id="btn-prev" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:text-primary-300">
                  <i class="fas fa-chevron-left"></i>
                  Leçon précédente
                </button>
                <button id="btn-next" type="button" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-primary-700">
                  Leçon suivante
                  <i class="fas fa-chevron-right"></i>
                </button>
              </div>
            </div>

            <div data-watch-pane="resources" class="hidden space-y-4">
              <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm dark:border-gray-700 dark:bg-gray-950/35">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200/80 px-5 py-5 dark:border-gray-700">
                  <div class="flex items-center gap-3">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-cyan-500 text-white shadow-lg shadow-primary-600/20">
                      <i class="fas fa-paperclip"></i>
                    </span>
                    <div>
                      <h2 class="text-xl font-bold text-gray-900 dark:text-white">Ressources de la leçon</h2>
                      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Les documents liés à la leçon active s’affichent ici.</p>
                    </div>
                  </div>
                  <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" data-lesson-resources-count><?php echo e((string) count($activeLessonResources)); ?></span>
                </div>

                <div class="p-5">
                  <div id="tab-lesson-resources-list" class="space-y-3" data-lesson-resources-list>
                    <?php foreach ($activeLessonResources as $resource): ?>
                      <a href="<?php echo e((string) ($resource['url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer" class="group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-950/55 dark:hover:border-primary-400">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-cyan-500 text-white shadow-md shadow-primary-600/20">
                          <i class="fas fa-<?php echo e((string) ($resource['icon'] ?? 'link')); ?>"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                          <span class="block truncate font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($resource['label'] ?? 'Ressource')); ?></span>
                          <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?php echo e((string) ($resource['kind'] ?? 'Document')); ?></span>
                          </span>
                        </span>
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition group-hover:border-primary-300 group-hover:text-primary-600 dark:border-gray-700 dark:text-gray-500 dark:group-hover:border-primary-400 dark:group-hover:text-primary-300">
                          <i class="fas fa-arrow-up-right-from-square"></i>
                        </span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                  <div id="tab-lesson-resources-empty" class="<?php echo $activeLessonResources !== [] ? 'hidden ' : ''; ?>rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-8 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400" data-lesson-resources-empty>
                    Aucune ressource n’est encore rattachée à cette leçon.
                  </div>
                </div>
              </div>
            </div>

            <div data-watch-pane="meetings" class="hidden space-y-4">
              <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm dark:border-gray-700 dark:bg-gray-950/35">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200/80 px-5 py-5 dark:border-gray-700">
                  <div class="flex items-center gap-3">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-primary-500 text-white shadow-lg shadow-primary-600/20">
                      <i class="fas fa-calendar-alt"></i>
                    </span>
                    <div>
                      <h2 class="text-xl font-bold text-gray-900 dark:text-white">Rendez-vous</h2>
                      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Les sessions proposées par chapitre apparaissent ici.</p>
                    </div>
                  </div>
                  <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"><?php echo e((string) $meetingCount); ?> session(s)</span>
                </div>

                <div class="space-y-4 p-5">
                  <?php if ($meetingCount > 0): ?>
                    <?php foreach ($chapters as $chapter): ?>
                      <?php
                      if (!is_array($chapter)) {
                          continue;
                      }
                      $chapterId = (int) ($chapter['id'] ?? 0);
                      $chapterMeetings = is_array($meetingsByChapter[$chapterId] ?? null) ? $meetingsByChapter[$chapterId] : [];
                      if ($chapterMeetings === []) {
                          continue;
                      }
                      ?>
                      <section class="rounded-2xl border border-gray-200 bg-gray-50/80 p-5 dark:border-gray-700 dark:bg-gray-900/45">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                          <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600/10 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                              <i class="fas fa-book-open"></i>
                            </span>
                            <div>
                              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Chapitre concerné</p>
                              <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></h3>
                            </div>
                          </div>
                          <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-950/55 dark:text-gray-300"><?php echo e((string) count($chapterMeetings)); ?> session(s)</span>
                        </div>

                        <div class="mt-4 space-y-4">
                          <?php foreach ($chapterMeetings as $meeting): ?>
                            <?php
                            $meetingTitle = trim((string) ($meeting['title'] ?? 'Rendez-vous'));
                            $meetingMode = (string) ($meeting['mode'] ?? 'online');
                            $meetingDurationMin = (int) ($meeting['duration_min'] ?? 0);
                            $meetingHasDuration = $meetingDurationMin > 0;
                            $meetingModeLabel = $meetingMode === 'online' ? 'En ligne' : 'Présentiel';
                            $meetingModeIcon = $meetingMode === 'online' ? 'fa-video' : 'fa-location-dot';
                            $meetingKey = $chapterId . '|' . $meetingTitle . '|' . $meetingMode;
                            $requests = is_array($myMeetingsByKey[$meetingKey] ?? null) ? $myMeetingsByKey[$meetingKey] : [];
                            $confirmedMeeting = null;
                            $pendingMeetings = [];
                            foreach ($requests as $request) {
                                if (($request['status'] ?? '') === 'confirmed' && $confirmedMeeting === null) {
                                    $confirmedMeeting = $request;
                                } elseif (($request['status'] ?? '') === 'pending') {
                                    $pendingMeetings[] = $request;
                                }
                            }
                            $meetingStateLabel = $confirmedMeeting
                                ? 'Confirmé'
                                : (($pendingMeetings !== []) ? 'En attente' : ($meetingHasDuration ? 'À planifier' : 'Durée à définir'));
                            $meetingStateClass = $confirmedMeeting
                                ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-100'
                                : (($pendingMeetings !== []) ? 'border-amber-500/20 bg-amber-500/10 text-amber-100' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-950/30 dark:text-slate-300');
                            ?>
                            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-950/45">
                              <div class="h-1 bg-gradient-to-r from-primary-500 via-cyan-500 to-emerald-500"></div>
                              <div class="p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                  <div class="min-w-0">
                                    <p class="truncate text-base font-semibold text-gray-900 dark:text-white"><?php echo e($meetingTitle); ?></p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                      <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-600/10 px-2.5 py-1 font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">
                                        <i class="fas <?php echo e($meetingModeIcon); ?>"></i>
                                        <?php echo e($meetingModeLabel); ?>
                                      </span>
                                      <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        <i class="fas fa-hourglass-half"></i>
                                        <?php echo $meetingHasDuration ? e(format_duration_minutes_hm($meetingDurationMin)) : 'Durée à définir'; ?>
                                      </span>
                                    </div>
                                  </div>
                                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] <?php echo e($meetingStateClass); ?>"><?php echo e($meetingStateLabel); ?></span>
                                </div>

                                <?php if ($isEnrolled): ?>
                                  <div class="mt-4 space-y-3">
                                    <?php if ($confirmedMeeting): ?>
                                      <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-4 text-sm text-emerald-100">
                                        <div class="flex items-start gap-3">
                                          <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-100">
                                            <i class="fas fa-circle-check"></i>
                                          </span>
                                          <div class="min-w-0">
                                            <p class="font-semibold">Rendez-vous confirmé</p>
                                            <p class="mt-1 text-emerald-50/90"><?php echo e(view_format_date_label($confirmedMeeting['start_at'] ?? null, true)); ?> • <?php echo e(view_format_date_label($confirmedMeeting['end_at'] ?? null, true)); ?></p>
                                          </div>
                                        </div>
                                      </div>
                                    <?php else: ?>
                                      <?php if ($pendingMeetings !== []): ?>
                                        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-4 py-4 text-sm text-amber-100">
                                          <div class="flex items-start gap-3">
                                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-50">
                                              <i class="fas fa-clock"></i>
                                            </span>
                                            <div class="min-w-0">
                                              <p class="font-semibold">Demandes en attente</p>
                                              <div class="mt-2 space-y-1.5 text-amber-50/95">
                                                <?php foreach (array_slice($pendingMeetings, 0, 3) as $pendingMeeting): ?>
                                                  <p><?php echo e(view_format_date_label($pendingMeeting['start_at'] ?? null, true)); ?> • <?php echo e(view_format_date_label($pendingMeeting['end_at'] ?? null, true)); ?></p>
                                                <?php endforeach; ?>
                                              </div>
                                            </div>
                                          </div>
                                        </div>
                                      <?php endif; ?>

                                      <?php if ($meetingHasDuration): ?>
                                        <?php if ($pendingMeetings !== []): ?>
                                          <form method="post" class="flex justify-end">
                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="cancel_meeting_requests">
                                            <input type="hidden" name="meeting_template_id" value="<?php echo e((string) ($meeting['id'] ?? '0')); ?>">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-50 dark:border-red-900/40 dark:bg-gray-950 dark:text-red-300 dark:hover:bg-red-900/20">Annuler mes demandes</button>
                                          </form>
                                        <?php endif; ?>
                                        <form method="post" class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950/30">
                                          <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                          <input type="hidden" name="action" value="request_meeting">
                                          <input type="hidden" name="meeting_template_id" value="<?php echo e((string) ($meeting['id'] ?? '0')); ?>">
                                          <div class="grid gap-3 sm:grid-cols-3">
                                            <?php for ($slotIndex = 0; $slotIndex < 3; $slotIndex++): ?>
                                              <label class="space-y-1">
                                                <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">Créneau <?php echo e((string) ($slotIndex + 1)); ?></span>
                                                <input type="datetime-local" name="slot_start[]" required value="<?php echo e((string) ($pendingMeetings[$slotIndex]['start_local'] ?? '')); ?>" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                                              </label>
                                            <?php endfor; ?>
                                          </div>
                                          <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-primary-700">Envoyer ma demande</button>
                                          </div>
                                        </form>
                                      <?php else: ?>
                                        <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-950/30 dark:text-gray-300">
                                          La durée de cette session n’est pas encore définie par le formateur.
                                        </div>
                                      <?php endif; ?>
                                    <?php endif; ?>
                                  </div>
                                <?php else: ?>
                                  <div class="mt-4 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-950/30 dark:text-gray-300">
                                    Cette session sera accessible après votre inscription au cours.
                                  </div>
                                <?php endif; ?>
                              </div>
                            </article>
                          <?php endforeach; ?>
                        </div>
                      </section>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-8 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                      Aucun rendez-vous n’est publié pour ce cours.
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div data-watch-pane="notes" class="hidden space-y-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Mes notes du cours</h2>
                    <span class="rounded-full bg-primary-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">Cours entier</span>
                  </div>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Les notes sont sauvegardées localement pour l’ensemble du cours, pas par leçon.</p>
                </div>
              </div>
              <textarea id="lesson-notes" data-course-notes-textarea placeholder="Écrivez ici les points importants de ce cours." class="min-h-[260px] w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"></textarea>
              <span id="notes-status" data-course-notes-status class="block text-sm text-gray-500 dark:text-gray-400"></span>
              <div class="flex flex-wrap gap-2">
                <button id="btn-notes-doc" data-course-notes-export="doc" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:text-primary-300">
                  <i class="fas fa-file-word"></i>
                  Exporter en Word
                </button>
                <button id="btn-notes-pdf" data-course-notes-export="pdf" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:text-primary-300">
                  <i class="fas fa-file-pdf"></i>
                  Exporter en PDF
                </button>
              </div>
            </div>

            <div data-watch-pane="qna" class="hidden space-y-4">
              <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/40">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md"><i class="fas fa-comments"></i></span>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Q&amp;R</p>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Questions & réponses</h2>
                  </div>
                </div>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">Posez vos questions via le support.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                  <a href="<?php echo e(site_url('support?space=student')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-primary-700">
                    <i class="fas fa-headset"></i>
                    Ouvrir le support
                  </a>
                  <a href="<?php echo e($myCoursesUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:text-primary-300">
                    <i class="fas fa-book-open"></i>
                    Mes cours
                  </a>
                </div>
              </div>

              <div class="rounded-xl border border-dashed border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-950/40">
                <p class="font-semibold text-gray-900 dark:text-white">Conseil de suivi</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Terminez la leçon, puis marquez-la comme vue.</p>
              </div>
            </div>
          </div>
        </div>
      </main>

      <aside class="max-h-[42vh] w-full shrink-0 overflow-y-auto border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 lg:max-h-none lg:w-96 lg:border-l lg:border-t-0">
        <div class="space-y-4 p-5">
          <div class="flex items-center gap-3">
            <?php if ($courseCoverUrl !== ''): ?>
              <img src="<?php echo e($courseCoverUrl); ?>" alt="<?php echo e($courseCoverAlt); ?>" class="h-12 w-12 rounded-lg object-cover shadow-sm">
            <?php else: ?>
              <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-600 text-white shadow-md">
                <i class="fas fa-graduation-cap"></i>
              </div>
            <?php endif; ?>
            <div class="min-w-0">
              <h3 class="truncate font-bold text-gray-900 dark:text-white"><?php echo e($courseTitle); ?></h3>
              <p class="truncate text-xs text-gray-500 dark:text-gray-400"><?php echo e($teacherFullName); ?> · <?php echo e($durationLabel); ?></p>
            </div>
          </div>

          <div>
            <div class="mb-1 flex items-center justify-between text-xs">
              <span class="text-gray-500 dark:text-gray-400">Progression globale</span>
              <span id="sidebar-progress" class="font-semibold text-gray-700 dark:text-gray-200"><?php echo e((string) $progressPercent); ?>% · <?php echo e($progressSummary); ?></span>
            </div>
            <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
              <div id="sidebar-progress-bar" class="h-2 rounded-full bg-primary-600 transition-all duration-500" style="width: <?php echo e((string) $progressPercent); ?>%"></div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2">
              <span id="course-progress-state" class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($courseProgressStateClass); ?>">
                <i class="fas <?php echo $isCourseCompleted ? 'fa-check' : 'fa-play'; ?>" aria-hidden="true"></i>
                <?php echo e($courseProgressStateLabel); ?>
              </span>
              <button type="button" data-course-rating-open data-rating-source="course_watch_sidebar" data-course-id="<?php echo e((string) ($course['id'] ?? '')); ?>" data-title="<?php echo e($courseTitle); ?>" data-rating-value="<?php echo e((string) $watchRatingValue); ?>" data-rating-comment="<?php echo e($watchRatingComment); ?>" class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-600 transition hover:border-amber-300 hover:text-amber-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:border-amber-500 dark:hover:text-amber-300">
                <i class="fas fa-star"></i>
                <span data-rating-trigger-label><?php echo e($watchRatingLabel); ?></span>
              </button>
            </div>
          </div>

          <div class="space-y-4">
            <?php foreach ($chapters as $chapter): ?>
              <?php
              if (!is_array($chapter)) {
                  continue;
              }
              $chapterId = (int) ($chapter['id'] ?? 0);
              $chapterLessons = is_array($playlistByChapter[$chapterId] ?? null) ? $playlistByChapter[$chapterId] : [];
              if ($chapterLessons === []) {
                  continue;
              }
              $chapterActive = $chapterId === $activeChapterId;
              ?>
              <details class="course-watch-details overflow-hidden rounded-xl border <?php echo $chapterActive ? 'border-primary-200 shadow-sm dark:border-primary-800' : 'border-gray-200 dark:border-gray-700'; ?>" <?php echo $chapterActive ? 'open' : ''; ?>>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4 <?php echo $chapterActive ? 'bg-primary-50 dark:bg-primary-900/20' : 'bg-gray-50 dark:bg-gray-700/50'; ?>">
                  <span class="min-w-0">
                    <span class="block truncate text-base font-medium text-gray-900 dark:text-white"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></span>
                  </span>
                  <i class="fas fa-chevron-down course-watch-chevron text-sm text-gray-400 transition-transform duration-200"></i>
                </summary>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                  <?php foreach ($chapterLessons as $index => $lesson): ?>
                    <?php
                    if (!is_array($lesson)) {
                        continue;
                    }
                    $lessonId = (int) ($lesson['id'] ?? 0);
                    $lessonTitle = trim((string) ($lesson['title'] ?? 'Leçon'));
                    $lessonDone = $lessonId > 0 && in_array($lessonId, $doneByLesson, true);
                    $lessonActive = $lessonId > 0 && $lessonId === $currentLessonId;
                    $lessonDuration = !empty($lesson['duration_seconds'])
                        ? format_duration_minutes_hm((int) max(1, ceil(((int) $lesson['duration_seconds']) / 60)))
                        : '';
                    $lessonStatusIcon = $lessonActive
                        ? 'fa-play'
                        : ($lessonDone ? 'fa-check' : 'fa-circle');
                    $lessonStatusIconClass = $lessonDone
                        ? 'text-[10px] text-white'
                        : ($lessonActive ? 'text-[10px] text-primary-600 dark:text-primary-300' : 'text-[10px] text-gray-400');
                    $lessonTitleClass = $lessonActive ? 'font-medium text-primary-700 dark:text-primary-300' : 'text-gray-900 dark:text-white';
                    ?>
                    <button
                      type="button"
                      class="queue-item flex w-full items-center gap-3 p-3 text-left <?php echo $lessonActive ? 'active bg-primary-50/50 dark:bg-primary-900/10 border-l-4 border-primary-600' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/50'; ?>"
                      data-embed-url="<?php echo e((string) ($lesson['embed'] ?? '')); ?>"
                      data-video-id="<?php echo e((string) ($lesson['video_id'] ?? '')); ?>"
                      data-lesson-id="<?php echo e((string) $lessonId); ?>"
                      data-lesson-title="<?php echo e($lessonTitle); ?>"
                      data-lesson-pos="<?php echo e((string) ($lesson['position'] ?? '')); ?>"
                      data-chapter-title="<?php echo e((string) ($chapter['title'] ?? '')); ?>"
                      data-chapter-pos="<?php echo e((string) ($chapter['position'] ?? '')); ?>"
                      data-index="<?php echo e((string) $index); ?>"
                      data-done="<?php echo $lessonDone ? '1' : '0'; ?>"
                    >
                      <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border <?php echo $lessonDone ? 'border-green-500 bg-green-500 text-white dark:border-green-500 dark:bg-green-500 dark:text-white' : ($lessonActive ? 'border-primary-600 bg-white text-primary-600 dark:border-primary-400 dark:text-primary-300' : 'border-gray-300 bg-white text-gray-400 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-400'); ?>" data-lesson-status-chip>
                        <i class="fas <?php echo e($lessonStatusIcon); ?> <?php echo e($lessonStatusIconClass); ?>" data-lesson-status-icon aria-hidden="true"></i>
                      </span>
                      <span class="min-w-0 flex-1 truncate text-sm <?php echo e($lessonTitleClass); ?>" data-lesson-title-node><?php echo e($lessonTitle); ?></span>
                      <span class="text-xs text-gray-400 dark:text-gray-500"><?php echo e($lessonDuration !== '' ? $lessonDuration : ''); ?></span>
                    </button>
                  <?php endforeach; ?>
                </div>
              </details>
            <?php endforeach; ?>
          </div>

          <section class="mt-6 overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-950/35">
            <div class="flex items-center gap-3">
              <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-cyan-500 text-white shadow-md shadow-primary-600/20">
                <i class="fas fa-paperclip"></i>
              </span>
              <div class="min-w-0">
                <h3 class="font-semibold text-gray-900 dark:text-white">Ressources de la leçon</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Accédez rapidement aux supports disponibles.</p>
              </div>
            </div>
            <div id="sidebar-lesson-resources-list" class="course-watch-resource-list mt-4 max-h-[24rem] space-y-3 overflow-y-auto pr-1" data-lesson-resources-list>
              <?php foreach ($activeLessonResources as $resource): ?>
                <a href="<?php echo e((string) ($resource['url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer" class="group flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-3 py-3 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-950/50 dark:hover:border-primary-400">
                  <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-cyan-500 text-white shadow-md shadow-primary-600/20">
                    <i class="fas fa-<?php echo e((string) ($resource['icon'] ?? 'link')); ?>"></i>
                  </span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($resource['label'] ?? 'Ressource')); ?></span>
                    <span class="mt-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"><?php echo e((string) ($resource['kind'] ?? 'Document')); ?></span>
                  </span>
                  <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition group-hover:border-primary-300 group-hover:text-primary-600 dark:border-gray-700 dark:text-gray-500 dark:group-hover:border-primary-400 dark:group-hover:text-primary-300">
                    <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>
            <div id="sidebar-lesson-resources-empty" class="<?php echo $activeLessonResources !== [] ? 'hidden ' : ''; ?>mt-4 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400" data-lesson-resources-empty>
              Aucune ressource n’est encore rattachée à cette leçon.
            </div>
          </section>

          <details class="notes-widget mt-6 overflow-hidden rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm transition duration-200 dark:border-gray-700 dark:bg-gray-950/35" data-course-notes-widget>
            <summary class="group flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4 transition hover:bg-gray-50/80 dark:hover:bg-gray-900/40">
              <span class="flex min-w-0 items-center gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-fuchsia-500 text-white shadow-md shadow-violet-600/20">
                  <i class="fas fa-sticky-note"></i>
                </span>
                <span class="min-w-0">
                  <span class="block text-base font-semibold text-gray-900 dark:text-white">Notes rapides</span>
                  <span class="block text-xs text-gray-500 dark:text-gray-400">Gardez vos idées sans quitter la lecture.</span>
                </span>
              </span>
              <span class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">Cours entier</span>
                <i class="fas fa-chevron-down notes-widget-chevron text-sm text-gray-400 transition-transform duration-200"></i>
              </span>
            </summary>
            <div class="border-t border-gray-100 px-4 py-4 dark:border-gray-700/70">
              <textarea id="sidebar-course-notes" data-course-notes-textarea placeholder="Écrivez ici vos points clés du cours..." class="min-h-[170px] w-full resize-y rounded-2xl border border-gray-200 bg-white px-3 py-3 text-sm text-gray-800 outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:focus:border-primary-500"></textarea>
              <div class="mt-3 flex flex-col gap-3">
                <span class="text-xs text-gray-500 dark:text-gray-400" data-course-notes-status>Vide</span>
                <div class="flex flex-wrap gap-2">
                  <button type="button" data-course-notes-export="doc" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:text-primary-300">
                    <i class="fas fa-file-word"></i>
                    Word
                  </button>
                  <button type="button" data-course-notes-export="pdf" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:text-gray-300">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                  </button>
                </div>
              </div>
            </div>
          </details>
        </div>
      </aside>
    </div>

    <script id="lesson-resources-map" type="application/json"><?php echo $lessonResourcesJson; ?></script>
  </div>
</body>
</html>
