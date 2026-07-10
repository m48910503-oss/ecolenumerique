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
$chartJsUrl = site_url('assets/js/chart.umd.min.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/chart.umd.min.js') ?: time()));
$studentDashboardJsUrl = site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time()));
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

$activeEnrollments = max(0, (int) ($activeEnrollments ?? 0));
$activeEnrollmentsThisMonth = max(0, (int) ($activeEnrollmentsThisMonth ?? 0));
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$certCount = max(0, (int) ($certCount ?? 0));
$overallProgressPct = max(0, min(100, (int) ($overallProgressPct ?? 0)));
$profileCompletionPct = max(0, min(100, (int) ($studentKpi['profile_completion_pct'] ?? 0)));
$completedCoursesCount = max(0, (int) ($studentKpi['completed_courses'] ?? 0));
$watchHoursLabel = trim((string) ($watchHoursLabel ?? '0h'));
if ($watchHoursLabel === '') {
    $watchHoursLabel = '0h';
}
$watchWeekHoursLabel = trim((string) ($watchWeekHoursLabel ?? 'Aucune activité cette semaine'));
$continueItems = is_array($continueItems ?? null) ? $continueItems : [];
$popularCourses = is_array($popularCourses ?? null) ? $popularCourses : [];
$certificates = is_array($certificates ?? null) ? $certificates : [];
$nextMeeting = is_array($nextMeeting ?? null) ? $nextMeeting : null;
$activityChartData = is_array($activityChartData ?? null) ? $activityChartData : [];
$fmtDtShort = is_callable($fmtDtShort ?? null) ? $fmtDtShort : static fn (?string $value): string => '';
$primaryContinueItem = is_array($continueItems[0] ?? null) ? $continueItems[0] : [];
$heroSubtitle = trim((string) ($heroSubtitle ?? ''));
if ($heroSubtitle === '') {
    if (($primaryContinueItem['type'] ?? '') === 'course') {
        $heroCourseTitle = trim((string) ($primaryContinueItem['title'] ?? ''));
        $heroCourseRemainingLessons = max(0, (int) ($primaryContinueItem['remaining_lessons'] ?? 0));
        if ($heroCourseTitle !== '') {
            $heroSubtitle = $heroCourseRemainingLessons > 0
                ? 'Continuez "' . $heroCourseTitle . '" · ' . $heroCourseRemainingLessons . ' module' . ($heroCourseRemainingLessons > 1 ? 's' : '') . ' à terminer.'
                : 'Continuez "' . $heroCourseTitle . '".';
        }
    }

    if ($heroSubtitle === '' && $nextMeeting) {
        $meetingLabel = trim((string) ($nextMeeting['course_title'] ?? '')) ?: trim((string) ($nextMeeting['meeting_title'] ?? 'Rendez-vous'));
        $meetingWhen = $fmtDtShort(isset($nextMeeting['start_at']) ? (string) $nextMeeting['start_at'] : null);
        $heroSubtitle = $meetingWhen !== ''
            ? 'Votre prochain rendez-vous pour "' . $meetingLabel . '" est confirmé le ' . $meetingWhen . '.'
            : 'Votre prochain rendez-vous pour "' . $meetingLabel . '" est confirmé.';
    }
}

$latestCertificateCourse = trim((string) ($certificates[0]['course_title'] ?? ''));
$certificatesNote = $certCount > 0
    ? ($latestCertificateCourse !== '' ? 'Dernier : ' . $latestCertificateCourse : 'Dernier certificat disponible')
    : 'Terminez un cours pour obtenir votre premier certificat.';
$progressNote = $completedCoursesCount > 0
    ? $completedCoursesCount . ' cours terminés'
    : 'Votre progression démarre dès le premier cours.';
$courseCountNote = $activeEnrollmentsThisMonth > 0
    ? '+' . $activeEnrollmentsThisMonth . ' ce mois'
    : 'Explorez le catalogue pour ajouter une nouvelle formation.';
