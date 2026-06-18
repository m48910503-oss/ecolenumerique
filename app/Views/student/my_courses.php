<?php
$dashboardUrl = site_url('student/dashboard');
$myCoursesUrl = site_url('student/my_courses');
$calendarUrl = site_url('student/meetings');
$certificatesUrl = site_url('student/certificates');
$favoritesUrl = site_url('student/favorites');
$ordersUrl = site_url('student/orders');
$notificationsUrl = site_url('notifications?space=student');
$referralsUrl = site_url('student/referrals');
$supportUrl = site_url('support?space=student');
$profileUrl = site_url('profile?space=student');
$homeUrl = site_url('');
$messagesUrl = site_url('support?space=student');
$settingsUrl = site_url('profile?space=student');
$catalogueUrl = site_url('catalogue');
$logoutUrl = site_url('logout');
$logoutCsrf = function_exists('view_csrf_token') ? view_csrf_token() : '';
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$sidebarToggleJsUrl = site_url('assets/js/sidebar_toggle.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/sidebar_toggle.js') ?: time()));
$studentMyCoursesJsUrl = site_url('assets/js/student_my_courses.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_my_courses.js') ?: time()));
$courseRatingJsUrl = site_url('assets/js/course_rating.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_rating.js') ?: time()));
$studentDashboardCssUrl = site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()));

$dashboardUser = is_array($user ?? null) ? $user : [];
$dashboardDisplayName = dashboard_user_display_name($dashboardUser, 'Utilisateur');
$dashboardEmail = trim((string) ($dashboardUser['email'] ?? ''));
$dashboardFirstName = trim((string) ($dashboardUser['first_name'] ?? ''));
if ($dashboardFirstName === '') {
    $displayNameParts = preg_split('/\s+/', $dashboardDisplayName) ?: [];
    $candidateFirstName = trim((string) ($displayNameParts[0] ?? ''));
    if ($candidateFirstName === '' && $dashboardEmail !== '') {
        $emailPrefix = trim((string) explode('@', $dashboardEmail, 2)[0]);
        $emailPrefix = str_replace(['.', '_', '-'], ' ', $emailPrefix);
        $emailParts = preg_split('/\s+/', $emailPrefix) ?: [];
        $candidateFirstName = trim((string) ($emailParts[0] ?? ''));
    }
    $dashboardFirstName = $candidateFirstName !== '' ? $candidateFirstName : 'Étudiant';
}
$dashboardAvatarUrl = dashboard_user_avatar_url($dashboardUser);
$dashboardInitials = dashboard_user_initials($dashboardUser, 'ET');
$dashboardSidebarName = $dashboardDisplayName;
if ($dashboardEmail !== '' && strcasecmp($dashboardSidebarName, $dashboardEmail) === 0) {
    $dashboardSidebarName = 'Compte étudiant';
}

$activeEnrollments = max(0, (int) ($enrollmentsCount ?? 0));
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$coursesPagination = is_array($coursesPagination ?? null) ? $coursesPagination : ['page' => 1];
$levelLabels = is_array($levelLabels ?? null) ? $levelLabels : [];

$fmtDtShort = static function (?string $dbValue): string {
    if (!$dbValue) {
        return '';
    }

    $ts = strtotime($dbValue);
    return $ts ? date('d/m/Y H:i', $ts) : (string) $dbValue;
};

$normalizeText = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);
    if (is_string($transliterated) && $transliterated !== '') {
        return strtolower($transliterated);
    }

    return $lower;
};

$resolveCourseUrl = static function (array $course): string {
    $slug = trim((string) ($course['slug'] ?? ''));
    if ($slug !== '') {
        return site_url('course/watch?slug=' . urlencode($slug));
    }

    $courseId = max(0, (int) ($course['id'] ?? $course['course_id'] ?? 0));
    if ($courseId > 0) {
        return site_url('course/watch?id=' . $courseId);
    }

    return site_url('student/my_courses');
};

$resolveCourseCoverUrl = static function (array $course): string {
    $coverPath = trim((string) ($course['cover_url'] ?? $course['cover_path'] ?? ''));
    if ($coverPath === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $coverPath) === 1 || str_starts_with($coverPath, '//') || str_starts_with($coverPath, 'data:')) {
        return $coverPath;
    }

    return site_url(ltrim($coverPath, '/'));
};

$resolveLevelLabel = static function (string $level) use ($levelLabels): string {
    $level = trim($level);
    if ($level !== '' && isset($levelLabels[$level])) {
        return (string) $levelLabels[$level];
    }

    if ($level === '') {
        return 'Niveau non renseigné';
    }

    return ucfirst(str_replace(['-', '_'], ' ', $level));
};

$courseInitials = static function (string $title): string {
    $title = trim($title);
    if ($title === '') {
        return 'EN';
    }

    $parts = preg_split('/\s+/', $title) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $clean = preg_replace('/[^[:alnum:]]+/u', '', $part);
        if ($clean === null || $clean === '') {
            continue;
        }
        $letters .= function_exists('mb_substr') ? mb_substr($clean, 0, 1, 'UTF-8') : substr($clean, 0, 1);
    }

    if ($letters === '') {
        $letters = function_exists('mb_substr') ? mb_substr($title, 0, 2, 'UTF-8') : substr($title, 0, 2);
    }

    $letters = strtoupper($letters);
    return $letters !== '' ? $letters : 'EN';
};

