<?php
declare(strict_types=1);

$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = dashboard_user_display_name($teacherUser, 'Enseignant');
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = dashboard_user_avatar_url($teacherUser);
$teacherInitials = dashboard_user_initials($teacherUser, 'EN');

$normalizeTitleCase = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_convert_case')) {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    return ucwords(strtolower($value));
};

$teacherFirstName = trim((string) ($teacherUser['first_name'] ?? ''));
if ($teacherFirstName === '') {
    $displayParts = preg_split('/\s+/', trim($teacherDisplayName)) ?: [];
    $teacherFirstName = trim((string) ($displayParts[0] ?? ''));
}
if ($teacherFirstName === '' && $teacherEmail !== '') {
    $emailPrefix = trim((string) explode('@', $teacherEmail, 2)[0]);
    $emailPrefix = str_replace(['.', '_', '-'], ' ', $emailPrefix);
    $emailParts = preg_split('/\s+/', $emailPrefix) ?: [];
    $teacherFirstName = trim((string) ($emailParts[0] ?? ''));
}
$teacherFirstName = $normalizeTitleCase($teacherFirstName !== '' ? $teacherFirstName : 'Enseignant');

$dashboardKpis = array_values(is_array($dashboardKpis ?? null) ? $dashboardKpis : []);
$dashboardCourses = array_values(is_array($dashboardCourses ?? null) ? $dashboardCourses : []);
$dashboardMeetings = array_values(is_array($dashboardMeetings ?? null) ? $dashboardMeetings : []);
$dashboardReviews = array_values(is_array($dashboardReviews ?? null) ? $dashboardReviews : []);
$dashboardTasks = array_values(is_array($dashboardTasks ?? null) ? $dashboardTasks : []);
$courseTotals = is_array($courseTotals ?? null) ? $courseTotals : [];
$dashboardHeroSummary = trim((string) ($dashboardHeroSummary ?? ''));
$dashboardRange = (string) ($dashboardRange ?? '30d');
if (!in_array($dashboardRange, ['30d', '3m', 'all'], true)) {
    $dashboardRange = '30d';
}
$dashboardChart = is_array($dashboardChart ?? null) ? $dashboardChart : [];
$chartLabels = array_values(array_map('strval', is_array($dashboardChart['labels'] ?? null) ? $dashboardChart['labels'] : []));
$chartValues = array_values(array_map('intval', is_array($dashboardChart['values'] ?? null) ? $dashboardChart['values'] : []));
$chartTitle = 'Inscriptions';
if ($chartLabels === []) {
    $chartLabels = ['S1', 'S2', 'S3', 'S4'];
}
if ($chartValues === [] || count($chartValues) !== count($chartLabels)) {
    $chartValues = array_pad(array_slice($chartValues, 0, count($chartLabels)), count($chartLabels), 0);
}
$chartHasUsableData = array_sum(array_map('intval', $chartValues)) > 0;

$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$pendingMeetingsCount = max(0, (int) ($pendingMeetingsCount ?? 0));
$courseCount = max(0, (int) ($courseTotals['total'] ?? count($dashboardCourses)));
$teacherSupportUrl = site_url('support?space=teacher');
$teacherProfileUrl = site_url('profile?space=teacher');
$messagesBadge = $supportUnreadCount > 0 ? ($supportUnreadCount > 99 ? '99+' : (string) $supportUnreadCount) : '';
$meetingsBadge = $pendingMeetingsCount > 0 ? ($pendingMeetingsCount > 99 ? '99+' : (string) $pendingMeetingsCount) : '';
$coursesBadge = $courseCount > 0 ? ($courseCount > 99 ? '99+' : (string) $courseCount) : '';

$siteUrl = static function (string $path): string {
    return site_url($path);
};

$projectRoot = dirname(__DIR__, 3);

$resolvePublicUrl = static function (string $value) use ($siteUrl, $projectRoot): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '//') || str_starts_with($value, 'data:')) {
        return $value;
    }

    if (str_starts_with($value, '/')) {
        $relativePath = ltrim($value, '/');
        $basePath = ltrim(rtrim((string) (defined('BASE_PATH') ? BASE_PATH : ''), '/'), '/');
        if ($basePath !== '' && str_starts_with($relativePath, $basePath . '/')) {
            $relativePath = substr($relativePath, strlen($basePath) + 1);
        }
        if (is_file($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath))) {
            return $value;
        }
        return '';
    }

    $relativePath = ltrim($value, '/');
    if (is_file($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath))) {
        return $siteUrl($relativePath);
    }

    return '';
};

