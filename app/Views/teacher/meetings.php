<?php
declare(strict_types=1);

$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = dashboard_user_display_name($teacherUser, 'Enseignant');
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = dashboard_user_avatar_url($teacherUser);
$teacherInitials = dashboard_user_initials($teacherUser, 'EN');

$fmtDt = isset($fmtDt) && is_callable($fmtDt) ? $fmtDt : static function (?string $dbValue): string {
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

$errors = array_values(is_array($errors ?? null) ? $errors : []);
$success = trim((string) ($success ?? ''));
$status = trim((string) ($status ?? 'pending'));
if (!in_array($status, ['pending', 'confirmed', 'cancelled', 'all'], true)) {
    $status = 'pending';
}
$courseFilterId = max(0, (int) ($courseFilterId ?? 0));
$q = trim((string) ($q ?? ''));
$courses = array_values(is_array($courses ?? null) ? $courses : []);
$meetingGroups = is_array($meetingGroups ?? null) ? $meetingGroups : [];
$groupOrder = array_values(is_array($groupOrder ?? null) ? $groupOrder : array_keys($meetingGroups));
$statusLabels = array_merge(
    [
        'pending' => 'En attente',
        'confirmed' => 'Confirmé',
        'cancelled' => 'Annulé',
        'all' => 'Tous',
    ],
    is_array($statusLabels ?? null) ? $statusLabels : []
);
$statusLabels['confirmed'] = 'Confirmé';
$statusLabels['cancelled'] = 'Annulé';
$statusLabels['all'] = 'Tous';
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$csrfToken = view_csrf_token();

$formatCount = static fn (int|float $value): string => number_format((float) max(0, $value), 0, ',', ' ');
$formatDuration = static function (?string $start, ?string $end): string {
    if (!$start || !$end) {
        return '';
    }

    $startTs = strtotime($start);
    $endTs = strtotime($end);
    if (!$startTs || !$endTs || $endTs <= $startTs) {
        return '';
    }

    $minutes = (int) round(($endTs - $startTs) / 60);
    if ($minutes < 60) {
        return $minutes . ' min';
    }

    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;
    return $remaining > 0 ? ($hours . ' h ' . $remaining . ' min') : ($hours . ' h');
};

$statusBadgeClass = static function (string $state): string {
    return match ($state) {
        'confirmed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
        'pending' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
        'cancelled' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200',
        default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-300',
    };
};

$toneClass = static function (string $tone): array {
    return match ($tone) {
        'emerald' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-600 dark:text-green-300'],
        'amber' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-600 dark:text-yellow-300'],
        'rose' => ['bg' => 'bg-rose-100 dark:bg-rose-900/30', 'text' => 'text-rose-600 dark:text-rose-300'],
        'cyan' => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/30', 'text' => 'text-cyan-600 dark:text-cyan-300'],
        default => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-primary-600 dark:text-primary-300'],
    };
};

$modeLabel = static function (string $mode): string {
    return $mode === 'onsite' ? 'Présentiel' : 'En ligne';
};

$inputClass = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100';
$labelClass = 'block text-sm font-semibold text-gray-700 dark:text-gray-200';
$filterButtonClass = 'inline-flex items-center justify-center rounded-xl border px-4 py-2 text-sm font-semibold transition';

$groupStats = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'cancelled' => 0,
];
$slotTotal = 0;
$nextConfirmedAt = '';
foreach ($groupOrder as $groupKey) {
    if (!isset($meetingGroups[$groupKey]) || !is_array($meetingGroups[$groupKey])) {
        continue;
    }

    $groupStats['total']++;
    $statusKey = (string) ($meetingGroups[$groupKey]['status'] ?? 'pending');
    if (isset($groupStats[$statusKey])) {
        $groupStats[$statusKey]++;
    }

    $items = array_values(is_array($meetingGroups[$groupKey]['items'] ?? null) ? $meetingGroups[$groupKey]['items'] : []);
    $slotTotal += count($items);
    if ($statusKey === 'confirmed') {
        foreach ($items as $item) {
            $candidate = trim((string) ($item['start_at'] ?? ''));
            if ($candidate === '') {
                continue;
            }
            if ($nextConfirmedAt === '' || strtotime($candidate) < strtotime($nextConfirmedAt)) {
                $nextConfirmedAt = $candidate;
            }
        }
    }
}