$formatCourseTitle = static function (string $title): string {
    $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);
    if ($title === '') {
        return 'Cours';
    }

    $hadUppercase = preg_match('/\p{Lu}/u', $title) === 1;
    $acronyms = ['api', 'css', 'html', 'ia', 'js', 'php', 'sql', 'ui', 'ux'];
    $words = preg_split('/\s+/', $title) ?: [$title];
    foreach ($words as &$word) {
        $clean = function_exists('mb_strtolower') ? mb_strtolower($word, 'UTF-8') : strtolower($word);
        if (in_array($clean, $acronyms, true)) {
            $word = strtoupper($clean);
        }
    }
    unset($word);

    $title = implode(' ', $words);
    $lower = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
    if (!$hadUppercase || $title === $lower) {
        $first = function_exists('mb_substr') ? mb_substr($title, 0, 1, 'UTF-8') : substr($title, 0, 1);
        $rest = function_exists('mb_substr') ? mb_substr($title, 1, null, 'UTF-8') : substr($title, 1);
        $title = (function_exists('mb_strtoupper') ? mb_strtoupper($first, 'UTF-8') : strtoupper($first)) . $rest;
    }

    return $title;
};

$countLabel = static function (int $count, string $singular, string $plural): string {
    return $count . ' ' . ($count === 1 ? $singular : $plural);
};

$formatTeacherName = static function (array $row): string {
    $firstName = trim((string) ($row['teacher_first_name'] ?? $row['first_name'] ?? ''));
    $lastName = trim((string) ($row['teacher_last_name'] ?? $row['last_name'] ?? ''));
    $name = trim($firstName . ' ' . $lastName);
    if ($name !== '') {
        return function_exists('mb_convert_case') ? mb_convert_case($name, MB_CASE_TITLE, 'UTF-8') : ucwords(strtolower($name));
    }

    $email = trim((string) ($row['teacher_email'] ?? ''));
    if ($email === '') {
        return '';
    }

    $prefix = trim((string) explode('@', $email, 2)[0]);
    $prefix = trim(str_replace(['.', '_', '-'], ' ', $prefix));
    return $prefix !== '' ? (function_exists('mb_convert_case') ? mb_convert_case($prefix, MB_CASE_TITLE, 'UTF-8') : ucwords(strtolower($prefix))) : '';
};

$enrollmentsList = is_array($enrollments ?? null) ? $enrollments : [];
$courseCards = [];
$inProgressCoursesCount = 0;
$completedCoursesCount = 0;
$notStartedCoursesCount = 0;
$archivedCoursesCount = 0;
$totalProgressPct = 0;

