<?php

$activityFilters = is_array($activityFilters ?? null) ? $activityFilters : [];
$activityEntries = is_array($activityEntries ?? null) ? $activityEntries : [];
$activityTopActors = is_array($activityTopActors ?? null) ? $activityTopActors : [];
$activityModules = is_array($activityModules ?? null) ? $activityModules : [];
$activityCounts = is_array($activityCounts ?? null) ? $activityCounts : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];

$secondaryAdminsUrl = (string) ($secondaryAdminsUrl ?? site_url('admin/secondary_admins'));
$secondaryAdminsCreateUrl = (string) ($secondaryAdminsCreateUrl ?? site_url('admin/secondary_admins/create'));

$searchQuery = trim((string) ($activityFilters['search'] ?? ''));
$fromFilter = trim((string) ($activityFilters['from'] ?? ''));
$toFilter = trim((string) ($activityFilters['to'] ?? ''));
$moduleFilter = trim((string) ($activityFilters['module'] ?? ''));

$totalActivities = max(0, (int) ($activityCounts['total'] ?? count($activityEntries)));
$activeFilterCount = max(0, (int) ($activityCounts['active_filters'] ?? 0));

$paginationPage = max(1, (int) ($pagination['page'] ?? 1));
$paginationTotalPages = max(1, (int) ($pagination['total_pages'] ?? 1));

$buildPageUrl = static function (int $targetPage) use ($searchQuery, $fromFilter, $toFilter, $moduleFilter): string {
    $page = max(1, $targetPage);
    $params = [];
    if ($searchQuery !== '') {
        $params['q'] = $searchQuery;
    }
    if ($fromFilter !== '') {
        $params['from'] = $fromFilter;
    }
    if ($toFilter !== '') {
        $params['to'] = $toFilter;
    }
    if ($moduleFilter !== '') {
        $params['module'] = $moduleFilter;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }

    $query = http_build_query($params);
    return site_url('admin/secondary_admins/activity') . ($query !== '' ? ('?' . $query) : '');
};

$adminNavActive = 'secondary_admin_activity';
$adminTopTitle = 'Activité des admins secondaires';
$adminTopSubtitle = 'Journal d\'activité';
$adminTopBadge = $totalActivities > 0 ? (number_format($totalActivities, 0, ',', ' ') . ' actions') : 'Aucune activité';
$adminTopBadgeTone = $totalActivities > 0 ? 'active' : 'done';
$adminTopSearchAction = site_url('admin/secondary_admins/activity');
$adminTopSearchValue = $searchQuery;
$adminTopSearchPlaceholder = 'Rechercher une activité...';
$adminTopSearchHidden = [];
if ($fromFilter !== '') {
    $adminTopSearchHidden['from'] = $fromFilter;
}
if ($toFilter !== '') {
    $adminTopSearchHidden['to'] = $toFilter;
}
if ($moduleFilter !== '') {
    $adminTopSearchHidden['module'] = $moduleFilter;
}

$adminPageScripts = [];

$icon = static function (string $name): string {
    return function_exists('dashboard_icon_svg') ? (string) dashboard_icon_svg($name) : '';
};

