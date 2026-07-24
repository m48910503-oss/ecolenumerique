<?php
$course = is_array($course ?? null) ? $course : [];
$stats = is_array($stats ?? null) ? $stats : [];
$updatedAt = trim((string) ($updatedAt ?? ''));
$mode = strtolower(trim((string) ($mode ?? 'course')));
$teacherMode = $mode === 'teacher';
$teacher = is_array($teacher ?? null) ? $teacher : [];
$teacherCourses = is_array($teacherCourses ?? null) ? $teacherCourses : [];
$teacherStats = is_array($teacherStats ?? null) ? $teacherStats : [];

$courseId = max(0, (int) ($course['id'] ?? 0));
$courseTitle = trim((string) ($course['title'] ?? 'Cours approuvé'));
$teacherName = trim((string) ($course['teacher_first_name'] ?? '') . ' ' . (string) ($course['teacher_last_name'] ?? ''));
$categoryName = trim((string) ($course['category_name'] ?? 'Non renseignée'));
$description = trim((string) strip_tags(html_entity_decode((string) ($course['description'] ?? ''), ENT_QUOTES, 'UTF-8')));
$coverPath = trim((string) ($course['cover_path'] ?? ''));
$previewUrl = site_url('admin/course_preview?id=' . $courseId);
$backUrl = site_url('admin/courses');
$durationHours = is_numeric($course['duration_hours'] ?? null) ? (float) $course['duration_hours'] : 0.0;
$priceXof = max(0, (int) ($course['price_xof'] ?? 0));
$levelRaw = trim((string) ($course['level'] ?? ''));
$chartRows = is_array($stats['monthly_enrollments'] ?? null) ? array_values($stats['monthly_enrollments']) : [];

$fmtMoney = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ') . ' F CFA';
};

$fmtNumber = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ');
};

$fmtDateTime = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '-';
    }
    try {
        return (new DateTimeImmutable($value))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $value;
    }
};

$resolveMediaUrl = static function (string $path): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $path) === 1) {
        return $path;
    }

    return site_url(ltrim($path, '/'));
};

$levelLabel = static function (string $level): string {
    return match (strtolower(trim($level))) {
        'beginner' => 'Débutant',
        'intermediate' => 'Intermédiaire',
        'advanced' => 'Avancé',
        default => trim($level) !== '' ? ucfirst($level) : 'Non renseigné',
    };
};

$ratingLabel = static function (float $average, int $count): string {
    if ($count <= 0) {
        return 'Aucun avis';
    }

    return number_format(max(0, min(5, $average)), 1, ',', ' ') . ' / 5';
};

$descriptionPreview = $description !== '' ? $description : 'Aucune description renseignée pour ce cours.';
if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($descriptionPreview) > 260) {
    $descriptionPreview = rtrim(mb_substr($descriptionPreview, 0, 257)) . '...';
} elseif (strlen($descriptionPreview) > 260) {
    $descriptionPreview = rtrim(substr($descriptionPreview, 0, 257)) . '...';
}

