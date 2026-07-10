<?php
$profileShellRole = 'student';
$profileIsAdmin = !empty($isAdminProfile);
if (!$profileIsAdmin && is_array($roles ?? null)) {
    $profileIsAdmin = in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true) || in_array('support', $roles, true);
}
$profileDashboardSpace = strtolower(trim((string) ($dashboardSpace ?? '')));
if ($profileDashboardSpace === 'company') {
    $profileShellRole = 'company';
} elseif ($profileIsAdmin && ($profileDashboardSpace === 'admin' || ($profileDashboardSpace === '' && !$isTeacher && !$isStudent))) {
    $profileShellRole = 'admin';
} elseif ($profileDashboardSpace === 'teacher' || ($profileDashboardSpace === '' && $isTeacher && !$isStudent)) {
    $profileShellRole = 'teacher';
} elseif ($profileDashboardSpace === 'student' || ($profileDashboardSpace === '' && $isStudent)) {
    $profileShellRole = 'student';
}

$profileHtmlDataViewAttr = match ($profileShellRole) {
    'teacher' => ' data-view="teacher/dashboard"',
    'admin' => ' data-view="admin/profile"',
    'company' => ' data-view="company/profile"',
    default => '',
};
$profileBodyDataViewAttr = match ($profileShellRole) {
    'teacher' => 'data-view="teacher/dashboard"',
    'admin' => 'data-view="admin/profile" data-sidebar-desktop-toggle="1"',
    'company' => 'data-view="company/profile" data-sidebar-desktop-toggle="0"',
    default => 'data-view="student/dashboard" data-sidebar-desktop-toggle="0"',
};
$profileBodyClass = match ($profileShellRole) {
    'admin' => 'app-view app-role-admin min-h-screen overflow-hidden bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100',
    'teacher' => 'app-role-teacher teacher-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen',
    'company' => 'app-role-company student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen',
    default => 'app-role-student student-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen',
};
$profileShellCssUrl = match ($profileShellRole) {
    'admin' => site_url('assets/css/admin-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/admin-dashboard.css') ?: time())),
    'teacher' => site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time())),
    'company' => site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time())),
    default => site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time())),
};
$profileShellJsUrl = match ($profileShellRole) {
    'admin' => site_url('assets/js/admin_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/admin_dashboard.js') ?: time())),
    'teacher' => site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time())),
    'company' => site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time())),
    default => site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time())),
};
$profileTypographyCssUrl = site_url('assets/css/app-typography.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/app-typography.css') ?: time()));
$profileLayoutClass = $profileShellRole === 'teacher'
    ? 'teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row'
    : 'student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72';
$profileMainClass = $profileShellRole === 'teacher'
    ? 'teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900'
    : 'student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900';
$profileContentClass = match ($profileShellRole) {
    'admin' => 'profile-modern-page profile-modern-page--admin space-y-5 animate-fade-up',
    'teacher' => 'profile-modern-page p-4 pb-36 space-y-8 animate-fade-up sm:p-6 sm:pb-40',
    'company' => 'profile-modern-page profile-modern-page--company student-dashboard-content p-4 pb-36 space-y-5 animate-fade-up sm:p-6 sm:pb-40 sm:space-y-6 lg:space-y-8',
    default => 'profile-modern-page student-dashboard-content p-4 pb-36 space-y-5 animate-fade-up sm:p-6 sm:pb-40 sm:space-y-6 lg:space-y-8',
};
$profileTopTitle = 'Mon profil';
$profileTopSubtitle = match ($profileShellRole) {
    'admin' => 'Espace administrateur',
    'teacher' => 'Espace enseignant',
    'company' => 'Espace entreprise',
    default => 'Espace étudiant',
};
$profileBackdropClass = $profileShellRole === 'teacher' || $profileShellRole === 'admin'
    ? 'js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden'
    : 'js-sidebar-backdrop fixed inset-0 z-30 bg-gray-950/75 opacity-0 pointer-events-none transition lg:hidden';
