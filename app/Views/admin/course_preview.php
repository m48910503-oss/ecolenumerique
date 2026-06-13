<?php
declare(strict_types=1);
 
$errors = is_array($errors ?? null) ? $errors : [];
$success = trim((string) ($success ?? ''));
$course = is_array($course ?? null) ? $course : [];
$courseId = (int) ($courseId ?? 0);
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$levelLabels = is_array($levelLabels ?? null) ? $levelLabels : [];
$chapters = is_array($chapters ?? null) ? $chapters : [];
$lessonsByChapter = is_array($lessonsByChapter ?? null) ? $lessonsByChapter : [];
$docsByChapter = is_array($docsByChapter ?? null) ? $docsByChapter : [];
$docsByLesson = is_array($docsByLesson ?? null) ? $docsByLesson : [];
$meetings = is_array($meetings ?? null) ? $meetings : [];
$exams = is_array($exams ?? null) ? $exams : [];
$durationLabel = $durationLabel ?? null;
$videoPlaylist = is_array($videoPlaylist ?? null) ? $videoPlaylist : [];
$docTotals = is_array($docTotals ?? null) ? $docTotals : ['exercise' => 0, 'resource' => 0, 'exam' => 0, 'all' => 0];
$isGeneralAdmin = !empty($isGeneralAdmin);

$extractYoutubeId = isset($extractYoutubeId) && is_callable($extractYoutubeId) ? $extractYoutubeId : static function (string $url): ?string {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }
$host = strtolower((string) $parts['host']);
        if ($host === 'youtu.be') {
            return ltrim((string) ($parts['path'] ?? ''), '/');
        }
if (str_contains($host, 'youtube.com') && !empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            return is_string($query['v'] ?? null) ? $query['v'] : null;
        }
return null;
    };

$docLabel = isset($docLabel) && is_callable($docLabel) ? $docLabel : static function (string $type, string $mime): string {
        if ($type === 'exercise') {
            return 'Exercice';
        }
if ($type === 'exam') {
            return 'Examen';
        }
if ($type === 'resource') {
            if (is_string($mime) && stripos($mime, 'url_document') !== false) {
                return "Document d'accompagnement";
            }
if (is_string($mime) && stripos($mime, 'url_file') !== false) {
                return 'Fichier';
            }
        }
return 'Ressource';
    };

$formatCourseList = isset($formatCourseList) && is_callable($formatCourseList) ? $formatCourseList : static function ($value): string {
        return nl2br(e((string) $value));
    };

$previewMode = 'admin';
require __DIR__ . '/../shared/_course_preview_bootstrap.php';

$courseStatusTone = match ((string) ($course['status'] ?? '')) {
    'submitted' => 'pending',
    'rejected' => 'urgent',
    'approved' => 'active',
    default => 'done',
};

$adminNavActive = 'courses';
$adminTopTitle = 'Aperçu du cours';
$adminTopSubtitle = 'Modération';
$adminTopBadge = $courseStatusLabel;
$adminTopBadgeTone = $courseStatusTone;
$adminTopActions = [
    ['label' => 'Retour à la modération', 'url' => site_url('admin/courses?course_id=' . $courseId), 'tone' => 'neutral'],
    ['label' => 'Liste des cours', 'url' => site_url('admin/courses'), 'tone' => 'neutral'],
];
if ($isGeneralAdmin) {
    $adminTopActions[] = ['label' => 'Admins secondaires', 'url' => site_url('admin/secondary_admins'), 'tone' => 'neutral'];
}