foreach ($enrollmentsList as $enrollmentRow) {
    $totalLessons = max(0, (int) ($enrollmentRow['total_lessons'] ?? 0));
    $doneLessons = max(0, (int) ($enrollmentRow['done_lessons'] ?? 0));
    if ($totalLessons > 0) {
        $doneLessons = min($doneLessons, $totalLessons);
    } else {
        $doneLessons = 0;
    }
    $progressPct = $totalLessons > 0 ? (int) floor(($doneLessons / $totalLessons) * 100) : 0;
    $enrollmentStatusRaw = strtolower(trim((string) ($enrollmentRow['enroll_status'] ?? $enrollmentRow['enrollment_status'] ?? $enrollmentRow['status'] ?? 'active')));
    $courseStatusRaw = strtolower(trim((string) ($enrollmentRow['course_status'] ?? '')));
    $isExpired = in_array($enrollmentStatusRaw, ['expired', 'expire', 'expiré', 'expirée'], true)
        || in_array($courseStatusRaw, ['expired', 'expire', 'expiré', 'expirée'], true);
    $isArchived = $isExpired
        || in_array($enrollmentStatusRaw, ['archived', 'archive', 'archivé', 'archivée', 'cancelled', 'canceled', 'inactive'], true)
        || in_array($courseStatusRaw, ['archived', 'archive', 'archivé', 'archivée'], true);
    $courseStatusKey = 'not-started';
    if ($isArchived) {
        $courseStatusKey = 'archived';
        $archivedCoursesCount++;
    } elseif ($progressPct >= 100 && $totalLessons > 0) {
        $courseStatusKey = 'completed';
        $completedCoursesCount++;
    } elseif ($doneLessons > 0) {
        $courseStatusKey = 'in-progress';
        $inProgressCoursesCount++;
    } else {
        $notStartedCoursesCount++;
    }

    $courseId = max(0, (int) ($enrollmentRow['id'] ?? $enrollmentRow['course_id'] ?? 0));
    $courseTitle = $formatCourseTitle((string) ($enrollmentRow['title'] ?? 'Cours'));
    $courseUrl = $resolveCourseUrl($enrollmentRow);
    $coverUrl = $resolveCourseCoverUrl($enrollmentRow);
    $coverAlt = trim((string) ($enrollmentRow['cover_alt'] ?? $courseTitle));
    if ($coverAlt === '') {
        $coverAlt = $courseTitle;
    }
    $teacherName = $formatTeacherName($enrollmentRow);

    $levelKey = trim((string) ($enrollmentRow['level'] ?? ''));
    $levelLabel = $resolveLevelLabel($levelKey);
    $enrolledLabel = $fmtDtShort(isset($enrollmentRow['enrolled_at']) ? (string) $enrollmentRow['enrolled_at'] : null);
    $remainingLessons = max(0, $totalLessons - $doneLessons);
    if ($courseStatusKey === 'completed') {
        $remainingLabel = 'Cours terminé';
    } elseif ($courseStatusKey === 'archived') {
        $remainingLabel = $isExpired ? 'Accès expiré' : 'Accès archivé';
    } elseif ($courseStatusKey === 'not-started') {
        $remainingLabel = $totalLessons > 0
            ? $countLabel($totalLessons, 'leçon à suivre', 'leçons à suivre')
            : 'À commencer';
    } else {
        $remainingLabel = $remainingLessons > 0
            ? $countLabel($remainingLessons, 'leçon restante', 'leçons restantes')
            : 'À reprendre';
    }
    $lessonProgressLabel = $totalLessons > 0
        ? $doneLessons . ' sur ' . $totalLessons . ' ' . ($totalLessons === 1 ? 'leçon' : 'leçons')
        : 'Aucune leçon publiée';

    $statusLabel = 'Non commencé';
    $statusTone = 'not-started';
    $statusBadgeClass = 'course-status-badge course-status-badge--not-started';
    $progressBarClass = 'bg-amber-500';
    $ctaLabel = 'Démarrer';
    $ctaFullLabel = 'Démarrer le cours';
    $ctaIcon = 'fa-play';
    $ctaClass = 'course-action-btn course-action-btn--not-started';
    if ($courseStatusKey === 'completed') {
        $statusLabel = 'Terminé';
        $statusTone = 'completed';
        $statusBadgeClass = 'course-status-badge course-status-badge--completed';
        $progressBarClass = 'bg-green-500';
        $ctaLabel = 'Revoir';
        $ctaFullLabel = 'Revoir le cours';
        $ctaIcon = 'fa-undo-alt';
        $ctaClass = 'course-action-btn course-action-btn--completed';
    } elseif ($courseStatusKey === 'in-progress') {
        $statusLabel = 'En cours';
        $statusTone = 'in-progress';
        $statusBadgeClass = 'course-status-badge course-status-badge--in-progress';
        $progressBarClass = 'bg-blue-500';
        $ctaLabel = 'Continuer';
        $ctaFullLabel = 'Continuer le cours';
        $ctaIcon = 'fa-play';
        $ctaClass = 'course-action-btn course-action-btn--in-progress';
    } elseif ($courseStatusKey === 'archived') {
        $statusLabel = $isExpired ? 'Expiré' : 'Archivé';
        $statusTone = 'archived';
        $statusBadgeClass = 'course-status-badge course-status-badge--archived';
        $progressBarClass = 'bg-slate-400';
        $ctaLabel = 'Voir';
        $ctaFullLabel = 'Voir le cours';
        $ctaIcon = 'fa-eye';
        $ctaClass = 'course-action-btn course-action-btn--archived';
    }

    $searchText = $normalizeText(trim(implode(' ', [
        $courseTitle,
        $teacherName,
        $levelLabel,
        $statusLabel,
        (string) $doneLessons,
        (string) $totalLessons,
        $remainingLabel,
    ])));

    $sortRecent = strtotime((string) ($enrollmentRow['enrolled_at'] ?? '')) ?: 0;
    $sortAlpha = $normalizeText($courseTitle);
    $ratingValue = max(0, min(5, (int) ($enrollmentRow['user_rating'] ?? 0)));
    $ratingComment = trim((string) ($enrollmentRow['user_rating_comment'] ?? ''));
    $ratingActionLabel = $ratingValue > 0 ? "Modifier l'avis" : 'Noter';

    $courseCards[] = [
        'course_id' => $courseId,
        'course_title' => $courseTitle,
        'course_url' => $courseUrl,
        'cover_url' => $coverUrl,
        'cover_alt' => $coverAlt,
        'initials' => $courseInitials($courseTitle),
        'teacher_name' => $teacherName,
        'level_label' => $levelLabel,
        'status_key' => $courseStatusKey,
        'status_label' => $statusLabel,
        'status_tone' => $statusTone,
        'status_badge_class' => $statusBadgeClass,
        'progress_pct' => $progressPct,
        'progress_bar_class' => $progressBarClass,
        'done_lessons' => $doneLessons,
        'total_lessons' => $totalLessons,
        'lesson_progress_label' => $lessonProgressLabel,
        'remaining_label' => $remainingLabel,
        'enrolled_label' => $enrolledLabel,
        'search_text' => $searchText,
        'sort_recent' => $sortRecent,
        'sort_alpha' => $sortAlpha,
        'cta_label' => $ctaLabel,
        'cta_full_label' => $ctaFullLabel,
        'cta_icon' => $ctaIcon,
        'cta_class' => $ctaClass,
        'certificate_url' => $courseStatusKey === 'completed' ? $certificatesUrl : '',
        'can_rate' => !$isArchived,
        'rating_value' => $ratingValue,
        'rating_comment' => $ratingComment,
        'rating_action_label' => $ratingActionLabel,
    ];

    $totalProgressPct += $progressPct;
}