$statusLabel = (string) ($statusLabels[$status] ?? 'Tous');
$courseLabel = 'Tous les cours';
if ($courseFilterId > 0) {
    foreach ($courses as $courseItem) {
        if ((int) ($courseItem['id'] ?? 0) === $courseFilterId) {
            $courseLabel = trim((string) ($courseItem['title'] ?? $courseLabel));
            break;
        }
    }
}

$isFilterPanelOpen = ($courseFilterId > 0) || ($q !== '');

$baseUrl = site_url('teacher/meetings');
$buildMeetingsUrl = static function (array $overrides = []) use ($baseUrl, $status, $courseFilterId, $q): string {
    $params = [
        'status' => $status,
    ];
    if ($courseFilterId > 0) {
        $params['course_id'] = $courseFilterId;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            unset($params[$key]);
            continue;
        }
        $params[$key] = $value;
    }

    $query = http_build_query($params);
    return $baseUrl . ($query !== '' ? ('?' . $query) : '');
};
$statusLinks = [
    ['key' => 'pending', 'label' => 'En attente', 'url' => $buildMeetingsUrl(['status' => 'pending'])],
    ['key' => 'confirmed', 'label' => 'Confirmés', 'url' => $buildMeetingsUrl(['status' => 'confirmed'])],
    ['key' => 'cancelled', 'label' => 'Annulés', 'url' => $buildMeetingsUrl(['status' => 'cancelled'])],
    ['key' => 'all', 'label' => 'Tous', 'url' => $buildMeetingsUrl(['status' => 'all'])],
];