$coverUrl = $resolveMediaUrl($coverPath);
$durationLabel = $durationHours > 0 ? format_duration_hours_hm($durationHours) : 'Non renseignée';
$levelLabelText = $levelLabel($levelRaw);
$students = max(0, (int) ($stats['students'] ?? 0));
$paidUnits = max(0, (int) ($stats['paid_units'] ?? 0));
$totalRevenue = max(0, (int) ($stats['revenue_total_xof'] ?? 0));
$directRevenue = max(0, (int) ($stats['revenue_direct_xof'] ?? 0));
$promoRevenue = max(0, (int) ($stats['revenue_promo_xof'] ?? 0));
$enterpriseRevenue = max(0, (int) ($stats['revenue_enterprise_xof'] ?? 0));
$directUnits = max(0, (int) ($stats['units_direct'] ?? 0));
$promoUnits = max(0, (int) ($stats['units_promo'] ?? 0));
$enterpriseUnits = max(0, (int) ($stats['units_enterprise'] ?? 0));
$ratingAverage = max(0.0, min(5.0, (float) ($stats['avg_rating'] ?? 0.0)));
$ratingCount = max(0, (int) ($stats['rating_count'] ?? 0));
$completionRate = max(0, min(100, (int) ($stats['completion_rate'] ?? 0)));
$enrollmentChannels = is_array($stats['enrollment_channels'] ?? null) ? $stats['enrollment_channels'] : [];
$channelTotal = max(0, (int) ($enrollmentChannels['total'] ?? $students));
$channelDirect = max(0, (int) ($enrollmentChannels['direct'] ?? 0));
$channelPromo = max(0, (int) ($enrollmentChannels['promo'] ?? 0));
$channelEnterprise = max(0, (int) ($enrollmentChannels['enterprise'] ?? 0));
$teacherId = max(0, (int) ($teacher['id'] ?? 0));
$teacherDisplayName = trim((string) ($teacher['first_name'] ?? '') . ' ' . (string) ($teacher['last_name'] ?? ''));
if ($teacherDisplayName === '') {
    $teacherDisplayName = 'Enseignant';
}
$teacherEmail = trim((string) ($teacher['email'] ?? ''));
$teacherAvatar = static function (string $firstName, string $lastName): string {
    $initials = trim(mb_substr(trim($firstName), 0, 1) . mb_substr(trim($lastName), 0, 1));
    return $initials !== '' ? mb_strtoupper($initials) : 'EN';
};
$teacherStatsApprovedCourses = max(0, (int) ($teacherStats['approved_courses'] ?? count($teacherCourses)));
$teacherStatsStudents = max(0, (int) ($teacherStats['students'] ?? 0));
$teacherStatsPaidUnits = max(0, (int) ($teacherStats['paid_units'] ?? 0));
$teacherStatsRevenue = max(0, (int) ($teacherStats['revenue_total_xof'] ?? 0));
$teacherStatsRatingAverage = max(0.0, min(5.0, (float) ($teacherStats['avg_rating'] ?? 0.0)));
$teacherStatsRatingCount = max(0, (int) ($teacherStats['rating_count'] ?? 0));
$teacherStatsCompletion = max(0, min(100, (int) ($teacherStats['completion_rate'] ?? 0)));
$teacherStatsChannels = is_array($teacherStats['enrollment_channels'] ?? null) ? $teacherStats['enrollment_channels'] : [];
$teacherStatsChannelTotal = max(0, (int) ($teacherStatsChannels['total'] ?? 0));
$teacherStatsChannelDirect = max(0, (int) ($teacherStatsChannels['direct'] ?? 0));
$teacherStatsChannelPromo = max(0, (int) ($teacherStatsChannels['promo'] ?? 0));
$teacherStatsChannelEnterprise = max(0, (int) ($teacherStatsChannels['enterprise'] ?? 0));
$teacherChartRows = is_array($teacherStats['monthly_enrollments'] ?? null) ? array_values($teacherStats['monthly_enrollments']) : [];
$channelCards = [
    [
        'label' => 'Total',
        'tone' => 'blue',
        'students' => $channelTotal,
        'units' => $paidUnits,
        'revenue' => $totalRevenue,
    ],
    [
        'label' => 'Direct',
        'tone' => 'green',
        'students' => $channelDirect,
        'units' => $directUnits,
        'revenue' => $directRevenue,
    ],
    [
        'label' => 'Coupon / promo',
        'tone' => 'amber',
        'students' => $channelPromo,
        'units' => $promoUnits,
        'revenue' => $promoRevenue,
    ],
    [
        'label' => 'Entreprise',
        'tone' => 'indigo',
        'students' => $channelEnterprise,
        'units' => $enterpriseUnits,
        'revenue' => $enterpriseRevenue,
    ],
];

if ($teacherMode) {
    $chartRows = $teacherChartRows;
}