$activeCoursesCount = count($courseCards);
$averageProgressPct = $activeCoursesCount > 0 ? (int) floor($totalProgressPct / $activeCoursesCount) : 0;

$sortMode = strtolower(trim((string) ($_GET['sort'] ?? 'recent')));
if (!in_array($sortMode, ['recent', 'progress-asc', 'progress-desc', 'alpha'], true)) {
    $sortMode = 'recent';
}

usort($courseCards, static function (array $a, array $b) use ($sortMode): int {
    $aProgress = (int) ($a['progress_pct'] ?? 0);
    $bProgress = (int) ($b['progress_pct'] ?? 0);
    $aRecent = (int) ($a['sort_recent'] ?? 0);
    $bRecent = (int) ($b['sort_recent'] ?? 0);
    $aAlpha = (string) ($a['sort_alpha'] ?? '');
    $bAlpha = (string) ($b['sort_alpha'] ?? '');

    if ($sortMode === 'progress-asc') {
        if ($aProgress !== $bProgress) {
            return $aProgress <=> $bProgress;
        }
    } elseif ($sortMode === 'progress-desc') {
        if ($aProgress !== $bProgress) {
            return $bProgress <=> $aProgress;
        }
    } elseif ($sortMode === 'alpha') {
        $alphaCompare = strcmp($aAlpha, $bAlpha);
        if ($alphaCompare !== 0) {
            return $alphaCompare;
        }
    } else {
        if ($aRecent !== $bRecent) {
            return $bRecent <=> $aRecent;
        }
    }

    if ($aRecent !== $bRecent) {
        return $bRecent <=> $aRecent;
    }

    if ($aProgress !== $bProgress) {
        return $bProgress <=> $aProgress;
    }

    return strcmp($aAlpha, $bAlpha);
});

$filterMode = strtolower(trim((string) ($_GET['filter'] ?? 'all')));
if (!in_array($filterMode, ['all', 'in-progress', 'completed', 'not-started', 'archived'], true)) {
    $filterMode = 'all';
}

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$normalizedSearchQuery = $normalizeText($searchQuery);
$initialVisibleCourseCards = array_values(array_filter($courseCards, static function (array $card) use ($filterMode, $normalizedSearchQuery): bool {
    if ($filterMode !== 'all' && ($card['status_key'] ?? '') !== $filterMode) {
        return false;
    }

    if ($normalizedSearchQuery === '') {
        return true;
    }

    return str_contains((string) ($card['search_text'] ?? ''), $normalizedSearchQuery);
}));
$visibleCourseCards = $courseCards;

$courseSearchSummary = $activeCoursesCount > 0
    ? $countLabel($activeCoursesCount, 'cours acheté', 'cours achetés') . ' · ' . $inProgressCoursesCount . ' en cours · ' . $countLabel($completedCoursesCount, 'terminé', 'terminés')
    : 'Aucun cours acheté pour le moment';

$courseHeroSummary = $activeCoursesCount > 0
    ? 'Recherchez, filtrez et reprenez votre progression.'
    : 'Commencez votre parcours depuis le catalogue et retrouvez ici toutes vos inscriptions.';

$studentWelcomeSummary = $courseHeroSummary;
$studentWelcomeTitle = 'Votre bibliothèque de cours';
$studentWelcomeActionUrl = $catalogueUrl;
$studentWelcomeActionLabel = 'Explorer le catalogue';
$studentWelcomeActionIcon = 'search';

$courseTabCounts = [
    'all' => $activeCoursesCount,
    'in-progress' => $inProgressCoursesCount,
    'completed' => $completedCoursesCount,
    'not-started' => $notStartedCoursesCount,
    'archived' => $archivedCoursesCount,
];

$courseGridLayout = strtolower(trim((string) ($_GET['layout'] ?? 'grid')));
if (!in_array($courseGridLayout, ['grid', 'list'], true)) {
    $courseGridLayout = 'grid';
}

$courseGridClass = $courseGridLayout === 'list'
    ? 'grid grid-cols-1 gap-4'
    : 'grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3';

$courseEmptyTitle = $activeCoursesCount === 0
    ? 'Aucun cours acheté pour le moment'
    : 'Aucun cours trouvé';
$courseEmptyMessage = $activeCoursesCount === 0
    ? 'Commencez votre parcours depuis le catalogue ou retrouvez vos sélections dans vos favoris.'
    : 'Modifiez vos filtres.';
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
  <title>Mes cours · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e($studentDashboardCssUrl); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($sidebarToggleJsUrl); ?>" defer></script>
  <script src="<?php echo e($studentMyCoursesJsUrl); ?>" defer></script>
  <script src="<?php echo e($courseRatingJsUrl); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="student/my_courses" data-sidebar-desktop-toggle="0" data-rating-api-url="<?php echo e(site_url('api/rating')); ?>" class="app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">

