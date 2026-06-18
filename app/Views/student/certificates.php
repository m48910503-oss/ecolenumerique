<?php
$dashboardUrl = (string) ($dashboardUrl ?? site_url('student/dashboard'));
$myCoursesUrl = site_url('student/my_courses');
$calendarUrl = site_url('student/meetings');
$certificatesUrl = site_url('student/certificates');
$favoritesUrl = site_url('student/favorites');
$ordersUrl = site_url('student/orders');
$notificationsUrl = site_url('notifications?space=student');
$referralsUrl = site_url('student/affiliation');
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
$studentDashboardJsUrl = site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time()));
$studentDashboardCssUrl = site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()));

$dashboardUser = is_array($user ?? null) ? $user : [];
$dashboardDisplayName = dashboard_user_display_name($dashboardUser, 'Utilisateur');
$dashboardEmail = trim((string) ($dashboardUser['email'] ?? ''));
$dashboardAvatarUrl = dashboard_user_avatar_url($dashboardUser);
$dashboardInitials = dashboard_user_initials($dashboardUser, 'ET');
$dashboardSidebarName = $dashboardDisplayName;
if ($dashboardEmail !== '' && strcasecmp($dashboardSidebarName, $dashboardEmail) === 0) {
    $dashboardSidebarName = 'Compte étudiant';
}

$studentUserId = (int) ($dashboardUser['id'] ?? 0);
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));

$fmtDtShort = is_callable($fmtDtShort ?? null) ? $fmtDtShort : static function (?string $dbValue): string {
    if (!$dbValue) {
        return '';
    }

    $ts = strtotime($dbValue);
    if (!$ts) {
        return (string) $dbValue;
    }

    return date('d/m/Y H:i', $ts);
};

$certificatesList = is_array($certificates ?? null) ? $certificates : [];
$certCountValue = max(0, (int) ($certCount ?? count($certificatesList)));
$certificatesPagination = is_array($pagination ?? null) ? $pagination : ['page' => 1];
$schemaReady = (bool) ($schemaReady ?? false);
$latestCertificate = is_array($certificatesList[0] ?? null) ? $certificatesList[0] : [];
$latestCertificateCourse = trim((string) ($latestCertificate['course_title'] ?? ''));
$latestIssuedAt = $fmtDtShort($latestCertificate['issued_at'] ?? null);
$availableCount = $schemaReady ? $certCountValue : 0;
$isEmptyCertificateState = $schemaReady && $certificatesList === [];
$heroSubtitle = $schemaReady
    ? ($certCountValue > 0
        ? 'Vous avez ' . $certCountValue . ' certificat' . ($certCountValue > 1 ? 's' : '') . ' validé' . ($certCountValue > 1 ? 's' : '') . '.'
        : 'Disponibles après validation d’un cours.')
    : 'Le module des certificats n’est pas encore disponible sur ce compte.';
$heroBadgeText = $schemaReady ? 'Module actif' : 'Configuration requise';
$heroSecondBadgeText = $certCountValue > 0
    ? ($latestCertificateCourse !== ''
        ? 'Dernier : ' . $latestCertificateCourse
        : 'Certificat récent disponible')
    : 'Aucun document validé pour le moment';
$studentWelcomeSummary = $heroSubtitle;
$studentWelcomeTitle = 'Certificats et attestations';
$studentWelcomeActionUrl = $certCountValue > 0 ? $myCoursesUrl : $catalogueUrl;
$studentWelcomeActionLabel = $certCountValue > 0 ? 'Voir mes cours' : 'Explorer le catalogue';
$studentWelcomeActionIcon = $certCountValue > 0 ? 'book-open' : 'search';
$certificateGradients = [
    'from-blue-600 via-cyan-500 to-blue-500',
    'from-cyan-500 via-blue-500 to-sky-500',
    'from-sky-500 via-blue-600 to-cyan-500',
];
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Mes certificats · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e($studentDashboardCssUrl); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e($sidebarToggleJsUrl); ?>" defer></script>
  <script src="<?php echo e($studentDashboardJsUrl); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body
  data-view="student/certificates"
  data-sidebar-desktop-toggle="0"
  data-user-id="<?php echo e((string) $studentUserId); ?>"
  class="app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen"