$chartJson = json_encode($chartRows, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
if (!is_string($chartJson)) {
    $chartJson = '[]';
}

$adminNavActive = 'courses';
$adminTopTitle = $teacherMode ? 'Statistiques de l’enseignant' : 'Statistiques du cours';
$adminTopSubtitle = $teacherMode ? 'Cours validés et performance' : 'Cours approuvé';
$adminTopBadge = $teacherMode ? ($teacherStatsApprovedCourses . ' cours validés') : 'Publié';
$adminTopBadgeTone = 'done';
$adminPageScripts = ['admin_course_insights.js'];
$teacherBackUrl = site_url('admin/courses?tab=approved&teacher_id=' . $teacherId);
$teacherDossierUrl = site_url('admin/teacher_dossier?teacher_id=' . $teacherId);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($teacherMode ? 'Statistiques de l’enseignant · Administrateur' : 'Statistiques du cours · Administrateur'); ?></title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin admin-course-insights-page min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body" data-view="admin/course_insights" data-sidebar-desktop-toggle="1">
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
                <?php if ($teacherMode): ?>
                    <?php
                    $teacherStatus = strtolower(trim((string) ($teacher['status'] ?? 'active')));
                    $teacherStatusLabel = $teacherStatus === 'suspended' ? 'Suspendu' : 'Actif';
                    $teacherStatusClass = $teacherStatus === 'suspended'
                        ? 'admin-course-badge admin-course-badge--neutral'
                        : 'admin-course-badge admin-course-badge--active';
                    $teacherInitials = $teacherAvatar((string) ($teacher['first_name'] ?? ''), (string) ($teacher['last_name'] ?? ''));
                    $teacherCoursesEmpty = $teacherCourses === [];
                    ?>
                    <section class="admin-course-panel p-4 sm:p-5 xl:p-6">
                        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="admin-course-badge admin-course-badge--neutral">Enseignant validé</span>
                                    <span class="admin-course-badge admin-course-badge--active"><?php echo e($teacherStatsApprovedCourses); ?> cours validés</span>
                                </div>
                                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br from-primary-600 to-accent-600 text-2xl font-black text-white shadow-lg">
                                        <?php echo e($teacherInitials); ?>
                                    </div>
                                    <div class="min-w-0">
                                        <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-[2rem]"><?php echo e($teacherDisplayName); ?></h2>
                                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                            Vue globale des cours validés de cet enseignant et accès direct à chaque statistique de cours.
                                        </p>
                                        <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                            <span class="admin-course-mini admin-course-mini--teacher"><i class="fas fa-envelope" aria-hidden="true"></i><?php echo e($teacherEmail !== '' ? $teacherEmail : 'Email non renseigné'); ?></span>
                                            <span class="<?php echo e($teacherStatusClass); ?>"><i class="fas fa-circle" aria-hidden="true"></i><?php echo e($teacherStatusLabel); ?></span>
                                            <span class="admin-course-mini admin-course-mini--date"><i class="far fa-calendar-alt" aria-hidden="true"></i>Profil lié depuis <?php echo e($fmtDateTime((string) ($teacher['created_at'] ?? ''))); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row xl:flex-col">
                                <a href="<?php echo e($teacherBackUrl); ?>" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-book-open" aria-hidden="true"></i>
                                    Cours validés
                                </a>
                                <a href="<?php echo e($teacherDossierUrl); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                                    Fiche enseignant
                                </a>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="admin-course-stats-kpi admin-course-stats-kpi--blue">
                            <div class="admin-course-stats-kpi__icon"><i class="fas fa-book-open" aria-hidden="true"></i></div>
                            <p class="admin-course-stats-kpi__label">Cours validés</p>
                            <p class="admin-course-stats-kpi__value"><?php echo e($fmtNumber($teacherStatsApprovedCourses)); ?></p>
                            <p class="admin-course-stats-kpi__meta">Cours publiés par cet enseignant</p>
                        </article>

                        <article class="admin-course-stats-kpi admin-course-stats-kpi--green">
                            <div class="admin-course-stats-kpi__icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                            <p class="admin-course-stats-kpi__label">Inscrits actifs</p>
                            <p class="admin-course-stats-kpi__value"><?php echo e($fmtNumber($teacherStatsStudents)); ?></p>
                            <p class="admin-course-stats-kpi__meta">Apprenants sur l’ensemble des cours</p>
                        </article>

                        <article class="admin-course-stats-kpi admin-course-stats-kpi--amber">
                            <div class="admin-course-stats-kpi__icon"><i class="fas fa-wallet" aria-hidden="true"></i></div>
                            <p class="admin-course-stats-kpi__label">Revenus générés</p>
                            <p class="admin-course-stats-kpi__value"><?php echo e($fmtMoney($teacherStatsRevenue)); ?></p>
                            <p class="admin-course-stats-kpi__meta"><?php echo e($fmtNumber($teacherStatsPaidUnits)); ?> commande(s) payée(s)</p>
                        </article>

                        <article class="admin-course-stats-kpi admin-course-stats-kpi--indigo">
                            <div class="admin-course-stats-kpi__icon"><i class="fas fa-star" aria-hidden="true"></i></div>
                            <p class="admin-course-stats-kpi__label">Note moyenne</p>
                            <p class="admin-course-stats-kpi__value"><?php echo e($teacherStatsRatingCount > 0 ? number_format($teacherStatsRatingAverage, 1, ',', ' ') . ' / 5' : '0 / 5'); ?></p>
                            <p class="admin-course-stats-kpi__meta"><?php echo e($fmtNumber($teacherStatsRatingCount)); ?> avis publiés</p>
                        </article>
                    </section>

                    <section class="grid gap-5 2xl:grid-cols-[minmax(0,1.35fr)_minmax(24rem,0.95fr)]">
                        <section class="admin-course-panel p-4 sm:p-5">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-base font-black text-slate-900 dark:text-white">Évolution des inscriptions</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Volume cumulé sur les six derniers mois</p>
                                </div>
                                <span class="admin-course-badge admin-course-badge--neutral">Données agrégées</span>
                            </div>
                            <div id="admin-course-insights-chart" class="mt-4" data-chart="<?php echo e($chartJson); ?>"></div>
                        </section>

                        <section class="admin-course-panel p-4 sm:p-5">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-base font-black text-slate-900 dark:text-white">Répartition des inscriptions</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Vue par canal d’acquisition</p>
                                </div>
                                <span class="admin-course-badge admin-course-badge--neutral"><?php echo e($fmtNumber($teacherStatsChannelTotal)); ?> au total</span>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <article class="admin-course-channel-card admin-course-channel-card--blue">
                                    <p class="admin-course-channel-card__label">Total</p>
                                    <p class="admin-course-channel-card__revenue"><?php echo e($fmtNumber($teacherStatsChannelTotal)); ?> inscrits</p>
                                </article>
                                <article class="admin-course-channel-card admin-course-channel-card--green">
                                    <p class="admin-course-channel-card__label">Direct</p>
                                    <p class="admin-course-channel-card__revenue"><?php echo e($fmtNumber($teacherStatsChannelDirect)); ?> inscrits</p>
                                </article>
                                <article class="admin-course-channel-card admin-course-channel-card--amber">
                                    <p class="admin-course-channel-card__label">Coupon / promo</p>
                                    <p class="admin-course-channel-card__revenue"><?php echo e($fmtNumber($teacherStatsChannelPromo)); ?> inscrits</p>
                                </article>
                                <article class="admin-course-channel-card admin-course-channel-card--indigo">
                                    <p class="admin-course-channel-card__label">Entreprise</p>
                                    <p class="admin-course-channel-card__revenue"><?php echo e($fmtNumber($teacherStatsChannelEnterprise)); ?> inscrits</p>
                                </article>
                            </div>
                        </section>
                    </section>

                    <section class="admin-course-panel p-4 sm:p-5">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">Cours validés de l’enseignant</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Chaque cours conserve ses propres statistiques détaillées.</p>
                            </div>
                            <span class="admin-course-badge admin-course-badge--neutral"><?php echo e($fmtNumber($teacherStatsApprovedCourses)); ?> cours</span>
                        </div>

                        <div class="mt-4 space-y-3">
                            <?php if ($teacherCoursesEmpty): ?>
                                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
                                    Aucun cours validé pour cet enseignant.
                                </div>
                            <?php else: ?>
                                <?php foreach ($teacherCourses as $teacherCourse): ?>
                                    <?php
                                    $teacherCourseId = (int) ($teacherCourse['id'] ?? 0);
                                    if ($teacherCourseId < 1) {
                                        continue;
                                    }
                                    $teacherCourseTitle = trim((string) ($teacherCourse['title'] ?? 'Cours sans titre'));
                                    $teacherCourseDate = $fmtDateTime((string) ($teacherCourse['created_at'] ?? ''));
                                    $teacherCourseStats = is_array($teacherCourse['stats'] ?? null) ? $teacherCourse['stats'] : [];
                                    $teacherCourseStudents = max(0, (int) ($teacherCourseStats['students'] ?? 0));
                                    $teacherCourseRevenue = max(0, (int) ($teacherCourseStats['revenue_xof'] ?? 0));
                                    $teacherCourseRatingCount = max(0, (int) ($teacherCourseStats['rating_count'] ?? 0));
                                    $teacherCourseRatingAverage = max(0.0, min(5.0, (float) ($teacherCourseStats['avg_rating'] ?? 0.0)));
                                    $teacherCourseCompletion = max(0, min(100, (int) ($teacherCourseStats['completion_rate'] ?? 0)));
                                    $teacherCourseStatsUrl = site_url('admin/course_insights?id=' . $teacherCourseId);
                                    $teacherCoursePreviewUrl = site_url('admin/course_preview?id=' . $teacherCourseId);
                                    ?>
                                    <article class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/60">
                                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white"><?php echo e($teacherCourseTitle); ?></h4>
                                                    <span class="admin-course-badge admin-course-badge--active">Validé</span>
                                                </div>
                                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Créé le <?php echo e($teacherCourseDate !== '-' ? $teacherCourseDate : 'date inconnue'); ?></p>
                                                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                                                    <span class="admin-course-mini admin-course-mini--teacher"><i class="fas fa-users" aria-hidden="true"></i><?php echo e($fmtNumber($teacherCourseStudents)); ?> inscrits</span>
                                                    <span class="admin-course-mini admin-course-mini--date"><i class="fas fa-wallet" aria-hidden="true"></i><?php echo e($fmtMoney($teacherCourseRevenue)); ?></span>
                                                    <span class="admin-course-mini admin-course-mini--category"><i class="fas fa-star" aria-hidden="true"></i><?php echo e($teacherCourseRatingCount > 0 ? number_format($teacherCourseRatingAverage, 1, ',', ' ') . ' / 5' : 'Aucun avis'); ?></span>
                                                    <span class="admin-course-mini admin-course-mini--level"><i class="fas fa-chart-line" aria-hidden="true"></i><?php echo e($teacherCourseCompletion); ?>% complété</span>
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                                                <a href="<?php echo e($teacherCourseStatsUrl); ?>" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2">
                                                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                                                    Statistiques du cours
                                                </a>
                                                <a href="<?php echo e($teacherCoursePreviewUrl); ?>" target="_blank" rel="noopener noreferrer" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                                    <i class="fas fa-up-right-from-square" aria-hidden="true"></i>
                                                    Aperçu
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php else: ?>
                <section class="admin-course-panel p-4 sm:p-5 xl:p-6">
                    <div class="admin-course-header-grid">
                        <div class="admin-course-cover-frame">
                            <?php if ($coverUrl !== ''): ?>
                                <img src="<?php echo e($coverUrl); ?>" alt="" class="h-full w-full object-cover">
                            <?php else: ?>
                                <div class="flex h-full min-h-[8rem] items-center justify-center text-4xl text-slate-400 dark:text-slate-600">
                                    <i class="fas fa-book-open" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="min-w-0">
                            <div class="admin-course-heading-badges">
                                <span class="admin-course-badge admin-course-badge--active">Cours validé</span>
                                <span class="admin-course-badge admin-course-badge--neutral">Analyse de performance</span>
                            </div>
                            <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-[2rem]"><?php echo e($courseTitle); ?></h2>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e($descriptionPreview); ?></p>

                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                <span class="admin-course-mini admin-course-mini--teacher"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i><?php echo e($teacherName !== '' ? $teacherName : 'Non renseigné'); ?></span>
                                <span class="admin-course-mini admin-course-mini--category"><i class="fas fa-layer-group" aria-hidden="true"></i><?php echo e($categoryName); ?></span>
                                <span class="admin-course-mini admin-course-mini--date"><i class="far fa-calendar-alt" aria-hidden="true"></i>Créé le <?php echo e($fmtDateTime((string) ($course['created_at'] ?? ''))); ?></span>
                                <span class="admin-course-chip admin-course-chip--duration"><i class="fas fa-hourglass-half" aria-hidden="true"></i><?php echo e($durationLabel); ?></span>
                                <span class="admin-course-chip admin-course-chip--price"><i class="fas fa-money-bill-wave" aria-hidden="true"></i><?php echo e($fmtMoney($priceXof)); ?></span>
                                <span class="admin-course-chip admin-course-chip--level"><i class="fas fa-chart-bar" aria-hidden="true"></i><?php echo e($levelLabelText); ?></span>
                            </div>
                        </div>

                        <aside class="admin-course-header-actions">
                            <a href="<?php echo e($previewUrl); ?>" target="_blank" rel="noopener noreferrer" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2">
                                <i class="fas fa-up-right-from-square" aria-hidden="true"></i>
                                Aperçu du cours
                            </a>
                            <a href="<?php echo e($backUrl); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                Retour aux cours
                            </a>
                            <div class="admin-course-summary-panel">
                                <div class="admin-course-summary-item">
                                    <span>Mise à jour</span>
                                    <strong><?php echo e($updatedAt !== '' ? $updatedAt : '-'); ?></strong>
                                </div>
                                <div class="admin-course-summary-item">
                                    <span>Inscrits actifs</span>
                                    <strong><?php echo e($fmtNumber($students)); ?></strong>
                                </div>
                                <div class="admin-course-summary-item">
                                    <span>Ventes payées</span>
                                    <strong><?php echo e($fmtNumber($paidUnits)); ?></strong>
                                </div>
                                <div class="admin-course-summary-item">
                                    <span>Total généré</span>
                                    <strong><?php echo e($fmtMoney($totalRevenue)); ?></strong>
                                </div>
                            </div>
                        </aside>
                    </div>
                </section>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="admin-course-stats-kpi admin-course-stats-kpi--blue">
                        <div class="admin-course-stats-kpi__icon"><i class="fas fa-user-graduate" aria-hidden="true"></i></div>
                        <p class="admin-course-stats-kpi__label">Inscrits actifs</p>
                        <p class="admin-course-stats-kpi__value"><?php echo e($fmtNumber($students)); ?></p>
                        <p class="admin-course-stats-kpi__meta">Apprenants actuellement actifs</p>
                    </article>

                    <article class="admin-course-stats-kpi admin-course-stats-kpi--green">
                        <div class="admin-course-stats-kpi__icon"><i class="fas fa-wallet" aria-hidden="true"></i></div>
                        <p class="admin-course-stats-kpi__label">Montant total généré</p>
                        <p class="admin-course-stats-kpi__value"><?php echo e($fmtMoney($totalRevenue)); ?></p>
                        <p class="admin-course-stats-kpi__meta"><?php echo e($fmtNumber($paidUnits)); ?> commande(s) comptabilisées</p>
                    </article>

                    <article class="admin-course-stats-kpi admin-course-stats-kpi--amber">
                        <div class="admin-course-stats-kpi__icon"><i class="fas fa-star" aria-hidden="true"></i></div>
                        <p class="admin-course-stats-kpi__label">Note moyenne</p>
                        <p class="admin-course-stats-kpi__value"><?php echo e($ratingLabel($ratingAverage, $ratingCount)); ?></p>
                        <p class="admin-course-stats-kpi__meta"><?php echo e($fmtNumber($ratingCount)); ?> avis publiés</p>
                    </article>

                    <article class="admin-course-stats-kpi admin-course-stats-kpi--indigo">
                        <div class="admin-course-stats-kpi__icon"><i class="fas fa-circle-check" aria-hidden="true"></i></div>
                        <p class="admin-course-stats-kpi__label">Taux de complétion</p>
                        <p class="admin-course-stats-kpi__value"><?php echo e($fmtNumber($completionRate)); ?>%</p>
                        <p class="admin-course-stats-kpi__meta">Basé sur les leçons terminées</p>
                    </article>
                </section>

                <section class="grid gap-5 2xl:grid-cols-[minmax(0,1.3fr)_minmax(22rem,0.95fr)]">
                    <section class="admin-course-panel p-4 sm:p-5">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">Évolution des inscriptions</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Lecture directe sur les six derniers mois</p>
                            </div>
                            <span class="admin-course-badge admin-course-badge--neutral">Données réelles</span>
                        </div>
                        <div id="admin-course-insights-chart" class="mt-4" data-chart="<?php echo e($chartJson); ?>"></div>
                    </section>

                    <section class="admin-course-panel p-4 sm:p-5">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">Acquisition et revenus</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Inscrits, ventes et chiffre par canal</p>
                            </div>
                            <span class="admin-course-badge admin-course-badge--neutral">Inscrits · Total · Direct · Coupon / promo · Entreprise</span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <?php foreach ($channelCards as $card): ?>
                                <article class="admin-course-channel-card admin-course-channel-card--<?php echo e((string) $card['tone']); ?>">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="admin-course-channel-card__label"><?php echo e((string) $card['label']); ?></p>
                                            <p class="admin-course-channel-card__revenue"><?php echo e($fmtMoney((int) $card['revenue'])); ?></p>
                                        </div>
                                        <span class="admin-course-badge admin-course-badge--neutral"><?php echo e($fmtNumber((int) $card['units'])); ?> vente(s)</span>
                                    </div>
                                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                        <div class="admin-course-channel-card__metric">
                                            <span>Inscrits</span>
                                            <strong><?php echo e($fmtNumber((int) $card['students'])); ?></strong>
                                        </div>
                                        <div class="admin-course-channel-card__metric">
                                            <span>Ventes</span>
                                            <strong><?php echo e($fmtNumber((int) $card['units'])); ?></strong>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </section>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
