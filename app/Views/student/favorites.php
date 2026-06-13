<?php
$dashboardUrl = (string) ($dashboardUrl ?? site_url('student/dashboard'));
$myCoursesUrl = site_url('student/my_courses');
$calendarUrl = site_url('student/meetings');
$certificatesUrl = site_url('student/certificates');
$favoritesUrl = site_url('student/favorites');
$favoritesCountValue = (int) ($favoritesCount ?? 0);
$favoritesPagination = is_array($pagination ?? null) ? $pagination : ['page' => 1];
$favoritesList = is_array($favorites ?? null) ? $favorites : [];
$purgedCountValue = max(0, (int) ($purgedCount ?? 0));
$favoritesListCount = max(0, count($favoritesList));
$schemaReady = (bool) ($schemaReady ?? false);

$favoritesHeroSubtitle = $schemaReady
    ? ($favoritesListCount > 0
        ? 'Vous avez ' . $favoritesListCount . ' cours enregistré' . ($favoritesListCount > 1 ? 's' : '') . ' pour comparer les prix, revoir le programme et décider de votre prochain achat.'
        : 'Ajoutez des cours depuis le catalogue pour les retrouver plus tard.')
    : 'Le module des favoris n’est pas encore disponible sur ce compte.';

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
$logoutUrl = site_url('logout');
$logoutCsrf = function_exists('view_csrf_token') ? view_csrf_token() : '';
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$sidebarToggleJsUrl = site_url('assets/js/sidebar_toggle.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/sidebar_toggle.js') ?: time()));
$studentDashboardJsUrl = site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time()));
$studentDashboardCssUrl = site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()));
$catalogueUrl = site_url('catalogue');
$supportUrl = site_url('support?space=student');
$notificationsUrl = site_url('notifications?space=student');
$settingsUrl = site_url('profile?space=student');
$profileUrl = site_url('profile?space=student');
$homeUrl = site_url('');
$messagesUrl = site_url('support?space=student');
$ordersUrl = site_url('student/orders');
$referralsUrl = site_url('student/affiliation');

$dashboardUrl = $dashboardUrl !== '' ? $dashboardUrl : site_url('student/dashboard');
$favoritesSyncBadge = $schemaReady ? 'Synchronisation auto' : 'Configuration requise';
$favoritesDecisionBadge = $favoritesListCount > 1
    ? 'Prêts à comparer'
    : ($favoritesListCount === 1 ? '1 choix à finaliser' : 'Ajouts en attente');
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Mes favoris · École Numérique</title>
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
  data-view="student/favorites"
  data-sidebar-desktop-toggle="0"
  data-user-id="<?php echo e((string) $studentUserId); ?>"
  class="app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen"
