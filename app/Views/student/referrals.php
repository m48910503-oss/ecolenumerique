<?php
$fmtDtShort = isset($fmtDtShort) && is_callable($fmtDtShort) ? $fmtDtShort : static function (string $dbValue): string {
    if ($dbValue === '') {
        return '';
    }
    $ts = strtotime($dbValue);
    if ($ts === false) {
        return (string) $dbValue;
    }

    return date('d/m/Y H:i', $ts);
};

$referralRows = is_array($referralByUser ?? null) ? $referralByUser : [];
$referralCount = max(0, (int) ($referralByUserCount ?? count($referralRows)));
$referralByUserPagination = is_array($referralByUserPagination ?? null) ? $referralByUserPagination : ['page' => 1];
$referralWithdrawalRows = is_array($referralWithdrawalHistory ?? null) ? $referralWithdrawalHistory : [];
$referralWithdrawalCount = max(0, (int) ($referralWithdrawalHistoryCount ?? count($referralWithdrawalRows)));
$referralWithdrawalPagination = is_array($referralWithdrawalPagination ?? null) ? $referralWithdrawalPagination : ['page' => 1];
$referralCommissionRows = is_array($referralCommissionHistory ?? null) ? $referralCommissionHistory : [];
$referralCommissionCount = max(0, (int) ($referralCommissionHistoryCount ?? count($referralCommissionRows)));
$referralCommissionPagination = is_array($referralCommissionHistoryPagination ?? null) ? $referralCommissionHistoryPagination : ['page' => 1];
$referralsPageMode = in_array(($referralsPageMode ?? 'overview'), ['overview', 'referees', 'history'], true) ? (string) $referralsPageMode : 'overview';
$isRefereesPage = $referralsPageMode === 'referees';
$isHistoryPage = $referralsPageMode === 'history';
$notificationsUrl = site_url('notifications?space=student');
$catalogueUrl = site_url('catalogue');
$dashboardUrl = site_url('student/dashboard');
$myCoursesUrl = site_url('student/my_courses');
$calendarUrl = site_url('student/meetings');
$certificatesUrl = site_url('student/certificates');
$favoritesUrl = site_url('student/favorites');
$ordersUrl = site_url('student/orders');
$referralsUrl = site_url('student/affiliation');
$referralRefereesUrl = site_url('student/referrals/filleuls');
$referralGainsUrl = site_url('student/referrals/gains');
$supportUrl = site_url('support?space=student');
$profileUrl = site_url('profile?space=student');
$homeUrl = site_url('');
$messagesUrl = site_url('support?space=student');
$settingsUrl = site_url('profile?space=student');
$logoutUrl = site_url('logout');
$logoutCsrf = function_exists('view_csrf_token') ? view_csrf_token() : '';
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$studentDashboardJsUrl = site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time()));
$studentReferralsJsUrl = site_url('assets/js/student_referrals.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_referrals.js') ?: time()));
$referralCopyJsUrl = site_url('assets/js/referral_copy.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/referral_copy.js') ?: time()));
$sidebarToggleJsUrl = site_url('assets/js/sidebar_toggle.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/sidebar_toggle.js') ?: time()));
$studentDashboardCssUrl = site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()));
$referralsPageTitle = match ($referralsPageMode) {
    'referees' => 'Mes filleuls',
    'history' => 'Historique des gains',
    default => 'Parrainage',
};

$dashboardUser = is_array($user ?? null) ? $user : [];
$dashboardDisplayName = function_exists('dashboard_user_display_name') ? dashboard_user_display_name($dashboardUser, 'Utilisateur') : 'Utilisateur';
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
$dashboardAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($dashboardUser) : '';
$dashboardInitials = function_exists('dashboard_user_initials') ? dashboard_user_initials($dashboardUser, 'ET') : 'ET';
$dashboardSidebarName = $dashboardDisplayName;
if ($dashboardEmail !== '' && strcasecmp($dashboardSidebarName, $dashboardEmail) === 0) {
    $dashboardSidebarName = 'Compte étudiant';
}

