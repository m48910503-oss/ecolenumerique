<?php
$errors = is_array($errors ?? null) ? $errors : [];
$success = (string) ($success ?? '');
$courseRows = is_array($courseRows ?? null) ? $courseRows : [];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$moderationSummary = is_array($moderationSummary ?? null) ? $moderationSummary : [];
$filters = is_array($filters ?? null) ? $filters : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];
$currentStatusTab = in_array((string) ($currentStatusTab ?? 'submitted'), ['submitted', 'approved'], true)
    ? (string) ($currentStatusTab ?? 'submitted')
    : 'submitted';
$pendingTotalCount = max(0, (int) ($pendingTotalCount ?? 0));
$approvedTotalCount = max(0, (int) ($approvedTotalCount ?? 0));

$currentPage = max(1, (int) ($pagination['page'] ?? 1));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? $pagination['totalPages'] ?? 1));
$totalCourses = max(0, (int) ($pagination['total'] ?? count($courseRows)));

$adminNavActive = 'courses';
$adminTopTitle = 'Validation des cours soumis';
$adminTopSubtitle = 'Administration';
$adminTopBadge = (string) $pendingTotalCount . ' en attente';
$adminTopBadgeTone = $pendingTotalCount > 0 ? 'pending' : 'done';
$adminTopActions = [
    ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'],
];

$normalizeText = static function (string $value): string {
    return trim((string) preg_replace('/\s+/', ' ', $value));
};

$plainText = static function (string $value) use ($normalizeText): string {
    return $normalizeText(strip_tags(html_entity_decode($value, ENT_QUOTES, 'UTF-8')));
};

$truncate = static function (string $value, int $limit = 150): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 1) . '…' : $value;
    }

    return strlen($value) > $limit ? substr($value, 0, $limit - 1) . '...' : $value;
};

$formatDate = static function (string $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '-';
    }
    try {
        $dt = new DateTimeImmutable($raw);
        return $dt->format('d/m/Y');
    } catch (Throwable $e) {
        return $raw;
    }
};

$formatMoney = static function (int $amount): string {
    return number_format(max(0, $amount), 0, ',', ' ') . ' F CFA';
};

$resolveMediaUrl = static function ($path): string {
    $value = trim((string) $path);
    if ($value === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $value) === 1) {
        return $value;
    }

    return site_url(ltrim($value, '/'));
};

$statusLabel = static function (string $status) use ($statusLabels): string {
    $key = strtolower(trim($status));
    if (isset($statusLabels[$key])) {
        return (string) $statusLabels[$key];
    }

    return match ($key) {
        'submitted' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
        default => $key !== '' ? ucfirst($key) : 'Inconnu',
    };
};

$buildPageUrl = static function (int $targetPage) use ($filters, $currentStatusTab): string {
    $page = max(1, $targetPage);
    $query = ['tab' => $currentStatusTab];
    foreach (['search_type', 'q', 'teacher', 'teacher_id', 'category', 'date_from', 'date_to'] as $key) {
        $value = trim((string) ($filters[$key] ?? ''));
        if ($value !== '') {
            $query[$key] = $value;
        }
    }
    if ($page > 1) {
        $query['page'] = $page;
    }

    return site_url('admin/courses' . ($query !== [] ? ('?' . http_build_query($query)) : ''));
};

$buildTabUrl = static function (string $tab) use ($filters): string {
    $query = ['tab' => $tab];
    foreach (['search_type', 'q', 'teacher', 'teacher_id', 'category', 'date_from', 'date_to'] as $key) {
        $value = trim((string) ($filters[$key] ?? ''));
        if ($value !== '') {
            $query[$key] = $value;
        }
    }

    return site_url('admin/courses' . ($query !== [] ? ('?' . http_build_query($query)) : ''));
};

$buildResetUrl = static function (string $tab): string {
    return site_url('admin/courses?' . http_build_query(['tab' => $tab]));
};

$pendingTotal = (int) ($moderationSummary['pending_total'] ?? $pendingTotalCount);
$processedToday = (int) ($moderationSummary['processed_today'] ?? 0);
$approvedTotal = (int) ($moderationSummary['approved_total'] ?? $approvedTotalCount);

$filterQ = trim((string) ($filters['q'] ?? ''));
$filterTeacher = trim((string) ($filters['teacher'] ?? ''));
$filterCategory = trim((string) ($filters['category'] ?? ''));
$filterDateFrom = trim((string) ($filters['date_from'] ?? ''));
$filterDateTo = trim((string) ($filters['date_to'] ?? ''));
$filterTeacherId = max(0, (int) ($filters['teacher_id'] ?? 0));
$filterSearchType = strtolower(trim((string) ($filters['search_type'] ?? 'name')));
if (!in_array($filterSearchType, ['name', 'teacher', 'category', 'date'], true)) {
    $filterSearchType = 'name';
}