$iconClass = static function (string $name): string {
    static $map = [
        'home' => 'fas fa-home',
        'book' => 'fas fa-book',
        'book-open' => 'fas fa-book-open',
        'plus-circle' => 'fas fa-plus-circle',
        'users' => 'fas fa-users',
        'chart-bar' => 'fas fa-chart-bar',
        'hand-holding-usd' => 'fas fa-hand-holding-usd',
        'handshake' => 'fas fa-handshake',
        'gift' => 'fas fa-gift',
        'ticket' => 'fas fa-ticket',
        'comments' => 'fas fa-comments',
        'headset' => 'fas fa-headset',
        'user-circle' => 'fas fa-user-circle',
        'cog' => 'fas fa-cog',
        'bell' => 'far fa-bell',
        'moon' => 'fas fa-moon',
        'sun' => 'fas fa-sun',
        'bars' => 'fas fa-bars',
        'times' => 'fas fa-times',
        'sign-out-alt' => 'fas fa-sign-out-alt',
        'search' => 'fas fa-search',
        'user-graduate' => 'fas fa-user-graduate',
        'layer-group' => 'fas fa-layer-group',
        'euro-sign' => 'fas fa-euro-sign',
        'star' => 'fas fa-star',
        'video' => 'fas fa-video',
        'calendar' => 'fas fa-calendar-alt',
        'wallet' => 'fas fa-wallet',
        'file-text' => 'fas fa-file-alt',
        'chevron-down' => 'fas fa-chevron-down',
        'check' => 'fas fa-check',
        'check-circle' => 'fas fa-check-circle',
        'award' => 'fas fa-award',
        'play-circle' => 'fas fa-play-circle',
        'video-slash' => 'fas fa-video-slash',
        'filter' => 'fas fa-filter',
        'credit-card' => 'fas fa-credit-card',
    ];

    $key = strtolower(trim($name));
    return $map[$key] ?? 'fas fa-circle';
};

$toneClasses = static function (string $tone): array {
    $tone = strtolower(trim($tone));
    return match ($tone) {
        'sky' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900/30',
            'text' => 'text-primary-600 dark:text-primary-300',
        ],
        'cyan' => [
            'bg' => 'bg-cyan-100 dark:bg-cyan-900/30',
            'text' => 'text-accent-600 dark:text-cyan-300',
        ],
        'emerald', 'green' => [
            'bg' => 'bg-green-100 dark:bg-green-900/30',
            'text' => 'text-green-600 dark:text-green-300',
        ],
        'amber', 'yellow' => [
            'bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            'text' => 'text-yellow-600 dark:text-yellow-300',
        ],
        'purple' => [
            'bg' => 'bg-purple-100 dark:bg-purple-900/30',
            'text' => 'text-purple-600 dark:text-purple-300',
        ],
        'rose', 'red' => [
            'bg' => 'bg-rose-100 dark:bg-rose-900/30',
            'text' => 'text-rose-600 dark:text-rose-300',
        ],
        default => [
            'bg' => 'bg-gray-100 dark:bg-gray-700/50',
            'text' => 'text-gray-500 dark:text-gray-300',
        ],
    };
};

$statusBadgeClasses = static function (string $tone): string {
    return match (strtolower(trim($tone))) {
        'emerald', 'green' => 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300',
        'amber', 'yellow' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300',
        'rose', 'red' => 'bg-rose-100 dark:bg-rose-900/40 text-rose-800 dark:text-rose-300',
        'slate', 'gray' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
        default => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
    };
};

$sidebarBadgeClass = static function (string $tone): string {
    return match (strtolower(trim($tone))) {
        'primary', 'blue' => 'bg-primary-600 text-white',
        'red', 'rose' => 'bg-red-500 text-white',
        'emerald', 'green' => 'bg-green-500 text-white',
        default => 'bg-blue-500 text-white',
    };
};

$formatCount = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ');
};

$formatMoney = static function (int|float $value): string {
    return number_format((float) $value, 0, ',', ' ') . ' F CFA';
};

$formatRating = static function (float $value): string {
    return number_format(max(0.0, $value), 2, ',', ' ');
};

