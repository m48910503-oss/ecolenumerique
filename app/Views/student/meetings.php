<?php
$dashboardUrl = site_url('student/dashboard');
$myCoursesUrl = site_url('student/my_courses');
$calendarUrl = site_url('student/meetings');
$certificatesUrl = site_url('student/certificates');
$favoritesUrl = site_url('student/favorites');
$ordersUrl = site_url('student/orders');
$messagesUrl = site_url('support?space=student');
$settingsUrl = site_url('profile?space=student');
$referralsUrl = site_url('student/affiliation');
$supportUrl = site_url('support?space=student');
$profileUrl = site_url('profile?space=student');
$homeUrl = site_url('');
$catalogueUrl = site_url('catalogue');
$notificationsUrl = site_url('notifications?space=student');
$logoutUrl = site_url('logout');
$logoutCsrf = function_exists('view_csrf_token') ? view_csrf_token() : '';
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$sidebarToggleJsUrl = site_url('assets/js/sidebar_toggle.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/sidebar_toggle.js') ?: time()));
$referralCopyJsUrl = site_url('assets/js/referral_copy.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/referral_copy.js') ?: time()));
$studentMeetingsJsUrl = site_url('assets/js/student_meetings.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_meetings.js') ?: time()));
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

$studentUserId = (int) ($dashboardUser['id'] ?? 0);
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));

$confirmedList = is_array($confirmedMeetings ?? null) ? $confirmedMeetings : [];
$confirmedCount = max(0, (int) ($confirmedMeetingsCount ?? count($confirmedList)));
$meetingsPagination = is_array($pagination ?? null) ? $pagination : ['page' => 1];
$upcomingCount = max(0, (int) ($upcomingMeetingsCount ?? 0));
$pastCount = max(0, $confirmedCount - $upcomingCount);
$activeFilter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));
if (!in_array($activeFilter, ['all', 'upcoming', 'past'], true)) {
    $activeFilter = 'all';
}
$showAllMeetingsTab = $confirmedCount > 0 && $upcomingCount > 0 && $pastCount > 0;
if (!$showAllMeetingsTab && $activeFilter === 'all') {
    if ($upcomingCount > 0) {
        $activeFilter = 'upcoming';
    } elseif ($pastCount > 0) {
        $activeFilter = 'past';
    }
}
if ($activeFilter === 'upcoming' && $upcomingCount === 0 && $pastCount > 0) {
    $activeFilter = 'past';
}
if ($activeFilter === 'past' && $pastCount === 0 && $upcomingCount > 0) {
    $activeFilter = 'upcoming';
}
$meetingsFilterHint = '';
if (!$showAllMeetingsTab && $confirmedCount > 0) {
    $meetingsFilterHint = $pastCount > 0
        ? 'Historique uniquement'
        : 'Sessions à venir uniquement';
}
$searchQuery = trim((string) ($_GET['q'] ?? ''));