$activityWeekValues = array_map(
    static fn ($value): float => (float) $value,
    is_array($activityChartData['week']['values'] ?? null) ? $activityChartData['week']['values'] : []
);
$activityMonthValues = array_map(
    static fn ($value): float => (float) $value,
    is_array($activityChartData['month']['values'] ?? null) ? $activityChartData['month']['values'] : []
);
$hasWeekLearningActivity = array_sum($activityWeekValues) > 0;
$hasMonthLearningActivity = array_sum($activityMonthValues) > 0;
$hasLearningActivity = $hasWeekLearningActivity || $hasMonthLearningActivity;
$defaultActivityRange = $hasWeekLearningActivity ? 'week' : ($hasMonthLearningActivity ? 'month' : 'week');
if (!$hasLearningActivity) {
    $watchWeekHoursLabel = "Reprenez un cours aujourd'hui pour relancer votre rythme.";
}
$courseCountIconClass = 'bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-300';
$watchIconClass = 'bg-cyan-100 dark:bg-cyan-500/10 text-cyan-600 dark:text-cyan-300';
$certIconClass = 'bg-purple-100 dark:bg-purple-900/30 text-purple-600';
$progressIconClass = 'bg-green-100 dark:bg-green-900/30 text-green-600';

$dashboardStats = [
    [
        'label' => 'Cours suivis',
        'value' => (string) $activeEnrollments,
        'note' => $courseCountNote,
        'note_class' => $activeEnrollmentsThisMonth > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400',
        'icon' => 'book-open',
        'icon_class' => $courseCountIconClass,
        'progress_pct' => 0,
        'progress_bar_class' => '',
    ],
    [
        'label' => 'Heures visionnées',
        'value' => $watchHoursLabel,
        'note' => $watchWeekHoursLabel,
        'note_class' => 'text-gray-500 dark:text-gray-400',
        'icon' => 'clock',
        'icon_class' => $watchIconClass,
        'progress_pct' => 0,
        'progress_bar_class' => '',
    ],
    [
        'label' => 'Certificats obtenus',
        'value' => (string) $certCount,
        'note' => $certificatesNote,
        'note_class' => 'text-gray-500 dark:text-gray-400',
        'icon' => 'award',
        'icon_class' => $certIconClass,
        'progress_pct' => 0,
        'progress_bar_class' => '',
    ],
    [
        'label' => 'Progression globale',
        'value' => $overallProgressPct . '%',
        'note' => $progressNote,
        'note_class' => 'text-gray-500 dark:text-gray-400',
        'icon' => 'pie-chart',
        'icon_class' => $progressIconClass,
        'progress_pct' => $overallProgressPct,
        'progress_bar_class' => 'bg-green-500',
    ],
];

$primaryItemType = trim((string) ($primaryContinueItem['type'] ?? ''));
$primaryItemTitle = trim((string) ($primaryContinueItem['title'] ?? ''));
$primaryItemMeta = trim((string) ($primaryContinueItem['meta'] ?? ''));
$primaryItemUrl = trim((string) ($primaryContinueItem['url'] ?? ''));
if ($primaryItemUrl === '') {
    $primaryItemUrl = $myCoursesUrl;
}
$primaryItemProgress = max(0, min(100, (int) ($primaryContinueItem['progress_pct'] ?? 0)));
$primaryItemRemainingLessons = max(0, (int) ($primaryContinueItem['remaining_lessons'] ?? 0));
$nextActionTitle = 'Votre prochaine action';
$nextActionPrimaryLabel = 'Explorer les formations';
$nextActionPrimaryUrl = $catalogueUrl;
$nextActionSecondaryLabel = '';
$nextActionSecondaryUrl = '';
$nextActionIcon = 'book-open';
$nextActionToneClass = 'from-blue-600 to-cyan-500';
$heroActionLabel = 'Explorer';
$heroActionUrl = $catalogueUrl;

