<?php
declare(strict_types=1);

$companySimplePage = trim((string) ($companySimplePage ?? 'dashboard'));
$companySimpleTitle = trim((string) ($companySimpleTitle ?? 'Tableau de bord'));
$companySimpleEyebrow = trim((string) ($companySimpleEyebrow ?? 'Espace entreprise'));
$companySimpleSubtitle = trim((string) ($companySimpleSubtitle ?? 'Invitez, achetez des places et suivez les accès.'));
$companySimpleMemberMode = !empty($companySimpleMemberMode);
$companyRecord = is_array($company ?? null) ? $company : [];
$companyUser = is_array($user ?? null) ? $user : [];
$companyName = trim((string) ($companyRecord['name'] ?? 'Entreprise'));
$companyEmail = trim((string) ($companyUser['email'] ?? ''));
$companyDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($companyUser, 'Utilisateur')
    : trim((string) (($companyUser['first_name'] ?? '') . ' ' . ($companyUser['last_name'] ?? '')));
if ($companyDisplayName === '') {
    $companyDisplayName = $companyEmail !== '' ? $companyEmail : 'Utilisateur';
}
$companyInitials = function_exists('dashboard_user_initials') ? dashboard_user_initials($companyUser, 'EN') : 'EN';
$companyAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($companyUser) : '';
$companyUnreadCount = max(0, (int) ($unreadCount ?? 0));
$csrfToken = function_exists('view_csrf_token') ? view_csrf_token() : '';

$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$studentDashboardCssUrl = site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()));
$appTypographyCssUrl = site_url('assets/css/app-typography.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/app-typography.css') ?: time()));

$companyNav = $companySimpleMemberMode ? [
    ['key' => 'dashboard', 'label' => 'Tableau de bord', 'url' => site_url('company/dashboard'), 'icon' => 'home', 'badge' => 0],
    ['key' => 'courses', 'label' => 'Mes cours', 'url' => site_url('student/my_courses'), 'icon' => 'book-open', 'badge' => 0],
    ['key' => 'catalogue', 'label' => 'Catalogue', 'url' => site_url('catalogue'), 'icon' => 'search', 'badge' => 0],
    ['key' => 'support', 'label' => 'Assistance', 'url' => site_url('support?space=company'), 'icon' => 'life-ring', 'badge' => 0],
] : [
    ['key' => 'dashboard', 'label' => 'Tableau de bord', 'url' => site_url('company/dashboard'), 'icon' => 'home', 'badge' => 0],
    ['key' => 'invites', 'label' => 'Invitations', 'url' => site_url('company/invites'), 'icon' => 'envelope', 'badge' => max(0, (int) ($inviteSummary['pending'] ?? 0))],
    ['key' => 'members', 'label' => 'Collaborateurs', 'url' => site_url('company/members'), 'icon' => 'users', 'badge' => max(0, (int) ($memberSummary['active'] ?? 0))],
    ['key' => 'seats', 'label' => 'Formations / Places', 'url' => site_url('company/seats'), 'icon' => 'book-open', 'badge' => max(0, (int) ($seatSummary['seats_available'] ?? 0))],
    ['key' => 'billing', 'label' => 'Factures', 'url' => site_url('company/billing'), 'icon' => 'receipt', 'badge' => 0],
    ['key' => 'catalogue', 'label' => 'Acheter des cours', 'url' => site_url('catalogue'), 'icon' => 'shopping-cart', 'badge' => 0],
];
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title><?php echo e($companySimpleTitle); ?> · Entreprise · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e($studentDashboardCssUrl); ?>">
    <link rel="stylesheet" href="<?php echo e($appTypographyCssUrl); ?>">
    <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="company/<?php echo e($companySimplePage); ?>" data-sidebar-desktop-toggle="0" class="app-role-company student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">