$fmtDtFn = is_callable($fmtDt ?? null) ? $fmtDt : static function (string $dbValue): string {
    if ($dbValue === '') {
        return '';
    }

    $ts = strtotime($dbValue);
    return $ts ? date('d/m/Y H:i', $ts) : $dbValue;
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

$resolveCourseUrl = static function (array $meeting): string {
    $slug = trim((string) ($meeting['course_slug'] ?? ''));
    if ($slug !== '') {
        return site_url('course/watch?slug=' . urlencode($slug));
    }

    $courseId = max(0, (int) ($meeting['course_id'] ?? 0));
    if ($courseId > 0) {
        return site_url('course/watch?id=' . $courseId);
    }

    return site_url('student/my_courses');
};

$weekdayShortLabels = [
    1 => 'Lun.',
    2 => 'Mar.',
    3 => 'Mer.',
    4 => 'Jeu.',
    5 => 'Ven.',
    6 => 'Sam.',
    7 => 'Dim.',
];

$weekdayFullLabels = [
    1 => 'Lundi',
    2 => 'Mardi',
    3 => 'Mercredi',
    4 => 'Jeudi',
    5 => 'Vendredi',
    6 => 'Samedi',
    7 => 'Dimanche',
];

$monthShortLabels = [
    1 => 'Jan.',
    2 => 'Fév.',
    3 => 'Mar.',
    4 => 'Avr.',
    5 => 'Mai',
    6 => 'Juin',
    7 => 'Juil.',
    8 => 'Août',
    9 => 'Sep.',
    10 => 'Oct.',
    11 => 'Nov.',
    12 => 'Déc.',
];

$meetingWeekdayTitle = static function (string $value) use ($normalizeText): bool {
    $value = $normalizeText($value);

    return in_array($value, [
        'lundi',
        'lun',
        'lun.',
        'mardi',
        'mar',
        'mar.',
        'mercredi',
        'mer',
        'mer.',
        'jeudi',
        'jeu',
        'jeu.',
        'vendredi',
        'ven',
        'ven.',
        'samedi',
        'sam',
        'sam.',
        'dimanche',
        'dim',
        'dim.',
    ], true);
};

$meetingTimeLabel = static function (?string $startAt, ?string $endAt) use ($fmtDtFn): string {
    $startAt = trim((string) $startAt);
    $endAt = trim((string) $endAt);

    if ($startAt === '') {
        return 'Date non renseignée';
    }

    $startTs = strtotime($startAt);
    $endTs = $endAt !== '' ? strtotime($endAt) : false;

    if ($startTs && $endTs) {
        if (date('Y-m-d', $startTs) === date('Y-m-d', $endTs)) {
            return date('H:i', $startTs) . ' - ' . date('H:i', $endTs);
        }

        return date('d/m/Y H:i', $startTs) . ' - ' . date('d/m/Y H:i', $endTs);
    }

    return $fmtDtFn($startAt) . ($endAt !== '' ? ' - ' . $fmtDtFn($endAt) : '');
};

$buildDateBadge = static function (int $ts) use ($weekdayShortLabels, $monthShortLabels): array {
    $todayKey = date('Y-m-d');
    $tomorrowKey = date('Y-m-d', strtotime('+1 day'));
    $dateKey = date('Y-m-d', $ts);
    $topLabel = $weekdayShortLabels[(int) date('N', $ts)] ?? date('D', $ts);

    if ($dateKey === $todayKey) {
        $topLabel = 'Auj.';
    } elseif ($dateKey === $tomorrowKey) {
        $topLabel = 'Dem.';
    }

    return [
        'date_key' => $dateKey,
        'top_label' => $topLabel,
        'day_label' => (string) (int) date('j', $ts),
        'month_label' => $monthShortLabels[(int) date('n', $ts)] ?? date('M', $ts),
    ];
};

$formatDateHeadline = static function (int $ts) use ($weekdayFullLabels, $monthShortLabels): string {
    $weekday = $weekdayFullLabels[(int) date('N', $ts)] ?? date('l', $ts);
    $day = (string) (int) date('j', $ts);
    $month = $monthShortLabels[(int) date('n', $ts)] ?? date('M', $ts);

    return $weekday . ' ' . $day . ' ' . $month . ' ' . date('Y', $ts);
};

$meetingCards = [];
$nextUpcomingMeeting = null;
$hasOnsiteMeeting = false;
$nowTs = time();

foreach ($confirmedList as $meetingRow) {
    $courseTitle = trim((string) ($meetingRow['course_title'] ?? 'Cours'));
    if ($courseTitle === '') {
        $courseTitle = 'Cours';
    }

    $meetingTitle = trim((string) ($meetingRow['meeting_title'] ?? 'Rendez-vous'));
    if ($meetingTitle === '') {
        $meetingTitle = 'Rendez-vous';
    }

    $chapterTitle = trim((string) ($meetingRow['chapter_title'] ?? ''));
    $mode = strtolower(trim((string) ($meetingRow['mode'] ?? 'online')));
    if (!in_array($mode, ['online', 'onsite'], true)) {
        $mode = 'online';
    }

    $meetingLink = trim((string) ($meetingRow['meeting_link'] ?? ''));
    $meetingPlace = trim((string) ($meetingRow['meeting_place'] ?? ''));
    $startAt = trim((string) ($meetingRow['start_at'] ?? ''));
    $endAt = trim((string) ($meetingRow['end_at'] ?? ''));
    $startTs = $startAt !== '' ? strtotime($startAt) : false;
    $endTs = $endAt !== '' ? strtotime($endAt) : false;
    $referenceTs = $endTs !== false ? $endTs : $startTs;
    $isPast = $referenceTs !== false ? $referenceTs < $nowTs : false;
    $statusKey = $isPast ? 'past' : 'upcoming';
    $statusLabel = $isPast ? 'Passé' : 'À venir';
    $statusTone = $isPast ? 'done' : 'active';
    $modeLabel = $mode === 'onsite' ? 'Présentiel' : 'En ligne';
    $modeTone = $mode === 'onsite' ? 'pending' : 'sky';

    if ($mode === 'onsite') {
        $hasOnsiteMeeting = true;
    }

    if (!$isPast && $nextUpcomingMeeting === null) {
        $nextUpcomingMeeting = [
            'title' => $meetingTitle,
            'when' => $fmtDtFn($startAt),
        ];
    }

    $courseUrl = $resolveCourseUrl($meetingRow);
    $meetingLinkPreview = '';
    if ($meetingLink !== '') {
        $parsedHost = parse_url($meetingLink, PHP_URL_HOST);
        $meetingLinkPreview = is_string($parsedHost) && $parsedHost !== '' ? $parsedHost : $meetingLink;
    }

    $dateBadge = $startTs ? $buildDateBadge($startTs) : [
        'date_key' => '',
        'top_label' => '--',
        'day_label' => '--',
        'month_label' => '---',
    ];
    $dateHeadlineLabel = $startTs ? $formatDateHeadline($startTs) : 'Date non renseignée';
    $meetingHeading = $meetingTitle;
    if ($startTs && $meetingWeekdayTitle($meetingTitle)) {
        $meetingHeading = $dateHeadlineLabel;
    }
    $dateSummaryLabel = $meetingHeading === $dateHeadlineLabel ? '' : $dateHeadlineLabel;
    $dateBadgeClass = $statusKey === 'past'
        ? 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300'
        : ($mode === 'onsite'
            ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300'
            : 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300');
    if (!$isPast && isset($dateBadge['date_key']) && $dateBadge['date_key'] === date('Y-m-d')) {
        $dateBadgeClass = 'bg-green-100 text-green-600 dark:bg-green-500/15 dark:text-green-300';
    }

    $timeLabel = $meetingTimeLabel($startAt !== '' ? $startAt : null, $endAt !== '' ? $endAt : null);
    $placeOrLinkLabel = $mode === 'online'
        ? ($meetingLinkPreview !== '' ? $meetingLinkPreview : 'Visioconférence')
        : 'Présentiel';
    $metaIcon = $mode === 'online' ? 'fa-video' : 'fa-map-marker-alt';
    $searchText = $normalizeText(trim(implode(' ', array_filter([
        $courseTitle,
        $meetingHeading,
        $chapterTitle,
        $dateSummaryLabel,
        $modeLabel,
        $statusLabel,
        $timeLabel,
        $meetingLinkPreview,
        $meetingPlace,
    ]))));
    $summaryText = trim(implode(' · ', array_filter([
        $meetingHeading,
        $courseTitle,
        $dateSummaryLabel,
        $timeLabel,
        $modeLabel,
        $statusLabel,
    ])));
    $primaryActionLabel = $isPast
        ? 'Voir les détails'
        : (($mode === 'online' && $meetingLink !== '')
            ? 'Rejoindre'
            : (($mode === 'onsite' && $meetingPlace !== '') ? 'Voir le lieu' : 'Voir les détails'));
    $primaryActionType = $isPast
        ? 'details'
        : (($mode === 'online' && $meetingLink !== '')
            ? 'link'
            : (($mode === 'onsite' && $meetingPlace !== '') ? 'place' : 'details'));
    $primaryActionButtonClass = $primaryActionType === 'link'
        ? 'flex items-center justify-center gap-1 rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-700'
        : ($primaryActionType === 'place'
            ? 'flex items-center justify-center gap-1 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600'
            : 'flex items-center justify-center gap-1 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700');
    $courseActionButtonClass = 'flex items-center justify-center gap-1 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300';
    $linkOpenButtonClass = $isPast
        ? 'inline-flex items-center justify-center gap-2 rounded-xl border border-violet-200 bg-white px-3.5 py-2 text-sm font-semibold text-violet-700 transition hover:border-violet-300 hover:text-violet-800 dark:border-violet-400/40 dark:bg-gray-800 dark:text-violet-200 dark:hover:border-violet-300'
        : 'inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-violet-700';
    $linkOpenButtonLabel = $isPast ? 'Ouvrir le lien' : 'Ouvrir';

    $meetingCards[] = [
        'course_title' => $courseTitle,
        'meeting_title' => $meetingTitle,
        'meeting_heading' => $meetingHeading,
        'chapter_title' => $chapterTitle,
        'course_url' => $courseUrl,
        'meeting_link' => $meetingLink,
        'meeting_place' => $meetingPlace,
        'meeting_link_preview' => $meetingLinkPreview,
        'mode_label' => $modeLabel,
        'mode_tone' => $modeTone,
        'status_key' => $statusKey,
        'status_label' => $statusLabel,
        'status_tone' => $statusTone,
        'date_badge_class' => $dateBadgeClass,
        'date_top_label' => $dateBadge['top_label'],
        'date_day_label' => $dateBadge['day_label'],
        'date_month_label' => $dateBadge['month_label'],
        'date_headline_label' => $dateHeadlineLabel,
        'date_key' => $dateBadge['date_key'],
        'time_label' => $timeLabel,
        'place_or_link_label' => $placeOrLinkLabel,
        'meta_icon' => $metaIcon,
        'search_text' => $searchText,
        'summary_text' => $summaryText,
        'primary_action_label' => $primaryActionLabel,
        'primary_action_type' => $primaryActionType,
        'primary_action_button_class' => $primaryActionButtonClass,
        'course_action_button_class' => $courseActionButtonClass,
        'link_open_button_class' => $linkOpenButtonClass,
        'link_open_button_label' => $linkOpenButtonLabel,
    ];
}

$nextMeetingLabel = $nextUpcomingMeeting['title'] ?? '';
$nextMeetingWhen = $nextUpcomingMeeting['when'] ?? '';
$upcomingNote = $nextMeetingLabel !== ''
    ? 'Prochain : ' . $nextMeetingLabel . ($nextMeetingWhen !== '' ? ' · ' . $nextMeetingWhen : '')
    : 'Aucune session à venir';
$confirmedNote = $confirmedCount > 0 ? 'Sessions validées' : 'Aucune session confirmée';
$pastNote = $pastCount > 0 ? 'Historique disponible' : 'Aucun historique pour le moment';
$showUpcomingEmptyState = $confirmedCount > 0 && $upcomingCount === 0;
$studentWelcomeSummary = $upcomingCount > 0
    ? $upcomingNote
    : 'Consultez vos rendez-vous ou planifiez une nouvelle session.';
$studentWelcomeTitle = 'Mes rendez-vous';
$studentWelcomeActionUrl = $upcomingCount > 0 ? $myCoursesUrl : $catalogueUrl;
$studentWelcomeActionLabel = $upcomingCount > 0 ? 'Voir mes cours' : 'Explorer le catalogue';
$studentWelcomeActionIcon = $upcomingCount > 0 ? 'book-open' : 'search';
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Mes rendez-vous · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e($studentDashboardCssUrl); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($sidebarToggleJsUrl); ?>" defer></script>
  <script src="<?php echo e($referralCopyJsUrl); ?>" defer></script>
  <script src="<?php echo e($studentMeetingsJsUrl); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body
  data-view="student/meetings"
  data-sidebar-desktop-toggle="0"
  data-user-id="<?php echo e((string) $studentUserId); ?>"
  class="app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen"
>
  <div class="student-dashboard-shell relative min-h-screen overflow-hidden">
    <?php $studentNavActive = 'meetings'; ?>
    <?php $studentDashboardShell = true; ?>
    <?php $studentTopTitle = 'Mes rendez-vous'; ?>
    <?php $studentTopSubtitle = 'Sessions accompagnées'; ?>
    <?php $studentTopSearchUrl = $calendarUrl; ?>
    <?php $studentTopSearchValue = $searchQuery; ?>
    <?php $studentTopSearchPlaceholder = 'Rechercher un rendez-vous'; ?>
    <?php $studentTopSearchAriaLabel = 'Rechercher un rendez-vous'; ?>
    <?php $studentTopHideSupportFab = true; ?>
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

    <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
      <main class="student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
        <?php require __DIR__ . '/_topbar.php'; ?>

        <div class="student-dashboard-content p-4 pb-24 space-y-5 animate-fade-up sm:p-6 sm:pb-24 sm:space-y-6 lg:space-y-8">
          <?php require __DIR__ . '/_welcome_hero.php'; ?>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">À venir</p>
                  <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $upcomingCount); ?></p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                  <i class="fas fa-calendar-check"></i>
                </div>
              </div>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e($upcomingNote); ?></p>
            </article>

            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Confirmés</p>
                  <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $confirmedCount); ?></p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-100 text-green-600 dark:bg-green-500/15 dark:text-green-300">
                  <i class="fas fa-check-circle"></i>
                </div>
              </div>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e($confirmedNote); ?></p>
            </article>

            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Passés</p>
                  <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $pastCount); ?></p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300">
                  <i class="fas fa-clock"></i>
                </div>
              </div>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e($pastNote); ?></p>
            </article>
          </div>

          <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-center gap-3 border-b border-gray-200 overflow-x-auto dark:border-gray-700">
              <?php if ($showAllMeetingsTab): ?>
                <button
                  type="button"
                  class="tab-filter pb-3 text-sm transition whitespace-nowrap <?php echo $activeFilter === 'all' ? 'tab-active font-semibold text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'font-medium text-gray-500 border-b-2 border-transparent dark:text-gray-400 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white'; ?>"
                  data-meeting-tab="all"
                  aria-pressed="<?php echo $activeFilter === 'all' ? 'true' : 'false'; ?>"
                >
                  Tous
                  <span class="ml-1.5 inline-flex items-center rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?php echo e((string) $confirmedCount); ?></span>
                </button>
              <?php endif; ?>
              <button
                type="button"
                class="tab-filter pb-3 text-sm transition whitespace-nowrap <?php echo $activeFilter === 'upcoming' ? 'tab-active font-semibold text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : (($upcomingCount === 0) ? 'tab-filter--disabled font-medium text-gray-300 border-b-2 border-transparent dark:text-gray-600' : 'font-medium text-gray-500 border-b-2 border-transparent dark:text-gray-400 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white'); ?>"
                data-meeting-tab="upcoming"
                data-meeting-tab-disabled="<?php echo $upcomingCount === 0 ? '1' : '0'; ?>"
                aria-pressed="<?php echo $activeFilter === 'upcoming' ? 'true' : 'false'; ?>"
                aria-disabled="<?php echo $upcomingCount === 0 ? 'true' : 'false'; ?>"
                <?php echo $upcomingCount === 0 ? 'disabled' : ''; ?>
              >
                À venir
                <span class="ml-1.5 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"><?php echo e((string) $upcomingCount); ?></span>
              </button>
              <button
                type="button"
                class="tab-filter pb-3 text-sm transition whitespace-nowrap <?php echo $activeFilter === 'past' ? 'tab-active font-semibold text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : (($pastCount === 0) ? 'tab-filter--disabled font-medium text-gray-300 border-b-2 border-transparent dark:text-gray-600' : 'font-medium text-gray-500 border-b-2 border-transparent dark:text-gray-400 hover:border-gray-300 hover:text-gray-900 dark:hover:text-white'); ?>"
                data-meeting-tab="past"
                data-meeting-tab-disabled="<?php echo $pastCount === 0 ? '1' : '0'; ?>"
                aria-pressed="<?php echo $activeFilter === 'past' ? 'true' : 'false'; ?>"
                aria-disabled="<?php echo $pastCount === 0 ? 'true' : 'false'; ?>"
                <?php echo $pastCount === 0 ? 'disabled' : ''; ?>
              >
                Passés
                <span class="ml-1.5 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-500/10 dark:text-slate-300"><?php echo e((string) $pastCount); ?></span>
              </button>
              <?php if ($meetingsFilterHint !== ''): ?>
                <span class="mb-3 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                  <?php echo e($meetingsFilterHint); ?>
                </span>
              <?php endif; ?>
            </div>

          </div>

          <?php if ($meetingCards === []): ?>
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center shadow-card dark:border-gray-700 dark:bg-gray-800">
              <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                <?php echo dashboard_icon_svg('calendar', 'class="h-7 w-7 shrink-0"'); ?>
              </div>
              <h2 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Aucun rendez-vous confirmé</h2>
              <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Les sessions validées apparaîtront ici.</p>
              <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a href="<?php echo e($myCoursesUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-blue-700 hover:to-cyan-600">
                  <i class="fas fa-book-open text-xs"></i>
                  Voir mes cours
                </a>
                <a href="<?php echo e($messagesUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-600 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300">
                  <i class="fas fa-headset text-xs"></i>
                  Contacter le support
                </a>
              </div>
            </div>
          <?php else: ?>
            <div id="appointments-container" class="space-y-4">
              <?php foreach ($meetingCards as $meeting): ?>
                <article
                  class="appointment-card appointment-card-enter meeting-card-shell bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 p-5 hover-lift flex flex-col sm:flex-row sm:items-center gap-4"
                  data-meeting-card
                  data-status="<?php echo e((string) $meeting['status_key']); ?>"
                  data-search="<?php echo e((string) $meeting['search_text']); ?>"
                  data-date="<?php echo e((string) $meeting['date_key']); ?>"
                >
                  <div class="meeting-card-date flex-shrink-0 flex items-center justify-center w-16 h-16 rounded-2xl <?php echo e((string) $meeting['date_badge_class']); ?>">
                    <div class="text-center">
                      <p class="text-[11px] font-bold uppercase tracking-wide"><?php echo e((string) $meeting['date_top_label']); ?></p>
                      <p class="text-xl font-extrabold"><?php echo e((string) $meeting['date_day_label']); ?></p>
                      <p class="text-[11px] font-medium"><?php echo e((string) $meeting['date_month_label']); ?></p>
                    </div>
                  </div>

                  <div class="meeting-card-body flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <h3 class="meeting-card-title truncate text-base font-bold text-gray-900 dark:text-white"><?php echo e((string) $meeting['meeting_heading']); ?></h3>
                        <p class="meeting-card-course mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">
                          <?php echo e((string) $meeting['course_title']); ?>
                          <?php if (trim((string) ($meeting['chapter_title'] ?? '')) !== ''): ?>
                            · <?php echo e(trim((string) $meeting['chapter_title'])); ?>
                          <?php endif; ?>
                        </p>
                        <?php if ((string) $meeting['meeting_heading'] !== (string) $meeting['date_headline_label']): ?>
                          <p class="meeting-card-date-line mt-1 flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                            <i class="far fa-calendar"></i>
                            <span><?php echo e((string) $meeting['date_headline_label']); ?></span>
                          </p>
                        <?php endif; ?>
                      </div>
                      <div class="meeting-card-pills flex flex-wrap items-center justify-end gap-2">
                        <span class="meeting-card-pill meeting-card-pill--status <?php echo e(app_status_pill_class((string) $meeting['status_tone'])); ?>"><?php echo e((string) $meeting['status_label']); ?></span>
                        <span class="meeting-card-pill meeting-card-pill--mode <?php echo e(app_status_pill_class((string) $meeting['mode_tone'])); ?>"><?php echo e((string) $meeting['mode_label']); ?></span>
                      </div>
                    </div>

                    <div class="meeting-card-meta mt-3 flex flex-wrap items-center gap-3 text-sm">
                      <span class="meeting-card-meta-item meeting-card-meta-item--time flex items-center gap-1 text-gray-600 dark:text-gray-400">
                        <i class="far fa-clock"></i>
                        <?php echo e((string) $meeting['time_label']); ?>
                      </span>
                      <span class="meeting-card-meta-item meeting-card-meta-item--access flex items-center gap-1 text-gray-600 dark:text-gray-400">
                        <i class="fas <?php echo e((string) $meeting['meta_icon']); ?>"></i>
                        <?php echo e((string) $meeting['place_or_link_label']); ?>
                      </span>
                    </div>

                    <?php if ((string) $meeting['meeting_link'] !== '' && (string) $meeting['mode_label'] === 'En ligne'): ?>
                      <div class="meeting-card-link-wrap mt-4 space-y-2" data-referral-copy-wrap>
                        <p class="meeting-card-link-label text-xs font-semibold uppercase tracking-[0.2em] text-violet-600 dark:text-violet-300">Lien de session</p>
                        <div class="meeting-card-link-panel flex flex-col gap-2 rounded-2xl border border-violet-100 bg-violet-50/80 p-3 dark:border-violet-500/20 dark:bg-violet-500/10 sm:flex-row sm:items-center">
                          <span class="meeting-card-link-preview min-w-0 flex-1 truncate text-sm text-violet-700 dark:text-violet-200"><?php echo e((string) $meeting['meeting_link_preview']); ?></span>
                          <div class="meeting-card-link-actions flex flex-wrap gap-2">
                            <a
                              class="meeting-card-inline-action meeting-card-inline-action--open <?php echo e((string) $meeting['link_open_button_class']); ?>"
                              href="<?php echo e((string) $meeting['meeting_link']); ?>"
                              target="_blank"
                              rel="noopener noreferrer"
                            >
                              <i class="fas fa-external-link-alt text-xs"></i>
                              <?php echo e((string) $meeting['link_open_button_label']); ?>
                            </a>
                            <button
                              type="button"
                              class="meeting-card-inline-action meeting-card-inline-action--copy inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 transition hover:border-violet-300 hover:text-violet-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-violet-400 dark:hover:text-violet-300"
                              data-referral-copy
                              data-referral-value="<?php echo e((string) $meeting['meeting_link']); ?>"
                              data-referral-copy-success="Lien du rendez-vous copié."
                              data-referral-copy-error="Copie impossible. Utilisez Ctrl+C."
                            >
                              <i class="fas fa-copy text-xs"></i>
                              Copier
                            </button>
                          </div>
                        </div>
                        <p class="hidden text-[11px] app-inline-note transition-opacity" data-referral-copy-status>Lien du rendez-vous copié.</p>
                      </div>
                    <?php elseif ((string) $meeting['meeting_place'] !== '' && (string) $meeting['mode_label'] === 'Présentiel'): ?>
                      <div class="mt-4 rounded-2xl bg-gray-50 p-3 dark:bg-gray-900/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Lieu</p>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300"><?php echo e((string) $meeting['meeting_place']); ?></p>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="meeting-card-actions flex items-center gap-2 sm:flex-col sm:items-stretch">
                    <a
                      class="meeting-card-action meeting-card-action--course <?php echo e((string) $meeting['course_action_button_class']); ?>"
                      href="<?php echo e((string) $meeting['course_url']); ?>"
                    >
                      <i class="fas fa-book-open text-xs"></i>
                      Ouvrir le cours
                    </a>

                    <?php if ((string) $meeting['primary_action_type'] === 'link'): ?>
                      <a
                        class="meeting-card-action meeting-card-action--join <?php echo e((string) $meeting['primary_action_button_class']); ?>"
                        href="<?php echo e((string) $meeting['meeting_link']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        <i class="fas fa-video text-xs"></i>
                        Rejoindre
                      </a>
                    <?php elseif ((string) $meeting['primary_action_type'] === 'place'): ?>
                      <button
                        type="button"
                        class="meeting-card-action meeting-card-action--place <?php echo e((string) $meeting['primary_action_button_class']); ?>"
                        data-meeting-action="place"
                        data-meeting-place="<?php echo e((string) $meeting['meeting_place']); ?>"
                        data-meeting-title="<?php echo e((string) $meeting['meeting_title']); ?>"
                      >
                        <i class="fas fa-map-marker-alt text-xs"></i>
                        <?php echo e((string) $meeting['primary_action_label']); ?>
                      </button>
                    <?php else: ?>
                      <button
                        type="button"
                        class="meeting-card-action meeting-card-action--details <?php echo e((string) $meeting['primary_action_button_class']); ?>"
                        data-meeting-action="details"
                        data-meeting-summary="<?php echo e((string) $meeting['summary_text']); ?>"
                      >
                        <i class="fas fa-info-circle text-xs"></i>
                        <?php echo e((string) $meeting['primary_action_label']); ?>
                      </button>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <div class="hidden" data-meetings-no-match>
              <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-14 text-center shadow-card dark:border-gray-700 dark:bg-gray-800">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-300">
                  <?php echo dashboard_icon_svg('search', 'class="h-7 w-7 shrink-0"'); ?>
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Aucun résultat</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Modifiez vos filtres.</p>
                <button
                  type="button"
                  class="mt-4 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-blue-700 hover:to-cyan-600"
                  data-meetings-reset
                >
                  <i class="fas fa-undo-alt"></i>
                  Réinitialiser les filtres
                </button>
              </div>
            </div>

            <?php
            $pagination = $meetingsPagination;
            $basePath = 'student/meetings';
            $queryKey = 'page';
            $baseQuery = [
                'q' => $searchQuery !== '' ? $searchQuery : null,
                'filter' => $activeFilter !== 'all' ? $activeFilter : null,
            ];
            require __DIR__ . '/_pagination.php';
            ?>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <a
    href="#support-chat"
    data-support-url="<?php echo e($supportUrl); ?>"
    data-support-fab="1"
    class="student-dashboard-support-fab fixed bottom-3 right-3 z-40 inline-flex items-center gap-3 rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 px-3 py-2.5 text-white shadow-glow transition hover:-translate-y-0.5 hover:from-blue-700 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:from-blue-500 dark:to-cyan-500 dark:hover:from-blue-600 dark:hover:to-cyan-600 sm:bottom-4 sm:right-4 sm:px-4 sm:py-3"
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
    <span class="block text-xs text-cyan-100">Réponse rapide</span>
  </span>