<div class="student-dashboard-shell relative min-h-screen overflow-hidden" data-student-courses-page="1">
  <?php $studentNavActive = 'my_courses'; ?>
  <?php $studentDashboardShell = true; ?>
  <?php $studentTopTitle = 'Mes cours'; ?>
  <?php $studentTopSubtitle = 'Espace étudiant'; ?>
  <?php $studentTopSearchUrl = $myCoursesUrl; ?>
  <?php $studentTopSearchValue = $searchQuery; ?>
  <?php $studentTopSearchPlaceholder = 'Rechercher un cours'; ?>
  <?php $studentTopSearchAriaLabel = 'Rechercher un cours'; ?>
  <?php $studentTopSearchInputAttributes = 'data-course-search autocomplete="off"'; ?>
  <?php $studentTopSearchHiddenFields = [
      ['name' => 'filter', 'value' => $filterMode, 'attributes' => 'data-course-state="filter"'],
      ['name' => 'sort', 'value' => $sortMode, 'attributes' => 'data-course-state="sort"'],
      ['name' => 'layout', 'value' => $courseGridLayout, 'attributes' => 'data-course-state="layout"'],
  ]; ?>
  <?php $studentTopHideSupportFab = false; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
    <main class="student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
      <?php require __DIR__ . '/_topbar.php'; ?>

      <div class="student-dashboard-content p-4 pb-24 space-y-5 animate-fade-up sm:p-6 sm:pb-24 sm:space-y-6 lg:space-y-8">
        <?php require __DIR__ . '/_welcome_hero.php'; ?>

        <section class="student-dashboard-panel course-library-toolbar bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 sm:p-5">
          <div class="course-library-toolbar__inner">
            <div class="course-library-toolbar__copy">
              <p class="course-library-toolbar__eyebrow">Organisation</p>
              <p class="course-library-toolbar__summary"><?php echo e($courseSearchSummary); ?></p>
            </div>
            <div class="course-library-toolbar__actions">
              <div class="course-view-toggle" role="group" aria-label="Mode d’affichage">
                <button
                  id="view-grid"
                  type="button"
                  data-course-view="grid"
                  class="course-view-toggle__button px-3 py-1.5 rounded-md text-sm font-medium bg-white text-slate-900 shadow-sm transition"
                  aria-pressed="true"
                >
                  <i class="fas fa-th-large mr-1"></i> Grille
                </button>
                <button
                  id="view-list"
                  type="button"
                  data-course-view="list"
                  class="course-view-toggle__button px-3 py-1.5 rounded-md text-sm font-medium text-blue-900/90 transition hover:text-slate-950"
                  aria-pressed="false"
                >
                  <i class="fas fa-list mr-1"></i> Liste
                </button>
              </div>
              <label class="course-sort-control" for="course-sort">
                <i class="fas fa-sort-amount-down" aria-hidden="true"></i>
                <select id="course-sort" data-course-sort>
                  <option value="recent"<?php echo $sortMode === 'recent' ? ' selected' : ''; ?>>Récents</option>
                  <option value="progress-asc"<?php echo $sortMode === 'progress-asc' ? ' selected' : ''; ?>>Progression croissante</option>
                  <option value="progress-desc"<?php echo $sortMode === 'progress-desc' ? ' selected' : ''; ?>>Progression décroissante</option>
                  <option value="alpha"<?php echo $sortMode === 'alpha' ? ' selected' : ''; ?>>Alphabétique</option>
                </select>
              </label>
            </div>
          </div>
        </section>

        <div class="flex border-b border-gray-200 dark:border-gray-700 gap-6 overflow-x-auto">
          <?php
            $tabBaseClass = 'tab-filter pb-3 text-sm border-b-2 border-transparent transition whitespace-nowrap';
            $tabActiveClass = 'tab-active text-blue-600 dark:text-blue-400 font-semibold border-blue-600 dark:border-blue-400';
            $tabInactiveClass = 'font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-300';
          ?>
          <button type="button" class="<?php echo e($tabBaseClass . ' ' . ($filterMode === 'all' ? $tabActiveClass : $tabInactiveClass)); ?>" data-course-filter="all" aria-pressed="<?php echo $filterMode === 'all' ? 'true' : 'false'; ?>">
            Tous les cours <span class="ml-1.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full"><?php echo e((string) ($courseTabCounts['all'] ?? 0)); ?></span>
          </button>
          <button type="button" class="<?php echo e($tabBaseClass . ' ' . ($filterMode === 'in-progress' ? $tabActiveClass : $tabInactiveClass)); ?>" data-course-filter="in-progress" aria-pressed="<?php echo $filterMode === 'in-progress' ? 'true' : 'false'; ?>">
            En cours <span class="ml-1.5 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs px-2 py-0.5 rounded-full"><?php echo e((string) ($courseTabCounts['in-progress'] ?? 0)); ?></span>
          </button>
          <button type="button" class="<?php echo e($tabBaseClass . ' ' . ($filterMode === 'completed' ? $tabActiveClass : $tabInactiveClass)); ?>" data-course-filter="completed" aria-pressed="<?php echo $filterMode === 'completed' ? 'true' : 'false'; ?>">
            Terminés <span class="ml-1.5 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 text-xs px-2 py-0.5 rounded-full"><?php echo e((string) ($courseTabCounts['completed'] ?? 0)); ?></span>
          </button>
          <button type="button" class="<?php echo e($tabBaseClass . ' ' . ($filterMode === 'not-started' ? $tabActiveClass : $tabInactiveClass)); ?>" data-course-filter="not-started" aria-pressed="<?php echo $filterMode === 'not-started' ? 'true' : 'false'; ?>">
            Non commencés <span class="ml-1.5 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 text-xs px-2 py-0.5 rounded-full"><?php echo e((string) ($courseTabCounts['not-started'] ?? 0)); ?></span>
          </button>
          <?php if (($courseTabCounts['archived'] ?? 0) > 0): ?>
            <button type="button" class="<?php echo e($tabBaseClass . ' ' . ($filterMode === 'archived' ? $tabActiveClass : $tabInactiveClass)); ?>" data-course-filter="archived" aria-pressed="<?php echo $filterMode === 'archived' ? 'true' : 'false'; ?>">
              Archivés <span class="ml-1.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full"><?php echo e((string) ($courseTabCounts['archived'] ?? 0)); ?></span>
            </button>
          <?php endif; ?>
        </div>

        <?php if ($activeCoursesCount === 0): ?>
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="flex flex-col items-center justify-center gap-4 px-6 py-14 text-center">
              <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-300">
                <?php echo dashboard_icon_svg('search', 'class="h-8 w-8 shrink-0"'); ?>
              </div>
              <div class="max-w-md">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo e($courseEmptyTitle); ?></h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo e($courseEmptyMessage); ?></p>
              </div>
              <div class="flex flex-wrap items-center justify-center gap-3">
                <?php if ($activeCoursesCount === 0): ?>
                  <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <i class="fas fa-search"></i>
                    Découvrir le catalogue
                  </a>
                  <a href="<?php echo e(site_url('student/favorites')); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                    <i class="fas fa-heart"></i>
                    Voir mes favoris
                  </a>
                <?php else: ?>
                  <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" data-course-reset>
                    <i class="fas fa-undo-alt"></i>
                    Réinitialiser les filtres
                  </button>
                  <a href="<?php echo e($myCoursesUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                    <i class="fas fa-list"></i>
                    Voir tous les cours
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div id="courses-container" class="student-dashboard-course-grid <?php echo e($courseGridClass); ?>" data-course-grid data-layout="<?php echo e($courseGridLayout); ?>">
            <?php foreach ($visibleCourseCards as $index => $courseCard): ?>
              <?php
                $courseCardDelay = min(300, 50 + ((int) $index * 45));
                $statusKey = (string) ($courseCard['status_key'] ?? 'all');
                $statusLabel = (string) ($courseCard['status_label'] ?? 'Cours');
                $statusBadgeClass = (string) ($courseCard['status_badge_class'] ?? 'course-status-badge course-status-badge--not-started');
                $courseId = max(0, (int) ($courseCard['course_id'] ?? 0));
                $progressPct = max(0, min(100, (int) ($courseCard['progress_pct'] ?? 0)));
                $courseUrl = (string) ($courseCard['course_url'] ?? '#');
                $progressLabel = $progressPct . '%';
                $doneLessons = max(0, (int) ($courseCard['done_lessons'] ?? 0));
                $totalLessons = max(0, (int) ($courseCard['total_lessons'] ?? 0));
                $lessonProgressLabel = (string) ($courseCard['lesson_progress_label'] ?? '');
                $remainingLabel = (string) ($courseCard['remaining_label'] ?? '');
                $enrolledLabel = (string) ($courseCard['enrolled_label'] ?? '');
                $progressBarClass = (string) ($courseCard['progress_bar_class'] ?? 'bg-blue-500');
                $courseCoverUrl = (string) ($courseCard['cover_url'] ?? '');
                $courseCoverAlt = (string) ($courseCard['cover_alt'] ?? '');
                $courseTitle = (string) ($courseCard['course_title'] ?? 'Cours');
                $teacherName = (string) ($courseCard['teacher_name'] ?? '');
                $levelLabel = (string) ($courseCard['level_label'] ?? 'Niveau');
                $initials = (string) ($courseCard['initials'] ?? 'EN');
                $ctaLabel = (string) ($courseCard['cta_label'] ?? 'Continuer');
                $ctaFullLabel = (string) ($courseCard['cta_full_label'] ?? ($ctaLabel . ' le cours'));
                $ctaIcon = (string) ($courseCard['cta_icon'] ?? 'fa-play');
                $ctaClass = (string) ($courseCard['cta_class'] ?? 'course-action-btn course-action-btn--in-progress');
                $certificateUrl = (string) ($courseCard['certificate_url'] ?? '');
                $canRate = !empty($courseCard['can_rate']);
                $ratingValue = max(0, min(5, (int) ($courseCard['rating_value'] ?? 0)));
                $ratingComment = (string) ($courseCard['rating_comment'] ?? '');
                $ratingActionLabel = (string) ($courseCard['rating_action_label'] ?? ($ratingValue > 0 ? "Modifier l'avis" : 'Noter'));
                $sortRecent = (int) ($courseCard['sort_recent'] ?? 0);
                $sortAlpha = (string) ($courseCard['sort_alpha'] ?? '');
                $searchText = (string) ($courseCard['search_text'] ?? '');
                $matchesInitialFilter = $filterMode === 'all' || $statusKey === $filterMode;
                $matchesInitialSearch = $normalizedSearchQuery === '' || str_contains($searchText, $normalizedSearchQuery);
                $cardVisibilityClass = ($matchesInitialFilter && $matchesInitialSearch) ? '' : ' hidden';
              ?>
              <article
                class="course-card course-card-enter student-dashboard-course-card bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden hover-lift group cursor-pointer<?php echo e($cardVisibilityClass); ?>"
                data-course-card="1"
                data-course-id="<?php echo e((string) $courseId); ?>"
                data-status="<?php echo e($statusKey); ?>"
                data-title="<?php echo e($courseTitle); ?>"
                data-search-text="<?php echo e($searchText); ?>"
                data-sort-recent="<?php echo e((string) $sortRecent); ?>"
                data-sort-alpha="<?php echo e($sortAlpha); ?>"
                data-progress="<?php echo e((string) $progressPct); ?>"
                data-cover-url="<?php echo e($courseCoverUrl); ?>"
                data-cover-alt="<?php echo e($courseCoverAlt); ?>"
                data-level-label="<?php echo e($levelLabel); ?>"
                data-status-label="<?php echo e($statusLabel); ?>"
                data-status-badge-class="<?php echo e($statusBadgeClass); ?>"
                data-course-url="<?php echo e($courseUrl); ?>"
                data-done-lessons="<?php echo e((string) $doneLessons); ?>"
                data-total-lessons="<?php echo e((string) $totalLessons); ?>"
                data-lesson-progress-label="<?php echo e($lessonProgressLabel); ?>"
                data-remaining-label="<?php echo e($remainingLabel); ?>"
                data-enrolled-label="<?php echo e($enrolledLabel); ?>"
                data-cta-label="<?php echo e($ctaLabel); ?>"
                data-cta-full-label="<?php echo e($ctaFullLabel); ?>"
                data-cta-icon="<?php echo e($ctaIcon); ?>"
                data-cta-class="<?php echo e($ctaClass); ?>"
                data-can-rate="<?php echo $canRate ? '1' : '0'; ?>"
                data-rating-value="<?php echo e((string) $ratingValue); ?>"
                data-rating-comment="<?php echo e($ratingComment); ?>"
                style="animation-delay: <?php echo e((string) $courseCardDelay); ?>ms;"
              >
                <div class="relative">
                  <?php if ($courseCoverUrl !== ''): ?>
                    <img src="<?php echo e($courseCoverUrl); ?>" class="h-40 w-full object-cover group-hover:scale-105 transition duration-500" alt="<?php echo e($courseCoverAlt !== '' ? $courseCoverAlt : $courseTitle); ?>">
                  <?php else: ?>
                    <div class="h-40 w-full bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-500 p-4 text-white flex items-end justify-between gap-4">
                      <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-white/80">Cours</p>
                        <h3 class="mt-2 text-lg font-bold leading-snug"><?php echo e($courseTitle); ?></h3>
                      </div>
                      <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-lg font-bold uppercase backdrop-blur-sm">
                        <?php echo e($initials); ?>
                      </div>
                    </div>
                  <?php endif; ?>
                  <div class="absolute top-3 left-3">
                    <span class="<?php echo e($statusBadgeClass); ?>"><?php echo e($statusLabel); ?></span>
                  </div>
                  <div class="absolute top-3 right-3">
                    <a href="<?php echo e(site_url('student/favorites')); ?>" class="w-8 h-8 bg-white/90 dark:bg-gray-800/90 rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 transition shadow-sm" title="Voir les favoris" aria-label="Voir les favoris">
                      <i class="far fa-heart"></i>
                    </a>
                  </div>
                  <div class="absolute bottom-0 left-0 right-0 h-1.5 bg-gray-300/50 dark:bg-gray-700/50">
                    <div class="<?php echo e($progressBarClass); ?> h-full rounded-r-full" style="width: <?php echo e((string) $progressPct); ?>%"></div>
                  </div>
                </div>
                <div class="p-4">
                  <div class="flex items-start justify-between gap-2">
                    <h3 class="course-card-title font-bold text-gray-900 dark:text-white text-base leading-snug"><?php echo e($courseTitle); ?></h3>
                  </div>
                  <?php if ($teacherName !== ''): ?>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Par <span class="text-blue-600 dark:text-blue-400 font-medium"><?php echo e($teacherName); ?></span></p>
                  <?php endif; ?>
                  <div class="flex flex-wrap items-center gap-2 mt-3 text-xs text-gray-500 dark:text-gray-400">
                    <span><i class="fas fa-book-open mr-1 text-blue-500"></i> <?php echo e($lessonProgressLabel); ?></span>
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <span><i class="fas fa-clock mr-1 text-amber-500"></i> <?php echo e($remainingLabel); ?></span>
                  </div>
                  <div class="course-card-footer flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                      <span class="<?php echo e($statusBadgeClass); ?>"><?php echo e($statusLabel); ?></span>
                      <span class="text-xs text-gray-400"><?php echo e($levelLabel); ?></span>
                    </div>
                    <div class="course-card-actions">
                      <?php if ($canRate): ?>
                        <button type="button" class="course-action-link course-action-link--rating" data-course-rating-open data-rating-source="my_courses_card" aria-label="<?php echo e($ratingActionLabel . ' pour ' . $courseTitle); ?>">
                          <i class="fas fa-star"></i>
                          <span data-rating-trigger-label><?php echo e($ratingActionLabel); ?></span>
                        </button>
                      <?php endif; ?>
                      <button type="button" class="course-action-link course-action-link--details" data-course-open-details>
                        <i class="fas fa-eye"></i>
                        <span>Voir détails</span>
                      </button>
                      <?php if ($certificateUrl !== ''): ?>
                        <a href="<?php echo e($certificateUrl); ?>" class="course-action-link course-action-link--certificate">
                          <i class="fas fa-award"></i>
                          <span>Voir certificat</span>
                        </a>
                      <?php endif; ?>
                      <a href="<?php echo e($courseUrl); ?>" class="<?php echo e($ctaClass); ?>">
                        <i class="fas <?php echo e($ctaIcon); ?>"></i>
                        <span><?php echo e($ctaLabel); ?></span>
                      </a>
                    </div>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <div class="<?php echo $initialVisibleCourseCards === [] ? '' : 'hidden'; ?>" data-courses-no-match>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden">
              <div class="flex flex-col items-center justify-center gap-4 px-6 py-14 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-300">
                  <?php echo dashboard_icon_svg('search', 'class="h-8 w-8 shrink-0"'); ?>
                </div>
                <div class="max-w-md">
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white">Aucun cours trouvé</h3>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Modifiez vos filtres.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" data-course-reset>
                  <i class="fas fa-undo-alt"></i>
                  Réinitialiser les filtres
                </button>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php
        $pagination = $coursesPagination;
        $basePath = 'student/my_courses';
        $queryKey = 'courses_page';
        $baseQuery = [
            'q' => $searchQuery !== '' ? $searchQuery : null,
            'filter' => $filterMode !== 'all' ? $filterMode : null,
            'sort' => $sortMode !== 'recent' ? $sortMode : null,
            'layout' => $courseGridLayout !== 'grid' ? $courseGridLayout : null,
        ];
        require __DIR__ . '/_pagination.php';
        ?>
      </div>
    </main>
  </div>
