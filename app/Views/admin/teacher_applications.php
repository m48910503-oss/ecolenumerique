<?php
$errors = is_array($errors ?? null) ? $errors : [];
$successMessage = (string) ($success ?? '');
$applications = is_array($applications ?? null) ? $applications : [];
$teacherRows = is_array($teacherRows ?? null) ? $teacherRows : [];
$teacherSummary = is_array($teacherSummary ?? null) ? $teacherSummary : [];
$selectedTeacher = is_array($selectedTeacher ?? null) ? $selectedTeacher : null;
$selectedTeacherId = max(0, (int) ($selectedTeacherId ?? 0));
$currentTab = in_array((string) ($currentTab ?? 'applications'), ['applications', 'teachers'], true) ? (string) $currentTab : 'applications';
$searchQuery = trim((string) ($searchQuery ?? ''));

$formatDate = static function (string $value): string {
    $raw = trim($value);
    if ($raw === '') {
        return '-';
    }
    try {
        return (new DateTimeImmutable($raw))->format('d/m/Y');
    } catch (Throwable $e) {
        return $raw;
    }
};

$formatDateTime = static function (string $value): string {
    $raw = trim($value);
    if ($raw === '') {
        return '-';
    }
    try {
        return (new DateTimeImmutable($raw))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $raw;
    }
};

$formatMoney = static function (int $amount): string {
    return number_format(max(0, $amount), 0, ',', ' ') . ' F CFA';
};

$formatNumber = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ');
};

$normalizeText = static function (string $value): string {
    return trim((string) preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES, 'UTF-8'))));
};

$truncateText = static function (string $value, int $limit = 180) use ($normalizeText): string {
    $value = $normalizeText($value);
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value) > $limit ? rtrim(mb_substr($value, 0, max(0, $limit - 3))) . '...' : $value;
    }
    return strlen($value) > $limit ? rtrim(substr($value, 0, max(0, $limit - 3))) . '...' : $value;
};

$resolveUrl = static function (string $path): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $path) === 1) {
        return $path;
    }
    return site_url(ltrim($path, '/'));
};

$initials = static function (string $name, string $fallback = 'EN'): string {
    $name = trim($name);
    if ($name === '') {
        return $fallback;
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
        }
        if ((function_exists('mb_strlen') ? mb_strlen($letters) : strlen($letters)) >= 2) {
            break;
        }
    }
    $letters = strtoupper($letters);
    return $letters !== '' ? $letters : $fallback;
};

$applicationStatusMeta = [
    'pending' => ['label' => 'En attente', 'class' => 'admin-shell-badge admin-shell-badge--warning', 'icon' => 'fa-hourglass-half'],
    'approved' => ['label' => 'Approuvée', 'class' => 'admin-shell-badge admin-shell-badge--info', 'icon' => 'fa-check-circle'],
    'rejected' => ['label' => 'Rejetée', 'class' => 'admin-shell-badge admin-shell-badge--danger', 'icon' => 'fa-times-circle'],
];

$courseStatusMeta = [
    'draft' => ['label' => 'Brouillon', 'class' => 'admin-shell-badge', 'icon' => 'fa-pen'],
    'submitted' => ['label' => 'En validation', 'class' => 'admin-shell-badge admin-shell-badge--warning', 'icon' => 'fa-hourglass-half'],
    'approved' => ['label' => 'Publié', 'class' => 'admin-shell-badge admin-shell-badge--info', 'icon' => 'fa-check-circle'],
    'rejected' => ['label' => 'Rejeté', 'class' => 'admin-shell-badge admin-shell-badge--danger', 'icon' => 'fa-times-circle'],
];

$levelLabel = static function (string $level): string {
    return match (strtolower(trim($level))) {
        'beginner' => 'Débutant',
        'intermediate' => 'Intermédiaire',
        'advanced' => 'Avancé',
        default => trim($level) !== '' ? ucfirst($level) : 'Non renseigné',
    };
};

$levelChipClass = static function (string $level): string {
    return match (strtolower(trim($level))) {
        'beginner' => 'admin-course-chip--level-beginner',
        'intermediate' => 'admin-course-chip--level-intermediate',
        'advanced' => 'admin-course-chip--level-advanced',
        default => 'admin-course-chip--level',
    };
};