</a>

  <?php if ($hasOnsiteMeeting): ?>
    <div
      id="onsite-disclaimer-modal"
      data-onsite-disclaimer-modal
      data-onsite-disclaimer-user-id="<?php echo e((string) $studentUserId); ?>"
      class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/70 px-4"
    >
      <div class="w-full max-w-xl rounded-2xl border border-slate-700 bg-slate-900 p-5 text-slate-100 shadow-2xl">
        <p class="text-xs uppercase tracking-wide app-inline-note app-inline-note--warning">Information importante</p>
        <h2 class="mt-2 text-lg font-semibold">Rendez-vous en présentiel</h2>
        <p class="mt-3 text-sm text-slate-300">
          En participant à un rendez-vous en présentiel, vous acceptez que la plateforme École Numérique ne soit pas responsable des incidents, dommages ou litiges pouvant survenir hors ligne pendant cette rencontre.
        </p>
        <p class="mt-2 text-xs text-slate-400">
          Vérifiez l’adresse, le cadre de sécurité et l’identité de votre interlocuteur avant de vous déplacer.
        </p>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
          <button type="button" data-onsite-disclaimer-later class="rounded-lg border border-slate-600 px-3 py-2 text-sm text-slate-200 hover:border-slate-400">Plus tard</button>
          <button type="button" data-onsite-disclaimer-ack class="app-btn-warning rounded-lg px-3 py-2 text-sm font-semibold">J’ai compris</button>
        </div>
      </div>
    </div>
  <?php endif; ?>
</body>
</html>