$metricCards = [
    ['label' => 'Demandes', 'value' => $groupStats['total'], 'meta' => $formatCount($slotTotal) . ' créneau(x)', 'icon' => 'calendar', 'tone' => 'blue'],
    ['label' => 'À traiter', 'value' => $groupStats['pending'], 'meta' => 'À confirmer', 'icon' => 'clock', 'tone' => 'amber'],
    ['label' => 'Confirmés', 'value' => $groupStats['confirmed'], 'meta' => $nextConfirmedAt !== '' ? ('Prochain ' . $fmtDt($nextConfirmedAt)) : 'Aucun créneau confirmé', 'icon' => 'check-circle', 'tone' => 'emerald'],
    ['label' => 'Annulés', 'value' => $groupStats['cancelled'], 'meta' => 'Historique conservé', 'icon' => 'alert-circle', 'tone' => 'rose'],
];
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/meetings">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Mes rendez-vous · Enseignant · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_meetings_filters.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_meetings_filters.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/meetings" class="app-role-teacher teacher-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">
<?php
$teacherNavActive = 'meetings';
$teacherDashboardShell = true;
$showCatalogue = false;
$teacherTopTitle = 'Mes rendez-vous';
$teacherTopSubtitle = 'Espace formateur';
$teacherTopContextSection = 'Gestion';
$teacherTopContextLabel = $statusLabel;
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

      <section class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-2xl p-5 text-white shadow-glow sm:p-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-100">Rendez-vous étudiants</p>
            <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Gestion des demandes</h1>
            <p class="mt-2 max-w-3xl text-sm font-medium text-white/85">Gérez les demandes des étudiants et vos créneaux disponibles.</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <?php echo e($formatCount((int) $groupStats['pending'])); ?> à traiter
              </span>
              <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
                <i class="fas fa-filter" aria-hidden="true"></i>
                <?php echo e($statusLabel); ?>
              </span>
              <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
                <i class="fas fa-book-open" aria-hidden="true"></i>
                <?php echo e($courseLabel); ?>
              </span>
              <?php if ($q !== ''): ?>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
                  <i class="fas fa-search" aria-hidden="true"></i>
                  <?php echo e($q); ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <a href="<?php echo e($baseUrl); ?>" class="inline-flex w-fit items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-50">
              <i class="fas fa-undo-alt" aria-hidden="true"></i>
              Réinitialiser
            </a>
          </div>
        </div>
      </section>

      <section id="teacher-meetings-stats" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($metricCards as $card): ?>
          <?php $metricTone = $toneClass((string) ($card['tone'] ?? 'blue')); ?>
          <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-200 dark:border-gray-700/90 hover-lift">
            <div class="flex items-center justify-between gap-4">
              <div class="min-w-0">
                <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo e((string) $card['label']); ?></p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($formatCount((int) ($card['value'] ?? 0))); ?></p>
              </div>
              <div class="flex h-10 w-10 items-center justify-center rounded-xl <?php echo e($metricTone['bg']); ?> <?php echo e($metricTone['text']); ?>">
                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg((string) ($card['icon'] ?? 'calendar')); ?></span>
              </div>
            </div>
            <p class="mt-2 text-xs font-medium text-gray-600 dark:text-gray-300"><?php echo e((string) ($card['meta'] ?? '')); ?></p>
          </article>
        <?php endforeach; ?>
      </section>

      <section class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-200 dark:border-gray-700/90">
        <form method="get" class="space-y-3" data-meetings-filter-form data-filter-open="<?php echo $isFilterPanelOpen ? 'true' : 'false'; ?>">
          <input type="hidden" name="status" value="<?php echo e($status); ?>">
          <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-300">Filtres</p>
              <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Demandes de rendez-vous</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <?php foreach ($statusLinks as $link): ?>
                <a href="<?php echo e((string) $link['url']); ?>" class="inline-flex items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold transition <?php echo $status === $link['key'] ? 'border-primary-500 bg-primary-50 text-primary-700 dark:border-primary-500/60 dark:bg-primary-500/10 dark:text-primary-200' : 'border-gray-200 bg-white text-gray-600 hover:border-primary-200 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-primary-500/50'; ?>">
                  <?php echo e((string) $link['label']); ?>
                </a>
              <?php endforeach; ?>
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-primary-200 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                data-meetings-filter-toggle
                aria-controls="teacher-meetings-filter-panel"
                aria-expanded="<?php echo $isFilterPanelOpen ? 'true' : 'false'; ?>"
              >
                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('filter'); ?></span>
                <span data-meetings-filter-toggle-label><?php echo $isFilterPanelOpen ? 'Masquer' : 'Filtres'; ?></span>
              </button>
            </div>
          </div>

          <div id="teacher-meetings-filter-panel" data-meetings-filter-panel <?php echo $isFilterPanelOpen ? '' : 'hidden'; ?> class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
              <label class="<?php echo e($labelClass); ?>">
                Cours
                <select name="course_id" class="<?php echo e($inputClass); ?>">
                  <option value="0">Tous les cours</option>
                  <?php foreach ($courses as $course): ?>
                    <?php $courseId = (int) ($course['id'] ?? 0); ?>
                    <option value="<?php echo e((string) $courseId); ?>" <?php echo $courseFilterId === $courseId ? 'selected' : ''; ?>>
                      <?php echo e((string) ($course['title'] ?? 'Cours')); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>

              <label class="<?php echo e($labelClass); ?>">
                Recherche étudiant
                <input name="q" value="<?php echo e($q); ?>" class="<?php echo e($inputClass); ?>" placeholder="Nom ou e-mail">
              </label>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
              <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700">
                <i class="fas fa-filter" aria-hidden="true"></i>
                Appliquer
              </button>
              <a href="<?php echo e($baseUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-primary-200 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <i class="fas fa-undo-alt" aria-hidden="true"></i>
                Réinitialiser
              </a>
            </div>
          </div>
        </form>
      </section>

      <section class="space-y-4" data-meetings-list data-pagination-list data-pagination-item=".teacher-meeting-card" data-pagination-size-mobile="3" data-pagination-size-desktop="5">
        <?php if ($groupOrder === []): ?>
          <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-card border border-gray-200 dark:border-gray-700/90">
            <p class="text-sm text-gray-600 dark:text-gray-300">Aucune demande de rendez-vous pour ce filtre.</p>
          </div>
        <?php else: ?>
          <?php foreach ($groupOrder as $key): ?>
            <?php
              if (!isset($meetingGroups[$key]) || !is_array($meetingGroups[$key])) {
                  continue;
              }
              $group = $meetingGroups[$key];
              $statusVal = (string) ($group['status'] ?? 'pending');
              $items = array_values(is_array($group['items'] ?? null) ? $group['items'] : []);
              $first = $items[0] ?? null;
              $groupMode = (string) ($group['mode'] ?? '');
              $courseLine = trim((string) ($group['course_title'] ?? 'Cours'));
              $chapterTitle = trim((string) ($group['chapter_title'] ?? ''));
              if ($chapterTitle !== '') {
                  $courseLine .= ' · ' . $chapterTitle;
              }
              $studentName = trim((string) ($group['student_name'] ?? 'Étudiant'));
              $studentEmail = trim((string) ($group['student_email'] ?? ''));
            ?>
            <details class="teacher-meeting-card bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-200 dark:border-gray-700/90 overflow-hidden hover-lift" data-status="<?php echo e($statusVal); ?>" <?php echo $statusVal === 'pending' ? 'open' : ''; ?>>
              <summary class="cursor-pointer list-none px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($statusBadgeClass($statusVal)); ?>"><?php echo e((string) ($statusLabels[$statusVal] ?? $statusVal)); ?></span>
                      <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"><?php echo e($modeLabel($groupMode)); ?></span>
                      <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"><?php echo e($formatCount(count($items))); ?> créneau(x)</span>
                    </div>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.16em] text-gray-600 dark:text-gray-300"><?php echo e($courseLine); ?></p>
                    <h3 class="mt-1 text-base font-bold text-gray-900 dark:text-white"><?php echo e((string) ($group['title'] ?? 'Rendez-vous')); ?></h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                      <?php echo e($studentName); ?><?php if ($studentEmail !== ''): ?> · <?php echo e($studentEmail); ?><?php endif; ?>
                    </p>
                  </div>
                  <div class="text-left lg:text-right">
                    <?php if ($statusVal === 'confirmed' && is_array($first) && !empty($first['start_at'])): ?>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo e($fmtDt((string) $first['start_at'])); ?></p>
                      <?php if (!empty($first['end_at'])): ?>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Fin <?php echo e($fmtDt((string) $first['end_at'])); ?></p>
                      <?php endif; ?>
                    <?php else: ?>
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">Détails</p>
                      <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Ouvrir la fiche</p>
                    <?php endif; ?>
                  </div>
                </div>
              </summary>

              <div class="space-y-4 border-t border-gray-100 bg-gray-50 px-5 py-5 dark:border-gray-700 dark:bg-gray-900">
                <div class="grid gap-3">
                  <?php foreach ($items as $idx => $meeting): ?>
                    <?php
                      $meetingStatus = (string) ($meeting['status'] ?? $statusVal);
                      $meetingMode = (string) ($meeting['mode'] ?? $groupMode);
                      $duration = $formatDuration($meeting['start_at'] ?? null, $meeting['end_at'] ?? null);
                    ?>
                    <article class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-950">
                      <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                          <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-gray-900 dark:text-white">Créneau <?php echo e((string) ($idx + 1)); ?></p>
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($statusBadgeClass($meetingStatus)); ?>"><?php echo e((string) ($statusLabels[$meetingStatus] ?? $meetingStatus)); ?></span>
                            <?php if ($duration !== ''): ?>
                              <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"><?php echo e($duration); ?></span>
                            <?php endif; ?>
                          </div>
                          <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            <?php echo e($fmtDt($meeting['start_at'] ?? null)); ?>
                            <?php if (!empty($meeting['end_at'])): ?> · fin <?php echo e($fmtDt($meeting['end_at'] ?? null)); ?><?php endif; ?>
                          </p>
                          <?php if ($meetingMode === 'online' && !empty($meeting['meeting_link'])): ?>
                            <a href="<?php echo e((string) $meeting['meeting_link']); ?>" target="_blank" rel="noopener" class="mt-2 inline-flex max-w-full items-center gap-2 break-all text-sm font-semibold text-primary-600 underline dark:text-primary-300">
                              <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                              <?php echo e((string) $meeting['meeting_link']); ?>
                            </a>
                          <?php elseif ($meetingMode === 'onsite' && !empty($meeting['meeting_place'])): ?>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300"><?php echo e((string) $meeting['meeting_place']); ?></p>
                          <?php endif; ?>
                        </div>

                        <?php if ($meetingStatus === 'pending'): ?>
                          <details class="w-full xl:max-w-xl">
                            <summary class="cursor-pointer list-none rounded-xl border border-primary-200 bg-primary-50 px-4 py-2.5 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200">
                              Confirmer ce créneau
                            </summary>
                            <form method="post" class="mt-3 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900" data-confirm-message="Confirmer ce créneau ?">
                              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                              <input type="hidden" name="action" value="confirm_meeting">
                              <input type="hidden" name="meeting_id" value="<?php echo e((string) ($meeting['id'] ?? 0)); ?>">
                              <div class="grid gap-4 sm:grid-cols-2">
                                <label class="<?php echo e($labelClass); ?>">
                                  Début
                                  <input type="datetime-local" name="start_at" required value="<?php echo e($dtLocalValue($meeting['start_at'] ?? null)); ?>" class="<?php echo e($inputClass); ?>">
                                </label>
                                <label class="<?php echo e($labelClass); ?>">
                                  Fin
                                  <input type="datetime-local" name="end_at" required value="<?php echo e($dtLocalValue($meeting['end_at'] ?? null)); ?>" class="<?php echo e($inputClass); ?>">
                                </label>
                              </div>
                              <?php if ($meetingMode === 'online'): ?>
                                <label class="<?php echo e($labelClass); ?> mt-4">
                                  Lien de visioconférence
                                  <input name="meeting_link" required placeholder="https://..." class="<?php echo e($inputClass); ?>">
                                </label>
                              <?php else: ?>
                                <label class="<?php echo e($labelClass); ?> mt-4">
                                  Adresse
                                  <input name="meeting_place" required placeholder="Adresse complète" class="<?php echo e($inputClass); ?>">
                                </label>
                              <?php endif; ?>
                              <div class="mt-4 flex justify-end">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700">
                                  <i class="fas fa-check" aria-hidden="true"></i>
                                  Confirmer
                                </button>
                              </div>
                            </form>
                          </details>
                        <?php endif; ?>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>

                <?php if (in_array($statusVal, ['pending', 'confirmed'], true) && is_array($first)): ?>
                  <?php $cancelMessage = $statusVal === 'pending' ? 'Demander 3 nouveaux créneaux ?' : 'Annuler ce rendez-vous ?'; ?>
                  <details class="flex justify-end">
                    <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 transition hover:border-rose-300 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                      <i class="fas fa-times" aria-hidden="true"></i>
                      <?php echo e($statusVal === 'pending' ? 'Demander 3 nouveaux créneaux' : 'Annuler le rendez-vous'); ?>
                    </summary>
                    <form method="post" class="mt-3 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900" data-confirm-message="<?php echo e($cancelMessage); ?>">
                      <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                      <input type="hidden" name="action" value="cancel_meeting">
                      <input type="hidden" name="meeting_id" value="<?php echo e((string) ($first['id'] ?? 0)); ?>">
                      <label class="<?php echo e($labelClass); ?>">
                        Motif
                        <textarea name="reason" required rows="3" class="<?php echo e($inputClass); ?>" placeholder="Expliquez à l'étudiant pourquoi ce rendez-vous est annulé"></textarea>
                      </label>
                      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Ce motif sera envoyé à l'étudiant.</p>
                      <div class="mt-4 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                          <i class="fas fa-paper-plane" aria-hidden="true"></i>
                          Envoyer le motif
                        </button>
                      </div>
                    </form>
                  </details>
                <?php endif; ?>
              </div>
            </details>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </div>
  </main>
</div>
</body>
</html>