$courseShortDescription = trim(strip_tags(html_entity_decode($courseDescription, ENT_QUOTES, 'UTF-8')));
if ($courseShortDescription === '') {
    $courseShortDescription = 'Aucune description n’a encore été renseignée pour ce cours.';
}
$courseSubmittedAt = trim((string) ($course['submitted_at'] ?? ''));
$courseSubmittedLabel = $courseSubmittedAt !== '' ? $formatDate($courseSubmittedAt) : 'Date de soumission non précisée';
$courseStatusPillClass = match ((string) ($course['status'] ?? '')) {
    'submitted' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
};
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aperçu du cours (administration) - <?php echo e($courseTitle); ?></title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin admin-course-preview-page min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/course_preview" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense w-full max-w-none space-y-6 px-4 py-5 sm:px-6 sm:py-6">
                <?php if ($errors !== []): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $error): ?>
                            <p>- <?php echo e((string) $error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="admin-shell-alert admin-shell-alert--success">
                        <?php echo e($success); ?>
                    </div>
                <?php endif; ?>

                <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 to-accent-600 text-white shadow-glow animate-fade-up">
                    <div class="grid gap-5 p-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,24rem)] lg:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="admin-preview-pill admin-preview-pill--status"><?php echo e($courseStatusLabel); ?></span>
                                <span class="admin-preview-pill admin-preview-pill--id">Cours #<?php echo e((string) $courseId); ?></span>
                            </div>
                            <h2 class="mt-4 text-2xl font-bold sm:text-3xl"><?php echo e($courseTitle); ?></h2>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-primary-100"><?php echo e($courseShortDescription); ?></p>
                            <div class="admin-preview-hero-meta mt-4 flex flex-wrap gap-2 text-xs">
                                <span class="admin-preview-pill admin-preview-pill--teacher"><i class="fas fa-chalkboard-teacher"></i><?php echo e($teacherName); ?></span>
                                <?php if ($categoryName !== ''): ?>
                                    <span class="admin-preview-pill admin-preview-pill--category"><i class="fas fa-tag"></i><?php echo e($categoryName); ?></span>
                                <?php endif; ?>
                                <span class="admin-preview-pill admin-preview-pill--level"><i class="fas fa-signal"></i><?php echo e($courseLevelLabel); ?></span>
                                <?php if (!is_null($durationLabel) && trim((string) $durationLabel) !== ''): ?>
                                    <span class="admin-preview-pill admin-preview-pill--duration"><i class="fas fa-hourglass-half"></i><?php echo e((string) $durationLabel); ?></span>
                                <?php endif; ?>
                                <span class="admin-preview-pill admin-preview-pill--price"><i class="fas fa-money-bill-wave"></i><?php echo e($formatMoney($coursePrice)); ?></span>
                            </div>
                        </div>
                        <div class="admin-preview-cover-card rounded-2xl border border-white/20 bg-white/10 p-3 backdrop-blur-sm">
                            <?php if ($coverUrl !== ''): ?>
                                <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" class="h-48 w-full rounded-xl object-cover">
                            <?php else: ?>
                                <div class="flex h-48 w-full items-center justify-center rounded-xl bg-white/15 text-4xl">
                                    <i class="fas fa-book-open"></i>
                                </div>
                            <?php endif; ?>
                            <p class="admin-preview-submitted mt-3 text-xs text-primary-100"><i class="far fa-calendar-alt mr-1"></i><?php echo e($courseSubmittedLabel); ?></p>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-5">
                    <article class="admin-preview-kpi admin-preview-kpi--chapters bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Chapitres</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $chapterCount); ?></p>
                    </article>
                    <article class="admin-preview-kpi admin-preview-kpi--lessons bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Leçons</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $lessonCount); ?></p>
                    </article>
                    <article class="admin-preview-kpi admin-preview-kpi--videos bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Vidéos</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $videoCount); ?></p>
                    </article>
                    <article class="admin-preview-kpi admin-preview-kpi--resources bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ressources</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $resourceCount); ?></p>
                    </article>
                    <article class="admin-preview-kpi admin-preview-kpi--status bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Statut</p>
                        <p class="mt-3"><span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo e($courseStatusPillClass); ?>"><?php echo e($courseStatusLabel); ?></span></p>
                    </article>
                </section>

                <?php if ((string) ($course['status'] ?? '') === 'submitted') : ?>
                    <section class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Décision rapide</p>
                                <h2 class="mt-1.5 text-lg font-bold text-gray-900 dark:text-white">Ce cours est en attente de validation</h2>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Approuvez ou rejetez avec un motif clair.</p>
                            </div>
                            <div class="flex w-full flex-col gap-3 xl:w-auto xl:min-w-[36rem]">
                                <form method="post" action="<?php echo e(site_url('admin/course_preview?id=' . $courseId)); ?>" class="flex flex-wrap items-center gap-3" data-confirm-message="Approuver ce cours et le publier sur la plateforme ?">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700"><i class="fas fa-check-circle mr-1"></i>Approuver le cours</button>
                                </form>
                                <form method="post" action="<?php echo e(site_url('admin/course_preview?id=' . $courseId)); ?>" class="flex flex-col gap-3 xl:flex-row xl:items-center">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="text" name="reason" required class="admin-shell-input flex-1" placeholder="Motif du rejet obligatoire">
                                    <button type="submit" class="rounded-xl bg-red-100 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50"><i class="fas fa-times-circle mr-1"></i>Rejeter le cours</button>
                                </form>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <div class="admin-course-preview-content space-y-6">
                    <?php require __DIR__ . '/../shared/_course_preview_overview.php'; ?>
                    <?php require __DIR__ . '/../shared/_course_preview_plan.php'; ?>
                    <?php require __DIR__ . '/../shared/_course_preview_meetings.php'; ?>
                    <?php require __DIR__ . '/../shared/_course_preview_exams.php'; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>