if ($primaryItemTitle !== '') {
    $heroActionUrl = $primaryItemUrl;
    if ($primaryItemType === 'course') {
        $nextActionIcon = $primaryItemProgress >= 100 ? 'award' : 'book-open';
        $nextActionToneClass = $primaryItemProgress >= 100 ? 'from-emerald-500 to-teal-500' : 'from-blue-600 to-cyan-500';

        if ($primaryItemProgress >= 100) {
            $nextActionTitle = 'Cours terminé';
            $nextActionPrimaryLabel = $certCount > 0 ? 'Voir mes certificats' : 'Revoir le cours';
            $nextActionPrimaryUrl = $certCount > 0 ? $certificatesUrl : $primaryItemUrl;
            $nextActionSecondaryLabel = 'Explorer les formations';
            $nextActionSecondaryUrl = $catalogueUrl;
            $heroActionLabel = $certCount > 0 ? 'Voir le certificat' : 'Revoir le cours';
            $heroActionUrl = $nextActionPrimaryUrl;
        } elseif ($primaryItemProgress <= 0) {
            $nextActionTitle = 'Commencer votre apprentissage';
            $nextActionPrimaryLabel = 'Commencer';
            $nextActionPrimaryUrl = $primaryItemUrl;
            $nextActionSecondaryLabel = 'Voir mes cours';
            $nextActionSecondaryUrl = $myCoursesUrl;
            $heroActionLabel = 'Commencer';
        } else {
            $nextActionTitle = 'Reprendre votre apprentissage';
            $nextActionPrimaryLabel = 'Reprendre maintenant';
            $nextActionPrimaryUrl = $primaryItemUrl;
            $nextActionSecondaryLabel = 'Voir mes cours';
            $nextActionSecondaryUrl = $myCoursesUrl;
            $heroActionLabel = 'Reprendre';
        }
    } elseif ($primaryItemType === 'certificate') {
        $nextActionTitle = 'Certificat disponible';
        $nextActionPrimaryLabel = 'Voir le certificat';
        $nextActionPrimaryUrl = $primaryItemUrl;
        $nextActionSecondaryLabel = 'Mes certificats';
        $nextActionSecondaryUrl = $certificatesUrl;
        $nextActionIcon = 'award';
        $nextActionToneClass = 'from-emerald-500 to-teal-500';
        $heroActionLabel = 'Voir le certificat';
    } elseif ($primaryItemType === 'meeting') {
        $nextActionTitle = 'Rendez-vous à venir';
        $nextActionPrimaryLabel = 'Voir le rendez-vous';
        $nextActionPrimaryUrl = $primaryItemUrl;
        $nextActionSecondaryLabel = 'Mes cours';
        $nextActionSecondaryUrl = $myCoursesUrl;
        $nextActionIcon = 'calendar';
        $nextActionToneClass = 'from-cyan-500 to-blue-500';
        $heroActionLabel = 'Voir le rendez-vous';
    } else {
        $nextActionTitle = $primaryItemTitle;
        $nextActionPrimaryLabel = 'Continuer';
        $nextActionPrimaryUrl = $primaryItemUrl;
        $nextActionIcon = trim((string) ($primaryContinueItem['icon'] ?? 'book-open'));
        $heroActionLabel = 'Continuer';
    }
} elseif ($nextMeeting) {
    $nextMeetingLabel = trim((string) ($nextMeeting['course_title'] ?? '')) ?: trim((string) ($nextMeeting['meeting_title'] ?? 'Rendez-vous'));
    $nextMeetingWhen = $fmtDtShort(isset($nextMeeting['start_at']) ? (string) $nextMeeting['start_at'] : null);
    $nextActionTitle = 'Préparer votre prochain rendez-vous';
    $nextActionPrimaryLabel = 'Voir mes rendez-vous';
    $nextActionPrimaryUrl = $calendarUrl;
    $nextActionSecondaryLabel = 'Voir mes cours';
    $nextActionSecondaryUrl = $myCoursesUrl;
    $nextActionIcon = 'calendar';
    $nextActionToneClass = 'from-cyan-500 to-blue-500';
    $heroActionLabel = 'Voir le rendez-vous';
    $heroActionUrl = $calendarUrl;
}