$reasonPresets = [
    'Contenu incomplet ou structure insuffisante',
    'Objectifs pédagogiques non conformes',
    'Qualité vidéo/audio insuffisante',
    "Problème de droits d'auteur",
    'Prix incohérent avec le contenu',
    'Non conforme aux règles pédagogiques',
];

$categoryOptions = [];
foreach ($courseRows as $course) {
    $categoryName = $normalizeText((string) ($course['category_name'] ?? ''));
    if ($categoryName !== '') {
        $categoryOptions[$categoryName] = $categoryName;
    }
}
if ($filterCategory !== '') {
    $categoryOptions[$filterCategory] = $filterCategory;
}
ksort($categoryOptions, SORT_NATURAL | SORT_FLAG_CASE);

$csrfToken = view_csrf_token();
$adminPageScripts = ['admin_courses_search.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation des cours · Administrateur</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body" data-view="admin/courses" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <?php if ($errors): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $err): ?>
                            <p>- <?php echo e((string) $err); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="admin-shell-alert admin-shell-alert--success">
                        <p><?php echo e($success); ?></p>
                    </div>
                <?php endif; ?>

                <section class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-2xl p-6 text-white shadow-glow animate-fade-up">
                    <div class="flex flex-wrap gap-4 justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold">Modération des cours</h2>
                            <p class="text-primary-100 mt-1">Examinez, validez ou refusez les soumissions des enseignants avant publication.</p>
                        </div>
                        <div class="bg-white/20 rounded-full px-4 py-2 backdrop-blur-sm text-sm font-semibold">
                            <i class="fas fa-clock mr-2"></i>En attente : <?php echo e((string) $pendingTotal); ?> cours
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">En attente</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e((string) $pendingTotal); ?></p>
                    </article>
                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Traités aujourd’hui</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e((string) $processedToday); ?></p>
                    </article>
                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Approuvés</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e((string) $approvedTotal); ?></p>
                    </article>
                </section>

                <section class="border-b border-gray-200 dark:border-gray-700">
                    <div class="flex gap-4 overflow-x-auto">
                        <a href="<?php echo e($buildTabUrl('submitted')); ?>" class="tab-btn shrink-0 border-b-2 py-3 px-1 font-medium transition <?php echo $currentStatusTab === 'submitted' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'; ?>" aria-current="<?php echo $currentStatusTab === 'submitted' ? 'page' : 'false'; ?>">
                            <i class="fas fa-hourglass-half mr-2"></i>En attente
                            <span class="ml-1 bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo e((string) $pendingTotal); ?></span>
                        </a>
                        <a href="<?php echo e($buildTabUrl('approved')); ?>" class="tab-btn shrink-0 border-b-2 py-3 px-1 font-medium transition <?php echo $currentStatusTab === 'approved' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'; ?>" aria-current="<?php echo $currentStatusTab === 'approved' ? 'page' : 'false'; ?>">
                            <i class="fas fa-check-circle mr-2"></i>Approuvés
                            <span class="ml-1 bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo e((string) $approvedTotal); ?></span>
                        </a>
                    </div>
                </section>

                <section class="admin-shell-card rounded-3xl p-4 sm:p-5" data-course-current-status="<?php echo e($currentStatusTab); ?>">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Filtres</p>
                            <h3 class="mt-1 text-base font-black text-slate-900 dark:text-white">Filtrage des cours</h3>
                        </div>
                        <div class="inline-flex w-full items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/90 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 xl:w-auto xl:min-w-[17rem]">
                            <span class="inline-flex items-center gap-2">
                                <i class="fas fa-sliders-h text-primary-500 dark:text-primary-300" aria-hidden="true"></i>
                                <span id="admin-courses-filter-count"><?php echo e((string) $totalCourses); ?> cours visibles</span>
                            </span>
                            <span id="admin-courses-filter-status" class="rounded-full bg-white px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.18em] text-slate-500 shadow-sm dark:bg-slate-800 dark:text-slate-300">
                                <?php echo e($currentStatusTab === 'approved' ? 'Approuvés' : 'En attente'); ?>
                            </span>
                        </div>
                    </div>

                    <form method="get" action="<?php echo e(site_url('admin/courses')); ?>" class="admin-courses-filter-form mt-4">
                        <input type="hidden" name="tab" value="<?php echo e($currentStatusTab); ?>">
                        <input type="hidden" name="search_type" value="name">
                        <?php if ($filterTeacherId > 0): ?>
                            <input type="hidden" name="teacher_id" value="<?php echo e((string) $filterTeacherId); ?>">
                        <?php endif; ?>

                        <label class="admin-courses-filter-field">
                            <span>Recherche</span>
                            <span class="relative mt-2 block">
                                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                                <input id="admin-courses-inline-search" type="search" name="q" placeholder="Titre du cours ou nom de l’enseignant" value="<?php echo e($filterQ !== '' ? $filterQ : $filterTeacher); ?>" class="h-12 w-full rounded-2xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-primary-400">
                            </span>
                        </label>

                        <label class="admin-courses-filter-field">
                            <span>Catégorie</span>
                            <span class="relative mt-2 block">
                                <i class="fas fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                                <select id="admin-courses-category-filter" name="category" class="h-12 w-full appearance-none rounded-2xl border border-slate-200 bg-white pl-11 pr-10 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-primary-400">
                                    <option value="">Toutes catégories</option>
                                    <?php foreach ($categoryOptions as $categoryName): ?>
                                        <option value="<?php echo e($categoryName); ?>" <?php echo strcasecmp($categoryName, $filterCategory) === 0 ? 'selected' : ''; ?>><?php echo e($categoryName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                            </span>
                        </label>

                        <div class="admin-courses-filter-actions">
                            <button type="submit" class="admin-shell-button admin-shell-button--primary h-12 w-full min-w-[8.5rem] whitespace-nowrap px-4 xl:w-auto xl:shrink-0">
                                <i class="fas fa-filter text-white" aria-hidden="true"></i>
                                Filtrer
                            </button>

                            <a id="admin-courses-filter-reset" href="<?php echo e($buildResetUrl($currentStatusTab)); ?>" class="admin-shell-button admin-shell-button--secondary h-12 w-full min-w-[9rem] whitespace-nowrap px-4 xl:w-auto xl:shrink-0">
                                <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </section>

                <section class="space-y-5">
                    <?php foreach ($courseRows as $course): ?>
                        <?php
                        $courseId = (int) ($course['id'] ?? 0);
                        if ($courseId < 1) {
                            continue;
                        }
                        $status = strtolower(trim((string) ($course['status'] ?? 'submitted')));
                        if ($status === 'submitted') {
                            $statusUi = [
                                'label' => 'En attente',
                                'icon' => 'fa-clock',
                                'modifier' => 'pending',
                                'date_label' => 'Soumis le',
                            ];
                        } elseif ($status === 'approved') {
                            $statusUi = [
                                'label' => 'Approuvé',
                                'icon' => 'fa-check',
                                'modifier' => 'approved',
                                'date_label' => 'Validé le',
                            ];
                        } else {
                            continue;
                        }
                        $title = $normalizeText((string) ($course['title'] ?? 'Cours sans titre'));
                        $teacherName = $normalizeText((string) ($course['first_name'] ?? '') . ' ' . (string) ($course['last_name'] ?? ''));
                        if ($teacherName === '') {
                            $teacherName = 'Non renseigné';
                        }
                        $categoryName = $normalizeText((string) ($course['category_name'] ?? 'Non renseigné'));
                        $description = $plainText((string) ($course['description'] ?? ''));
                        $shortDescription = $truncate($description !== '' ? $description : 'Aucune description renseignée.', 120);
                        $submittedAt = (string) ($course['submitted_at'] ?? '');
                        $updatedAt = (string) ($course['updated_at'] ?? '');
                        $displayedDate = $status === 'submitted'
                            ? ($submittedAt !== '' ? $submittedAt : $updatedAt)
                            : ($updatedAt !== '' ? $updatedAt : $submittedAt);
                        $priceAmount = (int) ($course['price_xof'] ?? 0);
                        $priceLabel = $formatMoney($priceAmount);
                        $priceChipClass = $priceAmount > 0 ? 'admin-course-chip--price' : 'admin-course-chip--free';
                        $durationHours = trim((string) ($course['duration_hours'] ?? ''));
                        $durationLabel = $durationHours !== ''
                            ? format_duration_hours_hm((float) $durationHours)
                            : 'Non renseignée';
                        $coverUrl = $resolveMediaUrl($course['cover_path'] ?? '');
                        $previewUrl = site_url('admin/course_preview?id=' . $courseId);
                        $statsUrl = site_url('admin/course_insights?id=' . $courseId);
                        $searchData = $title . ' ' . $teacherName . ' ' . $categoryName . ' ' . $shortDescription;
                        ?>
                        <article
                            class="admin-course-card admin-course-card--<?php echo e($statusUi['modifier']); ?> bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 hover-lift overflow-hidden transition"
                            data-course-card
                            data-status="<?php echo e($status); ?>"
                            data-category="<?php echo e($categoryName); ?>"
                            data-search="<?php echo e($searchData); ?>"
                        >
                            <div class="admin-course-card__layout p-4 md:p-5">
                                <div class="admin-course-card__media">
                                    <?php if ($coverUrl !== ''): ?>
                                        <img src="<?php echo e($coverUrl); ?>" class="admin-course-card__cover" alt="">
                                    <?php else: ?>
                                        <div class="admin-course-card__cover admin-course-card__cover--placeholder">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="admin-course-card__body min-w-0">
                                    <div class="admin-course-card__heading">
                                        <h3 class="admin-course-card__title"><?php echo e($title); ?></h3>
                                        <span class="admin-course-status admin-course-status--<?php echo e($statusUi['modifier']); ?>">
                                            <i class="fas <?php echo e($statusUi['icon']); ?>" aria-hidden="true"></i>
                                            <?php echo e($statusUi['label']); ?>
                                        </span>
                                    </div>

                                    <div class="admin-course-card__meta text-xs">
                                        <span class="admin-course-mini admin-course-mini--teacher"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i><?php echo e($teacherName); ?></span>
                                        <span class="admin-course-mini admin-course-mini--date"><i class="far fa-calendar-alt" aria-hidden="true"></i><?php echo e($statusUi['date_label']); ?> <?php echo e($formatDate($displayedDate)); ?></span>
                                    </div>

                                    <p class="admin-course-card__description line-clamp-1 text-sm text-gray-600 dark:text-gray-300"><?php echo e($shortDescription); ?></p>

                                    <div class="admin-course-card__facts text-xs">
                                        <span class="admin-course-chip <?php echo e($priceChipClass); ?>"><i class="fas fa-money-bill-wave"></i><?php echo e($priceLabel); ?></span>
                                        <span class="admin-course-chip admin-course-chip--duration"><i class="fas fa-hourglass-half"></i><?php echo e($durationLabel); ?></span>
                                    </div>

                                </div>

                                <div class="admin-course-card__actions">
                                    <?php if ($status === 'submitted'): ?>
                                        <a href="<?php echo e($previewUrl); ?>" target="_blank" rel="noopener noreferrer" class="admin-course-action admin-course-action--preview">
                                            <i class="fas fa-up-right-from-square"></i> Aperçu
                                        </a>
                                        <form method="post" action="<?php echo e(site_url('admin/courses')); ?>" data-confirm-message="<?php echo e('Approuver le cours "' . $title . '" ? Il sera publié sur la plateforme.'); ?>">
                                            <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                            <input type="hidden" name="tab" value="<?php echo e($currentStatusTab); ?>">
                                            <input type="hidden" name="page" value="<?php echo e((string) $currentPage); ?>">
                                            <input type="hidden" name="q" value="<?php echo e($filterQ); ?>">
                                            <input type="hidden" name="teacher" value="<?php echo e($filterTeacher); ?>">
                                            <input type="hidden" name="category" value="<?php echo e($filterCategory); ?>">
                                            <input type="hidden" name="date_from" value="<?php echo e($filterDateFrom); ?>">
                                            <input type="hidden" name="date_to" value="<?php echo e($filterDateTo); ?>">
                                            <input type="hidden" name="search_type" value="<?php echo e($filterSearchType); ?>">
                                            <?php if ($filterTeacherId > 0): ?>
                                                <input type="hidden" name="teacher_id" value="<?php echo e((string) $filterTeacherId); ?>">
                                            <?php endif; ?>
                                            <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                            <button type="submit" name="action" value="approve" class="admin-course-action admin-course-action--approve">
                                                <i class="fas fa-check-circle"></i> Approuver
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            data-course-moderation
                                            data-course-id="<?php echo e((string) $courseId); ?>"
                                            data-course-title="<?php echo e($title); ?>"
                                            data-course-action="reject"
                                            data-course-modal-title="Motif du rejet"
                                            data-course-modal-submit="Confirmer le rejet"
                                            data-course-modal-note="L’enseignant sera notifié avec ce motif."
                                            data-course-modal-placeholder="Qualité insuffisante, contenu incomplet, problème de licence..."
                                            class="admin-course-action admin-course-action--reject"
                                        >
                                            <i class="fas fa-times-circle"></i> Rejeter
                                        </button>
                                    <?php elseif ($status === 'approved'): ?>
                                        <a href="<?php echo e($previewUrl); ?>" target="_blank" rel="noopener noreferrer" class="admin-course-action admin-course-action--preview">
                                            <i class="fas fa-up-right-from-square"></i> Aperçu
                                        </a>
                                        <a href="<?php echo e($statsUrl); ?>" class="admin-course-action admin-course-action--stats">
                                            <i class="fas fa-chart-line"></i> Statistiques
                                        </a>
                                        <button
                                            type="button"
                                            data-course-moderation
                                            data-course-id="<?php echo e((string) $courseId); ?>"
                                            data-course-title="<?php echo e($title); ?>"
                                            data-course-action="remove"
                                            data-course-modal-title="Motif du retrait"
                                            data-course-modal-submit="Retirer le cours"
                                            data-course-modal-note="Le cours sera retiré du catalogue et l’enseignant sera notifié avec ce motif."
                                            data-course-modal-placeholder="Expliquez pourquoi le cours doit être retiré du catalogue..."
                                            class="admin-course-action admin-course-action--reject"
                                        >
                                            <i class="fas fa-ban"></i> Retirer
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($courseRows === []): ?>
                        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-soft border border-gray-100 dark:border-gray-700">
                            <i class="fas fa-folder-open text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
                            <p class="text-gray-500 dark:text-gray-400">Aucun cours disponible.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <?php
                $paginationData = $pagination;
                $paginationUrlBuilder = $buildPageUrl;
                $paginationLabel = 'cours';
                $paginationAriaLabel = 'Pagination des cours';
                require __DIR__ . '/_pagination.php';
                ?>
            </main>
        </div>
    </div>

    <div id="admin-course-reject-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <form method="post" action="<?php echo e(site_url('admin/courses')); ?>" class="bg-white dark:bg-gray-800 max-w-md w-full rounded-2xl shadow-xl">
            <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="tab" value="<?php echo e($currentStatusTab); ?>">
            <input type="hidden" name="page" value="<?php echo e((string) $currentPage); ?>">
            <input type="hidden" name="q" value="<?php echo e($filterQ); ?>">
            <input type="hidden" name="teacher" value="<?php echo e($filterTeacher); ?>">
            <input type="hidden" name="category" value="<?php echo e($filterCategory); ?>">
            <input type="hidden" name="date_from" value="<?php echo e($filterDateFrom); ?>">
            <input type="hidden" name="date_to" value="<?php echo e($filterDateTo); ?>">
            <input type="hidden" name="search_type" value="<?php echo e($filterSearchType); ?>">
            <?php if ($filterTeacherId > 0): ?>
                <input type="hidden" name="teacher_id" value="<?php echo e((string) $filterTeacherId); ?>">
            <?php endif; ?>
            <input type="hidden" id="admin-course-reject-action" name="action" value="reject">
            <input type="hidden" id="admin-course-reject-id" name="course_id" value="">

            <div class="p-6">
                <h3 class="text-lg font-bold flex items-center gap-2 text-red-600"><i class="fas fa-ban"></i> <span id="admin-course-reject-heading">Motif du rejet</span></h3>
                <p class="text-sm text-gray-500 mt-1">Cours : <span id="admin-course-reject-title" class="font-semibold text-gray-700 dark:text-gray-200"></span></p>
                <p id="admin-course-reject-note" class="mt-2 text-sm text-gray-500 dark:text-gray-400">L’enseignant sera notifié avec ce motif.</p>

                <label class="mt-4 block">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Motif prédéfini</span>
                    <select name="reason_preset" class="mt-2 w-full border border-gray-300 dark:border-gray-600 rounded-xl p-3 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-red-400 outline-none">
                        <?php foreach ($reasonPresets as $preset): ?>
                            <option value="<?php echo e($preset); ?>"><?php echo e($preset); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="mt-3 block">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Précision complémentaire</span>
                    <textarea id="admin-course-reject-custom" name="reason_custom" rows="3" class="mt-2 w-full border border-gray-300 dark:border-gray-600 rounded-xl p-3 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-red-400 outline-none" placeholder="Qualité insuffisante, contenu incomplet, problème de licence..."></textarea>
                </label>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" data-course-reject-close class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600">Annuler</button>
                    <button id="admin-course-reject-submit" type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">Confirmer le rejet</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
