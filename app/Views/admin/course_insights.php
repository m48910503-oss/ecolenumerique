<?php
$teacherFilters = is_array($teacherFilters ?? null) ? $teacherFilters : [];
$selectedTeacherId = max(0, (int) ($selectedTeacherId ?? 0));
$selectedTeacher = is_array($selectedTeacher ?? null) ? $selectedTeacher : null;
$searchQuery = trim((string) ($searchQuery ?? ''));
$courseRows = is_array($courseRows ?? null) ? $courseRows : [];
$summary = is_array($summary ?? null) ? $summary : [];
$updatedAt = trim((string) ($updatedAt ?? ''));
 
$totalCourses = max(0, (int) ($summary['course_count'] ?? 0));
$coursesWithRevenue = max(0, (int) ($summary['courses_with_revenue_count'] ?? 0));
$totalEnrollments = max(0, (int) ($summary['enrolled_count'] ?? 0));
$totalPaidUnits = max(0, (int) ($summary['paid_units'] ?? 0));
$totalRevenue = max(0, (int) ($summary['revenue_total_xof'] ?? 0));
$directRevenue = max(0, (int) ($summary['revenue_direct_xof'] ?? 0));
$promoRevenue = max(0, (int) ($summary['revenue_promo_xof'] ?? 0));
$enterpriseRevenue = max(0, (int) ($summary['revenue_enterprise_xof'] ?? 0));
$directUnits = max(0, (int) ($summary['units_direct'] ?? 0));
$promoUnits = max(0, (int) ($summary['units_promo'] ?? 0));
$enterpriseUnits = max(0, (int) ($summary['units_enterprise'] ?? 0));

$isFilterActive = $selectedTeacherId > 0 || $searchQuery !== '';

$adminNavActive = 'course_insights';
$adminTopTitle = 'Cours approuvés';
$adminTopSubtitle = 'Modération';
$adminTopBadge = (string) $totalCourses . ' cours';
$adminTopBadgeTone = $totalCourses > 0 ? 'active' : 'pending';
$adminTopActions = [
    ['label' => 'Cours à valider', 'url' => site_url('admin/courses'), 'tone' => 'neutral'],
    ['label' => 'Transactions et retraits', 'url' => site_url('admin/sales'), 'tone' => 'neutral'],
];

$fmtMoney = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ') . ' F CFA';
};

$fmtDate = static function (string $dbDate): string {
    if ($dbDate === null || trim($dbDate) === '') {
        return '-';
    }
    $ts = strtotime($dbDate);
    if ($ts === false) {
        return (string) $dbDate;
    }
    return date('d/m/Y H:i', $ts);
};

