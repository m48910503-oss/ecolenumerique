<?php
$user = is_array($user ?? null) ? $user : [];
$isStaff = !empty($isStaff);
$basePath = (string) ($basePath ?? rtrim(BASE_PATH, '/'));
$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$pendingMeetingsCount = max(0, (int) ($pendingMeetingsCount ?? 0));
$studentCoursesCount = max(0, (int) ($studentCoursesCount ?? 0));
$dashboardSpace = trim((string) ($dashboardSpace ?? ''));
$supportRoleSpace = in_array($dashboardSpace, ['teacher', 'student'], true) ? $dashboardSpace : 'student';
$supportTickets = is_array($supportTickets ?? null) ? $supportTickets : [];
$supportTicketStats = is_array($supportTicketStats ?? null) ? $supportTicketStats : ['open' => 0, 'waiting' => 0, 'resolved' => 0];
$userId = max(0, (int) ($user['id'] ?? 0));

$appendSpace = static function (string $url) use ($dashboardSpace): string {
    if ($dashboardSpace === '') {
        return $url;
    }

    return $url . (str_contains($url, '?') ? '&' : '?') . 'space=' . urlencode($dashboardSpace);
};

$countLabel = static function (int $count): string {
    return $count > 99 ? '99+' : (string) $count;
};

$supportDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($user, 'Utilisateur')
    : trim((string) (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
if ($supportDisplayName === '') {
    $supportDisplayName = 'Utilisateur';
}

$supportFirstName = trim((string) ($user['first_name'] ?? ''));
if ($supportFirstName === '') {
    $nameParts = preg_split('/\s+/', $supportDisplayName);
    $supportFirstName = trim((string) ($nameParts[0] ?? ''));
}
if ($supportFirstName === '') {
    $supportFirstName = 'Utilisateur';
}

$supportEmail = trim((string) ($user['email'] ?? ''));
$supportAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($user) : '';
$supportInitials = function_exists('dashboard_user_initials') ? dashboard_user_initials($user, 'SP') : 'SP';
$supportAvatarMarkup = $supportAvatarUrl !== ''
    ? '<img src="' . e($supportAvatarUrl) . '" alt="" class="h-full w-full object-cover">'
    : e($supportInitials);

$pageTitle = $supportRoleSpace === 'teacher' ? 'Support enseignant' : 'Assistance';
$pageSubtitle = 'Communication · Assistance';
$pageContextLabel = $supportRoleSpace === 'teacher' ? 'Espace enseignant' : 'Espace étudiant';
$profileRowLabel = 'Niveau Pro';
$profileRowBadge = $supportRoleSpace === 'teacher' ? 'Enseignant' : 'Premium';
$newTicketIntro = $supportRoleSpace === 'teacher'
    ? 'Décrivez le blocage et ajoutez une pièce si besoin.'
    : 'Décrivez le problème et ajoutez une capture si besoin.';
$newTicketSubjectPlaceholder = $supportRoleSpace === 'teacher'
    ? 'Ex. Problème de publication du cours'
    : 'Ex. Problème d’accès au cours';
$newTicketBodyPlaceholder = $supportRoleSpace === 'teacher'
    ? 'Décrivez le blocage et les étapes testées.'
    : 'Décrivez le problème en quelques lignes.';
$assistantGreeting = 'Bonjour ' . $supportFirstName . ' ! Comment puis-je vous aider aujourd\'hui ?';

$siteLogoIcon = '<i class="fas fa-graduation-cap text-lg"></i>';

$studentDashboardUrl = site_url('student/dashboard');
$studentCoursesUrl = site_url('student/my_courses');
$studentFavoritesUrl = site_url('student/favorites');
$studentOrdersUrl = site_url('student/orders');
$studentMeetingsUrl = site_url('student/meetings');
$studentCertificatesUrl = site_url('student/certificates');
$studentReferralsUrl = site_url('student/referrals');

$teacherDashboardUrl = site_url('teacher/dashboard');
$teacherCoursesUrl = site_url('teacher/courses');
$teacherCourseCreateUrl = site_url('teacher/course_create');
$teacherWithdrawalsUrl = site_url('teacher/withdrawals');
$teacherMeetingsUrl = site_url('teacher/meetings?status=pending');

$notificationsUrl = $appendSpace(site_url('notifications'));
$supportUrl = $appendSpace(site_url('support'));
$logoutUrl = site_url('logout');

$sidebarLinks = $supportRoleSpace === 'teacher'
    ? [
        ['label' => 'Tableau de bord', 'url' => $teacherDashboardUrl, 'icon' => 'fa-home', 'badge' => null, 'active' => false],
        ['label' => 'Nouveau cours', 'url' => $teacherCourseCreateUrl, 'icon' => 'fa-plus-square', 'badge' => null, 'active' => false],
        ['label' => 'Mes cours', 'url' => $teacherCoursesUrl, 'icon' => 'fa-book-open', 'badge' => null, 'active' => false],
        ['label' => 'Mes revenus', 'url' => $teacherWithdrawalsUrl, 'icon' => 'fa-wallet', 'badge' => null, 'active' => false],
        ['label' => 'Rendez-vous', 'url' => $teacherMeetingsUrl, 'icon' => 'fa-handshake', 'badge' => null, 'active' => false],
    ]
    : [
        ['label' => 'Tableau de bord', 'url' => $studentDashboardUrl, 'icon' => 'fa-home', 'badge' => null, 'active' => false],
        ['label' => 'Mes cours', 'url' => $studentCoursesUrl, 'icon' => 'fa-book-open', 'badge' => $studentCoursesCount > 0 ? $countLabel($studentCoursesCount) : null, 'active' => false],
        ['label' => 'Mes favoris', 'url' => $studentFavoritesUrl, 'icon' => 'fa-heart', 'badge' => null, 'active' => false],
        ['label' => 'Mes achats', 'url' => $studentOrdersUrl, 'icon' => 'fa-shopping-bag', 'badge' => null, 'active' => false],
        ['label' => 'Mes rendez-vous', 'url' => $studentMeetingsUrl, 'icon' => 'fa-handshake', 'badge' => null, 'active' => false],
        ['label' => 'Mes certificats', 'url' => $studentCertificatesUrl, 'icon' => 'fa-certificate', 'badge' => null, 'active' => false],
        ['label' => 'Parrainage', 'url' => $studentReferralsUrl, 'icon' => 'fa-users', 'badge' => null, 'active' => false],
    ];

$kpiCards = [
    [
        'key' => 'open',
        'label' => 'Tickets ouverts',
        'value' => max(0, (int) ($supportTicketStats['open'] ?? 0)),
        'icon' => 'fa-ticket-alt',
        'tone' => 'amber',
    ],
    [
        'key' => 'waiting',
        'label' => 'En attente de réponse',
        'value' => max(0, (int) ($supportTicketStats['waiting'] ?? 0)),
        'icon' => 'fa-clock',
        'tone' => 'blue',
    ],
    [
        'key' => 'resolved',
        'label' => 'Résolus ce mois',
        'value' => max(0, (int) ($supportTicketStats['resolved'] ?? 0)),
        'icon' => 'fa-check-circle',
        'tone' => 'green',
    ],
];

$ticketCategories = $supportRoleSpace === 'teacher'
    ? [
        ['value' => 'technical', 'label' => 'Problème technique', 'priority' => 'normal'],
        ['value' => 'lesson', 'label' => 'Cours / contenu', 'priority' => 'normal'],
        ['value' => 'meeting', 'label' => 'Rendez-vous', 'priority' => 'normal'],
        ['value' => 'withdrawal', 'label' => 'Retrait / revenus', 'priority' => 'high'],
        ['value' => 'other', 'label' => 'Autre', 'priority' => 'low'],
    ]
    : [
        ['value' => 'course', 'label' => 'Accès au cours', 'priority' => 'normal'],
        ['value' => 'billing', 'label' => 'Paiement', 'priority' => 'high'],
        ['value' => 'certificate', 'label' => 'Certificat', 'priority' => 'normal'],
        ['value' => 'technical', 'label' => 'Problème technique', 'priority' => 'normal'],
        ['value' => 'other', 'label' => 'Autre', 'priority' => 'low'],
    ];

$ticketPriorityOptions = [
    ['value' => 'low', 'label' => 'Faible'],
    ['value' => 'normal', 'label' => 'Normal'],
    ['value' => 'high', 'label' => 'Urgent'],
];

$useTeacherShell = $supportRoleSpace === 'teacher';
$supportDataView = $useTeacherShell ? 'teacher/support' : 'student/support';
$supportContentShellClass = $useTeacherShell
    ? 'p-4 pb-24 space-y-8 animate-fade-up sm:p-6 sm:pb-24'
    : 'student-dashboard-content support-dashboard-content p-4 pb-24 space-y-5 animate-fade-up sm:p-6 sm:pb-24 sm:space-y-6 lg:space-y-8';
?>
<!doctype html>
<html
    lang="fr"
    class="light"
    data-default-theme="light"
    data-view="<?php echo e($supportDataView); ?>"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle . ' · École Numérique'); ?></title>
    <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
    <meta name="app-global-assets" content="off">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/support.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/support.css') ?: time()))); ?>">
<?php if ($useTeacherShell): ?>
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
<?php else: ?>
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time()))); ?>" defer></script>
<?php endif; ?>
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/sidebar_toggle.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/support_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/support_page.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body
    data-view="<?php echo e($supportDataView); ?>"
    data-support-space="<?php echo e($supportRoleSpace); ?>"
    data-base-path="<?php echo e($basePath); ?>"
    data-user-id="<?php echo e((string) $userId); ?>"
    data-initial-conv="<?php echo e((string) ($initialConv ?? 0)); ?>"
    data-sidebar-desktop-toggle="0"
    class="<?php echo e($useTeacherShell
        ? 'app-view app-role-teacher teacher-dashboard-page teacher-support-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen'
        : 'app-view app-role-student student-dashboard-page support-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen'); ?>"