>
  <div class="student-dashboard-shell relative min-h-screen overflow-hidden">
    <?php $studentNavActive = 'favorites'; ?>
    <?php $studentDashboardShell = true; ?>
    <?php $studentTopTitle = 'Mes favoris'; ?>
    <?php $studentTopSubtitle = 'Sélection personnelle'; ?>
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
          <section class="student-dashboard-hero rounded-[1.5rem] bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 p-4 text-white shadow-glow sm:rounded-2xl sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
              <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-100/80">Sélection personnelle</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Cours enregistrés</h2>
                <p class="mt-2 text-sm text-blue-100 sm:text-base"><?php echo e($favoritesHeroSubtitle); ?></p>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
                  <i class="fas fa-heart text-[10px]"></i>
                  <?php echo e((string) $favoritesListCount); ?> favori<?php echo $favoritesListCount > 1 ? 's' : ''; ?>
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
                  <i class="fas fa-sync-alt text-[10px]"></i>
                  <?php echo e($favoritesSyncBadge); ?>
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20">
                  <i class="fas fa-filter text-[10px]"></i>
                  <?php echo e($favoritesDecisionBadge); ?>
                </span>
              </div>
            </div>
          </section>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Favoris</p>
                  <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $favoritesCountValue); ?></p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                  <i class="fas fa-heart"></i>
                </div>
              </div>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Cours enregistrés pour plus tard</p>
            </article>

            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Nettoyage auto</p>
                  <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $purgedCountValue); ?></p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                  <i class="fas fa-sync-alt"></i>
                </div>
              </div>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Favoris déjà achetés retirés automatiquement</p>
            </article>

            <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Synchronisation</p>
                  <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($schemaReady ? 'Auto' : 'Requise'); ?></p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl <?php echo $schemaReady ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300'; ?>">
                  <i class="fas <?php echo $schemaReady ? 'fa-sync-alt' : 'fa-exclamation-triangle'; ?>"></i>
                </div>
              </div>
              <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e($schemaReady ? 'Vos favoris sont synchronisés automatiquement.' : 'Activez la migration des favoris pour afficher la liste.'); ?></p>
            </article>
          </div>

          <?php if (!$schemaReady): ?>
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
              Le module des favoris n’est pas encore disponible. Appliquez la migration <code>schema_v35.sql</code>, puis rechargez cette page.
            </section>
          <?php else: ?>
            <?php if ($success !== ''): ?>
              <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">
                <?php echo e($success); ?>
              </section>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
              <section class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
                <?php foreach ((array) $errors as $error): ?>
                  <p>- <?php echo e((string) $error); ?></p>
                <?php endforeach; ?>
              </section>
            <?php endif; ?>

            <?php if ($purgedCountValue > 0): ?>
              <section class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-200">
                <?php echo e((string) $purgedCountValue); ?> favori(s) déjà achetés ont été retirés automatiquement.
              </section>
            <?php endif; ?>

            <?php if ($favoritesList === []): ?>
              <section class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900/40">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                  <i class="fas fa-heart text-2xl"></i>
                </div>
                <h4 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Liste vide</h4>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Ajoutez des cours depuis le catalogue.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                  <a href="<?php echo e(site_url('catalogue')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-blue-700 hover:to-cyan-600">
                    <i class="fas fa-search text-xs"></i>
                    Explorer le catalogue
                  </a>
                  <a href="<?php echo e(site_url('student/orders')); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-600 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-300">
                    <i class="fas fa-receipt text-xs"></i>
                    Voir mes achats
                  </a>
                </div>
              </section>
            <?php else: ?>
              <div class="student-favorites-grid mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                <?php foreach ($favoritesList as $row): ?>
                  <?php
                    $courseUrl = trim((string) ($row['course_url'] ?? ''));
                    if ($courseUrl === '') {
                        $courseUrl = site_url('catalogue');
                    }
                    $checkoutUrl = trim((string) ($row['checkout_url'] ?? ''));
                    if ($checkoutUrl === '') {
                        $checkoutUrl = site_url('checkout?course=' . (int) ($row['id'] ?? 0));
                    }
                    $coverUrl = trim((string) ($row['cover_url'] ?? ''));
                    $teacherLabel = trim((string) ($row['teacher'] ?? 'Enseignant'));
                    $titleLabel = trim((string) ($row['title'] ?? 'Cours'));
                    $categoryLabel = trim((string) ($row['category'] ?? 'Autre'));
                    $levelLabel = trim((string) ($row['level_label'] ?? 'Niveau'));
                    $priceLabel = number_format((int) ($row['price_xof'] ?? 0), 0, ',', ' ') . ' F CFA';
                    $basePriceLabel = number_format((int) ($row['base_price_xof'] ?? 0), 0, ',', ' ') . ' F CFA';
                    $promoActive = !empty($row['promo_active']);
                    $currentPriceXof = max(0, (int) ($row['price_xof'] ?? 0));
                    $coverAlt = trim((string) ($row['cover_alt'] ?? $titleLabel));
                    $buyLabel = $currentPriceXof > 0 ? 'Acheter' : 'Obtenir';
                  ?>
                  <article class="student-card student-favorite-card min-w-0 overflow-hidden !p-0">
                    <div class="relative h-28 sm:h-32 lg:h-36">
                      <?php if ($coverUrl !== ''): ?>
                        <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" class="h-full w-full object-cover">
                      <?php else: ?>
                        <div class="flex h-full w-full items-center justify-center text-sm font-semibold text-white">
                          <?php echo e($titleLabel); ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="space-y-2 p-2.5 sm:p-3">
                      <div>
                        <p class="student-card__eyebrow"><?php echo e($teacherLabel); ?></p>
                        <h4 class="student-card__title text-[0.9rem]"><?php echo e($titleLabel); ?></h4>
                        <p class="student-card__meta"><?php echo e($categoryLabel); ?> · <?php echo e($levelLabel); ?></p>
                      </div>

                      <div class="flex flex-wrap items-end gap-2">
                        <div>
                          <p class="text-[0.9rem] font-semibold text-slate-900 dark:text-slate-100"><?php echo e($priceLabel); ?></p>
                          <?php if ($promoActive): ?>
                            <p class="student-card__meta line-through"><?php echo e($basePriceLabel); ?></p>
                          <?php endif; ?>
                        </div>
                      </div>

                      <div class="student-card__footer student-favorite-card__footer">
                        <div class="student-favorite-card__actions">
                          <a class="student-favorite-action student-favorite-action--buy inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-[0.74rem] font-semibold sm:text-xs" href="<?php echo e($checkoutUrl); ?>" aria-label="<?php echo e($buyLabel); ?> ce cours">
                            <span class="app-icon" aria-hidden="true"><i class="fas fa-shopping-cart"></i></span>
                            <span><?php echo e($buyLabel); ?></span>
                          </a>
                          <div class="student-favorite-card__actions-secondary">
                            <a class="student-favorite-action student-favorite-action--view app-segmented-link inline-flex items-center gap-2 rounded-xl px-2.5 py-2 text-[0.72rem] font-semibold sm:px-3 sm:text-xs" href="<?php echo e($courseUrl); ?>" aria-label="Voir la fiche du cours">
                              <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('external-link')); ?>"></i></span>
                              <span>Voir la fiche</span>
                            </a>
                            <form method="post" class="student-favorite-card__remove-form">
                              <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                              <input type="hidden" name="action" value="remove_favorite">
                              <input type="hidden" name="course_id" value="<?php echo e((string) (int) ($row['id'] ?? 0)); ?>">
                              <button class="student-favorite-action student-favorite-action--remove app-btn-soft inline-flex items-center gap-2 rounded-xl px-2.5 py-2 text-[0.72rem] font-semibold transition sm:px-3 sm:text-xs" type="submit" aria-label="Retirer ce favori">
                                <span class="app-icon" aria-hidden="true"><i class="fas fa-times"></i></span>
                                <span>Retirer</span>
                              </button>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>

              <?php
              $pagination = $favoritesPagination;
              $basePath = 'student/favorites';
              $queryKey = 'page';
              $baseQuery = [];
              require __DIR__ . '/_pagination.php';
              ?>
            <?php endif; ?>
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
      <span class="block text-xs text-blue-100">Réponse rapide</span>
    </span>
  </a>
</body>
</html>
