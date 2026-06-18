<?php
declare(strict_types=1);

$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = dashboard_user_display_name($teacherUser, 'Enseignant');
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = dashboard_user_avatar_url($teacherUser);
$teacherInitials = dashboard_user_initials($teacherUser, 'EN');

$isExamIndex = !empty($isExamIndex);
$exam = is_array($exam ?? null) ? $exam : [];
$examRows = array_values(is_array($examRows ?? null) ? $examRows : []);
$errors = array_values(is_array($errors ?? null) ? $errors : []);
$success = trim((string) ($success ?? ''));
$examType = trim((string) ($examType ?? ($isExamIndex ? '' : 'qcm')));
if (!in_array($examType, ['qcm', 'drive', 'meeting'], true) && !$isExamIndex) {
    $examType = 'qcm';
}

$minScore = max(0, (int) ($minScore ?? ($exam['min_score'] ?? 0)));
$attemptsHasMeetingId = !empty($attemptsHasMeetingId);
$questionsByVariant = is_array($questionsByVariant ?? null) ? $questionsByVariant : [1 => [], 2 => [], 3 => []];
$optionsByQuestion = is_array($optionsByQuestion ?? null) ? $optionsByQuestion : [];
$optionTextById = is_array($optionTextById ?? null) ? $optionTextById : [];
$correctTextByQuestion = is_array($correctTextByQuestion ?? null) ? $correctTextByQuestion : [];
$maxScoreByVariant = is_array($maxScoreByVariant ?? null) ? $maxScoreByVariant : [1 => 0, 2 => 0, 3 => 0];
$manualMax = max(0, (int) ($manualMax ?? 0));
$manualMaxByVariant = is_array($manualMaxByVariant ?? null) ? $manualMaxByVariant : [1 => 0, 2 => 0, 3 => 0];
$variantLabels = is_array($variantLabels ?? null) ? $variantLabels : [1 => 'Version A', 2 => 'Version B', 3 => 'Version C'];
$status = trim((string) ($status ?? 'submitted'));
if (!in_array($status, ['submitted', 'graded', 'all'], true)) {
    $status = 'submitted';
}
$attempts = array_values(is_array($attempts ?? null) ? $attempts : []);
$filesByAttempt = is_array($filesByAttempt ?? null) ? $filesByAttempt : [];
$answersByAttempt = is_array($answersByAttempt ?? null) ? $answersByAttempt : [];
$meetingSlots = array_values(is_array($meetingSlots ?? null) ? $meetingSlots : []);
$defaultMeetingDuration = max(1, (int) ($defaultMeetingDuration ?? 60));
$defaultMeetingMode = in_array((string) ($defaultMeetingMode ?? 'online'), ['online', 'onsite'], true) ? (string) $defaultMeetingMode : 'online';
$examId = max(0, (int) ($examId ?? ($exam['id'] ?? 0)));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$csrfToken = view_csrf_token();

$formatCount = static fn (int|float $value): string => number_format((float) max(0, $value), 0, ',', ' ');
$fmtDtShort = isset($fmtDtShort) && is_callable($fmtDtShort) ? $fmtDtShort : static function (?string $dbValue): string {
    if (!$dbValue) {
        return '';
    }
    $ts = strtotime($dbValue);
    return $ts ? date('d/m/Y H:i', $ts) : (string) $dbValue;
};

$dtLocalValue = isset($dtLocalValue) && is_callable($dtLocalValue) ? $dtLocalValue : static function (?string $dbValue): string {
    if (!$dbValue) {
        return '';
    }
    $ts = strtotime($dbValue);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
};

$formatDuration = static function (int $minutes): string {
    $minutes = max(0, $minutes);
    if (function_exists('format_duration_minutes_hm')) {
        return format_duration_minutes_hm($minutes);
    }
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;
    return $remaining > 0 ? ($hours . ' h ' . $remaining . ' min') : ($hours . ' h');
};

$typeMeta = static function (string $type): array {
    return match ($type) {
        'drive' => [
            'label' => 'Remise Drive',
            'icon' => 'upload',
            'tone' => 'emerald',
            'summary' => 'Copies déposées',
        ],
        'meeting' => [
            'label' => 'Rendez-vous',
            'icon' => 'calendar',
            'tone' => 'amber',
            'summary' => 'Créneaux',
        ],
        default => [
            'label' => 'QCM',
            'icon' => 'clipboard-list',
            'tone' => 'blue',
            'summary' => 'Questions',
        ],
    };
};

$toneClass = static function (string $tone): array {
    return match ($tone) {
        'emerald' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-600 dark:text-green-300'],
        'amber' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-600 dark:text-yellow-300'],
        'rose' => ['bg' => 'bg-rose-100 dark:bg-rose-900/30', 'text' => 'text-rose-600 dark:text-rose-300'],
        'slate' => ['bg' => 'bg-gray-100 dark:bg-gray-700/50', 'text' => 'text-gray-500 dark:text-gray-300'],
        default => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-primary-600 dark:text-primary-300'],
    };
};

$statusBadgeClass = static function (string $state): string {
    return match ($state) {
        'graded', 'done', 'available' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
        'submitted', 'booked' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
        'cancelled' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200',
        default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-300',
    };
};

$statusLabel = static function (string $state): string {
    return match ($state) {
        'submitted' => 'À corriger',
        'graded' => 'Corrigé',
        'available' => 'Disponible',
        'booked' => 'Réservé',
        'done' => 'Terminé',
        'cancelled' => 'Annulé',
        default => 'En cours',
    };
};

$courseTitle = trim((string) ($exam['course_title'] ?? 'Cours'));
$examTitle = trim((string) ($exam['title'] ?? 'Examens'));
$typeInfo = $typeMeta($examType);
$durationLabel = !empty($exam['duration_min']) ? $formatDuration((int) $exam['duration_min']) : 'Durée libre';
$courseWorkflowStatus = !empty($exam['course_deleted_at']) ? 'archived' : (string) ($exam['course_status'] ?? 'draft');
$courseWorkflowLabel = match ($courseWorkflowStatus) {
    'approved' => 'Publié',
    'submitted' => 'En validation',
    'archived' => 'Archivé',
    'draft' => 'Brouillon',
    default => ucfirst($courseWorkflowStatus),
};