>
  <div class="student-dashboard-shell relative min-h-screen overflow-hidden">
    <?php $studentNavActive = 'certificates'; ?>
    <?php $studentDashboardShell = true; ?>
    <?php $studentTopTitle = 'Mes certificats'; ?>
    <?php $studentTopSubtitle = 'Validation des acquis'; ?>
    <?php $studentTopSearchUrl = $catalogueUrl; ?>
    <?php $studentTopSearchPlaceholder = 'Rechercher dans le catalogue'; ?>
    <?php $studentTopSearchAriaLabel = 'Rechercher dans le catalogue'; ?>
    <?php $studentTopHideSupportFab = true; ?>
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

    <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
      <main class="student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
        <?php require __DIR__ . '/_topbar.php'; ?>

        <div class="student-dashboard-content p-4 pb-24 space-y-5 animate-fade-up sm:p-6 sm:pb-24 sm:space-y-6 lg:space-y-8">
          <?php require __DIR__ . '/_welcome_hero.php'; ?>

          <?php if ($isEmptyCertificateState): ?>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Obtenus</p>
                  <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">0</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                  <i class="fas fa-award"></i>
                </div>
              </div>
            </article>

            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Disponibilité</p>
                  <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Après validation d’un cours</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-600 dark:bg-cyan-500/15 dark:text-cyan-300">
                  <i class="fas fa-lock-open"></i>
                </div>
              </div>
            </article>
          </div>
          <?php else: ?>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Obtenus</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $certCountValue); ?></p>
                  </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                  <i class="fas fa-award"></i>
                </div>
              </div>
            </article>

            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Disponibles</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $availableCount); ?></p>
                  </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-600 dark:bg-cyan-500/15 dark:text-cyan-300">
                  <i class="fas fa-file-download"></i>
                </div>
              </div>
            </article>

            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">État du module</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($schemaReady ? 'Actif' : 'Indisponible'); ?></p>
                  </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl <?php echo $schemaReady ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300'; ?>">
                  <i class="fas <?php echo $schemaReady ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                </div>
              </div>
            </article>
          </div>
          <?php endif; ?>

          <div class="student-dashboard-panels-grid grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_340px]">
            <section class="student-dashboard-panel rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-6">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                  <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-500">Documents disponibles</p>
                  <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Mes certificats</h3>
                </div>
                <?php if ($schemaReady && !$isEmptyCertificateState): ?>
                  <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">Module actif</span>
                <?php elseif (!$schemaReady): ?>
                  <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Configuration requise</span>
                <?php endif; ?>
              </div>

              <?php if (!$schemaReady): ?>
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
                  Le module des certificats n’est pas encore disponible. Appliquez la migration des certificats puis rechargez cette page.
                </div>
              <?php elseif ($certificatesList === []): ?>
                <div class="mt-5 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900/40">
                  <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                    <svg
                      viewBox="0 0 24 24"
                      class="h-7 w-7 shrink-0"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.95"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      aria-hidden="true"
                    >
                      <path d="M7 3h7l5 5v13H7z"></path>
                      <path d="M14 3v5h5"></path>
                      <circle cx="12" cy="13.5" r="2.3"></circle>
                      <path d="M11.2 15.4 10.6 19l1.8-1 1.8 1-.6-3.6"></path>
                    </svg>
                  </div>
                  <h4 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Aucun certificat disponible</h4>
                  <div class="mt-6">
                    <a href="<?php echo e($myCoursesUrl); ?>" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 px-6 py-3 text-base font-semibold text-white shadow-glow transition hover:-translate-y-0.5 hover:from-blue-700 hover:via-blue-600 hover:to-cyan-600">
                      <i class="fas fa-book-open text-xs"></i>
                      Continuer mes cours
                    </a>
                  </div>
                  <a href="<?php echo e($dashboardUrl); ?>" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-300">
                    <i class="fas fa-chart-line text-xs"></i>
                    Retour au tableau de bord
                  </a>
                </div>
              <?php else: ?>
                <div class="student-dashboard-certificate-grid mt-5 space-y-4">
                  <?php foreach ($certificatesList as $index => $cert): ?>
                    <?php
                      $certUrl = site_url('certificate?code=' . urlencode((string) ($cert['code'] ?? '')));
                      $courseUrl = !empty($cert['course_slug'])
                          ? site_url('course/watch?slug=' . urlencode((string) $cert['course_slug']))
                          : site_url('course/watch?id=' . (int) ($cert['course_id'] ?? 0));
                      $issued = $fmtDtShort($cert['issued_at'] ?? null);
                      $score = isset($cert['score']) && $cert['score'] !== null ? (int) $cert['score'] : null;
                      $gradientClass = $certificateGradients[$index % count($certificateGradients)];
                      $codeLabel = trim((string) ($cert['code'] ?? ''));
                      $courseTitle = trim((string) ($cert['course_title'] ?? 'Cours'));
                      if ($courseTitle === '') {
                          $courseTitle = 'Cours';
                      }
                    ?>
                    <article class="student-dashboard-certificate-card overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-card dark:border-gray-700 dark:bg-gray-800">
                      <div class="h-1.5 bg-gradient-to-r <?php echo e($gradientClass); ?>"></div>
                      <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                          <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-500">Certificat validé</p>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">Disponible</span>
                          </div>
                          <h4 class="mt-2 text-lg font-bold text-gray-900 dark:text-white"><?php echo e($courseTitle); ?></h4>
                          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Code <?php echo e($codeLabel !== '' ? $codeLabel : '—'); ?>
                            <?php if ($issued !== ''): ?>
                              · délivré le <?php echo e($issued); ?>
                            <?php endif; ?>
                            <?php if ($score !== null): ?>
                              · score <?php echo e((string) $score); ?>/100
                            <?php endif; ?>
                          </p>
                          <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
                            <?php if ($latestIssuedAt !== ''): ?>
                              <span class="flex items-center gap-1.5">
                                <i class="far fa-calendar-check text-blue-500"></i>
                                <?php echo e($issued !== '' ? $issued : $latestIssuedAt); ?>
                              </span>
                            <?php endif; ?>
                            <?php if ($latestCertificateCourse !== '' && $index === 0): ?>
                              <span class="flex items-center gap-1.5">
                                <i class="fas fa-star text-cyan-500"></i>
                                Dernier document ajouté
                              </span>
                            <?php endif; ?>
                          </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 lg:flex-col lg:items-stretch">
                          <a
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300"
                            href="<?php echo e($courseUrl); ?>"
                          >
                            <i class="fas fa-book-open text-xs"></i>
                            Revoir le cours
                          </a>
                          <a
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                            href="<?php echo e($certUrl); ?>"
                            target="_blank"
                            rel="noopener"
                          >
                            <i class="fas fa-external-link-alt text-xs"></i>
                            Ouvrir le certificat
                          </a>
                        </div>
                      </div>
                    </article>
                  <?php endforeach; ?>
                </div>

                <?php
                $pagination = $certificatesPagination;
                $basePath = 'student/certificates';
                $queryKey = 'page';
                $baseQuery = [];
                require __DIR__ . '/_pagination.php';
                ?>
              <?php endif; ?>
            </section>

            <aside class="student-dashboard-panel rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-6">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-500">Comment l’obtenir</p>
                <h3 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">3 étapes pour débloquer un certificat</h3>
              </div>

              <div class="mt-5 space-y-3">
                <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                  <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 font-bold dark:bg-blue-500/15 dark:text-blue-300">1</span>
                    <div class="min-w-0">
                      <h4 class="font-semibold text-gray-900 dark:text-white">Terminer le cours</h4>
                    </div>
                  </div>
                </article>
                <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                  <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-600 font-bold dark:bg-cyan-500/15 dark:text-cyan-300">2</span>
                    <div class="min-w-0">
                      <h4 class="font-semibold text-gray-900 dark:text-white">Réussir l’évaluation</h4>
                    </div>
                  </div>
                </article>
                <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                  <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 font-bold dark:bg-emerald-500/15 dark:text-emerald-300">3</span>
                    <div class="min-w-0">
                      <h4 class="font-semibold text-gray-900 dark:text-white">Télécharger le certificat</h4>
                    </div>
                  </div>
                </article>
              </div>
            </aside>
          </div>
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
      <span class="block text-xs text-blue-100">Réponse rapide</span>
    </span>
  </a>
</body>
</html>