$profileAvatarInitials = trim((string) ($initials ?? ''));
if ($profileAvatarInitials === '') {
    $profileAvatarInitials = function_exists('dashboard_user_initials') ? dashboard_user_initials($user ?? [], 'EN') : 'EN';
}
$profileAvatarHasImage = trim((string) ($profileAvatarUrl ?? '')) !== '';
$profilePostedAction = strtoupper((string) ($_POST['action'] ?? ''));
$profileInitialTab = in_array($profilePostedAction, ['PASSWORD', 'DELETE_ACCOUNT'], true) ? 'security' : 'infos';
$deleteAccountPanelOpen = $profilePostedAction === 'DELETE_ACCOUNT';
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light"<?php echo $profileHtmlDataViewAttr; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - &Eacute;cole Num&eacute;rique</title>
    <meta name="app-global-assets" content="off">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link rel="stylesheet" href="<?php echo e($profileShellCssUrl); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/profile_modern.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/profile_modern.css') ?: time()))); ?>">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
    <?php if ($profileShellRole === 'student'): ?>
        <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <?php endif; ?>
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e($profileShellJsUrl); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/signature_image.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/signature_image.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/profile_form.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/profile_form.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/togglePassword.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/profile_modern.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/profile_modern.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body <?php echo $profileBodyDataViewAttr; ?> class="<?php echo e($profileBodyClass); ?>">
    <?php if ($profileShellRole === 'admin'): ?>
        <div class="admin-shell-backdrop">
            <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
            <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
        </div>
        <div class="admin-shell-root relative z-10 flex h-screen overflow-hidden">
            <?php
            $adminRoles = is_array($roles ?? null) ? $roles : [];
            $isGeneralAdmin = !empty($isGeneralAdmin) || in_array('admin', $adminRoles, true);
            $adminPermissions = is_array($adminPermissions ?? null) ? $adminPermissions : [];
            $hasAdminAccess = array_key_exists('hasAdminAccess', get_defined_vars())
                ? (bool) $hasAdminAccess
                : ($isGeneralAdmin || in_array('admin_secondary', $adminRoles, true));
            $adminNavActive = 'profile';
            $adminTopTitle = $profileTopTitle;
            $adminTopSubtitle = $profileTopSubtitle;
            $adminTopBadge = $profileVerifiedLabel;
            $adminTopBadgeTone = $emailVerifiedAt !== '' ? 'done' : 'urgent';
            $adminTopSearchAction = site_url('admin/courses');
            $adminTopSearchPlaceholder = 'Rechercher un utilisateur, un cours...';
            require __DIR__ . '/../admin/_sidebar.php';
            ?>
            <div class="<?php echo e($profileBackdropClass); ?>"></div>
            <div class="admin-shell-main flex min-w-0 flex-1 flex-col min-h-0 overflow-y-auto overflow-x-hidden overscroll-contain" data-scroll-top-root="1">
                <?php require __DIR__ . '/../admin/_topbar.php'; ?>
                <main class="admin-shell-content admin-profile-content px-4 py-5 sm:px-6">
                    <div class="<?php echo e($profileContentClass); ?>" data-profile-initial-tab="<?php echo e($profileInitialTab); ?>">
    <?php elseif ($profileShellRole === 'student'): ?>
        <div class="student-dashboard-shell relative min-h-screen overflow-hidden">
            <?php
            $studentDashboardShell = true;
            $studentNavActive = 'profile';
            $studentTopTitle = $profileTopTitle;
            $studentTopSubtitle = $profileTopSubtitle;
            $studentTopShowSearch = true;
            $studentTopHideSupportFab = true;
            $catalogueUrl = site_url('catalogue');
            $messagesUrl = site_url('support?space=student');
            $studentTopSearchValue = '';
            $studentTopSearchUrl = $catalogueUrl;
            $studentTopSearchPlaceholder = 'Rechercher...';
            $studentTopSearchAriaLabel = 'Rechercher...';
            $showThemeToggle = true;
            require __DIR__ . '/../student/_sidebar.php';
            ?>
            <div class="<?php echo e($profileBackdropClass); ?>"></div>
            <?php require __DIR__ . '/../student/_topbar.php'; ?>
            <div class="<?php echo e($profileLayoutClass); ?>">
                <main class="<?php echo e($profileMainClass); ?>" data-scroll-top-root="1">
                    <div class="<?php echo e($profileContentClass); ?>" data-profile-initial-tab="<?php echo e($profileInitialTab); ?>">
    <?php else: ?>
        <div class="<?php echo e($profileLayoutClass); ?>">
            <?php
            $teacherDashboardShell = true;
            $teacherNavActive = 'profile';
            $teacherTopTitle = $profileTopTitle;
            $teacherTopSubtitle = $profileTopSubtitle;
            require __DIR__ . '/../teacher/_sidebar.php';
            ?>
            <div class="<?php echo e($profileBackdropClass); ?>"></div>
            <main class="<?php echo e($profileMainClass); ?>" data-scroll-top-root="1">
                <?php require __DIR__ . '/../teacher/_topbar.php'; ?>
                <div class="<?php echo e($profileContentClass); ?>" data-profile-initial-tab="<?php echo e($profileInitialTab); ?>">
    <?php endif; ?>
                <section class="<?php echo e($profileCardClass); ?> profile-modern-hero">
                    <div class="profile-modern-hero__inner">
                        <div class="profile-modern-hero__identity">
                            <div class="relative">
                                <?php if ($profileAvatarHasImage): ?>
                                    <img src="<?php echo e($profileAvatarUrl); ?>" class="profile-modern-hero__avatar h-20 w-20 rounded-full object-cover border-4 border-primary-200 dark:border-primary-800" alt="Photo de profil">
                                <?php else: ?>
                                    <div class="profile-modern-hero__avatar flex h-20 w-20 items-center justify-center rounded-full border-4 border-primary-200 bg-gradient-to-br from-primary-600 to-accent-500 text-xl font-bold text-white shadow-md dark:border-primary-800">
                                        <?php echo e($profileAvatarInitials); ?>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="profile-modern-hero__camera absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-white transition hover:bg-primary-700" title="Modifier la photo" aria-label="Modifier la photo" data-upload-trigger="avatar_file">
                                    <i class="fas fa-camera text-xs"></i>
                                </button>
                            </div>
                            <div class="profile-modern-hero__copy min-w-0 flex-1 text-center sm:text-left">
                                <p class="profile-modern-kicker"><?php echo e($profileSpaceLabel); ?></p>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($displayName); ?></h2>
                                <?php if ($profileMemberSinceLabel !== ''): ?>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Membre depuis <?php echo e($profileMemberSinceLabel); ?></p>
                                <?php endif; ?>
                                <p class="profile-modern-intro mt-3 text-sm text-gray-500 dark:text-gray-300"><?php echo e($profileIntro); ?></p>
                                <div class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start">
                                    <?php foreach ($roleChips as $chip): ?>
                                        <span class="<?php echo e((string) ($chip['class'] ?? 'profile-modern-chip profile-modern-chip--slate')); ?>">
                                            <i class="fas <?php echo e((string) ($chip['icon'] ?? 'fa-user')); ?>" aria-hidden="true"></i>
                                            <?php echo e((string) ($chip['label'] ?? 'Profil')); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="profile-modern-tabs flex gap-2 border-b border-gray-200 dark:border-gray-700" role="tablist" aria-label="Sections du profil">
                    <button type="button" id="profile-tab-infos" role="tab" aria-selected="true" aria-controls="tab-infos" class="profile-tab tab-active border-b-2 border-primary-600 pb-3 text-sm font-medium text-primary-600 transition dark:border-primary-400 dark:text-primary-400" data-tab="infos"><i class="fas fa-id-card" aria-hidden="true"></i> Informations personnelles</button>
                    <button type="button" id="profile-tab-preferences" role="tab" aria-selected="false" aria-controls="tab-preferences" class="profile-tab border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" data-tab="preferences"><i class="fas fa-sliders-h" aria-hidden="true"></i> Préférences</button>
                    <button type="button" id="profile-tab-security" role="tab" aria-selected="false" aria-controls="tab-security" class="profile-tab border-b-2 border-transparent pb-3 text-sm font-medium text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white" data-tab="security"><i class="fas fa-shield-alt" aria-hidden="true"></i> Sécurité</button>
                </div>

                <?php if ($profileMessage !== ''): ?>
                    <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200" role="status" aria-live="polite">
                        <?php echo e($profileMessage); ?>
                    </section>
                <?php endif; ?>

                <?php if ($profileErrors !== []): ?>
                    <section class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200" role="alert">
                        <ul class="space-y-1">
                            <?php foreach ($profileErrors as $error): ?>
                                <li>- <?php echo e((string) $error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <form method="post" action="<?php echo e($formAction); ?>" enctype="multipart/form-data" data-profile-form class="space-y-6">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <input type="hidden" name="action" value="profile">
                    <input type="hidden" name="notification_preferences_present" value="1">

                    <div id="tab-infos" class="tab-panel space-y-6" role="tabpanel" aria-labelledby="profile-tab-infos">
                        <section class="<?php echo e($profileCardClass); ?>" data-upload-dropzone="avatar_file">
                            <div class="flex flex-col gap-6 xl:flex-row">
                                <div class="xl:w-64">
                                    <p class="text-[11px] uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">Photo</p>
                                    <h3 class="profile-section-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><i class="fas fa-camera" aria-hidden="true"></i> Avatar</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Formats JPG, PNG ou WebP (<?php echo e((string) $avatarMaxMb); ?> Mo max).</p>
                                    <?php if ($needsAvatar): ?>
                                        <span class="mt-3 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Recommand&eacute;</span>
                                    <?php endif; ?>
                                    <div class="mt-4 flex items-start gap-4">
                                        <div class="relative">
                                            <img id="avatar_preview" src="<?php echo e($profileAvatarHasImage ? $profileAvatarUrl : ''); ?>" alt="Photo de profil" class="h-24 w-24 rounded-full object-cover border-4 border-primary-200 shadow-sm dark:border-primary-800<?php echo $profileAvatarHasImage ? '' : ' hidden'; ?>">
                                            <div id="avatar_placeholder" class="<?php echo $profileAvatarHasImage ? 'hidden ' : ''; ?>flex h-24 w-24 items-center justify-center rounded-full border-4 border-primary-200 bg-gradient-to-br from-primary-600 to-accent-500 text-2xl font-bold text-white shadow-sm dark:border-primary-800">
                                                <?php echo e($profileAvatarInitials); ?>
                                            </div>
                                            <button type="button" class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-white transition hover:bg-primary-700" title="Choisir un fichier" aria-label="Choisir un fichier" data-upload-trigger="avatar_file">
                                                <i class="fas fa-camera text-xs"></i>
                                            </button>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Nouvelle photo</p>
                                            <p id="avatar_filename" class="text-xs text-gray-500 dark:text-gray-400">Aucun nouveau fichier choisi</p>
                                            <p id="avatar_feedback" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                            <p data-upload-state="avatar_file" class="text-[11px] text-gray-500 dark:text-gray-400">État: <?php echo $avatarSrc !== '' ? 'fichier actuel charg&eacute;.' : 'aucune s&eacute;lection.'; ?></p>
                                        </div>
                                    </div>
                                    <div class="mt-4 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-4 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                                        <p><i class="fas fa-cloud-upload-alt mr-1.5 text-primary-500" aria-hidden="true"></i>Glissez-d&eacute;posez l'image ou utilisez le bouton photo.</p>
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <span>Max <?php echo e((string) $avatarMaxMb); ?> Mo</span>
                                        </div>
                                        <input class="sr-only" type="file" id="avatar_file" name="avatar_file" accept=".jpg,.jpeg,.png,.webp" data-max-mb="<?php echo e((string) $avatarMaxMb); ?>" data-upload-feedback="avatar_feedback" data-upload-name="avatar_filename" data-upload-preview="avatar_preview" data-upload-placeholder="avatar_placeholder">
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <div class="grid gap-5 md:grid-cols-2">
                                        <label class="<?php echo e($profileLabelClass); ?>" for="first_name">
                                            <span class="profile-field-label"><i class="fas fa-user" aria-hidden="true"></i> Pr&eacute;nom</span>
                                            <input class="<?php echo e($profileFieldClass); ?>" type="text" id="first_name" name="first_name" autocomplete="given-name" value="<?php echo e($firstName); ?>" required>
                                        </label>
                                        <label class="<?php echo e($profileLabelClass); ?>" for="last_name">
                                            <span class="profile-field-label"><i class="fas fa-user-tag" aria-hidden="true"></i> Nom</span>
                                            <input class="<?php echo e($profileFieldClass); ?>" type="text" id="last_name" name="last_name" autocomplete="family-name" value="<?php echo e($lastName); ?>" required>
                                        </label>
                                        <label class="<?php echo e($profileLabelClass); ?> md:col-span-2" for="email">
                                            <span class="profile-field-label"><i class="fas fa-envelope" aria-hidden="true"></i> Email</span>
                                            <input class="<?php echo e($profileFieldClass); ?>" type="email" id="email" name="email" autocomplete="email" value="<?php echo e($email); ?>" required>
                                            <span id="email_inline_error" class="mt-1 hidden text-xs text-gray-500 dark:text-gray-400"></span>
                                            <?php if ($status === 'pending_email'): ?>
                                                <span class="mt-1 block text-xs text-amber-600 dark:text-amber-300">V&eacute;rifiez votre bo&icirc;te mail pour confirmer le nouvel email.</span>
                                            <?php endif; ?>
                                        </label>
                                        <label class="<?php echo e($profileLabelClass); ?> md:col-span-2" for="phone">
                                            <span class="profile-field-label"><i class="fas fa-phone" aria-hidden="true"></i> Num&eacute;ro de t&eacute;l&eacute;phone<?php echo $isTeacher ? ' (retraits)' : ''; ?></span>
                                            <input class="<?php echo e($profileFieldClass); ?>" type="text" id="phone" name="phone" autocomplete="tel" placeholder="90 00 00 00" value="<?php echo e($phone); ?>">
                                            <span id="phone_inline_error" class="mt-1 hidden text-xs text-gray-500 dark:text-gray-400"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="<?php echo e($isStudent ? 'grid gap-6 lg:grid-cols-2' : 'space-y-6'); ?>">
                            <section class="<?php echo e($profileCardClass); ?> h-full">
                                <p class="text-[11px] uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">Localisation</p>
                                <h3 class="profile-section-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Coordonn&eacute;es g&eacute;ographiques</h3>
                                <div class="mt-5 grid gap-5 md:grid-cols-2">
                                    <label class="<?php echo e($profileLabelClass); ?> md:col-span-2" for="country">
                                        <span class="profile-field-label"><i class="fas fa-flag" aria-hidden="true"></i> Pays</span>
                                        <input class="<?php echo e($profileFieldClass); ?>" type="text" id="country" name="country" autocomplete="country-name" value="<?php echo e($country); ?>">
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Indiquez le pays associ&eacute; &agrave; votre profil.</span>
                                    </label>
                                </div>
                            </section>

                            <?php if ($isStudent): ?>
                                <section class="<?php echo e($profileCardClass); ?> h-full">
                                    <p class="text-[11px] uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">Profil &eacute;tudiant</p>
                                    <h3 class="profile-section-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><i class="fas fa-award" aria-hidden="true"></i> Nom affich&eacute; sur les certificats</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Indiquez le nom tel qu'il appara&icirc;tra sur vos attestations.</p>
                                    <label class="<?php echo e($profileLabelClass); ?> mt-4" for="certificate_display_name">
                                        <span class="profile-field-label"><i class="fas fa-signature" aria-hidden="true"></i> Nom sur le certificat</span>
                                        <input class="<?php echo e($profileFieldClass); ?>" type="text" id="certificate_display_name" name="certificate_display_name" maxlength="180" placeholder="Ex : Pr&eacute;nom Nom" value="<?php echo e((string) ($studentProfile['certificate_display_name'] ?? '')); ?>">
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Ce nom appara&icirc;tra exactement sur vos attestations.</span>
                                    </label>
                                </section>
                            <?php endif; ?>
                        </div>

                        <?php if ($isTeacher): ?>
                            <section class="<?php echo e($profileCardClass); ?>">
                                <p class="text-[11px] uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">Profil enseignant</p>
                                <h3 class="profile-section-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><i class="fas fa-briefcase" aria-hidden="true"></i> D&eacute;tails professionnels</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Compl&eacute;tez votre profil enseignant.</p>
                                <div class="mt-5 grid gap-5 md:grid-cols-2">
                                    <label class="<?php echo e($profileLabelClass); ?> md:col-span-2" for="professional_title">
                                        <span class="profile-field-label"><i class="fas fa-id-badge" aria-hidden="true"></i> Titre professionnel</span>
                                        <input class="<?php echo e($profileFieldClass); ?>" type="text" id="professional_title" name="professional_title" maxlength="150" placeholder="Ex : Formateur en d&eacute;veloppement web" value="<?php echo e((string) ($teacherProfile['professional_title'] ?? '')); ?>" required>
                                    </label>
                                    <label class="<?php echo e($profileLabelClass); ?> md:col-span-2" for="short_bio">
                                        <span class="profile-field-label"><i class="fas fa-align-left" aria-hidden="true"></i> Courte biographie</span>
                                        <textarea class="<?php echo e($profileFieldClass); ?> min-h-[120px] resize-y" id="short_bio" name="short_bio" maxlength="1200" placeholder="R&eacute;sumez votre parcours et votre approche." required><?php echo e((string) ($teacherProfile['short_bio'] ?? '')); ?></textarea>
                                    </label>
                                    <label class="<?php echo e($profileLabelClass); ?>" for="portfolio_url">
                                        <span class="profile-field-label"><i class="fas fa-link" aria-hidden="true"></i> Portfolio (optionnel)</span>
                                        <input class="<?php echo e($profileFieldClass); ?>" type="url" id="portfolio_url" name="portfolio_url" placeholder="https://..." value="<?php echo e((string) ($teacherProfile['portfolio_url'] ?? '')); ?>">
                                    </label>
                                    <label class="<?php echo e($profileLabelClass); ?>" for="linkedin_url">
                                        <span class="profile-field-label"><i class="fab fa-linkedin" aria-hidden="true"></i> LinkedIn (optionnel)</span>
                                        <input class="<?php echo e($profileFieldClass); ?>" type="url" id="linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/in/..." value="<?php echo e((string) ($teacherProfile['linkedin_url'] ?? '')); ?>">
                                    </label>
                                </div>

                                <?php if ($teacherCvUrl !== ''): ?>
                                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-semibold">
                                        <span class="inline-flex rounded-full bg-primary-100 px-2.5 py-1 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">CV enregistr&eacute;</span>
                                        <a class="<?php echo e($profileSecondaryButtonClass); ?>" href="<?php echo e($teacherCvUrl); ?>" target="_blank" rel="noopener">
                                            <i class="fas fa-external-link-alt"></i>
                                            <span>Voir le CV actuel</span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </section>

                            <?php if ($hasSignatureColumn): ?>
                                <section id="teacher-signature-section" class="<?php echo e($profileCardClass); ?>" data-upload-dropzone="signature_file">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[11px] uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">Signature</p>
                                            <h3 class="profile-section-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><i class="fas fa-pen-nib" aria-hidden="true"></i> Signature enseignant</h3>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Utilis&eacute;e automatiquement sur les certificats. La capture sera recadr&eacute;e en PNG transparent 800 &times; 240 px.</p>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-start">
                                        <div class="relative flex h-24 w-full max-w-xs items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                                            <img id="signature_preview" src="<?php echo e($signatureUrl); ?>" alt="Signature enseignant" class="h-24 w-full object-contain<?php echo $signatureUrl !== '' ? '' : ' hidden'; ?>">
                                            <span id="signature_placeholder" class="<?php echo $signatureUrl !== '' ? 'hidden ' : ''; ?>text-xs font-semibold text-gray-400">Aucune signature</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Fichier signature</p>
                                            <p id="signature_filename" class="text-xs text-gray-500 dark:text-gray-400">Aucun nouveau fichier choisi</p>
                                            <p id="signature_feedback" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                            <p data-upload-state="signature_file" class="text-[11px] text-gray-500 dark:text-gray-400">État: <?php echo $signatureUrl !== '' ? 'fichier actuel charg&eacute;.' : 'aucune s&eacute;lection.'; ?></p>

                                            <div class="mt-4 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-4 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
                                                <p><i class="fas fa-cloud-upload-alt mr-1.5 text-primary-500" aria-hidden="true"></i>Glissez-d&eacute;posez la signature ou cliquez pour s&eacute;lectionner un fichier.</p>
                                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                                    <button type="button" class="<?php echo e($profileSecondaryButtonClass); ?> gap-2 px-3 py-2 text-xs" data-upload-trigger="signature_file">
                                                        <i class="fas fa-upload"></i>
                                                        <span>Choisir un fichier</span>
                                                    </button>
                                                    <span>Max <?php echo e((string) $signatureMaxMb); ?> Mo</span>
                                                </div>
                                                <input class="sr-only" type="file" id="signature_file" name="signature_file" accept=".jpg,.jpeg,.png,.webp" data-max-mb="<?php echo e((string) $signatureMaxMb); ?>" data-upload-feedback="signature_feedback" data-upload-name="signature_filename" data-upload-preview="signature_preview" data-upload-placeholder="signature_placeholder" data-signature-normalize="1" data-signature-source-max-mb="10">
                                            </div>

                                            <?php if ($signatureUrl !== ''): ?>
                                                <label class="mt-4 inline-flex items-start gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                                    <input class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" type="checkbox" name="remove_signature" value="1">
                                                    <span>Supprimer la signature actuelle</span>
                                                </label>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </section>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div id="tab-preferences" class="tab-panel hidden space-y-6" role="tabpanel" aria-labelledby="profile-tab-preferences">
                        <section class="<?php echo e($profileCardClass); ?>">
                            <p class="text-[11px] uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">Notifications</p>
                            <h3 class="profile-section-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><i class="fas fa-bell" aria-hidden="true"></i> Vos pr&eacute;f&eacute;rences</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choisissez o&ugrave; recevoir les alertes importantes.</p>

                            <div class="mt-5 grid gap-3 md:grid-cols-2">
                                <label class="profile-preference-row flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                                    <span class="min-w-0">
                                        <strong class="profile-field-label text-gray-900 dark:text-white"><i class="fas fa-bullhorn" aria-hidden="true"></i> Annonces par email</strong>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Recevoir les communications officielles.</span>
                                    </span>
                                    <span class="profile-switch">
                                        <input class="profile-switch__input" type="checkbox" role="switch" name="notification_preferences[email_announcements]" value="1" <?php echo !empty($notificationPreferences['email_announcements']) ? 'checked' : ''; ?>>
                                        <span class="profile-switch__track" aria-hidden="true"></span>
                                    </span>
                                </label>
                                <label class="profile-preference-row flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                                    <span class="min-w-0">
                                        <strong class="profile-field-label text-gray-900 dark:text-white"><i class="fas fa-headset" aria-hidden="true"></i> Support par email</strong>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">R&eacute;ponses et suivis du support.</span>
                                    </span>
                                    <span class="profile-switch">
                                        <input class="profile-switch__input" type="checkbox" role="switch" name="notification_preferences[email_support]" value="1" <?php echo !empty($notificationPreferences['email_support']) ? 'checked' : ''; ?>>
                                        <span class="profile-switch__track" aria-hidden="true"></span>
                                    </span>
                                </label>
                                <label class="profile-preference-row flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                                    <span class="min-w-0">
                                        <strong class="profile-field-label text-gray-900 dark:text-white"><i class="fas fa-calendar-check" aria-hidden="true"></i> Rendez-vous par email</strong>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Confirmations et rappels.</span>
                                    </span>
                                    <span class="profile-switch">
                                        <input class="profile-switch__input" type="checkbox" role="switch" name="notification_preferences[email_meetings]" value="1" <?php echo !empty($notificationPreferences['email_meetings']) ? 'checked' : ''; ?>>
                                        <span class="profile-switch__track" aria-hidden="true"></span>
                                    </span>
                                </label>
                                <label class="profile-preference-row flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                                    <span class="min-w-0">
                                        <strong class="profile-field-label text-gray-900 dark:text-white"><i class="fas fa-desktop" aria-hidden="true"></i> Annonces dans l'application</strong>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Notifications visibles dans votre espace.</span>
                                    </span>
                                    <span class="profile-switch">
                                        <input class="profile-switch__input" type="checkbox" role="switch" name="notification_preferences[popup_announcements]" value="1" <?php echo !empty($notificationPreferences['popup_announcements']) ? 'checked' : ''; ?>>
                                        <span class="profile-switch__track" aria-hidden="true"></span>
                                    </span>
                                </label>
                                <label class="profile-preference-row flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40">
                                    <span class="min-w-0">
                                        <strong class="profile-field-label text-gray-900 dark:text-white"><i class="fas fa-comments" aria-hidden="true"></i> Support dans l'application</strong>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Alertes lorsque le support vous r&eacute;pond.</span>
                                    </span>
                                    <span class="profile-switch">
                                        <input class="profile-switch__input" type="checkbox" role="switch" name="notification_preferences[popup_support]" value="1" <?php echo !empty($notificationPreferences['popup_support']) ? 'checked' : ''; ?>>
                                        <span class="profile-switch__track" aria-hidden="true"></span>
                                    </span>
                                </label>
                            </div>
                        </section>
                    </div>

                    <div class="profile-action-bar rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800" data-profile-action-bar>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <span data-profile-dirty class="text-sm text-gray-600 dark:text-gray-300">Aucune modification en attente.</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" data-profile-cancel="<?php echo e($dashboardUrl); ?>" class="<?php echo e($profileSecondaryButtonClass); ?> w-full sm:w-auto">
                                    <i class="fas fa-times"></i>
                                    <span>Annuler</span>
                                </button>
                                <button type="submit" data-profile-save class="<?php echo e($profilePrimaryButtonClass); ?> w-full sm:w-auto disabled:cursor-not-allowed disabled:opacity-50" disabled>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Enregistrer</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if ($passwordMessage !== ''): ?>
                    <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200" role="status" aria-live="polite">
                        <?php echo e($passwordMessage); ?>
                    </section>
                <?php endif; ?>

                <?php if ($passwordErrors !== []): ?>
                    <section class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200" role="alert">
                        <ul class="space-y-1">
                            <?php foreach ($passwordErrors as $error): ?>
                                <li>- <?php echo e((string) $error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <div id="tab-security" class="tab-panel hidden space-y-6" role="tabpanel" aria-labelledby="profile-tab-security">
                    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(20rem,0.85fr)]">
                        <?php if ($isGoogleAccount): ?>
                            <section class="<?php echo e($profileCardClass); ?> text-sm text-gray-600 dark:text-gray-300">
                                <p class="text-[11px] uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">S&eacute;curit&eacute;</p>
                                <h3 class="profile-section-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><i class="fas fa-lock" aria-hidden="true"></i> Mot de passe</h3>
                                <p class="mt-2">Compte connect&eacute; &agrave; Google. Le mot de passe se g&egrave;re directement chez Google.</p>
                            </section>
                        <?php else: ?>
                            <section class="<?php echo e($profileCardClass); ?>">
                                <p class="text-[11px] uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">S&eacute;curit&eacute;</p>
                                <h3 class="profile-section-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><i class="fas fa-lock" aria-hidden="true"></i> Changer le mot de passe</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choisissez un mot de passe fort et unique.</p>

                                <form method="post" action="<?php echo e($formAction); ?>" class="mt-5 space-y-4">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="password">

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="md:col-span-2">
                                            <label class="<?php echo e($profileLabelClass); ?>" for="current_password">
                                                <span class="profile-field-label"><i class="fas fa-key" aria-hidden="true"></i> Mot de passe actuel</span>
                                            </label>
                                            <div class="mt-1 flex gap-3">
                                                <input class="<?php echo e($profileFieldClass); ?> !mt-0 flex-1" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                                                <button type="button" class="<?php echo e($profileSecondaryButtonClass); ?> profile-password-icon-button h-12 w-12 shrink-0 p-0 text-sm" data-password-toggle="#current_password" data-label-show="Afficher le mot de passe actuel" data-label-hide="Masquer le mot de passe actuel">
                                                    <i class="fas fa-eye" data-password-toggle-icon aria-hidden="true"></i>
                                                    <span class="sr-only" data-password-toggle-label>Afficher le mot de passe actuel</span>
                                                </button>
                                            </div>
                                            <span id="current_password_inline_error" class="mt-1 hidden text-xs text-gray-500 dark:text-gray-400"></span>
                                        </div>

                                        <div>
                                            <label class="<?php echo e($profileLabelClass); ?>" for="new_password">
                                                <span class="profile-field-label"><i class="fas fa-lock-open" aria-hidden="true"></i> Nouveau mot de passe</span>
                                            </label>
                                            <div class="mt-1 flex gap-3">
                                                <input class="<?php echo e($profileFieldClass); ?> !mt-0 flex-1" type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required>
                                                <button type="button" class="<?php echo e($profileSecondaryButtonClass); ?> profile-password-icon-button h-12 w-12 shrink-0 p-0 text-sm" data-password-toggle="#new_password" data-label-show="Afficher le nouveau mot de passe" data-label-hide="Masquer le nouveau mot de passe">
                                                    <i class="fas fa-eye" data-password-toggle-icon aria-hidden="true"></i>
                                                    <span class="sr-only" data-password-toggle-label>Afficher le nouveau mot de passe</span>
                                                </button>
                                            </div>
                                            <span id="new_password_inline_error" class="mt-1 hidden text-xs text-gray-500 dark:text-gray-400"></span>
                                        </div>

                                        <div>
                                            <label class="<?php echo e($profileLabelClass); ?>" for="confirm_password">
                                                <span class="profile-field-label"><i class="fas fa-check-double" aria-hidden="true"></i> Confirmer le mot de passe</span>
                                            </label>
                                            <div class="mt-1 flex gap-3">
                                                <input class="<?php echo e($profileFieldClass); ?> !mt-0 flex-1" type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                                                <button type="button" class="<?php echo e($profileSecondaryButtonClass); ?> profile-password-icon-button h-12 w-12 shrink-0 p-0 text-sm" data-password-toggle="#confirm_password" data-label-show="Afficher la confirmation du mot de passe" data-label-hide="Masquer la confirmation du mot de passe">
                                                    <i class="fas fa-eye" data-password-toggle-icon aria-hidden="true"></i>
                                                    <span class="sr-only" data-password-toggle-label>Afficher la confirmation du mot de passe</span>
                                                </button>
                                            </div>
                                            <span id="confirm_password_inline_error" class="mt-1 hidden text-xs text-gray-500 dark:text-gray-400"></span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="submit" class="<?php echo e($profilePrimaryButtonClass); ?>">
                                            <i class="fas fa-shield-alt"></i>
                                            <span>Mettre &agrave; jour</span>
                                        </button>
                                    </div>
                                </form>
                            </section>
                        <?php endif; ?>

                        <details class="profile-danger-zone rounded-2xl border border-rose-200 bg-white dark:border-rose-900/40 dark:bg-gray-800" <?php echo $deleteAccountPanelOpen ? 'open' : ''; ?>>
                            <summary class="profile-danger-zone__summary flex cursor-pointer list-none items-center justify-between gap-4 p-5">
                                <span class="min-w-0">
                                    <span class="block text-[11px] font-bold uppercase tracking-[0.28em] text-rose-600 dark:text-rose-300"><i class="fas fa-exclamation-triangle mr-1.5" aria-hidden="true"></i>Zone dangereuse</span>
                                    <span class="profile-section-title mt-1 text-lg font-bold text-rose-700 dark:text-rose-200"><i class="fas fa-trash-alt" aria-hidden="true"></i> Suppression d&eacute;finitive</span>
                                    <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">Gardez cette section ferm&eacute;e sauf si vous voulez supprimer le compte.</span>
                                </span>
                                <span class="profile-danger-zone__chevron flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-200">
                                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                </span>
                            </summary>

                            <form method="post" action="<?php echo e($formAction); ?>" class="profile-danger-zone__panel border-t border-rose-100 p-5 dark:border-rose-900/40" data-delete-account-form data-final-confirm-message="Derni&egrave;re confirmation : supprimer d&eacute;finitivement ce compte ?">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="action" value="delete_account">
                                <input class="hidden" type="text" name="delete_account_username" value="<?php echo e($email); ?>" autocomplete="username" tabindex="-1" aria-hidden="true">
                                <p class="text-[11px] uppercase tracking-[0.28em] text-rose-600 dark:text-rose-300">Suppression</p>
                                <h3 class="profile-section-title mt-1 text-lg font-bold text-rose-700 dark:text-rose-200"><i class="fas fa-user-slash" aria-hidden="true"></i> Confirmer la suppression</h3>
                                <p class="mt-1 text-sm text-rose-700/80 dark:text-rose-200/80">Cette action est irr&eacute;versible.</p>

                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    <label class="<?php echo e($profileLabelClass); ?> md:col-span-2" for="delete_confirmation">
                                        <span class="profile-field-label"><i class="fas fa-keyboard" aria-hidden="true"></i> Saisissez exactement SUPPRIMER</span>
                                        <input class="<?php echo e($profileFieldClass); ?> border-rose-200 bg-white font-semibold tracking-[0.18em] dark:border-rose-900/60 dark:bg-gray-900/70" type="text" id="delete_confirmation" name="delete_confirmation" value="" autocomplete="new-password" inputmode="text" autocapitalize="characters" spellcheck="false" placeholder="SUPPRIMER" pattern="SUPPRIMER" data-delete-confirmation-input required>
                                        <span class="mt-1 block text-xs text-rose-600 dark:text-rose-300">Le champ doit contenir uniquement SUPPRIMER.</span>
                                    </label>

                                    <?php if ($isGoogleAccount): ?>
                                        <div class="rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm text-gray-600 dark:border-rose-900/60 dark:bg-gray-900/60 dark:text-gray-300 md:col-span-2">
                                            Compte Google : la confirmation se fait sans mot de passe local suppl&eacute;mentaire.
                                        </div>
                                    <?php else: ?>
                                        <label class="<?php echo e($profileLabelClass); ?> md:col-span-2" for="delete_account_password">
                                            <span class="profile-field-label"><i class="fas fa-lock" aria-hidden="true"></i> Mot de passe de confirmation</span>
                                            <input class="<?php echo e($profileFieldClass); ?> border-rose-200 bg-white dark:border-rose-900/60 dark:bg-gray-900/70" type="password" id="delete_account_password" name="delete_account_password" autocomplete="current-password" required>
                                        </label>
                                    <?php endif; ?>

                                    <label class="md:col-span-2 inline-flex items-start gap-3 rounded-2xl border border-rose-200 bg-white px-4 py-3 text-sm text-gray-700 dark:border-rose-900/60 dark:bg-gray-900/60 dark:text-gray-200">
                                        <input class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" type="checkbox" name="delete_acknowledge" value="1" required>
                                        <span><i class="fas fa-exclamation-circle mr-1.5 text-rose-500" aria-hidden="true"></i>Je confirme vouloir supprimer mon compte de mani&egrave;re irr&eacute;versible et perdre l'acc&egrave;s &agrave; cet espace.</span>
                                    </label>
                                </div>

                                <div class="mt-5 flex flex-wrap items-center gap-2">
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                                        <i class="fas fa-trash-alt"></i>
                                        <span>Supprimer mon compte</span>
                                    </button>
                                </div>
                            </form>
                        </details>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php if ($profileShellRole === 'admin'): ?>
        </div>
    <?php elseif ($profileShellRole === 'student'): ?>
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
                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('headset'); ?></span>
                <?php if ($supportUnreadCount > 0): ?>
                    <span class="absolute -right-1 -top-1 min-w-4 rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white text-center"><?php echo e((string) $supportUnreadCount); ?></span>
                <?php endif; ?>
            </span>
            <span class="student-dashboard-support-label hidden sm:block pr-1">
                <span class="block text-sm font-semibold leading-tight">Assistance support</span>
                <span class="block text-xs text-blue-100">Réponse rapide</span>
            </span>
        </a>
    <?php endif; ?>
</body>
</html>
