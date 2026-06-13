<?php
$errors = is_array($errors ?? null) ? $errors : [];
$success = (string) ($success ?? '');
$pendingCourses = is_array($pendingCourses ?? null) ? $pendingCourses : [];
$historyCourses = is_array($historyCourses ?? null) ? $historyCourses : [];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$moderationSummary = is_array($moderationSummary ?? null) ? $moderationSummary : [];
$filters = is_array($filters ?? null) ? $filters : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];

$currentPage = max(1, (int) ($pagination['page'] ?? 1));
$totalPages = max(1, (int) ($pagination['totalPages'] ?? 1));
$totalSubmitted = max(0, (int) ($pagination['total'] ?? count($pendingCourses)));

$adminNavActive = 'courses';
$adminTopTitle = 'Validation des cours soumis';
$adminTopSubtitle = 'Administration';
$adminTopBadge = (string) $totalSubmitted . ' en attente';
$adminTopBadgeTone = $totalSubmitted > 0 ? 'pending' : 'done';
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

$formatDateTime = static function (string $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '-';
    }
    try {
        $dt = new DateTimeImmutable($raw);
        return $dt->format('d/m/Y H:i');
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

$buildPageUrl = static function (int $targetPage) use ($filters): string {
    $page = max(1, $targetPage);
    $query = [];
    foreach (['search_type', 'q', 'teacher', 'category', 'date_from', 'date_to'] as $key) {
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

$pendingTotal = (int) ($moderationSummary['pending_total'] ?? $totalSubmitted);
$processedToday = (int) ($moderationSummary['processed_today'] ?? 0);
$approvedTotal = (int) ($moderationSummary['approved_total'] ?? 0);
$rejectedTotal = (int) ($moderationSummary['rejected_total'] ?? 0);

$filterQ = trim((string) ($filters['q'] ?? ''));
$filterTeacher = trim((string) ($filters['teacher'] ?? ''));
$filterCategory = trim((string) ($filters['category'] ?? ''));
$filterDateFrom = trim((string) ($filters['date_from'] ?? ''));
$filterDateTo = trim((string) ($filters['date_to'] ?? ''));
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

$courseRows = [];
foreach ($pendingCourses as $course) {
    if (is_array($course)) {
        $course['status'] = 'submitted';
        $courseRows[] = $course;
    }
}
foreach ($historyCourses as $course) {
    if (is_array($course)) {
        $courseRows[] = $course;
    }
}

$categoryOptions = [];
foreach ($courseRows as $course) {
    $categoryName = $normalizeText((string) ($course['category_name'] ?? ''));
    if ($categoryName !== '') {
        $categoryOptions[$categoryName] = $categoryName;
    }
}
ksort($categoryOptions, SORT_NATURAL | SORT_FLAG_CASE);

$csrfToken = view_csrf_token();
$adminPageScripts = ['admin_courses_search.js'];
$encodeJsonAttribute = static function (array $value): string {
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);

    return is_string($json) ? $json : '{}';
};
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

                <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
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
                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Rejetés</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e((string) $rejectedTotal); ?></p>
                    </article>
                </section>

                <section class="border-b border-gray-200 dark:border-gray-700">
                    <div class="flex gap-4 overflow-x-auto">
                        <button type="button" data-course-status-tab="submitted" class="tab-btn shrink-0 py-3 px-1 font-medium text-primary-600 border-b-2 border-primary-600 dark:text-primary-400 dark:border-primary-400 transition" aria-pressed="true">
                            <i class="fas fa-hourglass-half mr-2"></i>En attente
                            <span class="ml-1 bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo e((string) $pendingTotal); ?></span>
                        </button>
                        <button type="button" data-course-status-tab="approved" class="tab-btn shrink-0 py-3 px-1 font-medium text-gray-500 dark:text-gray-400 border-b-2 border-transparent hover:text-gray-700 transition" aria-pressed="false">
                            <i class="fas fa-check-circle mr-2"></i>Approuvés
                            <span class="ml-1 bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo e((string) $approvedTotal); ?></span>
                        </button>
                        <button type="button" data-course-status-tab="rejected" class="tab-btn shrink-0 py-3 px-1 font-medium text-gray-500 dark:text-gray-400 border-b-2 border-transparent hover:text-gray-700 transition" aria-pressed="false">
                            <i class="fas fa-times-circle mr-2"></i>Rejetés
                            <span class="ml-1 bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded-full text-xs"><?php echo e((string) $rejectedTotal); ?></span>
                        </button>
                    </div>
                </section>

                <section class="flex flex-wrap justify-between items-center gap-3">
                    <div class="flex flex-wrap gap-2">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="search" id="admin-courses-inline-search" placeholder="Rechercher cours, enseignant..." value="<?php echo e($filterQ !== '' ? $filterQ : $filterTeacher); ?>" class="pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 outline-none w-72 max-w-full">
                        </div>
                        <select id="admin-courses-category-filter" class="text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 focus:ring-primary-500">
                            <option value="all">Toutes catégories</option>
                            <?php foreach ($categoryOptions as $categoryName): ?>
                                <option value="<?php echo e($categoryName); ?>" <?php echo strcasecmp($categoryName, $filterCategory) === 0 ? 'selected' : ''; ?>><?php echo e($categoryName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><i class="fas fa-info-circle"></i> Cliquez sur "Détails" pour voir le résumé ou ouvrir l’aperçu complet.</p>
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
                                'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'icon' => 'fa-clock',
                            ];
                        } elseif ($status === 'approved') {
                            $statusUi = [
                                'label' => 'Approuvé',
                                'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                'icon' => 'fa-check',
                            ];
                        } else {
                            $statusUi = [
                                'label' => 'Rejeté',
                                'class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                'icon' => 'fa-times',
                            ];
                        }
                        $title = $normalizeText((string) ($course['title'] ?? 'Cours sans titre'));
                        $teacherName = $normalizeText((string) ($course['first_name'] ?? '') . ' ' . (string) ($course['last_name'] ?? ''));
                        if ($teacherName === '') {
                            $teacherName = 'Non renseigné';
                        }
                        $categoryName = $normalizeText((string) ($course['category_name'] ?? 'Non renseigné'));
                        $description = $plainText((string) ($course['description'] ?? ''));
                        $shortDescription = $truncate($description !== '' ? $description : 'Aucune description renseignée.', 170);
                        $submittedAt = (string) ($course['submitted_at'] ?? ($course['updated_at'] ?? ''));
                        $priceAmount = (int) ($course['price_xof'] ?? 0);
                        $priceLabel = $formatMoney($priceAmount);
                        $priceChipClass = $priceAmount > 0 ? 'admin-course-chip--price' : 'admin-course-chip--free';
                        $durationHours = trim((string) ($course['duration_hours'] ?? ''));
                        $durationLabel = $durationHours !== '' ? rtrim(rtrim($durationHours, '0'), '.') . ' h' : 'Non renseignée';
                        $rawLevel = (string) ($course['level'] ?? '');
                        $level = $levelLabel($rawLevel);
                        $levelToneClass = $levelChipClass($rawLevel);
                        $coverUrl = $resolveMediaUrl($course['cover_path'] ?? '');
                        $previewUrl = site_url('admin/course_preview?id=' . $courseId);
                        $searchData = $title . ' ' . $teacherName . ' ' . $categoryName . ' ' . $shortDescription;
                        $rejectionReason = $plainText((string) ($course['last_rejection_reason'] ?? ''));
                        $stats = is_array($course['stats'] ?? null) ? $course['stats'] : [];
                        $statsPayload = [
                            'students' => max(0, (int) ($stats['students'] ?? 0)),
                            'revenue_xof' => max(0, (int) ($stats['revenue_xof'] ?? 0)),
                            'paid_orders' => max(0, (int) ($stats['paid_orders'] ?? 0)),
                            'avg_rating' => max(0.0, min(5.0, (float) ($stats['avg_rating'] ?? 0))),
                            'rating_count' => max(0, (int) ($stats['rating_count'] ?? 0)),
                            'completion_rate' => max(0, min(100, (int) ($stats['completion_rate'] ?? 0))),
                            'monthly_enrollments' => is_array($stats['monthly_enrollments'] ?? null) ? array_values($stats['monthly_enrollments']) : [],
                        ];
                        ?>
                        <article
                            class="admin-course-card bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 hover-lift overflow-hidden transition"
                            data-course-card
                            data-status="<?php echo e($status); ?>"
                            data-category="<?php echo e($categoryName); ?>"
                            data-category-label="<?php echo e($categoryName); ?>"
                            data-search="<?php echo e($searchData); ?>"
                            data-title="<?php echo e($title); ?>"
                            data-teacher="<?php echo e($teacherName); ?>"
                            data-price="<?php echo e($priceLabel); ?>"
                            data-duration="<?php echo e($durationLabel); ?>"
                            data-level="<?php echo e($level); ?>"
                            data-submitted="<?php echo e($formatDateTime($submittedAt)); ?>"
                            data-description="<?php echo e($description); ?>"
                            data-cover="<?php echo e($coverUrl); ?>"
                            data-preview-url="<?php echo e($previewUrl); ?>"
                            data-stats="<?php echo e($encodeJsonAttribute($statsPayload)); ?>"
                        >
                            <div class="flex flex-col md:flex-row gap-4 p-5">
                                <?php if ($coverUrl !== ''): ?>
                                    <img src="<?php echo e($coverUrl); ?>" class="w-full md:w-36 h-28 rounded-xl object-cover bg-gray-100 dark:bg-gray-700" alt="">
                                <?php else: ?>
                                    <div class="w-full md:w-36 h-28 rounded-xl bg-gradient-to-br from-primary-600 to-accent-600 flex items-center justify-center text-white text-3xl">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="flex-1 space-y-2 min-w-0">
                                    <div class="flex flex-wrap justify-between items-start gap-2">
                                        <div class="min-w-0">
                                            <h3 class="font-bold text-lg text-gray-900 dark:text-white truncate"><?php echo e($title); ?></h3>
                                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                                <span class="admin-course-mini admin-course-mini--teacher"><i class="fas fa-chalkboard-teacher"></i><?php echo e($teacherName); ?></span>
                                                <span class="admin-course-mini admin-course-mini--date"><i class="far fa-calendar-alt"></i>Soumis le <?php echo e($formatDate($submittedAt)); ?></span>
                                                <span class="admin-course-mini admin-course-mini--category"><i class="fas fa-layer-group"></i><?php echo e($categoryName); ?></span>
                                            </div>
                                        </div>
                                        <span class="admin-course-status text-xs px-3 py-1 rounded-full <?php echo e($statusUi['class']); ?>">
                                            <i class="fas <?php echo e($statusUi['icon']); ?> mr-1"></i><?php echo e($statusUi['label']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300"><?php echo e($shortDescription); ?></p>
                                    <div class="flex flex-wrap gap-2 text-xs">
                                        <span class="admin-course-chip <?php echo e($priceChipClass); ?>"><i class="fas fa-money-bill-wave"></i><?php echo e($priceLabel); ?></span>
                                        <span class="admin-course-chip admin-course-chip--duration"><i class="fas fa-hourglass-half"></i><?php echo e($durationLabel); ?></span>
                                        <span class="admin-course-chip <?php echo e($levelToneClass); ?>"><i class="fas fa-chart-bar"></i><?php echo e($level); ?></span>
                                    </div>
                                    <?php if ($status === 'rejected' && $rejectionReason !== ''): ?>
                                        <p class="text-xs text-red-600 dark:text-red-300"><i class="fas fa-comment-dots"></i> Motif : <?php echo e($truncate($rejectionReason, 90)); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex flex-col gap-2 justify-end items-stretch md:items-end">
                                    <button type="button" data-course-detail class="admin-course-detail-button px-4 py-2 rounded-xl text-sm font-medium">
                                        <i class="fas fa-eye"></i> Détails
                                    </button>
                                    <button type="button" data-course-stats-open class="admin-course-stats-button px-4 py-2 rounded-xl text-sm font-medium">
                                        <i class="fas fa-chart-line"></i> Statistiques
                                    </button>

                                    <?php if ($status === 'submitted'): ?>
                                        <form method="post" action="<?php echo e(site_url('admin/courses')); ?>" data-confirm-message="<?php echo e('Approuver le cours "' . $title . '" ? Il sera publié sur la plateforme.'); ?>">
                                            <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                            <input type="hidden" name="page" value="<?php echo e((string) $currentPage); ?>">
                                            <input type="hidden" name="q" value="<?php echo e($filterQ); ?>">
                                            <input type="hidden" name="teacher" value="<?php echo e($filterTeacher); ?>">
                                            <input type="hidden" name="category" value="<?php echo e($filterCategory); ?>">
                                            <input type="hidden" name="date_from" value="<?php echo e($filterDateFrom); ?>">
                                            <input type="hidden" name="date_to" value="<?php echo e($filterDateTo); ?>">
                                            <input type="hidden" name="search_type" value="<?php echo e($filterSearchType); ?>">
                                            <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                            <button type="submit" name="action" value="approve" class="w-full px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-sm font-medium">
                                                <i class="fas fa-check-circle"></i> Approuver
                                            </button>
                                        </form>

                                        <button type="button" data-course-reject data-course-id="<?php echo e((string) $courseId); ?>" data-course-title="<?php echo e($title); ?>" class="px-4 py-2 rounded-xl bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 text-sm">
                                            <i class="fas fa-times-circle"></i> Rejeter
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <div id="admin-courses-empty-state" class="hidden text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-soft border border-gray-100 dark:border-gray-700">
                        <i class="fas fa-folder-open text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">Aucun cours dans cette catégorie</p>
                    </div>

                    <?php if ($courseRows === []): ?>
                        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-soft border border-gray-100 dark:border-gray-700">
                            <i class="fas fa-folder-open text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
                            <p class="text-gray-500 dark:text-gray-400">Aucun cours disponible.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <?php if ($totalPages > 1): ?>
                    <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination des cours soumis">
                        <a href="<?php echo e($buildPageUrl(max(1, $currentPage - 1))); ?>" class="admin-shell-button admin-shell-button--secondary <?php echo $currentPage <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>">Précédent</a>

                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++):
                            $isActive = $pageNumber === $currentPage;
                            ?>
                            <a href="<?php echo e($buildPageUrl($pageNumber)); ?>" class="admin-shell-button <?php echo $isActive ? 'admin-shell-button--primary' : 'admin-shell-button--secondary'; ?>"><?php echo e((string) $pageNumber); ?></a>
                        <?php endfor; ?>

                        <a href="<?php echo e($buildPageUrl(min($totalPages, $currentPage + 1))); ?>" class="admin-shell-button admin-shell-button--secondary <?php echo $currentPage >= $totalPages ? 'opacity-50 pointer-events-none' : ''; ?>">Suivant</a>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <div id="admin-course-detail-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 max-w-3xl w-full rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-bold flex items-center gap-2"><i class="fas fa-info-circle text-primary-500"></i> Aperçu détaillé</h3>
                <button type="button" data-course-detail-close class="text-gray-400 hover:text-gray-600" aria-label="Fermer"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-6 space-y-4" id="admin-course-detail-content"></div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700">
                <button type="button" data-course-detail-close class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600">Fermer</button>
            </div>
        </div>
    </div>

    <div id="admin-course-stats-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="admin-course-stats-modal bg-white dark:bg-gray-800 max-w-5xl w-full rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold flex items-center gap-2 text-gray-900 dark:text-white"><i class="fas fa-chart-pie text-primary-500"></i> Statistiques du cours</h3>
                    <p id="admin-course-stats-title" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
                </div>
                <button type="button" data-course-stats-close class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Fermer"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-6 space-y-6">
                <div id="admin-course-stats-cards" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"></div>
                <section class="admin-course-stats-chart-card rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white">Évolution des inscriptions</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Six derniers mois</p>
                        </div>
                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-300"><i class="fas fa-project-diagram mr-1"></i>Données réelles</span>
                    </div>
                    <div id="admin-course-stats-chart" class="mt-4"></div>
                </section>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700">
                <button type="button" data-course-stats-close class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600">Fermer</button>
            </div>
        </div>
    </div>

    <div id="admin-course-reject-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <form method="post" action="<?php echo e(site_url('admin/courses')); ?>" class="bg-white dark:bg-gray-800 max-w-md w-full rounded-2xl shadow-xl">
            <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="page" value="<?php echo e((string) $currentPage); ?>">
            <input type="hidden" name="q" value="<?php echo e($filterQ); ?>">
            <input type="hidden" name="teacher" value="<?php echo e($filterTeacher); ?>">
            <input type="hidden" name="category" value="<?php echo e($filterCategory); ?>">
            <input type="hidden" name="date_from" value="<?php echo e($filterDateFrom); ?>">
            <input type="hidden" name="date_to" value="<?php echo e($filterDateTo); ?>">
            <input type="hidden" name="search_type" value="<?php echo e($filterSearchType); ?>">
            <input type="hidden" id="admin-course-reject-id" name="course_id" value="">

            <div class="p-6">
                <h3 class="text-lg font-bold flex items-center gap-2 text-red-600"><i class="fas fa-ban"></i> Motif du rejet</h3>
                <p class="text-sm text-gray-500 mt-1">Cours : <span id="admin-course-reject-title" class="font-semibold text-gray-700 dark:text-gray-200"></span></p>

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
                    <button type="submit" name="action" value="reject" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">Confirmer le rejet</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