</div>

<div id="course-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6 backdrop-blur-sm">
  <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800">
    <div class="relative">
      <div id="course-modal-cover" class="h-52 w-full bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-500"></div>
      <button type="button" data-course-modal-close class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-sm transition hover:text-gray-900 dark:bg-gray-900/80 dark:text-gray-200">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="p-6">
      <div class="flex flex-wrap items-center gap-2">
        <span id="course-modal-status" class="app-status-pill app-status-pill--info">En cours</span>
        <span id="course-modal-level" class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Niveau</span>
      </div>
      <h3 id="course-modal-title" class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">Cours</h3>
      <p id="course-modal-meta" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>

      <div class="mt-5 space-y-3">
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-500 dark:text-gray-400">Progression</span>
          <span id="course-modal-progress-text" class="font-semibold text-gray-900 dark:text-white">0%</span>
        </div>
        <div class="w-full rounded-full bg-gray-200 dark:bg-gray-700 h-2">
          <div id="course-modal-progress-bar" class="h-2 rounded-full bg-blue-600" style="width: 0%;"></div>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Leçons</p>
            <p id="course-modal-lessons" class="mt-1 text-base font-bold text-gray-900 dark:text-white">0/0</p>
          </div>
          <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Date d’inscription</p>
            <p id="course-modal-enrolled" class="mt-1 text-base font-bold text-gray-900 dark:text-white">-</p>
          </div>
        </div>
      </div>

      <div class="mt-6 flex flex-wrap gap-3">
        <a id="course-modal-cta" href="#" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 sm:flex-none">
          Continuer le cours
        </a>
        <button id="course-modal-rating" type="button" data-course-rating-open data-rating-source="my_courses_modal" class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200 dark:hover:bg-amber-900/30">
          <i class="fas fa-star"></i>
          <span data-rating-trigger-label>Noter le cours</span>
        </button>
        <button type="button" data-course-modal-close class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
          Fermer
        </button>
      </div>
    </div>
  </div>
</div>
</body>
</html>
