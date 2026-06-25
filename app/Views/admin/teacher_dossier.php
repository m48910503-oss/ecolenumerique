<?php
$teacher = is_array($teacher ?? null) ? $teacher : null;
$teacherId = max(0, (int) ($teacher['id'] ?? ($teacherId ?? 0)));
$teacherBackUrl = trim((string) ($teacherBackUrl ?? site_url('admin/teacher_applications?tab=teachers')));
$teacherCoursesUrl = trim((string) ($teacherCoursesUrl ?? site_url('admin/courses')));
$teacherStatsUrl = trim((string) ($teacherStatsUrl ?? site_url('admin/course_insights')));
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

$truncateText = static function (string $value, int $limit = 240) use ($normalizeText): string {
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

$statusMeta = static function (string $status, array $map): array {
    $key = strtolower(trim($status));
    return $map[$key] ?? ['label' => $status !== '' ? ucfirst($status) : 'Inconnu', 'class' => 'admin-shell-badge', 'icon' => 'fa-circle'];
};

$teacherStatusMeta = [
    'active' => ['label' => 'Compte actif', 'class' => 'admin-shell-badge admin-shell-badge--info', 'icon' => 'fa-check-circle'],
    'suspended' => ['label' => 'Compte suspendu', 'class' => 'admin-shell-badge admin-shell-badge--danger', 'icon' => 'fa-pause-circle'],
];

$applicationStatusMeta = [
    'pending' => ['label' => 'Demande en attente', 'class' => 'admin-shell-badge admin-shell-badge--warning', 'icon' => 'fa-hourglass-half'],
    'approved' => ['label' => 'Demande approuvée', 'class' => 'admin-shell-badge admin-shell-badge--info', 'icon' => 'fa-check-circle'],
    'rejected' => ['label' => 'Demande rejetée', 'class' => 'admin-shell-badge admin-shell-badge--danger', 'icon' => 'fa-times-circle'],
];

$teacherExperienceLabel = static function (string $value): string {
    return match (strtolower(trim($value))) {
        'regular' => 'Oui, régulièrement',
        'occasional' => 'Oui, occasionnellement',
        'beginner' => 'Non, mais je souhaite commencer',
        default => trim($value) !== '' ? ucfirst($value) : 'Non précisé',
    };
};

$adminNavActive = 'teacher_applications';
$adminTopTitle = 'Fiche enseignant';
$adminTopSubtitle = 'Pilotage pédagogique';
$adminTopBadge = $teacher !== null ? 'Dossier ouvert' : 'Enseignant introuvable';
$adminTopBadgeTone = $teacher !== null ? 'done' : 'pending';
$adminTopSearchAction = site_url('admin/teacher_applications');
$adminTopSearchName = 'q';
$adminTopSearchValue = $searchQuery;
$adminTopSearchPlaceholder = 'Rechercher un enseignant...';
$adminTopSearchHidden = ['tab' => 'teachers'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche enseignant · Administrateur</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/teacher_dossier" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense px-4 py-5 sm:px-6 sm:py-6 space-y-5">
                <?php if ($teacher === null): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <p class="inline-flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            Cette fiche enseignant est introuvable.
                        </p>
                    </div>

                    <section class="admin-teacher-empty rounded-3xl">
                        <i class="fas fa-user-graduate" aria-hidden="true"></i>
                        <div class="space-y-3">
                            <p>Impossible d’ouvrir ce dossier.</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <a href="<?php echo e($teacherBackUrl); ?>" class="admin-shell-button admin-shell-button--primary">
                                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                    Retour aux enseignants
                                </a>
                                <a href="<?php echo e(site_url('admin/courses')); ?>" class="admin-shell-button admin-shell-button--secondary">
                                    <i class="fas fa-book-open" aria-hidden="true"></i>
                                    Cours validés
                                </a>
                            </div>
                        </div>
                    </section>
                <?php else: ?>
                    <?php
                    $teacherDisplayName = trim((string) ($teacher['first_name'] ?? '') . ' ' . (string) ($teacher['last_name'] ?? ''));
                    if ($teacherDisplayName === '') {
                        $teacherDisplayName = 'Enseignant #' . $teacherId;
                    }
                    $teacherEmail = trim((string) ($teacher['email'] ?? ''));
                    $teacherAvatar = $initials($teacherDisplayName);
                    $teacherStatus = strtolower(trim((string) ($teacher['status'] ?? 'active')));
                    $teacherStatusInfo = $statusMeta($teacherStatus, $teacherStatusMeta);
                    $applicationStatus = strtolower(trim((string) ($teacher['application_status'] ?? '')));
                    $applicationStatusInfo = $statusMeta($applicationStatus, $applicationStatusMeta);
                    $teacherTitle = $normalizeText((string) ($teacher['application_title'] ?? ($teacher['professional_title'] ?? '')));
                    $teacherBio = $truncateText((string) ($teacher['application_bio'] ?? ($teacher['short_bio'] ?? '')), 300);
                    $teacherExperience = $teacherExperienceLabel((string) ($teacher['application_teaching_experience'] ?? ''));
                    $teacherCreatedAt = $formatDateTime((string) ($teacher['created_at'] ?? ''));
                    $teacherUpdatedAt = $formatDateTime((string) ($teacher['updated_at'] ?? ''));
                    $teacherApplicationCreatedAt = $formatDateTime((string) ($teacher['application_created_at'] ?? ''));
                    $teacherApplicationDecidedAt = $formatDateTime((string) ($teacher['application_decided_at'] ?? ''));
                    $teacherApplicationReason = $truncateText((string) ($teacher['application_reason'] ?? ''), 220);
                    $teacherCvUrl = $resolveUrl((string) ($teacher['application_cv_path'] ?? ($teacher['profile_cv_path'] ?? '')));
                    $teacherPortfolioUrl = $resolveUrl((string) ($teacher['portfolio_url'] ?? ''));
                    $teacherLinkedinUrl = $resolveUrl((string) ($teacher['linkedin_url'] ?? ''));
                    $teacherApprovedCourses = max(0, (int) ($teacher['approved_courses'] ?? 0));
                    $teacherStudents = max(0, (int) ($teacher['enrolled_students'] ?? 0));
                    $teacherRevenue = max(0, (int) ($teacher['revenue_xof'] ?? 0));
                    $teacherRatingAverage = max(0.0, min(5.0, (float) ($teacher['avg_rating'] ?? 0.0)));
                    $teacherRatingCount = max(0, (int) ($teacher['rating_count'] ?? 0));
                    ?>

                    <section class="admin-teacher-dossier rounded-3xl p-5 xl:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="admin-teacher-avatar"><?php echo e($teacherAvatar); ?></span>
                                <div class="min-w-0">
                                    <p class="admin-shell-section-title inline-flex items-center gap-2">
                                        <i class="fas fa-user-tie admin-inline-accent admin-inline-accent--info" aria-hidden="true"></i>Fiche enseignant
                                    </p>
                                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-[2rem]"><?php echo e($teacherDisplayName); ?></h2>
                                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                        Dossier administratif, pièces justificatives et accès direct aux cours validés de cet enseignant.
                                    </p>
                                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                        <span class="<?php echo e((string) $teacherStatusInfo['class']); ?>">
                                            <i class="fas <?php echo e((string) $teacherStatusInfo['icon']); ?>" aria-hidden="true"></i>
                                            <?php echo e((string) $teacherStatusInfo['label']); ?>
                                        </span>
                                        <span class="<?php echo e((string) $applicationStatusInfo['class']); ?>">
                                            <i class="fas <?php echo e((string) $applicationStatusInfo['icon']); ?>" aria-hidden="true"></i>
                                            <?php echo e((string) $applicationStatusInfo['label']); ?>
                                        </span>
                                        <span class="admin-shell-badge admin-shell-badge--info">
                                            <i class="fas fa-book-open" aria-hidden="true"></i>
                                            <?php echo e($formatNumber($teacherApprovedCourses)); ?> cours validés
                                        </span>
                                        <span class="admin-shell-badge admin-shell-badge--info">
                                            <i class="fas fa-users" aria-hidden="true"></i>
                                            <?php echo e($formatNumber($teacherStudents)); ?> inscrits
                                        </span>
                                        <span class="admin-shell-badge admin-shell-badge--info">
                                            <i class="fas fa-wallet" aria-hidden="true"></i>
                                            <?php echo e($formatMoney($teacherRevenue)); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                                <a href="<?php echo e($teacherCoursesUrl); ?>" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-book-open" aria-hidden="true"></i>
                                    Cours validés
                                </a>
                                <a href="<?php echo e($teacherStatsUrl); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                                    Statistiques
                                </a>
                                <a href="<?php echo e($teacherBackUrl); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                    Retour
                                </a>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-4 xl:grid-cols-2">
                        <article class="admin-teacher-dossier__panel space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="admin-teacher-dossier__eyebrow">Profil</p>
                                    <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">Présentation et parcours</h3>
                                </div>
                                <span class="admin-shell-badge admin-shell-badge--info">
                                    <i class="fas fa-id-badge" aria-hidden="true"></i>
                                    <?php echo e($teacherTitle !== '' ? $teacherTitle : 'Titre non renseigné'); ?>
                                </span>
                            </div>

                            <?php if ($teacherBio !== ''): ?>
                                <p class="text-sm leading-6 text-slate-700 dark:text-slate-200"><?php echo e($teacherBio); ?></p>
                            <?php else: ?>
                                <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">Aucune présentation n’a été renseignée par l’enseignant.</p>
                            <?php endif; ?>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Expérience</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($teacherExperience); ?></p>
                                </div>
                                <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Profil lié</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($teacherCreatedAt !== '-' ? $teacherCreatedAt : '-'); ?></p>
                                </div>
                            </div>
                        </article>

                        <article class="admin-teacher-dossier__panel space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="admin-teacher-dossier__eyebrow">Dossier</p>
                                    <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">Validation et décision</h3>
                                </div>
                                <span class="<?php echo e((string) $applicationStatusInfo['class']); ?>">
                                    <i class="fas <?php echo e((string) $applicationStatusInfo['icon']); ?>" aria-hidden="true"></i>
                                    <?php echo e((string) $applicationStatusInfo['label']); ?>
                                </span>
                            </div>

                            <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-slate-500 dark:text-slate-400">Soumise le</span>
                                    <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($teacherApplicationCreatedAt !== '-' ? $teacherApplicationCreatedAt : '-'); ?></span>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-slate-500 dark:text-slate-400">Décision le</span>
                                    <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($teacherApplicationDecidedAt !== '-' ? $teacherApplicationDecidedAt : 'En cours'); ?></span>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-slate-500 dark:text-slate-400">Compte</span>
                                    <span class="font-semibold text-slate-900 dark:text-white"><?php echo e((string) $teacherStatusInfo['label']); ?></span>
                                </div>
                            </div>

                            <?php if ($applicationStatus === 'rejected' && $teacherApplicationReason !== ''): ?>
                                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/35 dark:text-rose-100">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-600 dark:text-rose-300">Motif du refus</p>
                                    <p class="mt-2 leading-6"><?php echo e($teacherApplicationReason); ?></p>
                                </div>
                            <?php elseif ($applicationStatus !== ''): ?>
                                <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Lecture rapide</p>
                                    <p class="mt-2 leading-6">
                                        <?php echo e($applicationStatus === 'approved'
                                            ? 'Le dossier a été validé et le compte est prêt à être consulté.'
                                            : 'Le dossier reste accessible pour contrôle et suivi administratif.'); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </article>

                        <article class="admin-teacher-dossier__panel space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="admin-teacher-dossier__eyebrow">Pièces du dossier</p>
                                    <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">Documents disponibles</h3>
                                </div>
                                <span class="admin-shell-badge admin-shell-badge--info">
                                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                                    Vérification
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <?php if ($teacherCvUrl !== ''): ?>
                                    <a class="admin-shell-button admin-shell-button--secondary" href="<?php echo e($teacherCvUrl); ?>" target="_blank" rel="noopener">
                                        <i class="fas fa-file-alt" aria-hidden="true"></i>
                                        CV
                                    </a>
                                <?php endif; ?>
                                <?php if ($teacherPortfolioUrl !== ''): ?>
                                    <a class="admin-shell-button admin-shell-button--secondary" href="<?php echo e($teacherPortfolioUrl); ?>" target="_blank" rel="noopener">
                                        <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                        Portfolio
                                    </a>
                                <?php endif; ?>
                                <?php if ($teacherLinkedinUrl !== ''): ?>
                                    <a class="admin-shell-button admin-shell-button--secondary" href="<?php echo e($teacherLinkedinUrl); ?>" target="_blank" rel="noopener">
                                        <i class="fab fa-linkedin" aria-hidden="true"></i>
                                        LinkedIn
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php if ($teacherCvUrl === '' && $teacherPortfolioUrl === '' && $teacherLinkedinUrl === ''): ?>
                                <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">Aucune pièce n’est actuellement liée à ce dossier.</p>
                            <?php endif; ?>
                        </article>

                        <article class="admin-teacher-dossier__panel space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="admin-teacher-dossier__eyebrow">Coordonnées</p>
                                    <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">Accès et repères</h3>
                                </div>
                                <span class="admin-shell-badge admin-shell-badge--info">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                    ID #<?php echo e((string) $teacherId); ?>
                                </span>
                            </div>

                            <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-slate-500 dark:text-slate-400"><i class="fas fa-envelope mr-2" aria-hidden="true"></i>Email</span>
                                    <span class="min-w-0 text-right font-semibold text-slate-900 dark:text-white"><?php echo e($teacherEmail !== '' ? $teacherEmail : '-'); ?></span>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-slate-500 dark:text-slate-400"><i class="far fa-calendar-alt mr-2" aria-hidden="true"></i>Inscrit le</span>
                                    <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($teacherCreatedAt !== '-' ? $teacherCreatedAt : '-'); ?></span>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-slate-500 dark:text-slate-400"><i class="fas fa-sync-alt mr-2" aria-hidden="true"></i>Mise à jour</span>
                                    <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($teacherUpdatedAt !== '-' ? $teacherUpdatedAt : '-'); ?></span>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <span class="text-slate-500 dark:text-slate-400"><i class="fas fa-star mr-2" aria-hidden="true"></i>Note moyenne</span>
                                    <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($teacherRatingCount > 0 ? number_format($teacherRatingAverage, 1, ',', ' ') . ' / 5' : 'Aucun avis'); ?></span>
                                </div>
                            </div>
                        </article>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