$questionTotal = 0;
foreach ($questionsByVariant as $variantItems) {
    $questionTotal = max($questionTotal, count((array) $variantItems));
}
$questionTotalLabel = $questionTotal . ' question' . ($questionTotal > 1 ? 's' : '');

$submittedCount = 0;
$gradedCount = 0;
foreach ($attempts as $attemptRow) {
    $attemptStatus = (string) ($attemptRow['status'] ?? '');
    if ($attemptStatus === 'submitted') {
        $submittedCount++;
    } elseif ($attemptStatus === 'graded') {
        $gradedCount++;
    }
}

$selectedAttemptId = max(0, (int) ($selectedAttemptId ?? 0));
$selectedAttemptIndex = -1;
$selectedAttempt = null;
if ($attempts !== []) {
    foreach ($attempts as $attemptIndex => $attemptRow) {
        if ((int) ($attemptRow['id'] ?? 0) === $selectedAttemptId) {
            $selectedAttempt = $attemptRow;
            $selectedAttemptIndex = $attemptIndex;
            break;
        }
    }
    if ($selectedAttempt === null) {
        foreach ($attempts as $attemptIndex => $attemptRow) {
            if ((string) ($attemptRow['status'] ?? '') === 'submitted') {
                $selectedAttempt = $attemptRow;
                $selectedAttemptIndex = $attemptIndex;
                $selectedAttemptId = (int) ($attemptRow['id'] ?? 0);
                break;
            }
        }
    }
    if ($selectedAttempt === null) {
        $selectedAttempt = $attempts[0];
        $selectedAttemptIndex = 0;
        $selectedAttemptId = (int) ($attempts[0]['id'] ?? 0);
    }
}
$selectedAttemptCount = count($attempts);
$selectedAttemptPrev = $selectedAttemptIndex > 0 ? $attempts[$selectedAttemptIndex - 1] : null;
$selectedAttemptNext = ($selectedAttemptIndex >= 0 && $selectedAttemptIndex < ($selectedAttemptCount - 1)) ? $attempts[$selectedAttemptIndex + 1] : null;
$attemptDetailBaseUrl = $isExamIndex ? '' : site_url('teacher/exams_manage?exam_id=' . $examId . '&status=' . urlencode($status));
$attemptDetailUrl = static fn (int $attemptId): string => $attemptId > 0 && $attemptDetailBaseUrl !== '' ? ($attemptDetailBaseUrl . '&attempt_id=' . $attemptId) : $attemptDetailBaseUrl;

$indexTotalAttempts = 0;
$indexSubmittedTotal = 0;
$indexGradedTotal = 0;
$indexUrgentCount = 0;
foreach ($examRows as $row) {
    $indexTotalAttempts += max(0, (int) ($row['attempt_count'] ?? 0));
    $indexSubmittedTotal += max(0, (int) ($row['submitted_count'] ?? 0));
    $indexGradedTotal += max(0, (int) ($row['graded_count'] ?? 0));
    $oldest = trim((string) ($row['oldest_pending_at'] ?? ''));
    if ($oldest !== '') {
        $ts = strtotime($oldest);
        if ($ts !== false && (time() - $ts) >= 72 * 3600) {
            $indexUrgentCount++;
        }
    }
}

$baseUrl = $isExamIndex ? site_url('teacher/exams_manage') : site_url('teacher/exams_manage?exam_id=' . $examId);
$linkSubmitted = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'status=submitted';
$linkGraded = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'status=graded';
$linkAll = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'status=all';
$linkUrgent = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'status=urgent';
$courseId = max(0, (int) ($exam['course_id'] ?? 0));
$previewExamUrl = $examId > 0 ? site_url('teacher/exam_preview?exam_id=' . $examId) : '';
$editExamUrl = $courseId > 0 ? site_url('teacher/exams_create?course_id=' . $courseId . '&return=edit') : '';
$exportGradesUrl = $examId > 0 ? site_url('teacher/exams_manage?exam_id=' . $examId . '&status=all&export=csv') : '';
$backToListUrl = $examId > 0 ? site_url('teacher/exams_manage?status=' . urlencode($status)) : '';
$structureCard = match ($examType) {
    'meeting' => ['label' => 'Créneaux', 'value' => count($meetingSlots), 'meta' => 'Rendez-vous planifiés'],
    'drive' => ['label' => 'Copies', 'value' => count($attempts), 'meta' => 'Dépôts reçus'],
    default => ['label' => 'Questions', 'value' => $questionTotal, 'meta' => $questionTotalLabel],
};
$structureHeroLabel = match ($examType) {
    'meeting' => count($meetingSlots) . ' créneau' . (count($meetingSlots) > 1 ? 'x' : ''),
    'drive' => count($attempts) . ' copie' . (count($attempts) > 1 ? 's' : ''),
    default => $questionTotalLabel,
};