$renderStars = static function (float $rating): string {
    $rating = max(0.0, min(5.0, $rating));
    $fullStars = (int) floor($rating);
    $hasHalf = ($rating - $fullStars) >= 0.5 && $fullStars < 5;
    $html = '';

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) {
            $html .= '<i class="fas fa-star"></i>';
            continue;
        }

        if ($hasHalf && $i === $fullStars + 1) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
            continue;
        }

        $html .= '<i class="far fa-star text-gray-300"></i>';
    }

    return $html;
};

$sidebarLinks = [
    [
        'label' => 'Tableau de bord',
        'url' => $siteUrl('teacher/dashboard'),
        'icon' => 'home',
        'active' => true,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Mes formations',
        'url' => $siteUrl('teacher/courses'),
        'icon' => 'book-open',
        'active' => false,
        'badge' => $coursesBadge,
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Créer un cours',
        'url' => $siteUrl('teacher/course_create'),
        'icon' => 'plus-circle',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Revenus',
        'url' => $siteUrl('teacher/withdrawals'),
        'icon' => 'hand-holding-usd',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Promotions et coupons',
        'url' => $siteUrl('teacher/promotions'),
        'icon' => 'ticket',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Rendez-vous',
        'url' => $siteUrl('teacher/meetings?status=pending'),
        'icon' => 'handshake',
        'active' => false,
        'badge' => $meetingsBadge,
        'badge_tone' => 'blue',
    ],
    [
        'label' => 'Parrainage',
        'url' => $siteUrl('teacher/affiliation'),
        'icon' => 'gift',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
    [
        'label' => 'Messages',
        'url' => $siteUrl('support?space=teacher'),
        'icon' => 'headset',
        'active' => false,
        'badge' => $messagesBadge,
        'badge_tone' => 'red',
    ],
    [
        'label' => 'Mon profil',
        'url' => $siteUrl('profile?space=teacher'),
        'icon' => 'user-circle',
        'active' => false,
        'badge' => '',
        'badge_tone' => 'primary',
    ],
];

$chartLabelsJson = json_encode(
    $chartLabels,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($chartLabelsJson) || $chartLabelsJson === '') {
    $chartLabelsJson = '[]';
}

$chartValuesJson = json_encode(
    $chartValues,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($chartValuesJson) || $chartValuesJson === '') {
    $chartValuesJson = '[]';
}

?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/dashboard">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Dashboard Enseignant · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/dashboard" class="app-role-teacher teacher-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">
<div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
  <?php $teacherNavActive = 'dashboard'; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
    <?php $teacherTopTitle = 'Tableau de bord'; ?>
    <?php $teacherTopSubtitle = 'Espace formateur'; ?>
    <?php $showCatalogue = false; ?>
    <?php require __DIR__ . '/_topbar.php'; ?>
    <div class="teacher-dashboard-content p-4 pb-20 space-y-6 animate-fade-up sm:p-5 sm:pb-20">
      <?php $teacherWelcomeTitle = 'Bon retour, ' . $teacherFirstName . ' !'; ?>
      <?php $teacherWelcomeSummary = $dashboardHeroSummary !== '' ? $dashboardHeroSummary : 'Suivez vos cours, vos inscriptions et vos avis récents depuis cette vue.'; ?>
      <?php $teacherWelcomeActionUrl = $siteUrl('teacher/course_create'); ?>
      <?php $teacherWelcomeActionLabel = 'Créer un cours'; ?>
      <?php $teacherWelcomeActionIcon = 'plus-square'; ?>
      <?php require __DIR__ . '/_welcome_hero.php'; ?>

      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($dashboardKpis as $kpi): ?>
          <?php
            $label = trim((string) ($kpi['label'] ?? ''));
            $meta = trim((string) ($kpi['meta'] ?? ''));
            $type = (string) ($kpi['type'] ?? 'count');
            $tone = (string) ($kpi['tone'] ?? 'sky');
            $icon = (string) ($kpi['icon'] ?? 'star');
            $value = $kpi['value'] ?? 0;
            $toneClass = $toneClasses($tone);
            $displayValue = match ($type) {
                'money' => $formatMoney((int) $value),
                'rating' => $formatRating((float) $value),
                default => $formatCount((int) $value),
            };
            $isRating = $type === 'rating';
          ?>
          <article class="teacher-dashboard-card teacher-kpi-card bg-white dark:bg-gray-800 rounded-xl p-4 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
            <div class="flex items-center justify-between gap-4">
              <div class="min-w-0">
                <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo e($label); ?></p>
                <p class="text-2xl font-bold text-gray-950 dark:text-white mt-1"><?php echo e($displayValue); ?></p>
              </div>
              <div class="w-9 h-9 <?php echo e($toneClass['bg']); ?> rounded-xl flex items-center justify-center <?php echo e($toneClass['text']); ?>">
                <i class="<?php echo e($iconClass($icon)); ?>"></i>
              </div>
            </div>
            <?php if ($isRating): ?>
              <div class="flex text-yellow-400 text-sm mt-2"><?php echo $renderStars((float) $value); ?></div>
              <p class="text-xs text-gray-600 dark:text-gray-300 mt-2"><?php echo e($meta !== '' ? $meta : 'Aucun avis pour le moment'); ?></p>
            <?php else: ?>
              <p class="text-xs text-gray-600 dark:text-gray-300 mt-2"><?php echo e($meta !== '' ? $meta : ''); ?></p>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </section>

      <section>
        <div class="flex items-center justify-between mb-4 gap-3">
          <h3 class="app-inline-title font-bold text-gray-950 dark:text-white text-lg"><?php echo dashboard_title_icon('book-open'); ?><span>Mes formations</span></h3>
          <a href="<?php echo e($siteUrl('teacher/course_create')); ?>" class="teacher-primary-action inline-flex items-center gap-2 rounded-xl bg-primary-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-300">
            <i class="fas fa-plus-circle"></i>
            <span>Créer un cours</span>
          </a>
        </div>

        <?php if ($dashboardCourses !== []): ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach (array_slice($dashboardCourses, 0, 4) as $course): ?>
              <?php
                $courseTitle = trim((string) ($course['title'] ?? 'Cours'));
                $courseStudents = trim((string) ($course['students_label'] ?? '0 étudiants'));
                $coursePrice = trim((string) ($course['price_label'] ?? '--'));
                $courseStatus = trim((string) ($course['status_label'] ?? 'Brouillon'));
                $courseStatusTone = (string) ($course['status_tone'] ?? 'slate');
                $courseUrl = trim((string) ($course['url'] ?? ''));
                $coverUrl = $resolvePublicUrl((string) ($course['cover_url'] ?? ''));
                $coverAlt = trim((string) ($course['cover_alt'] ?? 'Miniature du cours'));
                $statusClasses = $statusBadgeClasses($courseStatusTone);
                $courseInitial = function_exists('mb_substr')
                  ? mb_strtoupper(mb_substr($courseTitle !== '' ? $courseTitle : 'Cours', 0, 1, 'UTF-8'), 'UTF-8')
                  : strtoupper(substr($courseTitle !== '' ? $courseTitle : 'Cours', 0, 1));
              ?>
              <?php if ($courseUrl !== ''): ?><a href="<?php echo e($courseUrl); ?>" class="teacher-course-card block bg-white dark:bg-gray-800 rounded-xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden hover-lift"><?php else: ?><article class="teacher-course-card bg-white dark:bg-gray-800 rounded-xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden hover-lift"><?php endif; ?>
                <?php if ($coverUrl !== ''): ?>
                  <div class="teacher-course-cover">
                    <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt !== '' ? $coverAlt : $courseTitle); ?>" class="h-full w-full object-cover">
                  </div>
                <?php else: ?>
                  <div class="teacher-course-cover teacher-course-cover--fallback">
                    <span class="teacher-course-cover__initial"><?php echo e($courseInitial); ?></span>
                    <span class="teacher-course-cover__label">Formation</span>
                  </div>
                <?php endif; ?>
                <div class="p-4">
                  <div class="flex items-start justify-between gap-3">
                    <h4 class="font-bold text-gray-950 dark:text-white leading-snug"><?php echo e($courseTitle); ?></h4>
                    <span class="<?php echo e($statusClasses); ?> shrink-0 text-[10px] px-2 py-0.5 rounded-full"><?php echo e($courseStatus !== '' ? $courseStatus : 'Brouillon'); ?></span>
                  </div>
                  <div class="flex items-center text-xs text-gray-600 dark:text-gray-300 mt-2">
                    <i class="fas fa-users mr-1"></i>
                    <span><?php echo e($courseStudents !== '' ? $courseStudents : '0 étudiants'); ?></span>
                  </div>
                  <div class="flex items-center justify-between mt-3">
                    <span class="text-primary-600 dark:text-primary-400 font-bold"><?php echo e($coursePrice !== '' ? $coursePrice : '--'); ?></span>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-300">Gérer</span>
                  </div>
                </div>
              <?php if ($courseUrl !== ''): ?></a><?php else: ?></article><?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="rounded-2xl border border-dashed border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-6 text-sm text-gray-500 dark:text-gray-300">
            Aucune formation publiée pour le moment.
          </div>
        <?php endif; ?>
      </section>

      <section class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="teacher-dashboard-card lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div class="min-w-0">
              <h3 class="app-inline-title font-bold text-gray-950 dark:text-white"><?php echo dashboard_title_icon('trending-up'); ?><span><?php echo e($chartTitle); ?></span></h3>
              <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Tendance des inscriptions sur la période sélectionnée.</p>
            </div>
            <form method="get" action="<?php echo e($siteUrl('teacher/dashboard')); ?>">
              <label class="sr-only" for="dashboard-range-select">Période du graphique</label>
              <select id="dashboard-range-select" data-dashboard-range-select name="dashboard_range" class="text-xs bg-gray-100 dark:bg-gray-700 border-none rounded-lg px-2 py-1.5 text-gray-700 dark:text-gray-100">
                <option value="30d" <?php echo $dashboardRange === '30d' ? 'selected' : ''; ?>>30 derniers jours</option>
                <option value="3m" <?php echo $dashboardRange === '3m' ? 'selected' : ''; ?>>3 derniers mois</option>
                <option value="all" <?php echo $dashboardRange === 'all' ? 'selected' : ''; ?>>Tout</option>
              </select>
            </form>
          </div>
          <?php if ($chartHasUsableData): ?>
            <div class="teacher-chart-frame h-48">
              <canvas
                id="teacher-dashboard-chart"
                class="teacher-dashboard-chart block h-full w-full"
                data-chart-labels="<?php echo e($chartLabelsJson); ?>"
                data-chart-values="<?php echo e($chartValuesJson); ?>"
              ></canvas>
            </div>
          <?php else: ?>
            <div class="teacher-chart-empty rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-6 dark:border-gray-700 dark:bg-gray-700/35">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                  <i class="fas fa-chart-line"></i>
                </span>
                <div>
                  <p class="font-semibold text-gray-950 dark:text-white">Pas encore assez d'inscriptions pour générer une tendance.</p>
                  <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Les données apparaîtront après vos prochaines ventes ou inscriptions actives.
                    <?php if ($dashboardRange !== 'all'): ?>
                      Choisissez "Tout" pour consulter l'historique complet depuis la création du compte.
                    <?php endif; ?>
                  </p>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="teacher-dashboard-card bg-white dark:bg-gray-800 rounded-xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between mb-4 gap-3">
            <h3 class="app-inline-title font-bold text-gray-950 dark:text-white"><?php echo dashboard_title_icon('calendar'); ?><span>Prochains rendez-vous</span></h3>
            <a href="<?php echo e($siteUrl('teacher/meetings?status=pending')); ?>" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300">Tout voir</a>
          </div>

          <div class="space-y-4">
            <?php if ($dashboardMeetings !== []): ?>
              <?php foreach (array_slice($dashboardMeetings, 0, 3) as $meeting): ?>
                <?php
                  $meetingTone = (string) ($meeting['tone'] ?? 'primary');
                  $meetingToneClass = $toneClasses($meetingTone);
                  $meetingIcon = (string) ($meeting['icon'] ?? 'calendar');
                  $meetingTitle = trim((string) ($meeting['title'] ?? 'Rendez-vous'));
                  $meetingWhen = trim((string) ($meeting['when_short'] ?? ''));
                  if ($meetingWhen === '') {
                      $meetingWhen = trim((string) ($meeting['when_label'] ?? 'À planifier'));
                  }
                  $meetingCourse = trim((string) ($meeting['course_title'] ?? ''));
                  $meetingUrl = trim((string) ($meeting['url'] ?? ''));
                ?>
                <?php if ($meetingUrl !== ''): ?><a href="<?php echo e($meetingUrl); ?>" class="flex items-start gap-3"><?php else: ?><div class="flex items-start gap-3"><?php endif; ?>
                  <div class="w-10 h-10 <?php echo e($meetingToneClass['bg']); ?> rounded-xl flex items-center justify-center <?php echo e($meetingToneClass['text']); ?>">
                    <i class="<?php echo e($iconClass($meetingIcon)); ?>"></i>
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-gray-950 dark:text-white"><?php echo e($meetingTitle); ?></p>
                    <p class="text-xs text-gray-600 dark:text-gray-300"><?php echo e($meetingWhen); ?></p>
                    <?php if ($meetingCourse !== ''): ?>
                      <p class="text-[11px] text-gray-500 dark:text-gray-400"><?php echo e($meetingCourse); ?></p>
                    <?php endif; ?>
                  </div>
                <?php if ($meetingUrl !== ''): ?></a><?php else: ?></div><?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-300">
                Aucun rendez-vous en attente pour le moment.
              </div>
            <?php endif; ?>
          </div>

          <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">À faire</p>
            <?php if ($dashboardTasks !== []): ?>
              <ul class="mt-2 space-y-2 text-sm">
                <?php foreach (array_slice($dashboardTasks, 0, 4) as $task): ?>
                  <?php
                    $taskLabel = trim((string) ($task['label'] ?? 'Action'));
                    $taskCount = max(0, (int) ($task['count'] ?? 0));
                    $taskCritical = !empty($task['critical']);
                    $taskText = $taskLabel;
                    if ($taskCount > 0) {
                        $taskText .= ' (' . $formatCount($taskCount) . ')';
                    }
                  ?>
                  <li class="flex items-center gap-2">
                    <input type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="<?php echo $taskCritical ? 'font-medium text-gray-950 dark:text-white' : 'text-gray-700 dark:text-gray-300'; ?>"><?php echo e($taskText); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="mt-2 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-300">
                Aucune action prioritaire pour le moment.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="teacher-dashboard-card bg-white dark:bg-gray-800 rounded-xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h3 class="app-inline-title font-bold text-gray-950 dark:text-white"><?php echo dashboard_title_icon('star'); ?><span>Derniers avis étudiants</span></h3>
          <a href="<?php echo e($siteUrl('teacher/reviews')); ?>" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300">Voir tous les avis</a>
        </div>

        <?php if ($dashboardReviews !== []): ?>
          <div class="grid gap-3 md:grid-cols-2">
            <?php foreach (array_slice($dashboardReviews, 0, 4) as $index => $review): ?>
              <?php
                $reviewName = trim((string) ($review['reviewer_name'] ?? 'Étudiant'));
                $reviewCourse = trim((string) ($review['course_title'] ?? 'Cours'));
                $reviewComment = trim((string) ($review['comment'] ?? 'Sans commentaire.'));
                $reviewRating = max(1, min(5, (int) ($review['rating'] ?? 0)));
                $reviewCreated = trim((string) ($review['created_label'] ?? 'Récemment'));
                $reviewAvatar = $resolvePublicUrl((string) ($review['avatar_url'] ?? ''));
                $reviewInitials = trim((string) ($review['avatar_initials'] ?? ''));
                if ($reviewInitials === '') {
                  $reviewNameParts = preg_split('/\s+/', trim($reviewName)) ?: [];
                  $reviewInitials = '';
                  foreach (array_slice($reviewNameParts, 0, 2) as $part) {
                    $part = trim((string) $part);
                    if ($part === '') {
                      continue;
                    }
                    $reviewInitials .= function_exists('mb_substr')
                      ? mb_substr($part, 0, 1, 'UTF-8')
                      : substr($part, 0, 1);
                  }
                  $reviewInitials = strtoupper($reviewInitials !== '' ? $reviewInitials : 'ET');
                }
              ?>
              <div class="teacher-review-card flex items-start gap-4 rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/35">
                <?php if ($reviewAvatar !== ''): ?>
                  <img src="<?php echo e($reviewAvatar); ?>" class="w-10 h-10 rounded-full object-cover" alt="<?php echo e($reviewName); ?>">
                <?php else: ?>
                  <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-200 font-semibold">
                    <?php echo e($reviewInitials); ?>
                  </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="font-medium text-gray-950 dark:text-white"><?php echo e($reviewName); ?></p>
                    <span class="rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-200"><?php echo e($reviewCourse); ?></span>
                  </div>
                  <div class="mt-1 flex items-center gap-2 text-yellow-400 text-sm">
                    <span><?php echo $renderStars((float) $reviewRating); ?></span>
                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300"><?php echo e((string) $reviewRating); ?>/5</span>
                  </div>
                  <p class="mt-2 text-gray-700 dark:text-gray-200 text-sm">"<?php echo e($reviewComment); ?>"</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-2"><?php echo e($reviewCreated); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-300">
            Aucun avis étudiant pour le moment.
          </div>
        <?php endif; ?>
      </section>
    </div>
  </main>
</div>

</body>
</html>