>
    <div class="<?php echo e($useTeacherShell ? 'teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row' : 'student-dashboard-shell relative min-h-screen overflow-hidden'); ?>">
<?php if ($useTeacherShell): ?>
        <?php $teacherNavActive = 'support'; ?>
        <?php require __DIR__ . '/../teacher/_sidebar.php'; ?>
<?php else: ?>
        <?php $studentNavActive = 'support'; ?>
        <?php $studentDashboardShell = true; ?>
        <?php $studentTopTitle = 'Assistance'; ?>
        <?php $studentTopSubtitle = 'Espace étudiant'; ?>
        <?php $studentTopShowSearch = false; ?>
        <?php $studentTopHideSupportFab = true; ?>
        <?php $activeEnrollments = $studentCoursesCount; ?>
        <?php require __DIR__ . '/../student/_sidebar.php'; ?>
<?php endif; ?>

        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

<?php if (!$useTeacherShell): ?>
        <div class="student-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72">
<?php endif; ?>
        <main data-scroll-top-root class="<?php echo e($useTeacherShell ? 'teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900' : 'student-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900'); ?>">
<?php if ($useTeacherShell): ?>
            <?php $teacherTopTitle = 'Support technique'; ?>
            <?php $teacherTopSubtitle = 'Espace formateur'; ?>
            <?php $showCatalogue = false; ?>
            <?php $showSupportFab = false; ?>
            <?php require __DIR__ . '/../teacher/_topbar.php'; ?>
<?php else: ?>
            <?php require __DIR__ . '/../student/_topbar.php'; ?>