<div class="student-dashboard-shell relative min-h-screen overflow-hidden">
  <aside id="company-sidebar" class="student-dashboard-sidebar js-sidebar fixed -left-full top-0 z-40 flex h-screen w-64 flex-col overflow-y-auto border-r border-gray-200 bg-white shadow-soft transition-[left] duration-200 dark:border-gray-700 dark:bg-gray-800 sm:w-72 md:left-0 md:shadow-none">
    <div class="p-3 sm:p-4 flex items-center gap-2 border-b border-gray-100 dark:border-gray-700">
      <span class="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-xl flex items-center justify-center text-white shadow-md">
        <i class="fas fa-building"></i>
      </span>
      <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white sm:text-xl">École <span class="text-blue-600 dark:text-blue-400">Numérique</span></span>
    </div>

    <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-700">
      <div class="flex items-center gap-3">
        <?php if ($companyAvatarUrl !== ''): ?>
          <img src="<?php echo e($companyAvatarUrl); ?>" alt="Photo de profil" class="w-12 h-12 rounded-full object-cover border-2 border-blue-200 dark:border-blue-500/20">
        <?php else: ?>
          <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center text-white font-bold shadow-md">
            <?php echo e($companyInitials); ?>
          </div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-900 dark:text-white truncate"><?php echo e($companyName); ?></p>
          <p class="text-xs text-gray-500 dark:text-gray-400 truncate"><?php echo e($companyDisplayName); ?></p>
        </div>
      </div>
      <div class="mt-3 flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 rounded-xl p-2">
        <span class="text-xs text-gray-600 dark:text-gray-300"><?php echo $companySimpleMemberMode ? 'Mode collaborateur' : 'Mode administrateur'; ?></span>
        <span class="text-xs font-medium bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-full">V1 simple</span>
      </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto sm:px-4 sm:py-6">
      <?php foreach ($companyNav as $item): ?>
        <?php
        $isActive = (string) ($item['key'] ?? '') === $companySimplePage;
        $navClass = $isActive
            ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 font-medium'
            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700';
        ?>
        <a href="<?php echo e((string) ($item['url'] ?? '#')); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?php echo e($navClass); ?> transition sidebar-icon sm:px-4 sm:py-3">
          <i class="fas fa-<?php echo e((string) ($item['icon'] ?? 'circle')); ?> w-5"></i>
          <span><?php echo e((string) ($item['label'] ?? '')); ?></span>
          <?php if ((int) ($item['badge'] ?? 0) > 0): ?>
            <span class="ml-auto bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo e((string) min(99, (int) $item['badge'])); ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>

      <div class="mt-2 border-t border-gray-100 pt-2 dark:border-gray-700">
        <p class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500 sm:px-4">Compte</p>
        <a href="<?php echo e(site_url('notifications?space=company')); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition sidebar-icon sm:px-4 sm:py-3">
          <i class="fas fa-bell w-5"></i>
          <span>Notifications</span>
          <?php if ($companyUnreadCount > 0): ?>
            <span class="ml-auto bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo e((string) min(99, $companyUnreadCount)); ?></span>
          <?php endif; ?>
        </a>
        <a href="<?php echo e(site_url('profile?space=company')); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition sidebar-icon sm:px-4 sm:py-3">
          <i class="fas fa-user w-5"></i>
          <span>Mon profil</span>
        </a>
        <a href="<?php echo e(site_url('')); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition sidebar-icon sm:px-4 sm:py-3">
          <i class="fas fa-home w-5"></i>
          <span>Retour au site</span>
        </a>
      </div>
    </nav>

    <div class="p-3 sm:p-4 border-t border-gray-100 dark:border-gray-700">
      <button id="theme-toggle-sidebar" data-web-theme-toggle type="button" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition sm:px-4 sm:py-3">
        <i class="fas fa-moon text-base w-5" data-theme-icon-dark aria-hidden="true"></i>
        <i class="fas fa-sun hidden text-base w-5" data-theme-icon-light aria-hidden="true"></i>
        <span class="dark:hidden">Mode sombre</span>
        <span class="hidden dark:inline">Mode clair</span>
      </button>
      <form method="post" action="<?php echo e(site_url('logout')); ?>" class="mt-1">
        <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
        <button type="submit" class="flex w-full items-center gap-3 px-3 py-2.5 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition sm:px-4 sm:py-3">
          <i class="fas fa-sign-out-alt w-5"></i>
          <span>Déconnexion</span>
        </button>
      </form>
    </div>
  </aside>

  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-gray-950/75 opacity-0 pointer-events-none transition lg:hidden"></div>

  <header class="student-dashboard-topbar fixed left-0 top-0 z-50 flex h-16 w-full items-center justify-between border-b border-gray-200 bg-white/80 px-3 backdrop-blur-xl dark:border-gray-700 dark:bg-gray-800/80 sm:h-[4.5rem] sm:px-4 lg:px-6">
    <div class="flex items-center gap-3 min-w-0 sm:gap-4">
      <button type="button" class="js-sidebar-toggle lg:hidden p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" aria-controls="company-sidebar" aria-expanded="false" aria-label="Ouvrir le menu">
        <i class="fas fa-bars"></i>
      </button>
      <div class="min-w-0">
        <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400"><?php echo e($companySimpleEyebrow); ?></p>
        <h1 class="student-dashboard-title mt-0.5 text-lg font-semibold tracking-tight text-gray-900 dark:text-white sm:text-xl"><?php echo e($companySimpleTitle); ?></h1>
      </div>
    </div>
    <div class="flex items-center gap-2 sm:gap-4">
      <a href="<?php echo e(site_url('catalogue')); ?>" class="hidden rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 md:inline-flex">
        Acheter des formations
      </a>
      <button type="button" data-web-theme-toggle class="student-dashboard-theme-toggle inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300 sm:h-11 sm:w-11">
        <i class="fas fa-moon text-base" data-theme-icon-dark aria-hidden="true"></i>
        <i class="fas fa-sun hidden text-base" data-theme-icon-light aria-hidden="true"></i>
        <span class="sr-only">Changer le thème</span>
      </button>
      <a href="<?php echo e(site_url('notifications?space=company')); ?>" class="student-dashboard-notification relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300 sm:h-11 sm:w-11" aria-label="Notifications">
        <i class="far fa-bell"></i>
        <?php if ($companyUnreadCount > 0): ?>
          <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-5 text-white shadow-sm"><?php echo e((string) min(99, $companyUnreadCount)); ?></span>
        <?php endif; ?>
      </a>
    </div>
  </header>

  <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
    <main class="student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
      <div class="student-dashboard-content p-4 pb-24 space-y-5 animate-fade-up sm:p-6 sm:pb-24 sm:space-y-6 lg:space-y-8">
        <div class="student-dashboard-hero bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 rounded-[1.5rem] p-4 text-white shadow-glow sm:rounded-2xl sm:p-6">
          <h2 class="text-xl font-bold sm:text-2xl"><?php echo e($companySimpleTitle); ?></h2>
          <p class="mt-1 text-sm text-blue-100 sm:text-base"><?php echo e($companySimpleSubtitle); ?></p>
        </div>