$studentWelcomeSummary = $heroSubtitle;
$studentWelcomeActionUrl = $heroActionUrl;
$studentWelcomeActionLabel = $heroActionLabel;
$studentWelcomeActionIcon = $nextActionIcon;

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

$resolveCourseTeacher = static function (array $course): string {
    $teacher = trim((string) ($course['first_name'] ?? ''));
    $teacherLast = trim((string) ($course['last_name'] ?? ''));
    $full = trim($teacher . ' ' . $teacherLast);
    return $full !== '' ? $full : 'Équipe pédagogique';
};

$renderStars = static function (float $rating): string {
    $rating = max(0.0, min(5.0, $rating));
    $fullStars = (int) floor($rating);
    $fraction = $rating - $fullStars;
    $halfStar = $fraction >= 0.25 && $fraction < 0.75;
    $html = '<span class="sr-only">' . e(number_format($rating, 1, '.', '')) . ' sur 5</span>';

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($halfStar && $i === $fullStars + 1) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star text-gray-300"></i>';
        }
    }

    return $html;
};

$chartPayloadJson = json_encode(
    $activityChartData,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($chartPayloadJson) || $chartPayloadJson === '') {
    $chartPayloadJson = '{}';
}
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Tableau de bord · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e($studentDashboardCssUrl); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($chartJsUrl); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
  <script src="<?php echo e($studentDashboardJsUrl); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="student/dashboard" data-sidebar-desktop-toggle="0" class="app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">