$statusMeta = static function (string $status, array $map): array {
    $key = strtolower(trim($status));
    return $map[$key] ?? ['label' => $status !== '' ? ucfirst($status) : 'Inconnu', 'class' => 'admin-shell-badge', 'icon' => 'fa-circle'];
};

$pendingApplications = [];
$processedApplications = [];
foreach ($applications as $application) {
    if (!is_array($application)) {
        continue;
    }
    if ((string) ($application['status'] ?? '') === 'pending') {
        $pendingApplications[] = $application;
    } else {
        $processedApplications[] = $application;
    }
}

$pendingCount = max(0, (int) ($teacherSummary['pending_applications'] ?? count($pendingApplications)));
$activeTeacherCount = max(0, (int) ($teacherSummary['active_teachers'] ?? 0));
$suspendedTeacherCount = max(0, (int) ($teacherSummary['suspended_teachers'] ?? 0));
$teacherDirectoryCount = count($teacherRows);

$buildTabUrl = static function (string $tab) use ($searchQuery, $selectedTeacherId): string {
    $query = ['tab' => $tab];
    if ($searchQuery !== '') {
        $query['q'] = $searchQuery;
    }
    if ($tab === 'teachers' && $selectedTeacherId > 0) {
        $query['teacher_id'] = $selectedTeacherId;
    }
    return site_url('admin/teacher_applications?' . http_build_query($query));
};

$listPage = \App\Support\Pagination::pageFromQuery($_GET, 'page', 1);
$listPagination = $currentTab === 'applications'
    ? \App\Support\Pagination::build(count($pendingApplications), $listPage, 6)
    : \App\Support\Pagination::build(count($teacherRows), $listPage, 6);

if ($currentTab === 'applications') {
    $pendingApplications = \App\Support\Pagination::slice($pendingApplications, $listPagination);
} else {
    $teacherRows = \App\Support\Pagination::slice($teacherRows, $listPagination);
}

$buildListPageUrl = static function (int $targetPage) use ($currentTab, $searchQuery, $selectedTeacherId): string {
    $query = ['tab' => $currentTab];
    if ($searchQuery !== '') {
        $query['q'] = $searchQuery;
    }
    if ($currentTab === 'teachers' && $selectedTeacherId > 0) {
        $query['teacher_id'] = $selectedTeacherId;
    }
    if ($targetPage > 1) {
        $query['page'] = max(1, $targetPage);
    }

    return site_url('admin/teacher_applications?' . http_build_query($query));
};