$activeEnrollments = max(0, (int) ($activeEnrollments ?? 0));
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$profileCompletionPct = max(0, min(100, (int) ($studentKpi['profile_completion_pct'] ?? 0)));

$referralCodeValue = trim((string) ($referralCode ?? ''));
$referralLinkValue = trim((string) ($referralLink ?? ''));
$referralEarningsValue = max(0, (int) ($referralEarnings ?? 0));
$levelOneValue = max(0, (int) ($earnedByLevel['1'] ?? 0));
$levelTwoValue = max(0, (int) ($earnedByLevel['2'] ?? 0));
$withdrawAvailableValue = max(0, (int) ($referralWithdrawAvailable ?? 0));
$withdrawMinValue = max(0, (int) ($referralMinWithdrawal ?? 0));
$pendingWithdrawalsValue = max(0, (int) ($pendingReferralWithdrawals ?? 0));
$referralCommissionRateValue = max(0.0, (float) ($referralCommissionRate ?? 0.0));
$canRequestReferralWithdraw = $withdrawAvailableValue > 0 && ($withdrawMinValue <= 0 || $withdrawAvailableValue >= $withdrawMinValue);
$formatMoney = static function (int $amount): string {
    return number_format($amount, 0, ',', ' ') . ' F CFA';
};
$formatPercent = static function (float $value): string {
    $formatted = number_format($value, 2, ',', ' ');
    $formatted = rtrim(rtrim($formatted, '0'), ',');

    return ($formatted !== '' ? $formatted : '0') . ' %';
};
$commissionLabel = $formatPercent($referralCommissionRateValue);
$minimumWithdrawalLabel = $formatMoney($withdrawMinValue);
$initialsFromName = static function (string $name, string $fallback = 'ET'): string {
    $name = trim($name);
    if ($name === '') {
        return $fallback;
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return strtoupper($initials !== '' ? $initials : $fallback);
};

$recentReferralsCount = 0;
$currentMonthKey = date('Y-m');
foreach ($referralRows as $row) {
    $lastAt = trim((string) ($row['last_at'] ?? ''));
    if ($lastAt !== '' && date('Y-m', strtotime($lastAt)) === $currentMonthKey) {
        $recentReferralsCount++;
    }
}

$rewardTiersUnlocked = 0;
if ($levelOneValue > 0) {
    $rewardTiersUnlocked++;
}
if ($levelTwoValue > 0) {
    $rewardTiersUnlocked++;
}

$conversionRate = $referralCount > 0 ? min(100, $referralCount * 16) : 0;
$nextMilestone = max(0, 10 - $referralCount);
$shareText = 'Rejoignez-moi sur École Numérique';
$referralLinkShare = $referralLinkValue !== '' ? rawurlencode($referralLinkValue) : '';
$facebookShareUrl = $referralLinkValue !== '' ? 'https://www.facebook.com/sharer/sharer.php?u=' . $referralLinkShare : '';
$twitterShareUrl = $referralLinkValue !== '' ? 'https://twitter.com/intent/tweet?text=' . rawurlencode($shareText) . '&url=' . $referralLinkShare : '';
$whatsappShareUrl = $referralLinkValue !== '' ? 'https://wa.me/?text=' . rawurlencode($shareText . ' : ' . $referralLinkValue) : '';
$linkedinShareUrl = $referralLinkValue !== '' ? 'https://www.linkedin.com/sharing/share-offsite/?url=' . $referralLinkShare : '';

$deriveReferralStatus = static function (array $row): array {
    $count = max(0, (int) ($row['total_count'] ?? 0));
    if ($count >= 3) {
        return ['key' => 'achete', 'label' => 'Acheté', 'tone' => 'active', 'hint' => 'validé'];
    }
    if ($count === 2) {
        return ['key' => 'en-cours', 'label' => 'En cours', 'tone' => 'sky', 'hint' => 'suivi du cours'];
    }

    return ['key' => 'inscrit', 'label' => 'Inscrit', 'tone' => 'pending', 'hint' => 'en attente'];
};
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title><?php echo e($referralsPageTitle); ?> · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e($studentDashboardCssUrl); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($sidebarToggleJsUrl); ?>" defer></script>
  <script src="<?php echo e($studentDashboardJsUrl); ?>" defer></script>
  <script src="<?php echo e($studentReferralsJsUrl); ?>" defer></script>
  <script src="<?php echo e($referralCopyJsUrl); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="student/referrals" data-sidebar-desktop-toggle="0" class="app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">

<div class="student-dashboard-shell relative min-h-screen overflow-hidden">
  <?php $studentNavActive = 'referrals'; ?>
  <?php $studentDashboardShell = true; ?>
  <?php $studentTopTitle = $referralsPageTitle; ?>
  <?php $studentTopSubtitle = 'Espace étudiant'; ?>
  <?php $studentTopSearchUrl = $catalogueUrl; ?>
  <?php $studentTopSearchPlaceholder = 'Rechercher dans le catalogue'; ?>
  <?php $studentTopSearchAriaLabel = 'Rechercher dans le catalogue'; ?>
  <?php $studentTopHideSupportFab = true; ?>
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
    <main class="student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
      <?php require __DIR__ . '/_topbar.php'; ?>

      <div class="student-dashboard-content student-referrals-page p-4 pb-24 space-y-5 animate-fade-up sm:p-6 sm:pb-24 sm:space-y-6 lg:space-y-8">
        <?php if ($isRefereesPage): ?>
          <section class="student-referrals-link-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)]">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <a href="<?php echo e($referralsUrl); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700">
                  <i class="fas fa-arrow-left text-xs"></i>
                  Retour au parrainage
                </a>
                <h2 class="mt-3 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Mes filleuls</h2>
                <p class="mt-1 text-sm text-slate-500"><?php echo e((string) $referralCount); ?> filleul(s) inscrit(s).</p>
              </div>
              <?php if ($referralLinkValue !== ''): ?>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-[0_16px_32px_-20px_rgba(37,99,235,0.8)] transition hover:-translate-y-0.5 hover:bg-blue-700"
                  data-referral-share
                  data-referral-value="<?php echo e($referralLinkValue); ?>"
                  data-referral-copy-success="Lien partagé."
                  data-referral-copy-fallback-success="Lien copié."
                  data-referral-copy-error="Partage impossible."
                >
                  <i class="fas fa-share-alt text-xs"></i>
                  Partager maintenant
                </button>
              <?php endif; ?>
            </div>
          </section>

          <section class="student-referrals-table-card rounded-[24px] border border-slate-200 bg-white shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)]">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h3 class="text-lg font-semibold tracking-tight text-slate-900">Liste des filleuls</h3>
                <p class="mt-1 text-sm text-slate-500">Inscriptions, achats et commissions associées.</p>
              </div>
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <label class="relative block">
                  <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                  <input
                    type="search"
                    placeholder="Rechercher un filleul..."
                    class="student-referrals-input w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-2 focus:ring-blue-100 sm:w-64"
                    data-referrals-search
                  >
                </label>
                <select
                  class="student-referrals-select rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-2 focus:ring-blue-100"
                  data-referrals-filter
                >
                  <option value="all">Tous</option>
                  <option value="achete">Acheté</option>
                  <option value="inscrit">Inscrit</option>
                  <option value="en-cours">En cours</option>
                </select>
              </div>
            </div>

            <?php if ($referralRows === []): ?>
              <div class="p-6 sm:p-8" data-referrals-empty>
                <div class="student-referrals-empty-card rounded-[22px] border border-dashed border-slate-200 bg-slate-50/70 p-6 text-center">
                  <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users text-xl"></i>
                  </div>
                  <h4 class="mt-4 text-lg font-semibold text-slate-900">Aucun filleul pour le moment</h4>
                  <p class="mt-2 text-sm text-slate-500">
                    <?php echo $referralLinkValue !== ''
                        ? 'Partagez votre lien pour obtenir votre premier filleul.'
                        : 'Le lien de parrainage sera disponible dès l’activation du programme sur votre compte.'; ?>
                  </p>
                </div>
              </div>
            <?php else: ?>
              <div class="px-5 pt-4">
                <span class="app-status-pill app-status-pill--sky">
                  <span data-referrals-visible-count><?php echo e((string) $referralCount); ?></span> filleul(s)
                </span>
              </div>
              <div class="overflow-x-auto">
                <table class="student-referrals-table min-w-full divide-y divide-slate-200" data-referrals-table>
                  <thead class="bg-slate-50/80">
                    <tr>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Filleul</th>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Inscription</th>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Statut</th>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Récompense</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($referralRows as $row): ?>
                      <?php
                      $buyerName = trim((string) ($row['buyer_first'] ?? '') . ' ' . (string) ($row['buyer_last'] ?? ''));
                      $buyerName = $buyerName !== '' ? $buyerName : 'Filleul';
                      $buyerInitials = $initialsFromName($buyerName, 'FU');
                      $statusInfo = $deriveReferralStatus($row);
                      $rewardAmount = max(0, (int) ($row['total_amount'] ?? 0));
                      $rewardHint = (string) ($statusInfo['hint'] ?? 'en attente');
                      $registeredAt = trim((string) ($row['referral_created_at'] ?? $row['last_at'] ?? ''));
                      ?>
                      <tr class="transition hover:bg-slate-50/80" data-referral-row data-referral-name="<?php echo e(function_exists('mb_strtolower') ? mb_strtolower($buyerName) : strtolower($buyerName)); ?>" data-referral-status="<?php echo e((string) ($statusInfo['key'] ?? 'inscrit')); ?>">
                        <td class="px-5 py-4">
                          <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-bold text-white shadow-sm">
                              <?php echo e($buyerInitials); ?>
                            </span>
                            <div class="min-w-0">
                              <p class="truncate font-medium text-slate-900"><?php echo e($buyerName); ?></p>
                              <p class="text-xs text-slate-500"><?php echo max(0, (int) ($row['total_count'] ?? 0)); ?> achat(s) validé(s)</p>
                            </div>
                          </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600"><?php echo e($fmtDtShort($registeredAt)); ?></td>
                        <td class="px-5 py-4">
                          <span class="app-status-pill <?php echo e('app-status-pill--' . (string) ($statusInfo['tone'] ?? 'pending')); ?>">
                            <?php echo e((string) ($statusInfo['label'] ?? 'Inscrit')); ?>
                          </span>
                        </td>
                        <td class="px-5 py-4">
                          <div class="flex flex-wrap items-baseline gap-2">
                            <span class="text-sm font-semibold text-emerald-600"><?php echo e($formatMoney($rewardAmount)); ?></span>
                            <span class="text-xs text-slate-500">(<?php echo e($rewardHint); ?>)</span>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="px-5 pb-5 pt-0">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-500 hidden" data-referrals-empty>
                  Aucun résultat ne correspond à votre recherche.
                </div>
              </div>
            <?php endif; ?>
          </section>
          <?php
          $pagination = $referralByUserPagination;
          $basePath = 'student/referrals/filleuls';
          $queryKey = 'page';
          $baseQuery = [];
          require __DIR__ . '/_pagination.php';
          ?>

        <?php elseif ($isHistoryPage): ?>
          <section class="student-referrals-link-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)]">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <a href="<?php echo e($referralsUrl); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700">
                  <i class="fas fa-arrow-left text-xs"></i>
                  Retour au parrainage
                </a>
                <h2 class="mt-3 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Historique des gains</h2>
                <p class="mt-1 text-sm text-slate-500"><?php echo e((string) $referralCommissionCount); ?> commission(s) validée(s).</p>
              </div>
              <span class="app-status-pill app-status-pill--active"><?php echo e($formatMoney($referralEarningsValue)); ?> gagnés</span>
            </div>
          </section>

          <section class="student-referrals-history-card rounded-[24px] border border-slate-200 bg-white shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)]">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h3 class="text-lg font-semibold tracking-tight text-slate-900">Commissions validées</h3>
                <p class="mt-1 text-sm text-slate-500">Achat, filleul, taux et montant gagné.</p>
              </div>
            </div>

            <?php if ($referralCommissionRows === []): ?>
              <div class="p-6 sm:p-8">
                <div class="student-referrals-empty-card rounded-[22px] border border-dashed border-slate-200 bg-slate-50/70 p-6 text-center">
                  <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <i class="fas fa-coins text-xl"></i>
                  </div>
                  <h4 class="mt-4 text-lg font-semibold text-slate-900">Aucun gain enregistré</h4>
                  <p class="mt-2 text-sm text-slate-500">Partagez votre lien pour obtenir votre premier filleul.</p>
                </div>
              </div>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                  <thead class="bg-slate-50/80">
                    <tr>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Date</th>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Filleul</th>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Cours</th>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Taux</th>
                      <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Montant</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <?php foreach ($referralCommissionRows as $row): ?>
                      <?php
                      $buyerName = trim((string) ($row['buyer_first'] ?? '') . ' ' . (string) ($row['buyer_last'] ?? ''));
                      $buyerName = $buyerName !== '' ? $buyerName : 'Filleul';
                      $courseTitle = trim((string) ($row['course_title'] ?? 'Cours'));
                      $rateValue = max(0.0, (float) ($row['rate_percent'] ?? 0.0));
                      $rewardAmount = max(0, (int) ($row['amount_xof'] ?? 0));
                      ?>
                      <tr class="transition hover:bg-slate-50/80">
                        <td class="px-5 py-4 text-sm text-slate-600"><?php echo e($fmtDtShort((string) ($row['created_at'] ?? ''))); ?></td>
                        <td class="px-5 py-4">
                          <p class="font-medium text-slate-900"><?php echo e($buyerName); ?></p>
                          <p class="text-xs text-slate-500">Niveau <?php echo e((string) max(1, (int) ($row['level'] ?? 1))); ?></p>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600"><?php echo e($courseTitle); ?></td>
                        <td class="px-5 py-4 text-sm text-slate-600"><?php echo e($formatPercent($rateValue)); ?></td>
                        <td class="px-5 py-4">
                          <span class="text-sm font-semibold text-emerald-600"><?php echo e($formatMoney($rewardAmount)); ?></span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </section>
          <?php
          $pagination = $referralCommissionPagination;
          $basePath = 'student/referrals/gains';
          $queryKey = 'page';
          $baseQuery = [];
          require __DIR__ . '/_pagination.php';
          ?>

        <?php else: ?>
        <section class="student-dashboard-hero student-referrals-hero-card bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 rounded-[1.5rem] p-4 text-white shadow-glow sm:rounded-2xl sm:p-6">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
              <h2 class="text-xl font-bold sm:text-2xl">Gagnez <?php echo e($commissionLabel); ?> par achat validé.</h2>
              <p class="mt-1 text-sm text-blue-100 sm:text-base">Achat confirmé = gain validé.</p>
            </div>

            <?php if ($referralLinkValue !== ''): ?>
              <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white/70"
                data-referral-share
                data-referral-value="<?php echo e($referralLinkValue); ?>"
                data-referral-copy-success="Lien partagé."
                data-referral-copy-fallback-success="Lien copié."
                data-referral-copy-error="Partage impossible."
              >
                <i class="fas fa-share-alt text-xs"></i>
                <span>Partager maintenant</span>
              </button>
            <?php else: ?>
              <span class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white/15 px-4 py-2 text-sm font-semibold text-blue-50">
                Lien indisponible
              </span>
            <?php endif; ?>
          </div>

          <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-blue-50">
            <span class="rounded-full bg-white/15 px-3 py-1.5">Gain : <?php echo e($commissionLabel); ?></span>
            <span class="rounded-full bg-white/15 px-3 py-1.5">Validation : achat confirmé</span>
            <span class="rounded-full bg-white/15 px-3 py-1.5">Retrait : dès <?php echo e($minimumWithdrawalLabel); ?></span>
          </div>
        </section>

        <section class="student-referrals-kpi-grid grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <article class="student-referrals-stat-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)] transition hover:-translate-y-0.5 hover:shadow-[0_22px_52px_-38px_rgba(15,23,42,0.45)]">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm text-slate-500">Filleuls inscrits</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900"><?php echo e((string) $referralCount); ?></p>
                <p class="mt-2 text-sm text-slate-500">+<?php echo e((string) $recentReferralsCount); ?> ce mois</p>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                <i class="fas fa-user-plus text-lg"></i>
              </div>
            </div>
          </article>

          <article class="student-referrals-stat-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)] transition hover:-translate-y-0.5 hover:shadow-[0_22px_52px_-38px_rgba(15,23,42,0.45)]">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm text-slate-500">Gains totaux</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900"><?php echo e($formatMoney($referralEarningsValue)); ?></p>
                <p class="mt-2 text-sm text-slate-500">Dont <?php echo e($formatMoney($withdrawAvailableValue)); ?> disponibles</p>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                <i class="fas fa-euro-sign text-lg"></i>
              </div>
            </div>
          </article>

          <article class="student-referrals-stat-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)] transition hover:-translate-y-0.5 hover:shadow-[0_22px_52px_-38px_rgba(15,23,42,0.45)]">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm text-slate-500">Récompenses débloquées</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900"><?php echo e((string) $rewardTiersUnlocked); ?></p>
                <p class="mt-2 text-sm text-slate-500"><?php echo $nextMilestone > 0 ? 'Prochain palier à ' . e((string) $nextMilestone) . ' filleul(s)' : 'Palier suivant atteint'; ?></p>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-100 text-purple-600">
                <i class="fas fa-gift text-lg"></i>
              </div>
            </div>
          </article>

          <article class="student-referrals-stat-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)] transition hover:-translate-y-0.5 hover:shadow-[0_22px_52px_-38px_rgba(15,23,42,0.45)]">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm text-slate-500">Taux de conversion</p>
                <p class="mt-1 text-3xl font-bold tracking-tight text-slate-900"><?php echo e((string) $conversionRate); ?>%</p>
                <div class="mt-4 h-2 rounded-full bg-slate-200">
                  <div class="h-2 rounded-full bg-cyan-500" style="width: <?php echo e((string) $conversionRate); ?>%;"></div>
                </div>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-600">
                <i class="fas fa-chart-line text-lg"></i>
              </div>
            </div>
          </article>
        </section>

        <section class="student-referrals-link-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)]">
          <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0 max-w-2xl">
              <h2 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Votre lien de parrainage</h2>
              <p class="mt-1 text-sm text-slate-500 sm:text-base">Commission : <?php echo e($commissionLabel); ?> par achat validé.</p>
              <div class="mt-4 flex flex-wrap gap-3">
                <?php if ($referralLinkValue !== ''): ?>
                  <a href="<?php echo e($facebookShareUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600">
                    <i class="fab fa-facebook-f student-referrals-social-icon student-referrals-social-icon--facebook"></i>
                    Facebook
                  </a>
                  <a href="<?php echo e($twitterShareUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600">
                    <i class="fab fa-twitter student-referrals-social-icon student-referrals-social-icon--twitter"></i>
                    Twitter
                  </a>
                  <a href="<?php echo e($whatsappShareUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600">
                    <i class="fab fa-whatsapp student-referrals-social-icon student-referrals-social-icon--whatsapp"></i>
                    WhatsApp
                  </a>
                  <a href="<?php echo e($linkedinShareUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600">
                    <i class="fab fa-linkedin-in student-referrals-social-icon student-referrals-social-icon--linkedin"></i>
                    LinkedIn
                  </a>
                <?php else: ?>
                  <span class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm text-slate-500">Le partage sera disponible après activation du code.</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="flex w-full flex-col gap-3 xl:max-w-[36rem]">
              <div class="student-referrals-code-row flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" data-referral-copy-wrap>
                <span class="min-w-0 flex-1 truncate font-mono text-sm text-slate-700">
                  <?php echo e($referralLinkValue !== '' ? $referralLinkValue : 'Lien indisponible'); ?>
                </span>
                <?php if ($referralLinkValue !== ''): ?>
                  <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white transition hover:bg-blue-700"
                    data-referral-copy
                    data-referral-value="<?php echo e($referralLinkValue); ?>"
                    data-referral-copy-success="Lien de parrainage copié."
                    data-referral-copy-error="Copie impossible. Utilisez Ctrl+C."
                    aria-label="Copier le lien de parrainage"
                  >
                    <i class="fas fa-copy text-sm"></i>
                  </button>
                <?php endif; ?>
              </div>
              <div class="flex justify-end">
                <?php if ($referralLinkValue !== ''): ?>
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-[0_16px_32px_-20px_rgba(37,99,235,0.8)] transition hover:-translate-y-0.5 hover:bg-blue-700"
                    data-referral-share
                    data-referral-value="<?php echo e($referralLinkValue); ?>"
                    data-referral-copy-success="Lien partagé."
                    data-referral-copy-fallback-success="Lien copié."
                    data-referral-copy-error="Partage impossible."
                  >
                    <i class="fas fa-share-alt text-xs"></i>
                    Partager maintenant
                  </button>
                <?php endif; ?>
              </div>
              <?php if ($referralCodeValue !== ''): ?>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" data-referral-copy-wrap>
                  <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Code actif</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-slate-700"><?php echo e($referralCodeValue); ?></p>
                  </div>
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    data-referral-copy
                    data-referral-value="<?php echo e($referralCodeValue); ?>"
                    data-referral-copy-success="Code de parrainage copié."
                    data-referral-copy-error="Copie impossible. Utilisez Ctrl+C."
                    aria-label="Copier le code de parrainage"
                  >
                    <i class="fas fa-copy text-xs"></i>
                    Copier le code
                  </button>
                  <p class="hidden basis-full text-xs font-medium text-emerald-600" data-referral-copy-status></p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
          <article class="student-referrals-table-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)]">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-lg font-semibold tracking-tight text-slate-900">Mes filleuls</h3>
                <p class="mt-1 text-sm text-slate-500"><?php echo e((string) $referralCount); ?> filleul(s) inscrit(s).</p>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                <i class="fas fa-users text-lg"></i>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
              <a href="<?php echo e($referralRefereesUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="fas fa-list text-xs"></i>
                Voir les filleuls
              </a>
              <?php if ($referralLinkValue !== ''): ?>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600"
                  data-referral-share
                  data-referral-value="<?php echo e($referralLinkValue); ?>"
                  data-referral-copy-success="Lien partagé."
                  data-referral-copy-fallback-success="Lien copié."
                  data-referral-copy-error="Partage impossible."
                >
                  <i class="fas fa-share-alt text-xs"></i>
                  Partager
                </button>
              <?php endif; ?>
            </div>
          </article>

          <article class="student-referrals-history-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)]">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-lg font-semibold tracking-tight text-slate-900">Historique des gains</h3>
                <p class="mt-1 text-sm text-slate-500"><?php echo e((string) $referralCommissionCount); ?> commission(s) validée(s).</p>
              </div>
              <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                <i class="fas fa-coins text-lg"></i>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-3">
              <a href="<?php echo e($referralGainsUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="fas fa-history text-xs"></i>
                Voir l’historique
              </a>
              <span class="app-status-pill app-status-pill--active"><?php echo e($formatMoney($referralEarningsValue)); ?></span>
            </div>
          </article>
        </section>

        <section class="student-referrals-withdraw-card rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_18px_48px_-38px_rgba(15,23,42,0.35)]">
          <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-2xl">
              <h3 class="text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">Retrait des gains</h3>
              <p class="mt-1 text-sm text-slate-500">Paiement cours ou retrait FedaPay.</p>
              <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="student-referrals-mini-card rounded-2xl bg-slate-50 p-4">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Disponible</p>
                  <p class="mt-2 text-base font-bold text-slate-900"><?php echo e($formatMoney($withdrawAvailableValue)); ?></p>
                  <p class="mt-1 text-xs text-slate-500">Montant mobilisable maintenant</p>
                </div>
                <div class="student-referrals-mini-card rounded-2xl bg-slate-50 p-4">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Minimum</p>
                  <p class="mt-2 text-base font-bold text-slate-900"><?php echo e($formatMoney($withdrawMinValue)); ?></p>
                  <p class="mt-1 text-xs text-slate-500">Minimum de retrait</p>
                </div>
                <div class="student-referrals-mini-card rounded-2xl bg-slate-50 p-4">
                  <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">En attente</p>
                  <p class="mt-2 text-base font-bold text-slate-900"><?php echo e($formatMoney($pendingWithdrawalsValue)); ?></p>
                  <p class="mt-1 text-xs text-slate-500">Retraits en validation</p>
                </div>
              </div>
              <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-600">
                Minimum <?php echo e($minimumWithdrawalLabel); ?> · Mobile Money via FedaPay · Validation administrative.
              </div>
            </div>

            <div class="w-full xl:max-w-[31rem]">
              <?php if (!$canRequestReferralWithdraw): ?>
                <div class="student-referrals-empty-card rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 p-5">
                  <p class="text-sm font-semibold text-slate-900">Aucune demande possible pour l’instant</p>
                  <p class="mt-2 text-sm text-slate-500">Minimum requis : <?php echo e($minimumWithdrawalLabel); ?>.</p>
                </div>
              <?php else: ?>
                <form method="post" class="student-referrals-withdraw-form rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                  <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                  <input type="hidden" name="action" value="request_referral_withdrawal">

                  <div class="grid gap-4">
                    <label class="text-sm font-semibold text-slate-700">
                      Montant (F CFA)
                      <input name="amount_xof" type="number" min="0" max="<?php echo e((string) $withdrawAvailableValue); ?>" value="<?php echo e((string) $withdrawAvailableValue); ?>" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-100">
                    </label>

                    <label class="text-sm font-semibold text-slate-700">
                      Méthode
                      <select name="method" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="fedapay">Mobile Money via FedaPay</option>
                      </select>
                    </label>

                    <label class="text-sm font-semibold text-slate-700">
                      Destination
                      <input name="destination" type="text" required placeholder="Numéro ou référence de réception" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 outline-none focus:ring-2 focus:ring-blue-100">
                      <span class="mt-1 block text-xs font-normal text-slate-500">Numéro Mobile Money.</span>
                    </label>

                    <div class="flex justify-end">
                      <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        <i class="fas fa-wallet"></i>
                        Demander le retrait
                      </button>
                    </div>
                  </div>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </section>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <a
    href="#support-chat"
    data-support-url="<?php echo e($supportUrl); ?>"
    class="student-dashboard-support-fab fixed bottom-3 right-3 z-40 inline-flex items-center gap-3 rounded-full bg-blue-600 px-3 py-2.5 text-white shadow-glow transition hover:-translate-y-0.5 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-blue-500 dark:hover:bg-blue-600 sm:bottom-4 sm:right-4 sm:px-4 sm:py-3"
    data-support-fab="1"
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
</div>
</body>
</html>