$metricCards = $isExamIndex
    ? [
        ['label' => 'Examens', 'value' => count($examRows), 'meta' => 'Selon l’affichage', 'icon' => 'clipboard-list', 'tone' => 'blue'],
        ['label' => 'À corriger', 'value' => $indexSubmittedTotal, 'meta' => 'Copies en attente', 'icon' => 'clock', 'tone' => 'amber'],
        ['label' => 'Corrigés', 'value' => $indexGradedTotal, 'meta' => 'Notes enregistrées', 'icon' => 'check-circle', 'tone' => 'emerald'],
        ['label' => 'Urgents', 'value' => $indexUrgentCount, 'meta' => 'À corriger depuis plus de 72 h', 'icon' => 'alert-circle', 'tone' => 'rose'],
    ]
    : [
        ['label' => 'Tentatives', 'value' => count($attempts), 'meta' => 'Selon l’affichage', 'icon' => 'users', 'tone' => 'blue'],
        ['label' => 'À corriger', 'value' => $submittedCount, 'meta' => 'Copies en attente', 'icon' => 'clock', 'tone' => 'amber'],
        ['label' => 'Corrigés', 'value' => $gradedCount, 'meta' => 'Notes enregistrées', 'icon' => 'check-circle', 'tone' => 'emerald'],
        ['label' => $structureCard['label'], 'value' => $structureCard['value'], 'meta' => $structureCard['meta'], 'icon' => (string) $typeInfo['icon'], 'tone' => (string) $typeInfo['tone']],
    ];
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/exams_manage">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title><?php echo e($isExamIndex ? 'Centre de correction' : ('Gestion examen - ' . $courseTitle)); ?> · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_exams_manage.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_exams_manage.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/exams_manage" class="app-role-teacher teacher-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">
<?php
$teacherNavActive = 'exams_manage';
$teacherDashboardShell = true;
$showCatalogue = false;
$teacherTopTitle = $isExamIndex ? 'Centre de correction' : 'Gestion de l\'examen';
$teacherTopSubtitle = 'Espace formateur';
$teacherTopContextSection = $isExamIndex ? 'Corrections' : (string) $typeInfo['label'];
$teacherTopContextLabel = $isExamIndex ? 'Vue globale' : $examTitle;
?>
<div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
    <?php require __DIR__ . '/_topbar.php'; ?>

    <div class="p-4 pb-24 space-y-6 animate-fade-up sm:p-6 sm:pb-24">
      <?php if ($success !== ''): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
          <?php echo e($success); ?>
        </div>
      <?php endif; ?>

      <?php if ($errors !== []): ?>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100" role="alert">
          <?php foreach ($errors as $error): ?>
            <p><?php echo e((string) $error); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php $teacherWelcomeTitle = $isExamIndex ? 'Centre de correction' : $examTitle; ?>
      <?php $teacherWelcomeSummary = $isExamIndex
          ? ($formatCount($indexSubmittedTotal) . ' à corriger · ' . $formatCount($indexTotalAttempts) . ' tentative(s) · ' . $formatCount($indexUrgentCount) . ' urgent(s).')
          : ($courseTitle . ' · ' . $courseWorkflowLabel . ' · ' . $durationLabel . ' · ' . $structureHeroLabel); ?>
      <?php $teacherWelcomeActionUrl = $isExamIndex ? site_url('teacher/courses') : ($previewExamUrl !== '' ? $previewExamUrl : $backToListUrl); ?>
      <?php $teacherWelcomeActionLabel = $isExamIndex ? 'Mes cours' : ($previewExamUrl !== '' ? 'Voir les questions' : ($backToListUrl !== '' ? 'Retour' : '')); ?>
      <?php $teacherWelcomeActionIcon = $isExamIndex ? 'book-open' : ($previewExamUrl !== '' ? 'clipboard-list' : 'arrow-left'); ?>
      <?php require __DIR__ . '/_welcome_hero.php'; ?>

      <?php if (!$isExamIndex && ($backToListUrl !== '' || $editExamUrl !== '' || $exportGradesUrl !== '')): ?>
        <div class="exam-action-bar flex flex-wrap items-center justify-end gap-2">
          <?php if ($backToListUrl !== ''): ?>
            <a href="<?php echo e($backToListUrl); ?>" class="exam-action-btn inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
              <i class="fas fa-arrow-left" aria-hidden="true"></i>
              Retour
            </a>
          <?php endif; ?>
          <?php if ($editExamUrl !== ''): ?>
            <a href="<?php echo e($editExamUrl); ?>" class="exam-action-btn inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
              <i class="fas fa-pen" aria-hidden="true"></i>
              Modifier
            </a>
          <?php endif; ?>
          <?php if ($exportGradesUrl !== ''): ?>
            <a href="<?php echo e($exportGradesUrl); ?>" class="exam-action-btn inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
              <i class="fas fa-file-export" aria-hidden="true"></i>
              Exporter
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <section class="exam-summary-strip student-dashboard-stats-grid grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Résumé des examens">
        <?php foreach ($metricCards as $card): ?>
          <?php $metricTone = $toneClass((string) ($card['tone'] ?? 'blue')); ?>
          <article class="exam-summary-pill student-dashboard-stat-card bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift" title="<?php echo e((string) ($card['meta'] ?? '')); ?>">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="exam-summary-pill__label text-sm text-gray-500 dark:text-gray-400"><?php echo e((string) $card['label']); ?></p>
                <p class="exam-summary-pill__value mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e($formatCount((int) ($card['value'] ?? 0))); ?></p>
              </div>
              <span class="exam-summary-pill__icon student-dashboard-icon-tile flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl <?php echo e($metricTone['bg']); ?> <?php echo e($metricTone['text']); ?>">
                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg((string) ($card['icon'] ?? 'star')); ?></span>
              </span>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <section class="exam-filter-bar bg-white dark:bg-gray-800 rounded-2xl px-3 py-2 shadow-card border border-gray-200 dark:border-gray-700">
        <div class="exam-filter-row flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
          <?php
            $filters = [
              ['url' => $linkAll, 'key' => 'all', 'label' => 'Tous'],
              ['url' => $linkSubmitted, 'key' => 'submitted', 'label' => 'À corriger'],
              ['url' => $linkGraded, 'key' => 'graded', 'label' => 'Corrigés'],
              ['url' => $linkUrgent, 'key' => 'urgent', 'label' => 'Urgents'],
            ];
          ?>
          <nav class="exam-filter-tabs flex flex-wrap gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-900" aria-label="Filtrer les examens">
            <?php foreach ($filters as $filter): ?>
              <a href="<?php echo e($filter['url']); ?>" aria-current="<?php echo $status === $filter['key'] ? 'page' : 'false'; ?>" class="exam-filter-tab inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-semibold transition <?php echo $status === $filter['key'] ? 'bg-white text-primary-700 shadow-sm ring-1 ring-primary-200 dark:bg-primary-500/15 dark:text-primary-100 dark:ring-primary-500/40' : 'text-gray-600 hover:bg-white hover:text-primary-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-primary-200'; ?>">
                <?php echo e($filter['label']); ?>
              </a>
            <?php endforeach; ?>
          </nav>
          <label class="exam-search-field relative block w-full xl:max-w-sm">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
              <i class="fas fa-search" aria-hidden="true"></i>
            </span>
            <input type="search" data-exam-search placeholder="Examen ou étudiant" class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm font-medium text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:placeholder:text-gray-500">
          </label>
        </div>
      </section>

      <?php if ($isExamIndex): ?>
        <section class="exam-index-table overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-card dark:border-gray-700 dark:bg-gray-800" data-exam-search-root>
          <?php if ($examRows === []): ?>
            <div class="p-6 text-sm text-gray-600 dark:text-gray-300">
              Aucun examen pour ce filtre.
            </div>
          <?php else: ?>
            <div class="hidden grid-cols-[minmax(16rem,1.8fr)_8rem_12rem_7rem_7rem_7rem_7rem] gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-bold uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 lg:grid">
              <span>Examen</span>
              <span>Type</span>
              <span>Dernière activité</span>
              <span class="text-center">Tentatives</span>
              <span class="text-center">À corriger</span>
              <span class="text-center">Corrigées</span>
              <span class="text-right">Action</span>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($examRows as $row): ?>
              <?php
                $rowType = in_array((string) ($row['type'] ?? 'qcm'), ['qcm', 'drive', 'meeting'], true) ? (string) $row['type'] : 'qcm';
                $rowTypeInfo = $typeMeta($rowType);
                $rowTone = $toneClass((string) $rowTypeInfo['tone']);
                $pending = max(0, (int) ($row['submitted_count'] ?? 0));
                $graded = max(0, (int) ($row['graded_count'] ?? 0));
                $attemptCount = max(0, (int) ($row['attempt_count'] ?? 0));
                $lastActivity = trim((string) ($row['latest_activity_at'] ?? ''));
                $oldestPending = trim((string) ($row['oldest_pending_at'] ?? ''));
                $isUrgent = false;
                if ($oldestPending !== '') {
                    $oldestTs = strtotime($oldestPending);
                    $isUrgent = $oldestTs !== false && (time() - $oldestTs) >= 72 * 3600;
                }
                $activityLabel = $lastActivity !== '' ? $fmtDtShort($lastActivity) : 'Aucune activité';
                $rowSearchText = implode(' ', [
                    (string) ($row['course_title'] ?? ''),
                    (string) ($row['exam_title'] ?? ''),
                    (string) $rowTypeInfo['label'],
                    $activityLabel,
                    $isUrgent ? 'urgent' : '',
                ]);
              ?>
              <article data-exam-search-item data-search-text="<?php echo e($rowSearchText); ?>" class="exam-index-row grid grid-cols-1 gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-900/60 lg:grid-cols-[minmax(16rem,1.8fr)_8rem_12rem_7rem_7rem_7rem_7rem] lg:items-center">
                <div class="min-w-0">
                  <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg <?php echo e($rowTone['bg']); ?> <?php echo e($rowTone['text']); ?>">
                      <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg((string) $rowTypeInfo['icon']); ?></span>
                    </span>
                    <div class="min-w-0">
                      <p class="truncate text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-300"><?php echo e((string) ($row['course_title'] ?? 'Cours')); ?></p>
                      <h3 class="mt-0.5 truncate text-base font-bold text-gray-900 dark:text-white"><?php echo e((string) ($row['exam_title'] ?? 'Examen')); ?></h3>
                    </div>
                  </div>
                </div>
                <div class="flex items-center justify-between gap-3 lg:block">
                  <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 lg:hidden">Type</span>
                  <span class="inline-flex w-fit rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"><?php echo e((string) $rowTypeInfo['label']); ?></span>
                </div>
                <div class="flex items-center justify-between gap-3 text-sm text-gray-600 dark:text-gray-300 lg:block">
                  <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 lg:hidden">Dernière activité</span>
                  <span><?php echo e($activityLabel); ?></span>
                  <?php if ($isUrgent): ?>
                    <span class="ml-0 inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold <?php echo e($statusBadgeClass('cancelled')); ?> lg:mt-1">Urgent</span>
                  <?php endif; ?>
                </div>
                <div class="flex items-center justify-between gap-3 text-sm lg:block lg:text-center">
                  <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 lg:hidden">Tentatives</span>
                  <span class="font-bold text-gray-900 dark:text-white"><?php echo e($formatCount($attemptCount)); ?></span>
                </div>
                <div class="flex items-center justify-between gap-3 text-sm lg:block lg:text-center">
                  <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 lg:hidden">À corriger</span>
                  <span class="font-bold <?php echo $pending > 0 ? 'text-amber-700 dark:text-amber-200' : 'text-gray-900 dark:text-white'; ?>"><?php echo e($formatCount($pending)); ?></span>
                </div>
                <div class="flex items-center justify-between gap-3 text-sm lg:block lg:text-center">
                  <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 lg:hidden">Corrigées</span>
                  <span class="font-bold text-gray-900 dark:text-white"><?php echo e($formatCount($graded)); ?></span>
                </div>
                <div class="flex justify-start lg:justify-end">
                  <a href="<?php echo e(site_url('teacher/exams_manage?exam_id=' . (int) ($row['exam_id'] ?? 0) . '&status=' . urlencode($status))); ?>" class="exam-open-btn inline-flex items-center gap-2 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    Voir
                  </a>
                </div>
              </article>
            <?php endforeach; ?>
            </div>
            <div data-exam-search-empty class="hidden p-6 text-sm text-gray-600 dark:text-gray-300">
              Aucun examen ne correspond à cette recherche.
            </div>
          <?php endif; ?>
        </section>
      <?php else: ?>
        <?php if ($examType === 'meeting'): ?>
          <section class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Organisation</p>
                <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Créneaux d'examen</h2>
              </div>
              <span class="inline-flex w-fit rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <?php echo e($formatCount(count($meetingSlots))); ?> créneau(x)
              </span>
            </div>

            <?php if (!$attemptsHasMeetingId): ?>
              <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                Mise à jour requise pour associer les tentatives aux créneaux.
              </div>
            <?php endif; ?>

            <details class="mt-5 rounded-2xl border border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
              <summary class="cursor-pointer px-4 py-4 text-sm font-semibold text-gray-900 dark:text-white">Ajouter un créneau</summary>
              <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                <form method="post" class="grid gap-4 md:grid-cols-5" data-meeting-slot-form>
                  <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                  <input type="hidden" name="action" value="add_meeting_slot">
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 md:col-span-2">
                    Début
                    <input type="datetime-local" name="start_at" required class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                  </label>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Durée
                    <input type="number" name="duration_min" min="1" required value="<?php echo e((string) $defaultMeetingDuration); ?>" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                  </label>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Mode
                    <select name="mode" data-meeting-mode class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                      <option value="online" <?php echo $defaultMeetingMode === 'online' ? 'selected' : ''; ?>>En ligne</option>
                      <option value="onsite" <?php echo $defaultMeetingMode === 'onsite' ? 'selected' : ''; ?>>Présentiel</option>
                    </select>
                  </label>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 md:col-span-3" data-meeting-online>
                    Lien
                    <input name="link" value="<?php echo e((string) ($exam['meeting_link'] ?? '')); ?>" placeholder="https://..." class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                  </label>
                  <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 md:col-span-2" data-meeting-onsite>
                    Lieu
                    <input name="place" value="<?php echo e((string) ($exam['meeting_place'] ?? '')); ?>" placeholder="Adresse ou salle" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                  </label>
                  <div class="md:col-span-5 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700">
                      <i class="fas fa-plus" aria-hidden="true"></i>
                      Ajouter
                    </button>
                  </div>
                </form>
              </div>
            </details>

            <div class="mt-5 space-y-3">
              <?php if ($meetingSlots === []): ?>
                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Aucun créneau.</div>
              <?php endif; ?>
              <?php foreach ($meetingSlots as $slot): ?>
                <?php
                  $slotStatus = (string) ($slot['status'] ?? '');
                  $slotMode = (string) ($slot['mode'] ?? 'online');
                  $slotModeLabel = $slotMode === 'onsite' ? 'Présentiel' : 'En ligne';
                  $slotDuration = 0;
                  if (!empty($slot['start_at']) && !empty($slot['end_at'])) {
                      $slotDuration = (int) round((strtotime((string) $slot['end_at']) - strtotime((string) $slot['start_at'])) / 60);
                  }
                  $slotStudent = trim((string) ($slot['first_name'] ?? '') . ' ' . (string) ($slot['last_name'] ?? ''));
                ?>
                <article class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                  <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 space-y-2">
                      <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo e($fmtDtShort($slot['start_at'] ?? null)); ?><?php if (!empty($slot['end_at'])): ?> · fin <?php echo e($fmtDtShort($slot['end_at'] ?? null)); ?><?php endif; ?></p>
                        <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($statusBadgeClass($slotStatus)); ?>"><?php echo e($statusLabel($slotStatus)); ?></span>
                      </div>
                      <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($slotModeLabel); ?><?php if ($slotDuration > 0): ?> · <?php echo e($formatDuration($slotDuration)); ?><?php endif; ?></p>
                      <?php if ($slotMode === 'online' && !empty($slot['link'])): ?>
                        <a href="<?php echo e((string) $slot['link']); ?>" target="_blank" rel="noopener" class="inline-flex max-w-full items-center gap-2 break-all text-sm font-semibold text-primary-600 underline dark:text-primary-300">
                          <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                          <?php echo e((string) $slot['link']); ?>
                        </a>
                      <?php endif; ?>
                      <?php if ($slotMode === 'onsite' && !empty($slot['place'])): ?>
                        <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo e((string) $slot['place']); ?></p>
                      <?php endif; ?>
                      <?php if ($slotStatus === 'booked' && $slotStudent !== ''): ?>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($slotStudent); ?><?php if (!empty($slot['email'])): ?> · <?php echo e((string) $slot['email']); ?><?php endif; ?></p>
                      <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                      <?php if ($slotStatus === 'booked'): ?>
                        <form method="post" data-confirm-message="Marquer ce créneau comme terminé ?">
                          <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                          <input type="hidden" name="action" value="mark_meeting_done">
                          <input type="hidden" name="meeting_id" value="<?php echo e((string) ($slot['id'] ?? 0)); ?>">
                          <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                            <i class="fas fa-check" aria-hidden="true"></i>
                            Terminer
                          </button>
                        </form>
                      <?php endif; ?>
                      <?php if (in_array($slotStatus, ['available', 'booked'], true)): ?>
                        <form method="post" data-confirm-message="Annuler ce créneau ?">
                          <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                          <input type="hidden" name="action" value="cancel_meeting_slot">
                          <input type="hidden" name="meeting_id" value="<?php echo e((string) ($slot['id'] ?? 0)); ?>">
                          <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:border-rose-300 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                            <i class="fas fa-times" aria-hidden="true"></i>
                            Annuler
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] xl:items-start">
          <div class="space-y-4">
            <div class="exam-correction-panel rounded-2xl border border-gray-200 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                  <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Correction</p>
                  <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Copies</h2>
                </div>
                <span class="inline-flex w-fit rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                  <?php echo e($formatCount(count($attempts))); ?> copie(s)
                </span>
              </div>
            </div>

            <div class="exam-attempt-list overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-card dark:border-gray-700 dark:bg-gray-800" data-exam-search-root>
              <?php if ($attempts === []): ?>
                <div class="p-6 text-sm text-gray-600 dark:text-gray-300">Aucune tentative pour ce filtre.</div>
              <?php else: ?>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                  <?php foreach ($attempts as $attempt): ?>
                    <?php
                      $attemptId = max(0, (int) ($attempt['id'] ?? 0));
                      $studentName = trim((string) ($attempt['first_name'] ?? '') . ' ' . (string) ($attempt['last_name'] ?? ''));
                      $attemptStatus = (string) ($attempt['status'] ?? '');
                      $scoreVal = ($attempt['score'] ?? null) !== null ? (int) $attempt['score'] : null;
                      $attemptVariant = max(1, min(3, (int) ($attempt['variant_index'] ?? 1)));
                      $attemptVariantLabel = (string) ($variantLabels[$attemptVariant] ?? 'Version A');
                      $attemptModeLabel = $examType === 'drive' ? 'Remise Drive' : ($examType === 'meeting' ? 'Rendez-vous' : 'QCM');
                      $attemptSearchText = implode(' ', [
                          $studentName,
                          (string) ($attempt['email'] ?? ''),
                          $statusLabel($attemptStatus),
                          !empty($attempt['submitted_at']) ? $fmtDtShort((string) $attempt['submitted_at']) : '',
                          $scoreVal !== null ? (string) $scoreVal : 'en attente',
                          $attemptVariantLabel,
                          $attemptModeLabel,
                      ]);
                      $isSelectedAttempt = $attemptId === $selectedAttemptId;
                      $attemptUrl = $attemptDetailUrl($attemptId);
                      $attemptScoreLabel = $scoreVal !== null ? (string) $scoreVal : '—';
                    ?>
                    <a
                      href="<?php echo e($attemptUrl); ?>"
                      data-exam-search-item
                      data-search-text="<?php echo e($attemptSearchText); ?>"
                      aria-current="<?php echo $isSelectedAttempt ? 'true' : 'false'; ?>"
                      class="exam-attempt-card block border-l-4 px-4 py-3 transition <?php echo $isSelectedAttempt ? 'border-primary-500 bg-primary-50/70 dark:bg-primary-500/10' : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-900/60'; ?>"
                    >
                      <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                          <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-base font-bold text-gray-900 dark:text-white"><?php echo e($studentName !== '' ? $studentName : 'Étudiant'); ?></h3>
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($statusBadgeClass($attemptStatus)); ?>"><?php echo e($statusLabel($attemptStatus)); ?></span>
                            <?php if ($examType === 'qcm'): ?>
                              <span class="inline-flex rounded-full border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300"><?php echo e($attemptVariantLabel); ?></span>
                            <?php endif; ?>
                          </div>
                          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <?php echo e(!empty($attempt['email']) ? (string) $attempt['email'] : ''); ?>
                            <?php if (!empty($attempt['submitted_at'])): ?>
                              <?php echo !empty($attempt['email']) ? ' · ' : ''; ?>Soumis le <?php echo e($fmtDtShort((string) $attempt['submitted_at'])); ?>
                            <?php endif; ?>
                          </p>
                          <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-semibold">
                            <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"><?php echo e($attemptModeLabel); ?></span>
                            <?php if ($scoreVal === null): ?>
                              <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">En attente</span>
                            <?php elseif ($scoreVal >= $minScore): ?>
                              <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">Réussi</span>
                            <?php else: ?>
                              <span class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">Non validé</span>
                            <?php endif; ?>
                            <?php if ($isSelectedAttempt): ?>
                              <span class="inline-flex rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-primary-700 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200">Active</span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="shrink-0 text-right">
                          <p class="text-lg font-bold text-gray-900 dark:text-white"><?php echo e($attemptScoreLabel); ?><?php if ($examType === 'qcm' && $scoreVal !== null && ($maxScoreByVariant[$attemptVariant] ?? 0) > 0): ?>/<?php echo e((string) (int) ($maxScoreByVariant[$attemptVariant] ?? 0)); ?><?php endif; ?></p>
                          <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">Voir</p>
                        </div>
                      </div>
                    </a>
                  <?php endforeach; ?>
                </div>
                <div data-exam-search-empty class="hidden p-6 text-sm text-gray-600 dark:text-gray-300">
                  Aucune copie ne correspond à cette recherche.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <aside class="space-y-4 xl:sticky xl:top-6">
            <?php if ($selectedAttempt !== null): ?>
              <?php
                $selectedAttemptRow = is_array($selectedAttempt) ? $selectedAttempt : [];
                $selectedAttemptId = max(0, (int) ($selectedAttemptRow['id'] ?? 0));
                $selectedStudentName = trim((string) ($selectedAttemptRow['first_name'] ?? '') . ' ' . (string) ($selectedAttemptRow['last_name'] ?? ''));
                $selectedEmail = trim((string) ($selectedAttemptRow['email'] ?? ''));
                $selectedStatus = (string) ($selectedAttemptRow['status'] ?? '');
                $selectedScore = ($selectedAttemptRow['score'] ?? null) !== null ? (int) $selectedAttemptRow['score'] : null;
                $selectedAutoScore = ($selectedAttemptRow['auto_score'] ?? null) !== null ? (int) $selectedAttemptRow['auto_score'] : null;
                $selectedManualScore = ($selectedAttemptRow['manual_score'] ?? null) !== null ? (int) $selectedAttemptRow['manual_score'] : null;
                $selectedPassed = $selectedScore !== null && $selectedScore >= $minScore;
                $selectedVariant = max(1, min(3, (int) ($selectedAttemptRow['variant_index'] ?? 1)));
                $selectedVariantLabel = (string) ($variantLabels[$selectedVariant] ?? 'Version A');
                $selectedQuestions = $examType === 'qcm' ? ($questionsByVariant[$selectedVariant] ?? $questionsByVariant[1] ?? []) : [];
                $selectedMaxScore = max(0, (int) ($maxScoreByVariant[$selectedVariant] ?? 0));
                $selectedManualMax = max(0, (int) ($manualMaxByVariant[$selectedVariant] ?? $manualMax));
                $selectedFile = is_array($filesByAttempt[$selectedAttemptId] ?? null) ? $filesByAttempt[$selectedAttemptId] : [];
                $selectedAnswers = is_array($answersByAttempt[$selectedAttemptId] ?? null) ? $answersByAttempt[$selectedAttemptId] : [];
                $selectedMeetingStart = $selectedAttemptRow['meeting_start'] ?? null;
                $selectedMeetingEnd = $selectedAttemptRow['meeting_end'] ?? null;
                $selectedMeetingStatus = (string) ($selectedAttemptRow['meeting_status'] ?? '');
                $selectedMeetingMode = (string) ($selectedAttemptRow['meeting_mode'] ?? ($exam['meeting_mode'] ?? 'online'));
                $selectedMeetingLink = $selectedAttemptRow['meeting_link'] ?? ($exam['meeting_link'] ?? null);
                $selectedMeetingPlace = $selectedAttemptRow['meeting_place'] ?? ($exam['meeting_place'] ?? null);
                $selectedMeetingModeLabel = $selectedMeetingMode === 'onsite' ? 'Présentiel' : 'En ligne';
                $selectedSubmittedAt = !empty($selectedAttemptRow['submitted_at']) ? $fmtDtShort((string) $selectedAttemptRow['submitted_at']) : (!empty($selectedAttemptRow['created_at']) ? $fmtDtShort((string) $selectedAttemptRow['created_at']) : '');
                $selectedPrevUrl = $selectedAttemptPrev ? $attemptDetailUrl((int) ($selectedAttemptPrev['id'] ?? 0)) : '';
                $selectedNextUrl = $selectedAttemptNext ? $attemptDetailUrl((int) ($selectedAttemptNext['id'] ?? 0)) : '';
              ?>
              <section class="exam-active-copy rounded-2xl border border-gray-200 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                  <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Copie active</p>
                    <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?php echo e($selectedStudentName !== '' ? $selectedStudentName : 'Étudiant'); ?></h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo e($selectedEmail !== '' ? $selectedEmail : 'Aucun email renseigné'); ?><?php if ($selectedSubmittedAt !== ''): ?> · <?php echo e($selectedSubmittedAt); ?><?php endif; ?></p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                      <span class="inline-flex rounded-full border px-2.5 py-1 <?php echo e($statusBadgeClass($selectedStatus)); ?>"><?php echo e($statusLabel($selectedStatus)); ?></span>
                      <?php if ($examType === 'qcm'): ?>
                        <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"><?php echo e($selectedVariantLabel); ?></span>
                      <?php else: ?>
                        <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"><?php echo e($typeInfo['label']); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="exam-scorebox rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-right dark:border-gray-700 dark:bg-gray-900">
                    <?php if ($selectedScore !== null): ?>
                      <p class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $selectedScore); ?><?php if ($examType === 'qcm' && $selectedMaxScore > 0): ?>/<?php echo e((string) $selectedMaxScore); ?><?php endif; ?></p>
                      <p class="mt-1 text-xs font-semibold <?php echo e($selectedPassed ? 'text-emerald-700 dark:text-emerald-200' : 'text-rose-700 dark:text-rose-200'); ?>"><?php echo e($selectedPassed ? 'Réussi' : 'Non validé'); ?></p>
                    <?php else: ?>
                      <p class="text-3xl font-bold text-gray-400 dark:text-gray-500">—</p>
                      <p class="mt-1 text-xs font-semibold text-amber-700 dark:text-amber-200">En attente</p>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                  <?php if ($selectedPrevUrl !== ''): ?>
                    <a href="<?php echo e($selectedPrevUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                      <i class="fas fa-arrow-left" aria-hidden="true"></i>
                      Précédente
                    </a>
                  <?php endif; ?>
                  <?php if ($selectedNextUrl !== ''): ?>
                    <a href="<?php echo e($selectedNextUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200">
                      Suivante
                      <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                  <?php endif; ?>
                  <a href="<?php echo e($attemptDetailUrl($selectedAttemptId)); ?>" class="inline-flex items-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-semibold text-primary-700 transition hover:border-primary-300 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200">
                    <i class="fas fa-redo-alt" aria-hidden="true"></i>
                    Recharger
                  </a>
                </div>
              </section>

              <section class="exam-detail-card rounded-2xl border border-gray-200 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800">
                <?php if ($examType === 'drive'): ?>
                  <?php
                    $selectedDriveLink = '';
                    if (!empty($selectedFile['drive_view_link'])) {
                        $selectedDriveLink = (string) $selectedFile['drive_view_link'];
                    } elseif (!empty($selectedFile['drive_link'])) {
                        $selectedDriveLink = (string) $selectedFile['drive_link'];
                    }
                    $selectedDriveName = trim((string) ($selectedFile['original_name'] ?? ''));
                  ?>
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Dépôt</p>
                      <h4 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Fichier envoyé</h4>
                    </div>
                    <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                      <?php echo e($statusLabel($selectedStatus)); ?>
                    </span>
                  </div>
                  <?php if ($selectedDriveLink !== ''): ?>
                    <a class="mt-4 inline-flex max-w-full items-center gap-2 break-all text-sm font-semibold text-primary-600 underline dark:text-primary-300" href="<?php echo e($selectedDriveLink); ?>" target="_blank" rel="noopener">
                      <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                      <?php echo e($selectedDriveName !== '' ? $selectedDriveName : $selectedDriveLink); ?>
                    </a>
                  <?php else: ?>
                    <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Aucun fichier disponible.</div>
                  <?php endif; ?>
                  <?php if (!empty($selectedFile['comment'])): ?>
                    <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                      <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Commentaire</p>
                      <p class="mt-2 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-200"><?php echo e((string) $selectedFile['comment']); ?></p>
                    </div>
                  <?php endif; ?>
                <?php elseif ($examType === 'meeting'): ?>
                  <div class="flex items-center justify-between gap-3">
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Rendez-vous</p>
                      <h4 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Créneau associé</h4>
                    </div>
                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($statusBadgeClass($selectedMeetingStatus)); ?>"><?php echo e($statusLabel($selectedMeetingStatus)); ?></span>
                  </div>
                  <p class="mt-3 text-sm text-gray-700 dark:text-gray-200"><?php echo e($selectedMeetingStart ? $fmtDtShort((string) $selectedMeetingStart) : 'Créneau non associé'); ?><?php if ($selectedMeetingEnd): ?> · fin <?php echo e($fmtDtShort((string) $selectedMeetingEnd)); ?><?php endif; ?></p>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo e($selectedMeetingModeLabel); ?></p>
                  <?php if ($selectedMeetingMode === 'online' && !empty($selectedMeetingLink)): ?>
                    <a class="mt-3 inline-flex max-w-full items-center gap-2 break-all text-sm font-semibold text-primary-600 underline dark:text-primary-300" href="<?php echo e((string) $selectedMeetingLink); ?>" target="_blank" rel="noopener">
                      <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                      <?php echo e((string) $selectedMeetingLink); ?>
                    </a>
                  <?php endif; ?>
                  <?php if ($selectedMeetingMode === 'onsite' && !empty($selectedMeetingPlace)): ?>
                    <p class="mt-3 text-sm text-gray-700 dark:text-gray-200"><?php echo e((string) $selectedMeetingPlace); ?></p>
                  <?php endif; ?>
                <?php else: ?>
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Correction détaillée</p>
                      <h4 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Questions et réponses</h4>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold">
                      <?php if ($selectedAutoScore !== null): ?>
                        <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Auto <?php echo e((string) $selectedAutoScore); ?><?php if ($selectedMaxScore > 0): ?>/<?php echo e((string) $selectedMaxScore); ?><?php endif; ?></span>
                      <?php endif; ?>
                      <?php if ($selectedManualScore !== null): ?>
                        <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Manuel <?php echo e((string) $selectedManualScore); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <?php if ($selectedQuestions !== []): ?>
                    <div class="mt-4 space-y-3">
                      <?php foreach ($selectedQuestions as $questionIndex => $question): ?>
                        <?php
                          $questionId = max(0, (int) ($question['id'] ?? 0));
                          $questionType = (string) ($question['question_type'] ?? 'single');
                          $questionPoints = max(0, (int) ($question['points'] ?? 0));
                          $answer = $selectedAnswers[$questionId] ?? ['options' => [], 'text' => null];
                          $selectedTexts = [];
                          foreach (($answer['options'] ?? []) as $optionId) {
                              if (isset($optionTextById[(int) $optionId])) {
                                  $selectedTexts[] = (string) $optionTextById[(int) $optionId];
                              }
                          }
                          $correctTexts = $correctTextByQuestion[$questionId] ?? [];
                        ?>
                        <article class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                          <p class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($questionIndex + 1)); ?>. <?php echo e((string) ($question['question_text'] ?? 'Question')); ?> <span class="text-xs text-gray-500 dark:text-gray-400">(<?php echo e((string) $questionPoints); ?> pt)</span></p>
                          <?php if ($questionType === 'text'): ?>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Réponse</p>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-200"><?php echo e((string) ($answer['text'] ?? '')); ?></p>
                          <?php else: ?>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Sélection</p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200"><?php echo e($selectedTexts !== [] ? implode(' | ', $selectedTexts) : 'Aucune'); ?></p>
                            <?php if ($correctTexts !== []): ?>
                              <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Correction attendue</p>
                              <p class="mt-1 text-sm text-gray-700 dark:text-gray-200"><?php echo e(implode(' | ', $correctTexts)); ?></p>
                            <?php endif; ?>
                          <?php endif; ?>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </section>

              <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
                <?php if ($examType === 'qcm' && $selectedManualMax > 0): ?>
                  <form method="post" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]" data-confirm-message="Enregistrer cette note ?">
                    <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="grade_attempt">
                    <input type="hidden" name="attempt_id" value="<?php echo e((string) $selectedAttemptId); ?>">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                      Note manuelle
                      <input type="number" name="manual_score" min="0" max="<?php echo e((string) $selectedManualMax); ?>" value="<?php echo e((string) ($selectedManualScore ?? 0)); ?>" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </label>
                    <div class="flex items-end">
                      <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Enregistrer
                      </button>
                    </div>
                  </form>
                <?php elseif ($examType !== 'qcm'): ?>
                  <form method="post" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto]" data-confirm-message="Enregistrer cette note ?">
                    <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="grade_attempt">
                    <input type="hidden" name="attempt_id" value="<?php echo e((string) $selectedAttemptId); ?>">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200">
                      Note
                      <input type="number" name="score" min="0" value="<?php echo e((string) ($selectedScore ?? 0)); ?>" class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    </label>
                    <div class="flex items-end">
                      <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Enregistrer
                      </button>
                    </div>
                  </form>
                <?php else: ?>
                  <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <?php if ($selectedManualMax <= 0): ?>
                      Aucune note manuelle à saisir pour cette copie.
                    <?php else: ?>
                      La note finale est calculée automatiquement puis ajustée manuellement.
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </section>
            <?php else: ?>
              <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Correction</p>
                <h3 class="mt-2 text-lg font-bold text-gray-900 dark:text-white">Aucune copie sélectionnée</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Choisissez une copie dans la file pour afficher son détail et la corriger.</p>
              </section>
            <?php endif; ?>
          </aside>
        </section>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