$activityToneClass = static function (string $tone): string {
    return match (strtolower(trim($tone))) {
        'success' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
};
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activité des admins secondaires - Administration</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/secondary_admins_activity" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop" aria-hidden="true">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <section class="rounded-2xl bg-gradient-to-r from-primary-600 to-accent-600 p-6 text-white shadow-glow">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                        <div class="max-w-3xl">
                            <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary-100">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                Gouvernance
                            </p>
                            <h2 class="mt-3 text-2xl font-bold sm:text-3xl">Activité des administrateurs secondaires</h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?php echo e($secondaryAdminsUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-50">
                                <i class="fas fa-users-cog" aria-hidden="true"></i>
                                Liste des admins
                            </a>
                            <a href="<?php echo e($secondaryAdminsCreateUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                Nouvel administrateur
                            </a>
                        </div>
                    </div>
                </section>

                <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Filtres</p>
                            <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Affiner le journal d’activité</h3>
                        </div>
                        <?php if ($activeFilterCount > 0): ?>
                            <div class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1.5 text-xs font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                <i class="fas fa-filter" aria-hidden="true"></i>
                                <?php echo e((string) $activeFilterCount); ?> filtre(s) actif(s)
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="get" action="<?php echo e(site_url('admin/secondary_admins/activity')); ?>" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-[repeat(3,minmax(0,1fr))_auto]">
                        <input type="hidden" name="q" value="<?php echo e($searchQuery); ?>">
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-400">Du</span>
                            <input type="date" name="from" value="<?php echo e($fromFilter); ?>" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-700 dark:text-white">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-400">Au</span>
                            <input type="date" name="to" value="<?php echo e($toFilter); ?>" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-700 dark:text-white">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-400">Module</span>
                            <select name="module" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100">
                                <option value="">Tous les modules</option>
                                <?php foreach ($activityModules as $module): ?>
                                    <?php
                                    if (!is_array($module)) {
                                        continue;
                                    }
                                    $moduleKey = (string) ($module['key'] ?? '');
                                    $moduleLabel = (string) ($module['label'] ?? $moduleKey);
                                    ?>
                                    <option value="<?php echo e($moduleKey); ?>" <?php echo $moduleKey === $moduleFilter ? 'selected' : ''; ?>>
                                        <?php echo e($moduleLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-primary-700">
                                <i class="fas fa-check" aria-hidden="true"></i>
                                Appliquer
                            </button>
                            <a href="<?php echo e(site_url('admin/secondary_admins/activity')); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <i class="fas fa-undo" aria-hidden="true"></i>
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </section>

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(18rem,0.75fr)]">
                    <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('history'); ?><span>Dernières activités</span></h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Les actions les plus récentes des admins secondaires.</p>
                            </div>
                            <span class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                Page <?php echo e((string) $paginationPage); ?> / <?php echo e((string) $paginationTotalPages); ?>
                            </span>
                        </div>

                        <div class="space-y-3">
                            <?php if ($activityEntries === []): ?>
                                <div class="rounded-2xl bg-gray-50 p-5 text-sm text-gray-500 dark:bg-gray-700/30 dark:text-gray-400">
                                    <?php if ($totalActivities === 0 && $activeFilterCount === 0): ?>
                                        Aucune activité secondaire n'a encore été journalisée. Les prochaines connexions et actions des admins secondaires apparaîtront ici.
                                    <?php else: ?>
                                        Aucune activité ne correspond à ces filtres.
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($activityEntries as $entry): ?>
                                    <?php
                                    if (!is_array($entry)) {
                                        continue;
                                    }
                                    $actionTone = (string) ($entry['action_tone'] ?? 'done');
                                    $actionClass = $activityToneClass($actionTone);
                                    $actorName = (string) ($entry['actor_name'] ?? 'Administrateur secondaire');
                                    $actorEmail = trim((string) ($entry['actor_email'] ?? ''));
                                    $actorInitials = (string) ($entry['actor_initials'] ?? 'AS');
                                    $actionLabel = (string) ($entry['action_label'] ?? 'Action enregistrée');
                                    $actionIcon = (string) ($entry['action_icon'] ?? 'history');
                                    $targetLabel = (string) ($entry['target_label'] ?? 'Cible inconnue');
                                    $moduleLabel = (string) ($entry['module_label'] ?? 'Activité');
                                    $details = trim((string) ($entry['details'] ?? ''));
                                    $createdLabel = (string) ($entry['created_label'] ?? 'Date non disponible');
                                    ?>
                                    <article class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/30">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex min-w-0 items-start gap-3">
                                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-600 to-accent-500 text-sm font-bold text-white shadow-md">
                                                    <?php echo e($actorInitials); ?>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h4 class="truncate font-bold text-gray-900 dark:text-white"><?php echo e($actorName); ?></h4>
                                                        <span class="<?php echo e($actionClass); ?> inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold">
                                                            <i class="fas <?php echo e($actionIcon); ?>" aria-hidden="true"></i>
                                                            <?php echo e($actionLabel); ?>
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 truncate text-sm text-gray-600 dark:text-gray-300">
                                                        <?php echo e($targetLabel); ?>
                                                    </p>
                                                    <?php if ($details !== ''): ?>
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($details); ?></p>
                                                    <?php endif; ?>
                                                    <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-1 font-medium shadow-sm dark:bg-gray-800">
                                                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                                                            <?php echo e($moduleLabel); ?>
                                                        </span>
                                                        <?php if ($actorEmail !== ''): ?>
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-1 font-medium shadow-sm dark:bg-gray-800">
                                                                <i class="fas fa-envelope" aria-hidden="true"></i>
                                                                <?php echo e($actorEmail); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Le</p>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white"><?php echo e($createdLabel); ?></p>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($paginationTotalPages > 1): ?>
                            <nav class="mt-5 flex flex-wrap items-center gap-2" aria-label="Pagination du journal d'activité">
                                <a href="<?php echo e($buildPageUrl(max(1, $paginationPage - 1))); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 <?php echo $paginationPage <= 1 ? 'pointer-events-none opacity-50' : ''; ?>">
                                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                    Précédent
                                </a>

                                <?php
                                $startPage = max(1, $paginationPage - 2);
                                $endPage = min($paginationTotalPages, $paginationPage + 2);
                                for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++):
                                    $isCurrentPage = $pageNumber === $paginationPage;
                                    ?>
                                    <a href="<?php echo e($buildPageUrl($pageNumber)); ?>" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-bold transition <?php echo $isCurrentPage ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'; ?>">
                                        <?php echo e((string) $pageNumber); ?>
                                    </a>
                                <?php endfor; ?>

                                <a href="<?php echo e($buildPageUrl(min($paginationTotalPages, $paginationPage + 1))); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 <?php echo $paginationPage >= $paginationTotalPages ? 'pointer-events-none opacity-50' : ''; ?>">
                                    Suivant
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </nav>
                        <?php endif; ?>
                    </article>

                    <aside class="space-y-5">
                        <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('users'); ?><span>Admins les plus actifs</span></h3>
                                <span class="rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">Top 5</span>
                            </div>
                            <div class="space-y-3">
                                <?php if ($activityTopActors === []): ?>
                                    <div class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-700/30 dark:text-gray-400">
                                        <?php echo $totalActivities === 0 && $activeFilterCount === 0
                                            ? "Les auteurs s'afficheront dès qu'un admin secondaire se connectera ou fera une action."
                                            : 'Aucun auteur trouvé pour ces filtres.'; ?>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($activityTopActors as $actor): ?>
                                        <?php
                                        if (!is_array($actor)) {
                                            continue;
                                        }
                                        $actorName = (string) ($actor['name'] ?? 'Administrateur secondaire');
                                        $actorEmail = trim((string) ($actor['email'] ?? ''));
                                        $actorInitials = (string) ($actor['initials'] ?? 'AS');
                                        $actorCount = max(0, (int) ($actor['count'] ?? 0));
                                        ?>
                                        <div class="flex items-center gap-3 rounded-2xl bg-gray-50 p-3 dark:bg-gray-700/30">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-600 to-accent-500 text-xs font-bold text-white shadow-md">
                                                <?php echo e($actorInitials); ?>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-gray-900 dark:text-white"><?php echo e($actorName); ?></p>
                                                <p class="truncate text-xs text-gray-500 dark:text-gray-400"><?php echo e($actorEmail !== '' ? $actorEmail : 'Compte secondaire'); ?></p>
                                            </div>
                                            <span class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                                <?php echo e((string) $actorCount); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </article>

                        <article class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 p-5 shadow-card dark:border-gray-700">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('folder'); ?><span>Modules couverts</span></h3>
                                <span class="rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <?php echo e((string) count($activityModules)); ?>
                                </span>
                            </div>
                            <?php if ($activityModules === []): ?>
                                <div class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-700/30 dark:text-gray-400">
                                    <?php echo $totalActivities === 0 && $activeFilterCount === 0
                                        ? "Aucun module n'est encore remonté dans le journal."
                                        : 'Aucun module disponible pour le moment.'; ?>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($activityModules as $module): ?>
                                        <?php
                                        if (!is_array($module)) {
                                            continue;
                                        }
                                        $moduleLabel = (string) ($module['label'] ?? $module['key'] ?? 'Module');
                                        ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            <i class="fas fa-tag" aria-hidden="true"></i>
                                            <?php echo e($moduleLabel); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    </aside>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