<?php endif; ?>

            <div class="<?php echo e($supportContentShellClass); ?>">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <?php foreach ($kpiCards as $card): ?>
                        <?php
                            $tone = (string) ($card['tone'] ?? 'blue');
                            $toneClass = $tone === 'amber'
                                ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-300'
                                : ($tone === 'green'
                                    ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-300'
                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300');
                            $valueClass = $tone === 'amber'
                                ? 'text-amber-500'
                                : ($tone === 'green' ? 'text-green-600 dark:text-green-400' : 'text-blue-600 dark:text-blue-400');
                        ?>
                        <div class="bg-white dark:bg-gray-800 rounded-[24px] p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e((string) ($card['label'] ?? '')); ?></p>
                                    <p class="mt-1 text-3xl font-bold <?php echo e($valueClass); ?>" data-support-kpi="<?php echo e((string) ($card['key'] ?? '')); ?>"><?php echo e((string) ((int) ($card['value'] ?? 0))); ?></p>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl <?php echo e($toneClass); ?>">
                                    <i class="fas <?php echo e((string) ($card['icon'] ?? 'fa-circle')); ?>"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(320px,350px)_minmax(0,1fr)]">
                    <section class="rounded-[24px] border border-gray-100 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                            <i class="fas fa-plus-circle text-primary-500"></i>
                            Nouveau ticket
                        </h3>
                        <p class="support-form-help mb-4 text-sm text-gray-600 dark:text-gray-300">
                            <?php echo e($newTicketIntro); ?>
                        </p>

                        <form id="support-new-form" class="space-y-4" method="post" action="<?php echo e(site_url('api/support_conversations')); ?>" enctype="multipart/form-data">
                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                            <div>
                                <label for="support-new-subject" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Sujet</label>
                                <input
                                    id="support-new-subject"
                                    name="subject"
                                    type="text"
                                    placeholder="<?php echo e($newTicketSubjectPlaceholder); ?>"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                                    required
                                >
                            </div>

                            <div>
                                <label for="support-new-category" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Catégorie</label>
                                <select
                                    id="support-new-category"
                                    name="category"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                                >
                                    <?php foreach ($ticketCategories as $index => $category): ?>
                                        <option value="<?php echo e((string) ($category['value'] ?? 'other')); ?>" data-priority="<?php echo e((string) ($category['priority'] ?? 'normal')); ?>"<?php echo $index === 0 ? ' selected' : ''; ?>>
                                            <?php echo e((string) ($category['label'] ?? 'Autre')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="support-new-priority" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Priorité</label>
                                <select
                                    id="support-new-priority"
                                    name="priority"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-700 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                                >
                                    <?php foreach ($ticketPriorityOptions as $priorityOption): ?>
                                        <?php $priorityValue = (string) ($priorityOption['value'] ?? 'normal'); ?>
                                        <option value="<?php echo e($priorityValue); ?>"<?php echo $priorityValue === 'normal' ? ' selected' : ''; ?>>
                                            <?php echo e((string) ($priorityOption['label'] ?? 'Normal')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="support-new-body" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                                <textarea
                                    id="support-new-body"
                                    name="body"
                                    rows="5"
                                    placeholder="<?php echo e($newTicketBodyPlaceholder); ?>"
                                    class="w-full resize-none rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-700 placeholder-gray-400 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                                    required
                                ></textarea>
                            </div>

                            <div>
                                <label for="support-new-attachments" class="support-file-picker flex cursor-pointer items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold transition">
                                    <i class="fas fa-paperclip" aria-hidden="true"></i>
                                    Ajouter une capture
                                </label>
                                <input
                                    id="support-new-attachments"
                                    name="attachments[]"
                                    type="file"
                                    accept="image/*,.pdf"
                                    multiple
                                    class="hidden"
                                >
                                <p id="support-new-files-hint" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Capture d’écran ou PDF, 5 Mo maximum par fichier.
                                </p>
                            </div>

                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 py-3.5 font-semibold text-white shadow-sm transition hover:bg-primary-700">
                                <i class="fas fa-paper-plane"></i>
                                Envoyer
                            </button>

                            <p id="support-new-status" class="hidden rounded-xl border px-3 py-2 text-sm"></p>
                        </form>
                    </section>

                    <section class="rounded-[24px] border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h3 class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-list-ul text-primary-500"></i>
                                Mes tickets
                            </h3>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="ticket-filter-btn rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm"
                                    data-ticket-filter="all"
                                >
                                    Tous
                                </button>
                                <button
                                    type="button"
                                    class="ticket-filter-btn rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    data-ticket-filter="open"
                                >
                                    Ouverts
                                </button>
                                <button
                                    type="button"
                                    class="ticket-filter-btn rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    data-ticket-filter="closed"
                                >
                                    Résolus
                                </button>
                            </div>
                        </div>

                        <div id="support-ticket-list" class="space-y-3">
                            <?php foreach ($supportTickets as $ticket): ?>
                                <?php
                                    $ticketId = max(0, (int) ($ticket['id'] ?? 0));
                                    $ticketStatusGroup = (string) ($ticket['status_group'] ?? 'open');
                                    $ticketStatusTone = (string) ($ticket['status_tone'] ?? 'amber');
                                    $ticketStatusLabel = (string) ($ticket['status_label'] ?? 'Ouvert');
                                    $ticketNoteTone = (string) ($ticket['note_tone'] ?? 'amber');
                                    $ticketDimmed = !empty($ticket['dimmed']);
                                    $ticketPriority = (string) ($ticket['priority'] ?? 'normal');
                                    $ticketPriorityLabel = (string) ($ticket['priority_label'] ?? ($ticketPriority === 'high' ? 'Urgent' : ($ticketPriority === 'low' ? 'Faible' : 'Normal')));
                                    $ticketCategoryLabel = (string) ($ticket['category_label'] ?? 'Autre');
                                    $ticketUpdatedLabel = (string) ($ticket['updated_label'] ?? ($ticket['date'] ?? '—'));
                                    $ticketActionLabel = $ticketStatusGroup === 'closed' ? 'Détails' : 'Voir / Répondre';
                                    $ticketStatusPillClass = 'support-ticket-status support-ticket-status--' . ($ticketStatusTone === 'action' ? 'action' : ($ticketStatusTone === 'green' ? 'closed' : ($ticketStatusTone === 'blue' ? 'open' : 'waiting')));
                                    $ticketNoteClass = 'support-ticket-note support-ticket-note--' . ($ticketNoteTone === 'action' ? 'action' : ($ticketNoteTone === 'blue' ? 'info' : ($ticketNoteTone === 'green' ? 'done' : 'waiting')));
                                    $ticketPriorityClass = 'support-ticket-priority support-ticket-priority--' . ($ticketPriority === 'high' ? 'urgent' : ($ticketPriority === 'low' ? 'low' : 'normal'));
                                    $ticketTitleClass = $ticketDimmed
                                        ? 'font-medium text-gray-700 dark:text-gray-300'
                                        : 'font-bold text-gray-900 dark:text-white';
                                    $ticketPreviewClass = $ticketDimmed
                                        ? 'text-gray-400 dark:text-gray-500'
                                        : 'text-gray-500 dark:text-gray-400';
                                ?>
                                <article
                                    class="ticket support-ticket-card rounded-xl p-4 <?php echo $ticketDimmed ? 'is-dimmed' : ''; ?>"
                                    data-conversation-id="<?php echo e((string) $ticketId); ?>"
                                    data-status="<?php echo e($ticketStatusGroup === 'closed' ? 'closed' : 'open'); ?>"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="support-ticket-number">
                                                Ticket #<?php echo e((string) $ticketId); ?>
                                            </p>
                                            <h4 class="<?php echo e($ticketTitleClass); ?>">
                                                <?php echo e((string) ($ticket['subject'] ?? 'Demande support')); ?>
                                            </h4>
                                        </div>
                                        <span class="<?php echo e($ticketStatusPillClass); ?>">
                                            <?php echo e($ticketStatusLabel); ?>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm <?php echo e($ticketPreviewClass); ?>">
                                        <?php echo e((string) ($ticket['preview'] ?? '')); ?>
                                    </p>
                                    <div class="support-ticket-meta mt-3">
                                        <span>
                                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                                            <?php echo e($ticketCategoryLabel); ?>
                                        </span>
                                        <span class="<?php echo e($ticketPriorityClass); ?>">
                                            <i class="fas fa-flag" aria-hidden="true"></i>
                                            <?php echo e($ticketPriorityLabel); ?>
                                        </span>
                                        <span>
                                            <i class="far fa-clock" aria-hidden="true"></i>
                                            Mis à jour : <?php echo e($ticketUpdatedLabel); ?>
                                        </span>
                                    </div>
                                    <div class="support-ticket-footer mt-3">
                                        <span class="<?php echo e($ticketNoteClass); ?>">
                                            <?php echo e((string) ($ticket['note'] ?? '')); ?>
                                        </span>
                                        <button type="button" class="support-ticket-action" data-ticket-action>
                                            <?php echo e($ticketActionLabel); ?>
                                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div id="support-ticket-empty" class="<?php echo $supportTickets !== [] ? 'hidden ' : ''; ?>rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                            Aucun ticket pour le moment.
                        </div>
                    </section>
                </div>
            </div>

            <button
                id="chat-toggle-btn"
                type="button"
                data-support-fab
                class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg transition hover:scale-110 hover:bg-primary-700 animate-bounce-in"
                title="Assistance en direct"
                aria-label="Assistance"
            >
                <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('headset')); ?>"></i></span>
                <?php if ($supportUnreadCount > 0): ?>
                    <span class="absolute -right-1 -top-1 inline-flex min-w-4 rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white text-center shadow-sm" data-live-support-badge><?php echo e($countLabel($supportUnreadCount)); ?></span>
                <?php endif; ?>
            </button>

            <div
                id="chat-widget"
                class="chat-widget fixed bottom-24 right-4 z-50 flex w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl animate-bounce-in hidden dark:border-gray-700 dark:bg-gray-800 sm:right-6 sm:w-96"
            >
                <div class="flex items-center justify-between bg-primary-600 p-4 text-white">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-white/20">
                            <i class="<?php echo e(dashboard_icon_fa_class('headset')); ?>" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold">Assistance École Numérique</p>
                            <p id="chat-thread-meta" class="text-xs text-primary-100">Sélectionnez un ticket ou démarrez un nouveau message.</p>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <button id="minimize-chat" type="button" class="rounded p-1 transition hover:bg-primary-700" aria-label="Minimiser">
                            <i class="fas fa-minus text-xs" aria-hidden="true"></i>
                        </button>
                        <button id="close-chat" type="button" class="rounded p-1 transition hover:bg-primary-700" aria-label="Fermer">
                            <i class="fas fa-times text-xs" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div id="chat-messages" class="custom-scroll flex-1 space-y-3 overflow-y-auto p-3 max-h-[300px] min-h-[150px]">
                    <div class="flex justify-start">
                        <div class="max-w-[80%] px-4 py-2 text-sm chat-bubble-received">
                            <?php echo e($assistantGreeting); ?>
                        </div>
                    </div>
                </div>

                <form id="support-message-form" class="border-t border-gray-100 p-3 dark:border-gray-700" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <input type="hidden" name="conversation_id" id="support-conv-id" value="">
                    <p id="chat-thread-status" class="hidden rounded-xl border px-3 py-2 text-sm"></p>
                    <div class="mt-3 flex items-center gap-2">
                        <input
                            type="text"
                            id="chat-input"
                            name="body"
                            placeholder="Écrivez votre message..."
                            class="flex-1 rounded-xl border-none bg-gray-100 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700"
                        >
                        <label for="chat-attachments" class="support-chat-attach inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl transition" aria-label="Joindre une capture" title="Joindre une capture">
                            <i class="fas fa-paperclip" aria-hidden="true"></i>
                        </label>
                        <input id="chat-attachments" name="attachments[]" type="file" accept="image/*,.pdf" multiple class="hidden">
                        <button id="send-chat-btn" type="submit" class="rounded-xl bg-primary-600 p-2 text-white transition hover:bg-primary-700" aria-label="Envoyer">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p id="chat-files-hint" class="mt-2 hidden text-xs text-gray-500 dark:text-gray-400"></p>
                </form>
            </div>
        </main>
<?php if (!$useTeacherShell): ?>
        </div>
<?php endif; ?>
    </div>
</body>
</html>