$adminNavActive = 'teacher_applications';
$adminTopTitle = 'Gestion des enseignants';
$adminTopSubtitle = 'Administration';
$adminTopBadge = $pendingCount > 0 ? $pendingCount . ' candidature(s) en attente' : 'Aucune candidature en attente';
$adminTopBadgeTone = $pendingCount > 0 ? 'pending' : 'done';
$adminTopSearchAction = site_url('admin/teacher_applications');
$adminTopSearchPlaceholder = 'Rechercher un enseignant...';
$adminTopSearchValue = $searchQuery;
$adminTopSearchHidden = ['tab' => 'teachers'];
$adminTopActions = [
    ['label' => 'Cours', 'url' => site_url('admin/courses'), 'tone' => 'neutral'],
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des enseignants · Administrateur</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/teacher_applications" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense admin-teachers-page px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <?php if ($errors): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $err): ?>
                            <p class="inline-flex items-center gap-2"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><?php echo e((string) $err); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($successMessage !== ''): ?>
                    <div class="admin-shell-alert admin-shell-alert--success">
                        <p class="inline-flex items-center gap-2"><i class="fas fa-check-circle" aria-hidden="true"></i><?php echo e($successMessage); ?></p>
                    </div>
                <?php endif; ?>

                <section class="admin-teachers-hero rounded-2xl p-6 text-white shadow-glow font-body">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="admin-teachers-hero__eyebrow inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.22em] text-white/75">
                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                Pilotage pédagogique
                            </p>
                            <h2 class="admin-teachers-hero__title mt-2 text-2xl font-bold leading-tight tracking-tight sm:text-3xl">Suivi des enseignants</h2>
                            <p class="admin-teachers-hero__summary mt-3 max-w-2xl text-sm leading-6 text-white/80">
                                Validez les candidatures, ouvrez les dossiers et consultez les enseignants actifs.
                            </p>
                        </div>
                        <div class="admin-teachers-hero__badge">
                            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                            <span><?php echo e($formatNumber($pendingCount)); ?> candidature(s) à traiter</span>
                        </div>
                    </div>
                </section>

                <section class="admin-teacher-tabs">
                    <a href="<?php echo e($buildTabUrl('applications')); ?>" class="<?php echo e($currentTab === 'applications' ? 'admin-teacher-tab admin-teacher-tab--active' : 'admin-teacher-tab'); ?>">
                        <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                        Demandes en attente
                        <span><?php echo e($formatNumber($pendingCount)); ?></span>
                    </a>
                    <a href="<?php echo e($buildTabUrl('teachers')); ?>" class="<?php echo e($currentTab === 'teachers' ? 'admin-teacher-tab admin-teacher-tab--active' : 'admin-teacher-tab'); ?>">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        Enseignants actifs
                        <span><?php echo e($formatNumber($teacherDirectoryCount)); ?></span>
                    </a>
                </section>

                <?php if ($currentTab === 'applications'): ?>
                    <section class="space-y-4">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-lg font-bold inline-flex items-center gap-2"><i class="fas fa-file-signature admin-inline-accent admin-inline-accent--warning" aria-hidden="true"></i>Candidatures enseignants</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Chaque rejet doit inclure un motif clair envoyé au candidat.</p>
                            </div>
                            <a href="<?php echo e($buildTabUrl('teachers')); ?>" class="admin-shell-button admin-shell-button--secondary">
                                <i class="fas fa-users" aria-hidden="true"></i>
                                Voir les enseignants
                            </a>
                        </div>

                        <?php if ($pendingApplications === []): ?>
                            <div class="admin-teacher-empty rounded-3xl">
                                <i class="fas fa-inbox" aria-hidden="true"></i>
                                <p>Aucune candidature en attente.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($pendingApplications as $application): ?>
                                    <?php
                                    $appId = (int) ($application['id'] ?? 0);
                                    if ($appId < 1) {
                                        continue;
                                    }
                                    $fullName = trim((string) ($application['first_name'] ?? '') . ' ' . (string) ($application['last_name'] ?? ''));
                                    if ($fullName === '') {
                                        $fullName = 'Candidat #' . $appId;
                                    }
                                    $email = trim((string) ($application['email'] ?? ''));
                                    $title = $normalizeText((string) ($application['title'] ?? ($application['professional_title'] ?? '')));
                                    $bio = $truncateText((string) ($application['bio'] ?? ($application['short_bio'] ?? '')), 260);
                                    $teachingExperience = $normalizeText((string) ($application['teaching_experience'] ?? ''));
                                    $cvUrl = $resolveUrl((string) ($application['cv_path'] ?? ($application['profile_cv_path'] ?? '')));
                                    $portfolioUrl = $resolveUrl((string) ($application['portfolio_url'] ?? ''));
                                    $linkedinUrl = $resolveUrl((string) ($application['linkedin_url'] ?? ''));
                                    ?>
                                    <article class="admin-teacher-application-card rounded-3xl p-5 hover-lift">
                                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto]">
                                            <div class="min-w-0">
                                                <div class="flex items-start gap-4">
                                                    <span class="admin-teacher-avatar"><?php echo e($initials($fullName)); ?></span>
                                                    <div class="min-w-0">
                                                        <h3 class="text-xl font-bold text-slate-950 dark:text-white"><?php echo e($fullName); ?></h3>
                                                        <p class="mt-1 flex flex-wrap gap-2 text-sm text-slate-500 dark:text-slate-400">
                                                            <?php if ($email !== ''): ?>
                                                                <span class="inline-flex items-center gap-1.5"><i class="fas fa-envelope" aria-hidden="true"></i><?php echo e($email); ?></span>
                                                            <?php endif; ?>
                                                            <span class="inline-flex items-center gap-1.5"><i class="far fa-calendar-alt" aria-hidden="true"></i>Soumise le <?php echo e($formatDate((string) ($application['created_at'] ?? ''))); ?></span>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    <span class="admin-course-chip admin-course-chip--category"><i class="fas fa-id-badge" aria-hidden="true"></i><?php echo e($title !== '' ? $title : 'Titre non renseigné'); ?></span>
                                                    <?php if ($teachingExperience !== ''): ?>
                                                        <span class="admin-course-chip admin-course-chip--level"><i class="fas fa-user-graduate" aria-hidden="true"></i><?php echo e($teachingExperience); ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($bio !== ''): ?>
                                                    <p class="mt-4 text-sm font-medium leading-6 text-slate-600 dark:text-slate-300"><?php echo e($bio); ?></p>
                                                <?php endif; ?>

                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    <?php if ($cvUrl !== ''): ?>
                                                        <a class="admin-shell-button admin-shell-button--secondary" href="<?php echo e($cvUrl); ?>" target="_blank" rel="noopener">
                                                            <i class="fas fa-file-alt" aria-hidden="true"></i>
                                                            CV
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($portfolioUrl !== ''): ?>
                                                        <a class="admin-shell-button admin-shell-button--secondary" href="<?php echo e($portfolioUrl); ?>" target="_blank" rel="noopener">
                                                            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                                            Portfolio
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($linkedinUrl !== ''): ?>
                                                        <a class="admin-shell-button admin-shell-button--secondary" href="<?php echo e($linkedinUrl); ?>" target="_blank" rel="noopener">
                                                            <i class="fab fa-linkedin" aria-hidden="true"></i>
                                                            LinkedIn
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="admin-teacher-action-strip">
                                                <form method="post" action="<?php echo e(site_url('admin/teacher_applications')); ?>">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="app_id" value="<?php echo e((string) $appId); ?>">
                                                    <input type="hidden" name="page" value="<?php echo e((string) $listPage); ?>">
                                                    <button type="submit" class="admin-shell-button admin-shell-button--primary w-full">
                                                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                        Approuver
                                                    </button>
                                                </form>
                                                <form method="post" action="<?php echo e(site_url('admin/teacher_applications')); ?>" class="grid gap-2">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="app_id" value="<?php echo e((string) $appId); ?>">
                                                    <input type="hidden" name="page" value="<?php echo e((string) $listPage); ?>">
                                                    <textarea name="reason" class="admin-shell-input" rows="3" placeholder="Motif du rejet" required></textarea>
                                                    <button type="submit" class="admin-shell-button admin-shell-button--danger w-full">
                                                        <i class="fas fa-times-circle" aria-hidden="true"></i>
                                                        Rejeter
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <?php
                            $paginationData = $listPagination;
                            $paginationUrlBuilder = $buildListPageUrl;
                            $paginationLabel = 'candidatures';
                            $paginationAriaLabel = 'Pagination des candidatures';
                            require __DIR__ . '/_pagination.php';
                            ?>
                        <?php endif; ?>

                        <?php if ($processedApplications !== []): ?>
                            <details class="admin-shell-card rounded-3xl p-5">
                                <summary class="cursor-pointer text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    <i class="fas fa-history mr-2 text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                    Historique récent des candidatures
                                </summary>
                                <div class="mt-4 overflow-x-auto">
                                    <table class="admin-shell-table">
                                        <thead>
                                            <tr>
                                                <th>Candidat</th>
                                                <th>Statut</th>
                                                <th>Soumission</th>
                                                <th>Décision</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($processedApplications as $application): ?>
                                                <?php
                                                $status = (string) ($application['status'] ?? '');
                                                $meta = $statusMeta($status, $applicationStatusMeta);
                                                $fullName = trim((string) ($application['first_name'] ?? '') . ' ' . (string) ($application['last_name'] ?? ''));
                                                $reason = $truncateText((string) ($application['reason'] ?? ''), 90);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <p class="font-bold text-slate-900 dark:text-white"><?php echo e($fullName !== '' ? $fullName : 'Candidat'); ?></p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e((string) ($application['email'] ?? '')); ?></p>
                                                    </td>
                                                    <td><span class="<?php echo e((string) $meta['class']); ?>"><i class="fas <?php echo e((string) $meta['icon']); ?>" aria-hidden="true"></i><?php echo e((string) $meta['label']); ?></span></td>
                                                    <td class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($formatDateTime((string) ($application['created_at'] ?? ''))); ?></td>
                                                    <td class="text-sm text-slate-600 dark:text-slate-300">
                                                        <?php echo e($formatDateTime((string) ($application['decided_at'] ?? ''))); ?>
                                                        <?php if ($reason !== ''): ?>
                                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Motif : <?php echo e($reason); ?></p>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <section class="space-y-4">
                        <div class="admin-shell-card rounded-3xl p-5">
                            <form method="get" action="<?php echo e(site_url('admin/teacher_applications')); ?>" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center">
                                <input type="hidden" name="tab" value="teachers">
                                <?php if ($selectedTeacherId > 0): ?>
                                    <input type="hidden" name="teacher_id" value="<?php echo e((string) $selectedTeacherId); ?>">
                                <?php endif; ?>
                                <label class="relative block">
                                    <span class="sr-only">Rechercher un enseignant</span>
                                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                                    <input class="admin-shell-input pl-11" type="search" name="q" value="<?php echo e($searchQuery); ?>" placeholder="Rechercher par nom, email ou titre">
                                </label>
                                <button type="submit" class="admin-shell-button admin-shell-button--primary">
                                    <i class="fas fa-filter" aria-hidden="true"></i>
                                    Filtrer
                                </button>
                                <a href="<?php echo e(site_url('admin/teacher_applications?tab=teachers')); ?>" class="admin-shell-button admin-shell-button--secondary">
                                    <i class="fas fa-undo" aria-hidden="true"></i>
                                    Réinitialiser
                                </a>
                            </form>
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-4">
                                <div class="admin-shell-card rounded-3xl p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="admin-shell-section-title">Liste des enseignants</p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Parcourez les profils et ouvrez un dossier.</p>
                                        </div>
                                        <span class="admin-shell-badge admin-shell-badge--info">
                                            <?php echo e($teacherDirectoryCount); ?> profil(s)
                                        </span>
                                    </div>
                                </div>

                                <?php if ($teacherRows === []): ?>
                                    <div class="admin-teacher-empty rounded-3xl">
                                        <i class="fas fa-users-slash" aria-hidden="true"></i>
                                        <p>Aucun enseignant ne correspond à votre recherche.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($teacherRows as $teacher): ?>
                                            <?php
                                            $teacherId = (int) ($teacher['id'] ?? 0);
                                            if ($teacherId < 1) {
                                                continue;
                                            }
                                            $teacherName = trim((string) ($teacher['first_name'] ?? '') . ' ' . (string) ($teacher['last_name'] ?? ''));
                                            if ($teacherName === '') {
                                                $teacherName = 'Enseignant #' . $teacherId;
                                            }
                                            $teacherStatus = strtolower(trim((string) ($teacher['status'] ?? 'active')));
                                            $isSuspended = $teacherStatus === 'suspended';
                                            $profileTitle = $normalizeText((string) ($teacher['professional_title'] ?? ''));
                                            $teacherCoursesCount = max(0, (int) ($teacher['total_courses'] ?? 0));
                                            $teacherRatingCount = max(0, (int) ($teacher['rating_count'] ?? 0));
                                            $teacherRating = max(0.0, min(5.0, (float) ($teacher['avg_rating'] ?? 0)));
                                            $isSelectedTeacher = $teacherId === $selectedTeacherId;
                                            $dossierQuery = ['teacher_id' => $teacherId];
                                            if ($searchQuery !== '') {
                                                $dossierQuery['q'] = $searchQuery;
                                            }
                                            if ($listPage > 1) {
                                                $dossierQuery['page'] = $listPage;
                                            }
                                            ?>
                                            <article class="<?php echo e($isSelectedTeacher ? 'admin-teacher-card admin-teacher-card--selected rounded-3xl p-5 hover-lift' : 'admin-teacher-card rounded-3xl p-5 hover-lift'); ?>">
                                                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_17rem] xl:items-start">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                                            <span class="<?php echo e($isSuspended ? 'admin-teacher-avatar admin-teacher-avatar--muted' : 'admin-teacher-avatar'); ?>"><?php echo e($initials($teacherName)); ?></span>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <h3 class="text-xl font-bold text-slate-950 dark:text-white"><?php echo e($teacherName); ?></h3>
                                                                    <span class="<?php echo e($isSuspended ? 'admin-shell-badge admin-shell-badge--danger' : 'admin-shell-badge admin-shell-badge--info'); ?>">
                                                                        <i class="fas <?php echo e($isSuspended ? 'fa-pause-circle' : 'fa-check-circle'); ?>" aria-hidden="true"></i>
                                                                        <?php echo e($isSuspended ? 'Suspendu' : 'Actif'); ?>
                                                                    </span>
                                                                </div>
                                                                <p class="mt-1 flex flex-wrap gap-2 text-sm text-slate-500 dark:text-slate-400">
                                                                    <span class="inline-flex items-center gap-1.5"><i class="fas fa-envelope" aria-hidden="true"></i><?php echo e((string) ($teacher['email'] ?? '')); ?></span>
                                                                    <span class="inline-flex items-center gap-1.5"><i class="far fa-calendar-alt" aria-hidden="true"></i>Inscrit le <?php echo e($formatDate((string) ($teacher['created_at'] ?? ''))); ?></span>
                                                                </p>
                                                                <div class="mt-3 flex flex-wrap gap-2">
                                                                    <span class="admin-course-chip admin-course-chip--category"><i class="fas fa-id-badge" aria-hidden="true"></i><?php echo e($profileTitle !== '' ? $profileTitle : 'Profil enseignant'); ?></span>
                                                                    <span class="admin-course-chip admin-course-chip--level"><i class="fas fa-book-open" aria-hidden="true"></i><?php echo e($formatNumber($teacherCoursesCount)); ?> cours</span>
                                                                    <span class="admin-course-chip admin-course-chip--level"><i class="fas fa-star" aria-hidden="true"></i><?php echo e($teacherRatingCount > 0 ? number_format($teacherRating, 1, ',', ' ') . '/5' : '0/5'); ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="admin-teacher-card__actions">
                                                        <a href="<?php echo e(site_url('admin/teacher_dossier?' . http_build_query($dossierQuery))); ?>" class="admin-shell-button admin-shell-button--primary w-full">
                                                            <i class="fas fa-folder-open" aria-hidden="true"></i>
                                                            Dossier
                                                        </a>
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <a href="<?php echo e(site_url('admin/courses?tab=approved&teacher_id=' . $teacherId)); ?>" class="admin-shell-button admin-shell-button--secondary">
                                                                <i class="fas fa-book-open" aria-hidden="true"></i>
                                                                Cours
                                                            </a>
                                                            <a href="<?php echo e(site_url('admin/course_insights?teacher_id=' . $teacherId)); ?>" class="admin-shell-button admin-shell-button--secondary">
                                                                <i class="fas fa-chart-line" aria-hidden="true"></i>
                                                                Stats
                                                            </a>
                                                        </div>
                                                        <?php if ($isSuspended): ?>
                                                            <form method="post" action="<?php echo e(site_url('admin/teacher_applications')); ?>">
                                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                <input type="hidden" name="action" value="reactivate_teacher">
                                                                <input type="hidden" name="teacher_id" value="<?php echo e((string) $teacherId); ?>">
                                                                <input type="hidden" name="page" value="<?php echo e((string) $listPage); ?>">
                                                                <input type="hidden" name="q" value="<?php echo e($searchQuery); ?>">
                                                                <button type="submit" class="admin-shell-button admin-shell-button--primary w-full">
                                                                    <i class="fas fa-play" aria-hidden="true"></i>
                                                                    Réactiver
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="post" action="<?php echo e(site_url('admin/teacher_applications')); ?>">
                                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                                <input type="hidden" name="action" value="suspend_teacher">
                                                                <input type="hidden" name="teacher_id" value="<?php echo e((string) $teacherId); ?>">
                                                                <input type="hidden" name="page" value="<?php echo e((string) $listPage); ?>">
                                                                <input type="hidden" name="q" value="<?php echo e($searchQuery); ?>">
                                                                <button type="submit" class="admin-shell-button admin-shell-button--danger w-full">
                                                                    <i class="fas fa-pause" aria-hidden="true"></i>
                                                                    Suspendre
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php
                                    $paginationData = $listPagination;
                                    $paginationUrlBuilder = $buildListPageUrl;
                                    $paginationLabel = 'enseignants';
                                    $paginationAriaLabel = 'Pagination des enseignants';
                                    require __DIR__ . '/_pagination.php';
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