<div class="student-dashboard-shell relative min-h-screen overflow-hidden">
  <?php $studentNavActive = 'dashboard'; ?>
  <?php $studentDashboardShell = true; ?>
  <?php $studentTopTitle = 'Tableau de bord'; ?>
  <?php $studentTopSubtitle = 'Espace étudiant'; ?>
  <?php $studentTopShowSearch = false; ?>
  <?php $studentTopHideSupportFab = true; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
    <main class="student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
      <?php require __DIR__ . '/_topbar.php'; ?>

      <div class="student-dashboard-content p-4 pb-24 space-y-5 animate-fade-up sm:p-6 sm:pb-24 sm:space-y-6 lg:space-y-8">
        <?php require __DIR__ . '/_welcome_hero.php'; ?>

        <div class="student-dashboard-stats-grid grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5">
          <?php foreach ($dashboardStats as $stat): ?>
            <?php
            $progressPct = max(0, min(100, (int) ($stat['progress_pct'] ?? 0)));
            $progressWidthClass = $progressPct > 0 ? 'w-[' . $progressPct . '%]' : '';
            ?>
            <div class="student-dashboard-stat-card bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 hover-lift sm:p-5">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e((string) ($stat['label'] ?? '')); ?></p>
                  <p class="text-xl font-bold text-gray-900 dark:text-white mt-1 sm:text-2xl"><?php echo e((string) ($stat['value'] ?? '0')); ?></p>
                </div>
                <div class="student-dashboard-icon-tile flex h-9 w-9 items-center justify-center rounded-xl <?php echo e((string) ($stat['icon_class'] ?? 'bg-gray-100 text-gray-600')); ?> sm:h-10 sm:w-10">
                  <i class="<?php echo e(dashboard_icon_fa_class((string) ($stat['icon'] ?? 'bar-chart'))); ?>" aria-hidden="true"></i>
                </div>
              </div>
              <?php if ($progressPct > 0): ?>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-3">
                  <div class="<?php echo e((string) ($stat['progress_bar_class'] ?? 'bg-green-500')); ?> h-1.5 rounded-full <?php echo e($progressWidthClass); ?>"></div>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="student-dashboard-focus-grid grid grid-cols-1 gap-4 lg:grid-cols-2 sm:gap-6">
          <section class="student-dashboard-panel student-dashboard-continue-panel bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 sm:p-5">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Priorité</p>
                <h3 class="mt-1 font-bold text-gray-900 dark:text-white">Continuer votre apprentissage</h3>
              </div>
              <a href="<?php echo e($myCoursesUrl); ?>" class="text-xs font-semibold text-blue-600 hover:underline">Voir tout</a>
            </div>

            <?php if ($primaryItemTitle !== ''): ?>
              <?php
              $primaryCoverUrl = trim((string) ($primaryContinueItem['cover_url'] ?? ''));
              $primaryIcon = trim((string) ($primaryContinueItem['icon'] ?? 'book-open'));
              $primaryTone = trim((string) ($primaryContinueItem['tone'] ?? 'blue'));
              $primaryGradient = match ($primaryTone) {
                  'emerald' => 'from-emerald-500 to-teal-500',
                  'sky' => 'from-cyan-500 to-blue-500',
                  'slate' => 'from-slate-500 to-slate-700',
                  default => 'from-blue-600 to-cyan-500',
              };
              $primaryStatusLabel = $primaryItemProgress >= 100
                  ? 'Terminé'
                  : ($primaryItemProgress <= 0 ? 'Prêt à commencer' : $primaryItemProgress . '% terminé');
              ?>
              <div class="mt-4 flex flex-col gap-4 md:flex-row md:items-center">
                <div class="h-24 w-full shrink-0 overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-700 md:h-28 md:w-44">
                  <?php if ($primaryCoverUrl !== ''): ?>
                    <img src="<?php echo e($primaryCoverUrl); ?>" class="h-full w-full object-cover" alt="<?php echo e($primaryItemTitle); ?>">
                  <?php else: ?>
                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br <?php echo e($primaryGradient); ?> text-2xl text-white">
                      <i class="<?php echo e(dashboard_icon_fa_class($primaryIcon)); ?>" aria-hidden="true"></i>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-200"><?php echo e($primaryStatusLabel); ?></span>
                    <?php if ($primaryItemMeta !== ''): ?>
                      <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($primaryItemMeta); ?></span>
                    <?php endif; ?>
                  </div>
                  <h4 class="mt-2 truncate text-lg font-bold text-gray-900 dark:text-white"><?php echo e($primaryItemTitle); ?></h4>
                  <div class="mt-3 flex items-center gap-3">
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                      <div class="h-2 rounded-full bg-blue-600" style="width: <?php echo e((string) $primaryItemProgress); ?>%"></div>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400"><?php echo e((string) $primaryItemProgress); ?>%</span>
                  </div>
                  <div class="mt-4 flex flex-wrap gap-2">
                    <a href="<?php echo e($nextActionPrimaryUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                      <i class="<?php echo e(dashboard_icon_fa_class($nextActionIcon)); ?>" aria-hidden="true"></i>
                      <span><?php echo e($nextActionPrimaryLabel); ?></span>
                    </a>
                    <?php if ($nextActionSecondaryLabel !== '' && $nextActionSecondaryUrl !== ''): ?>
                      <a href="<?php echo e($nextActionSecondaryUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:text-gray-200">
                        <span><?php echo e($nextActionSecondaryLabel); ?></span>
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <?php $secondaryContinueItems = array_slice($continueItems, 1, 2); ?>
              <?php if ($secondaryContinueItems !== []): ?>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                  <?php foreach ($secondaryContinueItems as $item): ?>
                    <?php
                    $itemTitle = trim((string) ($item['title'] ?? 'Cours'));
                    $itemProgress = max(0, min(100, (int) ($item['progress_pct'] ?? 0)));
                    $itemUrl = trim((string) ($item['url'] ?? $myCoursesUrl));
                    ?>
                    <a href="<?php echo e($itemUrl !== '' ? $itemUrl : $myCoursesUrl); ?>" class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 px-3 py-2 text-sm transition hover:border-blue-200 hover:bg-blue-50/50 dark:border-gray-700 dark:hover:border-blue-500/30 dark:hover:bg-blue-500/10">
                      <span class="min-w-0 truncate font-medium text-gray-700 dark:text-gray-200"><?php echo e($itemTitle); ?></span>
                      <span class="shrink-0 text-xs font-semibold text-gray-500"><?php echo e((string) $itemProgress); ?>%</span>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-dashed border-blue-200 bg-blue-50/60 p-4 dark:border-blue-500/30 dark:bg-blue-500/10">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Aucun cours en cours pour le moment.</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Explorez le catalogue pour choisir une formation et démarrer votre progression.</p>
                <a href="<?php echo e($catalogueUrl); ?>" class="inline-flex w-fit items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                  <i class="<?php echo e(dashboard_icon_fa_class('search')); ?>" aria-hidden="true"></i>
                  <span>Explorer le catalogue</span>
                </a>
              </div>
            <?php endif; ?>
          </section>

          <section class="student-dashboard-panel student-dashboard-next-action-panel bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 sm:p-5">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br <?php echo e($nextActionToneClass); ?> text-white">
                <i class="<?php echo e(dashboard_icon_fa_class($nextActionIcon)); ?>" aria-hidden="true"></i>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Prochaine action</p>
                <h3 class="font-bold text-gray-900 dark:text-white"><?php echo e($nextActionTitle); ?></h3>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <a href="<?php echo e($nextActionPrimaryUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <span><?php echo e($nextActionPrimaryLabel); ?></span>
              </a>
              <?php if ($nextActionSecondaryLabel !== '' && $nextActionSecondaryUrl !== ''): ?>
                <a href="<?php echo e($nextActionSecondaryUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:text-gray-200">
                  <span><?php echo e($nextActionSecondaryLabel); ?></span>
                </a>
              <?php endif; ?>
            </div>
          </section>
        </div>

        <section class="student-dashboard-panel student-dashboard-activity-panel bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 sm:p-5">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="font-bold text-gray-900 dark:text-white">Activité d'apprentissage</h3>
            </div>
            <?php if ($hasLearningActivity): ?>
              <select id="activityRange" class="text-xs bg-gray-100 dark:bg-gray-700 border-none rounded-lg px-2 py-1.5">
                <option value="week"<?php echo $defaultActivityRange === 'week' ? ' selected' : ''; ?>>Cette semaine</option>
                <option value="month"<?php echo $defaultActivityRange === 'month' ? ' selected' : ''; ?>>Ce mois</option>
              </select>
            <?php endif; ?>
          </div>
          <?php if ($hasLearningActivity): ?>
            <div class="mt-4 h-44 sm:h-52">
              <canvas id="activityChart"></canvas>
            </div>
          <?php else: ?>
            <div class="student-dashboard-empty-chart mt-4 flex flex-col items-center justify-center rounded-2xl border border-dashed border-blue-200 bg-blue-50/60 px-4 py-8 text-center dark:border-blue-500/30 dark:bg-blue-500/10">
              <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm dark:bg-gray-800 dark:text-blue-300">
                <i class="<?php echo e(dashboard_icon_fa_class('bar-chart')); ?>" aria-hidden="true"></i>
              </div>
              <h4 class="mt-3 text-sm font-bold text-gray-900 dark:text-white">Aucune activité cette semaine</h4>
              <a href="<?php echo e($nextActionPrimaryUrl); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="<?php echo e(dashboard_icon_fa_class($nextActionIcon)); ?>" aria-hidden="true"></i>
                <span><?php echo e($nextActionPrimaryLabel); ?></span>
              </a>
            </div>
          <?php endif; ?>
        </section>

        <div>
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 dark:text-white text-base sm:text-lg">Recommandé pour vous</h3>
            <a href="<?php echo e($catalogueUrl); ?>" class="text-sm text-blue-600 hover:underline">Explorer le catalogue</a>
          </div>

          <?php if ($popularCourses !== []): ?>
            <div class="student-dashboard-course-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5">
              <?php foreach (array_slice($popularCourses, 0, 4) as $course): ?>
                <?php
                $courseTitle = trim((string) ($course['title'] ?? 'Cours recommandé')) ?: 'Cours recommandé';
                $courseTeacher = $resolveCourseTeacher($course);
                $coursePrice = view_format_money_xof((int) ($course['price_xof'] ?? 0));
                $courseUrl = $resolveCourseUrl($course);
                $courseCoverUrl = $resolveCourseCoverUrl($course);
                $rating = (float) ($course['avg_rating'] ?? 0);
                $ratingCount = max(0, (int) ($course['rating_count'] ?? 0));
                $courseInitial = function_exists('mb_substr')
                    ? mb_substr($courseTitle, 0, 1, 'UTF-8')
                    : substr($courseTitle, 0, 1);
                $courseInitial = strtoupper((string) $courseInitial);
                ?>
                <a href="<?php echo e($courseUrl); ?>" class="student-dashboard-course-card bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden hover-lift block">
                  <div class="student-dashboard-course-cover h-28 w-full overflow-hidden bg-slate-100 dark:bg-slate-700 sm:h-32">
                    <?php if ($courseCoverUrl !== ''): ?>
                      <img src="<?php echo e($courseCoverUrl); ?>" class="h-28 w-full object-cover sm:h-32" alt="<?php echo e($courseTitle); ?>">
                    <?php else: ?>
                      <div class="h-28 w-full bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center text-white text-2xl font-bold sm:h-32">
                        <?php echo e($courseInitial !== '' ? $courseInitial : 'C'); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="p-3 sm:p-4">
                    <h4 class="font-bold text-gray-900 dark:text-white"><?php echo e($courseTitle); ?></h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($courseTeacher); ?></p>
                    <div class="flex items-center justify-between mt-2">
                      <span class="app-price font-bold"><?php echo e($coursePrice); ?></span>
                      <span class="app-rating-stars text-yellow-400 text-sm flex items-center gap-0.5">
                        <?php echo $renderStars($rating); ?>
                        <?php if ($ratingCount > 0): ?>
                          <span class="sr-only"><?php echo e((string) $ratingCount); ?> avis</span>
                        <?php endif; ?>
                      </span>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 text-sm text-gray-500 dark:text-gray-400">
              Aucun cours recommandé pour le moment.
            </div>
          <?php endif; ?>
        </div>
      </div>

      <script id="student-dashboard-chart-data" type="application/json"><?php echo $chartPayloadJson; ?></script>
    </main>
  </div>
</div>

<a
  href="#support-chat"
  data-support-url="<?php echo e($messagesUrl); ?>"
  data-support-fab="1"
  class="student-dashboard-support-fab fixed bottom-3 right-3 z-40 inline-flex items-center gap-3 rounded-full bg-blue-600 px-3 py-2.5 text-white shadow-glow transition hover:-translate-y-0.5 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 sm:bottom-4 sm:right-4 sm:px-4 sm:py-3"
  aria-label="Assistance support"
  title="Assistance support"
>
    <span class="relative flex h-10 w-10 items-center justify-center rounded-full bg-white/15 sm:h-11 sm:w-11">
      <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('headset')); ?>"></i></span>
      <?php if ($supportUnreadCount > 0): ?>
        <span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white text-center" data-live-support-badge><?php echo e((string) min(99, $supportUnreadCount)); ?></span>
      <?php endif; ?>
    </span>
  <span class="student-dashboard-support-label hidden sm:block pr-1">
    <span class="block text-sm font-semibold leading-tight">Assistance support</span>
    <span class="block text-xs text-blue-100">Réponse rapide</span>
  </span>
</a>
</body>
</html>