$buildFilterUrl = static function (int $teacherId, string $searchQuery): string {
    $params = [];
    if ($teacherId > 0) {
        $params['teacher_id'] = $teacherId;
    }
    if ($searchQuery !== '') {
        $params['q'] = $searchQuery;
    }
    $query = http_build_query($params);
    return site_url('admin/course_insights') . ($query !== '' ? ('?' . $query) : '');
};
?>
<!doctype html> 
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrateur - Cours approuvés</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body" data-view="admin/course_insights" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense space-y-5 px-4 py-5 sm:px-6 sm:py-6">
                <section class="admin-shell-hero rounded-3xl p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="max-w-3xl">
                            <p class="admin-shell-section-title inline-flex items-center gap-2"><i class="fas fa-chart-line" aria-hidden="true"></i>Pilotage des cours</p>
                            <h2 class="mt-2 inline-flex items-center gap-3 text-2xl font-semibold font-display text-slate-900 dark:text-slate-100">
                                <i class="fas fa-book-open text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                Cours approuv&eacute;s et revenus d&eacute;taill&eacute;s
                            </h2>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                Filtrez par enseignant, suivez le nombre d&apos;inscrits, et comparez les revenus directs, coupon/promotion et entreprise.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="admin-dashboard-count admin-dashboard-count--info inline-flex items-center gap-1.5"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i><?php echo e((string) count($teacherFilters)); ?> enseignant(s)</span>
                            <span class="admin-dashboard-count admin-dashboard-count--success inline-flex items-center gap-1.5"><i class="fas fa-money-bill-wave" aria-hidden="true"></i><?php echo e((string) $coursesWithRevenue); ?> cours générant des revenus</span>
                            <?php if ($updatedAt !== ''): ?>
                                <span class="admin-dashboard-count admin-dashboard-count--warning inline-flex items-center gap-1.5"><i class="fas fa-clock" aria-hidden="true"></i>Mis &agrave; jour : <?php echo e($updatedAt); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="admin-shell-panel rounded-3xl p-5">
                    <form method="get" action="<?php echo e(site_url('admin/course_insights')); ?>" class="grid gap-3 md:grid-cols-[1.2fr_1fr_auto_auto] md:items-end">
                        <label class="admin-shell-field">
                            <span class="admin-shell-label inline-flex items-center gap-2"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>Enseignant</span>
                            <select name="teacher_id" class="admin-shell-input">
                                <option value="0">Tous les enseignants</option>
                                <?php foreach ($teacherFilters as $teacher): ?>
                                    <?php $teacherId = (int) ($teacher['teacher_id'] ?? 0); ?>
                                    <option value="<?php echo e((string) $teacherId); ?>" <?php echo $selectedTeacherId === $teacherId ? 'selected' : ''; ?>>
                                        <?php echo e(trim((string) ($teacher['teacher_first_name'] ?? '') . ' ' . (string) ($teacher['teacher_last_name'] ?? ''))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="admin-shell-field">
                            <span class="admin-shell-label inline-flex items-center gap-2"><i class="fas fa-search" aria-hidden="true"></i>Rechercher un cours</span>
                            <input type="search" name="q" value="<?php echo e($searchQuery); ?>" class="admin-shell-input" placeholder="Titre, slug ou nom enseignant">
                        </label>

                        <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2"><i class="fas fa-filter" aria-hidden="true"></i>Filtrer</button>
                        <a href="<?php echo e(site_url('admin/course_insights')); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2"><i class="fas fa-undo" aria-hidden="true"></i>Réinitialiser</a>
                    </form>

                    <?php if ($teacherFilters !== []): ?>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="<?php echo e($buildFilterUrl(0, $searchQuery)); ?>" class="app-segmented-link rounded-full px-3 py-1.5 text-xs font-semibold <?php echo $selectedTeacherId === 0 ? 'app-segmented-link--active' : ''; ?>">
                                <i class="fas fa-layer-group mr-1" aria-hidden="true"></i>Tous
                            </a>
                            <?php foreach ($teacherFilters as $teacher): ?>
                                <?php
                                    $teacherId = (int) ($teacher['teacher_id'] ?? 0);
                                    $teacherName = trim((string) ($teacher['teacher_first_name'] ?? '') . ' ' . (string) ($teacher['teacher_last_name'] ?? ''));
                                    $teacherCourses = max(0, (int) ($teacher['approved_course_count'] ?? 0));
                                ?>
                                <a href="<?php echo e($buildFilterUrl($teacherId, $searchQuery)); ?>" class="app-segmented-link rounded-full px-3 py-1.5 text-xs font-semibold <?php echo $selectedTeacherId === $teacherId ? 'app-segmented-link--active' : ''; ?>">
                                    <i class="fas fa-user-tie mr-1" aria-hidden="true"></i><?php echo e($teacherName !== '' ? $teacherName : ('#' . $teacherId)); ?>
                                    <span class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-200/80 px-1.5 text-[10px] dark:bg-slate-700/80"><?php echo e((string) $teacherCourses); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="app-kpi-card app-kpi-card--blue rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon"><?php echo dashboard_icon_svg('book-open'); ?></span>
                            <span>Cours approuvés</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e((string) $totalCourses); ?></p>
                        <p class="app-kpi-card__meta"><?php echo e((string) $coursesWithRevenue); ?> générant des revenus</p>
                    </article>

                    <article class="app-kpi-card app-kpi-card--sky rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon"><?php echo dashboard_icon_svg('users'); ?></span>
                            <span>Inscrits actifs</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e(number_format($totalEnrollments, 0, ',', ' ')); ?></p>
                        <p class="app-kpi-card__meta"><?php echo e(number_format($totalPaidUnits, 0, ',', ' ')); ?> vente(s) payées</p>
                    </article>

                    <article class="app-kpi-card app-kpi-card--emerald rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon"><?php echo dashboard_icon_svg('wallet'); ?></span>
                            <span>Montant total généré</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($fmtMoney($totalRevenue)); ?></p>
                        <p class="app-kpi-card__meta">Direct : <?php echo e($fmtMoney($directRevenue)); ?></p>
                    </article>

                    <article class="app-kpi-card app-kpi-card--amber rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon"><?php echo dashboard_icon_svg('ticket'); ?></span>
                            <span>Promos / Entreprise</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($fmtMoney($promoRevenue + $enterpriseRevenue)); ?></p>
                        <p class="app-kpi-card__meta">Promo : <?php echo e($fmtMoney($promoRevenue)); ?> &middot; Entreprise : <?php echo e($fmtMoney($enterpriseRevenue)); ?></p>
                    </article>
                </section>

                <?php if ($selectedTeacher !== null): ?>
                    <?php
                        $selectedTeacherName = trim((string) ($selectedTeacher['teacher_first_name'] ?? '') . ' ' . (string) ($selectedTeacher['teacher_last_name'] ?? ''));
                        $selectedTeacherCourses = max(0, (int) ($selectedTeacher['approved_course_count'] ?? 0));
                        $selectedTeacherEnrollments = max(0, (int) ($selectedTeacher['enrolled_students_count'] ?? 0));
                        $selectedTeacherRevenue = max(0, (int) ($selectedTeacher['total_generated_xof'] ?? 0));
                    ?>
                    <section class="admin-shell-card rounded-3xl p-4">
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            Filtre actif : <?php echo e($selectedTeacherName !== '' ? $selectedTeacherName : ('Enseignant #' . $selectedTeacherId)); ?>
                        </p>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                            <?php echo e((string) $selectedTeacherCourses); ?> cours &middot;
                            <?php echo e(number_format($selectedTeacherEnrollments, 0, ',', ' ')); ?> inscrits &middot;
                            <?php echo e($fmtMoney($selectedTeacherRevenue)); ?>
                        </p>
                    </section>
                <?php endif; ?>

                <section class="admin-shell-card rounded-3xl p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="inline-flex items-center gap-2 text-lg font-semibold font-display text-slate-900 dark:text-slate-100"><i class="fas fa-list text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Liste des cours approuvés</h3>
                        <span class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400"><i class="fas fa-table" aria-hidden="true"></i><?php echo e((string) count($courseRows)); ?> ligne(s)</span>
                    </div>

                    <?php if ($courseRows === []): ?>
                        <div class="mt-4 rounded-2xl border border-slate-200/70 bg-slate-50/70 p-4 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-300">
                            <i class="fas fa-info-circle mr-1 text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Aucun cours approuvé ne correspond aux filtres actuels.
                        </div>
                    <?php else: ?>
                        <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200/70 bg-white/80 dark:border-slate-800 dark:bg-slate-950/40">
                            <table class="admin-shell-table min-w-[980px] text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3">Cours</th>
                                        <th class="px-4 py-3">Enseignant</th>
                                        <th class="px-4 py-3 text-right">Inscrits</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                        <th class="px-4 py-3 text-right">Direct</th>
                                        <th class="px-4 py-3 text-right">Coupon / promo</th>
                                        <th class="px-4 py-3 text-right">Entreprise</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courseRows as $row): ?>
                                        <?php
                                            $courseId = (int) ($row['id'] ?? 0);
                                            $teacherName = trim((string) ($row['teacher_first_name'] ?? '') . ' ' . (string) ($row['teacher_last_name'] ?? ''));
                                            $enrolledCount = max(0, (int) ($row['enrolled_count'] ?? 0));
                                            $courseTotalRevenue = max(0, (int) ($row['total_generated_xof'] ?? 0));
                                            $courseDirectRevenue = max(0, (int) ($row['direct_generated_xof'] ?? 0));
                                            $coursePromoRevenue = max(0, (int) ($row['promo_generated_xof'] ?? 0));
                                            $courseEnterpriseRevenue = max(0, (int) ($row['enterprise_generated_xof'] ?? 0));
                                            $courseDirectUnits = max(0, (int) ($row['direct_units'] ?? 0));
                                            $coursePromoUnits = max(0, (int) ($row['promo_units'] ?? 0));
                                            $courseEnterpriseUnits = max(0, (int) ($row['enterprise_units'] ?? 0));
                                        ?>
                                        <tr>
                                            <td class="px-4 py-3 align-top">
                                                <p class="font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($row['title'] ?? 'Cours')); ?></p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Créé le <?php echo e($fmtDate((string) ($row['created_at'] ?? ''))); ?></p>
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-700 dark:text-slate-200">
                                                <?php echo e($teacherName !== '' ? $teacherName : ('#' . (int) ($row['teacher_id'] ?? 0))); ?>
                                            </td>
                                            <td class="px-4 py-3 text-right align-top font-medium"><?php echo e(number_format($enrolledCount, 0, ',', ' ')); ?></td>
                                            <td class="px-4 py-3 text-right align-top font-semibold"><?php echo e($fmtMoney($courseTotalRevenue)); ?></td>
                                            <td class="px-4 py-3 text-right align-top">
                                                <p><?php echo e($fmtMoney($courseDirectRevenue)); ?></p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400"><?php echo e(number_format($courseDirectUnits, 0, ',', ' ')); ?> vente(s)</p>
                                            </td>
                                            <td class="px-4 py-3 text-right align-top">
                                                <p><?php echo e($fmtMoney($coursePromoRevenue)); ?></p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400"><?php echo e(number_format($coursePromoUnits, 0, ',', ' ')); ?> vente(s)</p>
                                            </td>
                                            <td class="px-4 py-3 text-right align-top">
                                                <p><?php echo e($fmtMoney($courseEnterpriseRevenue)); ?></p>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400"><?php echo e(number_format($courseEnterpriseUnits, 0, ',', ' ')); ?> place(s)</p>
                                            </td>
                                            <td class="px-4 py-3 text-right align-top">
                                                <a href="<?php echo e(site_url('admin/course_preview?id=' . $courseId)); ?>" class="admin-shell-button admin-shell-button--secondary" target="_blank" rel="noopener">
                                                    <i class="fas fa-external-link-alt mr-1" aria-hidden="true"></i>Ouvrir
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="admin-shell-card rounded-3xl p-5">
                    <h3 class="inline-flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-slate-100"><i class="fas fa-chart-pie text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Répartition globale des ventes</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <article class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-800 dark:bg-slate-950/45">
                            <p class="inline-flex items-center gap-1.5 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"><i class="fas fa-credit-card" aria-hidden="true"></i>Paiement direct</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100"><?php echo e($fmtMoney($directRevenue)); ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e(number_format($directUnits, 0, ',', ' ')); ?> vente(s)</p>
                        </article>
                        <article class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-800 dark:bg-slate-950/45">
                            <p class="inline-flex items-center gap-1.5 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"><i class="fas fa-ticket-alt" aria-hidden="true"></i>Coupon / promotion</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100"><?php echo e($fmtMoney($promoRevenue)); ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e(number_format($promoUnits, 0, ',', ' ')); ?> vente(s)</p>
                        </article>
                        <article class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 dark:border-slate-800 dark:bg-slate-950/45">
                            <p class="inline-flex items-center gap-1.5 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"><i class="fas fa-building" aria-hidden="true"></i>Canal entreprise</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100"><?php echo e($fmtMoney($enterpriseRevenue)); ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e(number_format($enterpriseUnits, 0, ',', ' ')); ?> place(s)</p>
                        </article>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>

